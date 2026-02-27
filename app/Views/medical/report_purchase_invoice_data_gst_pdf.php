<?php
$totalTaxable = 0.0;
$totalGst = 0.0;
$totalAmount = 0.0;
$totalZero = 0.0;
$totalFive = 0.0;
$totalTwelve = 0.0;
$totalEighteen = 0.0;
$totalTwentyEight = 0.0;
$totalCgst = 0.0;
$totalSgst = 0.0;
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; }
        .title { font-size: 14px; font-weight: bold; margin-bottom: 4px; }
        .subtitle { margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #222; padding: 3px 4px; }
        .right { text-align: right; }
    </style>
</head>
<body>
<div class="title">Purchase Invoice GST Rate Report</div>
<div class="subtitle">Date: <?= esc($dateFrom ?? '') ?> to <?= esc($dateTo ?? '') ?></div>

<table>
    <thead>
    <tr>
        <th>#</th>
        <th>Invoice</th>
        <th>Supplier</th>
        <th>Date</th>
        <th class="right">Taxable</th>
        <th class="right">GST</th>
        <th class="right">Amount</th>
        <th class="right">0%</th>
        <th class="right">5%</th>
        <th class="right">12%</th>
        <th class="right">18%</th>
        <th class="right">28%</th>
        <th class="right">CGST</th>
        <th class="right">SGST/IGST</th>
    </tr>
    </thead>
    <tbody>
    <?php $i = 1; foreach (($rows ?? []) as $row):
        $taxable = (float) ($row['taxable_amount'] ?? 0);
        $gst = (float) ($row['gst_amount'] ?? 0);
        $amount = (float) ($row['tamount'] ?? 0);
        $zero = (float) ($row['tot_gst_0'] ?? 0);
        $five = (float) ($row['tot_gst_5'] ?? 0);
        $twelve = (float) ($row['tot_gst_12'] ?? 0);
        $eighteen = (float) ($row['tot_gst_18'] ?? 0);
        $twentyEight = (float) ($row['tot_gst_28'] ?? 0);
        $cgst = (float) ($row['total_cgst'] ?? 0);
        $sgst = (float) ($row['total_sgst'] ?? 0);

        $totalTaxable += $taxable;
        $totalGst += $gst;
        $totalAmount += $amount;
        $totalZero += $zero;
        $totalFive += $five;
        $totalTwelve += $twelve;
        $totalEighteen += $eighteen;
        $totalTwentyEight += $twentyEight;
        $totalCgst += $cgst;
        $totalSgst += $sgst;
        ?>
        <tr>
            <td><?= esc((string) $i++) ?></td>
            <td><?= esc((string) ($row['invoice_no'] ?? '')) ?></td>
            <td><?= esc((string) ($row['name_supplier'] ?? '')) ?></td>
            <td><?= esc((string) ($row['str_date_of_invoice'] ?? '')) ?></td>
            <td class="right"><?= esc(number_format($taxable, 2)) ?></td>
            <td class="right"><?= esc(number_format($gst, 2)) ?></td>
            <td class="right"><?= esc(number_format($amount, 2)) ?></td>
            <td class="right"><?= esc(number_format($zero, 2)) ?></td>
            <td class="right"><?= esc(number_format($five, 2)) ?></td>
            <td class="right"><?= esc(number_format($twelve, 2)) ?></td>
            <td class="right"><?= esc(number_format($eighteen, 2)) ?></td>
            <td class="right"><?= esc(number_format($twentyEight, 2)) ?></td>
            <td class="right"><?= esc(number_format($cgst, 2)) ?></td>
            <td class="right"><?= esc(number_format($sgst, 2)) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
    <tr>
        <th colspan="4" class="right">Total</th>
        <th class="right"><?= esc(number_format($totalTaxable, 2)) ?></th>
        <th class="right"><?= esc(number_format($totalGst, 2)) ?></th>
        <th class="right"><?= esc(number_format($totalAmount, 2)) ?></th>
        <th class="right"><?= esc(number_format($totalZero, 2)) ?></th>
        <th class="right"><?= esc(number_format($totalFive, 2)) ?></th>
        <th class="right"><?= esc(number_format($totalTwelve, 2)) ?></th>
        <th class="right"><?= esc(number_format($totalEighteen, 2)) ?></th>
        <th class="right"><?= esc(number_format($totalTwentyEight, 2)) ?></th>
        <th class="right"><?= esc(number_format($totalCgst, 2)) ?></th>
        <th class="right"><?= esc(number_format($totalSgst, 2)) ?></th>
    </tr>
    </tfoot>
</table>
</body>
</html>
