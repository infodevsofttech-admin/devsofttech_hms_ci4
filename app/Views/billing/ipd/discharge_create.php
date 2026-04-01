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

        // Keep saved systemic text as-is; do not auto-prefix master labels.
        $systemicParts[] = $value;
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
$legacyDrugRows = $legacy_drug_rows ?? [];
$medicineRows = [];
if (! empty($legacyDrugRows)) {
    foreach ($legacyDrugRows as $row) {
        $medicineRows[] = [
            'id' => (int) ($row['id'] ?? 0),
            'source' => 'legacy',
            'med_name' => (string) ($row['med_name'] ?? ''),
            'med_type' => (string) ($row['med_type'] ?? ''),
            'dosage' => (string) ($row['dosage'] ?? ''),
            'dosage_when' => (string) ($row['dosage_when'] ?? ''),
            'dosage_freq' => (string) ($row['dosage_freq'] ?? ''),
            'no_of_days' => (string) ($row['no_of_days'] ?? ''),
            'qty' => (string) ($row['qty'] ?? ''),
            'remark' => (string) ($row['remark'] ?? ''),
        ];
    }
} else {
    foreach ($drugRows as $row) {
        $medicineRows[] = [
            'id' => (int) ($row['id'] ?? 0),
            'source' => 'classic',
            'med_name' => (string) ($row['drug_name'] ?? ''),
            'med_type' => '',
            'dosage' => (string) ($row['drug_dose'] ?? ''),
            'dosage_when' => '',
            'dosage_freq' => '',
            'no_of_days' => (string) ($row['drug_day'] ?? ''),
            'qty' => '',
            'remark' => '',
        ];
    }
}
$inhosRemark = (string) ($inhos_remark ?? '');
$otherExamText = (string) ($other_exam_text ?? '');
$painValue = (string) ($pain_value ?? '');
$opdHistorySnapshot = $opd_history_snapshot ?? [];
$nursingAdmissionSnapshot = $nursing_admission_snapshot ?? [];
$instructionFoodRows = $instruction_food_rows ?? [];
$instructionFoodIdsRaw = $instruction_food_ids ?? [];
$instructionFoodIds = [];
if (is_array($instructionFoodIdsRaw)) {
    foreach ($instructionFoodIdsRaw as $fid) {
        $fidInt = (int) $fid;
        if ($fidInt > 0) {
            $instructionFoodIds[$fidInt] = true;
        }
    }
}
$instructionOther = (string) ($instruction_other ?? '');
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

    @media (min-width: 992px) {
        .discharge-page {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 64px);
            overflow: hidden;
        }

        .discharge-page-title {
            flex: 0 0 auto;
            margin-bottom: 10px;
            padding-bottom: 2px;
            background: #f0f2f7;
        }

        .discharge-main-card {
            flex: 1 1 auto;
            min-height: 0;
            margin-bottom: 0;
        }

        .discharge-main-card > .card-body {
            /* Let notices consume natural height; keep form row as flexible remainder */
            height: 100%;
            display: flex;
            flex-direction: column;
            max-height: none;
            overflow: hidden;
        }

        .discharge-main-card > .card-body > .row {
            flex: 1 1 auto;
            min-height: 0;
        }

        .discharge-side-panel {
            height: 100%;
            margin-bottom: 0;
            overflow: hidden;
        }

        .discharge-form-area {
            height: 100%;
            max-height: none;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 6px;
        }

        #discharge_section_nav {
            top: 0;
            max-height: none;
        }
    }

    .discharge-side-panel {
        border: 1px solid var(--dc-border);
        background: var(--dc-muted-bg);
        border-radius: 10px;
        padding: 10px;
        margin-bottom: 12px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        min-height: 0;
    }

    .discharge-side-actions {
        border-top: 1px solid #d8e2ef;
        margin-top: 0;
        margin-bottom: 2px;
        padding-top: 12px;
        display: grid;
        gap: 6px;
        flex: 0 0 auto;
        position: relative;
        z-index: 2;
    }

    .discharge-side-actions .btn {
        width: 100%;
    }

    #discharge_section_nav {
        position: static;
        top: auto;
        max-height: none;
        overflow-y: auto;
        padding-right: 4px;
        margin-bottom: 0;
        flex: 1 1 auto;
        min-height: 0;
        padding-bottom: 6px;
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

    .rx-quick-btn {
        border: 1px solid #b8c5d6;
        background: #fff;
        color: #486485;
        border-radius: 0.35rem;
        padding: 0.2rem 0.6rem;
        font-size: 0.88rem;
        margin-right: 0.35rem;
        margin-bottom: 0.35rem;
    }

    @media (max-width: 991.98px) {
        .discharge-page {
            height: auto;
            overflow: visible;
        }

        .discharge-main-card > .card-body {
            max-height: none;
            overflow: visible;
        }

        .discharge-form-area {
            max-height: none;
            overflow: visible;
            padding-right: 0;
        }

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

                    <div class="discharge-side-actions">
                        <button type="button" class="btn btn-primary btn-sm" id="btn_preview_side" onclick="openDischargePreview('<?= site_url('Ipd_discharge/preview_discharge_report/' . $ipdId . '?regen=1') ?>', 'Discharge Preview');">Preview</button>
                    </div>
                    </div>
                </div>

                <div class="col-md-9 discharge-form-area">
                    <form id="discharge_main_form" method="post" action="<?= site_url('Ipd_discharge/ipd_select/' . $ipdId) ?>" class="row g-3">
                        <?= csrf_field() ?>

                        <div class="card border-primary" id="section-personal-history">
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
                                <div class="mt-3 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-outline-success btn-sm" name="action" value="save_main" data-reload-section="section-personal-history">Save Personal History</button>
                                </div>
                            </div>
                        </div>

                        <div class="card border-info mt-3" id="section-nursing-history">
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
                                <div class="mt-3 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-outline-success btn-sm" name="action" value="save_main" data-reload-section="section-nursing-history">Save Nursing H&amp;P Section</button>
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
                                            <div class="row g-2">
                                                <div class="col-md-4">
                                                    <label class="form-label mb-1">Drug Allergy Status</label>
                                                    <select class="form-select form-select-sm" id="drug_allergy_status" name="drug_allergy_status">
                                                        <option value="Allergies Not Known" <?= ($allergyStatusUnknown || (!$allergyStatusKnown && !$allergyStatusNoKnown)) ? 'selected' : '' ?>>Allergies Not Known</option>
                                                        <option value="Known" <?= $allergyStatusKnown ? 'selected' : '' ?>>Known</option>
                                                        <option value="No Known Drug Allergy" <?= $allergyStatusNoKnown ? 'selected' : '' ?>>No Known Drug Allergy</option>
                                                    </select>
                                                    <div id="drug_allergy_status_error" class="invalid-feedback d-block" style="display:none;"></div>
                                                </div>
                                                <div class="col-md-8">
                                                    <label class="form-label mb-1">Drug Allergy Details <span class="small text-muted">(when status = Known)</span></label>
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

                        <div class="card border-info mt-3" id="section-systemic">
                            <div class="card-header py-2"><strong>Other / Systemic Examinations</strong></div>
                            <div class="card-body">
                                <label class="form-label"><strong>Other / Systemic Examinations (Single Editor)</strong></label>
                                <textarea class="form-control" id="systemic_exam_editor" name="systemic_exam_text" rows="8"><?= esc($systemicExamText) ?></textarea>
                                <div id="systemic_save_status" class="complaint-status text-muted mt-2"></div>
                                <div class="mt-3 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-outline-success btn-sm" name="action" value="save_main" data-reload-section="section-systemic" data-save-mode="json" data-status-id="systemic_save_status">Save Systemic Examination</button>
                                </div>
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
                                <div class="col-md-12 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-outline-success btn-sm" name="action" value="save_main" data-reload-section="section-admission">Save Admission/Discharge</button>
                                </div>
                            </div>
                        </div>

                        <div class="card border-secondary mt-3" id="section-surgery">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <strong>Surgery / Procedure / delivery if any</strong>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_discharge_manage_surgery_master">Master CRUD</button>
                            </div>
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
                                <input type="hidden" name="new_surgery_master_id" id="new_surgery_master_id" value="0">
                                <div class="row g-2 mb-3">
                                    <div class="col-md-5"><input type="text" class="form-control" name="new_surgery_name" id="new_surgery_name" list="discharge_surgery_suggest" autocomplete="off" placeholder="Surgery name"></div>
                                    <div class="col-md-3"><input type="date" class="form-control" name="new_surgery_date"></div>
                                    <div class="col-md-3"><input type="text" class="form-control" name="new_surgery_remark" placeholder="Remark"></div>
                                    <div class="col-md-1"><button type="submit" class="btn btn-primary btn-sm" name="action" value="add_surgery">+ADD</button></div>
                                </div>
                                <datalist id="discharge_surgery_suggest"></datalist>

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
                                <input type="hidden" name="new_procedure_master_id" id="new_procedure_master_id" value="0">
                                <div class="row g-2">
                                    <div class="col-md-5"><input type="text" class="form-control" name="new_procedure_name" id="new_procedure_name" list="discharge_procedure_suggest" autocomplete="off" placeholder="Procedure name"></div>
                                    <div class="col-md-3"><input type="date" class="form-control" name="new_procedure_date"></div>
                                    <div class="col-md-3"><input type="text" class="form-control" name="new_procedure_remark" placeholder="Remark"></div>
                                    <div class="col-md-1"><button type="submit" class="btn btn-primary btn-sm" name="action" value="add_procedure">+ADD</button></div>
                                </div>
                                <datalist id="discharge_procedure_suggest"></datalist>
                                <div id="discharge_surgery_status" class="complaint-status text-muted"></div>
                            </div>
                        </div>

                        <div class="card border-secondary mt-3" id="section-diagnosis">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <strong>Final Diagnosis</strong>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_discharge_seed_icd">Load ICD Starter</button>
                            </div>
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
                                    <div class="col-md-6"><input type="text" class="form-control" name="new_diagnosis_name" id="new_diagnosis_name" list="discharge_diagnosis_suggest" autocomplete="off" placeholder="Diagnosis"></div>
                                    <div class="col-md-5"><input type="text" class="form-control" name="new_diagnosis_remark" placeholder="Remark"></div>
                                    <div class="col-md-1"><button type="submit" class="btn btn-primary btn-sm" name="action" value="add_diagnosis">+ADD</button></div>
                                </div>
                                <datalist id="discharge_diagnosis_suggest"></datalist>
                                <small class="text-muted">Tip: if ICD match exists, selected value appends code in diagnosis text.</small>
                                <div id="discharge_diagnosis_status" class="complaint-status text-muted"></div>

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
                                <div class="mt-3 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-outline-success btn-sm" name="action" value="save_main" data-reload-section="section-summary-invest">Save Summary Investigation</button>
                                </div>
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
                                    <div class="col-md-6"><input type="text" class="form-control" name="new_course_name" id="new_course_name" list="discharge_course_suggest" autocomplete="off" placeholder="Course / treatment"></div>
                                    <div class="col-md-5"><input type="text" class="form-control" name="new_course_remark" placeholder="Remark"></div>
                                    <div class="col-md-1"><button type="submit" class="btn btn-primary btn-sm" name="action" value="add_course">+ADD</button></div>
                                </div>
                                <datalist id="discharge_course_suggest"></datalist>
                                <div id="discharge_course_status" class="complaint-status text-muted"></div>

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
                                <div class="col-md-12 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-outline-success btn-sm" name="action" value="save_main" data-reload-section="section-condition">Save Discharge Condition</button>
                                </div>
                            </div>
                        </div>

                        <div class="card border-secondary mt-3" id="section-medicine">
                            <div class="card-header py-2"><strong>Discharge Medicine Prescribed</strong></div>
                            <div class="card-body">
                                <input type="hidden" name="selected_rx_group_id" id="selected_rx_group_id" value="0">
                                <input type="hidden" name="drug_remove_source" id="drug_remove_source" value="legacy">
                                <button type="submit" class="d-none" id="btn_apply_rx_group" name="action" value="apply_rx_group" data-reload-section="section-medicine">Apply Rx Group</button>

                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_open_rx_group_modal">Rx Group</button>
                                        <span id="rx_group_selected_name" class="text-muted">No Rx-Group selected</span>
                                    </div>
                                    <div class="small text-muted">Select group and preview medicines before add.</div>
                                </div>

                                <table class="table table-sm table-bordered">
                                    <thead><tr><th>Medicine</th><th>Type</th><th>Dose</th><th>When</th><th>Freq</th><th>Days</th><th>Qty</th><th>Remark</th><th style="width:90px;">Action</th></tr></thead>
                                    <tbody>
                                        <?php if (empty($medicineRows)): ?>
                                            <tr><td colspan="9" class="text-muted text-center">No medicine added</td></tr>
                                        <?php else: foreach ($medicineRows as $row): ?>
                                            <tr>
                                                <td><?= esc((string) ($row['med_name'] ?? '')) ?></td>
                                                <td><?= esc((string) ($row['med_type'] ?? '')) ?></td>
                                                <td><?= esc((string) ($row['dosage'] ?? '')) ?></td>
                                                <td><?= esc((string) ($row['dosage_when'] ?? '')) ?></td>
                                                <td><?= esc((string) ($row['dosage_freq'] ?? '')) ?></td>
                                                <td><?= esc((string) ($row['no_of_days'] ?? '')) ?></td>
                                                <td><?= esc((string) ($row['qty'] ?? '')) ?></td>
                                                <td><?= esc((string) ($row['remark'] ?? '')) ?></td>
                                                <td><button type="submit" class="btn btn-outline-danger btn-sm" name="action" value="remove_drug" data-reload-section="section-medicine" onclick="document.getElementById('drug_remove_id').value='<?= (int) ($row['id'] ?? 0) ?>';document.getElementById('drug_remove_source').value='<?= esc((string) ($row['source'] ?? 'legacy')) ?>';">Remove</button></td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                                <input type="hidden" name="drug_remove_id" id="drug_remove_id" value="0">

                                <div class="row g-2 mb-2">
                                    <div class="col-md-4"><input type="text" class="form-control" name="new_drug_name" id="new_drug_name" list="discharge_med_suggest" autocomplete="off" placeholder="Medicine name"></div>
                                    <div class="col-md-2"><input type="text" class="form-control" name="new_drug_type" id="new_drug_type" placeholder="Type"></div>
                                    <div class="col-md-2"><input type="text" class="form-control" name="new_drug_dose" id="new_drug_dose" placeholder="Dose"></div>
                                    <div class="col-md-2"><input type="text" class="form-control" name="new_drug_when" id="new_drug_when" list="discharge_med_when_suggest" autocomplete="off" placeholder="When (BF/AF/WF)"></div>
                                    <div class="col-md-2"><input type="text" class="form-control" name="new_drug_freq" id="new_drug_freq" list="discharge_med_freq_suggest" autocomplete="off" placeholder="Freq (OD/BD/TDS/HS)"></div>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-md-2"><input type="text" class="form-control" name="new_drug_day" id="new_drug_day" placeholder="Days"></div>
                                    <div class="col-md-2"><input type="text" class="form-control" name="new_drug_qty" id="new_drug_qty" placeholder="Qty"></div>
                                    <div class="col-md-6"><input type="text" class="form-control" name="new_drug_remark" id="new_drug_remark" placeholder="Remark"></div>
                                    <div class="col-md-2"><button type="submit" class="btn btn-primary" name="action" value="add_drug" data-reload-section="section-medicine" style="width:100%;">Add</button></div>
                                </div>

                                <div class="mb-2">
                                    <button type="button" class="rx-quick-btn" data-fill-target="new_drug_when" data-fill-value="BF">Before Food</button>
                                    <button type="button" class="rx-quick-btn" data-fill-target="new_drug_when" data-fill-value="AF">After Food</button>
                                    <button type="button" class="rx-quick-btn" data-fill-target="new_drug_when" data-fill-value="WF">With Food</button>
                                    <button type="button" class="rx-quick-btn" data-fill-target="new_drug_freq" data-fill-value="OD">OD</button>
                                    <button type="button" class="rx-quick-btn" data-fill-target="new_drug_freq" data-fill-value="BD">BD</button>
                                    <button type="button" class="rx-quick-btn" data-fill-target="new_drug_freq" data-fill-value="TDS">TDS</button>
                                    <button type="button" class="rx-quick-btn" data-fill-target="new_drug_freq" data-fill-value="HS">HS</button>
                                    <button type="button" class="rx-quick-btn" data-fill-target="new_drug_day" data-fill-value="3 Days">3 Days</button>
                                    <button type="button" class="rx-quick-btn" data-fill-target="new_drug_day" data-fill-value="5 Days">5 Days</button>
                                    <button type="button" class="rx-quick-btn" data-fill-target="new_drug_day" data-fill-value="7 Days">7 Days</button>
                                </div>

                                <datalist id="discharge_med_suggest"></datalist>
                                <datalist id="discharge_med_when_suggest">
                                    <option value="BF" label="Before Food"></option>
                                    <option value="AF" label="After Food"></option>
                                    <option value="WF" label="With Food"></option>
                                </datalist>
                                <datalist id="discharge_med_freq_suggest">
                                    <option value="OD" label="Once Daily"></option>
                                    <option value="BD" label="Twice Daily"></option>
                                    <option value="TDS" label="Thrice Daily"></option>
                                    <option value="HS" label="At Bedtime"></option>
                                    <option value="QID" label="Four Times Daily"></option>
                                    <option value="SOS" label="As Needed"></option>
                                </datalist>
                                <div id="discharge_medicine_status" class="complaint-status text-muted"></div>
                            </div>
                        </div>

                        

                        <div class="card border-secondary mt-3" id="section-instructions">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <strong>Discharge Instructions / Advice</strong>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_discharge_manage_food_master">Dietary Master CRUD</button>
                            </div>
                            <div class="card-body row g-2">
                                <div class="col-md-12">
                                    <label class="form-label mb-1">Dietary Advice</label>
                                    <div class="border rounded p-2" style="max-height: 220px; overflow-y: auto;">
                                        <?php if (empty($instructionFoodRows)): ?>
                                            <div class="text-muted small">No dietary advice master found.</div>
                                        <?php else: ?>
                                            <?php foreach ($instructionFoodRows as $food): ?>
                                                <?php
                                                    $foodId = (int) ($food['id'] ?? 0);
                                                    $foodShort = trim((string) ($food['food_short'] ?? ''));
                                                    $foodDesc = trim((string) ($food['food_desc'] ?? ''));
                                                    $foodLang = trim((string) ($food['food_desc_lang'] ?? ''));
                                                    $labelText = $foodShort !== '' ? $foodShort : $foodDesc;
                                                ?>
                                                <div class="form-check mb-1">
                                                    <input
                                                        class="form-check-input instruction-food-item"
                                                        type="checkbox"
                                                        name="instruction_food_ids[]"
                                                        id="instruction_food_<?= $foodId ?>"
                                                        value="<?= $foodId ?>"
                                                        data-food-short="<?= esc($foodShort !== '' ? $foodShort : $foodDesc) ?>"
                                                        data-food-desc="<?= esc($foodDesc !== '' ? $foodDesc : $foodShort) ?>"
                                                            data-food-lang="<?= esc($foodLang) ?>"
                                                        <?= ! empty($instructionFoodIds[$foodId]) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="instruction_food_<?= $foodId ?>">
                                                        <?= esc($labelText) ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-2 d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="btn_instruction_add_selected_food">Add Selected To Advice</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_instruction_clear_food">Clear Selection</button>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="border rounded p-2 bg-light">
                                        <div class="small fw-bold mb-1">Selected Dietary Advice (Hindi print preview)</div>
                                        <div id="instruction_selected_preview" class="small text-muted">No dietary advice selected.</div>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label">Other Advice</label>
                                    <textarea class="form-control" name="instruction_other" id="instruction_other" rows="2" placeholder="Additional custom advice..."><?= esc($instructionOther) ?></textarea>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label">Discharge Instructions / Advice</label>
                                    <textarea class="form-control" name="instruction_remark" id="instruction_remark" rows="3"><?= esc((string) ($instruction_remark ?? '')) ?></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Review After (days/text)</label>
                                    <input type="text" class="form-control" name="review_after" value="<?= esc((string) ($review_after ?? '')) ?>">
                                </div>
                                <div class="col-md-12 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-outline-success btn-sm" name="action" value="save_main" data-reload-section="section-instructions">Save Discharge Advice</button>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="dischargeRxGroupModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Rx Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="text" class="form-control form-control-sm mb-2" id="discharge_rx_group_search" placeholder="Search Rx Group...">
                    <div id="discharge_rx_group_list" class="d-flex flex-wrap gap-2"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="ipdDietaryMasterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Dietary Advice Master</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 align-items-end mb-2">
                        <div class="col-md-9">
                            <label class="form-label">Search</label>
                            <input type="text" class="form-control form-control-sm" id="food_master_search" placeholder="Search short/English/Hindi text...">
                        </div>
                        <div class="col-md-3 d-grid">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_food_master_refresh">Refresh List</button>
                        </div>
                    </div>

                    <div class="table-responsive" style="max-height:220px;overflow:auto;">
                        <table class="table table-sm table-bordered align-middle mb-2">
                            <thead>
                                <tr>
                                    <th style="width:24%;">Short</th>
                                    <th>English</th>
                                    <th>Hindi</th>
                                    <th style="width:110px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="food_master_rows">
                                <tr><td colspan="4" class="text-center text-muted">No records.</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <hr>
                    <input type="hidden" id="food_master_id" value="0">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Short Heading</label>
                            <input type="text" class="form-control form-control-sm" id="food_master_short" maxlength="255">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">English Text</label>
                            <textarea class="form-control form-control-sm" id="food_master_desc" rows="2"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Hindi Text</label>
                            <textarea class="form-control form-control-sm" id="food_master_lang" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="mt-2 d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm" id="btn_food_master_save">Save</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_food_master_clear">New</button>
                    </div>
                    <div id="food_master_status" class="complaint-status text-muted mt-2"></div>
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

    <div class="modal fade" id="ipdSurgeryMasterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Surgery / Procedure Master</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 align-items-end mb-2">
                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <select class="form-select form-select-sm" id="surgery_master_type">
                                <option value="surgery">Surgery</option>
                                <option value="procedure">Procedure</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Search</label>
                            <input type="text" class="form-control form-control-sm" id="surgery_master_search" placeholder="Search name/code/icd...">
                        </div>
                        <div class="col-md-3 d-grid">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_surgery_master_refresh">Refresh List</button>
                        </div>
                    </div>

                    <div class="table-responsive" style="max-height:220px;overflow:auto;">
                        <table class="table table-sm table-bordered align-middle mb-2">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th style="width:120px;">Code</th>
                                    <th style="width:120px;">ICD</th>
                                    <th style="width:70px;">Status</th>
                                    <th style="width:110px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="surgery_master_rows">
                                <tr><td colspan="5" class="text-center text-muted">No records.</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <hr>
                    <input type="hidden" id="surgery_master_id" value="0">
                    <div class="row g-2">
                        <div class="col-md-5">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control form-control-sm" id="surgery_master_name" maxlength="255">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Code</label>
                            <input type="text" class="form-control form-control-sm" id="surgery_master_code" maxlength="60">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">ICD</label>
                            <input type="text" class="form-control form-control-sm" id="surgery_master_icd" maxlength="60">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select class="form-select form-select-sm" id="surgery_master_active">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-2 d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm" id="btn_surgery_master_save">Save</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_surgery_master_clear">New</button>
                    </div>
                    <div id="surgery_master_status" class="complaint-status text-muted mt-2"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function() {
        var narrativeTemplateLoadState = { section: '', target: '', rows: [] };
        var narrativeTemplateSaveState = { section: '', text: '', target: '' };
        var narrativeTemplateToolsBound = false;
        var surgeryMasterState = { surgery: [], procedure: [] };
        var foodMasterState = [];

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

        function updateFormCsrf(form, data) {
            if (!form || !data || !data.csrfName || !data.csrfHash) {
                return;
            }

            var csrfInput = form.querySelector('input[name="' + data.csrfName + '"]');
            if (csrfInput) {
                csrfInput.value = data.csrfHash;
            }
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
                    updateFormCsrf(form, data);

                    var rows = (data && data.rows) ? data.rows : [];
                    targetInput.value = rows.length ? String(rows[0] || inputVal) : inputVal;
                    lookup.value = '';
                    setSectionStatus(statusId, rows.length ? 'Matched predefined term. Click +ADD to save row.' : 'Custom term ready. Click +ADD to save row.', 'success');
                }, 'json').fail(function() {
                    setSectionStatus(statusId, 'Lookup failed right now.', 'error');
                });
            });
        }

        function bindSmartTermInputLookup(form, lookupId, suggestId, statusId, emptyText) {
            var lookup = document.getElementById(lookupId);
            var suggest = document.getElementById(suggestId);
            if (!lookup) {
                return;
            }

            if (lookup.dataset.lookupBound === '1') {
                return;
            }
            lookup.dataset.lookupBound = '1';

            var suggestions = [];

            function optionLabel(row) {
                var label = row.name || '';
                if (row.name_hinglish) {
                    label += ' (' + row.name_hinglish + ')';
                }
                return label;
            }

            function applySelection() {
                var inputVal = (lookup.value || '').trim();
                if (inputVal === '') {
                    setSectionStatus(statusId, '', 'muted');
                    return;
                }

                var chosen = '';
                suggestions.forEach(function(row) {
                    var label = optionLabel(row);
                    if (label.toUpperCase() === inputVal.toUpperCase() || (row.name || '').toUpperCase() === inputVal.toUpperCase()) {
                        chosen = row.name || inputVal;
                    }
                });

                if (chosen !== '') {
                    lookup.value = chosen;
                    setSectionStatus(statusId, 'Term selected. Click +ADD to save row.', 'success');
                    return;
                }

                if (!window.jQuery) {
                    setSectionStatus(statusId, emptyText || 'Custom term ready. Click +ADD to save row.', 'success');
                    return;
                }

                var csrf = getCsrfPair(form);
                var payload = { text: inputVal };
                payload[csrf.name] = csrf.value;

                $.post('<?= base_url('Opd_prescription/complaints_parse') ?>', payload, function(data) {
                    updateFormCsrf(form, data);

                    var rows = (data && data.rows) ? data.rows : [];
                    lookup.value = rows.length ? String(rows[0] || inputVal) : inputVal;
                    setSectionStatus(statusId, rows.length ? 'Matched predefined term. Click +ADD to save row.' : 'Custom term ready. Click +ADD to save row.', 'success');
                }, 'json').fail(function() {
                    setSectionStatus(statusId, 'Lookup failed right now.', 'error');
                });
            }

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
                        html += '<option value="' + $('<div>').text(optionLabel(row)).html() + '"></option>';
                    });
                    suggest.innerHTML = html;
                }, 'json');
            });

            lookup.addEventListener('change', applySelection);
            lookup.addEventListener('blur', applySelection);
        }

        function bindSurgeryTermLookup(form, type, lookupId, suggestId, targetMasterId, statusId) {
            var lookup = document.getElementById(lookupId);
            var suggest = document.getElementById(suggestId);
            var targetMaster = document.getElementById(targetMasterId);
            if (!lookup || !targetMaster || !window.jQuery) {
                return;
            }

            if (lookup.dataset.lookupBound === '1') {
                return;
            }
            lookup.dataset.lookupBound = '1';

            function optionLabel(row) {
                var label = (row.term_name || '').toString();
                var code = (row.term_code || '').toString().trim();
                var icd = (row.icd_code || '').toString().trim();
                if (code !== '') {
                    label += ' [' + code + ']';
                }
                if (icd !== '') {
                    label += ' (ICD ' + icd + ')';
                }
                return label.trim();
            }

            function applySelectionFromInput() {
                var inputVal = (lookup.value || '').trim();
                if (inputVal === '') {
                    targetMaster.value = '0';
                    return;
                }

                var chosen = null;
                (surgeryMasterState[type] || []).forEach(function(row) {
                    var label = optionLabel(row);
                    var name = (row.term_name || '').toString();
                    if (label.toUpperCase() === inputVal.toUpperCase() || name.toUpperCase() === inputVal.toUpperCase()) {
                        chosen = row;
                    }
                });

                if (chosen) {
                    lookup.value = (chosen.term_name || '').toString();
                    targetMaster.value = String(chosen.id || 0);
                    setSectionStatus(statusId, 'Master term selected. Click +ADD to save row.', 'success');
                    return;
                }

                targetMaster.value = '0';
            }

            lookup.addEventListener('input', function() {
                targetMaster.value = '0';
                var q = (lookup.value || '').trim();
                if (q.length < 2) {
                    return;
                }

                $.get('<?= base_url('Ipd_discharge/surgery_master_lookup') ?>?type=' + encodeURIComponent(type) + '&q=' + encodeURIComponent(q), function(data) {
                    var rows = (data && data.rows) ? data.rows : [];
                    surgeryMasterState[type] = rows;

                    if (!suggest) {
                        return;
                    }

                    var html = '';
                    rows.forEach(function(row) {
                        html += '<option value="' + $('<div>').text(optionLabel(row)).html() + '"></option>';
                    });
                    suggest.innerHTML = html;
                }, 'json');
            });

            lookup.addEventListener('change', applySelectionFromInput);
            lookup.addEventListener('blur', applySelectionFromInput);
        }

        function initSurgeryMasterCrud(form) {
            if (!window.jQuery) {
                return;
            }

            if (window.__ipdSurgeryCrudBound === true) {
                return;
            }
            window.__ipdSurgeryCrudBound = true;

            var $type = $('#surgery_master_type');
            var $search = $('#surgery_master_search');
            var $rows = $('#surgery_master_rows');

            function setMasterStatus(text, level) {
                setSectionStatus('surgery_master_status', text, level || 'muted');
            }

            function rowHtml(row) {
                var status = parseInt(row.is_active || '0', 10) === 1 ? 'Active' : 'Inactive';
                var safeName = $('<div>').text(row.term_name || '').html();
                var safeCode = $('<div>').text(row.term_code || '').html();
                var safeIcd = $('<div>').text(row.icd_code || '').html();
                return '<tr>'
                    + '<td>' + safeName + '</td>'
                    + '<td>' + safeCode + '</td>'
                    + '<td>' + safeIcd + '</td>'
                    + '<td>' + status + '</td>'
                    + '<td>'
                    + '<button type="button" class="btn btn-outline-primary btn-sm btn-master-edit" data-id="' + (row.id || 0) + '">Edit</button> '
                    + '<button type="button" class="btn btn-outline-danger btn-sm btn-master-delete" data-id="' + (row.id || 0) + '">Del</button>'
                    + '</td>'
                    + '</tr>';
            }

            function clearMasterForm() {
                $('#surgery_master_id').val('0');
                $('#surgery_master_name').val('');
                $('#surgery_master_code').val('');
                $('#surgery_master_icd').val('');
                $('#surgery_master_active').val('1');
            }

            function fetchMasterRows() {
                var type = ($type.val() || 'surgery').toString();
                var q = ($search.val() || '').toString().trim();
                $.get('<?= base_url('Ipd_discharge/surgery_master_list') ?>?type=' + encodeURIComponent(type) + '&q=' + encodeURIComponent(q), function(data) {
                    var rows = (data && data.rows) ? data.rows : [];
                    surgeryMasterState[type] = rows;
                    if (!rows.length) {
                        $rows.html('<tr><td colspan="5" class="text-center text-muted">No records.</td></tr>');
                        return;
                    }

                    var html = '';
                    rows.forEach(function(row) {
                        html += rowHtml(row);
                    });
                    $rows.html(html);
                }, 'json').fail(function() {
                    setMasterStatus('Unable to load master list.', 'error');
                });
            }

            $(document).on('click', '#btn_discharge_manage_surgery_master', function() {
                clearMasterForm();
                setMasterStatus('', 'muted');
                fetchMasterRows();
                showModalById('ipdSurgeryMasterModal');
            });

            $('#btn_surgery_master_refresh').on('click', fetchMasterRows);
            $type.on('change', function() {
                clearMasterForm();
                fetchMasterRows();
            });
            $search.on('input', function() {
                fetchMasterRows();
            });

            $('#btn_surgery_master_clear').on('click', function() {
                clearMasterForm();
            });

            $(document).on('click', '.btn-master-edit', function() {
                var id = parseInt($(this).data('id') || '0', 10);
                var type = ($type.val() || 'surgery').toString();
                var row = (surgeryMasterState[type] || []).find(function(item) {
                    return parseInt(item.id || '0', 10) === id;
                }) || null;
                if (!row) {
                    return;
                }

                $('#surgery_master_id').val(String(row.id || 0));
                $('#surgery_master_name').val((row.term_name || '').toString());
                $('#surgery_master_code').val((row.term_code || '').toString());
                $('#surgery_master_icd').val((row.icd_code || '').toString());
                $('#surgery_master_active').val(parseInt(row.is_active || '0', 10) === 1 ? '1' : '0');
            });

            $(document).on('click', '.btn-master-delete', function() {
                var id = parseInt($(this).data('id') || '0', 10);
                if (id <= 0) {
                    return;
                }

                if (!window.confirm('Delete this master row?')) {
                    return;
                }

                var csrf = getCsrfPair(form);
                var payload = { id: id };
                payload[csrf.name] = csrf.value;

                $.post('<?= base_url('Ipd_discharge/surgery_master_delete') ?>', payload, function(data) {
                    updateFormCsrf(form, data);
                    if (!data || parseInt(data.update || '0', 10) !== 1) {
                        setMasterStatus((data && data.error_text) ? data.error_text : 'Unable to delete record.', 'error');
                        return;
                    }

                    setMasterStatus('Record deleted.', 'success');
                    fetchMasterRows();
                }, 'json').fail(function() {
                    setMasterStatus('Delete failed.', 'error');
                });
            });

            $('#btn_surgery_master_save').on('click', function() {
                var name = ($('#surgery_master_name').val() || '').toString().trim();
                if (name === '') {
                    setMasterStatus('Name is required.', 'error');
                    return;
                }

                var csrf = getCsrfPair(form);
                var payload = {
                    id: parseInt($('#surgery_master_id').val() || '0', 10),
                    type: ($type.val() || 'surgery').toString(),
                    name: name,
                    code: ($('#surgery_master_code').val() || '').toString().trim(),
                    icd_code: ($('#surgery_master_icd').val() || '').toString().trim(),
                    is_active: parseInt($('#surgery_master_active').val() || '1', 10)
                };
                payload[csrf.name] = csrf.value;

                $.post('<?= base_url('Ipd_discharge/surgery_master_save') ?>', payload, function(data) {
                    updateFormCsrf(form, data);
                    if (!data || parseInt(data.update || '0', 10) !== 1) {
                        setMasterStatus((data && data.error_text) ? data.error_text : 'Unable to save record.', 'error');
                        return;
                    }

                    setMasterStatus('Record saved.', 'success');
                    clearMasterForm();
                    fetchMasterRows();
                }, 'json').fail(function() {
                    setMasterStatus('Save failed.', 'error');
                });
            });
        }

        function initSurgeryTools(form) {
            bindSurgeryTermLookup(form, 'surgery', 'new_surgery_name', 'discharge_surgery_suggest', 'new_surgery_master_id', 'discharge_surgery_status');
            bindSurgeryTermLookup(form, 'procedure', 'new_procedure_name', 'discharge_procedure_suggest', 'new_procedure_master_id', 'discharge_surgery_status');
            initSurgeryMasterCrud(form);
        }

        function bindDiagnosisIcdLookup(form) {
            var lookup = document.getElementById('new_diagnosis_name');
            var suggest = document.getElementById('discharge_diagnosis_suggest');
            var seedBtn = document.getElementById('btn_discharge_seed_icd');
            if (!lookup || !suggest || !window.jQuery) {
                return;
            }

            if (lookup.dataset.lookupBound === '1') {
                return;
            }
            lookup.dataset.lookupBound = '1';

            var suggestions = [];

            function diagnosisLabel(row) {
                var name = (row.name || '').toString();
                var code = (row.icd_code || '').toString().trim();
                if (code !== '') {
                    return name + ' (ICD ' + code + ')';
                }
                return name;
            }

            lookup.addEventListener('input', function() {
                var q = (lookup.value || '').trim();
                if (q.length < 2) {
                    return;
                }

                $.get('<?= base_url('Ipd_discharge/diagnosis_icd_lookup') ?>?q=' + encodeURIComponent(q), function(data) {
                    suggestions = (data && data.rows) ? data.rows : [];
                    var html = '';
                    suggestions.forEach(function(row) {
                        html += '<option value="' + $('<div>').text(diagnosisLabel(row)).html() + '"></option>';
                    });
                    suggest.innerHTML = html;
                }, 'json');
            });

            function applyDiagnosisSelection() {
                var inputVal = (lookup.value || '').trim();
                if (inputVal === '') {
                    setSectionStatus('discharge_diagnosis_status', '', 'muted');
                    return;
                }

                var chosen = null;
                suggestions.forEach(function(row) {
                    var label = diagnosisLabel(row);
                    var name = (row.name || '').toString();
                    if (label.toUpperCase() === inputVal.toUpperCase() || name.toUpperCase() === inputVal.toUpperCase()) {
                        chosen = row;
                    }
                });

                if (chosen) {
                    var diagnosisText = (chosen.name || '').toString().trim();
                    var code = (chosen.icd_code || '').toString().trim();
                    if (code !== '') {
                        diagnosisText += ' [ICD: ' + code + ']';
                    }
                    lookup.value = diagnosisText;
                    setSectionStatus('discharge_diagnosis_status', code !== '' ? 'Diagnosis with ICD selected. Click +ADD to save row.' : 'Diagnosis selected. Click +ADD to save row.', 'success');
                    return;
                }

                setSectionStatus('discharge_diagnosis_status', 'Custom diagnosis ready. Click +ADD to save row.', 'success');
            }

            lookup.addEventListener('change', applyDiagnosisSelection);
            lookup.addEventListener('blur', applyDiagnosisSelection);

            if (seedBtn) {
                if (seedBtn.dataset.seedBound === '1') {
                    return;
                }
                seedBtn.dataset.seedBound = '1';

                seedBtn.addEventListener('click', function() {
                    var csrf = getCsrfPair(form);
                    var payload = {};
                    payload[csrf.name] = csrf.value;

                    $.post('<?= base_url('Ipd_discharge/diagnosis_icd_seed_starter') ?>', payload, function(data) {
                        updateFormCsrf(form, data);
                        if (!data || parseInt(data.update || '0', 10) !== 1) {
                            setSectionStatus('discharge_diagnosis_status', (data && data.error_text) ? data.error_text : 'Unable to load ICD starter.', 'error');
                            return;
                        }

                        setSectionStatus('discharge_diagnosis_status', data.error_text || 'ICD starter loaded.', 'success');
                    }, 'json').fail(function() {
                        setSectionStatus('discharge_diagnosis_status', 'ICD starter request failed.', 'error');
                    });
                });
            }
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

            bindDiagnosisIcdLookup(form);

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

            bindSmartTermInputLookup(
                form,
                'new_course_name',
                'discharge_course_suggest',
                'discharge_course_status',
                'Custom term ready. Click +ADD to save row.'
            );

            bindAiRewrite(form, 'btn_discharge_ai_course', 'course_remark', 'autotype', 'discharge_course_status', 'Type course narrative first.');
            bindAiRewrite(form, 'btn_discharge_hinglish_course', 'course_remark', 'hinglish_to_english', 'discharge_course_status', 'Type course narrative first.');
        }

        function initInstructionTools() {
            var section = document.getElementById('section-instructions');
            if (!section || section.dataset.toolsBound === '1') {
                return;
            }
            section.dataset.toolsBound = '1';

            var addSelectedBtn = section.querySelector('#btn_instruction_add_selected_food');
            var clearBtn = section.querySelector('#btn_instruction_clear_food');
            var remark = section.querySelector('#instruction_remark');
            var other = section.querySelector('#instruction_other');
            var preview = section.querySelector('#instruction_selected_preview');
            var autoSaveTimer = null;
            var autoSaveBusy = false;

            function saveInstructionSectionImmediate() {
                var activeForm = section.closest('form') || getDischargeForm();
                if (!activeForm || autoSaveBusy) {
                    return;
                }

                if (!window.jQuery) {
                    return;
                }

                autoSaveBusy = true;
                var csrf = getCsrfPair(activeForm);
                var payload = {
                    action: 'save_main',
                    dietary_autosave: '1',
                    instruction_other: other ? String(other.value || '').trim() : ''
                };
                payload[csrf.name] = csrf.value;

                section.querySelectorAll('.instruction-food-item:checked').forEach(function(item) {
                    var id = parseInt(item.value || '0', 10);
                    if (id > 0) {
                        if (!Array.isArray(payload.instruction_food_ids)) {
                            payload.instruction_food_ids = [];
                        }
                        payload.instruction_food_ids.push(String(id));
                    }
                });

                window.jQuery.ajax({
                    url: activeForm.getAttribute('action') || window.location.href,
                    type: 'POST',
                    data: payload,
                    dataType: 'json',
                    timeout: 120000
                }).done(function(data) {
                    updateFormCsrf(activeForm, data || {});
                    if (data && parseInt(data.update || '0', 10) === 1) {
                        setSectionStatus('discharge_instruction_status', 'Dietary advice saved.', 'success');
                    } else {
                        setSectionStatus('discharge_instruction_status', (data && data.error_text) ? data.error_text : 'Unable to save dietary advice.', 'error');
                    }
                }).always(function() {
                    autoSaveBusy = false;
                });
            }

            function saveInstructionSection() {
                if (autoSaveTimer) {
                    window.clearTimeout(autoSaveTimer);
                }
                autoSaveTimer = window.setTimeout(function() {
                    saveInstructionSectionImmediate();
                }, 180);
            }

            function refreshInstructionPreview() {
                if (!preview) {
                    return;
                }

                var lines = [];
                section.querySelectorAll('.instruction-food-item:checked').forEach(function(item) {
                    var heading = String(item.getAttribute('data-food-short') || '').trim();
                    var lang = String(item.getAttribute('data-food-lang') || '').trim();
                    var desc = String(item.getAttribute('data-food-desc') || '').trim();
                    var body = lang !== '' ? lang : desc;
                    if (heading === '' && body === '') {
                        return;
                    }

                    var line = heading !== '' ? ('<strong>' + heading + ':</strong> ') : '';
                    line += body !== '' ? body : heading;
                    lines.push('<div>' + line + '</div>');
                });

                var otherText = other ? String(other.value || '').trim() : '';
                if (otherText !== '') {
                    lines.push('<div><strong>Other:</strong> ' + otherText + '</div>');
                }

                preview.innerHTML = lines.length ? lines.join('') : '<span class="text-muted">No dietary advice selected.</span>';
            }

            function appendLinesToAdvice(lines) {
                if (!remark || !Array.isArray(lines) || !lines.length) {
                    return;
                }

                var existing = String(remark.value || '').trim();
                var bucket = existing === '' ? [] : existing.split(/\r?\n/).map(function(line) {
                    return String(line || '').trim();
                }).filter(function(line) {
                    return line !== '';
                });

                lines.forEach(function(line) {
                    var normalized = String(line || '').trim();
                    if (normalized === '') {
                        return;
                    }
                    if (bucket.some(function(prev) { return prev.toUpperCase() === normalized.toUpperCase(); })) {
                        return;
                    }
                    bucket.push(normalized);
                });

                remark.value = bucket.join('\n');
            }

            if (addSelectedBtn && addSelectedBtn.dataset.bound !== '1') {
                addSelectedBtn.dataset.bound = '1';
                addSelectedBtn.addEventListener('click', function() {
                    var lines = [];
                    section.querySelectorAll('.instruction-food-item:checked').forEach(function(item) {
                        var desc = String(item.getAttribute('data-food-desc') || '').trim();
                        var shortText = String(item.getAttribute('data-food-short') || '').trim();
                        var text = desc !== '' ? desc : shortText;
                        if (text !== '') {
                            lines.push(text);
                        }
                    });

                    var otherText = other ? String(other.value || '').trim() : '';
                    if (otherText !== '') {
                        lines.push(otherText);
                    }

                    appendLinesToAdvice(lines);
                });
            }

            if (clearBtn && clearBtn.dataset.bound !== '1') {
                clearBtn.dataset.bound = '1';
                clearBtn.addEventListener('click', function() {
                    section.querySelectorAll('.instruction-food-item:checked').forEach(function(item) {
                        item.checked = false;
                    });
                    refreshInstructionPreview();
                    saveInstructionSection();
                });
            }

            section.querySelectorAll('.instruction-food-item').forEach(function(item) {
                if (item.dataset.bound === '1') {
                    return;
                }
                item.dataset.bound = '1';
                item.addEventListener('change', function() {
                    refreshInstructionPreview();
                    saveInstructionSection();
                });
            });

            if (other && other.dataset.bound !== '1') {
                other.dataset.bound = '1';
                other.addEventListener('input', refreshInstructionPreview);
            }

            refreshInstructionPreview();
            initDietaryMasterCrud(getDischargeForm());
        }

        function initDietaryMasterCrud(form) {
            if (!window.jQuery) {
                return;
            }
            if (window.__ipdFoodCrudBound === true) {
                return;
            }
            window.__ipdFoodCrudBound = true;

            var $search = $('#food_master_search');
            var $rows = $('#food_master_rows');

            function setFoodStatus(text, level) {
                setSectionStatus('food_master_status', text, level || 'muted');
            }

            function clearFoodForm() {
                $('#food_master_id').val('0');
                $('#food_master_short').val('');
                $('#food_master_desc').val('');
                $('#food_master_lang').val('');
            }

            function rowHtml(row) {
                var safeShort = $('<div>').text(row.food_short || '').html();
                var safeDesc = $('<div>').text(row.food_desc || '').html();
                var safeLang = $('<div>').text(row.food_desc_lang || '').html();
                return '<tr>'
                    + '<td>' + safeShort + '</td>'
                    + '<td>' + safeDesc + '</td>'
                    + '<td>' + safeLang + '</td>'
                    + '<td>'
                    + '<button type="button" class="btn btn-outline-primary btn-sm btn-food-edit" data-id="' + (row.id || 0) + '">Edit</button> '
                    + '<button type="button" class="btn btn-outline-danger btn-sm btn-food-delete" data-id="' + (row.id || 0) + '">Del</button>'
                    + '</td>'
                    + '</tr>';
            }

            function fetchFoodRows() {
                var q = ($search.val() || '').toString().trim();
                $.get('<?= base_url('Ipd_discharge/dietary_master_list') ?>?q=' + encodeURIComponent(q), function(data) {
                    var rows = (data && data.rows) ? data.rows : [];
                    foodMasterState = rows;
                    if (!rows.length) {
                        $rows.html('<tr><td colspan="4" class="text-center text-muted">No records.</td></tr>');
                        return;
                    }

                    var html = '';
                    rows.forEach(function(row) {
                        html += rowHtml(row);
                    });
                    $rows.html(html);
                }, 'json').fail(function() {
                    setFoodStatus('Unable to load dietary master list.', 'error');
                });
            }

            $(document).on('click', '#btn_discharge_manage_food_master', function() {
                clearFoodForm();
                setFoodStatus('', 'muted');
                fetchFoodRows();
                showModalById('ipdDietaryMasterModal');
            });

            $('#btn_food_master_refresh').on('click', fetchFoodRows);
            $search.on('input', fetchFoodRows);
            $('#btn_food_master_clear').on('click', clearFoodForm);

            $(document).on('click', '.btn-food-edit', function() {
                var id = parseInt($(this).data('id') || '0', 10);
                var row = (foodMasterState || []).find(function(item) {
                    return parseInt(item.id || '0', 10) === id;
                }) || null;
                if (!row) {
                    return;
                }

                $('#food_master_id').val(String(row.id || 0));
                $('#food_master_short').val((row.food_short || '').toString());
                $('#food_master_desc').val((row.food_desc || '').toString());
                $('#food_master_lang').val((row.food_desc_lang || '').toString());
            });

            $(document).on('click', '.btn-food-delete', function() {
                var id = parseInt($(this).data('id') || '0', 10);
                if (id <= 0) {
                    return;
                }
                if (!window.confirm('Delete this dietary master row?')) {
                    return;
                }

                var csrf = getCsrfPair(form);
                var payload = { id: id };
                payload[csrf.name] = csrf.value;

                $.post('<?= base_url('Ipd_discharge/dietary_master_delete') ?>', payload, function(data) {
                    updateFormCsrf(form, data);
                    if (!data || parseInt(data.update || '0', 10) !== 1) {
                        setFoodStatus((data && data.error_text) ? data.error_text : 'Unable to delete record.', 'error');
                        return;
                    }
                    setFoodStatus('Record deleted.', 'success');
                    fetchFoodRows();
                }, 'json').fail(function() {
                    setFoodStatus('Delete failed.', 'error');
                });
            });

            $('#btn_food_master_save').on('click', function() {
                var shortText = ($('#food_master_short').val() || '').toString().trim();
                if (shortText === '') {
                    setFoodStatus('Short heading is required.', 'error');
                    return;
                }

                var csrf = getCsrfPair(form);
                var payload = {
                    id: parseInt($('#food_master_id').val() || '0', 10),
                    food_short: shortText,
                    food_desc: ($('#food_master_desc').val() || '').toString().trim(),
                    food_desc_lang: ($('#food_master_lang').val() || '').toString().trim()
                };
                payload[csrf.name] = csrf.value;

                $.post('<?= base_url('Ipd_discharge/dietary_master_save') ?>', payload, function(data) {
                    updateFormCsrf(form, data);
                    if (!data || parseInt(data.update || '0', 10) !== 1) {
                        setFoodStatus((data && data.error_text) ? data.error_text : 'Unable to save record.', 'error');
                        return;
                    }

                    setFoodStatus('Record saved.', 'success');
                    clearFoodForm();
                    fetchFoodRows();
                }, 'json').fail(function() {
                    setFoodStatus('Save failed.', 'error');
                });
            });
        }

        function initMedicineTools() {
            var section = document.getElementById('section-medicine');
            if (!section || section.dataset.toolsBound === '1') {
                return;
            }
            section.dataset.toolsBound = '1';

            if (!window.jQuery) {
                return;
            }

            var suggestRows = [];
            var rxGroupCache = [];
            var medInput = section.querySelector('#new_drug_name');
            var medSuggest = section.querySelector('#discharge_med_suggest');
            var medType = section.querySelector('#new_drug_type');
            var medWhen = section.querySelector('#new_drug_when');
            var medFreq = section.querySelector('#new_drug_freq');
            var rxGroupInput = section.querySelector('#selected_rx_group_id');
            var rxGroupName = section.querySelector('#rx_group_selected_name');
            var rxGroupList = document.getElementById('discharge_rx_group_list');
            var rxGroupSearch = document.getElementById('discharge_rx_group_search');
            var applyBtn = section.querySelector('#btn_apply_rx_group');

            function normalizeRxShortCode(raw, kind) {
                var v = String(raw || '').trim();
                if (v === '') {
                    return '';
                }

                var key = v.replace(/\./g, '').replace(/\s+/g, '').toUpperCase();
                if (kind === 'when') {
                    var whenMap = {
                        'BEFOREFOOD': 'BF',
                        'BFOOD': 'BF',
                        'BF': 'BF',
                        'AF': 'AF',
                        'AFOOD': 'AF',
                        'AFTERFOOD': 'AF',
                        'WF': 'WF',
                        'WFOOD': 'WF',
                        'WITHFOOD': 'WF'
                    };
                    return whenMap[key] || key;
                }

                var freqMap = {
                    'OD': 'OD',
                    'QD': 'OD',
                    'ONCEDAILY': 'OD',
                    'BD': 'BD',
                    'BID': 'BD',
                    'TWICEDAILY': 'BD',
                    'TDS': 'TDS',
                    'TID': 'TDS',
                    'THRICEDAILY': 'TDS',
                    'HS': 'HS',
                    'QHS': 'HS',
                    'QID': 'QID',
                    'SOS': 'SOS'
                };
                return freqMap[key] || key;
            }

            function setMedicineStatus(text, level) {
                setSectionStatus('discharge_medicine_status', text, level || 'muted');
            }

            function renderRxGroups() {
                if (!rxGroupList) {
                    return;
                }

                var q = rxGroupSearch ? String(rxGroupSearch.value || '').trim().toLowerCase() : '';
                var html = '';
                rxGroupCache.forEach(function(row) {
                    var id = parseInt(row.id || '0', 10);
                    var name = String(row.rx_group_name || '').trim();
                    if (id <= 0 || name === '') {
                        return;
                    }
                    if (q !== '' && name.toLowerCase().indexOf(q) === -1) {
                        return;
                    }

                    var medCount = parseInt(row.med_count || '0', 10);
                    var label = name + (medCount > 0 ? (' (' + medCount + ')') : '');
                    html += '<button type="button" class="btn btn-outline-secondary btn-sm js-discharge-rx-group" data-id="' + id + '" data-name="' + $('<div>').text(name).html() + '">' + $('<div>').text(label).html() + '</button>';
                });

                rxGroupList.innerHTML = html || '<div class="text-muted small">No Rx Group found.</div>';
            }

            function loadRxGroups() {
                $.get('<?= base_url('Opd_prescription/save_rx_group_list') ?>/0', function(data) {
                    rxGroupCache = (data && data.rows) ? data.rows : [];
                    renderRxGroups();
                }, 'json').fail(function() {
                    setMedicineStatus('Unable to load Rx Groups.', 'error');
                });
            }

            medInput.addEventListener('input', function() {
                var q = String(medInput.value || '').trim();
                if (q.length < 2) {
                    return;
                }

                $.get('<?= base_url('Opd_prescription/medicine_search') ?>?q=' + encodeURIComponent(q) + '&scope=active', function(data) {
                    suggestRows = (data && data.rows) ? data.rows : [];
                    var html = '';
                    suggestRows.forEach(function(row) {
                        var name = String(row.med_name || '').trim();
                        if (name === '') {
                            return;
                        }
                        html += '<option value="' + $('<div>').text(name).html() + '"></option>';
                    });
                    if (medSuggest) {
                        medSuggest.innerHTML = html;
                    }
                }, 'json');
            });

            medInput.addEventListener('change', function() {
                var value = String(medInput.value || '').trim().toUpperCase();
                if (value === '') {
                    return;
                }

                var matched = null;
                suggestRows.forEach(function(row) {
                    if (String(row.med_name || '').trim().toUpperCase() === value) {
                        matched = row;
                    }
                });

                if (matched && medType && String(medType.value || '').trim() === '') {
                    medType.value = String(matched.med_type || '').trim();
                }
            });

            section.querySelectorAll('.rx-quick-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var targetId = String(btn.getAttribute('data-fill-target') || '').trim();
                    var value = String(btn.getAttribute('data-fill-value') || '').trim();
                    var target = targetId ? section.querySelector('#' + targetId) : null;
                    if (target) {
                        if (targetId === 'new_drug_when') {
                            target.value = normalizeRxShortCode(value, 'when');
                        } else if (targetId === 'new_drug_freq') {
                            target.value = normalizeRxShortCode(value, 'freq');
                        } else {
                            target.value = value;
                        }
                    }
                });
            });

            if (medWhen && medWhen.dataset.shortBound !== '1') {
                medWhen.dataset.shortBound = '1';
                medWhen.addEventListener('blur', function() {
                    medWhen.value = normalizeRxShortCode(medWhen.value, 'when');
                });
                medWhen.addEventListener('change', function() {
                    medWhen.value = normalizeRxShortCode(medWhen.value, 'when');
                });
            }

            if (medFreq && medFreq.dataset.shortBound !== '1') {
                medFreq.dataset.shortBound = '1';
                medFreq.addEventListener('blur', function() {
                    medFreq.value = normalizeRxShortCode(medFreq.value, 'freq');
                });
                medFreq.addEventListener('change', function() {
                    medFreq.value = normalizeRxShortCode(medFreq.value, 'freq');
                });
            }

            var openRxBtn = section.querySelector('#btn_open_rx_group_modal');
            if (openRxBtn) {
                openRxBtn.addEventListener('click', function() {
                    loadRxGroups();
                    showModalById('dischargeRxGroupModal');
                });
            }

            if (rxGroupSearch) {
                if (rxGroupSearch.dataset.bound !== '1') {
                    rxGroupSearch.dataset.bound = '1';
                    rxGroupSearch.addEventListener('input', function() {
                        renderRxGroups();
                    });
                }
            }

            if (window.__dischargeRxGroupSelectBound !== true) {
                window.__dischargeRxGroupSelectBound = true;
                $(document).on('click', '.js-discharge-rx-group', function() {
                    var id = parseInt($(this).data('id') || '0', 10);
                    var name = String($(this).data('name') || '').trim();
                    var activeSection = document.getElementById('section-medicine');
                    if (!activeSection || id <= 0) {
                        return;
                    }

                    var activeRxInput = activeSection.querySelector('#selected_rx_group_id');
                    var activeRxLabel = activeSection.querySelector('#rx_group_selected_name');
                    var activeApplyBtn = activeSection.querySelector('#btn_apply_rx_group');
                    if (!activeRxInput) {
                        return;
                    }

                    activeRxInput.value = String(id);
                    if (activeRxLabel) {
                        activeRxLabel.textContent = name ? ('Selected: ' + name) : ('Selected Rx Group #' + id);
                    }
                    hideModalById('dischargeRxGroupModal');

                    if (activeApplyBtn) {
                        activeApplyBtn.click();
                    }
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
        initComplaintTools();
        initSurgeryTools(getDischargeForm());
        initDiagnosisTools();
        initCourseTools();
        initMedicineTools();
        initInstructionTools();
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
                statusEl.value = 'Allergies Not Known';
                status = 'Allergies Not Known';
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
                var saveMode = submitter && submitter.dataset ? String(submitter.dataset.saveMode || '').toLowerCase() : '';
                var statusTargetId = submitter && submitter.dataset ? String(submitter.dataset.statusId || '').trim() : '';
                var isComplaintAction = actionValue === 'add_complaint' || actionValue === 'remove_complaint';
                var isMedicineAction = actionValue === 'add_drug' || actionValue === 'remove_drug' || actionValue === 'apply_rx_group';
                var targetSectionId = '';

                if (saveMode === 'json') {
                    payloadArray.push({
                        name: 'ajax_mode',
                        value: 'json'
                    });
                    payload = window.jQuery.param(payloadArray);
                }

                if (submitter && submitter.dataset && submitter.dataset.reloadSection) {
                    targetSectionId = String(submitter.dataset.reloadSection);
                }

                if (!targetSectionId && submitter && typeof submitter.closest === 'function') {
                    var sectionCard = submitter.closest('.card[id]');
                    if (sectionCard && sectionCard.id) {
                        targetSectionId = sectionCard.id;
                    }
                }

                if (isMedicineAction) {
                    targetSectionId = 'section-medicine';
                }

                window.jQuery.ajax({
                    url: form.getAttribute('action') || window.location.href,
                    type: 'POST',
                    data: payload,
                    dataType: saveMode === 'json' ? 'json' : 'html',
                    timeout: 120000
                }).done(function(result) {
                    if (saveMode === 'json') {
                        updateFormCsrf(form, result || {});
                        if (statusTargetId !== '') {
                            var level = result && String(result.notice_type || '').toLowerCase() === 'warning' ? 'error' : 'success';
                            if (!result || parseInt(result.update || '0', 10) !== 1) {
                                level = 'error';
                            }
                            setSectionStatus(statusTargetId, (result && result.error_text) ? String(result.error_text) : 'Save completed.', level);
                        }
                        return;
                    }

                    var html = String(result || '');
                    var holder = document.createElement('div');
                    holder.innerHTML = html;
                    updateCsrfFromHtml(holder, form);
                    patchNoticeFromHtml(holder);
                    notifyFromHtml(holder);

                    if (isComplaintAction) {
                        patchComplaintSectionFromHtml(holder, form);
                        initComplaintEditor();
                        initComplaintTools();
                        initSurgeryTools(document.querySelector('form[action*="Ipd_discharge/ipd_select/"]'));
                        initDiagnosisTools();
                        initCourseTools();
                        initMedicineTools();
                        initInstructionTools();
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
                        } else if (targetSectionId === 'section-systemic') {
                            initComplaintEditor();
                            initNabhHistorySection(document.querySelector('form[action*="Ipd_discharge/ipd_select/"]'));
                        } else if (targetSectionId === 'section-nursing-history') {
                            initNabhHistorySection(document.querySelector('form[action*="Ipd_discharge/ipd_select/"]'));
                        } else if (targetSectionId === 'section-surgery') {
                            initSurgeryTools(document.querySelector('form[action*="Ipd_discharge/ipd_select/"]'));
                        } else if (targetSectionId === 'section-diagnosis') {
                            initDiagnosisTools();
                        } else if (targetSectionId === 'section-course') {
                            initCourseTools();
                        } else if (targetSectionId === 'section-medicine') {
                            initMedicineTools();
                        } else if (targetSectionId === 'section-instructions') {
                            initInstructionTools();
                        }
                        bindDischargeAjaxSubmit();
                        syncNavOnScroll();
                        return;
                    }

                    patchFormAreaFromHtml(holder);
                    initComplaintEditor();
                    initComplaintTools();
                    initSurgeryTools(document.querySelector('form[action*="Ipd_discharge/ipd_select/"]'));
                    initDiagnosisTools();
                    initCourseTools();
                    initMedicineTools();
                    initInstructionTools();
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

        function getFormScrollContainer() {
            var container = document.querySelector('.discharge-form-area');
            if (!container) {
                return null;
            }

            var style = window.getComputedStyle(container);
            var isScrollable = (style.overflowY === 'auto' || style.overflowY === 'scroll')
                && container.scrollHeight > container.clientHeight;
            return isScrollable ? container : null;
        }

        navLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                var sectionId = link.getAttribute('data-target');
                var section = document.getElementById(sectionId);
                if (!section) {
                    return;
                }

                var container = getFormScrollContainer();
                if (container) {
                    var cRect = container.getBoundingClientRect();
                    var sRect = section.getBoundingClientRect();
                    var nextTop = container.scrollTop + (sRect.top - cRect.top) - 8;
                    container.scrollTo({ top: Math.max(nextTop, 0), behavior: 'smooth' });
                } else {
                    section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                setActiveNavBySection(sectionId);
            });
        });

        function syncNavOnScroll() {
            var container = getFormScrollContainer();
            var referenceTop = 120;
            if (container) {
                referenceTop = container.getBoundingClientRect().top + 12;
            }

            var bestSection = sectionIds[0];
            var bestDelta = Number.POSITIVE_INFINITY;

            sectionIds.forEach(function(id) {
                var section = document.getElementById(id);
                if (!section) {
                    return;
                }
                var rect = section.getBoundingClientRect();
                var delta = Math.abs(rect.top - referenceTop);
                if (delta < bestDelta) {
                    bestDelta = delta;
                    bestSection = id;
                }
            });

            setActiveNavBySection(bestSection);
        }

        window.addEventListener('scroll', syncNavOnScroll, { passive: true });
        document.addEventListener('scroll', function(evt) {
            var target = evt.target;
            if (target && target.classList && target.classList.contains('discharge-form-area')) {
                syncNavOnScroll();
            }
        }, { passive: true, capture: true });
        syncNavOnScroll();
    })();
    </script>
</section>
