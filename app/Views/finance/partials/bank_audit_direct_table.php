<div class="table-responsive">
    <table class="table table-sm table-striped align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th style="width:36px;"><input type="checkbox" id="select_all_bank_payments" onchange="toggleSelectAllBankPayments(this)"></th>
                <th>#</th>
                <th>HMS Ref</th>
                <th>Payment Date</th>
                <th>Amount</th>
                <th>Txn Ref / UTR</th>
                <th>Channel</th>
                <th>Accepted By / Matched By</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows ?? [])): ?>
                <tr><td colspan="10" class="text-center text-muted py-3">No bank payments found for the selected criteria.</td></tr>
            <?php else: ?>
                <?php $sr = 1; foreach (($rows ?? []) as $row): ?>
                    <?php
                    $status = (string) ($row['bank_reconcile_status'] ?? '');
                    $isMatched = $status === 'matched';
                    $sourceLabel = trim((string) ($row['bank_source_label'] ?? ''));
                    if ($sourceLabel === '[]') {
                        $sourceLabel = '';
                    }
                    $isPos = stripos($sourceLabel, 'machine') !== false || stripos((string) ($row['bankcard_machine'] ?? ''), 'machine') !== false;
                    $channel = $isPos ? 'POS' : 'UPI/Direct';
                    ?>
                    <tr>
                        <td>
                            <?php if (! $isMatched): ?>
                                <input type="checkbox" class="bank-payment-check" value="<?= (int) ($row['id'] ?? 0) ?>">
                            <?php endif; ?>
                        </td>
                        <td><?= $sr++ ?></td>
                        <td class="font-monospace small">PH-<?= (int) ($row['id'] ?? 0) ?></td>
                        <td class="text-nowrap"><?= esc((string) ($row['payment_date'] ?? '')) ?></td>
                        <td class="text-end fw-semibold">₹<?= number_format((float) ($row['amount'] ?? 0), 2) ?></td>
                        <td class="font-monospace small"><?= esc((string) ($row['card_tran_id'] ?? '—')) ?></td>
                        <td>
                            <span class="badge <?= $isPos ? 'bg-info text-dark' : 'bg-secondary' ?>"><?= esc($channel) ?></span>
                            <?php if ($sourceLabel !== ''): ?>
                                <div class="small text-muted"><?= esc($sourceLabel) ?></div>
                            <?php elseif ((string) ($row['bankcard_machine'] ?? '') !== ''): ?>
                                <div class="small text-muted"><?= esc((string) ($row['bankcard_machine'] ?? '')) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="small">
                            <div><?= esc((string) ($row['accepted_by_name'] ?? '—')) ?></div>
                            <?php if ($isMatched && (string)($row['matched_by'] ?? '') !== ''): ?>
                                <div class="text-muted" style="font-size:0.78em;">
                                    <i class="bi bi-check2-circle me-1 text-success"></i>Matched: <?= esc((string)($row['matched_by'] ?? '')) ?>
                                    <?php if ((string)($row['matched_at'] ?? '') !== ''): ?>
                                        <br><span><?= esc(date('d-M-y H:i', strtotime((string)$row['matched_at']))) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($isMatched): ?>
                                <span class="badge bg-success">Matched</span>
                                <?php if ((string) ($row['bank_reconcile_batch_ref'] ?? '') !== ''): ?>
                                    <div class="small text-muted"><?= esc((string) ($row['bank_reconcile_batch_ref'] ?? '')) ?></div>
                                <?php endif; ?>
                                <?php if ((int) ($row['bank_settlement_entry_id'] ?? 0) > 0): ?>
                                    <div class="small mt-1"><span class="badge bg-warning text-dark">Settlement</span></div>
                                    <?php if ((string) ($row['settlement_ref'] ?? '') !== ''): ?>
                                        <div class="small text-muted"><?= esc((string) ($row['settlement_ref'] ?? '')) ?></div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Unmatched</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap">
                            <?php if ($isMatched): ?>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="unmatchPayment(<?= (int)($row['id']??0) ?>)">
                                    <i class="bi bi-x-circle me-1"></i>Unmatch
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="matchSinglePayment(<?= (int)($row['id']??0) ?>)">
                                    <i class="bi bi-link-45deg me-1"></i>Match
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
