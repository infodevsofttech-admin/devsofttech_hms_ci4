<?php
$rows = $rows ?? [];
$dateFrom = $dateFrom ?? '';
$dateTo = $dateTo ?? '';
$showHeader = !empty($showHeader);
?>

<?php if ($showHeader): ?>
    <div class="small text-muted mb-2">Date range: <?= esc($dateFrom) ?> to <?= esc($dateTo) ?></div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-bordered table-striped table-sm align-middle" id="gst-sale-report-table">
        <thead>
        <tr>
            <th style="width:60px;">#</th>
            <th>CGST Per</th>
            <th class="text-end">T. CGST</th>
            <th>SGST Per</th>
            <th class="text-end">T. SGST</th>
            <th class="text-end">Total GST</th>
            <th class="text-end">Taxable Amount</th>
            <th class="text-end">Amount</th>
            <th class="text-end">Item Qty</th>
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
                <tr>
                    <td><?= $isTotal ? '#' : esc((string) $sr) ?></td>
                    <td><?= $isTotal ? '<b>Grand Total :</b>' : esc((string) ($row['cgst_per'] ?? '-')) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['tcgst'] ?? 0), 2)) ?></td>
                    <td><?= $isTotal ? '' : esc((string) ($row['sgst_per'] ?? '-')) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['tsgst'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['tgst'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['taxable_amount'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['amount'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['t_qty'] ?? 0), 2)) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="9" class="text-center text-muted">No records found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
