<?php $invoice = $purchase_return_invoice ?? null; ?>

<?php if (! $invoice): ?>
    <div class="alert alert-warning mb-0">Purchase return invoice not found.</div>
<?php else: ?>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Purchase Return Invoice / Supplier : <?= esc((string) ($invoice->name_supplier ?? '-')) ?></h5>
        <small>
            <i>Invoice No.</i>: <?= esc((string) ($invoice->Invoice_no ?? '')) ?> |
            <i>Invoice Date</i>: <?= esc((string) ($invoice->str_date_of_invoice ?? '')) ?>
            <button onclick="load_form_div('<?= base_url('Medical_backpanel/PurchaseReturnInvoiceEdit/' . (int) ($invoice->id ?? 0)) ?>','searchresult','Purchase Return Invoice Edit');" type="button" class="btn btn-warning btn-sm ms-2">Reload Invoice</button>
            <a href="<?= base_url('Medical_Print/print_purchase_return/' . (int) ($invoice->id ?? 0)) ?>" target="_blank" class="btn btn-secondary btn-sm ms-1"><i class="fa fa-print"></i> Print</a>
        </small>
    </div>
    <div class="card-body pt-3">
        <div class="row g-2">
            <div class="col-lg-6">
                <div id="show_item_list">
                    <?= $content ?? '' ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-info">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Item List</h6>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-info" onclick="load_form_div('<?= base_url('Medical_backpanel/Purchase_Invoice_old/' . (int) ($invoice->sid ?? 0)) ?>','search_body_part');">Show Items from same supplier</button>
                            <button type="button" class="btn btn-outline-info" onclick="load_form_div('<?= base_url('Medical_backpanel/Purchase_Invoice_product/' . (int) ($invoice->sid ?? 0)) ?>','search_body_part');">Product Search</button>
                        </div>
                    </div>
                    <div class="card-body" id="search_body_part" style="max-height:520px;overflow:auto;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function remove_item_add(itemid) {
    var rqty = parseFloat($('#input_qty_' + itemid).val() || 0);
    var inv_id = <?= (int) ($invoice->id ?? 0) ?>;

    if (rqty <= 0) {
        notify('error', 'Please Attention', 'Invalid quantity');
        return;
    }

    var payload = {
        itemid: itemid,
        inv_id: inv_id,
        rqty: rqty
    };

    var csrfName = $('input[name="<?= csrf_token() ?>"]').attr('name');
    var csrfValue = $('input[name="<?= csrf_token() ?>"]').val();
    if (csrfName && csrfValue) {
        payload[csrfName] = csrfValue;
    }

    $.post('<?= base_url('Medical_backpanel/add_remove_item') ?>', payload, function (data) {
        if (!data || (data.update || 0) === 0) {
            notify('error', 'Please Attention', data && data.msg_text ? data.msg_text : 'Unable to add item');
            return;
        }
        notify('success', 'Please Attention', data.msg_text || 'Item Added');
        $('#show_item_list').html(data.content || '');
    }, 'json');
}

function remove_custom_item() {
    var rqty = parseFloat($('#input_product_qty').val() || 0);
    var itemid = parseInt($('#l_ssno').val() || '0', 10);
    var inv_id = <?= (int) ($invoice->id ?? 0) ?>;

    if (itemid <= 0 || rqty <= 0) {
        notify('error', 'Please Attention', 'Choose product and quantity');
        return;
    }

    var payload = {
        itemid: itemid,
        inv_id: inv_id,
        rqty: rqty,
        rbatch_no: $('#input_batch_no').val() || '',
        rexpiry_dt: $('#input_expiry_dt').val() || ''
    };

    var csrfName = $('input[name="<?= csrf_token() ?>"]').attr('name');
    var csrfValue = $('input[name="<?= csrf_token() ?>"]').val();
    if (csrfName && csrfValue) {
        payload[csrfName] = csrfValue;
    }

    $.post('<?= base_url('Medical_backpanel/add_remove_item') ?>', payload, function (data) {
        if (!data || (data.update || 0) === 0) {
            notify('error', 'Please Attention', data && data.msg_text ? data.msg_text : 'Unable to add item');
            return;
        }

        $('#input_product_code').html('');
        $('#input_product_name').html('');
        $('#input_batch').html('');
        $('#input_product_mrp').html('');
        $('#input_product_unit_rate').val('');
        $('#l_ssno').val(0);
        $('#input_drug').val('');
        $('#input_product_qty').val('0');
        $('#stock_product_qty').html('');
        $('#purchase_id').val('');

        notify('success', 'Please Attention', data.msg_text || 'Item Added');
        $('#show_item_list').html(data.content || '');
        $('#input_drug').focus();
    }, 'json');
}

function remove_item_invoice(itemid) {
    var payload = {};
    var csrfName = $('input[name="<?= csrf_token() ?>"]').attr('name');
    var csrfValue = $('input[name="<?= csrf_token() ?>"]').val();
    if (csrfName && csrfValue) {
        payload[csrfName] = csrfValue;
    }

    $.post('<?= base_url('Medical_backpanel/remove_item_invoice') ?>/' + itemid, payload, function (data) {
        if (!data || (data.update || 0) === 0) {
            notify('error', 'Please Attention', data && data.msg_text ? data.msg_text : 'Unable to remove item');
            return;
        }
        notify('success', 'Please Attention', data.msg_text || 'Item Removed');
        $('#show_item_list').html(data.content || '');
    }, 'json');
}
</script>
