<?php
$cid = 0;
$companyName = '';
$contactPersonName = '';
$contactPhoneNo = '';

if (!empty($med_company ?? [])) {
    $row = $med_company[0];
    $cid = (int) ($row->id ?? 0);
    $companyName = (string) ($row->company_name ?? '');
    $contactPersonName = (string) ($row->contact_person_name ?? '');
    $contactPhoneNo = (string) ($row->contact_phone_no ?? '');
}
?>

<div class="card">
    <div class="card-header">Company</div>
    <div class="card-body pt-3">
        <div id="company_msg"></div>

        <form id="company-form" class="row g-2" method="post" action="javascript:void(0)">
            <?= csrf_field() ?>
            <input type="hidden" id="hid_cid" name="hid_cid" value="<?= esc((string) $cid) ?>">

            <div class="col-md-6">
                <label class="form-label">Company Name</label>
                <input class="form-control" name="input_company_name" id="input_company_name" placeholder="Company Name" type="text" value="<?= esc($companyName) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Contact Person Name</label>
                <input class="form-control" name="input_contact_person_name" id="input_contact_person_name" placeholder="Contact Person Name" type="text" value="<?= esc($contactPersonName) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Contact Phone No</label>
                <input class="form-control" name="input_contact_phone_no" id="input_contact_phone_no" placeholder="Contact Phone No" type="text" value="<?= esc($contactPhoneNo) ?>">
            </div>

            <div class="col-12">
                <button type="button" class="btn btn-primary" id="btn_company_update" accesskey="A">Add & Update Company</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    function showMessage(html) {
        $('#company_msg').html(html || '');
    }

    $('#btn_company_update').off('click').on('click', function () {
        $.post('<?= base_url('Product_master/CompanyUpdate') ?>', $('#company-form').serialize(), function (data) {
            if (!data || typeof data !== 'object') {
                showMessage('<div class="alert alert-danger mb-0">Unexpected response.</div>');
                return;
            }

            showMessage(data.show_text || '');
            if ((data.insertid || 0) > 0) {
                $('#hid_cid').val(data.insertid);
                load_form_div('<?= base_url('Product_master/CompanyListSub') ?>', 'company_list');
                load_form_div('<?= base_url('Product_master/CompanyEdit') ?>/' + data.insertid, 'test_div');
            }
        }, 'json').fail(function () {
            showMessage('<div class="alert alert-danger mb-0">Unable to update company.</div>');
        });
    });
})();
</script>
