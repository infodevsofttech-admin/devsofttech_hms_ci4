<div class="table-responsive">
    <table class="table table-sm table-striped align-middle mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Doctor</th>
                <th>Type</th>
                <th>Units</th>
                <th>Calculated</th>
                <th>Approved</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($payouts ?? [])): ?>
                <tr><td colspan="9" class="text-center text-muted">No payouts found.</td></tr>
            <?php else: ?>
                <?php $sr = 1; foreach (($payouts ?? []) as $row): ?>
                    <?php $status = (string) ($row['status'] ?? 'draft'); ?>
                    <tr>
                        <td><?= $sr++ ?></td>
                        <td><?= esc((string) ($row['payout_date'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['doctor_name'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['payout_type'] ?? '')) ?></td>
                        <td><?= (int) ($row['units'] ?? 0) ?></td>
                        <td><?= number_format((float) ($row['calculated_amount'] ?? 0), 2) ?></td>
                        <td><?= number_format((float) ($row['approved_amount'] ?? 0), 2) ?></td>
                        <td>
                            <?php if ($status === 'paid'): ?>
                                <span class="badge bg-success">Paid</span>
                            <?php elseif ($status === 'ceo_approved'): ?>
                                <span class="badge bg-primary">CEO Approved</span>
                            <?php elseif ($status === 'finance_approved'): ?>
                                <span class="badge bg-info text-dark">Finance Approved</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Draft</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($status === 'draft'): ?>
                                <button type="button" class="btn btn-sm btn-outline-info" onclick="financePayoutApprove(<?= (int) ($row['id'] ?? 0) ?>, 'finance')">Finance Approve</button>
                            <?php elseif ($status === 'finance_approved'): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="financePayoutApprove(<?= (int) ($row['id'] ?? 0) ?>, 'ceo')">CEO Approve</button>
                            <?php elseif ($status === 'ceo_approved'): ?>
                                <button type="button" class="btn btn-sm btn-outline-success" onclick="financePayoutApprove(<?= (int) ($row['id'] ?? 0) ?>, 'paid')">Mark Paid</button>
                            <?php else: ?>
                                <span class="text-muted small">Completed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
