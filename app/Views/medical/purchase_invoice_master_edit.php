<?php
$inv = $inv_master_data[0] ?? null;
if (! $inv) {
    echo '<div class="alert alert-warning mb-0">Purchase invoice not found.</div>';
    return;
}

$invStatus = (int) ($inv->inv_status ?? 0);
$canUpdatePurchaseStatus = (bool) ($can_update_purchase_status ?? false);
?>

<div class="card">
    <div class="card-header">Purchase Master Edit</div>
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
                    <input class="form-control form-control-sm" name="datepicker_invoice" id="datepicker_invoice" type="text" value="<?= !empty($inv->date_of_invoice) ? date('d/m/Y', strtotime((string) $inv->date_of_invoice)) : date('d/m/Y') ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-primary btn-sm" id="btn_update" accesskey="U"><u>U</u>pdate</button>
                </div>
            <?php else: ?>
                <div class="col-md-4">
                    <label class="form-label">Supplier</label>
                    <div class="form-control form-control-sm"><?= esc($inv->name_supplier ?? '-') ?></div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Invoice ID</label>
                    <div class="form-control form-control-sm"><?= esc((string) ($inv->Invoice_no ?? '')) ?></div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date of Invoice</label>
                    <div class="form-control form-control-sm"><?= !empty($inv->date_of_invoice) ? esc(date('d/m/Y', strtotime((string) $inv->date_of_invoice))) : '' ?></div>
                </div>
            <?php endif; ?>
        </form>

        <div class="table-responsive mt-3">
            <table class="table table-striped table-sm align-middle">
                <thead>
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
                    <tr <?= ((int) ($row->item_return ?? 0) === 1) ? 'style="color:red;"' : '' ?>>
                        <td><?= $sr ?></td>
                        <td><?= esc($row->Item_name ?? '') ?></td>
                        <td><?= esc($row->batch_no ?? '') ?></td>
                        <td><?= esc((string) ($row->expiry_date ?? '')) ?></td>
                        <td><?= esc((string) ($row->mrp ?? 0)) ?></td>
                        <td><?= esc((string) ($row->qty ?? 0)) ?>+<?= esc((string) ($row->qty_free ?? 0)) ?></td>
                        <td><?= esc((string) ($row->purchase_price ?? 0)) ?></td>
                        <td><?= esc((string) ($row->amount ?? 0)) ?></td>
                        <td><?= esc((string) ($row->discount ?? 0)) ?></td>
                        <td><?= esc((string) ($row->taxable_amount ?? 0)) ?></td>
                        <td><?= esc((string) ($row->CGST_per ?? 0)) ?></td>
                        <td><?= esc((string) ($row->SGST_per ?? 0)) ?></td>
                        <td><?= esc((string) ($row->net_amount ?? 0)) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <th colspan="9" class="text-end">Total</th>
                    <th><?= esc((string) ($inv->Taxable_Amt ?? 0)) ?></th>
                    <th><?= esc((string) ($inv->CGST_Amt ?? 0)) ?></th>
                    <th><?= esc((string) ($inv->SGST_Amt ?? 0)) ?></th>
                    <th><?= esc((string) ($inv->T_Net_Amount ?? 0)) ?></th>
                </tr>
                </tbody>
            </table>
        </div>

        <form id="purchase-status-form" class="form2 row g-2" method="post" action="javascript:void(0)">
            <?php if ($invStatus === 0): ?>
                <div class="col-md-2">
                    <button onclick="load_form_div('<?= base_url('Medical/PurchaseInvoiceEdit/' . (int) ($inv->id ?? 0)) ?>','searchresult','Purchase Invoice Edit');" type="button" class="btn btn-warning btn-sm">Edit Items</button>
                </div>
            <?php endif; ?>
            <?php if ($canUpdatePurchaseStatus): ?>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" id="cbo_invoice_status" name="cbo_invoice_status">
                        <option value="0" <?= $invStatus === 0 ? 'selected' : '' ?>>Pending Entry</option>
                        <option value="1" <?= $invStatus === 1 ? 'selected' : '' ?>>Final and Checked</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary btn-sm" onclick="update_invoice_status()">Update Status</button>
                </div>
            <?php else: ?>
                <div class="col-md-4">
                    <label class="form-label">Invoice Status</label>
                    <div class="form-control form-control-sm"><?= $invStatus === 1 ? 'Final and Checked' : 'Pending Entry' ?></div>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
function update_invoice_status() {
    var invoiceStatus = $('#cbo_invoice_status').val();
    if (confirm('Are you sure to update Status?')) {
        load_form_div('<?= base_url('Medical/UpdatePurchaseInvoiceStatus/' . (int) ($inv->id ?? 0)) ?>/' + invoiceStatus, 'searchresult', 'Purchase Master Edit');
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
            load_form_div('<?= base_url('Medical/PurchaseMasterEdit/' . (int) ($inv->id ?? 0)) ?>', 'searchresult', 'Purchase Master Edit');
        }, 'json');
    });
});
</script>
