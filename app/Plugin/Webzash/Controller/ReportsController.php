<?php

App::uses('WebzashAppController', 'Webzash.Controller');
App::uses('LedgerTree', 'Webzash.Lib');
App::uses('AccountList', 'Webzash.Lib');

/**
 * Webzash Plugin Reports Controller
 *
 * @package Webzash
 * @subpackage Webzash.controllers
 */
class ReportsController extends WebzashAppController {

/**
 * This controller does not use a model
 *
 * @var array
 */
	public $uses = array('Webzash.Group', 'Webzash.Ledger', 'Webzash.Entry',
		'Webzash.Entryitem', 'Webzash.Tag');

/**
 * index method
 *
 * @return void
 */
	public function index() {
		$this->set('title_for_layout', __d('webzash', 'Reports'));

		return;
	}

	/**
	 * Generates a download filename using the Account name, a report title, and the Account end date.
	 * – The date is formatted as MM-DD-YY.
	 * – Each part (company name, report title, date) is separated by a single space,
	 *   with no extra space at the end.
	 * – If either the company name or the end date is not set, they will fall back to an empty string.
	 *
	 * @param string $reportTitle The report title (for example, "balancesheet" or "profitloss")
	 * @param string $extension   The file extension (e.g. "csv" or "xls")
	 * @return string The generated filename.
	 */
	protected function _generateFilename($reportTitle, $extension) {
		$company = Configure::read('Account.name');
		$company = $company ? trim($company) : "";

		$dateRaw = Configure::read('Account.enddate');
		// Use today’s date as a fallback if no end date is set.
		$date = $dateRaw ? date('m-d-y', strtotime($dateRaw)) : date('m-d-y');

		$parts = array();
		if ($company !== "") {
			$parts[] = $company;
		}
		if ($reportTitle !== "") {
			$parts[] = $reportTitle;
		}
		if ($date !== "") {
			$parts[] = $date;
		}
		return implode(" ", $parts) . "." . $extension;
	}


	/**
 * balancesheet method
 *
 * @return void
 */
	public function balancesheet() {

		$this->set('title_for_layout', __d('webzash', 'Balance Sheet'));

		/* POST */
		if ($this->request->is('post')) {
			if ($this->request->data['Balancesheet']['opening'] == 1) {
				return $this->redirect(array(
					'plugin' => 'webzash',
					'controller' => 'reports',
					'action' => 'balancesheet',
					'options' => 1,
					'opening' => 1,
				));
			} else {
				if (!empty($this->request->data['Balancesheet']['startdate']) || !empty($this->request->data['Balancesheet']['enddate'])) {
					return $this->redirect(array(
						'plugin' => 'webzash',
						'controller' => 'reports',
						'action' => 'balancesheet',
						'options' => 1,
						'opening' => 0,
						'startdate' => $this->request->data['Balancesheet']['startdate'],
						'enddate' => $this->request->data['Balancesheet']['enddate']
					));
				} else {
					return $this->redirect(array(
						'plugin' => 'webzash',
						'controller' => 'reports',
						'action' => 'balancesheet'
					));
				}
			}
		}

		$only_opening = false;
		$startdate = null;
		$enddate = null;

		if (empty($this->passedArgs['options'])) {
			$this->set('options', false);

			/* Sub-title*/
			$this->set('subtitle', __d('webzash', 'Closing Balance Sheet as on ') .
				dateFromSql(Configure::read('Account.enddate')));
		} else {
			$this->set('options', true);
			if (!empty($this->passedArgs['opening'])) {
				$only_opening = true;
				$this->request->data['Balancesheet']['opening'] = '1';

				/* Sub-title*/
				$this->set('subtitle', __d('webzash', 'Opening Balance Sheet as on ') .
					dateFromSql(Configure::read('Account.startdate')));
			} else {
				if (!empty($this->passedArgs['startdate'])) {
					$startdate = dateToSQL($this->passedArgs['startdate']);
					$this->request->data['Balancesheet']['startdate'] =
						$this->passedArgs['startdate'];
				}
				if (!empty($this->passedArgs['enddate'])) {
					$enddate = dateToSQL($this->passedArgs['enddate']);
					$this->request->data['Balancesheet']['enddate'] =
						$this->passedArgs['enddate'];
				}

				/* Sub-title*/
				if (!empty($this->passedArgs['startdate']) &&
					!empty($this->passedArgs['enddate'])) {
					$this->set('subtitle', __d('webzash', 'Balance Sheet from ' .
						dateFromSql(dateToSQL($this->passedArgs['startdate'])) . ' to ' .
						dateFromSql(dateToSQL($this->passedArgs['enddate']))
					));
				} else if (!empty($this->passedArgs['startdate'])) {
					$this->set('subtitle', __d('webzash', 'Balance Sheet from ' .
						dateFromSql(dateToSQL($this->passedArgs['startdate'])) . ' to ' .
						dateFromSql(Configure::read('Account.enddate'))
					));
				} else if (!empty($this->passedArgs['enddate'])) {
					$this->set('subtitle', __d('webzash', 'Balance Sheet from ' .
						dateFromSql(Configure::read('Account.startdate')) . ' to ' .
						dateFromSql(dateToSQL($this->passedArgs['enddate']))
					));
				}
			}
		}

		/**********************************************************************/
		/*********************** BALANCESHEET CALCULATIONS ********************/
		/**********************************************************************/

		/* Liabilities */
		$liabilities = new AccountList();
		$liabilities->Group = &$this->Group;
		$liabilities->Ledger = &$this->Ledger;
		$liabilities->only_opening = $only_opening;
		$liabilities->start_date = $startdate;
		$liabilities->end_date = $enddate;
		$liabilities->affects_gross = -1;
		$liabilities->start(2);

		$bsheet['liabilities'] = $liabilities;

		$bsheet['liabilities_total'] = 0;
		if ($liabilities->cl_total_dc == 'C') {
			$bsheet['liabilities_total'] = $liabilities->cl_total;
		} else {
			$bsheet['liabilities_total'] = calculate($liabilities->cl_total, 0, 'n');
		}

		/* Assets */
		$assets = new AccountList();
		$assets->Group = &$this->Group;
		$assets->Ledger = &$this->Ledger;
		$assets->only_opening = $only_opening;
		$assets->start_date = $startdate;
		$assets->end_date = $enddate;
		$assets->affects_gross = -1;
		$assets->start(1);

		$bsheet['assets'] = $assets;

		$bsheet['assets_total'] = 0;
		if ($assets->cl_total_dc == 'D') {
			$bsheet['assets_total'] = $assets->cl_total;
		} else {
			$bsheet['assets_total'] = calculate($assets->cl_total, 0, 'n');
		}

		/* Profit and loss calculations */
		$income = new AccountList();
		$income->Group = &$this->Group;
		$income->Ledger = &$this->Ledger;
		$income->only_opening = $only_opening;
		$income->start_date = $startdate;
		$income->end_date = $enddate;
		$income->affects_gross = -1;
		$income->start(3);

		$expense = new AccountList();
		$expense->Group = &$this->Group;
		$expense->Ledger = &$this->Ledger;
		$expense->only_opening = $only_opening;
		$expense->start_date = $startdate;
		$expense->end_date = $enddate;
		$expense->affects_gross = -1;
		$expense->start(4);

		if ($income->cl_total_dc == 'C') {
			$income_total = $income->cl_total;
		} else {
			$income_total = calculate($income->cl_total, 0, 'n');
		}
		if ($expense->cl_total_dc == 'D') {
			$expense_total = $expense->cl_total;
		} else {
			$expense_total = calculate($expense->cl_total, 0, 'n');
		}

		$bsheet['pandl'] = calculate($income_total, $expense_total, '-');

		/* Difference in opening balance */
		$bsheet['opdiff'] = $this->Ledger->getOpeningDiff();
		if (calculate($bsheet['opdiff']['opdiff_balance'], 0, '==')) {
			$bsheet['is_opdiff'] = false;
		} else {
			$bsheet['is_opdiff'] = true;
		}

		/**** Final balancesheet total ****/
		$bsheet['final_liabilities_total'] = $bsheet['liabilities_total'];
		$bsheet['final_assets_total'] = $bsheet['assets_total'];

		/* If net profit add to liabilities, if net loss add to assets */
		if (calculate($bsheet['pandl'], 0, '>=')) {
			$bsheet['final_liabilities_total'] = calculate(
				$bsheet['final_liabilities_total'],
				$bsheet['pandl'], '+');
		} else {
			$positive_pandl = calculate($bsheet['pandl'], 0, 'n');
			$bsheet['final_assets_total'] = calculate(
				$bsheet['final_assets_total'],
				$positive_pandl, '+');
		}

		/**
		 * If difference in opening balance is Dr then subtract from
		 * assets else subtract from liabilities
		 */
		if ($bsheet['is_opdiff']) {
			if ($bsheet['opdiff']['opdiff_balance_dc'] == 'D') {
				$bsheet['final_assets_total'] = calculate(
					$bsheet['final_assets_total'],
					$bsheet['opdiff']['opdiff_balance'], '+');
			} else {
				$bsheet['final_liabilities_total'] = calculate(
					$bsheet['final_liabilities_total'],
					$bsheet['opdiff']['opdiff_balance'], '+');
			}
		}

		$this->set('bsheet', $bsheet);

		/* Download report */
		if (isset($this->passedArgs['downloadcsv'])) {
			Configure::write('Account.currency_format', 'none');
			$this->layout = false;
			$view = new View($this, false);
			$response =  $view->render('Reports/downloadcsv/balancesheet');
			$this->response->body($response);
			$this->response->type('text/csv');
			$this->response->download($this->_generateFilename('Balance Sheet', 'csv'));
			return $this->response;
		}

		/* Download report */
		if (isset($this->passedArgs['downloadxls'])) {
			Configure::write('Account.currency_format', 'none');
			$this->layout = 'xls';
			$view = new View($this, false);
			$response =  $view->render('Reports/downloadxls/balancesheet');
			$this->response->body($response);
			$this->response->type('application/vnd.ms-excel');
			$this->response->download($this->_generateFilename('Balance Sheet', 'xls'));
			return $this->response;
		}

		/* Print report */
		if (isset($this->passedArgs['print'])) {
			$this->layout = 'print';
			$view = new View($this, false);
			$response =  $view->render('Reports/print/balancesheet');
			$this->response->body($response);
			return $this->response;
		}

		return;
	}

/**
 * profitloss method
 *
 * @return void
 */
	public function profitloss() {

		$this->set('title_for_layout', __d('webzash', 'Trading and Profit & Loss Statement'));
		$this->set('subtitle', '');

		/* POST */
		if ($this->request->is('post')) {
			if ($this->request->data['Profitloss']['opening'] == 1) {
				return $this->redirect(array(
					'plugin' => 'webzash',
					'controller' => 'reports',
					'action' => 'profitloss',
					'options' => 1,
					'opening' => 1,
				));
			} else {
				if (!empty($this->request->data['Profitloss']['startdate']) || !empty($this->request->data['Profitloss']['enddate'])) {
					return $this->redirect(array(
						'plugin' => 'webzash',
						'controller' => 'reports',
						'action' => 'profitloss',
						'options' => 1,
						'opening' => 0,
						'startdate' => $this->request->data['Profitloss']['startdate'],
						'enddate' => $this->request->data['Profitloss']['enddate']
					));
				} else {
					return $this->redirect(array(
						'plugin' => 'webzash',
						'controller' => 'reports',
						'action' => 'profitloss'
					));
				}
			}
		}

		$only_opening = false;
		$startdate = null;
		$enddate = null;

		if (empty($this->passedArgs['options'])) {
			$this->set('options', false);

			/* Sub-title*/
			$this->set('subtitle', __d('webzash', 'Trading and Profit & Loss Statement from ') .
				dateFromSql(Configure::read('Account.startdate')) . ' to ' .
				dateFromSql(Configure::read('Account.enddate')));
		} else {
			$this->set('options', true);
			if (!empty($this->passedArgs['opening'])) {
				$only_opening = true;
				$this->request->data['Profitloss']['opening'] = '1';

				/* Sub-title*/
				$this->set('subtitle', __d('webzash', 'Opening Trading and Profit & Loss Statement as on ') .
					dateFromSql(Configure::read('Account.startdate')));
			} else {
				if (!empty($this->passedArgs['startdate'])) {
					$startdate = dateToSQL($this->passedArgs['startdate']);
					$this->request->data['Profitloss']['startdate'] = $this->passedArgs['startdate'];
				}
				if (!empty($this->passedArgs['enddate'])) {
					$enddate = dateToSQL($this->passedArgs['enddate']);
					$this->request->data['Profitloss']['enddate'] = $this->passedArgs['enddate'];
				}

				/* Sub-title*/
				if (!empty($this->passedArgs['startdate']) &&
					!empty($this->passedArgs['enddate'])) {
					$this->set('subtitle', __d('webzash', 'Trading and Profit & Loss Statement from ' .
						dateFromSql(dateToSQL($this->passedArgs['startdate'])) . ' to ' .
						dateFromSql(dateToSQL($this->passedArgs['enddate']))
					));
				} else if (!empty($this->passedArgs['startdate'])) {
					$this->set('subtitle', __d('webzash', 'Trading and Profit & Loss Statement from ' .
						dateFromSql(dateToSQL($this->passedArgs['startdate'])) . ' to ' .
						dateFromSql(Configure::read('Account.enddate'))
					));
				} else if (!empty($this->passedArgs['enddate'])) {
					$this->set('subtitle', __d('webzash', 'Trading and Profit & Loss Statement from ' .
						dateFromSql(Configure::read('Account.startdate')) . ' to ' .
						dateFromSql(dateToSQL($this->passedArgs['enddate']))
					));
				}
			}
		}

		/**********************************************************************/
		/*********************** GROSS CALCULATIONS ***************************/
		/**********************************************************************/

		/* Gross P/L : Expenses */
		$gross_expenses = new AccountList();
		$gross_expenses->Group = &$this->Group;
		$gross_expenses->Ledger = &$this->Ledger;
		$gross_expenses->only_opening = $only_opening;
		$gross_expenses->start_date = $startdate;
		$gross_expenses->end_date = $enddate;
		$gross_expenses->affects_gross = 1;
		$gross_expenses->start(4);

		$pandl['gross_expenses'] = $gross_expenses;

		$pandl['gross_expense_total'] = 0;
		if ($gross_expenses->cl_total_dc == 'D') {
			$pandl['gross_expense_total'] = $gross_expenses->cl_total;
		} else {
			$pandl['gross_expense_total'] = calculate($gross_expenses->cl_total, 0, 'n');
		}

		/* Gross P/L : Incomes */
		$gross_incomes = new AccountList();
		$gross_incomes->Group = &$this->Group;
		$gross_incomes->Ledger = &$this->Ledger;
		$gross_incomes->only_opening = $only_opening;
		$gross_incomes->start_date = $startdate;
		$gross_incomes->end_date = $enddate;
		$gross_incomes->affects_gross = 1;
		$gross_incomes->start(3);

		$pandl['gross_incomes'] = $gross_incomes;

		$pandl['gross_income_total'] = 0;
		if ($gross_incomes->cl_total_dc == 'C') {
			$pandl['gross_income_total'] = $gross_incomes->cl_total;
		} else {
			$pandl['gross_income_total'] = calculate($gross_incomes->cl_total, 0, 'n');
		}

		/* Calculating Gross P/L */
		$pandl['gross_pl'] = calculate($pandl['gross_income_total'], $pandl['gross_expense_total'], '-');

		/**********************************************************************/
		/************************* NET CALCULATIONS ***************************/
		/**********************************************************************/

		/* Net P/L : Expenses */
		$net_expenses = new AccountList();
		$net_expenses->Group = &$this->Group;
		$net_expenses->Ledger = &$this->Ledger;
		$net_expenses->only_opening = $only_opening;
		$net_expenses->start_date = $startdate;
		$net_expenses->end_date = $enddate;
		$net_expenses->affects_gross = 0;
		$net_expenses->start(4);

		$pandl['net_expenses'] = $net_expenses;

		$pandl['net_expense_total'] = 0;
		if ($net_expenses->cl_total_dc == 'D') {
			$pandl['net_expense_total'] = $net_expenses->cl_total;
		} else {
			$pandl['net_expense_total'] = calculate($net_expenses->cl_total, 0, 'n');
		}

		/* Net P/L : Incomes */
		$net_incomes = new AccountList();
		$net_incomes->Group = &$this->Group;
		$net_incomes->Ledger = &$this->Ledger;
		$net_incomes->only_opening = $only_opening;
		$net_incomes->start_date = $startdate;
		$net_incomes->end_date = $enddate;
		$net_incomes->affects_gross = 0;
		$net_incomes->start(3);

		$pandl['net_incomes'] = $net_incomes;

		$pandl['net_income_total'] = 0;
		if ($net_incomes->cl_total_dc == 'C') {
			$pandl['net_income_total'] = $net_incomes->cl_total;
		} else {
			$pandl['net_income_total'] = calculate($net_incomes->cl_total, 0, 'n');
		}

		/* Calculating Net P/L */
		$pandl['net_pl'] = calculate($pandl['net_income_total'], $pandl['net_expense_total'], '-');
		$pandl['net_pl'] = calculate($pandl['net_pl'], $pandl['gross_pl'], '+');

		$this->set('pandl', $pandl);

		/* Download report */
		if (isset($this->passedArgs['downloadcsv'])) {
			Configure::write('Account.currency_format', 'none');
			$this->layout = false;
			$view = new View($this, false);
			$response =  $view->render('Reports/downloadcsv/profitloss');
			$this->response->body($response);
			$this->response->type('text/csv');
			$this->response->download($this->_generateFilename('Profit & Loss', 'csv'));

			return $this->response;
		}

		/* Download report */
		if (isset($this->passedArgs['downloadxls'])) {
			Configure::write('Account.currency_format', 'none');
			$this->layout = 'xls';
			$view = new View($this, false);
			$response =  $view->render('Reports/downloadxls/profitloss');
			$this->response->body($response);
			$this->response->type('application/vnd.ms-excel');
			$this->response->download($this->_generateFilename('Profit & Loss', 'xls'));
			return $this->response;
		}

		/* Print report */
		if (isset($this->passedArgs['print'])) {
			$this->layout = 'print';
			$view = new View($this, false);
			$response =  $view->render('Reports/print/profitloss');
			$this->response->body($response);
			return $this->response;
		}

		return;
	}

/**
 * trialbalance method
 *
 * @return void
 */
	public function trialbalance() {

		$this->set('title_for_layout', __d('webzash', 'Trial Balance'));

		/* Sub-title*/
		$this->set('subtitle', __d('webzash', 'Trial Balance from %s to %s',
			dateFromSql(Configure::read('Account.startdate')),
			dateFromSql(Configure::read('Account.enddate'))
		));

		$accountlist = new AccountList();
		$accountlist->Group = &$this->Group;
		$accountlist->Ledger = &$this->Ledger;
		$accountlist->only_opening = false;
		$accountlist->start_date = null;
		$accountlist->end_date = null;
		$accountlist->affects_gross = -1;

		$accountlist->start(0);

		$this->set('accountlist', $accountlist);

		/* Download report */
		if (isset($this->passedArgs['downloadcsv'])) {
			Configure::write('Account.currency_format', 'none');
			$this->layout = false;
			$view = new View($this, false);
			$response =  $view->render('Reports/downloadcsv/trialbalance');
			$this->response->body($response);
			$this->response->type('text/csv');
			$this->response->download($this->_generateFilename('Trial Balance', 'csv'));
			return $this->response;
		}

		/* Download report */
		if (isset($this->passedArgs['downloadxls'])) {
			Configure::write('Account.currency_format', 'none');
			$this->layout = 'xls';
			$view = new View($this, false);
			$response =  $view->render('Reports/downloadxls/trialbalance');
			$this->response->body($response);
			$this->response->type('application/vnd.ms-excel');
			$this->response->download($this->_generateFilename('Trial Balance', 'xls'));
			return $this->response;
		}

		/* Print report */
		if (isset($this->passedArgs['print'])) {
			$this->layout = 'print';
			$view = new View($this, false);
			$response =  $view->render('Reports/print/trialbalance');
			$this->response->body($response);
			return $this->response;
		}

		return;
	}

/**
 * ledgerstatement method
 *
 * @return void
 */
	public function ledgerstatement() {

		$this->set('title_for_layout', __d('webzash', 'Ledger Statement'));

		/* Create list of ledgers to pass to view */
		$ledgers = new LedgerTree();
		$ledgers->Group = &$this->Group;
		$ledgers->Ledger = &$this->Ledger;
		$ledgers->current_id = -1;
		$ledgers->restriction_bankcash = 1;
		$ledgers->build(0);
		$ledgers->toList($ledgers, -1);
		$ledgers_disabled = array();
		foreach ($ledgers->ledgerList as $row => $data) {
			if ($row < 0) {
				$ledgers_disabled[] = $row;
			}
		}
		$this->set('ledgers', $ledgers->ledgerList);
		$this->set('ledgers_disabled', $ledgers_disabled);

		if ($this->request->is('post')) {
			/* If valid data then redirect with POST values are URL parameters so that pagination works */
			if (empty($this->request->data['Report']['ledger_id'])) {
				$this->Session->setFlash(__d('webzash', 'Invalid ledger.'), 'danger');
				return $this->redirect(array('plugin' => 'webzash', 'controller' => 'reports', 'action' => 'ledgerstatement'));
			}

			if (!empty($this->request->data['Report']['startdate']) ||
				!empty($this->request->data['Report']['enddate'])) {
				return $this->redirect(array('plugin' => 'webzash', 'controller' => 'reports', 'action' => 'ledgerstatement',
					'ledgerid' => $this->request->data['Report']['ledger_id'],
					'options' => 1,
					'startdate' => $this->request->data['Report']['startdate'],
					'enddate' => $this->request->data['Report']['enddate'],
				));
			} else {
				return $this->redirect(array('plugin' => 'webzash', 'controller' => 'reports', 'action' => 'ledgerstatement',
					'ledgerid' => $this->request->data['Report']['ledger_id'],
				));
			}
		}

		$this->set('showEntries', false);
		$this->set('options', false);

		/* Check if ledger id is set in parameters, if not return and end view here */
		if (empty($this->passedArgs['ledgerid'])) {
			return;
		}

		$ledgerId = $this->passedArgs['ledgerid'];

		/* Check if ledger exists */
		$ledger = $this->Ledger->findById($ledgerId);
		if (!$ledger) {
			$this->Session->setFlash(__d('webzash', 'Ledger not found.'), 'danger');
			return $this->redirect(array('plugin' => 'webzash', 'controller' => 'reports', 'action' => 'ledgerstatement'));
		}

		$this->set('ledger', $ledger);

		$this->request->data['Report']['ledger_id'] = $ledgerId;

		/* Set the approprite search conditions */
		$conditions = array();
		$conditions['Entryitem.ledger_id'] = $ledgerId;

		/* Set the approprite search conditions if custom date is selected */
		$startdate = null;
		$enddate = null;
		if (empty($this->passedArgs['options'])) {
			$this->set('options', false);

			/* Sub-title*/
			$this->set('subtitle', __d('webzash', 'Ledger statement for %s from %s to %s',
				h(toCodeWithName($ledger['Ledger']['code'], $ledger['Ledger']['name'])),
				dateFromSql(Configure::read('Account.startdate')),
				dateFromSql(Configure::read('Account.enddate'))
			));
		} else {
			$this->set('options', true);

			if (!empty($this->passedArgs['startdate'])) {
				/* TODO : Validate date */
				$startdate = dateToSql($this->passedArgs['startdate']);
				$this->request->data['Report']['startdate'] = $this->passedArgs['startdate'];
				$conditions['Entry.date >='] = $startdate;
			}
			if (!empty($this->passedArgs['enddate'])) {
				/* TODO : Validate date */
				$enddate = dateToSql($this->passedArgs['enddate']);
				$this->request->data['Report']['enddate'] = $this->passedArgs['enddate'];
				$conditions['Entry.date <='] = $enddate;
			}

			/* Sub-title*/
			if (!empty($this->passedArgs['startdate']) &&
				!empty($this->passedArgs['enddate'])) {
				$this->set('subtitle', __d('webzash', 'Ledger statement for %s from %s to %s',
					h($ledger['Ledger']['name']),
					dateFromSql(dateToSQL($this->passedArgs['startdate'])),
					dateFromSql(dateToSQL($this->passedArgs['enddate']))
				));
			} else if (!empty($this->passedArgs['startdate'])) {
				$this->set('subtitle', __d('webzash', 'Ledger statement for %s from %s to %s',
					h($ledger['Ledger']['name']),
					dateFromSql(dateToSQL($this->passedArgs['startdate'])),
					dateFromSql(Configure::read('Account.enddate'))
				));
			} else if (!empty($this->passedArgs['enddate'])) {
				$this->set('subtitle', __d('webzash', 'Ledger statement for %s from %s to %s',
					h($ledger['Ledger']['name']),
					dateFromSql(Configure::read('Account.startdate')),
					dateFromSql(dateToSQL($this->passedArgs['enddate']))
				));
			}
		}

		/* Opening and closing titles */
		if (is_null($startdate)) {
			$this->set('opening_title', __d('webzash', 'Opening balance as on %s',
				dateFromSql(Configure::read('Account.startdate'))));
		} else {
			$this->set('opening_title', __d('webzash', 'Opening balance as on %s',
				dateFromSql($startdate)));
		}
		if (is_null($enddate)) {
			$this->set('closing_title', __d('webzash', 'Closing balance as on %s',
				dateFromSql(Configure::read('Account.enddate'))));
		} else {
			$this->set('closing_title', __d('webzash', 'Closing balance as on %s',
				dateFromSql($enddate)));
		}

		/* Calculating opening balance */
		$op = $this->Ledger->openingBalance($ledgerId, $startdate);
		$this->set('op', $op);

		/* Calculating closing balance */
		$cl = $this->Ledger->closingBalance($ledgerId, null, $enddate);
		$this->set('cl', $cl);

		/* Calculate current page opening balance */
		if (!isset($this->passedArgs['page']) || $this->passedArgs['page'] <= 1) {
			/* If 1st page then current page opening balance is opening balance */
			$current_op = $op;
		} else {
			/* Setup limit that selects all previous entryitems */
			$cur_limit = (($this->passedArgs['page'] - 1) *
				$this->Session->read('Wzsetting.row_count'));

			/* Find all previous entryitems */
			$prev_entries = $this->Entry->find('all', array(
				'fields' => array(
					'Entry.id', 'Entry.tag_id', 'Entry.entrytype_id', 'Entry.number', 'Entry.date', 'Entry.dr_total', 'Entry.cr_total', 'Entry.narration',
					'Entryitem.id', 'Entryitem.entry_id', 'Entryitem.ledger_id', 'Entryitem.amount', 'Entryitem.dc', 'Entryitem.reconciliation_date',
				),
				'limit' => $cur_limit,
				'order' => array('Entry.date' => 'asc'),
				'conditions' => $conditions,
				'joins' => array(
					array(
						'table' => 'entryitems',
						'alias' => 'Entryitem',
						'conditions' => array(
							'Entry.id = Entryitem.entry_id'
						)
					),
				),
			));

			/* Initially set as opening balance */
			$temp['amount'] = $op['amount'];
			$temp['dc'] = $op['dc'];

			/* Loop through each previous entryitem and add the amount */
			foreach ($prev_entries as $prev_entry) {
				$temp = calculate_withdc(
					$temp['amount'],
					$temp['dc'],
					$prev_entry['Entryitem']['amount'],
					$prev_entry['Entryitem']['dc']
				);
			}
			$current_op['amount'] = $temp['amount'];
			$current_op['dc'] = $temp['dc'];
		}
		/* Set the current page opening balance */
		$this->set('current_op', $current_op);

		/* Setup pagination */
		if (isset($this->passedArgs['downloadcsv']) ||
			isset($this->passedArgs['downloadxls']) ||
			isset($this->passedArgs['print'])) {
			$this->CustomPaginator->settings = array(
				'Entry' => array(
					'fields' => array(
						'Entry.id', 'Entry.tag_id', 'Entry.entrytype_id', 'Entry.number', 'Entry.date', 'Entry.dr_total', 'Entry.cr_total', 'Entry.narration',
						'Entryitem.id', 'Entryitem.entry_id', 'Entryitem.ledger_id', 'Entryitem.amount', 'Entryitem.dc', 'Entryitem.reconciliation_date',
					),
					'maxLimit' => 100000000000,	/* Max limit */
					'limit' => 100000000000,	/* Max limit */
					'order' => array('Entry.date' => 'asc'),
					'conditions' => $conditions,
					'joins' => array(
						array(
							'table' => 'entryitems',
							'alias' => 'Entryitem',
							'conditions' => array(
								'Entry.id = Entryitem.entry_id'
							)
						),
					),
				),
			);
		} else {
			$this->CustomPaginator->settings = array(
				'Entry' => array(
					'fields' => array(
						'Entry.id', 'Entry.tag_id', 'Entry.entrytype_id', 'Entry.number', 'Entry.date', 'Entry.dr_total', 'Entry.cr_total', 'Entry.narration',
						'Entryitem.id', 'Entryitem.entry_id', 'Entryitem.ledger_id', 'Entryitem.amount', 'Entryitem.dc', 'Entryitem.reconciliation_date',
					),
					'limit' => $this->Session->read('Wzsetting.row_count'),
					'order' => array('Entry.date' => 'asc'),
					'conditions' => $conditions,
					'joins' => array(
						array(
							'table' => 'entryitems',
							'alias' => 'Entryitem',
							'conditions' => array(
								'Entry.id = Entryitem.entry_id'
							)
						),
					),
				),
			);
		}

		/* Pass varaibles to view which are used in Helpers */
		$this->set('allTags', $this->Tag->fetchAll());

		$this->set('entries', $this->CustomPaginator->paginate('Entry'));
		$this->set('showEntries', true);

		/* Download report */
		if (isset($this->passedArgs['downloadcsv'])) {
			Configure::write('Account.currency_format', 'none');
			$this->layout = false;
			$view = new View($this, false);
			$response =  $view->render('Reports/downloadcsv/ledgerstatement');
			$this->response->body($response);
			$this->response->type('text/csv');
			$this->response->download($this->_generateFilename('Ledger Statement', 'csv'));
			return $this->response;
		}

		/* Download report */
		if (isset($this->passedArgs['downloadxls'])) {
			Configure::write('Account.currency_format', 'none');
			$this->layout = 'xls';
			$view = new View($this, false);
			$response =  $view->render('Reports/downloadxls/ledgerstatement');
			$this->response->body($response);
			$this->response->type('application/vnd.ms-excel');
			$this->response->download($this->_generateFilename('Ledger Statement', 'xls'));
			return $this->response;
		}

		/* Print report */
		if (isset($this->passedArgs['print'])) {
			$this->layout = 'print';
			$view = new View($this, false);
			$response =  $view->render('Reports/print/ledgerstatement');
			$this->response->body($response);
			return $this->response;
		}

		return;
	}


/**
 * ledgerentries method
 *
 * @return void
 */
	public function ledgerentries() {

		$this->set('title_for_layout', __d('webzash', 'Ledger Entries'));

		/* Create list of ledgers to pass to view */
		$ledgers = new LedgerTree();
		$ledgers->Group = &$this->Group;
		$ledgers->Ledger = &$this->Ledger;
		$ledgers->current_id = -1;
		$ledgers->restriction_bankcash = 1;
		$ledgers->build(0);
		$ledgers->toList($ledgers, -1);
		$ledgers_disabled = array();
		foreach ($ledgers->ledgerList as $row => $data) {
			if ($row < 0) {
				$ledgers_disabled[] = $row;
			}
		}
		$this->set('ledgers', $ledgers->ledgerList);
		$this->set('ledgers_disabled', $ledgers_disabled);

		if ($this->request->is('post')) {
			/* If valid data then redirect with POST values are URL parameters so that pagination works */
			if (empty($this->request->data['Report']['ledger_id'])) {
				$this->Session->setFlash(__d('webzash', 'Invalid ledger.'), 'danger');
				return $this->redirect(array('plugin' => 'webzash', 'controller' => 'reports', 'action' => 'ledgerentries'));
			}

			if (!empty($this->request->data['Report']['startdate']) ||
				!empty($this->request->data['Report']['enddate'])) {
				return $this->redirect(array('plugin' => 'webzash', 'controller' => 'reports', 'action' => 'ledgerentries',
					'ledgerid' => $this->request->data['Report']['ledger_id'],
					'options' => 1,
					'startdate' => $this->request->data['Report']['startdate'],
					'enddate' => $this->request->data['Report']['enddate'],
				));
			} else {
				return $this->redirect(array('plugin' => 'webzash', 'controller' => 'reports', 'action' => 'ledgerentries',
					'ledgerid' => $this->request->data['Report']['ledger_id'],
				));
			}
		}

		$this->set('showEntries', false);
		$this->set('options', false);

		/* Check if ledger id is set in parameters, if not return and end view here */
		if (empty($this->passedArgs['ledgerid'])) {
			return;
		}

		$ledgerId = $this->passedArgs['ledgerid'];

		/* Check if ledger exists */
		$ledger = $this->Ledger->findById($ledgerId);
		if (!$ledger) {
			$this->Session->setFlash(__d('webzash', 'Ledger not found.'), 'danger');
			return $this->redirect(array('plugin' => 'webzash', 'controller' => 'reports', 'action' => 'ledgerentries'));
		}

		$this->set('ledger', $ledger);

		$this->request->data['Report']['ledger_id'] = $ledgerId;

		/* Set the approprite search conditions */
		$conditions = array();
		$conditions['Entryitem.ledger_id'] = $ledgerId;

		/* Set the approprite search conditions if custom date is selected */
		$startdate = null;
		$enddate = null;
		if (empty($this->passedArgs['options'])) {
			$this->set('options', false);

			/* Sub-title*/
			$this->set('subtitle', __d('webzash', 'Ledger entries for %s from %s to %s',
				h(toCodeWithName($ledger['Ledger']['code'], $ledger['Ledger']['name'])),
				dateFromSql(Configure::read('Account.startdate')),
				dateFromSql(Configure::read('Account.enddate'))
			));
		} else {
			$this->set('options', true);

			if (!empty($this->passedArgs['startdate'])) {
				/* TODO : Validate date */
				$startdate = dateToSql($this->passedArgs['startdate']);
				$this->request->data['Report']['startdate'] = $this->passedArgs['startdate'];
				$conditions['Entry.date >='] = $startdate;
			}
			if (!empty($this->passedArgs['enddate'])) {
				/* TODO : Validate date */
				$enddate = dateToSql($this->passedArgs['enddate']);
				$this->request->data['Report']['enddate'] = $this->passedArgs['enddate'];
				$conditions['Entry.date <='] = $enddate;
			}

			/* Sub-title*/
			if (!empty($this->passedArgs['startdate']) &&
				!empty($this->passedArgs['enddate'])) {
				$this->set('subtitle', __d('webzash', 'Ledger entries for %s from %s to %s',
					h($ledger['Ledger']['name']),
					dateFromSql(dateToSQL($this->passedArgs['startdate'])),
					dateFromSql(dateToSQL($this->passedArgs['enddate']))
				));
			} else if (!empty($this->passedArgs['startdate'])) {
				$this->set('subtitle', __d('webzash', 'Ledger entries for %s from %s to %s',
					h($ledger['Ledger']['name']),
					dateFromSql(dateToSQL($this->passedArgs['startdate'])),
					dateFromSql(Configure::read('Account.enddate'))
				));
			} else if (!empty($this->passedArgs['enddate'])) {
				$this->set('subtitle', __d('webzash', 'Ledger entries for %s from %s to %s',
					h($ledger['Ledger']['name']),
					dateFromSql(Configure::read('Account.startdate')),
					dateFromSql(dateToSQL($this->passedArgs['enddate']))
				));
			}
		}

		/* Opening and closing titles */
		if (is_null($startdate)) {
			$this->set('opening_title', __d('webzash', 'Opening balance as on %s',
				dateFromSql(Configure::read('Account.startdate'))));
		} else {
			$this->set('opening_title', __d('webzash', 'Opening balance as on %s',
				dateFromSql($startdate)));
		}
		if (is_null($enddate)) {
			$this->set('closing_title', __d('webzash', 'Closing balance as on %s',
				dateFromSql(Configure::read('Account.enddate'))));
		} else {
			$this->set('closing_title', __d('webzash', 'Closing balance as on %s',
				dateFromSql($enddate)));
		}

		/* Calculating opening balance */
		$op = $this->Ledger->openingBalance($ledgerId, $startdate);
		$this->set('op', $op);

		/* Calculating closing balance */
		$cl = $this->Ledger->closingBalance($ledgerId, null, $enddate);
		$this->set('cl', $cl);

		/* Setup pagination */
		if (isset($this->passedArgs['download']) ||
			isset($this->passedArgs['downloadxls']) ||
			isset($this->passedArgs['print'])) {
			$this->CustomPaginator->settings = array(
				'Entry' => array(
					'fields' => array(
						'Entry.id', 'Entry.tag_id', 'Entry.entrytype_id', 'Entry.number', 'Entry.date', 'Entry.dr_total', 'Entry.cr_total', 'Entry.narration',
						'Entryitem.id', 'Entryitem.entry_id', 'Entryitem.ledger_id', 'Entryitem.amount', 'Entryitem.dc', 'Entryitem.reconciliation_date',
					),
					'maxLimit' => 100000000000,	/* Max limit */
					'limit' => 100000000000,	/* Max limit */
					'order' => array('Entry.date' => 'desc'),
					'conditions' => $conditions,
					'joins' => array(
						array(
							'table' => 'entryitems',
							'alias' => 'Entryitem',
							'conditions' => array(
								'Entry.id = Entryitem.entry_id'
							)
						),
					),
				),
			);
		} else {
			$this->CustomPaginator->settings = array(
				'Entry' => array(
					'fields' => array(
						'Entry.id', 'Entry.tag_id', 'Entry.entrytype_id', 'Entry.number', 'Entry.date', 'Entry.dr_total', 'Entry.cr_total', 'Entry.narration',
						'Entryitem.id', 'Entryitem.entry_id', 'Entryitem.ledger_id', 'Entryitem.amount', 'Entryitem.dc', 'Entryitem.reconciliation_date',
					),
					'limit' => $this->Session->read('Wzsetting.row_count'),
					'order' => array('Entry.date' => 'desc'),
					'conditions' => $conditions,
					'joins' => array(
						array(
							'table' => 'entryitems',
							'alias' => 'Entryitem',
							'conditions' => array(
								'Entry.id = Entryitem.entry_id'
							)
						),
					),
				),
			);
		}

		/* Pass varaibles to view which are used in Helpers */
		$this->set('allTags', $this->Tag->fetchAll());

		$this->set('entries', $this->CustomPaginator->paginate('Entry'));
		$this->set('showEntries', true);

		/* Download report */
		if (isset($this->passedArgs['downloadcsv'])) {
			Configure::write('Account.currency_format', 'none');
			$this->layout = false;
			$view = new View($this, false);
			$response =  $view->render('Reports/downloadcsv/ledgerentries');
			$this->response->body($response);
			$this->response->type('text/csv');
			$this->response->download($this->_generateFilename('Ledger Entries', 'csv'));
			return $this->response;
		}

		/* Download report */
		if (isset($this->passedArgs['downloadxls'])) {
			Configure::write('Account.currency_format', 'none');
			$this->layout = 'xls';
			$view = new View($this, false);
			$response =  $view->render('Reports/downloadxls/ledgerentries');
			$this->response->body($response);
			$this->response->type('application/vnd.ms-excel');
			$this->response->download($this->_generateFilename('Ledger Entries', 'xls'));
			return $this->response;
		}

		/* Print report */
		if (isset($this->passedArgs['print'])) {
			$this->layout = 'print';
			$view = new View($this, false);
			$response =  $view->render('Reports/print/ledgerentries');
			$this->response->body($response);
			return $this->response;
		}

		return;
	}

/**
 * reconciliation method
 *
 * @return void
 */
	public function reconciliation() {

		$this->set('title_for_layout', __d('webzash', 'Ledger Reconciliation'));

		/* Create list of ledgers to pass to view */
		$ledgers_q = $this->Ledger->find('all', array(
			'fields' => array('Ledger.id', 'Ledger.name', 'Ledger.code'),
			'order' => array('Ledger.name'),
			'conditions' => array('Ledger.reconciliation' => '1'),
		));
		$ledgers = array(0 => __d('webzash', 'Please select...'));
		foreach ($ledgers_q as $row) {
			$ledgers[$row['Ledger']['id']] = toCodeWithName(
				$row['Ledger']['code'], $row['Ledger']['name']
			);
		}
		$this->set('ledgers', $ledgers);

		if ($this->request->is('post')) {

			/* Ledger selection form submitted */
			if (!empty($this->request->data['Report']['submitledger'])) {

				/* If valid data then redirect with POST values are URL parameters so that pagination works */
				if (empty($this->request->data['Report']['ledger_id'])) {
					$this->Session->setFlash(__d('webzash', 'Invalid ledger.'), 'danger');
					return $this->redirect(array('plugin' => 'webzash', 'controller' => 'reports', 'action' => 'reconciliation'));
				}

				if (!empty($this->request->data['Report']['startdate']) ||
					!empty($this->request->data['Report']['enddate']) ||
					!empty($this->request->data['Report']['showall'])) {
					return $this->redirect(array('plugin' => 'webzash', 'controller' => 'reports', 'action' => 'reconciliation',
						'ledgerid' => $this->request->data['Report']['ledger_id'],
						'options' => 1,
						'showall' => $this->request->data['Report']['showall'],
						'startdate' => $this->request->data['Report']['startdate'],
						'enddate' => $this->request->data['Report']['enddate'],
					));
				} else {
					return $this->redirect(array('plugin' => 'webzash', 'controller' => 'reports', 'action' => 'reconciliation',
						'ledgerid' => $this->request->data['Report']['ledger_id']
					));
				}

			} else if (!empty($this->request->data['ReportRec']['submitrec'])) {

				/* Check if acccount is locked */
				if (Configure::read('Account.locked') == 1) {
					$this->Session->setFlash(__d('webzash', 'Sorry, no changes are possible since the account is locked.'), 'danger');
					return $this->redirect(array('plugin' => 'webzash', 'controller' => 'reports', 'action' => 'reconciliation'));
				}

				/* Reconciliation form submitted */
				foreach ($this->request->data['ReportRec'] as $row => $recitem) {
					if (empty($recitem['id'])) {
						continue;
					}
					if (!empty($recitem['recdate'])) {
						$recdate = dateToSql($recitem['recdate']);
						if (!$recdate) {
							$this->Session->setFlash(__d('webzash', 'Invalid reconciliation date.'), 'danger');
							continue;
						}
					} else {
						$recdate = '';
					}

					$this->Entryitem->id = $recitem['id'];
					if (!$this->Entryitem->read()) {
						continue;
					}
					$this->Entryitem->saveField('reconciliation_date', $recdate);
				}
				/* Unset all POST data so that data for reconciliation date is loaded from database */
				unset($this->request->data['ReportRec']);

			} else {
				return $this->redirect(array('plugin' => 'webzash', 'controller' => 'reports', 'action' => 'reconciliation'));
			}
		}

		$this->set('showEntries', false);
		$this->set('options', false);

		/* Check if ledger id is set in parameters, if not return and end view here */
		if (empty($this->passedArgs['ledgerid'])) {
			return;
		}

		$ledgerId = $this->passedArgs['ledgerid'];

		/* Check if ledger exists */
		$ledger = $this->Ledger->findById($ledgerId);
		if (!$ledger) {
			$this->Session->setFlash(__d('webzash', 'Ledger not found.'), 'danger');
			return $this->redirect(array('plugin' => 'webzash', 'controller' => 'reports', 'action' => 'reconciliation'));
		}

		$this->set('ledger', $ledger);

		$this->request->data['Report']['ledger_id'] = $ledgerId;

		/* Set the approprite search conditions */
		$conditions = array();
		$conditions['Entryitem.ledger_id'] = $ledgerId;

		/* Set the approprite search conditions if custom date is selected */
		$startdate = null;
		$enddate = null;

		if (empty($this->passedArgs['options'])) {
			$this->set('options', false);

			/* Sub-title*/
			$this->set('subtitle', __d('webzash', 'Reconciliation report for %s from %s to %s',
				h(toCodeWithName($ledger['Ledger']['code'], $ledger['Ledger']['name'])),
				dateFromSql(Configure::read('Account.startdate')),
				dateFromSql(Configure::read('Account.enddate'))
			));
		} else {
			$this->set('options', true);

			if (!empty($this->passedArgs['showall'])) {
				$this->request->data['Report']['showall'] = 1;
			}
			if (!empty($this->passedArgs['startdate'])) {
				/* TODO : Validate date */
				$startdate = dateToSql($this->passedArgs['startdate']);
				$this->request->data['Report']['startdate'] = $this->passedArgs['startdate'];
				$conditions['Entry.date >='] = $startdate;
			}
			if (!empty($this->passedArgs['enddate'])) {
				/* TODO : Validate date */
				$enddate = dateToSql($this->passedArgs['enddate']);
				$this->request->data['Report']['enddate'] = $this->passedArgs['enddate'];
				$conditions['Entry.date <='] = $enddate;
			}

			/* Sub-title*/
			if (!empty($this->passedArgs['startdate']) &&
				!empty($this->passedArgs['enddate'])) {
				$this->set('subtitle', __d('webzash', 'Reconciliation report for %s from %s to %s',
					h($ledger['Ledger']['name']),
					dateFromSql(dateToSQL($this->passedArgs['startdate'])),
					dateFromSql(dateToSQL($this->passedArgs['enddate']))
				));
			} else if (!empty($this->passedArgs['startdate'])) {
				$this->set('subtitle', __d('webzash', 'Reconciliation report for %s from %s to %s',
					h($ledger['Ledger']['name']),
					dateFromSql(dateToSQL($this->passedArgs['startdate'])),
					dateFromSql(Configure::read('Account.enddate'))
				));
			} else if (!empty($this->passedArgs['enddate'])) {
				$this->set('subtitle', __d('webzash', 'Reconciliation report for %s from %s to %s',
					h($ledger['Ledger']['name']),
					dateFromSql(Configure::read('Account.startdate')),
					dateFromSql(dateToSQL($this->passedArgs['enddate']))
				));
			} else if (empty($this->passedArgs['startdate']) &&
				empty($this->passedArgs['enddate'])) {
				$this->set('subtitle', __d('webzash', 'Reconciliation report for %s from %s to %s',
					h($ledger['Ledger']['name']),
					dateFromSql(Configure::read('Account.startdate')),
					dateFromSql(Configure::read('Account.enddate'))
				));
			}
		}

		if (!empty($this->passedArgs['showall'])) {
			/* Nothing to do */
		} else {
			$conditions['Entryitem.reconciliation_date'] = NULL;
		}

		/* Opening and closing titles */
		if (is_null($startdate)) {
			$this->set('opening_title', __d('webzash', 'Opening balance as on %s',
				dateFromSql(Configure::read('Account.startdate'))));
		} else {
			$this->set('opening_title', __d('webzash', 'Opening balance as on %s',
				dateFromSql($startdate)));
		}
		if (is_null($enddate)) {
			$this->set('closing_title', __d('webzash', 'Closing balance as on %s',
				dateFromSql(Configure::read('Account.enddate'))));
		} else {
			$this->set('closing_title', __d('webzash', 'Closing balance as on %s',
				dateFromSql($enddate)));
		}
		/* Reconciliation pending title */
		$this->set('recpending_title', '');
		if (is_null($startdate) && is_null($enddate)) {
			$this->set('recpending_title', __d('webzash', 'Reconciliation pending from %s to %s',
				dateFromSql(Configure::read('Account.startdate')),
				dateFromSql(Configure::read('Account.enddate'))
			));
		} else if (!is_null($startdate) && !is_null($enddate)) {
			$this->set('recpending_title', __d('webzash', 'Reconciliation pending from %s to %s',
				dateFromSql($startdate), dateFromSql($enddate)
			));
		} else if (is_null($startdate)) {
			$this->set('recpending_title', __d('webzash', 'Reconciliation pending from %s to %s',
				dateFromSql(Configure::read('Account.startdate')),
				dateFromSql($enddate)
			));
		} else if (is_null($enddate)) {
			$this->set('recpending_title', __d('webzash', 'Reconciliation pending from %s to %s',
				dateFromSql($startdate),
				dateFromSql(Configure::read('Account.enddate'))
			));
		}

		/* Calculating opening balance */
		$op = $this->Ledger->openingBalance($ledgerId, $startdate);
		$this->set('op', $op);

		/* Calculating closing balance */
		$cl = $this->Ledger->closingBalance($ledgerId, null, $enddate);
		$this->set('cl', $cl);

		/* Calculating reconciliation pending balance */
		$rp = $this->Ledger->reconciliationPending($ledgerId, $startdate, $enddate);
		$this->set('rp', $rp);

		/* Setup pagination */
		if (isset($this->passedArgs['download']) ||
			isset($this->passedArgs['downloadxls']) ||
			isset($this->passedArgs['print'])) {
			$this->CustomPaginator->settings = array(
				'Entry' => array(
					'fields' => array(
						'Entry.id', 'Entry.tag_id', 'Entry.entrytype_id', 'Entry.number', 'Entry.date', 'Entry.dr_total', 'Entry.cr_total', 'Entry.narration',
						'Entryitem.id', 'Entryitem.entry_id', 'Entryitem.ledger_id', 'Entryitem.amount', 'Entryitem.dc', 'Entryitem.reconciliation_date',
					),
					'maxLimit' => 100000000000,	/* Max limit */
					'limit' => 100000000000,	/* Max limit */
					'order' => array('Entry.date' => 'desc'),
					'conditions' => $conditions,
					'joins' => array(
						array(
							'table' => 'entryitems',
							'alias' => 'Entryitem',
							'conditions' => array(
								'Entry.id = Entryitem.entry_id'
							)
						),
					),
				),
			);
		} else {
			$this->CustomPaginator->settings = array(
				'Entry' => array(
					'fields' => array(
						'Entry.id', 'Entry.tag_id', 'Entry.entrytype_id', 'Entry.number', 'Entry.date', 'Entry.dr_total', 'Entry.cr_total', 'Entry.narration',
						'Entryitem.id', 'Entryitem.entry_id', 'Entryitem.ledger_id', 'Entryitem.amount', 'Entryitem.dc', 'Entryitem.reconciliation_date',
					),
					'limit' => $this->Session->read('Wzsetting.row_count'),
					'order' => array('Entry.date' => 'desc'),
					'conditions' => $conditions,
					'joins' => array(
						array(
							'table' => 'entryitems',
							'alias' => 'Entryitem',
							'conditions' => array(
								'Entry.id = Entryitem.entry_id'
							)
						),
					),
				),
			);
		}

		/* Pass varaibles to view which are used in Helpers */
		$this->set('allTags', $this->Tag->fetchAll());

		$this->set('entries', $this->CustomPaginator->paginate('Entry'));
		$this->set('showEntries', true);

		/* Download report */
		if (isset($this->passedArgs['downloadcsv'])) {
			Configure::write('Account.currency_format', 'none');
			$this->layout = false;
			$view = new View($this, false);
			$response =  $view->render('Reports/downloadcsv/reconciliation');
			$this->response->body($response);
			$this->response->type('text/csv');
			$this->response->download($this->_generateFilename('Reconciliation', 'csv'));
			return $this->response;
		}

		/* Download report */
		if (isset($this->passedArgs['downloadxls'])) {
			Configure::write('Account.currency_format', 'none');
			$this->layout = 'xls';
			$view = new View($this, false);
			$response =  $view->render('Reports/downloadxls/reconciliation');
			$this->response->body($response);
			$this->response->type('application/vnd.ms-excel');
			$this->response->download($this->_generateFilename('Reconciliation', 'xls'));
			return $this->response;
		}

		/* Print report */
		if (isset($this->passedArgs['print'])) {
			$this->layout = 'print';
			$view = new View($this, false);
			$response =  $view->render('Reports/print/reconciliation');
			$this->response->body($response);
			return $this->response;
		}

		return;
	}

	public function beforeFilter() {
		parent::beforeFilter();

		/* Skip the ajax/javascript fields from Security component to prevent request being blackholed */
		$this->Security->unlockedFields = array('startdate', 'enddate',
			'ledger_id');
	}

	/* Authorization check */
	public function isAuthorized($user) {
		if ($this->action === 'index') {
			return $this->Permission->is_allowed('access reports');
		}

		if ($this->action === 'balancesheet') {
			return $this->Permission->is_allowed('access reports');
		}

		if ($this->action === 'profitloss') {
			return $this->Permission->is_allowed('access reports');
		}

		if ($this->action === 'trialbalance') {
			return $this->Permission->is_allowed('access reports');
		}

		if ($this->action === 'ledgerstatement') {
			return $this->Permission->is_allowed('access reports');
		}

		if ($this->action === 'ledgerentries') {
			return $this->Permission->is_allowed('access reports');
		}

		if ($this->action === 'reconciliation') {
			return $this->Permission->is_allowed('access reports');
		}

		if ($this->action === 'cashflow') {
			return $this->Permission->is_allowed('access reports');
		}

		return parent::isAuthorized($user);
	}








	public function cashflow() {
		$this->set('title_for_layout', __d('webzash', 'Cash Flow Statement'));

		// Handle POST: if start/end dates are submitted, redirect with parameters.
		if ($this->request->is('post')) {
			$redirect_params = array('plugin' => 'webzash', 'controller' => 'reports', 'action' => 'cashflow');
			if (!empty($this->request->data['cashflow']['startdate']) || !empty($this->request->data['cashflow']['enddate'])) {
				$redirect_params['options'] = 1;
				if (!empty($this->request->data['cashflow']['startdate'])) {
					$redirect_params['startdate'] = $this->request->data['cashflow']['startdate'];
				}
				if (!empty($this->request->data['cashflow']['enddate'])) {
					$redirect_params['enddate'] = $this->request->data['cashflow']['enddate'];
				}
			}
			return $this->redirect($redirect_params);
		}

		// Initialize dates from configuration or passed arguments.
		$startdate = Configure::read('Account.startdate');
		$enddate   = Configure::read('Account.enddate');
		if (!empty($this->passedArgs['options'])) {
			$this->set('options', true);
			if (!empty($this->passedArgs['startdate'])) {
				$startdate = dateToSQL($this->passedArgs['startdate']);
				$this->request->data['cashflow']['startdate'] = $this->passedArgs['startdate'];
			}
			if (!empty($this->passedArgs['enddate'])) {
				$enddate = dateToSQL($this->passedArgs['enddate']);
				$this->request->data['cashflow']['enddate'] = $this->passedArgs['enddate'];
			}
		} else {
			$this->set('options', false);
		}

		$subtitle = __d('webzash', 'Cash Flow Statement from %s to %s',
			dateFromSql($startdate),
			dateFromSql($enddate)
		);
		$this->set('subtitle', $subtitle);

		// Initialize the cash flow data structure.
		$cashflow = array(
			'beginning_balance'    => 0,
			'beginning_balance_dc' => 'D',
			'ending_balance'       => 0,
			'ending_balance_dc'    => 'D',
			'net_change'           => 0,
			'net_change_dc'        => 'D',
			'operating_activities' => array(),
			'investing_activities' => array(),
			'financing_activities' => array(),
			'totals'               => array(
				'operating'    => 0,
				'operating_dc' => 'D',
				'investing'    => 0,
				'investing_dc' => 'D',
				'financing'    => 0,
				'financing_dc' => 'D'
			)
		);

		// --- 1. Get bank (or cash) accounts by using the ledger type flag (e.g. type==1) ---
		$bank_ledgers = $this->Ledger->find('all', array(
			'conditions' => array('Ledger.type' => 1)
		));
		$bank_ids = array();
		$beginning_balance_total = 0;
		foreach ($bank_ledgers as $ledger) {
			$bank_ids[] = $ledger['Ledger']['id'];
			// Use the Ledger model’s openingBalance() method.
			$op = $this->Ledger->openingBalance($ledger['Ledger']['id'], $startdate);
			// Assume that if op['dc']=='D' it is positive cash, if 'C' then negative.
			$sign = ($op['dc'] == 'D') ? 1 : -1;
			$beginning_balance_total += $op['amount'] * $sign;
		}
		$cashflow['beginning_balance'] = abs($beginning_balance_total);
		$cashflow['beginning_balance_dc'] = ($beginning_balance_total >= 0) ? 'D' : 'C';

		// --- 2. Get all entries in the period that affect these bank accounts ---
		// We now want to pick up the "other" side of each bank transaction.
		if (!empty($bank_ids)) {
			$bank_entries = $this->Entry->find('all', array(
				'conditions' => array(
					'Entry.date >=' => $startdate,
					'Entry.date <=' => $enddate
				),
				'joins' => array(
					// Join to get the bank-side entry item(s)
					array(
						'table'      => 'entryitems',
						'alias'      => 'BankItem',
						'conditions' => array(
							'Entry.id = BankItem.entry_id',
							'BankItem.ledger_id IN' => $bank_ids
						)
					),
					// Join to get the "other" side (non-bank) entry item(s)
					array(
						'table'      => 'entryitems',
						'alias'      => 'OtherItem',
						'conditions' => array(
							'Entry.id = OtherItem.entry_id',
							'OtherItem.ledger_id NOT IN' => $bank_ids
						)
					),
					// Join to get details of the other ledger (especially its id, group, name and code)
					array(
						'table'      => 'ledgers',
						'alias'      => 'OtherLedger',
						'conditions' => array(
							'OtherItem.ledger_id = OtherLedger.id'
						)
					)
				),
				'fields' => array(
					'Entry.id', 'Entry.date',
					'BankItem.amount', 'BankItem.dc',
					'OtherItem.amount',
					'OtherLedger.id', 'OtherLedger.group_id', 'OtherLedger.name', 'OtherLedger.code'
				),
				'order' => 'Entry.date ASC'
			));
		} else {
			$bank_entries = array();
		}

		// --- 3. Process each entry and consolidate (aggregate) by ledger ---
		// We'll build separate associative arrays keyed by OtherLedger.id.
		$consolidated_operating = array();
		$consolidated_investing = array();
		$consolidated_financing = array();
		$operating_total = 0;
		$investing_total = 0;
		$financing_total = 0;

		foreach ($bank_entries as $entry) {
			$other_ledger_id   = $entry['OtherLedger']['id'];
			$other_ledger_name = $entry['OtherLedger']['name'];
			$other_ledger_code = isset($entry['OtherLedger']['code']) ? $entry['OtherLedger']['code'] : '';
			$amount            = $entry['OtherItem']['amount'];
			$bank_dc           = $entry['BankItem']['dc'];
			// If bank side is credit then cash is flowing out.
			$is_outflow = ($bank_dc == 'C');
			// Determine net effect: add amount if inflow, subtract if outflow.
			$net_amount = $amount * ($is_outflow ? -1 : 1);

			// Determine the classification by getting the root group of the "other" ledger.
			$group = $this->Group->findById($entry['OtherLedger']['group_id']);
			$root = $this->getRootGroup($group);
			$root_id = $root['Group']['id'];

			if ($root_id == 3 || $root_id == 4) {
				// Operating activities
				if (!isset($consolidated_operating[$other_ledger_id])) {
					$consolidated_operating[$other_ledger_id] = array(
						'name'   => $other_ledger_name,
						'code'   => $other_ledger_code,
						'amount' => 0
					);
				}
				$consolidated_operating[$other_ledger_id]['amount'] += $net_amount;
				$operating_total += $net_amount;
			} elseif ($root_id == 1) {
				// Investing activities
				if (!isset($consolidated_investing[$other_ledger_id])) {
					$consolidated_investing[$other_ledger_id] = array(
						'name'   => $other_ledger_name,
						'code'   => $other_ledger_code,
						'amount' => 0
					);
				}
				$consolidated_investing[$other_ledger_id]['amount'] += $net_amount;
				$investing_total += $net_amount;
			} elseif ($root_id == 2) {
				// Financing activities
				if (!isset($consolidated_financing[$other_ledger_id])) {
					$consolidated_financing[$other_ledger_id] = array(
						'name'   => $other_ledger_name,
						'code'   => $other_ledger_code,
						'amount' => 0
					);
				}
				$consolidated_financing[$other_ledger_id]['amount'] += $net_amount;
				$financing_total += $net_amount;
			} else {
				// Default to operating activities.
				if (!isset($consolidated_operating[$other_ledger_id])) {
					$consolidated_operating[$other_ledger_id] = array(
						'name'   => $other_ledger_name,
						'code'   => $other_ledger_code,
						'amount' => 0
					);
				}
				$consolidated_operating[$other_ledger_id]['amount'] += $net_amount;
				$operating_total += $net_amount;
			}
		}

		// Now convert the consolidated arrays into list format for the view,
		// converting the net amount to an absolute value and setting the proper DC flag.
		$cashflow['operating_activities'] = array();
		foreach ($consolidated_operating as $ledger) {
			$net = $ledger['amount'];
			$entry_data = array(
				'name'   => $ledger['name'],
				'code'   => $ledger['code'],
				'amount' => abs($net),
				'dc'     => ($net >= 0) ? 'C' : 'D'
			);
			$cashflow['operating_activities'][] = $entry_data;
		}

		$cashflow['investing_activities'] = array();
		foreach ($consolidated_investing as $ledger) {
			$net = $ledger['amount'];
			$entry_data = array(
				'name'   => $ledger['name'],
				'code'   => $ledger['code'],
				'amount' => abs($net),
				'dc'     => ($net >= 0) ? 'C' : 'D'
			);
			$cashflow['investing_activities'][] = $entry_data;
		}

		$cashflow['financing_activities'] = array();
		foreach ($consolidated_financing as $ledger) {
			$net = $ledger['amount'];
			$entry_data = array(
				'name'   => $ledger['name'],
				'code'   => $ledger['code'],
				'amount' => abs($net),
				'dc'     => ($net >= 0) ? 'C' : 'D'
			);
			$cashflow['financing_activities'][] = $entry_data;
		}

		// Set totals (adjust the sign conventions as needed)
		$cashflow['totals']['operating']  = abs($operating_total);
		$cashflow['totals']['operating_dc'] = ($operating_total >= 0) ? 'C' : 'D';
		$cashflow['totals']['investing']  = abs($investing_total);
		$cashflow['totals']['investing_dc'] = ($investing_total >= 0) ? 'C' : 'D';
		$cashflow['totals']['financing']  = abs($financing_total);
		$cashflow['totals']['financing_dc'] = ($financing_total >= 0) ? 'C' : 'D';

		$net_total = $operating_total + $investing_total + $financing_total;
		$cashflow['net_change']    = abs($net_total);
		$cashflow['net_change_dc'] = ($net_total >= 0) ? 'C' : 'D';

		// --- 4. Calculate ending balance ---
		if ($cashflow['beginning_balance_dc'] == 'D') {
			$ending_total = $beginning_balance_total + $net_total;
		} else {
			$ending_total = -$beginning_balance_total + $net_total;
		}
		$cashflow['ending_balance']    = abs($ending_total);
		$cashflow['ending_balance_dc'] = ($ending_total >= 0) ? 'D' : 'C';

		$this->set('cashflow', $cashflow);

		// --- 5. Handle downloads and print requests ---
		if (isset($this->passedArgs['downloadcsv'])) {
			Configure::write('Account.currency_format', 'none');
			$this->layout = false;
			$view = new View($this, false);
			$response = $view->render('Reports/downloadcsv/cashflow');
			$this->response->body($response);
			$this->response->type('text/csv');
			$this->response->download($this->_generateFilename('Cash Flow', 'csv'));
			return $this->response;
		}
		if (isset($this->passedArgs['downloadxls'])) {
			Configure::write('Account.currency_format', 'none');
			$this->layout = 'xls';
			$view = new View($this, false);
			$response = $view->render('Reports/downloadxls/cashflow');
			$this->response->body($response);
			$this->response->type('application/vnd.ms-excel');
			$this->response->download($this->_generateFilename('Cash Flow', 'xls'));
			return $this->response;
		}
		if (isset($this->passedArgs['print'])) {
			$this->layout = 'print';
			$view = new View($this, false);
			$response = $view->render('Reports/print/cashflow');
			$this->response->body($response);
			return $this->response;
		}

		return;
	}

	/**
	 * Helper method that recursively finds the root group.
	 * It assumes that a group with no parent (or a falsy parent_id) is the root.
	 */
	private function getRootGroup($group) {
		if (empty($group['Group']['parent_id'])) {
			return $group;
		} else {
			$parent = $this->Group->findById($group['Group']['parent_id']);
			return $this->getRootGroup($parent);
		}
	}




//
//	public function cashflow() {
//		$this->set('title_for_layout', __d('webzash', 'Cash Flow Statement'));
//
//		// Handle POST: if start/end dates are submitted, redirect with parameters.
//		if ($this->request->is('post')) {
//			$redirect_params = array('plugin' => 'webzash', 'controller' => 'reports', 'action' => 'cashflow');
//			if (!empty($this->request->data['cashflow']['startdate']) || !empty($this->request->data['cashflow']['enddate'])) {
//				$redirect_params['options'] = 1;
//				if (!empty($this->request->data['cashflow']['startdate'])) {
//					$redirect_params['startdate'] = $this->request->data['cashflow']['startdate'];
//				}
//				if (!empty($this->request->data['cashflow']['enddate'])) {
//					$redirect_params['enddate'] = $this->request->data['cashflow']['enddate'];
//				}
//			}
//			return $this->redirect($redirect_params);
//		}
//
//		// Initialize dates from configuration or passed arguments.
//		$startdate = Configure::read('Account.startdate');
//		$enddate   = Configure::read('Account.enddate');
//		if (!empty($this->passedArgs['options'])) {
//			$this->set('options', true);
//			if (!empty($this->passedArgs['startdate'])) {
//				$startdate = dateToSQL($this->passedArgs['startdate']);
//				$this->request->data['cashflow']['startdate'] = $this->passedArgs['startdate'];
//			}
//			if (!empty($this->passedArgs['enddate'])) {
//				$enddate = dateToSQL($this->passedArgs['enddate']);
//				$this->request->data['cashflow']['enddate'] = $this->passedArgs['enddate'];
//			}
//		} else {
//			$this->set('options', false);
//		}
//
//		$subtitle = __d('webzash', 'Cash Flow Statement from %s to %s',
//			dateFromSql($startdate),
//			dateFromSql($enddate)
//		);
//		$this->set('subtitle', $subtitle);
//
//		// Initialize the cash flow data structure.
//		$cashflow = array(
//			'beginning_balance'    => 0,
//			'beginning_balance_dc' => 'D',
//			'ending_balance'       => 0,
//			'ending_balance_dc'    => 'D',
//			'net_change'           => 0,
//			'net_change_dc'        => 'D',
//			'operating_activities' => array(),
//			'investing_activities' => array(),
//			'financing_activities' => array(),
//			'totals'               => array(
//				'operating'  => 0,
//				'operating_dc' => 'D',
//				'investing'  => 0,
//				'investing_dc' => 'D',
//				'financing'  => 0,
//				'financing_dc' => 'D'
//			)
//		);
//
//		// --- 1. Get bank (or cash) accounts by using the ledger type flag (e.g. type==1) ---
//		$bank_ledgers = $this->Ledger->find('all', array(
//			'conditions' => array('Ledger.type' => 1)
//		));
//		$bank_ids = array();
//		$beginning_balance_total = 0;
//		foreach ($bank_ledgers as $ledger) {
//			$bank_ids[] = $ledger['Ledger']['id'];
//			// Use the Ledger model’s openingBalance() method.
//			$op = $this->Ledger->openingBalance($ledger['Ledger']['id'], $startdate);
//			// Assume that if op['dc']=='D' it is positive cash, if 'C' then negative.
//			$sign = ($op['dc'] == 'D') ? 1 : -1;
//			$beginning_balance_total += $op['amount'] * $sign;
//		}
//		$cashflow['beginning_balance'] = abs($beginning_balance_total);
//		$cashflow['beginning_balance_dc'] = ($beginning_balance_total >= 0) ? 'D' : 'C';
//
//		// --- 2. Get all entries in the period that affect these bank accounts ---
//		// We now want to pick up the "other" side of each bank transaction.
//		if (!empty($bank_ids)) {
//			$bank_entries = $this->Entry->find('all', array(
//				'conditions' => array(
//					'Entry.date >=' => $startdate,
//					'Entry.date <=' => $enddate
//				),
//				'joins' => array(
//					// Join to get the bank-side entry item(s)
//					array(
//						'table'      => 'entryitems',
//						'alias'      => 'BankItem',
//						'conditions' => array(
//							'Entry.id = BankItem.entry_id',
//							'BankItem.ledger_id IN' => $bank_ids
//						)
//					),
//					// Join to get the "other" side (non-bank) entry item(s)
//					array(
//						'table'      => 'entryitems',
//						'alias'      => 'OtherItem',
//						'conditions' => array(
//							'Entry.id = OtherItem.entry_id',
//							'OtherItem.ledger_id NOT IN' => $bank_ids
//						)
//					),
//					// Join to get details of the other ledger (especially its group)
//					array(
//						'table'      => 'ledgers',
//						'alias'      => 'OtherLedger',
//						'conditions' => array(
//							'OtherItem.ledger_id = OtherLedger.id'
//						)
//					)
//				),
//				'fields' => array(
//					'Entry.id', 'Entry.date',
//					'BankItem.amount', 'BankItem.dc',
//					'OtherItem.amount',
//					'OtherLedger.group_id'
//				),
//				'order' => 'Entry.date ASC'
//			));
//		} else {
//			$bank_entries = array();
//		}
//
//		// --- 3. Process each entry and classify it using the root group of the “other” ledger ---
//		$operating_total = 0;
//		$investing_total = 0;
//		$financing_total = 0;
//
//		// Example snippet from the cashflow() method in ReportsController:
//		foreach ($bank_entries as $entry) {
//			$other_ledger_name = $entry['OtherLedger']['name'];
//			// Optionally, if your OtherLedger has a 'code' field:
//			$other_ledger_code = isset($entry['OtherLedger']['code']) ? $entry['OtherLedger']['code'] : '';
//
//			$amount  = $entry['OtherItem']['amount'];
//			$bank_dc = $entry['BankItem']['dc'];
//			$is_outflow = ($bank_dc == 'C');  // Credit means cash outflow
//
//			// Build the entry data with name and code.
//			$entry_data = array(
//				'name'   => $other_ledger_name,
//				'code'   => $other_ledger_code,
//				'amount' => $amount,
//				'dc'     => $is_outflow ? 'D' : 'C'
//			);
//
//			// Get the group of the "other" ledger...
//			$group = $this->Group->findById($entry['OtherLedger']['group_id']);
//			$root = $this->getRootGroup($group);
//			$root_id = $root['Group']['id'];
//
//			if ($root_id == 3 || $root_id == 4) {
//				$cashflow['operating_activities'][] = $entry_data;
//				$operating_total += $amount * ($is_outflow ? -1 : 1);
//			} elseif ($root_id == 1) {
//				$cashflow['investing_activities'][] = $entry_data;
//				$investing_total += $amount * ($is_outflow ? -1 : 1);
//			} elseif ($root_id == 2) {
//				$cashflow['financing_activities'][] = $entry_data;
//				$financing_total += $amount * ($is_outflow ? -1 : 1);
//			} else {
//				$cashflow['operating_activities'][] = $entry_data;
//				$operating_total += $amount * ($is_outflow ? -1 : 1);
//			}
//		}
//
//
//		// Set totals (note that you can adjust the sign conventions as needed)
//		$cashflow['totals']['operating']  = abs($operating_total);
//		$cashflow['totals']['operating_dc'] = ($operating_total >= 0) ? 'C' : 'D';
//		$cashflow['totals']['investing']  = abs($investing_total);
//		$cashflow['totals']['investing_dc'] = ($investing_total >= 0) ? 'C' : 'D';
//		$cashflow['totals']['financing']  = abs($financing_total);
//		$cashflow['totals']['financing_dc'] = ($financing_total >= 0) ? 'C' : 'D';
//
//		$net_total = $operating_total + $investing_total + $financing_total;
//		$cashflow['net_change']    = abs($net_total);
//		$cashflow['net_change_dc'] = ($net_total >= 0) ? 'C' : 'D';
//
//		// --- 5. Calculate ending balance ---
//		// Here we “add” the net change to the beginning balance.
//		if ($cashflow['beginning_balance_dc'] == 'D') {
//			$ending_total = $beginning_balance_total + $net_total;
//		} else {
//			$ending_total = -$beginning_balance_total + $net_total;
//		}
//		$cashflow['ending_balance']    = abs($ending_total);
//		$cashflow['ending_balance_dc'] = ($ending_total >= 0) ? 'D' : 'C';
//
//		$this->set('cashflow', $cashflow);
//
//		// --- 6. Handle downloads and print requests (unchanged from your original code) ---
//		if (isset($this->passedArgs['downloadcsv'])) {
//			Configure::write('Account.currency_format', 'none');
//			$this->layout = false;
//			$view = new View($this, false);
//			$response = $view->render('Reports/downloadcsv/cashflow');
//			$this->response->body($response);
//			$this->response->type('text/csv');
//			$this->response->download('cashflow.csv');
//			return $this->response;
//		}
//		if (isset($this->passedArgs['downloadxls'])) {
//			Configure::write('Account.currency_format', 'none');
//			$this->layout = 'xls';
//			$view = new View($this, false);
//			$response = $view->render('Reports/downloadxls/cashflow');
//			$this->response->body($response);
//			$this->response->type('application/vnd.ms-excel');
//			$this->response->download('cashflow.xls');
//			return $this->response;
//		}
//		if (isset($this->passedArgs['print'])) {
//			$this->layout = 'print';
//			$view = new View($this, false);
//			$response = $view->render('Reports/print/cashflow');
//			$this->response->body($response);
//			return $this->response;
//		}
//
//		return;
//	}
//
//	/**
//	 * Helper method that recursively finds the root group.
//	 * It assumes that a group with no parent (or a falsy parent_id) is the root.
//	 */
//	private function getRootGroup($group) {
//		if (empty($group['Group']['parent_id'])) {
//			return $group;
//		} else {
//			$parent = $this->Group->findById($group['Group']['parent_id']);
//			return $this->getRootGroup($parent);
//		}
//	}
//
//
//
//
//
//



/////////
///









//
//	public function cashflow() {
//		$this->set('title_for_layout', __d('webzash', 'Cash Flow Statement'));
//
//		// Handle POST request
//		if ($this->request->is('post')) {
//			$redirect_params = array('plugin' => 'webzash', 'controller' => 'reports', 'action' => 'cashflow');
//
//			if (!empty($this->request->data['cashflow']['startdate']) || !empty($this->request->data['cashflow']['enddate'])) {
//				$redirect_params['options'] = 1;
//				if (!empty($this->request->data['cashflow']['startdate'])) {
//					$redirect_params['startdate'] = $this->request->data['cashflow']['startdate'];
//				}
//				if (!empty($this->request->data['cashflow']['enddate'])) {
//					$redirect_params['enddate'] = $this->request->data['cashflow']['enddate'];
//				}
//			}
//
//			return $this->redirect($redirect_params);
//		}
//
//		// Initialize dates
//		$startdate = Configure::read('Account.startdate');
//		$enddate = Configure::read('Account.enddate');
//
//		// Set options and dates if provided
//		if (!empty($this->passedArgs['options'])) {
//			$this->set('options', true);
//			if (!empty($this->passedArgs['startdate'])) {
//				$startdate = dateToSQL($this->passedArgs['startdate']);
//				$this->request->data['cashflow']['startdate'] = $this->passedArgs['startdate'];
//			}
//			if (!empty($this->passedArgs['enddate'])) {
//				$enddate = dateToSQL($this->passedArgs['enddate']);
//				$this->request->data['cashflow']['enddate'] = $this->passedArgs['enddate'];
//			}
//		} else {
//			$this->set('options', false);
//		}
//
//		// Set subtitle
//		$subtitle = __d('webzash', 'Cash Flow Statement from %s to %s',
//			dateFromSql($startdate),
//			dateFromSql($enddate)
//		);
//		$this->set('subtitle', $subtitle);
//
//		// Initialize cash flow data structure
//		$cashflow = array(
//			'beginning_balance' => 0,
//			'beginning_balance_dc' => 'D',
//			'ending_balance' => 0,
//			'ending_balance_dc' => 'D',
//			'net_change' => 0,
//			'net_change_dc' => 'D',
//			'operating_activities' => array(),
//			'investing_activities' => array(),
//			'financing_activities' => array(),
//			'totals' => array(
//				'operating' => 0,
//				'operating_dc' => 'D',
//				'investing' => 0,
//				'investing_dc' => 'D',
//				'financing' => 0,
//				'financing_dc' => 'D'
//			)
//		);
//
//		// Calculate beginning balance
//		$bank_ledger = $this->Ledger->find('first', array(
//			'conditions' => array(
//				'Ledger.name' => 'bank'
//			)
//		));
//
//		if ($bank_ledger) {
//			$op = $this->Ledger->openingBalance($bank_ledger['Ledger']['id'], $startdate);
//			$cashflow['beginning_balance'] = $op['amount'];
//			$cashflow['beginning_balance_dc'] = $op['dc'];
//		}
//
//		// Get all bank entries
//		$bank_entries = $this->Entry->find('all', array(
//			'conditions' => array(
//				'Entry.date >=' => $startdate,
//				'Entry.date <=' => $enddate
//			),
//			'joins' => array(
//				array(
//					'table' => 'entryitems',
//					'alias' => 'BankItem',
//					'conditions' => array(
//						'Entry.id = BankItem.entry_id',
//						'BankItem.ledger_id' => $bank_ledger['Ledger']['id']
//					)
//				),
//				array(
//					'table' => 'entryitems',
//					'alias' => 'OtherItem',
//					'conditions' => array(
//						'Entry.id = OtherItem.entry_id',
//						'OtherItem.ledger_id !=' => $bank_ledger['Ledger']['id']
//					)
//				),
//				array(
//					'table' => 'ledgers',
//					'alias' => 'OtherLedger',
//					'conditions' => array(
//						'OtherItem.ledger_id = OtherLedger.id'
//					)
//				)
//			),
//			'fields' => array(
//				'Entry.*',
//				'BankItem.*',
//				'OtherItem.*',
//				'OtherLedger.name',
//				'OtherLedger.group_id'
//			),
//			'order' => 'Entry.date ASC'
//		));
//
//		// Track running totals with signs
//		$operating_total = 0;
//		$investing_total = 0;
//		$financing_total = 0;
//
//		foreach ($bank_entries as $entry) {
//			$other_ledger_name = $entry['OtherLedger']['name'];
//			$amount = $entry['OtherItem']['amount'];
//			$bank_dc = $entry['BankItem']['dc'];
//
//			// Determine if this is an inflow or outflow of cash
//			$is_outflow = ($bank_dc == 'C');  // Credit to bank means cash outflow
//
//			// Create entry array
//			$entry_data = array(
//				'name' => $other_ledger_name,
//				'amount' => $amount,
//				'dc' => $is_outflow ? 'D' : 'C'
//			);
//
//			// Get parent group
//			$group = $this->Group->findById($entry['OtherLedger']['group_id']);
//			$parent_id = $group['Group']['parent_id'];
//
//			// Operating activities (using parent groups 3 and 4)
//			if ($parent_id == 3 || $parent_id == 4) {
//				$cashflow['operating_activities'][] = $entry_data;
//				$operating_total += $amount * ($is_outflow ? -1 : 1);
//			}
//			// Investing activities (using parent group 1 and specific groups 5,7)
//			elseif ($parent_id == 1 && in_array($entry['OtherLedger']['group_id'], array(5, 7))) {
//				$cashflow['investing_activities'][] = $entry_data;
//				$investing_total += $amount * ($is_outflow ? -1 : 1);
//			}
//			// Financing activities (using parent group 2 and specific groups 8,10)
//			elseif ($parent_id == 2 && in_array($entry['OtherLedger']['group_id'], array(8, 10))) {
//				$cashflow['financing_activities'][] = $entry_data;
//				$financing_total += $amount * ($is_outflow ? -1 : 1);
//			}
//		}
//
//		// Set totals with proper Dr/Cr
//		$cashflow['totals']['operating'] = abs($operating_total);
//		$cashflow['totals']['operating_dc'] = $operating_total >= 0 ? 'C' : 'D';
//
//		$cashflow['totals']['investing'] = abs($investing_total);
//		$cashflow['totals']['investing_dc'] = $investing_total >= 0 ? 'C' : 'D';
//
//		$cashflow['totals']['financing'] = abs($financing_total);
//		$cashflow['totals']['financing_dc'] = $financing_total >= 0 ? 'C' : 'D';
//
//		// Calculate net change
//		$net_total = $operating_total + $investing_total + $financing_total;
//		$cashflow['net_change'] = abs($net_total);
//		$cashflow['net_change_dc'] = $net_total >= 0 ? 'C' : 'D';
//
//		// Calculate ending balance
//		if ($cashflow['beginning_balance_dc'] == 'D') {
//			$ending_total = $cashflow['beginning_balance'] + $net_total;
//		} else {
//			$ending_total = -$cashflow['beginning_balance'] + $net_total;
//		}
//
//		$cashflow['ending_balance'] = abs($ending_total);
//		$cashflow['ending_balance_dc'] = $ending_total >= 0 ? 'D' : 'C';
//
//		$this->set('cashflow', $cashflow);
//
//		// Handle downloads and printing
//		if (isset($this->passedArgs['downloadcsv'])) {
//			Configure::write('Account.currency_format', 'none');
//			$this->layout = false;
//			$view = new View($this, false);
//			$response = $view->render('Reports/downloadcsv/cashflow');
//			$this->response->body($response);
//			$this->response->type('text/csv');
//			$this->response->download('cashflow.csv');
//			return $this->response;
//		}
//
//		if (isset($this->passedArgs['downloadxls'])) {
//			Configure::write('Account.currency_format', 'none');
//			$this->layout = 'xls';
//			$view = new View($this, false);
//			$response = $view->render('Reports/downloadxls/cashflow');
//			$this->response->body($response);
//			$this->response->type('application/vnd.ms-excel');
//			$this->response->download('cashflow.xls');
//			return $this->response;
//		}
//
//		if (isset($this->passedArgs['print'])) {
//			$this->layout = 'print';
//			$view = new View($this, false);
//			$response = $view->render('Reports/print/cashflow');
//			$this->response->body($response);
//			return $this->response;
//		}
//
//		return;
//	}







//// Works- but incorrect totals.
//	public function cashflow() {
//		$this->set('title_for_layout', __d('webzash', 'Cash Flow Statement'));
//
//		// Handle POST request
//		if ($this->request->is('post')) {
//			$redirect_params = array('plugin' => 'webzash', 'controller' => 'reports', 'action' => 'cashflow');
//
//			if (!empty($this->request->data['Cashflow']['startdate']) || !empty($this->request->data['Cashflow']['enddate'])) {
//				$redirect_params['options'] = 1;
//				if (!empty($this->request->data['Cashflow']['startdate'])) {
//					$redirect_params['startdate'] = $this->request->data['Cashflow']['startdate'];
//				}
//				if (!empty($this->request->data['Cashflow']['enddate'])) {
//					$redirect_params['enddate'] = $this->request->data['Cashflow']['enddate'];
//				}
//			}
//
//			return $this->redirect($redirect_params);
//		}
//
//		// Initialize dates
//		$startdate = Configure::read('Account.startdate');
//		$enddate = Configure::read('Account.enddate');
//
//		// Set options and dates if provided
//		if (!empty($this->passedArgs['options'])) {
//			$this->set('options', true);
//			if (!empty($this->passedArgs['startdate'])) {
//				$startdate = dateToSQL($this->passedArgs['startdate']);
//				$this->request->data['Cashflow']['startdate'] = $this->passedArgs['startdate'];
//			}
//			if (!empty($this->passedArgs['enddate'])) {
//				$enddate = dateToSQL($this->passedArgs['enddate']);
//				$this->request->data['Cashflow']['enddate'] = $this->passedArgs['enddate'];
//			}
//		} else {
//			$this->set('options', false);
//		}
//
//		// Set subtitle
//		$subtitle = __d('webzash', 'Cash Flow Statement from %s to %s',
//			dateFromSql($startdate),
//			dateFromSql($enddate)
//		);
//		$this->set('subtitle', $subtitle);
//
//		// Initialize cash flow data structure
//		$cashflow = array(
//			'beginning_balance' => 0,
//			'ending_balance' => 0,
//			'net_change' => 0,
//			'operating_activities' => array(),
//			'investing_activities' => array(),
//			'financing_activities' => array(),
//			'totals' => array(
//				'operating' => 0,
//				'investing' => 0,
//				'financing' => 0
//			),
//			'subtotals' => array()
//		);
//
//		// Calculate beginning cash balance
//		$cash_ledgers = $this->Ledger->find('all', array(
//			'conditions' => array(
//				'Ledger.type' => 1  // Type 1 for Cash/Bank accounts
//			)
//		));
//
//		foreach ($cash_ledgers as $ledger) {
//			$op = $this->Ledger->openingBalance($ledger['Ledger']['id'], $startdate);
//			if ($op['dc'] == 'D') {
//				$cashflow['beginning_balance'] = calculate($cashflow['beginning_balance'], $op['amount'], '+');
//			} else {
//				$cashflow['beginning_balance'] = calculate($cashflow['beginning_balance'], $op['amount'], '-');
//			}
//		}
//
//		// Define group structure for cash flow activities
//		$cashflow_groups = array(
//			'operating' => array(
//				6,  // Current Assets
//				9,  // Current Liabilities
//				3,  // Income (includes direct & indirect)
//				4,  // Expenses (includes direct & indirect)
//				11, // Direct Income
//				12, // Direct Expenses
//				13, // Indirect Income
//				14, // Indirect Expenses
//				15, // Sales
//				16  // Purchases
//			),
//			'investing' => array(
//				5, // Fixed Assets
//				7  // Investments
//			),
//			'financing' => array(
//				8, // Capital Account
//				10 // Loans (Liabilities)
//			)
//		);
//
//		// Get all ledgers for each activity type
//		$operating_ledgers = $this->Ledger->find('all', array(
//			'conditions' => array('Ledger.group_id' => $cashflow_groups['operating'])
//		));
//
//		$investing_ledgers = $this->Ledger->find('all', array(
//			'conditions' => array('Ledger.group_id' => $cashflow_groups['investing'])
//		));
//
//		$financing_ledgers = $this->Ledger->find('all', array(
//			'conditions' => array('Ledger.group_id' => $cashflow_groups['financing'])
//		));
//
//		// Calculate net income (part of operating activities)
//		$income = new AccountList();
//		$income->Group = &$this->Group;
//		$income->Ledger = &$this->Ledger;
//		$income->start_date = $startdate;
//		$income->end_date = $enddate;
//		$income->affects_gross = -1;
//		$income->start(3);
//
//		$expense = new AccountList();
//		$expense->Group = &$this->Group;
//		$expense->Ledger = &$this->Ledger;
//		$expense->start_date = $startdate;
//		$expense->end_date = $enddate;
//		$expense->affects_gross = -1;
//		$expense->start(4);
//
//		$net_pl = 0;
//		if ($income->cl_total_dc == 'C') {
//			$net_pl = calculate($net_pl, $income->cl_total, '+');
//		} else {
//			$net_pl = calculate($net_pl, $income->cl_total, '-');
//		}
//		if ($expense->cl_total_dc == 'D') {
//			$net_pl = calculate($net_pl, $expense->cl_total, '-');
//		} else {
//			$net_pl = calculate($net_pl, $expense->cl_total, '+');
//		}
//
//		// Add net income to operating activities
//		$cashflow['operating_activities'][] = array(
//			'name' => 'Net Income',
//			'amount' => abs($net_pl),
//			'dc' => $net_pl >= 0 ? 'D' : 'C'
//		);
//
//		// Process operating activities
//		foreach ($operating_ledgers as $ledger) {
//			// Skip if it's a cash/bank account since we handled those already
//			if ($ledger['Ledger']['type'] == 1) continue;
//
//			$cl = $this->Ledger->closingBalance($ledger['Ledger']['id'], $startdate, $enddate);
//			$amount = $cl['dc'] == 'D' ? $cl['amount'] : -$cl['amount'];
//
//			if ($amount != 0) {
//				$cashflow['operating_activities'][] = array(
//					'name' => $ledger['Ledger']['name'],
//					'code' => $ledger['Ledger']['code'],
//					'amount' => abs($amount),
//					'dc' => $amount >= 0 ? 'D' : 'C'
//				);
//				$cashflow['totals']['operating'] = calculate($cashflow['totals']['operating'], $amount, '+');
//			}
//		}
//
//		// Process investing activities
//		foreach ($investing_ledgers as $ledger) {
//			$cl = $this->Ledger->closingBalance($ledger['Ledger']['id'], $startdate, $enddate);
//			$amount = $cl['dc'] == 'D' ? $cl['amount'] : -$cl['amount'];
//
//			if ($amount != 0) {
//				$cashflow['investing_activities'][] = array(
//					'name' => $ledger['Ledger']['name'],
//					'code' => $ledger['Ledger']['code'],
//					'amount' => abs($amount),
//					'dc' => $amount >= 0 ? 'D' : 'C'
//				);
//				$cashflow['totals']['investing'] = calculate($cashflow['totals']['investing'], $amount, '+');
//			}
//		}
//
//		// Process financing activities
//		foreach ($financing_ledgers as $ledger) {
//			$cl = $this->Ledger->closingBalance($ledger['Ledger']['id'], $startdate, $enddate);
//			$amount = $cl['dc'] == 'D' ? $cl['amount'] : -$cl['amount'];
//
//			if ($amount != 0) {
//				$cashflow['financing_activities'][] = array(
//					'name' => $ledger['Ledger']['name'],
//					'code' => $ledger['Ledger']['code'],
//					'amount' => abs($amount),
//					'dc' => $amount >= 0 ? 'D' : 'C'
//				);
//				$cashflow['totals']['financing'] = calculate($cashflow['totals']['financing'], $amount, '+');
//			}
//		}
//
//		// Calculate net change and ending balance
//		$cashflow['net_change'] = calculate($cashflow['totals']['operating'], $cashflow['totals']['investing'], '+');
//		$cashflow['net_change'] = calculate($cashflow['net_change'], $cashflow['totals']['financing'], '+');
//		$cashflow['ending_balance'] = calculate($cashflow['beginning_balance'], $cashflow['net_change'], '+');
//
//		$this->set('cashflow', $cashflow);
//
//		// Handle downloads and printing
//		if (isset($this->passedArgs['downloadcsv'])) {
//			Configure::write('Account.currency_format', 'none');
//			$this->layout = false;
//			$view = new View($this, false);
//			$response = $view->render('Reports/downloadcsv/cashflow');
//			$this->response->body($response);
//			$this->response->type('text/csv');
//			$this->response->download('cashflow.csv');
//			return $this->response;
//		}
//
//		if (isset($this->passedArgs['downloadxls'])) {
//			Configure::write('Account.currency_format', 'none');
//			$this->layout = 'xls';
//			$view = new View($this, false);
//			$response = $view->render('Reports/downloadxls/cashflow');
//			$this->response->body($response);
//			$this->response->type('application/vnd.ms-excel');
//			$this->response->download('cashflow.xls');
//			return $this->response;
//		}
//
//		if (isset($this->passedArgs['print'])) {
//			$this->layout = 'print';
//			$view = new View($this, false);
//			$response = $view->render('Reports/print/cashflow');
//			$this->response->body($response);
//			return $this->response;
//		}
//
//		return;
//	}

//	public function cashflow() {
//		$this->set('title_for_layout', __d('webzash', 'Cash Flow Statement'));
//
//		// Handle POST request for date filtering
//		if ($this->request->is('post')) {
//			$redirect_params = array('plugin' => 'webzash', 'controller' => 'reports', 'action' => 'cashflow');
//			if (!empty($this->request->data['Cashflow']['startdate']) || !empty($this->request->data['Cashflow']['enddate'])) {
//				$redirect_params['options'] = 1;
//				if (!empty($this->request->data['Cashflow']['startdate'])) {
//					$redirect_params['startdate'] = $this->request->data['Cashflow']['startdate'];
//				}
//				if (!empty($this->request->data['Cashflow']['enddate'])) {
//					$redirect_params['enddate'] = $this->request->data['Cashflow']['enddate'];
//				}
//			}
//			return $this->redirect($redirect_params);
//		}
//
//		// Initialize dates
//		$startdate = Configure::read('Account.startdate');
//		$enddate   = Configure::read('Account.enddate');
//		if (!empty($this->passedArgs['options'])) {
//			$this->set('options', true);
//			if (!empty($this->passedArgs['startdate'])) {
//				$startdate = dateToSQL($this->passedArgs['startdate']);
//				$this->request->data['Cashflow']['startdate'] = $this->passedArgs['startdate'];
//			}
//			if (!empty($this->passedArgs['enddate'])) {
//				$enddate = dateToSQL($this->passedArgs['enddate']);
//				$this->request->data['Cashflow']['enddate'] = $this->passedArgs['enddate'];
//			}
//		} else {
//			$this->set('options', false);
//		}
//
//		// Set subtitle for view
//		$subtitle = __d('webzash', 'Cash Flow Statement from %s to %s',
//			dateFromSql($startdate), dateFromSql($enddate)
//		);
//		$this->set('subtitle', $subtitle);
//
//		// -------------------------------
//		// Initialize totals
//		// -------------------------------
//		$beginning = 0;
//		$operating = 0;
//		$investing = 0;
//		$financing = 0;
//
//		// -------------------------------
//		// (1) Calculate Beginning Cash Balance (from all Cash/Bank accounts; type==1)
//		// -------------------------------
//		$cash_ledgers = $this->Ledger->find('all', array(
//			'conditions' => array('Ledger.type' => 1)
//		));
//		foreach ($cash_ledgers as $ledger) {
//			$op = $this->Ledger->openingBalance($ledger['Ledger']['id'], $startdate);
//			// In cash accounts a Debit increases cash and Credit decreases it.
//			if ($op['dc'] == 'D') {
//				$beginning += $op['amount'];
//			} else {
//				$beginning -= $op['amount'];
//			}
//		}
//
//		// -------------------------------
//		// (2) Operating Activities
//		//   (a) Start with Net Income (from the P&L)
//		//       (A net loss will be negative.)
//		// -------------------------------
//		$income = new AccountList();
//		$income->Group = &$this->Group;
//		$income->Ledger = &$this->Ledger;
//		$income->start_date = $startdate;
//		$income->end_date   = $enddate;
//		$income->affects_gross = -1;
//		$income->start(3);
//
//		$expense = new AccountList();
//		$expense->Group = &$this->Group;
//		$expense->Ledger = &$this->Ledger;
//		$expense->start_date = $startdate;
//		$expense->end_date   = $enddate;
//		$expense->affects_gross = -1;
//		$expense->start(4);
//
//		// Using our convention: Income normally has a credit balance and Expenses a debit.
//		// So net income (profit) = (income as positive) minus (expenses).
//		// In our example, Income = 5 and Expenses = 120, yielding a net loss of -115.
//		$net_pl = ($income->cl_total_dc == 'C' ? $income->cl_total : -$income->cl_total)
//			- ($expense->cl_total_dc == 'D' ? $expense->cl_total : -$expense->cl_total);
//		// Add net income (loss) to operating total.
//		$operating += $net_pl;
//		// (For display, a net loss will be shown as "Dr" with the absolute value.)
//
//		// (b) Add back non–cash depreciation.
//		// For Fixed Assets (group 5), assume any reduction is due to depreciation.
//		$fixed_assets_ledgers = $this->Ledger->find('all', array(
//			'conditions' => array('Ledger.group_id' => 5)
//		));
//		foreach ($fixed_assets_ledgers as $ledger) {
//			$op = $this->Ledger->openingBalance($ledger['Ledger']['id'], $startdate);
//			$cl = $this->Ledger->closingBalance($ledger['Ledger']['id'], $startdate, $enddate);
//			if ($op['dc'] == 'D' && $cl['dc'] == 'D' && $op['amount'] > $cl['amount']) {
//				$dep = $op['amount'] - $cl['amount']; // e.g. 1000 - 972 = 28
//				$operating += $dep; // add back depreciation
//			}
//		}
//
//		// (c) Include working capital adjustments from current asset & liability accounts (groups 6 & 9).
//		$working_capital_ledgers = $this->Ledger->find('all', array(
//			'conditions' => array('Ledger.group_id' => array(6, 9))
//		));
//		foreach ($working_capital_ledgers as $ledger) {
//			if ($ledger['Ledger']['type'] == 1) continue; // skip cash accounts
//			$cl = $this->Ledger->closingBalance($ledger['Ledger']['id'], $startdate, $enddate);
//			// For current assets: an increase (Debit) is a use of cash (negative).
//			// For current liabilities: an increase (Credit) is a source (positive).
//			$amount = ($cl['dc'] == 'C') ? $cl['amount'] : -$cl['amount'];
//			$operating += $amount;
//		}
//
//		// At this point in our example, operating should be:
//		// net_pl = -115, depreciation = +28, working capital = 0  -> total = -87.
//
//		// -------------------------------
//		// (3) Investing Activities (use group 7: Investments)
//		// For asset accounts like Investments, an increase (Debit) uses cash.
//		// -------------------------------
//		$investing_ledgers = $this->Ledger->find('all', array(
//			'conditions' => array('Ledger.group_id' => 7)
//		));
//		foreach ($investing_ledgers as $ledger) {
//			$cl = $this->Ledger->closingBalance($ledger['Ledger']['id'], $startdate, $enddate);
//			// If the closing balance is Debit, it indicates an increase (cash outflow)
//			$amount = ($cl['dc'] == 'D') ? -$cl['amount'] : $cl['amount'];
//			$investing += $amount;
//		}
//		// In our example, this yields -20005.
//
//		// -------------------------------
//		// (4) Financing Activities (groups 8 and 10)
//		// For liabilities, a credit increase is a source of cash.
//		// -------------------------------
//		$financing_ledgers = $this->Ledger->find('all', array(
//			'conditions' => array('Ledger.group_id' => array(8, 10))
//		));
//		foreach ($financing_ledgers as $ledger) {
//			$cl = $this->Ledger->closingBalance($ledger['Ledger']['id'], $startdate, $enddate);
//			$amount = ($cl['dc'] == 'C') ? $cl['amount'] : -$cl['amount'];
//			$financing += $amount;
//		}
//		// In our example, owner equity (Cr 19950) plus loan reduction (Dr 31) gives +19919.
//
//		// -------------------------------
//		// (5) Compute Net Change and Ending Cash Balance
//		// -------------------------------
//		$net_change = $operating + $investing + $financing;  // expected: -87 + (-20005) + 19919 = -173
//		$ending = $beginning + $net_change;                    // 1000 + (-173) = 827
//
//		// Store the computed values in the cashflow array
//		$cashflow['beginning_balance'] = $beginning;
//		$cashflow['operating_total']   = $operating;
//		$cashflow['investing_total']   = $investing;
//		$cashflow['financing_total']   = $financing;
//		$cashflow['net_change']        = $net_change;
//		$cashflow['ending_balance']    = $ending;
//
//		$this->set('cashflow', $cashflow);
//
//		// The view should now display (using proper sign–conversion):
//		//   Operating Activities: Net cash used of 87 (i.e. Dr 87)
//		//   Investing Activities: Cash used 20005 (Dr 20005)
//		//   Financing Activities: Cash provided 19919 (Cr 19919)
//		//   Net Change: –173 (Dr 173) and Ending Balance: 827 (Dr 827)
//		//
//		// (Any view formatting helper should check the sign and then output "Dr" for negative amounts
//		// and "Cr" for positive amounts.)
//
//		// Handle downloads and printing (unchanged)
//		if (isset($this->passedArgs['downloadcsv'])) {
//			Configure::write('Account.currency_format', 'none');
//			$this->layout = false;
//			$view = new View($this, false);
//			$response = $view->render('Reports/downloadcsv/cashflow');
//			$this->response->body($response);
//			$this->response->type('text/csv');
//			$this->response->download('cashflow.csv');
//			return $this->response;
//		}
//		if (isset($this->passedArgs['downloadxls'])) {
//			Configure::write('Account.currency_format', 'none');
//			$this->layout = 'xls';
//			$view = new View($this, false);
//			$response = $view->render('Reports/downloadxls/cashflow');
//			$this->response->body($response);
//			$this->response->type('application/vnd.ms-excel');
//			$this->response->download('cashflow.xls');
//			return $this->response;
//		}
//		if (isset($this->passedArgs['print'])) {
//			$this->layout = 'print';
//			$view = new View($this, false);
//			$response = $view->render('Reports/print/cashflow');
//			$this->response->body($response);
//			return $this->response;
//		}
//
//		return;
//	}
//
//	public function cashflow() {
//		$this->set('title_for_layout', __d('webzash', 'Cash Flow Statement'));
//
//		// Handle POST (date filter) like in other reports
//		if ($this->request->is('post')) {
//			$redirect_params = array('plugin' => 'webzash', 'controller' => 'reports', 'action' => 'cashflow');
//			if (!empty($this->request->data['Cashflow']['startdate']) || !empty($this->request->data['Cashflow']['enddate'])) {
//				$redirect_params['options'] = 1;
//				if (!empty($this->request->data['Cashflow']['startdate'])) {
//					$redirect_params['startdate'] = $this->request->data['Cashflow']['startdate'];
//				}
//				if (!empty($this->request->data['Cashflow']['enddate'])) {
//					$redirect_params['enddate'] = $this->request->data['Cashflow']['enddate'];
//				}
//			}
//			return $this->redirect($redirect_params);
//		}
//
//		// Initialize dates from configuration; override with passedArgs if present.
//		$startdate = Configure::read('Account.startdate');
//		$enddate   = Configure::read('Account.enddate');
//		if (!empty($this->passedArgs['options'])) {
//			$this->set('options', true);
//			if (!empty($this->passedArgs['startdate'])) {
//				$startdate = dateToSQL($this->passedArgs['startdate']);
//				$this->request->data['Cashflow']['startdate'] = $this->passedArgs['startdate'];
//			}
//			if (!empty($this->passedArgs['enddate'])) {
//				$enddate = dateToSQL($this->passedArgs['enddate']);
//				$this->request->data['Cashflow']['enddate'] = $this->passedArgs['enddate'];
//			}
//		} else {
//			$this->set('options', false);
//		}
//		$subtitle = __d('webzash', 'Cash Flow Statement from %s to %s',
//			dateFromSql($startdate),
//			dateFromSql($enddate)
//		);
//		$this->set('subtitle', $subtitle);
//
//		// -------------------------------
//		// (1) Beginning Cash Balance:
//		//    Sum of cash/bank ledgers (Ledger.type == 1)
//		// -------------------------------
//		$beginning = 0;
//		$cash_ledgers = $this->Ledger->find('all', array(
//			'conditions' => array('Ledger.type' => 1)
//		));
//		foreach ($cash_ledgers as $ledger) {
//			$op = $this->Ledger->openingBalance($ledger['Ledger']['id'], $startdate);
//			// In cash accounts, Debit increases cash; Credit decreases it.
//			if ($op['dc'] == 'D') {
//				$beginning += $op['amount'];
//			} else {
//				$beginning -= $op['amount'];
//			}
//		}
//
//		// -------------------------------
//		// (2) Operating Activities: Use net income from the P&L.
//		// -------------------------------
//		$income = new AccountList();
//		$income->Group = &$this->Group;
//		$income->Ledger = &$this->Ledger;
//		$income->start_date = $startdate;
//		$income->end_date   = $enddate;
//		$income->affects_gross = -1;
//		$income->start(3);
//
//		$expense = new AccountList();
//		$expense->Group = &$this->Group;
//		$expense->Ledger = &$this->Ledger;
//		$expense->start_date = $startdate;
//		$expense->end_date   = $enddate;
//		$expense->affects_gross = -1;
//		$expense->start(4);
//
//		// Compute net income (loss) as: Income - Expense.
//		// In our example: 5 - 120 = -115 (a net loss of 115)
//		$net_pl = ($income->cl_total_dc == 'C' ? $income->cl_total : -$income->cl_total)
//			- ($expense->cl_total_dc == 'D' ? $expense->cl_total : -$expense->cl_total);
//		$operating_total = $net_pl;
//		// Build operating activities array:
//		$operating_activities = array();
//		$operating_activities[] = array(
//			'name' => __d('webzash', 'Net Income'),
//			'amount' => abs($net_pl),
//			'dc' => ($net_pl < 0 ? 'Dr' : 'Cr')
//		);
//
//		// -------------------------------
//		// (3) Investing Activities:
//		//    For group 7 (Investments), compute change (delta) from opening to closing.
//		//    For asset accounts, an increase uses cash so: cash effect = –(closing – opening)
//		// -------------------------------
//		$investing_total = 0;
//		$investing_activities = array();
//		$investing_ledgers = $this->Ledger->find('all', array(
//			'conditions' => array('Ledger.group_id' => 7)
//		));
//		foreach ($investing_ledgers as $ledger) {
//			$op = $this->Ledger->openingBalance($ledger['Ledger']['id'], $startdate);
//			$cl = $this->Ledger->closingBalance($ledger['Ledger']['id'], $startdate, $enddate);
//			// For asset accounts, treat Debit as positive.
//			$op_val = ($op['dc'] == 'D') ? $op['amount'] : -$op['amount'];
//			$cl_val = ($cl['dc'] == 'D') ? $cl['amount'] : -$cl['amount'];
//			$delta = $cl_val - $op_val; // Change in balance
//			$effect = -$delta;         // Increase in asset uses cash
//			$investing_total += $effect;
//			$investing_activities[] = array(
//				'name' => $ledger['Ledger']['name'],
//				'amount' => abs($effect),
//				'dc' => ($effect < 0 ? 'Dr' : 'Cr')
//			);
//		}
//		// In our example, if the investment ledger went from 0 to Dr 20,005 then delta = 20,005 and effect = -20,005.
//
//		// -------------------------------
//		// (4) Financing Activities:
//		//    For groups 8 and 10 (Capital & Loans), assume that the accounts are normally Credit.
//		//    For financing, we use the credit amounts directly:
//		//         change = (closing_credit - opening_credit)
//		// -------------------------------
//		$financing_total = 0;
//		$financing_activities = array();
//		$financing_ledgers = $this->Ledger->find('all', array(
//			'conditions' => array('Ledger.group_id' => array(8,10))
//		));
//		foreach ($financing_ledgers as $ledger) {
//			$op = $this->Ledger->openingBalance($ledger['Ledger']['id'], $startdate);
//			$cl = $this->Ledger->closingBalance($ledger['Ledger']['id'], $startdate, $enddate);
//			// For financing accounts, if the balance is Credit, use its amount; otherwise assume 0.
//			$op_amount = ($op['dc'] == 'C') ? $op['amount'] : 0;
//			$cl_amount = ($cl['dc'] == 'C') ? $cl['amount'] : 0;
//			$change = $cl_amount - $op_amount;
//			$financing_total += $change;
//			$financing_activities[] = array(
//				'name' => $ledger['Ledger']['name'],
//				'amount' => abs($change),
//				'dc' => ($change >= 0 ? 'Cr' : 'Dr')
//			);
//		}
//		// In our example:
//		// - Owner equity: Opening = 1900, Closing = 21850, so change = 21850 - 1900 = +19950.
//		// - Loan: Opening = 100, Closing = 69, so change = 69 - 100 = -31.
//		// Net financing = 19950 + (-31) = +19919.
//
//		// -------------------------------
//		// (5) Compute Net Change and Ending Cash Balance
//		// -------------------------------
//		$net_change = $operating_total + $investing_total + $financing_total;
//		// Expected: -115 + (-20005) + 19919 = -201.
//		$ending = $beginning + $net_change;
//		// Expected ending cash: 1000 + (-201) = 799.
//
//		// Build final cashflow array with keys for the view.
//		$cashflow = array(
//			'beginning_balance' => $beginning,
//			'operating_total'   => $operating_total,
//			'investing_total'   => $investing_total,
//			'financing_total'   => $financing_total,
//			'net_change'        => $net_change,
//			'ending_balance'    => $ending,
//			'operating_activities' => $operating_activities,
//			'investing_activities' => $investing_activities,
//			'financing_activities' => $financing_activities,
//			'totals' => array(
//				'operating' => $operating_total,
//				'investing' => $investing_total,
//				'financing' => $financing_total
//			)
//		);
//
//		$this->set('cashflow', $cashflow);
//
//		// With the test data provided, the expected results are:
//		//   Beginning Cash Balance: Dr 1,000.00
//		//   Operating Activities: Net Income = Dr 115.00 (i.e. use of cash 115)
//		//   Investing Activities: investment = Dr 20,005.00
//		//   Financing Activities: owner equity change = Cr 19,950.00; loan change = Dr 31.00; net = Cr 19,919.00
//		//   Net Change in Cash = Dr 201.00 and Ending Cash Balance = Dr 799.00
//
//		// Handle downloads/printing as in other reports.
//		if (isset($this->passedArgs['downloadcsv'])) {
//			Configure::write('Account.currency_format', 'none');
//			$this->layout = false;
//			$view = new View($this, false);
//			$response = $view->render('Reports/downloadcsv/cashflow');
//			$this->response->body($response);
//			$this->response->type('text/csv');
//			$this->response->download('cashflow.csv');
//			return $this->response;
//		}
//		if (isset($this->passedArgs['downloadxls'])) {
//			Configure::write('Account.currency_format', 'none');
//			$this->layout = 'xls';
//			$view = new View($this, false);
//			$response = $view->render('Reports/downloadxls/cashflow');
//			$this->response->body($response);
//			$this->response->type('application/vnd.ms-excel');
//			$this->response->download('cashflow.xls');
//			return $this->response;
//		}
//		if (isset($this->passedArgs['print'])) {
//			$this->layout = 'print';
//			$view = new View($this, false);
//			$response = $view->render('Reports/print/cashflow');
//			$this->response->body($response);
//			return $this->response;
//		}
//		return;
//	}
//



}
