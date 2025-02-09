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

App::uses('WebzashAppController', 'Webzash.Controller');
App::uses('GroupTree', 'Webzash.Lib');

/**
 * Webzash Plugin Ledgers Controller
 *
 * @package Webzash
 * @subpackage Webzash.controllers
 */
class LedgersController extends WebzashAppController {

	public $uses = array('Webzash.Ledger', 'Webzash.Group', 'Webzash.Entryitem',
		'Webzash.Log');

/**
 * index method
 *
 * @return void
 */
	public function index() {
		$this->redirect(array('plugin' => 'webzash', 'controller' => 'accounts', 'action' => 'show'));
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {

		$this->set('title_for_layout', __d('webzash', 'Add Account Ledger'));

		/* Create list of parent groups */
		$parentGroups = new GroupTree();
		$parentGroups->Group = &$this->Group;
		$parentGroups->current_id = -1;
		$parentGroups->build(0);
		$parentGroups->toList($parentGroups, -1);
		$this->set('parents', $parentGroups->groupList);

		/* On POST */
		if ($this->request->is('post')) {
			$this->Ledger->create();
			if (!empty($this->request->data)) {
				/* Unset ID */
				unset($this->request->data['Ledger']['id']);

				/* If code is empty set it as NULL */
				if (strlen($this->request->data['Ledger']['code']) <= 0) {
					$this->request->data['Ledger']['code'] = NULL;
				}

				/* If opening balance is not set or empty make it 0 */
				if (empty($this->request->data['Ledger']['op_balance'])) {
					$this->request->data['Ledger']['op_balance'] = 0;
				}

				/* Count number of decimal places */
				if (countDecimal($this->request->data['Ledger']['op_balance']) > Configure::read('Account.decimal_places')) {
					$this->Session->setFlash(__d('webzash', 'Invalid amount specified. Maximum %s decimal places allowed.', Configure::read('Account.decimal_places')), 'danger');
					return;
				}

				/* Save ledger */
				$ds = $this->Ledger->getDataSource();
				$ds->begin();

				if ($this->Ledger->save($this->request->data)) {
					$this->Log->add('Added Ledger : ' . $this->request->data['Ledger']['name'], 1);
					$ds->commit();
					$this->Session->setFlash(__d('webzash', 'Account ledger "%s" created.', $this->request->data['Ledger']['name']), 'success');
					return $this->redirect(array('plugin' => 'webzash', 'controller' => 'accounts', 'action' => 'show'));
				} else {
					$ds->rollback();
					$this->Session->setFlash(__d('webzash', 'Failed to create account ledger. Please, try again.'), 'danger');
					return;
				}
			} else {
				$this->Session->setFlash(__d('webzash', 'No data. Please, try again.'), 'danger');
				return;
			}
		}
	}

/**
 * edit method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function edit($id = null) {

		$this->set('title_for_layout', __d('webzash', 'Edit Account Ledger'));

		/* Check for valid ledger */
		if (empty($id)) {
			$this->Session->setFlash(__d('webzash', 'Account ledger not specified.'), 'danger');
			return $this->redirect(array('plugin' => 'webzash', 'controller' => 'accounts', 'action' => 'show'));
		}
		$ledger = $this->Ledger->findById($id);
		if (!$ledger) {
			$this->Session->setFlash(__d('webzash', 'Account ledger not found.'), 'danger');
			return $this->redirect(array('plugin' => 'webzash', 'controller' => 'accounts', 'action' => 'show'));
		}

		/* Create list of parent groups */
		$parentGroups = new GroupTree();
		$parentGroups->Group = &$this->Group;
		$parentGroups->current_id = -1;
		$parentGroups->build(0);
		$parentGroups->toList($parentGroups, -1);
		$this->set('parents', $parentGroups->groupList);

		/* on POST */
		if ($this->request->is('post') || $this->request->is('put')) {

			/* Check if acccount is locked */
			if (Configure::read('Account.locked') == 1) {
				$this->Session->setFlash(__d('webzash', 'Sorry, no changes are possible since the account is locked.'), 'danger');
				return $this->redirect(array('plugin' => 'webzash', 'controller' => 'accounts', 'action' => 'show'));
			}

			/* If code is empty set it as NULL */
			if (strlen($this->request->data['Ledger']['code']) <= 0) {
				$this->request->data['Ledger']['code'] = NULL;
			}

			/* If opening balance is not set or empty make it 0 */
			if (empty($this->request->data['Ledger']['op_balance'])) {
				$this->request->data['Ledger']['op_balance'] = 0;
			}

			/* Count number of decimal places */
			if (countDecimal($this->request->data['Ledger']['op_balance']) > Configure::read('Account.decimal_places')) {
				$this->Session->setFlash(__d('webzash', 'Invalid amount specified. Maximum %s decimal places allowed.', Configure::read('Account.decimal_places')), 'danger');
				return;
			}

			/* Set ledger id */
			unset($this->request->data['Ledger']['id']);
			$this->Ledger->id = $id;

			/* Save ledger */
			$ds = $this->Ledger->getDataSource();
			$ds->begin();

			if ($this->Ledger->save($this->request->data)) {
				$this->Log->add('Edited Ledger : ' . $this->request->data['Ledger']['name'], 1);
				$ds->commit();
				$this->Session->setFlash(__d('webzash', 'Account ledger "%s" updated.', $this->request->data['Ledger']['name']), 'success');
				return $this->redirect(array('plugin' => 'webzash', 'controller' => 'accounts', 'action' => 'show'));
			} else {
				$ds->rollback();
				$this->Session->setFlash(__d('webzash', 'Failed to update account ledger. Please, try again.'), 'danger');
				return;
			}
		} else {
			$this->request->data = $ledger;
			return;
		}
	}

/**
 * delete method
 *
 * @throws NotFoundException
 * @throws MethodNotAllowedException
 * @param string $id
 * @return void
 */
	public function delete($id = null) {

		/* GET access not allowed */
		if ($this->request->is('get')) {
			throw new MethodNotAllowedException();
		}

		/* Check if valid id */
		if (empty($id)) {
			$this->Session->setFlash(__d('webzash', 'Account ledger not specified.'), 'danger');
			return $this->redirect(array('plugin' => 'webzash', 'controller' => 'accounts', 'action' => 'show'));
		}

		/* Check if ledger exists */
		$ledger = $this->Ledger->findById($id);
		if (!$ledger) {
			$this->Session->setFlash(__d('webzash', 'Account ledger not found.'), 'danger');
			return $this->redirect(array('plugin' => 'webzash', 'controller' => 'accounts', 'action' => 'show'));
		}

		/* Check if any entry item using this ledger still exists */
		$entries = $this->Entryitem->find('count', array('conditions' => array('Entryitem.ledger_id' => $id)));
		if ($entries > 0) {
			$this->Session->setFlash(__d('webzash', 'Account ledger cannot not be deleted since it has one or more entries still present.'), 'danger');
			return $this->redirect(array('plugin' => 'webzash', 'controller' => 'accounts', 'action' => 'show'));
		}

		/* Delete ledger */
		$ds = $this->Ledger->getDataSource();
		$ds->begin();

		if ($this->Ledger->delete($id)) {
			$this->Log->add('Deleted Ledger : ' . $ledger['Ledger']['name'], 1);
			$ds->commit();
			$this->Session->setFlash(__d('webzash', 'Account ledger "%s" deleted.', $ledger['Ledger']['name']), 'success');
		} else {
			$ds->rollback();
			$this->Session->setFlash(__d('webzash', 'Failed to delete account ledger. Please, try again.'), 'danger');
		}

		return $this->redirect(array('plugin' => 'webzash', 'controller' => 'accounts', 'action' => 'show'));
	}

/**
 * closing balance method
 *
 * Return closing balance for the ledger
 *
 * @return void
 */
	public function cl() {
		$this->layout = null;

		/* Read ledger id from url get request */
		$id = (int)$this->request->query('id');

		/* Check if valid id */
		if (!$id) {
			$this->set('cl', array('cl' => array('dc' => '', 'amount' => '')));
			return;
		}

		/* Check if ledger exists */
		$ledger = $this->Ledger->findById($id);
		if (!$ledger) {
			$this->set('cl', array('cl' => array('dc' => '', 'amount' => '')));
			return;
		}

		$cl = $this->Ledger->closingBalance($id);

		$status = 'ok';
		/* If its a cash or bank account and closing balance is Cr then negative balance */
		if ($ledger['Ledger']['type'] == 1) {
			if ($cl['dc'] == 'C') {
				$status = 'neg';
			}
		}

		/* Return closing balance */
		$this->set('cl', array('cl' => array(
			'dc' => $cl['dc'],
			'amount' => $cl['amount'],
			'status' => $status,
		)));

		return;
	}

	function beforeFilter() {
		parent::beforeFilter();

		/* Check if acccount is locked */
		if (Configure::read('Account.locked') == 1) {
			if ($this->action == 'add' || $this->action == 'delete') {
				$this->Session->setFlash(__d('webzash', 'Sorry, no changes are possible since the account is locked.'), 'danger');
				return $this->redirect(array('plugin' => 'webzash', 'controller' => 'accounts', 'action' => 'show'));
			}
		}
	}

	/* Authorization check */
	public function isAuthorized($user) {
		if ($this->action === 'add') {
			return $this->Permission->is_allowed('add ledger');
		}

		if ($this->action === 'edit') {
			return $this->Permission->is_allowed('edit ledger');
		}

		if ($this->action === 'delete') {
			return $this->Permission->is_allowed('delete ledger');
		}

		return parent::isAuthorized($user);
	}



	public function copy() { //  created new method for copying ledgers
		$this->set('title_for_layout', __d('webzash', 'Copy Account Ledgers')); //  title updated for ledgers

		if (Configure::read('Account.locked') == 1) { //  same locked check as for groups
			$this->Session->setFlash(__d('webzash', 'Sorry, no changes are possible since the account is locked.'), 'danger'); //  flash message
			return $this->redirect(array('plugin' => 'webzash', 'controller' => 'accounts', 'action' => 'show')); //  redirect unchanged
		}

		$this->loadModel('Webzash.Wzaccount'); //  load the Wzaccount model as in groups copy
		$this->Wzaccount->useDbConfig = 'wz'; //  set db config to 'wz'

		// Get current active account ID from session
		$current_account = $this->Session->read('ActiveAccount.id'); //  same as in groups copy

		$wzaccounts = $this->Wzaccount->find('list', array( //  retrieve list of available accounts
			'fields' => array('Wzaccount.id', 'Wzaccount.label'), //  same fields as groups copy
			'conditions' => array('Wzaccount.id !=' => $current_account), //  exclude the current account
			'order' => array('Wzaccount.label' => 'asc') //  same order as groups copy
		));
		$this->set('wzaccounts', $wzaccounts); //  set available accounts for the view

		if ($this->request->is('post')) { //  process the submitted form
			if (!empty($this->request->data)) { //  ensure data is not empty
				// Use the Ledger field (not Group) to retrieve the source account ID
				$sourceId = $this->request->data['Ledger']['source_account_id']; //  use Ledger instead of Group
				$destDs = $this->Ledger->getDataSource(); //  get datasource from Ledger model
				$destDs->begin(); //  begin transaction

				try {
					// Get source account details
					$sourceAccount = $this->Wzaccount->find('first', array(
						'conditions' => array('Wzaccount.id' => $sourceId)
					)); //  same lookup as groups
					if (!$sourceAccount) {
						throw new Exception(__d('webzash', 'Source account not found')); //  throw exception if missing
					}

					// Get destination account details
					$destAccount = $this->Wzaccount->find('first', array(
						'conditions' => array('Wzaccount.id' => $current_account)
					)); //  same as groups
					if (!$destAccount) {
						throw new Exception(__d('webzash', 'Destination account not found')); //  exception if missing
					}

					// Setup SOURCE connection
					$sourceConfig = $destDs->config; //  use existing configuration
					$sourceConfig['prefix'] = $sourceAccount['Wzaccount']['db_prefix']; //  set source db prefix
					ConnectionManager::create('source_db', $sourceConfig); //  create source connection

					// Setup DESTINATION connection
					$destConfig = $sourceConfig; //  duplicate config array
					$destConfig['prefix'] = $destAccount['Wzaccount']['db_prefix']; //  set destination db prefix
					ConnectionManager::create('dest_db', $destConfig); //  create destination connection

					// Create the Ledger models for source and destination
					App::import('Model', 'Webzash.Ledger'); //  import Ledger model instead of Group
					$SourceLedger = new Ledger(); //  create source ledger model
					$SourceLedger->useTable = 'ledgers'; //  specify table 'ledgers'
					$SourceLedger->setDataSource('source_db'); //  set datasource for source ledger

					$DestLedger = new Ledger(); //  create destination ledger model
					$DestLedger->useTable = 'ledgers'; //  specify table 'ledgers'
					$DestLedger->setDataSource('dest_db'); //  set datasource for destination ledger

					// Create Group models to look up parent groups (by name)
					App::import('Model', 'Webzash.Group'); //  import Group model for lookup
					$SourceGroup = new Group(); //  source group model
					$SourceGroup->useTable = 'groups'; //  set table to 'groups'
					$SourceGroup->setDataSource('source_db'); //  set datasource for source group

					$DestGroup = new Group(); //  destination group model
					$DestGroup->useTable = 'groups'; //  set table to 'groups'
					$DestGroup->setDataSource('dest_db'); //  set datasource for destination group

					// Get all source ledgers
					$sourceLedgers = $SourceLedger->find('all', array(
						'order' => array('Ledger.id' => 'asc') //  order by ledger id (or name if preferred)
					)); //  retrieve all ledgers from the source account

					$copied = 0; //  initialize counter for copied ledgers
					$skipped = 0; //  initialize counter for skipped ledgers

					foreach ($sourceLedgers as $srcLedger) { //  iterate over each source ledger
						$srcName = $srcLedger['Ledger']['name']; //  get ledger name

						// Check if ledger with this name already exists in destination
						$existing = $DestLedger->find('first', array(
							'conditions' => array('Ledger.name' => $srcName)
						)); //  lookup by ledger name
						if ($existing) { //  if already exists, skip copying
							$skipped++; //  increment skipped counter
							continue; //  skip to next ledger
						}

						echo "Copying new ledger: " . $srcName . "\n"; //  output for feedback

						$data = $srcLedger['Ledger']; //  get ledger data array
						unset($data['id']); //  remove primary key so a new one is assigned

						//  Force op_balance to 0 for the copied ledger
						$data['op_balance'] = 0; //  set op_balance to 0
						$data['op_balance_dc'] = 'D'; //  set op_balance_dc to 'D'

						// Look up the ledger's parent group by its source group id, then find the destination group by name
						$srcGroupId = $data['group_id']; //  save source group id
						$srcGroup = $SourceGroup->find('first', array(
							'conditions' => array('Group.id' => $srcGroupId)
						)); //  get source group record
						if (!$srcGroup) { //  if source group not found
							$data['group_id'] = 0; //  default to 0 (or handle as desired)
						} else {
							$groupName = $srcGroup['Group']['name']; //  get group name from source
							$destGroup = $DestGroup->find('first', array(
								'conditions' => array('Group.name' => $groupName)
							)); //  look up destination group by name
							if ($destGroup) { //  if found, update group_id
								$data['group_id'] = $destGroup['Group']['id']; //  set ledger group_id to destination id
							} else { //  if not found, default to 0 (or handle otherwise)
								$data['group_id'] = 0; //  default group_id to 0
							}
						}

						$DestLedger->create(); //  create a new ledger record in destination
						if ($DestLedger->save(array('Ledger' => $data))) { //  attempt to save the ledger
							$copied++; //  increment copied counter on success
							$this->Log->add('Copied Ledger: ' . $srcName, 1); //  log success message
						} else { //  if save fails
							$skipped++; //  increment skipped counter
							$this->Log->add('Failed to copy ledger: ' . $srcName, 1); //  log failure
						}
					}

					$destDs->commit(); //  commit the transaction

					$preview_output = '<h4>Ledger Copy Details</h4><ul><li>'
						. implode('</li><li>', $message_output)
						. '</li></ul>'; //  build an HTML unordered list

					// Create the final result message with a summary and the detailed list
					$result_msg = '<div class="alert alert-success">';
					$result_msg .= '<h4>Ledger Copy Summary</h4>';
					$result_msg .= '<ul>';
					$result_msg .= '<li>Copied: ' . $copied . ' ledger(s)</li>';
					$result_msg .= '<li>Skipped: ' . $skipped . ' ledger(s)</li>';
					$result_msg .= '</ul>';
					$result_msg .= $preview_output;
					$result_msg .= '</div>'; //  final pretty HTML summary message


					$this->Session->setFlash($result_msg, 'success'); //  flash success message
					return $this->redirect(array('plugin' => 'webzash', 'controller' => 'accounts', 'action' => 'show')); //  redirect to accounts page

				} catch (Exception $e) { //  catch any exceptions during the process
					$destDs->rollback(); //  rollback transaction on error
					$this->Session->setFlash($e->getMessage(), 'danger'); //  flash error message
					return;
				}
			}
		}
	}







}
