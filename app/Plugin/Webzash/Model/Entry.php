<?php
/**
 * The MIT License (MIT)
 *
 * Webzash - Easy to use web based double entry accounting software
 *
 * Copyright (c) 2014 Prashant Shah <pshah.mumbai@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

App::uses('WebzashAppModel', 'Webzash.Model');

/**
 * Webzash Plugin Entry Model
 *
 * @package Webzash
 * @subpackage Webzash.Model
 */
class Entry extends WebzashAppModel {

	public $validationDomain = 'webzash';

	/* Validation rules for the Entry table */
	public $validate = array(
		'tag_id' => array(
			'rule1' => array(
				'rule' => 'numeric',
				'message' => 'Tag id is not a valid number',
				'required' => true,
				'allowEmpty' => true,
			),
			'rule2' => array(
				'rule' => array('maxLength', 18),
				'message' => 'Tag id length cannot be more than 18',
				'required' => true,
				'allowEmpty' => true,
			),
			'rule3' => array(
				'rule' => 'validTag',
				'message' => 'Tag id is not valid',
				'required' => true,
				'allowEmpty' => true,
			),
		),
		'entrytype_id' => array(
			'rule1' => array(
				'rule' => 'notBlank',
				'message' => 'Entry type cannot be empty',
				'required' => true,
				'allowEmpty' => false,
			),
			'rule2' => array(
				'rule' => 'numeric',
				'message' => 'Entry type is not a valid number',
				'required' => true,
				'allowEmpty' => false,
			),
			'rule3' => array(
				'rule' => array('maxLength', 18),
				'message' => 'Entry type length cannot be more than 18',
				'required' => true,
				'allowEmpty' => false,
			),
			'rule4' => array(
				'rule' => 'validEntrytype',
				'message' => 'Entry type is not valid',
				'required' => true,
				'allowEmpty' => false,
			),
		),
		'number' => array(
			'rule1' => array(
				'rule' => 'numeric',
				'message' => 'Entry number is not a valid number',
				'required' => true,
				'allowEmpty' => true,
			),
			'rule2' => array(
				'rule' => array('maxLength', 18),
				'message' => 'Entry number length cannot be more than 18',
				'required' => true,
				'allowEmpty' => true,
			),
			'rule3' => array(
				'rule' => 'isUniqueEntryNumber',
				'message' => 'Entry number already exists',
				'required' => true,
				'allowEmpty' => true,
			),
		),
		'date' => array(
			'rule1' => array(
				'rule' => 'fullDateTime',
				'message' => 'Invalid value for entry date',
				'required' => true,
				'allowEmpty' => false,
			),
			'rule2' => array(
				'rule' => 'afterStart',
				'message' => 'Entry date should be after financial year start',
				'required' => true,
				'allowEmpty' => false,
			),
			'rule3' => array(
				'rule' => 'beforeEnd',
				'message' => 'Entry date should be before financial year end',
				'required' => true,
				'allowEmpty' => false,
			),
		),
		'dr_total' => array(
			'rule1' => array(
				'rule' => 'notBlank',
				'message' => 'Debit total cannot be empty',
				'required' => true,
				'allowEmpty' => false,
			),
			'rule2' => array(
				'rule' => 'isAmount',
				'message' => 'Debit total is not a valid amount',
				'required' => true,
				'allowEmpty' => false,
			),
			'rule3' => array(
				'rule' => array('maxLength', 28),
				'message' => 'Debit total length cannot be more than 28',
				'required' => true,
				'allowEmpty' => false,
			),
			'rule4' => array(
				'rule' => 'isPositive',
				'message' => 'Debit total cannot be less than 0.00',
				'required' => true,
				'allowEmpty' => false,
			),
		),
		'cr_total' => array(
			'rule1' => array(
				'rule' => 'notBlank',
				'message' => 'Credit total cannot be empty',
				'required' => true,
				'allowEmpty' => false,
			),
			'rule2' => array(
				'rule' => 'isAmount',
				'message' => 'Credit total is not a valid amount',
				'required' => true,
				'allowEmpty' => false,
			),
			'rule3' => array(
				'rule' => array('maxLength', 28),
				'message' => 'Credit total length cannot be more than 28',
				'required' => true,
				'allowEmpty' => false,
			),
			'rule4' => array(
				'rule' => 'isPositive',
				'message' => 'Credit total cannot be less than 0.00',
				'required' => true,
				'allowEmpty' => false,
			),
			'rule5' => array(
				'rule' => 'isEqualDrTotal',
				'message' => 'Credit total is not equal to debit total',
				'required' => true,
				'allowEmpty' => false,
			),
		),
		'narration' => array(
		),
	);

/**
 * Validation - Check if entry type is valid
 */
	public function validEntrytype($data) {
		$values = array_values($data);
		if (!isset($values)) {
			return false;
		}
		$value = $values[0];

		/* Load the Entrytype model */
		App::import("Webzash.Model", "Entrytype");
		$Entrytype = new Entrytype();

		if ($Entrytype->exists($value)) {
			return true;
		} else {
			return false;
		}
	}

/**
 * Validation - Check if entry number is unique within the entry type
 */
	public function isUniqueEntryNumber($data) {
		$values = array_values($data);
		if (!isset($values)) {
			return false;
		}
		$value = $values[0];

		/* Check if any entry number exists within the same entry type */
		if (empty($this->data['Entry']['id'])) {
			/* On create if id is not set */
			$count = $this->find('count', array(
				'conditions' => array(
					'Entry.number' => $value,
					'Entry.entrytype_id' => $this->data['Entry']['entrytype_id'],
				),
			));
		} else {
			/* On update if id is set */
			$count = $this->find('count', array(
				'conditions' => array(
					'Entry.id !=' => $this->data['Entry']['id'],
					'Entry.number' => $value,
					'Entry.entrytype_id' => $this->data['Entry']['entrytype_id'],
				),
			));
		}

		if ($count != 0) {
			return false;
		} else {
			return true;
		}
	}

/**
 * Validation - Check if tag_id is a valid id
 */
	public function validTag($data) {
		$values = array_values($data);
		if (!isset($values)) {
			return false;
		}
		$value = $values[0];

		/* Load the Tag model */
		App::import("Webzash.Model", "Tag");
		$Tag = new Tag();

		if ($Tag->exists($value)) {
			return true;
		} else {
			return false;
		}
	}

/**
 * Validation - Check if value is a proper decimal number with 2 decimal places
 */
	public function isAmount($data) {
		$values = array_values($data);
		if (!isset($values)) {
			return false;
		}
		$value = $values[0];
		if (preg_match('/^[0-9]{0,23}+(\.[0-9]{0,' . Configure::read('Account.decimal_places') . '})?$/', $value)) {
			return true;
		} else {
			return false;
		}
	}

/**
 * Validation - Check if value is a positive value
 */
	public function isPositive($data) {
		$values = array_values($data);
		if (!isset($values)) {
			return false;
		}
		$value = $values[0];
		if ($value >= 0.00) {
			return true;
		} else {
			return false;
		}
	}

/**
 * Validation - Check if debit total and credit total are equal
 */
	public function isEqualDrTotal($data) {
		$values = array_values($data);
		if (!isset($values)) {
			return false;
		}

		$crvalue = $values[0];
		$drvalue = $this->data['Entry']['dr_total'];

		if ($drvalue == $crvalue) {
			return true;
		} else {
			return false;
		}
	}

/**
 * Validation - Check if valid datetime
 */
	public function fullDateTime($data) {
		$values = array_values($data);
		if (!isset($values)) {
			return false;
		}
		$value = $values[0];

		$unixtime = strtotime($value . ' 00:00:00');

		if ($unixtime !== FALSE) {
			return true;
		} else {
			return false;
		}
	}

/**
 * Validation - Check if entry date is after financial year start
 */
	public function afterStart($data) {
		$values = array_values($data);
		if (!isset($values)) {
			return false;
		}
		$value = $values[0];

		$startdate = strtotime(Configure::read('Account.startdate') . ' 00:00:00');
		$entrydate = strtotime($value . ' 00:00:00');

		if ($startdate <= $entrydate) {
			return true;
		} else {
			return false;
		}
	}

/**
 * Validation - Check if entry date is before financial year end
 */
	public function beforeEnd($data) {
		$values = array_values($data);
		if (!isset($values)) {
			return false;
		}
		$value = $values[0];

		$enddate = strtotime(Configure::read('Account.enddate') . ' 00:00:00');
		$entrydate = strtotime($value . ' 00:00:00');

		if ($enddate >= $entrydate) {
			return true;
		} else {
			return false;
		}
	}

/**
 * Calculate the next number for a entry based on entry type
 */
	public function nextNumber($id)	{
		$max = $this->find('first', array(
			'conditions' => array('Entry.entrytype_id' => $id),
			'fields' => array('MAX(Entry.number) AS max'),
		));
		if (empty($max[0]['max'])) {
			$maxNumber = 0;
		} else {
			$maxNumber = $max[0]['max'];
		}
		return $maxNumber + 1;
	}

/**
 * Show the entry ledger details
 */
	public function entryLedgers($id, $entryitem_id = 0) {
		/* Load the Entryitem model */
		App::import("Webzash.Model", "Entryitem");
		$Entryitem = new Entryitem();

		/* Load the Ledger model */
		App::import("Webzash.Model", "Ledger");
		$Ledger = new Ledger();

		$rawentryitems = $Entryitem->find('all', array(
			'conditions' => array('Entryitem.entry_id' => $id),
		));

		/* Initialize */
		$ledgerstr = '';

		/* Get the debit and credit ledgers */
		foreach ($rawentryitems as $row => $entryitem) {
			/* Get ledger name */
			$ledger_id = $entryitem['Entryitem']['ledger_id'];
			$ledger_name = $Ledger->getName($ledger_id);

			/* Check if the current ledger is the one that is active */
			if ($entryitem['Entryitem']['id'] == $entryitem_id) {
				$ledgerstr .= '<span class="bold-text green">';
			}

			/* Add the ledger name */
			if ($entryitem['Entryitem']['dc'] == 'D') {
				if (CakeSession::read('Wzsetting.drcr_toby') == 'toby') {
					$ledgerstr .= 'By ' . h($ledger_name);
				} else {
					$ledgerstr .= 'Dr ' . h($ledger_name);
				}
				/* Add the amount */
				$ledgerstr .=  ' <span class="text-extra-small">(Dr ' . $entryitem['Entryitem']['amount'] . ')</span>';
			} elseif ($entryitem['Entryitem']['dc'] == 'C') {
				if (CakeSession::read('Wzsetting.drcr_toby') == 'toby') {
					$ledgerstr .= 'To ' . h($ledger_name);
				} else {
					$ledgerstr .= 'Cr ' . h($ledger_name);
				}
				$ledgerstr .=  ' <span class="text-extra-small">(Cr ' . $entryitem['Entryitem']['amount'] . ')</span>';
			}

			$ledgerstr .= "<br/>";

			/* Check if the current ledger is the one that is active */
			if ($entryitem['Entryitem']['id'] == $entryitem_id) {
				$ledgerstr .= "</span>";
			}
		}

		return $ledgerstr;
	}

/**
 * Show the entry ledger details
 */
	public function entryLedgersReport($id, $entryitem_id = 0, $report_type = 'csv') {
		/* Load the Entryitem model */
		App::import("Webzash.Model", "Entryitem");
		$Entryitem = new Entryitem();

		/* Load the Ledger model */
		App::import("Webzash.Model", "Ledger");
		$Ledger = new Ledger();

		$rawentryitems = $Entryitem->find('all', array(
			'conditions' => array('Entryitem.entry_id' => $id),
		));

		/* Initialize */
		$ledgerstr = '';

		/* Get the debit and credit ledgers */
		foreach ($rawentryitems as $row => $entryitem) {
			/* Get ledger name */
			$ledger_id = $entryitem['Entryitem']['ledger_id'];
			$ledger_name = $Ledger->getName($ledger_id);

			/* Add the ledger name */
			if ($entryitem['Entryitem']['dc'] == 'D') {
				if (CakeSession::read('Wzsetting.drcr_toby') == 'toby') {
					$ledgerstr .= 'By ' . h($ledger_name) . ' / ';
				} else {
					$ledgerstr .= 'Dr ' . h($ledger_name) . ' / ';
				}
			} elseif ($entryitem['Entryitem']['dc'] == 'C') {
				if (CakeSession::read('Wzsetting.drcr_toby') == 'toby') {
					$ledgerstr .= 'To ' . h($ledger_name) . ' / ';
				} else {
					$ledgerstr .= 'Cr ' . h($ledger_name) . ' / ';
				}
			}

		}

		// Return after removing the last 3 char i.e. ' / ' from string
		return substr($ledgerstr, 0, -3);
	}



//DT CREATED
	public function createEntry($entryData, $entryItemsData) {
		$ds = $this->getDataSource();
		$ds->begin();

		$this->create();
		if ($this->save($entryData)) {
			foreach ($entryItemsData as $itemData) {
				$this->Entryitem->create();
				$itemData['entry_id'] = $this->id;
				if (!$this->Entryitem->save($itemData)) {
					$ds->rollback();
					return false;
				}
			}
			$ds->commit();
			return true;
		} else {
			$ds->rollback();
			return false;
		}
	}

	public function isDuplicate($transaction) {
		$conditions = [
			'Entry.type' => $transaction['type'],
			'Entry.amount' => $transaction['amount'],
			'Entry.memo' => $transaction['memo'],
			'Entry.name' => $transaction['name'],
		];
		return $this->hasAny($conditions);
	}




}
