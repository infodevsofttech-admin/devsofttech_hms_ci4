<div class="table-responsive">
    <table class="table table-sm table-striped align-middle mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>Doctor</th>
                <th>Specialization</th>
                <th>Consultation Rate</th>
                <th>Surgery Rate</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($agreements ?? [])): ?>
                <tr><td colspan="6" class="text-center text-muted">No agreements found.</td></tr>
            <?php else: ?>
                <?php $sr = 1; foreach (($agreements ?? []) as $row): ?>
                    <tr>
                        <td><?= $sr++ ?></td>
                        <td>
                            <div class="fw-semibold"><?= esc((string) ($row['doctor_name'] ?? '')) ?></div>
                            <div class="small text-muted"><?= esc((string) ($row['doctor_code'] ?? '')) ?></div>
                        </td>
                        <td><?= esc((string) ($row['specialization'] ?? '')) ?></td>
                        <td><?= number_format((float) ($row['consultation_rate'] ?? 0), 2) ?></td>
                        <td><?= number_format((float) ($row['surgery_rate'] ?? 0), 2) ?></td>
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
