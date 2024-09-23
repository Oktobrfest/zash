<?php

App::uses('WebzashAppModel', 'Webzash.Model');

class LedgerKeyword extends WebzashAppModel {

	public $validationDomain = 'webzash';

	public $validate = array(
		'keyword' => array(
			'rule1' => array(
				'rule' => 'notBlank',
				'message' => 'Keyword cannot be empty',
				'required' => true,
				'allowEmpty' => false,
			),
			'rule2' => array(
				'rule' => array('maxLength', 255),
				'message' => 'Keyword cannot be more than 255 characters',
				'required' => true,
				'allowEmpty' => false,
			),
		),
		'ledger_id' => array(
			'rule1' => array(
				'rule' => 'notBlank',
				'message' => 'Ledger ID cannot be empty',
				'required' => true,
				'allowEmpty' => false,
			),
			'rule2' => array(
				'rule' => 'numeric',
				'message' => 'Ledger ID must be a number',
				'required' => true,
				'allowEmpty' => false,
			),
		),
		'amount_condition' => array(
			'valid' => array(
				'rule' => array('inList', array('gt', 'lt', 'eq')),
				'message' => 'Please enter a valid amount condition (gt, lt, eq)',
				'allowEmpty' => true,
			),
		),
		'debit_or_credit' => array(
			'valid' => array(
				'rule' => array('inList', array('D', 'C')),
				'message' => 'Please enter a valid debit or credit indicator (D or C)',
				'allowEmpty' => true,
			),
		),
	);

	/**
	 * Retrieves the Ledger ID based on narration and optional conditions. AFDDF
	 *
	 * @param string $narration The narration text to search for keywords.
	 * @param float|null $transactionAmount The amount of the transaction.
	 * @param string|null $transactionType The type of the transaction (e.g., 'payment', 'receipt').
	 * @param string|null $debit_or_credit The debit or credit indicator ('D' or 'C').
	 * @return int|null The corresponding Ledger ID if a match is found; otherwise, null.
	 */
	public function getLedgerIdByLedgerKeywordsMapping($narration, $transactionAmount = null, $transactionType = null, $debit_or_credit = null) {
		// Fetch all keyword mappings ordered by priority descending
		$ledgerKeywords = $this->find('all', array(
			'order' => array('LedgerKeyword.priority' => 'DESC')
		));

		foreach ($ledgerKeywords as $mapping) {
			$keyword = $mapping['LedgerKeyword']['keyword'];
			$amountCondition = $mapping['LedgerKeyword']['amount'];
			$amountOperator = $mapping['LedgerKeyword']['amount_condition'];
			$typeCondition = $mapping['LedgerKeyword']['transaction_type'];

			// Check if keyword is found in narration (case-insensitive)
			if (stripos($narration, $keyword) !== false) {

				// Check optional amount condition with operator
				if ($amountCondition !== null && $transactionAmount !== null && $amountOperator !== null) {
					switch ($amountOperator) {
						case 'gt':
							if (!($transactionAmount > $amountCondition)) {
								continue;
							}
							break;
						case 'lt':
							if (!($transactionAmount < $amountCondition)) {
								continue;
							}
							break;
						case 'eq':
							if (!($transactionAmount == $amountCondition)) {
								continue;
							}
							break;
						default:
							continue; // Invalid operator
					}
				}

				// Check optional transaction type condition if set
				if ($typeCondition !== null && $transactionType !== null) {
					if (strcasecmp($transactionType, $typeCondition) !== 0) {
						continue;
					}
				}

				// Check optional debit_or_credit condition if set
				if ($mapping['LedgerKeyword']['debit_or_credit'] !== null && $debit_or_credit !== null) {
					if (strcasecmp($mapping['LedgerKeyword']['debit_or_credit'], $debit_or_credit) !== 0) {
						continue;
					}
				}

				// All conditions match, return the ledger_id
				return $mapping['LedgerKeyword']['ledger_id'];
			}
		}

		// No matching keyword found
		return null;
	}
}
