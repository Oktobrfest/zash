<?php
// File: app/Plugin/Webzash/View/Reports/cashflow.ctp
?>

<script type="text/javascript">
	$(document).ready(function() {
		$("#accordion").accordion({
			collapsible: true,
			<?php if ($options == false) { echo 'active: false'; } ?>
		});

		/* Calculate date range in javascript */
		startDate = new Date(<?php echo strtotime(Configure::read('Account.startdate')) * 1000; ?> + (new Date().getTimezoneOffset() * 60 * 1000));
		endDate = new Date(<?php echo strtotime(Configure::read('Account.enddate')) * 1000; ?> + (new Date().getTimezoneOffset() * 60 * 1000));

		/* Setup jQuery datepicker ui */
		$('#CashflowStartdate').datepicker({
			minDate: startDate,
			maxDate: endDate,
			dateFormat: '<?php echo Configure::read('Account.dateformatJS'); ?>',
			numberOfMonths: 1,
			onClose: function(selectedDate) {
				if (selectedDate) {
					$("#CashflowEnddate").datepicker("option", "minDate", selectedDate);
				} else {
					$("#CashflowEnddate").datepicker("option", "minDate", startDate);
				}
			}
		});
		$('#CashflowEnddate').datepicker({
			minDate: startDate,
			maxDate: endDate,
			dateFormat: '<?php echo Configure::read('Account.dateformatJS'); ?>',
			numberOfMonths: 1,
			onClose: function(selectedDate) {
				if (selectedDate) {
					$("#CashflowStartdate").datepicker("option", "maxDate", selectedDate);
				} else {
					$("#CashflowStartdate").datepicker("option", "maxDate", endDate);
				}
			}
		});
	});
</script>

<div id="accordion">
	<h3>Options</h3>
	<div class="cashflow form">
		<?php
		echo $this->Form->create('Cashflow', array(
			'inputDefaults' => array(
				'div' => 'form-group',
				'wrapInput' => false,
				'class' => 'form-control',
			),
		));

		echo $this->Form->input('startdate', array(
			'label' => __d('webzash', 'Start date'),
			'afterInput' => '<span class="help-block">' . __d('webzash', 'Leave empty for beginning of financial year') . '</span>'
		));

		echo $this->Form->input('enddate', array(
			'label' => __d('webzash', 'End date'),
			'afterInput' => '<span class="help-block">' . __d('webzash', 'Leave empty for end of financial year') . '</span>'
		));

		echo '<div class="form-group">';
		echo $this->Form->submit(__d('webzash', 'Submit'), array(
			'div' => false,
			'class' => 'btn btn-primary'
		));
		echo $this->Html->tag('span', '', array('class' => 'link-pad'));
		echo $this->Html->link(__d('webzash', 'Clear'), array('plugin' => 'webzash', 'controller' => 'reports', 'action' => 'cashflow'), array('class' => 'btn btn-default'));
		echo '</div>';

		echo $this->Form->end();
		?>
	</div>
</div>
<br />

<!-- Download/Print buttons -->
<div class="btn-group" role="group">
	<?php
	echo $this->Html->link(
		__d('webzash', 'DOWNLOAD .CSV'),
		'/' . $this->params->url . '/downloadcsv:true',
		array('class' => 'btn btn-default btn-sm')
	);

	echo $this->Html->link(
		__d('webzash', 'DOWNLOAD .XLS'),
		'/' . $this->params->url . '/downloadxls:true',
		array('class' => 'btn btn-default btn-sm')
	);

	echo $this->Html->link(__d('webzash', 'PRINT'), '',
		array(
			'class' => 'btn btn-default btn-sm',
			'onClick' => "window.open('" . $this->Html->url('/' . $this->params->url . '/print:true') . "', 'windowname','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,copyhistory=no,width=1000,height=600'); return false;"
		)
	);
	?>
</div>
<br /><br />

<!-- Report Title -->
<div class="subtitle text-center">
	<?php
	if (Configure::read('Account.name')) {
		echo '<div>' . h(Configure::read('Account.name')) . '</div>';
	}
	echo $subtitle;
	?>
</div>

<!-- Cash Flow Statement -->
<table class="stripped">
	<tr>
		<th colspan="3"><?php echo __d('webzash', 'Cash Flow Statement'); ?></th>
	</tr>

	<!-- Beginning Balance -->
	<tr>
		<td>Beginning Cash Balance</td>
		<td></td>
		<td class="text-right"><?php echo toCurrency('D', $cashflow['beginning_balance']); ?></td>
	</tr>

	<tr><td colspan="3">&nbsp;</td></tr> <!-- Added spacing -->

	<!-- Operating Activities -->
	<tr><th colspan="3">Cash Flow from Operating Activities</th></tr>

	<?php foreach ($cashflow['operating_activities'] as $item): ?>
		<tr>
			<td><?php echo h($item['name']); ?></td>
			<td class="text-right"><?php echo isset($item['year']) ? h($item['year']) : ''; ?></td>
			<td class="text-right"><?php echo toCurrency($item['dc'], $item['amount']); ?></td>
		</tr>
	<?php endforeach; ?>

	<!-- Operating Activities Subtotals -->
	<?php if (!empty($cashflow['operating_subtotals'])): ?>
		<?php foreach ($cashflow['operating_subtotals'] as $subtitle => $amount): ?>
			<tr>
				<td>Total for <?php echo h($subtitle); ?></td>
				<td></td>
				<td class="text-right"><?php echo toCurrency('D', $amount); ?></td>
			</tr>
		<?php endforeach; ?>
	<?php endif; ?>

	<tr class="bold-text">
		<td>Net cash provided by Operating Activities</td>
		<td></td>
		<td class="text-right"><?php echo toCurrency('D', $cashflow['totals']['operating']); ?></td>
	</tr>

	<tr><td colspan="3">&nbsp;</td></tr> <!-- Added spacing -->

	<!-- Investing Activities -->
	<tr><th colspan="3">Cash Flow from Investing Activities</th></tr>

	<?php foreach ($cashflow['investing_activities'] as $item): ?>
		<tr>
			<td><?php echo h($item['name']); ?></td>
			<td class="text-right"><?php echo isset($item['code']) ? h($item['code']) : ''; ?></td>
			<td class="text-right"><?php echo toCurrency($item['dc'], $item['amount']); ?></td>
		</tr>
	<?php endforeach; ?>

	<tr class="bold-text">
		<td>Net cash provided by Investing Activities</td>
		<td></td>
		<td class="text-right"><?php echo toCurrency('D', $cashflow['totals']['investing']); ?></td>
	</tr>

	<tr><td colspan="3">&nbsp;</td></tr> <!-- Added spacing -->

	<!-- Financing Activities -->
	<tr><th colspan="3">Cash Flow from Financing Activities</th></tr>

	<?php foreach ($cashflow['financing_activities'] as $item): ?>
		<tr>
			<td><?php echo h($item['name']); ?></td>
			<td class="text-right"><?php echo isset($item['code']) ? h($item['code']) : ''; ?></td>
			<td class="text-right"><?php echo toCurrency($item['dc'], $item['amount']); ?></td>
		</tr>
	<?php endforeach; ?>

	<tr class="bold-text">
		<td>Net cash provided by Financing Activities</td>
		<td></td>
		<td class="text-right"><?php echo toCurrency('D', $cashflow['totals']['financing']); ?></td>
	</tr>

	<tr><td colspan="3">&nbsp;</td></tr> <!-- Added spacing -->

	<!-- Net Change and Ending Balance -->
	<tr class="bold-text">
		<td>Net Change in cash</td>
		<td></td>
		<td class="text-right"><?php echo toCurrency('D', $cashflow['net_change']); ?></td>
	</tr>

	<tr class="bold-text">
		<td>Ending Cash Balance</td>
		<td></td>
		<td class="text-right"><?php echo toCurrency('D', $cashflow['ending_balance']); ?></td>
	</tr>
</table>
