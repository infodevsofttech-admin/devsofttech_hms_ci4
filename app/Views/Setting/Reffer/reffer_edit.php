<?php if (! empty($data)) : ?>
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h3 class="card-title mb-0">Edit Referral Client</h3>
            <div class="card-tools ms-auto">
                <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('setting/admin/reffer') ?>','maindiv','Referral Clients');">
                    <i class="bi bi-arrow-left"></i>
                    Back to List
                </button>
            </div>
        </div>
        <div class="card-body">
            <div id="infoMessage" class="jsError"></div>
            <form action="<?= base_url('setting/admin/reffer/update/' . $data[0]->id) ?>" method="post" role="form" class="form1">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Title</label>
                        <input class="form-control" name="cbo_title" id="cbo_title" type="text" placeholder="Title / Clinic / Hospital" value="<?= esc($data[0]->title ?? '') ?>" autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Name</label>
                        <input class="form-control" name="input_name" placeholder="Full Name" value="<?= esc($data[0]->f_name ?? '') ?>" type="text" autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Phone Number</label>
                        <input class="form-control" name="input_phone_number" placeholder="Phone Number" value="<?= esc($data[0]->phone_number ?? '') ?>" type="text" autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="cbo_refer_type" id="cbo_refer_type">
                            <?php foreach ($refer_type as $row) : ?>
                                <option value="<?= esc($row->id ?? '') ?>" <?= combo_checked($row->id ?? '', $data[0]->refer_type ?? '') ?>><?= esc($row->type_desc ?? '') ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Place</label>
                        <input class="form-control" name="input_place" placeholder="Place" value="<?= esc($data[0]->place ?? '') ?>" type="text" autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">City</label>
                        <input class="form-control" name="input_city" placeholder="City" value="<?= esc($data[0]->city ?? '') ?>" type="text" autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">District</label>
                        <input class="form-control" name="input_district" placeholder="District" value="<?= esc($data[0]->district ?? '') ?>" type="text" autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">State</label>
                        <input class="form-control" name="input_state" placeholder="State" value="<?= esc($data[0]->state ?? '') ?>" type="text" autocomplete="off">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary" id="btn_update">Update Record</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('form.form1').on('submit', function(form) {
                form.preventDefault();
                $.post('<?= base_url('setting/admin/reffer/update/' . $data[0]->id) ?>', $('form.form1').serialize(), function(data) {
                    if (data.update == 0) {
                        $('div.jsError').html(data.error_text);
                        if (typeof notify === 'function') {
                            notify('error', 'Please Attention', data.error_text || 'Please Check');
                        }
                    } else {
                        if (typeof notify === 'function') {
                            notify('success', 'Saved', 'Referral client updated.');
                        }
                        load_form_div('<?= base_url('setting/admin/reffer') ?>', 'maindiv', 'Referral Clients');
                    }
                }, 'json');
            });
        });
    </script>
<?php endif ?>
