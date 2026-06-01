<?php
$rows = $rows ?? [];
$showHeader = (bool) ($showHeader ?? false);
$saleDate = $saleDate ?? date('Y-m-d');
$billType = (int) ($billType ?? 0);

$billTypeLabel = [
    0 => 'All Bills',
    1 => 'OPD Cash',
    2 => 'OPD Organisation',
    3 => 'IPD Cash',
    4 => 'IPD Credit',
    5 => 'IPD Package',
    6 => 'Sale Return',
][$billType] ?? 'All Bills';

$totals = [
    'net_amount' => 0.0,
    'payment_received' => 0.0,
    'payment_balance' => 0.0,
];
?>

<?php if ($showHeader): ?>
    <h3 style="margin:0 0 8px 0;">Day Report</h3>
    <p style="margin:0 0 12px 0;">Date: <?= esc($saleDate) ?> | Bill Type: <?= esc($billTypeLabel) ?></p>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-bordered table-striped table-sm align-middle">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Bill No</th>
                <th>Date</th>
                <th>UHID</th>
                <th>Patient Name</th>
                <th>Org</th>
                <th>Bill Type</th>
                <th class="text-end">Net Amount</th>
                <th class="text-end">Paid</th>
                <th class="text-end">Balance</th>
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
                    $totals['net_amount'] += (float) ($row['net_amount'] ?? 0);
                    $totals['payment_received'] += (float) ($row['payment_received'] ?? 0);
                    $totals['payment_balance'] += (float) ($row['payment_balance'] ?? 0);
                    ?>
                    <tr>
                        <td><?= esc((string) ($index + 1)) ?></td>
                        <td><?= esc((string) ($row['bill_no'] ?? '-')) ?></td>
                        <td><?= esc((string) ($row['inv_date'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['uhid'] ?? '-')) ?></td>
                        <td><?= esc((string) ($row['patient_name'] ?? '-')) ?></td>
                        <td><?= esc((string) ($row['org_name'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['bill_type'] ?? '')) ?></td>
                        <td class="text-end"><?= esc(number_format((float) ($row['net_amount'] ?? 0), 2)) ?></td>
                        <td class="text-end"><?= esc(number_format((float) ($row['payment_received'] ?? 0), 2)) ?></td>
                        <td class="text-end"><?= esc(number_format((float) ($row['payment_balance'] ?? 0), 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot class="table-light">
            <tr>
                <th colspan="7" class="text-end">Total</th>
                <th class="text-end"><?= esc(number_format($totals['net_amount'], 2)) ?></th>
                <th class="text-end"><?= esc(number_format($totals['payment_received'], 2)) ?></th>
                <th class="text-end"><?= esc(number_format($totals['payment_balance'], 2)) ?></th>
            </tr>
        </tfoot>
    </table>
</div>
