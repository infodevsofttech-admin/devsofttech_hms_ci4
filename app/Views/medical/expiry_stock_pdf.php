<?php
$rows = $purchase_list ?? [];
$todayTs = strtotime(date('Y-m-d'));
$expirySummary = [
    'expired' => 0,
    'd30' => 0,
    'd60' => 0,
    'later' => 0,
];

foreach ($rows as $summaryRow) {
    $expiryTs = strtotime((string) ($summaryRow->expiry_date ?? ''));
    if ($expiryTs === false) {
        $expirySummary['later']++;
        continue;
    }

    $daysToExpiry = (int) floor(($expiryTs - $todayTs) / 86400);
    if ($daysToExpiry < 0) {
        $expirySummary['expired']++;
    } elseif ($daysToExpiry <= 30) {
        $expirySummary['d30']++;
    } elseif ($daysToExpiry <= 60) {
        $expirySummary['d60']++;
    } else {
        $expirySummary['later']++;
    }
}
?>

<style>
    body { font-family: sans-serif; font-size: 11px; }
    h2 { margin: 0 0 6px 0; }
    .meta { margin-bottom: 10px; color: #555; }
    .summary-table, .data-table { border-collapse: collapse; width: 100%; }
    .summary-table td, .summary-table th, .data-table td, .data-table th {
        border: 1px solid #ccc;
        padding: 5px;
    }
    .summary-table th { background: #f6f6f6; text-align: left; }
    .data-table th { background: #f2f2f2; }
    .text-right { text-align: right; }
    .expired-row { background: #f8d7da; }
    .d30-row { background: #fff3cd; }
    .d60-row { background: #cff4fc; }
</style>

<h2>Expiry Medicine Report</h2>
<div class="meta">Generated: <?= esc((string) ($generated_at ?? date('d-m-Y H:i'))) ?></div>

<table class="summary-table" style="margin-bottom:10px;">
    <tr>
        <th>Expired</th>
        <th>Expiring in 30 days</th>
        <th>Expiring in 2 months</th>
        <th>Above 2 months</th>
    </tr>
    <tr>
        <td><?= esc((string) ($expirySummary['expired'] ?? 0)) ?></td>
        <td><?= esc((string) ($expirySummary['d30'] ?? 0)) ?></td>
        <td><?= esc((string) ($expirySummary['d60'] ?? 0)) ?></td>
        <td><?= esc((string) ($expirySummary['later'] ?? 0)) ?></td>
    </tr>
</table>

<table class="data-table">
    <thead>
    <tr>
        <th style="width:30px;">#</th>
        <th>Item</th>
        <th>Supplier</th>
        <th>Pur. Date</th>
        <th>Batch</th>
        <th>Exp. Dt</th>
        <th class="text-right">Rate</th>
        <th class="text-right">Qty</th>
        <th class="text-right">C.Qty</th>
        <th>Status</th>
    </tr>
    </thead>
    <tbody>
    <?php $srno = 1; foreach ($rows as $row): ?>
        <?php
        $statusLabel = 'Above 2 months';
        $rowClass = '';
        $daysText = '';

        $expiryTs = strtotime((string) ($row->expiry_date ?? ''));
        if ($expiryTs !== false) {
            $daysToExpiry = (int) floor(($expiryTs - $todayTs) / 86400);
            $daysText = ' (' . $daysToExpiry . ' days)';

            if ($daysToExpiry < 0) {
                $statusLabel = 'Expired';
                $rowClass = 'expired-row';
            } elseif ($daysToExpiry <= 30) {
                $statusLabel = 'Expiring in 30 days';
                $rowClass = 'd30-row';
            } elseif ($daysToExpiry <= 60) {
                $statusLabel = 'Expiring in 2 months';
                $rowClass = 'd60-row';
            }
        }
        ?>
        <tr class="<?= esc($rowClass) ?>">
            <td><?= esc((string) $srno++) ?></td>
            <td><?= esc((string) ($row->item_name ?? $row->Item_name ?? '')) ?></td>
            <td><?= esc((string) ($row->name_supplier ?? '')) ?></td>
            <td><?= esc((string) ($row->str_date_of_invoice ?? '')) ?></td>
            <td><?= esc((string) ($row->batch_no ?? '')) ?></td>
            <td><?= esc((string) ($row->exp_date ?? '')) ?></td>
            <td class="text-right"><?= esc(number_format((float) ($row->mrp ?? 0), 2)) ?></td>
            <td class="text-right"><?= esc(number_format((float) ($row->tqty ?? 0), 2)) ?></td>
            <td class="text-right"><?= esc(number_format((float) ($row->cur_qty ?? 0), 2)) ?></td>
            <td><?= esc($statusLabel . $daysText) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
