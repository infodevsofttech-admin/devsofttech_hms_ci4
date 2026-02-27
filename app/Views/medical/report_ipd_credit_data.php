<?php
$rows = $rows ?? [];
$dateFrom = $dateFrom ?? '';
$dateTo = $dateTo ?? '';
$showHeader = !empty($showHeader);

$grouped = [];
$grandTotal = 0.0;

foreach ($rows as $row) {
    $ipdId = (string) ($row['ipd_id'] ?? '0');
    if (!isset($grouped[$ipdId])) {
        $grouped[$ipdId] = [
            'ipd_code' => (string) ($row['ipd_code'] ?? '-'),
            'patient_code' => (string) ($row['patient_code'] ?? '-'),
            'patient_name' => (string) ($row['patient_name'] ?? '-'),
            'tpa_name' => (string) ($row['tpa_name'] ?? '-'),
            'items' => [],
            'subtotal' => 0.0,
        ];
    }

    $amount = (float) ($row['ipd_total_amount'] ?? 0);
    $grouped[$ipdId]['items'][] = $row;
    $grouped[$ipdId]['subtotal'] += $amount;
    $grandTotal += $amount;
}
?>

<?php if ($showHeader): ?>
    <div class="small text-muted mb-2">Discharge date range: <?= esc($dateFrom) ?> to <?= esc($dateTo) ?></div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-bordered table-striped table-sm align-middle" id="ipd-credit-report-table">
        <thead>
        <tr>
            <th>IPD Code</th>
            <th>Patient Code</th>
            <th>Patient Name</th>
            <th>TPA Name</th>
        </tr>
        <tr>
            <th>Inv.No.</th>
            <th>Inv.Date</th>
            <th class="text-end">Amount</th>
            <th>Type</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($grouped)): ?>
            <?php foreach ($grouped as $group): ?>
                <tr>
                    <td><?= esc($group['ipd_code']) ?></td>
                    <td><?= esc($group['patient_code']) ?></td>
                    <td><?= esc($group['patient_name']) ?></td>
                    <td><?= esc($group['tpa_name']) ?></td>
                </tr>

                <?php foreach ($group['items'] as $item): ?>
                    <tr>
                        <td><?= esc((string) ($item['inv_med_code'] ?? '-')) ?></td>
                        <td><?= esc((string) ($item['inv_date_str'] ?? '-')) ?></td>
                        <td class="text-end"><?= esc(number_format((float) ($item['ipd_total_amount'] ?? 0), 2)) ?></td>
                        <td><?= esc((string) ($item['bill_type'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; ?>

                <tr>
                    <th colspan="2">Total</th>
                    <th class="text-end"><?= esc(number_format((float) ($group['subtotal'] ?? 0), 2)) ?></th>
                    <th></th>
                </tr>
                <tr><td colspan="4" class="bg-light"></td></tr>
            <?php endforeach; ?>

            <tr>
                <th colspan="2">Grand Total</th>
                <th class="text-end"><?= esc(number_format($grandTotal, 2)) ?></th>
                <th></th>
            </tr>
        <?php else: ?>
            <tr>
                <td colspan="4" class="text-center text-muted">No records found for selected filters.</td>
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

    var tableId = '#ipd-credit-report-table';
    if (!jQuery(tableId).length) {
        return;
    }

    if (jQuery.fn.dataTable.isDataTable(tableId)) {
        jQuery(tableId).DataTable().destroy();
    }

    jQuery(tableId).DataTable({
        order: [[1, 'asc']],
        pageLength: 50,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        autoWidth: false
    });
})();
</script>
<?php endif; ?>
