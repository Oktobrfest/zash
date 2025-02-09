<?php

App::uses('WebzashAppController', 'Webzash.Controller');
App::uses('GroupTree', 'Webzash.Lib');

/**
 * Webzash Plugin Groups Controller
 *
 * @package Webzash
 * @subpackage Webzash.controllers
 */
class GroupsController extends WebzashAppController
{

	public $uses = array('Webzash.Group', 'Webzash.Ledger', 'Webzash.Log');

	/**
	 * index method
	 *
	 * @return void
	 */
	public function index()
	{
		$this->redirect(array('plugin' => 'webzash', 'controller' => 'accounts', 'action' => 'show'));
	}

	/**
	 * add method
	 *
	 * @return void
	 */
	public function add()
	{

		$this->set('title_for_layout', __d('webzash', 'Add Account Group'));

		/* Create list of parent groups */
		$parentGroups = new GroupTree();
		$parentGroups->Group = &$this->Group;
		$parentGroups->current_id = -1;
		$parentGroups->build(0);
		$parentGroups->toList($parentGroups, -1);
		$this->set('parents', $parentGroups->groupList);

		/* On POST */
		if ($this->request->is('post')) {
			$this->Group->create();
			if (!empty($this->request->data)) {
				/* Unset ID */
				unset($this->request->data['Group']['id']);

				/* If code is empty set it as NULL */
				if (empty($this->request->data['Group']['code'])) {
					$this->request->data['Group']['code'] = NULL;
				}

				/* Save group */
				$ds = $this->Group->getDataSource();
				$ds->begin();

				if ($this->Group->save($this->request->data)) {
					$this->Log->add('Added Group : ' . $this->request->data['Group']['name'], 1);
					$ds->commit();
					$this->Session->setFlash(__d('webzash', 'Account group "%s" created.', $this->request->data['Group']['name']), 'success');
					return $this->redirect(array('plugin' => 'webzash', 'controller' => 'accounts', 'action' => 'show'));
				} else {
					$ds->rollback();
					$this->Session->setFlash(__d('webzash', 'Failed to create account group. Please, try again.'), 'danger');
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
	 * @param string $id
	 * @return void
	 * @throws NotFoundException
	 * @throws ForbiddenException
	 */
	public function edit($id = null)
	{

		$this->set('title_for_layout', __d('webzash', 'Edit Account Group'));

		/* Check for valid group */
		if (empty($id)) {
			$this->Session->setFlash(__d('webzash', 'Account group not specified.'), 'danger');
			return $this->redirect(array('plugin' => 'webzash', 'controller' => 'accounts', 'action' => 'show'));
		}
		$group = $this->Group->findById($id);
		if (!$group) {
			$this->Session->setFlash(__d('webzash', 'Account group not found.'), 'danger');
			return $this->redirect(array('plugin' => 'webzash', 'controller' => 'accounts', 'action' => 'show'));
		}
		if ($id <= 4) {
			$this->Session->setFlash(__d('webzash', 'Cannot edit basic account groups.'), 'danger');
			return $this->redirect(array('plugin' => 'webzash', 'controller' => 'accounts', 'action' => 'show'));
		}

		/* Create list of parent groups */
		$parentGroups = new GroupTree();
		$parentGroups->Group = &$this->Group;
		$parentGroups->current_id = $id;
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

			/* Set group id */
			unset($this->request->data['Group']['id']);
			$this->Group->id = $id;

			/* Check if group and parent group are not same */
			if ($id == $this->request->data['Group']['parent_id']) {
				$this->Session->setFlash(__d('webzash', 'Account group and parent group cannot be same.'), 'danger');
				return;
			}

			/* If code is empty set it as NULL */
			if (empty($this->request->data['Group']['code'])) {
				$this->request->data['Group']['code'] = NULL;
			}

			/* Save group */
			$ds = $this->Group->getDataSource();
			$ds->begin();

			if ($this->Group->save($this->request->data)) {
				$this->Log->add('Edited Group : ' . $this->request->data['Group']['name'], 1);
				$ds->commit();
				$this->Session->setFlash(__d('webzash', 'Account group "%s" updated.', $this->request->data['Group']['name']), 'success');
				return $this->redirect(array('plugin' => 'webzash', 'controller' => 'accounts', 'action' => 'show'));
			} else {
				$ds->rollback();
				$this->Session->setFlash(__d('webzash', 'Failed to update account group. Please, try again.'), 'danger');
				return;
			}
		} else {
			$this->request->data = $group;
			return;
		}
	}

	/**
	 * delete method
	 *
	 * @param string $id
	 * @return void
	 * @throws NotFoundException
	 * @throws MethodNotAllowedException
	 */
	public function delete($id = null)
	{

		/* GET access not allowed */
		if ($this->request->is('get')) {
			throw new MethodNotAllowedException();
		}

		/* Check if valid id */
		if (empty($id)) {
			$this->Session->setFlash(__d('webzash', 'Account group not specified.'), 'danger');
			return $this->redirect(array('plugin' => 'webzash', 'controller' => 'accounts', 'action' => 'show'));
		}

		/* Check if group exists */
		$group = $this->Group->findById($id);
		if (!$group) {
			$this->Session->setFlash(__d('webzash', 'Account group not found.'), 'danger');
			return $this->redirect(array('plugin' => 'webzash', 'controller' => 'accounts', 'action' => 'show'));
		}

		/* Check if group can be deleted */
		if ($id <= 4) {
			$this->Session->setFlash(__d('webzash', 'Cannot delete basic account groups.'), 'danger');
			return $this->redirect(array('plugin' => 'webzash', 'controller' => 'accounts', 'action' => 'show'));
		}

		/* Check if any child groups exists */
		$child = $this->Group->find('count', array('conditions' => array('Group.parent_id' => $id)));
		if ($child > 0) {
			$this->Session->setFlash(__d('webzash', 'Account group cannot be deleted since it has one or more child group accounts still present.'), 'danger');
			return $this->redirect(array('plugin' => 'webzash', 'controller' => 'accounts', 'action' => 'show'));
		}

		/* Check if any child ledgers exists */
		$child = $this->Ledger->find('count', array('conditions' => array('Ledger.group_id' => $id)));
		if ($child > 0) {
			$this->Session->setFlash(__d('webzash', 'Account group cannot not be deleted since it has one or more child ledger accounts still present.'), 'danger');
			return $this->redirect(array('plugin' => 'webzash', 'controller' => 'accounts', 'action' => 'show'));
		}

		/* Delete group */
		$ds = $this->Group->getDataSource();
		$ds->begin();

		if ($this->Group->delete($id)) {
			$this->Log->add('Deleted Group : ' . $group['Group']['name'], 1);
			$ds->commit();
			$this->Session->setFlash(__d('webzash', 'Account group "%s" deleted.', $group['Group']['name']), 'success');
		} else {
			$ds->rollback();
			$this->Session->setFlash(__d('webzash', 'Failed to delete account group. Please, try again.'), 'danger');
		}

		return $this->redirect(array('plugin' => 'webzash', 'controller' => 'accounts', 'action' => 'show'));
	}

	function beforeFilter()
	{
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
	public function isAuthorized($user)
	{
		if ($this->action === 'add') {
			return $this->Permission->is_allowed('add group');
		}

		if ($this->action === 'edit') {
			return $this->Permission->is_allowed('edit group');
		}

		if ($this->action === 'delete') {
			return $this->Permission->is_allowed('delete group');
		}

		return parent::isAuthorized($user);
	}


// WORKS!
	public function copy() {
		$this->set('title_for_layout', __d('webzash', 'Copy Account Groups'));

		if (Configure::read('Account.locked') == 1) {
			$this->Session->setFlash(__d('webzash', 'Sorry, no changes are possible since the account is locked.'), 'danger');
			return $this->redirect(array('plugin' => 'webzash', 'controller' => 'accounts', 'action' => 'show'));
		}

		$this->loadModel('Webzash.Wzaccount');
		$this->Wzaccount->useDbConfig = 'wz';

		// Get current active account ID from session
		$current_account = $this->Session->read('ActiveAccount.id');

		$wzaccounts = $this->Wzaccount->find('list', array(
			'fields' => array('Wzaccount.id', 'Wzaccount.label'),
			'conditions' => array('Wzaccount.id !=' => $current_account),
			'order' => array('Wzaccount.label' => 'asc')
		));
		$this->set('wzaccounts', $wzaccounts);

		if ($this->request->is('post')) {
			if (!empty($this->request->data)) {
				$sourceId = $this->request->data['Group']['source_account_id'];
				$destDs = $this->Group->getDataSource();
				$destDs->begin();

				try {
					// Get source account details
					$sourceAccount = $this->Wzaccount->find('first', array(
						'conditions' => array('Wzaccount.id' => $sourceId)
					));
					if (!$sourceAccount) {
						throw new Exception(__d('webzash', 'Source account not found'));
					}

					// Get destination account details
					$destAccount = $this->Wzaccount->find('first', array(
						'conditions' => array('Wzaccount.id' => $current_account)
					));
					if (!$destAccount) {
						throw new Exception(__d('webzash', 'Destination account not found'));
					}

					// Setup SOURCE connection
					$sourceConfig = $destDs->config;
					$sourceConfig['prefix'] = $sourceAccount['Wzaccount']['db_prefix'];
					ConnectionManager::create('source_db', $sourceConfig);

					// Setup DESTINATION connection
					$destConfig = $sourceConfig;
					$destConfig['prefix'] = $destAccount['Wzaccount']['db_prefix'];
					ConnectionManager::create('dest_db', $destConfig);

					// Create the models
					App::import('Model', 'Webzash.Group');
					$SourceGroup = new Group();
					$SourceGroup->useTable = 'groups';
					$SourceGroup->setDataSource('source_db');

					$DestGroup = new Group();
					$DestGroup->useTable = 'groups';
					$DestGroup->setDataSource('dest_db');

					$sourceGroups = $SourceGroup->find('all', array(
						'order' => array('Group.parent_id' => 'asc')
					));

					$idMap = array();
					$copied = 0;
					$skipped = 0;

					foreach ($sourceGroups as $srcGroup) {
						$srcName = $srcGroup['Group']['name'];

						$existing = $DestGroup->find('first', array(
							'conditions' => array('Group.name' => $srcName)
						));

						if ($existing) {
							$idMap[$srcGroup['Group']['id']] = $existing['Group']['id'];
							$skipped++;
							continue;
						}

						echo "Copying new group: " . $srcName . "\n";

						$data = $srcGroup['Group'];
						unset($data['id']);

						if (!empty($data['parent_id'])) {
							if (isset($idMap[$data['parent_id']])) {
								$data['parent_id'] = $idMap[$data['parent_id']];
							} else {
								$data['parent_id'] = 0;
							}
						}

						$DestGroup->create();
						if ($DestGroup->save(array('Group' => $data))) {
							$newId = $DestGroup->id;
							$idMap[$srcGroup['Group']['id']] = $newId;
							$copied++;
							$this->Log->add('Copied Group: ' . $srcName, 1);
						} else {
							$skipped++;
							$this->Log->add('Failed to copy group: ' . $srcName, 1);
						}
					}

					$destDs->commit();

					$summary = sprintf(
						__d('webzash', 'Groups copy complete. %d groups copied, %d groups skipped.'),
						$copied,
						$skipped
					);
					$this->Session->setFlash($summary, 'success');
					return $this->redirect(array('plugin' => 'webzash', 'controller' => 'accounts', 'action' => 'show'));

				} catch (Exception $e) {
					$destDs->rollback();
					$this->Session->setFlash($e->getMessage(), 'danger');
					return;
				}
			}
		}
	}




}
