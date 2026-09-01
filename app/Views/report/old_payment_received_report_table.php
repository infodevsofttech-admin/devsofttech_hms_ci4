<div class="alert alert-info d-flex justify-content-between align-items-center mb-3">
    <div>
        <i class="bi bi-info-circle-fill me-2"></i>
        Payment Date Range: <strong><?= esc($min_range ?? '') ?></strong> to <strong><?= esc($max_range ?? '') ?></strong>
    </div>
    <div>
        Total Records: <span class="badge bg-primary fs-6"><?= count($rows ?? []) ?></span> | 
        Total Amount Collected: <span class="badge bg-success fs-6">₹<?= number_format((float) ($total_amount ?? 0), 2, '.', ',') ?></span>
    </div>
</div>

<?php if (empty($rows)): ?>
    <div class="text-center py-4 border rounded bg-light text-muted">
        <i class="bi bi-check-circle-fill text-success fs-4 d-block mb-2"></i>
        <h5>No Old Payment Receipts Found</h5>
        <p class="mb-0 small">No transactions found where Invoice Date &lt; Payment Date in the selected date range.</p>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle border" id="oldPaymentTable">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Payment Date</th>
                    <th>Invoice Date</th>
                    <th>Delay (Days)</th>
                    <th>Invoice Code</th>
                    <th>Patient Name</th>
                    <th>Patient Code</th>
                    <th class="text-end">Invoice Net Amt</th>
                    <th class="text-end">Amount Received</th>
                    <th>Pay Mode</th>
                    <th>Collected By</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($rows as $row): ?>
                    <?php 
                        $payDate = ! empty($row['payment_date']) ? date('d-m-Y H:i', strtotime((string) $row['payment_date'])) : '-';
                        $invDate = ! empty($row['inv_date']) ? date('d-m-Y', strtotime((string) $row['inv_date'])) : '-';
                        $delay = (int) ($row['delay_days'] ?? 0);
                    ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><span class="badge bg-secondary"><?= esc($payDate) ?></span></td>
                        <td><span class="badge bg-warning text-dark"><?= esc($invDate) ?></span></td>
                        <td>
                            <span class="badge bg-danger-subtle text-danger font-monospace">
                                <i class="bi bi-clock-history me-1"></i><?= $delay ?> day<?= $delay > 1 ? 's' : '' ?> ago
                            </span>
                        </td>
                        <td class="fw-bold"><?= esc((string) ($row['invoice_code'] ?? '-')) ?></td>
                        <td><?= esc((string) ($row['patient_name'] ?? '-')) ?></td>
                        <td><?= esc((string) ($row['patient_code'] ?? '-')) ?></td>
                        <td class="text-end fw-bold"><?= number_format((float) ($row['net_amount'] ?? 0), 2, '.', ',') ?></td>
                        <td class="text-end fw-bold text-success">₹<?= number_format((float) ($row['paid_amount'] ?? 0), 2, '.', ',') ?></td>
                        <td><span class="badge bg-info text-dark"><?= esc((string) ($row['pay_mode'] ?? 'Cash')) ?></span></td>
                        <td><?= esc((string) ($row['update_by'] ?? 'Staff')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
