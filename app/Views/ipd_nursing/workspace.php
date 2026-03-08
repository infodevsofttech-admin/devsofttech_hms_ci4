<?php
$ipd = $ipd_info ?? null;
$person = $person_info ?? null;
$entries = $nursing_entries ?? [];
$ipdId = (int) ($ipd->id ?? 0);
$saveUrl = site_url('ipd/patient/nursing/save/' . $ipdId);
$historySaveUrl = site_url('ipd/patient/history/save/' . $ipdId);
$scanExtractUrl = site_url('ipd/patient/nursing/scan/' . $ipdId);
$scanSaveUrl = site_url('ipd/patient/nursing/scan-save/' . $ipdId);
$reloadUrl = site_url('ipd/patient/workspace/' . $ipdId);
$printUrl = site_url('ipd/patient/nursing/print/' . $ipdId);
$dischargeSummaryUrl = site_url('Ipd_discharge/preview_discharge_report/' . $ipdId);
$dischargeSummaryCreateUrl = site_url('Ipd_discharge/ipd_select/' . $ipdId);
$bedsideChargeUrl = site_url('billing/ipd/bedside-charge/add/' . $ipdId);
$doctorVisitChargeUrl = site_url('billing/ipd/doctor-visit/add/' . $ipdId);
$today = date('Y-m-d');
$csrfName = csrf_token();
$csrfHash = csrf_hash();
$bedsideItemsByCategory = $bedside_items_by_category ?? [];
$doctorVisitFeeTypes = $doctor_visit_fee_types ?? [];
$doctorVisitFeeMap = $doctor_visit_fee_map ?? [];
$docList = $doc_list ?? [];
$nursingChargeHistory = $nursing_charge_history ?? [];
$historyFields = $history_fields ?? [];
$patientHistoryRow = $patient_history_row ?? [];
$opdHistorySnapshot = $opd_history_snapshot ?? [];
$isFemalePatient = (bool) ($is_female_patient ?? false);
$coMorbidityOptions = ['Hypertension', 'DM', 'COPD', 'Asthma', 'IHD', 'CKD', 'Hypothyroidism'];
$selectedCoMorbidities = array_filter(array_map('trim', explode(',', (string) ($opdHistorySnapshot['co_morbidities'] ?? ''))), static fn ($v) => $v !== '');
$otherCoMorbidities = array_values(array_filter($selectedCoMorbidities, static fn ($item) => ! in_array($item, $coMorbidityOptions, true)));
$toFahrenheit = static function ($temperatureC): string {
    if ($temperatureC === null || $temperatureC === '') {
        return '';
    }

    $value = (((float) $temperatureC) * 9 / 5) + 32;

    return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
};
$nurseNames = [];
$scannedAuditRows = [];
foreach ($entries as $entryRow) {
    $name = trim((string) ($entryRow['recorded_by'] ?? ''));
    if ($name === '') {
        continue;
    }
    $nurseNames[$name] = $name;

    $note = trim((string) ($entryRow['general_note'] ?? ''));
    if (stripos($note, 'Source: Scanned Paper') !== false) {
        $scanRef = '';
        $confidence = '';
        $corrected = '';
        if (preg_match('/ScanRef\s*:\s*([^\]|]+)/i', $note, $m) === 1) {
            $scanRef = trim((string) ($m[1] ?? ''));
        }
        if (preg_match('/AIConfidence\s*:\s*([^\]|]+)/i', $note, $m) === 1) {
            $confidence = trim((string) ($m[1] ?? ''));
        }
        if (preg_match('/Corrected\s*:\s*([^\]|]+)/i', $note, $m) === 1) {
            $corrected = trim((string) ($m[1] ?? ''));
        }

        $scannedAuditRows[] = [
            'entry_id' => (int) ($entryRow['id'] ?? 0),
            'recorded_at' => (string) ($entryRow['recorded_at'] ?? ''),
            'entry_type' => (string) ($entryRow['entry_type'] ?? ''),
            'recorded_by' => (string) ($entryRow['recorded_by'] ?? ''),
            'scan_ref' => $scanRef,
            'confidence' => $confidence,
            'corrected' => $corrected,
            'note' => $note,
        ];
    }
}
ksort($nurseNames);
?>
<style>
    .nursing-workspace-page #nursing_panel_content {
        margin-top: 12px;
    }

    .nursing-workspace-page .card {
        border: 1px solid #e6edf5;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
    }

    .nursing-workspace-page #nursing_entry_tabs {
        gap: 0.35rem;
        border-bottom: 1px solid #e6edf5;
        padding: 0.65rem 0.85rem 0 !important;
        background: #f8f9fc;
    }

    .nursing-workspace-page #nursing_entry_tabs .nav-link {
        border: 1px solid transparent;
        border-radius: 0.45rem 0.45rem 0 0;
        color: #4154f1;
        font-weight: 500;
        padding: 0.5rem 0.85rem;
        margin-bottom: -1px;
    }

    .nursing-workspace-page #nursing_entry_tabs .nav-link:hover {
        background: #eef2ff;
        border-color: #dbe2f3;
    }

    .nursing-workspace-page #nursing_entry_tabs .nav-link.active {
        background: #fff;
        border-color: #e6edf5 #e6edf5 #fff;
        color: #012970;
    }

    .nursing-workspace-page .nursing-tab-pane {
        margin-top: 0.85rem;
        padding: 0.25rem 0.45rem 0.75rem;
    }

    .nursing-workspace-page .nursing-tab-pane .card,
    .nursing-workspace-page .card .card {
        margin-top: 0.9rem;
    }

    .nursing-workspace-page .form-label {
        margin-bottom: 0.35rem;
    }

    .nursing-workspace-page .table-responsive {
        margin-top: 0.35rem;
    }

    @media (max-width: 991.98px) {
        .nursing-workspace-page #nursing_entry_tabs {
            overflow-x: auto;
            flex-wrap: nowrap;
            white-space: nowrap;
        }

        .nursing-workspace-page #nursing_entry_tabs .nav-item {
            flex: 0 0 auto;
        }
    }
</style>

<section class="content nursing-workspace-page">
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Nursing Workspace</h5>
            <div class="d-flex gap-2">
                <button
                    type="button"
                    class="btn btn-outline-primary btn-sm"
                    onclick="load_form('<?= esc($dischargeSummaryCreateUrl) ?>','Create Discharge Summary');"
                >
                    Create Discharge Summary
                </button>
                <button
                    type="button"
                    class="btn btn-outline-info btn-sm"
                    onclick="load_form('<?= esc($dischargeSummaryUrl) ?>','Discharge Summary');"
                >
                    Preview Discharge Summary
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="load_form('<?= base_url('ipd/patient') ?>','IPD Patient List');">Back to IPD Patient List</button>
            </div>
        </div>
        <div class="card-body">
            <p class="mb-1">
                <strong>IPD:</strong> <?= esc((string) ($ipd->ipd_code ?? '')) ?> |
                <strong>Patient:</strong> <?= esc((string) ($person->p_fname ?? '')) ?> |
                <strong>UHID:</strong> <?= esc((string) ($person->p_code ?? '')) ?>
            </p>
        </div>
    </div>

    <div id="nursing_panel_content">
        <div class="card mb-3">
            <div class="card-header p-0">
                <ul class="nav nav-tabs card-header-tabs px-2 pt-2" id="nursing_entry_tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link" data-nursing-tab="nursing_tab_admission">Admission Snapshot</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link" data-nursing-tab="nursing_tab_history">History &amp; Physical Assessment</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link active" data-nursing-tab="nursing_tab_vitals">Vitals</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link" data-nursing-tab="nursing_tab_fluid">Fluid Intake / Output</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link" data-nursing-tab="nursing_tab_treatment">Treatment / Procedure Note</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link" data-nursing-tab="nursing_tab_scan">Scan Paper -> Auto Fill</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link" data-nursing-tab="nursing_tab_charge">Charge Entry</button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="nursing-tab-pane d-none" id="nursing_tab_admission">
                    <form class="row g-2 nursing-form" data-save-url="<?= esc($saveUrl) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="entry_type" value="admission">
                        <div class="col-md-3"><label class="form-label">Recorded At</label><input type="datetime-local" class="form-control" name="recorded_at" value="<?= date('Y-m-d\\TH:i') ?>"></div>
                        <div class="col-md-2"><label class="form-label">Shift</label><input type="text" class="form-control nursing-shift-display" value="Morning" readonly></div>
                        <div class="col-md-12"><label class="form-label">Admission Complaints / Reason</label><textarea class="form-control" name="treatment_text" rows="3" placeholder="Chief complaints and reason for admission"></textarea></div>
                        <div class="col-md-1"><label class="form-label">Temp °F</label><input type="number" step="0.1" class="form-control" name="temperature_f"></div>
                        <div class="col-md-1"><label class="form-label">Pulse</label><input type="number" class="form-control" name="pulse_rate"></div>
                        <div class="col-md-1"><label class="form-label">Resp</label><input type="number" class="form-control" name="resp_rate"></div>
                        <div class="col-md-1"><label class="form-label">SBP</label><input type="number" class="form-control" name="bp_systolic"></div>
                        <div class="col-md-1"><label class="form-label">DBP</label><input type="number" class="form-control" name="bp_diastolic"></div>
                        <div class="col-md-1"><label class="form-label">SpO2 %</label><input type="number" class="form-control" name="spo2"></div>
                        <div class="col-md-1"><label class="form-label">Weight</label><input type="number" step="0.1" class="form-control" name="weight_kg"></div>
                        <div class="col-md-12"><label class="form-label">Additional Admission Note</label><textarea class="form-control" name="general_note" rows="2"></textarea></div>
                        <div class="col-md-12"><button type="submit" class="btn btn-primary btn-sm">Save Admission Snapshot</button></div>
                    </form>
                </div>

                <div class="nursing-tab-pane d-none" id="nursing_tab_history">
                    <form class="row g-3" id="nursing_admission_history_form" data-save-url="<?= esc($historySaveUrl) ?>">
                        <?= csrf_field() ?>
                        <div class="col-md-12">
                            <div class="alert alert-info py-2 mb-1">
                                Keep manual admission paper form if needed. This section stores the same H&amp;P data digitally.
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label mb-1"><strong>Past / Personal History</strong></label>
                            <div class="d-flex flex-wrap gap-3 border rounded p-2">
                                <?php foreach ($historyFields as $fieldKey => $fieldLabel) : ?>
                                    <div class="form-check">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            id="history_<?= esc($fieldKey) ?>"
                                            name="<?= esc($fieldKey) ?>"
                                            value="1"
                                            <?= (int) ($patientHistoryRow[$fieldKey] ?? 0) === 1 ? 'checked' : '' ?>
                                        >
                                        <label class="form-check-label" for="history_<?= esc($fieldKey) ?>"><?= esc($fieldLabel) ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Drug Allergy Status</label>
                            <?php $allergyStatus = strtolower(trim((string) ($opdHistorySnapshot['drug_allergy_status'] ?? ''))); ?>
                            <select class="form-select" id="nursing_history_drug_allergy_status" name="drug_allergy_status">
                                <option value="">Select</option>
                                <option value="yes" <?= $allergyStatus === 'yes' ? 'selected' : '' ?>>Yes</option>
                                <option value="no" <?= $allergyStatus === 'no' ? 'selected' : '' ?>>No</option>
                                <option value="unknown" <?= $allergyStatus === 'unknown' ? 'selected' : '' ?>>Unknown</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Drug Allergy Details</label>
                            <input type="text" class="form-control" id="nursing_history_drug_allergy_details" name="drug_allergy_details" value="<?= esc((string) ($opdHistorySnapshot['drug_allergy_details'] ?? '')) ?>" placeholder="Required when Drug Allergy Status is Yes">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">ADR History</label>
                            <textarea class="form-control" rows="2" name="adr_history"><?= esc((string) ($opdHistorySnapshot['adr_history'] ?? '')) ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Current Medications</label>
                            <textarea class="form-control" rows="2" name="current_medications"><?= esc((string) ($opdHistorySnapshot['current_medications'] ?? '')) ?></textarea>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label mb-1">Co-Morbidities</label>
                            <div class="d-flex flex-wrap gap-3 border rounded p-2">
                                <?php foreach ($coMorbidityOptions as $coLabel) : ?>
                                    <?php $isChecked = in_array($coLabel, $selectedCoMorbidities, true); ?>
                                    <div class="form-check">
                                        <input class="form-check-input nursing-history-morbidity" type="checkbox" id="nursing_history_morb_<?= esc(strtolower(str_replace(' ', '_', $coLabel))) ?>" value="<?= esc($coLabel) ?>" <?= $isChecked ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="nursing_history_morb_<?= esc(strtolower(str_replace(' ', '_', $coLabel))) ?>"><?= esc($coLabel) ?></label>
                                    </div>
                                <?php endforeach; ?>
                                <div class="input-group input-group-sm" style="max-width: 320px;">
                                    <span class="input-group-text">Other</span>
                                    <input type="text" class="form-control" id="nursing_history_morbidity_other" value="<?= esc(implode(', ', $otherCoMorbidities)) ?>" placeholder="Other co-morbidity">
                                </div>
                            </div>
                            <input type="hidden" name="co_morbidities" id="nursing_history_co_morbidities" value="<?= esc((string) ($opdHistorySnapshot['co_morbidities'] ?? '')) ?>">
                        </div>

                        <?php if ($isFemalePatient) : ?>
                            <div class="col-md-3"><label class="form-label">Women Related LMP</label><input type="text" class="form-control" name="women_lmp" value="<?= esc((string) ($opdHistorySnapshot['women_lmp'] ?? '')) ?>"></div>
                            <div class="col-md-3"><label class="form-label">Last Baby</label><input type="text" class="form-control" name="women_last_baby" value="<?= esc((string) ($opdHistorySnapshot['women_last_baby'] ?? '')) ?>"></div>
                            <div class="col-md-3"><label class="form-label">Pregnancy Related</label><input type="text" class="form-control" name="women_pregnancy_related" value="<?= esc((string) ($opdHistorySnapshot['women_pregnancy_related'] ?? '')) ?>"></div>
                            <div class="col-md-3"><label class="form-label">Women Related Problems</label><input type="text" class="form-control" name="women_related_problems" value="<?= esc((string) ($opdHistorySnapshot['women_related_problems'] ?? '')) ?>"></div>
                        <?php endif; ?>

                        <div class="col-md-12">
                            <label class="form-label">History &amp; Physical Note</label>
                            <textarea class="form-control" rows="3" name="hpi_note" placeholder="Brief H&P assessment summary"><?= esc((string) ($opdHistorySnapshot['hpi_note'] ?? '')) ?></textarea>
                        </div>

                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary btn-sm">Save History &amp; Physical Assessment</button>
                        </div>
                    </form>
                </div>

                <div class="nursing-tab-pane" id="nursing_tab_vitals">
                    <form class="row g-2 nursing-form" data-save-url="<?= esc($saveUrl) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="entry_type" value="vitals">
                        <div class="col-md-3"><label class="form-label">Recorded At</label><input type="datetime-local" class="form-control" name="recorded_at" value="<?= date('Y-m-d\\TH:i') ?>"></div>
                        <div class="col-md-2"><label class="form-label">Shift</label><input type="text" class="form-control nursing-shift-display" value="Morning" readonly></div>
                        <div class="col-md-1"><label class="form-label">Temp °F</label><input type="number" step="0.1" class="form-control" name="temperature_f"></div>
                        <div class="col-md-1"><label class="form-label">Pulse</label><input type="number" class="form-control" name="pulse_rate"></div>
                        <div class="col-md-1"><label class="form-label">Resp</label><input type="number" class="form-control" name="resp_rate"></div>
                        <div class="col-md-1"><label class="form-label">SBP</label><input type="number" class="form-control" name="bp_systolic"></div>
                        <div class="col-md-1"><label class="form-label">DBP</label><input type="number" class="form-control" name="bp_diastolic"></div>
                        <div class="col-md-1"><label class="form-label">SpO2 %</label><input type="number" class="form-control" name="spo2"></div>
                        <div class="col-md-1"><label class="form-label">Weight</label><input type="number" step="0.1" class="form-control" name="weight_kg"></div>
                        <div class="col-md-12"><label class="form-label">Nursing Note</label><textarea class="form-control" name="general_note" rows="2"></textarea></div>
                        <div class="col-md-12"><button type="submit" class="btn btn-primary btn-sm">Save Vitals</button></div>
                    </form>
                </div>

                <div class="nursing-tab-pane d-none" id="nursing_tab_fluid">
                    <form class="row g-2 nursing-form" data-save-url="<?= esc($saveUrl) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="entry_type" value="fluid">
                        <div class="col-md-3"><label class="form-label">Recorded At</label><input type="datetime-local" class="form-control" name="recorded_at" value="<?= date('Y-m-d\\TH:i') ?>"></div>
                        <div class="col-md-2"><label class="form-label">Shift</label><input type="text" class="form-control nursing-shift-display" value="Morning" readonly></div>
                        <div class="col-md-2"><label class="form-label">Direction</label><select class="form-select" name="fluid_direction"><option value="intake">Intake</option><option value="output">Output</option></select></div>
                        <div class="col-md-2"><label class="form-label">Route</label><input type="text" class="form-control" name="fluid_route"></div>
                        <div class="col-md-2"><label class="form-label">Amount (ml)</label><input type="number" class="form-control" name="fluid_amount_ml" required></div>
                        <div class="col-md-12"><label class="form-label">Nursing Note</label><textarea class="form-control" name="general_note" rows="2"></textarea></div>
                        <div class="col-md-12"><button type="submit" class="btn btn-primary btn-sm">Save Fluid Entry</button></div>
                    </form>
                </div>

                <div class="nursing-tab-pane d-none" id="nursing_tab_treatment">
                    <form class="row g-2 nursing-form" data-save-url="<?= esc($saveUrl) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="entry_type" value="treatment">
                        <div class="col-md-3"><label class="form-label">Recorded At</label><input type="datetime-local" class="form-control" name="recorded_at" value="<?= date('Y-m-d\\TH:i') ?>"></div>
                        <div class="col-md-2"><label class="form-label">Shift</label><input type="text" class="form-control nursing-shift-display" value="Morning" readonly></div>
                        <div class="col-md-12"><label class="form-label">Treatment Given</label><textarea class="form-control" name="treatment_text" rows="3" required></textarea></div>
                        <div class="col-md-12"><label class="form-label">Additional Note</label><textarea class="form-control" name="general_note" rows="2"></textarea></div>
                        <div class="col-md-12"><button type="submit" class="btn btn-primary btn-sm">Save Treatment Note</button></div>
                    </form>
                </div>

                <div class="nursing-tab-pane d-none" id="nursing_tab_scan">
                    <div class="alert alert-warning py-2 mb-3">
                        OCR/AI extraction is assistive only. Nurse review is mandatory before save (NABH documentation requirement).
                    </div>

                    <form id="nursing_scan_form" class="row g-2" enctype="multipart/form-data" data-extract-url="<?= esc($scanExtractUrl) ?>">
                        <?= csrf_field() ?>
                        <div class="col-md-3">
                            <label class="form-label">Document Type</label>
                            <select class="form-select" name="document_type" id="nursing_scan_document_type">
                                <option value="auto">Auto Detect</option>
                                <option value="vitals">Vitals Sheet</option>
                                <option value="fluid">Fluid Intake/Output</option>
                                <option value="treatment">Treatment/Procedure Note</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Scan File (PDF/Image/TXT)</label>
                            <input type="file" class="form-control" name="scan_file" id="nursing_scan_file" accept=".pdf,.png,.jpg,.jpeg,.webp,.txt">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Scan Ref</label>
                            <input type="text" class="form-control" id="nursing_scan_ref" readonly>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">OCR Text (optional manual paste)</label>
                            <textarea class="form-control" rows="4" name="ocr_text" id="nursing_scan_ocr_text" placeholder="Paste OCR text here if scan OCR is external."></textarea>
                        </div>
                        <div class="col-md-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">Scan and Extract</button>
                            <button type="button" class="btn btn-success btn-sm" id="nursing_scan_save_rows" data-save-url="<?= esc($scanSaveUrl) ?>" disabled>Save Reviewed Rows</button>
                        </div>
                    </form>

                    <div class="mt-3" id="nursing_scan_notice"></div>

                    <div class="card mt-3 d-none" id="nursing_scan_review_card">
                        <div class="card-header py-2"><strong>Extracted Rows (Review Before Save)</strong></div>
                        <div class="card-body table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0" id="nursing_scan_rows_table">
                                <thead>
                                    <tr>
                                        <th style="width: 70px;">#</th>
                                        <th style="width: 100px;">Type</th>
                                        <th style="width: 180px;">Recorded At</th>
                                        <th>Values / Notes</th>
                                        <th style="width: 90px;">Confidence</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="nursing-tab-pane d-none" id="nursing_tab_charge">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="card border-info h-100">
                                <div class="card-header py-2"><strong>Bedside Clinical / Nursing Charge</strong></div>
                                <div class="card-body">
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="form-label">Item Type</label>
                                            <select class="form-select form-select-sm" id="nursing_bedside_item_type">
                                                <option value="">All</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Category</label>
                                            <select class="form-select form-select-sm" id="nursing_bedside_category">
                                                <option value="">Select</option>
                                                <?php foreach (array_keys($bedsideItemsByCategory) as $categoryName) : ?>
                                                    <option value="<?= esc($categoryName) ?>"><?= esc($categoryName) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Item</label>
                                            <select class="form-select form-select-sm" id="nursing_bedside_item_id">
                                                <option value="">Select</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Quick Search</label>
                                            <input type="text" class="form-control form-control-sm" id="nursing_bedside_item_search" placeholder="Search by code or name">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Rate</label>
                                            <input class="form-control form-control-sm" id="nursing_bedside_item_rate" value="0" type="number" step="0.01" />
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Qty</label>
                                            <input class="form-control form-control-sm" id="nursing_bedside_item_qty" value="1" type="number" step="0.01" />
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Date</label>
                                            <input class="form-control form-control-sm" id="nursing_bedside_item_date" value="<?= esc($today) ?>" type="date" />
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label">Doctor</label>
                                            <select class="form-select form-select-sm" id="nursing_bedside_doc_id">
                                                <option value="0">NONE</option>
                                                <?php foreach ($docList as $doc) : ?>
                                                    <option value="<?= esc($doc->id ?? '') ?>"><?= esc($doc->DocSpecName ?? '') ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label">Comment</label>
                                            <input class="form-control form-control-sm" id="nursing_bedside_comment" value="" type="text" />
                                        </div>
                                        <div class="col-md-12">
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="nursing_add_bedside_charge">Add Bedside Charge</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="card border-primary h-100">
                                <div class="card-header py-2"><strong>Doctor Visit Charge</strong></div>
                                <div class="card-body">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <label class="form-label">Doctor</label>
                                            <select class="form-select form-select-sm" id="nursing_doctor_visit_doc_id">
                                                <option value="">Select</option>
                                                <?php foreach ($docList as $doc) : ?>
                                                    <option value="<?= esc($doc->id ?? '') ?>"><?= esc($doc->DocSpecName ?? '') ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Visit Type</label>
                                            <select class="form-select form-select-sm" id="nursing_doctor_visit_fee_type">
                                                <option value="">Select</option>
                                                <?php foreach ($doctorVisitFeeTypes as $row) : ?>
                                                    <option value="<?= esc($row['id'] ?? 0) ?>"><?= esc($row['fee_type'] ?? '') ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Rate</label>
                                            <input class="form-control form-control-sm" id="nursing_doctor_visit_rate" value="0.00" type="number" step="0.01" />
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Qty</label>
                                            <input class="form-control form-control-sm" id="nursing_doctor_visit_qty" value="1" type="number" step="0.01" />
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Date</label>
                                            <input class="form-control form-control-sm" id="nursing_doctor_visit_date" value="<?= esc($today) ?>" type="date" />
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Time</label>
                                            <input class="form-control form-control-sm" id="nursing_doctor_visit_time" type="time" />
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Duration (min)</label>
                                            <input class="form-control form-control-sm" id="nursing_doctor_visit_duration" value="0" type="number" min="0" step="1" />
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
                                            <div class="form-check mb-1">
                                                <input class="form-check-input" type="checkbox" id="nursing_doctor_visit_outside" value="1">
                                                <label class="form-check-label" for="nursing_doctor_visit_outside">Outside Doctor</label>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label">Description</label>
                                            <input class="form-control form-control-sm" id="nursing_doctor_visit_fee_desc" value="" type="text" readonly />
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label">Hospital/Clinic</label>
                                            <input class="form-control form-control-sm" id="nursing_doctor_visit_hospital" value="" type="text" />
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label">Comment</label>
                                            <input class="form-control form-control-sm" id="nursing_doctor_visit_comment" value="" type="text" />
                                        </div>
                                        <div class="col-md-12">
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="nursing_add_doctor_visit_charge">Add Doctor Visit</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header py-2"><strong>Charge Entry History</strong></div>
                        <div class="card-body table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 70px;">ID</th>
                                        <th>Item</th>
                                        <th style="width: 130px;">Type</th>
                                        <th style="width: 150px;">Doctor</th>
                                        <th style="width: 120px;">Date</th>
                                        <th style="width: 90px;">Time</th>
                                        <th style="width: 80px;" class="text-end">Qty</th>
                                        <th style="width: 100px;" class="text-end">Rate</th>
                                        <th style="width: 110px;" class="text-end">Amount</th>
                                        <th style="width: 140px;">In Bill</th>
                                        <th>Comment</th>
                                        <th style="width: 130px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($nursingChargeHistory)) : ?>
                                        <tr>
                                            <td colspan="12" class="text-center text-muted">No charge history found.</td>
                                        </tr>
                                    <?php else : ?>
                                        <?php foreach ($nursingChargeHistory as $chargeRow) : ?>
                                            <?php
                                            $chargeId = (int) ($chargeRow['charge_id'] ?? 0);
                                            $chargeRate = (float) ($chargeRow['rate'] ?? 0);
                                            $chargeQty = (float) ($chargeRow['qty'] ?? 0);
                                            $chargeComment = (string) ($chargeRow['remarks'] ?? '');
                                            $chargeDate = (string) ($chargeRow['visit_date'] ?? '');
                                            $chargeTime = (string) ($chargeRow['visit_time'] ?? '');
                                            ?>
                                            <tr id="nursing_charge_row_<?= $chargeId ?>"
                                                data-rate="<?= esc((string) $chargeRate) ?>"
                                                data-qty="<?= esc((string) $chargeQty) ?>"
                                                data-comment="<?= esc($chargeComment) ?>"
                                                data-visit-date="<?= esc($chargeDate) ?>"
                                                data-visit-time="<?= esc($chargeTime) ?>">
                                                <td><?= esc((string) ($chargeRow['charge_id'] ?? '')) ?></td>
                                                <td><?= esc((string) ($chargeRow['item_name'] ?? '')) ?></td>
                                                <td><?= esc((string) ($chargeRow['item_type'] ?? '')) ?></td>
                                                <td><?= esc((string) ($chargeRow['doctor_name'] ?? '')) ?></td>
                                                <td><?= esc((string) ($chargeRow['visit_date'] ?? '')) ?></td>
                                                <td><?= esc((string) ($chargeRow['visit_time'] ?? '')) ?></td>
                                                <td class="text-end"><?= esc(number_format((float) ($chargeRow['qty'] ?? 0), 2)) ?></td>
                                                <td class="text-end"><?= esc(number_format((float) ($chargeRow['rate'] ?? 0), 2)) ?></td>
                                                <td class="text-end"><?= esc(number_format((float) ($chargeRow['amount'] ?? 0), 2)) ?></td>
                                                <td><?= (int) ($chargeRow['include_in_bill'] ?? 0) === 1 ? 'Yes' : 'No' ?></td>
                                                <td><?= esc((string) ($chargeRow['remarks'] ?? '')) ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group" aria-label="History Actions">
                                                        <button type="button" class="btn btn-outline-warning" onclick="nursingEditChargeHistory(<?= $chargeId ?>)">Edit</button>
                                                        <button type="button" class="btn btn-outline-danger" onclick="nursingDeleteChargeHistory(<?= $chargeId ?>)">Delete</button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><strong>Nursing Entries History</strong></div>
            <div class="card-body table-responsive">
                <div class="row g-2 mb-3">
                    <div class="col-md-3"><label class="form-label">Filter Nurse</label><select class="form-select" id="nursing_filter_nurse"><option value="">All</option><?php foreach ($nurseNames as $nurseName) : ?><option value="<?= esc($nurseName) ?>"><?= esc($nurseName) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-3"><label class="form-label">Filter Type</label><select class="form-select" id="nursing_filter_type"><option value="">All</option><option value="admission">Admission Snapshot</option><option value="vitals">Vitals</option><option value="fluid">Fluid</option><option value="treatment">Treatment</option></select></div>
                    <div class="col-md-2"><label class="form-label">Chart Date</label><input type="date" class="form-control" id="nursing_chart_date" value="<?= date('Y-m-d') ?>"></div>
                    <div class="col-md-4 d-flex align-items-end gap-2"><button type="button" class="btn btn-outline-secondary" id="nursing_filter_reset">Clear Filters</button><button type="button" class="btn btn-outline-primary" id="nursing_print_chart">Print 24h Nursing Chart</button></div>
                </div>

                <table class="table table-sm table-bordered align-middle">
                    <thead><tr><th style="width: 150px;">Time</th><th style="width: 90px;">Type</th><th style="width: 90px;">Shift</th><th>Details</th><th style="width: 130px;">By</th></tr></thead>
                    <tbody>
                    <?php if (empty($entries)) : ?>
                        <tr><td colspan="5" class="text-center text-muted" id="nursing_no_data_row">No nursing entries yet.</td></tr>
                    <?php else : ?>
                        <?php foreach ($entries as $entry) : ?>
                            <tr class="nursing-entry-row" data-nurse="<?= esc((string) ($entry['recorded_by'] ?? '')) ?>" data-type="<?= esc(strtolower((string) ($entry['entry_type'] ?? ''))) ?>">
                                <td><?= esc((string) ($entry['recorded_at'] ?? '')) ?></td>
                                <td><?= esc((string) ($entry['entry_type'] ?? '')) ?></td>
                                <td><?= esc((string) ($entry['shift_name'] ?? '')) ?></td>
                                <td>
                                    <?php if (($entry['entry_type'] ?? '') === 'vitals') : ?>
                                        Temp: <?= esc($toFahrenheit($entry['temperature_c'] ?? null)) ?> °F, Pulse: <?= esc((string) ($entry['pulse_rate'] ?? '')) ?>, Resp: <?= esc((string) ($entry['resp_rate'] ?? '')) ?>, BP: <?= esc((string) ($entry['bp_systolic'] ?? '')) ?>/<?= esc((string) ($entry['bp_diastolic'] ?? '')) ?>, SpO2: <?= esc((string) ($entry['spo2'] ?? '')) ?>, Wt: <?= esc((string) ($entry['weight_kg'] ?? '')) ?>
                                    <?php elseif (($entry['entry_type'] ?? '') === 'fluid') : ?>
                                        <?= esc((string) ($entry['fluid_direction'] ?? '')) ?>, Route: <?= esc((string) ($entry['fluid_route'] ?? '')) ?>, Amount: <?= esc((string) ($entry['fluid_amount_ml'] ?? '')) ?> ml
                                    <?php else : ?>
                                        <?= esc((string) ($entry['treatment_text'] ?? '')) ?>
                                    <?php endif; ?>
                                    <?php if (! empty($entry['general_note'])) : ?><br><small><strong>Note:</strong> <?= esc((string) $entry['general_note']) ?></small><?php endif; ?>
                                </td>
                                <td><?= esc((string) ($entry['recorded_by'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><strong>Scanned Entry Audit Trail</strong></div>
            <div class="card-body table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Entry ID</th>
                            <th style="width: 150px;">Recorded At</th>
                            <th style="width: 90px;">Type</th>
                            <th style="width: 180px;">Scan Ref</th>
                            <th style="width: 100px;">AI Confidence</th>
                            <th style="width: 90px;">Corrected</th>
                            <th style="width: 130px;">Reviewed By</th>
                            <th>Audit Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($scannedAuditRows)) : ?>
                            <tr><td colspan="8" class="text-center text-muted">No scanned-entry audit rows yet.</td></tr>
                        <?php else : ?>
                            <?php foreach ($scannedAuditRows as $audit) : ?>
                                <tr>
                                    <td><?= esc((string) ($audit['entry_id'] ?? '')) ?></td>
                                    <td><?= esc((string) ($audit['recorded_at'] ?? '')) ?></td>
                                    <td><?= esc((string) ($audit['entry_type'] ?? '')) ?></td>
                                    <td><?= esc((string) ($audit['scan_ref'] ?? '')) ?></td>
                                    <td><?= esc((string) ($audit['confidence'] ?? '')) ?></td>
                                    <td><?= esc((string) ($audit['corrected'] ?? '')) ?></td>
                                    <td><?= esc((string) ($audit['recorded_by'] ?? '')) ?></td>
                                    <td><small><?= esc((string) ($audit['note'] ?? '')) ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    var forms = document.querySelectorAll('#nursing_panel_content .nursing-form');
    var tabButtons = document.querySelectorAll('#nursing_entry_tabs [data-nursing-tab]');
    var historyForm = document.getElementById('nursing_admission_history_form');
    var scanForm = document.getElementById('nursing_scan_form');
    var scanSaveBtn = document.getElementById('nursing_scan_save_rows');
    var nursingChargeCsrfName = '<?= esc($csrfName) ?>';
    var nursingChargeCsrfHash = '<?= esc($csrfHash) ?>';
    var nursingBedsideChargeUrl = '<?= esc($bedsideChargeUrl) ?>';
    var nursingDoctorVisitChargeUrl = '<?= esc($doctorVisitChargeUrl) ?>';
    var nursingChargeUpdateUrlBase = '<?= esc(site_url('billing/ipd/nursing-charge/update/' . $ipdId)) ?>';
    var nursingChargeDeleteUrlBase = '<?= esc(site_url('billing/ipd/nursing-charge/delete/' . $ipdId)) ?>';
    var nursingBedsideItemsByCategory = <?= json_encode($bedsideItemsByCategory, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    var nursingDoctorVisitFeeTypes = <?= json_encode($doctorVisitFeeTypes, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    var nursingDoctorVisitFeeMap = <?= json_encode($doctorVisitFeeMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    var nursingScannedRows = [];

    function nursingScanRowToSummary(row, index) {
        var html = '';
        var type = String(row.entry_type || '').toLowerCase();
        var dt = String(row.recorded_at || '');
        var confidence = row.confidence !== undefined ? Number(row.confidence || 0) : 0;

        html += '<div class="mb-2"><label class="form-label small mb-1">Recorded At</label>';
        html += '<input type="datetime-local" class="form-control form-control-sm nursing-scan-input" data-idx="' + index + '" data-key="recorded_at" value="' + dt + '"></div>';

        if (type === 'vitals') {
            html += '<div class="row g-1">';
            html += '<div class="col-4"><input class="form-control form-control-sm nursing-scan-input" data-idx="' + index + '" data-key="temperature_f" placeholder="Temp F" value="' + (row.temperature_f || '') + '"></div>';
            html += '<div class="col-4"><input class="form-control form-control-sm nursing-scan-input" data-idx="' + index + '" data-key="pulse_rate" placeholder="Pulse" value="' + (row.pulse_rate || '') + '"></div>';
            html += '<div class="col-4"><input class="form-control form-control-sm nursing-scan-input" data-idx="' + index + '" data-key="resp_rate" placeholder="Resp" value="' + (row.resp_rate || '') + '"></div>';
            html += '<div class="col-4"><input class="form-control form-control-sm nursing-scan-input" data-idx="' + index + '" data-key="bp_systolic" placeholder="SBP" value="' + (row.bp_systolic || '') + '"></div>';
            html += '<div class="col-4"><input class="form-control form-control-sm nursing-scan-input" data-idx="' + index + '" data-key="bp_diastolic" placeholder="DBP" value="' + (row.bp_diastolic || '') + '"></div>';
            html += '<div class="col-4"><input class="form-control form-control-sm nursing-scan-input" data-idx="' + index + '" data-key="spo2" placeholder="SpO2" value="' + (row.spo2 || '') + '"></div>';
            html += '</div>';
            html += '<textarea class="form-control form-control-sm mt-1 nursing-scan-input" data-idx="' + index + '" data-key="general_note" rows="2" placeholder="Nursing note">' + (row.general_note || '') + '</textarea>';
        } else if (type === 'fluid') {
            html += '<div class="row g-1">';
            html += '<div class="col-4"><select class="form-select form-select-sm nursing-scan-input" data-idx="' + index + '" data-key="fluid_direction"><option value="intake">Intake</option><option value="output">Output</option></select></div>';
            html += '<div class="col-4"><input class="form-control form-control-sm nursing-scan-input" data-idx="' + index + '" data-key="fluid_route" placeholder="Route" value="' + (row.fluid_route || '') + '"></div>';
            html += '<div class="col-4"><input class="form-control form-control-sm nursing-scan-input" data-idx="' + index + '" data-key="fluid_amount_ml" placeholder="Amount ml" value="' + (row.fluid_amount_ml || '') + '"></div>';
            html += '</div>';
            html += '<textarea class="form-control form-control-sm mt-1 nursing-scan-input" data-idx="' + index + '" data-key="general_note" rows="2" placeholder="Nursing note">' + (row.general_note || '') + '</textarea>';
        } else {
            html += '<textarea class="form-control form-control-sm nursing-scan-input" data-idx="' + index + '" data-key="treatment_text" rows="2" placeholder="Treatment text">' + (row.treatment_text || '') + '</textarea>';
            html += '<textarea class="form-control form-control-sm mt-1 nursing-scan-input" data-idx="' + index + '" data-key="general_note" rows="2" placeholder="Additional note">' + (row.general_note || '') + '</textarea>';
        }

        return {
            html: html,
            confidence: confidence,
        };
    }

    function nursingRenderScannedRows() {
        var card = document.getElementById('nursing_scan_review_card');
        var tbody = document.querySelector('#nursing_scan_rows_table tbody');
        if (!card || !tbody) {
            return;
        }

        tbody.innerHTML = '';
        if (!Array.isArray(nursingScannedRows) || nursingScannedRows.length === 0) {
            card.classList.add('d-none');
            if (scanSaveBtn) {
                scanSaveBtn.disabled = true;
            }
            return;
        }

        nursingScannedRows.forEach(function (row, idx) {
            var mapped = nursingScanRowToSummary(row, idx);
            var tr = document.createElement('tr');
            tr.innerHTML = ''
                + '<td>' + (idx + 1) + '</td>'
                + '<td><select class="form-select form-select-sm nursing-scan-input" data-idx="' + idx + '" data-key="entry_type"><option value="vitals">Vitals</option><option value="fluid">Fluid</option><option value="treatment">Treatment</option></select></td>'
                + '<td>' + mapped.html.split('</div>')[0] + '</td>'
                + '<td>' + mapped.html.substring(mapped.html.indexOf('</div>') + 6) + '</td>'
                + '<td class="text-end">' + Math.round((mapped.confidence || 0) * 100) + '%</td>';
            tbody.appendChild(tr);

            var typeSelector = tr.querySelector('select[data-key="entry_type"]');
            if (typeSelector) {
                typeSelector.value = String(row.entry_type || 'treatment').toLowerCase();
            }

            var directionSelector = tr.querySelector('select[data-key="fluid_direction"]');
            if (directionSelector) {
                directionSelector.value = String(row.fluid_direction || 'intake').toLowerCase();
            }
        });

        card.classList.remove('d-none');
        if (scanSaveBtn) {
            scanSaveBtn.disabled = false;
        }
    }

    function nursingSyncScannedRowsFromGrid() {
        var inputs = document.querySelectorAll('.nursing-scan-input');
        inputs.forEach(function (el) {
            var idx = parseInt(el.getAttribute('data-idx') || '-1', 10);
            var key = String(el.getAttribute('data-key') || '');
            if (idx < 0 || !nursingScannedRows[idx] || key === '') {
                return;
            }
            nursingScannedRows[idx][key] = el.value;
            nursingScannedRows[idx].is_corrected = 1;
        });
    }

    function nursingSyncHistoryMorbidities() {
        if (!historyForm) {
            return;
        }

        var values = [];
        historyForm.querySelectorAll('.nursing-history-morbidity:checked').forEach(function (el) {
            var value = (el.value || '').trim();
            if (value !== '') {
                values.push(value);
            }
        });

        var otherEl = document.getElementById('nursing_history_morbidity_other');
        var otherValue = otherEl ? (otherEl.value || '').trim() : '';
        if (otherValue !== '') {
            values.push(otherValue);
        }

        var hidden = document.getElementById('nursing_history_co_morbidities');
        if (hidden) {
            hidden.value = values.join(', ');
        }
    }

    function nursingValidateHistoryForm() {
        if (!historyForm) {
            return true;
        }

        var statusEl = document.getElementById('nursing_history_drug_allergy_status');
        var detailsEl = document.getElementById('nursing_history_drug_allergy_details');
        var status = statusEl ? String(statusEl.value || '').toLowerCase().trim() : '';
        var details = detailsEl ? String(detailsEl.value || '').trim() : '';

        if (status === 'yes' && details === '') {
            if (detailsEl) {
                detailsEl.focus();
            }
            notify('error', 'Nursing Care', 'Drug allergy details are required when status is Yes.');
            return false;
        }

        return true;
    }

    function nursingGetAllBedsideItems() {
        var items = [];
        Object.keys(nursingBedsideItemsByCategory || {}).forEach(function (categoryName) {
            var categoryItems = nursingBedsideItemsByCategory[categoryName] || [];
            categoryItems.forEach(function (item) {
                items.push(item);
            });
        });

        return items;
    }

    function nursingRefreshBedsideTypeOptions() {
        var selectedType = $('#nursing_bedside_item_type').val() || '';
        var typeSelect = $('#nursing_bedside_item_type');
        typeSelect.empty().append('<option value="">All</option>');

        var uniqueTypes = {};
        nursingGetAllBedsideItems().forEach(function (item) {
            var typeName = String(item.item_type || '').trim();
            if (typeName !== '') {
                uniqueTypes[typeName] = true;
            }
        });

        Object.keys(uniqueTypes).sort().forEach(function (typeName) {
            typeSelect.append('<option value="' + typeName + '">' + typeName + '</option>');
        });

        if (selectedType !== '' && uniqueTypes[selectedType]) {
            typeSelect.val(selectedType);
        }
    }

    function nursingRefreshBedsideCategories() {
        var selectedType = $('#nursing_bedside_item_type').val() || '';
        var selectedCategory = $('#nursing_bedside_category').val() || '';
        var categories = {};

        nursingGetAllBedsideItems().forEach(function (item) {
            var itemType = String(item.item_type || '').trim();
            if (selectedType !== '' && itemType !== selectedType) {
                return;
            }
            var categoryName = String(item.category || '').trim();
            if (categoryName === '') {
                categoryName = 'General';
            }
            categories[categoryName] = true;
        });

        var categorySelect = $('#nursing_bedside_category');
        categorySelect.empty().append('<option value="">Select</option>');
        Object.keys(categories).sort().forEach(function (categoryName) {
            categorySelect.append('<option value="' + categoryName + '">' + categoryName + '</option>');
        });

        if (selectedCategory !== '' && categories[selectedCategory]) {
            categorySelect.val(selectedCategory);
        }
    }

    function nursingRefreshBedsideItems() {
        var selectedType = $('#nursing_bedside_item_type').val() || '';
        var category = $('#nursing_bedside_category').val() || '';
        var search = ($('#nursing_bedside_item_search').val() || '').toLowerCase().trim();
        var itemSelect = $('#nursing_bedside_item_id');
        itemSelect.empty().append('<option value="">Select</option>');

        var items = (nursingBedsideItemsByCategory[category] || []).filter(function (item) {
            var typeName = String(item.item_type || '').trim();
            var itemName = String(item.item_name || '').toLowerCase();
            var itemCode = String(item.item_code || '').toLowerCase();

            if (search !== '' && itemName.indexOf(search) === -1 && itemCode.indexOf(search) === -1) {
                return false;
            }

            return selectedType === '' || typeName === selectedType;
        });

        items.forEach(function (item) {
            var rate = Number(item.default_rate || 0);
            var label = (item.item_name || '') + ' [' + rate.toFixed(2) + ']';
            var insuranceCode = String(item.insurance_code || '').trim();
            if (insuranceCode !== '') {
                label += ' {' + insuranceCode + '}';
            }
            var option = $('<option></option>')
                .attr('value', item.item_id)
                .attr('data-rate', rate)
                .text(label);
            itemSelect.append(option);
        });

        $('#nursing_bedside_item_rate').val(items.length > 0 ? Number(items[0].default_rate || 0).toFixed(2) : '0.00');
    }

    function nursingSetBedsideRate() {
        var selected = $('#nursing_bedside_item_id option:selected');
        var rate = selected.data('rate');
        $('#nursing_bedside_item_rate').val(rate !== undefined ? Number(rate).toFixed(2) : '0.00');
    }

    function nursingRefreshDoctorVisitTypes() {
        var docId = parseInt($('#nursing_doctor_visit_doc_id').val() || '0', 10);
        var selectedFeeType = $('#nursing_doctor_visit_fee_type').val() || '';
        var feeTypeSelect = $('#nursing_doctor_visit_fee_type');
        feeTypeSelect.empty().append('<option value="">Select</option>');

        if (docId > 0 && nursingDoctorVisitFeeMap[docId]) {
            var docFees = nursingDoctorVisitFeeMap[docId];
            Object.keys(docFees).forEach(function (feeTypeId) {
                var row = docFees[feeTypeId] || {};
                var label = String(row.fee_type || '').trim();
                if (label === '') {
                    label = 'Visit Type ' + feeTypeId;
                }
                feeTypeSelect.append('<option value="' + feeTypeId + '">' + label + '</option>');
            });
        } else {
            (nursingDoctorVisitFeeTypes || []).forEach(function (row) {
                feeTypeSelect.append('<option value="' + row.id + '">' + (row.fee_type || '') + '</option>');
            });
        }

        if (selectedFeeType !== '' && feeTypeSelect.find('option[value="' + selectedFeeType + '"]').length > 0) {
            feeTypeSelect.val(selectedFeeType);
        }
    }

    function nursingRefreshDoctorVisitRate() {
        var docId = parseInt($('#nursing_doctor_visit_doc_id').val() || '0', 10);
        var feeTypeId = parseInt($('#nursing_doctor_visit_fee_type').val() || '0', 10);
        var feeDetail = null;

        if (docId > 0 && nursingDoctorVisitFeeMap[docId]) {
            var docFees = nursingDoctorVisitFeeMap[docId];
            if (feeTypeId > 0 && docFees[feeTypeId]) {
                feeDetail = docFees[feeTypeId];
            } else {
                var firstKey = Object.keys(docFees)[0] || null;
                if (firstKey && docFees[firstKey]) {
                    feeDetail = docFees[firstKey];
                    $('#nursing_doctor_visit_fee_type').val(String(firstKey));
                }
            }
        }

        if (feeDetail) {
            $('#nursing_doctor_visit_rate').val(Number(feeDetail.amount || 0).toFixed(2));
            $('#nursing_doctor_visit_fee_desc').val(feeDetail.fee_desc || feeDetail.fee_type || '');
            return;
        }

        $('#nursing_doctor_visit_rate').val('0.00');
        $('#nursing_doctor_visit_fee_desc').val('');
    }

    function nursingAddBedsideCharge() {
        var bedsideItemId = $('#nursing_bedside_item_id').val() || '';
        if (!bedsideItemId) {
            alert('Select bedside item.');
            return;
        }

        var payload = {};
        payload[nursingChargeCsrfName] = nursingChargeCsrfHash;
        payload.bedside_item_id = bedsideItemId;
        payload.item_rate = $('#nursing_bedside_item_rate').val() || 0;
        payload.item_qty = $('#nursing_bedside_item_qty').val() || 1;
        payload.item_date = $('#nursing_bedside_item_date').val() || '<?= esc($today) ?>';
        payload.comment = $('#nursing_bedside_comment').val() || '';
        payload.doc_id = $('#nursing_bedside_doc_id').val() || 0;

        $.post(nursingBedsideChargeUrl, payload, function (resp) {
            if (resp && resp.ok) {
                notify('success', 'Nursing Charge', 'Bedside charge added');
                load_form('<?= esc($reloadUrl) ?>', 'Nursing Workspace');
                return;
            }
            alert((resp && resp.error) ? resp.error : 'Unable to add bedside charge');
        }, 'json').fail(function (xhr) {
            var msg = (xhr && xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Unable to add bedside charge';
            alert(msg);
        });
    }

    function nursingAddDoctorVisitCharge() {
        var docId = $('#nursing_doctor_visit_doc_id').val() || '';
        if (!docId) {
            alert('Select doctor.');
            return;
        }

        var payload = {};
        payload[nursingChargeCsrfName] = nursingChargeCsrfHash;
        payload.doc_id = docId;
        payload.fee_type_id = $('#nursing_doctor_visit_fee_type').val() || 0;
        payload.item_rate = $('#nursing_doctor_visit_rate').val() || 0;
        payload.item_qty = $('#nursing_doctor_visit_qty').val() || 1;
        payload.item_date = $('#nursing_doctor_visit_date').val() || '<?= esc($today) ?>';
        payload.visit_time = $('#nursing_doctor_visit_time').val() || '';
        payload.duration = $('#nursing_doctor_visit_duration').val() || 0;
        payload.hospital_clinic = $('#nursing_doctor_visit_hospital').val() || '';
        payload.is_outside_doctor = $('#nursing_doctor_visit_outside').is(':checked') ? 1 : 0;
        payload.comment = $('#nursing_doctor_visit_comment').val() || '';

        $.post(nursingDoctorVisitChargeUrl, payload, function (resp) {
            if (resp && resp.ok) {
                notify('success', 'Nursing Charge', 'Doctor visit charge added');
                load_form('<?= esc($reloadUrl) ?>', 'Nursing Workspace');
                return;
            }
            alert((resp && resp.error) ? resp.error : 'Unable to add doctor visit charge');
        }, 'json').fail(function (xhr) {
            var msg = (xhr && xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Unable to add doctor visit charge';
            alert(msg);
        });
    }

    window.nursingEditChargeHistory = function (chargeId) {
        var row = $('#nursing_charge_row_' + chargeId);
        if (!row.length) {
            alert('Charge row not found.');
            return;
        }

        var currentRate = String(row.data('rate') || '0');
        var currentQty = String(row.data('qty') || '1');
        var currentComment = String(row.data('comment') || '');
        var visitDate = String(row.data('visit-date') || '<?= esc($today) ?>');
        var visitTime = String(row.data('visit-time') || '');

        var nextRate = prompt('Update rate:', currentRate);
        if (nextRate === null) {
            return;
        }
        var nextQty = prompt('Update qty:', currentQty);
        if (nextQty === null) {
            return;
        }
        var nextComment = prompt('Update comment:', currentComment);
        if (nextComment === null) {
            return;
        }

        var parsedRate = parseFloat(nextRate);
        var parsedQty = parseFloat(nextQty);
        if (isNaN(parsedRate) || parsedRate < 0) {
            alert('Invalid rate.');
            return;
        }
        if (isNaN(parsedQty) || parsedQty <= 0) {
            alert('Invalid qty.');
            return;
        }

        var payload = {};
        payload[nursingChargeCsrfName] = nursingChargeCsrfHash;
        payload.rate = parsedRate;
        payload.qty = parsedQty;
        payload.comment = nextComment;
        payload.visit_date = visitDate;
        payload.visit_time = visitTime;

        $.post(nursingChargeUpdateUrlBase + '/' + chargeId, payload, function (resp) {
            if (resp && resp.ok) {
                notify('success', 'Nursing Charge', 'Charge updated');
                load_form('<?= esc($reloadUrl) ?>', 'Nursing Workspace');
                return;
            }
            alert((resp && resp.error) ? resp.error : 'Unable to update charge');
        }, 'json').fail(function (xhr) {
            var msg = (xhr && xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Unable to update charge';
            alert(msg);
        });
    };

    window.nursingDeleteChargeHistory = function (chargeId) {
        if (!confirm('Delete this charge from billing list?')) {
            return;
        }

        var payload = {};
        payload[nursingChargeCsrfName] = nursingChargeCsrfHash;

        $.post(nursingChargeDeleteUrlBase + '/' + chargeId, payload, function (resp) {
            if (resp && resp.ok) {
                notify('success', 'Nursing Charge', 'Charge deleted');
                load_form('<?= esc($reloadUrl) ?>', 'Nursing Workspace');
                return;
            }
            alert((resp && resp.error) ? resp.error : 'Unable to delete charge');
        }, 'json').fail(function (xhr) {
            var msg = (xhr && xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Unable to delete charge';
            alert(msg);
        });
    };

    tabButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var targetId = button.getAttribute('data-nursing-tab');

            tabButtons.forEach(function (btn) {
                btn.classList.remove('active');
            });

            var panes = document.querySelectorAll('#nursing_panel_content .nursing-tab-pane');
            panes.forEach(function (pane) {
                pane.classList.add('d-none');
            });

            button.classList.add('active');
            var targetPane = document.getElementById(targetId);
            if (targetPane) {
                targetPane.classList.remove('d-none');
            }
        });
    });

    forms.forEach(function (form) {
        var recordedAtInput = form.querySelector('input[name="recorded_at"]');
        var shiftDisplayInput = form.querySelector('.nursing-shift-display');

        function resolveShiftName(recordedAtValue) {
            if (!recordedAtValue || recordedAtValue.length < 16) {
                return 'Morning';
            }

            var timePart = recordedAtValue.substring(11, 16);
            var parts = timePart.split(':');
            if (parts.length !== 2) {
                return 'Morning';
            }

            var hour = parseInt(parts[0], 10);
            var minute = parseInt(parts[1], 10);
            if (isNaN(hour) || isNaN(minute)) {
                return 'Morning';
            }

            var totalMinutes = (hour * 60) + minute;
            if (totalMinutes >= 360 && totalMinutes < 840) {
                return 'Morning';
            }
            if (totalMinutes >= 840 && totalMinutes < 1320) {
                return 'Evening';
            }

            return 'Night';
        }

        function syncShiftDisplay() {
            if (!shiftDisplayInput) {
                return;
            }
            shiftDisplayInput.value = resolveShiftName(recordedAtInput ? recordedAtInput.value : '');
        }

        if (recordedAtInput) {
            recordedAtInput.addEventListener('input', syncShiftDisplay);
            recordedAtInput.addEventListener('change', syncShiftDisplay);
        }
        syncShiftDisplay();

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var saveUrl = form.getAttribute('data-save-url');
            $.post(saveUrl, $(form).serialize(), function (resp) {
                if (resp && String(resp.status) === '1') {
                    notify('success', 'Nursing Care', resp.message || 'Saved');
                    load_form('<?= esc($reloadUrl) ?>', 'Nursing Workspace');
                    return;
                }
                notify('error', 'Nursing Care', (resp && resp.message) ? resp.message : 'Unable to save entry');
            }, 'json').fail(function (xhr) {
                var message = (xhr && xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Unable to save entry';
                notify('error', 'Nursing Care', message);
            });
        });
    });

    if (historyForm) {
        var historyStatusEl = document.getElementById('nursing_history_drug_allergy_status');
        var historyDetailsEl = document.getElementById('nursing_history_drug_allergy_details');
        var historyOtherMorbidityEl = document.getElementById('nursing_history_morbidity_other');

        historyForm.querySelectorAll('.nursing-history-morbidity').forEach(function (el) {
            el.addEventListener('change', nursingSyncHistoryMorbidities);
        });
        if (historyOtherMorbidityEl) {
            historyOtherMorbidityEl.addEventListener('input', nursingSyncHistoryMorbidities);
        }

        if (historyStatusEl && historyDetailsEl) {
            var toggleRequired = function () {
                var required = String(historyStatusEl.value || '').toLowerCase().trim() === 'yes';
                historyDetailsEl.required = required;
            };
            historyStatusEl.addEventListener('change', toggleRequired);
            toggleRequired();
        }

        nursingSyncHistoryMorbidities();

        historyForm.addEventListener('submit', function (event) {
            event.preventDefault();
            nursingSyncHistoryMorbidities();
            if (!nursingValidateHistoryForm()) {
                return;
            }

            var saveUrl = historyForm.getAttribute('data-save-url');
            $.post(saveUrl, $(historyForm).serialize(), function (resp) {
                if (resp && String(resp.status) === '1') {
                    notify('success', 'Nursing Care', resp.message || 'History saved');
                    load_form('<?= esc($reloadUrl) ?>', 'Nursing Workspace');
                    return;
                }
                notify('error', 'Nursing Care', (resp && resp.message) ? resp.message : 'Unable to save history');
            }, 'json').fail(function (xhr) {
                var message = (xhr && xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Unable to save history';
                notify('error', 'Nursing Care', message);
            });
        });
    }

    if (scanForm) {
        scanForm.addEventListener('submit', function (event) {
            event.preventDefault();
            var notice = document.getElementById('nursing_scan_notice');
            if (notice) {
                notice.innerHTML = '';
            }

            var extractUrl = scanForm.getAttribute('data-extract-url');
            var formData = new window.FormData(scanForm);
            $.ajax({
                url: extractUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done(function (resp) {
                if (!resp || String(resp.status) !== '1') {
                    notify('error', 'Nursing Care', (resp && resp.message) ? resp.message : 'Scan failed');
                    return;
                }

                if (resp.csrfName && resp.csrfHash) {
                    var csrfEl = scanForm.querySelector('input[name="' + resp.csrfName + '"]');
                    if (csrfEl) {
                        csrfEl.value = resp.csrfHash;
                    }
                }

                nursingScannedRows = Array.isArray(resp.rows) ? resp.rows : [];
                var scanRefEl = document.getElementById('nursing_scan_ref');
                if (scanRefEl) {
                    scanRefEl.value = String(resp.scan_ref || '');
                }
                nursingScannedRows.forEach(function (row) {
                    row.is_corrected = 0;
                });

                if (notice) {
                    var warnings = Array.isArray(resp.warnings) ? resp.warnings : [];
                    var reqs = Array.isArray(resp.nabh_requirements) ? resp.nabh_requirements : [];
                    var html = '<div class="alert alert-info py-2 mb-2">' + (resp.message || 'Scan complete') + '</div>';
                    if (warnings.length > 0) {
                        html += '<div class="alert alert-warning py-2 mb-2">' + warnings.join('<br>') + '</div>';
                    }
                    if (reqs.length > 0) {
                        html += '<div class="small text-muted"><strong>NABH/IPD Documentation Checks:</strong><br>' + reqs.join('<br>') + '</div>';
                    }
                    notice.innerHTML = html;
                }

                nursingRenderScannedRows();
            }).fail(function (xhr) {
                var msg = (xhr && xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Scan failed';
                notify('error', 'Nursing Care', msg);
            });
        });
    }

    if (scanSaveBtn) {
        scanSaveBtn.addEventListener('click', function () {
            nursingSyncScannedRowsFromGrid();
            if (!Array.isArray(nursingScannedRows) || nursingScannedRows.length === 0) {
                notify('warning', 'Nursing Care', 'No reviewed rows available to save.');
                return;
            }

            var saveUrl = scanSaveBtn.getAttribute('data-save-url');
            var scanRef = $('#nursing_scan_ref').val() || '';
            var csrfEl = scanForm ? scanForm.querySelector('input[name^="csrf_"]') : null;
            var payload = {
                rows_json: JSON.stringify(nursingScannedRows),
                scan_ref: scanRef,
                document_type: $('#nursing_scan_document_type').val() || 'auto'
            };
            if (csrfEl) {
                payload[csrfEl.name] = csrfEl.value;
            }

            $.post(saveUrl, payload, function (resp) {
                if (resp && String(resp.status) === '1') {
                    notify('success', 'Nursing Care', resp.message || 'Scanned rows saved');
                    load_form('<?= esc($reloadUrl) ?>', 'Nursing Workspace');
                    return;
                }

                var errors = (resp && Array.isArray(resp.errors)) ? resp.errors.join('\n') : '';
                notify('error', 'Nursing Care', (resp && resp.message) ? resp.message : 'Unable to save scanned rows');
                if (errors !== '') {
                    alert(errors);
                }
            }, 'json').fail(function (xhr) {
                var msg = (xhr && xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Unable to save scanned rows';
                notify('error', 'Nursing Care', msg);
            });
        });
    }

    function applyHistoryFilter() {
        var nurse = ($('#nursing_filter_nurse').val() || '').toLowerCase();
        var type = ($('#nursing_filter_type').val() || '').toLowerCase();
        var rows = document.querySelectorAll('#nursing_panel_content .nursing-entry-row');
        var shown = 0;

        rows.forEach(function (row) {
            var rowNurse = (row.getAttribute('data-nurse') || '').toLowerCase();
            var rowType = (row.getAttribute('data-type') || '').toLowerCase();
            var visible = (nurse === '' || rowNurse === nurse) && (type === '' || rowType === type);
            row.style.display = visible ? '' : 'none';
            if (visible) { shown += 1; }
        });

        var noDataRow = document.getElementById('nursing_no_data_row');
        if (noDataRow) {
            noDataRow.parentElement.style.display = shown === 0 ? '' : 'none';
            if (shown === 0) {
                noDataRow.textContent = 'No entries found for selected filters.';
            }
        }
    }

    $('#nursing_filter_nurse').on('change', applyHistoryFilter);
    $('#nursing_filter_type').on('change', applyHistoryFilter);
    $('#nursing_filter_reset').on('click', function () {
        $('#nursing_filter_nurse').val('');
        $('#nursing_filter_type').val('');
        applyHistoryFilter();
    });

    $('#nursing_print_chart').on('click', function () {
        var chartDate = $('#nursing_chart_date').val() || '<?= date('Y-m-d') ?>';
        var nurseName = $('#nursing_filter_nurse').val() || '';
        var url = '<?= esc($printUrl) ?>?date=' + encodeURIComponent(chartDate);
        if (nurseName !== '') {
            url += '&nurse=' + encodeURIComponent(nurseName);
        }
        window.open(url, '_blank');
    });

    $('#nursing_bedside_item_type').on('change', function () {
        nursingRefreshBedsideCategories();
        nursingRefreshBedsideItems();
    });
    $('#nursing_bedside_category').on('change', nursingRefreshBedsideItems);
    $('#nursing_bedside_item_search').on('input', nursingRefreshBedsideItems);
    $('#nursing_bedside_item_id').on('change', nursingSetBedsideRate);
    $('#nursing_doctor_visit_doc_id').on('change', function () {
        nursingRefreshDoctorVisitTypes();
        nursingRefreshDoctorVisitRate();
    });
    $('#nursing_doctor_visit_fee_type').on('change', nursingRefreshDoctorVisitRate);
    $('#nursing_add_bedside_charge').on('click', nursingAddBedsideCharge);
    $('#nursing_add_doctor_visit_charge').on('click', nursingAddDoctorVisitCharge);

    nursingRefreshBedsideTypeOptions();
    nursingRefreshBedsideCategories();
    nursingRefreshBedsideItems();
    nursingRefreshDoctorVisitTypes();
    nursingRefreshDoctorVisitRate();
})();
</script>
