<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">New Referral Client</h3>
        <div class="card-tools ms-auto">
            <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('setting/admin/reffer') ?>','maindiv','Referral Clients');">
                <i class="bi bi-arrow-left"></i>
                Back to List
            </button>
        </div>
    </div>
    <div class="card-body">
        <div id="infoMessage" class="jsError"></div>
        <form action="<?= base_url('setting/admin/reffer/save') ?>" method="post" role="form" class="form1">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Title</label>
                    <select class="form-select" name="cbo_title" id="cbo_title">
                        <option value="Mr.">Mr.</option>
                        <option value="Mrs.">Mrs.</option>
                        <option value="Ms.">Ms.</option>
                        <option value="Dr.">Dr.</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Name</label>
                    <input class="form-control" name="input_name" placeholder="Full Name" value="" type="text" autocomplete="off">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Phone Number</label>
                    <input class="form-control" name="input_phone_number" placeholder="Phone Number" value="" type="text" autocomplete="off">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <select class="form-select" name="cbo_refer_type" id="cbo_refer_type">
                        <?php foreach ($refer_type as $row) : ?>
                            <option value="<?= esc($row->id ?? '') ?>"><?= esc($row->type_desc ?? '') ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary" id="btn_update">Add Record</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('form.form1').on('submit', function(form) {
            form.preventDefault();
            $.post('<?= base_url('setting/admin/reffer/save') ?>', $('form.form1').serialize(), function(data) {
                if (data.insertid == 0) {
                    $('div.jsError').html(data.error_text);
                    if (typeof notify === 'function') {
                        notify('error', 'Please Attention', data.error_text || 'Please Check');
                    }
                } else {
                    if (typeof notify === 'function') {
                        notify('success', 'Saved', 'Referral client added.');
                    }
                    load_form_div('<?= base_url('setting/admin/reffer') ?>', 'maindiv', 'Referral Clients');
                }
            }, 'json');
        });
    });
</script>
