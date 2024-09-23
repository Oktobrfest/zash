<div class="box-container">
	<?php
	echo $this->Form->create('LedgerKeyword', array('class' => 'form-horizontal'));

	// Keyword input
	echo '<div class="form-group">';
	echo $this->Form->label('keyword', 'Keyword', array('class' => 'control-label col-md-2'));
	echo '<div class="col-md-10">';
	echo $this->Form->input('keyword', array(
		'class' => 'form-control',
		'label' => false
	));
	echo '</div>';
	echo '</div>';

	// Ledger dropdown
	echo '<div class="form-group">';
	echo $this->Form->label('ledger_id', 'Ledger', array('class' => 'control-label col-md-2'));
	echo '<div class="col-md-10">';
	echo $this->Form->input('ledger_id', array(
		'type' => 'select',
		'options' => $ledgerOptions,
		'class' => 'form-control',
		'label' => false
	));
	echo '</div>';
	echo '</div>';

	// Transaction Amount input (optional)
	echo '<div class="form-group">';
	echo $this->Form->label('amount', 'Amount (Optional)', array('class' => 'control-label col-md-2'));
	echo '<div class="col-md-10">';
	echo $this->Form->input('amount', array(
		'type' => 'number',
		'step' => '0.01',
		'class' => 'form-control',
		'label' => false,
		'required' => false
	));
	echo '</div>';
	echo '</div>';

	// Amount condition (gt, lt, eq)
	echo '<div class="form-group">';
	echo $this->Form->label('amount_condition', 'Amount Condition', array('class' => 'control-label col-md-2'));
	echo '<div class="col-md-10">';
	echo $this->Form->input('amount_condition', array(
		'type' => 'select',
		'options' => array(
			'' => 'Any',
			'gt' => 'Greater Than',
			'lt' => 'Less Than',
			'eq' => 'Equal To'
		),
		'class' => 'form-control',
		'label' => false,
		'required' => false
	));
	echo '</div>';
	echo '</div>';

	// Debit or Credit dropdown
	echo '<div class="form-group">';
	echo $this->Form->label('debit_or_credit', 'Debit or Credit (Optional)', array('class' => 'control-label col-md-2'));
	echo '<div class="col-md-10">';
	echo $this->Form->input('debit_or_credit', array(
		'type' => 'select',
		'options' => array(
			'' => 'Any',
			'D' => 'Debit',
			'C' => 'Credit'
		),
		'class' => 'form-control',
		'label' => false,
		'required' => false
	));
	echo '</div>';
	echo '</div>';

	// Transaction Type dropdown (optional)
	echo '<div class="form-group">';
	echo $this->Form->label('transaction_type', 'Transaction Type (Optional)', array('class' => 'control-label col-md-2'));
	echo '<div class="col-md-10">';
	echo $this->Form->input('transaction_type', array(
		'type' => 'select',
		'options' => array(
			'' => 'Any',
			'debit' => 'Debit',
			'credit' => 'Credit',
			'payment' => 'Payment',
			'receipt' => 'Receipt'
		),
		'class' => 'form-control',
		'label' => false,
		'required' => false
	));
	echo '</div>';
	echo '</div>';

	// Submit button
	echo '<div class="form-group">';
	echo '<div class="col-md-offset-2 col-md-10">';
	echo $this->Form->button('Save', array('class' => 'btn btn-primary'));
	echo '</div>';
	echo '</div>';

	echo $this->Form->end();
	?>
</div>
