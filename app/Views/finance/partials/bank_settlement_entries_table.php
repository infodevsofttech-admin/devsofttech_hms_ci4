<div class="table-responsive">
    <table class="table table-sm table-striped align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Settlement Date</th>
                <th>Settlement Ref</th>
                <th>Bank Name</th>
                <th class="text-end">Payment Count</th>
                <th class="text-end">Total Amount</th>
                <th>Remarks</th>
                <th>Status</th>
                <th>Created By</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($entries ?? [])): ?>
                <tr>
                    <td colspan="10" class="text-center text-muted py-3">No settlement entries found.</td>
                </tr>
            <?php else: ?>
                <?php $sr = 1; foreach (($entries ?? []) as $row): ?>
                    <?php $st = trim((string) ($row['reconciliation_status'] ?? 'unmatched')); ?>
                    <?php $bankNames = trim((string) ($row['bank_names'] ?? '')); ?>
                    <tr>
                        <td><?= $sr++ ?></td>
                        <td class="text-nowrap"><?= esc((string) ($row['settlement_date'] ?? '')) ?></td>
                        <td class="font-monospace"><?= esc((string) ($row['settlement_ref'] ?? '')) ?></td>
                        <td><?= esc($bankNames !== '' ? $bankNames : '—') ?></td>
                        <td class="text-end"><?= (int) ($row['payment_count'] ?? 0) ?></td>
                        <td class="text-end fw-semibold">₹<?= number_format((float) ($row['total_amount'] ?? 0), 2) ?></td>
                        <td><?= esc((string) ($row['remarks'] ?? '—')) ?></td>
                        <td>
                            <?php if ($st === 'matched'): ?>
                                <span class="badge bg-success">Matched</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Unmatched</span>
                            <?php endif; ?>
                        </td>
                        <td><?= esc((string) ($row['created_by'] ?? '—')) ?></td>
                        <td>
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                onclick="openSettlementLinkedPayments(<?= (int) ($row['id'] ?? 0) ?>, '<?= esc((string) ($row['settlement_ref'] ?? ''), 'js') ?>')">
                                <i class="bi bi-list-check me-1"></i>View Linked Payments
                            </button>
                            <?php if ($st !== 'matched'): ?>
                                <button type="button" class="btn btn-outline-success btn-sm mt-1"
                                    onclick="matchSettlementWithBankStatement(<?= (int) ($row['id'] ?? 0) ?>)">
                                    <i class="bi bi-check2-circle me-1"></i>Match with Bank Statement
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
