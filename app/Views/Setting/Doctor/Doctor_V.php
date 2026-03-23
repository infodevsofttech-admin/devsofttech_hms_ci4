<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">Add Doctor</h3>
        <div class="card-tools ms-auto">
            <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('setting/admin/doctor') ?>','maindiv','Doctor List');">
                <i class="bi bi-arrow-left"></i>
                Back to List
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php $errors = $errors ?? session('errors'); ?>
        <?php $formData = $formData ?? []; ?>
        <?php
        $doctorDobValue = trim((string) ($formData['datepicker_dob'] ?? ''));
        if ($doctorDobValue !== '' && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $doctorDobValue) === 1) {
            [$dobDay, $dobMonth, $dobYear] = explode('/', $doctorDobValue);
            $doctorDobValue = $dobYear . '-' . $dobMonth . '-' . $dobDay;
        }
        ?>
        <?php if (! empty($errors)) : ?>
            <div class="alert alert-danger">
                <?php foreach ((array) $errors as $error) : ?>
                    <div><?= esc($error) ?></div>
                <?php endforeach ?>
            </div>
        <?php endif ?>
        <div class="jsError"></div>
        <form action="<?= base_url('setting/admin/doctor/new') ?>" method="post" role="form" class="form1">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Title</label>
                    <select class="form-select" name="select_title" id="select_title">
                        <option value="Dr" <?= ($formData['select_title'] ?? '') === 'Dr' ? 'selected' : '' ?>>Dr.</option>
                        <option value="Mr" <?= ($formData['select_title'] ?? '') === 'Mr' ? 'selected' : '' ?>>Mr.</option>
                        <option value="Ms" <?= ($formData['select_title'] ?? '') === 'Ms' ? 'selected' : '' ?>>Ms.</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Full Name</label>
                    <input class="form-control" name="input_name" placeholder="Full Name" type="text" value="<?= esc($formData['input_name'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input class="form-control" name="input_email" placeholder="Email" type="email" value="<?= esc($formData['input_email'] ?? '') ?>">
                </div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-2">
                    <label class="form-label">Gender</label>
                    <div class="form-check">
                        <input class="form-check-input" name="optionsRadios_gender" id="options_gender1" value="1" type="radio" checked>
                        <label class="form-check-label" for="options_gender1">Male</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" name="optionsRadios_gender" id="options_gender2" value="2" type="radio">
                        <label class="form-check-label" for="options_gender2">Female</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone Number</label>
                    <input class="form-control" name="input_mphone1" placeholder="Phone Number" type="text" value="<?= esc($formData['input_mphone1'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Doctor Registration No.</label>
                    <input class="form-control" name="input_doc_reg_no" placeholder="NMC/MCI Registration No." type="text" value="<?= esc($formData['input_doc_reg_no'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date of Birth</label>
                    <input class="form-control" name="datepicker_dob" type="date" value="<?= esc($doctorDobValue) ?>">
                </div>
            </div>
            <?php
            $templateFieldSet = array_flip($template_fields ?? []);
            $templateOptions = $template_options ?? [];
            $opdPrintTemplateOptions = $opd_print_template_options ?? [];
            ?>
            <?php if (! empty($templateFieldSet)) : ?>
                <div class="row g-3 mt-1">
                    <?php if (isset($templateFieldSet['opd_print_format'])) : ?>
                        <div class="col-md-4">
                            <label class="form-label">OPD Print Template</label>
                            <select class="form-select" name="tmpl_opd_print_format">
                                <option value="">-- Select Template --</option>
                                <?php foreach ($opdPrintTemplateOptions as $tmpl) : ?>
                                    <option value="<?= esc($tmpl) ?>" <?= combo_checked($tmpl, strtolower(trim((string) ($formData['tmpl_opd_print_format'] ?? '')))) ?>><?= esc($tmpl) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($templateFieldSet['opd_blank_print'])) : ?>
                        <div class="col-md-4">
                            <label class="form-label">OPD Blank Head Template</label>
                            <select class="form-select" name="tmpl_opd_blank_print">
                                <option value="">-- Select Template --</option>
                                <?php foreach ($templateOptions as $tmpl) : ?>
                                    <option value="<?= esc($tmpl) ?>" <?= combo_checked($tmpl, strtolower(trim((string) ($formData['tmpl_opd_blank_print'] ?? '')))) ?>><?= esc($tmpl) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($templateFieldSet['rx_pre_print_letter_head_format'])) : ?>
                        <div class="col-md-4">
                            <label class="form-label">Rx Pre-Print Letterhead</label>
                            <select class="form-select" name="tmpl_rx_pre_print_letter_head_format">
                                <option value="">-- Select Template --</option>
                                <?php foreach ($templateOptions as $tmpl) : ?>
                                    <option value="<?= esc($tmpl) ?>" <?= combo_checked($tmpl, strtolower(trim((string) ($formData['tmpl_rx_pre_print_letter_head_format'] ?? '')))) ?>><?= esc($tmpl) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($templateFieldSet['rx_blank_letter_head'])) : ?>
                        <div class="col-md-6">
                            <label class="form-label">Rx Blank Letterhead</label>
                            <select class="form-select" name="tmpl_rx_blank_letter_head">
                                <option value="">-- Select Template --</option>
                                <?php foreach ($templateOptions as $tmpl) : ?>
                                    <option value="<?= esc($tmpl) ?>" <?= combo_checked($tmpl, strtolower(trim((string) ($formData['tmpl_rx_blank_letter_head'] ?? '')))) ?>><?= esc($tmpl) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($templateFieldSet['rx_plain_paper'])) : ?>
                        <div class="col-md-6">
                            <label class="form-label">Rx Plain Paper</label>
                            <select class="form-select" name="tmpl_rx_plain_paper">
                                <option value="">-- Select Template --</option>
                                <?php foreach ($templateOptions as $tmpl) : ?>
                                    <option value="<?= esc($tmpl) ?>" <?= combo_checked($tmpl, strtolower(trim((string) ($formData['tmpl_rx_plain_paper'] ?? '')))) ?>><?= esc($tmpl) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function() {
        var form = document.querySelector('form.form1');
        if (!form || !window.jQuery) {
            return;
        }

        $(form).on('submit', function(event) {
            event.preventDefault();
            $('#maindiv').html('Data Posting... Please wait.');
            $.post($(form).attr('action'), $(form).serialize())
                .done(function(html) {
                    $('#maindiv').html(html);
                })
                .fail(function() {
                    alert('Request failed. Please try again.');
                });
        });
    })();
</script>
