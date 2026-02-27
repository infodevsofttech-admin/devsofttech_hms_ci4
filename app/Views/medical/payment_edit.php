<?php
$payId = (int) ($payment_history['id'] ?? 0);
$paymentMode = (int) ($payment_mode ?? 1);
$amount = (float) ($payment_history['amount'] ?? 0);
$amountStr = (float) ($amount_str ?? 0);
$updateById = (int) ($payment_history['update_by_id'] ?? 0);
$payBankId = (int) ($pay_bank_id ?? 0);
?>

<div class="card border-primary">
    <div class="card-body pt-3">
        <h5 class="mb-3">Payment ID : <?= esc((string) ($rec_no ?? '')) ?></h5>

        <div class="row mb-3 small">
            <div class="col-md-2 text-primary">Invoice Type : <span class="text-success"><?= esc((string) ($inv_Type ?? '')) ?></span></div>
            <div class="col-md-3 text-primary">IPD/OPD No : <span class="text-success"><?= esc((string) ($invoice_no ?? '')) ?></span></div>
            <div class="col-md-3 text-primary">Invoice To : <span class="text-success"><?= esc(ucwords((string) ($invoice_to_name ?? ''))) ?></span></div>
            <div class="col-md-2 text-primary">Amount : <span class="text-success">Rs. <?= esc(number_format($amountStr, 2)) ?></span></div>
            <div class="col-md-2 text-primary">Mode of Payment. : <span class="text-success"><?= $paymentMode === 1 ? 'CASH' : 'BANK' ?></span></div>
        </div>

        <input type="hidden" value="<?= esc((string) $payId) ?>" id="p_id" name="p_id" />

        <div class="border rounded p-3 mb-3">
        <div class="row mb-1">
            <div class="col-12"><h6 class="mb-2">Payment Mode Correction</h6></div>
        </div>
        <div class="row mb-0">
            <?php if ($paymentMode === 1): ?>
                <div class="col-md-5">
                    <label class="form-label">Payment By Bank/Online</label>
                    <select class="form-select" id="cbo_pay_type">
                        <?php if (!empty($bank_sources ?? [])): ?>
                            <?php foreach (($bank_sources ?? []) as $row): ?>
                                <?php
                                    $sourceId = (int) ($row['id'] ?? 0);
                                    $label = trim((string) ($row['_label'] ?? ''));
                                    if ($label === '') {
                                        $label = $sourceId > 0 ? ('Source #' . $sourceId) : 'Default Bank Source';
                                    }
                                ?>
                                <option value="<?= esc((string) $sourceId) ?>" <?= $sourceId === $payBankId ? 'selected' : '' ?>><?= esc($label) ?></option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="0">Default Bank Source</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tran. ID / Ref.</label>
                    <input class="form-control" id="input_card_tran" placeholder="Card Tran.ID." type="text" autocomplete="off">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-primary" id="btn_update_bank">Change to Bank/Online</button>
                </div>
            <?php else: ?>
                <div class="col-md-4">
                    <label class="form-label">Current Bank Source</label>
                    <select class="form-select" id="cbo_pay_type" disabled>
                        <?php if (!empty($bank_sources ?? [])): ?>
                            <?php foreach (($bank_sources ?? []) as $row): ?>
                                <?php
                                    $sourceId = (int) ($row['id'] ?? 0);
                                    $label = trim((string) ($row['_label'] ?? ''));
                                    if ($label === '') {
                                        $label = $sourceId > 0 ? ('Source #' . $sourceId) : 'Default Bank Source';
                                    }
                                ?>
                                <option value="<?= esc((string) $sourceId) ?>" <?= $sourceId === $payBankId ? 'selected' : '' ?>><?= esc($label) ?></option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="0">Bank/Online</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" class="btn btn-primary" id="btn_update_cash">Change to CASH</button>
                </div>
            <?php endif; ?>
        </div>
        </div>

        <div class="border rounded p-3 mb-3">
        <div class="row mb-1">
            <div class="col-12"><h6 class="mb-2">Change User</h6></div>
        </div>
        <div class="row mb-0">
            <div class="col-md-4">
                <label for="user_list" class="form-label">Select User</label>
                <select name="user_list" id="user_list" class="form-select">
                    <?php foreach (($all_user_list ?? []) as $user): ?>
                        <?php
                        $id = (int) ($user['id'] ?? 0);
                        $username = (string) ($user['username'] ?? ('user' . $id));
                        $firstName = trim((string) ($user['first_name'] ?? ''));
                        $lastName = trim((string) ($user['last_name'] ?? ''));
                        $label = $username . '[' . trim($firstName . ' ' . $lastName) . ']';
                        ?>
                        <option value="<?= esc((string) $id) ?>" <?= $id === $updateById ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="button" class="btn btn-primary" id="btn_update_user">Change User</button>
            </div>
        </div>
        </div>

        <div class="border rounded p-3">
        <div class="row mb-1">
            <div class="col-12"><h6 class="mb-2">Change/Correct Amount</h6></div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <label class="form-label">Amount Value</label>
                <input class="form-control" id="input_change_value" name="input_change_value" value="<?= esc(number_format($amount, 2, '.', '')) ?>" type="text" autocomplete="off">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="button" class="btn btn-primary" id="btn_update_amount">Change Amount</button>
            </div>
        </div>
        </div>
    </div>
</div>


