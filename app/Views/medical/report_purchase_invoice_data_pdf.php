<?php
$totalTaxable = 0.0;
$totalGst = 0.0;
$totalAmount = 0.0;

$currentSupplier = '';
$currentGst = '';
$currentState = '';
$supplierSr = 0;
$supplierTaxable = 0.0;
$supplierGst = 0.0;
$supplierAmount = 0.0;

$printSupplierTotal = static function (string $supplierName, float $taxable, float $gst, float $amount): string {
    if ($supplierName === '') {
        return '';
    }

    return '<tr>'
        . '<td>#</td>'
        . '<td colspan="2"><strong>Total of : ' . esc($supplierName) . '</strong></td>'
        . '<td style="text-align:right;"><strong>' . esc(number_format($taxable, 2)) . '</strong></td>'
        . '<td style="text-align:right;"><strong>' . esc(number_format($gst, 2)) . '</strong></td>'
        . '<td style="text-align:right;"><strong>' . esc(number_format($amount, 0, '.', '')) . '</strong></td>'
        . '</tr>';
};
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; }
        .title { font-size: 16px; font-weight: bold; margin-bottom: 4px; }
        .subtitle { margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #222; padding: 3px 5px; }
        .right { text-align: right; }
    </style>
</head>
<body>
<div class="title">Purchase Invoice Report</div>
<div class="subtitle">Date: <?= esc($dateFrom ?? '') ?> to <?= esc($dateTo ?? '') ?></div>

<table>
    <tbody>
    <tr><td colspan="6">_</td></tr>

    <?php foreach (($rows ?? []) as $row):
        $supplierName = trim((string) ($row['name_supplier'] ?? ''));
        $gstNo = trim((string) ($row['gst_no'] ?? ''));
        $state = trim((string) ($row['state'] ?? ''));
        $taxable = (float) ($row['taxable_amount'] ?? 0);
        $gst = (float) ($row['gst_amount'] ?? 0);
        $amount = (float) ($row['tamount'] ?? 0);

        if ($supplierName !== $currentSupplier || $gstNo !== $currentGst || $state !== $currentState) {
            if ($currentSupplier !== '') {
                echo $printSupplierTotal($currentSupplier, $supplierTaxable, $supplierGst, $supplierAmount);
                echo '<tr><td colspan="6">_</td></tr>';
            }

            $currentSupplier = $supplierName;
            $currentGst = $gstNo;
            $currentState = $state;
            $supplierSr = 0;
            $supplierTaxable = 0.0;
            $supplierGst = 0.0;
            $supplierAmount = 0.0;
            ?>
            <tr>
                <td colspan="6"><?= esc($currentSupplier) ?> / GST No. <?= esc($currentGst) ?> / State : <?= esc($currentState) ?></td>
            </tr>
            <tr>
                <td>#</td>
                <td>Purchase. Inv. ID</td>
                <td>Inv. Date</td>
                <td class="right">Taxable Amt</td>
                <td class="right">Tax Amt</td>
                <td class="right">Inv. Amount</td>
            </tr>
        <?php }

        $supplierSr++;
        $supplierTaxable += $taxable;
        $supplierGst += $gst;
        $supplierAmount += $amount;

        $totalTaxable += $taxable;
        $totalGst += $gst;
        $totalAmount += $amount;
        ?>
        <tr>
            <td><?= esc((string) $supplierSr) ?></td>
            <td><?= esc((string) ($row['invoice_no'] ?? '')) ?></td>
            <td><?= esc((string) ($row['str_date_of_invoice'] ?? '')) ?></td>
            <td class="right"><?= esc(number_format($taxable, 2)) ?></td>
            <td class="right"><?= esc(number_format($gst, 2)) ?></td>
            <td class="right"><?= esc(number_format($amount, 0, '.', '')) ?></td>
        </tr>
    <?php endforeach; ?>

    <?php if ($currentSupplier !== ''): ?>
        <?= $printSupplierTotal($currentSupplier, $supplierTaxable, $supplierGst, $supplierAmount) ?>
    <?php endif; ?>

    <tr>
        <td>#</td>
        <td></td>
        <td><strong>Grand Total</strong></td>
        <td class="right"><strong><?= esc(number_format($totalTaxable, 2)) ?></strong></td>
        <td class="right"><strong><?= esc(number_format($totalGst, 2)) ?></strong></td>
        <td class="right"><strong><?= esc(number_format($totalAmount, 0, '.', '')) ?></strong></td>
    </tr>
    </tbody>
</table>
</body>
</html>
