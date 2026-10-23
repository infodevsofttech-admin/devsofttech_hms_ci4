<?php
$inv = $inv_master_data[0] ?? null;
if (! $inv) {
    echo '<div class="alert alert-warning mb-0">Purchase invoice not found.</div>';
    return;
}

$invStatus = (int) ($inv->inv_status ?? 0);
$canUpdatePurchaseStatus = (bool) ($can_update_purchase_status ?? false);
$invDate = '';
if (!empty($inv->date_of_invoice) && $inv->date_of_invoice !== '0000-00-00') {
    $time = strtotime((string) $inv->date_of_invoice);
    if ($time !== false && $time > 0) {
        $invDate = date('d/m/Y', $time);
    }
}
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="mb-0">Purchase Master Edit</h5>
            <small class="text-muted">
                Invoice No: <strong><?= esc((string) ($inv->Invoice_no ?? '')) ?></strong> |
                Supplier: <strong><?= esc($inv->name_supplier ?? '-') ?></strong> |
                Status: <strong><?= $invStatus === 1 ? '<span class="badge bg-success">Final and Checked</span>' : '<span class="badge bg-warning text-dark">Pending Entry</span>' ?></strong>
            </small>
        </div>
        <div>
            <button onclick="var t = document.getElementById('searchresult') ? 'searchresult' : 'medical-main'; load_form_div('<?= base_url('Medical/PurchaseInvoiceEdit/' . (int) ($inv->id ?? 0)) ?>', t, 'Purchase Invoice');" type="button" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-card-checklist"></i> View Invoice Items
            </button>
            <?php if (!empty($linked_grn)): ?>
                <button onclick="load_form_div('<?= base_url('Medical/grn_detail/' . (int) $linked_grn['grn_id']) ?>','medical-main','GRN Detail');" type="button" class="btn btn-outline-info btn-sm ms-1">
                    <i class="bi bi-box-arrow-left"></i> Back to GRN #<?= esc($linked_grn['grn_number'] ?? $linked_grn['grn_id']) ?>
                </button>
            <?php endif; ?>
            <button onclick="load_form_div('<?= base_url('Medical/Purchase') ?>','medical-main','Purchase :Pharmacy');" type="button" class="btn btn-secondary btn-sm ms-1">
                <i class="bi bi-list"></i> Purchase List
            </button>
        </div>
    </div>
    <div class="card-body pt-3">
        <form id="purchase-master-form" class="form1 row g-2" method="post" action="javascript:void(0)">
            <?= csrf_field() ?>
            <input type="hidden" id="hid_purchaseid" name="hid_purchaseid" value="<?= (int) ($inv->id ?? 0) ?>">

            <?php if ($invStatus === 0): ?>
                <div class="col-md-4">
                    <label class="form-label">Supplier</label>
                    <select class="form-select form-select-sm" id="input_supplier" name="input_supplier">
                        <?php foreach (($supplier_data ?? []) as $row): ?>
                            <option value="<?= (int) ($row->sid ?? 0) ?>" <?= ((int) ($row->sid ?? 0) === (int) ($inv->sid ?? 0)) ? 'selected' : '' ?>><?= esc($row->name_supplier ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Bill Type</label>
                    <select class="form-select form-select-sm" name="cbo_billtype" id="cbo_billtype">
                        <option value="0" <?= ((int) ($inv->ischallan ?? 0) === 0) ? 'selected' : '' ?>>Invoice</option>
                        <option value="1" <?= ((int) ($inv->ischallan ?? 0) === 1) ? 'selected' : '' ?>>Challan</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Invoice ID</label>
                    <input class="form-control form-control-sm" name="input_invoicecode" type="text" value="<?= esc((string) ($inv->Invoice_no ?? '')) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date of Invoice</label>
                    <input class="form-control form-control-sm" name="datepicker_invoice" id="datepicker_invoice" type="text" value="<?= $invDate !== '' ? esc($invDate) : date('d/m/Y') ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-primary btn-sm" id="btn_update" accesskey="U"><u>U</u>pdate</button>
                </div>
            <?php else: ?>
                <div class="col-md-4">
                    <label class="form-label">Supplier</label>
                    <div class="form-control form-control-sm bg-light"><?= esc($inv->name_supplier ?? '-') ?></div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Invoice ID</label>
                    <div class="form-control form-control-sm bg-light"><?= esc((string) ($inv->Invoice_no ?? '')) ?></div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date of Invoice</label>
                    <div class="form-control form-control-sm bg-light"><?= esc($invDate) ?></div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <span class="badge bg-success py-2 w-100"><i class="bi bi-lock-fill"></i> Locked (Final)</span>
                </div>
            <?php endif; ?>
        </form>

        <div class="table-responsive mt-3">
            <table class="table table-striped table-sm align-middle">
                <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Item Name</th>
                    <th>Batch No</th>
                    <th>Exp.</th>
                    <th>MRP</th>
                    <th>Qty.</th>
                    <th>Rate</th>
                    <th>Amount</th>
                    <th>Disc.</th>
                    <th>Tax Amount</th>
                    <th>CGST</th>
                    <th>SGST</th>
                    <th>Net Amount</th>
                </tr>
                </thead>
                <tbody>
                <?php $sr = 0; foreach (($purchase_item ?? []) as $row): $sr++; ?>
                    <?php
                    $expStr = (string) ($row->expiry_date ?? '');
                    if (!empty($row->exp_date_str)) {
                        $expStr = (string) $row->exp_date_str;
                    } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $expStr)) {
                        $expStr = date('m/y', strtotime($expStr));
                    }
                    ?>
                    <tr <?= ((int) ($row->item_return ?? 0) === 1) ? 'style="color:red;"' : '' ?>>
                        <td><?= $sr ?></td>
                        <td><?= esc($row->Item_name ?? '') ?></td>
                        <td><?= esc($row->batch_no ?? '') ?></td>
                        <td><?= esc($expStr) ?></td>
                        <td><?= number_format((float) ($row->mrp ?? 0), 2) ?></td>
                        <td><?= esc((string) ($row->qty ?? 0)) ?> + <?= esc((string) ($row->qty_free ?? 0)) ?></td>
                        <td><?= number_format((float) ($row->purchase_price ?? 0), 2) ?></td>
                        <td><?= number_format((float) ($row->amount ?? 0), 2) ?></td>
                        <td><?= number_format((float) ($row->discount ?? 0), 2) ?></td>
                        <td><?= number_format((float) ($row->taxable_amount ?? 0), 2) ?></td>
                        <td><?= esc((string) ($row->CGST_per ?? 0)) ?>%</td>
                        <td><?= esc((string) ($row->SGST_per ?? 0)) ?>%</td>
                        <td class="fw-bold"><?= number_format((float) ($row->net_amount ?? 0), 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="table-secondary fw-bold">
                    <td colspan="9" class="text-end">Total</td>
                    <td><?= number_format((float) ($inv->Taxable_Amt ?? 0), 2) ?></td>
                    <td><?= number_format((float) ($inv->CGST_Amt ?? 0), 2) ?></td>
                    <td><?= number_format((float) ($inv->SGST_Amt ?? 0), 2) ?></td>
                    <td><?= number_format((float) ($inv->T_Net_Amount ?? 0), 2) ?></td>
                </tr>
                </tbody>
            </table>
        </div>

        <form id="purchase-status-form" class="form2 row g-2 mt-2 align-items-center" method="post" action="javascript:void(0)">
            <div class="col-auto">
                <button onclick="var t = document.getElementById('searchresult') ? 'searchresult' : 'medical-main'; load_form_div('<?= base_url('Medical/PurchaseInvoiceEdit/' . (int) ($inv->id ?? 0)) ?>', t, 'Purchase Invoice Edit');" type="button" class="btn btn-warning btn-sm">
                    <i class="bi bi-pencil"></i> <?= $invStatus === 0 ? 'Edit Items' : 'View Invoice Items' ?>
                </button>
            </div>
            <?php if ($canUpdatePurchaseStatus): ?>
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <select class="form-select" id="cbo_invoice_status" name="cbo_invoice_status">
                            <option value="0" <?= $invStatus === 0 ? 'selected' : '' ?>>Pending Entry</option>
                            <option value="1" <?= $invStatus === 1 ? 'selected' : '' ?>>Final and Checked</option>
                        </select>
                        <button type="button" class="btn btn-primary" onclick="update_invoice_status()">Update Status</button>
                    </div>
                </div>
            <?php else: ?>
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">Status</span>
                        <div class="form-control form-control-sm"><?= $invStatus === 1 ? 'Final and Checked' : 'Pending Entry' ?></div>
                    </div>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
function update_invoice_status() {
    var invoiceStatus = $('#cbo_invoice_status').val();
    if (confirm('Are you sure to update Status?')) {
        var t = document.getElementById('searchresult') ? 'searchresult' : 'medical-main';
        load_form_div('<?= base_url('Medical/UpdatePurchaseInvoiceStatus/' . (int) ($inv->id ?? 0)) ?>/' + invoiceStatus, t, 'Purchase Master Edit');
    }
}

$(document).ready(function () {
    $('#btn_update').off('click').on('click', function () {
        $.post('<?= base_url('Medical/UpdatePurchase') ?>', $('#purchase-master-form').serialize(), function (data) {
            if (!data || (data.insertid || 0) === 0) {
                notify('error', 'Please Attention', (data && data.show_text) ? data.show_text : 'Unable to update');
                return;
            }
            notify('success', 'Update Success', data.show_text || 'Data Update');
            var t = document.getElementById('searchresult') ? 'searchresult' : 'medical-main';
            load_form_div('<?= base_url('Medical/PurchaseMasterEdit/' . (int) ($inv->id ?? 0)) ?>', t, 'Purchase Master Edit');
        }, 'json');
    });
});
</script>
