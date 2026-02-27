<?php
$sid = 0;
$nameSupplier = '';
$shortName = '';
$contactNo = '';
$gstNo = '';
$city = '';
$state = '';
$active = 1;

if (!empty($supplier_data ?? [])) {
    $row = $supplier_data[0];
    $sid = (int) ($row->sid ?? 0);
    $nameSupplier = (string) ($row->name_supplier ?? '');
    $shortName = (string) ($row->short_name ?? '');
    $contactNo = (string) ($row->contact_no ?? '');
    $gstNo = (string) ($row->gst_no ?? '');
    $city = (string) ($row->city ?? '');
    $state = (string) ($row->state ?? '');
    $active = (int) ($row->active ?? 1);
}
?>

<div class="card">
    <div class="card-header">Supplier</div>
    <div class="card-body pt-3">
        <div id="supplier_msg"></div>

        <form id="supplier-form" class="row g-2" method="post" action="javascript:void(0)">
            <?= csrf_field() ?>
            <input type="hidden" id="hid_sid" name="hid_sid" value="<?= esc((string) $sid) ?>">

            <div class="col-md-6">
                <label class="form-label">Supplier Name</label>
                <input class="form-control" name="input_name_supplier" id="input_name_supplier" placeholder="Supplier Name" type="text" value="<?= esc($nameSupplier) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Short Name</label>
                <input class="form-control" name="input_short_name" id="input_short_name" placeholder="Short Name" type="text" value="<?= esc($shortName) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">GST No.</label>
                <input class="form-control" name="input_gst_no" id="input_gst_no" placeholder="GST No." type="text" value="<?= esc($gstNo) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone No.</label>
                <input class="form-control" name="input_contact_no" id="input_contact_no" placeholder="Phone No." type="text" value="<?= esc($contactNo) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">City</label>
                <input class="form-control" name="input_city" id="input_city" placeholder="City" type="text" value="<?= esc($city) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">State</label>
                <select name="input_state" id="input_state" class="form-select">
                    <?php foreach (($india_state ?? []) as $row): ?>
                        <?php $stateName = (string) ($row->state_name ?? ''); ?>
                        <option value="<?= esc($stateName) ?>" <?= strcasecmp($stateName, $state) === 0 ? 'selected' : '' ?>><?= esc($stateName) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <div class="form-check mt-2">
                    <input class="form-check-input" id="chk_active" name="chk_active" type="checkbox" <?= $active === 1 ? 'checked' : '' ?>>
                    <label class="form-check-label" for="chk_active">Active</label>
                </div>
            </div>

            <div class="col-12">
                <button type="button" class="btn btn-primary" id="btn_supplier_update" accesskey="A">Add Supplier & Update</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    function showMessage(html) {
        $('#supplier_msg').html(html || '');
    }

    $('#btn_supplier_update').off('click').on('click', function () {
        $.post('<?= base_url('Medical/SupplierUpdate') ?>', $('#supplier-form').serialize(), function (data) {
            if (!data || typeof data !== 'object') {
                showMessage('<div class="alert alert-danger mb-0">Unexpected response.</div>');
                return;
            }

            showMessage(data.show_text || '');
            if ((data.insertid || 0) > 0) {
                $('#hid_sid').val(data.insertid);
                load_form_div('<?= base_url('Medical/SupplierListSub') ?>', 'supplier_list');
                load_form_div('<?= base_url('Medical/SupplierEdit') ?>/' + data.insertid, 'test_div');
            }
        }, 'json').fail(function () {
            showMessage('<div class="alert alert-danger mb-0">Unable to update supplier.</div>');
        });
    });
})();
</script>
