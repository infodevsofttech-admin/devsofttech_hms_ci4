<?= $this->include('storestock/_niceadmin_theme') ?>
<div class="storestock-ui">
<section class="content-header">
    <h1>
        Indent No. <?= esc($invoice_stock_master[0]->indent_code ?? '') ?>
        <small>Edit</small>
    </h1>
</section>
<section class="content">
    <?= csrf_field() ?>
    <div class="col-md-8" style="padding: 5px;">
        <div class="box box-danger">
            <div class="box-header">
                <div class="row">
                    <input type="hidden" id="location_type" name="location_type" value="<?= $invoice_stock_master[0]->location_type ?? 0 ?>" />
                    <input type="hidden" id="location_id"   name="location_id"   value="<?= $invoice_stock_master[0]->location_id ?? 0 ?>" />
                    <input type="hidden" id="indent_id"     name="indent_id"     value="<?= $invoice_stock_master[0]->id ?? 0 ?>" />
                    <?php if (($invoice_stock_master[0]->location_id ?? 0) > 0) { ?>
                    <div class="col-md-12">
                        <p>
                            <strong>Location Name :</strong> <?= esc($invoice_stock_master[0]->issued_name) ?>
                            <strong>/ Indent No. :</strong> <?= esc($invoice_stock_master[0]->indent_code) ?>
                            <strong>/ Date :</strong> <?= date('d-m-Y', strtotime($invoice_stock_master[0]->indent_date)) ?>
                        </p>
                    </div>
                    <?php } ?>
                </div>
            </div>
            <div class="box-body">
                <div class="col-md-12">
                    <!-- Item List (AJAX-refreshable) -->
                    <div class="row">
                        <div id="show_item_list">
                            <?= $content ?>
                        </div>
                    </div>
                    <hr />
                    <div class="row">
                        <div id="show_add_item_list"></div>
                    </div>

                    <!-- Add Item Form -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="input_drug">Product Search:</label>
                                <input class="form-control input-sm" name="input_drug" id="input_drug"
                                       placeholder="Item Code / Item Name" type="text" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted" id="input_product_code"></span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <input type="hidden" id="l_ssno"    name="l_ssno"    value="0" />
                            <input type="hidden" id="item_code" name="item_code" value="0" />
                            <p class="text-red">
                                <span class="text-green"      id="input_product_name"></span>
                                <span class="text-light-blue" id="input_batch"></span>
                                <span class="text-lead"       id="input_product_mrp"></span>
                                <span class="text-yellow"     id="stock_product_qty"></span>
                            </p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Unit Rate</label>
                                <input class="form-control input-sm" id="input_product_unit_rate"
                                       name="input_product_unit_rate" placeholder="Unit Rate" autocomplete="off" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Qty</label>
                                <input class="form-control number input-sm" id="input_product_qty"
                                       name="input_product_qty" placeholder="Qty (Tabs/Units)" type="text"
                                       value="0" autocomplete="off" />
                                <input type="hidden" id="hid_c_qty" name="hid_c_qty" value="0" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Disc %</label>
                                <input class="form-control number input-sm" id="input_disc"
                                       name="input_disc" placeholder="Discount %" type="text"
                                       value="0" autocomplete="off" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <button type="button" class="btn btn-primary" onclick="add_item_invoice()">Add</button>
                            </div>
                        </div>
                    </div>
                    <!-- /Add Item Form -->
                </div>
            </div>
            <div class="box-footer">
                <div class="row">
                    <div class="col-md-2">
                        <button type="button" class="btn btn-success" id="finalinvoice">Final Invoice</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4" style="padding: 5px;">
        <div class="box box-info">
            <div class="box-header with-border">
                <h4 class="box-title">Search Medicine</h4>
            </div>
            <div class="box-body" style="overflow:scroll;" id="search_body_part"></div>
            <div class="box-footer" id="search_footer_part"></div>
        </div>
    </div>
</section>

<script>
$(document).ready(function () {

    document.title = 'Indent : <?= esc($invoice_stock_master[0]->issued_name ?? '') ?> / <?= esc($invoice_stock_master[0]->indent_code ?? '') ?>';

    // Final invoice button
    $('#finalinvoice').click(function () {
        var inv_id = $('#indent_id').val();
        load_form_div('/Storestock/final_invoice/' + inv_id, 'maindiv');
    });

    // Autocomplete
    $("#input_drug").autocomplete({
        source: function (request, response) {
            $.getJSON('/Storestock/get_drug', { term: request.term }, response);
        },
        minLength: 2,
        select: function (event, ui) {
            $('#input_product_code').text(ui.item.l_item_code);
            $('#input_product_name').text(ui.item.value);
            $('#input_batch').text(' | Batch: ' + ui.item.l_Batch);
            $('#input_product_mrp').text(' | MRP: ' + ui.item.l_mrp);
            $('#input_product_unit_rate').val(ui.item.l_unit_rate);
            $('#stock_product_qty').text(' | Qty: ' + ui.item.l_c_qty);
            $('#hid_c_qty').val(ui.item.l_c_qty);
            $('#l_ssno').val(ui.item.l_ss_no);
            $('#item_code').val(ui.item.l_item_code);
        }
    });
});

function add_item_invoice() {
    var inv_id           = $('#indent_id').val();
    var l_ssno           = $('#l_ssno').val();
    var input_qty        = $('#input_product_qty').val();
    var hid_c_qty        = $('#hid_c_qty').val();
    var product_unit_rate = $('#input_product_unit_rate').val();
    var csrf_value       = $('input[name="<?= csrf_token() ?>"]').val();
    var wait             = parseInt($('#wait_for_next').val() || '0');

    if (wait > 0) {
        notify('error', 'Please Attention', 'Wait for last process');
        return false;
    }

    if (parseInt(l_ssno) < 1) {
        notify('error', 'Please Attention', 'Select Product First');
        $('#input_drug').focus();
        return false;
    }

    if (parseInt(input_qty) < 1) {
        notify('error', 'Please Attention', 'Qty. is 0');
        $('#input_product_qty').focus();
        return false;
    }

    if (parseInt(input_qty) > parseInt(hid_c_qty)) {
        notify('error', 'Please Attention', 'Product Qty. is less than available: ' + hid_c_qty);
        $('#input_product_qty').focus();
        return false;
    }

    $('#wait_for_next').val('1');
    setTimeout(function () { $('#wait_for_next').val('0'); }, 1000);

    $.post('/Storestock/add_item/1', {
        "l_ssno"            : l_ssno,
        "qty"               : input_qty,
        "product_unit_rate" : product_unit_rate,
        "disc"              : $('#input_disc').val(),
        "inv_id"            : inv_id,
        '<?= csrf_token() ?>' : csrf_value
    }, function (data) {
        // Reset form
        $('#input_product_code').text('');
        $('#input_product_name').text('');
        $('#input_batch').text('');
        $('#input_product_mrp').text('');
        $('#input_product_unit_rate').val('');
        $('#l_ssno').val(0);
        $('#input_drug').val('');
        $('#input_product_qty').val('0');
        $('#input_disc').val('0');
        $('#stock_product_qty').text('');
        $('#input_drug').focus();

        if (data.exist > 0) {
            notify('warning', 'Please Attention', 'This Item Already in List');
        }

        if (data.insertid > 0) {
            $('#show_item_list').html(data.content);
        } else {
            notify('error', 'Please Attention', data.error);
            $('#wait_for_next').val('0');
        }

        // Refresh CSRF
        $('input[name="<?= csrf_token() ?>"]').val(data.csrf_token_value);

    }, 'json');
}

function update_qty(item_id) {
    var new_qty   = $('#input_qty_' + item_id).val();
    var csrf_value = $('input[name="<?= csrf_token() ?>"]').val();

    $.post('/Storestock/add_item/2', {
        "itemid"            : item_id,
        "u_qty"             : new_qty,
        '<?= csrf_token() ?>' : csrf_value
    }, function (data) {
        if (data.insertid > 0) {
            $('#show_item_list').html(data.content);
        } else {
            notify('error', 'Please Attention', data.error);
        }
        $('input[name="<?= csrf_token() ?>"]').val(data.csrf_token_value);
    }, 'json');
}

function remove_item_invoice(item_id) {
    if (!confirm('Are you sure you want to remove this item?')) return false;
    var csrf_value = $('input[name="<?= csrf_token() ?>"]').val();

    $.post('/Storestock/add_item/3', {
        "itemid"            : item_id,
        '<?= csrf_token() ?>' : csrf_value
    }, function (data) {
        if (data.insertid > 0) {
            $('#show_item_list').html(data.content);
        } else {
            notify('error', 'Please Attention', data.error || 'Delete failed');
        }
        $('input[name="<?= csrf_token() ?>"]').val(data.csrf_token_value);
    }, 'json');
}
</script>
</div>
