<div id="actionlinks">
	<?php echo $this->Html->link('Add Keyword Mapping', array('action' => 'add'), array('class' => 'btn btn-primary')); ?>
</div>

<div class="box-container">
	<table class="stripped">
		<tr class="table-top">
			<th>Keyword</th>
			<th>Ledger</th>
			<th>Ledger ID</th>
			<th>Amount</th>
			<th>Transaction Type</th>
			<th>Amount Condition</th>
			<th>Debit/Credit</th>
			<th>Actions</th>
		</tr>
		<?php foreach ($ledgerKeywords as $mapping): ?>
			<tr class="tr-ledger">
				<td><?php echo h($mapping['LedgerKeyword']['keyword']); ?></td>
				<td><?php echo h($mapping['LedgerKeyword']['ledger_name']); ?></td>
				<td><?php echo h($mapping['LedgerKeyword']['ledger_id']); ?></td>
				<td><?php echo h($mapping['LedgerKeyword']['amount']); ?></td>
				<td><?php echo h($mapping['LedgerKeyword']['transaction_type']); ?></td>
				<td>
					<?php
					$amountOperator = $mapping['LedgerKeyword']['amount_condition'];
					$amount = $mapping['LedgerKeyword']['amount'];
					if ($amountOperator && $amount) {
						$operatorSymbols = array('gt' => '>', 'lt' => '<', 'eq' => '=');
						$operatorSymbol = isset($operatorSymbols[$amountOperator]) ? $operatorSymbols[$amountOperator] : '';
						echo $operatorSymbol . ' ' . h($amount);
					} else {
						echo 'Any';
					}
					?>
				</td>
				<td><?php echo h($mapping['LedgerKeyword']['debit_or_credit'] ?: 'Any'); ?></td>

				<td>
					<?php echo $this->Html->link('Edit', array('action' => 'edit', $mapping['LedgerKeyword']['id'])); ?>
					|
					<?php echo $this->Form->postLink('Delete', array('action' => 'delete', $mapping['LedgerKeyword']['id']), array('confirm' => 'Are you sure?')); ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
</div>
