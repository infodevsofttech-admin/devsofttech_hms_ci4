<div class="box">
    <div class="box-header d-flex justify-content-between align-items-center">
        <h3 class="box-title mb-0">New Purchase Return Invoice</h3>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="load_form_div('<?= base_url('Storestock/Purchase_return') ?>','searchresult','Purchase Return');">Back</button>
    </div>
    <div class="box-body pt-3">
        <form id="new-purchase-return-form" class="row g-2" method="post" action="javascript:void(0)">
            <?= csrf_field() ?>

            <div class="col-md-4">
                <label class="form-label">Supplier</label>
                <select class="form-select" id="input_supplier" name="input_supplier">
                    <?php foreach (($supplier_data ?? []) as $row): ?>
                        <option value="<?= (int) ($row->sid ?? 0) ?>"><?= esc($row->name_supplier ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Date of Invoice</label>
                <input class="form-control" name="datepicker_invoice" id="datepicker_invoice" type="text" value="<?= date('d/m/Y') ?>">
            </div>

            <div class="col-12">
                <button type="button" class="btn btn-primary" id="btn_create_purchase_return" accesskey="C">Create</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    $('#btn_create_purchase_return').off('click').on('click', function () {
        $.post('<?= base_url('Storestock/create_purchase_return') ?>', $('#new-purchase-return-form').serialize(), function (data) {
            if (!data || typeof data !== 'object') {
                notify('error', 'Please Attention', 'Unexpected response');
                return;
            }
            if ((data.insertid || 0) <= 0) {
                notify('error', 'Please Attention', data.show_text || 'Unable to create purchase return invoice');
                return;
            }
            notify('success', 'Please Attention', 'Return Invoice : ID->' + data.insertid);
            load_form_div('<?= base_url('Storestock/PurchaseReturnInvoiceEdit') ?>/' + data.insertid, 'searchresult', 'Purchase Return Invoice Edit');
        }, 'json').fail(function () {
            notify('error', 'Please Attention', 'Unable to create purchase return invoice');
        });
    });
})();
</script>