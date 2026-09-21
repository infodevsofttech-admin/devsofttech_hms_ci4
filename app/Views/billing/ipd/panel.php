<?php
$ipd = $ipd_info ?? null;
$person = $person_info ?? null;
$age = '';
if ($person) {
    $age = get_age_1($person->dob ?? null, $person->age ?? '', $person->age_in_month ?? '', $person->estimate_dob ?? '');
}
?>
<section class="content">


    <style>
        .ipd-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 5px;
            margin-top: 5px;
            margin-bottom: 5px;
            margin-left: 5px;
            margin-right: 5px;
        }

        .ipd-summary-card {
            border-radius: 10px;
            color: #fff;
            padding: 14px 16px;
            font-weight: 600;
        }

        .ipd-summary-card span {
            display: block;
            font-weight: 500;
        }

        .ipd-summary-card.orange {
            background: #f59e0b;
        }

        .ipd-summary-card.green {
            background: #16a34a;
        }

        .ipd-summary-card.blue {
            background: #0ea5e9;
        }

        .ipd-summary-card.gray {
            background: #64748b;
        }

        .ipd-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .ipd-actions .btn {
            border-radius: 8px;
        }

        .ipd-tabs .nav-link {
            font-weight: 600;
        }

        .ipd-tab-panel {
            padding: 16px;
            border: 1px solid #e2e8f0;
            border-top: none;
            border-radius: 0 0 10px 10px;
            background: #fff;
        }
    </style>

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <h5 class="mb-0">
                        <?php if (($ipd->admission_type ?? 'ipd') === 'daycare'): ?>
                            Day Care : <?= esc($ipd->ipd_code ?? '') ?>
                        <?php elseif (($ipd->admission_type ?? 'ipd') === 'emergency'): ?>
                            Emergency : <?= esc($ipd->ipd_code ?? '') ?>
                        <?php else: ?>
                            IPD : <?= esc($ipd->ipd_code ?? '') ?>
                        <?php endif; ?>
                    </h5>
                    <?php if (($ipd->admission_type ?? 'ipd') === 'daycare'): ?>
                        <span class="badge bg-warning text-dark"><i class="bi bi-clock-history"></i> Day Care (&lt; 24 Hrs)</span>
                    <?php elseif (($ipd->admission_type ?? 'ipd') === 'emergency'): ?>
                        <?php 
                            $tLevel = (int) ($ipd->triage_level ?? 3);
                            $tColor = $tLevel === 1 ? 'danger' : ($tLevel === 2 ? 'warning text-dark' : ($tLevel === 3 ? 'warning text-dark' : 'info text-dark'));
                        ?>
                        <span class="badge bg-<?= $tColor ?>"><i class="bi bi-ambulance"></i> Emergency ESI-<?= $tLevel ?></span>
                    <?php endif; ?>
                    <?php if (! empty($ipd->converted_from_type)): ?>
                        <span class="badge bg-info text-dark" title="Converted from <?= esc($ipd->converted_from_type) ?> (<?= esc($ipd->converted_from_code) ?>) on <?= esc($ipd->converted_at) ?>">
                            <i class="bi bi-arrow-repeat"></i> Converted from <?= ucfirst(esc($ipd->converted_from_type)) ?> (<?= esc($ipd->converted_from_code) ?>)
                        </span>
                    <?php endif; ?>
                    <?php if (! empty($ipd->is_mlc)): ?>
                        <span class="badge bg-danger"><i class="bi bi-shield-shaded"></i> MLC</span>
                    <?php endif; ?>
                </div>
                <div class="ipd-actions">
                    <?php if (in_array($ipd->admission_type ?? 'ipd', ['daycare', 'emergency'], true) && (int) ($ipd->ipd_status ?? 0) === 0): ?>
                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#convertToIpdModal">
                            <i class="bi bi-arrow-up-right-circle-fill"></i> Convert to IPD
                        </button>
                    <?php endif; ?>
                    <button class="btn btn-sm btn-primary" onclick="javascript:load_form_div('<?= base_url('billing/patient/person_record') ?>/<?= esc($person->id ?? 0) ?>/0','maindiv','Patient Record');">
                        <i class="bi bi-person-lines-fill"></i> Patient Record
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-lg-9">
                    <p class="mb-2">
                        <strong>Name :</strong> <?= esc($person->p_fname ?? '') ?> {<i><?= esc($person->p_rname ?? '') ?></i>}
                        <strong>/ Age :</strong> <?= esc($age) ?>
                        <strong>/ Gender :</strong> <?= esc($person->xgender ?? '') ?>
                        <strong>/ UHID :</strong> <?= esc($person->p_code ?? '') ?>
                        <strong>/ Bed :</strong> <span class="badge bg-light text-dark border"><?= esc($ipd->bed_no ?? 'None') ?></span>
                        <strong>/ No of Days :</strong> <span id="ipd-panel-no-days"><?= esc($ipd->no_days ?? '') ?></span>
                    </p>
                    <p class="mb-1">
                        <strong>Admit Date :</strong> <span id="ipd-panel-admit-date"><?= esc($ipd->str_register_date ?? '') ?></span>
                        <strong>/ Discharge Date :</strong> <span id="ipd-panel-discharge-date"><?= esc($ipd->str_discharge_date ?? '') ?></span>
                        <?php if (! empty($ipd->daycare_procedure_name)): ?>
                            <strong>/ Day Care Procedure :</strong> <span class="text-primary font-monospace"><?= esc($ipd->daycare_procedure_name) ?></span>
                        <?php endif; ?>
                    </p>
                    <?php if (($ipd->admission_type ?? '') === 'emergency' || ! empty($ipd->is_mlc) || ! empty($ipd->converted_from_type)): ?>
                        <div class="p-2 mt-1 rounded" style="background:#f8fafc; border:1px solid #e2e8f0; font-size:12.5px;">
                            <?php if (($ipd->admission_type ?? '') === 'emergency'): ?>
                                <span class="me-3"><strong>Triage Acuity:</strong> Level <?= esc($ipd->triage_level ?? '3') ?></span>
                                <?php if (! empty($ipd->brought_by)): ?>
                                    <span class="me-3"><strong>Brought By:</strong> <?= esc($ipd->brought_by) ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if (! empty($ipd->is_mlc)): ?>
                                <span class="me-3 text-danger"><strong>MLC No:</strong> <?= esc($ipd->mlc_number ?: 'Pending') ?></span>
                                <?php if (! empty($ipd->police_station)): ?>
                                    <span class="me-3"><strong>Police Station:</strong> <?= esc($ipd->police_station) ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if (! empty($ipd->converted_from_type)): ?>
                                <div class="text-muted mt-1">
                                    <i class="bi bi-info-circle"></i> Escalated from <strong><?= ucfirst(esc($ipd->converted_from_type)) ?></strong> (Original: <?= esc($ipd->converted_from_code) ?>) on <?= esc($ipd->converted_at) ?>. Reason: <em><?= esc($ipd->conversion_reason ?: 'Clinical escalation') ?></em>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <div class="ipd-summary">
            <div class="ipd-summary-card orange">
                <span>Charges : <?= esc($ipd->charge_amount ?? '0') ?></span>
                <span>Pharmacy Cr. IPD : <?= esc($ipd->med_amount ?? '0') ?></span>
                <span>Net Amount : <?= esc($ipd->net_amount ?? '0') ?></span>
            </div>
            <div class="ipd-summary-card green">
                <span>Total Paid : <?= esc($ipd->total_paid_amount ?? '0') ?></span>
                <span>Balance : <?= esc($ipd->balance_amount ?? '0') ?></span>
                <hr/>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#payModal" data-ipd-id="<?= (int) ($ipd->id ?? 0) ?>">Payment Add</button> &nbsp;&nbsp;
                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#payModal_ded" data-ipd-id="<?= (int) ($ipd->id ?? 0) ?>">Payment Refund</button>
            </div>
            <div class="ipd-summary-card blue">
                <span>Pharmacy Bill : <?= esc($ipd->cash_med_amount ?? '0') ?></span>
                <span>Paid Amount : <?= esc($ipd->med_paid ?? '0') ?></span>
            </div>
            <div class="ipd-summary-card gray">
                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#payModal_TPA" data-ipd-id="<?= (int) ($ipd->id ?? 0) ?>">Payment TPA And Others</button>
                <?php if (! empty($ipd->ins_company_name ?? '') || ! empty($ipd->case_id_code ?? '') || ! empty($ipd->insurance_no ?? '') || ! empty($ipd->insurance_no_1 ?? '') || ! empty($ipd->insurance_no_2 ?? '')) : ?>
                    <div class="mt-2 small">
                        <?php if (! empty($ipd->ins_company_name ?? '')) : ?>
                            <span class="d-block">
                                Insurance: <?= esc($ipd->ins_company_name ?? '') ?>
                                <?php if (! empty($ipd->ins_short_name ?? '')) : ?>
                                    Short: <?= esc($ipd->ins_short_name ?? '') ?>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                        <?php if (! empty($ipd->case_id_code ?? '')) : ?>
                            <span class="d-block">Org Case: <?= esc($ipd->case_id_code ?? '') ?></span>
                        <?php endif; ?>
                        <?php if (! empty($ipd->insurance_no ?? '')) : ?>
                            <span class="d-block">Card No: <?= esc($ipd->insurance_no ?? '') ?></span>
                        <?php endif; ?>
                        <?php if (! empty($ipd->insurance_no_1 ?? '')) : ?>
                            <span class="d-block">Claim ID: <?= esc($ipd->insurance_no_1 ?? '') ?></span>
                        <?php endif; ?>
                        <?php if (! empty($ipd->insurance_no_2 ?? '')) : ?>
                            <span class="d-block">Service No: <?= esc($ipd->insurance_no_2 ?? '') ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    </div>

    <div class="card">
        <div class="card-body" style="margin: 15px;">
            <ul class="nav nav-tabs ipd-tabs" id="ipdPanelTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab_admission" data-url="<?= site_url('billing/ipd/panel/' . (int) ($ipd->id ?? 0) . '/tab/admission') ?>" type="button" role="tab">Admission Info</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_bed" data-url="<?= site_url('billing/ipd/panel/' . (int) ($ipd->id ?? 0) . '/tab/bed-assign') ?>" type="button" role="tab">Bed Assign</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_charges" data-url="<?= site_url('billing/ipd/panel/' . (int) ($ipd->id ?? 0) . '/tab/ipd-charges') ?>" type="button" role="tab">IPD Charges</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_package" data-url="<?= site_url('billing/ipd/panel/' . (int) ($ipd->id ?? 0) . '/tab/package') ?>" type="button" role="tab">Package</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_diagnosis" data-url="<?= site_url('billing/ipd/panel/' . (int) ($ipd->id ?? 0) . '/tab/diagnosis-charges') ?>" type="button" role="tab">Diagnosis Charges</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_payments" data-url="<?= site_url('billing/ipd/panel/' . (int) ($ipd->id ?? 0) . '/tab/payments') ?>" type="button" role="tab">Payments</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_medical" data-url="<?= site_url('billing/ipd/panel/' . (int) ($ipd->id ?? 0) . '/tab/medical-bills') ?>" type="button" role="tab">Medical Bills</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_bill" data-url="<?= site_url('billing/ipd/panel/' . (int) ($ipd->id ?? 0) . '/tab/bill-details') ?>" type="button" role="tab">Bill Details</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_discharge" data-url="<?= site_url('billing/ipd/panel/' . (int) ($ipd->id ?? 0) . '/tab/discharge-process') ?>" type="button" role="tab">Discharge Process</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_documents" data-url="<?= site_url('billing/ipd/panel/' . (int) ($ipd->id ?? 0) . '/tab/documents') ?>" type="button" role="tab">Documents</button>
                </li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab_admission" role="tabpanel">
                    <div class="ipd-tab-panel" id="tab_admission_content">Loading...</div>
                </div>
                <div class="tab-pane fade" id="tab_bed" role="tabpanel">
                    <div class="ipd-tab-panel" id="tab_bed_content">Loading...</div>
                </div>
                <div class="tab-pane fade" id="tab_charges" role="tabpanel">
                    <div class="ipd-tab-panel" id="tab_charges_content">Loading...</div>
                </div>
                <div class="tab-pane fade" id="tab_package" role="tabpanel">
                    <div class="ipd-tab-panel" id="tab_package_content">Loading...</div>
                </div>
                <div class="tab-pane fade" id="tab_diagnosis" role="tabpanel">
                    <div class="ipd-tab-panel" id="tab_diagnosis_content">Loading...</div>
                </div>
                <div class="tab-pane fade" id="tab_payments" role="tabpanel">
                    <div class="ipd-tab-panel" id="tab_payments_content">Loading...</div>
                </div>
                <div class="tab-pane fade" id="tab_medical" role="tabpanel">
                    <div class="ipd-tab-panel" id="tab_medical_content">Loading...</div>
                </div>
                <div class="tab-pane fade" id="tab_bill" role="tabpanel">
                    <div class="ipd-tab-panel" id="tab_bill_content">Loading...</div>
                </div>
                <div class="tab-pane fade" id="tab_discharge" role="tabpanel">
                    <div class="ipd-tab-panel" id="tab_discharge_content">Loading...</div>
                </div>
                <div class="tab-pane fade" id="tab_documents" role="tabpanel">
                    <div class="ipd-tab-panel" id="tab_documents_content">Loading...</div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="payModal" tabindex="-1" aria-labelledby="payModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="payModalLabel">Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="payModal-bodyc"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="payModal_ded" tabindex="-1" aria-labelledby="payModalDedLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="payModalDedLabel">Payment Deduction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="payModal_ded-bodyc"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="payModal_TPA" tabindex="-1" aria-labelledby="payModalTpaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="payModalTpaLabel">Payment TPA and Others</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="payModal_TPA-bodyc"></div>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        function loadTabContent(tabButton) {
            var url = tabButton.getAttribute('data-url');
            var target = tabButton.getAttribute('data-bs-target');
            if (!url || !target) {
                return;
            }
            var panel = document.querySelector(target + ' .ipd-tab-panel');
            if (!panel) {
                return;
            }
            panel.innerHTML = 'Loading...';
            load_form_div(url, panel.id);
        }

        var tabButtons = document.querySelectorAll('#ipdPanelTabs button[data-url]');
        tabButtons.forEach(function(button) {
            button.addEventListener('shown.bs.tab', function() {
                loadTabContent(button);
            });
        });

        var firstTab = document.querySelector('#ipdPanelTabs button[data-url]');
        if (firstTab) {
            loadTabContent(firstTab);
        }

        if (window.jQuery) {
            $(document).on('ipd:admission-updated', function(event, detail) {
                detail = detail || {};
                var admitDate = document.getElementById('ipd-panel-admit-date');
                var dischargeDate = document.getElementById('ipd-panel-discharge-date');
                var noDays = document.getElementById('ipd-panel-no-days');

                if (admitDate && typeof detail.admit_date === 'string') {
                    admitDate.textContent = detail.admit_date;
                }
                if (dischargeDate && typeof detail.discharge_date === 'string') {
                    dischargeDate.textContent = detail.discharge_date;
                }
                if (noDays && typeof detail.no_days === 'string') {
                    noDays.textContent = detail.no_days;
                }
            });
        } else {
            document.addEventListener('ipd:admission-updated', function(event) {
                var detail = event.detail || {};
                var admitDate = document.getElementById('ipd-panel-admit-date');
                var dischargeDate = document.getElementById('ipd-panel-discharge-date');
                var noDays = document.getElementById('ipd-panel-no-days');

                if (admitDate && typeof detail.admit_date === 'string') {
                    admitDate.textContent = detail.admit_date;
                }
                if (dischargeDate && typeof detail.discharge_date === 'string') {
                    dischargeDate.textContent = detail.discharge_date;
                }
                if (noDays && typeof detail.no_days === 'string') {
                    noDays.textContent = detail.no_days;
                }
            });
        }

        var payModal = document.getElementById('payModal');
        if (payModal) {
            payModal.addEventListener('shown.bs.modal', function(event) {
                var button = event.relatedTarget;
                var ipdId = button ? button.getAttribute('data-ipd-id') : '';
                if (ipdId) {
                    load_form_div('<?= site_url('billing/ipd/payment/modal') ?>/' + ipdId, 'payModal-bodyc');
                }
            });
        }

        var payModalDed = document.getElementById('payModal_ded');
        if (payModalDed) {
            payModalDed.addEventListener('shown.bs.modal', function(event) {
                var button = event.relatedTarget;
                var ipdId = button ? button.getAttribute('data-ipd-id') : '';
                if (ipdId) {
                    load_form_div('<?= site_url('billing/ipd/payment/deduction-modal') ?>/' + ipdId, 'payModal_ded-bodyc');
                }
            });
        }

        var payModalTpa = document.getElementById('payModal_TPA');
        if (payModalTpa) {
            payModalTpa.addEventListener('shown.bs.modal', function(event) {
                var button = event.relatedTarget;
                var ipdId = button ? button.getAttribute('data-ipd-id') : '';
                if (ipdId) {
                    load_form_div('<?= site_url('billing/ipd/payment/tpa-modal') ?>/' + ipdId, 'payModal_TPA-bodyc');
                }
            });
        }

        // Convert to IPD Modal handling
        var convertModal = document.getElementById('convertToIpdModal');
        if (convertModal) {
            convertModal.addEventListener('shown.bs.modal', function() {
                var $bedSelect = $('#convert_target_bed_id');
                var $docSelect = $('#convert_doctor_id');

                $bedSelect.html('<option value="">Loading available beds...</option>');

                $.getJSON('<?= site_url('billing/ipd/available-ipd-beds') ?>', function(resp) {
                    if (resp && resp.status) {
                        var bedHtml = '<option value="">-- Select Target Inpatient Bed --</option>';
                        var groupedBeds = {};
                        (resp.beds || []).forEach(function(b) {
                            var wName = b.ward_name ? (b.ward_name + ' (' + (b.ward_type || 'General') + ')') : 'General Wards';
                            if (!groupedBeds[wName]) {
                                groupedBeds[wName] = [];
                            }
                            groupedBeds[wName].push(b);
                        });

                        for (var group in groupedBeds) {
                            bedHtml += '<optgroup label="' + group + '">';
                            groupedBeds[group].forEach(function(b) {
                                bedHtml += '<option value="' + b.id + '">Bed ' + (b.bed_number || b.bed_code) + ' - ' + group + '</option>';
                            });
                            bedHtml += '</optgroup>';
                        }
                        $bedSelect.html(bedHtml);

                        var docHtml = '<option value="">Keep current attending doctor</option>';
                        (resp.doctors || []).forEach(function(d) {
                            docHtml += '<option value="' + d.id + '">' + d.doc_name + '</option>';
                        });
                        $docSelect.html(docHtml);
                    } else {
                        $bedSelect.html('<option value="">Failed to load beds</option>');
                    }
                }).fail(function() {
                    $bedSelect.html('<option value="">Unable to load beds</option>');
                });
            });

            $('#convertIpdForm').on('submit', function(e) {
                e.preventDefault();
                var $btn = $('#btnConfirmConvertToIpd');
                var bedId = $('#convert_target_bed_id').val();
                var reason = $('#convert_reason').val();

                if (!bedId) {
                    alert('Please select a target Inpatient Bed.');
                    return;
                }
                if (!reason || reason.trim() === '') {
                    alert('Please specify the clinical reason for conversion to IPD.');
                    return;
                }

                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Converting...');

                $.post('<?= site_url('billing/ipd/convert-to-ipd') ?>', $(this).serialize(), function(res) {
                    $btn.prop('disabled', false).html('<i class="bi bi-check-circle-fill"></i> Confirm &amp; Transfer to IPD');
                    if (res && res.status) {
                        var modalInstance = bootstrap.Modal.getInstance(convertModal);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                        if (typeof window.notify === 'function') {
                            window.notify('success', 'IPD Conversion', res.message || 'Converted to Inpatient successfully');
                        } else {
                            alert(res.message || 'Converted to Inpatient successfully');
                        }
                        load_form_div('<?= base_url('billing/ipd/panel/' . (int) ($ipd->id ?? 0)) ?>', 'maindiv', 'IPD Panel');
                    } else {
                        alert(res.message || 'Failed to convert to IPD');
                    }
                }, 'json').fail(function(xhr) {
                    $btn.prop('disabled', false).html('<i class="bi bi-check-circle-fill"></i> Confirm &amp; Transfer to IPD');
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Server error occurred during conversion';
                    alert(msg);
                });
            });
        }
    })();
</script>

<!-- Convert / Escalate to IPD Modal -->
<div class="modal fade" id="convertToIpdModal" tabindex="-1" aria-labelledby="convertToIpdModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="convertToIpdModalLabel">
                    <i class="bi bi-arrow-up-right-circle-fill"></i> Escalate / Convert to Inpatient (IPD)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2" style="font-size:13px;">
                    <i class="bi bi-info-circle-fill"></i>
                    Converting this patient to <strong>Inpatient (IPD)</strong> will transfer the patient to an IPD ward/bed and update their encounter status.
                    <strong>All current Day Care / Emergency bills, orders, and medications will automatically remain unified under this patient's invoice.</strong>
                </div>

                <form id="convertIpdForm">
                    <input type="hidden" name="ipd_id" value="<?= (int) ($ipd->id ?? 0) ?>">
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label font-weight-bold">Select Inpatient Ward &amp; Bed <span class="text-danger">*</span></label>
                            <select class="form-select" id="convert_target_bed_id" name="target_bed_id" required>
                                <option value="">Loading available beds...</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-weight-bold">Admitting / Primary Consultant</label>
                            <select class="form-select" id="convert_doctor_id" name="doctor_id">
                                <option value="">Keep current doctor</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Reason for Escalation / IPD Admission <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="conversion_reason" id="convert_reason" placeholder="e.g. Clinical deterioration, post-op complication, stay required > 24 hours, ICU monitoring needed" required>
                    </div>

                    <div class="card border-light-subtle mb-3">
                        <div class="card-header bg-light py-2">
                            <strong class="small text-primary"><i class="bi bi-journal-medical"></i> SBAR Clinical Handover Protocol (Medical Standard)</strong>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small mb-1"><strong>S - Situation:</strong></label>
                                    <textarea class="form-control form-control-sm" name="sbar_situation" rows="2" placeholder="Current condition, acute change, vital signs..."></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small mb-1"><strong>B - Background:</strong></label>
                                    <textarea class="form-control form-control-sm" name="sbar_background" rows="2" placeholder="Admission diagnosis, procedures done in ER/Day Care, past medical history..."></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small mb-1"><strong>A - Assessment:</strong></label>
                                    <textarea class="form-control form-control-sm" name="sbar_assessment" rows="2" placeholder="Clinical findings, lab investigations, IV access, O2 requirements..."></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small mb-1"><strong>R - Recommendation:</strong></label>
                                    <textarea class="form-control form-control-sm" name="sbar_recommendation" rows="2" placeholder="Target monitoring plan, stat medications, pending lab reviews..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" id="btnConfirmConvertToIpd">
                            <i class="bi bi-check-circle-fill"></i> Confirm &amp; Transfer to IPD
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>