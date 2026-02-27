<?php
$batchRow = is_array($batch ?? null) ? $batch : null;
$valueRows = is_array($values ?? null) ? $values : [];
?>

<div class="card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>AI Extracted Lab Values</strong>
        <?php if (! empty($batchRow['id'])): ?>
            <span class="badge <?= (int) ($batchRow['doctor_verified'] ?? 0) === 1 ? 'bg-success' : 'bg-warning text-dark' ?>">
                <?= (int) ($batchRow['doctor_verified'] ?? 0) === 1 ? 'Doctor Verified' : 'Verification Pending' ?>
            </span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (empty($batchRow)): ?>
            <div class="text-muted">No AI extraction found yet for this invoice.</div>
        <?php else: ?>
            <div class="mb-2 small text-muted">
                Batch #<?= esc((string) ($batchRow['id'] ?? '')) ?> |
                Panel: <?= esc((string) ($batchRow['panel_name'] ?? 'N/A')) ?> |
                Model: <?= esc((string) ($batchRow['model_name'] ?? 'N/A')) ?> |
                Created: <?= esc((string) ($batchRow['created_at'] ?? '')) ?>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Test</th>
                            <th>Value</th>
                            <th>Unit</th>
                            <th>Reference</th>
                            <th>Flag</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (! empty($valueRows)): ?>
                        <?php foreach ($valueRows as $row): ?>
                            <tr>
                                <td><?= esc((string) ($row['test_name'] ?? '')) ?></td>
                                <td><?= esc((string) ($row['test_value'] ?? '')) ?></td>
                                <td><?= esc((string) ($row['unit'] ?? '')) ?></td>
                                <td><?= esc((string) ($row['reference_range'] ?? '')) ?></td>
                                <td><?= esc((string) ($row['abnormal_flag'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-muted">No extracted values found in latest batch.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
