<?php
/**
 * Modern Hospital Admission Desk (IPD, Day Care, Emergency / Casualty)
 * Compliant with NABH, HL7 FHIR R4, and ESI Triage Standards.
 */
?>
<style>
  .hms-admission-desk {
    --desk-primary: #0284c7;
    --desk-primary-dark: #0369a1;
    --desk-ipd: #2563eb;
    --desk-daycare: #d97706;
    --desk-emergency: #dc2626;
    --desk-surface: #ffffff;
    --desk-bg: #f8fafc;
    --desk-border: #e2e8f0;
    --desk-text-dark: #0f172a;
    --desk-text-muted: #64748b;
    font-family: inherit;
    color: var(--desk-text-dark);
    padding-bottom: 90px;
  }

  .hms-admission-desk .desk-card {
    background: var(--desk-surface);
    border: 1px solid var(--desk-border);
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
    margin-bottom: 20px;
    overflow: hidden;
    transition: box-shadow .2s ease;
  }

  .hms-admission-desk .desk-card:hover {
    box-shadow: 0 6px 20px rgba(15, 23, 42, 0.07);
  }

  .hms-admission-desk .desk-card-header {
    background: #ffffff;
    border-bottom: 1px solid var(--desk-border);
    padding: 14px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
  }

  .hms-admission-desk .desk-card-header .card-title {
    font-size: 15px;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .hms-admission-desk .desk-card-body {
    padding: 20px;
  }

  /* Patient Demographics Executive Banner */
  .hms-admission-desk .patient-hero-card {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: #ffffff;
    border-radius: 14px;
    padding: 20px 24px;
    margin-bottom: 22px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.15);
    position: relative;
    overflow: hidden;
  }

  .hms-admission-desk .patient-hero-card::after {
    content: '';
    position: absolute;
    top: -50px;
    right: -50px;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(56, 189, 248, 0.15) 0%, rgba(255,255,255,0) 70%);
    border-radius: 50%;
    pointer-events: none;
  }

  .hms-admission-desk .patient-avatar {
    width: 58px;
    height: 58px;
    border-radius: 12px;
    background: linear-gradient(135deg, #0284c7 0%, #38bdf8 100%);
    color: #ffffff;
    font-size: 22px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(2, 132, 199, 0.4);
    flex-shrink: 0;
  }

  /* Interactive Admission Type Cards */
  .hms-admission-desk .adm-type-card {
    border: 2px solid var(--desk-border);
    border-radius: 12px;
    padding: 16px 18px;
    cursor: pointer;
    background: #ffffff;
    transition: all .2s ease;
    height: 100%;
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }

  .hms-admission-desk .adm-type-card:hover {
    border-color: #94a3b8;
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(0, 0, 0, 0.05);
  }

  .hms-admission-desk .adm-type-radio:checked + .adm-type-card.card-ipd {
    border-color: #2563eb;
    background: #f0f7ff;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
  }

  .hms-admission-desk .adm-type-radio:checked + .adm-type-card.card-daycare {
    border-color: #d97706;
    background: #fffbeb;
    box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.15);
  }

  .hms-admission-desk .adm-type-radio:checked + .adm-type-card.card-emergency {
    border-color: #dc2626;
    background: #fef2f2;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.15);
  }

  .hms-admission-desk .adm-type-card .card-badge-check {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid #cbd5e1;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all .2s;
  }

  .hms-admission-desk .adm-type-radio:checked + .adm-type-card .card-badge-check {
    background: #0f172a;
    border-color: #0f172a;
    color: #ffffff;
  }

  .hms-admission-desk .adm-type-radio:checked + .adm-type-card.card-ipd .card-badge-check {
    background: #2563eb;
    border-color: #2563eb;
  }

  .hms-admission-desk .adm-type-radio:checked + .adm-type-card.card-daycare .card-badge-check {
    background: #d97706;
    border-color: #d97706;
  }

  .hms-admission-desk .adm-type-radio:checked + .adm-type-card.card-emergency .card-badge-check {
    background: #dc2626;
    border-color: #dc2626;
  }

  .hms-admission-desk .adm-type-radio:checked + .adm-type-card .card-badge-check::after {
    content: '✓';
    font-size: 11px;
    font-weight: 900;
  }

  /* Procedure & Chip Selectors */
  .hms-admission-desk .chip-tag {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #334155;
    transition: all .15s;
    user-select: none;
    margin-right: 6px;
    margin-bottom: 6px;
  }

  .hms-admission-desk .chip-tag:hover {
    background: #e2e8f0;
    color: #0f172a;
  }

  .hms-admission-desk .chip-tag.active {
    background: #0f172a;
    border-color: #0f172a;
    color: #ffffff;
  }

  /* Form Control Refinements */
  .hms-admission-desk .form-label {
    font-size: 13px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 4px;
  }

  .hms-admission-desk .form-control,
  .hms-admission-desk .form-select {
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 13.5px;
    color: #0f172a;
    background-color: #ffffff;
    transition: border-color .15s ease, box-shadow .15s ease;
  }

  .hms-admission-desk .form-control:focus,
  .hms-admission-desk .form-select:focus {
    border-color: #0284c7;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
    outline: 0;
  }

  /* Doctor Search & Multi-Select Box */
  .hms-admission-desk .doctor-container {
    max-height: 230px;
    overflow-y: auto;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 8px;
    background: #ffffff;
  }

  .hms-admission-desk .doctor-item {
    padding: 8px 10px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    transition: background .15s;
    margin-bottom: 4px;
  }

  .hms-admission-desk .doctor-item:hover {
    background: #f1f5f9;
  }

  .hms-admission-desk .doctor-item.selected {
    background: #e0f2fe;
    border-left: 3px solid #0284c7;
  }

  /* Sticky Footer Action Bar */
  .hms-admission-desk .sticky-action-bar {
    position: sticky;
    bottom: 0;
    z-index: 100;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(8px);
    border-top: 1px solid #e2e8f0;
    padding: 14px 24px;
    box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.06);
    border-radius: 12px 12px 0 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
  }

  /* Triage Indicator Badges */
  .triage-badge-1 { background:#dc2626; color:#fff; }
  .triage-badge-2 { background:#ea580c; color:#fff; }
  .triage-badge-3 { background:#ca8a04; color:#fff; }
  .triage-badge-4 { background:#16a34a; color:#fff; }
  .triage-badge-5 { background:#2563eb; color:#fff; }
</style>

<div class="hms-admission-desk container-fluid px-0">

  <!-- Breadcrumb & Top Bar -->
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
          <li class="breadcrumb-item"><a href="javascript:void(0);" onclick="load_form('<?= base_url('billing/ipd') ?>','IPD List')" class="text-decoration-none">IPD Billing</a></li>
          <li class="breadcrumb-item"><a href="javascript:void(0);" onclick="load_form('<?= base_url('billing/ipd/admit') ?>','Admission Desk')" class="text-decoration-none">Admission Desk</a></li>
          <li class="breadcrumb-item active" aria-current="page">Patient Registration</li>
        </ol>
      </nav>
      <h3 class="mb-0 fw-bold text-dark">
        <i class="bi bi-hospital me-2 text-primary"></i>Hospital Admission Desk
      </h3>
    </div>
    <div class="d-flex gap-2">
      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="load_form('<?= base_url('billing/ipd/current-admission') ?>', 'Current Admissions')">
        <i class="bi bi-arrow-left me-1"></i> Current Admissions
      </button>
      <button type="button" class="btn btn-outline-primary btn-sm" onclick="load_form('<?= base_url('billing/ipd/admit') ?>', 'Admission Desk')">
        <i class="bi bi-search me-1"></i> Switch Patient
      </button>
    </div>
  </div>

  <?php if (empty($person_info)): ?>
    <div class="alert alert-danger shadow-sm border-0 rounded-3 p-4">
      <i class="bi bi-exclamation-triangle-fill fs-4 me-2"></i>
      <strong>Patient record not found.</strong> Please return to the admission search screen.
    </div>
  <?php else: 
    // Full patient name is stored in p_fname (p_lname is deprecated/unused)
    $fullName = ucwords(trim($person_info->p_fname ?? ''));
    $nameParts = preg_split('/\s+/', $fullName);
    $initials = strtoupper(substr($nameParts[0] ?? '', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
    if ($initials === '') $initials = 'PT';

    $relativeInfo = trim(($person_info->p_relative ?? '') . ' ' . ($person_info->p_rname ?? ''));
    $curAdmType = strtolower($default_admission_type ?? 'ipd');
  ?>

  <!-- Executive Patient Demographics Banner -->
  <div class="patient-hero-card">
    <div class="row align-items-center g-3">
      <div class="col-auto">
        <div class="patient-avatar"><?= esc($initials) ?></div>
      </div>
      <div class="col">
        <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
          <h4 class="mb-0 fw-bold text-white"><?= esc($fullName) ?></h4>
          <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1">
            <i class="bi bi-person-vcard me-1"></i><?= esc($person_info->p_code ?? ('#' . $person_info->id)) ?>
          </span>
          <?php if (!empty($case_master)): ?>
            <span class="badge bg-success px-2 py-1"><i class="bi bi-shield-check me-1"></i>Org. Credit: <?= esc($case_master[0]->case_id_code ?? '') ?></span>
          <?php else: ?>
            <span class="badge bg-light text-dark px-2 py-1"><i class="bi bi-cash me-1"></i>Direct Cash</span>
          <?php endif; ?>
        </div>
        <div class="d-flex align-items-center flex-wrap gap-3 text-light small opacity-90">
          <?php if ($relativeInfo !== ''): ?>
            <span><i class="bi bi-people me-1"></i><?= esc($relativeInfo) ?></span>
          <?php endif; ?>
          <span><i class="bi bi-gender-ambiguous me-1"></i><strong>Gender:</strong> <?= esc($person_info->xgender ?? '-') ?></span>
          <span><i class="bi bi-calendar3 me-1"></i><strong>Age:</strong> <?= esc($person_info->age ?? '-') ?></span>
          <span><i class="bi bi-telephone me-1"></i><strong>Phone:</strong> <?= esc($person_info->mphone1 ?? '-') ?></span>
        </div>
      </div>
    </div>
  </div>

  <form action="<?= base_url('IpdNew/AddNew') ?>" role="form" class="form1" method="post" accept-charset="utf-8" id="hmsAdmissionForm">
    <?= csrf_field() ?>
    <input type="hidden" id="pid" name="pid" value="<?= esc($person_info->id ?? 0) ?>" />
    <input type="hidden" id="pname" name="pname" value="<?= esc($person_info->p_fname ?? '') ?>" />

    <!-- Submission Error Feedback Banner -->
    <div class="jsError mb-3"></div>

    <!-- 1. Admission Category & Clinical Urgency -->
    <div class="desk-card">
      <div class="desk-card-header">
        <h5 class="card-title text-dark">
          <i class="bi bi-diagram-3-fill text-primary"></i>1. Admission Category &amp; Clinical Pathway
        </h5>
        <span class="badge bg-light text-secondary border">Medical Standard Triage &amp; Bed Protocols</span>
      </div>
      <div class="desk-card-body">
        <div class="row g-3 mb-3">
          <!-- Inpatient Option -->
          <div class="col-md-4">
            <input class="btn-check adm-type-radio js-adm-type" type="radio" name="admission_type" id="type_ipd" value="ipd" <?= ($curAdmType === 'ipd') ? 'checked="checked"' : '' ?>>
            <label class="adm-type-card card-ipd w-100" for="type_ipd">
              <div class="card-badge-check"></div>
              <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                  <span class="badge bg-primary text-white p-2 rounded-circle"><i class="bi bi-hospital fs-6"></i></span>
                  <span class="fw-bold text-dark fs-6">Inpatient (IPD)</span>
                </div>
                <p class="small text-muted mb-0">Standard multi-day inpatient stay (General Ward, Semi-Private, Deluxe, or ICU care).</p>
              </div>
              <div class="mt-3 pt-2 border-top">
                <span class="badge bg-primary-subtle text-primary small">Prefix: A-YYYY-XXXXX</span>
              </div>
            </label>
          </div>

          <!-- Day Care Option -->
          <div class="col-md-4">
            <input class="btn-check adm-type-radio js-adm-type" type="radio" name="admission_type" id="type_daycare" value="daycare" <?= ($curAdmType === 'daycare') ? 'checked="checked"' : '' ?>>
            <label class="adm-type-card card-daycare w-100" for="type_daycare">
              <div class="card-badge-check"></div>
              <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                  <span class="badge bg-warning text-dark p-2 rounded-circle"><i class="bi bi-clock-history fs-6"></i></span>
                  <span class="fw-bold text-dark fs-6">Day Care Unit (&lt; 24 Hrs)</span>
                </div>
                <p class="small text-muted mb-0">Short stay procedures without overnight stay (Chemo, Dialysis, Cataract, Endoscopy).</p>
              </div>
              <div class="mt-3 pt-2 border-top">
                <span class="badge bg-warning-subtle text-warning small">Prefix: DC-YYYY-XXXXX</span>
              </div>
            </label>
          </div>

          <!-- Emergency Option -->
          <div class="col-md-4">
            <input class="btn-check adm-type-radio js-adm-type" type="radio" name="admission_type" id="type_emergency" value="emergency" <?= ($curAdmType === 'emergency') ? 'checked="checked"' : '' ?>>
            <label class="adm-type-card card-emergency w-100" for="type_emergency">
              <div class="card-badge-check"></div>
              <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                  <span class="badge bg-danger text-white p-2 rounded-circle"><i class="bi bi-ambulance fs-6"></i></span>
                  <span class="fw-bold text-dark fs-6">Emergency (Casualty)</span>
                </div>
                <p class="small text-muted mb-0">Acute resuscitation, severe trauma, immediate clinical stabilization &amp; ESI Triage 1–5.</p>
              </div>
              <div class="mt-3 pt-2 border-top">
                <span class="badge bg-danger-subtle text-danger small">Prefix: ER-YYYY-XXXXX</span>
              </div>
            </label>
          </div>
        </div>

        <!-- Day Care Contextual Drawer -->
        <div id="daycare_section" class="p-3 mb-2 rounded-3 border" style="background:#fffbeb; border-color:#fde68a !important; display:none;">
          <div class="d-flex align-items-center gap-2 mb-2 text-warning">
            <i class="bi bi-clock-history fs-5"></i>
            <h6 class="mb-0 fw-bold text-dark">Day Care Clinical Protocols &amp; Planned Procedure</h6>
          </div>
          <div class="mb-3">
            <label class="form-label" for="daycare_procedure_name">
              <i class="bi bi-prescription2 text-warning me-1"></i>Planned Day Care Procedure / Clinical Indication:
            </label>
            <input type="text" class="form-control form-control-lg" id="daycare_procedure_name" name="daycare_procedure_name" placeholder="e.g. Cataract Surgery, Hemodialysis, Chemotherapy, Upper GI Endoscopy...">
          </div>
          <div>
            <span class="small fw-semibold text-secondary d-block mb-1">Quick Select Popular Procedures:</span>
            <div id="daycareChipsContainer">
              <span class="chip-tag" data-proc="Cataract Extraction with IOL">👁 Cataract Surgery</span>
              <span class="chip-tag" data-proc="Maintenance Hemodialysis">🩸 Hemodialysis</span>
              <span class="chip-tag" data-proc="Chemotherapy Infusion Session">💊 Chemotherapy</span>
              <span class="chip-tag" data-proc="Upper GI Diagnostic Endoscopy">🔬 Endoscopy</span>
              <span class="chip-tag" data-proc="Diagnostic Colonoscopy">🩺 Colonoscopy</span>
              <span class="chip-tag" data-proc="Excisional Biopsy / Minor OT">✂ Minor Surgery</span>
              <span class="chip-tag" data-proc="Short-Stay Medical Observation">⏱ Medical Observation</span>
            </div>
          </div>
          <div class="mt-2 text-muted small">
            <i class="bi bi-info-circle me-1"></i>Short stay &lt; 24 hours. If clinical complications occur, patient can be escalated to Inpatient via <strong>Convert to IPD</strong>.
          </div>
        </div>

        <!-- Emergency / Casualty Contextual Drawer -->
        <div id="emergency_section" class="p-3 mb-2 rounded-3 border" style="background:#fef2f2; border-color:#fecaca !important; display:none;">
          <div class="d-flex align-items-center gap-2 mb-2 text-danger">
            <i class="bi bi-heart-pulse-fill fs-5"></i>
            <h6 class="mb-0 fw-bold text-dark">Casualty Resuscitation &amp; ESI Severity Triage</h6>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="triage_level">
                <i class="bi bi-speedometer2 text-danger me-1"></i>Emergency Severity Index (ESI Triage Level):
              </label>
              <select class="form-select form-select-lg" id="triage_level" name="triage_level">
                <option value="1">🔴 ESI 1: Resuscitation (Immediate Life Threat)</option>
                <option value="2">🟠 ESI 2: Emergent (High Risk / Severe Distress / Confusion)</option>
                <option value="3" selected>🟡 ESI 3: Urgent (Stable Vitals, Multiple Resources)</option>
                <option value="4">🟢 ESI 4: Less Urgent (Requires 1 Diagnostic/Therapeutic Resource)</option>
                <option value="5">🔵 ESI 5: Non-Urgent (Routine Minor / Prescription Refill)</option>
              </select>
              <small class="text-muted d-block mt-1">NABH standard 5-level clinical triage classification.</small>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="brought_by">
                <i class="bi bi-ambulance text-danger me-1"></i>Mode of Arrival / Brought By:
              </label>
              <input type="text" class="form-control form-control-lg" id="brought_by" name="brought_by" placeholder="e.g. 108 Ambulance, Family Vehicle, Police Escort...">
              <div class="mt-2" id="broughtByChipsContainer">
                <span class="chip-tag" data-mode="108 Ambulance">🚑 108 Ambulance</span>
                <span class="chip-tag" data-mode="Private Ambulance">🚐 Private Ambulance</span>
                <span class="chip-tag" data-mode="Self / Relative Vehicle">🚗 Family / Self</span>
                <span class="chip-tag" data-mode="Police Escort">👮 Police Escort</span>
                <span class="chip-tag" data-mode="Walk-in Ambulatory">🚶 Walk-in</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. Next of Kin & Responsible Attendant -->
    <div class="desk-card">
      <div class="desk-card-header">
        <h5 class="card-title text-dark">
          <i class="bi bi-people-fill text-primary"></i>2. Next of Kin &amp; Primary Attendant Contact
        </h5>
        <span class="badge bg-light text-secondary border">Emergency Contact Protocol</span>
      </div>
      <div class="desk-card-body">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label" for="rp_name">Responsible Person / Attendant Name</label>
            <input type="text" class="form-control" id="rp_name" name="rp_name" placeholder="Full name of attendant" value="<?= esc($person_info->p_rname ?? '') ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label" for="r_relation">Relationship to Patient</label>
            <select class="form-select" id="r_relation" name="r_relation">
              <?php 
                $defaultRel = trim($person_info->p_relative ?? 'Spouse');
                $relations = ['Spouse', 'Father', 'Mother', 'Son', 'Daughter', 'Brother', 'Sister', 'Guardian', 'Self', 'Friend', 'Attendant', 'Other'];
              ?>
              <option value="">Select Relation</option>
              <?php foreach ($relations as $rel): ?>
                <option value="<?= esc($rel) ?>" <?= (strcasecmp($defaultRel, $rel) === 0 || ($rel === 'Father' && stripos($defaultRel, 'S/o') !== false)) ? 'selected' : '' ?>><?= esc($rel) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="phone1">Primary Contact Phone</label>
            <div class="input-group">
              <span class="input-group-text bg-light text-muted"><i class="bi bi-telephone"></i></span>
              <input type="text" name="phone1" id="phone1" class="form-control" placeholder="10-digit mobile" value="<?= esc($person_info->mphone1 ?? '') ?>" maxlength="10">
            </div>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="phone2">Alternate / Secondary Phone</label>
            <div class="input-group">
              <span class="input-group-text bg-light text-muted"><i class="bi bi-phone"></i></span>
              <input type="text" name="phone2" id="phone2" class="form-control" placeholder="Optional backup mobile" value="<?= esc($person_info->mphone2 ?? '') ?>" maxlength="10">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 3. Clinical Care Team, Bed & Placement -->
    <div class="desk-card">
      <div class="desk-card-header">
        <h5 class="card-title text-dark">
          <i class="bi bi-door-open-fill text-primary"></i>3. Care Team, Bed Allocation &amp; Admission Timing
        </h5>
        <span class="badge bg-light text-secondary border">Clinical Placement</span>
      </div>
      <div class="desk-card-body">
        <div class="row g-3 mb-3">
          <!-- Date -->
          <div class="col-md-3">
            <label class="form-label" for="res_date_display"><i class="bi bi-calendar-event text-primary me-1"></i>Admission Date</label>
            <input id="res_date_display" class="form-control" type="date" value="<?= date('Y-m-d') ?>" required>
            <input id="res_date" name="res_date" type="hidden" value="<?= date('d/m/Y') ?>" />
          </div>
          <!-- Time -->
          <div class="col-md-3">
            <label class="form-label" for="res_time"><i class="bi bi-clock text-primary me-1"></i>Admission Time (24h)</label>
            <input id="res_time" name="res_time" class="form-control" type="time" value="<?= date('H:i') ?>" step="60" required>
          </div>
          <!-- Department -->
          <div class="col-md-3">
            <label class="form-label" for="dept_id"><i class="bi bi-building text-primary me-1"></i>Department</label>
            <select class="form-select" name="dept_id" id="dept_id">
              <option value="0">Select Department</option>
              <?php foreach ($hc_department as $row): ?>
                <option value="<?= esc($row->iId ?? 0) ?>"><?= esc($row->vName ?? '') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <!-- Referral -->
          <div class="col-md-3">
            <label class="form-label" for="refer_by_list_search"><i class="bi bi-person-lines-fill text-primary me-1"></i>Referred By</label>
            <input type="hidden" name="refer_by_list" id="refer_by_list" value="0">
            <input class="form-control" id="refer_by_list_search" list="refer_by_list_options" placeholder="Type doctor / referral source..." autocomplete="off">
            <datalist id="refer_by_list_options">
              <?php foreach ($refer_master as $row): ?>
                <option value="<?= esc(trim(($row->title ?? '') . ' ' . ($row->f_name ?? ''))) ?>" data-id="<?= esc($row->id ?? 0) ?>"></option>
              <?php endforeach; ?>
            </datalist>
          </div>
        </div>

        <div class="row g-3">
          <!-- Room / Bed Selection -->
          <div class="col-md-6">
            <label class="form-label" for="room_list">
              <i class="bi bi-hospital text-primary me-1"></i>Allocate Room / Bed <span class="text-danger">*</span>
            </label>
            <select class="form-select form-select-lg" name="room_list" id="room_list" required>
              <option value="">-- Choose Available Bed --</option>
              <?php foreach ($ipd_bed_list as $row): 
                $wtype = strtolower($row->ward_type ?? '');
              ?>
                <option value="<?= esc($row->id ?? 0) ?>" data-ward-type="<?= esc($wtype) ?>">
                  <?= esc($row->Bed_Desc ?? '') ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div id="smartBedHint" class="small text-muted mt-1">
              <i class="bi bi-lightbulb me-1"></i>Select an available bed suitable for the patient's care category.
            </div>
          </div>

          <!-- Attending Doctor Selection -->
          <div class="col-md-6">
            <label class="form-label">
              <i class="bi bi-person-badge text-primary me-1"></i>Attending Doctor(s) <span class="text-danger">*</span>
            </label>
            <div class="doctor-container" id="doctorContainer">
              <input type="text" class="form-control form-control-sm mb-2" id="docFilterInput" placeholder="Filter doctor name or specialty...">
              <div id="doctorListWrapper">
                <?php foreach ($doc_spec_l as $row): ?>
                  <label class="doctor-item">
                    <div class="d-flex align-items-center gap-2">
                      <input type="checkbox" name="doc_id[]" class="form-check-input js-doc-check" value="<?= esc($row->id ?? 0) ?>">
                      <span class="fw-semibold text-dark"><?= esc($row->p_fname ?? '') ?></span>
                    </div>
                    <?php if (!empty($row->SpecName)): ?>
                      <span class="badge bg-light text-secondary border small"><?= esc($row->SpecName) ?></span>
                    <?php endif; ?>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
            <small class="text-muted d-block mt-1">Select one or more primary attending consultants.</small>
          </div>
        </div>
      </div>
    </div>

    <!-- 4. Provisional Diagnosis, Assessment & Clinical Notes -->
    <div class="desk-card">
      <div class="desk-card-header">
        <h5 class="card-title text-dark">
          <i class="bi bi-clipboard2-pulse-fill text-primary"></i>4. Clinical Diagnosis &amp; Initial Assessment
        </h5>
        <span class="badge bg-light text-secondary border">Clinical Documentation</span>
      </div>
      <div class="desk-card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label" for="problem">
              <i class="bi bi-file-medical text-primary me-1"></i>Provisional Diagnosis / Chief Reason for Admission:
            </label>
            <input class="form-control form-control-lg" type="text" id="problem" name="problem" placeholder="e.g. Acute Appendicitis, Senile Cataract RE, Uncontrolled Diabetes, Road Accident Trauma..." />
            <small class="text-muted d-block mt-1">Primary ICD clinical indication triggering hospital admission.</small>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="remark">
              <i class="bi bi-chat-left-text text-primary me-1"></i>Initial Clinical Notes / Presenting Assessment:
            </label>
            <textarea id="remark" name="remark" class="form-control" rows="3" placeholder="Presenting complaints, vital signs on arrival, known drug allergies, or specific care instructions..."></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- 5. Medico-Legal (MLC) & Financial Billing Category -->
    <div class="desk-card">
      <div class="desk-card-header">
        <h5 class="card-title text-dark">
          <i class="bi bi-shield-lock-fill text-primary"></i>5. Medico-Legal (MLC) &amp; Billing Classification
        </h5>
        <span class="badge bg-light text-secondary border">Administrative &amp; Statutory Compliance</span>
      </div>
      <div class="desk-card-body">
        <div class="row g-3 align-items-center mb-2">
          <!-- MLC Switch -->
          <div class="col-md-6">
            <label class="form-label d-block">Medico-Legal Status (MLC):</label>
            <div class="d-flex gap-3 align-items-center">
              <div class="form-check form-check-inline">
                <input class="form-check-input js-mlc-toggle" type="radio" name="optionsRadios_mlc" id="options_mlc1" value="0" checked="checked">
                <label class="form-check-label fw-semibold" for="options_mlc1">Non-MLC (General Medical / Surgical)</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input js-mlc-toggle" type="radio" name="optionsRadios_mlc" id="options_mlc2" value="1">
                <label class="form-check-label fw-bold text-danger" for="options_mlc2">
                  <i class="bi bi-shield-exclamation me-1"></i>MLC (Medico-Legal Case)
                </label>
              </div>
            </div>
          </div>

          <!-- Billing Category -->
          <div class="col-md-6">
            <label class="form-label d-block">Billing / Financial Classification:</label>
            <?php if (!empty($case_master)): ?>
              <div class="d-flex gap-3 align-items-center">
                <div class="form-check form-check-inline">
                  <input class="form-check-input" name="optionsRadios_org" id="options_org1" value="0" type="radio">
                  <label class="form-check-label" for="options_org1">Direct Cash</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" name="optionsRadios_org" id="options_morg2" value="<?= esc($case_master[0]->id ?? 0) ?>" type="radio" checked="checked">
                  <label class="form-check-label fw-bold text-success" for="options_morg2">
                    <i class="bi bi-building-check me-1"></i>Organization Credit: <?= esc($case_master[0]->case_id_code ?? '') ?>
                  </label>
                </div>
              </div>
            <?php else: ?>
              <span class="badge bg-light text-dark border p-2"><i class="bi bi-cash me-1"></i>Standard Direct Cash Account</span>
              <input name="optionsRadios_org" id="options_morg2" value="0" type="hidden">
            <?php endif; ?>
          </div>
        </div>

        <!-- MLC Expanded Fields -->
        <div id="mlc_details_block" class="p-3 mt-3 rounded-3 border" style="background:#fef2f2; border-color:#fca5a5 !important; display:none;">
          <div class="d-flex align-items-center gap-2 mb-2 text-danger">
            <i class="bi bi-shield-exclamation fs-5"></i>
            <h6 class="mb-0 fw-bold">Statutory MLC Documentation</h6>
          </div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label" for="mlc_number">MLC Register Number</label>
              <input type="text" class="form-control" id="mlc_number" name="mlc_number" placeholder="e.g. MLC/2026/0842">
            </div>
            <div class="col-md-4">
              <label class="form-label" for="police_station">Police Station Intimated</label>
              <input type="text" class="form-control" id="police_station" name="police_station" placeholder="e.g. Central City Police Station">
            </div>
            <div class="col-md-4">
              <label class="form-label" for="police_constable_details">Police Constable Details</label>
              <input type="text" class="form-control" id="police_constable_details" name="police_constable_details" placeholder="e.g. PC Verma (Badge #1042)">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Sticky Confirmation Action Bar -->
    <div class="sticky-action-bar shadow">
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="text-muted small">Admitting Patient:</span>
        <strong class="text-dark"><?= esc($fullName) ?></strong>
        <span class="badge bg-secondary font-monospace"><?= esc($person_info->p_code ?? ('#' . $person_info->id)) ?></span>
        <span class="text-muted small">&bull; Mode:</span>
        <span id="summaryAdmTypeBadge" class="badge bg-primary text-uppercase">Inpatient</span>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-outline-secondary px-3" onclick="load_form('<?= base_url('billing/ipd/current-admission') ?>','Current Admissions')">
          Cancel
        </button>
        <button type="submit" class="btn btn-primary btn-lg px-4 fw-bold shadow-sm" id="btnnextconfirm">
          <i class="bi bi-check2-circle me-1"></i> Confirm &amp; Admit Patient
        </button>
      </div>
    </div>
  </form>

  <?php endif; ?>

</div>

<script>
$(function() {
  // Referral Datalist Binding
  function bindReferByDatalist(hiddenSelector, inputSelector, listSelector) {
    var $hidden = $(hiddenSelector);
    var $input = $(inputSelector);
    var list = document.querySelector(listSelector);
    if (!$hidden.length || !$input.length || !list) return;

    function syncReferByValue() {
      var typedValue = ($input.val() || '').trim();
      var matchedId = '0';
      var options = list.querySelectorAll('option');
      options.forEach(function(opt) {
        if (matchedId !== '0') return;
        if ((opt.value || '').trim().toUpperCase() === typedValue.toUpperCase()) {
          matchedId = opt.getAttribute('data-id') || '0';
        }
      });
      $hidden.val(matchedId);
    }
    $input.on('input change blur', syncReferByValue);
  }
  bindReferByDatalist('#refer_by_list', '#refer_by_list_search', '#refer_by_list_options');

  // Legacy Date Synchronizer
  function formatLegacyDate(dateValue) {
    if (!dateValue) return '';
    var parts = String(dateValue).split('-');
    return parts.length === 3 ? [parts[2], parts[1], parts[0]].join('/') : dateValue;
  }

  function syncLegacyDateField() {
    $('#res_date').val(formatLegacyDate($('#res_date_display').val()));
  }
  syncLegacyDateField();
  $('#res_date_display').on('change', syncLegacyDateField);

  // Admission Type Switching & Bed Hinting
  function syncAdmissionTypeUI() {
    var selectedType = $('input[name="admission_type"]:checked').val() || 'ipd';
    var $summaryBadge = $('#summaryAdmTypeBadge');
    var $bedHint = $('#smartBedHint');

    if (selectedType === 'daycare') {
      $('#daycare_section').slideDown(200);
      $('#emergency_section').slideUp(200);
      $summaryBadge.text('Day Care (<24h)').attr('class', 'badge bg-warning text-dark text-uppercase');
      $bedHint.html('<i class="bi bi-lightbulb text-warning me-1"></i><strong>Recommendation:</strong> Select a Day Care short-stay bed (e.g. DC-01) for this procedure.');
    } else if (selectedType === 'emergency') {
      $('#emergency_section').slideDown(200);
      $('#daycare_section').slideUp(200);
      $summaryBadge.text('Emergency (Casualty)').attr('class', 'badge bg-danger text-uppercase');
      $bedHint.html('<i class="bi bi-lightbulb text-danger me-1"></i><strong>Recommendation:</strong> Select a Casualty resuscitation bed (e.g. ER-01) for immediate triage.');
    } else {
      $('#daycare_section').slideUp(200);
      $('#emergency_section').slideUp(200);
      $summaryBadge.text('Inpatient (IPD)').attr('class', 'badge bg-primary text-uppercase');
      $bedHint.html('<i class="bi bi-lightbulb text-primary me-1"></i>Select an appropriate Inpatient General Ward, Private, or ICU bed.');
    }
  }

  $('.js-adm-type').on('change', syncAdmissionTypeUI);
  syncAdmissionTypeUI();

  // Day Care Quick Chips
  $('#daycareChipsContainer').on('click', '.chip-tag', function() {
    var procName = $(this).attr('data-proc');
    $('#daycare_procedure_name').val(procName).focus();
    $('#daycareChipsContainer .chip-tag').removeClass('active');
    $(this).addClass('active');
  });

  // Emergency Brought By Chips
  $('#broughtByChipsContainer').on('click', '.chip-tag', function() {
    var mode = $(this).attr('data-mode');
    $('#brought_by').val(mode).focus();
    $('#broughtByChipsContainer .chip-tag').removeClass('active');
    $(this).addClass('active');
  });

  // MLC Toggle
  function syncMlcUI() {
    var isMlc = $('input[name="optionsRadios_mlc"]:checked').val() === '1';
    if (isMlc) {
      $('#mlc_details_block').slideDown(200);
    } else {
      $('#mlc_details_block').slideUp(200);
    }
  }
  $('.js-mlc-toggle').on('change', syncMlcUI);
  syncMlcUI();

  // Doctor Filter Search
  $('#docFilterInput').on('keyup input', function() {
    var term = $(this).val().toLowerCase();
    $('#doctorListWrapper .doctor-item').each(function() {
      var text = $(this).text().toLowerCase();
      $(this).toggle(text.indexOf(term) > -1);
    });
  });

  // Highlight Doctor Item on Checkbox Change
  $('#doctorListWrapper').on('change', '.js-doc-check', function() {
    $(this).closest('.doctor-item').toggleClass('selected', this.checked);
  });

  // Form Submit Handler
  $('#hmsAdmissionForm').on('submit', function(e) {
    e.preventDefault();

    // Ensure date is formatted dd/mm/YYYY
    syncLegacyDateField();

    var roomVal = $('#room_list').val();
    if (!roomVal || roomVal === '0' || roomVal === '') {
      alert('Please select a Room / Bed before admitting the patient.');
      $('#room_list').focus();
      return;
    }

    var checkedDocs = $('.js-doc-check:checked').length;
    if (checkedDocs === 0) {
      alert('Please select at least one Attending Doctor.');
      $('#docFilterInput').focus();
      return;
    }

    var $btn = $('#btnnextconfirm');
    var originalBtnHtml = $btn.html();
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>Admitting Patient...');

    $.ajax({
      url: '<?= base_url('IpdNew/AddNew') ?>',
      type: 'POST',
      data: $(this).serialize(),
      dataType: 'json'
    }).done(function(data) {
      if (Number(data.insertid || 0) === 0) {
        $btn.prop('disabled', false).html(originalBtnHtml);
        $('div.jsError').html(
          '<div class="alert alert-danger shadow-sm border-0 d-flex align-items-center">' +
          '<i class="bi bi-exclamation-octagon-fill fs-4 me-2"></i>' +
          '<div>' + (data.error_text || 'Unable to complete admission. Please verify required fields.') + '</div>' +
          '</div>'
        );
        $('html, body').animate({ scrollTop: 0 }, 200);
      } else {
        // Success: Redirect to active IPD Panel
        load_form('<?= base_url('IpdNew/ipd_panel') ?>/' + data.insertid, 'IPD Panel');
      }
    }).fail(function(xhr) {
      $btn.prop('disabled', false).html(originalBtnHtml);
      $('div.jsError').html(
        '<div class="alert alert-danger shadow-sm border-0 d-flex align-items-center">' +
        '<i class="bi bi-exclamation-triangle-fill fs-4 me-2"></i>' +
        '<div>Server communication error. Please try again.</div>' +
        '</div>'
      );
    });
  });
});
</script>
