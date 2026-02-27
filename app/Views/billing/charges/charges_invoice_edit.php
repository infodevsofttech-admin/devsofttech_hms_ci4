<section class="content-header">
  <h1>Charges</h1>
  <ol class="breadcrumb">
    <?php if (($invoiceMaster[0]->ipd_id ?? 0) > 0) : ?>
      <li><a href="javascript:load_form('<?= base_url('IpdNew/ipd_panel') ?>/<?= esc($invoiceMaster[0]->ipd_id) ?>');">IPD Panel</a></li>
    <?php endif; ?>
    <li><a href="javascript:load_form('<?= base_url('billing/patient/person_record') ?>/<?= esc($invoiceMaster[0]->attach_id ?? 0) ?>');">Person</a></li>
  </ol>
</section>
<section class="content">
<form role="form" class="form1">
<?= csrf_field() ?>
<?php
    $user = auth()->user();
    $canChargeDateEdit = is_object($user) && method_exists($user, 'can') ? $user->can('billing.charges.date-edit') : false;
    $invoiceDateValue = '';
    if (!empty($invoiceMaster[0]->inv_date)) {
        $invoiceDateValue = date('Y-m-d', strtotime((string) $invoiceMaster[0]->inv_date));
    }
    $dateReadonly = $canChargeDateEdit ? '' : 'readonly';
    $dateInputClass = $canChargeDateEdit ? 'form-control' : 'form-control bg-light';
?>
<div class="card border">
    <div class="card-header bg-white py-1">
        <div class="card-title mb-0" style="padding-top: 5px; padding-bottom: 5px;">
            <p><strong>Name :</strong><?= esc($person_info[0]->p_fname ?? '') ?> {<i><?= esc($person_info[0]->p_rname ?? '') ?></i>}
            <strong>/ Age :</strong><?= esc($person_info[0]->age ?? '') ?>
            <strong>/ Gender :</strong><?= esc($person_info[0]->xgender ?? '') ?>
            <strong>/ P Code :</strong><?= esc($person_info[0]->p_code ?? '') ?>
            <?php if (count($ipd_master) > 0) : ?>
                <strong>/ No. of Days :</strong><?= esc($ipd_master[0]->no_days ?? '') ?>
            <?php endif; ?>
            </p>
            <input type="hidden" id="pid" name="pid" value="<?= esc($person_info[0]->id ?? 0) ?>" />
            <input type="hidden" id="ins_id" name="ins_id" value="<?= esc($pdata ?? 0) ?>" />
            <input type="hidden" id="lab_invoice_id" name="lab_invoice_id" value="<?= esc($invoiceMaster[0]->id ?? 0) ?>" />
        </div>
    </div>
    <div class="card-body p-4">
        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-4">
                <label class="form-label">Doctor Name</label>
                <select class="form-select" id="doc_name_id" name="doc_name_id">
                    <option value="0" <?= combo_checked('0', $invoiceMaster[0]->refer_by_id ?? '') ?>>From Other Hospital</option>
                    <?php foreach ($doclist as $row) : ?>
                        <option value="<?= esc($row->id ?? '') ?>" <?= combo_checked($row->id ?? '', $invoiceMaster[0]->refer_by_id ?? '') ?>><?= esc($row->p_fname ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Other Doctor</label>
                <input class="form-control" name="input_doc_name" id="input_doc_name" placeholder="Doctor Name" value="<?= esc($invoiceMaster[0]->refer_by_other ?? '') ?>" type="text" />
            </div>
            <div class="col-md-4">
                <label class="form-label">Invoice Date</label>
                <input class="<?= $dateInputClass ?>" id="datepicker_invoicedate" name="datepicker_invoicedate" type="date" value="<?= esc($invoiceDateValue) ?>" <?= $dateReadonly ?> />
            </div>
        </div>
        <div class="row" id="show_item_list">
            <table class="table table-striped mb-3">
                <tr>
                    <th style="width: 10px">#</th>
                    <th>Charges Group</th>
                    <th>Charge Name</th>
                    <th>Rate</th>
                    <th>Qty</th>
                    <th>Updated Qty</th>
                    <th>Amount</th>
                </tr>
                <?php
                $srno = 0;
                foreach ($invoiceDetails as $row) {
                    $srno++;
                    echo '<tr>';
                    echo '<td>' . $srno . '</td>';
                    echo '<td>' . $row->group_desc . '</td>';
                    echo '<td>' . $row->item_name . '</td>';
                    if (in_array((int) $row->item_type, [1, 2, 3, 4, 5], true)) {
                        echo '<td>' . $row->item_rate . '</td>';
                        echo '<td>' . $row->item_qty . '</td>';
                        echo '<td>' . $row->item_amount . '</td>';
                        echo '<td>';
                    } else {
                        echo '<td><input type=hidden name="hidden_rate_' . $row->id . '" id="hidden_rate_' . $row->id . '" value="' . $row->item_rate . '" >' . $row->item_rate . '</td>';
                        echo '<td><input class="form-control" style="width:100px" name="input_qty_' . $row->id . '" id="input_qty_' . $row->id . '" value="' . $row->item_qty . '" type="number" /></td>';
                        echo '<td>' . $row->item_amount . '</td>';
                        echo '<td>';
                        echo '<button type="button" class="btn btn-primary" id="btn_update" onclick="update_qty(' . $row->id . ')">Update</button>';
                    }
                    echo '<button type="button" class="btn btn-danger" id="btn_remove" onclick="remove_item_invoice(' . $row->id . ')">-Remove</button>';
                    echo '</td>';
                    echo '</tr>';
                }
                echo '<input type="hidden" id="srno" name="srno" value="' . $srno . '" />';
                ?>
                <tr>
                    <th style="width: 10px">#</th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th>Gross Total</th>
                    <th><?= esc($invoiceGtotal[0]->Gtotal ?? 0) ?></th>
                </tr>
            </table>
        </div>
        <hr class="my-4" />
        <div class="row g-3 align-items-end">
            <div class="col-md-2">
                <div class="form-group">
                    <label>Charge Type [D]</label>
                    <select class="form-control select2" id="itype_idv" name="itype_id" accesskey="D">
                        <option value="0">Select Type</option>
                        <?php foreach ($labitemtype as $row) : ?>
                            <option value="<?= esc($row->itype_id ?? '') ?>"><?= esc($row->group_desc ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-4 show_lab_test">
                <div class="form-group">
                    <label>Charge Name</label>
                    <select class="form-control select2" id="itype_name_id" name="itype_name_id">
                        <option value="0">No Value</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Rate</label>
                    <input class="form-control number" name="input_rate" id="input_rate" placeholder="Rate" value="0.00" type="text" />
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Qty</label>
                    <input class="form-control" name="input_qty" id="input_qty" placeholder="Qty" value="1" type="number" />
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <button type="button" class="btn btn-primary" id="additem" onclick="add_item_invoice()" accesskey="A"><u>A</u>dd in List</button>
                </div>
            </div>
        </div>
        <div class="row g-3 align-items-end mt-1">
            
            <div class="col-md-8"></div>
            <div class="col-md-2">
                <div class="form-group">
                    <button type="button" class="btn btn-success" id="finalinvoice" accesskey="F"><u>F</u>inal Invoice</button>
                </div>
            </div>
        </div>
    </div>
</div>
</form>
</section>
<script>
$(document).ready(function(){
    $('#itype_idv').change(function(){
        var csrf_value = $('input[name="<?= csrf_token() ?>"]').val();
        $.post('<?= base_url('billing/charges/list-by-type') ?>', {
            "itype_idv": $('#itype_idv').val(),
            "ins_id": $('#ins_id').val(),
            "<?= csrf_token() ?>": csrf_value
        }, function(data){
            $('.show_lab_test').html(data);
            $('.select2').select2({
                width: '100%',
                dropdownAutoWidth: true,
                minimumResultsForSearch: 0
            });
            $('#input_rate').val('0.00');
            $('#input_qty').val('1');
            var $chargeSelect = $('#itype_name_id');
            $chargeSelect.on('select2:open', function() {
                setTimeout(function() {
                    var searchField = document.querySelector('.select2-container--open .select2-search__field');
                    if (searchField) {
                        searchField.focus();
                    }
                }, 0);
            });
            $chargeSelect.focus();
            $chargeSelect.select2('open');
            $('#itype_name_id').change(function(){
                $('#input_rate').val('0.00');
                $('#input_qty').val('1');
            });
        });
    });

    $('#finalinvoice').click(function(){
        var srno = $('#srno').val();
        var inv_id = $('#lab_invoice_id').val();
        var doc_id = $('#doc_name_id').val();
        var refername = $('#input_doc_name').val();
        var inv_date = $('#datepicker_invoicedate').val();
        var csrf_value = $('input[name="<?= csrf_token() ?>"]').val();

        if (srno < 1) {
            if (!confirm('Your Item List is Empty, Are you sure to Process this Invoice ?')) {
                return false;
            }
        }

        $.post('<?= base_url('billing/charges/update-refer') ?>', {
            "inv_id": inv_id,
            "doc_id": doc_id,
            "inv_date": inv_date,
            "refername": refername,
            "<?= csrf_token() ?>": csrf_value
        }, function(){
            load_form('<?= base_url('billing/charges/show') ?>/' + inv_id);
        });
    });
});

function add_item_invoice() {
    var csrf_value = $('input[name="<?= csrf_token() ?>"]').val();
    if ($('#input_qty').val() > 0 && $('#itype_name_id').val() > 0) {
        $.post('<?= base_url('billing/charges/item') ?>/1', {
            "itype_name_id": $('#itype_name_id').val(),
            "itype_idv": $('#itype_idv').val(),
            "lab_invoice_id": $('#lab_invoice_id').val(),
            "ins_id": $('#ins_id').val(),
            "input_qty": $('#input_qty').val(),
            "input_rate": $('#input_rate').val(),
            "<?= csrf_token() ?>": csrf_value
        }, function(data){
            $('#show_item_list').html(data);
        });
    }
}

function remove_item_invoice(itemid) {
    var csrf_value = $('input[name="<?= csrf_token() ?>"]').val();
    if (confirm('Are you sure Remove this item')) {
        $.post('<?= base_url('billing/charges/item') ?>/0', {
            "itemid": itemid,
            "lab_invoice_id": $('#lab_invoice_id').val(),
            "<?= csrf_token() ?>": csrf_value
        }, function(data){
            $('#show_item_list').html(data);
        });
    }
}

function update_qty(itemid) {
    var csrf_value = $('input[name="<?= csrf_token() ?>"]').val();
    if (confirm('Are you sure Update this item')) {
        var update_qty = $('#input_qty_' + itemid).val();
        var item_rate = $('#hidden_rate_' + itemid).val();
        $.post('<?= base_url('billing/charges/item') ?>/2', {
            "itemid": itemid,
            "lab_invoice_id": $('#lab_invoice_id').val(),
            "update_qty": update_qty,
            "item_rate": item_rate,
            "<?= csrf_token() ?>": csrf_value
        }, function(data){
            $('#show_item_list').html(data);
        });
    }
}
</script>
