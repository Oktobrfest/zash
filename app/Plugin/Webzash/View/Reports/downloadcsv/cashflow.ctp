<?php
// File: app/Plugin/Webzash/View/Reports/downloadcsv/cashflow.ctp

// Output the report subtitle followed by two newlines.
echo $subtitle;
echo "\n\n";

// Beginning Cash Balance
echo '"Beginning Cash Balance","","' . toCurrency('D', $cashflow['beginning_balance']) . "\"\n\n";

// Operating Activities Section Header
echo '"Cash Flow from Operating Activities","",""' . "\n";

// List each Operating Activity item.
if (!empty($cashflow['operating_activities'])) {
	foreach ($cashflow['operating_activities'] as $item) {
		echo '"' . h($item['name']) . '",';
		echo '"' . (isset($item['year']) ? h($item['year']) : '') . '",';
		echo '"' . toCurrency($item['dc'], $item['amount']) . "\"\n";
	}
}

// Operating Activities Subtotals (if any)
if (!empty($cashflow['operating_subtotals'])) {
	foreach ($cashflow['operating_subtotals'] as $opSubTitle => $amount) {
		echo '"Total for ' . h($opSubTitle) . '","","' . toCurrency('D', $amount) . "\"\n";
	}
}

// Net Cash Provided by Operating Activities
echo '"Net cash provided by Operating Activities","","' . toCurrency('D', $cashflow['totals']['operating']) . "\"\n\n";

// Investing Activities Section Header
echo '"Cash Flow from Investing Activities","",""' . "\n";

// List each Investing Activity item.
if (!empty($cashflow['investing_activities'])) {
	foreach ($cashflow['investing_activities'] as $item) {
		echo '"' . h($item['name']) . '",';
		echo '"' . (isset($item['code']) ? h($item['code']) : '') . '",';
		echo '"' . toCurrency($item['dc'], $item['amount']) . "\"\n";
	}
}

// Net Cash Provided by Investing Activities
echo '"Net cash provided by Investing Activities","","' . toCurrency('D', $cashflow['totals']['investing']) . "\"\n\n";

// Financing Activities Section Header
echo '"Cash Flow from Financing Activities","",""' . "\n";

// List each Financing Activity item.
if (!empty($cashflow['financing_activities'])) {
	foreach ($cashflow['financing_activities'] as $item) {
		echo '"' . h($item['name']) . '",';
		echo '"' . (isset($item['code']) ? h($item['code']) : '') . '",';
		echo '"' . toCurrency($item['dc'], $item['amount']) . "\"\n";
	}
}

// Net Cash Provided by Financing Activities
echo '"Net cash provided by Financing Activities","","' . toCurrency('D', $cashflow['totals']['financing']) . "\"\n\n";

// Net Change in Cash
echo '"Net Change in cash","","' . toCurrency('D', $cashflow['net_change']) . "\"\n";

// Ending Cash Balance
echo '"Ending Cash Balance","","' . toCurrency('D', $cashflow['ending_balance']) . "\"\n";
?>
