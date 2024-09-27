<?php /** @noinspection PhpLanguageLevelInspection */

App::uses('WebzashAppController', 'Webzash.Controller');


use OfxParser\Parser;

class BulkController extends WebzashAppController
{

	public $uses = array('Webzash.LedgerKeyword', 'Webzash.Entry', 'Webzash.Group', 'Webzash.Ledger',
		'Webzash.Entrytype', 'Webzash.Entryitem', 'Webzash.Tag', 'Webzash.Log');

	/**
	 * Action to display upload form and process the uploaded OFX file
	 *
	 * @return void
	 */
	public function upload()
	{
		if ($this->request->is('post')) {
			// Check if a file is uploaded
			if (!empty($this->request->data['Bulk']['file']['tmp_name'])) {
				// Load the OFX parser library
				App::import('Vendor', 'OfxParser', array('file' => 'OfxParser/vendor/autoload.php'));

				// Parse the OFX file
				$ofxParser = new \OfxParser\Parser();
				try {
					$ofx = $ofxParser->loadFromFile($this->request->data['Bulk']['file']['tmp_name']);
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
					break;
				default:
					$entrytypeLabel = 'receipt';
					$debit_or_credit = 'D';
					$dr_amount = number_format(abs($transaction->amount), 2, '.', '');
					$cr_amount = 0.00;
					break;
			}

			$entrytype = $this->Entrytype->find('first', array(
				'conditions' => array(
					'Entrytype.label' => $entrytypeLabel)));

			$narration = '';
			if (isset($transaction->name)) {
				if (is_array($transaction->name)) {
					// Convert array to string
					$transaction->name = implode(', ', $transaction->name);
				}
				$narration = $transaction->name;
			}
			if (isset($transaction->memo)) {
				if (is_array($transaction->memo)) {
					$transaction->memo = implode(', ', $transaction->memo);
				}
				$narration .= $transaction->memo;
			}

			$entrydata = null;
			$entrydata['Entry']['number'] = $this->Entry->nextNumber($entrytype['Entrytype']['id']);
			$entrydata['Entry']['entrytype_id'] = (string) $entrytype['Entrytype']['id'];
			$entrydata['Entry']['tag_id'] = null;
			$entrydata['Entry']['narration'] = (string) $narration;
			$entrydata['Entry']['date'] = $transaction->date->format('Y-m-d');

			$curEntryitems[0] = array(
				"ledger_id" => (string) $bank_account_ledger_id,
				"dc" => $debit_or_credit,
				"dr_amount" => $dr_amount,
				"cr_amount" => $cr_amount
			);

			// BALANCING TRANSACTION- (used to have ledger_id (string), but got rid of it. see if it works still!
			$transactionAmount = max($dr_amount, $cr_amount);

			// Check for duplicate before proceeding
			if ($this->isDuplicateEntry($narration, $entrydata['Entry']['date'] , $transactionAmount, $entrydata['Entry']['entrytype_id'], $debit_or_credit)) {
				// If a duplicate is found, skip this transaction
				$this->Session->setFlash(__d('webzash', 'Skipped Duplicate Entry!'), 'danger');
				continue;
			}

			$opposite_dc = ($debit_or_credit == 'D') ? 'C' : 'D';
			$curEntryitems[1] = array(
				"ledger_id" => $this->setBalancingLedgerId($entrydata['Entry']['narration'], $transactionAmount, $entrytypeLabel, $debit_or_credit, $keyword = 'UNALLOCATED'),
				"dc" => $opposite_dc,
				"dr_amount" => $cr_amount,
				"cr_amount" => $dr_amount
			);

			$dc_valid = $this->validate_ledger_restrictions($curEntryitems, $entrytype);
			if (!$dc_valid) {
				return false;
			}

			$success = $this->checkEquality($curEntryitems, $entrydata);
			if (!$success) {
//				 Instead of breaking entire operation, just skip the transaction.
				continue;
//				return false;
			}

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

	/**
	 * @param $keyword
	 * @return mixed
	 */
	public function setDefaultBalancingLedgerId($keyword = 'UNALLOCATED') {
		$ledger_id = $this->Ledger->findLedgerIdByNameKeyword($keyword);
		if (!$ledger_id) {
			// try again and create it if not exists
			$ledger_id = $this->Ledger->getUnallocatedLedgerId();
		}
			return $ledger_id;
	}

	/**
	 * @param $narration
	 * @param $transactionAmount
	 * @param $transactionType
	 * @param $debit_or_credit
	 * @param $keyword
	 * @return false
	 */
	public function setBalancingLedgerId($narration, $transactionAmount = null, $transactionType = null, $debit_or_credit = null, $keyword = 'UNALLOCATED') {
		// Determine ledger_id based on narration
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


	/**
	 * Check if an entry with the same narration, date, amount, and entry type already exists.
	 *
	 * @param string $narration
	 * @param string $date
	 * @param float $amount
	 * @param int $entrytype_id
	 * @param string $dc
	 * @return bool
	 */
	private function isDuplicateEntry($narration, $date, $amount, $entrytype_id, $dc)
	{
		$conditions = array(
			'Entry.narration' => $narration,
			'Entry.date' => $date,
			'Entry.entrytype_id' => $entrytype_id,
			'Entryitem.amount' => $amount,
			'Entryitem.dc' => $dc
		);

		$existingEntry = $this->Entry->find('first', array(
			'conditions' => $conditions,
			'joins' => array(
				array(
					'table' => 'entryitems',
					'alias' => 'Entryitem',
					'type' => 'INNER',
					'conditions' => array(
						'Entryitem.entry_id = Entry.id'
					)
				)
			),
			'recursive' => -1
		));

		return !empty($existingEntry);
	}





//	private function isDuplicateEntry($entrydata, $curEntryitems, $entrytypeLabel)
//	{
//		$conditions = array(
//			'Entry.narration' => $entrydata['Entry']['narration'],
//			'Entry.date' => $entrydata['Entry']['date'],
//			'Entrytype.label' => $entrytypeLabel,
//			'OR' => array(
//				array('Entryitem.dr_amount' => $curEntryitems[0]["dr_amount"]),
//				array('Entryitem.cr_amount' => $curEntryitems[0]["cr_amount"])
//			)
//		);
//
//		$existingEntry = $this->Entry->find('first', array(
//			'conditions' => $conditions,
//			'joins' => array(
//				array(
//					'table' => 'entryitems',
//					'alias' => 'Entryitem',
//					'type' => 'INNER',
//					'conditions' => array(
//						'Entryitem.entry_id = Entry.id'
//					)
//				)
//			)
//		));
//
//		return !empty($existingEntry);
//	}



}
