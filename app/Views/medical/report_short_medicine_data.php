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
    <table class="table table-bordered table-striped table-sm align-middle" id="short-med-table">
        <thead>
        <tr>
            <th style="width: 50px;">ID</th>
            <th style="width: 110px;">Date</th>
            <th>Item Name</th>
            <th style="width: 160px;">Formulation</th>
            <th class="text-end" style="width: 90px;">Qty</th>
            <th>Supplier</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($rows)): ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= esc((string) ($row['id'] ?? '')) ?></td>
                    <td><?= esc((string) ($row['str_short_date'] ?? '')) ?></td>
                    <td><?= esc((string) ($row['item_name'] ?? '-')) ?></td>
                    <td><?= esc((string) ($row['formulation'] ?? '-')) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['cur_qty'] ?? 0), 0)) ?></td>
                    <td><?= esc((string) ($row['supplier_name'] ?? '-')) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" class="text-center text-muted">No records found for selected filters.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (!$showHeader): ?>
<script>
(function () {
    if (!window.jQuery || !jQuery.fn || typeof jQuery.fn.DataTable !== 'function') {
        return;
    }

    var tableId = '#short-med-table';
    if (!jQuery(tableId).length) {
        return;
    }

    if (jQuery.fn.dataTable.isDataTable(tableId)) {
        jQuery(tableId).DataTable().destroy();
    }

    jQuery(tableId).DataTable({
        order: [[1, 'desc'], [0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        autoWidth: false
    });
})();
</script>
<?php endif; ?>
