<?php $invoice = $inv_master_data[0] ?? null; ?>

<?php if (! $invoice): ?>
    <div class="alert alert-warning mb-0">Purchase invoice not found.</div>
<?php else: ?>
<div class="box">
    <div class="box-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h3 class="box-title mb-0">Purchase Invoice / Supplier : <?= esc((string) ($invoice->name_supplier ?? '-')) ?></h3>
            <small class="text-muted">
                Invoice No.: <?= esc((string) ($invoice->Invoice_no ?? '')) ?> |
                Invoice Date: <?= esc((string) ($invoice->str_date_of_invoice ?? '')) ?>
            </small>
        </div>
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-secondary" onclick="closeStorestockPurchaseSubView();">Back to List</button>
            <button type="button" class="btn btn-warning" onclick="openStorestockPurchaseSubView('<?= base_url('Storestock/PurchaseMasterEdit/' . (int) ($invoice->id ?? 0)) ?>','Purchase : Edit');">Reload Invoice</button>
            <a href="<?= base_url('Storestock/print_purchase/' . (int) ($invoice->id ?? 0)) ?>" target="_blank" class="btn btn-secondary"><i class="fa fa-print"></i> Print</a>
        </div>
    </div>
    <div class="box-body">
        <form id="storestock-purchase-edit-form" class="row g-3" method="post" action="javascript:void(0)">
            <?= csrf_field() ?>
            <input type="hidden" id="hid_purchaseid" name="hid_purchaseid" value="<?= (int) ($invoice->id ?? 0) ?>">
            <div class="col-md-4">
                <label class="form-label">Supplier</label>
                <select class="form-select" id="input_supplier" name="input_supplier" <?= ((int) ($invoice->inv_status ?? 0)) === 0 ? '' : 'disabled' ?>>
                    <?php foreach (($supplier_data ?? []) as $row): ?>
                        <option value="<?= (int) ($row->sid ?? 0) ?>" <?= ((int) ($row->sid ?? 0)) === (int) ($invoice->sid ?? 0) ? 'selected' : '' ?>><?= esc($row->name_supplier ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Bill Type</label>
                <select class="form-select" name="cbo_billtype" id="cbo_billtype" <?= ((int) ($invoice->inv_status ?? 0)) === 0 ? '' : 'disabled' ?>>
                    <option value="0" <?= ((int) ($invoice->ischallan ?? 0)) === 0 ? 'selected' : '' ?>>Invoice</option>
                    <option value="1" <?= ((int) ($invoice->ischallan ?? 0)) === 1 ? 'selected' : '' ?>>Challan</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Invoice No.</label>
                <input class="form-control" name="input_invoicecode" type="text" value="<?= esc((string) ($invoice->Invoice_no ?? '')) ?>" <?= ((int) ($invoice->inv_status ?? 0)) === 0 ? '' : 'readonly' ?>>
            </div>
            <div class="col-md-2">
                <label class="form-label">Date of Invoice</label>
                <input class="form-control" name="datepicker_invoice" type="text" value="<?= esc((string) ($invoice->str_date_of_invoice ?? '')) ?>" <?= ((int) ($invoice->inv_status ?? 0)) === 0 ? '' : 'readonly' ?>>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="button" class="btn btn-primary" id="btn_update_purchase" <?= ((int) ($invoice->inv_status ?? 0)) === 0 ? '' : 'disabled' ?>>Update</button>
                <button type="button" class="btn btn-outline-secondary" id="btn_toggle_status">Toggle Status</button>
            </div>
        </form>

        <div class="table-responsive mt-4">
            <table class="table table-bordered table-striped table-sm align-middle">
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
                <?php if (empty($purchase_item)): ?>
                    <tr><td colspan="13" class="text-center text-muted">No items added yet.</td></tr>
                <?php endif; ?>
                <?php $srno = 0; foreach (($purchase_item ?? []) as $row): $srno++; ?>
                    <tr>
                        <td><?= $srno ?></td>
                        <td><?= esc((string) ($row->Item_name ?? $row->item_name ?? '')) ?></td>
                        <td><?= esc((string) ($row->batch_no ?? '-')) ?></td>
                        <td><?= esc((string) ($row->expiry_date ?? '-')) ?></td>
                        <td><?= esc((string) ($row->mrp ?? '0')) ?></td>
                        <td><?= esc((string) ((float) ($row->qty ?? 0))) ?><?= ((float) ($row->qty_free ?? 0)) > 0 ? ' + ' . esc((string) ((float) ($row->qty_free ?? 0))) : '' ?></td>
                        <td><?= esc((string) ($row->purchase_price ?? '0')) ?></td>
                        <td><?= esc((string) ($row->amount ?? '0')) ?></td>
                        <td><?= esc((string) ($row->discount ?? $row->disc_amount ?? '0')) ?></td>
                        <td><?= esc((string) ($row->taxable_amount ?? '0')) ?></td>
                        <td><?= esc((string) ($row->CGST_per ?? '0')) ?></td>
                        <td><?= esc((string) ($row->SGST_per ?? '0')) ?></td>
                        <td><?= esc((string) ($row->net_amount ?? '0')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                <tr>
                    <th colspan="9" class="text-end">Totals</th>
                    <th><?= esc((string) ($invoice->Taxable_Amt ?? '0')) ?></th>
                    <th><?= esc((string) ($invoice->CGST_Amt ?? '0')) ?></th>
                    <th><?= esc((string) ($invoice->SGST_Amt ?? '0')) ?></th>
                    <th><?= esc((string) ($invoice->T_Net_Amount ?? '0')) ?></th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script>
(function () {
    $('#btn_update_purchase').off('click').on('click', function () {
        $.post('<?= base_url('Storestock/UpdatePurchase') ?>', $('#storestock-purchase-edit-form').serialize(), function (data) {
            if (!data || typeof data !== 'object') {
                notify('error', 'Please Attention', 'Unexpected response');
                return;
            }

            if ((data.insertid || 0) === 0) {
                notify('error', 'Please Attention', data.show_text || 'Please check input');
                return;
            }

            notify('success', 'Update Success', data.show_text || 'Data Update');
            openStorestockPurchaseSubView('<?= base_url('Storestock/PurchaseMasterEdit/' . (int) ($invoice->id ?? 0)) ?>', 'Purchase : Edit');
        }, 'json').fail(function () {
            notify('error', 'Please Attention', 'Unable to update invoice');
        });
    });

    $('#btn_toggle_status').off('click').on('click', function () {
        var nextStatus = <?= ((int) ($invoice->inv_status ?? 0)) === 0 ? 1 : 0 ?>;
        openStorestockPurchaseSubView('<?= base_url('Storestock/UpdatePurchaseInvoiceStatus/' . (int) ($invoice->id ?? 0)) ?>/' + nextStatus, 'Purchase : Edit');
    });
})();
</script>
<?php endif; ?>