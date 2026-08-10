<?php
$payment = isset($payment) && is_array($payment) ? $payment : [];
$context = isset($context) && is_array($context) ? $context : [];
$users = isset($users) && is_array($users) ? $users : [];
$bank_sources = isset($bank_sources) && is_array($bank_sources) ? $bank_sources : [];
$paymentId = (int) ($payment['id'] ?? 0);
$paymentMode = (int) ($payment['payment_mode'] ?? 0);
$updateById = (int) ($payment['update_by_id'] ?? 0);
$bankSourceId = (int) ($payment['pay_bank_id'] ?? 0);
$cashStatus = strtolower(trim((string) ($payment['cash_submission_status'] ?? 'open')));
$bankStatus = strtolower(trim((string) ($payment['bank_reconcile_status'] ?? '')));
$locked = ($cashStatus !== '' && $cashStatus !== 'open')
    || (int) ($payment['cash_submission_scroll_id'] ?? 0) > 0
    || $bankStatus !== ''
    || (int) ($payment['bank_statement_entry_id'] ?? 0) > 0
    || (int) ($payment['bank_settlement_entry_id'] ?? 0) > 0;
?>

<div class="card border-primary">
    <div class="card-body pt-3">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <div>
                <h5 class="mb-1">Payment ID: <?= esc((string) $paymentId) ?></h5>
                <div class="text-muted small"><?= esc((string) ($payment['payment_date'] ?? $payment['insert_time'] ?? '')) ?></div>
            </div>
            <span class="badge <?= $paymentMode === 1 ? 'bg-success' : 'bg-primary' ?>"><?= $paymentMode === 1 ? 'CASH' : 'BANK / ONLINE' ?></span>
        </div>

        <div class="row g-3 mb-3 small">
            <div class="col-md-3"><strong>Invoice Type</strong><br><?= esc((string) ($context['type'] ?? '-')) ?></div>
            <div class="col-md-3"><strong>Invoice / Visit No.</strong><br><?= esc((string) ($context['code'] ?? '-')) ?></div>
            <div class="col-md-3"><strong>Patient</strong><br><?= esc((string) ($context['patient'] ?? '-')) ?></div>
            <div class="col-md-3"><strong>Amount</strong><br>Rs. <?= esc(number_format((float) ($payment['amount'] ?? 0), 2)) ?></div>
        </div>

        <?php if ($locked): ?>
            <div class="alert alert-warning">This payment is already submitted or bank-reconciled. Corrections are locked to preserve the finance audit trail.</div>
        <?php endif; ?>

        <input type="hidden" id="payment-edit-id" value="<?= esc((string) $paymentId) ?>">
        <div id="payment-edit-action-message"></div>

        <div class="mb-3">
            <label for="payment-edit-reason" class="form-label">Correction Reason <span class="text-danger">*</span></label>
            <input class="form-control" id="payment-edit-reason" maxlength="120" placeholder="Why is this payment being corrected?" <?= $locked ? 'disabled' : '' ?> required>
        </div>

        <div class="border rounded p-3 mb-3">
            <h6>Payment Mode</h6>
            <?php if ($paymentMode === 1): ?>
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label for="payment-edit-bank-source" class="form-label">Bank / Online Source</label>
                        <select class="form-select" id="payment-edit-bank-source" <?= $locked ? 'disabled' : '' ?>>
                            <option value="">Select source</option>
                            <?php foreach ($bank_sources as $source): ?>
                                <option value="<?= esc((string) $source['id']) ?>"><?= esc((string) $source['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="payment-edit-transaction" class="form-label">Transaction ID / Reference</label>
                        <input class="form-control" id="payment-edit-transaction" maxlength="100" <?= $locked ? 'disabled' : '' ?>>
                    </div>
                    <div class="col-md-3 d-grid"><button class="btn btn-primary" id="payment-edit-to-bank" <?= $locked ? 'disabled' : '' ?>>Change to Bank/Online</button></div>
                </div>
            <?php else: ?>
                <div class="row g-2 align-items-end">
                    <div class="col-md-6"><strong>Source:</strong> <?= esc((string) ($payment['card_bank'] ?? 'Bank/Online')) ?><br><strong>Reference:</strong> <?= esc((string) ($payment['card_tran_id'] ?? '-')) ?></div>
                    <div class="col-md-3 d-grid"><button class="btn btn-primary" id="payment-edit-to-cash" <?= $locked ? 'disabled' : '' ?>>Change to Cash</button></div>
                </div>
            <?php endif; ?>
        </div>

        <div class="border rounded p-3 mb-3">
            <h6>Recorded User</h6>
            <div class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label for="payment-edit-user" class="form-label">User</label>
                    <select class="form-select" id="payment-edit-user" <?= $locked ? 'disabled' : '' ?>>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= esc((string) $user['id']) ?>" <?= (int) $user['id'] === $updateById ? 'selected' : '' ?>><?= esc((string) $user['username']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-grid"><button class="btn btn-primary" id="payment-edit-change-user" <?= $locked ? 'disabled' : '' ?>>Change User</button></div>
            </div>
        </div>

        <div class="border rounded p-3">
            <h6>Payment Amount</h6>
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="payment-edit-amount" class="form-label">Correct Amount</label>
                    <input class="form-control" type="number" min="0.01" step="0.01" id="payment-edit-amount" value="<?= esc(number_format((float) ($payment['amount'] ?? 0), 2, '.', '')) ?>" <?= $locked ? 'disabled' : '' ?>>
                </div>
                <div class="col-md-3 d-grid"><button class="btn btn-primary" id="payment-edit-change-amount" <?= $locked ? 'disabled' : '' ?>>Change Amount</button></div>
            </div>
        </div>
    </div>
</div>