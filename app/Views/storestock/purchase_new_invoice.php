<div class="box">
    <div class="box-header d-flex justify-content-between align-items-center">
        <h3 class="box-title mb-0">New Purchase Invoice</h3>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="closeStorestockPurchaseSubView();">Back to List</button>
    </div>
    <div class="box-body">
        <form id="storestock-purchase-new-form" class="row g-3" method="post" action="javascript:void(0)">
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
                <label class="form-label">Invoice / Challan No.</label>
                <input class="form-control" name="input_invoicecode" placeholder="Invoice No." type="text">
            </div>
            <div class="col-md-3">
                <label class="form-label">Date of Invoice</label>
                <input class="form-control" name="datepicker_invoice" id="datepicker_invoice" type="text" value="<?= date('d/m/Y') ?>">
            </div>
            <div class="col-12">
                <button type="button" class="btn btn-primary" id="btn_create_purchase">Create</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    $('#btn_create_purchase').off('click').on('click', function () {
        $.post('<?= base_url('Storestock/CreatePurchase') ?>', $('#storestock-purchase-new-form').serialize(), function (data) {
            if (!data || typeof data !== 'object') {
                notify('error', 'Please Attention', 'Unexpected response');
                return;
            }

            if ((data.insertid || 0) <= 0) {
                notify('error', 'Please Attention', data.show_text || 'Unable to create purchase invoice');
                return;
            }

            notify('success', 'Please Attention', 'Invoice Added : ID->' + data.insertid);
            openStorestockPurchaseSubView('<?= base_url('Storestock/PurchaseMasterEdit') ?>/' + data.insertid, 'Purchase : Edit');
        }, 'json').fail(function () {
            notify('error', 'Please Attention', 'Unable to create purchase invoice');
        });
    });
})();
</script>