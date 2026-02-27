<?php
$supplierId = (int) ($s_id ?? 0);
$today = date('Y-m-d');
$old = is_array($old ?? null) ? $old : [];
?>

<?php if (!empty($error ?? '')): ?>
    <div class="alert alert-danger py-2"><?= esc((string) $error) ?></div>
<?php endif; ?>

<form id="supplier-ledger-add-form" class="row g-3" data-save-url="<?= base_url('Medical/supplier_account_add_entry/' . $supplierId) ?>">
    <input type="hidden" name="s_id" value="<?= esc((string) $supplierId) ?>">
    <input type="hidden" name="request_key" value="<?= esc((string) ($request_key ?? '')) ?>">

    <div class="col-md-4">
        <label class="form-label">Cr/Dr</label>
        <select name="cr_dr_type" class="form-select" required>
            <option value="0" <?= (string) ($old['cr_dr_type'] ?? '0') === '0' ? 'selected' : '' ?>>Credit</option>
            <option value="1" <?= (string) ($old['cr_dr_type'] ?? '') === '1' ? 'selected' : '' ?>>Debit</option>
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">Mode</label>
        <select name="mode_type" class="form-select">
            <?php foreach (($bank_account_master ?? []) as $row): ?>
                <?php $bankId = (string) ($row->bank_id ?? 0); ?>
                <option value="<?= esc($bankId) ?>" <?= (string) ($old['mode_type'] ?? '') === $bankId ? 'selected' : '' ?>><?= esc(trim((string) (($row->bank_account_name ?? '') . ' ' . ($row->bank_name ?? '')))) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">Date Of Tran.</label>
        <input type="date" name="tran_date" class="form-control" value="<?= esc((string) ($old['tran_date'] ?? $today)) ?>" required>
    </div>

    <div class="col-md-4">
        <label class="form-label">Amount</label>
        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="<?= esc((string) ($old['amount'] ?? '')) ?>" required>
    </div>

    <div class="col-md-8">
        <label class="form-label">Remark/Chq. No.</label>
        <textarea name="tran_desc" class="form-control" rows="2"><?= esc((string) ($old['tran_desc'] ?? '')) ?></textarea>
    </div>

    <div class="col-md-12 text-end">
        <button type="submit" class="btn btn-success">Save</button>
    </div>
</form>
