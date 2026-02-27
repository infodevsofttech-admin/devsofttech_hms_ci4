<?php
$rows = $rows ?? [];
$dateFrom = $dateFrom ?? '';
$dateTo = $dateTo ?? '';
$showHeader = !empty($showHeader);

$grouped = [];
$totals = [
    'cash' => 0.0,
    'credit' => 0.0,
    'package' => 0.0,
    'total' => 0.0,
];

foreach ($rows as $row) {
    $ipdId = (string) ($row['ipd_id'] ?? '0');
    if (!isset($grouped[$ipdId])) {
        $grouped[$ipdId] = [
            'ipd_code' => (string) ($row['ipd_code'] ?? '-'),
            'patient_code' => (string) ($row['patient_code'] ?? '-'),
            'patient_name' => (string) ($row['patient_name'] ?? '-'),
            'tpa_name' => (string) ($row['tpa_name'] ?? '-'),
            'items' => [],
        ];
    }

    $grouped[$ipdId]['items'][] = $row;
    $totals['cash'] += (float) ($row['ipd_cash_amount'] ?? 0);
    $totals['credit'] += (float) ($row['ipd_credit_amount'] ?? 0);
    $totals['package'] += (float) ($row['ipd_package_amount'] ?? 0);
    $totals['total'] += (float) ($row['ipd_total_amount'] ?? 0);
}
?>

<?php if ($showHeader): ?>
    <div class="small text-muted mb-2">Discharge date range: <?= esc($dateFrom) ?> to <?= esc($dateTo) ?></div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-bordered table-striped table-sm align-middle" id="ipd-sale-report-table">
        <thead>
        <tr>
            <th>IPD Code</th>
            <th>Patient Code</th>
            <th colspan="2">Patient Name</th>
            <th colspan="3">TPA Name</th>
        </tr>
        <tr>
            <th></th>
            <th>Inv.No.</th>
            <th>Inv.Date</th>
            <th class="text-end">Cash</th>
            <th class="text-end">Credit</th>
            <th class="text-end">Package</th>
            <th class="text-end">Total</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($grouped)): ?>
            <?php foreach ($grouped as $group): ?>
                <tr>
                    <td><?= esc($group['ipd_code']) ?></td>
                    <td><?= esc($group['patient_code']) ?></td>
                    <td colspan="2"><?= esc($group['patient_name']) ?></td>
                    <td colspan="3"><?= esc($group['tpa_name']) ?></td>
                </tr>

                <?php foreach ($group['items'] as $item): ?>
                    <tr>
                        <td></td>
                        <td><?= esc((string) ($item['inv_med_code'] ?? '-')) ?></td>
                        <td><?= esc((string) ($item['inv_date_str'] ?? '-')) ?></td>
                        <td class="text-end"><?= esc(number_format((float) ($item['ipd_cash_amount'] ?? 0), 2)) ?></td>
                        <td class="text-end"><?= esc(number_format((float) ($item['ipd_credit_amount'] ?? 0), 2)) ?></td>
                        <td class="text-end"><?= esc(number_format((float) ($item['ipd_package_amount'] ?? 0), 2)) ?></td>
                        <td class="text-end"><?= esc(number_format((float) ($item['ipd_total_amount'] ?? 0), 2)) ?></td>
                    </tr>
                <?php endforeach; ?>

                <tr><td colspan="7" class="bg-light"></td></tr>
            <?php endforeach; ?>

            <tr>
                <th colspan="3">IPD Total</th>
                <th class="text-end"><?= esc(number_format($totals['cash'], 2)) ?></th>
                <th class="text-end"><?= esc(number_format($totals['credit'], 2)) ?></th>
                <th class="text-end"><?= esc(number_format($totals['package'], 2)) ?></th>
                <th class="text-end"><?= esc(number_format($totals['total'], 2)) ?></th>
            </tr>
        <?php else: ?>
            <tr>
                <td colspan="7" class="text-center text-muted">No records found for selected date range.</td>
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

    var tableId = '#ipd-sale-report-table';
    if (!jQuery(tableId).length) {
        return;
    }

    if (jQuery.fn.dataTable.isDataTable(tableId)) {
        jQuery(tableId).DataTable().destroy();
    }

    jQuery(tableId).DataTable({
        order: [[2, 'asc']],
        pageLength: 50,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        autoWidth: false
    });
})();
</script>
<?php endif; ?>
