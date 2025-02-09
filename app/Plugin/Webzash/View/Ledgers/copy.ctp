<?php
echo $this->Html->script('select2'); // CHANGED THIS – loading select2 script for ledger copy form
?>

<script type="text/javascript">
	$(document).ready(function() {
		$("#LedgerSourceAccountId").select2({width:'100%'}); // CHANGED THIS – using a unique id for ledgers
	});
</script>

<div class="ledgers copy form"> <!-- CHANGED THIS – class updated to indicate ledger copying -->
	<?php
	echo $this->Form->create('Ledger', array( // CHANGED THIS – form created for Ledger model
		'inputDefaults' => array(
			'div' => 'form-group', // CHANGED THIS – same form-group styling as groups
			'wrapInput' => false, // CHANGED THIS – same configuration as groups
			'class' => 'form-control', // CHANGED THIS – same styling as groups
		),
	));

	echo $this->Form->input('source_account_id', array(
		'type' => 'select',
		'options' => $wzaccounts,
		'label' => __d('webzash', 'Preview copying ledgers from account'), // CHANGED THIS – label updated for ledgers
		'div' => 'form-group',
		'required' => true,
	));

	echo '<div class="form-group">';
	echo $this->Form->submit(__d('webzash', 'Copy Ledgers'), array( // CHANGED THIS – submit button text updated
		'div' => false,
		'class' => 'btn btn-primary'
	));
	echo $this->Html->tag('span', '', array('class' => 'link-pad'));
	echo $this->Html->link(__d('webzash', 'Cancel'),
		array('plugin' => 'webzash', 'controller' => 'accounts', 'action' => 'show'),
		array('class' => 'btn btn-default')
	);
	echo '</div>';

	echo $this->Form->end();

	if (isset($show_preview) && $show_preview) { // CHANGED THIS – if preview is set then show output
		echo '<div class="preview-output">';
		echo '<h3>' . __d('webzash', 'Preview of Changes') . '</h3>';
		echo '<pre class="well">';
		echo h($preview_output);
		echo '</pre>';
		echo '</div>';
	}
	?>
</div>
