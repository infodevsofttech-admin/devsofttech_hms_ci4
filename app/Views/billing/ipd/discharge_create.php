<?php
$ipd = $ipd_info ?? null;
$person = $person_info ?? null;
$ipdId = (int) ($ipd_id ?? 0);
$noticeText = (string) ($notice ?? '');
$noticeType = (string) ($notice_type ?? 'success');
$statusRows = $status_rows ?? [];
$departmentRows = $department_rows ?? [];
$master = $ipd_master_row ?? [];
$dischargeTemplates = $discharge_templates ?? [];
$selectedDischargeTemplateId = (int) ($selected_discharge_template_id ?? 0);

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

$patientId = (int) ($person->id ?? $person->p_id ?? 0);
$patientAbha = '';
if ($person) {
    $patientAbha = trim((string) (
        $person->abha_id
        ?? $person->abha_no
        ?? $person->abha_address
        ?? $person->abha
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
$doseMasterMaps = $dose_master_maps ?? ['dose' => [], 'when' => [], 'freq' => [], 'where' => []];

// Helper function to get dose label from ID
$getDoseLabel = function ($id, $type) use ($doseMasterMaps) {
    $id = (int) $id;
    if ($id <= 0 || !isset($doseMasterMaps[$type][$id])) {
        return '';
    }
    return $doseMasterMaps[$type][$id]['label'] ?? '';
};

$medicineRows = [];
if (! empty($legacyDrugRows)) {
    foreach ($legacyDrugRows as $row) {
        $doseId = (int) ($row['dosage'] ?? 0);
        $whenId = (int) ($row['dosage_when'] ?? 0);
        $freqId = (int) ($row['dosage_freq'] ?? 0);
        $doseLabel = $getDoseLabel($doseId, 'dose');
        $whenLabel = $getDoseLabel($whenId, 'when');
        $freqLabel = $getDoseLabel($freqId, 'freq');
        $medicineRows[] = [
            'id' => (int) ($row['id'] ?? 0),
            'source' => 'legacy',
            'med_name' => (string) ($row['med_name'] ?? ''),
            'med_salt' => (string) ($row['med_salt'] ?? ''),
            'med_type' => (string) ($row['med_type'] ?? ''),
            'dosage' => $doseLabel !== '' ? $doseLabel : (string) ($row['dosage'] ?? ''),
            'dosage_when' => $whenLabel !== '' ? $whenLabel : (string) ($row['dosage_when'] ?? ''),
            'dosage_freq' => $freqLabel !== '' ? $freqLabel : (string) ($row['dosage_freq'] ?? ''),
            'dosage_id' => $doseId,
            'dosage_when_id' => $whenId,
            'dosage_freq_id' => $freqId,
            'no_of_days' => (string) ($row['no_of_days'] ?? ''),
            'qty' => (string) ($row['qty'] ?? ''),
            'remark' => (string) ($row['remark'] ?? ''),
        ];
    }
}
if (! empty($drugRows)) {
    foreach ($drugRows as $row) {
        $medicineRows[] = [
            'id' => (int) ($row['id'] ?? 0),
            'source' => 'classic',
            'med_name' => (string) ($row['drug_name'] ?? ''),
            'med_salt' => '',
            'med_type' => '',
            'dosage' => (string) ($row['drug_dose'] ?? ''),
            'dosage_when' => '',
            'dosage_freq' => '',
            'dosage_id' => 0,
            'dosage_when_id' => 0,
            'dosage_freq_id' => 0,
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

    .discharge-main-card>.card-body {
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

        .discharge-main-card>.card-body {
            /* Let notices consume natural height; keep form row as flexible remainder */
            height: 100%;
            display: flex;
            flex-direction: column;
            max-height: none;
            overflow: hidden;
        }

        .discharge-main-card>.card-body>.row {
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

    @media (min-width: 768px) {
        .discharge-left-col {
            flex: 0 0 25%;
            max-width: 25%;
        }

        .discharge-right-col {
            flex: 0 0 75%;
            max-width: 75%;
        }
    }

    .discharge-form-area form>.card:first-child {
        margin-top: 0;
    }

    .discharge-form-area table th,
    .discharge-form-area table td {
        vertical-align: middle;
    }

    .discharge-page .table {
        border-color: #e6edf5;
        margin-bottom: 0.5rem;
    }

    .discharge-page .table> :not(caption)>*>* {
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

        .discharge-main-card>.card-body {
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

    <div class="mb-3 text-end d-flex justify-content-end gap-2 align-items-center">
        <label for="discharge_template_select" class="small mb-0">Print Template</label>
        <select class="form-select form-select-sm" id="discharge_template_select" style="width:220px;">
            <?php foreach ($dischargeTemplates as $template): ?>
                <option value="<?= (int) ($template['id'] ?? 0) ?>" <?= (int) ($template['id'] ?? 0) === $selectedDischargeTemplateId ? 'selected' : '' ?>><?= esc((string) ($template['template_name'] ?? '')) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="button" class="btn btn-outline-primary" id="btn_print_top" onclick="openSelectedDischargePrint();">
            <i class="fas fa-print me-1"></i> Print Discharge Summary
        </button>
        <button type="button" class="btn btn-primary" id="btn_preview_top" onclick="openDischargePreview('<?= site_url('Ipd_discharge/preview_discharge_report/' . $ipdId . '?regen=1') ?>', 'Discharge Preview');">
            <i class="fas fa-eye me-1"></i> Preview Discharge Summary
        </button>
    </div>

    <script>
        function openSelectedDischargePrint() {
            var selector = document.getElementById('discharge_template_select');
            var templateId = selector ? selector.value : '';
            var url = '<?= site_url('Ipd_discharge/show_discharge/' . $ipdId . '/1') ?>';
            if (templateId) {
                url += '?tpl=' + encodeURIComponent(templateId);
            }
            window.open(url, '_blank');
        }
    </script>

    <div class="card discharge-main-card">
        <div class="card-body">
            <?php if ($noticeText !== ''): ?>
                <div class="alert alert-<?= esc($noticeType) ?> py-2" role="alert"><?= esc($noticeText) ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-3 discharge-left-col">
                    <div class="discharge-side-panel">
                        <ul class="nav flex-column nav-pills" id="discharge_section_nav" role="tablist" aria-orientation="vertical">
                            <li class="nav-item"><a href="#section-complaints" class="nav-link discharge-nav-link active" data-target="section-complaints">Complaints with Duration and Reason for Admission</a></li>
                            <li class="nav-item"><a href="#section-history-risk" class="nav-link discharge-nav-link" data-target="section-history-risk">Clinical History and Risk Profile</a></li>
                            <li class="nav-item"><a href="#section-physical" class="nav-link discharge-nav-link" data-target="section-physical">Physical Examinations</a></li>
                            <li class="nav-item"><a href="#section-investigation" class="nav-link discharge-nav-link" data-target="section-investigation">Clinical Investigation Reports</a></li>
                            <li class="nav-item"><a href="#section-admission" class="nav-link discharge-nav-link" data-target="section-admission">Admission / Discharge Information</a></li>
                            <li class="nav-item"><a href="#section-surgery" class="nav-link discharge-nav-link" data-target="section-surgery">Surgery / Procedure / delivery if any</a></li>
                            <li class="nav-item"><a href="#section-diagnosis" class="nav-link discharge-nav-link" data-target="section-diagnosis">Final Diagnosis</a></li>
                            <li class="nav-item"><a href="#section-summary-invest" class="nav-link discharge-nav-link" data-target="section-summary-invest">Summary of key investigation during Hospitalization</a></li>
                            <li class="nav-item"><a href="#section-course" class="nav-link discharge-nav-link" data-target="section-course">Course / Treatment in the hospital</a></li>
                            <li class="nav-item"><a href="#section-condition" class="nav-link discharge-nav-link" data-target="section-condition">Condition at the time of Discharge</a></li>
                            <li class="nav-item"><a href="#section-medicine" class="nav-link discharge-nav-link" data-target="section-medicine">Discharge Medicine Prescribed</a></li>
                            <li class="nav-item"><a href="#section-instructions" class="nav-link discharge-nav-link" data-target="section-instructions">Discharge Summary</a></li>
                        </ul>

                    </div>
                </div>

                <div class="col-md-9 discharge-form-area discharge-right-col">
                    <form id="discharge_main_form" method="post" action="<?= site_url('Ipd_discharge/ipd_select/' . $ipdId) ?>" class="row g-3">
                        <?= csrf_field() ?>

                        <!-- Start Panel: Complaints with Duration and Reason for Admission (First Panel) -->
                        <div class="card border-primary" id="section-complaints">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center bg-primary text-white">
                                <strong>Complaints with Duration and Reason for Admission</strong>
                                <span class="badge bg-light text-primary">First Panel</span>
                            </div>
                            <div class="card-body">
                                <input type="hidden" name="complaint_remove_id" id="complaint_remove_id" value="0">

                                <!-- Healthplix-style inline complaint table (OPD-like Autotext) -->
                                <table class="table table-sm table-bordered align-middle mb-1" id="discharge_complaint_table">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:28px">#</th>
                                            <th>Complaint (Autotext Search)</th>
                                            <th style="width:130px">Frequency</th>
                                            <th style="width:120px">Severity</th>
                                            <th style="width:140px">Duration</th>
                                            <th style="width:28px"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="discharge_complaint_tbody">
                                        <!-- Rows will be dynamically populated by JavaScript -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td></td>
                                            <td colspan="4" class="p-1 position-relative">
                                                <input type="text" class="form-control form-control-sm" id="discharge_complaint_lookup"
                                                    autocomplete="off" placeholder="Type complaint to search autotext (e.g. Fever, Cough, Chest pain)...">
                                                <div id="discharge_complaint_dropdown" class="border rounded bg-white shadow-sm"
                                                    style="display:none;position:absolute;left:0;right:0;top:100%;z-index:1060;max-height:260px;overflow-y:auto;"></div>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-primary p-0" id="btn_discharge_add_complaint" title="Add Complaint" style="width:24px;height:24px;line-height:1">+</button>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>

                                <!-- Recent / Common Complaint Autotext Chips -->
                                <div class="my-2">
                                    <span class="text-muted small fw-bold me-2"><i class="fas fa-history me-1"></i> Quick Autotext Chips:</span>
                                    <div id="discharge_recent_complaint_chips" class="d-inline-flex flex-wrap gap-1 align-items-center">
                                        <!-- Autotext chips loaded via JS -->
                                    </div>
                                </div>

                                <!-- Hidden fields to store data for form submission -->
                                <?php
                                $complaintSeedRows = [];
                                if (isset($complaint_rows) && is_array($complaint_rows)) {
                                    foreach ($complaint_rows as $row) {
                                        $complaintSeedRows[] = [
                                            'id' => (int) ($row['id'] ?? 0),
                                            'term' => (string) ($row['comp_report'] ?? ''),
                                            'frequency' => '',
                                            'severity' => '',
                                            'duration' => (string) ($row['comp_remark'] ?? ''),
                                            'date' => '',
                                        ];
                                    }
                                }
                                ?>
                                <input type="hidden" id="discharge_complaint_seed_json" value="<?= esc((string) json_encode($complaintSeedRows), 'attr') ?>">
                                <input type="hidden" name="discharge_complaints_json" id="discharge_complaints_json" value="">

                                <!-- Fixed dropdowns for table cell inputs -->
                                <div id="discharge_freq_dd" style="display:none;position:fixed;z-index:1090;min-width:160px;background:#fff;border:1px solid #dee2e6;border-radius:.375rem;box-shadow:0 4px 12px rgba(0,0,0,.12);max-height:180px;overflow-y:auto;"></div>
                                <div id="discharge_sev_dd" style="display:none;position:fixed;z-index:1090;min-width:140px;background:#fff;border:1px solid #dee2e6;border-radius:.375rem;box-shadow:0 4px 12px rgba(0,0,0,.12);max-height:180px;overflow-y:auto;"></div>
                                <div id="discharge_dur_dd" style="display:none;position:fixed;z-index:1090;min-width:160px;background:#fff;border:1px solid #dee2e6;border-radius:.375rem;box-shadow:0 4px 12px rgba(0,0,0,.12);max-height:180px;overflow-y:auto;"></div>

                                <div id="discharge_complaint_status" class="small text-muted mb-2"></div>

                                <div class="mt-3">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label class="form-label fw-bold mb-0">Reason for Admission / Detailed Complaints History</label>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_complaint_mic" title="Voice Dictation (Med Mic)"><i class="fas fa-microphone text-danger me-1"></i> Med Mic</button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_complaint_past_data" title="Copy Nursing H&P Note">Past Data</button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_complaint_clear" title="Clear Textarea">Clear</button>
                                        </div>
                                    </div>
                                    <textarea id="complaint_remark_editor" class="form-control form-control-sm" name="complaint_remark" rows="5" placeholder="Reason for admission and detailed history of present illness..."><?= esc((string) ($complaint_remark ?? '')) ?></textarea>
                                </div>

                                <div class="mt-3 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-outline-success btn-sm" name="action" value="save_main" data-reload-section="section-complaints">Save Complaints Section</button>
                                </div>
                            </div>
                        </div>

                        <div class="card border-primary mt-3" id="section-history-risk">
                            <div class="card-header py-2"><strong>Clinical History and Risk Profile</strong></div>
                            <div class="card-body">
                                <h6 class="mb-2">Lifestyle and Personal History</h6>
                                <div class="row g-2 mb-3">
                                    <?php foreach ($historyFields as $field => $label): ?>
                                        <div class="col-md-4">
                                            <div class="form-check mb-1">
                                                <input class="form-check-input" type="checkbox" id="<?= esc($field) ?>" name="<?= esc($field) ?>" value="1" <?= (int) ($patientHistory[$field] ?? 0) === 1 ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="<?= esc($field) ?>"><?= esc($label) ?></label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <hr>

                                <h6 class="mb-2">Drug Allergy and ADR History</h6>
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

                                <hr>

                                <h6 class="mb-2">Comorbidities</h6>
                                <div class="d-flex flex-wrap gap-2 small">
                                    <?php foreach ($coMorbidityOptions as $mKey => $mOpt): ?>
                                        <label class="me-2">
                                            <input type="checkbox" class="co-morbidity-item" value="<?= esc((string) ($mOpt['label'] ?? '')) ?>" <?= ! empty($coMorbiditySelected[$mKey]) ? 'checked' : '' ?>>
                                            <?= esc((string) ($mOpt['label'] ?? '')) ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mt-2">
                                    <label class="form-label small mb-1">Other Comorbidities</label>
                                    <input type="text" class="form-control form-control-sm" id="co_morbidities_other" value="<?= esc($coMorbidityOtherText) ?>" placeholder="Add other comorbidities if any">
                                </div>
                                <input type="hidden" name="co_morbidities" id="co_morbidities" value="<?= esc($coMorbiditiesText) ?>">

                                <div class="mt-3 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-outline-success btn-sm" name="action" value="save_main" data-reload-section="section-history-risk">Save Clinical History Panel</button>
                                </div>
                            </div>
                        </div>

                        <div class="card border-primary mt-3" id="section-pain-measurement">
                            <div class="card-header py-2"><strong>Pain Measurement</strong></div>
                            <div class="card-body">
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

                        <div class="card border-primary mt-3" id="section-physical">
                            <div class="card-header py-2"><strong>Examination on Admission</strong></div>
                            <div class="card-body">
                                <h6 class="mb-2">General Examination</h6>
                                <div class="row g-2">
                                    <?php foreach ($generalExamGroup1 as $row): ?>
                                        <div class="col-md-3">
                                            <label class="form-label small"><?= esc((string) ($row['label'] ?? '')) ?></label>
                                            <?php if ((int) ($row['type'] ?? 0) === 1): ?><textarea class="form-control form-control-sm" name="gen_exam_<?= (int) ($row['id'] ?? 0) ?>" rows="2"><?= esc((string) ($row['value'] ?? '')) ?></textarea><?php elseif ((int) ($row['type'] ?? 0) === 4): ?><select class="form-select form-select-sm" name="gen_exam_<?= (int) ($row['id'] ?? 0) ?>"><?php foreach (preg_split('/[|\r\n]+/', (string) ($row['options'] ?? '')) as $option): $option = trim($option); if ($option === '') continue; ?><option value="<?= esc($option) ?>" <?= (string) ($row['value'] ?? '') === $option ? 'selected' : '' ?>><?= esc($option) ?></option><?php endforeach; ?></select><?php else: ?><input type="<?= (int) ($row['type'] ?? 0) === 2 ? 'number' : 'text' ?>" class="form-control form-control-sm" name="gen_exam_<?= (int) ($row['id'] ?? 0) ?>" value="<?= esc((string) ($row['value'] ?? '')) ?>"><?php endif; ?>
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
                                            <?php if ((int) ($row['type'] ?? 0) === 1): ?><textarea class="form-control form-control-sm" name="gen_exam_<?= (int) ($row['id'] ?? 0) ?>" rows="2"><?= esc((string) ($row['value'] ?? '')) ?></textarea><?php elseif ((int) ($row['type'] ?? 0) === 4): ?><select class="form-select form-select-sm" name="gen_exam_<?= (int) ($row['id'] ?? 0) ?>"><?php foreach (preg_split('/[|\r\n]+/', (string) ($row['options'] ?? '')) as $option): $option = trim($option); if ($option === '') continue; ?><option value="<?= esc($option) ?>" <?= (string) ($row['value'] ?? '') === $option ? 'selected' : '' ?>><?= esc($option) ?></option><?php endforeach; ?></select><?php else: ?><input type="<?= (int) ($row['type'] ?? 0) === 2 ? 'number' : 'text' ?>" class="form-control form-control-sm" name="gen_exam_<?= (int) ($row['id'] ?? 0) ?>" value="<?= esc((string) ($row['value'] ?? '')) ?>"><?php endif; ?>
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
                                <input type="hidden" name="clinical_lab_selection_mode" value="checkbox">
                                <input type="hidden" name="clinical_nonpath_selection_mode" value="checkbox">

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
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Date</th>
                                            <th>Remark</th>
                                            <th style="width:90px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="discharge_surgery_tbody">
                                        <?php if (empty($surgeryRows)): ?>
                                            <tr>
                                                <td colspan="4" class="text-muted text-center">No surgery rows.</td>
                                            </tr>
                                            <?php else: foreach ($surgeryRows as $row): ?>
                                                <tr>
                                                    <td><?= esc((string) ($row['surgery_name'] ?? '')) ?></td>
                                                    <td><?= esc((string) ($row['surgery_date'] ?? '')) ?></td>
                                                    <td><?= esc((string) ($row['surgery_remark'] ?? '')) ?></td>
                                                    <td><button type="button" class="btn btn-outline-danger btn-sm btn-remove-surgery-row" data-id="<?= (int) ($row['id'] ?? 0) ?>">Remove</button></td>
                                                </tr>
                                        <?php endforeach;
                                        endif; ?>
                                    </tbody>
                                </table>
                                <input type="hidden" name="surgery_remove_id" id="surgery_remove_id" value="0">
                                <input type="hidden" name="new_surgery_master_id" id="new_surgery_master_id" value="0">
                                <div class="row g-2 mb-3">
                                    <div class="col-md-5 position-relative">
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="new_surgery_name" id="new_surgery_name" autocomplete="off" placeholder="Surgery name (type to search master)">
                                            <button type="button" class="btn btn-outline-success btn-sm" id="btn_quick_add_surgery" title="Save new term in master">
                                                <i class="bi bi-plus-circle"></i> Save in Master
                                            </button>
                                        </div>
                                        <div id="discharge_surgery_dropdown" class="dropdown-menu" style="display:none;position:absolute;z-index:1050;max-height:250px;overflow-y:auto;width:100%;"></div>
                                    </div>
                                    <div class="col-md-3"><input type="date" class="form-control" name="new_surgery_date" id="new_surgery_date"></div>
                                    <div class="col-md-2"><input type="text" class="form-control" name="new_surgery_remark" id="new_surgery_remark" placeholder="Remark"></div>
                                    <div class="col-md-2"><button type="button" class="btn btn-primary btn-sm w-100" id="btn_add_surgery_row">+ADD Row</button></div>
                                </div>

                                <h6>Procedure</h6>
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Date</th>
                                            <th>Remark</th>
                                            <th style="width:90px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="discharge_procedure_tbody">
                                        <?php if (empty($procedureRows)): ?>
                                            <tr>
                                                <td colspan="4" class="text-muted text-center">No procedure rows.</td>
                                            </tr>
                                            <?php else: foreach ($procedureRows as $row): ?>
                                                <tr>
                                                    <td><?= esc((string) ($row['procedure_name'] ?? '')) ?></td>
                                                    <td><?= esc((string) ($row['procedure_date'] ?? '')) ?></td>
                                                    <td><?= esc((string) ($row['procedure_remark'] ?? '')) ?></td>
                                                    <td><button type="submit" class="btn btn-outline-danger btn-sm" name="action" value="remove_procedure" onclick="document.getElementById('procedure_remove_id').value='<?= (int) ($row['id'] ?? 0) ?>';">Remove</button></td>
                                                </tr>
                                        <?php endforeach;
                                        endif; ?>
                                    </tbody>
                                </table>
                                <input type="hidden" name="procedure_remove_id" id="procedure_remove_id" value="0">
                                <input type="hidden" name="new_procedure_master_id" id="new_procedure_master_id" value="0">
                                <div class="row g-2">
                                    <div class="col-md-5 position-relative">
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="new_procedure_name" id="new_procedure_name" autocomplete="off" placeholder="Procedure name (type to search master)">
                                            <button type="button" class="btn btn-outline-success btn-sm" id="btn_quick_add_procedure" title="Save new term in master">
                                                <i class="bi bi-plus-circle"></i> Save in Master
                                            </button>
                                        </div>
                                        <div id="discharge_procedure_dropdown" class="dropdown-menu" style="display:none;position:absolute;z-index:1050;max-height:250px;overflow-y:auto;width:100%;"></div>
                                    </div>
                                    <div class="col-md-3"><input type="date" class="form-control" name="new_procedure_date"></div>
                                    <div class="col-md-2"><input type="text" class="form-control" name="new_procedure_remark" placeholder="Remark"></div>
                                    <div class="col-md-2"><button type="submit" class="btn btn-primary btn-sm w-100" name="action" value="add_procedure">+ADD Row</button></div>
                                </div>
                                <datalist id="discharge_procedure_suggest"></datalist>
                                <div id="discharge_surgery_status" class="complaint-status text-muted"></div>
                            </div>
                        </div>

                        <div class="card border-secondary mt-3" id="section-diagnosis">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <strong>Final Diagnosis</strong>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_discharge_manage_diagnosis_master">Master CRUD</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_discharge_seed_icd">Load ICD Starter</button>
                                </div>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Diagnosis</th>
                                            <th>Remark</th>
                                            <th style="width:90px;">Action</th>
                                        </tr>
                                    </thead>
                                     <tbody id="discharge_final_diagnosis_tbody">
                                         <?php if (empty($diagnosisRows)): ?>
                                             <tr>
                                                 <td colspan="3" class="text-muted text-center">No diagnosis rows.</td>
                                             </tr>
                                             <?php else: foreach ($diagnosisRows as $row): ?>
                                                 <tr>
                                                     <td><?= esc((string) ($row['comp_report'] ?? '')) ?></td>
                                                     <td><?= esc((string) ($row['comp_remark'] ?? '')) ?></td>
                                                     <td><button type="button" class="btn btn-outline-danger btn-sm btn-remove-final-diagnosis-row" data-id="<?= (int) ($row['id'] ?? 0) ?>">Remove</button></td>
                                                 </tr>
                                         <?php endforeach;
                                         endif; ?>
                                     </tbody>
                                 </table>
                                 <input type="hidden" name="diagnosis_remove_id" id="diagnosis_remove_id" value="0">
                                 <input type="hidden" name="new_diagnosis_master_code" id="new_diagnosis_master_code" value="0">
                                 <input type="hidden" name="new_diagnosis_snomed_concept_id" id="new_diagnosis_snomed_concept_id" value="">
                                 <input type="hidden" name="new_diagnosis_snomed_term" id="new_diagnosis_snomed_term" value="">
                                 <div class="row g-2">
                                     <div class="col-md-6 position-relative">
                                         <input type="text" class="form-control" name="new_diagnosis_name" id="new_diagnosis_name" autocomplete="off" placeholder="Diagnosis">
                                         <div id="discharge_diagnosis_dropdown" class="dropdown-menu shadow-sm w-100" style="display:none; position:absolute; top:100%; left:0; z-index:1060; max-height:220px; overflow-y:auto;"></div>
                                     </div>
                                     <div class="col-md-5"><input type="text" class="form-control" name="new_diagnosis_remark" id="new_diagnosis_remark" placeholder="Remark"></div>
                                     <div class="col-md-1"><button type="button" class="btn btn-primary btn-sm w-100" id="btn_add_final_diagnosis_row">+ADD</button></div>
                                 </div>
                                 <small class="text-muted">Type a diagnosis name, SNOMED term, or ICD code to search master data.</small>
                                 <div id="discharge_diagnosis_status" class="complaint-status text-muted"></div>

                                <div class="mt-3">
                                    <label class="form-label">Final Diagnosis (Narrative)
                                        <button type="button" class="btn btn-outline-secondary btn-sm btn-discharge-field-past" data-section="diagnosis_remark" data-target="diagnosis_remark">Past Data</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm btn-discharge-template-load" data-section="diagnosis_remark" data-target="diagnosis_remark">Load Template</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm btn-discharge-template-save" data-section="diagnosis_remark" data-target="diagnosis_remark">Save as Template</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm btn-discharge-template-master" data-section="diagnosis_remark" data-target="diagnosis_remark" data-title="Final Diagnosis Narrative">Template Master</button>
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
                                <div class="mb-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-discharge-template-load" data-section="inhos_remark" data-target="inhos_remark">Load Template</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-discharge-template-save" data-section="inhos_remark" data-target="inhos_remark">Save as Template</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-discharge-template-master" data-section="inhos_remark" data-target="inhos_remark" data-title="Summary of Key Investigation during Hospitalization">Template Master</button>
                                </div>
                                <textarea class="form-control" name="inhos_remark" id="inhos_remark" rows="4"><?= esc($inhosRemark) ?></textarea>
                                <div class="mt-3 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-outline-success btn-sm" name="action" value="save_main" data-reload-section="section-summary-invest">Save Summary Investigation</button>
                                </div>
                            </div>
                        </div>

                        <div class="card border-secondary mt-3" id="section-course">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <strong>Course / Treatment in the hospital</strong>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_discharge_manage_course_master">Master CRUD</button>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Course</th>
                                            <th>Remark</th>
                                            <th style="width:90px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="discharge_course_tbody">
                                        <?php if (empty($courseRows)): ?>
                                            <tr>
                                                <td colspan="3" class="text-muted text-center">No course rows.</td>
                                            </tr>
                                            <?php else: foreach ($courseRows as $row): ?>
                                                <tr>
                                                    <td><?= esc((string) ($row['comp_report'] ?? '')) ?></td>
                                                    <td><?= esc((string) ($row['comp_remark'] ?? '')) ?></td>
                                                    <td><button type="button" class="btn btn-outline-danger btn-sm btn-remove-course-row" data-id="<?= (int) ($row['id'] ?? 0) ?>">Remove</button></td>
                                                </tr>
                                        <?php endforeach;
                                        endif; ?>
                                    </tbody>
                                </table>
                                <input type="hidden" name="course_remove_id" id="course_remove_id" value="0">
                                <input type="hidden" name="new_course_master_id" id="new_course_master_id" value="0">
                                <div class="row g-2">
                                    <div class="col-md-6 position-relative">
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="new_course_name" id="new_course_name" autocomplete="off" placeholder="Course / treatment (type to search master)">
                                            <button type="button" class="btn btn-outline-success btn-sm" id="btn_quick_add_course" title="Save new term in master">
                                                <i class="bi bi-plus-circle"></i> Save in Master
                                            </button>
                                        </div>
                                        <div id="discharge_course_dropdown" class="dropdown-menu" style="display:none;position:absolute;z-index:1050;max-height:250px;overflow-y:auto;width:100%;"></div>
                                    </div>
                                    <div class="col-md-4"><input type="text" class="form-control" name="new_course_remark" id="new_course_remark" placeholder="Remark"></div>
                                    <div class="col-md-2"><button type="button" class="btn btn-primary btn-sm w-100" id="btn_add_course_row">+ADD Row</button></div>
                                </div>
                                <datalist id="discharge_course_suggest"></datalist>
                                <div id="discharge_course_status" class="complaint-status text-muted"></div>

                                <div class="mt-3">
                                    <label class="form-label">Course / Treatment in Hospital (Narrative)
                                        <button type="button" class="btn btn-outline-secondary btn-sm btn-discharge-field-past" data-section="course_remark" data-target="course_remark">Past Data</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm btn-discharge-template-load" data-section="course_remark" data-target="course_remark">Load Template</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm btn-discharge-template-save" data-section="course_remark" data-target="course_remark">Save as Template</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm btn-discharge-template-master" data-section="course_remark" data-target="course_remark" data-title="Course / Treatment in Hospital Narrative">Template Master</button>
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
                                <?php endforeach;
                                endif; ?>
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

                                <input type="hidden" id="discharge_med_item_id" value="0">
                                <input type="hidden" id="discharge_med_item_source" value="legacy">
                                <input type="hidden" id="discharge_med_id" value="0">
                                <input type="hidden" id="discharge_med_salt" value="">
                                <input type="hidden" id="discharge_qty" value="">

                                <!-- Top Header Bar for Rx-Group -->
                                <div class="row g-2 mb-2">
                                    <div class="col-md-8 d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_open_rx_group_modal">Rx Group</button>
                                        <button type="button" class="btn btn-outline-success btn-sm" id="btn_discharge_rx_group" title="Select from Rx-Group">+ Rx-Group</button>
                                        <span class="small text-muted" id="rx_group_selected_name">No Rx-Group selected</span>
                                    </div>
                                    <div class="col-md-4 text-md-end small text-muted">
                                        Select group and preview medicines before add.
                                    </div>
                                </div>

                                <!-- Form Card matching OPD Consult -->
                                <div class="card border mb-3 shadow-sm">
                                    <div class="card-header bg-primary bg-gradient text-white py-2 d-flex justify-content-between align-items-center">
                                        <span class="fw-bold"><i class="bi bi-capsule me-2"></i>Prescribed Medicines</span>
                                    </div>
                                    <div class="card-body p-3 bg-light-subtle">
                                        <!-- Row 1: Medicine Name & Form / Type & Dose / Strength -->
                                        <div class="row g-3 align-items-end mb-2">
                                            <div class="col-md-7 position-relative">
                                                <label class="form-label fw-semibold mb-1 text-dark">Medicine Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-control-lg shadow-sm" id="discharge_med_name" autocomplete="off" placeholder="Type medicine name (e.g. Paracetamol 500mg, Amoxicillin)...">
                                                <div id="discharge_med_name_dd" style="display:none;position:absolute;left:0;right:0;top:100%;z-index:1080;background:#fff;border:1px solid #dee2e6;border-radius:.375rem;box-shadow:0 6px 16px rgba(0,0,0,.15);max-height:280px;overflow-y:auto;"></div>
                                                <!-- Substitute Box -->
                                                <div class="col-12" id="discharge_substitute_box" style="display:none;margin-top:0.5rem;">
                                                    <div class="small text-muted" id="discharge_substitute_note"></div>
                                                    <div class="small text-muted" id="discharge_substitute_empty" style="display:none;">No substitute found.</div>
                                                    <div id="discharge_substitute_rows" style="max-height:200px;overflow-y:auto;"></div>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label fw-semibold mb-1 text-dark">Form / Type</label>
                                                <select class="form-select shadow-sm" id="discharge_med_type">
                                                    <option value="">Select Type</option>
                                                    <option value="TAB">TAB (Tablet)</option>
                                                    <option value="CAP">CAP (Capsule)</option>
                                                    <option value="SYP">SYP (Syrup)</option>
                                                    <option value="INJ">INJ (Injection)</option>
                                                    <option value="CREAM">CREAM (Cream)</option>
                                                    <option value="OINT">OINT (Ointment)</option>
                                                    <option value="GEL">GEL (Gel)</option>
                                                    <option value="EYE DROP">EYE DROP</option>
                                                    <option value="EAR DROP">EAR DROP</option>
                                                    <option value="DROPS">DROPS</option>
                                                    <option value="RESPULES">RESPULES</option>
                                                    <option value="SACHET">SACHET</option>
                                                    <option value="LOTION">LOTION</option>
                                                    <option value="SPRAY">SPRAY</option>
                                                    <option value="PATCH">PATCH</option>
                                                    <option value="POWDER">POWDER</option>
                                                    <option value="SUPPOSITORY">SUPPOSITORY</option>
                                                    <option value="INFUSION">INFUSION</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label fw-semibold mb-1 text-dark">Dose / Strength</label>
                                                <input type="text" class="form-control shadow-sm" id="discharge_dosage" autocomplete="off" placeholder="e.g. 1 Tab / 5ml / 500mg">
                                            </div>
                                        </div>

                                        <!-- Row 2: Frequency & Relation to Food (When) & Route & Duration -->
                                        <div class="row g-3 align-items-end mb-2">
                                            <div class="col-md-3 position-relative">
                                                <label class="form-label fw-semibold mb-1 text-dark">Frequency (Times/Day) <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control shadow-sm" id="discharge_dosage_freq" autocomplete="off" placeholder="OD, BD, TDS, QID, HS, SOS...">
                                                <div id="discharge_dosage_freq_dd" style="display:none;position:absolute;left:0;right:0;top:100%;z-index:1080;background:#fff;border:1px solid #dee2e6;border-radius:.375rem;box-shadow:0 6px 16px rgba(0,0,0,.15);max-height:220px;overflow-y:auto;"></div>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label fw-semibold mb-1 text-dark">Relation to Food (When)</label>
                                                <select class="form-select shadow-sm" id="discharge_dosage_when">
                                                    <option value="">When</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label fw-semibold mb-1 text-dark">Route of Administration <span class="text-danger">*</span></label>
                                                <select class="form-select shadow-sm" id="discharge_dose_where">
                                                    <option value="">Route</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3 position-relative">
                                                <label class="form-label fw-semibold mb-1 text-dark">Duration</label>
                                                <input type="text" class="form-control shadow-sm" id="discharge_no_of_days" autocomplete="off" placeholder="e.g. 5 Days / 1 Month">
                                                <div id="discharge_no_of_days_dd" style="display:none;position:absolute;left:0;right:0;top:100%;z-index:1080;background:#fff;border:1px solid #dee2e6;border-radius:.375rem;box-shadow:0 4px 12px rgba(0,0,0,.12);max-height:200px;overflow-y:auto;"></div>
                                            </div>
                                        </div>

                                        <!-- Row 3: Advice & Action Buttons -->
                                        <div class="row g-3 align-items-end">
                                            <div class="col-md-8 position-relative">
                                                <label class="form-label fw-semibold mb-1 text-dark">Medicine Advice / Remarks</label>
                                                <input type="text" class="form-control shadow-sm" id="discharge_remark" autocomplete="off" placeholder="Special instructions (e.g. Take with warm water, avoid dairy)">
                                                <div id="discharge_remark_dd" style="display:none;position:absolute;left:0;right:0;top:100%;z-index:1080;background:#fff;border:1px solid #dee2e6;border-radius:.375rem;box-shadow:0 4px 12px rgba(0,0,0,.12);max-height:220px;overflow-y:auto;"></div>
                                            </div>
                                            <div class="col-md-4 d-flex gap-2">
                                                <style>
                                                    #btn_discharge_med_add:focus, #btn_discharge_med_add:active {
                                                        outline: 3px solid #ffc107 !important;
                                                        outline-offset: 2px !important;
                                                        box-shadow: 0 0 0 0.35rem rgba(13, 110, 253, 0.45), 0 0 12px rgba(255, 193, 7, 0.75) !important;
                                                        background-color: #0b5ed7 !important;
                                                        border-color: #0a58ca !important;
                                                        filter: brightness(1.1);
                                                    }
                                                </style>
                                                <button type="button" class="btn btn-primary flex-fill fw-bold shadow-sm" id="btn_discharge_med_add">
                                                    +ADD / Update
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary" id="btn_discharge_med_cancel" style="display:none;">Cancel</button>
                                            </div>
                                        </div>

                                        <!-- Categorized Quick Select Chips Bar -->
                                        <div class="d-flex flex-wrap align-items-center gap-1 mt-3 pt-2 border-top">
                                            <span class="badge bg-secondary me-1"><i class="bi bi-lightning-fill me-1"></i>Quick Select:</span>
                                            
                                            <span class="text-muted small fw-semibold ms-1 me-1">Frequency:</span>
                                            <button type="button" class="btn btn-outline-primary btn-sm discharge-quick-btn py-0 px-2" data-fill-target="discharge_dosage_freq" data-fill-value="OD">OD</button>
                                            <button type="button" class="btn btn-outline-primary btn-sm discharge-quick-btn py-0 px-2" data-fill-target="discharge_dosage_freq" data-fill-value="BD">BD</button>
                                            <button type="button" class="btn btn-outline-primary btn-sm discharge-quick-btn py-0 px-2" data-fill-target="discharge_dosage_freq" data-fill-value="TDS">TDS</button>
                                            <button type="button" class="btn btn-outline-primary btn-sm discharge-quick-btn py-0 px-2" data-fill-target="discharge_dosage_freq" data-fill-value="QID">QID</button>
                                            <button type="button" class="btn btn-outline-primary btn-sm discharge-quick-btn py-0 px-2" data-fill-target="discharge_dosage_freq" data-fill-value="HS">HS</button>
                                            <button type="button" class="btn btn-outline-primary btn-sm discharge-quick-btn py-0 px-2" data-fill-target="discharge_dosage_freq" data-fill-value="SOS">SOS</button>

                                            <span class="text-muted small fw-semibold ms-2 me-1">Timing:</span>
                                            <button type="button" class="btn btn-outline-info btn-sm discharge-quick-btn py-0 px-2" data-fill-target="discharge_dosage_when" data-fill-value="AF">After Food (AF)</button>
                                            <button type="button" class="btn btn-outline-info btn-sm discharge-quick-btn py-0 px-2" data-fill-target="discharge_dosage_when" data-fill-value="BF">Before Food (BF)</button>
                                            <button type="button" class="btn btn-outline-info btn-sm discharge-quick-btn py-0 px-2" data-fill-target="discharge_dosage_when" data-fill-value="WF">With Food (WF)</button>
                                            <button type="button" class="btn btn-outline-info btn-sm discharge-quick-btn py-0 px-2" data-fill-target="discharge_dosage_when" data-fill-value="ES">Empty Stomach (ES)</button>

                                            <span class="text-muted small fw-semibold ms-2 me-1">Route:</span>
                                            <button type="button" class="btn btn-outline-success btn-sm discharge-quick-btn py-0 px-2" data-fill-target="discharge_dose_where" data-fill-value="Oral">Oral</button>
                                            <button type="button" class="btn btn-outline-success btn-sm discharge-quick-btn py-0 px-2" data-fill-target="discharge_dose_where" data-fill-value="IV / Inj">IV / Inj</button>
                                            <button type="button" class="btn btn-outline-success btn-sm discharge-quick-btn py-0 px-2" data-fill-target="discharge_dose_where" data-fill-value="Topical">Topical</button>

                                            <span class="text-muted small fw-semibold ms-2 me-1">Duration:</span>
                                            <button type="button" class="btn btn-outline-dark btn-sm discharge-quick-btn py-0 px-2" data-fill-target="discharge_no_of_days" data-fill-value="3 Days">3 Days</button>
                                            <button type="button" class="btn btn-outline-dark btn-sm discharge-quick-btn py-0 px-2" data-fill-target="discharge_no_of_days" data-fill-value="5 Days">5 Days</button>
                                            <button type="button" class="btn btn-outline-dark btn-sm discharge-quick-btn py-0 px-2" data-fill-target="discharge_no_of_days" data-fill-value="7 Days">7 Days</button>
                                            <button type="button" class="btn btn-outline-dark btn-sm discharge-quick-btn py-0 px-2" data-fill-target="discharge_no_of_days" data-fill-value="14 Days">14 Days</button>
                                            <button type="button" class="btn btn-outline-dark btn-sm discharge-quick-btn py-0 px-2" data-fill-target="discharge_no_of_days" data-fill-value="1 Month">1 Month</button>
                                        </div>
                                        <div class="small text-muted mt-2" id="discharge_medicine_status">Ready.</div>
                                    </div>
                                </div>

                                <!-- Medicine List Table -->
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm rx-list-table">
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>Medicine</th>
                                                <th>Dose</th>
                                                <th>When</th>
                                                <th>Freq</th>
                                                <th>Days</th>
                                                <th>Remark</th>
                                                <th style="width:90px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="discharge_medicine_tbody">
                                            <?php if (empty($medicineRows)): ?>
                                                <tr>
                                                    <td colspan="8" class="text-muted text-center">No medicine added</td>
                                                </tr>
                                                <?php else: foreach ($medicineRows as $row): ?>
                                                    <tr data-row-id="<?= (int) ($row['id'] ?? 0) ?>" data-row-source="<?= esc((string) ($row['source'] ?? 'legacy')) ?>">
                                                        <td><?= esc((string) ($row['med_type'] ?? '')) ?></td>
                                                        <td><?= esc((string) ($row['med_name'] ?? '')) ?></td>
                                                        <td><?= esc((string) ($row['dosage'] ?? '')) ?></td>
                                                        <td><?= esc((string) ($row['dosage_when'] ?? '')) ?></td>
                                                        <td><?= esc((string) ($row['dosage_freq'] ?? '')) ?></td>
                                                        <td><?= esc((string) ($row['no_of_days'] ?? '')) ?></td>
                                                        <td><?= esc((string) ($row['remark'] ?? '')) ?></td>
                                                        <td class="d-flex gap-1">
                                                            <button
                                                                type="button"
                                                                class="btn btn-outline-primary btn-sm btn-edit-discharge-med"
                                                                data-id="<?= (int) ($row['id'] ?? 0) ?>"
                                                                data-source="<?= esc((string) ($row['source'] ?? 'legacy')) ?>"
                                                                data-med-name="<?= esc((string) ($row['med_name'] ?? '')) ?>"
                                                                data-med-salt="<?= esc((string) ($row['med_salt'] ?? '')) ?>"
                                                                data-med-type="<?= esc((string) ($row['med_type'] ?? '')) ?>"
                                                                data-dose-id="<?= (int) ($row['dosage_id'] ?? 0) ?>"
                                                                data-dose-when-id="<?= (int) ($row['dosage_when_id'] ?? 0) ?>"
                                                                data-dose-freq-id="<?= (int) ($row['dosage_freq_id'] ?? 0) ?>"
                                                                data-dose-label="<?= esc((string) ($row['dosage'] ?? '')) ?>"
                                                                data-dose-when-label="<?= esc((string) ($row['dosage_when'] ?? '')) ?>"
                                                                data-dose-freq-label="<?= esc((string) ($row['dosage_freq'] ?? '')) ?>"
                                                                data-days="<?= esc((string) ($row['no_of_days'] ?? '')) ?>"
                                                                data-qty="<?= esc((string) ($row['qty'] ?? '')) ?>"
                                                                data-remark="<?= esc((string) ($row['remark'] ?? '')) ?>">Edit</button>
                                                            <button type="button" class="btn btn-outline-danger btn-sm btn-remove-discharge-med" data-id="<?= (int) ($row['id'] ?? 0) ?>" data-source="<?= esc((string) ($row['source'] ?? 'legacy')) ?>">Remove</button>
                                                        </td>
                                                    </tr>
                                            <?php endforeach;
                                            endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-end align-items-center mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-danger" id="btn_discharge_med_reset" title="Remove all added medicines"><i class="bi bi-trash me-1"></i>Remove All</button>
                                </div>
                                <input type="hidden" name="drug_remove_id" id="drug_remove_id" value="0">
                                <input type="hidden" name="drug_remove_source" id="drug_remove_source" value="legacy">
                                <input type="hidden" name="discharge_medicine_json" id="discharge_medicine_json" value="">
                            </div>
                        </div>
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <strong>Discharge Summary</strong>
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
                                    <label class="form-label">Other Advice
                                        <button type="button" class="btn btn-outline-secondary btn-sm btn-discharge-template-load" data-section="instruction_other" data-target="instruction_other">Load Template</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm btn-discharge-template-save" data-section="instruction_other" data-target="instruction_other">Save as Template</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm btn-discharge-template-master" data-section="instruction_other" data-target="instruction_other" data-title="Other Advice">Template Master</button>
                                    </label>
                                    <textarea class="form-control" name="instruction_other" id="instruction_other" rows="2" placeholder="Additional custom advice..."><?= esc($instructionOther) ?></textarea>
                                    <div id="discharge_instruction_other_status" class="complaint-status text-muted"></div>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label">Discharge Summary
                                        <button type="button" class="btn btn-outline-secondary btn-sm btn-discharge-template-load" data-section="instruction_remark" data-target="instruction_remark">Load Template</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm btn-discharge-template-save" data-section="instruction_remark" data-target="instruction_remark">Save as Template</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm btn-discharge-template-master" data-section="instruction_remark" data-target="instruction_remark" data-title="Discharge Summary">Template Master</button>
                                    </label>
                                    <textarea class="form-control" name="instruction_remark" id="instruction_remark" rows="3"><?= esc((string) ($instruction_remark ?? '')) ?></textarea>
                                    <div id="discharge_instruction_remark_status" class="complaint-status text-muted"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Review After (days/text)</label>
                                    <input type="text" class="form-control" name="review_after" id="discharge_review_after" value="<?= esc((string) ($review_after ?? '')) ?>" placeholder="e.g. 5 Days">
                                </div>
                                <div class="col-md-12">
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php $nextVisitOptions = is_array($next_visit_options ?? null) ? ($next_visit_options ?? []) : []; ?>
                                        <?php foreach ($nextVisitOptions as $nextVisitOpt) : ?>
                                            <?php $nextVisitValue = trim((string) ($nextVisitOpt['value'] ?? '')); ?>
                                            <?php if ($nextVisitValue === '') {
                                                continue;
                                            } ?>
                                            <button type="button" class="btn btn-outline-secondary btn-sm discharge-review-chip" data-value="<?= esc($nextVisitValue) ?>"><?= esc($nextVisitValue) ?></button>
                                        <?php endforeach; ?>
                                    </div>
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
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No records.</td>
                                </tr>
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

    <div class="modal fade" id="ipdNarrativeTemplateMasterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ipd_narrative_template_master_title">Narrative Template Master</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive" style="max-height:240px;overflow:auto;">
                        <table class="table table-sm table-bordered align-middle mb-2">
                            <thead>
                                <tr>
                                    <th style="width:110px;">Scope</th>
                                    <th>Name</th>
                                    <th>Template Text</th>
                                    <th style="width:150px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="ipd_narrative_template_master_rows">
                                <tr><td colspan="4" class="text-center text-muted">No templates.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <hr>
                    <input type="hidden" id="ipd_narrative_template_master_id" value="0">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label">Scope</label>
                            <select class="form-select form-select-sm" id="ipd_narrative_template_master_scope">
                                <option value="doctor">My Template</option>
                                <option value="master">Master Template</option>
                            </select>
                        </div>
                        <div class="col-md-9">
                            <label class="form-label">Template Name</label>
                            <input type="text" class="form-control form-control-sm" id="ipd_narrative_template_master_name" maxlength="120">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Template Text</label>
                            <textarea class="form-control form-control-sm" id="ipd_narrative_template_master_text" rows="5"></textarea>
                        </div>
                    </div>
                    <div class="mt-2 d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm" id="btn_ipd_narrative_template_master_save">Save</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_ipd_narrative_template_master_new">New</button>
                    </div>
                    <div id="ipd_narrative_template_master_status" class="complaint-status text-muted mt-2"></div>
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
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No records.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <hr>
                    <input type="hidden" id="surgery_master_id" value="0">
                    <div class="row g-2">
                        <div class="col-md-5 position-relative">
                            <label class="form-label">Name <small class="text-muted">(Type to search SNOMED)</small></label>
                            <input type="text" class="form-control form-control-sm" id="surgery_master_name" maxlength="255" autocomplete="off" placeholder="Search surgery or SNOMED procedure">
                            <div id="surgery_master_name_dropdown" class="discharge-complaint-dd dropdown-menu shadow-sm w-100" style="display:none; position:absolute; top:100%; left:0; z-index:1060; max-height:220px; overflow-y:auto;"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Code / SNOMED ID</label>
                            <input type="text" class="form-control form-control-sm" id="surgery_master_code" maxlength="60" placeholder="Code or SNOMED ID">
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

    <div class="modal fade" id="ipdDiagnosisMasterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Final Diagnosis Master</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-2">
                        <div class="col-md-9">
                            <input type="text" class="form-control form-control-sm" id="diagnosis_master_search" placeholder="Search diagnosis or SNOMED term">
                        </div>
                        <div class="col-md-3 d-grid">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_diagnosis_master_refresh">Refresh List</button>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height:260px;overflow:auto;">
                        <table class="table table-sm table-bordered align-middle">
                            <thead><tr><th>Name</th><th>SNOMED ID</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody id="diagnosis_master_rows"><tr><td colspan="4" class="text-center text-muted">No records.</td></tr></tbody>
                        </table>
                    </div>
                    <hr>
                    <input type="hidden" id="diagnosis_master_code" value="0">
                    <div class="row g-2">
                        <div class="col-md-6 position-relative">
                            <label class="form-label">Name <small class="text-muted">(Type to search SNOMED)</small></label>
                            <input type="text" class="form-control form-control-sm" id="diagnosis_master_name" autocomplete="off" placeholder="Search diagnosis or SNOMED term">
                            <div id="diagnosis_master_name_dropdown" class="discharge-complaint-dd dropdown-menu shadow-sm w-100" style="display:none; position:absolute; top:100%; left:0; z-index:1060; max-height:220px; overflow-y:auto;"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">SNOMED ID</label>
                            <input type="text" class="form-control form-control-sm" id="diagnosis_master_snomed_id">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select form-select-sm" id="diagnosis_master_active"><option value="1">Active</option><option value="0">Inactive</option></select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">SNOMED Term</label>
                            <input type="text" class="form-control form-control-sm" id="diagnosis_master_snomed_term">
                        </div>
                    </div>
                    <div class="mt-2 d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm" id="btn_diagnosis_master_save">Save</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_diagnosis_master_clear">New</button>
                    </div>
                    <div id="diagnosis_master_status" class="complaint-status text-muted mt-2"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="ipdCourseMasterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Course / Treatment Master</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-2">
                        <div class="col-md-9">
                            <input type="text" class="form-control form-control-sm" id="course_master_search" placeholder="Search course/treatment master">
                        </div>
                        <div class="col-md-3 d-grid">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_course_master_refresh">Refresh List</button>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height:260px;overflow:auto;">
                        <table class="table table-sm table-bordered align-middle">
                            <thead><tr><th>Name</th><th>Code / SNOMED ID</th><th>ICD</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody id="course_master_rows"><tr><td colspan="5" class="text-center text-muted">No records.</td></tr></tbody>
                        </table>
                    </div>
                    <hr>
                    <input type="hidden" id="course_master_id" value="0">
                    <div class="row g-2">
                        <div class="col-md-5 position-relative">
                            <label class="form-label">Name <small class="text-muted">(Type to search SNOMED)</small></label>
                            <input type="text" class="form-control form-control-sm" id="course_master_name" maxlength="255" autocomplete="off" placeholder="Search course or SNOMED procedure">
                            <div id="course_master_name_dropdown" class="discharge-complaint-dd dropdown-menu shadow-sm w-100" style="display:none; position:absolute; top:100%; left:0; z-index:1060; max-height:220px; overflow-y:auto;"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Code / SNOMED ID</label>
                            <input type="text" class="form-control form-control-sm" id="course_master_code" maxlength="60" placeholder="Code or SNOMED ID">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">ICD</label>
                            <input type="text" class="form-control form-control-sm" id="course_master_icd" maxlength="60">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select class="form-select form-select-sm" id="course_master_active">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-2 d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm" id="btn_course_master_save">Save</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_course_master_clear">New</button>
                    </div>
                    <div id="course_master_status" class="complaint-status text-muted mt-2"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Add Surgery/Procedure Modal -->
    <div class="modal fade" id="quickAddTermModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Save New <span id="quick_term_type_label">Surgery</span> in Master</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="quick_term_type" value="surgery">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="quick_term_name" placeholder="Enter surgery/procedure name" readonly>
                        <div class="form-text">This will be saved to the master table</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SNOMED CT Code</label>
                        <input type="text" class="form-control" id="quick_term_code" placeholder="e.g., 80146002">
                        <div class="form-text">Optional - SNOMED CT terminology code</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ICD Code</label>
                        <input type="text" class="form-control" id="quick_term_icd" placeholder="e.g., K35.80">
                        <div class="form-text">Optional - ICD-10 code</div>
                    </div>
                    <div id="quick_term_status" class="text-muted"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="btn_quick_term_save">
                        <i class="bi bi-check-circle"></i> Save in Master
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Rx-Group Modal -->
    <div class="modal fade" id="dischargeRxGroupModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Rx-Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="discharge_rx_group_search" placeholder="Search Rx-Group by name...">
                    </div>
                    <div id="discharge_rx_group_list" style="max-height:400px;overflow-y:auto;">
                        <div class="text-muted">Loading Rx-Groups...</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            var narrativeTemplateLoadState = {
                section: '',
                target: '',
                rows: []
            };
            var narrativeTemplateSaveState = {
                section: '',
                text: '',
                target: ''
            };
            var narrativeTemplateMasterState = {
                section: '',
                target: '',
                title: '',
                rows: []
            };
            var narrativeTemplateToolsBound = false;
            var surgeryMasterState = {
                surgery: [],
                procedure: []
            };
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

                if (CKEDITOR.instances.instruction_other) {
                    CKEDITOR.instances.instruction_other.destroy(true);
                }

                if (CKEDITOR.instances.instruction_remark) {
                    CKEDITOR.instances.instruction_remark.destroy(true);
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

                if (document.getElementById('instruction_other')) {
                    CKEDITOR.replace('instruction_other', {
                        height: 120,
                        toolbar: [{
                                name: 'basicstyles',
                                items: ['Bold', 'Italic', 'Underline']
                            },
                            {
                                name: 'paragraph',
                                items: ['NumberedList', 'BulletedList']
                            },
                            {
                                name: 'editing',
                                items: ['Undo', 'Redo']
                            }
                        ]
                    });
                }

                if (document.getElementById('instruction_remark')) {
                    CKEDITOR.replace('instruction_remark', {
                        height: 150,
                        toolbar: [{
                                name: 'basicstyles',
                                items: ['Bold', 'Italic', 'Underline']
                            },
                            {
                                name: 'paragraph',
                                items: ['NumberedList', 'BulletedList']
                            },
                            {
                                name: 'editing',
                                items: ['Undo', 'Redo']
                            }
                        ]
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

            // Client-side complaint management (OPD-style Autotext) - MUST BE DECLARED BEFORE initComplaintTools
            var selectedDischargeComplaints = [];
            var _dischargeComplaintSearchTimer = null;
            var _dischargeComplaintSearchCache = {};
            var _dischargeComplaintDdIdx = -1;
            var _dischargeComplaintXhr = null;

            function initDischargeComplaintsTable() {
                // Always reset from server-rendered state to avoid stale rows after section reload.
                selectedDischargeComplaints = [];

                // Load existing complaints from section-scoped seed JSON.
                var seedEl = document.getElementById('discharge_complaint_seed_json');
                if (seedEl) {
                    try {
                        var seedRows = JSON.parse(String(seedEl.value || '[]'));
                        if (Array.isArray(seedRows)) {
                            selectedDischargeComplaints = seedRows.map(function(row) {
                                return {
                                    id: parseInt((row && row.id) || 0, 10) || 0,
                                    term: String((row && row.term) || ''),
                                    frequency: String((row && row.frequency) || ''),
                                    severity: String((row && row.severity) || ''),
                                    duration: String((row && row.duration) || ''),
                                    date: String((row && row.date) || '')
                                };
                            });
                        }
                    } catch (err) {
                        selectedDischargeComplaints = [];
                    }
                }

                renderDischargeComplaintTable();
                renderDischargeRecentChips();
            }

            function renderDischargeComplaintTable() {
                var tbody = document.getElementById('discharge_complaint_tbody');
                if (!tbody) return;

                tbody.innerHTML = '';

                if (selectedDischargeComplaints.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-muted text-center py-2">No complaints added yet. Type in the autotext box below or click quick chips.</td></tr>';
                    syncDischargeComplaintJsonField();
                    return;
                }

                selectedDischargeComplaints.forEach(function(item, idx) {
                    var tr = document.createElement('tr');
                    tr.setAttribute('data-idx', idx);

                    // # column
                    var td1 = document.createElement('td');
                    td1.className = 'text-center text-muted';
                    td1.textContent = idx + 1;
                    tr.appendChild(td1);

                    // Complaint name
                    var td2 = document.createElement('td');
                    td2.className = 'p-1';
                    var nameInput = document.createElement('input');
                    nameInput.type = 'text';
                    nameInput.name = 'complaint_term[]';
                    nameInput.className = 'form-control form-control-sm complaint-name-input';
                    nameInput.value = item.term || '';
                    nameInput.placeholder = 'Complaint…';
                    nameInput.style.fontSize = '.82rem';
                    nameInput.setAttribute('data-idx', idx);
                    nameInput.addEventListener('input', function() {
                        selectedDischargeComplaints[idx].term = this.value;
                        syncDischargeComplaintJsonField();
                    });
                    td2.appendChild(nameInput);
                    tr.appendChild(td2);

                    // Frequency
                    var td3 = document.createElement('td');
                    td3.className = 'p-1';
                    var freqInput = document.createElement('input');
                    freqInput.type = 'text';
                    freqInput.name = 'complaint_frequency[]';
                    freqInput.className = 'form-control form-control-sm complaint-freq-input';
                    freqInput.value = item.frequency || '';
                    freqInput.placeholder = 'daily…';
                    freqInput.style.fontSize = '.82rem';
                    freqInput.setAttribute('data-idx', idx);
                    freqInput.addEventListener('input', function() {
                        selectedDischargeComplaints[idx].frequency = this.value;
                        syncDischargeComplaintJsonField();
                    });
                    td3.appendChild(freqInput);
                    tr.appendChild(td3);

                    // Severity
                    var td4 = document.createElement('td');
                    td4.className = 'p-1';
                    var sevInput = document.createElement('input');
                    sevInput.type = 'text';
                    sevInput.name = 'complaint_severity[]';
                    sevInput.className = 'form-control form-control-sm complaint-sev-input';
                    sevInput.value = item.severity || '';
                    sevInput.placeholder = 'mild…';
                    sevInput.style.fontSize = '.82rem';
                    sevInput.setAttribute('data-idx', idx);
                    sevInput.addEventListener('input', function() {
                        selectedDischargeComplaints[idx].severity = this.value;
                        syncDischargeComplaintJsonField();
                    });
                    td4.appendChild(sevInput);
                    tr.appendChild(td4);

                    // Duration
                    var td5 = document.createElement('td');
                    td5.className = 'p-1';
                    var durInput = document.createElement('input');
                    durInput.type = 'text';
                    durInput.name = 'complaint_duration[]';
                    durInput.className = 'form-control form-control-sm complaint-dur-input';
                    durInput.value = item.duration || '';
                    durInput.placeholder = '2 days…';
                    durInput.style.fontSize = '.82rem';
                    durInput.setAttribute('data-idx', idx);
                    durInput.addEventListener('input', function() {
                        selectedDischargeComplaints[idx].duration = this.value;
                        syncDischargeComplaintJsonField();
                    });
                    td5.appendChild(durInput);
                    tr.appendChild(td5);

                    var idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'complaint_row_id[]';
                    idInput.value = parseInt(item.id || 0, 10) || 0;
                    tr.appendChild(idInput);

                    // Remove button
                    var td6 = document.createElement('td');
                    td6.className = 'p-1 text-center';
                    var removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'btn btn-sm text-danger p-0';
                    removeBtn.style.lineHeight = '1';
                    removeBtn.innerHTML = '×';
                    removeBtn.addEventListener('click', function() {
                        selectedDischargeComplaints.splice(idx, 1);
                        renderDischargeComplaintTable();
                        setComplaintStatus('Complaint removed.', 'success');
                    });
                    td6.appendChild(removeBtn);
                    tr.appendChild(td6);

                    tbody.appendChild(tr);
                });

                syncDischargeComplaintJsonField();
            }

            // Quick Autotext Chips
            function renderDischargeRecentChips() {
                var $box = $('#discharge_recent_complaint_chips');
                if (!$box.length) return;
                var commonComplaints = [
                    'Fever', 'Cough', 'Abdominal Pain', 'Chest Pain', 'Breathlessness',
                    'Headache', 'Vomiting', 'Loose Stools', 'Giddiness', 'Weakness'
                ];
                $box.empty();
                commonComplaints.forEach(function(item) {
                    var $chip = $('<button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 rounded-pill me-1 mb-1" style="font-size:.78rem;"></button>')
                        .text('+ ' + item)
                        .on('click', function() {
                            var exists = selectedDischargeComplaints.some(function(c) {
                                return c.term.toUpperCase() === item.toUpperCase();
                            });
                            if (exists) {
                                setComplaintStatus('"' + item + '" already added.', 'muted');
                                return;
                            }
                            selectedDischargeComplaints.push({
                                id: 0,
                                term: item,
                                frequency: '',
                                severity: '',
                                duration: '',
                                date: ''
                            });
                            renderDischargeComplaintTable();
                            setComplaintStatus('Added ' + item, 'success');
                            var $lastTr = $('#discharge_complaint_tbody tr:last');
                            $lastTr.find('.complaint-freq-input').trigger('focus');
                        });
                    $box.append($chip);
                });
            }

            // ─── Inline Autotext Dropdowns (Frequency, Severity, Duration) ─────────
            var _DISCHARGE_FREQ_OPTIONS = ['daily', 'twice daily', 'weekly', 'intermittent', 'continuous', 'occasional'];
            var _DISCHARGE_SEV_OPTIONS = ['mild', 'moderate', 'severe', 'profound'];
            var _DISCHARGE_DUR_UNITS = ['hours', 'days', 'weeks', 'months', 'years'];

            function _positionDischargeDd($dd, $input) {
                if (!$input || !$input.length) return;
                var r = $input[0].getBoundingClientRect();
                $dd.css({ top: (r.bottom + 2) + 'px', left: r.left + 'px', width: Math.max(r.width, 160) + 'px' });
            }

            function getDischargeDurationSuggestions(input) {
                input = (input || '').toString().trim();
                var numMatch = input.match(/^(\d+\.?\d*)\s*(.*)/);
                if (numMatch) {
                    var n = numMatch[1], unitHint = (numMatch[2] || '').trim().toLowerCase();
                    return _DISCHARGE_DUR_UNITS
                        .filter(function(u) { return !unitHint || u.startsWith(unitHint); })
                        .map(function(u) { return n + ' ' + u; });
                }
                if (!input) {
                    return ['1 day', '2 days', '3 days', '1 week', '2 weeks', '1 month', '3 months'];
                }
                return ['1 day','2 days','3 days','5 days','1 week','2 weeks','3 weeks','1 month','2 months','3 months','6 months','1 year']
                    .filter(function(s) { return s.indexOf(input.toLowerCase()) !== -1; });
            }

            function buildDischargeDdItem(text, onSelect) {
                return $('<div class="px-3 py-2 border-bottom" style="cursor:pointer;font-size:.85rem;color:#333;"></div>')
                    .text(text)
                    .on('mouseenter', function() { $(this).css('background','#f0f4ff'); })
                    .on('mouseleave', function() { $(this).css('background',''); })
                    .on('mousedown', function(e) { e.preventDefault(); })
                    .on('click', function() { onSelect(text); });
            }

            $(document).on('input focus', '.complaint-freq-input', function() {
                var $inp = $(this);
                var q = ($inp.val() || '').trim().toLowerCase();
                var sugs = q ? _DISCHARGE_FREQ_OPTIONS.filter(function(s) { return s.indexOf(q) !== -1; }) : _DISCHARGE_FREQ_OPTIONS;
                var idx = parseInt($inp.attr('data-idx'), 10);
                var $dd = $('#discharge_freq_dd').empty();
                if (!sugs.length) { $dd.hide(); return; }
                sugs.forEach(function(s) {
                    $dd.append(buildDischargeDdItem(s, function(val) {
                        $inp.val(val);
                        if (idx >= 0 && idx < selectedDischargeComplaints.length) {
                            selectedDischargeComplaints[idx].frequency = val;
                            syncDischargeComplaintJsonField();
                        }
                        $dd.hide().empty();
                        $inp.closest('tr').find('.complaint-sev-input').trigger('focus');
                    }));
                });
                _positionDischargeDd($dd, $inp);
                $dd.show();
            });

            $(document).on('input focus', '.complaint-sev-input', function() {
                var $inp = $(this);
                var q = ($inp.val() || '').trim().toLowerCase();
                var sugs = q ? _DISCHARGE_SEV_OPTIONS.filter(function(s) { return s.startsWith(q); }) : _DISCHARGE_SEV_OPTIONS;
                var idx = parseInt($inp.attr('data-idx'), 10);
                var $dd = $('#discharge_sev_dd').empty();
                if (!sugs.length) { $dd.hide(); return; }
                sugs.forEach(function(s) {
                    $dd.append(buildDischargeDdItem(s, function(val) {
                        $inp.val(val);
                        if (idx >= 0 && idx < selectedDischargeComplaints.length) {
                            selectedDischargeComplaints[idx].severity = val;
                            syncDischargeComplaintJsonField();
                        }
                        $dd.hide().empty();
                        $inp.closest('tr').find('.complaint-dur-input').trigger('focus');
                    }));
                });
                _positionDischargeDd($dd, $inp);
                $dd.show();
            });

            $(document).on('input focus', '.complaint-dur-input', function() {
                var $inp = $(this);
                var sugs = getDischargeDurationSuggestions($inp.val());
                var idx = parseInt($inp.attr('data-idx'), 10);
                var $dd = $('#discharge_dur_dd').empty();
                if (!sugs.length) { $dd.hide(); return; }
                sugs.forEach(function(s) {
                    $dd.append(buildDischargeDdItem(s, function(val) {
                        $inp.val(val);
                        if (idx >= 0 && idx < selectedDischargeComplaints.length) {
                            selectedDischargeComplaints[idx].duration = val;
                            syncDischargeComplaintJsonField();
                        }
                        $dd.hide().empty();
                        $('#discharge_complaint_lookup').val('').trigger('focus');
                    }));
                });
                _positionDischargeDd($dd, $inp);
                $dd.show();
            });

            $(document).on('blur', '.complaint-freq-input, .complaint-sev-input, .complaint-dur-input', function() {
                setTimeout(function() {
                    $('#discharge_freq_dd, #discharge_sev_dd, #discharge_dur_dd').hide();
                }, 150);
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('#discharge_complaint_lookup, #discharge_complaint_dropdown').length) {
                    closeDischargeComplaintDropdown();
                }
                if (!$(e.target).closest('.complaint-freq-input, #discharge_freq_dd').length) {
                    $('#discharge_freq_dd').hide();
                }
                if (!$(e.target).closest('.complaint-sev-input, #discharge_sev_dd').length) {
                    $('#discharge_sev_dd').hide();
                }
                if (!$(e.target).closest('.complaint-dur-input, #discharge_dur_dd').length) {
                    $('#discharge_dur_dd').hide();
                }
            });

            function syncDischargeComplaintJsonField() {
                var hidden = document.getElementById('discharge_complaints_json');
                if (!hidden) {
                    return;
                }

                var rows = [];
                selectedDischargeComplaints.forEach(function(item) {
                    var term = String((item && item.term) || '').trim();
                    if (term === '') {
                        return;
                    }

                    rows.push({
                        id: parseInt((item && item.id) || 0, 10) || 0,
                        term: term,
                        frequency: String((item && item.frequency) || '').trim(),
                        severity: String((item && item.severity) || '').trim(),
                        duration: String((item && item.duration) || '').trim(),
                        date: String((item && item.date) || '')
                    });
                });

                hidden.value = JSON.stringify(rows);
            }

            function buildDischargeComplaintDropdownItem(row, lookup, btnAdd) {
                var term = ((row.name || row.term) || '').toString();
                var source = (row.source || '').toString();
                var hierarchy = (row.hierarchy || '').toString();
                var isSnomed = source === 'snomed';
                var nameColor = isSnomed ? '#0d6efd' : '#495057';

                var $item = $('<div class="px-3 py-2 border-bottom discharge-complaint-dd-item" style="cursor:pointer;font-size:.88rem;transition:background .1s"></div>');
                $item.append($('<div class="fw-semibold text-truncate" style="color:' + nameColor + '">').text(term));
                if (isSnomed && hierarchy) {
                    $item.append($('<span class="text-muted" style="font-size:.72rem">').text(hierarchy));
                }
                $item.data('row', row);

                $item.on('mouseenter', function() {
                    $(this).css('background', '#f0f4ff');
                }).on('mouseleave', function() {
                    $(this).css('background', '');
                }).on('mousedown', function(e) {
                    e.preventDefault();
                }).on('click', function() {
                    var rowData = $(this).data('row');
                    var termVal = rowData ? (rowData.name || rowData.term || '') : '';
                    if (termVal && lookup) {
                        lookup.value = termVal;
                        if (btnAdd) btnAdd.click();
                    }
                    closeDischargeComplaintDropdown();
                });

                return $item;
            }

            function openDischargeComplaintDropdown(rows, lookup, btnAdd) {
                var $dd = $('#discharge_complaint_dropdown');
                $dd.empty();
                _dischargeComplaintDdIdx = -1;
                if (!rows || !rows.length) {
                    $dd.append('<div class="px-3 py-2 text-muted small">No matching complaints found</div>').show();
                    return;
                }
                rows.forEach(function(row) {
                    $dd.append(buildDischargeComplaintDropdownItem(row, lookup, btnAdd));
                });
                $dd.show();
            }

            function closeDischargeComplaintDropdown() {
                if (_dischargeComplaintSearchTimer) { clearTimeout(_dischargeComplaintSearchTimer); _dischargeComplaintSearchTimer = null; }
                if (_dischargeComplaintXhr) { try { _dischargeComplaintXhr.abort(); } catch(e){} _dischargeComplaintXhr = null; }
                var $dd = $('#discharge_complaint_dropdown');
                $dd.hide().empty();
                _dischargeComplaintDdIdx = -1;
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
                var dropdown = document.getElementById('discharge_complaint_dropdown');
                var btnAdd = document.getElementById('btn_discharge_add_complaint');
                var painHidden = document.getElementById('pain_value');
                var painOptions = section.querySelectorAll('.pain-option');

                function syncPainHidden() {
                    if (!painHidden) return;
                    var selected = '';
                    painOptions.forEach(function(option) {
                        if (option.checked) selected = option.value || '';
                    });
                    painHidden.value = selected;
                }

                if (painOptions && painOptions.length && painHidden) {
                    painOptions.forEach(function(option) {
                        option.addEventListener('change', syncPainHidden);
                    });
                    syncPainHidden();
                }

                // Autocomplete Search on Complaint Lookup
                if (lookup) {
                    lookup.addEventListener('input', function() {
                        var q = (this.value || '').trim();
                        if (_dischargeComplaintSearchTimer) clearTimeout(_dischargeComplaintSearchTimer);
                        if (_dischargeComplaintXhr) {
                            try { _dischargeComplaintXhr.abort(); } catch(e){}
                            _dischargeComplaintXhr = null;
                        }
                        if (q.length < 1) {
                            closeDischargeComplaintDropdown();
                            return;
                        }
                        var cacheKey = q.toUpperCase();
                        if (_dischargeComplaintSearchCache[cacheKey]) {
                            openDischargeComplaintDropdown(_dischargeComplaintSearchCache[cacheKey], lookup, btnAdd);
                            return;
                        }
                        _dischargeComplaintSearchTimer = setTimeout(function() {
                            if (document.activeElement !== lookup) return;
                            _dischargeComplaintXhr = $.ajax({
                                url: '<?= base_url('Opd_prescription/complaints_search') ?>',
                                data: { q: q },
                                dataType: 'json',
                                success: function(data) {
                                    _dischargeComplaintXhr = null;
                                    if (document.activeElement !== lookup) return;
                                    var rows = (data && data.rows) ? data.rows : [];
                                    _dischargeComplaintSearchCache[cacheKey] = rows;
                                    openDischargeComplaintDropdown(rows, lookup, btnAdd);
                                },
                                error: function() {
                                    _dischargeComplaintXhr = null;
                                }
                            });
                        }, 250);
                    });

                    lookup.addEventListener('keydown', function(e) {
                        var $dd = $('#discharge_complaint_dropdown');
                        var $items = $dd.find('.discharge-complaint-dd-item');
                        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                            if (!$dd.is(':visible') || !$items.length) return;
                            e.preventDefault();
                            _dischargeComplaintDdIdx = e.key === 'ArrowDown'
                                ? Math.min(_dischargeComplaintDdIdx + 1, $items.length - 1)
                                : Math.max(_dischargeComplaintDdIdx - 1, 0);
                            $items.css('background', '').eq(_dischargeComplaintDdIdx).css('background', '#f0f4ff');
                        } else if (e.key === 'Enter') {
                            e.preventDefault();
                            if ($dd.is(':visible') && _dischargeComplaintDdIdx >= 0 && _dischargeComplaintDdIdx < $items.length) {
                                $items.eq(_dischargeComplaintDdIdx).trigger('click');
                            } else if (btnAdd) {
                                btnAdd.click();
                            }
                            closeDischargeComplaintDropdown();
                        } else if (e.key === 'Escape') {
                            closeDischargeComplaintDropdown();
                        }
                    });

                    lookup.addEventListener('blur', function() {
                        setTimeout(function() {
                            closeDischargeComplaintDropdown();
                        }, 200);
                    });
                }

                if (btnAdd) {
                    btnAdd.addEventListener('click', function() {
                        var inputVal = lookup ? (lookup.value || '').trim() : '';
                        if (inputVal === '') {
                            setComplaintStatus('Type complaint text first.', 'error');
                            return;
                        }

                        // Check if already exists
                        var exists = selectedDischargeComplaints.some(function(item) {
                            return item.term.toUpperCase() === inputVal.toUpperCase();
                        });

                        if (exists) {
                            setComplaintStatus('Complaint already added.', 'error');
                            if (lookup) lookup.value = '';
                            return;
                        }

                        selectedDischargeComplaints.push({
                            id: 0,
                            term: inputVal,
                            frequency: '',
                            severity: '',
                            duration: '',
                            date: ''
                        });

                        if (lookup) lookup.value = '';
                        renderDischargeComplaintTable();
                        setComplaintStatus('Complaint added.', 'success');

                        var $lastTr = $('#discharge_complaint_tbody tr:last');
                        $lastTr.find('.complaint-freq-input').trigger('focus');
                    });
                }

                // Helper Action Buttons for Reason for Admission / Other Complaints
                $('#btn_complaint_mic').off('click').on('click', function() {
                    var $btn = $(this);
                    var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                    if (!SpeechRecognition) {
                        alert('Voice dictation is not supported in this browser. Please use Chrome or Edge.');
                        return;
                    }
                    if ($btn.hasClass('listening')) {
                        if (window._dischargeSpeechRec) window._dischargeSpeechRec.stop();
                        $btn.removeClass('listening btn-danger').addClass('btn-outline-secondary').html('<i class="fas fa-microphone text-danger me-1"></i> Med Mic');
                        return;
                    }
                    var rec = new SpeechRecognition();
                    rec.continuous = true;
                    rec.interimResults = true;
                    rec.lang = 'en-US';
                    window._dischargeSpeechRec = rec;

                    $btn.addClass('listening btn-danger').removeClass('btn-outline-secondary').html('<i class="fas fa-microphone me-1"></i> Listening...');

                    rec.onresult = function(e) {
                        var transcript = '';
                        for (var i = e.resultIndex; i < e.results.length; i++) {
                            transcript += e.results[i][0].transcript;
                        }
                        var $ed = $('#complaint_remark_editor');
                        var curr = $ed.val() || '';
                        $ed.val((curr ? curr + ' ' : '') + transcript);
                    };
                    rec.onerror = function() {
                        $btn.removeClass('listening btn-danger').addClass('btn-outline-secondary').html('<i class="fas fa-microphone text-danger me-1"></i> Med Mic');
                    };
                    rec.onend = function() {
                        $btn.removeClass('listening btn-danger').addClass('btn-outline-secondary').html('<i class="fas fa-microphone text-danger me-1"></i> Med Mic');
                    };
                    rec.start();
                });

                $('#btn_complaint_past_data').off('click').on('click', function() {
                    var hpiNote = $('textarea[name="hpi_note"]').val() || '';
                    if (!hpiNote.trim()) {
                        setComplaintStatus('No past H&P note available in Nursing History.', 'muted');
                        return;
                    }
                    var $ed = $('#complaint_remark_editor');
                    var curr = ($ed.val() || '').trim();
                    if (curr !== '') {
                        if (confirm('Append Nursing H&P Note to Reason for Admission text?')) {
                            $ed.val(curr + '\n' + hpiNote.trim());
                        }
                    } else {
                        $ed.val(hpiNote.trim());
                    }
                    setComplaintStatus('Copied Nursing H&P note.', 'success');
                });

                $('#btn_complaint_clear').off('click').on('click', function() {
                    $('#complaint_remark_editor').val('');
                });

                // Initialize table with existing data
                initDischargeComplaintsTable();
            }

            // Surgery/Procedure autocomplete with SNOMED/ICD code display
            function initSurgeryProcedureAutocomplete() {
                initTermAutocomplete('surgery', 'new_surgery_name', 'discharge_surgery_dropdown', 'new_surgery_master_id');
                initTermAutocomplete('procedure', 'new_procedure_name', 'discharge_procedure_dropdown', 'new_procedure_master_id');

                // Quick-add button handlers
                initQuickAddTermHandlers();
            }

            function initQuickAddTermHandlers() {
                var quickModal = document.getElementById('quickAddTermModal');
                var quickType = document.getElementById('quick_term_type');
                var quickTypeLabel = document.getElementById('quick_term_type_label');
                var quickName = document.getElementById('quick_term_name');
                var quickCode = document.getElementById('quick_term_code');
                var quickIcd = document.getElementById('quick_term_icd');
                var quickStatus = document.getElementById('quick_term_status');
                var btnSave = document.getElementById('btn_quick_term_save');

                if (!quickModal || !quickName) return;

                // Surgery quick-add button
                var btnQuickSurgery = document.getElementById('btn_quick_add_surgery');
                if (btnQuickSurgery && btnQuickSurgery.dataset.quickAddBound !== '1') {
                    btnQuickSurgery.dataset.quickAddBound = '1';
                    btnQuickSurgery.addEventListener('click', function() {
                        var surgeryInput = document.getElementById('new_surgery_name');
                        var surgeryName = surgeryInput ? surgeryInput.value.trim() : '';

                        if (!surgeryName) {
                            alert('Please enter a surgery name first');
                            return;
                        }

                        quickType.value = 'surgery';
                        quickTypeLabel.textContent = 'Surgery';
                        quickName.value = surgeryName;
                        quickCode.value = '';
                        quickIcd.value = '';
                        quickStatus.textContent = '';
                        quickStatus.className = 'text-muted';

                        showModalById('quickAddTermModal');
                    });
                }

                // Procedure quick-add button
                var btnQuickProcedure = document.getElementById('btn_quick_add_procedure');
                if (btnQuickProcedure && btnQuickProcedure.dataset.quickAddBound !== '1') {
                    btnQuickProcedure.dataset.quickAddBound = '1';
                    btnQuickProcedure.addEventListener('click', function() {
                        var procedureInput = document.getElementById('new_procedure_name');
                        var procedureName = procedureInput ? procedureInput.value.trim() : '';

                        if (!procedureName) {
                            alert('Please enter a procedure name first');
                            return;
                        }

                        quickType.value = 'procedure';
                        quickTypeLabel.textContent = 'Procedure';
                        quickName.value = procedureName;
                        quickCode.value = '';
                        quickIcd.value = '';
                        quickStatus.textContent = '';
                        quickStatus.className = 'text-muted';

                        showModalById('quickAddTermModal');
                    });
                }

                // Save button in modal
                if (btnSave && btnSave.dataset.quickAddBound !== '1') {
                    btnSave.dataset.quickAddBound = '1';
                    btnSave.addEventListener('click', function() {
                        var name = quickName.value.trim();
                        var type = quickType.value;
                        var code = quickCode.value.trim();
                        var icd = quickIcd.value.trim();

                        if (!name) {
                            setQuickStatus('Name is required', 'error');
                            return;
                        }

                        if (!window.jQuery) {
                            setQuickStatus('jQuery not loaded', 'error');
                            return;
                        }

                        var form = getDischargeForm();
                        if (!form) {
                            setQuickStatus('Form not found', 'error');
                            return;
                        }

                        var csrf = getCsrfPair(form);
                        var payload = {
                            id: 0,
                            name: name,
                            code: code,
                            icd_code: icd,
                            is_active: 1
                        };

                        // For surgery/procedure, include type in payload
                        if (type === 'surgery' || type === 'procedure') {
                            payload.type = type;
                        }

                        payload[csrf.name] = csrf.value;

                        setQuickStatus('Saving...', 'muted');
                        btnSave.disabled = true;

                        // Use appropriate endpoint based on type
                        var saveUrl = type === 'course' ?
                            '<?= base_url('Ipd_discharge/course_master_save') ?>' :
                            '<?= base_url('Ipd_discharge/surgery_master_save') ?>';

                        $.post(saveUrl, payload, function(data) {
                            updateFormCsrf(form, data);
                            btnSave.disabled = false;

                            if (!data || parseInt(data.update || '0', 10) !== 1) {
                                setQuickStatus((data && data.error_text) ? data.error_text : 'Save failed', 'error');
                                return;
                            }

                            setQuickStatus('✓ Saved successfully!', 'success');

                            // Update the input with saved name and set master_id
                            var savedId = parseInt(data.id || '0', 10);
                            if (type === 'surgery') {
                                var surgeryInput = document.getElementById('new_surgery_name');
                                var surgeryMasterId = document.getElementById('new_surgery_master_id');
                                if (surgeryInput) surgeryInput.value = name;
                                if (surgeryMasterId) surgeryMasterId.value = savedId;
                            } else if (type === 'procedure') {
                                var procedureInput = document.getElementById('new_procedure_name');
                                var procedureMasterId = document.getElementById('new_procedure_master_id');
                                if (procedureInput) procedureInput.value = name;
                                if (procedureMasterId) procedureMasterId.value = savedId;
                            } else if (type === 'course') {
                                var courseInput = document.getElementById('new_course_name');
                                var courseMasterId = document.getElementById('new_course_master_id');
                                if (courseInput) courseInput.value = name;
                                if (courseMasterId) courseMasterId.value = savedId;
                            }

                            // Close modal after short delay
                            setTimeout(function() {
                                hideModalById('quickAddTermModal');
                            }, 1000);
                        }, 'json').fail(function() {
                            btnSave.disabled = false;
                            setQuickStatus('Network error', 'error');
                        });
                    });
                }

                function setQuickStatus(text, level) {
                    if (!quickStatus) return;

                    quickStatus.classList.remove('text-success', 'text-danger', 'text-muted');
                    if (level === 'success') {
                        quickStatus.classList.add('text-success');
                    } else if (level === 'error') {
                        quickStatus.classList.add('text-danger');
                    } else {
                        quickStatus.classList.add('text-muted');
                    }
                    quickStatus.textContent = text;
                }
            }

            function initTermAutocomplete(type, inputId, dropdownId, hiddenId) {
                var input = document.getElementById(inputId);
                var dropdown = document.getElementById(dropdownId);
                var hidden = document.getElementById(hiddenId);
                if (!input || !dropdown || !hidden) return;
                if (input.dataset.termAutocompleteBound === '1') return;
                input.dataset.termAutocompleteBound = '1';

                var searchTimer = null;
                var highlightedIndex = -1;
                var currentResults = [];

                input.addEventListener('input', function() {
                    var q = input.value.trim();
                    highlightedIndex = -1;

                    if (q.length < 2) {
                        dropdown.style.display = 'none';
                        return;
                    }

                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(function() {
                        if (!window.jQuery) return;

                        $.get('<?= base_url('Ipd_discharge/surgery_master_lookup') ?>?type=' + type + '&q=' + encodeURIComponent(q), function(data) {
                            currentResults = (data && data.rows) ? data.rows : [];
                            if (!currentResults.length) {
                                dropdown.style.display = 'none';
                                return;
                            }

                            var html = '';
                            currentResults.forEach(function(row) {
                                var name = row.term_name || '';
                                var code = row.term_code || '';
                                var icd = row.icd_code || '';
                                var codeDisplay = '';

                                if (code && icd) {
                                    codeDisplay = '<span class="badge bg-info text-dark me-1">' + code + '</span><span class="badge bg-secondary">' + icd + '</span>';
                                } else if (code) {
                                    codeDisplay = '<span class="badge bg-info text-dark">' + code + '</span>';
                                } else if (icd) {
                                    codeDisplay = '<span class="badge bg-secondary">' + icd + '</span>';
                                }

                                html += '<div class="dropdown-item px-2 py-2" data-id="' + (row.id || 0) + '" data-name="' + name.replace(/"/g, '&quot;') + '" style="cursor:pointer;border-bottom:1px solid #f0f0f0;">';
                                html += '<div class="fw-semibold">' + name + '</div>';
                                if (codeDisplay) {
                                    html += '<div class="small mt-1">' + codeDisplay + '</div>';
                                }
                                html += '</div>';
                            });

                            dropdown.innerHTML = html;
                            dropdown.style.display = 'block';

                            dropdown.querySelectorAll('.dropdown-item').forEach(function(item, idx) {
                                item.addEventListener('mouseenter', function() {
                                    dropdown.querySelectorAll('.dropdown-item').forEach(function(el) {
                                        el.style.backgroundColor = '';
                                    });
                                    this.style.backgroundColor = '#f8f9fa';
                                    highlightedIndex = idx;
                                });
                                item.addEventListener('mouseleave', function() {
                                    this.style.backgroundColor = '';
                                });
                                item.addEventListener('click', function() {
                                    var id = this.getAttribute('data-id');
                                    var name = this.getAttribute('data-name');
                                    input.value = name;
                                    hidden.value = id;
                                    dropdown.style.display = 'none';
                                });
                            });
                        }, 'json');
                    }, 300);
                });

                input.addEventListener('keydown', function(e) {
                    var items = dropdown.querySelectorAll('.dropdown-item');
                    var isVisible = dropdown.style.display === 'block' && items.length > 0;

                    if (e.key === 'ArrowDown' && isVisible) {
                        e.preventDefault();
                        highlightedIndex = Math.min(highlightedIndex + 1, items.length - 1);
                        updateHighlight(items);
                    } else if (e.key === 'ArrowUp' && isVisible) {
                        e.preventDefault();
                        highlightedIndex = Math.max(highlightedIndex - 1, 0);
                        updateHighlight(items);
                    } else if (e.key === 'Enter' && isVisible) {
                        e.preventDefault();
                        if (highlightedIndex >= 0 && items[highlightedIndex]) {
                            items[highlightedIndex].click();
                        }
                    } else if (e.key === 'Escape') {
                        dropdown.style.display = 'none';
                        highlightedIndex = -1;
                    }
                });

                input.addEventListener('blur', function() {
                    setTimeout(function() {
                        dropdown.style.display = 'none';
                    }, 200);
                });

                function updateHighlight(items) {
                    items.forEach(function(item, idx) {
                        if (idx === highlightedIndex) {
                            item.style.backgroundColor = '#007bff';
                            item.style.color = '#fff';
                            item.scrollIntoView({
                                block: 'nearest'
                            });
                        } else {
                            item.style.backgroundColor = '';
                            item.style.color = '';
                        }
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
                if (section === 'instruction_other') {
                    return 'discharge_instruction_other_status';
                }
                if (section === 'instruction_remark') {
                    return 'discharge_instruction_remark_status';
                }

                return '';
            }

            function setNarrativeStatus(section, text, level) {
                var statusId = statusIdByNarrativeSection(section);
                if (statusId !== '') {
                    setSectionStatus(statusId, text, level);
                }
            }

            function getNarrativeFieldText(target) {
                if (!target) {
                    return '';
                }

                if (window.CKEDITOR && CKEDITOR.instances && CKEDITOR.instances[target]) {
                    return (CKEDITOR.instances[target].getData() || '').toString();
                }

                return ($('#' + target).val() || '').toString();
            }

            function setNarrativeFieldText(target, value) {
                if (!target) {
                    return;
                }

                var textValue = (value || '').toString();
                if (window.CKEDITOR && CKEDITOR.instances && CKEDITOR.instances[target]) {
                    CKEDITOR.instances[target].setData(textValue);
                    return;
                }

                $('#' + target).val(textValue);
            }

            // Course/Treatment autocomplete with SNOMED/ICD code display
            function initCourseAutocomplete() {
                var input = document.getElementById('new_course_name');
                var dropdown = document.getElementById('discharge_course_dropdown');
                var hidden = document.getElementById('new_course_master_id');
                if (!input || !dropdown || !hidden) return;

                var searchTimer = null;
                var highlightedIndex = -1;
                var currentResults = [];

                input.addEventListener('input', function() {
                    var q = input.value.trim();
                    highlightedIndex = -1;

                    if (q.length < 2) {
                        dropdown.style.display = 'none';
                        return;
                    }

                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(function() {
                        if (!window.jQuery) return;

                        $.get('<?= base_url('Ipd_discharge/course_master_lookup') ?>?q=' + encodeURIComponent(q), function(data) {
                            currentResults = (data && data.rows) ? data.rows : [];
                            if (!currentResults.length) {
                                dropdown.style.display = 'none';
                                return;
                            }

                            var html = '';
                            currentResults.forEach(function(row) {
                                var name = row.term_name || '';
                                var code = row.term_code || '';
                                var icd = row.icd_code || '';
                                var codeDisplay = '';

                                if (code && icd) {
                                    codeDisplay = '<span class="badge bg-info text-dark me-1">' + code + '</span><span class="badge bg-secondary">' + icd + '</span>';
                                } else if (code) {
                                    codeDisplay = '<span class="badge bg-info text-dark">' + code + '</span>';
                                } else if (icd) {
                                    codeDisplay = '<span class="badge bg-secondary">' + icd + '</span>';
                                }

                                html += '<div class="dropdown-item px-2 py-2" data-id="' + (row.id || 0) + '" data-name="' + name.replace(/"/g, '&quot;') + '" style="cursor:pointer;border-bottom:1px solid #f0f0f0;">';
                                html += '<div class="fw-semibold">' + name + '</div>';
                                if (codeDisplay) {
                                    html += '<div class="small mt-1">' + codeDisplay + '</div>';
                                }
                                html += '</div>';
                            });

                            dropdown.innerHTML = html;
                            dropdown.style.display = 'block';

                            dropdown.querySelectorAll('.dropdown-item').forEach(function(item, idx) {
                                item.addEventListener('mouseenter', function() {
                                    dropdown.querySelectorAll('.dropdown-item').forEach(function(el) {
                                        el.style.backgroundColor = '';
                                    });
                                    this.style.backgroundColor = '#f8f9fa';
                                    highlightedIndex = idx;
                                });
                                item.addEventListener('mouseleave', function() {
                                    this.style.backgroundColor = '';
                                });
                                item.addEventListener('click', function() {
                                    var id = this.getAttribute('data-id');
                                    var name = this.getAttribute('data-name');
                                    input.value = name;
                                    hidden.value = id;
                                    dropdown.style.display = 'none';
                                });
                            });
                        }, 'json');
                    }, 300);
                });

                input.addEventListener('keydown', function(e) {
                    var items = dropdown.querySelectorAll('.dropdown-item');
                    var isVisible = dropdown.style.display === 'block' && items.length > 0;

                    if (e.key === 'ArrowDown' && isVisible) {
                        e.preventDefault();
                        highlightedIndex = Math.min(highlightedIndex + 1, items.length - 1);
                        updateHighlight(items);
                    } else if (e.key === 'ArrowUp' && isVisible) {
                        e.preventDefault();
                        highlightedIndex = Math.max(highlightedIndex - 1, 0);
                        updateHighlight(items);
                    } else if (e.key === 'Enter' && isVisible) {
                        e.preventDefault();
                        if (highlightedIndex >= 0 && items[highlightedIndex]) {
                            items[highlightedIndex].click();
                        }
                    } else if (e.key === 'Escape') {
                        dropdown.style.display = 'none';
                        highlightedIndex = -1;
                    }
                });

                input.addEventListener('blur', function() {
                    setTimeout(function() {
                        dropdown.style.display = 'none';
                    }, 200);
                });

                function updateHighlight(items) {
                    items.forEach(function(item, idx) {
                        if (idx === highlightedIndex) {
                            item.style.backgroundColor = '#007bff';
                            item.style.color = '#fff';
                            item.scrollIntoView({
                                block: 'nearest'
                            });
                        } else {
                            item.style.backgroundColor = '';
                            item.style.color = '';
                        }
                    });
                }

                // Quick-add button handler
                var btnQuickCourse = document.getElementById('btn_quick_add_course');
                if (btnQuickCourse) {
                    btnQuickCourse.addEventListener('click', function() {
                        var courseName = input.value.trim();

                        if (!courseName) {
                            alert('Please enter a course/treatment name first');
                            return;
                        }

                        var quickModal = document.getElementById('quickAddTermModal');
                        var quickType = document.getElementById('quick_term_type');
                        var quickTypeLabel = document.getElementById('quick_term_type_label');
                        var quickName = document.getElementById('quick_term_name');
                        var quickCode = document.getElementById('quick_term_code');
                        var quickIcd = document.getElementById('quick_term_icd');
                        var quickStatus = document.getElementById('quick_term_status');

                        if (!quickModal || !quickName) return;

                        quickType.value = 'course';
                        quickTypeLabel.textContent = 'Course/Treatment';
                        quickName.value = courseName;
                        quickCode.value = '';
                        quickIcd.value = '';
                        quickStatus.textContent = '';
                        quickStatus.className = 'text-muted';

                        showModalById('quickAddTermModal');
                    });
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

                        setNarrativeFieldText(target, (data && data.past_text) ? data.past_text : '');
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

                    var text = getNarrativeFieldText(target).trim();
                    if (!text) {
                        setNarrativeStatus(section, 'Type text first, then save as template.', 'error');
                        return;
                    }

                    narrativeTemplateSaveState = {
                        section: section,
                        text: text,
                        target: target
                    };
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

                        narrativeTemplateLoadState = {
                            target: target,
                            section: section,
                            rows: rows
                        };
                        var $sel = $('#ipd_narrative_template_select');
                        $sel.empty();
                        rows.forEach(function(row, idx) {
                            var name = row.template_name || ('Template ' + (idx + 1));
                            var src = parseInt(row.doc_id || '0', 10) === 0 ? '[Master] ' : '[My] ';
                            $sel.append('<option value="' + idx + '">' + $('<div>').text(src + name).html() + '</option>');
                        });

                        var existingText = getNarrativeFieldText(target).trim();
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
                    var currentText = getNarrativeFieldText(target).trim();
                    var finalText = selectedText;

                    if (mode === 'append' && currentText !== '') {
                        if (selectedText !== '' && currentText.toLowerCase() !== selectedText.toLowerCase()) {
                            finalText = currentText + '\n' + selectedText;
                        } else {
                            finalText = currentText;
                        }
                    }

                    setNarrativeFieldText(target, finalText);
                    setNarrativeStatus(section, mode === 'append' ? 'Template appended.' : 'Template loaded.', 'success');
                    hideModalById('ipdNarrativeTemplateModal');
                });

                function resetNarrativeTemplateMasterForm() {
                    $('#ipd_narrative_template_master_id').val('0');
                    $('#ipd_narrative_template_master_scope').val('doctor').prop('disabled', false);
                    $('#ipd_narrative_template_master_name').val('');
                    $('#ipd_narrative_template_master_text').val('');
                    $('#ipd_narrative_template_master_status').text('').attr('class', 'complaint-status text-muted mt-2');
                }

                function renderNarrativeTemplateMasterRows(rows) {
                    narrativeTemplateMasterState.rows = rows || [];
                    var $body = $('#ipd_narrative_template_master_rows');
                    $body.empty();
                    if (!narrativeTemplateMasterState.rows.length) {
                        $body.html('<tr><td colspan="4" class="text-center text-muted">No templates.</td></tr>');
                        return;
                    }

                    narrativeTemplateMasterState.rows.forEach(function(row, index) {
                        var scope = parseInt(row.doc_id || '0', 10) === 0 ? 'Master' : 'My';
                        var scopeClass = scope === 'Master' ? 'bg-primary' : 'bg-secondary';
                        var $tr = $('<tr>');
                        $tr.append($('<td>').append($('<span>').addClass('badge ' + scopeClass).text(scope)));
                        $tr.append($('<td>').text(row.template_name || ''));
                        $tr.append($('<td>').addClass('text-break').text(row.template_text || ''));
                        var $actions = $('<td>');
                        $actions.append($('<button type="button" class="btn btn-outline-primary btn-sm me-1 btn-edit-ipd-narrative-template">Edit</button>').attr('data-index', index));
                        $actions.append($('<button type="button" class="btn btn-outline-danger btn-sm btn-remove-ipd-narrative-template">Deactivate</button>').attr('data-index', index));
                        $tr.append($actions);
                        $body.append($tr);
                    });
                }

                function loadNarrativeTemplateMaster() {
                    var section = (narrativeTemplateMasterState.section || '').toString();
                    if (!section) {
                        return;
                    }
                    $.get('<?= base_url('Ipd_discharge/section_template_list') ?>?section=' + encodeURIComponent(section), function(data) {
                        renderNarrativeTemplateMasterRows((data && data.rows) ? data.rows : []);
                    }, 'json').fail(function() {
                        $('#ipd_narrative_template_master_status').text('Unable to load templates.').attr('class', 'complaint-status text-danger mt-2');
                    });
                }

                $(document)
                    .off('click.ipdNarrativeMaster', '.btn-discharge-template-master')
                    .on('click.ipdNarrativeMaster', '.btn-discharge-template-master', function() {
                        narrativeTemplateMasterState.section = ($(this).data('section') || '').toString();
                        narrativeTemplateMasterState.target = ($(this).data('target') || '').toString();
                        narrativeTemplateMasterState.title = ($(this).data('title') || 'Narrative').toString();
                        $('#ipd_narrative_template_master_title').text(narrativeTemplateMasterState.title + ' Template Master');
                        resetNarrativeTemplateMasterForm();
                        loadNarrativeTemplateMaster();
                        showModalById('ipdNarrativeTemplateMasterModal');
                    })
                    .off('click.ipdNarrativeMaster', '#btn_ipd_narrative_template_master_new')
                    .on('click.ipdNarrativeMaster', '#btn_ipd_narrative_template_master_new', resetNarrativeTemplateMasterForm)
                    .off('click.ipdNarrativeMaster', '.btn-edit-ipd-narrative-template')
                    .on('click.ipdNarrativeMaster', '.btn-edit-ipd-narrative-template', function() {
                        var row = narrativeTemplateMasterState.rows[parseInt($(this).data('index') || '0', 10)] || null;
                        if (!row) {
                            return;
                        }
                        $('#ipd_narrative_template_master_id').val(row.id || '0');
                        $('#ipd_narrative_template_master_scope').val(parseInt(row.doc_id || '0', 10) === 0 ? 'master' : 'doctor').prop('disabled', true);
                        $('#ipd_narrative_template_master_name').val(row.template_name || '');
                        $('#ipd_narrative_template_master_text').val(row.template_text || '');
                    })
                    .off('click.ipdNarrativeMaster', '#btn_ipd_narrative_template_master_save')
                    .on('click.ipdNarrativeMaster', '#btn_ipd_narrative_template_master_save', function() {
                        var form = getDischargeForm();
                        var section = (narrativeTemplateMasterState.section || '').toString();
                        var name = ($('#ipd_narrative_template_master_name').val() || '').toString().trim();
                        var text = ($('#ipd_narrative_template_master_text').val() || '').toString().trim();
                        if (!form || !name || !text) {
                            $('#ipd_narrative_template_master_status').text('Template name and text are required.').attr('class', 'complaint-status text-danger mt-2');
                            return;
                        }
                        var csrf = getCsrfPair(form);
                        var payload = {
                            section: section,
                            template_id: $('#ipd_narrative_template_master_id').val() || '0',
                            template_scope: $('#ipd_narrative_template_master_scope').val() || 'doctor',
                            template_name: name,
                            template_text: text
                        };
                        payload[csrf.name] = csrf.value;
                        $.post('<?= base_url('Ipd_discharge/section_template_save') ?>', payload, function(data) {
                            if (data && data.csrfName && data.csrfHash) {
                                form.querySelector('input[name="' + data.csrfName + '"]').value = data.csrfHash;
                            }
                            if (!data || parseInt(data.update || '0', 10) !== 1) {
                                $('#ipd_narrative_template_master_status').text((data && data.error_text) || 'Unable to save template.').attr('class', 'complaint-status text-danger mt-2');
                                return;
                            }
                            resetNarrativeTemplateMasterForm();
                            $('#ipd_narrative_template_master_status').text(data.error_text || 'Template saved.').attr('class', 'complaint-status text-success mt-2');
                            loadNarrativeTemplateMaster();
                        }, 'json');
                    })
                    .off('click.ipdNarrativeMaster', '.btn-remove-ipd-narrative-template')
                    .on('click.ipdNarrativeMaster', '.btn-remove-ipd-narrative-template', function() {
                        var row = narrativeTemplateMasterState.rows[parseInt($(this).data('index') || '0', 10)] || null;
                        var form = getDischargeForm();
                        if (!row || !form || !window.confirm('Deactivate this template?')) {
                            return;
                        }
                        var csrf = getCsrfPair(form);
                        var payload = { section: narrativeTemplateMasterState.section || '', template_id: row.id || '0' };
                        payload[csrf.name] = csrf.value;
                        $.post('<?= base_url('Ipd_discharge/section_template_remove') ?>', payload, function(data) {
                            if (data && data.csrfName && data.csrfHash) {
                                form.querySelector('input[name="' + data.csrfName + '"]').value = data.csrfHash;
                            }
                            if (!data || parseInt(data.update || '0', 10) !== 1) {
                                $('#ipd_narrative_template_master_status').text((data && data.error_text) || 'Unable to deactivate template.').attr('class', 'complaint-status text-danger mt-2');
                                return;
                            }
                            resetNarrativeTemplateMasterForm();
                            $('#ipd_narrative_template_master_status').text(data.error_text || 'Template deactivated.').attr('class', 'complaint-status text-success mt-2');
                            loadNarrativeTemplateMaster();
                        }, 'json');
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
                    $('#new_diagnosis_master_code').val('0');
                    $('#new_diagnosis_snomed_concept_id').val('');
                    $('#new_diagnosis_snomed_term').val('');
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
                    var payload = {
                        text: inputVal
                    };
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
                    var payload = {
                        text: inputVal
                    };
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

            function renderSurgeryRows(rows) {
                var $tbody = $('#discharge_surgery_tbody');
                if (!rows || !rows.length) {
                    $tbody.html('<tr><td colspan="4" class="text-muted text-center">No surgery rows.</td></tr>');
                    return;
                }
                var html = '';
                rows.forEach(function(row) {
                    var id = parseInt(row.id || '0', 10);
                    var name = $('<div>').text(row.surgery_name || '').html();
                    var date = $('<div>').text(row.surgery_date || '').html();
                    var remark = $('<div>').text(row.surgery_remark || '').html();
                    html += '<tr>'
                        + '<td>' + name + '</td>'
                        + '<td>' + date + '</td>'
                        + '<td>' + remark + '</td>'
                        + '<td><button type="button" class="btn btn-outline-danger btn-sm btn-remove-surgery-row" data-id="' + id + '">Remove</button></td>'
                        + '</tr>';
                });
                $tbody.html(html);
            }

            function renderProcedureRows(rows) {
                var $tbody = $('#discharge_procedure_tbody');
                if (!rows || !rows.length) {
                    $tbody.html('<tr><td colspan="4" class="text-muted text-center">No procedure rows.</td></tr>');
                    return;
                }
                var html = '';
                rows.forEach(function(row) {
                    var id = parseInt(row.id || '0', 10);
                    var name = $('<div>').text(row.procedure_name || '').html();
                    var date = $('<div>').text(row.procedure_date || '').html();
                    var remark = $('<div>').text(row.procedure_remark || '').html();
                    html += '<tr>'
                        + '<td>' + name + '</td>'
                        + '<td>' + date + '</td>'
                        + '<td>' + remark + '</td>'
                        + '<td><button type="button" class="btn btn-outline-danger btn-sm btn-remove-procedure-row" data-id="' + id + '">Remove</button></td>'
                        + '</tr>';
                });
                $tbody.html(html);
            }

            $(document).on('click', '#btn_add_surgery_row', function() {
                var form = getDischargeForm();
                var name = ($('#new_surgery_name').val() || '').toString().trim();
                if (!name) {
                    setSectionStatus('discharge_surgery_status', 'Enter surgery name before adding.', 'error');
                    return;
                }
                var date = ($('#new_surgery_date').val() || '').toString().trim();
                var remark = ($('#new_surgery_remark').val() || '').toString().trim();
                var masterId = parseInt($('#new_surgery_master_id').val() || '0', 10);
                var csrf = getCsrfPair(form);
                var payload = {
                    action: 'add_surgery',
                    new_surgery_name: name,
                    new_surgery_date: date,
                    new_surgery_remark: remark,
                    new_surgery_master_id: masterId
                };
                payload[csrf.name] = csrf.value;

                $.post($(form).attr('action') || window.location.href, payload, function(data) {
                    updateFormCsrf(form, data);
                    if (data && data.surgeryRows) {
                        renderSurgeryRows(data.surgeryRows);
                    }
                    $('#new_surgery_name').val('');
                    $('#new_surgery_date').val('');
                    $('#new_surgery_remark').val('');
                    $('#new_surgery_master_id').val('0');
                    setSectionStatus('discharge_surgery_status', (data && data.notice) ? data.notice : 'Surgery row added.', 'success');
                }, 'json').fail(function() {
                    setSectionStatus('discharge_surgery_status', 'Unable to add surgery row.', 'error');
                });
            });

            $(document).on('click', '.btn-remove-surgery-row', function() {
                var form = getDischargeForm();
                var id = parseInt($(this).data('id') || '0', 10);
                if (id <= 0) return;
                var csrf = getCsrfPair(form);
                var payload = {
                    action: 'remove_surgery',
                    surgery_remove_id: id
                };
                payload[csrf.name] = csrf.value;

                $.post($(form).attr('action') || window.location.href, payload, function(data) {
                    updateFormCsrf(form, data);
                    if (data && data.surgeryRows) {
                        renderSurgeryRows(data.surgeryRows);
                    }
                    setSectionStatus('discharge_surgery_status', (data && data.notice) ? data.notice : 'Surgery row removed.', 'success');
                }, 'json');
            });

            $(document).on('click', '#btn_add_procedure_row', function() {
                var form = getDischargeForm();
                var name = ($('#new_procedure_name').val() || '').toString().trim();
                if (!name) {
                    setSectionStatus('discharge_surgery_status', 'Enter procedure name before adding.', 'error');
                    return;
                }
                var date = ($('#new_procedure_date').val() || '').toString().trim();
                var remark = ($('#new_procedure_remark').val() || '').toString().trim();
                var masterId = parseInt($('#new_procedure_master_id').val() || '0', 10);
                var csrf = getCsrfPair(form);
                var payload = {
                    action: 'add_procedure',
                    new_procedure_name: name,
                    new_procedure_date: date,
                    new_procedure_remark: remark,
                    new_procedure_master_id: masterId
                };
                payload[csrf.name] = csrf.value;

                $.post($(form).attr('action') || window.location.href, payload, function(data) {
                    updateFormCsrf(form, data);
                    if (data && data.procedureRows) {
                        renderProcedureRows(data.procedureRows);
                    }
                    $('#new_procedure_name').val('');
                    $('#new_procedure_date').val('');
                    $('#new_procedure_remark').val('');
                    $('#new_procedure_master_id').val('0');
                    setSectionStatus('discharge_surgery_status', (data && data.notice) ? data.notice : 'Procedure row added.', 'success');
                }, 'json').fail(function() {
                    setSectionStatus('discharge_surgery_status', 'Unable to add procedure row.', 'error');
                });
            });

            $(document).on('click', '.btn-remove-procedure-row', function() {
                var form = getDischargeForm();
                var id = parseInt($(this).data('id') || '0', 10);
                if (id <= 0) return;
                var csrf = getCsrfPair(form);
                var payload = {
                    action: 'remove_procedure',
                    procedure_remove_id: id
                };
                payload[csrf.name] = csrf.value;

                $.post($(form).attr('action') || window.location.href, payload, function(data) {
                    updateFormCsrf(form, data);
                    if (data && data.procedureRows) {
                        renderProcedureRows(data.procedureRows);
                    }
                    setSectionStatus('discharge_surgery_status', (data && data.notice) ? data.notice : 'Procedure row removed.', 'success');
                }, 'json');
            });

            function bindSurgeryTermLookup(form, type, lookupId, ddId, targetMasterId, statusId) {
                var lookup = document.getElementById(lookupId);
                var $dd = $('#' + ddId);
                var targetMaster = document.getElementById(targetMasterId);
                if (!lookup || !$dd.length || !targetMaster || !window.jQuery) {
                    return;
                }

                if (lookup.dataset.lookupBound === '1') {
                    return;
                }
                lookup.dataset.lookupBound = '1';

                var timer = null;
                var xhr = null;
                var ddIdx = -1;

                function closeDd() {
                    if (timer) { clearTimeout(timer); timer = null; }
                    if (xhr) { try { xhr.abort(); } catch(e){} xhr = null; }
                    $dd.hide().empty();
                    ddIdx = -1;
                }

                function openDd(rows) {
                    if (!rows || !rows.length) {
                        closeDd();
                        return;
                    }
                    var html = '';
                    rows.forEach(function(row, idx) {
                        var name = (row.term_name || row.name || row.term || '').toString();
                        var code = (row.term_code || row.concept_id || '').toString().trim();
                        var icd = (row.icd_code || '').toString().trim();
                        var sub = '';
                        if (code) sub += 'Code: ' + code;
                        if (icd) sub += (sub ? ' | ' : '') + 'ICD: ' + icd;

                        html += '<div class="dropdown-item py-1 px-2 surg-term-dd-item" data-idx="' + idx + '" style="cursor:pointer; font-size:13px;">'
                            + '<div class="d-flex justify-content-between align-items-center">'
                            + '<span>' + $('<div>').text(name).html() + '</span>'
                            + (sub ? '<small class="text-muted ms-2">' + $('<div>').text(sub).html() + '</small>' : '')
                            + '</div>'
                            + '</div>';
                    });
                    $dd.html(html).show();
                    ddIdx = -1;

                    $dd.find('.surg-term-dd-item').on('click', function() {
                        var idx = parseInt($(this).data('idx'), 10);
                        if (rows[idx]) {
                            var sel = rows[idx];
                            lookup.value = (sel.term_name || sel.name || sel.term || '').toString();
                            targetMaster.value = String(sel.id || 0);
                            setSectionStatus(statusId, 'Master term selected.', 'success');
                        }
                        closeDd();
                    });
                }

                $(lookup).on('input.surgTermLkp', function() {
                    targetMaster.value = '0';
                    var q = (this.value || '').trim();
                    if (timer) clearTimeout(timer);
                    if (xhr) { try { xhr.abort(); } catch(e){} xhr = null; }
                    if (q.length < 1) { closeDd(); return; }

                    timer = setTimeout(function() {
                        xhr = $.ajax({
                            url: '<?= base_url('Ipd_discharge/surgery_master_lookup') ?>',
                            data: { type: type, q: q, master_only: 1 },
                            dataType: 'json',
                            success: function(data) {
                                xhr = null;
                                openDd((data && data.rows) ? data.rows : []);
                            },
                            error: function() { xhr = null; }
                        });
                    }, 200);
                }).on('keydown.surgTermLkp', function(e) {
                    var $items = $dd.find('.surg-term-dd-item');
                    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                        if (!$dd.is(':visible') || !$items.length) return;
                        e.preventDefault();
                        ddIdx = e.key === 'ArrowDown'
                            ? Math.min(ddIdx + 1, $items.length - 1)
                            : Math.max(ddIdx - 1, 0);
                        $items.css('background', '').eq(ddIdx).css('background', '#f0f4ff');
                    } else if (e.key === 'Enter') {
                        if ($dd.is(':visible') && ddIdx >= 0 && ddIdx < $items.length) {
                            e.preventDefault();
                            $items.eq(ddIdx).trigger('click');
                        }
                    } else if (e.key === 'Escape') {
                        closeDd();
                    }
                }).on('blur.surgTermLkp', function() {
                    setTimeout(function() { closeDd(); }, 200);
                });
            }

            function initSurgeryMasterCrud(form) {
                if (!window.jQuery) {
                    return;
                }

                var $type = $('#surgery_master_type');
                var $search = $('#surgery_master_search');
                var $rows = $('#surgery_master_rows');
                if (!form || !$type.length || !$search.length || !$rows.length) {
                    return;
                }

                function setMasterStatus(text, level) {
                    setSectionStatus('surgery_master_status', text, level || 'muted');
                }

                function rowHtml(row) {
                    var status = parseInt(row.is_active || '0', 10) === 1 ? 'Active' : 'Inactive';
                    var safeName = $('<div>').text(row.term_name || '').html();
                    var safeCode = $('<div>').text(row.term_code || '').html();
                    var safeIcd = $('<div>').text(row.icd_code || '').html();
                    return '<tr>' +
                        '<td>' + safeName + '</td>' +
                        '<td>' + safeCode + '</td>' +
                        '<td>' + safeIcd + '</td>' +
                        '<td>' + status + '</td>' +
                        '<td>' +
                        '<button type="button" class="btn btn-outline-primary btn-sm btn-master-edit" data-id="' + (row.id || 0) + '">Edit</button> ' +
                        '<button type="button" class="btn btn-outline-danger btn-sm btn-master-delete" data-id="' + (row.id || 0) + '">Del</button>' +
                        '</td>' +
                        '</tr>';
                }

                var _surgMasterNameTimer = null;
                var _surgMasterNameXhr = null;
                var _surgMasterNameCache = {};
                var _surgMasterNameDdIdx = -1;

                function closeSurgMasterNameDropdown() {
                    if (_surgMasterNameTimer) { clearTimeout(_surgMasterNameTimer); _surgMasterNameTimer = null; }
                    if (_surgMasterNameXhr) { try { _surgMasterNameXhr.abort(); } catch(e){} _surgMasterNameXhr = null; }
                    $('#surgery_master_name_dropdown').hide().empty();
                    _surgMasterNameDdIdx = -1;
                }

                function openSurgMasterNameDropdown(rows) {
                    var $dd = $('#surgery_master_name_dropdown');
                    if (!rows || !rows.length) {
                        $dd.hide().empty();
                        return;
                    }

                    var html = '';
                    rows.forEach(function(item, idx) {
                        var name = item.term_name || item.name || item.term || '';
                        var code = item.term_code || item.concept_id || '';
                        var source = item.source || 'snomed_local';
                        var badgeClass = (source === 'master') ? 'bg-primary' : 'bg-info text-dark';
                        var badgeText = code ? ('SNOMED: ' + code) : (source === 'master' ? 'Master' : 'SNOMED');

                        html += '<div class="dropdown-item py-1 px-2 surg-master-name-dd-item" data-idx="' + idx + '" style="cursor:pointer; font-size:13px;">'
                            + '<div class="d-flex justify-content-between align-items-center">'
                            + '<span>' + $('<div>').text(name).html() + '</span>'
                            + '<span class="badge ' + badgeClass + ' ms-2" style="font-size:10px;">' + $('<div>').text(badgeText).html() + '</span>'
                            + '</div>'
                            + '</div>';
                    });

                    $dd.html(html).show();
                    _surgMasterNameDdIdx = -1;

                    $dd.find('.surg-master-name-dd-item').on('click', function() {
                        var idx = parseInt($(this).data('idx'), 10);
                        if (rows[idx]) {
                            var sel = rows[idx];
                            var selName = sel.term_name || sel.name || sel.term || '';
                            var selCode = sel.term_code || sel.concept_id || '';
                            var selIcd = sel.icd_code || '';

                            $('#surgery_master_name').val(selName);
                            if (selCode) { $('#surgery_master_code').val(selCode); }
                            if (selIcd) { $('#surgery_master_icd').val(selIcd); }
                            setMasterStatus(selCode ? ('SNOMED procedure concept ' + selCode + ' selected.') : 'Surgery selected.', 'success');
                        }
                        closeSurgMasterNameDropdown();
                    });
                }

                $('#surgery_master_name').off('.surgNameSrch').on('input.surgNameSrch', function() {
                    var q = (this.value || '').trim();
                    var type = ($type.val() || 'surgery').toString();
                    if (_surgMasterNameTimer) clearTimeout(_surgMasterNameTimer);
                    if (_surgMasterNameXhr) {
                        try { _surgMasterNameXhr.abort(); } catch(e){}
                        _surgMasterNameXhr = null;
                    }
                    if (q.length < 1) {
                        closeSurgMasterNameDropdown();
                        return;
                    }
                    var cacheKey = type + '_' + q.toUpperCase();
                    if (_surgMasterNameCache[cacheKey]) {
                        openSurgMasterNameDropdown(_surgMasterNameCache[cacheKey]);
                        return;
                    }
                    _surgMasterNameTimer = setTimeout(function() {
                        _surgMasterNameXhr = $.ajax({
                            url: '<?= base_url('Ipd_discharge/surgery_master_lookup') ?>',
                            data: { type: type, q: q },
                            dataType: 'json',
                            success: function(data) {
                                _surgMasterNameXhr = null;
                                var rows = (data && data.rows) ? data.rows : [];
                                _surgMasterNameCache[cacheKey] = rows;
                                openSurgMasterNameDropdown(rows);
                            },
                            error: function() {
                                _surgMasterNameXhr = null;
                            }
                        });
                    }, 250);
                }).on('keydown.surgNameSrch', function(e) {
                    var $dd = $('#surgery_master_name_dropdown');
                    var $items = $dd.find('.surg-master-name-dd-item');
                    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                        if (!$dd.is(':visible') || !$items.length) return;
                        e.preventDefault();
                        _surgMasterNameDdIdx = e.key === 'ArrowDown'
                            ? Math.min(_surgMasterNameDdIdx + 1, $items.length - 1)
                            : Math.max(_surgMasterNameDdIdx - 1, 0);
                        $items.css('background', '').eq(_surgMasterNameDdIdx).css('background', '#f0f4ff');
                    } else if (e.key === 'Enter') {
                        if ($dd.is(':visible') && _surgMasterNameDdIdx >= 0 && _surgMasterNameDdIdx < $items.length) {
                            e.preventDefault();
                            $items.eq(_surgMasterNameDdIdx).trigger('click');
                        }
                    } else if (e.key === 'Escape') {
                        closeSurgMasterNameDropdown();
                    }
                }).on('blur.surgNameSrch', function() {
                    setTimeout(function() {
                        closeSurgMasterNameDropdown();
                    }, 200);
                });

                function clearMasterForm() {
                    closeSurgMasterNameDropdown();
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

                $(document)
                    .off('click.ipdSurgeryCrud', '#btn_discharge_manage_surgery_master')
                    .on('click.ipdSurgeryCrud', '#btn_discharge_manage_surgery_master', function() {
                    clearMasterForm();
                    $search.val('');
                    setMasterStatus('', 'muted');
                    fetchMasterRows();
                    showModalById('ipdSurgeryMasterModal');
                });

                $('#btn_surgery_master_refresh').off('.ipdSurgeryCrud').on('click.ipdSurgeryCrud', fetchMasterRows);
                $type.off('.ipdSurgeryCrud').on('change.ipdSurgeryCrud', function() {
                    clearMasterForm();
                    fetchMasterRows();
                });
                $search.off('.ipdSurgeryCrud').on('input.ipdSurgeryCrud', function() {
                    fetchMasterRows();
                });

                $('#btn_surgery_master_clear').off('.ipdSurgeryCrud').on('click.ipdSurgeryCrud', function() {
                    clearMasterForm();
                });

                $(document)
                    .off('click.ipdSurgeryCrud', '.btn-master-edit')
                    .on('click.ipdSurgeryCrud', '.btn-master-edit', function() {
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

                $(document)
                    .off('click.ipdSurgeryCrud', '.btn-master-delete')
                    .on('click.ipdSurgeryCrud', '.btn-master-delete', function() {
                    var id = parseInt($(this).data('id') || '0', 10);
                    if (id <= 0) {
                        return;
                    }

                    if (!window.confirm('Delete this master row?')) {
                        return;
                    }

                    var csrf = getCsrfPair(form);
                    var payload = {
                        id: id
                    };
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

                $('#btn_surgery_master_save').off('.ipdSurgeryCrud').on('click.ipdSurgeryCrud', function() {
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
                initSurgeryProcedureAutocomplete();
                initSurgeryMasterCrud(form);
            }

            function renderFinalDiagnosisRows(rows) {
                var $tbody = $('#discharge_final_diagnosis_tbody');
                if (!rows || !rows.length) {
                    $tbody.html('<tr><td colspan="3" class="text-muted text-center">No diagnosis rows.</td></tr>');
                    return;
                }
                var html = '';
                rows.forEach(function(row) {
                    var id = parseInt(row.id || '0', 10);
                    var name = $('<div>').text(row.comp_report || '').html();
                    var remark = $('<div>').text(row.comp_remark || '').html();
                    html += '<tr>'
                        + '<td>' + name + '</td>'
                        + '<td>' + remark + '</td>'
                        + '<td><button type="button" class="btn btn-outline-danger btn-sm btn-remove-final-diagnosis-row" data-id="' + id + '">Remove</button></td>'
                        + '</tr>';
                });
                $tbody.html(html);
            }

            $(document).on('click', '#btn_add_final_diagnosis_row', function() {
                var form = getDischargeForm();
                var name = ($('#new_diagnosis_name').val() || '').toString().trim();
                if (!name) {
                    setSectionStatus('discharge_diagnosis_status', 'Enter diagnosis before adding.', 'error');
                    return;
                }
                var remark = ($('#new_diagnosis_remark').val() || '').toString().trim();
                var masterCode = parseInt($('#new_diagnosis_master_code').val() || '0', 10);
                var snomedConceptId = ($('#new_diagnosis_snomed_concept_id').val() || '').toString().trim();
                var snomedTerm = ($('#new_diagnosis_snomed_term').val() || '').toString().trim();
                var diagnosisRemarkText = ($('#diagnosis_remark').val() || '').toString().trim();

                var csrf = getCsrfPair(form);
                var payload = {
                    action: 'add_diagnosis',
                    new_diagnosis_name: name,
                    new_diagnosis_remark: remark,
                    new_diagnosis_master_code: masterCode,
                    new_diagnosis_snomed_concept_id: snomedConceptId,
                    new_diagnosis_snomed_term: snomedTerm,
                    diagnosis_remark: diagnosisRemarkText
                };
                payload[csrf.name] = csrf.value;

                $.post($(form).attr('action') || window.location.href, payload, function(data) {
                    updateFormCsrf(form, data);
                    if (data && data.diagnosisRows) {
                        renderFinalDiagnosisRows(data.diagnosisRows);
                    }
                    $('#new_diagnosis_name').val('');
                    $('#new_diagnosis_remark').val('');
                    $('#new_diagnosis_master_code').val('0');
                    $('#new_diagnosis_snomed_concept_id').val('');
                    $('#new_diagnosis_snomed_term').val('');
                    setSectionStatus('discharge_diagnosis_status', (data && data.notice) ? data.notice : 'Diagnosis row added.', 'success');
                }, 'json').fail(function() {
                    setSectionStatus('discharge_diagnosis_status', 'Unable to add diagnosis row.', 'error');
                });
            });

            $(document).on('click', '.btn-remove-final-diagnosis-row', function() {
                var form = getDischargeForm();
                var id = parseInt($(this).data('id') || '0', 10);
                if (id <= 0) return;
                var diagnosisRemarkText = ($('#diagnosis_remark').val() || '').toString().trim();
                var csrf = getCsrfPair(form);
                var payload = {
                    action: 'remove_diagnosis',
                    diagnosis_remove_id: id,
                    diagnosis_remark: diagnosisRemarkText
                };
                payload[csrf.name] = csrf.value;

                $.post($(form).attr('action') || window.location.href, payload, function(data) {
                    updateFormCsrf(form, data);
                    if (data && data.diagnosisRows) {
                        renderFinalDiagnosisRows(data.diagnosisRows);
                    }
                    setSectionStatus('discharge_diagnosis_status', (data && data.notice) ? data.notice : 'Diagnosis row removed.', 'success');
                }, 'json');
            });

            function bindDiagnosisIcdLookup(form) {
                var lookup = document.getElementById('new_diagnosis_name');
                var $dd = $('#discharge_diagnosis_dropdown');
                var seedBtn = document.getElementById('btn_discharge_seed_icd');
                if (!lookup || !window.jQuery) {
                    return;
                }

                if (lookup.dataset.lookupBound === '1') {
                    return;
                }
                lookup.dataset.lookupBound = '1';

                var timer = null;
                var xhr = null;
                var ddIdx = -1;

                function closeDd() {
                    if (timer) { clearTimeout(timer); timer = null; }
                    if (xhr) { try { xhr.abort(); } catch(e){} xhr = null; }
                    $dd.hide().empty();
                    ddIdx = -1;
                }

                function openDd(rows) {
                    if (!rows || !rows.length) { closeDd(); return; }
                    var html = '';
                    rows.forEach(function(item, idx) {
                        var name = (item.name || item.term || '').toString();
                        var code = (item.icd_code || item.snomed_concept_id || '').toString().trim();
                        var source = item.source || '';
                        var badgeText = code ? (item.icd_code ? ('ICD: ' + code) : ('SNOMED: ' + code)) : '';

                        html += '<div class="dropdown-item py-1 px-2 diag-panel-dd-item" data-idx="' + idx + '" style="cursor:pointer; font-size:13px;">'
                            + '<div class="d-flex justify-content-between align-items-center">'
                            + '<span>' + $('<div>').text(name).html() + '</span>'
                            + (badgeText ? '<span class="badge bg-secondary ms-2" style="font-size:10px;">' + $('<div>').text(badgeText).html() + '</span>' : '')
                            + '</div>'
                            + '</div>';
                    });
                    $dd.html(html).show();
                    ddIdx = -1;

                    $dd.find('.diag-panel-dd-item').on('click', function() {
                        var idx = parseInt($(this).data('idx'), 10);
                        if (rows[idx]) {
                            var chosen = rows[idx];
                            var name = (chosen.name || chosen.term || '').toString().trim();
                            var code = (chosen.icd_code || '').toString().trim();
                            if (code !== '' && name.indexOf('[ICD:') === -1) {
                                name += ' [ICD: ' + code + ']';
                            }
                            lookup.value = name;
                            $('#new_diagnosis_master_code').val(String(chosen.master_code || '0'));
                            $('#new_diagnosis_snomed_concept_id').val(String(chosen.snomed_concept_id || ''));
                            $('#new_diagnosis_snomed_term').val(String(chosen.snomed_term || chosen.name || ''));
                            setSectionStatus('discharge_diagnosis_status', 'Diagnosis selected.', 'success');
                        }
                        closeDd();
                    });
                }

                $(lookup).on('input.diagPanelLkp', function() {
                    $('#new_diagnosis_master_code').val('0');
                    $('#new_diagnosis_snomed_concept_id').val('');
                    $('#new_diagnosis_snomed_term').val('');
                    var q = (this.value || '').trim();
                    if (timer) clearTimeout(timer);
                    if (xhr) { try { xhr.abort(); } catch(e){} xhr = null; }
                    if (q.length < 1) { closeDd(); return; }

                    timer = setTimeout(function() {
                        xhr = $.ajax({
                            url: '<?= base_url('Ipd_discharge/diagnosis_icd_lookup') ?>',
                            data: { q: q },
                            dataType: 'json',
                            success: function(data) {
                                xhr = null;
                                openDd((data && data.rows) ? data.rows : []);
                            },
                            error: function() { xhr = null; }
                        });
                    }, 200);
                }).on('keydown.diagPanelLkp', function(e) {
                    var $items = $dd.find('.diag-panel-dd-item');
                    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                        if (!$dd.is(':visible') || !$items.length) return;
                        e.preventDefault();
                        ddIdx = e.key === 'ArrowDown'
                            ? Math.min(ddIdx + 1, $items.length - 1)
                            : Math.max(ddIdx - 1, 0);
                        $items.css('background', '').eq(ddIdx).css('background', '#f0f4ff');
                    } else if (e.key === 'Enter') {
                        if ($dd.is(':visible') && ddIdx >= 0 && ddIdx < $items.length) {
                            e.preventDefault();
                            $items.eq(ddIdx).trigger('click');
                        }
                    } else if (e.key === 'Escape') {
                        closeDd();
                    }
                }).on('blur.diagPanelLkp', function() {
                    setTimeout(function() { closeDd(); }, 200);
                });

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

            function initDiagnosisMasterCrud(form) {
                if (!window.jQuery || !form || !$('#ipdDiagnosisMasterModal').length) {
                    return;
                }

                var diagnosisMasterRows = [];
                var $search = $('#diagnosis_master_search');
                var $rows = $('#diagnosis_master_rows');

                var _diagMasterNameTimer = null;
                var _diagMasterNameXhr = null;
                var _diagMasterNameCache = {};
                var _diagMasterNameDdIdx = -1;

                function closeDiagMasterNameDropdown() {
                    if (_diagMasterNameTimer) { clearTimeout(_diagMasterNameTimer); _diagMasterNameTimer = null; }
                    if (_diagMasterNameXhr) { try { _diagMasterNameXhr.abort(); } catch(e){} _diagMasterNameXhr = null; }
                    $('#diagnosis_master_name_dropdown').hide().empty();
                    _diagMasterNameDdIdx = -1;
                }

                function openDiagMasterNameDropdown(rows) {
                    var $dd = $('#diagnosis_master_name_dropdown');
                    if (!rows || !rows.length) {
                        $dd.hide().empty();
                        return;
                    }

                    var html = '';
                    rows.forEach(function(item, idx) {
                        var name = item.name || item.term || '';
                        var snomedId = item.snomed_concept_id || item.concept_id || '';
                        var source = item.source || 'snomed';
                        var badgeClass = (source === 'disease_master' || source === 'local') ? 'bg-primary' : 'bg-info text-dark';
                        var badgeText = snomedId ? ('SNOMED: ' + snomedId) : (source === 'disease_master' ? 'Master' : 'Local');

                        html += '<div class="dropdown-item py-1 px-2 diag-master-name-dd-item" data-idx="' + idx + '" style="cursor:pointer; font-size:13px;">'
                            + '<div class="d-flex justify-content-between align-items-center">'
                            + '<span>' + $('<div>').text(name).html() + '</span>'
                            + '<span class="badge ' + badgeClass + ' ms-2" style="font-size:10px;">' + $('<div>').text(badgeText).html() + '</span>'
                            + '</div>'
                            + '</div>';
                    });

                    $dd.html(html).show();
                    _diagMasterNameDdIdx = -1;

                    $dd.find('.diag-master-name-dd-item').on('click', function() {
                        var idx = parseInt($(this).data('idx'), 10);
                        if (rows[idx]) {
                            var sel = rows[idx];
                            var selName = sel.name || sel.term || '';
                            var selSnomedId = sel.snomed_concept_id || sel.concept_id || '';
                            var selSnomedTerm = sel.snomed_term || sel.fsn || selName;

                            $('#diagnosis_master_name').val(selName);
                            $('#diagnosis_master_snomed_id').val(selSnomedId);
                            $('#diagnosis_master_snomed_term').val(selSnomedTerm);
                            setStatus(selSnomedId ? ('SNOMED concept ' + selSnomedId + ' selected.') : 'Diagnosis selected.', 'success');
                        }
                        closeDiagMasterNameDropdown();
                    });
                }

                $('#diagnosis_master_name').off('.diagNameSrch').on('input.diagNameSrch', function() {
                    var q = (this.value || '').trim();
                    if (_diagMasterNameTimer) clearTimeout(_diagMasterNameTimer);
                    if (_diagMasterNameXhr) {
                        try { _diagMasterNameXhr.abort(); } catch(e){}
                        _diagMasterNameXhr = null;
                    }
                    if (q.length < 1) {
                        closeDiagMasterNameDropdown();
                        return;
                    }
                    var cacheKey = q.toUpperCase();
                    if (_diagMasterNameCache[cacheKey]) {
                        openDiagMasterNameDropdown(_diagMasterNameCache[cacheKey]);
                        return;
                    }
                    _diagMasterNameTimer = setTimeout(function() {
                        _diagMasterNameXhr = $.ajax({
                            url: '<?= base_url('Opd_prescription/provisional_diagnosis_search') ?>',
                            data: { q: q },
                            dataType: 'json',
                            success: function(data) {
                                _diagMasterNameXhr = null;
                                var rows = (data && data.rows) ? data.rows : [];
                                _diagMasterNameCache[cacheKey] = rows;
                                openDiagMasterNameDropdown(rows);
                            },
                            error: function() {
                                _diagMasterNameXhr = null;
                            }
                        });
                    }, 250);
                }).on('keydown.diagNameSrch', function(e) {
                    var $dd = $('#diagnosis_master_name_dropdown');
                    var $items = $dd.find('.diag-master-name-dd-item');
                    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                        if (!$dd.is(':visible') || !$items.length) return;
                        e.preventDefault();
                        _diagMasterNameDdIdx = e.key === 'ArrowDown'
                            ? Math.min(_diagMasterNameDdIdx + 1, $items.length - 1)
                            : Math.max(_diagMasterNameDdIdx - 1, 0);
                        $items.css('background', '').eq(_diagMasterNameDdIdx).css('background', '#f0f4ff');
                    } else if (e.key === 'Enter') {
                        if ($dd.is(':visible') && _diagMasterNameDdIdx >= 0 && _diagMasterNameDdIdx < $items.length) {
                            e.preventDefault();
                            $items.eq(_diagMasterNameDdIdx).trigger('click');
                        }
                    } else if (e.key === 'Escape') {
                        closeDiagMasterNameDropdown();
                    }
                }).on('blur.diagNameSrch', function() {
                    setTimeout(function() {
                        closeDiagMasterNameDropdown();
                    }, 200);
                });

                function setStatus(text, level) {
                    setSectionStatus('diagnosis_master_status', text, level || 'muted');
                }

                function clearForm() {
                    closeDiagMasterNameDropdown();
                    $('#diagnosis_master_code').val('0');
                    $('#diagnosis_master_name').val('');
                    $('#diagnosis_master_snomed_id').val('');
                    $('#diagnosis_master_snomed_term').val('');
                    $('#diagnosis_master_active').val('1');
                }

                function renderRows() {
                    if (!diagnosisMasterRows.length) {
                        $rows.html('<tr><td colspan="4" class="text-center text-muted">No records.</td></tr>');
                        return;
                    }

                    var html = '';
                    diagnosisMasterRows.forEach(function(row) {
                        var code = parseInt(row.Code || '0', 10);
                        var active = typeof row.is_active === 'undefined' || parseInt(row.is_active || '0', 10) === 1;
                        html += '<tr>'
                            + '<td>' + $('<div>').text(row.Name || '').html() + '</td>'
                            + '<td>' + $('<div>').text(row.snomed_concept_id || '').html() + '</td>'
                            + '<td>' + (active ? 'Active' : 'Inactive') + '</td>'
                            + '<td><button type="button" class="btn btn-outline-primary btn-sm btn-diagnosis-master-edit" data-code="' + code + '">Edit</button> '
                            + (active ? '<button type="button" class="btn btn-outline-danger btn-sm btn-diagnosis-master-remove" data-code="' + code + '">Deactivate</button>' : '')
                            + '</td></tr>';
                    });
                    $rows.html(html);
                }

                function fetchRows() {
                    $.get('<?= base_url('Opd_prescription/disease_master_data') ?>', {
                        start: 0,
                        length: 100,
                        filter: ($search.val() || '').toString().trim()
                    }, function(data) {
                        diagnosisMasterRows = (data && data.data) ? data.data : [];
                        renderRows();
                    }, 'json').fail(function() {
                        setStatus('Unable to load diagnosis master.', 'error');
                    });
                }

                $(document)
                    .off('click.ipdDiagnosisCrud', '#btn_discharge_manage_diagnosis_master')
                    .on('click.ipdDiagnosisCrud', '#btn_discharge_manage_diagnosis_master', function() {
                        clearForm();
                        $search.val('');
                        setStatus('', 'muted');
                        fetchRows();
                        showModalById('ipdDiagnosisMasterModal');
                    })
                    .off('click.ipdDiagnosisCrud', '.btn-diagnosis-master-edit')
                    .on('click.ipdDiagnosisCrud', '.btn-diagnosis-master-edit', function() {
                        var code = parseInt($(this).data('code') || '0', 10);
                        var row = diagnosisMasterRows.find(function(item) {
                            return parseInt(item.Code || '0', 10) === code;
                        });
                        if (!row) return;
                        $('#diagnosis_master_code').val(String(code));
                        $('#diagnosis_master_name').val(row.Name || '');
                        $('#diagnosis_master_snomed_id').val(row.snomed_concept_id || '');
                        $('#diagnosis_master_snomed_term').val(row.snomed_term || '');
                        $('#diagnosis_master_active').val(typeof row.is_active === 'undefined' || parseInt(row.is_active || '0', 10) === 1 ? '1' : '0');
                    })
                    .off('click.ipdDiagnosisCrud', '.btn-diagnosis-master-remove')
                    .on('click.ipdDiagnosisCrud', '.btn-diagnosis-master-remove', function() {
                        var code = parseInt($(this).data('code') || '0', 10);
                        if (code <= 0 || !window.confirm('Deactivate this diagnosis?')) return;
                        var csrf = getCsrfPair(form);
                        var payload = {};
                        payload[csrf.name] = csrf.value;
                        $.post('<?= base_url('Opd_prescription/disease_master_remove') ?>/' + code, payload, function(data) {
                            updateFormCsrf(form, data);
                            setStatus((data && data.error_text) ? data.error_text : 'Diagnosis deactivated.', data && parseInt(data.update || '0', 10) === 1 ? 'success' : 'error');
                            fetchRows();
                        }, 'json');
                    });

                $('#btn_diagnosis_master_refresh').off('.ipdDiagnosisCrud').on('click.ipdDiagnosisCrud', fetchRows);
                $search.off('.ipdDiagnosisCrud').on('input.ipdDiagnosisCrud', fetchRows);
                $('#btn_diagnosis_master_clear').off('.ipdDiagnosisCrud').on('click.ipdDiagnosisCrud', clearForm);
                $('#btn_diagnosis_master_save').off('.ipdDiagnosisCrud').on('click.ipdDiagnosisCrud', function() {
                    var name = ($('#diagnosis_master_name').val() || '').toString().trim();
                    if (name === '') {
                        setStatus('Name is required.', 'error');
                        return;
                    }
                    var csrf = getCsrfPair(form);
                    var payload = {
                        Code: parseInt($('#diagnosis_master_code').val() || '0', 10),
                        Name: name,
                        snomed_concept_id: ($('#diagnosis_master_snomed_id').val() || '').toString().trim(),
                        snomed_term: ($('#diagnosis_master_snomed_term').val() || '').toString().trim(),
                        is_active: parseInt($('#diagnosis_master_active').val() || '1', 10)
                    };
                    payload[csrf.name] = csrf.value;
                    $.post('<?= base_url('Opd_prescription/disease_master_save') ?>', payload, function(data) {
                        updateFormCsrf(form, data);
                        if (!data || parseInt(data.update || '0', 10) !== 1) {
                            setStatus((data && data.error_text) ? data.error_text : 'Unable to save diagnosis.', 'error');
                            return;
                        }
                        setStatus(data.error_text || 'Diagnosis saved.', 'success');
                        clearForm();
                        fetchRows();
                    }, 'json').fail(function() {
                        setStatus('Unable to save diagnosis.', 'error');
                    });
                });
            }

            function renderCourseRows(rows) {
                var $tbody = $('#discharge_course_tbody');
                if (!rows || !rows.length) {
                    $tbody.html('<tr><td colspan="3" class="text-muted text-center">No course rows.</td></tr>');
                    return;
                }
                var html = '';
                rows.forEach(function(row) {
                    var id = parseInt(row.id || '0', 10);
                    var name = $('<div>').text(row.comp_report || '').html();
                    var remark = $('<div>').text(row.comp_remark || '').html();
                    html += '<tr>'
                        + '<td>' + name + '</td>'
                        + '<td>' + remark + '</td>'
                        + '<td><button type="button" class="btn btn-outline-danger btn-sm btn-remove-course-row" data-id="' + id + '">Remove</button></td>'
                        + '</tr>';
                });
                $tbody.html(html);
            }

            $(document).on('click', '#btn_add_course_row', function() {
                var form = getDischargeForm();
                var name = ($('#new_course_name').val() || '').toString().trim();
                if (!name) {
                    setSectionStatus('discharge_course_status', 'Enter course/treatment before adding.', 'error');
                    return;
                }
                var remark = ($('#new_course_remark').val() || '').toString().trim();
                var masterId = parseInt($('#new_course_master_id').val() || '0', 10);
                var courseRemarkText = ($('#course_remark').val() || '').toString().trim();

                var csrf = getCsrfPair(form);
                var payload = {
                    action: 'add_course',
                    new_course_name: name,
                    new_course_remark: remark,
                    new_course_master_id: masterId,
                    course_remark: courseRemarkText
                };
                payload[csrf.name] = csrf.value;

                $.post($(form).attr('action') || window.location.href, payload, function(data) {
                    updateFormCsrf(form, data);
                    if (data && data.courseRows) {
                        renderCourseRows(data.courseRows);
                    }
                    $('#new_course_name').val('');
                    $('#new_course_remark').val('');
                    $('#new_course_master_id').val('0');
                    setSectionStatus('discharge_course_status', (data && data.notice) ? data.notice : 'Course row added.', 'success');
                }, 'json').fail(function() {
                    setSectionStatus('discharge_course_status', 'Unable to add course row.', 'error');
                });
            });

            $(document).on('click', '.btn-remove-course-row', function() {
                var form = getDischargeForm();
                var id = parseInt($(this).data('id') || '0', 10);
                if (id <= 0) return;
                var courseRemarkText = ($('#course_remark').val() || '').toString().trim();
                var csrf = getCsrfPair(form);
                var payload = {
                    action: 'remove_course',
                    course_remove_id: id,
                    course_remark: courseRemarkText
                };
                payload[csrf.name] = csrf.value;

                $.post($(form).attr('action') || window.location.href, payload, function(data) {
                    updateFormCsrf(form, data);
                    if (data && data.courseRows) {
                        renderCourseRows(data.courseRows);
                    }
                    setSectionStatus('discharge_course_status', (data && data.notice) ? data.notice : 'Course row removed.', 'success');
                }, 'json');
            });

            function initCourseMasterCrud(form) {
                if (!window.jQuery) return;

                var $search = $('#course_master_search');
                var $rows = $('#course_master_rows');
                var $modal = $('#ipdCourseMasterModal');
                if (!form || !$modal.length || !$rows.length) return;

                function setMasterStatus(text, level) {
                    setSectionStatus('course_master_status', text, level || 'muted');
                }

                function rowHtml(row) {
                    var status = parseInt(row.is_active || '0', 10) === 1 ? 'Active' : 'Inactive';
                    var safeName = $('<div>').text(row.term_name || '').html();
                    var safeCode = $('<div>').text(row.term_code || '').html();
                    var safeIcd = $('<div>').text(row.icd_code || '').html();
                    return '<tr>' +
                        '<td>' + safeName + '</td>' +
                        '<td>' + safeCode + '</td>' +
                        '<td>' + safeIcd + '</td>' +
                        '<td>' + status + '</td>' +
                        '<td>' +
                        '<button type="button" class="btn btn-outline-primary btn-sm btn-course-master-edit" data-id="' + (row.id || 0) + '">Edit</button> ' +
                        '<button type="button" class="btn btn-outline-danger btn-sm btn-course-master-delete" data-id="' + (row.id || 0) + '">Del</button>' +
                        '</td>' +
                        '</tr>';
                }

                var _crsMasterTimer = null;
                var _crsMasterXhr = null;
                var _crsMasterDdIdx = -1;

                function closeCrsMasterDropdown() {
                    if (_crsMasterTimer) { clearTimeout(_crsMasterTimer); _crsMasterTimer = null; }
                    if (_crsMasterXhr) { try { _crsMasterXhr.abort(); } catch(e){} _crsMasterXhr = null; }
                    $('#course_master_name_dropdown').hide().empty();
                    _crsMasterDdIdx = -1;
                }

                function openCrsMasterDropdown(rows) {
                    var $dd = $('#course_master_name_dropdown');
                    if (!rows || !rows.length) {
                        $dd.hide().empty();
                        return;
                    }

                    var html = '';
                    rows.forEach(function(item, idx) {
                        var name = item.term_name || item.name || item.term || '';
                        var code = item.term_code || item.concept_id || '';
                        var source = item.source || 'snomed';
                        var badgeClass = (source === 'master') ? 'bg-primary' : 'bg-info text-dark';
                        var badgeText = code ? ('SNOMED: ' + code) : (source === 'master' ? 'Master' : 'SNOMED');

                        html += '<div class="dropdown-item py-1 px-2 crs-master-name-dd-item" data-idx="' + idx + '" style="cursor:pointer; font-size:13px;">'
                            + '<div class="d-flex justify-content-between align-items-center">'
                            + '<span>' + $('<div>').text(name).html() + '</span>'
                            + '<span class="badge ' + badgeClass + ' ms-2" style="font-size:10px;">' + $('<div>').text(badgeText).html() + '</span>'
                            + '</div>'
                            + '</div>';
                    });

                    $dd.html(html).show();
                    _crsMasterDdIdx = -1;

                    $dd.find('.crs-master-name-dd-item').on('click', function() {
                        var idx = parseInt($(this).data('idx'), 10);
                        if (rows[idx]) {
                            var sel = rows[idx];
                            $('#course_master_name').val(sel.term_name || sel.name || sel.term || '');
                            $('#course_master_code').val(sel.term_code || sel.concept_id || '');
                            $('#course_master_icd').val(sel.icd_code || '');
                        }
                        closeCrsMasterDropdown();
                    });
                }

                $('#course_master_name').on('input.crsMasterName', function() {
                    var q = (this.value || '').trim();
                    if (_crsMasterTimer) clearTimeout(_crsMasterTimer);
                    if (_crsMasterXhr) { try { _crsMasterXhr.abort(); } catch(e){} _crsMasterXhr = null; }
                    if (q.length < 2) { closeCrsMasterDropdown(); return; }

                    _crsMasterTimer = setTimeout(function() {
                        _crsMasterXhr = $.ajax({
                            url: '<?= base_url('Ipd_discharge/course_master_lookup') ?>',
                            data: { q: q, master_only: 0 },
                            dataType: 'json',
                            success: function(data) {
                                _crsMasterXhr = null;
                                openCrsMasterDropdown((data && data.rows) ? data.rows : []);
                            },
                            error: function() { _crsMasterXhr = null; }
                        });
                    }, 200);
                }).on('keydown.crsMasterName', function(e) {
                    var $dd = $('#course_master_name_dropdown');
                    var $items = $dd.find('.crs-master-name-dd-item');
                    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                        if (!$dd.is(':visible') || !$items.length) return;
                        e.preventDefault();
                        _crsMasterDdIdx = e.key === 'ArrowDown'
                            ? Math.min(_crsMasterDdIdx + 1, $items.length - 1)
                            : Math.max(_crsMasterDdIdx - 1, 0);
                        $items.css('background', '').eq(_crsMasterDdIdx).css('background', '#f0f4ff');
                    } else if (e.key === 'Enter') {
                        if ($dd.is(':visible') && _crsMasterDdIdx >= 0 && _crsMasterDdIdx < $items.length) {
                            e.preventDefault();
                            $items.eq(_crsMasterDdIdx).trigger('click');
                        }
                    } else if (e.key === 'Escape') {
                        closeCrsMasterDropdown();
                    }
                }).on('blur.crsMasterName', function() {
                    setTimeout(function() { closeCrsMasterDropdown(); }, 200);
                });

                function resetMasterForm() {
                    $('#course_master_id').val('0');
                    $('#course_master_name').val('');
                    $('#course_master_code').val('');
                    $('#course_master_icd').val('');
                    $('#course_master_active').val('1');
                    setMasterStatus('');
                    closeCrsMasterDropdown();
                }

                function fetchMasterRows() {
                    var q = ($search.val() || '').toString().trim();
                    setMasterStatus('Loading...');
                    $.get('<?= base_url('Ipd_discharge/course_master_list') ?>?q=' + encodeURIComponent(q), function(data) {
                        var rows = (data && data.rows) ? data.rows : [];
                        if (!rows.length) {
                            $rows.html('<tr><td colspan="5" class="text-center text-muted">No records found.</td></tr>');
                            setMasterStatus('');
                            return;
                        }

                        var html = '';
                        rows.forEach(function(r) {
                            html += rowHtml(r);
                        });
                        $rows.html(html);

                        $rows.find('.btn-course-master-edit').on('click', function() {
                            var id = parseInt($(this).data('id') || '0', 10);
                            var match = rows.find(function(r) { return r.id === id; });
                            if (match) {
                                $('#course_master_id').val(match.id || 0);
                                $('#course_master_name').val(match.term_name || '');
                                $('#course_master_code').val(match.term_code || '');
                                $('#course_master_icd').val(match.icd_code || '');
                                $('#course_master_active').val(String(match.is_active || 1));
                                setMasterStatus('Editing ID #' + id);
                            }
                        });

                        $rows.find('.btn-course-master-delete').on('click', function() {
                            var id = parseInt($(this).data('id') || '0', 10);
                            if (id <= 0 || !confirm('Delete master term #' + id + '?')) {
                                return;
                            }
                            var csrf = getCsrfPair(form);
                            var payload = { id: id };
                            payload[csrf.name] = csrf.value;

                            setMasterStatus('Deleting...');
                            $.post('<?= base_url('Ipd_discharge/course_master_delete') ?>', payload, function(res) {
                                updateFormCsrf(form, res);
                                fetchMasterRows();
                                resetMasterForm();
                            }, 'json').fail(function() {
                                setMasterStatus('Delete failed.', 'error');
                            });
                        });

                        setMasterStatus('');
                    }, 'json').fail(function() {
                        setMasterStatus('Failed to load master records.', 'error');
                    });
                }

                $(document).off('click.ipdCourseCrud', '#btn_discharge_manage_course_master')
                    .on('click.ipdCourseCrud', '#btn_discharge_manage_course_master', function() {
                        resetMasterForm();
                        fetchMasterRows();
                        showModalById('ipdCourseMasterModal');
                    });

                $('#btn_course_master_refresh').on('click', fetchMasterRows);
                $search.on('keyup', function(e) {
                    if (e.key === 'Enter') {
                        fetchMasterRows();
                    }
                });

                $('#btn_course_master_clear').on('click', resetMasterForm);

                $('#btn_course_master_save').on('click', function() {
                    var id = parseInt($('#course_master_id').val() || '0', 10);
                    var name = ($('#course_master_name').val() || '').toString().trim();
                    var code = ($('#course_master_code').val() || '').toString().trim();
                    var icdCode = ($('#course_master_icd').val() || '').toString().trim();
                    var isActive = parseInt($('#course_master_active').val() || '1', 10);

                    if (!name) {
                        setMasterStatus('Name is required', 'error');
                        return;
                    }

                    var csrf = getCsrfPair(form);
                    var payload = {
                        id: id,
                        name: name,
                        code: code,
                        icd_code: icdCode,
                        is_active: isActive
                    };
                    payload[csrf.name] = csrf.value;

                    setMasterStatus('Saving...');
                    $.post('<?= base_url('Ipd_discharge/course_master_save') ?>', payload, function(res) {
                        updateFormCsrf(form, res);
                        if (!res || parseInt(res.update || '0', 10) !== 1) {
                            setMasterStatus((res && res.error_text) ? res.error_text : 'Save failed', 'error');
                            return;
                        }
                        setMasterStatus('Saved successfully.', 'success');
                        resetMasterForm();
                        fetchMasterRows();
                    }, 'json').fail(function() {
                        setMasterStatus('Save failed.', 'error');
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
                initDiagnosisMasterCrud(form);
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

                initCourseAutocomplete();
                initCourseMasterCrud(form);
            }

            function initInstructionTools() {
                var section = document.getElementById('section-instructions');
                if (!section || section.dataset.toolsBound === '1') {
                    return;
                }
                section.dataset.toolsBound = '1';

                // Re-initialize CKEditor for instruction fields after section reload
                if (typeof CKEDITOR !== 'undefined') {
                    // Destroy existing instances first to avoid duplication
                    if (CKEDITOR.instances['instruction_other']) {
                        CKEDITOR.instances['instruction_other'].destroy(true);
                    }
                    if (CKEDITOR.instances['instruction_remark']) {
                        CKEDITOR.instances['instruction_remark'].destroy(true);
                    }

                    // Re-create CKEditor instances
                    if (document.getElementById('instruction_other')) {
                        CKEDITOR.replace('instruction_other', {
                            height: 120,
                            toolbar: [{
                                    name: 'basicstyles',
                                    items: ['Bold', 'Italic', 'Underline']
                                },
                                {
                                    name: 'paragraph',
                                    items: ['NumberedList', 'BulletedList']
                                },
                                {
                                    name: 'editing',
                                    items: ['Undo', 'Redo']
                                }
                            ]
                        });
                    }

                    if (document.getElementById('instruction_remark')) {
                        CKEDITOR.replace('instruction_remark', {
                            height: 150,
                            toolbar: [{
                                    name: 'basicstyles',
                                    items: ['Bold', 'Italic', 'Underline']
                                },
                                {
                                    name: 'paragraph',
                                    items: ['NumberedList', 'BulletedList']
                                },
                                {
                                    name: 'editing',
                                    items: ['Undo', 'Redo']
                                }
                            ]
                        });
                    }
                }

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
                        if (bucket.some(function(prev) {
                                return prev.toUpperCase() === normalized.toUpperCase();
                            })) {
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

                // Review After quick select buttons
                var reviewChips = section.querySelectorAll('.discharge-review-chip');
                var reviewInput = section.querySelector('#discharge_review_after');
                reviewChips.forEach(function(chip) {
                    chip.addEventListener('click', function() {
                        var value = chip.getAttribute('data-value') || chip.textContent.trim();
                        if (reviewInput) {
                            reviewInput.value = value;
                            reviewInput.focus();
                        }
                    });
                });
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
                    return '<tr>' +
                        '<td>' + safeShort + '</td>' +
                        '<td>' + safeDesc + '</td>' +
                        '<td>' + safeLang + '</td>' +
                        '<td>' +
                        '<button type="button" class="btn btn-outline-primary btn-sm btn-food-edit" data-id="' + (row.id || 0) + '">Edit</button> ' +
                        '<button type="button" class="btn btn-outline-danger btn-sm btn-food-delete" data-id="' + (row.id || 0) + '">Del</button>' +
                        '</td>' +
                        '</tr>';
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
                    var payload = {
                        id: id
                    };
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

                function reloadMedicineSectionFromServer() {
                    if (!window.jQuery) {
                        return;
                    }

                    var form = getDischargeForm();
                    var url = form ? (form.getAttribute('action') || window.location.href) : window.location.href;
                    window.jQuery.get(url, function(html) {
                        var holder = document.createElement('div');
                        holder.innerHTML = String(html || '');
                        if (patchSectionFromHtml(holder, 'section-medicine')) {
                            patchNoticeFromHtml(holder);
                            initMedicineTools();
                            bindDischargeAjaxSubmit();
                            syncNavOnScroll();
                        }
                    });
                }

                if (!window.jQuery) {
                    return;
                }

                var rxGroupCache = [];
                var rxGroupInput = section.querySelector('#selected_rx_group_id');
                var rxGroupName = section.querySelector('#rx_group_selected_name');
                var rxGroupList = document.getElementById('discharge_rx_group_list');
                var rxGroupSearch = document.getElementById('discharge_rx_group_search');
                var applyBtn = section.querySelector('#btn_apply_rx_group');

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

                // Load dose masters (Dose, When, Frequency, Route)
                var doseMasterCache = {
                    dose: [],
                    when: [],
                    freq: [],
                    where: []
                };

                var dischargeFreqDescMap = {
                    'OD': 'OD -> OD (Once Daily)',
                    'BD': 'BD -> BD (Twice Daily)',
                    'TDS': 'TDS -> TDS (Thrice Daily)',
                    'TID': 'TID -> TID (Three Times a Day)',
                    'QID': 'QID -> QID (Four Times a Day)',
                    'HS': 'HS -> HS (At Bedtime)',
                    'SOS': 'SOS -> SOS (As Needed)',
                    'STAT': 'STAT -> STAT (Immediately)',
                    'Q4H': 'Q4H -> Q4H (Every 4 Hours)',
                    'Q6H': 'Q6H -> Q6H (Every 6 Hours)',
                    'Q8H': 'Q8H -> Q8H (Every 8 Hours)'
                };

                var dischargeWhenDescMap = {
                    'BF': 'BF -> Before Food',
                    'AF': 'AF -> After Food',
                    'WF': 'WF -> With Food',
                    'ES': 'ES -> Empty Stomach',
                    'BBF': 'BBF -> Before Breakfast',
                    'ABF': 'ABF -> After Breakfast',
                    'BL': 'BL -> Before Lunch',
                    'AL': 'AL -> After Lunch',
                    'BD': 'BD -> Before Dinner',
                    'AD': 'AD -> After Dinner',
                    'BT': 'BT -> Bed Time'
                };

                function renderSelectOptions($select, rows, placeholder, descMap) {
                    var html = '<option value="">' + $('<div>').text(placeholder || 'Select').html() + '</option>';
                    (rows || []).forEach(function(row) {
                        var id = (row && row.id !== undefined) ? String(row.id) : '';
                        var label = (row && row.label !== undefined) ? String(row.label) : '';
                        if (!id || !label) {
                            return;
                        }
                        var displayText = label;
                        if (descMap && descMap[label.toUpperCase()]) {
                            displayText = descMap[label.toUpperCase()];
                        }
                        html += '<option value="' + $('<div>').text(id).html() + '">' + $('<div>').text(displayText).html() + '</option>';
                    });
                    $select.html(html);
                }

                function loadDischargeDoseMasters() {
                    $.get('<?= base_url('Opd_prescription/rx_group_dose_masters') ?>', function(data) {
                        doseMasterCache = {
                            dose: (data && data.dose) ? data.dose : [],
                            when: (data && data.when) ? data.when : [],
                            freq: (data && data.freq) ? data.freq : [],
                            where: (data && data.where) ? data.where : []
                        };

                        renderSelectOptions($('#discharge_dosage_when'), doseMasterCache.when, 'When', dischargeWhenDescMap);
                        renderSelectOptions($('#discharge_dose_where'), doseMasterCache.where, 'Route');
                    }, 'json').fail(function() {
                        setMedicineStatus('Unable to load dose masters.', 'error');
                    });
                }

                // ── Smart Frequency Autocomplete for #discharge_dosage_freq ─────────────────
                (function() {
                    var freqInput = document.getElementById('discharge_dosage_freq');
                    var freqDropdown = document.getElementById('discharge_dosage_freq_dd');
                    if (!freqInput || !freqDropdown) return;

                    var _FREQ_PRESET_MAP = [
                        { code: 'OD', desc: 'Once Daily (1 dose/day)' },
                        { code: 'BD', desc: 'Twice Daily (2 doses/day)' },
                        { code: 'TDS', desc: 'Thrice Daily (3 doses/day)' },
                        { code: 'QID', desc: 'Four Times Daily (4 doses/day)' },
                        { code: 'HS', desc: 'At Bedtime (Once at night)' },
                        { code: 'SOS', desc: 'As Needed (Only when required)' },
                        { code: 'Q4H', desc: 'Every 4 Hours' },
                        { code: 'Q6H', desc: 'Every 6 Hours' },
                        { code: 'Q8H', desc: 'Every 8 Hours' },
                        { code: 'Alternate Day', desc: 'Once every 2 days' },
                        { code: 'Weekly', desc: 'Long-interval (Once a week)' },
                        { code: 'Monthly', desc: 'Long-interval (Once a month)' },
                        { code: 'Continuous Infusion', desc: 'IV drip maintained continuously' },
                        { code: 'Stat', desc: 'Immediate single dose' }
                    ];

                    var highlightIdx = -1;

                    function getSuggestions(val) {
                        var q = String(val || '').trim().toLowerCase();
                        var numMap = { '1': 'OD', '2': 'BD', '3': 'TDS', '4': 'QID' };
                        var res = [], seen = {};

                        if (numMap[q]) {
                            var targetCode = numMap[q];
                            _FREQ_PRESET_MAP.forEach(function(item) {
                                if (item.code === targetCode) {
                                    seen[item.code] = true;
                                    res.push(item);
                                }
                            });
                        }

                        _FREQ_PRESET_MAP.forEach(function(item) {
                            if (!seen[item.code]) {
                                if (!q || item.code.toLowerCase().indexOf(q) !== -1 || item.desc.toLowerCase().indexOf(q) !== -1) {
                                    seen[item.code] = true;
                                    res.push(item);
                                }
                            }
                        });

                        (doseMasterCache.freq || []).forEach(function(row) {
                            var code = (row && row.label) ? String(row.label) : '';
                            if (code && !seen[code]) {
                                if (!q || code.toLowerCase().indexOf(q) !== -1) {
                                    seen[code] = true;
                                    res.push({ code: code, desc: '' });
                                }
                            }
                        });

                        return res.slice(0, 14);
                    }

                    function renderDropdown(items) {
                        if (!items.length) {
                            freqDropdown.style.display = 'none';
                            freqDropdown.innerHTML = '';
                            highlightIdx = -1;
                            return;
                        }

                        var html = '';
                        items.forEach(function(s, i) {
                            var bgStyle = (i === highlightIdx) ? 'background:#e2ebff;' : '';
                            var labelHtml = '<strong>' + $('<div>').text(s.code).html() + '</strong>';
                            if (s.desc) {
                                labelHtml += ' <span class="text-muted ms-2" style="font-size:12px;">(' + $('<div>').text(s.desc).html() + ')</span>';
                            }
                            html += '<div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center discharge-freq-opt" data-code="' + $('<div>').text(s.code).html() + '" style="cursor:pointer;font-size:13px;' + bgStyle + '">' + labelHtml + '</div>';
                        });

                        freqDropdown.innerHTML = html;
                        freqDropdown.style.display = 'block';
                    }

                    freqInput.addEventListener('focus', function() {
                        var sugs = getSuggestions(freqInput.value);
                        highlightIdx = sugs.length ? 0 : -1;
                        renderDropdown(sugs);
                    });

                    freqInput.addEventListener('input', function() {
                        var sugs = getSuggestions(freqInput.value);
                        highlightIdx = sugs.length ? 0 : -1;
                        renderDropdown(sugs);
                    });

                    freqInput.addEventListener('keydown', function(e) {
                        var sugs = getSuggestions(freqInput.value);
                        if (!sugs.length || freqDropdown.style.display === 'none') {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                var whenSelect = document.getElementById('discharge_dosage_when');
                                if (whenSelect) whenSelect.focus();
                            }
                            return;
                        }

                        if (e.key === 'ArrowDown') {
                            e.preventDefault();
                            highlightIdx = (highlightIdx + 1) % sugs.length;
                            renderDropdown(sugs);
                        } else if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            highlightIdx = (highlightIdx - 1 + sugs.length) % sugs.length;
                            renderDropdown(sugs);
                        } else if (e.key === 'Enter') {
                            e.preventDefault();
                            if (highlightIdx >= 0 && sugs[highlightIdx]) {
                                freqInput.value = sugs[highlightIdx].code;
                            }
                            freqDropdown.style.display = 'none';
                            var whenSelect = document.getElementById('discharge_dosage_when');
                            if (whenSelect) whenSelect.focus();
                        } else if (e.key === 'Escape') {
                            freqDropdown.style.display = 'none';
                        }
                    });

                    $(document).on('click', '.discharge-freq-opt', function() {
                        freqInput.value = $(this).data('code');
                        freqDropdown.style.display = 'none';
                        var whenSelect = document.getElementById('discharge_dosage_when');
                        if (whenSelect) whenSelect.focus();
                    });

                    document.addEventListener('click', function(e) {
                        if (!freqInput.contains(e.target) && !freqDropdown.contains(e.target)) {
                            freqDropdown.style.display = 'none';
                        }
                    });
                })();

                // ── Smart Duration Autotext for #discharge_no_of_days ─────────────────────────
                (function() {
                    var daysInput = document.getElementById('discharge_no_of_days');
                    var daysDropdown = document.getElementById('discharge_no_of_days_dd');
                    if (!daysInput || !daysDropdown) return;

                    var selectedIdx = -1;

                    function buildOptions(val) {
                        val = String(val || '').trim();
                        var num = parseInt(val, 10);
                        if (isNaN(num) || num <= 0) return [];
                        var unitDays = num === 1 ? 'Day' : 'Days';
                        var unitWeeks = num === 1 ? 'Week' : 'Weeks';
                        var unitMonths = num === 1 ? 'Month' : 'Months';
                        return [
                            num + ' ' + unitDays,
                            num + ' ' + unitWeeks,
                            num + ' ' + unitMonths
                        ];
                    }

                    function renderDropdown(items) {
                        if (!items.length) {
                            daysDropdown.style.display = 'none';
                            daysDropdown.innerHTML = '';
                            selectedIdx = -1;
                            return;
                        }
                        var html = '';
                        items.forEach(function(item, i) {
                            html += '<a href="javascript:void(0)" class="dropdown-item discharge-days-opt ' + (i === selectedIdx ? 'active' : '') + '" data-val="' + $('<div>').text(item).html() + '" style="font-size:12px; padding:4px 12px;">' + $('<div>').text(item).html() + '</a>';
                        });
                        daysDropdown.innerHTML = html;
                        daysDropdown.style.display = 'block';
                    }

                    daysInput.addEventListener('input', function() {
                        var items = buildOptions(daysInput.value);
                        selectedIdx = items.length ? 0 : -1;
                        renderDropdown(items);
                    });

                    daysInput.addEventListener('keydown', function(e) {
                        var items = daysDropdown.querySelectorAll('.discharge-days-opt');
                        if (!items.length || daysDropdown.style.display === 'none') {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                var remark = document.getElementById('discharge_remark');
                                if (remark) remark.focus();
                            }
                            return;
                        }
                        if (e.key === 'ArrowDown') {
                            e.preventDefault();
                            selectedIdx = (selectedIdx + 1) % items.length;
                            renderDropdown(buildOptions(daysInput.value));
                        } else if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            selectedIdx = (selectedIdx - 1 + items.length) % items.length;
                            renderDropdown(buildOptions(daysInput.value));
                        } else if (e.key === 'Enter') {
                            e.preventDefault();
                            if (selectedIdx >= 0 && items[selectedIdx]) {
                                daysInput.value = items[selectedIdx].getAttribute('data-val');
                            }
                            daysDropdown.style.display = 'none';
                            var remark = document.getElementById('discharge_remark');
                            if (remark) remark.focus();
                        } else if (e.key === 'Escape') {
                            daysDropdown.style.display = 'none';
                        }
                    });

                    $(document).on('click', '.discharge-days-opt', function() {
                        daysInput.value = $(this).data('val');
                        daysDropdown.style.display = 'none';
                        var remark = document.getElementById('discharge_remark');
                        if (remark) remark.focus();
                    });

                    document.addEventListener('click', function(e) {
                        if (!daysInput.contains(e.target) && !daysDropdown.contains(e.target)) {
                            daysDropdown.style.display = 'none';
                        }
                    });
                })();

                // ── Smart Medicine Advice Autotext for #discharge_remark ─────────────────────
                (function() {
                    var _REMARK_PRESETS = [
                        'Take with warm water',
                        'Take with milk',
                        'Avoid sour food and dairy products',
                        'Take after meals',
                        'Take on an empty stomach early morning',
                        'Chew well before swallowing',
                        'Dissolve in half glass of water',
                        'Apply locally twice daily',
                        'Instill 1-2 drops in affected eye',
                        'Instill 1-2 drops in affected ear',
                        'Use via nebulizer',
                        'Store in refrigerator (2-8°C)',
                        'Shake well before use',
                        'Do not crush or chew tablet',
                        'Avoid alcohol while taking this medicine',
                        'Complete full course of antibiotics',
                        'Avoid heavy and spicy food',
                        'Drink plenty of fluids / water'
                    ];

                    var medRemarkHighlightIdx = -1;

                    function highlightMedRemarkItem(idx) {
                        var $items = $('#discharge_remark_dd .discharge-remark-dd-item');
                        if (!$items.length) return;
                        if (idx < 0) idx = 0;
                        if (idx >= $items.length) idx = $items.length - 1;
                        medRemarkHighlightIdx = idx;

                        $items.css('background', '').removeClass('active-dd-item');
                        var $target = $items.eq(medRemarkHighlightIdx);
                        $target.css('background', '#e2ebff').addClass('active-dd-item');

                        var container = document.getElementById('discharge_remark_dd');
                        var elem = $target[0];
                        if (container && elem) {
                            var cTop = container.scrollTop;
                            var cBottom = cTop + container.clientHeight;
                            var eTop = elem.offsetTop;
                            var eBottom = eTop + elem.offsetHeight;
                            if (eTop < cTop) {
                                container.scrollTop = eTop;
                            } else if (eBottom > cBottom) {
                                container.scrollTop = eBottom - container.clientHeight;
                            }
                        }
                    }

                    function getMedRemarkSuggestions(input) {
                        var q = (input || '').toString().trim().toLowerCase();
                        var suggestions = [];
                        _REMARK_PRESETS.forEach(function(p) {
                            if (!q || p.toLowerCase().indexOf(q) !== -1) {
                                suggestions.push(p);
                            }
                        });
                        return suggestions.slice(0, 10);
                    }

                    function renderMedRemarkDropdown(sugs) {
                        var $dd = $('#discharge_remark_dd').empty();
                        medRemarkHighlightIdx = -1;
                        if (!sugs.length) { $dd.hide(); return; }

                        sugs.forEach(function(s, idx) {
                            var $row = $('<div class="px-3 py-2 border-bottom discharge-remark-dd-item" data-idx="' + idx + '" style="cursor:pointer;font-size:.875rem"></div>')
                                .text(s);

                            $row.on('mouseenter', function() { highlightMedRemarkItem(idx); })
                                .on('mouseleave', function() { $(this).css('background',''); })
                                .on('mousedown', function(e) { e.preventDefault(); })
                                .on('click', function() {
                                    $('#discharge_remark').val(s);
                                    $dd.hide().empty();
                                    medRemarkHighlightIdx = -1;
                                    var btnAdd = document.getElementById('btn_discharge_med_add');
                                    if (btnAdd) btnAdd.focus();
                                });

                            $dd.append($row);
                        });

                        $dd.show();
                    }

                    $('#discharge_remark').on('input focus', function() {
                        var q = ($(this).val() || '').trim();
                        var sugs = getMedRemarkSuggestions(q);
                        renderMedRemarkDropdown(sugs);
                    }).on('blur', function() {
                        setTimeout(function() { $('#discharge_remark_dd').hide(); medRemarkHighlightIdx = -1; }, 200);
                    }).on('keydown', function(e) {
                        var $dd = $('#discharge_remark_dd');
                        var isVisible = $dd.is(':visible');
                        var $items = $('#discharge_remark_dd .discharge-remark-dd-item');

                        if (e.key === 'ArrowDown') {
                            if (!isVisible) {
                                var sugs = getMedRemarkSuggestions(($(this).val() || '').trim());
                                renderMedRemarkDropdown(sugs);
                                highlightMedRemarkItem(0);
                                return;
                            }
                            e.preventDefault();
                            var nextIdx = medRemarkHighlightIdx + 1;
                            if (nextIdx >= $items.length) nextIdx = 0;
                            highlightMedRemarkItem(nextIdx);
                        } else if (e.key === 'ArrowUp') {
                            if (!isVisible) return;
                            e.preventDefault();
                            var prevIdx = medRemarkHighlightIdx - 1;
                            if (prevIdx < 0) prevIdx = $items.length - 1;
                            highlightMedRemarkItem(prevIdx);
                        } else if (e.key === 'Enter') {
                            if (isVisible && medRemarkHighlightIdx >= 0) {
                                e.preventDefault();
                                var $target = $items.eq(medRemarkHighlightIdx);
                                if ($target.length) {
                                    $target.trigger('click');
                                }
                            } else if (!isVisible && e.key === 'Enter') {
                                e.preventDefault();
                                var btnAdd = document.getElementById('btn_discharge_med_add');
                                if (btnAdd) btnAdd.focus();
                            }
                        } else if (e.key === 'Escape') {
                            $dd.hide().empty();
                            medRemarkHighlightIdx = -1;
                        }
                    });
                })();

                // Load dose masters on init
                loadDischargeDoseMasters();

                // Handle medicine autocomplete with full suggestions
                var dischargeMedInput = section.querySelector('#discharge_med_name');
                var dischargeMedSuggest = section.querySelector('#discharge_med_suggest');
                var dischargeMedType = section.querySelector('#discharge_med_type');
                var dischargeMedSuggestRows = [];
                var dischargeMedInputTimer = null;

                var isSelectingDischargeMed = false;

                function applyDischargeMedicineMatch(matched) {
                    if (!matched || !dischargeMedInput) {
                        return;
                    }

                    isSelectingDischargeMed = true;

                    var medName = String(matched.med_name || '').trim();
                    var medType = String(matched.med_type || '').trim();

                    if (medName !== '') {
                        dischargeMedInput.value = medName;
                    }

                    // Hide search dropdown immediately
                    $('#discharge_med_name_dd').hide().empty();
                    dischargeMedHighlightIdx = -1;
                    if (dischargeMedInputTimer) {
                        clearTimeout(dischargeMedInputTimer);
                        dischargeMedInputTimer = null;
                    }

                    $(dischargeMedInput).data('med-id', parseInt(matched.id || 0, 10));
                    if (section.querySelector('#discharge_med_id')) {
                        section.querySelector('#discharge_med_id').value = String(matched.id || '0');
                    }
                    if (section.querySelector('#discharge_med_salt')) {
                        section.querySelector('#discharge_med_salt').value = String(matched.med_salt || matched.genericname || '').trim();
                    }

                    if (dischargeMedType && medType !== '') {
                        dischargeMedType.value = medType;
                    }

                    var fieldMap = [
                        ['dosage', '#discharge_dosage'],
                        ['dosage_when', '#discharge_dosage_when'],
                        ['dosage_freq', '#discharge_dosage_freq'],
                        ['dosage_where', '#discharge_dose_where']
                    ];
                    fieldMap.forEach(function(mapping) {
                        var value = matched[mapping[0]];
                        var target = section.querySelector(mapping[1]);
                        if (value && target && target.value === '') {
                            ensureDoseOption(target, value);
                            target.value = value;
                        }
                    });

                    var textFieldMap = [
                        ['no_of_days', '#discharge_no_of_days'],
                        ['qty', '#discharge_qty'],
                        ['remark', '#discharge_remark']
                    ];
                    textFieldMap.forEach(function(mapping) {
                        var value = matched[mapping[0]];
                        var target = section.querySelector(mapping[1]);
                        if (value && target && target.value === '') {
                            target.value = String(value).trim();
                        }
                    });

                    if (matched.id) {
                        loadDischargeMedicineSubstitutes(matched.id, medName);
                    }

                    setTimeout(function() {
                        isSelectingDischargeMed = false;
                    }, 300);
                }

                var dischargeMedHighlightIdx = -1;

                function highlightDischargeMedNameItem(idx) {
                    var $items = $('#discharge_med_name_dd .discharge-med-name-dd-item');
                    if (!$items.length) return;
                    if (idx < 0) idx = 0;
                    if (idx >= $items.length) idx = $items.length - 1;
                    dischargeMedHighlightIdx = idx;

                    $items.css('background', '').removeClass('active-dd-item');
                    var $target = $items.eq(dischargeMedHighlightIdx);
                    $target.css('background', '#e2ebff').addClass('active-dd-item');

                    var container = document.getElementById('discharge_med_name_dd');
                    var elem = $target[0];
                    if (container && elem) {
                        var cTop = container.scrollTop;
                        var cBottom = cTop + container.clientHeight;
                        var eTop = elem.offsetTop;
                        var eBottom = eTop + elem.offsetHeight;
                        if (eTop < cTop) {
                            container.scrollTop = eTop;
                        } else if (eBottom > cBottom) {
                            container.scrollTop = eBottom - container.clientHeight;
                        }
                    }
                }

                function renderDischargeMedNameDropdown(rows) {
                    var $dd = $('#discharge_med_name_dd').empty();
                    dischargeMedSuggestRows = rows || [];
                    dischargeMedHighlightIdx = -1;
                    if (!dischargeMedSuggestRows.length) {
                        $dd.hide();
                        return;
                    }

                    dischargeMedSuggestRows.forEach(function(row, idx) {
                        var medName = (row.med_name || '').toString().trim();
                        var medType = (row.med_type || '').toString().trim();
                        var genericName = (row.genericname || row.med_salt || '').toString().trim();
                        var isFav = parseInt(row.is_favorite || 0, 10) === 1;

                        var $item = $('<div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center discharge-med-name-dd-item" data-idx="' + idx + '" style="cursor:pointer;"></div>');
                        var $left = $('<div></div>');
                        $left.append('<strong class="text-primary fs-6">' + $('<div>').text(medName).html() + '</strong>');
                        if (medType) {
                            $left.append('<span class="badge bg-secondary ms-2">' + $('<div>').text(medType).html() + '</span>');
                        }
                        if (genericName) {
                            $left.append('<small class="text-muted d-block">' + $('<div>').text(genericName).html() + '</small>');
                        }
                        $item.append($left);
                        if (isFav) {
                            $item.append('<span class="text-warning fw-bold">★</span>');
                        }

                        $item.on('mouseenter', function() {
                            highlightDischargeMedNameItem(idx);
                        });
                        $item.on('mouseleave', function() {
                            $(this).css('background', '');
                        });
                        $item.on('mousedown', function(e) { e.preventDefault(); });
                        $item.on('click', function() {
                            applyDischargeMedicineMatch(row);
                            $dd.hide().empty();
                            dischargeMedHighlightIdx = -1;
                        });

                        $dd.append($item);
                    });

                    $dd.show();
                }

                if (dischargeMedInput) {
                    $('#discharge_med_name').on('input', function() {
                        if (isSelectingDischargeMed) {
                            return;
                        }
                        var q = ($(this).val() || '').trim();
                        if (dischargeMedInputTimer) {
                            clearTimeout(dischargeMedInputTimer);
                            dischargeMedInputTimer = null;
                        }
                        if (q.length < 1) {
                            dischargeMedSuggestRows = [];
                            $('#discharge_med_name_dd').hide().empty();
                            dischargeMedHighlightIdx = -1;
                            return;
                        }
                        dischargeMedInputTimer = setTimeout(function() {
                            $.get('<?= base_url('Opd_prescription/medicine_search') ?>?q=' + encodeURIComponent(q) + '&scope=all&limit=15', function(data) {
                                var rows = (data && data.rows) ? data.rows : [];
                                renderDischargeMedNameDropdown(rows);
                            }, 'json').fail(function() {
                                dischargeMedSuggestRows = [];
                                $('#discharge_med_name_dd').hide().empty();
                            });
                        }, 120);
                    }).on('focus', function() {
                        if (isSelectingDischargeMed) {
                            return;
                        }
                    }).on('blur', function() {
                        setTimeout(function() { $('#discharge_med_name_dd').hide(); dischargeMedHighlightIdx = -1; }, 200);
                    }).on('keydown', function(e) {
                        var $dd = $('#discharge_med_name_dd');
                        var isVisible = $dd.is(':visible') && dischargeMedSuggestRows.length > 0;
                        var $items = $('#discharge_med_name_dd .discharge-med-name-dd-item');

                        if (e.key === 'ArrowDown') {
                            if (!isVisible) {
                                var q = ($(this).val() || '').trim();
                                if (q.length >= 1) {
                                    $.get('<?= base_url('Opd_prescription/medicine_search') ?>?q=' + encodeURIComponent(q) + '&scope=all&limit=15', function(data) {
                                        var rows = (data && data.rows) ? data.rows : [];
                                        renderDischargeMedNameDropdown(rows);
                                        highlightDischargeMedNameItem(0);
                                    }, 'json');
                                }
                                return;
                            }
                            e.preventDefault();
                            var nextIdx = dischargeMedHighlightIdx + 1;
                            if (nextIdx >= $items.length) nextIdx = 0;
                            highlightDischargeMedNameItem(nextIdx);
                        } else if (e.key === 'ArrowUp') {
                            if (!isVisible) return;
                            e.preventDefault();
                            var prevIdx = dischargeMedHighlightIdx - 1;
                            if (prevIdx < 0) prevIdx = $items.length - 1;
                            highlightDischargeMedNameItem(prevIdx);
                        } else if (e.key === 'Enter') {
                            if (isVisible && dischargeMedHighlightIdx >= 0 && dischargeMedSuggestRows[dischargeMedHighlightIdx]) {
                                e.preventDefault();
                                applyDischargeMedicineMatch(dischargeMedSuggestRows[dischargeMedHighlightIdx]);
                                $dd.hide().empty();
                                dischargeMedHighlightIdx = -1;
                                var nextTarget = section.querySelector('#discharge_med_type') || section.querySelector('#discharge_dosage');
                                if (nextTarget) nextTarget.focus();
                            }
                        } else if (e.key === 'Escape') {
                            $dd.hide();
                        }
                    });
                }

                // ─── Load Medicine Substitutes ──────────────────────────────────────
                function loadDischargeMedicineSubstitutes(medId, medName) {
                    medId = parseInt(medId || '0', 10);
                    medName = (medName || '').toString().trim();
                    var $box = $('#discharge_substitute_box');
                    var $note = $('#discharge_substitute_note');
                    var $empty = $('#discharge_substitute_empty');
                    var $rows = $('#discharge_substitute_rows');

                    if (medId <= 0) {
                        $box.hide();
                        return;
                    }

                    $note.text('');
                    $empty.hide();
                    $rows.empty();
                    $box.hide();

                    var url = '<?= base_url('Opd_prescription/medicine_substitutes') ?>?med_id=' + encodeURIComponent(medId) +
                        '&med_name=' + encodeURIComponent(medName);

                    $.get(url, function(data) {
                        var rows = (data && data.rows) ? data.rows : [];

                        if (!rows.length) {
                            $box.hide();
                            $empty.hide();
                            $rows.empty();
                            return;
                        }

                        $note.text(rows.length + ' substitute(s)');
                        $empty.hide();
                        $rows.empty();
                        $box.show();

                        rows.forEach(function(row) {
                            var medId = parseInt(row.id || 0, 10);
                            var name = String(row.med_name || '').trim();
                            var type = String(row.med_type || '').trim();
                            var generic = String(row.genericname || row.salt_name || '').trim();

                            if (name === '') {
                                return;
                            }

                            var card = '<div class=\"card mb-1\" style=\"font-size:0.875rem;\">' +
                                '<div class=\"card-body py-1 px-2\">' +
                                '<div class=\"d-flex justify-content-between align-items-center\">' +
                                '<div><strong>' + $('<div>').text(name).html() + '</strong>' +
                                (type ? ' <small class=\"text-muted\">(' + $('<div>').text(type).html() + ')</small>' : '') +
                                (generic ? '<br><small class=\"text-muted\">' + $('<div>').text(generic).html() + '</small>' : '') +
                                '</div>' +
                                '<div class=\"btn-group btn-group-sm\">' +
                                '<button type=\"button\" class=\"btn btn-sm btn-outline-primary btn-discharge-substitute-use\" ' +
                                'data-id=\"' + medId + '\" ' +
                                'data-name=\"' + $('<div>').text(name).html() + '\" ' +
                                'data-type=\"' + $('<div>').text(type).html() + '\">Use</button>' +
                                '</div></div></div></div>';

                            $rows.append(card);
                        });
                    }, 'json').fail(function() {
                        $note.text('');
                        $empty.show();
                        $rows.empty();
                    });
                }

                // Handle substitute "Use" button
                $(document).on('click', '.btn-discharge-substitute-use', function() {
                    var medId = parseInt($(this).data('id') || '0', 10);
                    var medName = String($(this).data('name') || '').trim();
                    var medType = String($(this).data('type') || '').trim();

                    if (medName === '') {
                        return;
                    }

                    $('#discharge_med_id').val(medId);
                    $('#discharge_med_name').val(medName);
                    if (medType !== '' && $('#discharge_med_type').val().trim() === '') {
                        $('#discharge_med_type').val(medType);
                    }

                    $('#discharge_substitute_box').hide();
                    $('#discharge_med_name').trigger('focus');
                });

                // Load substitutes when medicine is selected
                dischargeMedInput.addEventListener('change', function() {
                    var medId = parseInt($('#discharge_med_id').val() || '0', 10);
                    var medName = String(dischargeMedInput.value || '').trim();

                    if (medId > 0 || medName !== '') {
                        setTimeout(function() {
                            loadDischargeMedicineSubstitutes(medId, medName);
                        }, 100);
                    }
                });

                // ─── Rx-Group Functionality ──────────────────────────────────────
                var dischargeRxGroupCache = [];

                $('#btn_discharge_rx_group').on('click', function() {
                    loadDischargeRxGroups();
                    var modal = new bootstrap.Modal(document.getElementById('dischargeRxGroupModal'));
                    modal.show();
                });

                function loadDischargeRxGroups() {
                    $('#discharge_rx_group_list').html('<div class=\"text-muted\">Loading...</div>');

                    $.get('<?= base_url('Opd_prescription/rx_group_list') ?>', function(data) {
                        var rows = (data && data.rows) ? data.rows : [];
                        dischargeRxGroupCache = rows;
                        renderDischargeRxGroups(rows);
                    }, 'json').fail(function() {
                        $('#discharge_rx_group_list').html('<div class=\"text-danger\">Failed to load Rx-Groups</div>');
                    });
                }

                function renderDischargeRxGroups(rows) {
                    var $list = $('#discharge_rx_group_list');
                    var query = ($('#discharge_rx_group_search').val() || '').toLowerCase();

                    if (query) {
                        rows = rows.filter(function(row) {
                            var name = (row.rx_group_name || '').toLowerCase();
                            return name.indexOf(query) !== -1;
                        });
                    }

                    if (!rows.length) {
                        $list.html('<div class=\"text-muted\">No Rx-Groups found</div>');
                        return;
                    }

                    var html = '';
                    rows.forEach(function(row) {
                        var id = parseInt(row.id || 0, 10);
                        var name = String(row.rx_group_name || '').trim();
                        var medCount = parseInt(row.med_count || 0, 10);

                        if (id <= 0 || name === '') {
                            return;
                        }

                        html += '<div class=\"card mb-2\">' +
                            '<div class=\"card-body py-2\">' +
                            '<div class=\"d-flex justify-content-between align-items-center\">' +
                            '<div>' +
                            '<strong>' + $('<div>').text(name).html() + '</strong>' +
                            '<span class=\"badge bg-secondary ms-2\">' + medCount + ' med(s)</span>' +
                            '</div>' +
                            '<button type=\"button\" class=\"btn btn-sm btn-primary btn-discharge-rx-apply\" data-id=\"' + id + '\">Add</button>' +
                            '</div></div></div>';
                    });

                    $list.html(html);
                }

                $('#discharge_rx_group_search').on('input', function() {
                    renderDischargeRxGroups(dischargeRxGroupCache);
                });

                $(document).on('click', '.btn-discharge-rx-apply', function() {
                    var rxGroupId = parseInt($(this).data('id') || 0, 10);
                    if (rxGroupId <= 0) {
                        return;
                    }

                    $(this).prop('disabled', true).text('Loading...');

                    $.get('<?= base_url('Opd_prescription/rx_group_medicine_list') ?>/' + rxGroupId, function(data) {
                        var rows = (data && data.rows) ? data.rows : [];

                        if (!rows.length) {
                            setMedicineStatus('No medicines found in selected Rx-Group', 'error');
                            $('.btn-discharge-rx-apply').prop('disabled', false).text('Add');
                            return;
                        }

                        // Add each medicine from the group
                        rows.forEach(function(med) {
                            var dosageLabel = med.dosage_label || med.dosage || '';
                            var whenLabel = med.dosage_when_label || med.dosage_when || '';
                            var freqLabel = med.dosage_freq_label || med.dosage_freq || '';

                            var tbody = section.querySelector('#discharge_medicine_tbody');
                            if (!tbody) {
                                return;
                            }

                            // Remove "No medicine added" row if present
                            var emptyRow = tbody.querySelector('tr td[colspan=\"9\"]');
                            if (emptyRow) {
                                emptyRow.closest('tr').remove();
                            }

                            var tr = document.createElement('tr');
                            tr.innerHTML = '<td>' + $('<div>').text(med.med_type || '').html() + '</td>' +
                                '<td>' + $('<div>').text(med.med_name || '').html() + '</td>' +
                                '<td>' + $('<div>').text(dosageLabel).html() + '</td>' +
                                '<td>' + $('<div>').text(whenLabel).html() + '</td>' +
                                '<td>' + $('<div>').text(freqLabel).html() + '</td>' +
                                '<td>' + $('<div>').text(med.no_of_days || '').html() + '</td>' +
                                '<td>' + $('<div>').text(med.qty || '').html() + '</td>' +
                                '<td>' + $('<div>').text(med.remark || '').html() + '</td>' +
                                '<td><button type=\"button\" class=\"btn btn-outline-danger btn-sm btn-remove-discharge-med\">Remove</button></td>';
                            tbody.appendChild(tr);
                        });

                        setMedicineStatus('Rx-Group medicines added (client-side only, save form to persist)', 'success');
                        $('.btn-discharge-rx-apply').prop('disabled', false).text('Add');
                        bootstrap.Modal.getInstance(document.getElementById('dischargeRxGroupModal')).hide();
                    }, 'json').fail(function() {
                        setMedicineStatus('Failed to load Rx-Group medicines', 'error');
                        $('.btn-discharge-rx-apply').prop('disabled', false).text('Add');
                    });
                });

                function ensureDoseOption(select, value) {
                    value = (value || '').toString().trim();
                    if (!value) {
                        return;
                    }

                    var exists = false;
                    $(select).find('option').each(function() {
                        if ($(this).val() === value) {
                            exists = true;
                            return false;
                        }
                    });

                    if (!exists) {
                        $(select).append('<option value="' + $('<div>').text(value).html() + '">' + $('<div>').text(value + ' (Current)').html() + '</option>');
                    }
                }

                function resetMedicineFormState() {
                    $('#discharge_med_item_id').val('0');
                    $('#discharge_med_item_source').val('legacy');
                    $('#btn_discharge_med_add').text('+ADD / Update');
                }

                function medicineActionButtonsHtml(rowId, rowSource) {
                    var id = parseInt(rowId || 0, 10);
                    var source = String(rowSource || 'legacy').trim() || 'legacy';
                    return '<td class="d-flex gap-1">'
                        + '<button type="button" class="btn btn-outline-primary btn-sm btn-edit-discharge-med" data-id="' + id + '" data-source="' + $('<div>').text(source).html() + '">Edit</button>'
                        + '<button type="submit" class="btn btn-outline-danger btn-sm" name="action" value="remove_drug" data-reload-section="section-medicine" onclick="document.getElementById(\'drug_remove_id\').value=\'' + id + '\';document.getElementById(\'drug_remove_source\').value=\'' + $('<div>').text(source).html() + '\';">Remove</button>'
                        + '</td>';
                }

                $(document).on('click', '.btn-edit-discharge-med', function() {
                    var row = $(this).closest('tr');
                    if (!row.length) {
                        return;
                    }

                    var rowId = parseInt($(this).data('id') || row.data('row-id') || 0, 10);
                    var rowSource = String($(this).data('source') || row.data('row-source') || 'legacy').trim() || 'legacy';

                    var medName = String($(this).data('med-name') || row.find('td:eq(1)').text() || '').trim();
                    var medSalt = String($(this).data('med-salt') || '').trim();
                    var medType = String($(this).data('med-type') || row.find('td:eq(0)').text() || '').trim();
                    var doseId = String($(this).data('dose-id') || '').trim();
                    var whenId = String($(this).data('dose-when-id') || '').trim();
                    var freqId = String($(this).data('dose-freq-id') || '').trim();
                    var doseLabel = String($(this).data('dose-label') || row.find('td:eq(2)').text() || '').trim();
                    var whenLabel = String($(this).data('dose-when-label') || row.find('td:eq(3)').text() || '').trim();
                    var freqLabel = String($(this).data('dose-freq-label') || row.find('td:eq(4)').text() || '').trim();
                    var noOfDays = String($(this).data('days') || row.find('td:eq(5)').text() || '').trim();
                    var qty = String($(this).data('qty') || row.find('td:eq(6)').text() || '').trim();
                    var remark = String($(this).data('remark') || row.find('td:eq(7)').text() || '').trim();

                    $('#discharge_med_item_id').val(rowId > 0 ? rowId : 0);
                    $('#discharge_med_item_source').val(rowSource);
                    $('#discharge_med_name').val(medName);
                    $('#discharge_med_salt').val(medSalt);
                    $('#discharge_med_type').val(medType);

                    $('#discharge_dosage').val(doseLabel || doseId || '');

                    if (whenId !== '' && whenId !== '0') {
                        $('#discharge_dosage_when').val(whenId);
                    } else {
                        ensureDoseOption('#discharge_dosage_when', whenLabel);
                        $('#discharge_dosage_when').val(whenLabel);
                    }

                    $('#discharge_dosage_freq').val(freqLabel || freqId || '');

                    $('#discharge_no_of_days').val(noOfDays);
                    $('#discharge_qty').val(qty);
                    $('#discharge_remark').val(remark);

                    $('#btn_discharge_med_add').text('Update Medicine');
                    setMedicineStatus('Edit mode: update fields and click Update Medicine.', 'info');
                    $('#discharge_med_name').trigger('focus');
                });

                $(document).off('click', '.btn-remove-discharge-med').on('click', '.btn-remove-discharge-med', function(ev) {
                    if (ev) ev.preventDefault();
                    var $btn = $(this);
                    var $tr = $btn.closest('tr');

                    // Extract IDs before removing from DOM
                    var rowId = parseInt($btn.attr('data-id') || $btn.data('id') || $tr.attr('data-row-id') || $tr.data('row-id') || 0, 10);
                    var rowSource = String($btn.attr('data-source') || $btn.data('source') || $tr.attr('data-row-source') || $tr.data('row-source') || 'legacy').trim() || 'legacy';

                    // Instantly remove row from DOM
                    if ($tr.length) {
                        $tr.remove();
                    }

                    var tbody = document.getElementById('discharge_medicine_tbody');
                    if (tbody && !tbody.querySelector('tr')) {
                        tbody.innerHTML = '<tr><td colspan="8" class="text-muted text-center py-2">No medicine added</td></tr>';
                    }

                    var secEl = document.getElementById('section-medicine');
                    var activeForm = (secEl && secEl.closest('form')) || getDischargeForm();
                    if (!activeForm) {
                        setMedicineStatus('Medicine removed.', 'info');
                        return;
                    }

                    var csrf = getCsrfPair(activeForm);
                    var payload = {
                        action: 'remove_drug',
                        ajax_mode: 'json',
                        drug_remove_id: rowId,
                        drug_remove_source: rowSource
                    };
                    payload[csrf.name] = csrf.value;

                    setMedicineStatus('Removing medicine...', 'muted');

                    if (rowId > 0) {
                        $.post($(activeForm).attr('action') || window.location.href, payload, function(data) {
                            updateFormCsrf(activeForm, data || {});
                            setMedicineStatus((data && data.notice) ? data.notice : 'Medicine removed successfully.', 'success');
                        }, 'json').fail(function() {
                            setMedicineStatus('Medicine removed.', 'info');
                        });
                    } else {
                        setMedicineStatus('Medicine removed.', 'info');
                    }
                });

                // Handle quick buttons
                section.querySelectorAll('.discharge-quick-btn').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var targetId = String(btn.getAttribute('data-fill-target') || '').trim();
                        var value = String(btn.getAttribute('data-fill-value') || '').trim();
                        var target = targetId ? section.querySelector('#' + targetId) : null;
                        if (target) {
                            if (target.tagName.toLowerCase() === 'select') {
                                // For select elements, set the value or add it if needed
                                target.value = value;
                            } else {
                                target.value = value;
                            }
                            target.focus();
                        }
                    });
                });

                // Handle Add/Update button
                var btnAddMed = section.querySelector('#btn_discharge_med_add');
                if (btnAddMed && btnAddMed.dataset.bound !== '1') {
                    btnAddMed.dataset.bound = '1';
                    btnAddMed.addEventListener('click', function(ev) {
                        if (ev) ev.preventDefault();
                        var medName = $('#discharge_med_name').val().trim();
                        var medSalt = $('#discharge_med_salt').val().trim();
                        var medType = $('#discharge_med_type').val().trim();
                        var dosage = $('#discharge_dosage').val();
                        var dosageWhen = $('#discharge_dosage_when').val();
                        var dosageFreq = $('#discharge_dosage_freq').val();
                        var noOfDays = $('#discharge_no_of_days').val().trim();
                        var qty = $('#discharge_qty').val().trim();
                        var doseWhere = $('#discharge_dose_where').val();
                        var remark = $('#discharge_remark').val().trim();
                        var editRowId = parseInt($('#discharge_med_item_id').val() || '0', 10);
                        var editRowSource = String($('#discharge_med_item_source').val() || 'legacy').trim() || 'legacy';

                        if (medName === '') {
                            setMedicineStatus('Please enter medicine name.', 'error');
                            $('#discharge_med_name').focus();
                            return;
                        }

                        // Get dose labels from cache
                        var doseLabel = dosage;
                        if (dosage) {
                            var doseRow = doseMasterCache.dose.find(function(r) {
                                return String(r.id) === dosage;
                            });
                            if (doseRow) doseLabel = doseRow.label;
                        }

                        var whenLabel = dosageWhen;
                        if (dosageWhen) {
                            var whenRow = doseMasterCache.when.find(function(r) {
                                return String(r.id) === dosageWhen;
                            });
                            if (whenRow) whenLabel = whenRow.label;
                        }

                        var freqLabel = dosageFreq;
                        if (dosageFreq) {
                            var freqRow = doseMasterCache.freq.find(function(r) {
                                return String(r.id) === dosageFreq;
                            });
                            if (freqRow) freqLabel = freqRow.label;
                        }

                        // Format duration (e.g., "5" -> "5 days", "1 month" -> "1 month")
                        var formattedDuration = noOfDays;
                        if (noOfDays && /^\d+$/.test(noOfDays.trim())) {
                            var num = parseInt(noOfDays.trim(), 10);
                            formattedDuration = num + (num === 1 ? ' day' : ' days');
                        }

                        // AJAX auto-save medicine
                        if (!window.jQuery) {
                            setMedicineStatus('jQuery not available. Cannot save medicine.', 'error');
                            return;
                        }

                        var activeForm = section.closest('form') || getDischargeForm();
                        if (!activeForm) {
                            setMedicineStatus('Form not found.', 'error');
                            return;
                        }

                        var csrf = getCsrfPair(activeForm);
                        var payload = {
                            action: 'add_drug',
                            ajax_mode: 'json',
                            drug_edit_id: editRowId,
                            drug_edit_source: editRowSource,
                            new_drug_name: medName,
                            new_drug_salt: medSalt,
                            new_drug_type: medType,
                            new_drug_dose: dosage,
                            new_drug_when: dosageWhen,
                            new_drug_freq: dosageFreq,
                            new_drug_day: noOfDays,
                            new_drug_qty: qty,
                            new_drug_remark: remark
                        };
                        payload[csrf.name] = csrf.value;

                        setMedicineStatus('Saving medicine...', 'muted');

                        window.jQuery.ajax({
                            url: activeForm.getAttribute('action') || window.location.href,
                            type: 'POST',
                            data: payload,
                            dataType: 'json',
                            timeout: 30000
                        }).done(function(data) {
                            updateFormCsrf(activeForm, data || {});

                            var tbody = section.querySelector('#discharge_medicine_tbody');
                            if (!tbody) {
                                setMedicineStatus('Medicine table not found.', 'error');
                                return;
                            }

                            // Remove "No medicine added" row if present
                            var emptyRow = tbody.querySelector('tr td[colspan="8"]');
                            if (emptyRow) {
                                emptyRow.closest('tr').remove();
                            }

                            var responseRowId = parseInt((data && data.row_id) ? data.row_id : 0, 10);
                            var responseRowSource = String((data && data.row_source) ? data.row_source : (editRowSource || 'legacy')).trim() || 'legacy';
                            var rowIdToUse = responseRowId > 0 ? responseRowId : editRowId;

                            var rowHtml = '<td>' + $('<div>').text(medType).html() + '</td>' +
                                '<td>' + $('<div>').text(medName).html() + '</td>' +
                                '<td>' + $('<div>').text(doseLabel).html() + '</td>' +
                                '<td>' + $('<div>').text(whenLabel).html() + '</td>' +
                                '<td>' + $('<div>').text(freqLabel).html() + '</td>' +
                                '<td>' + $('<div>').text(formattedDuration).html() + '</td>' +
                                '<td>' + $('<div>').text(remark).html() + '</td>' +
                                medicineActionButtonsHtml(rowIdToUse, responseRowSource);

                            if (editRowId > 0) {
                                var existingRow = tbody.querySelector('tr[data-row-id="' + editRowId + '"][data-row-source="' + editRowSource + '"]');
                                if (existingRow) {
                                    existingRow.innerHTML = rowHtml;
                                    existingRow.setAttribute('data-row-id', String(rowIdToUse));
                                    existingRow.setAttribute('data-row-source', responseRowSource);
                                } else {
                                    var updatedRow = document.createElement('tr');
                                    updatedRow.setAttribute('data-row-id', String(rowIdToUse));
                                    updatedRow.setAttribute('data-row-source', responseRowSource);
                                    updatedRow.innerHTML = rowHtml;
                                    tbody.appendChild(updatedRow);
                                }
                            } else {
                                var tr = document.createElement('tr');
                                tr.setAttribute('data-row-id', String(rowIdToUse));
                                tr.setAttribute('data-row-source', responseRowSource);
                                tr.innerHTML = rowHtml;
                                tbody.appendChild(tr);
                            }

                            // Keep fresh values on edit button for repeated edits without reload
                            var targetRow = tbody.querySelector('tr[data-row-id="' + rowIdToUse + '"][data-row-source="' + responseRowSource + '"]');
                            if (targetRow) {
                                var editBtn = targetRow.querySelector('.btn-edit-discharge-med');
                                if (editBtn) {
                                    editBtn.setAttribute('data-med-name', medName);
                                    editBtn.setAttribute('data-med-salt', medSalt);
                                    editBtn.setAttribute('data-med-type', medType);
                                    editBtn.setAttribute('data-dose-id', dosage || '');
                                    editBtn.setAttribute('data-dose-when-id', dosageWhen || '');
                                    editBtn.setAttribute('data-dose-freq-id', dosageFreq || '');
                                    editBtn.setAttribute('data-dose-label', doseLabel || '');
                                    editBtn.setAttribute('data-dose-when-label', whenLabel || '');
                                    editBtn.setAttribute('data-dose-freq-label', freqLabel || '');
                                    editBtn.setAttribute('data-days', noOfDays || '');
                                    editBtn.setAttribute('data-qty', qty || '');
                                    editBtn.setAttribute('data-remark', remark || '');
                                }
                            }

                            // Clear form
                            $('#discharge_med_name, #discharge_med_salt, #discharge_med_type, #discharge_no_of_days, #discharge_qty, #discharge_remark').val('');
                            $('#discharge_dosage, #discharge_dosage_when, #discharge_dosage_freq, #discharge_dose_where').val('');
                            resetMedicineFormState();
                            $('#discharge_med_name').focus();

                            serializeDischargeMedicineTable();
                            setMedicineStatus(editRowId > 0 ? 'Medicine updated successfully.' : 'Medicine saved successfully.', 'success');
                        }).fail(function(xhr, status, error) {
                            var responseHtml = xhr && typeof xhr.responseText === 'string' ? xhr.responseText : '';
                            if (responseHtml !== '' && responseHtml.indexOf('section-medicine') !== -1) {
                                var holder = document.createElement('div');
                                holder.innerHTML = responseHtml;
                                if (patchSectionFromHtml(holder, 'section-medicine')) {
                                    patchNoticeFromHtml(holder);
                                    initMedicineTools();
                                    bindDischargeAjaxSubmit();
                                    syncNavOnScroll();
                                    serializeDischargeMedicineTable();
                                    setMedicineStatus('Medicine saved. Section refreshed from server response.', 'success');
                                    return;
                                }
                            }

                            serializeDischargeMedicineTable();
                            setMedicineStatus('Medicine added to list. Click Save Discharge Summary to finalize.', 'info');
                        });
                    });
                }

                // Handle Reset button
                var btnResetMed = section.querySelector('#btn_discharge_med_reset');
                if (btnResetMed && btnResetMed.dataset.bound !== '1') {
                    btnResetMed.dataset.bound = '1';
                    btnResetMed.addEventListener('click', function() {
                        if (confirm('Remove all medicines from the list?')) {
                            var tbody = section.querySelector('#discharge_medicine_tbody');
                            if (tbody) {
                                tbody.innerHTML = '<tr><td colspan="8" class="text-muted text-center">No medicine added</td></tr>';
                            }
                            resetMedicineFormState();
                            setMedicineStatus('All medicines removed.', 'info');
                        }
                    });
                }
            }

            function serializeDischargeMedicineTable() {
                var tbody = document.querySelector('#discharge_medicine_tbody');
                var hiddenField = document.querySelector('#discharge_medicine_json');
                if (!tbody || !hiddenField) {
                    return;
                }

                var rows = tbody.querySelectorAll('tr');
                var medicines = [];

                rows.forEach(function(row) {
                    var cells = row.querySelectorAll('td');
                    // Skip empty state row or rows with insufficient cells
                    if (cells.length >= 8) {
                        var cellData = {
                            med_type: cells[0].textContent.trim(),
                            med_name: cells[1].textContent.trim(),
                            dosage: cells[2].textContent.trim(),
                            dosage_when: cells[3].textContent.trim(),
                            dosage_freq: cells[4].textContent.trim(),
                            no_of_days: cells[5].textContent.trim(),
                            qty: cells[6].textContent.trim(),
                            remark: cells[7].textContent.trim()
                        };

                        // Only add non-empty medicine names
                        if (cellData.med_name !== '' && cellData.med_name !== 'No medicine added') {
                            medicines.push(cellData);
                        }
                    }
                });

                hiddenField.value = JSON.stringify(medicines);
            }

            function syncEditorValues() {
                syncDischargeComplaintJsonField();
                serializeDischargeMedicineTable();

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
            initSurgeryProcedureAutocomplete();
            initSurgeryTools(getDischargeForm());
            initCourseAutocomplete();
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
                    var medicineNameInput = form.querySelector('#discharge_med_name');
                    if (medicineNameInput && medicineNameInput.dataset.suppressImplicitSubmit === '1') {
                        delete medicineNameInput.dataset.suppressImplicitSubmit;
                        return;
                    }
                    syncEditorValues();
                    // DISABLED: No longer needed since medicines are saved via AJAX on Add button
                    // serializeDischargeMedicineTable();
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

                    if (actionValue === 'add_procedure') {
                        var procedureDateInput = form.querySelector('[name="new_procedure_date"]');
                        if (!procedureDateInput || String(procedureDateInput.value || '').trim() === '') {
                            setSectionStatus('discharge_surgery_status', 'Select procedure date before adding.', 'error');
                            if (procedureDateInput) {
                                procedureDateInput.focus();
                            }
                            return;
                        }
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
                'section-history-risk',
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
                var isScrollable = (style.overflowY === 'auto' || style.overflowY === 'scroll') &&
                    container.scrollHeight > container.clientHeight;
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
                        container.scrollTo({
                            top: Math.max(nextTop, 0),
                            behavior: 'smooth'
                        });
                    } else {
                        section.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
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

            window.addEventListener('scroll', syncNavOnScroll, {
                passive: true
            });
            document.addEventListener('scroll', function(evt) {
                var target = evt.target;
                if (target && target.classList && target.classList.contains('discharge-form-area')) {
                    syncNavOnScroll();
                }
            }, {
                passive: true,
                capture: true
            });
            syncNavOnScroll();

        })();
    </script>
</section>