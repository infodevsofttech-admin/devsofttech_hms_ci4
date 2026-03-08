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
$systemicExamText = '';
if (! empty($systemicExamRows)) {
    $systemicParts = [];
    foreach ($systemicExamRows as $row) {
        $value = trim((string) ($row['value'] ?? ''));
        if ($value === '') {
            continue;
        }

        $name = trim((string) ($row['name'] ?? ''));
        if ($name !== '' && stripos($value, $name . ':') !== 0) {
            $systemicParts[] = $name . ': ' . $value;
        } else {
            $systemicParts[] = $value;
        }
    }

    if (! empty($systemicParts)) {
        $systemicExamText = implode(PHP_EOL, $systemicParts);
    }
}
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
$painValue = (string) ($pain_value ?? '');
$opdHistorySnapshot = $opd_history_snapshot ?? [];
$nursingAdmissionSnapshot = $nursing_admission_snapshot ?? [];
$drugAllergyStatus = trim((string) ($opdHistorySnapshot['drug_allergy_status'] ?? ''));
$drugAllergyDetails = trim((string) ($opdHistorySnapshot['drug_allergy_details'] ?? ''));
$adrHistory = trim((string) ($opdHistorySnapshot['adr_history'] ?? ''));
$currentMedications = trim((string) ($opdHistorySnapshot['current_medications'] ?? ''));
$coMorbiditiesText = trim((string) ($opdHistorySnapshot['co_morbidities'] ?? ''));
$womenLmp = trim((string) ($opdHistorySnapshot['women_lmp'] ?? ''));
$womenLastBaby = trim((string) ($opdHistorySnapshot['women_last_baby'] ?? ''));
$womenPregnancyRelated = trim((string) ($opdHistorySnapshot['women_pregnancy_related'] ?? ''));
$womenRelatedProblems = trim((string) ($opdHistorySnapshot['women_related_problems'] ?? ''));
$hpiNote = trim((string) ($opdHistorySnapshot['hpi_note'] ?? ''));
$nursingHistoryRecordedAt = trim((string) ($nursingAdmissionSnapshot['recorded_at'] ?? ''));
$isFemalePatient = strtolower(trim((string) ($person->xgender ?? ''))) === 'female';
$hasNursingHistoryPrefill = $nursingHistoryRecordedAt !== ''
    && ($hpiNote !== ''
        || $drugAllergyStatus !== ''
        || $coMorbiditiesText !== ''
        || $womenLmp !== ''
        || $womenLastBaby !== ''
        || $womenPregnancyRelated !== ''
        || $womenRelatedProblems !== '');
if ($coMorbiditiesText === '') {
    $coMorbidFallback = [];
    if ((int) ($patientHistory['is_niddm'] ?? 0) === 1) {
        $coMorbidFallback[] = 'Diabetes mellitus (DM)';
    }
    if ((int) ($patientHistory['is_hypertesion'] ?? 0) === 1) {
        $coMorbidFallback[] = 'Hypertension';
    }
    if (! empty($coMorbidFallback)) {
        $coMorbiditiesText = implode(', ', $coMorbidFallback);
    }
}

$coMorbidityOptions = [
    'dm' => ['label' => 'Diabetes mellitus (DM)', 'keywords' => ['diabetes mellitus', 'diabetes', 'dm']],
    'htn' => ['label' => 'high blood pressure (HTn)', 'keywords' => ['high blood pressure', 'hypertension', 'htn']],
    'cad' => ['label' => 'Coronary artery disease (CAD)', 'keywords' => ['coronary artery disease', 'cad']],
    'copd' => ['label' => 'Chronic Obstructive Pulmonary Disease', 'keywords' => ['chronic obstructive pulmonary disease', 'copd']],
    'cva' => ['label' => 'cerebral vascular accident (CVA)', 'keywords' => ['cerebral vascular accident', 'stroke', 'cva']],
];
$coMorbiditySelected = array_fill_keys(array_keys($coMorbidityOptions), false);
$coMorbidityOtherText = '';
if ($coMorbiditiesText !== '') {
    $coMorbidityParts = preg_split('/[,;\n]+/', $coMorbiditiesText);
    if (is_array($coMorbidityParts)) {
        $otherParts = [];
        foreach ($coMorbidityParts as $part) {
            $cleanPart = trim((string) $part);
            if ($cleanPart === '') {
                continue;
            }
            $normalizedPart = strtolower($cleanPart);
            $matched = false;
            foreach ($coMorbidityOptions as $key => $opt) {
                foreach (($opt['keywords'] ?? []) as $kw) {
                    if ($kw !== '' && strpos($normalizedPart, strtolower((string) $kw)) !== false) {
                        $coMorbiditySelected[$key] = true;
                        $matched = true;
                        break;
                    }
                }
                if ($matched) {
                    break;
                }
            }
            if (! $matched) {
                $otherParts[] = $cleanPart;
            }
        }
        $coMorbidityOtherText = implode(', ', $otherParts);
    }
}

$allergyStatusNormalized = strtolower($drugAllergyStatus);
$allergyStatusKnown = in_array($allergyStatusNormalized, ['known'], true);
$allergyStatusUnknown = in_array($allergyStatusNormalized, ['allergies not known', 'not known', 'unknown'], true);
$allergyStatusNoKnown = in_array($allergyStatusNormalized, ['no known drug allergy', 'none', 'no known allergy'], true);
 
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
    .discharge-page {
        --dc-border: #e6edf5;
        --dc-muted-bg: #f8f9fc;
    }

    .discharge-page-title {
        font-size: 24px;
        color: #2a5f97;
        margin-bottom: 14px;
        line-height: 1.35;
    }

    .discharge-page-title strong {
        color: #2f7d32;
    }

    .discharge-main-card {
        border: 1px solid var(--dc-border);
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(19, 44, 76, 0.06);
    }

    .discharge-main-card > .card-body {
        padding: 1rem 1rem 1.15rem;
    }

    .discharge-side-panel {
        border: 1px solid var(--dc-border);
        background: var(--dc-muted-bg);
        border-radius: 10px;
        padding: 10px;
        margin-bottom: 12px;
    }

    #discharge_section_nav {
        position: sticky;
        top: 72px;
        max-height: calc(100vh - 88px);
        overflow-y: auto;
        padding-right: 4px;
        margin-bottom: 0;
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
        margin-top: 0.9rem;
        border-color: var(--dc-border);
    }

    .discharge-form-area form > .card:first-child {
        margin-top: 0;
    }

    .discharge-form-area .card-header {
        font-size: 14px;
        background: var(--dc-muted-bg);
    }

    .discharge-form-area .card-body {
        padding: 0.95rem;
    }

    .discharge-page .btn {
        border-radius: 0.45rem;
        font-weight: 500;
        letter-spacing: 0.01em;
    }

    .discharge-page .btn-sm {
        padding: 0.34rem 0.68rem;
        font-size: 0.79rem;
        line-height: 1.25;
    }

    .discharge-page .btn-outline-primary {
        color: #4154f1;
        border-color: #c7d2fe;
        background: #f8f9ff;
    }

    .discharge-page .btn-outline-primary:hover,
    .discharge-page .btn-outline-primary:focus {
        color: #fff;
        background: #4154f1;
        border-color: #4154f1;
    }

    .discharge-page .btn-outline-success {
        color: #198754;
        border-color: #bfe5d3;
        background: #f6fcf9;
    }

    .discharge-page .btn-outline-success:hover,
    .discharge-page .btn-outline-success:focus {
        color: #fff;
        background: #198754;
        border-color: #198754;
    }

    .discharge-page .btn-outline-info {
        color: #0dcaf0;
        border-color: #bdeaf4;
        background: #f5fdff;
    }

    .discharge-page .btn-outline-info:hover,
    .discharge-page .btn-outline-info:focus {
        color: #fff;
        background: #0aa2c0;
        border-color: #0aa2c0;
    }

    .discharge-page .btn-outline-warning {
        color: #b45309;
        border-color: #f4d6a6;
        background: #fffaf1;
    }

    .discharge-page .btn-outline-warning:hover,
    .discharge-page .btn-outline-warning:focus {
        color: #fff;
        background: #d97706;
        border-color: #d97706;
    }

    .discharge-page .btn-outline-danger {
        color: #dc3545;
        border-color: #f2c4c9;
        background: #fff8f8;
    }

    .discharge-page .btn-outline-danger:hover,
    .discharge-page .btn-outline-danger:focus {
        color: #fff;
        background: #dc3545;
        border-color: #dc3545;
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

    .discharge-page .table {
        border-color: #e6edf5;
        margin-bottom: 0.5rem;
    }

    .discharge-page .table > :not(caption) > * > * {
        padding: 0.48rem 0.52rem;
    }

    .discharge-page .table thead th {
        background: #f5f7fb;
        color: #012970;
        font-weight: 600;
        border-bottom-width: 1px;
        border-color: #e1e8f2;
        white-space: nowrap;
    }

    .discharge-page .table tbody tr:hover {
        background: #fbfcff;
    }

    .discharge-page .table tbody td {
        border-color: #edf2f8;
    }

    .discharge-page .table-responsive {
        margin-top: 0.35rem;
    }

    .rx-chip-wrap {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .rx-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 8px;
        border: 1px solid #c9ddff;
        border-radius: 14px;
        background: #f3f8ff;
        font-size: 12px;
    }

    .rx-chip button {
        border: none;
        background: transparent;
        color: #b02a37;
        line-height: 1;
        padding: 0;
        font-size: 14px;
        cursor: pointer;
    }

    .complaint-status {
        font-size: 12px;
        margin-top: 6px;
        min-height: 18px;
    }

    @media (max-width: 991.98px) {
        #discharge_section_nav {
            position: static;
            max-height: none;
            overflow: visible;
            padding-right: 0;
        }

        .discharge-side-panel {
            margin-bottom: 10px;
        }

        .discharge-form-area {
            margin-top: 0.75rem;
        }

        .discharge-nav-link {
            font-size: 13px;
            padding: 8px 10px;
        }
    }
</style>

<section class="content discharge-page">
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
                    <div class="discharge-side-panel">
                    <ul class="nav flex-column nav-pills" id="discharge_section_nav" role="tablist" aria-orientation="vertical">
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

                        <div class="card border-info mt-3">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <strong>From Nursing History &amp; Physical Assessment</strong>
                                <?php if ($hasNursingHistoryPrefill): ?>
                                    <span class="badge bg-success">Prefilled from Nursing H&amp;P</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">No Nursing H&amp;P prefill found</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <?php if ($nursingHistoryRecordedAt !== ''): ?>
                                    <div class="small text-muted mb-2">Admission snapshot time: <?= esc($nursingHistoryRecordedAt) ?></div>
                                <?php endif; ?>
                                <div class="row g-2">
                                    <div class="col-md-12">
                                        <label class="form-label mb-1 d-flex justify-content-between align-items-center">
                                            <span>History &amp; Physical Note (HPI)</span>
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="btn_copy_hpi_to_complaints">Copy H&amp;P Note to Other Complaints</button>
                                        </label>
                                        <textarea class="form-control form-control-sm" name="hpi_note" rows="2" placeholder="Nursing H&P summary"><?= esc($hpiNote) ?></textarea>
                                    </div>

                                    <?php if ($isFemalePatient): ?>
                                        <div class="col-md-3">
                                            <label class="form-label mb-1">Women Related LMP</label>
                                            <input type="text" class="form-control form-control-sm" name="women_lmp" value="<?= esc($womenLmp) ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label mb-1">Last Baby</label>
                                            <input type="text" class="form-control form-control-sm" name="women_last_baby" value="<?= esc($womenLastBaby) ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label mb-1">Pregnancy Related</label>
                                            <input type="text" class="form-control form-control-sm" name="women_pregnancy_related" value="<?= esc($womenPregnancyRelated) ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label mb-1">Women Related Problems</label>
                                            <input type="text" class="form-control form-control-sm" name="women_related_problems" value="<?= esc($womenRelatedProblems) ?>">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="card border-primary mt-3" id="section-complaints">
                            <div class="card-header py-2"><strong>Complaints with Duration and Reason for Admission</strong></div>
                            <div class="card-body">
                                <input type="hidden" name="complaint_remove_id" id="complaint_remove_id" value="0">
                                <input type="hidden" name="new_complaint_name" id="new_complaint_name" value="">
                                <input type="hidden" name="new_complaint_remark" id="new_complaint_remark" value="">
                                <button type="submit" class="d-none" id="btn_add_complaint_row" name="action" value="add_complaint" data-reload-section="section-complaints">Add Complaint Row</button>

                                <div class="mb-3">
                                    <label class="form-label">Smart Complaints Picker (English + Hinglish)</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="discharge_complaint_lookup" list="discharge_complaint_suggest" placeholder="Type: bukhar, khansi, pet dard, chakkar...">
                                        <button type="button" class="btn btn-outline-primary" id="btn_discharge_add_complaint">Add</button>
                                        <button type="button" class="btn btn-outline-success" id="btn_discharge_ai_draft">AI Draft</button>
                                    </div>
                                    <datalist id="discharge_complaint_suggest"></datalist>
                                    <div id="discharge_complaint_status" class="complaint-status text-muted"></div>
                                </div>

                                <div class="table-responsive mb-3">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead>
                                            <tr>
                                                <th>Complaint Name</th>
                                                <th>Remark</th>
                                                <th style="width:90px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($complaintRows)): ?>
                                                <tr><td colspan="3" class="text-muted text-center">No complaint rows yet.</td></tr>
                                            <?php else: foreach ($complaintRows as $row): ?>
                                                <tr>
                                                    <td><?= esc((string) ($row['comp_report'] ?? '')) ?></td>
                                                    <td><?= esc((string) ($row['comp_remark'] ?? '')) ?></td>
                                                    <td>
                                                        <button type="submit" class="btn btn-outline-danger btn-sm" name="action" value="remove_complaint" data-reload-section="section-complaints" onclick="document.getElementById('complaint_remove_id').value='<?= (int) ($row['id'] ?? 0) ?>';">Remove</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                </div>


                                <div class="mt-3">
                                    <h6 class="mb-2">Pain Measurement Scale</h6>
                                    <input type="hidden" name="pain_value" id="pain_value" value="<?= esc($painValue) ?>">
                                    <div class="btn-group flex-wrap" role="group" aria-label="Pain Measurement Scale">
                                        <input type="radio" class="btn-check pain-option" name="options-pain" id="pain_0" value="0" <?= $painValue === '0' ? 'checked' : '' ?>><label class="btn btn-sm btn-outline-success" for="pain_0">No Pain</label>
                                        <input type="radio" class="btn-check pain-option" name="options-pain" id="pain_1" value="1" <?= $painValue === '1' ? 'checked' : '' ?>><label class="btn btn-sm btn-outline-primary" for="pain_1">Mild Pain</label>
                                        <input type="radio" class="btn-check pain-option" name="options-pain" id="pain_2" value="2" <?= $painValue === '2' ? 'checked' : '' ?>><label class="btn btn-sm btn-outline-info" for="pain_2">Moderate</label>
                                        <input type="radio" class="btn-check pain-option" name="options-pain" id="pain_3" value="3" <?= $painValue === '3' ? 'checked' : '' ?>><label class="btn btn-sm btn-outline-warning" for="pain_3">Intense</label>
                                        <input type="radio" class="btn-check pain-option" name="options-pain" id="pain_4" value="4" <?= $painValue === '4' ? 'checked' : '' ?>><label class="btn btn-sm btn-outline-danger" for="pain_4">Worst Pain Possible</label>
                                    </div>
                                </div>

                                <div class="row g-2 mt-2">
                                    <div class="col-md-12">
                                        <div class="border rounded p-2">
                                            <h6 class="mb-2">Drug Allergy / ADR (NABH)</h6>
                                            <div class="small text-danger mb-2">Required: Drug Allergy Status is mandatory. If status is Known, Drug Allergy Details are mandatory.</div>
                                            <div class="row g-2">
                                                <div class="col-md-4">
                                                    <label class="form-label mb-1">Drug Allergy Status <span class="text-danger">*</span></label>
                                                    <select class="form-select form-select-sm" id="drug_allergy_status" name="drug_allergy_status">
                                                        <option value="">Select status</option>
                                                        <option value="Known" <?= $allergyStatusKnown ? 'selected' : '' ?>>Known</option>
                                                        <option value="Allergies Not Known" <?= $allergyStatusUnknown ? 'selected' : '' ?>>Allergies Not Known</option>
                                                        <option value="No Known Drug Allergy" <?= $allergyStatusNoKnown ? 'selected' : '' ?>>No Known Drug Allergy</option>
                                                    </select>
                                                    <div id="drug_allergy_status_error" class="invalid-feedback d-block" style="display:none;"></div>
                                                </div>
                                                <div class="col-md-8">
                                                    <label class="form-label mb-1">Drug Allergy Details <span class="text-danger">*</span> <span class="small text-muted">(when status = Known)</span></label>
                                                    <input type="text" class="form-control form-control-sm" id="drug_allergy_details" name="drug_allergy_details" value="<?= esc($drugAllergyDetails) ?>" placeholder="e.g. Penicillin rash, NSAID gastritis">
                                                    <div id="drug_allergy_details_error" class="invalid-feedback d-block" style="display:none;"></div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label mb-1">ADR History</label>
                                                    <input type="text" class="form-control form-control-sm" name="adr_history" value="<?= esc($adrHistory) ?>" placeholder="Previous adverse drug reaction details">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label mb-1">Current Medications</label>
                                                    <input type="text" class="form-control form-control-sm" name="current_medications" value="<?= esc($currentMedications) ?>" placeholder="Current/ongoing medicines">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="border rounded p-2">
                                            <h6 class="mb-2">Co-Morbidities</h6>
                                            <div class="d-flex flex-wrap gap-2 small">
                                                <?php foreach ($coMorbidityOptions as $mKey => $mOpt): ?>
                                                    <label class="me-2">
                                                        <input type="checkbox" class="co-morbidity-item" value="<?= esc((string) ($mOpt['label'] ?? '')) ?>" <?= ! empty($coMorbiditySelected[$mKey]) ? 'checked' : '' ?>>
                                                        <?= esc((string) ($mOpt['label'] ?? '')) ?>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="mt-2">
                                                <label class="form-label small mb-1">Other Co-Morbidities</label>
                                                <input type="text" class="form-control form-control-sm" id="co_morbidities_other" value="<?= esc($coMorbidityOtherText) ?>" placeholder="Add other co-morbidities if any">
                                            </div>
                                            <input type="hidden" name="co_morbidities" id="co_morbidities" value="<?= esc($coMorbiditiesText) ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <label class="form-label">Other Complaints
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="btn_discharge_hinglish_to_english">Hinglish -> English</button>
                                    </label>
                                    <textarea id="complaint_remark_editor" class="form-control" name="complaint_remark" rows="6"><?= esc((string) ($complaint_remark ?? '')) ?></textarea>
                                </div>

                                <div class="mt-3 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-outline-success btn-sm" name="action" value="save_main" data-reload-section="section-complaints">Save Complaints Section</button>
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
                                <label class="form-label"><strong>Other / Systemic Examinations (Single Editor)</strong></label>
                                <textarea class="form-control" id="systemic_exam_editor" name="systemic_exam_text" rows="8"><?= esc($systemicExamText) ?></textarea>
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
                                <div class="mb-3">
                                    <label class="form-label">Smart Diagnosis Picker (English + Hinglish)</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="discharge_diagnosis_lookup" list="discharge_diagnosis_suggest" placeholder="Type diagnosis term...">
                                        <button type="button" class="btn btn-outline-primary" id="btn_discharge_add_diagnosis">Add</button>
                                    </div>
                                    <datalist id="discharge_diagnosis_suggest"></datalist>
                                    <div id="discharge_diagnosis_status" class="complaint-status text-muted"></div>
                                </div>

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
                                    <div class="col-md-6"><input type="text" class="form-control" name="new_diagnosis_name" id="new_diagnosis_name" placeholder="Diagnosis"></div>
                                    <div class="col-md-5"><input type="text" class="form-control" name="new_diagnosis_remark" placeholder="Remark"></div>
                                    <div class="col-md-1"><button type="submit" class="btn btn-primary btn-sm" name="action" value="add_diagnosis">+ADD</button></div>
                                </div>

                                <div class="mt-3">
                                    <label class="form-label">Final Diagnosis (Narrative)
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="btn_discharge_ai_diagnosis">AI Assist</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="btn_discharge_hinglish_diagnosis">Hinglish -> English</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm btn-discharge-field-past" data-section="diagnosis_remark" data-target="diagnosis_remark">Past Data</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm btn-discharge-template-load" data-section="diagnosis_remark" data-target="diagnosis_remark">Load Template</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm btn-discharge-template-save" data-section="diagnosis_remark" data-target="diagnosis_remark">Save as Template</button>
                                    </label>
                                    <textarea class="form-control" name="diagnosis_remark" id="diagnosis_remark" rows="3"><?= esc((string) ($diagnosis_remark ?? '')) ?></textarea>
                                </div>

                                <div class="mt-3 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-outline-success btn-sm" name="action" value="save_main" data-reload-section="section-diagnosis">Save Final Diagnosis</button>
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
                                <div class="mb-3">
                                    <label class="form-label">Smart Course/Treatment Picker (English + Hinglish)</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="discharge_course_lookup" list="discharge_course_suggest" placeholder="Type treatment/course term...">
                                        <button type="button" class="btn btn-outline-primary" id="btn_discharge_add_course">Add</button>
                                    </div>
                                    <datalist id="discharge_course_suggest"></datalist>
                                    <div id="discharge_course_status" class="complaint-status text-muted"></div>
                                </div>

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
                                    <div class="col-md-6"><input type="text" class="form-control" name="new_course_name" id="new_course_name" placeholder="Course / treatment"></div>
                                    <div class="col-md-5"><input type="text" class="form-control" name="new_course_remark" placeholder="Remark"></div>
                                    <div class="col-md-1"><button type="submit" class="btn btn-primary btn-sm" name="action" value="add_course">+ADD</button></div>
                                </div>

                                <div class="mt-3">
                                    <label class="form-label">Course / Treatment in Hospital (Narrative)
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="btn_discharge_ai_course">AI Assist</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="btn_discharge_hinglish_course">Hinglish -> English</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm btn-discharge-field-past" data-section="course_remark" data-target="course_remark">Past Data</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm btn-discharge-template-load" data-section="course_remark" data-target="course_remark">Load Template</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm btn-discharge-template-save" data-section="course_remark" data-target="course_remark">Save as Template</button>
                                    </label>
                                    <textarea class="form-control" name="course_remark" id="course_remark" rows="3"><?= esc((string) ($course_remark ?? '')) ?></textarea>
                                </div>

                                <div class="mt-3 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-outline-success btn-sm" name="action" value="save_main" data-reload-section="section-course">Save Course/Treatment</button>
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

                        

                        <div class="card border-secondary mt-3" id="section-instructions">
                            <div class="card-header py-2"><strong>Discharge Instructions / Advice</strong></div>
                            <div class="card-body row g-2">
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

    <div class="modal fade" id="ipdNarrativeTemplateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Load Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Select Template</label>
                    <select class="form-select form-select-sm" id="ipd_narrative_template_select"></select>
                    <label class="form-label mt-2">Apply Mode</label>
                    <select class="form-select form-select-sm" id="ipd_narrative_apply_mode">
                        <option value="replace">Replace field text</option>
                        <option value="append">Append to existing text</option>
                    </select>
                    <label class="form-label mt-2">Preview</label>
                    <textarea class="form-control form-control-sm" id="ipd_narrative_template_preview" rows="6" readonly></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm" id="btn_apply_ipd_template_choice">Apply</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="ipdNarrativeTemplateSaveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ipdNarrativeTemplateSaveModalTitle">Save Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Template Name</label>
                    <input type="text" class="form-control form-control-sm" id="ipd_narrative_template_save_name" maxlength="100">
                    <label class="form-label mt-2">Template Scope</label>
                    <select class="form-select form-select-sm" id="ipd_narrative_template_save_scope">
                        <option value="doctor" selected>My Template (Doctor/Consultant only)</option>
                        <option value="master">Master Template (Visible to all users)</option>
                    </select>
                    <label class="form-label mt-2">Preview</label>
                    <textarea class="form-control form-control-sm" id="ipd_narrative_template_save_preview" rows="6" readonly></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm" id="btn_save_ipd_template_choice">Save</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function() {
        var narrativeTemplateLoadState = { section: '', target: '', rows: [] };
        var narrativeTemplateSaveState = { section: '', text: '', target: '' };
        var narrativeTemplateToolsBound = false;

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

            if (CKEDITOR.instances.systemic_exam_editor) {
                CKEDITOR.instances.systemic_exam_editor.destroy(true);
            }

            if (document.getElementById('complaint_remark_editor')) {
                CKEDITOR.replace('complaint_remark_editor', {
                    height: 180
                });
            }

            if (document.getElementById('systemic_exam_editor')) {
                CKEDITOR.replace('systemic_exam_editor', {
                    height: 200
                });
            }
        }

        function getCsrfPair(form) {
            var tokenInput = form ? form.querySelector('input[name^="csrf_"]') : null;
            if (tokenInput) {
                return {
                    name: tokenInput.name,
                    value: tokenInput.value
                };
            }

            return {
                name: '<?= csrf_token() ?>',
                value: '<?= csrf_hash() ?>'
            };
        }

        function getComplaintEditorText() {
            if (window.CKEDITOR && CKEDITOR.instances.complaint_remark_editor) {
                return CKEDITOR.instances.complaint_remark_editor.getData() || '';
            }

            var textarea = document.getElementById('complaint_remark_editor');
            return textarea ? (textarea.value || '') : '';
        }

        function setComplaintEditorText(value) {
            if (window.CKEDITOR && CKEDITOR.instances.complaint_remark_editor) {
                CKEDITOR.instances.complaint_remark_editor.setData(value || '');
                return;
            }

            var textarea = document.getElementById('complaint_remark_editor');
            if (textarea) {
                textarea.value = value || '';
            }
        }

        function setComplaintStatus(text, level) {
            var statusEl = document.getElementById('discharge_complaint_status');
            if (!statusEl) {
                return;
            }

            statusEl.classList.remove('text-success', 'text-danger', 'text-muted');
            if (level === 'success') {
                statusEl.classList.add('text-success');
            } else if (level === 'error') {
                statusEl.classList.add('text-danger');
            } else {
                statusEl.classList.add('text-muted');
            }
            statusEl.textContent = text || '';
        }

        function initComplaintTools() {
            var section = document.getElementById('section-complaints');
            if (!section || section.dataset.toolsBound === '1') {
                return;
            }
            section.dataset.toolsBound = '1';

            var form = section.closest('form');
            if (!form) {
                return;
            }

            var lookup = document.getElementById('discharge_complaint_lookup');
            var suggest = document.getElementById('discharge_complaint_suggest');
            var nameInput = document.getElementById('new_complaint_name');
            var remarkInput = document.getElementById('new_complaint_remark');
            var addRowBtn = document.getElementById('btn_add_complaint_row');
            var btnAdd = document.getElementById('btn_discharge_add_complaint');
            var btnAiDraft = document.getElementById('btn_discharge_ai_draft');
            var btnHinglish = document.getElementById('btn_discharge_hinglish_to_english');
            var painHidden = document.getElementById('pain_value');
            var painOptions = section.querySelectorAll('.pain-option');

            var selectedComplaints = [];
            var complaintSuggestions = [];

            section.querySelectorAll('table tbody tr').forEach(function(row) {
                var firstCell = row.querySelector('td');
                if (!firstCell) {
                    return;
                }
                var value = (firstCell.textContent || '').trim();
                if (value !== '' && value.toLowerCase() !== 'no complaint rows yet.') {
                    selectedComplaints.push(value);
                }
            });

            function addComplaintValue(value) {
                value = (value || '').trim();
                if (value === '') {
                    return false;
                }

                var exists = selectedComplaints.some(function(item) {
                    return item.toUpperCase() === value.toUpperCase();
                });
                if (exists) {
                    return false;
                }

                selectedComplaints.push(value);
                return true;
            }

            if (painOptions && painOptions.length && painHidden) {
                painOptions.forEach(function(option) {
                    option.addEventListener('change', function() {
                        if (this.checked) {
                            painHidden.value = this.value || '';
                        }
                    });
                });
            }

            if (lookup) {
                lookup.addEventListener('input', function() {
                    var q = (lookup.value || '').trim();
                    if (q.length < 2 || !window.jQuery) {
                        return;
                    }

                    $.get('<?= base_url('Opd_prescription/complaints_search') ?>?q=' + encodeURIComponent(q), function(data) {
                        complaintSuggestions = (data && data.rows) ? data.rows : [];
                        if (!suggest) {
                            return;
                        }
                        var html = '';
                        complaintSuggestions.forEach(function(row) {
                            var label = row.name || '';
                            if (row.name_hinglish) {
                                label += ' (' + row.name_hinglish + ')';
                            }
                            html += '<option value="' + $('<div>').text(label).html() + '"></option>';
                        });
                        suggest.innerHTML = html;
                    }, 'json');
                });
            }

            if (btnAdd) {
                btnAdd.addEventListener('click', function() {
                    var inputVal = lookup ? (lookup.value || '').trim() : '';
                    if (inputVal === '') {
                        setComplaintStatus('Type complaint text first.', 'error');
                        return;
                    }

                    var chosen = '';
                    complaintSuggestions.forEach(function(row) {
                        var label = row.name || '';
                        if (row.name_hinglish) {
                            label += ' (' + row.name_hinglish + ')';
                        }
                        if (label.toUpperCase() === inputVal.toUpperCase() || (row.name || '').toUpperCase() === inputVal.toUpperCase()) {
                            chosen = row.name || inputVal;
                        }
                    });

                    function submitComplaint(value) {
                        if (!nameInput || !addRowBtn) {
                            return;
                        }
                        nameInput.value = value;
                        if (remarkInput) {
                            remarkInput.value = '';
                        }
                        addRowBtn.click();
                    }

                    if (chosen !== '') {
                        addComplaintValue(chosen);
                        submitComplaint(chosen);
                        if (lookup) {
                            lookup.value = '';
                        }
                        setComplaintStatus('Complaint added.', 'success');
                        return;
                    }

                    if (!window.jQuery) {
                        submitComplaint(inputVal);
                        return;
                    }

                    var csrf = getCsrfPair(form);
                    var payload = {
                        text: inputVal
                    };
                    payload[csrf.name] = csrf.value;

                    $.post('<?= base_url('Opd_prescription/complaints_parse') ?>', payload, function(data) {
                        if (data && data.csrfName && data.csrfHash) {
                            var csrfInput = form.querySelector('input[name="' + data.csrfName + '"]');
                            if (csrfInput) {
                                csrfInput.value = data.csrfHash;
                            }
                        }

                        var rows = (data && data.rows) ? data.rows : [];
                        if (!rows.length) {
                            submitComplaint(inputVal);
                            if (lookup) {
                                lookup.value = '';
                            }
                            setComplaintStatus('Added custom complaint text.', 'success');
                            return;
                        }

                        var first = (rows[0] || '').toString().trim();
                        if (first === '') {
                            submitComplaint(inputVal);
                        } else {
                            addComplaintValue(first);
                            submitComplaint(first);
                        }
                        if (lookup) {
                            lookup.value = '';
                        }
                        setComplaintStatus((data && data.error_text) ? data.error_text : 'Complaint term matched.', 'success');
                    }, 'json').fail(function() {
                        setComplaintStatus('Unable to parse complaint text right now.', 'error');
                    });
                });
            }

            if (btnAiDraft) {
                btnAiDraft.addEventListener('click', function() {
                    if (!window.jQuery) {
                        return;
                    }

                    var currentText = getComplaintEditorText();
                    if (!selectedComplaints.length && (currentText || '').trim() === '') {
                        setComplaintStatus('Please add at least one complaint first.', 'error');
                        return;
                    }

                    var csrf = getCsrfPair(form);
                    var payload = {
                        complaints: selectedComplaints,
                        current_text: currentText
                    };
                    payload[csrf.name] = csrf.value;

                    $.post('<?= base_url('Opd_prescription/complaints_ai_draft') ?>', payload, function(data) {
                        if (data && data.csrfName && data.csrfHash) {
                            var csrfInput = form.querySelector('input[name="' + data.csrfName + '"]');
                            if (csrfInput) {
                                csrfInput.value = data.csrfHash;
                            }
                        }

                        if (!data || parseInt(data.update || '0', 10) !== 1) {
                            setComplaintStatus((data && data.error_text) ? data.error_text : 'Unable to draft complaint text.', 'error');
                            return;
                        }

                        setComplaintEditorText(data.draft_text || currentText);
                        setComplaintStatus((data.error_text || 'Complaint draft ready.'), 'success');
                    }, 'json').fail(function() {
                        setComplaintStatus('AI draft request failed.', 'error');
                    });
                });
            }

            if (btnHinglish) {
                btnHinglish.addEventListener('click', function() {
                    if (!window.jQuery) {
                        return;
                    }

                    var text = (getComplaintEditorText() || '').trim();
                    if (text === '') {
                        setComplaintStatus('Enter complaint text first.', 'error');
                        return;
                    }

                    var csrf = getCsrfPair(form);
                    var payload = {
                        text: text,
                        mode: 'hinglish_to_english'
                    };
                    payload[csrf.name] = csrf.value;

                    $.post('<?= base_url('Opd_prescription/clinical_autotype') ?>', payload, function(data) {
                        if (data && data.csrfName && data.csrfHash) {
                            var csrfInput = form.querySelector('input[name="' + data.csrfName + '"]');
                            if (csrfInput) {
                                csrfInput.value = data.csrfHash;
                            }
                        }

                        if (!data || parseInt(data.update || '0', 10) !== 1) {
                            setComplaintStatus((data && data.error_text) ? data.error_text : 'Unable to rewrite text.', 'error');
                            return;
                        }

                        setComplaintEditorText((data.draft_text || '').toString());
                        setComplaintStatus((data.error_text || 'Text rewritten.'), 'success');
                    }, 'json').fail(function() {
                        setComplaintStatus('Hinglish rewrite failed.', 'error');
                    });
                });
            }
        }

        function setSectionStatus(id, text, level) {
            var el = document.getElementById(id);
            if (!el) {
                return;
            }

            el.classList.remove('text-success', 'text-danger', 'text-muted');
            if (level === 'success') {
                el.classList.add('text-success');
            } else if (level === 'error') {
                el.classList.add('text-danger');
            } else {
                el.classList.add('text-muted');
            }
            el.textContent = text || '';
        }

        function getDischargeForm() {
            return document.querySelector('form[action*="Ipd_discharge/ipd_select/"]');
        }

        function statusIdByNarrativeSection(section) {
            if (section === 'diagnosis_remark') {
                return 'discharge_diagnosis_status';
            }
            if (section === 'course_remark') {
                return 'discharge_course_status';
            }

            return '';
        }

        function setNarrativeStatus(section, text, level) {
            var statusId = statusIdByNarrativeSection(section);
            if (statusId !== '') {
                setSectionStatus(statusId, text, level);
            }
        }

        function showModalById(modalId) {
            var el = document.getElementById(modalId);
            if (!el) {
                return;
            }

            if (window.bootstrap && window.bootstrap.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(el).show();
                return;
            }

            if (window.jQuery) {
                window.jQuery(el).show();
            }
        }

        function hideModalById(modalId) {
            var el = document.getElementById(modalId);
            if (!el) {
                return;
            }

            if (window.bootstrap && window.bootstrap.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(el).hide();
                return;
            }

            if (window.jQuery) {
                window.jQuery(el).hide();
            }
        }

        function bindNarrativeTemplateTools() {
            if (narrativeTemplateToolsBound || !window.jQuery) {
                return;
            }
            narrativeTemplateToolsBound = true;

            $(document).on('click', '.btn-discharge-field-past', function() {
                var target = ($(this).data('target') || '').toString();
                var section = ($(this).data('section') || '').toString();
                var form = getDischargeForm();
                if (!target || !section || !form || !$('#' + target).length) {
                    return;
                }

                var url = '<?= base_url('Ipd_discharge/section_past_data') ?>?section=' + encodeURIComponent(section) + '&ipd_id=' + encodeURIComponent('<?= (int) $ipdId ?>');
                $.get(url, function(data) {
                    if ((data && parseInt(data.update || '0', 10)) !== 1) {
                        setNarrativeStatus(section, (data && data.error_text) ? data.error_text : 'No past data found.', 'error');
                        return;
                    }

                    $('#' + target).val((data && data.past_text) ? data.past_text : '');
                    setNarrativeStatus(section, (data && data.error_text) ? data.error_text : 'Past data loaded.', 'success');
                }, 'json').fail(function() {
                    setNarrativeStatus(section, 'Unable to load past data right now.', 'error');
                });
            });

            $(document).on('click', '.btn-discharge-template-save', function() {
                var target = ($(this).data('target') || '').toString();
                var section = ($(this).data('section') || '').toString();
                if (!target || !section || !$('#' + target).length) {
                    return;
                }

                var text = ($('#' + target).val() || '').toString().trim();
                if (!text) {
                    setNarrativeStatus(section, 'Type text first, then save as template.', 'error');
                    return;
                }

                narrativeTemplateSaveState = { section: section, text: text, target: target };
                var sectionLabel = section.replace(/_/g, ' ').replace(/\b\w/g, function(ch) {
                    return ch.toUpperCase();
                });
                $('#ipdNarrativeTemplateSaveModalTitle').text('Save Template - ' + sectionLabel);
                $('#ipd_narrative_template_save_name').val(section.replace(/_/g, ' '));
                $('#ipd_narrative_template_save_scope').val('doctor');
                $('#ipd_narrative_template_save_preview').val(text);

                showModalById('ipdNarrativeTemplateSaveModal');
            });

            $('#btn_save_ipd_template_choice').on('click', function() {
                var section = (narrativeTemplateSaveState.section || '').toString();
                var text = (narrativeTemplateSaveState.text || '').toString().trim();
                var form = getDischargeForm();
                if (!form || !section || !text) {
                    setNarrativeStatus(section, 'Nothing to save for this section.', 'error');
                    return;
                }

                var templateName = ($('#ipd_narrative_template_save_name').val() || '').toString().trim();
                var templateScope = ($('#ipd_narrative_template_save_scope').val() || 'doctor').toString().trim().toLowerCase();
                if (!templateName) {
                    setNarrativeStatus(section, 'Template name is required.', 'error');
                    return;
                }

                var csrf = getCsrfPair(form);
                var payload = {
                    section: section,
                    template_name: templateName,
                    template_text: text,
                    template_scope: templateScope
                };
                payload[csrf.name] = csrf.value;

                $.post('<?= base_url('Ipd_discharge/section_template_save') ?>', payload, function(data) {
                    if (data && data.csrfName && data.csrfHash) {
                        var csrfInput = form.querySelector('input[name="' + data.csrfName + '"]');
                        if (csrfInput) {
                            csrfInput.value = data.csrfHash;
                        }
                    }

                    if (!data || parseInt(data.update || '0', 10) !== 1) {
                        setNarrativeStatus(section, (data && data.error_text) ? data.error_text : 'Unable to save template.', 'error');
                        return;
                    }

                    setNarrativeStatus(section, (data.error_text || 'Template saved.'), 'success');
                    hideModalById('ipdNarrativeTemplateSaveModal');
                }, 'json').fail(function() {
                    setNarrativeStatus(section, 'Unable to save template right now.', 'error');
                });
            });

            $(document).on('click', '.btn-discharge-template-load', function() {
                var target = ($(this).data('target') || '').toString();
                var section = ($(this).data('section') || '').toString();
                if (!target || !section || !$('#' + target).length) {
                    return;
                }

                var url = '<?= base_url('Ipd_discharge/section_template_list') ?>?section=' + encodeURIComponent(section);
                $.get(url, function(data) {
                    var rows = (data && data.rows) ? data.rows : [];
                    if (!rows.length) {
                        setNarrativeStatus(section, 'No template found for this section.', 'error');
                        return;
                    }

                    narrativeTemplateLoadState = { target: target, section: section, rows: rows };
                    var $sel = $('#ipd_narrative_template_select');
                    $sel.empty();
                    rows.forEach(function(row, idx) {
                        var name = row.template_name || ('Template ' + (idx + 1));
                        var src = parseInt(row.doc_id || '0', 10) === 0 ? '[Master] ' : '[My] ';
                        $sel.append('<option value="' + idx + '">' + $('<div>').text(src + name).html() + '</option>');
                    });

                    var existingText = ($('#' + target).val() || '').toString().trim();
                    $('#ipd_narrative_apply_mode').val(existingText ? 'append' : 'replace');
                    $('#ipd_narrative_template_preview').val((rows[0] && rows[0].template_text) ? rows[0].template_text : '');

                    showModalById('ipdNarrativeTemplateModal');
                }, 'json').fail(function() {
                    setNarrativeStatus(section, 'Unable to load template list.', 'error');
                });
            });

            $('#ipd_narrative_template_select').on('change', function() {
                var idx = parseInt($(this).val() || '0', 10);
                var row = (narrativeTemplateLoadState.rows || [])[idx] || {};
                $('#ipd_narrative_template_preview').val(row.template_text || '');
            });

            $('#btn_apply_ipd_template_choice').on('click', function() {
                var idx = parseInt($('#ipd_narrative_template_select').val() || '0', 10);
                var row = (narrativeTemplateLoadState.rows || [])[idx] || null;
                var section = (narrativeTemplateLoadState.section || '').toString();
                var target = (narrativeTemplateLoadState.target || '').toString();
                if (!row || !target || !$('#' + target).length) {
                    setNarrativeStatus(section, 'Invalid template selection.', 'error');
                    return;
                }

                var mode = ($('#ipd_narrative_apply_mode').val() || 'replace').toString();
                var selectedText = (row.template_text || '').toString().trim();
                var $target = $('#' + target);
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
                setNarrativeStatus(section, mode === 'append' ? 'Template appended.' : 'Template loaded.', 'success');
                hideModalById('ipdNarrativeTemplateModal');
            });
        }

        function bindSmartTermLookup(form, lookupId, suggestId, addBtnId, targetInputId, statusId, emptyText) {
            var lookup = document.getElementById(lookupId);
            var suggest = document.getElementById(suggestId);
            var addBtn = document.getElementById(addBtnId);
            var targetInput = document.getElementById(targetInputId);
            if (!lookup || !addBtn || !targetInput) {
                return;
            }

            var suggestions = [];

            lookup.addEventListener('input', function() {
                var q = (lookup.value || '').trim();
                if (q.length < 2 || !window.jQuery) {
                    return;
                }

                $.get('<?= base_url('Opd_prescription/complaints_search') ?>?q=' + encodeURIComponent(q), function(data) {
                    suggestions = (data && data.rows) ? data.rows : [];
                    if (!suggest) {
                        return;
                    }

                    var html = '';
                    suggestions.forEach(function(row) {
                        var label = row.name || '';
                        if (row.name_hinglish) {
                            label += ' (' + row.name_hinglish + ')';
                        }
                        html += '<option value="' + $('<div>').text(label).html() + '"></option>';
                    });
                    suggest.innerHTML = html;
                }, 'json');
            });

            addBtn.addEventListener('click', function() {
                var inputVal = (lookup.value || '').trim();
                if (inputVal === '') {
                    setSectionStatus(statusId, emptyText || 'Type text first.', 'error');
                    return;
                }

                var chosen = '';
                suggestions.forEach(function(row) {
                    var label = row.name || '';
                    if (row.name_hinglish) {
                        label += ' (' + row.name_hinglish + ')';
                    }
                    if (label.toUpperCase() === inputVal.toUpperCase() || (row.name || '').toUpperCase() === inputVal.toUpperCase()) {
                        chosen = row.name || inputVal;
                    }
                });

                if (chosen !== '') {
                    targetInput.value = chosen;
                    lookup.value = '';
                    setSectionStatus(statusId, 'Term added to input. Click +ADD to save row.', 'success');
                    return;
                }

                if (!window.jQuery) {
                    targetInput.value = inputVal;
                    lookup.value = '';
                    return;
                }

                var csrf = getCsrfPair(form);
                var payload = { text: inputVal };
                payload[csrf.name] = csrf.value;

                $.post('<?= base_url('Opd_prescription/complaints_parse') ?>', payload, function(data) {
                    if (data && data.csrfName && data.csrfHash) {
                        var csrfInput = form.querySelector('input[name="' + data.csrfName + '"]');
                        if (csrfInput) {
                            csrfInput.value = data.csrfHash;
                        }
                    }

                    var rows = (data && data.rows) ? data.rows : [];
                    targetInput.value = rows.length ? String(rows[0] || inputVal) : inputVal;
                    lookup.value = '';
                    setSectionStatus(statusId, rows.length ? 'Matched predefined term. Click +ADD to save row.' : 'Custom term ready. Click +ADD to save row.', 'success');
                }, 'json').fail(function() {
                    setSectionStatus(statusId, 'Lookup failed right now.', 'error');
                });
            });
        }

        function bindAiRewrite(form, btnId, sourceId, mode, statusId, emptyMsg) {
            var btn = document.getElementById(btnId);
            var source = document.getElementById(sourceId);
            if (!btn || !source) {
                return;
            }

            btn.addEventListener('click', function() {
                var text = (source.value || '').trim();
                if (text === '') {
                    setSectionStatus(statusId, emptyMsg || 'Type text first.', 'error');
                    return;
                }

                if (!window.jQuery) {
                    return;
                }

                var csrf = getCsrfPair(form);
                var payload = {
                    text: text,
                    mode: mode
                };
                payload[csrf.name] = csrf.value;

                $.post('<?= base_url('Opd_prescription/clinical_autotype') ?>', payload, function(data) {
                    if (data && data.csrfName && data.csrfHash) {
                        var csrfInput = form.querySelector('input[name="' + data.csrfName + '"]');
                        if (csrfInput) {
                            csrfInput.value = data.csrfHash;
                        }
                    }

                    if (!data || parseInt(data.update || '0', 10) !== 1) {
                        setSectionStatus(statusId, (data && data.error_text) ? data.error_text : 'Unable to process AI text.', 'error');
                        return;
                    }

                    source.value = (data.draft_text || text);
                    setSectionStatus(statusId, (data.error_text || 'AI text ready.'), 'success');
                }, 'json').fail(function() {
                    setSectionStatus(statusId, 'AI request failed.', 'error');
                });
            });
        }

        function initDiagnosisTools() {
            var section = document.getElementById('section-diagnosis');
            if (!section || section.dataset.toolsBound === '1') {
                return;
            }
            section.dataset.toolsBound = '1';

            var form = section.closest('form');
            if (!form) {
                return;
            }

            bindSmartTermLookup(
                form,
                'discharge_diagnosis_lookup',
                'discharge_diagnosis_suggest',
                'btn_discharge_add_diagnosis',
                'new_diagnosis_name',
                'discharge_diagnosis_status',
                'Type diagnosis text first.'
            );

            bindAiRewrite(form, 'btn_discharge_ai_diagnosis', 'diagnosis_remark', 'diagnosis', 'discharge_diagnosis_status', 'Type diagnosis narrative first.');
            bindAiRewrite(form, 'btn_discharge_hinglish_diagnosis', 'diagnosis_remark', 'hinglish_to_english', 'discharge_diagnosis_status', 'Type diagnosis narrative first.');
        }

        function initCourseTools() {
            var section = document.getElementById('section-course');
            if (!section || section.dataset.toolsBound === '1') {
                return;
            }
            section.dataset.toolsBound = '1';

            var form = section.closest('form');
            if (!form) {
                return;
            }

            bindSmartTermLookup(
                form,
                'discharge_course_lookup',
                'discharge_course_suggest',
                'btn_discharge_add_course',
                'new_course_name',
                'discharge_course_status',
                'Type course/treatment text first.'
            );

            bindAiRewrite(form, 'btn_discharge_ai_course', 'course_remark', 'autotype', 'discharge_course_status', 'Type course narrative first.');
            bindAiRewrite(form, 'btn_discharge_hinglish_course', 'course_remark', 'hinglish_to_english', 'discharge_course_status', 'Type course narrative first.');
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
        initComplaintTools();
        initDiagnosisTools();
        initCourseTools();
        bindNarrativeTemplateTools();

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

        function clearNabhFieldErrors(form) {
            var status = form.querySelector('#drug_allergy_status');
            var details = form.querySelector('#drug_allergy_details');
            var statusErr = form.querySelector('#drug_allergy_status_error');
            var detailsErr = form.querySelector('#drug_allergy_details_error');

            if (status) {
                status.classList.remove('is-invalid');
            }
            if (details) {
                details.classList.remove('is-invalid');
            }
            if (statusErr) {
                statusErr.textContent = '';
                statusErr.style.display = 'none';
            }
            if (detailsErr) {
                detailsErr.textContent = '';
                detailsErr.style.display = 'none';
            }
        }

        function markNabhFieldError(form, selector, message) {
            var el = form.querySelector(selector);
            if (el) {
                el.classList.add('is-invalid');
            }

            if (selector === '#drug_allergy_status') {
                var statusErr = form.querySelector('#drug_allergy_status_error');
                if (statusErr) {
                    statusErr.textContent = message || 'This field is required.';
                    statusErr.style.display = 'block';
                }
            }
            if (selector === '#drug_allergy_details') {
                var detailsErr = form.querySelector('#drug_allergy_details_error');
                if (detailsErr) {
                    detailsErr.textContent = message || 'This field is required.';
                    detailsErr.style.display = 'block';
                }
            }
        }

        function syncCoMorbidityHidden(form) {
            var hidden = form.querySelector('#co_morbidities');
            if (!hidden) {
                return;
            }

            var parts = [];
            var checked = form.querySelectorAll('.co-morbidity-item:checked');
            checked.forEach(function(item) {
                var val = (item.value || '').trim();
                if (val !== '') {
                    parts.push(val);
                }
            });

            var other = form.querySelector('#co_morbidities_other');
            if (other) {
                var otherValue = (other.value || '').trim();
                if (otherValue !== '') {
                    parts.push(otherValue);
                }
            }

            hidden.value = parts.join(', ');
        }

        function validateNabhHistorySection(form) {
            clearNabhFieldErrors(form);

            var statusEl = form.querySelector('#drug_allergy_status');
            var detailsEl = form.querySelector('#drug_allergy_details');
            if (!statusEl) {
                return true;
            }

            var status = (statusEl.value || '').trim();
            var details = detailsEl ? (detailsEl.value || '').trim() : '';
            if (status === '') {
                markNabhFieldError(form, '#drug_allergy_status', 'Drug Allergy Status is required as per NABH documentation.');
                return false;
            }
            if (status.toLowerCase() === 'known' && details === '') {
                markNabhFieldError(form, '#drug_allergy_details', 'Drug Allergy Details are required when Drug Allergy Status is Known.');
                return false;
            }

            return true;
        }

        function initNabhHistorySection(form) {
            if (!form) {
                return;
            }

            syncCoMorbidityHidden(form);

            var status = form.querySelector('#drug_allergy_status');
            var details = form.querySelector('#drug_allergy_details');
            if (status) {
                status.addEventListener('change', function() {
                    clearNabhFieldErrors(form);
                });
            }
            if (details) {
                details.addEventListener('input', function() {
                    clearNabhFieldErrors(form);
                });
            }

            var coItems = form.querySelectorAll('.co-morbidity-item');
            coItems.forEach(function(item) {
                item.addEventListener('change', function() {
                    syncCoMorbidityHidden(form);
                });
            });

            var other = form.querySelector('#co_morbidities_other');
            if (other) {
                other.addEventListener('input', function() {
                    syncCoMorbidityHidden(form);
                });
            }

            var copyBtn = form.querySelector('#btn_copy_hpi_to_complaints');
            if (copyBtn && copyBtn.dataset.bound !== '1') {
                copyBtn.dataset.bound = '1';
                copyBtn.addEventListener('click', function() {
                    var hpiEl = form.querySelector('textarea[name="hpi_note"]');
                    var hpiText = hpiEl ? String(hpiEl.value || '').trim() : '';
                    if (hpiText === '') {
                        if (typeof window.notify === 'function') {
                            window.notify('warning', 'Discharge Update', 'H&P note is empty.');
                        }
                        return;
                    }

                    var complaintEl = form.querySelector('#complaint_remark_editor');
                    if (!complaintEl) {
                        return;
                    }

                    var existing = '';
                    if (window.CKEDITOR && CKEDITOR.instances && CKEDITOR.instances.complaint_remark_editor) {
                        existing = String(CKEDITOR.instances.complaint_remark_editor.getData() || '').trim();
                    } else {
                        existing = String(complaintEl.value || '').trim();
                    }

                    var finalText = hpiText;
                    if (existing !== '') {
                        var append = window.confirm('Other Complaints already has text. Click OK to append H&P note, Cancel to replace.');
                        finalText = append ? (existing + '\n\n' + hpiText) : hpiText;
                    }

                    complaintEl.value = finalText;
                    if (window.CKEDITOR && CKEDITOR.instances && CKEDITOR.instances.complaint_remark_editor) {
                        CKEDITOR.instances.complaint_remark_editor.setData(finalText);
                    }

                    if (typeof window.notify === 'function') {
                        window.notify('success', 'Discharge Update', 'H&P note copied to Other Complaints.');
                    }
                });
            }
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
            initNabhHistorySection(form);

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
                syncCoMorbidityHidden(form);

                if (!validateNabhHistorySection(form)) {
                    return;
                }

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
                        initComplaintTools();
                        initDiagnosisTools();
                        initCourseTools();
                        bindDischargeAjaxSubmit();
                        initNabhHistorySection(document.querySelector('form[action*="Ipd_discharge/ipd_select/"]'));
                        syncNavOnScroll();
                        return;
                    }

                    if (patchSectionFromHtml(holder, targetSectionId)) {
                        if (targetSectionId === 'section-complaints') {
                            initComplaintEditor();
                            initComplaintTools();
                            initNabhHistorySection(document.querySelector('form[action*="Ipd_discharge/ipd_select/"]'));
                        } else if (targetSectionId === 'section-diagnosis') {
                            initDiagnosisTools();
                        } else if (targetSectionId === 'section-course') {
                            initCourseTools();
                        }
                        bindDischargeAjaxSubmit();
                        syncNavOnScroll();
                        return;
                    }

                    patchFormAreaFromHtml(holder);
                    initComplaintEditor();
                    initComplaintTools();
                    initDiagnosisTools();
                    initCourseTools();
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
