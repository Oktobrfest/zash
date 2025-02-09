<?php
echo $this->Html->script('select2');
?>

<script type="text/javascript">
	$(document).ready(function() {
		$("#GroupSourceAccountId").select2({width:'100%'});
	});
</script>

<div class="groups copy form">
	<?php
	echo $this->Form->create('Group', array(
		'inputDefaults' => array(
			'div' => 'form-group',
			'wrapInput' => false,
			'class' => 'form-control',
		),
	));

	echo $this->Form->input('source_account_id', array(
		'type' => 'select',
		'options' => $wzaccounts,
		'label' => __d('webzash', 'Preview copying groups from account'),
		'div' => 'form-group',
		'required' => true,
	));

	echo '<div class="form-group">';
	echo $this->Form->submit(__d('webzash', 'Copy Groups'), array(
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

	if (isset($show_preview) && $show_preview) {
		echo '<div class="preview-output">';
		echo '<h3>' . __d('webzash', 'Preview of Changes') . '</h3>';
		echo '<pre class="well">';
		echo h($preview_output);
		echo '</pre>';
		echo '</div>';
	}
	?>
</div>

