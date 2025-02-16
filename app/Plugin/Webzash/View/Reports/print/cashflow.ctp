<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title><?php echo h($subtitle); ?></title>
	<style type="text/css">
		/* Basic print styles */
		body {
			font-family: Arial, sans-serif;
			font-size: 13px;
			margin: 20px;
		}
		.subtitle {
			text-align: center;
			margin-bottom: 20px;
		}
		.subtitle div {
			font-size: 18px;
			font-weight: bold;
		}
		table {
			width: 100%;
			border-collapse: collapse;
			margin-bottom: 20px;
		}
		table.stripped th,
		table.stripped td {
			border: 1px solid #ccc;
			padding: 8px;
		}
		table.stripped th {
			background-color: #f2f2f2;
		}
		.text-right {
			text-align: right;
		}
		.bold-text {
			font-weight: bold;
		}
	</style>
</head>
<body>

<!-- Report Title -->
<div class="subtitle">
	<?php if (Configure::read('Account.name')): ?>
		<div><?php echo h(Configure::read('Account.name')); ?></div>
	<?php endif; ?>
	<?php echo $subtitle; ?>
</div>

<!-- Cash Flow Statement -->
<table class="stripped">
	<tr>
		<th colspan="3"><?php echo __d('webzash', 'Cash Flow Statement'); ?></th>
	</tr>
	<!-- Beginning Cash Balance -->
	<tr>
		<td><?php echo __d('webzash', 'Beginning Cash Balance'); ?></td>
		<td>&nbsp;</td>
		<td class="text-right"><?php echo toCurrency('D', $cashflow['beginning_balance']); ?></td>
	</tr>
	<tr><td colspan="3">&nbsp;</td></tr> <!-- Spacing Row -->

	<!-- Operating Activities -->
	<tr>
		<th colspan="3"><?php echo __d('webzash', 'Cash Flow from Operating Activities'); ?></th>
	</tr>
	<?php foreach ($cashflow['operating_activities'] as $item): ?>
		<tr>
			<td><?php echo h($item['name']); ?></td>
			<td class="text-right"><?php echo isset($item['year']) ? h($item['year']) : ''; ?></td>
			<td class="text-right"><?php echo toCurrency($item['dc'], $item['amount']); ?></td>
		</tr>
	<?php endforeach; ?>
	<!-- Operating Subtotals -->
	<?php if (!empty($cashflow['operating_subtotals'])): ?>
		<?php foreach ($cashflow['operating_subtotals'] as $subTitle => $amount): ?>
			<tr>
				<td><?php echo __d('webzash', 'Total for'); ?> <?php echo h($subTitle); ?></td>
				<td>&nbsp;</td>
				<td class="text-right"><?php echo toCurrency('D', $amount); ?></td>
			</tr>
		<?php endforeach; ?>
	<?php endif; ?>
	<tr class="bold-text">
		<td><?php echo __d('webzash', 'Net cash provided by Operating Activities'); ?></td>
		<td>&nbsp;</td>
		<td class="text-right"><?php echo toCurrency('D', $cashflow['totals']['operating']); ?></td>
	</tr>
	<tr><td colspan="3">&nbsp;</td></tr> <!-- Spacing Row -->

	<!-- Investing Activities -->
	<tr>
		<th colspan="3"><?php echo __d('webzash', 'Cash Flow from Investing Activities'); ?></th>
	</tr>
	<?php foreach ($cashflow['investing_activities'] as $item): ?>
		<tr>
			<td><?php echo h($item['name']); ?></td>
			<td class="text-right"><?php echo isset($item['code']) ? h($item['code']) : ''; ?></td>
			<td class="text-right"><?php echo toCurrency($item['dc'], $item['amount']); ?></td>
		</tr>
	<?php endforeach; ?>
	<tr class="bold-text">
		<td><?php echo __d('webzash', 'Net cash provided by Investing Activities'); ?></td>
		<td>&nbsp;</td>
		<td class="text-right"><?php echo toCurrency('D', $cashflow['totals']['investing']); ?></td>
	</tr>
	<tr><td colspan="3">&nbsp;</td></tr> <!-- Spacing Row -->

	<!-- Financing Activities -->
	<tr>
		<th colspan="3"><?php echo __d('webzash', 'Cash Flow from Financing Activities'); ?></th>
	</tr>
	<?php foreach ($cashflow['financing_activities'] as $item): ?>
		<tr>
			<td><?php echo h($item['name']); ?></td>
			<td class="text-right"><?php echo isset($item['code']) ? h($item['code']) : ''; ?></td>
			<td class="text-right"><?php echo toCurrency($item['dc'], $item['amount']); ?></td>
		</tr>
	<?php endforeach; ?>
	<tr class="bold-text">
		<td><?php echo __d('webzash', 'Net cash provided by Financing Activities'); ?></td>
		<td>&nbsp;</td>
		<td class="text-right"><?php echo toCurrency('D', $cashflow['totals']['financing']); ?></td>
	</tr>
	<tr><td colspan="3">&nbsp;</td></tr> <!-- Spacing Row -->

	<!-- Net Change and Ending Balance -->
	<tr class="bold-text">
		<td><?php echo __d('webzash', 'Net Change in cash'); ?></td>
		<td>&nbsp;</td>
		<td class="text-right"><?php echo toCurrency('D', $cashflow['net_change']); ?></td>
	</tr>
	<tr class="bold-text">
		<td><?php echo __d('webzash', 'Ending Cash Balance'); ?></td>
		<td>&nbsp;</td>
		<td class="text-right"><?php echo toCurrency('D', $cashflow['ending_balance']); ?></td>
	</tr>
</table>

</body>
</html>
