<section class="content-header">
    <?php
        $opdDateRaw = (string) ($opd_master[0]->apointment_date ?? '');
        $opdDateOnly = $opdDateRaw !== '' ? date('Y-m-d', strtotime($opdDateRaw)) : '';
        $opdDateDisplay = $opdDateRaw !== '' ? date('d-m-Y', strtotime($opdDateRaw)) : '';
        $doctorName = trim((string) ($opd_master[0]->doc_name ?? 'Doctor'));
        $doctorId = (int) ($opd_master[0]->doc_id ?? $opd_master[0]->doctor_id ?? 0);

        $appointmentBackUrl = $opdDateOnly !== ''
            ? base_url('Opd/get_appointment_data') . '/' . $opdDateOnly
            : base_url('opd/appointment');

        $doctorListUrl = ($opdDateOnly !== '' && $doctorId > 0)
            ? base_url('Opd/get_appointment_list') . '/' . $doctorId . '/' . $opdDateOnly
            : '';
    ?>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1 class="mb-0">Consult</h1>
        <div class="d-flex align-items-center flex-wrap gap-1">
            <a href="javascript:load_form('<?= esc($appointmentBackUrl) ?>','OPD Appointment List');" class="btn btn-sm btn-outline-primary">OPD Appointment</a>
            <span class="text-muted">→</span>

            <?php if ($doctorListUrl !== '') { ?>
                <a href="javascript:load_form('<?= esc($doctorListUrl) ?>','OPD Appointment List');" class="btn btn-sm btn-outline-primary"><?= esc($opdDateDisplay !== '' ? $opdDateDisplay : 'Date') ?></a>
            <?php } else { ?>
                <button type="button" class="btn btn-sm btn-outline-secondary" disabled><?= esc($opdDateDisplay !== '' ? $opdDateDisplay : 'Date') ?></button>
            <?php } ?>

            <span class="text-muted">→</span>
            <button type="button" class="btn btn-sm btn-primary" disabled><?= esc($doctorName !== '' ? $doctorName : 'Doctor') ?></button>
        </div>
    </div>
</section>

<section class="content">
    <style>
        .rx-sticky-actions {
            position: sticky;
            top: 0;
            z-index: 10;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: .5rem;
            padding: .75rem;
            margin-bottom: 1rem;
        }
        .rx-meta-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: .5rem;
            padding: .75rem;
        }
        .rx-counter {
            font-size: .8rem;
            color: #6c757d;
            text-align: right;
            margin-top: .25rem;
        }
        .rx-save-status {
            font-size: .875rem;
            display: flex;
            align-items: center;
            gap: .4rem;
            min-width: 0;
            max-width: 320px;
            white-space: nowrap;
        }
        #rx_status_text {
            display: inline-block;
            min-width: 0;
            max-width: 240px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .rx-tab-btn.active {
            background: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }
        .rx-panel {
            display: block;
            border-top: 1px solid #e9ecef;
            margin-top: 1rem;
            padding-top: 1rem;
        }
        .rx-list-table td,
        .rx-list-table th {
            vertical-align: middle;
        }
        .rx-chip-wrap {
            min-height: 40px;
            border: 1px solid #dee2e6;
            border-radius: .375rem;
            padding: .35rem;
            background: #fff;
        }
        .rx-chip {
            display: inline-flex;
            align-items: center;
            background: #eef5ff;
            border: 1px solid #b6d4fe;
            color: #0a58ca;
            border-radius: 999px;
            padding: .2rem .55rem;
            margin: .15rem;
            font-size: .85rem;
        }
        .rx-chip button {
            border: 0;
            background: transparent;
            color: #0a58ca;
            margin-left: .35rem;
            line-height: 1;
        }
        .rx-predefined-advice-box {
            background: #f8f4e8;
            border: 1px solid #efe2bf;
            border-radius: .375rem;
            padding: .5rem;
        }
        .rx-predefined-advice-list {
            max-height: 180px;
            overflow-y: auto;
            border-top: 1px solid #efe2bf;
            margin-top: .5rem;
            padding-top: .25rem;
        }
        .rx-alert-item {
            border-left: 4px solid #0d6efd;
            background: #f8fbff;
            padding: .5rem .6rem;
            margin-bottom: .5rem;
            border-radius: .25rem;
        }
        .rx-ai-btn {
            float: right;
            margin-top: -2px;
        }
        .rx-label-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            flex-wrap: wrap;
            margin-bottom: .25rem;
        }
        .rx-label-actions .rx-label-title {
            font-weight: 600;
            color: #344054;
        }
        .rx-complaint-actions {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }
        .rx-complaint-actions .rx-ai-btn {
            float: none;
            margin-top: 0;
        }
        .btn-complaints-rewrite {
            border-color: #2b6cb0;
            color: #2b6cb0;
            background: #eef6ff;
        }
        .btn-complaints-rewrite:hover {
            background: #2b6cb0;
            color: #fff;
        }
        .btn-complaints-mic {
            border-color: #13795b;
            color: #13795b;
            background: #ecfdf3;
            min-width: 120px;
        }
        .btn-medical-stt {
            border-color: #0f766e;
            color: #0f766e;
            background: #ecfeff;
            min-width: 120px;
        }
        .btn-medical-stt:hover {
            background: #0f766e;
            color: #fff;
        }
        .btn-complaints-mic:hover {
            background: #13795b;
            color: #fff;
        }
        .rx-scan-banner {
            display: none;
            border: 1px solid #b6d4fe;
            background: #eef5ff;
            color: #0a58ca;
            border-radius: .35rem;
            padding: .45rem .65rem;
            margin-top: .5rem;
            font-size: .86rem;
            position: sticky;
            top: 62px;
            z-index: 9;
        }
        .rx-scan-banner .btn {
            padding: .1rem .45rem;
            font-size: .78rem;
        }
        .rx-scan-highlight {
            border-color: #86b7fe !important;
            box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.12);
        }
        .rx-prefilled-morbidity {
            background: #f8fbff;
            border: 1px solid #d6e8ff;
            border-radius: .25rem;
            padding: .1rem .35rem;
        }
        .rx-history-list {
            max-height: 560px;
            overflow-y: auto;
            padding-right: .15rem;
        }
        .rx-history-image-wrap {
            width: 84px;
            flex: 0 0 84px;
        }
        .rx-history-preview {
            width: 84px;
            height: 72px;
            object-fit: cover;
            border: 1px solid #dfe3e7;
            border-radius: 4px;
            display: block;
        }
        .rx-history-item.expanded .rx-history-image-wrap {
            width: 100%;
            flex: 0 0 100%;
            max-width: 100%;
        }
        .rx-history-item.expanded .rx-history-preview {
            width: 100%;
            height: 320px;
            object-fit: contain;
            background: #f8f9fa;
        }
        @media (min-width: 992px) {
            .rx-two-panel {
                --rx-panel-height: calc(100vh - 170px);
            }
            .rx-left-panel,
            .rx-right-panel {
                height: var(--rx-panel-height);
                overflow-y: auto;
                overscroll-behavior: contain;
                padding-right: .25rem;
            }
        }
    </style>

    <form class="form1">
        <?= csrf_field() ?>
        <input type="hidden" id="opd_id" value="<?= esc($opd_id) ?>">
        <input type="hidden" id="opd_session_id" value="<?= esc($opd_prescription[0]->id ?? 0) ?>">

        <div class="rx-sticky-actions d-flex justify-content-between align-items-center">
            <div class="rx-save-status">
                <span id="rx_status_badge" class="badge bg-secondary">Not Saved</span>
                <span id="rx_status_text" class="ms-2 text-muted">No local changes</span>
            </div>
            <div>
                <select class="form-select form-select-sm d-inline-block" id="speech_mode_select" style="width:auto;min-width:150px;">
                    <option value="auto">Mic Mode: Auto</option>
                    <option value="browser">Mic Mode: Browser</option>
                    <option value="server">Mic Mode: Server</option>
                </select>
                <button type="button" class="btn btn-outline-warning btn-sm" id="btn_new_opd_session_reset" title="Start clean OPD session view (clear Advice/Investigation/Medicine list)">New OPD Session</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_restore_draft">Restore Draft</button>
                <button type="button" class="btn btn-outline-success btn-sm" id="btn_local_clinical_assist" title="Local rule-based support using complaints + vitals">Clinical Assist (Local)</button>
                <button type="button" class="btn btn-outline-primary btn-sm" id="btn_ai_full_draft" title="Use complete OPD data to generate draft notes">AI Draft (Full Form)</button>
                <button type="button" class="btn btn-primary btn-sm" id="btn_save_rx">Save Consult</button>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-dark dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">🖨 Print</button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">OPD Head</h6></li>
                        <li><a class="dropdown-item" href="<?= base_url('opd_print/opd_PDF_print') ?>/<?= esc($opd_id) ?>" target="_blank">Print OPD HEAD in Letter Head</a></li>
                        <li><a class="dropdown-item" href="<?= base_url('opd_print/opd_Cont_print') ?>/<?= esc($opd_id) ?>" target="_blank">Print Cont. Paper</a></li>
                        <li><a class="dropdown-item" href="<?= base_url('opd_print/opd_blank_print') ?>/<?= esc($opd_id) ?>" target="_blank">Print OPD HEAD in Blank Page</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header">Prescription (Rx)</h6></li>
                        <li><button type="button" class="dropdown-item text-danger" id="btn_print0">Print Rx in Plain Paper</button></li>
                        <li><button type="button" class="dropdown-item text-success" id="btn_print1">Print Rx Blank LetterHead</button></li>
                        <li><button type="button" class="dropdown-item text-warning" id="btn_print2">Print Rx With OPD Head LetterHead</button></li>
                    </ul>
                </div>
            </div>
        </div>
        <div id="rx_scan_banner" class="rx-scan-banner"></div>

        <div class="row g-3 rx-two-panel">
            <div class="col-lg-4 rx-left-panel">
                <div class="rx-meta-box mb-3">
                    <div><strong>Name:</strong> <?= esc($patient_master[0]->p_fname ?? '') ?></div>
                    <div><strong>UHID:</strong> <?= esc($patient_master[0]->p_code ?? '') ?></div>
                    <div><strong>Age:</strong> <?= esc($patient_master[0]->str_age ?? '') ?></div>
                    <div><strong>Gender:</strong> <?= esc($patient_master[0]->xgender ?? '') ?></div>
                    <div class="mt-2">
                        <label class="form-label mb-1"><strong>ABHA Address:</strong></label>
                        <input type="text" class="form-control form-control-sm" id="abha_address" maxlength="18"
                            value="<?= esc($patient_master[0]->abha_address ?? $patient_master[0]->abha ?? '') ?>"
                            placeholder="Enter ABHA Address">
                    </div>
                    <div><strong>OPD:</strong> <?= esc($opd_master[0]->opd_code ?? '') ?></div>
                    <div><strong>Date:</strong> <?= esc($opd_master[0]->apointment_date ?? '') ?></div>
                    <div><strong>Doctor:</strong> <?= esc($opd_master[0]->doc_name ?? '') ?></div>
                </div>
                <div class="card">
                    <div class="card-header"><strong>Quick Tips</strong></div>
                    <div class="card-body">
                        <ul class="mb-0 ps-3">
                            <li>Use <strong>Ctrl+S</strong> to save quickly.</li>
                            <li>Draft auto-saves locally every few seconds.</li>
                            <li>Unsaved changes are warned before leaving.</li>
                        </ul>
                    </div>
                </div>
                <div class="card mt-3">
                    <div class="card-header"><strong>Patient History Alerts</strong></div>
                    <div class="card-body">
                        <?php if (!empty($history_alerts ?? [])) { ?>
                            <?php foreach (($history_alerts ?? []) as $historyAlert) { ?>
                                <div class="rx-alert-item">
                                    <strong><?= esc($historyAlert['type'] ?? 'History') ?>:</strong>
                                    <div><?= esc($historyAlert['message'] ?? '') ?></div>
                                </div>
                            <?php } ?>
                        <?php } else { ?>
                            <div class="text-muted">No significant prior alerts found.</div>
                        <?php } ?>
                    </div>
                </div>
                <div class="card mt-3">
                    <div class="card-header"><strong>Remarks</strong></div>
                    <div class="card-body">
                        <?php if (!empty($left_remarks ?? [])) { ?>
                            <?php foreach (($left_remarks ?? []) as $remarkRow) { ?>
                                <div class="rx-alert-item">
                                    <div><?= esc($remarkRow['remark'] ?? '') ?></div>
                                    <small class="text-muted"><?= esc($remarkRow['insert_by'] ?? '') ?> <?= esc($remarkRow['insert_time'] ?? '') ?></small>
                                </div>
                            <?php } ?>
                        <?php } else { ?>
                            <div class="text-muted">No remarks available.</div>
                        <?php } ?>
                    </div>
                </div>
                <div class="card mt-3" id="ai_full_draft_preview_card" style="display:none;">
                    <div class="card-header"><strong>AI Draft Preview (Full Form)</strong></div>
                    <div class="card-body" id="ai_full_draft_preview_body">
                        <div class="text-muted">No AI draft preview yet.</div>
                    </div>
                </div>
                <div class="card mt-3" id="local_clinical_assist_card" style="display:none;">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>Clinical Assist Preview (Local Rules)</strong>
                        <button type="button" class="btn btn-outline-success btn-sm" id="btn_apply_local_clinical_assist">Apply Suggestions</button>
                    </div>
                    <div class="card-body" id="local_clinical_assist_body">
                        <div class="text-muted">No local assist generated yet.</div>
                    </div>
                </div>
                <div class="card mt-3" id="fhir_history_card" style="display:none;">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>FHIR Export History (Latest 5)</strong>
                        <button type="button" class="btn btn-outline-success btn-sm" id="btn_download_fhir">Download FHIR JSON</button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0" id="tbl_fhir_history">
                                <thead>
                                    <tr><th>Generated At</th><th>Generated By</th><th>Session</th><th width="80">JSON</th></tr>
                                </thead>
                                <tbody>
                                    <tr><td colspan="4" class="text-muted px-2 py-2">No FHIR export found</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="card mt-3" id="patient_scan_history_card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>Patient Scan History (All OPDs)</strong>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_reload_patient_scan_history">Reload</button>
                    </div>
                    <div class="card-body" id="patient_scan_history_body">
                        <div class="text-muted">Loading scan history...</div>
                    </div>
                </div>
                <div class="card mt-3">
                    <div class="card-header"><strong>Extracted Scan Text</strong></div>
                    <div class="card-body">
                        <textarea id="scan_extracted_text" class="form-control form-control-sm" rows="5" placeholder="ECG/Lab scan text will appear here..."></textarea>
                        <div class="mt-2 d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-success" id="btn_scan_to_finding">Copy to Finding</button>
                            <button type="button" class="btn btn-sm btn-outline-success" id="btn_scan_to_investigation">Copy to Investigation</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 rx-right-panel">
                <div class="card mb-3">
                    <div class="card-header">
                        <strong>Single Screen Consult</strong>
                        <small class="text-muted ms-2">(Old familiar fields + AI assist)</small>
                    </div>

                    <div class="card-body">
                        <div id="panel_notes" class="rx-panel">
                            <div class="mb-3">
                                <h6 class="mb-2">General Examination</h6>
                                <div class="row g-2">
                                    <div class="col-md-2"><input class="form-control form-control-sm rx-instant" id="pulse" placeholder="Pulse" value="<?= esc($opd_prescription[0]->pulse ?? '') ?>"></div>
                                    <div class="col-md-2"><input class="form-control form-control-sm rx-instant" id="spo2" placeholder="SPO2" value="<?= esc($opd_prescription[0]->spo2 ?? '') ?>"></div>
                                    <div class="col-md-2"><input class="form-control form-control-sm rx-instant" id="bp" placeholder="BP Systolic" value="<?= esc($opd_prescription[0]->bp ?? '') ?>"></div>
                                    <div class="col-md-2"><input class="form-control form-control-sm rx-instant" id="diastolic" placeholder="Diastolic" value="<?= esc($opd_prescription[0]->diastolic ?? '') ?>"></div>
                                    <div class="col-md-2"><input class="form-control form-control-sm rx-instant" id="temp" placeholder="Temp" value="<?= esc($opd_prescription[0]->temp ?? '') ?>"></div>
                                    <div class="col-md-2"><input class="form-control form-control-sm rx-instant" id="rr_min" placeholder="RR/min" value="<?= esc($opd_prescription[0]->rr_min ?? '') ?>"></div>
                                    <div class="col-md-2"><input class="form-control form-control-sm rx-instant" id="height" placeholder="Height" value="<?= esc($opd_prescription[0]->height ?? '') ?>"></div>
                                    <div class="col-md-2"><input class="form-control form-control-sm rx-instant" id="weight" placeholder="Weight" value="<?= esc($opd_prescription[0]->weight ?? '') ?>"></div>
                                    <div class="col-md-2"><input class="form-control form-control-sm rx-instant" id="waist" placeholder="Waist" value="<?= esc($opd_prescription[0]->waist ?? '') ?>"></div>
                                    <div class="col-md-2"><input class="form-control form-control-sm rx-instant" id="pallor" placeholder="Pallor" value="<?= esc($opd_prescription[0]->pallor ?? '') ?>"></div>
                                    <div class="col-md-2"><input class="form-control form-control-sm rx-instant" id="icterus" placeholder="Icterus" value="<?= esc($opd_prescription[0]->Icterus ?? '') ?>"></div>
                                    <div class="col-md-2"><input class="form-control form-control-sm rx-instant" id="cyanosis" placeholder="Cyanosis" value="<?= esc($opd_prescription[0]->cyanosis ?? '') ?>"></div>
                                    <div class="col-md-2"><input class="form-control form-control-sm rx-instant" id="clubbing" placeholder="Clubbing" value="<?= esc($opd_prescription[0]->clubbing ?? '') ?>"></div>
                                    <div class="col-md-2"><input class="form-control form-control-sm rx-instant" id="edema" placeholder="Edema" value="<?= esc($opd_prescription[0]->edema ?? '') ?>"></div>
                                </div>
                                <div class="row g-3 mt-2">
                                    <div class="col-md-12">
                                        <h6 class="mb-2">Pain Measurement Scale</h6>
                                        <input type="hidden" id="pain_value" value="<?= esc($opd_prescription[0]->pain_value ?? '') ?>">
                                        <?php $painValue = (string) ($opd_prescription[0]->pain_value ?? ''); ?>
                                        <div class="btn-group flex-wrap" role="group" aria-label="Pain Measurement Scale">
                                            <input type="radio" class="btn-check pain-option" name="options-pain" id="pain_0" value="0" <?= $painValue === '0' ? 'checked' : '' ?>>
                                            <label class="btn btn-sm btn-outline-success" for="pain_0">No Pain</label>

                                            <input type="radio" class="btn-check pain-option" name="options-pain" id="pain_1" value="1" <?= $painValue === '1' ? 'checked' : '' ?>>
                                            <label class="btn btn-sm btn-outline-primary" for="pain_1">Mild Pain</label>

                                            <input type="radio" class="btn-check pain-option" name="options-pain" id="pain_2" value="2" <?= $painValue === '2' ? 'checked' : '' ?>>
                                            <label class="btn btn-sm btn-outline-info" for="pain_2">Moderate</label>

                                            <input type="radio" class="btn-check pain-option" name="options-pain" id="pain_3" value="3" <?= $painValue === '3' ? 'checked' : '' ?>>
                                            <label class="btn btn-sm btn-outline-warning" for="pain_3">Intense</label>

                                            <input type="radio" class="btn-check pain-option" name="options-pain" id="pain_4" value="4" <?= $painValue === '4' ? 'checked' : '' ?>>
                                            <label class="btn btn-sm btn-outline-danger" for="pain_4">Worst Pain Possible</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="border rounded p-2 h-100">
                                            <h6 class="mb-2">Complication</h6>
                                            <div class="small">
                                                <label class="d-block mb-1"><input type="checkbox" id="pregnancy" <?= !empty($opd_prescription[0]->pregnancy) ? 'checked' : '' ?>> Pregnancy</label>
                                                <label class="d-block mb-1"><input type="checkbox" id="lactation" <?= !empty($opd_prescription[0]->lactation) ? 'checked' : '' ?>> Lactation</label>
                                                <label class="d-block mb-1"><input type="checkbox" id="liver_insufficiency" <?= !empty($opd_prescription[0]->liver_insufficiency) ? 'checked' : '' ?>> Liver Insufficiency</label>
                                                <label class="d-block mb-1"><input type="checkbox" id="renal_insufficiency" <?= !empty($opd_prescription[0]->renal_insufficiency) ? 'checked' : '' ?>> Renal Insufficiency</label>
                                                <label class="d-block mb-1"><input type="checkbox" id="pulmonary_insufficiency" <?= !empty($opd_prescription[0]->pulmonary_insufficiency) ? 'checked' : '' ?>> Pulmonary Insufficiency</label>
                                                <label class="d-block mb-1"><input type="checkbox" id="corona_suspected" <?= !empty($opd_prescription[0]->corona_suspected) ? 'checked' : '' ?>> Corona Suspected</label>
                                                <label class="d-block"><input type="checkbox" id="dengue" <?= !empty($opd_prescription[0]->dengue) ? 'checked' : '' ?>> Dengue</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="border rounded p-2 h-100">
                                            <h6 class="mb-2">Addiction(if any)</h6>
                                            <div class="small">
                                                <label class="d-block mb-1"><input type="checkbox" id="is_smoking" <?= !empty(($addiction_flags['is_smoking'] ?? ($patient_master[0]->is_smoking ?? 0))) ? 'checked' : '' ?>> Smoking</label>
                                                <label class="d-block mb-1"><input type="checkbox" id="is_alcohol" <?= !empty(($addiction_flags['is_alcohol'] ?? ($patient_master[0]->is_alcohol ?? 0))) ? 'checked' : '' ?>> Alcohol</label>
                                                <label class="d-block"><input type="checkbox" id="is_drug_abuse" <?= !empty(($addiction_flags['is_drug_abuse'] ?? ($patient_master[0]->is_drug_abuse ?? 0))) ? 'checked' : '' ?>> Drug Abuse</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="border rounded p-2">
                                            <h6 class="mb-2">Drug Allergy / ADR (NABH)</h6>
                                            <div class="small text-danger mb-2">Required: Drug Allergy Status is mandatory. If status is Known, Drug Allergy Details are mandatory.</div>
                                            <div class="row g-2">
                                                <div class="col-md-4">
                                                    <label class="form-label mb-1">Drug Allergy Status <span class="text-danger">*</span></label>
                                                    <?php $allergyStatus = strtolower(trim((string) ($opd_prescription[0]->drug_allergy_status ?? ''))); ?>
                                                    <select class="form-select form-select-sm rx-instant" id="drug_allergy_status">
                                                        <option value="">Select status</option>
                                                        <option value="Known" <?= $allergyStatus === 'known' ? 'selected' : '' ?>>Known</option>
                                                        <option value="Allergies Not Known" <?= in_array($allergyStatus, ['allergies not known', 'not known'], true) ? 'selected' : '' ?>>Allergies Not Known</option>
                                                        <option value="No Known Drug Allergy" <?= in_array($allergyStatus, ['no known drug allergy', 'none'], true) ? 'selected' : '' ?>>No Known Drug Allergy</option>
                                                    </select>
                                                    <div id="drug_allergy_status_error" class="invalid-feedback d-block" style="display:none;"></div>
                                                </div>
                                                <div class="col-md-8">
                                                    <label class="form-label mb-1">Drug Allergy Details <span class="text-danger">*</span> <span class="small text-muted">(when status = Known)</span></label>
                                                    <input type="text" class="form-control form-control-sm rx-instant" id="drug_allergy_details" placeholder="e.g. Penicillin rash, NSAID gastritis" value="<?= esc($opd_prescription[0]->drug_allergy_details ?? '') ?>">
                                                    <div id="drug_allergy_details_error" class="invalid-feedback d-block" style="display:none;"></div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label mb-1">ADR History</label>
                                                    <input type="text" class="form-control form-control-sm rx-instant" id="adr_history" placeholder="Previous adverse drug reaction details" value="<?= esc($opd_prescription[0]->adr_history ?? '') ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label mb-1">Current Medications</label>
                                                    <input type="text" class="form-control form-control-sm rx-instant" id="current_medications" placeholder="Current/ongoing medicines" value="<?= esc($opd_prescription[0]->current_medications ?? '') ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="border rounded p-2">
                                            <h6 class="mb-2">Co-Morbidities</h6>
                                            <?php if (!empty($co_morbidities ?? [])) { ?>
                                                <div class="d-flex flex-wrap gap-2 small">
                                                    <?php foreach (($co_morbidities ?? []) as $morbidityRow) { ?>
                                                        <label class="me-2 <?= (!empty($co_morbidities_prefilled_from_history ?? false) && !empty($morbidityRow['selected'])) ? 'rx-prefilled-morbidity' : '' ?>">
                                                            <input type="checkbox" name="morbidities" class="morbidities-item" value="<?= esc($morbidityRow['id'] ?? 0) ?>" data-name="<?= esc($morbidityRow['name'] ?? '') ?>" <?= !empty($morbidityRow['selected']) ? 'checked' : '' ?>>
                                                            <?= esc($morbidityRow['name'] ?? '') ?>
                                                        </label>
                                                    <?php } ?>
                                                </div>
                                                <?php if (!empty($co_morbidities_prefilled_from_history ?? false)) { ?>
                                                    <div class="small text-muted mt-1">Prefilled from patient history.</div>
                                                <?php } ?>
                                            <?php } else { ?>
                                                <div class="text-muted small">No co-morbidities master data found.</div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <?php $isFemalePatient = strtolower((string) ($patient_master[0]->xgender ?? '')) === 'female'; ?>
                                    <?php if ($isFemalePatient) { ?>
                                        <div class="col-md-12">
                                            <div class="border rounded p-2">
                                                <h6 class="mb-2">Women Related Problems</h6>
                                                <div class="row g-2 mb-2">
                                                    <div class="col-md-4">
                                                        <input type="number" min="0" step="1" class="form-control form-control-sm rx-instant" id="women_lmp" placeholder="LMP (No. of days before)" value="<?= esc($opd_prescription[0]->women_lmp ?? '') ?>">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <input type="text" class="form-control form-control-sm rx-instant" id="women_last_baby" placeholder="Last Baby" value="<?= esc($opd_prescription[0]->women_last_baby ?? '') ?>">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <input type="text" class="form-control form-control-sm rx-instant" id="women_pregnancy_related" placeholder="Pregnancy Related" value="<?= esc($opd_prescription[0]->women_pregnancy_related ?? '') ?>">
                                                    </div>
                                                </div>
                                                <textarea class="form-control form-control-sm rx-field" id="women_related_problems" rows="2" maxlength="4000" placeholder="Enter women related problems if applicable..."><?= esc($opd_prescription[0]->women_related_problems ?? '') ?></textarea>
                                                <div class="rx-counter" id="counter_women_related_problems">0/4000</div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Smart Complaints Picker (English + Hinglish)</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="complaint_lookup" list="complaint_suggest" placeholder="Type: bukhar, khansi, pet dard, chakkar...">
                                    <button type="button" class="btn btn-outline-primary" id="btn_add_complaint">Add</button>
                                    <button type="button" class="btn btn-outline-success" id="btn_ai_complaint_draft">AI Draft</button>
                                </div>
                                <datalist id="complaint_suggest"></datalist>
                                <div id="complaint_chips" class="rx-chip-wrap mt-2"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Complaints
                                    <button type="button" class="btn btn-outline-primary btn-sm rx-ai-btn btn-ai-rewrite btn-complaints-rewrite" data-target="complaints" data-mode="hinglish_to_english" title="Convert Hinglish text to English">↔ Hinglish → English</button>
                                    <button type="button" class="btn btn-outline-success btn-sm rx-ai-btn" id="btn_complaints_mic" title="Speech to text for complaints">Mic</button>
                                </label>
                                <textarea class="form-control rx-field" id="complaints" rows="4" maxlength="4000"><?= esc($opd_prescription[0]->complaints ?? '') ?></textarea>
                                <div id="complaints_interim_preview" style="display:none;font-size:.8rem;color:#6c757d;padding:2px 4px;font-style:italic;"></div>
                                <div class="rx-counter" id="counter_complaints">0/4000</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Examination
                                </label>
                                <div class="d-flex flex-wrap gap-1 mb-1">
                                    <button type="button" class="btn btn-sm btn-medical-stt" data-target="finding_examinations" data-label="🎙 Med Mic">🎙 Med Mic</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-field-past" data-section="finding_examinations" data-target="finding_examinations">Past Data</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-template-load" data-section="finding_examinations" data-target="finding_examinations">Load Template</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-template-save" data-section="finding_examinations" data-target="finding_examinations">Save as Template</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-field-clear" data-target="finding_examinations">Clear</button>
                                </div>
                                <textarea class="form-control rx-field" id="finding_examinations" rows="4" maxlength="4000"><?= esc($opd_prescription[0]->Finding_Examinations ?? '') ?></textarea>
                                <div class="rx-counter" id="counter_finding_examinations">0/4000</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Diagnosis
                                </label>
                                <div class="d-flex flex-wrap gap-1 mb-1">
                                    <button type="button" class="btn btn-sm btn-medical-stt" data-target="diagnosis" data-label="🎙 Med Mic">🎙 Med Mic</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-field-past" data-section="diagnosis" data-target="diagnosis">Past Data</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-template-load" data-section="diagnosis" data-target="diagnosis">Load Template</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-template-save" data-section="diagnosis" data-target="diagnosis">Save as Template</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-field-clear" data-target="diagnosis">Clear</button>
                                </div>
                                <textarea class="form-control rx-field" id="diagnosis" rows="4" maxlength="4000"><?= esc($opd_prescription[0]->diagnosis ?? '') ?></textarea>
                                <div class="rx-counter" id="counter_diagnosis">0/4000</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Provisional Diagnosis
                                </label>
                                <div class="d-flex flex-wrap gap-1 mb-1">
                                    <button type="button" class="btn btn-sm btn-medical-stt" data-target="provisional_diagnosis" data-label="🎙 Med Mic">🎙 Med Mic</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-field-past" data-section="provisional_diagnosis" data-target="provisional_diagnosis">Past Data</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-template-load" data-section="provisional_diagnosis" data-target="provisional_diagnosis">Load Template</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-template-save" data-section="provisional_diagnosis" data-target="provisional_diagnosis">Save as Template</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-field-clear" data-target="provisional_diagnosis">Clear</button>
                                </div>
                                <textarea class="form-control rx-field" id="provisional_diagnosis" rows="4" maxlength="4000"><?= esc($opd_prescription[0]->Provisional_diagnosis ?? '') ?></textarea>
                                <div class="rx-counter" id="counter_provisional_diagnosis">0/4000</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Prescription
                                </label>
                                <div class="d-flex flex-wrap gap-1 mb-1">
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-field-past" data-section="prescriber_remarks" data-target="prescriber_remarks">Past Data</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-template-load" data-section="prescriber_remarks" data-target="prescriber_remarks">Load Template</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-template-save" data-section="prescriber_remarks" data-target="prescriber_remarks">Save as Template</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-field-clear" data-target="prescriber_remarks">Clear</button>
                                </div>
                                <textarea class="form-control rx-field" id="prescriber_remarks" rows="3" maxlength="4000"><?= esc($opd_prescription[0]->Prescriber_Remarks ?? '') ?></textarea>
                                <div class="rx-counter" id="counter_prescriber_remarks">0/4000</div>
                            </div>

                            <div class="d-none">
                                <textarea class="form-control rx-field" id="investigation" rows="2" maxlength="4000"><?= esc($opd_prescription[0]->investigation ?? '') ?></textarea>
                                <div class="rx-counter" id="counter_investigation">0/4000</div>
                            </div>
                        </div>

                        <div id="panel_advice" class="rx-panel">
                            <div class="mb-3">
                                <label class="form-label"><strong>Advice Add</strong></label>
                                <div class="d-flex flex-wrap gap-1 mb-1">
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-field-past" data-section="advice" data-target="advice">Past Data</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-template-load" data-section="advice" data-target="advice">Load Template</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-template-save" data-section="advice" data-target="advice">Save as Template</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-field-clear" data-target="advice">Clear</button>
                                </div>
                                <textarea class="form-control rx-field" id="advice" rows="3" maxlength="4000"><?= esc($opd_prescription[0]->advice ?? '') ?></textarea>
                                <div class="rx-counter" id="counter_advice">0/4000</div>
                            </div>

                            <div class="rx-predefined-advice-box mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <label class="form-label mb-0" style="color:#4e9acb;"><strong>Pre define Advise</strong></label>
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-link btn-sm p-0" id="btn_reload_predefined_advice">Refresh</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" id="btn_toggle_predefined_advice" aria-expanded="false">Show</button>
                                    </div>
                                </div>
                                <div id="predefined_advice_body" class="d-none">
                                    <div id="predefined_advice_list" class="small rx-predefined-advice-list">
                                        <div class="text-muted">Loading predefined advice...</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-10">
                                    <input type="text" class="form-control" id="advice_text" list="advice_suggest" placeholder="Type advice (e.g., Rest, Hydration, Follow-up)">
                                    <datalist id="advice_suggest"></datalist>
                                </div>
                                <div class="col-md-2 d-grid">
                                    <button type="button" class="btn btn-primary" id="btn_add_advice">Add</button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm rx-list-table" id="tbl_advice">
                                    <thead><tr><th>Advice</th><th width="90">Action</th></tr></thead>
                                    <tbody><tr><td colspan="2" class="text-muted">No advice added</td></tr></tbody>
                                </table>
                            </div>

                            <div class="row g-2 mt-3 mb-2">
                                <div class="col-md-6">
                                    <label class="form-label"><strong>Next Visit</strong></label>
                                    <input type="text" class="form-control rx-instant" id="next_visit" value="<?= esc($opd_prescription[0]->next_visit ?? '') ?>" placeholder="e.g. 5 Days">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><strong>Refer To</strong></label>
                                    <input type="text" class="form-control rx-instant" id="refer_to" value="<?= esc($opd_prescription[0]->refer_to ?? '') ?>" placeholder="Doctor / Department">
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm rx-next-visit-chip" data-value="3 Days">3 Days</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm rx-next-visit-chip" data-value="4 Days">4 Days</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm rx-next-visit-chip" data-value="5 Days">5 Days</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm rx-next-visit-chip" data-value="1 Week">1 Week</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm rx-next-visit-chip" data-value="10 Days">10 Days</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm rx-next-visit-chip" data-value="15 Days">15 Days</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm rx-next-visit-chip" data-value="20 Days">20 Days</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm rx-next-visit-chip" data-value="1 Month">1 Month</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm rx-next-visit-chip" data-value="2 Months">2 Months</button>
                            </div>
                        </div>

                        <div id="panel_investigation" class="rx-panel">
                            <div class="mb-2">
                                <label class="form-label"><strong>Advise Investigation</strong></label>
                                <textarea class="form-control form-control-sm" id="advise_investigation_notes" rows="2" placeholder="Optional notes for investigation advice..."></textarea>
                            </div>

                            <div class="mb-2">
                                <div class="d-flex flex-wrap gap-2 align-items-center" id="inv_profile_chip_wrap">
                                    <button type="button" class="btn btn-outline-primary btn-sm inv-profile-chip" data-profile="cardiac">Cardiac Profile</button>
                                    <button type="button" class="btn btn-outline-success btn-sm inv-profile-chip" data-profile="viral_infection">Viral Infection</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_create_inv_profile">Create Profile</button>
                                    <select class="form-select form-select-sm" id="inv_custom_profile_select" style="max-width:260px;">
                                        <option value="">Load custom profile...</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_apply_custom_inv_profile">Apply</button>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-10">
                                    <select class="form-select" id="investigation_name_select2" style="width:100%;">
                                        <option value="">Search investigation...</option>
                                    </select>
                                    <input type="text" class="form-control d-none mt-2" id="investigation_name" list="investigation_suggest" placeholder="Type investigation name">
                                    <datalist id="investigation_suggest"></datalist>
                                </div>
                                <div class="col-md-2 d-grid gap-1">
                                    <button type="button" class="btn btn-primary" id="btn_add_investigation">Add</button>
                                    <button type="button" class="btn btn-outline-danger" id="btn_clear_investigation">Remove All</button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm rx-list-table" id="tbl_investigation">
                                    <thead><tr><th>Test Name</th><th>Code</th><th width="90">Action</th></tr></thead>
                                    <tbody><tr><td colspan="3" class="text-muted">No investigation added</td></tr></tbody>
                                </table>
                            </div>
                        </div>

                        <div id="panel_medicine" class="rx-panel">
                            <input type="hidden" id="med_item_id" value="0">
                            <div class="row g-2 mb-2">
                                <div class="col-md-8 d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_open_rx_group_modal">Rx Group</button>
                                    <button type="button" class="btn btn-outline-success btn-sm" id="btn_create_rx_group_modal" title="Create new Rx-Group">+ Rx-Group</button>
                                    <span class="small text-muted" id="rx_group_selected_name">No Rx-Group selected</span>
                                </div>
                                <div class="col-md-4 text-md-end small text-muted">
                                    Select group and preview medicines before add.
                                </div>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="med_name" list="medicine_suggest" placeholder="Medicine name">
                                    <datalist id="medicine_suggest"></datalist>
                                </div>
                                <div class="col-md-2">
                                    <input type="text" class="form-control" id="med_type" placeholder="Type">
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select" id="med_dosage">
                                        <option value="">Dose</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select" id="med_when">
                                        <option value="">When</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select" id="med_freq">
                                        <option value="">Freq</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-md-2">
                                    <input type="text" class="form-control" id="med_days" placeholder="Days">
                                </div>
                                <div class="col-md-2">
                                    <input type="text" class="form-control" id="med_qty" placeholder="Qty">
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select" id="med_where">
                                        <option value="">Where</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="med_remark" placeholder="Remark">
                                </div>
                                <div class="col-md-2 d-grid gap-2">
                                    <button type="button" class="btn btn-primary" id="btn_add_medicine">Add</button>
                                    <button type="button" class="btn btn-outline-danger" id="btn_clear_medicine">Remove All</button>
                                    <button type="button" class="btn btn-outline-secondary" id="btn_cancel_medicine_edit" style="display:none;">Cancel</button>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <button type="button" class="btn btn-outline-primary btn-sm med-scope-btn active" data-scope="active">Active</button>
                                <button type="button" class="btn btn-outline-primary btn-sm med-scope-btn" data-scope="favorite">Favorites</button>
                                <button type="button" class="btn btn-outline-primary btn-sm med-scope-btn" data-scope="all">All</button>
                                <small class="text-muted align-self-center">Search shows most-used medicines first.</small>
                            </div>
                            <div class="border rounded p-2 mb-3" id="substitute_box" style="display:none;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="small">Substitute Medicines</strong>
                                    <small class="text-muted" id="substitute_note"></small>
                                </div>
                                <div class="small text-muted" id="substitute_empty">No substitute found.</div>
                                <div class="d-flex flex-wrap gap-2" id="substitute_rows"></div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <button type="button" class="btn btn-outline-secondary btn-sm med-chip" data-target="med_when" data-value="Before Food">Before Food</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm med-chip" data-target="med_when" data-value="After Food">After Food</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm med-chip" data-target="med_when" data-value="With Food">With Food</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm med-chip" data-target="med_freq" data-value="OD">OD</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm med-chip" data-target="med_freq" data-value="BD">BD</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm med-chip" data-target="med_freq" data-value="TDS">TDS</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm med-chip" data-target="med_freq" data-value="HS">HS</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm med-chip" data-target="med_days" data-value="3">3 Days</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm med-chip" data-target="med_days" data-value="5">5 Days</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm med-chip" data-target="med_days" data-value="7">7 Days</button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm rx-list-table" id="tbl_medicine">
                                    <thead>
                                        <tr>
                                            <th>Medicine</th><th>Type</th><th>Dose</th><th>When</th><th>Freq</th><th>Where</th><th>Days</th><th>Qty</th><th>Remark</th><th width="170">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody><tr><td colspan="10" class="text-muted">No medicine added</td></tr></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="jsError text-danger mb-2"></div>
            </div>
        </div>
    </form>

    <div class="modal fade" id="rxTemplateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Load Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Suggested Templates</label>
                    <div class="d-flex flex-wrap gap-1 mb-2" id="rx_template_suggest_box">
                        <span class="text-muted small">No suggestion available.</span>
                    </div>
                    <label class="form-label">Search Template</label>
                    <input type="text" class="form-control form-control-sm mb-2" id="rx_template_search" placeholder="Search by name or content...">
                    <label class="form-label">Select Template</label>
                    <select class="form-select form-select-sm" id="rx_template_select"></select>
                    <label class="form-label mt-2">Apply Mode</label>
                    <select class="form-select form-select-sm" id="rx_template_apply_mode">
                        <option value="replace">Replace field text</option>
                        <option value="append">Append to existing text</option>
                    </select>
                    <label class="form-label mt-2">Preview</label>
                    <textarea class="form-control form-control-sm" id="rx_template_preview" rows="6" readonly></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm" id="btn_apply_template_choice">Apply</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rxTemplateSaveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rxTemplateSaveModalTitle">Save Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Template Name</label>
                    <input type="text" class="form-control form-control-sm" id="rx_template_save_name" maxlength="100">
                    <label class="form-label mt-2">Template Scope</label>
                    <select class="form-select form-select-sm" id="rx_template_save_scope">
                        <option value="doctor" selected>My Template (Doctor/Consultant only)</option>
                        <option value="master">Master Template (Visible to all users)</option>
                    </select>
                    <label class="form-label mt-2">Preview</label>
                    <textarea class="form-control form-control-sm" id="rx_template_save_preview" rows="6" readonly></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm" id="btn_save_template_choice">Save</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="invProfileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Investigation Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Profile Name</label>
                    <input type="text" class="form-control form-control-sm" id="inv_profile_name" maxlength="80" placeholder="e.g. Fever Panel">
                    <label class="form-label mt-2">Tests (comma or new line separated)</label>
                    <textarea class="form-control form-control-sm" id="inv_profile_tests" rows="6" placeholder="CBC, ESR, CRP"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm" id="btn_save_inv_profile">Save Profile</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rxGroupSelectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Rx Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <input type="text" class="form-control form-control-sm" id="rx_group_search" placeholder="Search Rx-Group...">
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex gap-1 justify-content-md-end">
                                <button type="button" class="btn btn-outline-primary btn-sm rx-group-scope-btn active" data-scope="active">Active</button>
                                <button type="button" class="btn btn-outline-primary btn-sm rx-group-scope-btn" data-scope="favorite">Favorites</button>
                                <button type="button" class="btn btn-outline-primary btn-sm rx-group-scope-btn" data-scope="all">All</button>
                            </div>
                        </div>
                    </div>
                    <div id="rx_group_modal_list" class="d-flex flex-wrap gap-2">
                        <div class="text-muted small">Loading Rx groups...</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Rx-Group Modal -->
    <div class="modal fade" id="rxGroupCreateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Rx-Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Left: Group info -->
                        <div class="col-md-5">
                            <div class="fw-semibold mb-2">Group Info</div>
                            <div class="mb-2">
                                <label class="form-label">Rx-Group Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="crgc_name" maxlength="150" placeholder="e.g. Fever Basic">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Complaints</label>
                                <input type="text" class="form-control form-control-sm" id="crgc_complaints" maxlength="1000">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Diagnosis</label>
                                <input type="text" class="form-control form-control-sm" id="crgc_diagnosis" maxlength="1000">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Investigation</label>
                                <input type="text" class="form-control form-control-sm" id="crgc_investigation" maxlength="1000">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Finding Examinations</label>
                                <input type="text" class="form-control form-control-sm" id="crgc_finding" maxlength="1000">
                            </div>
                        </div>
                        <!-- Right: Add medicine -->
                        <div class="col-md-7">
                            <div class="fw-semibold mb-2">Add Medicines</div>
                            <div class="mb-2">
                                <input type="text" class="form-control form-control-sm" id="crgc_med_name" list="crgc_med_suggest" placeholder="Medicine name">
                                <datalist id="crgc_med_suggest"></datalist>
                            </div>
                            <div class="mb-2">
                                <input type="text" class="form-control form-control-sm" id="crgc_med_type" placeholder="Type (TAB/CAP/SYR...)">
                            </div>
                            <div class="row g-1 mb-2">
                                <div class="col-4"><select class="form-select form-select-sm" id="crgc_med_dose"><option value="">Dose</option></select></div>
                                <div class="col-4"><select class="form-select form-select-sm" id="crgc_med_when"><option value="">When</option></select></div>
                                <div class="col-4"><select class="form-select form-select-sm" id="crgc_med_freq"><option value="">Freq</option></select></div>
                            </div>
                            <div class="row g-1 mb-2">
                                <div class="col-3"><input type="text" class="form-control form-control-sm" id="crgc_med_days" placeholder="Days"></div>
                                <div class="col-3"><input type="text" class="form-control form-control-sm" id="crgc_med_qty" placeholder="Qty"></div>
                                <div class="col-6"><select class="form-select form-select-sm" id="crgc_med_where"><option value="">Where</option></select></div>
                            </div>
                            <div class="mb-2">
                                <input type="text" class="form-control form-control-sm" id="crgc_med_remark" placeholder="Remark">
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" id="btn_crgc_add_med">+ Add Medicine</button>
                        </div>
                    </div>
                    <!-- Medicine list -->
                    <div class="mt-3" id="crgc_med_list_wrap" style="display:none;">
                        <div class="fw-semibold mb-1">Medicines to Add <span class="badge bg-secondary" id="crgc_med_count">0</span></div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0" id="crgc_med_table">
                                <thead><tr><th>Medicine</th><th>Type</th><th>Dose</th><th>When</th><th>Freq</th><th>Days</th><th>Where</th><th width="60"></th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="small text-danger mt-2" id="crgc_msg" style="display:none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm" id="btn_crgc_save">Save Rx-Group</button>
                    <button type="button" class="btn btn-success btn-sm" id="btn_crgc_save_apply">Save &amp; Apply</button>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    var isDirty = false;
    var isSaving = false;
    var isFhirHistoryLoaded = false;
    var autoSaveTimer = null;
    var latestLocalAssist = null;
    var complaintSuggestions = [];
    var selectedComplaints = [];
    var complaintsMicMode = 'off';
    var complaintsSpeechRecognition = null;
    var complaintsMicActive = false;
    var complaintsMediaRecorder = null;
    var complaintsMediaStream = null;
    var complaintsMediaChunks = [];
    var complaintsBaseText = '';
    var medicalSttTarget = '';
    var medicalSttRecorder = null;
    var medicalSttStream = null;
    var medicalSttChunks = [];
    var medicalSttActive = false;
    var medicalMicMode = 'off';
    var medicalSpeechRecognition = null;
    var medicalBrowserSttActive = false;
    // Use server-side proxy when page is HTTPS (avoids mixed-content block)
    var _onHttps = (window.location.protocol === 'https:');
    var complaintsSttServerHealthUrl    = _onHttps ? '<?= base_url('stt-proxy/health') ?>' : 'http://139.59.13.39:8000/health';
    var complaintsSttServerTranscribeUrl = _onHttps ? '<?= base_url('stt-proxy') ?>'        : 'http://139.59.13.39:8000/stt/transcribe';
    var speechModePreferenceKey = 'opd_speech_mode_preference';
    var speechModePreference = (localStorage.getItem(speechModePreferenceKey) || 'browser').toString();
    var draftKey = 'opd_rx_draft_' + ($('#opd_id').val() || '0');
    var templateLoadState = { target: '', section: '', rows: [] };
    var templateSaveState = { section: '', text: '', scope: 'doctor' };
    var predefinedAdviceLoaded = false;
    var rxGroupCache = [];
    var rxGroupPreviewCache = {};
    var activeRxGroupScope = 'active';
    var rxGroupFavoritesKey = 'opd_rx_group_favorites';
    var invCustomProfileKey = 'opd_inv_custom_profiles';
    var legacyInvestigationProfiles = {};
    var legacyInvestigationProfileList = [];
    var investigationBatchActive = false;
    var activeMedicineScope = 'active';
    var medicineSuggestRows = [];
    var medicineDoseMasterCache = { dose: [], when: [], freq: [], where: [] };
    var investigationProfiles = {
        cardiac: ['ECG', 'Lipid Profile', 'CBC'],
        viral_infection: ['CBC', 'LFT', 'KFT']
    };

    function alignPanelOrderToExample() {
        var $medicine = $('#panel_medicine');
        var $advice = $('#panel_advice');
        var $investigation = $('#panel_investigation');

        if ($medicine.length && $investigation.length) {
            $medicine.insertBefore($investigation);
        }
        if ($investigation.length && $advice.length) {
            $advice.insertAfter($investigation);
        }
    }

    alignPanelOrderToExample();

    function apiGet(url, cb) {
        $.get(url, function(data) {
            cb(data || {});
        }, 'json');
    }

    function apiPost(url, payload, cb) {
        var csrf = getCsrfPair();
        payload = payload || {};
        payload[csrf.name] = csrf.value;
        $.post(url, payload, function(data) {
            updateCsrf(data);
            cb(data || {});
        }, 'json');
    }

    function renderMedicineMasterSelectOptions($select, rows, placeholder) {
        var html = '<option value="">' + $('<div>').text(placeholder || 'Select').html() + '</option>';
        (rows || []).forEach(function(row) {
            var id = (row && row.id !== undefined) ? String(row.id) : '';
            var label = (row && row.label !== undefined) ? String(row.label) : '';
            if (!id || !label) {
                return;
            }
            html += '<option value="' + $('<div>').text(id).html() + '">' + $('<div>').text(label).html() + '</option>';
        });
        $select.html(html);
    }

    function ensureMedicineMasterOption($select, value) {
        value = (value || '').toString().trim();
        if (!value) {
            return;
        }

        if ($select.find('option[value="' + value.replace(/"/g, '&quot;') + '"]').length) {
            return;
        }

        $select.append('<option value="' + $('<div>').text(value).html() + '">' + $('<div>').text(value + ' (Current)').html() + '</option>');
    }

    function setMedicineSelectByLabel(selectId, labelText) {
        var $sel = $('#' + selectId);
        if (!$sel.length) {
            return;
        }

        var probe = (labelText || '').toString().trim().toLowerCase();
        if (!probe) {
            $sel.val('').trigger('change');
            return;
        }

        var matchedVal = '';
        $sel.find('option').each(function() {
            var txt = ($(this).text() || '').toString().trim().toLowerCase();
            if (!txt) {
                return;
            }
            if (txt === probe || txt.indexOf(probe) !== -1) {
                matchedVal = ($(this).val() || '').toString();
                return false;
            }
        });

        if (!matchedVal) {
            ensureMedicineMasterOption($sel, labelText);
            matchedVal = labelText;
        }

        $sel.val(matchedVal).trigger('change');
    }

    function loadMedicineDoseMasters(done) {
        apiGet('<?= base_url('Opd_prescription/rx_group_dose_masters') ?>', function(data) {
            medicineDoseMasterCache = {
                dose: (data && data.dose) ? data.dose : [],
                when: (data && data.when) ? data.when : [],
                freq: (data && data.freq) ? data.freq : [],
                where: (data && data.where) ? data.where : []
            };

            renderMedicineMasterSelectOptions($('#med_dosage'), medicineDoseMasterCache.dose, 'Dose');
            renderMedicineMasterSelectOptions($('#med_when'), medicineDoseMasterCache.when, 'When');
            renderMedicineMasterSelectOptions($('#med_freq'), medicineDoseMasterCache.freq, 'Freq');
            renderMedicineMasterSelectOptions($('#med_where'), medicineDoseMasterCache.where, 'Where');

            if (typeof done === 'function') {
                done();
            }
        });
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

    function setComplaintsMicMode(mode, note) {
        complaintsMicMode = mode;
        var $btn = $('#btn_complaints_mic');
        if (!$btn.length) {
            return;
        }

        $btn.removeClass('btn-outline-success btn-outline-primary btn-outline-secondary btn-danger');

        if (mode === 'server') {
            $btn.addClass('btn-outline-success').prop('disabled', false).text('🎙 Server Mic');
        } else if (mode === 'browser') {
            $btn.addClass('btn-outline-primary').prop('disabled', false).text('🎤 Browser Mic');
        } else {
            $btn.addClass('btn-outline-secondary').prop('disabled', true).text('⏸ Mic Off');
        }

        if (note) {
            $('.jsError').removeClass('text-danger text-success').addClass('text-muted').text(note);
        }
    }

    function getSpeechModePreference() {
        var pref = (speechModePreference || 'auto').toString().toLowerCase();
        if (pref !== 'auto' && pref !== 'browser' && pref !== 'server') {
            pref = 'auto';
        }
        return pref;
    }

    function applySpeechModePreference() {
        var pref = getSpeechModePreference();

        if ($('#speech_mode_select').length) {
            $('#speech_mode_select').val(pref);
        }

        if (pref === 'browser') {
            if (complaintsSpeechRecognition) {
                setComplaintsMicMode('browser', 'Speech mode set to Browser.');
            } else {
                setComplaintsMicMode('off', 'Browser speech is not supported for Complaints Mic.');
            }

            if (medicalSpeechRecognition) {
                setMedicalMicMode('browser', 'Speech mode set to Browser.');
            } else {
                setMedicalMicMode('off', 'Browser speech is not supported for Med Mic.');
            }
            return;
        }

        checkComplaintsSttServer().then(function(serverUp) {
            if (pref === 'server') {
                if (serverUp) {
                    setComplaintsMicMode('server', 'Speech mode set to Server.');
                    setMedicalMicMode('server', 'Speech mode set to Server.');
                } else {
                    if (complaintsSpeechRecognition) {
                        setComplaintsMicMode('browser', 'Server unavailable. Browser mode active.');
                    } else {
                        setComplaintsMicMode('off', 'Server unavailable and browser speech unsupported.');
                    }

                    if (medicalSpeechRecognition) {
                        setMedicalMicMode('browser', 'Server unavailable. Browser mode active.');
                    } else {
                        setMedicalMicMode('off', 'Server unavailable and browser speech unsupported.');
                    }
                }
                return;
            }

            // Auto mode
            if (serverUp) {
                setComplaintsMicMode('server', 'Complaints Mic ready (server mode).');
                setMedicalMicMode('server', 'Medical Mic ready (server mode).');
            } else {
                if (complaintsSpeechRecognition) {
                    setComplaintsMicMode('browser', 'Complaints Mic ready (browser mode).');
                } else {
                    setComplaintsMicMode('off', 'Complaints Mic unavailable in this browser.');
                }

                if (medicalSpeechRecognition) {
                    setMedicalMicMode('browser', 'Medical Mic ready (browser mode).');
                } else {
                    setMedicalMicMode('off', 'Medical Mic unavailable in this browser.');
                }
            }
        }).catch(function() {
            if (complaintsSpeechRecognition) {
                setComplaintsMicMode('browser', 'Complaints Mic ready (browser mode).');
            } else {
                setComplaintsMicMode('off', 'Complaints Mic unavailable in this browser.');
            }

            if (medicalSpeechRecognition) {
                setMedicalMicMode('browser', 'Medical Mic ready (browser mode).');
            } else {
                setMedicalMicMode('off', 'Medical Mic unavailable in this browser.');
            }
        });
    }

    function setComplaintsMicListening(listening) {
        var $btn = $('#btn_complaints_mic');
        if (!$btn.length) {
            return;
        }

        complaintsMicActive = listening;
        if (listening) {
            $btn.removeClass('btn-outline-success btn-outline-primary btn-outline-secondary').addClass('btn-danger').text('⏹ Stop Mic');
        } else {
            setComplaintsMicMode(complaintsMicMode);
        }
    }

    function appendTranscriptToComplaints(text) {
        var transcript = (text || '').toString().trim();
        if (!transcript) {
            return;
        }

        var current = ($('#complaints').val() || '').toString().trim();
        var nextVal = current ? (current + '\n' + transcript) : transcript;
        $('#complaints').val(nextVal);
        refreshCounters();
        markDirty('Speech text added in complaints');
        scheduleAutoSave();
    }

    function appendTranscriptToField(targetId, text) {
        var transcript = (text || '').toString().trim();
        if (!transcript || !targetId || !$('#' + targetId).length) {
            return;
        }

        var current = ($('#' + targetId).val() || '').toString().trim();
        var nextVal = current ? (current + '\n' + transcript) : transcript;
        $('#' + targetId).val(nextVal);
        refreshCounters();
        markDirty('Medical speech text added');
        scheduleAutoSave();
    }

    function getMedicalContextForField(targetId) {
        if (targetId === 'finding_examinations') {
            return 'examination findings';
        }
        if (targetId === 'diagnosis') {
            return 'diagnosis notes';
        }
        if (targetId === 'provisional_diagnosis') {
            return 'provisional diagnosis notes';
        }
        return 'clinical notes';
    }

    function setMedicalMicButtonState(activeTarget) {
        $('.btn-medical-stt').each(function() {
            var $btn = $(this);
            var target = ($btn.data('target') || '').toString();
            var fallbackLabel = medicalMicMode === 'browser' ? '🎤 Med Mic' : '🎙 Med Mic';
            if (medicalMicMode === 'off') {
                fallbackLabel = '⏸ Med Mic';
            }
            var baseText = ($btn.data('label') || fallbackLabel).toString();

            // Always keep btn-medical-stt so the click handler always fires
            $btn.removeClass('btn-danger').prop('disabled', medicalMicMode === 'off').text(baseText);

            if (activeTarget && target === activeTarget) {
                $btn.addClass('btn-danger').text('⏹ Stop Mic');
            }
        });
    }

    function setMedicalMicMode(mode, note) {
        medicalMicMode = mode;

        var label = '⏸ Med Mic';
        if (mode === 'server') {
            label = '🎙 Med Mic';
        } else if (mode === 'browser') {
            label = '🎤 Med Mic';
        }

        $('.btn-medical-stt').each(function() {
            $(this).data('label', label).attr('data-label', label);
        });

        setMedicalMicButtonState('');

        if (note) {
            $('.jsError').removeClass('text-danger text-success').addClass('text-muted').text(note);
        }
    }

    function stopMedicalSttStream() {
        if (medicalSttStream && medicalSttStream.getTracks) {
            medicalSttStream.getTracks().forEach(function(track) {
                track.stop();
            });
        }
        medicalSttStream = null;
        medicalSttRecorder = null;
        medicalSttChunks = [];
    }

    function sendMedicalAudioToServer(audioBlob, targetId) {
        var formData = new FormData();
        formData.append('audio', audioBlob, (targetId || 'clinical') + '.webm');
        formData.append('lang', 'en-IN');
        formData.append('medical_context', getMedicalContextForField(targetId));

        return fetch(complaintsSttServerTranscribeUrl, {
            method: 'POST',
            mode: 'cors',
            body: formData
        }).then(function(response) {
            if (!response.ok) {
                return response.json().catch(function() {
                    return {};
                }).then(function(payload) {
                    var err = new Error(payload.detail || ('Server response: ' + response.status));
                    err.statusCode = response.status;
                    throw err;
                });
            }
            return response.json();
        }).then(function(payload) {
            var text = String((payload && (payload.text || payload.transcript || payload.result)) || '').trim();
            if (!text) {
                throw new Error('Empty transcript from server');
            }
            return text;
        });
    }

    function startMedicalBrowserStt(targetId) {
        if (!targetId || !$('#' + targetId).length) {
            return;
        }

        if (!medicalSpeechRecognition) {
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Medical browser speech recognition is not available.');
            return;
        }

        if (medicalSttActive && medicalSttRecorder) {
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Server Med Mic is running. Stop it first.');
            return;
        }

        if (medicalBrowserSttActive) {
            if (medicalSttTarget === targetId) {
                medicalSpeechRecognition.stop();
                $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text('Processing medical transcript...');
                return;
            }
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Medical browser mic is already recording another field. Stop it first.');
            return;
        }

        medicalSttTarget = targetId;
        medicalBrowserSttActive = true;
        setMedicalMicButtonState(targetId);
        $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text('Listening medical speech (browser mode)... click same Mic to stop.');

        try {
            medicalSpeechRecognition.start();
        } catch (e) {
            medicalBrowserSttActive = false;
            medicalSttTarget = '';
            setMedicalMicButtonState('');
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Could not start medical browser speech recognition.');
        }
    }

    function startMedicalServerStt(targetId) {
        if (!targetId || !$('#' + targetId).length) {
            return;
        }

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || typeof MediaRecorder === 'undefined') {
            if (medicalSpeechRecognition) {
                setMedicalMicMode('browser', 'Server recording is unavailable. Switched to browser mode.');
                startMedicalBrowserStt(targetId);
                return;
            }
            setMedicalMicMode('off', 'Medical speech is not available in this browser.');
            return;
        }

        if (medicalBrowserSttActive && medicalSpeechRecognition) {
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Browser Med Mic is running. Stop it first.');
            return;
        }

        if (medicalSttActive && medicalSttRecorder) {
            if (medicalSttTarget === targetId) {
                medicalSttRecorder.stop();
                $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text('Processing medical transcript...');
                return;
            }
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Medical mic is already recording another field. Stop it first.');
            return;
        }

        navigator.mediaDevices.getUserMedia({ audio: true }).then(function(stream) {
            medicalSttTarget = targetId;
            medicalSttStream = stream;
            medicalSttRecorder = new MediaRecorder(stream);
            medicalSttChunks = [];

            medicalSttRecorder.ondataavailable = function(event) {
                if (event.data && event.data.size > 0) {
                    medicalSttChunks.push(event.data);
                }
            };

            medicalSttRecorder.onstop = function() {
                var blob = new Blob(medicalSttChunks, {
                    type: (medicalSttRecorder && medicalSttRecorder.mimeType) ? medicalSttRecorder.mimeType : 'audio/webm'
                });
                var targetForTranscript = medicalSttTarget;

                stopMedicalSttStream();
                medicalSttActive = false;
                medicalSttTarget = '';
                setMedicalMicButtonState('');

                if (!blob || !blob.size || blob.size < 1024) {
                    $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Recording too short. Please record for at least 1-2 seconds.');
                    return;
                }

                sendMedicalAudioToServer(blob, targetForTranscript).then(function(transcript) {
                    appendTranscriptToField(targetForTranscript, transcript);
                    $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text('Medical speech text added.');
                }).catch(function(error) {
                    if (error && error.statusCode && error.statusCode >= 400 && error.statusCode < 500) {
                        $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Medical speech input error: ' + (error.message || 'Invalid request'));
                        return;
                    }
                    if (medicalSpeechRecognition) {
                        setMedicalMicMode('browser', 'Medical server unavailable. Switched to browser mode.');
                    } else {
                        $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Medical speech server unavailable. Please try again.');
                    }
                });
            };

            medicalSttActive = true;
            setMedicalMicButtonState(targetId);
            $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text('Recording medical speech... click same Mic to stop.');
            medicalSttRecorder.start();
        }).catch(function() {
            medicalSttActive = false;
            medicalSttTarget = '';
            setMedicalMicButtonState('');
            if (medicalSpeechRecognition) {
                setMedicalMicMode('browser', 'Microphone permission failed for server mode. Switched to browser mode.');
            } else {
                $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Microphone permission denied for medical speech.');
            }
        });
    }

    function checkComplaintsSttServer() {
        if (!window.fetch || !window.AbortController) {
            return Promise.resolve(false);
        }

        var controller = new AbortController();
        var timeoutId = window.setTimeout(function() {
            controller.abort();
        }, 3500);

        return fetch(complaintsSttServerHealthUrl, {
            method: 'GET',
            mode: 'cors',
            signal: controller.signal
        }).then(function(response) {
            if (!response || !response.ok) {
                return false;
            }
            // Proxy returns {ok: bool}; direct server returns 200 with any body
            return response.json().then(function(data) {
                return data && data.ok !== false;
            }).catch(function() {
                return true; // direct server: 200 with non-JSON body is fine
            });
        }).catch(function() {
            return false;
        }).finally(function() {
            window.clearTimeout(timeoutId);
        });
    }

    function sendComplaintsAudioToServer(audioBlob) {
        var formData = new FormData();
        formData.append('audio', audioBlob, 'complaints.webm');
        formData.append('lang', 'en-IN');
        formData.append('medical_context', (selectedComplaints || []).join(', '));

        return fetch(complaintsSttServerTranscribeUrl, {
            method: 'POST',
            mode: 'cors',
            body: formData
        }).then(function(response) {
            if (!response.ok) {
                return response.json().catch(function() {
                    return {};
                }).then(function(payload) {
                    var err = new Error(payload.detail || ('Server response: ' + response.status));
                    err.statusCode = response.status;
                    throw err;
                });
            }
            return response.json();
        }).then(function(payload) {
            var text = String((payload && (payload.text || payload.transcript || payload.result)) || '').trim();
            if (!text) {
                throw new Error('Empty transcript from server');
            }
            return text;
        });
    }

    function stopComplaintsMediaStream() {
        if (complaintsMediaStream && complaintsMediaStream.getTracks) {
            complaintsMediaStream.getTracks().forEach(function(track) {
                track.stop();
            });
        }
        complaintsMediaStream = null;
        complaintsMediaRecorder = null;
        complaintsMediaChunks = [];
    }

    function startComplaintsBrowserStt() {
        if (!complaintsSpeechRecognition) {
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Browser speech recognition is not available.');
            return;
        }

        if (complaintsMicActive) {
            complaintsSpeechRecognition.stop();
            return;
        }

        setComplaintsMicListening(true);
        $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text('Listening complaints (browser mode)...');

        try {
            complaintsSpeechRecognition.start();
        } catch (e) {
            setComplaintsMicListening(false);
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Could not start browser speech recognition.');
        }
    }

    function startComplaintsServerStt() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || typeof MediaRecorder === 'undefined') {
            if (complaintsSpeechRecognition) {
                setComplaintsMicMode('browser', 'Server recording is unavailable. Switched to browser mode.');
                startComplaintsBrowserStt();
                return;
            }
            setComplaintsMicMode('off', 'Speech is not available in this browser.');
            return;
        }

        if (complaintsMicActive && complaintsMediaRecorder) {
            complaintsMediaRecorder.stop();
            $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text('Processing server transcript...');
            return;
        }

        navigator.mediaDevices.getUserMedia({ audio: true }).then(function(stream) {
            complaintsMediaStream = stream;
            complaintsMediaRecorder = new MediaRecorder(stream);
            complaintsMediaChunks = [];

            complaintsMediaRecorder.ondataavailable = function(event) {
                if (event.data && event.data.size > 0) {
                    complaintsMediaChunks.push(event.data);
                }
            };

            complaintsMediaRecorder.onstop = function() {
                var blob = new Blob(complaintsMediaChunks, {
                    type: (complaintsMediaRecorder && complaintsMediaRecorder.mimeType) ? complaintsMediaRecorder.mimeType : 'audio/webm'
                });

                stopComplaintsMediaStream();
                setComplaintsMicListening(false);

                if (!blob || !blob.size || blob.size < 1024) {
                    $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Recording too short. Please record for at least 1-2 seconds.');
                    return;
                }

                sendComplaintsAudioToServer(blob).then(function(transcript) {
                    appendTranscriptToComplaints(transcript);
                    $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text('Complaints speech text added.');
                }).catch(function(error) {
                    if (error && error.statusCode && error.statusCode >= 400 && error.statusCode < 500) {
                        $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Speech input error: ' + (error.message || 'Invalid request'));
                        return;
                    }
                    if (complaintsSpeechRecognition) {
                        setComplaintsMicMode('browser', 'Server STT unavailable. Switched to browser mode.');
                    } else {
                        setComplaintsMicMode('off', 'Server STT unavailable and browser speech not supported.');
                    }
                });
            };

            setComplaintsMicListening(true);
            $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text('Recording complaints... click Mic again to stop.');
            complaintsMediaRecorder.start();
        }).catch(function() {
            if (complaintsSpeechRecognition) {
                setComplaintsMicMode('browser', 'Microphone permission failed for server mode. Switched to browser mode.');
            } else {
                setComplaintsMicMode('off', 'Microphone permission denied.');
            }
        });
    }

    function initComplaintsSpeech() {
        // Show checking state while server availability is probed
        var $micBtn = $('#btn_complaints_mic');
        $micBtn.prop('disabled', true).text('…');

        var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (SpeechRecognition) {
            complaintsSpeechRecognition = new SpeechRecognition();
            complaintsSpeechRecognition.lang = 'en-IN';
            complaintsSpeechRecognition.interimResults = true;
            complaintsSpeechRecognition.continuous = false;

            complaintsSpeechRecognition.onresult = function(event) {
                var interimTranscript = '';
                var finalTranscript = '';
                for (var i = event.resultIndex; i < event.results.length; i++) {
                    var t = event.results[i][0].transcript;
                    if (event.results[i].isFinal) {
                        finalTranscript += t;
                    } else {
                        interimTranscript += t;
                    }
                }

                if (interimTranscript) {
                    // Show interim as a non-destructive preview below the textarea
                    $('#complaints_interim_preview').text('🎤 ' + interimTranscript).show();
                }

                if (finalTranscript.trim()) {
                    // Final: clear preview, append once to the base text
                    $('#complaints_interim_preview').hide().text('');
                    appendTranscriptToComplaints(finalTranscript.trim());
                    complaintsBaseText = ($('#complaints').val() || '').toString().trim();
                }
            };

            complaintsSpeechRecognition.onerror = function(event) {
                setComplaintsMicListening(false);
                $('#complaints_interim_preview').hide().text('');
                $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Browser speech error: ' + (event.error || 'unknown'));
            };

            complaintsSpeechRecognition.onend = function() {
                setComplaintsMicListening(false);
                $('#complaints_interim_preview').hide().text('');
            };
        }

        setComplaintsMicMode('off', 'Checking speech mode...');

        $('#btn_complaints_mic').on('click', function() {
            if (complaintsMicMode === 'server') {
                startComplaintsServerStt();
            } else if (complaintsMicMode === 'browser') {
                startComplaintsBrowserStt();
            }
        });
    }

    function initMedicalSpeechButtons() {
        var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (SpeechRecognition) {
            medicalSpeechRecognition = new SpeechRecognition();
            medicalSpeechRecognition.lang = 'en-IN';
            medicalSpeechRecognition.interimResults = true;
            medicalSpeechRecognition.continuous = false;

            medicalSpeechRecognition.onresult = function(event) {
                var finalTranscript = '';
                for (var i = event.resultIndex; i < event.results.length; i++) {
                    var t = event.results[i][0].transcript;
                    if (event.results[i].isFinal) {
                        finalTranscript += t;
                    }
                }

                if (finalTranscript.trim() && medicalSttTarget) {
                    appendTranscriptToField(medicalSttTarget, finalTranscript.trim());
                }
            };

            medicalSpeechRecognition.onerror = function(event) {
                medicalBrowserSttActive = false;
                medicalSttTarget = '';
                setMedicalMicButtonState('');
                $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Medical browser speech error: ' + (event.error || 'unknown'));
            };

            medicalSpeechRecognition.onend = function() {
                medicalBrowserSttActive = false;
                medicalSttTarget = '';
                setMedicalMicButtonState('');
            };
        }

        setMedicalMicMode('off', 'Checking speech mode...');

        $(document).on('click', '.btn-medical-stt', function() {
            var targetId = ($(this).data('target') || '').toString();
            if (medicalMicMode === 'server') {
                startMedicalServerStt(targetId);
            } else if (medicalMicMode === 'browser') {
                startMedicalBrowserStt(targetId);
            }
        });
    }

    function getCustomInvestigationProfiles() {
        try {
            var parsed = JSON.parse(localStorage.getItem(invCustomProfileKey) || '[]');
            return Array.isArray(parsed) ? parsed : [];
        } catch (e) {
            return [];
        }
    }

    function saveCustomInvestigationProfiles(rows) {
        localStorage.setItem(invCustomProfileKey, JSON.stringify(rows || []));
    }

    function refreshCustomProfileSelect() {
        var rows = getCustomInvestigationProfiles();
        var $sel = $('#inv_custom_profile_select');
        if (!$sel.length) {
            return;
        }
        $sel.html('<option value="">Load profile...</option>');

        if (legacyInvestigationProfileList.length) {
            $sel.append('<optgroup label="Old HMS Profiles"></optgroup>');
            var $legacyGroup = $sel.find('optgroup[label="Old HMS Profiles"]');
            legacyInvestigationProfileList.forEach(function(row) {
                var key = (row && row.key) ? row.key : '';
                var name = (row && row.name) ? row.name : '';
                if (!key || !name) {
                    return;
                }
                $legacyGroup.append('<option value="legacy:' + $('<div>').text(key).html() + '">' + $('<div>').text(name).html() + '</option>');
            });
        }

        if (rows.length) {
            $sel.append('<optgroup label="Custom Profiles"></optgroup>');
            var $customGroup = $sel.find('optgroup[label="Custom Profiles"]');
            rows.forEach(function(row, idx) {
                $customGroup.append('<option value="custom:' + idx + '">' + $('<div>').text(row.name || ('Profile ' + (idx + 1))).html() + '</option>');
            });
            return;
        }

        rows.forEach(function(row, idx) {
            $sel.append('<option value="custom:' + idx + '">' + $('<div>').text(row.name || ('Profile ' + (idx + 1))).html() + '</option>');
        });
    }

    function loadInvestigationShortcutsFromLegacy() {
        apiGet('<?= base_url('Opd_prescription/investigation_shortcuts') ?>', function(data) {
            var profiles = (data && data.profiles) ? data.profiles : [];
            var $profileWrap = $('#inv_profile_chip_wrap');

            if ($profileWrap.length) {
                $profileWrap.find('.inv-profile-db').remove();
            }
            legacyInvestigationProfiles = {};
            legacyInvestigationProfileList = [];

            if (profiles.length) {
                profiles.forEach(function(p) {
                    var profileCode = (p.profile_code || '').toString();
                    var profileName = (p.profile_name || '').toString();
                    var tests = Array.isArray(p.tests) ? p.tests : [];
                    if (!profileCode || !profileName || !tests.length) {
                        return;
                    }
                    var key = 'db:' + profileCode;
                    legacyInvestigationProfiles[key] = tests;
                    legacyInvestigationProfileList.push({ key: key, name: profileName });
                });
                refreshCustomProfileSelect();
            }

            if ($profileWrap.length && profiles.length) {
                var maxProfiles = Math.min(10, profiles.length);
                var $anchor = $('#btn_create_inv_profile');
                for (var i = 0; i < maxProfiles; i++) {
                    var p = profiles[i] || {};
                    var profileCode = (p.profile_code || '').toString();
                    var profileName = (p.profile_name || '').toString();
                    var tests = Array.isArray(p.tests) ? p.tests : [];
                    if (!profileCode || !profileName || !tests.length) {
                        continue;
                    }
                    var key = 'db:' + profileCode;

                    var $btn = $('<button type="button" class="btn btn-outline-primary btn-sm inv-profile-chip inv-profile-db"></button>')
                        .attr('data-profile', key)
                        .text(profileName);

                    if ($anchor.length) {
                        $btn.insertBefore($anchor);
                    } else {
                        $profileWrap.append($btn);
                    }
                }
            }

        });
    }

    function initInvestigationPicker() {
        var $select = $('#investigation_name_select2');
        var $fallback = $('#investigation_name');

        if (!$select.length) {
            return;
        }

        if ($.fn && $.fn.select2) {
            $select.select2({
                width: '100%',
                placeholder: 'Search investigation...',
                allowClear: true,
                ajax: {
                    delay: 250,
                    transport: function(params, success, failure) {
                        var term = (params.data && params.data.term) ? params.data.term : '';
                        $.ajax({
                            url: '<?= base_url('Opd_prescription/investigation_search') ?>',
                            dataType: 'json',
                            data: { q: term },
                            success: success,
                            error: failure
                        });
                    },
                    processResults: function(data) {
                        var rows = (data && data.rows) ? data.rows : [];
                        return {
                            results: rows.map(function(row) {
                                var name = (row.name || '').toString();
                                var code = (row.code || '').toString();
                                return {
                                    id: name + (code ? ' [' + code + ']' : ''),
                                    text: name + (code ? ' [' + code + ']' : ''),
                                    name: name,
                                    code: code
                                };
                            })
                        };
                    }
                }
            });
            $fallback.addClass('d-none');
            return;
        }

        $fallback.removeClass('d-none');
    }

    function setStatus(type, message) {
        var $badge = $('#rx_status_badge');
        var $text = $('#rx_status_text');
        var statusMessage = (message || '').toString();
        $badge.removeClass('bg-secondary bg-warning bg-success bg-danger');

        if (type === 'saved') {
            $badge.addClass('bg-success').text('Saved');
        } else if (type === 'dirty') {
            $badge.addClass('bg-warning').text('Unsaved');
        } else if (type === 'saving') {
            $badge.addClass('bg-secondary').text('Saving...');
        } else if (type === 'error') {
            $badge.addClass('bg-danger').text('Error');
        } else {
            $badge.addClass('bg-secondary').text('Not Saved');
        }

        $text.text(statusMessage).attr('title', statusMessage);
    }

    function updateScanBanner() {
        var $banner = $('#rx_scan_banner');
        var finding = ($('#finding_examinations').val() || '');
        var investigation = ($('#investigation').val() || '');
        var scanRegex = /\[SCAN-([A-Z]+)\s([^\]]+)\]/g;
        var latest = null;

        function collectMatches(text, sourceField) {
            if (!text) {
                return;
            }
            var match;
            while ((match = scanRegex.exec(text)) !== null) {
                latest = {
                    type: match[1] || 'SCAN',
                    stamp: match[2] || '',
                    field: sourceField
                };
            }
        }

        collectMatches(finding, 'finding_examinations');
        collectMatches(investigation, 'investigation');

        $('#finding_examinations, #investigation').removeClass('rx-scan-highlight');
        if (!latest) {
            $banner.hide().html('');
            return;
        }

        var fieldLabel = latest.field === 'investigation' ? 'Investigation' : 'Finding';
        var stamp = latest.stamp ? (' at ' + latest.stamp) : '';
        var html = ''
            + '<div class="d-flex justify-content-between align-items-center gap-2">'
            + '<span>New scan text received: ' + $('<div>').text(latest.type + stamp + ' -> ' + fieldLabel).html() + '</span>'
            + '<button type="button" class="btn btn-sm btn-outline-primary" id="btn_jump_scan_field" data-target="' + latest.field + '">Jump to section</button>'
            + '</div>';
        $banner.html(html).show();
        $('#' + latest.field).addClass('rx-scan-highlight');
    }

    $(document).on('click', '#btn_jump_scan_field', function() {
        var target = $(this).data('target');
        if (!target || !$('#' + target).length) {
            return;
        }

        var $field = $('#' + target);
        $('html, body').animate({
            scrollTop: Math.max(0, $field.offset().top - 120)
        }, 250, function() {
            $field.trigger('focus');
        });
    });

    function getPayload() {
        var morbidities = [];
        var morbiditiesText = [];
        $('input[name="morbidities"]:checked').each(function() {
            morbidities.push($(this).val());
            var text = ($(this).data('name') || '').toString().trim();
            if (text) {
                morbiditiesText.push(text);
            }
        });

        return {
            opd_id: $('#opd_id').val(),
            opd_session_id: $('#opd_session_id').val(),
            abha_address: $('#abha_address').val(),
            complaints: $('#complaints').val(),
            finding_examinations: $('#finding_examinations').val(),
            diagnosis: $('#diagnosis').val(),
            provisional_diagnosis: $('#provisional_diagnosis').val(),
            prescriber_remarks: $('#prescriber_remarks').val(),
            women_related_problems: $('#women_related_problems').length ? $('#women_related_problems').val() : '',
            women_lmp: $('#women_lmp').length ? $('#women_lmp').val() : '',
            women_last_baby: $('#women_last_baby').length ? $('#women_last_baby').val() : '',
            women_pregnancy_related: $('#women_pregnancy_related').length ? $('#women_pregnancy_related').val() : '',
            drug_allergy_status: $('#drug_allergy_status').length ? $('#drug_allergy_status').val() : '',
            drug_allergy_details: $('#drug_allergy_details').length ? $('#drug_allergy_details').val() : '',
            adr_history: $('#adr_history').length ? $('#adr_history').val() : '',
            current_medications: $('#current_medications').length ? $('#current_medications').val() : '',
            investigation: $('#investigation').val(),
            advice: $('#advice').val(),
            next_visit: $('#next_visit').val(),
            refer_to: $('#refer_to').val(),
            bp: $('#bp').val(),
            diastolic: $('#diastolic').val(),
            pulse: $('#pulse').val(),
            temp: $('#temp').val(),
            spo2: $('#spo2').val(),
            rr_min: $('#rr_min').val(),
            height: $('#height').val(),
            weight: $('#weight').val(),
            waist: $('#waist').val(),
            pallor: $('#pallor').val(),
            icterus: $('#icterus').val(),
            cyanosis: $('#cyanosis').val(),
            clubbing: $('#clubbing').val(),
            edema: $('#edema').val(),
            pain_value: $('#pain_value').val(),
            pregnancy: $('#pregnancy').is(':checked') ? 1 : 0,
            lactation: $('#lactation').is(':checked') ? 1 : 0,
            liver_insufficiency: $('#liver_insufficiency').is(':checked') ? 1 : 0,
            renal_insufficiency: $('#renal_insufficiency').is(':checked') ? 1 : 0,
            pulmonary_insufficiency: $('#pulmonary_insufficiency').is(':checked') ? 1 : 0,
            corona_suspected: $('#corona_suspected').is(':checked') ? 1 : 0,
            dengue: $('#dengue').is(':checked') ? 1 : 0,
            is_smoking: $('#is_smoking').is(':checked') ? 1 : 0,
            is_alcohol: $('#is_alcohol').is(':checked') ? 1 : 0,
            is_drug_abuse: $('#is_drug_abuse').is(':checked') ? 1 : 0,
            morbidities_list: morbidities.join('-'),
            morbidities_text: morbiditiesText.join(', ')
        };
    }

    function saveDraftLocal() {
        try {
            localStorage.setItem(draftKey, JSON.stringify(getPayload()));
        } catch (e) {
        }
    }

    function loadDraftLocal() {
        try {
            var raw = localStorage.getItem(draftKey);
            if (!raw) {
                return false;
            }
            var data = JSON.parse(raw);
            if (!data) {
                return false;
            }

            $('#complaints').val(data.complaints || '');
            $('#finding_examinations').val(data.finding_examinations || '');
            $('#diagnosis').val(data.diagnosis || '');
            $('#provisional_diagnosis').val(data.provisional_diagnosis || '');
            $('#prescriber_remarks').val(data.prescriber_remarks || '');
            if ($('#women_related_problems').length) {
                $('#women_related_problems').val(data.women_related_problems || '');
            }
            if ($('#women_lmp').length) {
                $('#women_lmp').val(data.women_lmp || '');
            }
            if ($('#women_last_baby').length) {
                $('#women_last_baby').val(data.women_last_baby || '');
            }
            if ($('#women_pregnancy_related').length) {
                $('#women_pregnancy_related').val(data.women_pregnancy_related || '');
            }
            if ($('#drug_allergy_status').length) {
                $('#drug_allergy_status').val(data.drug_allergy_status || '');
            }
            if ($('#drug_allergy_details').length) {
                $('#drug_allergy_details').val(data.drug_allergy_details || '');
            }
            if ($('#adr_history').length) {
                $('#adr_history').val(data.adr_history || '');
            }
            if ($('#current_medications').length) {
                $('#current_medications').val(data.current_medications || '');
            }
            $('#investigation').val(data.investigation || '');
            $('#advice').val(data.advice || '');
            $('#next_visit').val(data.next_visit || '');
            $('#refer_to').val(data.refer_to || '');
            $('#pain_value').val(data.pain_value || '');
            $('input[name="options-pain"][value="' + (data.pain_value || '') + '"]').prop('checked', true);
            $('#pregnancy').prop('checked', (data.pregnancy || 0) == 1);
            $('#lactation').prop('checked', (data.lactation || 0) == 1);
            $('#liver_insufficiency').prop('checked', (data.liver_insufficiency || 0) == 1);
            $('#renal_insufficiency').prop('checked', (data.renal_insufficiency || 0) == 1);
            $('#pulmonary_insufficiency').prop('checked', (data.pulmonary_insufficiency || 0) == 1);
            $('#corona_suspected').prop('checked', (data.corona_suspected || 0) == 1);
            $('#dengue').prop('checked', (data.dengue || 0) == 1);
            $('#is_smoking').prop('checked', (data.is_smoking || 0) == 1);
            $('#is_alcohol').prop('checked', (data.is_alcohol || 0) == 1);
            $('#is_drug_abuse').prop('checked', (data.is_drug_abuse || 0) == 1);
            if ((data.morbidities_list || '') !== '') {
                var selectedMorbidities = String(data.morbidities_list).split('-');
                $('input[name="morbidities"]').prop('checked', false);
                selectedMorbidities.forEach(function(morId) {
                    $('input[name="morbidities"][value="' + morId + '"]').prop('checked', true);
                });
            }
            refreshCounters();
            markDirty('Draft restored from local storage');
            return true;
        } catch (e) {
            return false;
        }
    }

    function clearDraftLocal() {
        try {
            localStorage.removeItem(draftKey);
        } catch (e) {
        }
    }

    function refreshCounters() {
        $('.rx-field').each(function() {
            var id = $(this).attr('id');
            var max = parseInt($(this).attr('maxlength') || '0', 10);
            var len = ($(this).val() || '').length;
            $('#counter_' + id).text(len + '/' + max);
        });
    }

    function markDirty(message) {
        isDirty = true;
        setStatus('dirty', message || 'Unsaved changes present');
        saveDraftLocal();
    }

    function clearNabhFieldErrors() {
        $('#drug_allergy_status,#drug_allergy_details').removeClass('is-invalid');
        $('#drug_allergy_status_error,#drug_allergy_details_error').text('').hide();
    }

    function markNabhFieldError(selector, message) {
        clearNabhFieldErrors();
        if (!selector || !$(selector).length) {
            return;
        }
        $(selector).addClass('is-invalid').trigger('focus');

        if (selector === '#drug_allergy_status') {
            $('#drug_allergy_status_error').text(message || 'This field is required.').show();
        } else if (selector === '#drug_allergy_details') {
            $('#drug_allergy_details_error').text(message || 'This field is required.').show();
        }
    }

    function savePrescription(autoMode, done) {
        if (isSaving) {
            return;
        }

        clearNabhFieldErrors();

        var allergyStatus = ($('#drug_allergy_status').length ? ($('#drug_allergy_status').val() || '').trim() : '');
        var allergyDetails = ($('#drug_allergy_details').length ? ($('#drug_allergy_details').val() || '').trim() : '');
        var allergyStatusNorm = allergyStatus.toLowerCase().replace(/\s+/g, ' ');

        if (allergyStatus === '') {
            var msgStatus = 'Drug Allergy Status is required as per NABH documentation.';
            setStatus('error', 'Drug Allergy Status is required');
            $('.jsError').removeClass('text-muted text-success').addClass('text-danger').text(msgStatus);
            markNabhFieldError('#drug_allergy_status', msgStatus);
            if (typeof done === 'function') {
                done(false, { update: 0, error_text: msgStatus });
            }
            return;
        }

        if ((allergyStatusNorm === 'known' || allergyStatusNorm === 'yes' || allergyStatusNorm === 'present') && allergyDetails === '') {
            var msgDetails = 'Drug Allergy Details are required when Drug Allergy Status is Known.';
            setStatus('error', 'Drug Allergy Details are required');
            $('.jsError').removeClass('text-muted text-success').addClass('text-danger').text(msgDetails);
            markNabhFieldError('#drug_allergy_details', msgDetails);
            if (typeof done === 'function') {
                done(false, { update: 0, error_text: msgDetails });
            }
            return;
        }

        isSaving = true;
        setStatus('saving', autoMode ? 'Auto-saving...' : 'Saving prescription...');
        $('.jsError').removeClass('text-success text-danger').addClass('text-muted').text('');

        var payload = getPayload();
        apiPost('<?= base_url('Opd_prescription/opd_prescription_save') ?>', payload, function(data) {
            if (data.update == 0) {
                isSaving = false;
                setStatus('error', data.error_text || 'Unable to save prescription');
                $('.jsError').removeClass('text-muted text-success').addClass('text-danger').text(data.error_text || 'Unable to save prescription');
                if (typeof done === 'function') {
                    done(false, data);
                }
                return;
            }
            $('#opd_session_id').val(data.opd_session_id || 0);
            isSaving = false;
            isDirty = false;
            clearDraftLocal();
            if ($('#fhir_history_card').is(':visible')) {
                loadFhirHistory();
                isFhirHistoryLoaded = true;
            }
            var savedTime = data.saved_at ? (' at ' + data.saved_at) : '';
            setStatus('saved', (autoMode ? 'Auto-saved successfully' : 'Saved successfully') + savedTime);
            $('.jsError').removeClass('text-muted text-danger').addClass('text-success').text(data.error_text || 'Saved');
            if (typeof done === 'function') {
                done(true, data);
            }
        });
    }

    $('#drug_allergy_status,#drug_allergy_details').on('input change', function() {
        $(this).removeClass('is-invalid');
        if (this.id === 'drug_allergy_status') {
            $('#drug_allergy_status_error').text('').hide();
        }
        if (this.id === 'drug_allergy_details') {
            $('#drug_allergy_details_error').text('').hide();
        }
    });

    function ensureSession(done) {
        var sid = parseInt($('#opd_session_id').val() || '0', 10);
        if (sid > 0) {
            done(sid);
            return;
        }
        savePrescription(false, function(ok, data) {
            if (!ok) {
                return;
            }
            var newSid = parseInt((data && data.opd_session_id) ? data.opd_session_id : '0', 10);
            if (newSid > 0) {
                done(newSid);
            }
        });
    }

    function scheduleAutoSave() {
        if (autoSaveTimer) {
            clearTimeout(autoSaveTimer);
        }
        autoSaveTimer = setTimeout(function() {
            if (isDirty) {
                savePrescription(true);
            }
        }, 8000);
    }

    $('#btn_save_rx').on('click', function() {
        savePrescription(false);
    });

    function openLegacyPrescriptionPrint(printType) {
        var openPrint = function() {
            var opdId = $('#opd_id').val() || '0';
            var sessionId = $('#opd_session_id').val() || '0';
            var url = '<?= base_url('Opd_prescription/opd_prescription_print') ?>/' + opdId + '/' + sessionId + '/' + (parseInt(printType || '0', 10) || 0);
            window.open(url, '_blank');
        };

        if (isDirty || parseInt($('#opd_session_id').val() || '0', 10) <= 0) {
            savePrescription(false, function(ok) {
                if (!ok) {
                    return;
                }
                openPrint();
            });
            return;
        }

        openPrint();
    }

    $('#btn_print0').on('click', function() {
        openLegacyPrescriptionPrint(0);
    });

    $('#btn_print1').on('click', function() {
        openLegacyPrescriptionPrint(1);
    });

    $('#btn_print2').on('click', function() {
        openLegacyPrescriptionPrint(2);
    });

    function renderAiFullDraftPreview(drafts) {
        var labels = {
            finding_examinations: 'Examination',
            diagnosis: 'Diagnosis',
            provisional_diagnosis: 'Provisional Diagnosis',
            prescriber_remarks: 'Prescription',
            investigation: 'Investigation',
            advice: 'Advice'
        };

        var html = '';
        Object.keys(labels).forEach(function(key) {
            var txt = (typeof drafts[key] === 'string') ? drafts[key].trim() : '';
            if (!txt) {
                return;
            }
            html += '<div class="border rounded p-2 mb-2">'
                + '<div class="small fw-bold mb-1">' + $('<div>').text(labels[key]).html() + '</div>'
                + '<div class="small" style="white-space:pre-wrap;">' + $('<div>').text(txt).html() + '</div>'
                + '</div>';
        });

        if (!html) {
            html = '<div class="text-muted">No AI draft text generated.</div>';
        }

        $('#ai_full_draft_preview_body').html(html);
        $('#ai_full_draft_preview_card').show();
    }

    function renderLocalClinicalAssistPreview(assist) {
        assist = assist || {};
        var html = '';

        var disclaimer = (assist.disclaimer || '').toString().trim();
        if (disclaimer) {
            html += '<div class="alert alert-warning py-2 px-2 small mb-2">' + $('<div>').text(disclaimer).html() + '</div>';
        }

        var vitalChecks = Array.isArray(assist.vital_checks) ? assist.vital_checks : [];
        if (vitalChecks.length) {
            html += '<div class="border rounded p-2 mb-2">'
                + '<div class="small fw-bold mb-1">Vital Checks</div>'
                + '<ul class="small mb-0 ps-3">'
                + vitalChecks.map(function(v) { return '<li>' + $('<div>').text(v || '').html() + '</li>'; }).join('')
                + '</ul></div>';
        }

        var redFlags = Array.isArray(assist.red_flags) ? assist.red_flags : [];
        if (redFlags.length) {
            html += '<div class="border border-danger rounded p-2 mb-2">'
                + '<div class="small fw-bold text-danger mb-1">Red Flags</div>'
                + '<ul class="small mb-0 ps-3">'
                + redFlags.map(function(v) { return '<li>' + $('<div>').text(v || '').html() + '</li>'; }).join('')
                + '</ul></div>';
        }

        [['suggested_diagnosis', 'Suggested Diagnosis'], ['suggested_investigation', 'Suggested Investigation'], ['suggested_advice', 'Suggested Advice']].forEach(function(row) {
            var txt = (assist[row[0]] || '').toString().trim();
            if (!txt) {
                return;
            }
            html += '<div class="border rounded p-2 mb-2">'
                + '<div class="small fw-bold mb-1">' + $('<div>').text(row[1]).html() + '</div>'
                + '<div class="small" style="white-space:pre-wrap;">' + $('<div>').text(txt).html() + '</div>'
                + '</div>';
        });

        if (!html) {
            html = '<div class="text-muted">No local assist suggestion generated.</div>';
        }

        $('#local_clinical_assist_body').html(html);
        $('#local_clinical_assist_card').show();
    }

    $('#btn_ai_full_draft').on('click', function() {
        var payload = getPayload();
        if (!(payload.complaints || payload.finding_examinations || payload.diagnosis)) {
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Add complaints/finding/diagnosis first, then use AI Draft.');
            return;
        }

        var $btn = $(this);
        var oldText = $btn.text();
        $btn.prop('disabled', true).text('Drafting...');

        apiPost('<?= base_url('Opd_prescription/ai_full_draft') ?>', payload, function(data) {
            $btn.prop('disabled', false).text(oldText);

            if (data.update != 1 || !data.drafts) {
                $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text(data.error_text || 'Unable to prepare full AI draft.');
                return;
            }

            var drafts = data.drafts || {};
            renderAiFullDraftPreview(drafts);
            $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text(data.error_text || 'AI draft preview shown in left panel. Controls unchanged.');
        });
    });

    $('#btn_local_clinical_assist').on('click', function() {
        var payload = getPayload();
        if (!(payload.complaints || payload.finding_examinations)) {
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Enter complaints or findings first for clinical assist.');
            return;
        }

        var $btn = $(this);
        var oldText = $btn.text();
        $btn.prop('disabled', true).text('Checking...');

        apiPost('<?= base_url('Opd_prescription/local_clinical_assist') ?>', payload, function(data) {
            $btn.prop('disabled', false).text(oldText);

            if (data.update != 1 || !data.assist) {
                $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text(data.error_text || 'Unable to prepare local clinical assist.');
                return;
            }

            latestLocalAssist = data.assist || {};
            renderLocalClinicalAssistPreview(latestLocalAssist);
            $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text(data.error_text || 'Local clinical assist prepared.');
        });
    });

    $('#btn_apply_local_clinical_assist').on('click', function() {
        if (!latestLocalAssist) {
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Generate local clinical assist first.');
            return;
        }

        var appendLines = function($field, value) {
            var incoming = (value || '').toString().trim();
            if (!incoming) {
                return;
            }
            var existing = ($field.val() || '').toString().trim();
            $field.val(existing ? (existing + '\n' + incoming) : incoming);
        };

        appendLines($('#diagnosis'), latestLocalAssist.suggested_diagnosis || '');
        appendLines($('#investigation'), latestLocalAssist.suggested_investigation || '');
        appendLines($('#advice'), latestLocalAssist.suggested_advice || '');

        refreshCounters();
        markDirty('Local clinical assist suggestions applied');
        $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text('Local assist suggestions added. Doctor can edit before final save.');
    });

    $('#btn_download_fhir').on('click', function() {
        var openBundle = function(sessionId) {
            if (parseInt(sessionId || '0', 10) <= 0) {
                return;
            }

            var url = '<?= base_url('Opd_prescription/fhir_bundle') ?>/' + ($('#opd_id').val() || '0') + '/' + sessionId;
            window.open(url, '_blank');
        };

        if (isDirty || parseInt($('#opd_session_id').val() || '0', 10) <= 0) {
            savePrescription(false, function(ok, data) {
                if (!ok) {
                    return;
                }
                openBundle((data && data.opd_session_id) ? data.opd_session_id : $('#opd_session_id').val());
            });
            return;
        }

        openBundle($('#opd_session_id').val());
    });

    $('#btn_restore_draft').on('click', function() {
        if (!loadDraftLocal()) {
            setStatus('normal', 'No draft found');
            $('.jsError').removeClass('text-success text-danger').addClass('text-muted').text('No local draft found');
        }
    });

    $('#btn_new_opd_session_reset').on('click', function() {
        if (!window.confirm('Start new OPD session view? This will clear Advice/Investigation/Medicine list from screen.')) {
            return;
        }

        $('#opd_session_id').val('0');
        $('#advice_text,#investigation_name').val('');
        clearMedicineForm(true);
        renderAdvice([]);
        renderInvestigation([]);
        renderMedicine([]);

        isDirty = false;
        setStatus('normal', 'New OPD session view started');
        $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text('Advice, Investigation, and Medicine lists cleared for new session testing.');
    });

    $(document).on('click', '.btn-field-clear', function() {
        var target = $(this).data('target');
        if (!target || !$('#' + target).length) {
            return;
        }
        $('#' + target).val('');
        refreshCounters();
        markDirty('Section cleared');
    });

    $(document).on('click', '.btn-template-save', function() {
        var target = ($(this).data('target') || '').toString();
        var section = ($(this).data('section') || '').toString();
        if (!target || !section || !$('#' + target).length) {
            return;
        }

        var text = ($('#' + target).val() || '').trim();
        if (!text) {
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Type text first, then save as template.');
            return;
        }

        templateSaveState = { section: section, text: text, scope: 'doctor' };
        var sectionLabel = section.replace(/_/g, ' ').replace(/\b\w/g, function(ch) { return ch.toUpperCase(); });
        $('#rxTemplateSaveModalTitle').text('Save Template - ' + sectionLabel);
        $('#rx_template_save_name').val(section.replace(/_/g, ' '));
        $('#rx_template_save_scope').val('doctor');
        $('#rx_template_save_preview').val(text);

        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(document.getElementById('rxTemplateSaveModal')).show();
        } else {
            $('#rxTemplateSaveModal').show();
        }
    });

    $('#btn_save_template_choice').on('click', function() {
        var section = (templateSaveState.section || '').toString();
        var text = (templateSaveState.text || '').toString().trim();
        var templateName = ($('#rx_template_save_name').val() || '').toString().trim();
        var templateScope = ($('#rx_template_save_scope').val() || 'doctor').toString().trim().toLowerCase();

        if (!section || !text) {
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Nothing to save for this section.');
            return;
        }
        if (!templateName) {
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Template name is required.');
            return;
        }

        apiPost('<?= base_url('Opd_prescription/section_template_save') ?>', {
            section: section,
            template_name: templateName,
            template_text: text,
            template_scope: templateScope
        }, function(data) {
            if (data.update != 1) {
                $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text(data.error_text || 'Unable to save template.');
                return;
            }

            $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text(data.error_text || 'Template saved');
            if (window.bootstrap && window.bootstrap.Modal) {
                var modalEl = document.getElementById('rxTemplateSaveModal');
                var modalInst = window.bootstrap.Modal.getOrCreateInstance(modalEl);
                modalInst.hide();
            } else {
                $('#rxTemplateSaveModal').hide();
            }
        });
    });

    function normalizeTemplateText(raw) {
        return (raw || '').toString().toLowerCase().replace(/[^a-z0-9\s]/g, ' ').replace(/\s+/g, ' ').trim();
    }

    function tokenSet(raw) {
        var text = normalizeTemplateText(raw);
        if (!text) {
            return {};
        }

        var stopWords = {
            'the': true, 'and': true, 'for': true, 'with': true, 'from': true, 'that': true,
            'this': true, 'are': true, 'was': true, 'were': true, 'has': true, 'have': true,
            'had': true, 'not': true, 'but': true, 'you': true, 'your': true, 'into': true,
            'can': true, 'will': true, 'may': true, 'all': true, 'any': true
        };

        var out = {};
        text.split(' ').forEach(function(word) {
            if (!word || word.length < 3 || stopWords[word]) {
                return;
            }
            out[word] = true;
        });

        return out;
    }

    function scoreTemplateRow(row, contextTokens) {
        row = row || {};
        var nameText = normalizeTemplateText(row.template_name || '');
        var bodyText = normalizeTemplateText(row.template_text || '');
        var score = 0;

        Object.keys(contextTokens || {}).forEach(function(token) {
            if (nameText.indexOf(token) !== -1) {
                score += 5;
            }
            if (bodyText.indexOf(token) !== -1) {
                score += 2;
            }
        });

        if ((row.template_text || '').toString().length > 40) {
            score += 1;
        }

        return score;
    }

    function buildTemplateContextText(targetField) {
        var targetVal = ($('#' + targetField).val() || '').toString();
        var complaintVal = ($('#complaints').val() || '').toString();
        var diagnosisVal = ($('#diagnosis').val() || '').toString();
        var provisionalVal = ($('#provisional_diagnosis').val() || '').toString();
        var adviceVal = ($('#advice').val() || '').toString();
        return [targetVal, complaintVal, diagnosisVal, provisionalVal, adviceVal].join(' ');
    }

    function rankTemplateRows(rows, targetField) {
        var list = Array.isArray(rows) ? rows.slice() : [];
        if (!list.length) {
            return [];
        }

        var contextTokens = tokenSet(buildTemplateContextText(targetField));

        list.sort(function(a, b) {
            var as = scoreTemplateRow(a, contextTokens);
            var bs = scoreTemplateRow(b, contextTokens);
            if (bs !== as) {
                return bs - as;
            }

            var ad = parseInt(a.doc_id || '0', 10);
            var bd = parseInt(b.doc_id || '0', 10);
            if (ad !== bd) {
                return bd - ad;
            }

            var an = (a.template_name || '').toString().toLowerCase();
            var bn = (b.template_name || '').toString().toLowerCase();
            if (an < bn) {
                return -1;
            }
            if (an > bn) {
                return 1;
            }
            return 0;
        });

        return list;
    }

    function renderTemplateQuickSuggestions(rows) {
        var $box = $('#rx_template_suggest_box');
        $box.empty();

        var list = Array.isArray(rows) ? rows : [];
        if (!list.length) {
            $box.html('<span class="text-muted small">No suggestion available.</span>');
            return;
        }

        list.slice(0, 3).forEach(function(row, idx) {
            var name = (row.template_name || ('Template ' + (idx + 1))).toString();
            var src = parseInt(row.doc_id || '0', 10) === 0 ? 'Master' : 'My';
            $box.append('<button type="button" class="btn btn-outline-primary btn-sm btn-template-quick" data-idx="' + idx + '">' + $('<div>').text(src + ': ' + name).html() + '</button>');
        });
    }

    function renderTemplateSelectOptions(searchQuery) {
        var list = Array.isArray(templateLoadState.rows) ? templateLoadState.rows : [];
        var query = (searchQuery || '').toString().trim().toLowerCase();
        var $sel = $('#rx_template_select');
        $sel.empty();

        var added = 0;
        list.forEach(function(row, idx) {
            var name = (row.template_name || ('Template ' + (idx + 1))).toString();
            var src = parseInt(row.doc_id || '0', 10) === 0 ? '[Master] ' : '[My] ';
            var previewText = (row.template_text || '').toString();
            var haystack = (name + ' ' + previewText + ' ' + src).toLowerCase();
            if (query && haystack.indexOf(query) === -1) {
                return;
            }
            $sel.append('<option value="' + idx + '">' + $('<div>').text(src + name).html() + '</option>');
            added++;
        });

        if (!added) {
            $sel.append('<option value="">No templates match search</option>');
            $('#rx_template_preview').val('');
            return;
        }

        var firstVal = ($sel.find('option:first').val() || '').toString();
        var firstIdx = parseInt(firstVal, 10);
        if (!isNaN(firstIdx) && firstIdx >= 0) {
            $sel.val(String(firstIdx));
            var firstRow = list[firstIdx] || {};
            $('#rx_template_preview').val(firstRow.template_text || '');
        }
    }

    $(document).on('click', '.btn-template-load', function() {
        var target = ($(this).data('target') || '').toString();
        var section = ($(this).data('section') || '').toString();
        if (!target || !section || !$('#' + target).length) {
            return;
        }

        var url = '<?= base_url('Opd_prescription/section_template_list') ?>?section=' + encodeURIComponent(section);
        apiGet(url, function(data) {
            var rows = (data && data.rows) ? data.rows : [];
            if (!rows.length) {
                $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('No template found for this section.');
                return;
            }

            var rankedRows = rankTemplateRows(rows, target);
            templateLoadState = { target: target, section: section, rows: rankedRows };
            $('#rx_template_search').val('');
            renderTemplateSelectOptions('');

            renderTemplateQuickSuggestions(rankedRows);

            var existingText = ($('#' + target).val() || '').toString().trim();
            $('#rx_template_apply_mode').val(existingText ? 'append' : 'replace');

            if (window.bootstrap && window.bootstrap.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(document.getElementById('rxTemplateModal')).show();
            } else {
                $('#rxTemplateModal').show();
            }
        });
    });

    $('#rx_template_select').on('change', function() {
        var idx = parseInt($(this).val() || '-1', 10);
        if (isNaN(idx) || idx < 0) {
            $('#rx_template_preview').val('');
            return;
        }
        var row = (templateLoadState.rows || [])[idx] || {};
        $('#rx_template_preview').val(row.template_text || '');
    });

    $('#rx_template_search').on('input', function() {
        renderTemplateSelectOptions($(this).val() || '');
    });

    $('#btn_apply_template_choice').on('click', function() {
        var idx = parseInt($('#rx_template_select').val() || '0', 10);
        var row = (templateLoadState.rows || [])[idx] || null;
        if (!row || !templateLoadState.target || !$('#' + templateLoadState.target).length) {
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Invalid template selection.');
            return;
        }

        var mode = ($('#rx_template_apply_mode').val() || 'replace').toString();
        var selectedText = (row.template_text || '').toString().trim();
        var $target = $('#' + templateLoadState.target);
        var currentText = ($target.val() || '').toString().trim();
        var finalText = selectedText;

        if (mode === 'append' && currentText !== '') {
            if (selectedText !== '' && currentText.toLowerCase() !== selectedText.toLowerCase()) {
                finalText = currentText + '\n' + selectedText;
            } else {
                finalText = currentText;
            }
        }

        $target.val(finalText);
        refreshCounters();
        markDirty(mode === 'append' ? 'Template appended' : 'Template loaded');
        $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text(mode === 'append' ? 'Template appended' : 'Template loaded');

        var templateId = parseInt(row.id || '0', 10);
        if (templateId > 0 && (templateLoadState.section || '').toString() !== '') {
            apiPost('<?= base_url('Opd_prescription/section_template_track_usage') ?>', {
                template_id: templateId,
                section: templateLoadState.section
            }, function() {
            });
        }

        if (window.bootstrap && window.bootstrap.Modal) {
            var modalEl = document.getElementById('rxTemplateModal');
            var modalInst = window.bootstrap.Modal.getOrCreateInstance(modalEl);
            modalInst.hide();
        } else {
            $('#rxTemplateModal').hide();
        }
    });

    $(document).on('click', '.btn-template-quick', function() {
        var idx = parseInt($(this).data('idx') || '0', 10);
        if (idx < 0) {
            return;
        }
        if (!$('#rx_template_select option[value="' + idx + '"]').length) {
            $('#rx_template_search').val('');
            renderTemplateSelectOptions('');
        }
        $('#rx_template_select').val(String(idx)).trigger('change');
    });

    $(document).on('click', '.btn-field-past', function() {
        var target = ($(this).data('target') || '').toString();
        var section = ($(this).data('section') || '').toString();
        if (!target || !section || !$('#' + target).length) {
            return;
        }

        var url = '<?= base_url('Opd_prescription/section_past_data') ?>?section=' + encodeURIComponent(section) + '&opd_id=' + encodeURIComponent($('#opd_id').val() || '0');
        apiGet(url, function(data) {
            if ((data.update || 0) != 1) {
                $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text(data.error_text || 'No past data found.');
                return;
            }

            $('#' + target).val(data.past_text || '');
            refreshCounters();
            markDirty('Past data loaded');
            $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text(data.error_text || 'Past data loaded');
        });
    });

    $('#btn_toggle_fhir_history').on('click', function() {
        var $card = $('#fhir_history_card');
        $card.toggle();
        if ($card.is(':visible')) {
            if (!isFhirHistoryLoaded) {
                loadFhirHistory();
                isFhirHistoryLoaded = true;
            }
            $(this).text('📄 Hide FHIR');
        } else {
            $(this).text('📄 FHIR History');
        }
    });

    function renderComplaintChips() {
        var $box = $('#complaint_chips');
        $box.empty();

        if (!selectedComplaints.length) {
            $box.html('<span class="text-muted small">No complaint selected</span>');
            return;
        }

        selectedComplaints.forEach(function(item, idx) {
            var label = $('<div>').text(item).html();
            $box.append('<span class="rx-chip">' + label + '<button type="button" class="btn-remove-complaint" data-idx="' + idx + '">&times;</button></span>');
        });
    }

    function addComplaintValue(finalValue) {
        var value = (finalValue || '').trim();
        if (!value) {
            return false;
        }

        var exists = selectedComplaints.some(function(item) {
            return item.toUpperCase() === value.toUpperCase();
        });

        if (exists) {
            return false;
        }

        selectedComplaints.push(value);
        renderComplaintChips();
        appendComplaintToTextarea(value);
        refreshCounters();
        markDirty('Complaint added');
        return true;
    }

    function appendComplaintToTextarea(value) {
        var current = ($('#complaints').val() || '').trim();
        if (current === '') {
            $('#complaints').val(value);
            return;
        }

        if (current.toUpperCase().indexOf(value.toUpperCase()) !== -1) {
            return;
        }

        $('#complaints').val(current + ', ' + value);
    }

    $('#complaint_lookup').on('input', function() {
        var q = ($(this).val() || '').trim();
        if (q.length < 2) {
            return;
        }

        apiGet('<?= base_url('Opd_prescription/complaints_search') ?>?q=' + encodeURIComponent(q), function(data) {
            complaintSuggestions = data.rows || [];
            var html = '';
            complaintSuggestions.forEach(function(row) {
                var label = row.name || '';
                if (row.name_hinglish) {
                    label += ' (' + row.name_hinglish + ')';
                }
                html += '<option value="' + $('<div>').text(label).html() + '"></option>';
            });
            $('#complaint_suggest').html(html);
        });
    });

    $('#btn_add_complaint').on('click', function() {
        var inputVal = ($('#complaint_lookup').val() || '').trim();
        if (!inputVal) {
            return;
        }

        var chosen = null;
        complaintSuggestions.forEach(function(row) {
            var label = row.name || '';
            if (row.name_hinglish) {
                label += ' (' + row.name_hinglish + ')';
            }
            if (label.toUpperCase() === inputVal.toUpperCase() || (row.name || '').toUpperCase() === inputVal.toUpperCase()) {
                chosen = row.name || inputVal;
            }
        });

        if (chosen) {
            addComplaintValue(chosen);
            $('#complaint_lookup').val('');
            return;
        }

        apiPost('<?= base_url('Opd_prescription/complaints_parse') ?>', {
            text: inputVal
        }, function(data) {
            var matchedRows = data.rows || [];
            var added = 0;

            matchedRows.forEach(function(name) {
                if (addComplaintValue(name)) {
                    added++;
                }
            });

            if (added === 0 && inputVal.indexOf(',') === -1) {
                if (addComplaintValue(inputVal.toUpperCase())) {
                    added = 1;
                }
            }

            if (added === 0) {
                $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('No complaint matched. Try simpler words like bukhar, khansi, kamar dard.');
            } else {
                $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text('Added ' + added + ' complaint term(s).');
            }

            $('#complaint_lookup').val('');
        });
    });

    $(document).on('click', '.btn-remove-complaint', function() {
        if (selectedComplaints.length <= 1) {
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('At least one complaint item should remain.');
            return;
        }

        var idx = parseInt($(this).data('idx') || '-1', 10);
        if (idx < 0 || idx >= selectedComplaints.length) {
            return;
        }

        selectedComplaints.splice(idx, 1);
        renderComplaintChips();
        markDirty('Complaint removed');
    });

    $('#btn_ai_complaint_draft').on('click', function() {
        if (!selectedComplaints.length && !($('#complaints').val() || '').trim()) {
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Please add at least one complaint first.');
            return;
        }

        apiPost('<?= base_url('Opd_prescription/complaints_ai_draft') ?>', {
            complaints: selectedComplaints,
            current_text: $('#complaints').val()
        }, function(data) {
            if (data.update != 1) {
                $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text(data.error_text || 'Unable to draft complaint text');
                return;
            }

            $('#complaints').val(data.draft_text || $('#complaints').val());
            refreshCounters();
            markDirty('AI complaint draft generated');
            $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text(data.error_text || 'Complaint draft ready');
        });
    });

    function renderFhirHistory(rows) {
        var $tb = $('#tbl_fhir_history tbody');
        $tb.empty();
        if (!rows || rows.length === 0) {
            $tb.html('<tr><td colspan="4" class="text-muted px-2 py-2">No FHIR export found</td></tr>');
            return;
        }

        rows.forEach(function(row) {
            var generatedAt = $('<div>').text(row.generated_at || '').html();
            var generatedBy = $('<div>').text(row.generated_by || 'system').html();
            var sessionId = parseInt(row.opd_session_id || 0, 10);
            var downloadUrl = row.download_url || '';
            var btn = '<a class="btn btn-sm btn-outline-success" target="_blank" href="' + $('<div>').text(downloadUrl).html() + '">JSON</a>';
            $tb.append('<tr><td>' + generatedAt + '</td><td>' + generatedBy + '</td><td>' + sessionId + '</td><td>' + btn + '</td></tr>');
        });
    }

    function loadFhirHistory() {
        var opdId = $('#opd_id').val() || 0;
        var sid = $('#opd_session_id').val() || 0;
        apiGet('<?= base_url('Opd_prescription/fhir_bundle_history') ?>/' + opdId + '/' + sid, function(data) {
            renderFhirHistory(data.rows || []);
        });
    }

    function loadPatientScanHistory() {
        var opdId = parseInt($('#opd_id').val() || '0', 10);
        if (opdId <= 0) {
            $('#patient_scan_history_body').html('<div class="text-muted">No scan history found.</div>');
            return;
        }

        $('#patient_scan_history_body').html('<div class="text-muted">Loading scan history...</div>');
        $.get('<?= base_url('Opd_prescription/patient_scan_history') ?>/' + opdId, function(html) {
            $('#patient_scan_history_body').html(html || '<div class="text-muted">No scan history found.</div>');
        }).fail(function() {
            $('#patient_scan_history_body').html('<div class="text-danger">Unable to load scan history.</div>');
        });
    }

    $('.rx-field').on('input', function() {
        refreshCounters();
        updateScanBanner();
        markDirty('Editing in progress');
        scheduleAutoSave();
    });

    $('.rx-instant, #pregnancy, #lactation, #liver_insufficiency, #renal_insufficiency, #pulmonary_insufficiency, #corona_suspected, #dengue, #is_smoking, #is_alcohol, #is_drug_abuse, input[name="morbidities"], input[name="options-pain"]').on('input change', function() {
        markDirty('Clinical details updated');
        scheduleAutoSave();
    });

    $('input[name="options-pain"]').on('change', function() {
        $('#pain_value').val($(this).val() || '');
    });

    $('.rx-field').on('blur', function() {
        if (isDirty) {
            scheduleAutoSave();
        }
    });

    $(document).on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
            e.preventDefault();
            savePrescription(false);
        }
    });

    window.addEventListener('beforeunload', function(e) {
        if (!isDirty) {
            return;
        }
        e.preventDefault();
        e.returnValue = '';
    });

    // Force browser mic by default in production-safe mode until server STT is stable.
    speechModePreference = 'browser';
    localStorage.setItem(speechModePreferenceKey, 'browser');

    refreshCounters();
    setStatus('normal', 'No local changes');
    renderComplaintChips();
    initComplaintsSpeech();
    initMedicalSpeechButtons();
    applySpeechModePreference();

    $('#speech_mode_select').val(getSpeechModePreference());
    $('#speech_mode_select').on('change', function() {
        var nextPref = (($(this).val() || 'auto').toString().toLowerCase());
        if (nextPref !== 'auto' && nextPref !== 'browser' && nextPref !== 'server') {
            nextPref = 'auto';
        }

        speechModePreference = nextPref;
        localStorage.setItem(speechModePreferenceKey, nextPref);

        if (complaintsMicActive && complaintsMediaRecorder) {
            complaintsMediaRecorder.stop();
        }
        if (complaintsMicActive && complaintsSpeechRecognition) {
            complaintsSpeechRecognition.stop();
        }
        if (medicalSttActive && medicalSttRecorder) {
            medicalSttRecorder.stop();
        }
        if (medicalBrowserSttActive && medicalSpeechRecognition) {
            medicalSpeechRecognition.stop();
        }

        applySpeechModePreference();
    });
    setTimeout(loadPatientScanHistory, 50);

    $(document).on('click', '.btn-ai-rewrite', function() {
        var target = $(this).data('target');
        var mode = $(this).data('mode') || 'autotype';
        if (!target || !$('#' + target).length) {
            return;
        }

        var text = ($('#' + target).val() || '').trim();
        if (!text) {
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Type text first, then use AI assist.');
            return;
        }

        apiPost('<?= base_url('Opd_prescription/clinical_autotype') ?>', {
            text: text,
            mode: mode
        }, function(data) {
            if (data.update != 1) {
                $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text(data.error_text || 'Unable to process AI text.');
                return;
            }

            $('#' + target).val(data.draft_text || text);
            refreshCounters();
            markDirty('AI text updated');
            $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text(data.error_text || 'AI text ready');
        });
    });

    $(document).on('click', '.btn-scan-extract', function() {
        var file = $(this).data('file') || '';
        var type = $(this).data('type') || 'general';
        if (!file) {
            return;
        }

        $('#scan_extracted_text').val('Processing scan text...');
        apiPost('<?= base_url('Opd_prescription/scan_text_extract') ?>', {
            file_path: file,
            scan_type: type
        }, function(data) {
            if (data.update != 1) {
                $('#scan_extracted_text').val('');
                $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text(data.error_text || 'Unable to extract scan text.');
                return;
            }

            $('#scan_extracted_text').val(data.extracted_text || '');
            $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text(data.error_text || 'Scan text extracted');
        });
    });

    $('#btn_scan_to_finding').on('click', function() {
        var txt = ($('#scan_extracted_text').val() || '').trim();
        if (!txt) {
            return;
        }
        var old = ($('#finding_examinations').val() || '').trim();
        $('#finding_examinations').val(old ? (old + '\n' + txt) : txt);
        refreshCounters();
        updateScanBanner();
        markDirty('Scan text appended to Finding');
    });

    $('#btn_scan_to_investigation').on('click', function() {
        var txt = ($('#scan_extracted_text').val() || '').trim();
        if (!txt) {
            return;
        }
        var old = ($('#investigation').val() || '').trim();
        $('#investigation').val(old ? (old + '\n' + txt) : txt);
        refreshCounters();
        updateScanBanner();
        markDirty('Scan text appended to Investigation');
    });

    $('#btn_reload_patient_scan_history').on('click', function() {
        loadPatientScanHistory();
    });

    $(document).on('click', '.btn-use-history-report', function() {
        var txt = ($(this).data('report') || '').toString().trim();
        if (!txt) {
            return;
        }
        $('#scan_extracted_text').val(txt);
        $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text('History scan report loaded.');
    });

    $(document).on('click', '.btn-history-run-ai', function() {
        var $btn = $(this);
        var fileId = parseInt($btn.data('file-id') || '0', 10);
        if (fileId <= 0) {
            return;
        }

        var $item = $btn.closest('.rx-history-item');
        $btn.prop('disabled', true).text('Running...');

        apiPost('<?= base_url('Opd/scan_ai_process_file') ?>', {
            file_id: fileId,
            apply_to_opd: 0
        }, function(data) {
            $btn.prop('disabled', false).text('Run AI');

            var status = (data.ai_status || '').toString().toLowerCase();
            var $statusBadge = $item.find('.js-history-ai-status');
            $statusBadge.removeClass('bg-secondary bg-warning bg-success bg-danger text-dark');

            if (status === 'completed') {
                $statusBadge.addClass('bg-success').text('AI Ready');
            } else if (status === 'failed') {
                $statusBadge.addClass('bg-danger').text('AI Failed');
            } else if (status === 'pending' || status === 'processing') {
                $statusBadge.addClass('bg-warning text-dark').text('AI ' + (status.charAt(0).toUpperCase() + status.slice(1)));
            } else {
                $statusBadge.addClass('bg-secondary').text('AI Not Run');
            }

            if ((data.document_type || '').toString().trim() !== '') {
                $item.find('.js-history-doc-type').text(data.document_type);
            }

            if ((data.content_description || '').toString().trim() !== '') {
                var report = (data.content_description || '').toString();
                $item.find('.js-history-report').text(report);
                $item.find('.btn-use-history-report').data('report', report);
            }

            var alertText = (data.ai_alert_text || '').toString().trim();
            var alertFlag = parseInt(data.ai_alert_flag || '0', 10) === 1;
            var $alert = $item.find('.js-history-alert');
            if (alertFlag && alertText !== '') {
                $alert.removeClass('d-none').text('⚠ ' + alertText);
            } else {
                $alert.addClass('d-none').text('');
            }

            if (parseInt(data.update || '0', 10) === 1) {
                $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text('AI report updated for selected history scan.');
            } else {
                $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text((data.error_text || 'Unable to run AI for this history scan.').toString());
            }
        });
    });

    $(document).on('click', '.btn-history-toggle-image', function() {
        var $item = $(this).closest('.rx-history-item');
        $item.toggleClass('expanded');
        $(this).text($item.hasClass('expanded') ? 'Minimize' : 'Expand');
    });

    $(document).on('click', '.btn-use-current-scan-report', function() {
        var txt = ($(this).data('report') || '').toString().trim();
        if (!txt) {
            return;
        }
        $('#scan_extracted_text').val(txt);
        $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text('Current scan report loaded.');
    });

    function switchPanel(panelId) {
        $('.rx-tab-btn').removeClass('active');
        $('.rx-tab-btn[data-panel="' + panelId + '"]').addClass('active');
        $('.rx-panel').removeClass('active');
        $('#' + panelId).addClass('active');

        if (panelId === 'panel_advice') {
            loadAdviceList();
        } else if (panelId === 'panel_investigation') {
            loadInvestigationList();
        } else if (panelId === 'panel_medicine') {
            loadMedicineList();
        }
    }

    $('.rx-tab-btn').on('click', function() {
        switchPanel($(this).data('panel'));
    });

    function renderAdvice(rows) {
        var $tb = $('#tbl_advice tbody');
        $tb.empty();
        if (!rows || rows.length === 0) {
            $tb.html('<tr><td colspan="2" class="text-muted">No advice added</td></tr>');
            return;
        }
        rows.forEach(function(row) {
            var txt = row.advice_txt || row.advice || row.advice_txt_hindi || '';
            $tb.append('<tr><td>' + $('<div>').text(txt).html() + '</td><td><button type="button" class="btn btn-sm btn-danger btn-del-advice" data-id="' + (row.id || 0) + '">Remove</button></td></tr>');
        });
    }

    function loadAdviceList() {
        var opdId = $('#opd_id').val();
        var sid = parseInt($('#opd_session_id').val() || '0', 10);
        if (sid <= 0) {
            renderAdvice([]);
            return;
        }
        apiGet('<?= base_url('Opd_prescription/advice_list') ?>/' + opdId + '/' + sid, function(data) {
            renderAdvice(data.rows || []);
        });
    }

    function renderPredefinedAdvice(rows) {
        var $box = $('#predefined_advice_list');
        if (!$box.length) {
            return;
        }

        if (!rows || rows.length === 0) {
            $box.html('<div class="text-muted">No predefined advice found.</div>');
            return;
        }

        var html = '<div class="list-group list-group-flush">';
        rows.forEach(function(row) {
            var id = parseInt(row.id || '0', 10);
            var label = $('<div>').text(row.label || '').html();
            html += '<div class="list-group-item px-0 py-2 border-0 border-bottom">';
            html += '  <div class="d-flex justify-content-between align-items-start gap-2">';
            html += '      <span>' + label + '</span>';
            html += '      <button type="button" class="btn btn-link btn-sm p-0 btn-add-predefined-advice" data-id="' + id + '" data-text="' + label + '">+Add</button>';
            html += '  </div>';
            html += '</div>';
        });
        html += '</div>';
        $box.html(html);
    }

    function loadPredefinedAdvice(q) {
        var query = (q || '').toString().trim();
        var url = '<?= base_url('Opd_prescription/advice_search') ?>';
        if (query !== '') {
            url += '?q=' + encodeURIComponent(query);
        }
        apiGet(url, function(data) {
            predefinedAdviceLoaded = true;
            renderPredefinedAdvice(data.rows || []);
        });
    }

    $('#btn_toggle_predefined_advice').on('click', function() {
        var $body = $('#predefined_advice_body');
        var isOpen = !$body.hasClass('d-none');

        if (isOpen) {
            $body.addClass('d-none');
            $(this).text('Show').attr('aria-expanded', 'false');
            return;
        }

        $body.removeClass('d-none');
        $(this).text('Hide').attr('aria-expanded', 'true');

        if (!predefinedAdviceLoaded) {
            loadPredefinedAdvice('');
        }
    });

    $('#btn_reload_predefined_advice').on('click', function() {
        loadPredefinedAdvice('');
    });

    $(document).on('click', '.btn-add-predefined-advice', function() {
        var txt = ($(this).data('text') || '').toString().trim();
        var adviceId = parseInt($(this).data('id') || '0', 10);
        if (!txt) {
            return;
        }

        ensureSession(function(sid) {
            apiPost('<?= base_url('Opd_prescription/advice_add') ?>', {
                opd_id: $('#opd_id').val(),
                opd_session_id: sid,
                advice_text: txt,
                advice_id: adviceId
            }, function(data) {
                if (data.update == 1) {
                    $('#opd_session_id').val(data.opd_session_id || sid);
                    loadAdviceList();
                }
            });
        });
    });

    $('#advice_text').on('input', function() {
        var q = ($(this).val() || '').trim();
        if (q.length < 2) {
            return;
        }
        apiGet('<?= base_url('Opd_prescription/advice_search') ?>?q=' + encodeURIComponent(q), function(data) {
            var html = '';
            (data.rows || []).forEach(function(row) {
                html += '<option value="' + $('<div>').text(row.label || '').html() + '" data-id="' + (row.id || 0) + '"></option>';
            });
            $('#advice_suggest').html(html);
            renderPredefinedAdvice(data.rows || []);
        });
    });

    $('#btn_add_advice').on('click', function() {
        var txt = ($('#advice_text').val() || '').trim();
        if (!txt) {
            return;
        }
        ensureSession(function(sid) {
            apiPost('<?= base_url('Opd_prescription/advice_add') ?>', {
                opd_id: $('#opd_id').val(),
                opd_session_id: sid,
                advice_text: txt,
                advice_id: 0
            }, function(data) {
                if (data.update == 1) {
                    $('#opd_session_id').val(data.opd_session_id || sid);
                    $('#advice_text').val('');
                    loadAdviceList();
                }
            });
        });
    });

    $(document).on('click', '.rx-next-visit-chip', function() {
        var value = ($(this).data('value') || '').toString().trim();
        if (!value) {
            return;
        }
        $('#next_visit').val(value).trigger('input').trigger('change');
    });

    $(document).on('click', '.btn-del-advice', function() {
        var id = $(this).data('id');
        apiPost('<?= base_url('Opd_prescription/advice_remove') ?>/' + id, {}, function() {
            loadAdviceList();
        });
    });

    function renderInvestigation(rows) {
        var $tb = $('#tbl_investigation tbody');
        $tb.empty();
        if (!rows || rows.length === 0) {
            $tb.html('<tr><td colspan="3" class="text-muted">No investigation added</td></tr>');
            return;
        }
        rows.forEach(function(row) {
            var name = row.investigation_name || row.name || '';
            var code = row.investigation_code || row.code || '';
            $tb.append('<tr><td>' + $('<div>').text(name).html() + '</td><td>' + $('<div>').text(code).html() + '</td><td><button type="button" class="btn btn-sm btn-danger btn-del-invest" data-id="' + (row.id || 0) + '">Remove</button></td></tr>');
        });
    }

    function getExistingInvestigationNames() {
        var map = {};
        $('#tbl_investigation tbody tr').each(function() {
            var txt = ($(this).find('td:first').text() || '').trim().toLowerCase();
            if (txt) {
                map[txt] = true;
            }
        });
        return map;
    }

    function normalizeInvestigationKey(name) {
        return (name || '').toString().trim().toLowerCase();
    }

    function addInvestigationEntry(name, code, done) {
        name = (name || '').trim();
        code = (code || '').trim();
        if (!name) {
            if (typeof done === 'function') {
                done(false);
            }
            return;
        }

        ensureSession(function(sid) {
            apiPost('<?= base_url('Opd_prescription/investigation_add') ?>', {
                opd_id: $('#opd_id').val(),
                opd_session_id: sid,
                investigation_code: code,
                investigation_name: name
            }, function(data) {
                if (data.update == 1) {
                    $('#opd_session_id').val(data.opd_session_id || sid);
                    if (typeof done === 'function') {
                        done(true);
                    }
                    return;
                }
                if (typeof done === 'function') {
                    done(false);
                }
            });
        });
    }

    function batchAddInvestigations(tests) {
        tests = Array.isArray(tests) ? tests : [];
        if (!tests.length) {
            return;
        }

        var rows = tests.map(function(testName) {
            return { name: (testName || '').toString(), code: '' };
        });
        batchAddInvestigationRows(rows);
    }

    function batchAddInvestigationRows(rows) {
        rows = Array.isArray(rows) ? rows : [];
        if (!rows.length) {
            return;
        }

        if (investigationBatchActive) {
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Please wait, previous profile is still being added.');
            return;
        }

        var existing = getExistingInvestigationNames();
        var localSeen = {};
        var queue = rows.filter(function(row) {
            var key = normalizeInvestigationKey((row && row.name) ? row.name : '');
            if (!key) {
                return false;
            }
            if (existing[key] || localSeen[key]) {
                return false;
            }
            localSeen[key] = true;
            return true;
        });

        if (!queue.length) {
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('All selected tests are already added.');
            return;
        }

        investigationBatchActive = true;

        var idx = 0;
        var added = 0;
        function next() {
            if (idx >= queue.length) {
                investigationBatchActive = false;
                loadInvestigationList();
                $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text(added + ' test(s) added.');
                markDirty('Investigation shortcuts applied');
                return;
            }
            var row = queue[idx++] || {};
            var key = normalizeInvestigationKey(row.name || '');
            addInvestigationEntry((row.name || '').toString(), (row.code || '').toString(), function(ok) {
                if (ok) {
                    added++;
                    if (key) {
                        existing[key] = true;
                    }
                }
                next();
            });
        }
        next();
    }

    function loadInvestigationList() {
        var opdId = $('#opd_id').val();
        var sid = parseInt($('#opd_session_id').val() || '0', 10);
        if (sid <= 0) {
            renderInvestigation([]);
            return;
        }
        apiGet('<?= base_url('Opd_prescription/investigation_list') ?>/' + opdId + '/' + sid, function(data) {
            renderInvestigation(data.rows || []);
        });
    }

    $('#investigation_name').on('input', function() {
        var q = ($(this).val() || '').trim();
        if (q.length < 2) {
            return;
        }
        apiGet('<?= base_url('Opd_prescription/investigation_search') ?>?q=' + encodeURIComponent(q), function(data) {
            var html = '';
            (data.rows || []).forEach(function(row) {
                var text = (row.name || '') + ((row.code || '') ? ' [' + row.code + ']' : '');
                html += '<option value="' + $('<div>').text(text).html() + '" data-code="' + $('<div>').text(row.code || '').html() + '" data-name="' + $('<div>').text(row.name || '').html() + '"></option>';
            });
            $('#investigation_suggest').html(html);
        });
    });

    $('#btn_add_investigation').on('click', function() {
        var name = '';
        var code = '';

        if ($.fn && $.fn.select2 && $('#investigation_name_select2').length) {
            var selected = $('#investigation_name_select2').select2('data') || [];
            if (selected.length) {
                name = (selected[0].name || selected[0].text || '').toString().trim();
                code = (selected[0].code || '').toString().trim();
            }
        }

        if (!name) {
            var val = ($('#investigation_name').val() || '').trim();
            if (!val) {
                return;
            }
            var m = /^(.*)\s\[(.*)\]$/.exec(val);
            if (m) {
                name = m[1].trim();
                code = m[2].trim();
            } else {
                name = val;
            }
        }

        if (!name) {
            return;
        }

        var existing = getExistingInvestigationNames();
        var key = normalizeInvestigationKey(name);
        if (key && existing[key]) {
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('This investigation is already added.');
            return;
        }

        addInvestigationEntry(name, code, function(ok) {
            if (ok) {
                $('#investigation_name').val('');
                if ($.fn && $.fn.select2 && $('#investigation_name_select2').length) {
                    $('#investigation_name_select2').val(null).trigger('change');
                }
                loadInvestigationList();
            }
        });
    });

    $('#btn_clear_investigation').on('click', function() {
        var ids = [];
        $('#tbl_investigation tbody .btn-del-invest').each(function() {
            var id = parseInt($(this).data('id') || '0', 10);
            if (id > 0) {
                ids.push(id);
            }
        });

        if (!ids.length) {
            return;
        }

        var index = 0;
        function removeNext() {
            if (index >= ids.length) {
                loadInvestigationList();
                $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text('All investigations removed.');
                markDirty('Investigation list cleared');
                return;
            }

            var id = ids[index++];
            apiPost('<?= base_url('Opd_prescription/investigation_remove') ?>/' + id, {}, function() {
                removeNext();
            });
        }
        removeNext();
    });

    $(document).on('click', '.inv-quick-chip', function() {
        var test = ($(this).data('test') || '').toString().trim();
        if (!test) {
            return;
        }
        batchAddInvestigations([test]);
    });

    $(document).on('click', '.inv-profile-chip', function() {
        var key = ($(this).data('profile') || '').toString().trim();
        if (legacyInvestigationProfiles[key] && legacyInvestigationProfiles[key].length) {
            batchAddInvestigationRows(legacyInvestigationProfiles[key]);
            return;
        }

        var tests = investigationProfiles[key] || [];
        if (!tests.length) {
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('No tests found in selected profile.');
            return;
        }
        batchAddInvestigations(tests);
    });

    $('#btn_create_inv_profile').on('click', function() {
        $('#inv_profile_name').val('');
        $('#inv_profile_tests').val('');
        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(document.getElementById('invProfileModal')).show();
        } else {
            $('#invProfileModal').show();
        }
    });

    $('#btn_save_inv_profile').on('click', function() {
        var name = ($('#inv_profile_name').val() || '').toString().trim();
        var raw = ($('#inv_profile_tests').val() || '').toString();
        var tests = raw.split(/\r?\n|,/).map(function(v) { return v.trim(); }).filter(function(v) { return v !== ''; });

        if (!name || !tests.length) {
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Profile name and tests are required.');
            return;
        }

        var rows = getCustomInvestigationProfiles();
        rows.push({ name: name, tests: tests });
        saveCustomInvestigationProfiles(rows);
        refreshCustomProfileSelect();
        $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text('Custom profile saved.');

        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(document.getElementById('invProfileModal')).hide();
        } else {
            $('#invProfileModal').hide();
        }
    });

    $('#btn_apply_custom_inv_profile').on('click', function() {
        var selected = ($('#inv_custom_profile_select').val() || '').toString();
        if (!selected) {
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Select a profile first.');
            return;
        }

        if (selected.indexOf('legacy:') === 0) {
            var legacyKey = selected.substring(7);
            var legacyRows = legacyInvestigationProfiles[legacyKey] || [];
            if (!legacyRows.length) {
                $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Selected old HMS profile has no tests.');
                return;
            }
            batchAddInvestigationRows(legacyRows);
            return;
        }

        if (selected.indexOf('custom:') !== 0) {
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Invalid profile selection.');
            return;
        }

        var idx = parseInt(selected.substring(7), 10);
        var rows = getCustomInvestigationProfiles();
        if (isNaN(idx) || idx < 0 || idx >= rows.length) {
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Selected custom profile is not available.');
            return;
        }
        batchAddInvestigations(rows[idx].tests || []);
    });

    $('#advise_investigation_notes').on('input', function() {
        $('#investigation').val($(this).val() || '');
        refreshCounters();
        markDirty('Investigation notes updated');
    });

    refreshCustomProfileSelect();
    loadInvestigationShortcutsFromLegacy();
    initInvestigationPicker();
    loadMedicineDoseMasters();
    $('#advise_investigation_notes').val($('#investigation').val() || '');

    $(document).on('click', '.btn-del-invest', function() {
        var id = $(this).data('id');
        apiPost('<?= base_url('Opd_prescription/investigation_remove') ?>/' + id, {}, function() {
            loadInvestigationList();
        });
    });

    function renderMedicine(rows) {
        var $tb = $('#tbl_medicine tbody');
        $tb.empty();
        if (!rows || rows.length === 0) {
            $tb.html('<tr><td colspan="10" class="text-muted">No medicine added</td></tr>');
            return;
        }
        function esc(v) {
            return $('<div>').text(v || '').html();
        }
        rows.forEach(function(row) {
            var rowId = parseInt(row.id || 0, 10);
            var dosageText = row.dosage_label || row.dosage || '';
            var whenText = row.dosage_when_label || row.dosage_when || '';
            var freqText = row.dosage_freq_label || row.dosage_freq || '';
            var whereText = row.dosage_where_label || row.dosage_where || '';
            $tb.append('<tr>' +
                '<td>' + esc(row.med_name) + '</td>' +
                '<td>' + esc(row.med_type) + '</td>' +
                '<td>' + esc(dosageText) + '</td>' +
                '<td>' + esc(whenText) + '</td>' +
                '<td>' + esc(freqText) + '</td>' +
                '<td>' + esc(whereText) + '</td>' +
                '<td>' + esc(row.no_of_days) + '</td>' +
                '<td>' + esc(row.qty) + '</td>' +
                '<td>' + esc(row.remark) + '</td>' +
                '<td>' +
                    '<button type="button" class="btn btn-sm btn-outline-primary btn-edit-med me-1" data-id="' + rowId + '" data-name="' + esc(row.med_name) + '" data-type="' + esc(row.med_type) + '" data-dose="' + esc(row.dosage) + '" data-when="' + esc(row.dosage_when) + '" data-freq="' + esc(row.dosage_freq) + '" data-where="' + esc(row.dosage_where) + '" data-days="' + esc(row.no_of_days) + '" data-qty="' + esc(row.qty) + '" data-remark="' + esc(row.remark) + '">Edit</button>' +
                    '<button type="button" class="btn btn-sm btn-danger btn-del-med" data-id="' + rowId + '">Remove</button>' +
                '</td>' +
            '</tr>');
        });
    }

    function clearMedicineForm(resetEdit) {
        $('#med_name,#med_type,#med_days,#med_qty,#med_remark').val('');
        $('#med_dosage,#med_when,#med_freq,#med_where').val('');
        $('#med_name').removeData('med-id');
        $('#substitute_box').hide();
        $('#substitute_rows').empty();
        $('#substitute_empty').show();
        $('#substitute_note').text('');
        if (resetEdit !== false) {
            $('#med_item_id').val('0');
            $('#btn_add_medicine').text('Add').removeClass('btn-warning').addClass('btn-primary');
            $('#btn_cancel_medicine_edit').hide();
        }
    }

    function setMedicineEditMode(row) {
        $('#med_item_id').val(row.id || 0);
        $('#med_name').val(row.name || '');
        $('#med_name').removeData('med-id');
        $('#med_type').val(row.type || '');
        ensureMedicineMasterOption($('#med_dosage'), row.dose || '');
        ensureMedicineMasterOption($('#med_when'), row.when || '');
        ensureMedicineMasterOption($('#med_freq'), row.freq || '');
        ensureMedicineMasterOption($('#med_where'), row.where || '');
        $('#med_dosage').val((row.dose || '').toString());
        $('#med_when').val((row.when || '').toString());
        $('#med_freq').val((row.freq || '').toString());
        $('#med_where').val((row.where || '').toString());
        $('#med_days').val(row.days || '');
        $('#med_qty').val(row.qty || '');
        $('#med_remark').val(row.remark || '');
        $('#btn_add_medicine').text('Update').removeClass('btn-primary').addClass('btn-warning');
        $('#btn_cancel_medicine_edit').show();
        $('#med_name').focus();
    }

    function renderMedicineSubstitutes(rows) {
        var list = rows || [];
        var $box = $('#substitute_box');
        var $rows = $('#substitute_rows');
        var $empty = $('#substitute_empty');
        $rows.empty();

        if (!list.length) {
            $empty.show();
            $box.show();
            return;
        }

        $empty.hide();
        list.forEach(function(row) {
            var medId = parseInt(row.id || 0, 10);
            var name = (row.med_name || '').toString();
            var type = (row.med_type || '').toString();
            var company = (row.company_name || '').toString();
            var text = name + (type ? ' [' + type + ']' : '');
            if (company) {
                text += ' - ' + company;
            }

            var card = '<div class="border rounded px-2 py-1 d-flex align-items-center gap-2">'
                + '<div class="small">' + $('<div>').text(text).html() + '</div>'
                + '<button type="button" class="btn btn-sm btn-outline-info btn-substitute-use" '
                    + 'data-id="' + medId + '" '
                    + 'data-name="' + $('<div>').text(name).html() + '" '
                    + 'data-type="' + $('<div>').text(type).html() + '">Use</button>'
                + '<button type="button" class="btn btn-sm btn-outline-success btn-substitute-add" '
                    + 'data-id="' + medId + '" '
                    + 'data-name="' + $('<div>').text(name).html() + '" '
                    + 'data-type="' + $('<div>').text(type).html() + '">+ Add</button>'
                + '</div>';
            $rows.append(card);
        });
        $box.show();
    }

    function loadMedicineSubstitutes(medId, medName) {
        medId = parseInt(medId || '0', 10);
        medName = (medName || '').toString().trim();
        if (medId <= 0 && medName === '') {
            $('#substitute_box').hide();
            return;
        }

        $('#substitute_note').text('loading...');
        $('#substitute_empty').show();
        $('#substitute_rows').empty();
        $('#substitute_box').show();

        var url = '<?= base_url('Opd_prescription/medicine_substitutes') ?>?med_id=' + encodeURIComponent(medId)
            + '&med_name=' + encodeURIComponent(medName);
        apiGet(url, function(data) {
            var rows = (data && data.rows) ? data.rows : [];
            $('#substitute_note').text(rows.length ? (rows.length + ' option(s)') : '');
            renderMedicineSubstitutes(rows);
        });
    }

    function loadMedicineList() {
        var sid = $('#opd_session_id').val() || 0;
        if (parseInt(sid, 10) <= 0) {
            renderMedicine([]);
            return;
        }
        apiGet('<?= base_url('Opd_prescription/medicine_list') ?>/' + sid, function(data) {
            renderMedicine(data.rows || []);
        });
    }

    function renderRxGroupModal(rows) {
        var $box = $('#rx_group_modal_list');
        if (!$box.length) {
            return;
        }

        rows = rows || [];
        var q = ($('#rx_group_search').val() || '').toString().trim().toLowerCase();
        var favMap = getRxGroupFavoriteMap();

        var filtered = rows.filter(function(row) {
            var id = parseInt(row.id || '0', 10);
            var name = (row.rx_group_name || '').toString().trim();
            if (id <= 0 || !name) {
                return false;
            }

            if (q && name.toLowerCase().indexOf(q) === -1) {
                return false;
            }

            if (activeRxGroupScope === 'favorite') {
                return !!favMap[id];
            }

            if (activeRxGroupScope === 'active') {
                return parseInt(row.med_count || '0', 10) > 0;
            }

            return true;
        });

        if (!filtered.length) {
            $box.html('<div class="text-muted small">No Rx groups found.</div>');
            return;
        }

        var html = '';
        filtered.forEach(function(row) {
            var id = parseInt(row.id || '0', 10);
            var name = (row.rx_group_name || '').toString().trim();
            if (id <= 0 || !name) {
                return;
            }

            var medCount = parseInt(row.med_count || '0', 10);
            var badge = medCount > 0 ? (' (' + medCount + ')') : '';
            html += '<div class="btn-group btn-group-sm mb-1">';
            html += '  <button type="button" class="btn btn-outline-warning js-rx-group-fav" data-id="' + id + '" title="Toggle favorite">' + (favMap[id] ? '★' : '☆') + '</button>';
            html += '  <button type="button" class="btn btn-outline-secondary js-rx-group-add" data-id="' + id + '" data-name="' + $('<div>').text(name).html() + '">' + $('<div>').text(name + badge).html() + '</button>';
            html += '  <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split js-rx-group-preview-toggle" data-id="' + id + '" data-bs-toggle="dropdown" aria-expanded="false"></button>';
            html += '  <ul class="dropdown-menu p-2" style="min-width:320px; max-width:420px;">';
            html += '      <li class="small text-muted js-rx-group-preview" data-id="' + id + '">Loading medicines...</li>';
            html += '      <li><hr class="dropdown-divider"></li>';
            html += '      <li><button type="button" class="dropdown-item js-rx-group-add" data-id="' + id + '" data-name="' + $('<div>').text(name).html() + '">+ Add this group</button></li>';
            html += '  </ul>';
            html += '</div>';
        });

        $box.html(html || '<div class="text-muted small">No Rx groups found.</div>');
    }

    function loadRxGroupCatalog() {
        apiGet('<?= base_url('Opd_prescription/save_rx_group_list') ?>/0', function(data) {
            rxGroupCache = data.rows || [];
            renderRxGroupModal(rxGroupCache);
        });
    }

    function getRxGroupFavoriteMap() {
        try {
            var raw = localStorage.getItem(rxGroupFavoritesKey);
            var list = raw ? JSON.parse(raw) : [];
            if (!Array.isArray(list)) {
                return {};
            }

            var out = {};
            list.forEach(function(item) {
                var id = parseInt(item || '0', 10);
                if (id > 0) {
                    out[id] = true;
                }
            });
            return out;
        } catch (e) {
            return {};
        }
    }

    function saveRxGroupFavoriteMap(map) {
        try {
            var ids = Object.keys(map || {}).filter(function(key) {
                return !!map[key];
            }).map(function(key) {
                return parseInt(key, 10);
            }).filter(function(id) {
                return id > 0;
            });
            localStorage.setItem(rxGroupFavoritesKey, JSON.stringify(ids));
        } catch (e) {}
    }

    function loadRxGroupPreview(rxGroupId, done) {
        rxGroupId = parseInt(rxGroupId || '0', 10);
        if (rxGroupId <= 0) {
            if (typeof done === 'function') {
                done([]);
            }
            return;
        }

        if (Array.isArray(rxGroupPreviewCache[rxGroupId])) {
            if (typeof done === 'function') {
                done(rxGroupPreviewCache[rxGroupId]);
            }
            return;
        }

        apiGet('<?= base_url('Opd_prescription/rx_group_medicine_list') ?>/' + rxGroupId, function(data) {
            var rows = data.rows || [];
            rxGroupPreviewCache[rxGroupId] = rows;
            if (typeof done === 'function') {
                done(rows);
            }
        });
    }

    $(document).on('click', '.js-rx-group-preview-toggle', function() {
        var rxGroupId = parseInt($(this).data('id') || '0', 10);
        var $target = $('.js-rx-group-preview[data-id="' + rxGroupId + '"]');
        if (!$target.length) {
            return;
        }

        $target.html('<span class="text-muted">Loading medicines...</span>');
        loadRxGroupPreview(rxGroupId, function(rows) {
            if (!rows.length) {
                $target.html('<span class="text-muted">No medicines in this group.</span>');
                return;
            }

            var html = '<div style="max-height:180px; overflow:auto;">';
            rows.forEach(function(row, index) {
                var med = ((row.med_type || '') + ' ' + (row.med_name || '')).trim();
                var dose = [row.dosage || '', row.dosage_when || '', row.dosage_freq || ''].join(' ').replace(/\s+/g, ' ').trim();
                html += '<div class="small mb-1">' + (index + 1) + '. ' + $('<div>').text(med).html();
                if (dose) {
                    html += ' <span class="text-muted">(' + $('<div>').text(dose).html() + ')</span>';
                }
                html += '</div>';
            });
            html += '</div>';
            $target.html(html);
        });
    });

    $(document).on('click', '.js-rx-group-fav', function() {
        var id = parseInt($(this).data('id') || '0', 10);
        if (id <= 0) {
            return;
        }

        var favMap = getRxGroupFavoriteMap();
        favMap[id] = !favMap[id];
        saveRxGroupFavoriteMap(favMap);
        renderRxGroupModal(rxGroupCache);
    });

    function applyRxGroupToSession(rxGroupId, rxGroupName) {
        rxGroupId = parseInt(rxGroupId || '0', 10);
        rxGroupName = (rxGroupName || '').toString().trim();
        if (rxGroupId <= 0) {
            return;
        }

        ensureSession(function(sid) {
            apiPost('<?= base_url('Opd_prescription/rx_group_apply_to_session') ?>', {
                opd_id: $('#opd_id').val(),
                opd_session_id: sid,
                rx_group_id: rxGroupId
            }, function(data) {
                if (parseInt(data.update || '0', 10) !== 1) {
                    $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text((data.error_text || 'Unable to apply Rx-Group.').toString());
                    return;
                }

                $('#opd_session_id').val(data.opd_session_id || sid);
                loadMedicineList();
                if (rxGroupName) {
                    $('#rx_group_selected_name').text('Selected: ' + rxGroupName);
                }

                try {
                    var modalEl = document.getElementById('rxGroupSelectModal');
                    var modalInst = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;
                    if (modalInst) {
                        modalInst.hide();
                    }
                } catch (e) {}

                $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text((data.error_text || 'Rx-Group applied.').toString());
            });
        });
    }

    $(document).on('click', '.js-rx-group-add', function() {
        applyRxGroupToSession($(this).data('id'), $(this).data('name'));
    });

    $('#btn_open_rx_group_modal').on('click', function() {
        loadRxGroupCatalog();
        try {
            var modalEl = document.getElementById('rxGroupSelectModal');
            if (!modalEl) {
                return;
            }
            var modalInst = bootstrap.Modal.getOrCreateInstance(modalEl);
            modalInst.show();
        } catch (e) {}
    });

    // ---- Create Rx-Group from consult page ----
    var crgcMedicineList = [];
    var crgcMedSuggestRows = [];

    function populateCrgcDoseMasters() {
        var cache = medicineDoseMasterCache || { dose: [], when: [], freq: [], where: [] };
        function fill(selectId, rows, placeholder) {
            var $sel = $('#' + selectId);
            $sel.html('<option value="">' + placeholder + '</option>');
            (rows || []).forEach(function(r) {
                var val = (r.dose_sign || r.dose_sign_desc || r.name || '').toString().trim();
                var label = (r.dose_sign_desc || r.dose_sign || r.name || '').toString().trim();
                if (val) {
                    $sel.append('<option value="' + $('<div>').text(val).html() + '">' + $('<div>').text(label).html() + '</option>');
                }
            });
        }
        fill('crgc_med_dose', cache.dose, 'Dose');
        fill('crgc_med_when', cache.when, 'When');
        fill('crgc_med_freq', cache.freq, 'Freq');
        fill('crgc_med_where', cache.where, 'Where');
    }

    function renderCrgcMedicineTable() {
        var $tbody = $('#crgc_med_table tbody');
        $tbody.empty();
        if (!crgcMedicineList.length) {
            $('#crgc_med_list_wrap').hide();
            $('#crgc_med_count').text('0');
            return;
        }
        $('#crgc_med_list_wrap').show();
        $('#crgc_med_count').text(crgcMedicineList.length);
        crgcMedicineList.forEach(function(m, idx) {
            $tbody.append('<tr>'
                + '<td>' + $('<div>').text(m.med_name).html() + '</td>'
                + '<td>' + $('<div>').text(m.med_type).html() + '</td>'
                + '<td>' + $('<div>').text(m.dosage).html() + '</td>'
                + '<td>' + $('<div>').text(m.dosage_when).html() + '</td>'
                + '<td>' + $('<div>').text(m.dosage_freq).html() + '</td>'
                + '<td>' + $('<div>').text(m.no_of_days).html() + '</td>'
                + '<td>' + $('<div>').text(m.dosage_where).html() + '</td>'
                + '<td><button type="button" class="btn btn-danger btn-sm py-0 px-1 crgc-med-remove" data-idx="' + idx + '">✕</button></td>'
                + '</tr>');
        });
    }

    function clearCrgcMedForm() {
        $('#crgc_med_name').val('').removeData('med-id').removeData('med-type');
        $('#crgc_med_type,#crgc_med_days,#crgc_med_qty,#crgc_med_remark').val('');
        $('#crgc_med_dose,#crgc_med_when,#crgc_med_freq,#crgc_med_where').val('');
        $('#crgc_med_suggest').html('');
        crgcMedSuggestRows = [];
    }

    function openCreateRxGroupModal() {
        var complaints = ($('#complaints').val() || '').trim();
        var diagnosis = ($('#diagnosis').val() || '').trim();
        $('#crgc_name').val('');
        $('#crgc_complaints').val(complaints);
        $('#crgc_diagnosis').val(diagnosis);
        $('#crgc_investigation').val('');
        $('#crgc_finding').val('');
        $('#crgc_msg').hide().text('');
        crgcMedicineList = [];
        clearCrgcMedForm();
        renderCrgcMedicineTable();
        populateCrgcDoseMasters();
        try {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('rxGroupCreateModal')).show();
        } catch (e) {}
    }

    // Medicine name autocomplete inside create modal
    $('#crgc_med_name').on('input', function() {
        var q = ($(this).val() || '').trim();
        if (q.length < 2) {
            crgcMedSuggestRows = [];
            $('#crgc_med_suggest').html('');
            return;
        }
        apiGet('<?= base_url('Opd_prescription/medicine_search') ?>?q=' + encodeURIComponent(q) + '&scope=active', function(data) {
            crgcMedSuggestRows = data.rows || [];
            var html = '';
            crgcMedSuggestRows.forEach(function(row) {
                html += '<option value="' + $('<div>').text(row.med_name || '').html() + '"'  
                    + ' data-type="' + $('<div>').text(row.med_type || '').html() + '"></option>';
            });
            $('#crgc_med_suggest').html(html);

            // Auto-fill type if exact name match (Chrome fires input on datalist pick)
            var currentVal = ($('#crgc_med_name').val() || '').trim().toUpperCase();
            for (var i = 0; i < crgcMedSuggestRows.length; i++) {
                if ((crgcMedSuggestRows[i].med_name || '').trim().toUpperCase() === currentVal) {
                    $('#crgc_med_type').val(crgcMedSuggestRows[i].med_type || '');
                    $('#crgc_med_name').data('med-id', parseInt(crgcMedSuggestRows[i].id || 0, 10));
                    break;
                }
            }
        });
    });

    $('#crgc_med_name').on('change', function() {
        var val = ($(this).val() || '').trim().toUpperCase();
        for (var i = 0; i < crgcMedSuggestRows.length; i++) {
            if ((crgcMedSuggestRows[i].med_name || '').trim().toUpperCase() === val) {
                $('#crgc_med_type').val(crgcMedSuggestRows[i].med_type || '');
                $('#crgc_med_name').data('med-id', parseInt(crgcMedSuggestRows[i].id || 0, 10));
                break;
            }
        }
    });

    $('#btn_crgc_add_med').on('click', function() {
        var medName = ($('#crgc_med_name').val() || '').trim();
        if (!medName) {
            $('#crgc_med_name').focus();
            return;
        }
        crgcMedicineList.push({
            med_id: parseInt($('#crgc_med_name').data('med-id') || 0, 10),
            med_name: medName,
            med_type: ($('#crgc_med_type').val() || '').trim(),
            genericname: '',
            dosage: ($('#crgc_med_dose').val() || '').trim(),
            dosage_when: ($('#crgc_med_when').val() || '').trim(),
            dosage_freq: ($('#crgc_med_freq').val() || '').trim(),
            dosage_where: ($('#crgc_med_where').val() || '').trim(),
            no_of_days: ($('#crgc_med_days').val() || '').trim(),
            qty: ($('#crgc_med_qty').val() || '').trim(),
            remark: ($('#crgc_med_remark').val() || '').trim()
        });
        renderCrgcMedicineTable();
        clearCrgcMedForm();
        $('#crgc_med_name').focus();
    });

    $(document).on('click', '.crgc-med-remove', function() {
        var idx = parseInt($(this).data('idx') || '0', 10);
        crgcMedicineList.splice(idx, 1);
        renderCrgcMedicineTable();
    });

    function saveCrgcMedicines(rxId, medicines, done) {
        if (!medicines.length) {
            done();
            return;
        }
        var med = medicines[0];
        var rest = medicines.slice(1);
        var payload = {
            item_id: 0,
            med_id: med.med_id || 0,
            med_name: med.med_name,
            med_type: med.med_type,
            genericname: med.genericname || '',
            dosage: med.dosage,
            dosage_when: med.dosage_when,
            dosage_freq: med.dosage_freq,
            no_of_days: med.no_of_days,
            qty: med.qty,
            dosage_where: med.dosage_where,
            remark: med.remark
        };
        apiPost('<?= base_url('Opd_prescription/rx_group_medicine_save') ?>/' + rxId, payload, function() {
            saveCrgcMedicines(rxId, rest, done);
        });
    }

    function saveCreateRxGroup(andApply) {
        var name = ($('#crgc_name').val() || '').trim();
        if (!name) {
            $('#crgc_msg').text('Rx-Group Name is required.').show();
            return;
        }
        $('#crgc_msg').hide();
        $('#btn_crgc_save,#btn_crgc_save_apply').prop('disabled', true);

        var payload = {
            id: 0,
            rx_group_name: name,
            complaints: ($('#crgc_complaints').val() || '').trim(),
            diagnosis: ($('#crgc_diagnosis').val() || '').trim(),
            investigation: ($('#crgc_investigation').val() || '').trim(),
            finding_examinations: ($('#crgc_finding').val() || '').trim()
        };

        apiPost('<?= base_url('Opd_prescription/rx_group_save') ?>', payload, function(data) {
            if (parseInt(data.update || '0', 10) !== 1) {
                $('#btn_crgc_save,#btn_crgc_save_apply').prop('disabled', false);
                $('#crgc_msg').text(data.error_text || 'Unable to save Rx-Group.').show();
                return;
            }
            var newId = parseInt(data.insertid || '0', 10);
            var newName = name;
            var medicinesToSave = crgcMedicineList.slice();

            saveCrgcMedicines(newId, medicinesToSave, function() {
                $('#btn_crgc_save,#btn_crgc_save_apply').prop('disabled', false);
                loadRxGroupCatalog();
                try {
                    bootstrap.Modal.getInstance(document.getElementById('rxGroupCreateModal')).hide();
                } catch (e) {}
                var msgPart = medicinesToSave.length > 0 ? ' with ' + medicinesToSave.length + ' medicine(s).' : '.';
                $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text('Rx-Group "' + newName + '" created' + msgPart);
                if (andApply && newId > 0) {
                    applyRxGroupToSession(newId, newName);
                }
            });
        });
    }

    $('#btn_create_rx_group_modal').on('click', function() {
        openCreateRxGroupModal();
    });

    $('#btn_crgc_save').on('click', function() {
        saveCreateRxGroup(false);
    });

    $('#btn_crgc_save_apply').on('click', function() {
        saveCreateRxGroup(true);
    });

    $('#rx_group_search').on('input', function() {
        renderRxGroupModal(rxGroupCache);
    });

    $(document).on('click', '.rx-group-scope-btn', function() {
        activeRxGroupScope = ($(this).data('scope') || 'active').toString();
        $('.rx-group-scope-btn').removeClass('active');
        $(this).addClass('active');
        renderRxGroupModal(rxGroupCache);
    });

    function applyMedicineSelection(rowData) {
        var medName = (rowData.med_name || '').toString().trim();
        if (medName) { $('#med_name').val(medName); }
        $('#med_type').val((rowData.med_type || '').toString());
        var medId = parseInt(rowData.id || 0, 10);
        var isFav = parseInt(rowData.is_favorite || 0, 10) === 1;
        if (medId > 0) {
            $('#med_name').data('med-id', medId);
            $('#btn_toggle_med_favorite')
                .data('med-id', medId)
                .text(isFav ? '★' : '☆')
                .removeClass('btn-outline-warning btn-warning')
                .addClass(isFav ? 'btn-warning' : 'btn-outline-warning')
                .show();
            loadMedicineSubstitutes(medId, medName);
        }
    }

    $('#med_name').on('input', function() {
        var q = ($(this).val() || '').trim();
        if (q.length < 2) {
            medicineSuggestRows = [];
            $('#medicine_suggest').html('');
            return;
        }
        apiGet('<?= base_url('Opd_prescription/medicine_search') ?>?q=' + encodeURIComponent(q) + '&scope=' + encodeURIComponent(activeMedicineScope), function(data) {
            var html = '';
            medicineSuggestRows = data.rows || [];
            medicineSuggestRows.forEach(function(row) {
                html += '<option '
                    + 'value="' + $('<div>').text(row.med_name || '').html() + '" '
                    + 'data-name="' + $('<div>').text(row.med_name || '').html() + '" '
                    + 'data-id="' + parseInt(row.id || 0, 10) + '" '
                    + 'data-type="' + $('<div>').text(row.med_type || '').html() + '" '
                    + 'data-fav="' + parseInt(row.is_favorite || 0, 10) + '"></option>';
            });
            $('#medicine_suggest').html(html);

            // Detect datalist selection: if current value exactly matches a row, fill type etc.
            // (Chrome fires 'input' on datalist pick, not 'change')
            var currentVal = ($('#med_name').val() || '').trim().toUpperCase();
            for (var i = 0; i < medicineSuggestRows.length; i++) {
                if ((medicineSuggestRows[i].med_name || '').trim().toUpperCase() === currentVal) {
                    applyMedicineSelection(medicineSuggestRows[i]);
                    break;
                }
            }
        });
    });

    $('#med_name').on('change', function() {
        var val = ($(this).val() || '').trim();
        var matchedRow = null;
        for (var i = 0; i < medicineSuggestRows.length; i++) {
            if ((medicineSuggestRows[i].med_name || '').trim().toUpperCase() === val.toUpperCase()) {
                matchedRow = medicineSuggestRows[i];
                break;
            }
        }
        if (matchedRow) {
            applyMedicineSelection(matchedRow);
            return;
        }
        $('#med_name').removeData('med-id');
        $('#btn_toggle_med_favorite').hide().data('med-id', 0).text('☆').removeClass('btn-warning').addClass('btn-outline-warning');
        $('#substitute_box').hide();
    });

    if (!$('#btn_toggle_med_favorite').length) {
        $('#med_name').closest('.col-md-4').append('<button type="button" class="btn btn-sm btn-outline-warning mt-1" id="btn_toggle_med_favorite" style="display:none;">☆</button>');
    }

    $(document).on('click', '.med-scope-btn', function() {
        activeMedicineScope = ($(this).data('scope') || 'active').toString();
        $('.med-scope-btn').removeClass('active');
        $(this).addClass('active');

        var currentVal = ($('#med_name').val() || '').trim();
        if (currentVal.length >= 2) {
            $('#med_name').trigger('input');
        }
    });

    $('#btn_toggle_med_favorite').on('click', function() {
        var medId = parseInt($(this).data('med-id') || '0', 10);
        if (medId <= 0) {
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Select medicine from suggestion to mark favorite.');
            return;
        }

        var $btn = $(this);
        apiPost('<?= base_url('Opd_prescription/medicine_favorite_toggle') ?>', { med_id: medId }, function(data) {
            if (parseInt(data.update || 0, 10) !== 1) {
                $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text(data.error_text || 'Unable to update favorite.');
                return;
            }

            var isFav = parseInt(data.is_favorite || 0, 10) === 1;
            $btn.text(isFav ? '★' : '☆')
                .removeClass('btn-outline-warning btn-warning')
                .addClass(isFav ? 'btn-warning' : 'btn-outline-warning');

            $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text(data.error_text || (isFav ? 'Added to favorites' : 'Removed from favorites'));
            if (($('#med_name').val() || '').trim().length >= 2) {
                $('#med_name').trigger('input');
            }
        });
    });

    $('#btn_add_medicine').on('click', function() {
        var medId = parseInt($('#med_item_id').val() || '0', 10);
        var medName = ($('#med_name').val() || '').trim();
        if (!medName) {
            return;
        }
        ensureSession(function(sid) {
            var payload = {
                opd_id: $('#opd_id').val(),
                opd_session_id: sid,
                med_id: parseInt($('#med_name').data('med-id') || '0', 10),
                med_name: medName,
                med_type: $('#med_type').val(),
                dosage: $('#med_dosage').val(),
                dosage_when: $('#med_when').val(),
                dosage_freq: $('#med_freq').val(),
                dosage_where: $('#med_where').val(),
                no_of_days: $('#med_days').val(),
                qty: $('#med_qty').val(),
                remark: $('#med_remark').val()
            };
            var url = medId > 0
                ? '<?= base_url('Opd_prescription/medicine_update') ?>/' + medId
                : '<?= base_url('Opd_prescription/medicine_add') ?>';

            apiPost(url, payload, function(data) {
                if (data.update == 1) {
                    $('#opd_session_id').val(data.opd_session_id || sid);
                    clearMedicineForm(true);
                    loadMedicineList();
                }
            });
        });
    });

    $('#btn_cancel_medicine_edit').on('click', function() {
        clearMedicineForm(true);
    });

    $(document).on('click', '.btn-edit-med', function() {
        setMedicineEditMode({
            id: $(this).data('id') || 0,
            name: $(this).data('name') || '',
            type: $(this).data('type') || '',
            dose: $(this).data('dose') || '',
            when: $(this).data('when') || '',
            freq: $(this).data('freq') || '',
            where: $(this).data('where') || '',
            days: $(this).data('days') || '',
            qty: $(this).data('qty') || '',
            remark: $(this).data('remark') || ''
        });
    });

    $(document).on('click', '.btn-del-med', function() {
        var id = $(this).data('id');
        apiPost('<?= base_url('Opd_prescription/medicine_remove') ?>/' + id, {}, function() {
            if (parseInt($('#med_item_id').val() || '0', 10) === parseInt(id || '0', 10)) {
                clearMedicineForm(true);
            }
            loadMedicineList();
        });
    });

    $('#btn_clear_medicine').on('click', function() {
        var ids = [];
        $('#tbl_medicine tbody .btn-del-med').each(function() {
            var id = parseInt($(this).data('id') || '0', 10);
            if (id > 0) {
                ids.push(id);
            }
        });

        if (!ids.length) {
            return;
        }

        var idx = 0;
        function removeNext() {
            if (idx >= ids.length) {
                clearMedicineForm(true);
                loadMedicineList();
                $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text('All medicines removed.');
                markDirty('Medicine list cleared');
                return;
            }

            var id = ids[idx++];
            apiPost('<?= base_url('Opd_prescription/medicine_remove') ?>/' + id, {}, function() {
                removeNext();
            });
        }
        removeNext();
    });

    $(document).on('click', '.med-chip', function() {
        var target = $(this).data('target');
        var value = $(this).data('value') || '';
        if (!target || !$('#' + target).length) {
            return;
        }
        var $target = $('#' + target);
        if ($target.is('select')) {
            setMedicineSelectByLabel(target, value);
            $target.focus();
            return;
        }
        $target.val(value).trigger('change').focus();
    });

    $(document).on('click', '.btn-substitute-use', function() {
        var medId = parseInt($(this).data('id') || '0', 10);
        var medName = ($(this).data('name') || '').toString();
        var medType = ($(this).data('type') || '').toString();
        if (!medName) {
            return;
        }

        $('#med_name').val(medName).data('med-id', medId).trigger('change');
        if (!($('#med_type').val() || '').trim()) {
            $('#med_type').val(medType);
        }
    });

    $(document).on('click', '.btn-substitute-add', function() {
        var medId = parseInt($(this).data('id') || '0', 10);
        var medName = ($(this).data('name') || '').toString().trim();
        var medType = ($(this).data('type') || '').toString().trim();
        if (!medName) {
            return;
        }

        var $btn = $(this);
        if ($btn.prop('disabled')) {
            return;
        }

        $('#med_name').val(medName).data('med-id', medId).trigger('change');
        if (medType) {
            $('#med_type').val(medType);
        }

        $btn.prop('disabled', true).text('Adding...');
        ensureSession(function(sid) {
            apiPost('<?= base_url('Opd_prescription/medicine_add') ?>', {
                opd_id: $('#opd_id').val(),
                opd_session_id: sid,
                med_id: medId,
                med_name: medName,
                med_type: medType,
                dosage: $('#med_dosage').val(),
                dosage_when: $('#med_when').val(),
                dosage_freq: $('#med_freq').val(),
                dosage_where: $('#med_where').val(),
                no_of_days: $('#med_days').val(),
                qty: $('#med_qty').val(),
                remark: $('#med_remark').val()
            }, function(data) {
                $btn.prop('disabled', false).text('+ Add');
                if (parseInt(data.update || 0, 10) !== 1) {
                    $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text(data.error_text || 'Unable to add substitute medicine.');
                    return;
                }

                $('#opd_session_id').val(data.opd_session_id || sid);
                loadMedicineList();
                $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text('Substitute medicine added.');
            });
        });
    });

    function buildOldMedicinePayload($btn) {
        return {
            med_id: parseInt($btn.data('med-id') || '0', 10),
            med_name: ($btn.data('med-name') || '').toString().trim(),
            med_type: ($btn.data('med-type') || '').toString().trim(),
            dosage: ($btn.data('dosage') || '').toString().trim(),
            dosage_when: ($btn.data('dosage-when') || '').toString().trim(),
            dosage_freq: ($btn.data('dosage-freq') || '').toString().trim(),
            no_of_days: ($btn.data('no-of-days') || '').toString().trim(),
            qty: ($btn.data('qty') || '').toString().trim(),
            remark: ($btn.data('remark') || '').toString().trim()
        };
    }

    function addOldMedicineToCurrentRx(payload, done) {
        if (!payload || !payload.med_name) {
            if (typeof done === 'function') {
                done(false);
            }
            return;
        }

        ensureSession(function(sid) {
            apiPost('<?= base_url('Opd_prescription/medicine_add') ?>', {
                opd_id: $('#opd_id').val(),
                opd_session_id: sid,
                med_id: parseInt(payload.med_id || 0, 10),
                med_name: payload.med_name,
                med_type: payload.med_type,
                dosage: payload.dosage,
                dosage_when: payload.dosage_when,
                dosage_freq: payload.dosage_freq,
                no_of_days: payload.no_of_days,
                qty: payload.qty,
                remark: payload.remark
            }, function(data) {
                var ok = parseInt(data.update || 0, 10) === 1;
                if (ok) {
                    $('#opd_session_id').val(data.opd_session_id || sid);
                }
                if (typeof done === 'function') {
                    done(ok);
                }
            });
        });
    }

    $(document).on('click', '.btn-old-rx-add-one', function() {
        var $btn = $(this);
        if ($btn.prop('disabled')) {
            return;
        }

        var payload = buildOldMedicinePayload($btn);
        if (!payload.med_name) {
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('Old medicine name is missing.');
            return;
        }

        $btn.prop('disabled', true).text('Adding...');
        addOldMedicineToCurrentRx(payload, function(ok) {
            $btn.prop('disabled', false).text('Add');
            if (ok) {
                loadMedicineList();
                $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text('Medicine added from old prescription.');
            }
        });
    });

    $(document).on('click', '.btn-old-rx-add-all', function() {
        var $btn = $(this);
        if ($btn.prop('disabled')) {
            return;
        }

        var $card = $btn.closest('.old-rx-card');
        var $allButtons = $card.find('.btn-old-rx-add-one');
        if (!$allButtons.length) {
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('No medicine row found in selected old prescription.');
            return;
        }

        var queue = [];
        $allButtons.each(function() {
            var payload = buildOldMedicinePayload($(this));
            if (payload.med_name) {
                queue.push(payload);
            }
        });

        if (!queue.length) {
            $('.jsError').removeClass('text-success text-muted').addClass('text-danger').text('No valid medicine found to add.');
            return;
        }

        $btn.prop('disabled', true).text('+...');
        var addedCount = 0;

        var processNext = function(index) {
            if (index >= queue.length) {
                $btn.prop('disabled', false).text('+ All');
                loadMedicineList();
                $('.jsError').removeClass('text-danger text-muted').addClass('text-success').text(addedCount + ' medicine(s) added from old prescription.');
                return;
            }

            addOldMedicineToCurrentRx(queue[index], function(ok) {
                if (ok) {
                    addedCount++;
                }
                processNext(index + 1);
            });
        };

        processNext(0);
    });

    if (localStorage.getItem(draftKey)) {
        setStatus('dirty', 'Draft available');
    }

    loadAdviceList();
    loadInvestigationList();
    loadMedicineList();
    loadRxGroupCatalog();
    updateScanBanner();
})();
</script>
