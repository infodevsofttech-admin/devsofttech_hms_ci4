<div class="row g-2">
    <div class="col-md-8">
        <label class="form-label" for="input_drug">Product Search</label>
        <input class="form-control form-control-sm" name="input_drug" id="input_drug" placeholder="Like Item Code, Item Name" type="text">
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <span class="text-muted" id="input_product_code"></span>
    </div>
</div>

<div class="row mt-2">
    <div class="col-12">
        <input type="hidden" id="l_ssno" name="l_ssno" value="0">
        <input type="hidden" id="purchase_id" name="purchase_id" value="0">
        <p class="text-danger mb-1">
            <span class="text-success" id="input_product_name"></span>
            <span class="text-info" id="input_batch"></span>
            <span class="text-primary" id="input_product_mrp"></span>
            <span class="text-warning" id="stock_product_qty"></span>
        </p>
    </div>
</div>

<div class="row g-2">
    <div class="col-md-3">
        <label class="form-label">Unit Rate</label>
        <input class="form-control form-control-sm" name="input_product_unit_rate" id="input_product_unit_rate" placeholder="Unit Rate" autocomplete="off">
    </div>
    <div class="col-md-3">
        <label class="form-label">Qty</label>
        <input class="form-control form-control-sm" name="input_product_qty" id="input_product_qty" placeholder="Qty" type="text" value="0" autocomplete="off">
        <input type="hidden" id="hid_c_qty" name="hid_c_qty" value="0">
    </div>
    <div class="col-md-3">
        <label class="form-label">Batch No.</label>
        <input class="form-control form-control-sm" name="input_batch_no" id="input_batch_no" placeholder="000000" type="text">
    </div>
    <div class="col-md-3">
        <label class="form-label">Exp.Date</label>
        <input class="form-control form-control-sm" name="input_expiry_dt" id="input_expiry_dt" type="date" autocomplete="off">
    </div>
    <div class="col-md-3">
        <button type="button" class="btn btn-primary" onclick="remove_custom_item()">Add</button>
    </div>
</div>

<script>
(function () {
    $('#input_drug').autocomplete({
        source: function (request, response) {
            $.getJSON('<?= base_url('Medical_backpanel/get_drug') ?>', request, function (data) {
                response(data || []);
            });
        },
        minLength: 1,
        autofocus: true,
        select: function (event, ui) {
            $('#input_product_code').html('| Product Code: ' + (ui.item.l_item_code || ''));
            $('#input_product_name').html('Name: ' + (ui.item.value || ''));
            $('#l_ssno').val(ui.item.l_ss_no || 0);
            $('#input_batch').html(' | Batch No.: ' + (ui.item.l_Batch || '') + ' | Exp.Dt: ' + (ui.item.l_Expiry || ''));
            $('#input_product_mrp').html(' | MRP: ' + (ui.item.l_mrp || '') + ' | Unit Rate: ' + (ui.item.l_unit_rate || ''));
            $('#input_product_unit_rate').val(ui.item.l_unit_rate || '');
            $('#purchase_id').val(ui.item.l_purchase_id || '');
            $('#hid_c_qty').val(ui.item.l_c_qty || 0);
            $('#input_product_qty').val(ui.item.l_c_qty || 0);
            $('#input_batch_no').val(ui.item.l_Batch || '');
            $('#input_expiry_dt').val(ui.item.l_Expiry || '');
            $('#stock_product_qty').html(' | Qty: ' + (ui.item.l_c_qty || 0) + ' | Pak: ' + (ui.item.l_packing || ''));
        }
    });

    $('#input_batch_no').autocomplete({
        source: function (request, response) {
            var itemCode = $('#l_ssno').val() || 0;
            if (parseInt(itemCode, 10) <= 0) {
                response([]);
                return;
            }
            $.getJSON('<?= base_url('Medical_backpanel/get_batch') ?>/' + itemCode, request, function (data) {
                response(data || []);
            });
        },
        minLength: 1,
        autofocus: true
    });
})();
</script>
