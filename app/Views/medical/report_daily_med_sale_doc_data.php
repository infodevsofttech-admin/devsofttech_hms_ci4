<?php
$rows = $rows ?? [];
$dateFrom = $dateFrom ?? '';
$dateTo = $dateTo ?? '';
$showHeader = !empty($showHeader);

$totalSaleAmount = 0.0;
$totalPurchaseAmount = 0.0;
$totalSaleGst = 0.0;

foreach ($rows as $sumRow) {
    $totalSaleAmount += (float) ($sumRow['sale_amount'] ?? 0);
    $totalPurchaseAmount += (float) ($sumRow['purchase_amount'] ?? 0);
    $totalSaleGst += (float) ($sumRow['sale_gst'] ?? 0);
}

$totalMargin = $totalSaleAmount - $totalPurchaseAmount - $totalSaleGst;
?>

<?php if ($showHeader): ?>
    <div class="small text-muted mb-2">Date range: <?= esc($dateFrom) ?> to <?= esc($dateTo) ?></div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-bordered table-striped table-sm align-middle" id="daily-med-sale-doc-table">
        <thead>
        <tr>
            <th style="width: 50px;">#</th>
            <th>Doc. Name</th>
            <th>Item Code</th>
            <th>Item Name</th>
            <th class="text-end">Sale Qty</th>
            <th class="text-end">Sale Amt</th>
            <th class="text-end">Pur. Amt</th>
            <th class="text-end">Sale GST</th>
            <th class="text-end">Cur.Qty</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($rows)): ?>
            <?php foreach ($rows as $idx => $row): ?>
                <tr>
                    <td><?= (int) $idx + 1 ?></td>
                    <td><?= esc((string) ($row['doc_name'] ?? '-')) ?></td>
                    <td><?= esc((string) ($row['item_code'] ?? '-')) ?></td>
                    <td><?= esc((string) ($row['item_name'] ?? '-')) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['sale_qty'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['sale_amount'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['purchase_amount'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['sale_gst'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['cur_qty'] ?? 0), 2)) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="9" class="text-center text-muted">No records found for selected filters.</td>
            </tr>
        <?php endif; ?>
        </tbody>
        <?php if (!empty($rows)): ?>
            <tfoot>
            <tr>
                <th>#</th>
                <th></th>
                <th></th>
                <th></th>
                <th>Total Sale</th>
                <th class="text-end"><?= esc(number_format($totalSaleAmount, 2)) ?></th>
                <th class="text-end"><?= esc(number_format($totalPurchaseAmount, 2)) ?></th>
                <th class="text-end"><?= esc(number_format($totalSaleGst, 2)) ?></th>
                <th></th>
            </tr>
            </tfoot>
        <?php endif; ?>
    </table>
</div>

<div class="mt-2 fw-semibold">Total Margin (Sale - Purchase - SaleGST): Rs. <?= esc(number_format($totalMargin, 2)) ?></div>

<?php if (!$showHeader): ?>
<script>
(function () {
    if (!window.jQuery || !jQuery.fn || typeof jQuery.fn.DataTable !== 'function') {
        return;
    }

    var tableId = '#daily-med-sale-doc-table';
    if (!jQuery(tableId).length) {
        return;
    }

    if (jQuery.fn.dataTable.isDataTable(tableId)) {
        jQuery(tableId).DataTable().destroy();
    }

    jQuery(tableId).DataTable({
        order: [[1, 'asc'], [3, 'asc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        autoWidth: false
    });
})();
</script>
<?php endif; ?>
