<?php /** @noinspection PhpLanguageLevelInspection */

App::uses('WebzashAppController', 'Webzash.Controller');


use OfxParser\Parser;

class OfxController extends WebzashAppController
{

	public $uses = array('Webzash.LedgerKeyword', 'Webzash.Entry', 'Webzash.Group', 'Webzash.Ledger',
		'Webzash.Entrytype', 'Webzash.Entryitem', 'Webzash.Tag', 'Webzash.Log');


	// Action to display upload form and process the uploaded OFX file
	public function upload()
	{
		if ($this->request->is('post')) {
			// Check if a file is uploaded
			if (!empty($this->request->data['Ofx']['file']['tmp_name'])) {
				// Load the OFX parser library
				App::import('Vendor', 'OfxParser', array('file' => 'OfxParser/vendor/autoload.php'));

				// Parse the OFX file
				$ofxParser = new \OfxParser\Parser();
				try {
					$ofx = $ofxParser->loadFromFile($this->request->data['Ofx']['file']['tmp_name']);
					// Extract data from OFX file (e.g., transactions)
					$transactions = $ofx->bankAccounts[0]->statement->transactions;
					$this->set('transactions', $transactions);
					if (isset($ofx->bankAccounts[0]->accountNumber)) {
						$bank_account_number = (string) $ofx->bankAccounts[0]->accountNumber;
					} else {
						$bank_account_number = 'Account number not available';
					}

					// Process transactions
					$success = $this->processTransactions($transactions, $bank_account_number);

					if (!$success) {
						$this->Session->setFlash(__d('webzash', 'Failed to save entry ledgers. Error!'), 'danger');
						// eturn something??
					}

				} catch (Exception $e) {
					$this->Session->setFlash(__('Error processing OFX file: ' . $e->getMessage()));
				}
			}
		}
	}

	//Need this datatype to go into curEntryItems:	(all strings) - Will need to map bank act somehow eventurally
	//$curEntryitems[0]["ledger_id"] = "1"
	//$curEntryitems[0]["dc"]
	//$curEntryitems[0]["dr_amount"]
	//$curEntryitems[0]["cr_amount"]

	/**
	 * @param $transactions
	 * @param $bank_account_number
	 * @return bool
	 */
	private function processTransactions($transactions, $bank_account_number)
	{
		// Loop through transactions and add them as entries
		foreach ($transactions as $transaction) {
			$bank_last_four = substr($bank_account_number, -4);
			$bank_account_ledger_id = $this->Ledger->findLedgerIdByNameKeyword($bank_last_four);
			if (!$bank_account_ledger_id) {
				$bank_account_ledger_id = "1";
			}

			//  logic to figure out if it's a Debit or Credit!!
			// NEED TO MATCH ENTRYTYPE: "payment",
			//Depending on Debit or Credit it'll be 'payment' or 'receive(or something)''
			switch ($transaction->type) {
				case  'DEBIT':
				case 'FEE':
				case 'SRVCHG':
				case 'OTHER':
				case 'XFER':
					$entrytypeLabel = 'payment';
					$debit_or_credit = 'C';
					$dr_amount = 0.00;
					$cr_amount = number_format(abs($transaction->amount), 2, '.', '');
				// debit_or_credit helps determine next part: Empty is "" not null or 0 (But I did it alternatively anyways)
				//	$dr_amount = "";
				//	$cr_amount = abs($transaction->amount);
					break;
				default:
					$entrytypeLabel = 'receipt';
					$debit_or_credit = 'D';
					$dr_amount = number_format(abs($transaction->amount), 2, '.', '');
					$cr_amount = 0.00;
					//	$dr_amount = abs($transaction->amount);
					//	$cr_amount = "";
					break;
			}

			$entrytype = $this->Entrytype->find('first', array(
				'conditions' => array(
					'Entrytype.label' => $entrytypeLabel)));

			$entrydata = null;
			$entrydata['Entry']['number'] = $this->Entry->nextNumber($entrytype['Entrytype']['id']);
			$entrydata['Entry']['entrytype_id'] = (string) $entrytype['Entrytype']['id'];
			$entrydata['Entry']['tag_id'] = null;
			$entrydata['Entry']['narration'] = (string) $transaction->name . $transaction->memo;
			$entrydata['Entry']['date'] = $transaction->date->format('Y-m-d');

			$curEntryitems[0] = array(
				"ledger_id" => (string) $bank_account_ledger_id,
				"dc" => $debit_or_credit,
				"dr_amount" => $dr_amount,
				"cr_amount" => $cr_amount
			);

	    	// BALANCING TRANSACTION- (used to have ledger_id (string), but got rid of it. see if it works still!
			$transactionAmount = max($dr_amount, $cr_amount);


			$opposite_dc = ($debit_or_credit == 'D') ? 'C' : 'D';
			$curEntryitems[1] = array(
				"ledger_id" => $this->setBalancingLedgerId($entrydata['Entry']['narration'], $transactionAmount, $entrytypeLabel, $debit_or_credit, $keyword = 'UNALLOCATED'),
				"dc" => $opposite_dc,
				"dr_amount" => $cr_amount,
				"cr_amount" => $dr_amount
			);

			// DONT THINK I NEED THIS?
			//$this->set('curEntryitems', $curEntryitems);
			$dc_valid = $this->validate_ledger_restrictions($curEntryitems, $entrytype);
			if (!$dc_valid) {
				return false;
			}

			$err = $this->checkEquality($curEntryitems, $entrydata);
			if (!$err) {
				return false;
			}

			// THIS IS SORTA REDUNDANT HERE!
			/* Add item to entryitemdata array if everything is ok */
			$entryitemdata = array();
			foreach ($curEntryitems as $row => $entryitem) {
				if ($entryitem['ledger_id'] <= 0) {
					continue;
				}
				if ($entryitem['dc'] == 'D') {
					$entryitemdata[] = array(
						'Entryitem' => array(
							'dc' => $entryitem['dc'],
							'ledger_id' => $entryitem['ledger_id'],
							'amount' => $entryitem['dr_amount'],
						)
					);
				} else {
					$entryitemdata[] = array(
						'Entryitem' => array(
							'dc' => $entryitem['dc'],
							'ledger_id' => $entryitem['ledger_id'],
							'amount' => $entryitem['cr_amount'],
						)
					);
				}
			}

	    	$this->saveEntry($entrydata, $entryitemdata, $entrytype);
		}
		return true;
	}


	/* Check ledger restriction. I also skipped the check for equal balances on both sides!*/
	/**
	 * @param $entry_items
	 * @param $entrytype
	 * @return bool
	 */
	public function validate_ledger_restrictions($entry_items, $entrytype): bool
	{

		$dc_valid = false;
		foreach ($entry_items as $entryitem) {
			if ($entryitem['ledger_id'] <= 0) {
				continue;
			}
			$ledger = $this->Ledger->findById($entryitem['ledger_id']);
			if (!$ledger) {
				// 'Invalid ledger selected.
				return $dc_valid;
			}

			if ($entrytype['Entrytype']['restriction_bankcash'] == 4) {
				if ($ledger['Ledger']['type'] != 1) {
					// 'Only bank or cash ledgers are allowed for this entry type.
					return $dc_valid;
				}
			}
			if ($entrytype['Entrytype']['restriction_bankcash'] == 5) {
				if ($ledger['Ledger']['type'] == 1) {
					//'Bank or cash ledgers are not allowed for this entry type.'), 'danger');
					return $dc_valid;
				}
			}

			if ($entryitem['dc'] == 'D') {
				if ($entrytype['Entrytype']['restriction_bankcash'] == 2) {
					if ($ledger['Ledger']['type'] == 1) {
						$dc_valid = true;
					}
				}
			} else if ($entryitem['dc'] == 'C') {
				if ($entrytype['Entrytype']['restriction_bankcash'] == 3) {
					if ($ledger['Ledger']['type'] == 1) {
						$dc_valid = true;
					}
				}
			}
		}

		return $dc_valid;
	}


	/**
	 * @param $entrydata
	 * @param $entryitemdata
	 * @param $entrytype
	 * @return bool
	 */
	private function saveEntry($entrydata, $entryitemdata, $entrytype)
{
	/* Save entry */
	$ds = $this->Entry->getDataSource();
	$ds->begin();

	$this->Entry->create();
	if ($this->Entry->save($entrydata)) {
		/* Save entry items */
		foreach ($entryitemdata as $itemdata) {
			$itemdata['Entryitem']['entry_id'] = (string) $this->Entry->id;
			$this->Entryitem->create();
			if (!$this->Entryitem->save($itemdata)) {
				foreach ($this->Entryitem->validationErrors as $field => $msg) {
					$errmsg = $msg[0];
					break;
				}
				$ds->rollback();
				$this->Session->setFlash(__d('webzash', 'Failed to save entry ledgers. Error is : %s', $errmsg), 'danger');
				return false;
			}
		}

		$tempentry = $this->Entry->read(null, $this->Entry->id);
		if (!$tempentry) {
			$this->Session->setFlash(__d('webzash', 'Oh snap ! Failed to create entry. Please, try again.'), 'danger');
			$ds->rollback();
			return false;
		}
		$entryNumber = h(toEntryNumber(
			$tempentry['Entry']['number'],
			$entrytype
		));

		$this->Log->add('Added ' . $entrytype['Entrytype']['name'] . ' entry numbered ' . $entryNumber, 1);
		$ds->commit();

		$this->Session->setFlash(__d('webzash',
			'%s entry numbered "%s" created.',
			$entrytype['Entrytype']['name'],
			$entryNumber), 'success');

		return true;
	} else {
		$ds->rollback();
		$this->Session->setFlash(__d('webzash', 'Failed to create entry. Please, try again.'), 'danger');
		return false;
	}

	}


	/**
	 * @param $curEntryitems
	 * @param $entrydata
	 * @return bool
	 */
	public function checkEquality($curEntryitems, &$entrydata) : bool {
		/* Check equality of debit and credit total */
		$dr_total = 0;
		$cr_total = 0;
		foreach ($curEntryitems as $entryitem) {
			if ($entryitem['ledger_id'] <= 0) {
				continue;
			}

			if ($entryitem['dc'] == 'D') {
				if ($entryitem['dr_amount'] <= 0) {
					$this->Session->setFlash(__d('webzash', 'Invalid amount specified. Amount cannot be negative or zero.'), 'danger');
					return false;
				}
				if (countDecimal($entryitem['dr_amount']) > Configure::read('Account.decimal_places')) {
					$this->Session->setFlash(__d('webzash', 'Invalid amount specified. Maximum %s decimal places allowed.', Configure::read('Account.decimal_places')), 'danger');
					return false;
				}
				$dr_total = calculate($dr_total, $entryitem['dr_amount'], '+');
			} else if ($entryitem['dc'] == 'C') {
				if ($entryitem['cr_amount'] <= 0) {
					$this->Session->setFlash(__d('webzash', 'Invalid amount specified. Amount cannot be negative or zero.'), 'danger');
					return false;
				}
				if (countDecimal($entryitem['cr_amount']) > Configure::read('Account.decimal_places')) {
					$this->Session->setFlash(__d('webzash', 'Invalid amount specified. Maximum %s decimal places allowed.', Configure::read('Account.decimal_places')), 'danger');
					return false;
				}
				$cr_total = calculate($cr_total, $entryitem['cr_amount'], '+');
			} else {
				$this->Session->setFlash(__d('webzash', 'Invalid Dr/Cr option selected.'), 'danger');
				return false;
			}
		}
		if (calculate($dr_total, $cr_total, '!=')) {
			$this->Session->setFlash(__d('webzash', 'Debit and Credit total do not match.'), 'danger');
			return false;
		}

		$entrydata['Entry']['dr_total'] = $dr_total;
		$entrydata['Entry']['cr_total'] = $cr_total;

		return true;
	}


// MOVED TO STATIC ON LEDGERKEYWORDS
//	private function getLedgerIdByLedgerKeywordsMapping($narration, $transactionAmount = null, $transactionType = null, $debit_or_credit = null) {
//		// Fetch all keyword mappings ordered by priority descending
//		$ledgerKeywords = $this->LedgerKeyword->find('all', array(
//			'order' => array('LedgerKeyword.priority' => 'DESC')
//		));
//
//		foreach ($ledgerKeywords as $mapping) {
//			$keyword = $mapping['LedgerKeyword']['keyword'];
//			$amountCondition = $mapping['LedgerKeyword']['amount'];
//			$typeCondition = $mapping['LedgerKeyword']['transaction_type'];
//
//			// Check if keyword is found in narration (case-insensitive)
//			if (stripos($narration, $keyword) !== false) {
//
//				// Check optional amount condition (if set) for greater, less, or equal
//				if ($amountCondition !== null && $transactionAmount !== null) {
//					if ($transactionAmount < $amountCondition) {
//						continue; // Skip if the amount is less than expected
//					}
//				}
//
//				// Check optional transaction type condition if set
//				if ($typeCondition !== null && $transactionType !== null) {
//					if (strcasecmp($transactionType, $typeCondition) !== 0) {
//						continue; // Skip if the transaction type doesn't match
//					}
//				}
//
//				// Check optional debit_or_credit condition if set
//				if ($mapping['LedgerKeyword']['debit_or_credit'] !== null && $debit_or_credit !== null) {
//					if (strcasecmp($mapping['LedgerKeyword']['debit_or_credit'], $debit_or_credit) !== 0) {
//						continue; // Skip if debit_or_credit doesn't match
//					}
//				}
//
//				// All conditions match, return the ledger_id
//				return $mapping['LedgerKeyword']['ledger_id'];
//			}
//		}
//
//		// No matching keyword found
//		return null;
//	}





	public function setDefaultBalancingLedgerId($keyword = 'UNALLOCATED') {
		$ledger_id = $this->Ledger->findLedgerIdByNameKeyword($keyword);
		if (!$ledger_id) {
			// try again and create it if not exists
			$ledger_id = $this->Ledger->getUnallocatedLedgerId();
		}
			return $ledger_id;
	}

	public function setBalancingLedgerId($narration, $transactionAmount = null, $transactionType = null, $debit_or_credit = null, $keyword = 'UNALLOCATED') {
		// Determine ledger_id based on narration
//		$balancing_entry_ledger_id = $this->getLedgerIdByLedgerKeywordsMapping($narration, $transactionAmount = null, $transactionType = null);
		$balancing_entry_ledger_id = $this->LedgerKeyword->getLedgerIdByLedgerKeywordsMapping($narration, $transactionAmount = null, $transactionType = null, $debit_or_credit = null);

		if (!$balancing_entry_ledger_id) {
			$balancing_entry_ledger_id = $this->setDefaultBalancingLedgerId($keyword);
		}
		if (!$balancing_entry_ledger_id) {
			return false;
		}
		//MAKES SURE VALID ACCOUNT IS USED.
		$ledger = $this->Ledger->findById($balancing_entry_ledger_id);
		if (!$ledger) {
			$this->Session->setFlash(__d('webzash', 'Invalid ledger selected.'), 'danger');
			return false;
		}
		return $balancing_entry_ledger_id;
	}

//
//
//// In Controller/OfxController.php
//
//public function upload($transactions) {
//	// Existing code to handle file upload and parsing...
//
//	foreach ($transactions as $transaction) {
//		$transactionData = [
//			'type' => $transaction->type,
//			'amount' => $transaction->amount,
//			'memo' => $transaction->memo,
//			'name' => $transaction->name,
//			'date' => $transaction->date->format('Y-m-d'),
//		];
//
//		// Check for duplicates
//		if ($this->Entry->isDuplicate($transactionData)) {
//			continue; // Skip this transaction
//		}
//
//		// Prepare data for saving
//		$entryData = [
//			'Entry' => [
//				'entrytype_id' => $this->determineEntryType($transactionData['type']),
//				'date' => $transactionData['date'],
//				'narration' => $transactionData['memo'],
//				// Add other necessary fields...
//			],
//		];
//
//		$entryItemsData = [
//			[
//				'dc' => $this->determineDC($transactionData['type']),
//				'ledger_id' => $this->determineLedgerId($transactionData),
//				'amount' => $transactionData['amount'],
//			],
//			// Add balancing entry item...
//		];
//
//		// Save the entry
//		if (!$this->Entry->createEntry($entryData, $entryItemsData)) {
//			$this->Session->setFlash(__('Failed to save transaction.'), 'danger');
//		}
//	}
//
//	// Set success message or handle errors as needed


}
