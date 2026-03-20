<style>
    .insurance-form-theme {
        border: 0;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 14px 32px rgba(15, 23, 42, 0.08);
    }

    .insurance-form-theme > .card-header {
        background: linear-gradient(120deg, #1d4ed8 0%, #0ea5e9 55%, #22c55e 100%);
        color: #ffffff;
        border-bottom: 0;
        padding: 14px 16px;
    }

    .insurance-form-theme > .card-header .card-title {
        font-weight: 700;
        letter-spacing: .2px;
    }

    .insurance-form-theme > .card-body {
        background: #f8fafc;
        padding: 18px;
    }

    .insurance-form-theme .form-label {
        font-weight: 600;
        color: #0f172a;
    }

    .insurance-form-theme .form-control {
        border-radius: 10px;
        border: 1px solid #cbd5e1;
        box-shadow: none;
        transition: border-color .2s ease, box-shadow .2s ease;
    }

    .insurance-form-theme .form-control:focus {
        border-color: #0284c7;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.16);
    }

    .insurance-form-theme #btn_update {
        border-radius: 10px;
        padding: 10px 16px;
        font-weight: 600;
    }
</style>

<div class="card insurance-form-theme">
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
