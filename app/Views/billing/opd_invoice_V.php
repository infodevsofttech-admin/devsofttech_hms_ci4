<?php
$canEdit = false;
$user = auth()->user();
if ($user) {
    if (method_exists($user, 'can') && $user->can('billing.opd.edit')) {
        $canEdit = true;
    }

    if (! $canEdit && method_exists($user, 'inGroup')) {
        $canEdit = $user->inGroup('OPDEdit');
    }
}
$paymentHistoryRows = $payment_history_rows ?? [];
?>
<section class="content-header">
    <h1>OPD Invoice</h1>
    <ol class="breadcrumb">
        <li><a href="javascript:load_form('<?= base_url('billing/patient/person_record') ?>/<?= esc($opd_master[0]->p_id ?? 0) ?>/0');">Person Home</a></li>
    </ol>
</section>

<section class="invoice">
    <form role="form" class="form1">
        <?= csrf_field() ?>
        <div class="row invoice-info">
            <div class="col-sm-4 invoice-col">
                To
                <address>
                    <strong><?= esc(strtoupper($opd_master[0]->P_name ?? '')) ?></strong><br>
                    <?= esc($patient_master[0]->p_relative ?? '') ?> : <?= esc($patient_master[0]->p_rname ?? '') ?><br>
                    OPD Book Date (y-m-d time) : <?= esc($opd_master[0]->opd_book_date ?? '') ?><br>
                    Gender : <?= esc($patient_master[0]->xgender ?? '') ?><br>
                    Age : <?= esc($patient_master[0]->age ?? '') ?><br>
                    Phone No : <?= esc($patient_master[0]->mphone1 ?? '') ?>
                </address>
            </div>
            <div class="col-sm-4 invoice-col">
                <b>OPD ID:</b> <?= esc($opd_master[0]->opd_code ?? '') ?><br>
                <b>Date of Appointment:</b> <?= esc($opd_master[0]->str_apointment_date ?? '') ?><br>
                <b>Patient ID :</b> <?= esc($patient_master[0]->p_code ?? '') ?><br>
                <?php if (($opd_master[0]->insurance_id ?? 0) > 1 && !empty($insurance)) : ?>
                    <strong> Ins. Comp. :</strong> <?= esc($insurance[0]->ins_company_name ?? '') ?><br>
                <?php endif; ?>
                <input type="hidden" id="oid" name="oid" value="<?= esc($opd_master[0]->opd_id ?? 0) ?>" />
                <?php if (!empty($opd_master[0]->payment_id)) : ?>
                    <strong>Payment No. :</strong> <?= esc($opd_master[0]->payment_id ?? '') ?><br>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <div class="col-12 table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Doctor</th>
                            <th>Department</th>
                            <th>OPD Fee</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?= esc(MysqlDate_to_str($opd_master[0]->apointment_date ?? '')) ?></td>
                            <td>Dr. <?= esc($opd_master[0]->doc_name ?? '') ?></td>
                            <td><?= esc($opd_master[0]->doc_spec ?? '') ?></td>
                            <td><?= esc($opd_master[0]->opd_fee_gross_amount ?? '') ?></td>
                            <td><?= esc($opd_master[0]->opd_fee_desc ?? '') ?></td>
                        </tr>
                        <?php if (($opd_master[0]->payment_status ?? 0) == 0) : ?>
                            <tr>
                                <td>Any Deduction</td>
                                <td colspan="2">
                                    <input class="form-control" name="input_dis_desc" id="input_dis_desc"
                                        placeholder="Ded. Desc." value="<?= esc($opd_master[0]->opd_disc_remark ?? '') ?>" type="text" />
                                </td>
                                <td>
                                    <input style="width: 100px" class="form-control" name="input_dis_amt" id="input_dis_amt"
                                        placeholder="Amount" value="<?= esc($opd_master[0]->opd_discount ?? '') ?>" type="text" />
                                </td>
                                <td><button type="button" class="btn btn-primary" id="btn_update_ded">Update</button></td>
                            </tr>
                        <?php else : ?>
                            <?php if (!empty($opd_master[0]->opd_discount)) : ?>
                                <tr>
                                    <td>Deduction</td>
                                    <td colspan="2"><?= esc($opd_master[0]->opd_disc_remark ?? '') ?></td>
                                    <td><?= esc($opd_master[0]->opd_discount ?? '') ?></td>
                                    <td></td>
                                </tr>
                            <?php endif; ?>
                        <?php endif; ?>
                        <tr>
                            <th style="width: 10px">#</th>
                            <th colspan="2">Net Amount</th>
                            <th><?= esc($opd_master[0]->opd_fee_amount ?? '') ?></th>
                            <th></th>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (($opd_master[0]->payment_status ?? 0) == 0 && ($refund_status ?? 0) == 0) : ?>
            <div class="payment_type">
                <div class="jsError text-danger"></div>
                <div id="payment_type" class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Payment Mode</label>
                            <div class="accordion" id="accordionPayment">
                                <?php if (($pending_amount ?? 0) > 0) : ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingCash">
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCash" aria-expanded="true" aria-controls="collapseCash">
                                                Cash
                                            </button>
                                        </h2>
                                        <div id="collapseCash" class="accordion-collapse collapse show" aria-labelledby="headingCash" data-bs-parent="#accordionPayment">
                                            <div class="accordion-body">
                                                <button type="button" class="btn btn-primary" id="btn_update1">Confirm Cash Received and Print Receipt</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingCard">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCard" aria-expanded="false" aria-controls="collapseCard">
                                                Credit / Debit Card / UPI / Online
                                            </button>
                                        </h2>
                                        <div id="collapseCard" class="accordion-collapse collapse" aria-labelledby="headingCard" data-bs-parent="#accordionPayment">
                                            <div class="accordion-body">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>Payment By</label>
                                                            <select class="form-select" name="cbo_pay_type" id="cbo_pay_type">
                                                                <?php foreach ($bank_data as $row) : ?>
                                                                    <option value="<?= esc($row->id ?? '') ?>"><?= esc($row->pay_type ?? '') ?> [<?= esc($row->bank_name ?? '') ?>]</option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>Tran. ID/Ref.</label>
                                                            <input class="form-control" id="input_card_tran" placeholder="Card Tran.ID." type="text" autocomplete="off">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>Payment Confirm By Bank/Online</label>
                                                            <button type="button" class="btn btn-primary" id="btn_update2">Confirm Payment</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php elseif (($pending_amount ?? 0) == 0) : ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingZero">
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseZero" aria-expanded="true" aria-controls="collapseZero">
                                                Zero Amount
                                            </button>
                                        </h2>
                                        <div id="collapseZero" class="accordion-collapse collapse show" aria-labelledby="headingZero" data-bs-parent="#accordionPayment">
                                            <div class="accordion-body">
                                                <button id="btn_update0" type="button" class="btn btn-primary">Confirm With Zero Amount</button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (($opd_master[0]->insurance_id ?? 0) > 0) : ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingOrg">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOrg" aria-expanded="false" aria-controls="collapseOrg">
                                                Credit to Organization
                                            </button>
                                        </h2>
                                        <div id="collapseOrg" class="accordion-collapse collapse" aria-labelledby="headingOrg" data-bs-parent="#accordionPayment">
                                            <div class="accordion-body">
                                                <?php foreach ($case_master as $row) : ?>
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <div class="form-group">
                                                                <label>Case ID</label>
                                                                <input class="form-control" id="input_case_id" placeholder="Claim ID" type="text"
                                                                    value="<?= esc($row->case_id_code ?? '') ?>" autocomplete="off" readonly>
                                                                <input type="hidden" id="hidden_case_id" value="<?= esc($row->id ?? '') ?>">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="form-group">
                                                                <label>Credit Confirm</label>
                                                                <button type="button" class="btn btn-primary" id="btn_update4">Click here to Confirm</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else : ?>
            <div class="row no-print">
                <div class="col-sm-6">
                    <a href="<?= base_url('opd_print/invoice_print_pdf') ?>/<?= esc($opd_master[0]->opd_id ?? 0) ?>" target="_blank" class="btn btn-outline-secondary">Print Invoice</a>
                    <a href="<?= base_url('opd_print/opd_PDF_print') ?>/<?= esc($opd_master[0]->opd_id ?? 0) ?>" target="_blank" class="btn btn-outline-secondary">Print OPD HEAD in Letter Head</a>
                    <a href="<?= base_url('opd_print/opd_blank_print') ?>/<?= esc($opd_master[0]->opd_id ?? 0) ?>" target="_blank" class="btn btn-outline-secondary">Print OPD HEAD in Blank Page</a>
                    <a href="<?= base_url('opd_print/opd_Cont_print') ?>/<?= esc($opd_master[0]->opd_id ?? 0) ?>" target="_blank" class="btn btn-outline-secondary">Print Cont. Paper</a>
                    
                </div>
                <div class="col-sm-6">
                    <?php if (($refund_status ?? 0) == 1) : ?>
                        Payment Refund Status : Pending
                    <?php elseif (($opd_master[0]->opd_status ?? 0) == 3) : ?>
                        <h1>Status is Cancelled</h1>
                    <?php else : ?>
                        Payment Method by : <?= esc($opd_master[0]->payment_mode_desc ?? '') ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <hr />
        <div class="row g-3">
            <div class="col-12">
                <h5 class="mb-2">Payment History</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Mode</th>
                                <th class="text-end">Amount</th>
                                <th>Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($paymentHistoryRows)) : ?>
                                <?php foreach ($paymentHistoryRows as $historyRow) : ?>
                                    <?php
                                    $isRefund = (int) ($historyRow->credit_debit ?? 0) === 1;
                                    $historyDate = trim((string) ($historyRow->payment_date ?? ''));
                                    if ($historyDate === '') {
                                        $historyDate = trim((string) ($historyRow->insert_time ?? ''));
                                    }
                                    ?>
                                    <tr>
                                        <td><?= esc($historyDate) ?></td>
                                        <td><?= $isRefund ? '<span class="text-danger">Refund</span>' : '<span class="text-success">Received</span>' ?></td>
                                        <td><?= esc($historyRow->payment_mode_str ?? '') ?></td>
                                        <td class="text-end"><?= esc(number_format((float) ($historyRow->amount ?? 0), 2, '.', '')) ?></td>
                                        <td><?= esc($historyRow->remark ?? '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No payment history entries.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($canEdit && ((int) ($opd_master[0]->opd_status ?? 0) === 1 || (int) ($opd_master[0]->opd_status ?? 0) === 2)) : ?>
            <hr />
            <div class="row g-3">
                <?php if ((int) ($opd_master[0]->opd_status ?? 0) === 1) : ?>
                    <div class="col-md-6">
                        <input class="form-control" name="input_remark" id="input_remark" placeholder="Remark" type="text" />
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-primary" id="btn_cancel_opd">Cancel OPD</button>
                    </div>
                <?php endif; ?>
                <div class="col-md-4">
                    <?php if (count($case_master ?? []) > 0) : ?>
                        <input type="hidden" id="hid_org_id" name="hid_org_id" value="<?= esc($case_master[0]->id ?? '') ?>">
                        <button type="button" class="btn btn-success" id="btn_cr_org">Credit To Org. [<?= esc($case_master[0]->case_id_code ?? '') ?>]</button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-3">
                    <label>Change Doctor</label>
                    <select class="form-select" id="doc_name_id" name="doc_name_id">
                        <?php foreach ($doc_spec_l as $row) : ?>
                            <option value="<?= esc($row->id ?? '') ?>" <?= combo_checked($row->id ?? '', $opd_master[0]->doc_id ?? '') ?>><?= esc($row->p_fname ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Date <?= esc(MysqlDate_to_str($opd_master[0]->apointment_date ?? '')) ?></label>
                    <input class="form-control datepicker" id="datepicker_opddate" name="datepicker_opddate" type="text" value="<?= esc(MysqlDate_to_str($opd_master[0]->apointment_date ?? '')) ?>" />
                </div>
                <div class="col-md-3">
                    <label>OPD Fee <?= esc($opd_master[0]->opd_fee_gross_amount ?? '') ?> / <?= esc($opd_master[0]->opd_fee_amount ?? '') ?></label>
                    <input style="width: 100px" class="form-control" name="input_opd_fee_amt" id="input_opd_fee_amt" placeholder="Amount" value="<?= esc($opd_master[0]->opd_fee_gross_amount ?? '') ?>" type="text" />
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary mt-4" id="update_doc_date">Update Doctor and Date</button>
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-3">
                    <?php if (((int) ($opd_master[0]->payment_mode ?? 0) === 4)) : ?>
                        <button type="button" class="btn btn-primary" id="update_opd_payment_mode_change">Reverse to Cash</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </form>
</section>

<div class="clearfix"></div>
<input type="hidden" id="spid" value="<?= date('dmyHis') . rand(10000, 99999) ?>" />

<script>
    (function() {
        function enable_btn() {
            $('#btn_update1').prop('disabled', false);
            $('#btn_update2').prop('disabled', false);
        }

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

        $('#btn_update0').click(function() {
            $('#btn_update1').prop('disabled', true);
            $('#btn_update2').prop('disabled', true);
            var csrf = getCsrfPair();
            var spid = $('#spid').val();

            if (confirm('Are you sure process this invoice')) {
                $.post('<?= base_url('Opd/confirm_payment') ?>', {
                    "mode": "0",
                    "oid": $('#oid').val(),
                    "spid": spid,
                    [csrf.name]: csrf.value
                }, function(data) {
                    updateCsrf(data);
                    if (data.update == 0) {
                        $('div.jsError').html(data.error_text);
                    } else {
                        load_form('<?= base_url('Opd/invoice') ?>/' + $('#oid').val());
                    }
                }, 'json');
            } else {
                setTimeout(enable_btn, 5000);
            }
        });

        $('#btn_update1').click(function() {
            $('#btn_update1').prop('disabled', true);
            $('#btn_update2').prop('disabled', true);
            var csrf = getCsrfPair();
            var spid = $('#spid').val();

            if (confirm('Are you sure process this invoice')) {
                $.post('<?= base_url('Opd/confirm_payment') ?>', {
                    "mode": "1",
                    "oid": $('#oid').val(),
                    "spid": spid,
                    [csrf.name]: csrf.value
                }, function(data) {
                    updateCsrf(data);
                    if (data.update == 0) {
                        $('div.jsError').html(data.error_text);
                    } else {
                        load_form('<?= base_url('Opd/invoice') ?>/' + $('#oid').val());
                    }
                }, 'json');
            } else {
                setTimeout(enable_btn, 5000);
            }
        });

        $('#btn_update2').click(function() {
            $('#btn_update1').prop('disabled', true);
            $('#btn_update2').prop('disabled', true);
            var csrf = getCsrfPair();
            var spid = $('#spid').val();

            if (confirm('Are you sure process this invoice')) {
                $.post('<?= base_url('Opd/confirm_payment') ?>', {
                    "mode": "2",
                    "oid": $('#oid').val(),
                    "cbo_pay_type": $('#cbo_pay_type').val(),
                    "input_card_tran": $('#input_card_tran').val(),
                    "spid": spid,
                    [csrf.name]: csrf.value
                }, function(data) {
                    updateCsrf(data);
                    if (data.update == 0) {
                        if (typeof notify === 'function') {
                            notify('error', 'Please Attention', data.error_text);
                        }
                        setTimeout(enable_btn, 5000);
                    } else {
                        load_form('<?= base_url('Opd/invoice') ?>/' + $('#oid').val());
                    }
                }, 'json');
            } else {
                setTimeout(enable_btn, 5000);
            }
        });

        $('#btn_update4').click(function() {
            var csrf = getCsrfPair();
            $.post('<?= base_url('Opd/confirm_payment') ?>', {
                "mode": "4",
                "oid": $('#oid').val(),
                "case_id": $('#hidden_case_id').val(),
                [csrf.name]: csrf.value
            }, function(data) {
                updateCsrf(data);
                if (data.update == 0) {
                    $('div.jsError').html(data.error_text);
                } else {
                    load_form('<?= base_url('Opd/invoice') ?>/' + $('#oid').val());
                }
            }, 'json');
        });

        $('#btn_update_ded').click(function() {
            var csrf = getCsrfPair();
            if (confirm('Are you sure process this invoice')) {
                $.post('<?= base_url('Opd/opd_discount_update') ?>/' + $('#oid').val(), {
                    "oid": $('#oid').val(),
                    "input_dis_desc": $('#input_dis_desc').val(),
                    "input_dis_amt": $('#input_dis_amt').val(),
                    [csrf.name]: csrf.value
                }, function() {
                    load_form('<?= base_url('Opd/invoice') ?>/' + $('#oid').val());
                });
            }
        });

        $('#btn_cancel_opd').click(function() {
            var csrf = getCsrfPair();
            if (confirm('Are you sure process this invoice')) {
                $.post('<?= base_url('Opd/opd_cancel') ?>/' + $('#oid').val(), {
                    "oid": $('#oid').val(),
                    "input_remark": $('#input_remark').val(),
                    [csrf.name]: csrf.value
                }, function(data) {
                    updateCsrf(data);
                    load_form('<?= base_url('Opd/invoice') ?>/' + $('#oid').val());
                });
            }
        });

        $('#btn_cr_org').click(function() {
            var csrf = getCsrfPair();
            if (confirm('Are you sure process this invoice')) {
                $.post('<?= base_url('Opd/opd_crorg') ?>/' + $('#oid').val() + '/' + $('#hid_org_id').val(), {
                    "oid": $('#oid').val(),
                    "org_code_id": $('#hid_org_id').val(),
                    [csrf.name]: csrf.value
                }, function() {
                    load_form('<?= base_url('Opd/invoice') ?>/' + $('#oid').val());
                });
            }
        });

        $('#update_doc_date').click(function() {
            var csrf = getCsrfPair();
            if (confirm('Are you sure process this invoice')) {
                $.post('<?= base_url('Opd/update_doc_date') ?>/' + $('#oid').val(), {
                    "oid": $('#oid').val(),
                    "doc_name_id": $('#doc_name_id').val(),
                    "opd_fee_amt": $('#input_opd_fee_amt').val(),
                    "datepicker_opddate": $('#datepicker_opddate').val(),
                    [csrf.name]: csrf.value
                }, function() {
                    load_form('<?= base_url('Opd/invoice') ?>/' + $('#oid').val());
                });
            }
        });
    })();
</script>
