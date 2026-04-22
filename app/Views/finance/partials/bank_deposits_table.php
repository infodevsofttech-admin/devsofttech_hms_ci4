<div class="table-responsive">
    <table class="table table-sm table-striped align-middle mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Department</th>
                <th>Bank</th>
                <th>Slip</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($deposits ?? [])): ?>
                <tr><td colspan="8" class="text-center text-muted">No bank deposits found.</td></tr>
            <?php else: ?>
                <?php $sr = 1; foreach (($deposits ?? []) as $row): ?>
                    <?php $status = (string) ($row['reconciliation_status'] ?? 'pending'); ?>
                    <tr>
                        <td><?= $sr++ ?></td>
                        <td><?= esc((string) ($row['deposit_date'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['department'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['bank_name'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['slip_no'] ?? '')) ?></td>
                        <td><?= number_format((float) ($row['deposited_amount'] ?? 0), 2) ?></td>
                        <td>
                            <?php if ($status === 'statement_updated'): ?>
                                <span class="badge bg-success">Statement Updated</span>
                            <?php elseif ($status === 'audited'): ?>
                                <span class="badge bg-info text-dark">Audited</span>
                            <?php elseif ($status === 'matched'): ?>
                                <span class="badge bg-success">Matched</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($status === 'statement_updated'): ?>
                                <span class="text-muted small">Completed</span>
                            <?php elseif ($status === 'audited'): ?>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="financeDepositStatusAction(<?= (int) ($row['id'] ?? 0) ?>, 'statement_update')">Update Statement</button>
                            <?php else: ?>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="financeDepositStatusAction(<?= (int) ($row['id'] ?? 0) ?>, 'audit')">Mark Audited</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
