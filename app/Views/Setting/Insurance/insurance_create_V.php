<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">Insurance Company - Add New</h3>
        <div class="card-tools ms-auto">
            <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('setting/admin/insurance') ?>','maindiv','Insurance');">
                <i class="bi bi-arrow-left"></i>
                Back to List
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="jsError"></div>
        <form action="<?= base_url('setting/admin/insurance/new') ?>" method="post" role="form" class="form1">
            <?= csrf_field() ?>
            <input type="hidden" value="0" id="p_id" name="p_id" />
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Company Name</label>
                    <input class="form-control" name="input_comp_name" placeholder="Full Name" value="" type="text" autocomplete="off">
                </div>
                <div class="col-md-3">
                    <label class="form-label">GST No</label>
                    <input class="form-control" name="input_gst_no" placeholder="GST Number" value="" type="text" autocomplete="off">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Phone Number</label>
                    <input class="form-control" name="input_mphone1" placeholder="Phone Number" value="" type="text" autocomplete="off">
                </div>
                <div class="col-md-3">
                    <label class="form-label">E-Mail</label>
                    <input class="form-control" name="input_email" placeholder="E-Mail" value="" type="text" autocomplete="off">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Contact Person Name</label>
                    <input class="form-control" name="input_cname" placeholder="Full Name" value="" type="text" autocomplete="off">
                </div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-3">
                    <label class="form-label">Agreement Start Date</label>
                    <input class="form-control datepicker" name="input_agreement_start_date" placeholder="dd/mm/yyyy" type="text" autocomplete="off">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Agreement End Date</label>
                    <input class="form-control datepicker" name="input_agreement_end_date" placeholder="dd/mm/yyyy" type="text" autocomplete="off">
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary" id="btn_update">Create Record</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('form.form1').on('submit', function(form) {
            form.preventDefault();
            $.post('<?= base_url('setting/admin/insurance/new') ?>', $('form.form1').serialize(), function(data) {
                if (data.insertid == 0) {
                    if (typeof notify === 'function') {
                        notify('Error', data.error_text);
                    }
                    return;
                }
                load_form_div('<?= base_url('setting/admin/insurance') ?>/' + data.insertid, 'maindiv', 'Insurance : ' + data.insertid);
            }, 'json');
        });
    });
</script>
