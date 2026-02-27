<?php
$rows = $rows ?? [];
$dateFrom = $dateFrom ?? '';
$dateTo = $dateTo ?? '';
$showHeader = !empty($showHeader);

$totalAmt = 0.0;
foreach ($rows as $row) {
    $totalAmt += (float) ($row['net_amount'] ?? 0);
}
?>

<?php if ($showHeader): ?>
    <div class="small text-muted mb-2">Invoice date range: <?= esc($dateFrom) ?> to <?= esc($dateTo) ?></div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-bordered table-striped table-sm align-middle" id="org-bills-report-table">
        <thead>
        <tr>
            <th>Inv. ID</th>
            <th>Org.Code</th>
            <th>P Code</th>
            <th>P Name</th>
            <th class="text-end">Amount</th>
            <th>Org. Name</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($rows)): ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= esc((string) ($row['inv_med_code'] ?? '-')) ?></td>
                    <td><?= esc((string) ($row['case_id_code'] ?? '-')) ?></td>
                    <td><?= esc((string) ($row['p_code'] ?? '-')) ?></td>
                    <td><?= esc((string) ($row['p_fname'] ?? '-')) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['net_amount'] ?? 0), 2)) ?></td>
                    <td><?= esc((string) ($row['ins_company_name'] ?? '-')) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <th colspan="4" class="text-end">Total</th>
                <th class="text-end"><?= esc(number_format($totalAmt, 2)) ?></th>
                <th></th>
            </tr>
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

    var tableId = '#org-bills-report-table';
    if (!jQuery(tableId).length) {
        return;
    }

    if (jQuery.fn.dataTable.isDataTable(tableId)) {
        jQuery(tableId).DataTable().destroy();
    }

    jQuery(tableId).DataTable({
        order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        autoWidth: false
    });
})();
</script>
<?php endif; ?>
