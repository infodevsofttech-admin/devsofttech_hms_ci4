<div class="table-responsive">
    <table class="table table-sm table-striped align-middle mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Type</th>
                <th>Department</th>
                <th>Reference</th>
                <th>Amount</th>
                <th>Mode</th>
                <th>Compliance</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($transactions ?? [])): ?>
                <tr><td colspan="8" class="text-center text-muted">No cash transactions yet.</td></tr>
            <?php else: ?>
                <?php $sr = 1; foreach (($transactions ?? []) as $row): ?>
                    <tr>
                        <td><?= $sr++ ?></td>
                        <td><?= esc((string) ($row['txn_date'] ?? '')) ?></td>
                        <td>
                            <div><?= esc((string) ($row['txn_type'] ?? '')) ?></div>
                            <div class="small text-muted"><?= esc((string) ($row['flow_type'] ?? '')) ?></div>
                        </td>
                        <td><?= esc((string) ($row['department'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['reference_no'] ?? '')) ?></td>
                        <td><?= number_format((float) ($row['amount'] ?? 0), 2) ?></td>
                        <td><?= esc((string) ($row['mode'] ?? '')) ?></td>
                        <td>
                            <?php if ((int) ($row['is_compliance_hold'] ?? 0) === 1): ?>
                                <span class="badge bg-danger">Hold</span>
                                <div class="small text-muted"><?= esc((string) ($row['compliance_note'] ?? '')) ?></div>
                            <?php else: ?>
                                <span class="badge bg-success">Clear</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
