<div class="table-responsive">
    <table class="table table-sm table-striped align-middle mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Department</th>
                <th>Total Receipts</th>
                <th>Submitted</th>
                <th>Variance</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($scrolls ?? [])): ?>
                <tr><td colspan="7" class="text-center text-muted">No scroll submissions yet.</td></tr>
            <?php else: ?>
                <?php $sr = 1; foreach (($scrolls ?? []) as $row): ?>
                    <tr>
                        <td><?= $sr++ ?></td>
                        <td><?= esc((string) ($row['scroll_date'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['department'] ?? '')) ?></td>
                        <td><?= number_format((float) ($row['total_receipts'] ?? 0), 2) ?></td>
                        <td><?= number_format((float) ($row['submitted_amount'] ?? 0), 2) ?></td>
                        <td><?= number_format((float) ($row['variance_amount'] ?? 0), 2) ?></td>
                        <td>
                            <?php $st = (string) ($row['reconciliation_status'] ?? 'pending'); ?>
                            <?php if ($st === 'matched'): ?>
                                <span class="badge bg-success">Matched</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Pending</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
