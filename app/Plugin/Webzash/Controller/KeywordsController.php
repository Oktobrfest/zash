<?php

App::uses('WebzashAppController', 'Webzash.Controller');

class KeywordsController extends WebzashAppController
{

	public $uses = array('Webzash.LedgerKeyword', 'Webzash.Ledger');

	public function index()
	{
		$this->set('title_for_layout', 'Keyword to Ledger Mappings');

		// Fetch all keyword mappings
		$ledgerKeywords = $this->LedgerKeyword->find('all');

		// Loop through mappings and get ledger names
		foreach ($ledgerKeywords as &$mapping) {
			$ledger = $this->Ledger->findById($mapping['LedgerKeyword']['ledger_id']);
			if ($ledger) {
				$mapping['LedgerKeyword']['ledger_name'] = $ledger['Ledger']['name'];
			} else {
				$mapping['LedgerKeyword']['ledger_name'] = 'Unknown Ledger';
			}

			// Handle transaction amount, type, and other optional fields
			$mapping['LedgerKeyword']['amount'] = $mapping['LedgerKeyword']['amount'] !== null ? $mapping['LedgerKeyword']['amount'] : 'Any';
			$mapping['LedgerKeyword']['transaction_type'] = $mapping['LedgerKeyword']['transaction_type'] !== null ? $mapping['LedgerKeyword']['transaction_type'] : 'Any';
			$mapping['LedgerKeyword']['amount_condition'] = $mapping['LedgerKeyword']['amount_condition'] !== null ? $mapping['LedgerKeyword']['amount_condition'] : 'Any';
			$mapping['LedgerKeyword']['debit_or_credit'] = $mapping['LedgerKeyword']['debit_or_credit'] !== null ? $mapping['LedgerKeyword']['debit_or_credit'] : 'Any';
		}

		// Pass the data to the view
		$this->set('ledgerKeywords', $ledgerKeywords);
	}

	public function add()
	{
		$this->set('title_for_layout', 'Add Keyword Mapping');

		$ledgerOptions = $this->Ledger->find('list', array('fields' => array('Ledger.id', 'Ledger.name')));
		$this->set('ledgerOptions', $ledgerOptions);

		if ($this->request->is('post')) {
			$this->LedgerKeyword->create();

			// Set fields to null if "Any" is selected
			if (empty($this->request->data['LedgerKeyword']['amount_condition'])) {
				$this->request->data['LedgerKeyword']['amount_condition'] = null;
			}
			if (empty($this->request->data['LedgerKeyword']['debit_or_credit'])) {
				$this->request->data['LedgerKeyword']['debit_or_credit'] = null;
			}
			if (empty($this->request->data['LedgerKeyword']['transaction_type'])) {
				$this->request->data['LedgerKeyword']['transaction_type'] = null;
			}

			if ($this->LedgerKeyword->save($this->request->data)) {
				$this->Session->setFlash('Keyword mapping saved.', 'success');
				return $this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash('Failed to save keyword mapping.', 'danger');
			}
		}
	}

	public function edit($id = null)
	{
		$this->set('title_for_layout', 'Edit Keyword Mapping');

		if (!$id) {
			$this->Session->setFlash('Invalid keyword mapping.', 'danger');
			return $this->redirect(array('action' => 'index'));
		}

		$ledgerOptions = $this->Ledger->find('list', array('fields' => array('Ledger.id', 'Ledger.name')));
		$this->set('ledgerOptions', $ledgerOptions);

		$ledgerKeyword = $this->LedgerKeyword->findById($id);
		if (!$ledgerKeyword) {
			$this->Session->setFlash('Keyword mapping not found.', 'danger');
			return $this->redirect(array('action' => 'index'));
		}

		if ($this->request->is(array('post', 'put'))) {
			$this->LedgerKeyword->id = $id;

			// Set fields to null if "Any" is selected
			if (empty($this->request->data['LedgerKeyword']['amount_condition'])) {
				$this->request->data['LedgerKeyword']['amount_condition'] = null;
			}
			if (empty($this->request->data['LedgerKeyword']['debit_or_credit'])) {
				$this->request->data['LedgerKeyword']['debit_or_credit'] = null;
			}
			if (empty($this->request->data['LedgerKeyword']['transaction_type'])) {
				$this->request->data['LedgerKeyword']['transaction_type'] = null;
			}

			if ($this->LedgerKeyword->save($this->request->data)) {
				$this->Session->setFlash('Keyword mapping updated.', 'success');
				return $this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash('Failed to update keyword mapping.', 'danger');
			}
		} else {
			$this->request->data = $ledgerKeyword;
		}
	}

	public function delete($id = null)
	{
		if (!$id) {
			$this->Session->setFlash('Invalid keyword mapping.', 'danger');
			return $this->redirect(array('action' => 'index'));
		}

		if ($this->LedgerKeyword->delete($id)) {
			$this->Session->setFlash('Keyword mapping deleted.', 'success');
		} else {
			$this->Session->setFlash('Failed to delete keyword mapping.', 'danger');
		}
		return $this->redirect(array('action' => 'index'));
	}

}
