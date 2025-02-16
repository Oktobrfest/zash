<?php
// Define XML row and cell tags for XLS output
$xRS = '<Row>';
$xRE = '</Row>' . "\n";
$xCS = '<Cell><Data ss:Type="String">';
$xCE = '</Data></Cell>';

// Print the report subtitle (if any)
echo $xRS . $xCS . $subtitle . $xCE . $xRE;
echo $xRS . $xRE;

// Header Row: Cash Flow Statement title
echo $xRS;
echo $xCS . __d('webzash', 'Cash Flow Statement') . $xCE;
echo $xCS . '' . $xCE;
echo $xCS . '' . $xCE;
echo $xRE;

// Beginning Cash Balance Row
echo $xRS;
echo $xCS . __d('webzash', 'Beginning Cash Balance') . $xCE;
echo $xCS . '' . $xCE;
echo $xCS . toCurrency('D', $cashflow['beginning_balance']) . $xCE;
echo $xRE;
echo $xRS . $xRE; // Blank row

// Section: Operating Activities Header
echo $xRS;
echo $xCS . __d('webzash', 'Cash Flow from Operating Activities') . $xCE;
echo $xCS . '' . $xCE;
echo $xCS . '' . $xCE;
echo $xRE;

// List Operating Activities
if (!empty($cashflow['operating_activities'])) {
	foreach ($cashflow['operating_activities'] as $item) {
		echo $xRS;
		echo $xCS . h($item['name']) . $xCE;
		echo $xCS . (isset($item['year']) ? h($item['year']) : '') . $xCE;
		echo $xCS . toCurrency($item['dc'], $item['amount']) . $xCE;
		echo $xRE;
	}
}

// Operating Subtotals (if any)
if (!empty($cashflow['operating_subtotals'])) {
	foreach ($cashflow['operating_subtotals'] as $subTitle => $amount) {
		echo $xRS;
		echo $xCS . __d('webzash', 'Total for') . ' ' . h($subTitle) . $xCE;
		echo $xCS . '' . $xCE;
		echo $xCS . toCurrency('D', $amount) . $xCE;
		echo $xRE;
	}
}

// Net Cash Provided by Operating Activities
echo $xRS;
echo $xCS . __d('webzash', 'Net cash provided by Operating Activities') . $xCE;
echo $xCS . '' . $xCE;
echo $xCS . toCurrency('D', $cashflow['totals']['operating']) . $xCE;
echo $xRE;
echo $xRS . $xRE; // Blank row

// Section: Investing Activities Header
echo $xRS;
echo $xCS . __d('webzash', 'Cash Flow from Investing Activities') . $xCE;
echo $xCS . '' . $xCE;
echo $xCS . '' . $xCE;
echo $xRE;

// List Investing Activities
if (!empty($cashflow['investing_activities'])) {
	foreach ($cashflow['investing_activities'] as $item) {
		echo $xRS;
		echo $xCS . h($item['name']) . $xCE;
		echo $xCS . (isset($item['code']) ? h($item['code']) : '') . $xCE;
		echo $xCS . toCurrency($item['dc'], $item['amount']) . $xCE;
		echo $xRE;
	}
}

// Net Cash Provided by Investing Activities
echo $xRS;
echo $xCS . __d('webzash', 'Net cash provided by Investing Activities') . $xCE;
echo $xCS . '' . $xCE;
echo $xCS . toCurrency('D', $cashflow['totals']['investing']) . $xCE;
echo $xRE;
echo $xRS . $xRE; // Blank row

// Section: Financing Activities Header
echo $xRS;
echo $xCS . __d('webzash', 'Cash Flow from Financing Activities') . $xCE;
echo $xCS . '' . $xCE;
echo $xCS . '' . $xCE;
echo $xRE;

// List Financing Activities
if (!empty($cashflow['financing_activities'])) {
	foreach ($cashflow['financing_activities'] as $item) {
		echo $xRS;
		echo $xCS . h($item['name']) . $xCE;
		echo $xCS . (isset($item['code']) ? h($item['code']) : '') . $xCE;
		echo $xCS . toCurrency($item['dc'], $item['amount']) . $xCE;
		echo $xRE;
	}
}

// Net Cash Provided by Financing Activities
echo $xRS;
echo $xCS . __d('webzash', 'Net cash provided by Financing Activities') . $xCE;
echo $xCS . '' . $xCE;
echo $xCS . toCurrency('D', $cashflow['totals']['financing']) . $xCE;
echo $xRE;
echo $xRS . $xRE; // Blank row

// Net Change in Cash
echo $xRS;
echo $xCS . __d('webzash', 'Net Change in cash') . $xCE;
echo $xCS . '' . $xCE;
echo $xCS . toCurrency('D', $cashflow['net_change']) . $xCE;
echo $xRE;

// Ending Cash Balance
echo $xRS;
echo $xCS . __d('webzash', 'Ending Cash Balance') . $xCE;
echo $xCS . '' . $xCE;
echo $xCS . toCurrency('D', $cashflow['ending_balance']) . $xCE;
echo $xRE;
?>
