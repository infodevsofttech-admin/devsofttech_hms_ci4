<?php
$rows = $rows ?? [];
$showHeader = (bool) ($showHeader ?? false);
$dateFrom = $dateFrom ?? date('Y-m-d');
$dateTo = $dateTo ?? date('Y-m-d');

$totals = [
    'no_invoice' => 0,
    'no_patients' => 0,
    'net_amount' => 0.0,
    'opd_cash_amount' => 0.0,
    'opd_org_amount' => 0.0,
    'ipd_cash_amount' => 0.0,
    'ipd_credit_amount' => 0.0,
    'ipd_package_amount' => 0.0,
];
?>

<?php if ($showHeader): ?>
    <h3 style="margin:0 0 8px 0;">Sale Day Report</h3>
    <p style="margin:0 0 12px 0;">From: <?= esc($dateFrom) ?> | To: <?= esc($dateTo) ?></p>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-bordered table-striped table-sm align-middle">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Sale Date</th>
                <th class="text-end">No Invoice</th>
                <th class="text-end">No Patient</th>
                <th class="text-end">Total Net</th>
                <th class="text-end">OPD Cash</th>
                <th class="text-end">OPD Org</th>
                <th class="text-end">IPD Cash</th>
                <th class="text-end">IPD Credit</th>
                <th class="text-end">IPD Package</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="10" class="text-center text-muted">No records found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $index => $row): ?>
                    <?php
                    $totals['no_invoice'] += (int) ($row['no_invoice'] ?? 0);
                    $totals['no_patients'] += (int) ($row['no_patients'] ?? 0);
                    $totals['net_amount'] += (float) ($row['net_amount'] ?? 0);
                    $totals['opd_cash_amount'] += (float) ($row['opd_cash_amount'] ?? 0);
                    $totals['opd_org_amount'] += (float) ($row['opd_org_amount'] ?? 0);
                    $totals['ipd_cash_amount'] += (float) ($row['ipd_cash_amount'] ?? 0);
                    $totals['ipd_credit_amount'] += (float) ($row['ipd_credit_amount'] ?? 0);
                    $totals['ipd_package_amount'] += (float) ($row['ipd_package_amount'] ?? 0);
                    ?>
                    <tr>
                        <td><?= esc((string) ($index + 1)) ?></td>
                        <td><?= esc((string) ($row['sale_date'] ?? '')) ?></td>
                        <td class="text-end"><?= esc((string) ((int) ($row['no_invoice'] ?? 0))) ?></td>
                        <td class="text-end"><?= esc((string) ((int) ($row['no_patients'] ?? 0))) ?></td>
                        <td class="text-end"><?= esc(number_format((float) ($row['net_amount'] ?? 0), 2)) ?></td>
                        <td class="text-end"><?= esc(number_format((float) ($row['opd_cash_amount'] ?? 0), 2)) ?></td>
                        <td class="text-end"><?= esc(number_format((float) ($row['opd_org_amount'] ?? 0), 2)) ?></td>
                        <td class="text-end"><?= esc(number_format((float) ($row['ipd_cash_amount'] ?? 0), 2)) ?></td>
                        <td class="text-end"><?= esc(number_format((float) ($row['ipd_credit_amount'] ?? 0), 2)) ?></td>
                        <td class="text-end"><?= esc(number_format((float) ($row['ipd_package_amount'] ?? 0), 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot class="table-light">
            <tr>
                <th colspan="2" class="text-end">Total</th>
                <th class="text-end"><?= esc((string) $totals['no_invoice']) ?></th>
                <th class="text-end"><?= esc((string) $totals['no_patients']) ?></th>
                <th class="text-end"><?= esc(number_format($totals['net_amount'], 2)) ?></th>
                <th class="text-end"><?= esc(number_format($totals['opd_cash_amount'], 2)) ?></th>
                <th class="text-end"><?= esc(number_format($totals['opd_org_amount'], 2)) ?></th>
                <th class="text-end"><?= esc(number_format($totals['ipd_cash_amount'], 2)) ?></th>
                <th class="text-end"><?= esc(number_format($totals['ipd_credit_amount'], 2)) ?></th>
                <th class="text-end"><?= esc(number_format($totals['ipd_package_amount'], 2)) ?></th>
            </tr>
        </tfoot>
    </table>
</div>
