<?php if (! empty($data_insurance)) : ?>
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h3 class="card-title mb-0">Insurance Company - <?= esc($data_insurance[0]->ins_company_name ?? '') ?></h3>
            <div class="card-tools ms-auto">
                <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('setting/admin/insurance') ?>','maindiv','Insurance');">
                    <i class="bi bi-arrow-left"></i>
                    Back to List
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="jsError"></div>
            <form action="<?= base_url('setting/admin/insurance/update') ?>" method="post" role="form" class="form1">
                <?= csrf_field() ?>
                <input type="hidden" value="<?= esc($data_insurance[0]->id ?? '') ?>" id="p_id" name="p_id" />
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Company Name</label>
                        <input class="form-control" name="input_comp_name" value="<?= esc($data_insurance[0]->ins_company_name ?? '') ?>" type="text" autocomplete="off">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">GST No</label>
                        <input class="form-control" name="input_gst_no" value="<?= esc($data_insurance[0]->gst_no ?? '') ?>" type="text" autocomplete="off">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Short Name</label>
                        <input class="form-control" name="input_short_name" value="<?= esc($data_insurance[0]->short_name ?? '') ?>" type="text" autocomplete="off">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Phone Number</label>
                        <input class="form-control" name="input_mphone1" value="<?= esc($data_insurance[0]->ins_contact_number ?? '') ?>" type="text" autocomplete="off">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">E-Mail</label>
                        <input class="form-control" name="input_email" value="<?= esc($data_insurance[0]->ins_email ?? '') ?>" type="text" autocomplete="off">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Contact Person Name</label>
                        <input class="form-control" name="input_cname" value="<?= esc($data_insurance[0]->ins_contact_person_name ?? '') ?>" type="text" autocomplete="off">
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-3">
                        <label class="form-label">Agreement Start Date</label>
                        <input class="form-control datepicker" name="input_agreement_start_date" value="<?= esc(MysqlDate_to_str($data_insurance[0]->agreement_start_date ?? '')) ?>" type="text" autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Agreement End Date</label>
                        <input class="form-control datepicker" name="input_agreement_end_date" value="<?= esc(MysqlDate_to_str($data_insurance[0]->agreement_end_date ?? '')) ?>" type="text" autocomplete="off">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="chk_active" id="chk_active" value="1" <?= checkbox_checked($data_insurance[0]->active ?? 0) ?>>
                            <label class="form-check-label" for="chk_active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">OPD</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="chk_opd_allowed" id="chk_opd_allowed" value="1" <?= checkbox_checked($data_insurance[0]->opd_allowed ?? 0) ?>>
                                            <label class="form-check-label" for="chk_opd_allowed">OPD Allow</label>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">OPD Rate Apply</label>
                                        <div class="form-check">
                                            <input class="form-check-input" name="optionsRadios_opd_rate_direct" id="options_opd_rate_direct1" value="0" <?= radio_checked('0', $data_insurance[0]->opd_rate_direct ?? '') ?> type="radio">
                                            <label class="form-check-label" for="options_opd_rate_direct1">Direct Customer Rate</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" name="optionsRadios_opd_rate_direct" id="options_opd_rate_direct2" value="1" <?= radio_checked('1', $data_insurance[0]->opd_rate_direct ?? '') ?> type="radio">
                                            <label class="form-check-label" for="options_opd_rate_direct2">Rate Specific Insurance Company</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-2 mt-1">
                                    <div class="col-md-4">
                                        <label class="form-label">OPD Fee Description</label>
                                        <input class="form-control" name="input_opd_fee_desc" value="<?= esc($data_insurance[0]->opd_desc ?? '') ?>" type="text" autocomplete="off">
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" name="chk_opd_cash" id="chk_opd_cash" value="1" <?= checkbox_checked($data_insurance[0]->opd_cash ?? 0) ?>>
                                            <label class="form-check-label" for="chk_opd_cash">Direct Allowed</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" name="chk_opd_credit" id="chk_opd_credit" value="1" <?= checkbox_checked($data_insurance[0]->opd_credit ?? 0) ?>>
                                            <label class="form-check-label" for="chk_opd_credit">Credit Allowed</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-2 mt-1">
                                    <div class="col-md-6">
                                        <label class="form-label">{if Direct Customer Rate} Discount</label>
                                        <input class="form-control" name="input_opd_master_rate_discount" value="<?= esc($data_insurance[0]->opd_master_rate_discount ?? '') ?>" type="text" autocomplete="off">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">{if Rate Specific Insurance Company} OPD Fee</label>
                                        <input class="form-control" name="input_opd_fee" value="<?= esc($data_insurance[0]->opd_fee ?? '') ?>" type="text" autocomplete="off">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Charges And Medicine</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label class="form-label">Rate Apply</label>
                                        <div class="form-check">
                                            <input class="form-check-input" name="optionsRadios_charge_rate_direct" id="options_charge_rate_direct1" value="0" <?= radio_checked('0', $data_insurance[0]->charge_rate_direct ?? '') ?> type="radio">
                                            <label class="form-check-label" for="options_charge_rate_direct1">Direct Customer Rate</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" name="optionsRadios_charge_rate_direct" id="options_charge_rate_direct2" value="1" <?= radio_checked('1', $data_insurance[0]->charge_rate_direct ?? '') ?> type="radio">
                                            <label class="form-check-label" for="options_charge_rate_direct2">Rate Specific Insurance Company</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-2 mt-1">
                                    <div class="col-md-4">
                                        <label class="form-label">{if Direct Customer Rate} Discount</label>
                                        <input class="form-control" name="input_charge_rate_dicount" value="<?= esc($data_insurance[0]->charge_rate_dicount ?? '') ?>" type="text" autocomplete="off">
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" name="chk_charge_credit" id="chk_charge_credit" value="1" <?= checkbox_checked($data_insurance[0]->charge_credit ?? 0) ?>>
                                            <label class="form-check-label" for="chk_charge_credit">Charge Credit</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" name="chk_med_credit" id="chk_med_credit" value="1" <?= checkbox_checked($data_insurance[0]->med_credit ?? 0) ?>>
                                            <label class="form-check-label" for="chk_med_credit">Medicine Credit</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-primary" id="btn_update">Update Record</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#btn_update').click(function() {
                $.post('<?= base_url('setting/admin/insurance/update') ?>', $('form.form1').serialize(), function(data) {
                    if (data.update == 0) {
                        $('div.jsError').html(data.error_text);
                        if (typeof notify === 'function') {
                            notify('error', 'Please Attention', data.error_text || 'Please Check');
                        }
                    } else if (typeof notify === 'function') {
                        notify('success', 'Saved', data.showcontent || 'Data Saved successfully');
                    }
                }, 'json');
            });
        });
    </script>

<?php endif ?>
