<div class="table-responsive">
    <table class="table table-sm table-striped align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Terminal</th>
                <th>Settlement Amt</th>
                <th>System Total</th>
                <th>Variance</th>
                <th>Transactions</th>
                <th>Status</th>
                <th>By</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows ?? [])): ?>
                <tr><td colspan="10" class="text-center text-muted py-3">No POS settlements recorded yet.</td></tr>
            <?php else: ?>
                <?php $sr = 1; foreach (($rows ?? []) as $row): ?>
                    <?php
                    $status   = (string) ($row['status'] ?? 'pending');
                    $variance = (float) ($row['variance'] ?? 0);
                    $badgeMap = [
                        'matched'  => '<span class="badge bg-success">Matched</span>',
                        'variance' => '<span class="badge bg-danger">Variance</span>',
                        'accepted' => '<span class="badge bg-primary">Accepted</span>',
                        'pending'  => '<span class="badge bg-warning text-dark">Pending</span>',
                    ];
                    $badge = $badgeMap[$status] ?? '<span class="badge bg-secondary">' . esc($status) . '</span>';
                    ?>
                    <tr>
                        <td><?= $sr++ ?></td>
                        <td class="text-nowrap"><?= esc((string)($row['settlement_date']??'')) ?></td>
                        <td>
                            <span class="fw-semibold"><?= esc((string)($row['terminal_id']??'')) ?></span>
                            <?php if ((string)($row['terminal_name']??'') !== ''): ?>
                                <div class="small text-muted"><?= esc((string)($row['terminal_name'])) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">₹<?= number_format((float)($row['settlement_amount']??0),2) ?></td>
                        <td class="text-end">₹<?= number_format((float)($row['system_total']??0),2) ?></td>
                        <td class="text-end <?= abs($variance) <= 0.01 ? 'text-success' : 'text-danger fw-bold' ?>">
                            <?= ($variance >= 0 ? '+' : '') . number_format($variance, 2) ?>
                        </td>
                        <td class="text-center"><?= (int)($row['payment_count']??0) ?></td>
                        <td><?= $badge ?></td>
                        <td class="small">
                            <?= esc((string)($row['reconciled_by']??'')) ?>
                            <?php if ((string)($row['reconciled_at']??'') !== ''): ?>
                                <div class="text-muted"><?= esc(substr((string)$row['reconciled_at'],0,16)) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap">
                            <?php if ($status === 'accepted'): ?>
                                <span class="text-muted small">Closed</span>
                            <?php elseif ($status === 'matched'): ?>
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                        onclick="openAcceptModal(<?= (int)($row['id']??0) ?>)">
                                    <i class="bi bi-lock me-1"></i>Accept
                                </button>
                            <?php elseif ($status === 'variance'): ?>
                                <button type="button" class="btn btn-outline-danger btn-sm"
                                        onclick="openAcceptModal(<?= (int)($row['id']??0) ?>)">
                                    <i class="bi bi-exclamation-triangle me-1"></i>Accept Variance
                                </button>
                            <?php else: ?>
                                <span class="text-muted small">No txns found</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
