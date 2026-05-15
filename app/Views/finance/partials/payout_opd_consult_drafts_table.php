<?php
$rows = $rows ?? [];
?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong>Recent OPD Payout Drafts</strong>
        <span class="small text-muted">Filtered by current date/doctor/state selection</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Payout Date</th>
                        <th>Case Ref</th>
                        <th>Doctor</th>
                        <th class="text-end">Units</th>
                        <th class="text-end">Rate</th>
                        <th class="text-end">Calculated</th>
                        <th class="text-end">Approved</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-3">No payout drafts found for selected filters.</td>
                        </tr>
                    <?php else: ?>
                        <?php $sr = 1; foreach ($rows as $row): ?>
                            <?php $status = strtolower(trim((string) ($row['status'] ?? 'draft'))); ?>
                            <tr>
                                <td><?= $sr++ ?></td>
                                <td class="text-nowrap"><?= esc((string) ($row['payout_date'] ?? '')) ?></td>
                                <td class="font-monospace"><?= esc((string) ($row['case_reference'] ?? '')) ?></td>
                                <td><?= esc((string) ($row['doctor_name'] ?? '')) ?></td>
                                <td class="text-end"><?= (int) ($row['units'] ?? 0) ?></td>
                                <td class="text-end">Rs <?= number_format((float) ($row['rate'] ?? 0), 2) ?></td>
                                <td class="text-end">Rs <?= number_format((float) ($row['calculated_amount'] ?? 0), 2) ?></td>
                                <td class="text-end">Rs <?= number_format((float) ($row['approved_amount'] ?? 0), 2) ?></td>
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
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="editOpdPayoutDraft(<?= (int) ($row['id'] ?? 0) ?>, '<?= esc((string) ($row['payout_date'] ?? ''), 'js') ?>', '<?= esc((string) ($row['approved_amount'] ?? '0'), 'js') ?>', '<?= esc((string) ($row['remarks'] ?? ''), 'js') ?>')">
                                            <i class="bi bi-pencil-square me-1"></i>Edit
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm mt-1"
                                            onclick="deleteOpdPayoutDraft(<?= (int) ($row['id'] ?? 0) ?>)">
                                            <i class="bi bi-trash me-1"></i>Delete
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted small">Locked</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
