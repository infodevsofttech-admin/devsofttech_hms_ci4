<?php $invoice = $purchase_invoice[0] ?? null; ?>
<?php $gstRates = $med_gst_per ?? []; ?>

<?php if (! $invoice): ?>
    <div class="alert alert-warning mb-0">Purchase invoice not found.</div>
<?php else: ?>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Purchase Invoice Information</h5>
        <small>
            <i>Supplier</i>: <?= esc($invoice->name_supplier ?? '-') ?> |
            <?= ((int) ($invoice->ischallan ?? 0) === 1) ? '<i>Challan No.</i>' : '<i>Invoice No.</i>' ?>: <?= esc((string) ($invoice->Invoice_no ?? '')) ?> |
            <i>Invoice Date</i>: <?= esc((string) ($invoice->str_date_of_invoice ?? '')) ?>
            <button onclick="load_form_div('<?= base_url('Medical/PurchaseMasterEdit/' . (int) ($invoice->id ?? 0)) ?>','searchresult','Purchase Master Edit');" type="button" class="btn btn-warning btn-sm ms-2">Edit Invoice</button>
            <button onclick="load_form_div('<?= base_url('Medical/Purchase') ?>','medical-main','Purchase :Pharmacy');" type="button" class="btn btn-secondary btn-sm ms-1">Back to Purchase</button>
        </small>
    </div>
    <div class="card-body">
        <form id="purchase-form" class="form1" method="post" action="javascript:void(0)">
            <?= csrf_field() ?>
            <input type="hidden" id="invoice_id" name="invoice_id" value="<?= (int) ($invoice->id ?? 0) ?>">

            <div id="invoice_item_list" class="mb-3"></div>

            <?php if ((int) ($invoice->inv_status ?? 0) === 0): ?>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Product Search</label>
                        <input class="form-control form-control-sm" name="input_drug" id="input_drug" placeholder="Like Item Code , Item Name" type="text">
                        <input name="input_drug_hid" id="input_drug_hid" type="hidden">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Product Code</label>
                        <input class="form-control form-control-sm" name="input_product_code" id="input_product_code" placeholder="Product Code" type="text" readonly>
                    </div>
                </div>

                <div class="row mt-1">
                    <div class="col-lg-8">
                <div id="update_purchase_items" class="row g-2">
                    <div class="col-md-2">
                        <label class="form-label">Packaging</label>
                        <input class="form-control form-control-sm" name="input_package" id="input_package" type="text">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Batch No.</label>
                        <input class="form-control form-control-sm" name="input_batch_code" id="input_batch_code" type="text">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Exp. Month</label>
                        <select id="datepicker_doe_month" name="datepicker_doe_month" class="form-select form-select-sm">
                            <?php for ($m = 1; $m <= 12; $m++): $mv = str_pad((string) $m, 2, '0', STR_PAD_LEFT); ?>
                                <option value="<?= $mv ?>"><?= $m ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Exp. Year</label>
                        <select id="datepicker_doe_year" name="datepicker_doe_year" class="form-select form-select-sm">
                            <?php $yy = (int) date('y'); for ($i = 0; $i < 10; $i++): ?>
                                <option value="<?= $yy + $i ?>"><?= $yy + $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">MRP</label>
                        <input class="form-control form-control-sm" name="input_product_mrp" id="input_product_mrp" type="text" onchange="update_selling_rate()">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Qty</label>
                        <input class="form-control form-control-sm" name="input_Qty" id="input_Qty" type="text" value="0" onchange="calculate()">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Free Qty</label>
                        <input class="form-control form-control-sm" name="input_Qty_Free" id="input_Qty_Free" type="text" value="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Purchase Rate</label>
                        <input class="form-control form-control-sm" name="input_purchase_price" id="input_purchase_price" type="text" onchange="calculate()">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Amount</label>
                        <input class="form-control form-control-sm" name="amount_price" id="amount_price" type="text" readonly>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Discount %</label>
                        <input class="form-control form-control-sm" name="input_disc_price" id="input_disc_price" type="text" onchange="calculate()">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">SCH Amount</label>
                        <input class="form-control form-control-sm" name="input_sch_amount" id="input_sch_amount" type="text" onchange="calculate()">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">SCH Discount %</label>
                        <input class="form-control form-control-sm" name="input_sch_disc" id="input_sch_disc" type="text" onchange="calculate()">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Taxable Amount</label>
                        <input class="form-control form-control-sm" name="Tamount_price" id="Tamount_price" type="text" readonly>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">CGST</label>
                        <select class="form-select form-select-sm" name="input_CGST" id="input_CGST" onchange="calculate()">
                            <?php if (! empty($gstRates)): ?>
                                <?php foreach ($gstRates as $rate): ?>
                                    <?php $gstValue = (string) ($rate->gst_per ?? '0'); ?>
                                    <option value="<?= esc($gstValue) ?>" <?= ((float) $gstValue === 2.5) ? 'selected' : '' ?>><?= esc($gstValue) ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="0">0</option>
                                <option value="2.5" selected>2.5</option>
                                <option value="5">5</option>
                                <option value="6">6</option>
                                <option value="9">9</option>
                                <option value="12">12</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">SGST / IGST</label>
                        <select class="form-select form-select-sm" name="input_SGST" id="input_SGST" onchange="calculate()">
                            <?php if (! empty($gstRates)): ?>
                                <?php foreach ($gstRates as $rate): ?>
                                    <?php $gstValue = (string) ($rate->gst_per ?? '0'); ?>
                                    <option value="<?= esc($gstValue) ?>" <?= ((float) $gstValue === 2.5) ? 'selected' : '' ?>><?= esc($gstValue) ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="0">0</option>
                                <option value="2.5" selected>2.5</option>
                                <option value="5">5</option>
                                <option value="6">6</option>
                                <option value="9">9</option>
                                <option value="12">12</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Net Amount</label>
                        <input class="form-control form-control-sm" name="Net_amount" id="Net_amount" type="text" readonly>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Selling Price</label>
                        <input class="form-control form-control-sm" name="input_selling_price" id="input_selling_price" type="text">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">HSNCODE</label>
                        <input class="form-control form-control-sm" name="input_HSNCODE" id="input_HSNCODE" type="text" value="3004">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Storage</label>
                        <input class="form-control form-control-sm" name="input_storage" id="input_storage" type="text">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Shelf No.</label>
                        <input class="form-control form-control-sm" name="input_shelf_no" id="input_shelf_no" type="text">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Rack No.</label>
                        <input class="form-control form-control-sm" name="input_rack_no" id="input_rack_no" type="text">
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <input type="hidden" id="invoice_item_id" name="invoice_item_id" value="0">
                        <button type="button" class="btn btn-primary" id="btn_update_stock" accesskey="A"><u>A</u>dd Stock</button>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-danger" id="btn_update_stock_return" accesskey="R"><u>R</u>eturn Stock</button>
                    </div>
                </div>
                    </div>
                    <div class="col-lg-4">
                        <div id="purchase_challan_history" class="mb-3"></div>
                        <div id="purchase_items_old_history" class="text-muted small">Select a product to view last 5 purchase orders.</div>
                    </div>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
function calculate() {
    var pp = parseFloat($('#input_purchase_price').val() || 0);
    var qty = parseFloat($('#input_Qty').val() || 0);
    var disc = parseFloat($('#input_disc_price').val() || 0);
    var schamt = parseFloat($('#input_sch_amount').val() || 0);
    var schdisc = parseFloat($('#input_sch_disc').val() || 0);

    if (schdisc > 0) {
        pp = pp - (pp * schdisc / 100);
    }

    var amount = pp * qty;
    if (schamt > 0) {
        amount = amount - schamt;
    }

    var taxamount = (amount - (amount * disc / 100));
    var cgst = parseFloat($('#input_CGST').val() || 0);
    var sgst = parseFloat($('#input_SGST').val() || 0);
    var cgst_amt = taxamount * cgst / 100;
    var sgst_amt = taxamount * sgst / 100;
    var net_amount = taxamount + cgst_amt + sgst_amt;

    $('#amount_price').val(amount.toFixed(2));
    $('#Tamount_price').val(taxamount.toFixed(2));
    $('#Net_amount').val(net_amount.toFixed(2));

    if ($('#input_product_mrp').val() !== '') {
        $('#input_selling_price').val($('#input_product_mrp').val());
    }
}

function update_selling_rate() {
    var product_mrp = parseFloat($('#input_product_mrp').val() || 0);
    $('#input_selling_price').val(product_mrp.toFixed(2));
}

function toggle_update_purchase_items(flag) {
    if (flag === true) {
        $('#update_purchase_items').show();
        reset_input();
    } else {
        $('#update_purchase_items').hide();
        reset_input();
    }
}

function focusProductSearch() {
    setTimeout(function () {
        $('#input_drug').focus();
        var el = document.getElementById('input_drug');
        if (el) {
            el.scrollIntoView({block: 'center'});
        }
    }, 80);
}

function resetItemHistoryPanel() {
    $('#purchase_items_old_history').html('<div class="text-muted small">Select a product to view last 5 purchase orders.</div>');
}

function reset_input() {
    $('#input_product_code').val('');
    $('#input_product_mrp').val('');
    $('#input_selling_price').val('');
    $('#input_batch_code').val('');
    $('#amount_price').val('');
    $('#Tamount_price').val('');
    $('#Net_amount').val('');
    $('#input_disc_price').val('');
    $('#input_sch_amount').val('');
    $('#input_sch_disc').val('');
    $('#input_Qty').val('');
    $('#input_Qty_Free').val('');
    $('#input_package').val('');
    $('#input_purchase_price').val('');
    $('#input_drug').val('');
    $('#input_drug_hid').val('');
    $('#input_storage').val('');
    $('#input_shelf_no').val('');
    $('#input_rack_no').val('');
    $('#invoice_item_id').val('0');
}

function remove_item_invoice(inv_item_id) {
    $.post('<?= base_url('Medical/purchase_invoice_item_delete/' . (int) ($invoice->id ?? 0)) ?>/' + inv_item_id, {}, function (data) {
        if ((data.is_update_stock || 0) === 0) {
            notify('error', 'Please Attention', data.show_text || 'Unable to remove item');
            return;
        }
        notify('success', 'Please Attention', data.show_text || 'Removed Successfully');
        load_form_div('<?= base_url('Medical/purchase_invoice_item_list/' . (int) ($invoice->id ?? 0)) ?>', 'invoice_item_list');
    }, 'json');
}

function show_challan() {
    load_form_div('<?= base_url('Medical/challan_invoice/' . (int) ($invoice->sid ?? 0)) ?>?current_purchase_id=<?= (int) ($invoice->id ?? 0) ?>', 'purchase_challan_history');
}

function add_item_in_invoice(ss_no) {
    $.post('<?= base_url('Medical/challan_item_to_purchase') ?>/' + ss_no + '/<?= (int) ($invoice->id ?? 0) ?>', $('#purchase-form').serialize(), function (data) {
        if ((data.is_transfer || 0) === 0) {
            notify('error', 'Please Attention', data.show_text || 'Unable to transfer item');
            return;
        }
        notify('success', 'Please Attention', data.show_text || 'Item Added Successfully');
        load_form_div('<?= base_url('Medical/purchase_invoice_item_list/' . (int) ($invoice->id ?? 0)) ?>', 'invoice_item_list');
        show_challan();
        resetItemHistoryPanel();
        focusProductSearch();
    }, 'json');
}

function return_to_challan(ss_no) {
    $.post('<?= base_url('Medical/challan_item_return') ?>/' + ss_no, $('#purchase-form').serialize(), function (data) {
        if ((data.is_transfer || 0) === 0) {
            notify('error', 'Please Attention', data.show_text || 'Unable to return item');
            return;
        }
        notify('success', 'Please Attention', data.show_text || 'Return Successfully');
        load_form_div('<?= base_url('Medical/purchase_invoice_item_list/' . (int) ($invoice->id ?? 0)) ?>', 'invoice_item_list');
        show_challan();
        resetItemHistoryPanel();
        focusProductSearch();
    }, 'json');
}

function edit_item_invoice(inv_item_id) {
    $.post('<?= base_url('Medical/purchase_invoice_item_edit/' . (int) ($invoice->id ?? 0)) ?>/' + inv_item_id, {}, function (data) {
        if ((data.is_update_stock || 0) === 0) {
            notify('error', 'Please Attention', data.show_text || 'Unable to load item');
            return;
        }

        toggle_update_purchase_items(true);
        $('#input_product_code').val(data.product_code || '');
        $('#input_product_mrp').val(data.product_mrp || '');
        $('#input_selling_price').val(data.selling_price || '');
        $('#input_batch_code').val(data.batch_code || '');
        $('#datepicker_doe_month').val(data.datepicker_doe_month || '01');
        $('#datepicker_doe_year').val(data.datepicker_doe_year || '26');
        $('#input_disc_price').val(data.disc_price || '');
        $('#input_sch_amount').val(data.sch_disc_amt || '');
        $('#input_sch_disc').val(data.sch_disc_per || '');
        $('#input_Qty').val(data.qty || '');
        $('#input_Qty_Free').val(data.qty_free || '');
        $('#input_package').val(data.package || '');
        $('#input_purchase_price').val(data.purchase_price || '');
        $('#input_drug').val(data.drug || '');
        $('#input_drug_hid').val(data.drug || '');
        $('#input_storage').val(data.cold_storage || '');
        $('#input_shelf_no').val(data.shelf_no || '');
        $('#input_rack_no').val(data.rack_no || '');
        $('#input_HSNCODE').val(data.HSNCODE || '');
        $('#input_CGST').val(data.CGST_per || '0');
        $('#input_SGST').val(data.SGST_per || '0');
        $('#invoice_item_id').val(data.item_id || 0);

        calculate();
        load_form_div('<?= base_url('Medical/purchase_invoice_item_list_old') ?>/' + data.product_code, 'purchase_items_old_history');
    }, 'json');
}

$(document).ready(function () {
    toggle_update_purchase_items(false);
    load_form_div('<?= base_url('Medical/purchase_invoice_item_list/' . (int) ($invoice->id ?? 0)) ?>', 'invoice_item_list');
    show_challan();

    $('#btn_update_stock').off('click').on('click', function () {
        calculate();
        $('#btn_update_stock').prop('disabled', true);

        $.post('<?= base_url('Medical/purchase_update_stock/' . (int) ($invoice->id ?? 0)) ?>', $('#purchase-form').serialize(), function (data) {
            if ((data.is_update_stock || 0) === 0) {
                notify('error', 'Please Attention', data.show_text || 'Unable to add stock');
                $('#btn_update_stock').prop('disabled', false);
                return;
            }
            notify('success', 'Please Attention', 'Added Successfully');
            toggle_update_purchase_items(false);
            load_form_div('<?= base_url('Medical/purchase_invoice_item_list/' . (int) ($invoice->id ?? 0)) ?>', 'invoice_item_list');
            show_challan();
            resetItemHistoryPanel();
            $('#btn_update_stock').prop('disabled', false);
            focusProductSearch();
        }, 'json');
    });

    $('#btn_update_stock_return').off('click').on('click', function () {
        if (!confirm('Are You sure ,Return this Item')) {
            return;
        }

        $.post('<?= base_url('Medical/purchase_update_stock/' . (int) ($invoice->id ?? 0) . '/1') ?>', $('#purchase-form').serialize(), function (data) {
            if ((data.is_update_stock || 0) === 0) {
                notify('error', 'Please Attention', data.show_text || 'Unable to return stock');
                return;
            }
            notify('success', 'Please Attention', 'Return Successfully');
            toggle_update_purchase_items(false);
            load_form_div('<?= base_url('Medical/purchase_invoice_item_list/' . (int) ($invoice->id ?? 0)) ?>', 'invoice_item_list');
            show_challan();
            resetItemHistoryPanel();
            focusProductSearch();
        }, 'json');
    });

    $('#input_drug').autocomplete({
        source: function (request, response) {
            $.getJSON('<?= base_url('Medical/get_drug_master') ?>', request, function (data) {
                response(data || []);
            });
        },
        minLength: 1,
        autofocus: true,
        select: function (event, ui) {
            toggle_update_purchase_items(true);
            $('#input_product_code').val(ui.item.l_item_code || '');
            $('#input_drug').val(ui.item.value || '');
            $('#input_drug_hid').val(ui.item.value || '');
            $('#input_product_mrp').val(ui.item.l_mrp || '');
            $('#input_selling_price').val(ui.item.l_mrp || '');
            $('#input_CGST').val(ui.item.l_CGST_per || '0');
            $('#input_SGST').val(ui.item.l_SGST_per || '0');
            $('#input_HSNCODE').val(ui.item.l_HSNCODE || '');
            $('#input_package').val(ui.item.l_package || ui.item.l_packing || '1');
            $('#input_purchase_price').val(ui.item.l_purchase_price || '');
            $('#input_disc_price').val(ui.item.l_disc_price || '0');
            $('#input_batch_code').val(ui.item.l_batch_no || '');
            $('#datepicker_doe_month').val(ui.item.datepicker_doe_month || '01');
            $('#datepicker_doe_year').val(ui.item.datepicker_doe_year || '26');
            $('#input_Qty_Free').val(0);
            $('#input_rack_no').val(ui.item.l_rack_no || '');
            $('#input_shelf_no').val(ui.item.l_shelf_no || '');
            $('#input_storage').val(ui.item.l_cold_storage || '');

            if (ui.item.l_item_code) {
                load_form_div('<?= base_url('Medical/purchase_invoice_item_list_old') ?>/' + ui.item.l_item_code, 'purchase_items_old_history');
            }
        }
    });
});
</script>
<?php endif; ?>
