<?php
$doctorRegNo = '';
if (!empty($data) && !empty($data[0])) {
    foreach (['nmc_reg_no', 'mci_reg_no', 'registration_no', 'reg_no', 'doctor_reg_no', 'doc_reg_no', 'council_reg_no'] as $regField) {
        if (isset($data[0]->{$regField}) && trim((string) $data[0]->{$regField}) !== '') {
            $doctorRegNo = trim((string) $data[0]->{$regField});
            break;
        }
    }

    if ($doctorRegNo === '') {
        $shortDescription = trim((string) ($data[0]->doc_sign ?? ''));
        if ($shortDescription !== '') {
            $normalizedShortDescription = preg_replace('/\s+/', ' ', $shortDescription);
            if ($normalizedShortDescription !== null) {
                if (preg_match('/(?:reg(?:istration)?\s*(?:no|number)?\s*[:\-]?\s*)([A-Z0-9\/-]{4,})/i', $normalizedShortDescription, $matches) === 1 && ! empty($matches[1])) {
                    $doctorRegNo = trim((string) $matches[1]);
                }
            }
        }
    }
}
?>
<?php
$doctorDobValue = '';
if (!empty($data) && !empty($data[0]) && !empty($data[0]->dob)) {
    $doctorDobValue = trim((string) $data[0]->dob);
}
?>
<?php if (! empty($data)) : ?>
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h3 class="card-title mb-0">Doctor: <?= esc(($data[0]->p_title ?? '') . ' ' . ($data[0]->p_fname ?? '')) ?></h3>
            <div class="card-tools ms-auto">
                <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('setting/admin/doctor') ?>','maindiv','Doctor List');">
                    <i class="bi bi-arrow-left"></i>
                    Back to List
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="jsError"></div>
            <form action="<?= base_url('setting/admin/doctor/update') ?>" method="post" role="form" class="form1">
                <?= csrf_field() ?>
                <input type="hidden" name="doc_id" id="doc_id" value="<?= esc($data[0]->id ?? '') ?>" />
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Title</label>
                        <select class="form-select" name="select_title" id="select_title">
                            <option value="Dr." <?= combo_checked('Dr.', $data[0]->p_title ?? '') ?>>Dr.</option>
                            <option value="Mr" <?= combo_checked('Mr', $data[0]->p_title ?? '') ?>>Mr.</option>
                            <option value="Ms" <?= combo_checked('Ms', $data[0]->p_title ?? '') ?>>Ms.</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Full Name</label>
                        <input class="form-control" name="input_name" placeholder="Full Name" type="text" value="<?= esc($data[0]->p_fname ?? '') ?>" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email</label>
                        <input class="form-control" name="input_email" placeholder="Email" type="email" value="<?= esc($data[0]->email1 ?? '') ?>" />
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-3">
                        <label class="form-label">Gender</label>
                        <div class="form-check">
                            <input class="form-check-input" name="optionsRadios_gender" id="options_gender1" value="1" <?= radio_checked(1, $data[0]->gender ?? '') ?> type="radio">
                            <label class="form-check-label" for="options_gender1">Male</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" name="optionsRadios_gender" id="options_gender2" value="2" <?= radio_checked(2, $data[0]->gender ?? '') ?> type="radio">
                            <label class="form-check-label" for="options_gender2">Female</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Phone Number</label>
                        <input class="form-control" name="input_mphone1" placeholder="Phone Number" type="text" value="<?= esc($data[0]->mphone1 ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Doctor Registration No.</label>
                        <input class="form-control" name="input_doc_reg_no" placeholder="NMC/MCI Registration No." type="text" value="<?= esc($doctorRegNo) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date of Birth</label>
                        <input class="form-control" name="datepicker_dob" value="<?= esc($doctorDobValue) ?>" type="date" />
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-12">
                        <label class="form-label">Short Description</label>
                        <textarea class="form-control" name="txt_doc_sign" placeholder="Short Info"><?= esc($data[0]->doc_sign ?? '') ?></textarea>
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
                                        <option value="<?= esc($tmpl) ?>" <?= combo_checked($tmpl, strtolower(trim((string) ($data[0]->opd_print_format ?? '')))) ?>><?= esc($tmpl) ?></option>
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
                                        <option value="<?= esc($tmpl) ?>" <?= combo_checked($tmpl, strtolower(trim((string) ($data[0]->opd_blank_print ?? '')))) ?>><?= esc($tmpl) ?></option>
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
                                        <option value="<?= esc($tmpl) ?>" <?= combo_checked($tmpl, strtolower(trim((string) ($data[0]->rx_pre_print_letter_head_format ?? '')))) ?>><?= esc($tmpl) ?></option>
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
                                        <option value="<?= esc($tmpl) ?>" <?= combo_checked($tmpl, strtolower(trim((string) ($data[0]->rx_blank_letter_head ?? '')))) ?>><?= esc($tmpl) ?></option>
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
                                        <option value="<?= esc($tmpl) ?>" <?= combo_checked($tmpl, strtolower(trim((string) ($data[0]->rx_plain_paper ?? '')))) ?>><?= esc($tmpl) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
                <hr/>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Specility</label>
                        <div id="sho_specility">
                            <?php foreach ($doc_spec_a as $row) : ?>
                                <div class="input-group input-group-sm">
                                    <input class="form-control" type="text" value="<?= esc($row->SpecName ?? '') ?>" readonly />
                                    <span class="input-group-btn">
                                        <button type="button" class="btn btn-info btn-flat" onclick="remove_doc_spec(<?= (int) ($row->doc_spec_id ?? 0) ?>)">Remove -</button>
                                    </span>
                                </div>
                            <?php endforeach ?>
                        </div>
                        <div class="input-group mt-2">
                            <select class="form-select" name="doc_spec" id="doc_spec">
                                <option value="0">------Select-------</option>
                                <?php foreach ($doc_spec_l as $row) : ?>
                                    <option value="<?= esc($row->id ?? '') ?>"><?= esc($row->SpecName ?? '') ?></option>
                                <?php endforeach ?>
                            </select>
                            <button type="button" class="btn btn-info" onclick="add_doc_spec()">Add +</button>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="mb-3">
                            <h6 class="text-muted">OPD Fees</h6>
                        </div>
                        <div class="show_fee_list">
                            <?php
                            $tableHtml = '<table class="table table-striped"><thead><tr><th>Fee Type</th><th>Description</th><th>Amount</th><th></th></tr></thead><tbody>';
                            foreach ($doc_fee_list as $row) {
                                $button = 'Not Define';
                                if (! empty($row->id)) {
                                    $button = '<a href="javascript:remove_fees(' . (int) $row->id . ')">Remove</a>';
                                }
                                $tableHtml .= '<tr><td>' . esc($row->fee_type ?? '') . '</td><td>' . esc($row->doc_fee_desc ?? '') . '</td><td>' . esc($row->amount ?? '') . '</td><td>' . $button . '</td></tr>';
                            }
                            $tableHtml .= '</tbody></table>';
                            echo $tableHtml;
                            ?>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-3">
                                <label class="form-label">Fee Type</label>
                                <select class="form-select" name="fee_type" id="fee_type">
                                    <?php foreach ($doc_fee_type as $row) : ?>
                                        <option value="<?= esc($row->id ?? '') ?>"><?= esc($row->fee_type ?? '') ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Fee Description</label>
                                <input class="form-control" id="input_fee_desc" name="input_fee_desc" placeholder="Fee Description" type="text" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Amount</label>
                                <input class="form-control" id="input_fee_amount" name="input_fee_amount" placeholder="Amount" type="text" />
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" class="btn btn-primary" id="btn_add_fee" onclick="add_fees()">+Add</button>
                            </div>
                        </div>
                        <hr/>
                        <div class="mb-3">
                            <h6 class="text-muted">IPD Fees</h6>
                        </div>
                        <div class="show_ipd_fee_list">
                            <?php
                            $ipdTableHtml = '<table class="table table-striped"><thead><tr><th>Fee Type</th><th>Description</th><th>Amount</th><th></th></tr></thead><tbody>';
                            foreach ($doc_ipd_fee_list as $row) {
                                $button = 'Not Define';
                                if (! empty($row->id)) {
                                    $button = '<a href="javascript:remove_ipd_fees(' . (int) $row->id . ')">Remove</a>';
                                }
                                $ipdTableHtml .= '<tr><td>' . esc($row->fee_type ?? '') . '</td><td>' . esc($row->doc_fee_desc ?? '') . '</td><td>' . esc($row->amount ?? '') . '</td><td>' . $button . '</td></tr>';
                            }
                            $ipdTableHtml .= '</tbody></table>';
                            echo $ipdTableHtml;
                            ?>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-3">
                                <label class="form-label">Fee Type</label>
                                <select class="form-select" name="ipd_fee_type" id="ipd_fee_type">
                                    <?php foreach ($doc_ipd_fee_type as $row) : ?>
                                        <option value="<?= esc($row->id ?? '') ?>"><?= esc($row->fee_type ?? '') ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Fee Description</label>
                                <input class="form-control" id="ipd_input_fee_desc" name="ipd_input_fee_desc" placeholder="Fee Description" type="text" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Amount</label>
                                <input class="form-control" id="ipd_input_fee_amount" name="ipd_input_fee_amount" placeholder="Amount" type="text" />
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" class="btn btn-primary" id="btn_add_ipd_fee" onclick="add_ipd_fees()">+Add</button>
                            </div>
                        </div>
                    </div>
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

                $.post('<?= base_url('setting/admin/doctor/update') ?>', $(form).serialize(), function(data) {
                    if (typeof notify === 'function') {
                        if (data.update == 0) {
                            notify('error', 'Please Attention', data.error_text);
                        } else {
                            notify('success', 'Please Attention', data.showcontent);
                        }
                    }
                }, 'json');
            });
        })();

        function getCsrfPair() {
            var input = document.querySelector('input[name="<?= csrf_token() ?>"]');
            if (!input) {
                return { name: '<?= csrf_token() ?>', value: '<?= csrf_hash() ?>' };
            }
            return { name: input.getAttribute('name'), value: input.value };
        }

        function updateCsrf(data) {
            if (!data || !data.csrfName || !data.csrfHash) {
                return;
            }
            var input = document.querySelector('input[name="' + data.csrfName + '"]');
            if (input) {
                input.value = data.csrfHash;
            }
        }

        function add_fees() {
            var csrf = getCsrfPair();

            $.post('<?= base_url('setting/admin/doctor/fee/add') ?>',
                {
                    "fee_type": $('#fee_type').val(),
                    "doc_id": $('#doc_id').val(),
                    "input_fee_desc": $('#input_fee_desc').val(),
                    "input_fee_amount": $('#input_fee_amount').val(),
                    [csrf.name]: csrf.value
                }, function(data) {
                    if (data.inser_id == 0) {
                        if (typeof notify === 'function') {
                            notify('error', 'Please Attention', data.error_text);
                        }
                    } else {
                        $('div.show_fee_list').html(data.show_fee_list);
                        if (typeof notify === 'function') {
                            notify('success', 'Please Attention', data.showcontent);
                        }
                    }
                    updateCsrf(data);
                }, 'json');
        }

        function remove_fees(rid) {
            var csrf = getCsrfPair();

            $.post('<?= base_url('setting/admin/doctor/fee/remove') ?>',
                {
                    "rid": rid,
                    "doc_id": $('#doc_id').val(),
                    [csrf.name]: csrf.value
                }, function(data) {
                    if (data.update == 0) {
                        if (typeof notify === 'function') {
                            notify('error', 'Please Attention', data.error_text);
                        }
                    } else {
                        $('div.show_fee_list').html(data.show_fee_list);
                        if (typeof notify === 'function') {
                            notify('success', 'Please Attention', data.showcontent);
                        }
                    }
                    updateCsrf(data);
                }, 'json');
        }

        function add_ipd_fees() {
            var csrf = getCsrfPair();

            $.post('<?= base_url('setting/admin/doctor/ipd-fee/add') ?>',
                {
                    "fee_type": $('#ipd_fee_type').val(),
                    "doc_id": $('#doc_id').val(),
                    "input_fee_desc": $('#ipd_input_fee_desc').val(),
                    "input_fee_amount": $('#ipd_input_fee_amount').val(),
                    [csrf.name]: csrf.value
                }, function(data) {
                    if (data.inser_id == 0) {
                        if (typeof notify === 'function') {
                            notify('error', 'Please Attention', data.error_text);
                        }
                    } else {
                        $('div.show_ipd_fee_list').html(data.show_fee_list);
                        if (typeof notify === 'function') {
                            notify('success', 'Please Attention', data.showcontent);
                        }
                    }
                    updateCsrf(data);
                }, 'json');
        }

        function remove_ipd_fees(rid) {
            var csrf = getCsrfPair();

            $.post('<?= base_url('setting/admin/doctor/ipd-fee/remove') ?>',
                {
                    "rid": rid,
                    "doc_id": $('#doc_id').val(),
                    [csrf.name]: csrf.value
                }, function(data) {
                    if (data.update == 0) {
                        if (typeof notify === 'function') {
                            notify('error', 'Please Attention', data.error_text);
                        }
                    } else {
                        $('div.show_ipd_fee_list').html(data.show_fee_list);
                        if (typeof notify === 'function') {
                            notify('success', 'Please Attention', data.showcontent);
                        }
                    }
                    updateCsrf(data);
                }, 'json');
        }

        function add_doc_spec() {
            var csrf = getCsrfPair();

            $.post('<?= base_url('setting/admin/doctor/spec') ?>',
                {
                    "doc_spec": $('#doc_spec').val(),
                    "doc_id": $('#doc_id').val(),
                    "isadd": 1,
                    [csrf.name]: csrf.value
                }, function(data) {
                    $('#sho_specility').html(data.show_Specility_list);
                    updateCsrf(data);
                }, 'json');
        }

        function remove_doc_spec(doc_spec) {
            var csrf = getCsrfPair();

            $.post('<?= base_url('setting/admin/doctor/spec') ?>',
                {
                    "doc_spec_id": doc_spec,
                    "doc_id": $('#doc_id').val(),
                    "isadd": 0,
                    [csrf.name]: csrf.value
                }, function(data) {
                    $('#sho_specility').html(data.show_Specility_list);
                    updateCsrf(data);
                }, 'json');
        }
    </script>
<?php endif ?>
