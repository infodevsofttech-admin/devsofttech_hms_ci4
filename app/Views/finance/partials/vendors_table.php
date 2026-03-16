<div class="table-responsive">
    <table class="table table-sm table-striped align-middle mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>Code</th>
                <th>Vendor</th>
                <th>Contact</th>
                <th>Phone</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($vendors ?? [])): ?>
                <tr><td colspan="6" class="text-center text-muted">No vendors yet.</td></tr>
            <?php else: ?>
                <?php $sr = 1; foreach (($vendors ?? []) as $row): ?>
                    <tr>
                        <td><?= $sr++ ?></td>
                        <td><?= esc((string) ($row['vendor_code'] ?? '')) ?></td>
                        <td>
                            <div class="fw-semibold"><?= esc((string) ($row['vendor_name'] ?? '')) ?></div>
                            <div class="small text-muted"><?= esc((string) ($row['gst_no'] ?? '')) ?></div>
                        </td>
                        <td><?= esc((string) ($row['contact_person'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['phone'] ?? '')) ?></td>
                        <td>
                            <?php if ((int) ($row['status'] ?? 0) === 1): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
