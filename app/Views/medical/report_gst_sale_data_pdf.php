<?php
$rows = $rows ?? [];
$dateFrom = $dateFrom ?? '';
$dateTo = $dateTo ?? '';
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #111; }
        .report-title { font-size: 16px; font-weight: bold; margin-bottom: 2px; }
        .report-subtitle { font-size: 11px; margin-bottom: 10px; color: #444; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 5px 6px; }
        thead th { background: #f3f3f3; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row td { font-weight: bold; background: #fafafa; }
    </style>
</head>
<body>
<div class="report-title">Sale GST Report</div>
<div class="report-subtitle">Date range: <?= esc($dateFrom) ?> to <?= esc($dateTo) ?></div>

<table>
    <thead>
    <tr>
        <th class="text-center" style="width:42px;">#</th>
        <th>CGST Per</th>
        <th class="text-right">T. CGST</th>
        <th>SGST Per</th>
        <th class="text-right">T. SGST</th>
        <th class="text-right">Total GST</th>
        <th class="text-right">Taxable Amount</th>
        <th class="text-right">Amount</th>
        <th class="text-right">Item Qty</th>
    </tr>
    </thead>
    <tbody>
    <?php if (!empty($rows)): ?>
        <?php $sr = 0; ?>
        <?php foreach ($rows as $row): ?>
            <?php
            $isTotal = !empty($row['is_total']) || ($row['cgst_per'] === null || $row['cgst_per'] === '');
            if (!$isTotal) {
                $sr++;
            }
            ?>
            <tr class="<?= $isTotal ? 'total-row' : '' ?>">
                <td class="text-center"><?= $isTotal ? '#' : esc((string) $sr) ?></td>
                <td><?= $isTotal ? 'Grand Total' : esc((string) ($row['cgst_per'] ?? '-')) ?></td>
                <td class="text-right"><?= esc(number_format((float) ($row['tcgst'] ?? 0), 2)) ?></td>
                <td><?= $isTotal ? '-' : esc((string) ($row['sgst_per'] ?? '-')) ?></td>
                <td class="text-right"><?= esc(number_format((float) ($row['tsgst'] ?? 0), 2)) ?></td>
                <td class="text-right"><?= esc(number_format((float) ($row['tgst'] ?? 0), 2)) ?></td>
                <td class="text-right"><?= esc(number_format((float) ($row['taxable_amount'] ?? 0), 2)) ?></td>
                <td class="text-right"><?= esc(number_format((float) ($row['amount'] ?? 0), 2)) ?></td>
                <td class="text-right"><?= esc(number_format((float) ($row['t_qty'] ?? 0), 2)) ?></td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="9" class="text-center">No records found.</td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>
</body>
</html>
