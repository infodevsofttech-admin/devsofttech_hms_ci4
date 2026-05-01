<div class="table-responsive">
    <table class="table table-sm table-bordered align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Request No</th>
                <th>Date</th>
                <th>Status</th>
                <th class="text-end">Requested</th>
                <th class="text-end">Paid</th>
                <th class="text-end">Pending</th>
                <th>Created By</th>
                <th class="text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows ?? [])): ?>
                <tr><td colspan="9" class="text-center text-muted py-3">No requests found.</td></tr>
            <?php else: ?>
                <?php foreach (($rows ?? []) as $i => $row): ?>
                    <?php
                        $status = (string) ($row['status'] ?? '');
                        $badgeClass = 'bg-secondary';
                        if ($status === 'submitted') {
                            $badgeClass = 'bg-primary';
                        } elseif ($status === 'finance_review') {
                            $badgeClass = 'bg-warning text-dark';
                        } elseif ($status === 'approved') {
                            $badgeClass = 'bg-success';
                        } elseif ($status === 'partially_paid') {
                            $badgeClass = 'bg-info text-dark';
                        } elseif ($status === 'paid') {
                            $badgeClass = 'bg-dark';
                        } elseif ($status === 'rejected') {
                            $badgeClass = 'bg-danger';
                        }
                    ?>
                    <tr>
                        <td><?= (int) $i + 1 ?></td>
                        <td><strong><?= esc((string) ($row['request_no'] ?? '')) ?></strong><br><small class="text-muted">ID: <?= (int) ($row['id'] ?? 0) ?></small></td>
                        <td><?= esc((string) ($row['request_date'] ?? '')) ?></td>
                        <td><span class="badge <?= esc($badgeClass) ?>"><?= esc($status) ?></span></td>
                        <td class="text-end"><?= esc(number_format((float) ($row['requested_amount'] ?? 0), 2)) ?></td>
                        <td class="text-end"><?= esc(number_format((float) ($row['paid_amount'] ?? 0), 2)) ?></td>
                        <td class="text-end"><?= esc(number_format((float) ($row['pending_amount'] ?? 0), 2)) ?></td>
                        <td><?= esc((string) ($row['created_by'] ?? '')) ?></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="openMedicalStoreRequestAction(<?= (int) ($row['id'] ?? 0) ?>)">Next</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
