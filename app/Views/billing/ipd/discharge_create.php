<?php
$ipd = $ipd_info ?? null;
$person = $person_info ?? null;
$ipdId = (int) ($ipd_id ?? 0);
$noticeText = (string) ($notice ?? '');
$noticeType = (string) ($notice_type ?? 'success');
$statusRows = $status_rows ?? [];
$departmentRows = $department_rows ?? [];
$master = $ipd_master_row ?? [];

$patientName = trim((string) ($person->p_fname ?? ''));

$patientCode = '';
if ($person) {
    $patientCode = trim((string) (
        $person->uhid
        ?? $person->UHID
        ?? $person->patient_code
        ?? $person->p_code
        ?? $person->reg_no
        ?? ''
    ));
}

$age = '';
if ($person) {
    $age = get_age_1($person->dob ?? null, $person->age ?? '', $person->age_in_month ?? '', $person->estimate_dob ?? '');
}

$dischargeDateValue = '';
if (! empty($master['discharge_date'])) {
    $ts = strtotime((string) $master['discharge_date']);
    if ($ts !== false) {
        $dischargeDateValue = date('Y-m-d', $ts);
    }
}

$dischargeTimeValue = (string) ($master['discharge_time'] ?? '');
if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $dischargeTimeValue)) {
    $dischargeTimeValue = substr($dischargeTimeValue, 0, 5);
}
$complaintRows = $complaint_rows ?? [];
$patientHistory = $patient_history_row ?? [];
$physicalExamRows = $physical_exam_rows ?? [];
$generalExamGroup1 = $physicalExamRows['general_group_1'] ?? [];
$generalExamGroup2 = $physicalExamRows['general_group_2'] ?? [];
$systemicExamRows = $physicalExamRows['systemic'] ?? [];
$manualInvestRows = $manual_invest_rows ?? [];
$specialInvestRows = $special_invest_rows ?? [];
$clinicalLabRows = $clinical_lab_rows ?? [];
$clinicalNonPathRows = $clinical_non_path_rows ?? [];
$labInvestigationList = (string) ($lab_investigation_list ?? '');
$nonPathInvestigationList = (string) ($non_path_investigation_list ?? '');
$dischargeConditionRows = $discharge_condition_rows ?? [];
$surgeryRows = $surgery_rows ?? [];
$procedureRows = $procedure_rows ?? [];
$diagnosisRows = $diagnosis_rows ?? [];
$courseRows = $course_rows ?? [];
$drugRows = $drug_rows ?? [];
$inhosRemark = (string) ($inhos_remark ?? '');
$otherExamText = (string) ($other_exam_text ?? '');
 
 $historyFields = [
     'is_smoking' => 'Smoking',
     'is_alcohol' => 'Alcohol',
     'is_tobacoo' => 'Tobacco',
     'is_drug_abuse' => 'Drug abuse',
     'is_hypertesion' => 'Hypertension',
     'is_niddm' => 'Type 2 diabetes mellitus (DM)',
     'is_hbsag' => 'HBsAg',
     'is_hcv' => 'HCV',
     'is_hiv_I_II' => 'HIV I & II',
 ];
?>

<style>
    .discharge-page-title {
        font-size: 24px;
        color: #2a5f97;
        margin-bottom: 10px;
        line-height: 1.35;
    }

    .discharge-page-title strong {
        color: #2f7d32;
    }

    .discharge-main-card {
        border: 1px solid #dde5ef;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(19, 44, 76, 0.06);
    }

    #discharge_section_nav {
        position: sticky;
        top: 72px;
        max-height: calc(100vh - 88px);
        overflow-y: auto;
        padding-right: 6px;
    }

    .discharge-nav-link {
        border-radius: 8px;
        margin-bottom: 6px;
        font-size: 14px;
        line-height: 1.35;
        color: #0d6efd;
        border: 1px solid transparent;
        transition: all 0.15s ease-in-out;
    }

    .discharge-nav-link:hover {
        background: #eff5ff;
        border-color: #d2e4ff;
        color: #0b57d0;
    }

    .discharge-nav-link.active {
        background: #0d6efd;
        color: #fff;
        border-color: #0d6efd;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.18);
    }

    .discharge-form-area .card {
        border-radius: 8px;
    }

    .discharge-form-area .card-header {
        font-size: 14px;
        background: #f8fbff;
    }

    .discharge-form-area .form-label {
        font-size: 13px;
        margin-bottom: 4px;
    }

    .discharge-form-area table th,
    .discharge-form-area table td {
        vertical-align: middle;
        font-size: 13px;
    }

    @media (max-width: 991.98px) {
        #discharge_section_nav {
            position: static;
            max-height: none;
            overflow: visible;
            padding-right: 0;
        }

        .discharge-nav-link {
            font-size: 13px;
            padding: 8px 10px;
        }
    }
</style>

<section class="content">
    <div class="discharge-page-title">
        Name : <strong><?= esc($patientName) ?></strong>
        / Gender : <strong><?= esc((string) ($person->xgender ?? '')) ?></strong>
        / Age : <strong><?= esc($age) ?></strong>
        / UHID / Patient Code : <strong><?= esc($patientCode !== '' ? $patientCode : '-') ?></strong>
        / IPD ID : <strong><?= esc((string) ($ipd->ipd_code ?? '')) ?></strong>
    </div>

    <div class="card discharge-main-card">
        <div class="card-body">
            <?php if ($noticeText !== ''): ?>
                <div class="alert alert-<?= esc($noticeType) ?> py-2" role="alert"><?= esc($noticeText) ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-3">
                    <ul class="nav flex-column nav-pills mb-3" id="discharge_section_nav" role="tablist" aria-orientation="vertical">
                        <li class="nav-item"><a href="#section-complaints" class="nav-link discharge-nav-link active" data-target="section-complaints">Presenting Complaints with Duration and Reason for Admission</a></li>
                        <li class="nav-item"><a href="#section-physical" class="nav-link discharge-nav-link" data-target="section-physical">Physical Examinations</a></li>
                        <li class="nav-item"><a href="#section-investigation" class="nav-link discharge-nav-link" data-target="section-investigation">Clinical Investigation Reports</a></li>
                        <li class="nav-item"><a href="#section-admission" class="nav-link discharge-nav-link" data-target="section-admission">Admission / Discharge Information</a></li>
                        <li class="nav-item"><a href="#section-surgery" class="nav-link discharge-nav-link" data-target="section-surgery">Surgery / Procedure / delivery if any</a></li>
                        <li class="nav-item"><a href="#section-diagnosis" class="nav-link discharge-nav-link" data-target="section-diagnosis">Final Diagnosis</a></li>
                        <li class="nav-item"><a href="#section-summary-invest" class="nav-link discharge-nav-link" data-target="section-summary-invest">Summary of key investigation during Hospitalization</a></li>
                        <li class="nav-item"><a href="#section-course" class="nav-link discharge-nav-link" data-target="section-course">Course / Treatment in the hospital</a></li>
                        <li class="nav-item"><a href="#section-condition" class="nav-link discharge-nav-link" data-target="section-condition">Condition at the time of Discharge</a></li>
                        <li class="nav-item"><a href="#section-medicine" class="nav-link discharge-nav-link" data-target="section-medicine">Discharge Medicine Prescribed</a></li>
                        <li class="nav-item"><a href="#section-instructions" class="nav-link discharge-nav-link" data-target="section-instructions">Discharge Instructions/Advise</a></li>
                    </ul>
                </div>

                <div class="col-md-9 discharge-form-area">
                    <form method="post" action="<?= site_url('Ipd_discharge/ipd_select/' . $ipdId) ?>" class="row g-3">
                        <?= csrf_field() ?>

                        <div class="card border-primary">
                            <div class="card-header py-2"><strong>Personal History</strong></div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($historyFields as $field => $label): ?>
                                        <div class="col-md-6">
                                            <div class="form-check mb-1">
                                                <input class="form-check-input" type="checkbox" id="<?= esc($field) ?>" name="<?= esc($field) ?>" value="1" <?= (int) ($patientHistory[$field] ?? 0) === 1 ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="<?= esc($field) ?>"><?= esc($label) ?></label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="card border-primary mt-3" id="section-complaints">
                            <div class="card-header py-2"><strong>Complaints with Duration and Reason for Admission</strong></div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Complaint Name</th>
                                                <th>Remarks</th>
                                                <th style="width:90px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($complaintRows)): ?>
                                                <tr><td colspan="3" class="text-muted text-center">No complaint rows yet.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($complaintRows as $row): ?>
                                                    <tr>
                                                        <td><?= esc((string) ($row['comp_report'] ?? '')) ?></td>
                                                        <td><?= esc((string) ($row['comp_remark'] ?? '')) ?></td>
                                                        <td>
                                                            <button type="submit" class="btn btn-outline-danger btn-sm" name="action" value="remove_complaint" onclick="document.getElementById('complaint_remove_id').value='<?= (int) ($row['id'] ?? 0) ?>';">Remove</button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <input type="hidden" name="complaint_remove_id" id="complaint_remove_id" value="0">

                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label">Complaints/ Reported Problems</label>
                                        <input type="text" class="form-control" name="new_complaint_name" value="">
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label">Remarks / Comments</label>
                                        <input type="text" class="form-control" name="new_complaint_remark" value="">
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary btn-sm" name="action" value="add_complaint">+ADD</button>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <label class="form-label">Other Complaints</label>
                                    <textarea id="complaint_remark_editor" class="form-control" name="complaint_remark" rows="6"><?= esc((string) ($complaint_remark ?? '')) ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="card border-primary mt-3" id="section-physical">
                            <div class="card-header py-2"><strong>Examination on Admission</strong></div>
                            <div class="card-body">
                                <h6 class="mb-2">General Examination</h6>
                                <div class="row g-2">
                                    <?php foreach ($generalExamGroup1 as $row): ?>
                                        <div class="col-md-3">
                                            <label class="form-label small"><?= esc((string) ($row['label'] ?? '')) ?></label>
                                            <input type="text" class="form-control form-control-sm" name="gen_exam_<?= (int) ($row['id'] ?? 0) ?>" value="<?= esc((string) ($row['value'] ?? '')) ?>">
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (empty($generalExamGroup1)): ?>
                                        <div class="col-12 text-muted small">No general examination master rows found (cat_group=1).</div>
                                    <?php endif; ?>
                                </div>

                                <hr>

                                <div class="row g-2">
                                    <?php foreach ($generalExamGroup2 as $row): ?>
                                        <div class="col-md-3">
                                            <label class="form-label small"><?= esc((string) ($row['label'] ?? '')) ?></label>
                                            <input type="text" class="form-control form-control-sm" name="gen_exam_<?= (int) ($row['id'] ?? 0) ?>" value="<?= esc((string) ($row['value'] ?? '')) ?>">
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (empty($generalExamGroup2)): ?>
                                        <div class="col-12 text-muted small">No general examination master rows found (cat_group=2).</div>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-3 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-outline-success btn-sm" name="action" value="save_main" data-reload-section="section-physical">Save General Examination</button>
                                </div>
                            </div>
                        </div>

                        <div class="card border-info mt-3">
                            <div class="card-header py-2"><strong>Other / Systemic Examinations</strong></div>
                            <div class="card-body">
                                <?php if (empty($systemicExamRows)): ?>
                                    <div class="text-muted small">No systemic examination master rows found.</div>
                                <?php else: ?>
                                    <?php foreach ($systemicExamRows as $row): ?>
                                        <div class="mb-3">
                                            <label class="form-label"><strong><?= esc((string) ($row['name'] ?? '')) ?></strong></label>
                                            <textarea class="form-control" name="sys_exam_<?= (int) ($row['id'] ?? 0) ?>" rows="4"><?= esc((string) ($row['value'] ?? '')) ?></textarea>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card border-info mt-3" id="section-investigation">
                            <div class="card-header py-2"><strong>Clinical Investigation Reports</strong></div>
                            <div class="card-body">
                                <div class="card border-info mb-3">
                                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                        <strong>Clinical Investigation (In-Hospital Lab)</strong>
                                        <span class="text-muted small">Blood Hb, Blood Sugar, Renal Function, Serum Bilirubin, Urine Test</span>
                                    </div>
                                    <div class="card-body py-2">
                                        <?php if (empty($clinicalLabRows)): ?>
                                            <div class="text-muted small">No pathology tests found between admission and discharge dates.</div>
                                        <?php else: ?>
                                            <?php foreach ($clinicalLabRows as $row): ?>
                                                <div class="form-check mb-1">
                                                    <input
                                                        class="form-check-input clinical-lab-check"
                                                        type="checkbox"
                                                        name="lab_investigation_dates[]"
                                                        value="<?= esc((string) ($row['inv_date'] ?? '')) ?>"
                                                        <?= ! empty($row['checked']) ? 'checked' : '' ?>>
                                                    <label class="form-check-label small">
                                                        [<?= esc((string) ($row['inv_date_label'] ?? '')) ?>]
                                                        <?= esc((string) ($row['test_list'] ?? '')) ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="card border-info mb-3">
                                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                        <strong>Clinical Investigation (X-Ray / ECG / Sonography / CT / MRI)</strong>
                                        <span class="text-muted small">Select finalized impression-based reports during this admission</span>
                                    </div>
                                    <div class="card-body py-2">
                                        <?php if (empty($clinicalNonPathRows)): ?>
                                            <div class="text-muted small">No non-pathology impression reports found between admission and discharge dates.</div>
                                        <?php else: ?>
                                            <?php foreach ($clinicalNonPathRows as $row): ?>
                                                <div class="form-check mb-2">
                                                    <input
                                                        class="form-check-input clinical-nonpath-check"
                                                        type="checkbox"
                                                        name="non_path_investigation_ids[]"
                                                        value="<?= (int) ($row['lab_request_id'] ?? 0) ?>"
                                                        <?= ! empty($row['checked']) ? 'checked' : '' ?>>
                                                    <label class="form-check-label small d-block">
                                                        [<?= esc((string) ($row['report_date_label'] ?? '')) ?>]
                                                        <strong><?= esc((string) ($row['modality'] ?? '')) ?></strong>
                                                        <?= esc((string) ($row['report_name'] ?? '')) ?>
                                                    </label>
                                                    <div class="small text-muted ms-4">
                                                        Impression: <?= esc((string) ($row['impression'] ?? '')) ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="row g-2">
                                    <?php foreach ($manualInvestRows as $row): ?>
                                        <div class="col-md-3">
                                            <label class="form-label small"><?= esc((string) ($row['label'] ?? '')) ?></label>
                                            <input type="text" class="form-control form-control-sm" name="manual_exam_<?= (int) ($row['id'] ?? 0) ?>" value="<?= esc((string) ($row['value'] ?? '')) ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <hr>

                                <div class="row g-2">
                                    <?php foreach ($specialInvestRows as $row): ?>
                                        <div class="col-md-12">
                                            <label class="form-label small"><?= esc((string) ($row['label'] ?? '')) ?></label>
                                            <input type="text" class="form-control form-control-sm" name="special_exam_<?= (int) ($row['id'] ?? 0) ?>" value="<?= esc((string) ($row['value'] ?? '')) ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="mt-3">
                                    <label class="form-label">Other Examinations / Provisional Diagnosis</label>
                                    <textarea class="form-control" name="other_exam_text" rows="4"><?= esc($otherExamText) ?></textarea>
                                </div>

                                <input type="hidden" name="lab_investigation_list" id="lab_investigation_list" value="<?= esc($labInvestigationList) ?>">
                                <input type="hidden" name="non_path_investigation_list" id="non_path_investigation_list" value="<?= esc($nonPathInvestigationList) ?>">

                                <div class="mt-3 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-outline-success btn-sm" name="action" value="save_main" data-reload-section="section-investigation">Save Clinical Investigation</button>
                                </div>
                            </div>
                        </div>

                        <div class="card border-secondary mt-3" id="section-surgery">
                            <div class="card-header py-2"><strong>Surgery / Procedure / delivery if any</strong></div>
                            <div class="card-body">
                                <h6>Surgery</h6>
                                <table class="table table-sm table-bordered">
                                    <thead><tr><th>Name</th><th>Date</th><th>Remark</th><th style="width:90px;">Action</th></tr></thead>
                                    <tbody>
                                        <?php if (empty($surgeryRows)): ?>
                                            <tr><td colspan="4" class="text-muted text-center">No surgery rows.</td></tr>
                                        <?php else: foreach ($surgeryRows as $row): ?>
                                            <tr>
                                                <td><?= esc((string) ($row['surgery_name'] ?? '')) ?></td>
                                                <td><?= esc((string) ($row['surgery_date'] ?? '')) ?></td>
                                                <td><?= esc((string) ($row['surgery_remark'] ?? '')) ?></td>
                                                <td><button type="submit" class="btn btn-outline-danger btn-sm" name="action" value="remove_surgery" onclick="document.getElementById('surgery_remove_id').value='<?= (int) ($row['id'] ?? 0) ?>';">Remove</button></td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                                <input type="hidden" name="surgery_remove_id" id="surgery_remove_id" value="0">
                                <div class="row g-2 mb-3">
                                    <div class="col-md-5"><input type="text" class="form-control" name="new_surgery_name" placeholder="Surgery name"></div>
                                    <div class="col-md-3"><input type="date" class="form-control" name="new_surgery_date"></div>
                                    <div class="col-md-3"><input type="text" class="form-control" name="new_surgery_remark" placeholder="Remark"></div>
                                    <div class="col-md-1"><button type="submit" class="btn btn-primary btn-sm" name="action" value="add_surgery">+ADD</button></div>
                                </div>

                                <h6>Procedure</h6>
                                <table class="table table-sm table-bordered">
                                    <thead><tr><th>Name</th><th>Date</th><th>Remark</th><th style="width:90px;">Action</th></tr></thead>
                                    <tbody>
                                        <?php if (empty($procedureRows)): ?>
                                            <tr><td colspan="4" class="text-muted text-center">No procedure rows.</td></tr>
                                        <?php else: foreach ($procedureRows as $row): ?>
                                            <tr>
                                                <td><?= esc((string) ($row['procedure_name'] ?? '')) ?></td>
                                                <td><?= esc((string) ($row['procedure_date'] ?? '')) ?></td>
                                                <td><?= esc((string) ($row['procedure_remark'] ?? '')) ?></td>
                                                <td><button type="submit" class="btn btn-outline-danger btn-sm" name="action" value="remove_procedure" onclick="document.getElementById('procedure_remove_id').value='<?= (int) ($row['id'] ?? 0) ?>';">Remove</button></td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                                <input type="hidden" name="procedure_remove_id" id="procedure_remove_id" value="0">
                                <div class="row g-2">
                                    <div class="col-md-5"><input type="text" class="form-control" name="new_procedure_name" placeholder="Procedure name"></div>
                                    <div class="col-md-3"><input type="date" class="form-control" name="new_procedure_date"></div>
                                    <div class="col-md-3"><input type="text" class="form-control" name="new_procedure_remark" placeholder="Remark"></div>
                                    <div class="col-md-1"><button type="submit" class="btn btn-primary btn-sm" name="action" value="add_procedure">+ADD</button></div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-secondary mt-3" id="section-diagnosis">
                            <div class="card-header py-2"><strong>Final Diagnosis</strong></div>
                            <div class="card-body">
                                <table class="table table-sm table-bordered">
                                    <thead><tr><th>Diagnosis</th><th>Remark</th><th style="width:90px;">Action</th></tr></thead>
                                    <tbody>
                                        <?php if (empty($diagnosisRows)): ?>
                                            <tr><td colspan="3" class="text-muted text-center">No diagnosis rows.</td></tr>
                                        <?php else: foreach ($diagnosisRows as $row): ?>
                                            <tr>
                                                <td><?= esc((string) ($row['comp_report'] ?? '')) ?></td>
                                                <td><?= esc((string) ($row['comp_remark'] ?? '')) ?></td>
                                                <td><button type="submit" class="btn btn-outline-danger btn-sm" name="action" value="remove_diagnosis" onclick="document.getElementById('diagnosis_remove_id').value='<?= (int) ($row['id'] ?? 0) ?>';">Remove</button></td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                                <input type="hidden" name="diagnosis_remove_id" id="diagnosis_remove_id" value="0">
                                <div class="row g-2">
                                    <div class="col-md-6"><input type="text" class="form-control" name="new_diagnosis_name" placeholder="Diagnosis"></div>
                                    <div class="col-md-5"><input type="text" class="form-control" name="new_diagnosis_remark" placeholder="Remark"></div>
                                    <div class="col-md-1"><button type="submit" class="btn btn-primary btn-sm" name="action" value="add_diagnosis">+ADD</button></div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-secondary mt-3" id="section-summary-invest">
                            <div class="card-header py-2"><strong>Summary of key investigation during Hospitalization</strong></div>
                            <div class="card-body">
                                <textarea class="form-control" name="inhos_remark" rows="4"><?= esc($inhosRemark) ?></textarea>
                            </div>
                        </div>

                        <div class="card border-secondary mt-3" id="section-course">
                            <div class="card-header py-2"><strong>Course / Treatment in the hospital</strong></div>
                            <div class="card-body">
                                <table class="table table-sm table-bordered">
                                    <thead><tr><th>Course</th><th>Remark</th><th style="width:90px;">Action</th></tr></thead>
                                    <tbody>
                                        <?php if (empty($courseRows)): ?>
                                            <tr><td colspan="3" class="text-muted text-center">No course rows.</td></tr>
                                        <?php else: foreach ($courseRows as $row): ?>
                                            <tr>
                                                <td><?= esc((string) ($row['comp_report'] ?? '')) ?></td>
                                                <td><?= esc((string) ($row['comp_remark'] ?? '')) ?></td>
                                                <td><button type="submit" class="btn btn-outline-danger btn-sm" name="action" value="remove_course" onclick="document.getElementById('course_remove_id').value='<?= (int) ($row['id'] ?? 0) ?>';">Remove</button></td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                                <input type="hidden" name="course_remove_id" id="course_remove_id" value="0">
                                <div class="row g-2">
                                    <div class="col-md-6"><input type="text" class="form-control" name="new_course_name" placeholder="Course / treatment"></div>
                                    <div class="col-md-5"><input type="text" class="form-control" name="new_course_remark" placeholder="Remark"></div>
                                    <div class="col-md-1"><button type="submit" class="btn btn-primary btn-sm" name="action" value="add_course">+ADD</button></div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-secondary mt-3" id="section-condition">
                            <div class="card-header py-2"><strong>Condition at the time of Discharge</strong></div>
                            <div class="card-body row g-2">
                                <?php if (empty($dischargeConditionRows)): ?>
                                    <div class="col-12 text-muted small">No discharge condition master rows found.</div>
                                <?php else: foreach ($dischargeConditionRows as $row): ?>
                                    <div class="col-md-3">
                                        <label class="form-label small"><?= esc((string) ($row['label'] ?? '')) ?></label>
                                        <input type="text" class="form-control form-control-sm" name="dis_exam_<?= (int) ($row['id'] ?? 0) ?>" value="<?= esc((string) ($row['value'] ?? '')) ?>">
                                    </div>
                                <?php endforeach; endif; ?>
                            </div>
                        </div>

                        <div class="card border-secondary mt-3" id="section-medicine">
                            <div class="card-header py-2"><strong>Discharge Medicine Prescribed</strong></div>
                            <div class="card-body">
                                <table class="table table-sm table-bordered">
                                    <thead><tr><th>Drug</th><th>Dose</th><th>Day</th><th style="width:90px;">Action</th></tr></thead>
                                    <tbody>
                                        <?php if (empty($drugRows)): ?>
                                            <tr><td colspan="4" class="text-muted text-center">No discharge drug rows.</td></tr>
                                        <?php else: foreach ($drugRows as $row): ?>
                                            <tr>
                                                <td><?= esc((string) ($row['drug_name'] ?? '')) ?></td>
                                                <td><?= esc((string) ($row['drug_dose'] ?? '')) ?></td>
                                                <td><?= esc((string) ($row['drug_day'] ?? '')) ?></td>
                                                <td><button type="submit" class="btn btn-outline-danger btn-sm" name="action" value="remove_drug" onclick="document.getElementById('drug_remove_id').value='<?= (int) ($row['id'] ?? 0) ?>';">Remove</button></td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                                <input type="hidden" name="drug_remove_id" id="drug_remove_id" value="0">
                                <div class="row g-2">
                                    <div class="col-md-4"><input type="text" class="form-control" name="new_drug_name" placeholder="Drug name"></div>
                                    <div class="col-md-4"><input type="text" class="form-control" name="new_drug_dose" placeholder="Dose"></div>
                                    <div class="col-md-3"><input type="text" class="form-control" name="new_drug_day" placeholder="Day / duration"></div>
                                    <div class="col-md-1"><button type="submit" class="btn btn-primary btn-sm" name="action" value="add_drug">+ADD</button></div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-secondary mt-3" id="section-admission">
                            <div class="card-header py-2"><strong>Admission / Discharge Information</strong></div>
                            <div class="card-body row g-2">
                                <div class="col-md-3">
                                    <label class="form-label">Department</label>
                                    <select name="dept_id" class="form-select">
                                        <option value="0">Select</option>
                                        <?php foreach ($departmentRows as $row): ?>
                                            <?php $deptId = (int) ($row['iId'] ?? 0); ?>
                                            <option value="<?= $deptId ?>" <?= $deptId === (int) ($master['dept_id'] ?? 0) ? 'selected' : '' ?>>
                                                <?= esc((string) ($row['vName'] ?? '')) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Patient Status</label>
                                    <select name="discarge_patient_status" class="form-select">
                                        <option value="0">Select</option>
                                        <?php foreach ($statusRows as $row): ?>
                                            <?php $statusId = (int) ($row['id'] ?? 0); ?>
                                            <option value="<?= $statusId ?>" <?= $statusId === (int) ($master['discarge_patient_status'] ?? 0) ? 'selected' : '' ?>>
                                                <?= esc((string) ($row['status_desc'] ?? '')) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Discharge Date</label>
                                    <input type="date" class="form-control" name="discharge_date" value="<?= esc($dischargeDateValue) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Discharge Time</label>
                                    <input type="time" class="form-control" name="discharge_time" value="<?= esc($dischargeTimeValue) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="card border-secondary mt-3" id="section-instructions">
                            <div class="card-header py-2"><strong>Summary Blocks</strong></div>
                            <div class="card-body row g-2">
                                <div class="col-md-12">
                                    <label class="form-label">Final Diagnosis</label>
                                    <textarea class="form-control" name="diagnosis_remark" rows="3"><?= esc((string) ($diagnosis_remark ?? '')) ?></textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Course / Treatment in Hospital</label>
                                    <textarea class="form-control" name="course_remark" rows="3"><?= esc((string) ($course_remark ?? '')) ?></textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Discharge Instructions / Advice</label>
                                    <textarea class="form-control" name="instruction_remark" rows="3"><?= esc((string) ($instruction_remark ?? '')) ?></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Review After (days/text)</label>
                                    <input type="text" class="form-control" name="review_after" value="<?= esc((string) ($review_after ?? '')) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="col-12 d-flex flex-wrap gap-2 mt-2">
                            <button type="submit" class="btn btn-success" name="action" value="save_main">Save Draft</button>
                            <button type="button" class="btn btn-primary" onclick="openDischargePreview('<?= site_url('Ipd_discharge/preview_discharge_report/' . $ipdId . '?regen=1') ?>', 'Discharge Preview');">Create IPD Discharge</button>
                            <button type="button" class="btn btn-outline-primary" onclick="openDischargePreview('<?= site_url('Ipd_discharge/preview_discharge_report/' . $ipdId) ?>', 'Discharge Preview');">Preview</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function() {
        window.openDischargePreview = function(url, title) {
            if (typeof window.load_form === 'function') {
                window.load_form(url, title || 'Discharge Preview');
                return;
            }

            window.location.href = url;
        };

        function initComplaintEditor() {
            if (!window.CKEDITOR) {
                return;
            }

            CKEDITOR.config.versionCheck = false;
            CKEDITOR.config.removePlugins = '';
            if (CKEDITOR.instances.complaint_remark_editor) {
                CKEDITOR.instances.complaint_remark_editor.destroy(true);
            }

            if (document.getElementById('complaint_remark_editor')) {
                CKEDITOR.replace('complaint_remark_editor', {
                    height: 180
                });
            }
        }

        function syncEditorValues() {
            if (!window.CKEDITOR) {
                return;
            }

            for (var key in CKEDITOR.instances) {
                if (Object.prototype.hasOwnProperty.call(CKEDITOR.instances, key)) {
                    CKEDITOR.instances[key].updateElement();
                }
            }
        }

        initComplaintEditor();

        function patchNoticeFromHtml(holder) {
            var nextNotice = holder.querySelector('.alert[role="alert"]');
            var currentNotice = document.querySelector('.alert[role="alert"]');

            if (!nextNotice) {
                return;
            }

            if (currentNotice) {
                currentNotice.outerHTML = nextNotice.outerHTML;
                return;
            }

            var cardBody = document.querySelector('.discharge-main-card .card-body');
            if (cardBody) {
                cardBody.insertAdjacentHTML('afterbegin', nextNotice.outerHTML);
            }
        }

        function notifyFromHtml(holder) {
            var nextNotice = holder.querySelector('.alert[role="alert"]');
            if (!nextNotice) {
                return;
            }

            var text = (nextNotice.textContent || '').trim();
            if (text === '') {
                return;
            }

            var level = 'info';
            if (nextNotice.classList.contains('alert-success')) {
                level = 'success';
            } else if (nextNotice.classList.contains('alert-warning')) {
                level = 'warning';
            } else if (nextNotice.classList.contains('alert-danger')) {
                level = 'error';
            }

            if (typeof window.notify === 'function') {
                window.notify(level, 'Discharge Update', text);
            }
        }

        function updateCsrfFromHtml(holder, form) {
            var nextCsrf = holder.querySelector('input[name="csrf_test_name"], input[name^="csrf_"]');
            var currentCsrf = form.querySelector('input[name="csrf_test_name"], input[name^="csrf_"]');
            if (nextCsrf && currentCsrf) {
                currentCsrf.name = nextCsrf.name;
                currentCsrf.value = nextCsrf.value;
            }
        }

        function syncClinicalLabSelection(form) {
            var hidden = form.querySelector('#lab_investigation_list');
            if (!hidden) {
                return;
            }

            var checked = form.querySelectorAll('.clinical-lab-check:checked');
            var values = [];
            checked.forEach(function(el) {
                if (el.value) {
                    values.push(el.value);
                }
            });

            hidden.value = values.join(',');

            var nonPathHidden = form.querySelector('#non_path_investigation_list');
            if (!nonPathHidden) {
                return;
            }

            var checkedNonPath = form.querySelectorAll('.clinical-nonpath-check:checked');
            var nonPathValues = [];
            checkedNonPath.forEach(function(el) {
                if (el.value) {
                    nonPathValues.push(el.value);
                }
            });

            nonPathHidden.value = nonPathValues.join(',');
        }

        function patchSectionFromHtml(holder, sectionId) {
            if (!sectionId) {
                return false;
            }

            var nextSection = holder.querySelector('#' + sectionId);
            var currentSection = document.getElementById(sectionId);
            if (!nextSection || !currentSection) {
                return false;
            }

            currentSection.outerHTML = nextSection.outerHTML;
            return true;
        }

        function patchComplaintSectionFromHtml(holder, form) {
            patchSectionFromHtml(holder, 'section-complaints');
            updateCsrfFromHtml(holder, form);

            patchNoticeFromHtml(holder);
        }

        function patchFormAreaFromHtml(holder) {
            var nextTitle = holder.querySelector('.discharge-page-title');
            var currentTitle = document.querySelector('.discharge-page-title');
            if (nextTitle && currentTitle) {
                currentTitle.outerHTML = nextTitle.outerHTML;
            }

            var nextArea = holder.querySelector('.discharge-form-area');
            var currentArea = document.querySelector('.discharge-form-area');
            if (nextArea && currentArea) {
                currentArea.outerHTML = nextArea.outerHTML;
            }

            patchNoticeFromHtml(holder);
        }

        function bindDischargeAjaxSubmit() {
            var form = document.querySelector('form[action*="Ipd_discharge/ipd_select/"]');
            if (!form || form.dataset.ajaxBound === '1') {
                return;
            }

            form.dataset.ajaxBound = '1';
            var lastSubmitControl = null;

            form.addEventListener('click', function(evt) {
                var el = evt.target;
                if (!el) {
                    return;
                }

                var submitControl = el.closest('button[type="submit"], input[type="submit"]');
                if (submitControl) {
                    lastSubmitControl = submitControl;
                }
            });

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                syncEditorValues();
                syncClinicalLabSelection(form);

                if (!window.jQuery) {
                    form.submit();
                    return;
                }

                var $form = window.jQuery(form);
                var payloadArray = $form.serializeArray();
                var submitter = e.submitter || lastSubmitControl;

                if (submitter && submitter.name) {
                    var exists = payloadArray.some(function(item) {
                        return item.name === submitter.name;
                    });
                    if (!exists) {
                        payloadArray.push({
                            name: submitter.name,
                            value: submitter.value || ''
                        });
                    }
                }

                var payload = window.jQuery.param(payloadArray);
                var actionValue = submitter && submitter.name === 'action' ? String(submitter.value || '') : '';
                var isComplaintAction = actionValue === 'add_complaint' || actionValue === 'remove_complaint';
                var targetSectionId = '';

                if (submitter && submitter.dataset && submitter.dataset.reloadSection) {
                    targetSectionId = String(submitter.dataset.reloadSection);
                }

                if (!targetSectionId && submitter && typeof submitter.closest === 'function') {
                    var sectionCard = submitter.closest('.card[id]');
                    if (sectionCard && sectionCard.id) {
                        targetSectionId = sectionCard.id;
                    }
                }

                window.jQuery.ajax({
                    url: form.getAttribute('action') || window.location.href,
                    type: 'POST',
                    data: payload,
                    dataType: 'html',
                    timeout: 120000
                }).done(function(html) {
                    var holder = document.createElement('div');
                    holder.innerHTML = html;
                    updateCsrfFromHtml(holder, form);
                    patchNoticeFromHtml(holder);
                    notifyFromHtml(holder);

                    if (isComplaintAction) {
                        patchComplaintSectionFromHtml(holder, form);
                        initComplaintEditor();
                        bindDischargeAjaxSubmit();
                        syncNavOnScroll();
                        return;
                    }

                    if (patchSectionFromHtml(holder, targetSectionId)) {
                        if (targetSectionId === 'section-complaints') {
                            initComplaintEditor();
                        }
                        bindDischargeAjaxSubmit();
                        syncNavOnScroll();
                        return;
                    }

                    patchFormAreaFromHtml(holder);
                    initComplaintEditor();
                    bindDischargeAjaxSubmit();
                    syncNavOnScroll();
                }).fail(function() {
                    alert('Unable to save right now. Please retry.');
                });
            });
        }

        bindDischargeAjaxSubmit();

        var navLinks = document.querySelectorAll('.discharge-nav-link');
        var sectionIds = [
            'section-complaints',
            'section-physical',
            'section-investigation',
            'section-admission',
            'section-surgery',
            'section-diagnosis',
            'section-summary-invest',
            'section-course',
            'section-condition',
            'section-medicine',
            'section-instructions'
        ];

        function setActiveNavBySection(sectionId) {
            navLinks.forEach(function(link) {
                link.classList.toggle('active', link.getAttribute('data-target') === sectionId);
            });
        }

        navLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                var sectionId = link.getAttribute('data-target');
                var section = document.getElementById(sectionId);
                if (!section) {
                    return;
                }

                section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                setActiveNavBySection(sectionId);
            });
        });

        function syncNavOnScroll() {
            var bestSection = sectionIds[0];
            var bestDelta = Number.POSITIVE_INFINITY;

            sectionIds.forEach(function(id) {
                var section = document.getElementById(id);
                if (!section) {
                    return;
                }
                var rect = section.getBoundingClientRect();
                var delta = Math.abs(rect.top - 120);
                if (delta < bestDelta) {
                    bestDelta = delta;
                    bestSection = id;
                }
            });

            setActiveNavBySection(bestSection);
        }

        window.addEventListener('scroll', syncNavOnScroll, { passive: true });
        syncNavOnScroll();
    })();
    </script>
</section>
