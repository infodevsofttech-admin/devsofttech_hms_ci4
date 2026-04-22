<div class="table-responsive">
    <table class="table table-sm table-striped align-middle mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>Collection Period</th>
                <th>Department</th>
                <th>Collected By</th>
                <th>Payments</th>
                <th>Total Receipts</th>
                <th>Submitted</th>
                <th>Variance</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($scrolls ?? [])): ?>
                <tr><td colspan="10" class="text-center text-muted">No cash statements submitted yet.</td></tr>
            <?php else: ?>
                <?php $sr = 1; foreach (($scrolls ?? []) as $row): ?>
                    <?php $st = (string) ($row['reconciliation_status'] ?? 'pending'); ?>
                    <tr>
                        <td><?= $sr++ ?></td>
                        <td>
                            <?php
                                $start = trim((string) ($row['start_datetime'] ?? ''));
                                $end = trim((string) ($row['end_datetime'] ?? ''));
                                if ($start !== '' && $end !== '') {
                                    echo esc($start) . ' to ' . esc($end);
                                } else {
                                    echo esc((string) ($row['scroll_date'] ?? ''));
                                }
                            ?>
                        </td>
                        <td><?= esc((string) ($row['department'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['collected_by'] ?? ($row['submitted_by'] ?? ''))) ?></td>
                        <td><?= (int) ($row['payment_count'] ?? 0) ?></td>
                        <td><?= number_format((float) ($row['total_receipts'] ?? 0), 2) ?></td>
                        <td><?= number_format((float) ($row['submitted_amount'] ?? 0), 2) ?></td>
                        <td><?= number_format((float) ($row['variance_amount'] ?? 0), 2) ?></td>
                        <td>
                            <?php if ($st === 'deposited' || $st === 'verified' || $st === 'matched'): ?>
                                <span class="badge bg-success">Deposited</span>
                            <?php elseif ($st === 'received' || $st === 'accepted'): ?>
                                <span class="badge bg-primary">Received</span>
                            <?php elseif ($st === 'submitted' || $st === 'pending'): ?>
                                <span class="badge bg-warning text-dark">Pending</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?= esc(ucfirst($st)) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="btn btn-outline-secondary btn-sm me-1" onclick="financeViewScrollItems(<?= (int) ($row['id'] ?? 0) ?>)">View Payments</button>
                            <?php if ($st === 'submitted' || $st === 'pending'): ?>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="financeScrollAction(<?= (int) ($row['id'] ?? 0) ?>, 'accept')">Mark Received</button>
                            <?php elseif ($st === 'accepted' || $st === 'received'): ?>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="financeScrollAction(<?= (int) ($row['id'] ?? 0) ?>, 'verify')">Mark Deposited</button>
                            <?php else: ?>
                                <span class="text-muted small">No action</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
