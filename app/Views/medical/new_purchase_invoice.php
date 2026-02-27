<div class="card">
    <div class="card-header">New Purchase Invoice</div>
    <div class="card-body pt-3">
        <form id="new-purchase-form" class="row g-2" method="post" action="javascript:void(0)">
            <?= csrf_field() ?>

            <div class="col-md-4">
                <label class="form-label">Supplier</label>
                <select class="form-select" id="input_supplier" name="input_supplier">
                    <?php foreach (($supplier_data ?? []) as $row): ?>
                        <option value="<?= (int) ($row->sid ?? 0) ?>"><?= esc($row->name_supplier ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Bill Type</label>
                <select class="form-select" name="cbo_billtype" id="cbo_billtype">
                    <option value="0">Invoice</option>
                    <option value="1">Challan</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Invoice / Challan ID</label>
                <input class="form-control" name="input_invoicecode" id="input_invoicecode" placeholder="Invoice No." type="text">
            </div>

            <div class="col-md-3">
                <label class="form-label">Date of Invoice</label>
                <input class="form-control" name="datepicker_invoice" id="datepicker_invoice" type="text" value="<?= date('d/m/Y') ?>">
            </div>

            <div class="col-12">
                <button type="button" class="btn btn-primary" id="btn_create_purchase" accesskey="C">Create</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    $('#btn_create_purchase').off('click').on('click', function () {
        $.post('<?= base_url('Medical/CreatePurchase') ?>', $('#new-purchase-form').serialize(), function (data) {
            if (!data || typeof data !== 'object') {
                notify('error', 'Please Attention', 'Unexpected response');
                return;
            }
            if ((data.insertid || 0) <= 0) {
                notify('error', 'Please Attention', data.show_text || 'Unable to create invoice');
                return;
            }
            notify('success', 'Please Attention', 'Invoice Added : ID->' + data.insertid);
            load_form_div('<?= base_url('Medical/PurchaseInvoiceEdit') ?>/' + data.insertid, 'searchresult', 'Purchase Invoice Edit');
        }, 'json').fail(function () {
            notify('error', 'Please Attention', 'Unable to create purchase invoice');
        });
    });
})();
</script>
