<?php
$rows = $rows ?? [];
$dateFrom = $dateFrom ?? '';
$dateTo = $dateTo ?? '';

$totalCash = 0.0;
$totalPaid = 0.0;
$totalBalance = 0.0;
$totalCredit = 0.0;
$totalPackage = 0.0;

foreach ($rows as $sumRow) {
    $totalCash += (float) ($sumRow['ipd_cash_amount'] ?? 0);
    $totalPaid += (float) ($sumRow['paid_amount'] ?? 0);
    $totalBalance += (float) ($sumRow['cash_balance'] ?? 0);
    $totalCredit += (float) ($sumRow['ipd_credit_amount'] ?? 0);
    $totalPackage += (float) ($sumRow['ipd_package_amount'] ?? 0);
}
?>

<div class="small text-muted mb-2">Date range: <?= esc($dateFrom) ?> to <?= esc($dateTo) ?></div>

<div class="table-responsive">
    <table class="table table-bordered table-striped table-sm align-middle" id="ipd-discharge-report-table">
        <thead>
        <tr>
            <th>IPD Code</th>
            <th>Patient Code</th>
            <th>Patient Name</th>
            <th>Admit Dt.</th>
            <th>Discharge Dt.</th>
            <th>TPA Name</th>
            <th class="text-end">Cash</th>
            <th class="text-end">Paid Amt.</th>
            <th class="text-end">Cash Balance</th>
            <th class="text-end">Credit</th>
            <th class="text-end">Package</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($rows)): ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= esc((string) ($row['ipd_code'] ?? '-')) ?></td>
                    <td><?= esc((string) ($row['p_code'] ?? '-')) ?></td>
                    <td><?= esc((string) ($row['p_name'] ?? '-')) ?></td>
                    <td><?= esc((string) ($row['admit_date'] ?? '-')) ?></td>
                    <td><?= esc((string) ($row['discharge_date'] ?? '-')) ?></td>
                    <td><?= esc((string) ($row['tpa_name'] ?? '-')) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['ipd_cash_amount'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['paid_amount'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['cash_balance'] ?? 0), 0)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['ipd_credit_amount'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['ipd_package_amount'] ?? 0), 2)) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="11" class="text-center text-muted">No discharged IPD records found for selected date range.</td>
            </tr>
        <?php endif; ?>
        </tbody>
        <?php if (!empty($rows)): ?>
            <tfoot>
            <tr>
                <th colspan="6" class="text-end">Total</th>
                <th class="text-end"><?= esc(number_format($totalCash, 2)) ?></th>
                <th class="text-end"><?= esc(number_format($totalPaid, 2)) ?></th>
                <th class="text-end"><?= esc(number_format($totalBalance, 0)) ?></th>
                <th class="text-end"><?= esc(number_format($totalCredit, 2)) ?></th>
                <th class="text-end"><?= esc(number_format($totalPackage, 2)) ?></th>
            </tr>
            </tfoot>
        <?php endif; ?>
    </table>
</div>

<script>
(function () {
    if (!window.jQuery || !jQuery.fn || typeof jQuery.fn.DataTable !== 'function') {
        return;
    }

    var tableId = '#ipd-discharge-report-table';
    if (!jQuery(tableId).length) {
        return;
    }

    if (jQuery.fn.dataTable.isDataTable(tableId)) {
        jQuery(tableId).DataTable().destroy();
    }

    jQuery(tableId).DataTable({
        order: [[4, 'asc'], [0, 'asc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        autoWidth: false
    });
})();
</script>
