<div class="table-responsive">
    <table class="table table-sm table-striped align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Ref / UTR</th>
                <th>Narration</th>
                <th>Amount</th>
                <th>Type</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($entries ?? [])): ?>
                <tr><td colspan="8" class="text-center text-muted py-3">No statement entries found.</td></tr>
            <?php else: ?>
                <?php $sr = 1; foreach (($entries ?? []) as $entry): ?>
                    <?php $status = (string) ($entry['reconciliation_status'] ?? 'unmatched'); ?>
                    <tr>
                        <td><?= $sr++ ?></td>
                        <td class="text-nowrap"><?= esc((string)($entry['entry_date'] ?? '')) ?></td>
                        <td class="font-monospace small"><?= esc((string)($entry['reference_no'] ?? '—')) ?></td>
                        <td class="small"><?= esc((string)($entry['narration'] ?? '—')) ?></td>
                        <td class="text-end fw-semibold">₹<?= number_format((float)($entry['amount'] ?? 0), 2) ?></td>
                        <td>
                            <?php if ((string)($entry['transaction_type']??'') === 'debit'): ?>
                                <span class="badge bg-secondary">Debit</span>
                            <?php else: ?>
                                <span class="badge bg-info text-dark">Credit</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($status === 'matched'): ?>
                                <span class="badge bg-success">Matched</span>
                                <?php if ($entry['matched_payment_id']): ?>
                                    <div class="small text-muted">Pmt #<?= (int)($entry['matched_payment_id']) ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Unmatched</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($status === 'unmatched'): ?>
                                <!-- Match button used when loaded inside the match modal -->
                                <button type="button" class="btn btn-success btn-sm" style="display:none"
                                        data-entry-id="<?= (int)($entry['id']??0) ?>"
                                        onclick="confirmMatch(<?= (int)($entry['id']??0) ?>)">
                                    <i class="bi bi-check2 me-1"></i>Select
                                </button>
                            <?php else: ?>
                                <span class="text-muted small"><?= esc((string)($entry['matched_by']??'')) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
