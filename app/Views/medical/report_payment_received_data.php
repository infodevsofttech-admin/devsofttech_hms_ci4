<?php
$rows = $rows ?? [];
$showHeader = (bool) ($showHeader ?? false);
$dateFrom = $dateFrom ?? date('Y-m-d');
$dateTo = $dateTo ?? date('Y-m-d');

$totalAmount = 0.0;
?>

<?php if ($showHeader): ?>
    <h3 style="margin:0 0 8px 0;">Payment Report</h3>
    <p style="margin:0 0 12px 0;">From: <?= esc($dateFrom) ?> | To: <?= esc($dateTo) ?></p>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-bordered table-striped table-sm align-middle">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Payment Date</th>
                <th>Bill No</th>
                <th>UHID</th>
                <th>Patient Name</th>
                <th>Payment Type</th>
                <th class="text-end">Amount</th>
                <th>Received By</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted">No records found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $index => $row): ?>
                    <?php
                    $amount = (float) ($row['amount'] ?? 0);
                    $isDebit = (int) ($row['credit_debit'] ?? 0) !== 0;
                    $signedAmount = $isDebit ? (-1 * abs($amount)) : abs($amount);
                    $totalAmount += $signedAmount;
                    ?>
                    <tr>
                        <td><?= esc((string) ($index + 1)) ?></td>
                        <td><?= esc((string) ($row['payment_date'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['bill_no'] ?? '-')) ?></td>
                        <td><?= esc((string) ($row['uhid'] ?? '-')) ?></td>
                        <td><?= esc((string) ($row['patient_name'] ?? '-')) ?></td>
                        <td><?= esc((string) ($row['payment_type'] ?? '-')) ?></td>
                        <td class="text-end\"><?= esc(number_format($signedAmount, 2)) ?></td>
                        <td><?= esc((string) ($row['update_by'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot class="table-light">
            <tr>
                <th colspan="6" class="text-end">Total</th>
                <th class="text-end"><?= esc(number_format($totalAmount, 2)) ?></th>
                <th></th>
            </tr>
        </tfoot>
    </table>
</div>
