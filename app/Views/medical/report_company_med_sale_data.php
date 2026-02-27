<?php
$rows = $rows ?? [];
$dateFrom = $dateFrom ?? '';
$dateTo = $dateTo ?? '';
$showHeader = !empty($showHeader);

$totalSaleAmount = 0.0;
$totalReturnAmount = 0.0;
$totalPurchaseAmount = 0.0;
$totalReturnPurchaseAmount = 0.0;
$totalSaleGst = 0.0;
$totalReturnGst = 0.0;

foreach ($rows as $sumRow) {
    $totalSaleAmount += (float) ($sumRow['sale_amount'] ?? 0);
    $totalReturnAmount += (float) ($sumRow['return_amount'] ?? 0);
    $totalPurchaseAmount += (float) ($sumRow['purchase_amount'] ?? 0);
    $totalReturnPurchaseAmount += (float) ($sumRow['return_purchase_amount'] ?? 0);
    $totalSaleGst += (float) ($sumRow['sale_gst'] ?? 0);
    $totalReturnGst += (float) ($sumRow['return_gst'] ?? 0);
}

$totalMargin = (($totalSaleAmount - $totalPurchaseAmount) - ($totalReturnAmount - $totalReturnPurchaseAmount)) - ($totalSaleGst - $totalReturnGst);
?>

<?php if ($showHeader): ?>
    <div class="small text-muted mb-2">Date range: <?= esc($dateFrom) ?> to <?= esc($dateTo) ?></div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-bordered table-striped table-sm align-middle" id="company-med-sale-table">
        <thead>
        <tr>
            <th style="width: 50px;">#</th>
            <th>Item Code</th>
            <th>Item Name</th>
            <th class="text-end">Sale Qty</th>
            <th class="text-end">Sale Amt</th>
            <th class="text-end">Pur. Amt</th>
            <th class="text-end">Sale GST</th>
            <th class="text-end">Return Qty</th>
            <th class="text-end">Return Amt</th>
            <th class="text-end">Return Pur. Amt</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($rows)): ?>
            <?php foreach ($rows as $idx => $row): ?>
                <tr>
                    <td><?= (int) $idx + 1 ?></td>
                    <td><?= esc((string) ($row['item_code'] ?? '-')) ?></td>
                    <td><?= esc((string) ($row['item_name'] ?? '-')) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['sale_qty'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['sale_amount'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['purchase_amount'] ?? 0), 2)) ?></td>
                    <td class="text-end\"><?= esc(number_format((float) ($row['sale_gst'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['return_qty'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['return_amount'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['return_purchase_amount'] ?? 0), 2)) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="10" class="text-center text-muted">No records found for selected filters.</td>
            </tr>
        <?php endif; ?>
        </tbody>
        <?php if (!empty($rows)): ?>
            <tfoot>
            <tr>
                <th>#</th>
                <th></th>
                <th></th>
                <th>Total Sale</th>
                <th class="text-end"><?= esc(number_format($totalSaleAmount, 2)) ?></th>
                <th class="text-end"><?= esc(number_format($totalPurchaseAmount, 2)) ?></th>
                <th class="text-end\"><?= esc(number_format($totalSaleGst, 2)) ?></th>
                <th>Total Return</th>
                <th class="text-end"><?= esc(number_format($totalReturnAmount, 2)) ?></th>
                <th class="text-end"><?= esc(number_format($totalReturnPurchaseAmount, 2)) ?></th>
            </tr>
            </tfoot>
        <?php endif; ?>
    </table>
</div>

<div class="mt-2 fw-semibold">Total Margin (Sale - Purchase - Return + ReturnPurchase - NetGST): Rs. <?= esc(number_format($totalMargin, 2)) ?></div>
<div class="small text-muted">Net GST = Sale GST (<?= esc(number_format($totalSaleGst, 2)) ?>) - Return GST (<?= esc(number_format($totalReturnGst, 2)) ?>)</div>

<?php if (!$showHeader): ?>
<script>
(function () {
    if (!window.jQuery || !jQuery.fn || typeof jQuery.fn.DataTable !== 'function') {
        return;
    }

    var tableId = '#company-med-sale-table';
    if (!jQuery(tableId).length) {
        return;
    }

    if (jQuery.fn.dataTable.isDataTable(tableId)) {
        jQuery(tableId).DataTable().destroy();
    }

    jQuery(tableId).DataTable({
        order: [[2, 'asc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        autoWidth: false
    });
})();
</script>
<?php endif; ?>
