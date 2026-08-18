<?php
$rows   = $rows   ?? [];
$edit   = $edit_row ?? [];
$editId = (int) ($edit['id'] ?? 0);

$tmplName    = (string) ($edit['template_name']    ?? '');
$headerHtml  = (string) ($edit['header_html']      ?? '');
$footerHtml  = (string) ($edit['footer_html']      ?? '');
$templateCss = (string) ($edit['template_css']     ?? '');
$templateHtml= (string) ($edit['template_html']    ?? '{{CONTENT}}');
$pageSize    = strtoupper((string) ($edit['page_size'] ?? 'A4'));
$marginTop   = (string) ($edit['page_margin_top_cm']    ?? '3.00');
$marginBottom= (string) ($edit['page_margin_bottom_cm'] ?? '0.50');
$marginLeft  = (string) ($edit['page_margin_left_cm']   ?? '0.80');
$marginRight = (string) ($edit['page_margin_right_cm']  ?? '0.80');
$marginHeader= (string) ($edit['margin_header_cm']      ?? '1.00');
$marginFooter= (string) ($edit['margin_footer_cm']      ?? '0.50');
$isDefault   = (int) ($edit['is_default']   ?? 0);
$isAuditOnly = (int) ($edit['is_audit_only'] ?? 0);
$status      = (int) ($edit['status'] ?? 1);

$placeholders = [
    'Hospital' => [
        'H_Name' => 'Hospital Name', 'hospital_address' => 'Hospital Address',
        'H_phone_No' => 'Hospital Phone', 'H_Email' => 'Hospital Email',
        'H_logo' => 'Hospital Logo (filename)', 'H_logo_abs' => 'Hospital Logo (absolute path for mPDF)',
    ],
    'Patient' => [
        'PATIENT_NAME' => 'Patient Name', 'UHID' => 'UHID', 'AGE_GENDER' => 'Age / Gender',
        'GUARDIAN' => 'Guardian', 'PATIENT_ADDRESS' => 'Address', 'PATIENT_PHONE' => 'Phone',
        'INSURANCE_COMPANY' => 'TPA/Insurance', 'DEPARTMENT' => 'Department', 'DOCTOR_NAMES' => 'Doctors',
    ],
    'IPD Info' => [
        'IPD_CODE' => 'IPD No.', 'ADMIT_DATE' => 'Admission Date', 'ADMISSION_TIME' => 'Admission Time',
        'DISCHARGE_DATE' => 'Discharge Date', 'DISCHARGE_TIME' => 'Discharge Time',
        'DISCHARGE_STATUS' => 'Discharge Status Heading',
    ],
    'Clinical Content' => [
        'CONTENT' => 'Full discharge content (all sections)',
        'FINAL_DIAGNOSIS' => 'Final Diagnosis', 'SURGERY' => 'Surgery', 'PROCEDURE' => 'Procedure',
        'PRESENTING_COMPLAINTS' => 'Presenting Complaints',
        'GENERAL_EXAM_ADMISSION' => 'General Exam on Admission',
        'CLINICAL_INVESTIGATION_REPORTS' => 'Investigation Reports',
        'COURSE_IN_HOSPITAL' => 'Course in Hospital',
        'EXAMINATION_ON_DISCHARGE' => 'Exam on Discharge',
        'DISCHARGE_MEDICATIONS' => 'Discharge Medications',
        'DISCHARGE_SUMMARY' => 'Summary/Advice', 'REVIEW_AFTER' => 'Review After',
        'DIETARY_ADVICE' => 'Dietary Advice',
        'DRUG_ALLERGY_ADR' => 'Drug Allergy/ADR', 'CO_MORBIDITIES' => 'Co-Morbidities',
        'PERSONAL_HISTORY' => 'Personal History',
        'PATIENT_INFO_TABLE' => 'Pre-built Patient Info Table',
    ],
    'Print Meta' => [
        'CURRENT_DATE' => 'Print Date', 'PRINT_TIME' => 'Print Date/Time',
    ],
];
?>
<section class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="mb-0">IPD Discharge Template Builder</h1>
        <a class="btn btn-outline-secondary btn-sm" href="javascript:load_form('<?= base_url('Ipd_discharge/print_template_builder') ?>?mode=list','IPD Discharge Templates');">
            ← Template List
        </a>
    </div>
</section>

<section class="content mt-3">
    <?= csrf_field() ?>
    <input type="hidden" id="tpl_edit_id" value="<?= $editId ?>">
    <div id="tpl_edit_msg" class="mb-2"></div>

    <div class="row">
        <!-- Left: Editor -->
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header py-2"><strong><?= $editId > 0 ? 'Edit Template' : 'New Template' ?></strong></div>
                <div class="card-body">
                    <div class="row g-2 mb-2">
                        <div class="col-8">
                            <label class="form-label form-label-sm">Template Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="tpl_name" value="<?= esc($tmplName) ?>">
                        </div>
                        <div class="col-4">
                            <label class="form-label form-label-sm">Status</label>
                            <select class="form-select form-select-sm" id="tpl_status">
                                <option value="1" <?= $status === 1 ? 'selected' : '' ?>>Active</option>
                                <option value="0" <?= $status === 0 ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-4">
                            <label class="form-label form-label-sm">Page Size</label>
                            <select class="form-select form-select-sm" id="tpl_page_size">
                                <?php foreach (['A4','A4-L','A5','A6','LETTER','LEGAL'] as $ps): ?>
                                    <option value="<?= $ps ?>" <?= $pageSize === $ps ? 'selected' : '' ?>><?= $ps ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label form-label-sm">Top (cm)</label>
                            <input type="number" class="form-control form-control-sm" id="tpl_margin_top" step="0.1" min="0" max="25" value="<?= esc($marginTop) ?>">
                        </div>
                        <div class="col-4">
                            <label class="form-label form-label-sm">Bottom (cm)</label>
                            <input type="number" class="form-control form-control-sm" id="tpl_margin_bottom" step="0.1" min="0" max="25" value="<?= esc($marginBottom) ?>">
                        </div>
                        <div class="col-4">
                            <label class="form-label form-label-sm">Left (cm)</label>
                            <input type="number" class="form-control form-control-sm" id="tpl_margin_left" step="0.1" min="0" max="25" value="<?= esc($marginLeft) ?>">
                        </div>
                        <div class="col-4">
                            <label class="form-label form-label-sm">Right (cm)</label>
                            <input type="number" class="form-control form-control-sm" id="tpl_margin_right" step="0.1" min="0" max="25" value="<?= esc($marginRight) ?>">
                        </div>
                        <div class="col-4">
                            <label class="form-label form-label-sm">Header (cm)</label>
                            <input type="number" class="form-control form-control-sm" id="tpl_margin_header" step="0.1" min="0" max="25" value="<?= esc($marginHeader) ?>">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label form-label-sm">Header HTML</label>
                        <textarea id="tpl_header_html" class="form-control form-control-sm" rows="4" style="font-family:Consolas,monospace;"><?= esc($headerHtml) ?></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm">Footer HTML</label>
                        <textarea id="tpl_footer_html" class="form-control form-control-sm" rows="3" style="font-family:Consolas,monospace;"><?= esc($footerHtml) ?></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm">HTML Content <span class="text-muted small">(use <code>{{CONTENT}}</code> or section placeholders)</span></label>
                        <textarea id="tpl_html" class="form-control form-control-sm" rows="10" style="font-family:Consolas,monospace;"><?= esc($templateHtml) ?></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm">CSS</label>
                        <textarea id="tpl_css" class="form-control form-control-sm" rows="5" style="font-family:Consolas,monospace;"><?= esc($templateCss) ?></textarea>
                    </div>
                    <div class="d-flex gap-2 mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="tpl_is_default" <?= $isDefault ? 'checked' : '' ?>>
                            <label class="form-check-label" for="tpl_is_default">Set as default</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="tpl_is_audit" <?= $isAuditOnly ? 'checked' : '' ?>>
                            <label class="form-check-label" for="tpl_is_audit">Audit-only (NABH)</label>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm" id="btn_save_tpl">
                            <?= $editId > 0 ? 'Update Template' : 'Create Template' ?>
                        </button>
                        <div class="ms-auto">
                            <label class="form-label form-label-sm mb-0">Preview IPD ID</label>
                            <input type="number" min="1" class="form-control form-control-sm d-inline-block" id="preview_ipd_id" style="width:100px;" placeholder="IPD ID">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btn_preview">Preview</button>
                            <button type="button" class="btn btn-outline-danger btn-sm" id="btn_pdf">PDF</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_debug_html" title="Show raw HTML sent to mPDF">Debug HTML</button>
                            <button type="button" class="btn btn-outline-dark btn-sm" id="btn_mpdf_input" title="View exact HTML saved immediately before WriteHTML">mPDF Input</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Placeholder reference -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header py-2"><strong>Placeholders</strong></div>
                <div class="card-body" style="max-height:80vh;overflow-y:auto;">
                    <input type="text" class="form-control form-control-sm mb-2" id="ph_search" placeholder="Search placeholders…">
                    <?php foreach ($placeholders as $group => $items): ?>
                        <div class="mb-2 ph-group">
                            <div class="text-muted small fw-bold mb-1"><?= esc($group) ?></div>
                            <?php foreach ($items as $token => $desc): ?>
                                <span class="badge text-bg-light border me-1 mb-1 ph-chip" style="cursor:pointer;font-size:11px;" data-token="{{<?= esc($token) ?>}}" title="<?= esc($desc) ?>">{{<?= esc($token) ?>}}</span>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    var editId = parseInt(document.getElementById('tpl_edit_id').value || '0', 10);
    var msg = document.getElementById('tpl_edit_msg');

    function getCsrf() {
        var el = document.querySelector('input[name^="csrf_"]');
        return el ? {name: el.name, value: el.value} : {name: 'csrf_test_name', value: ''};
    }

    function updateCsrf(data) {
        if (!data || !data.csrfName) return;
        var el = document.querySelector('input[name="' + data.csrfName + '"]');
        if (el) el.value = data.csrfHash || '';
    }

    function showMsg(text, ok) {
        if (!msg) return;
        msg.innerHTML = '<div class="alert alert-' + (ok ? 'success' : 'danger') + ' py-2 mb-0">' + text + '</div>';
    }

    function val(id) { var el = document.getElementById(id); return el ? el.value : ''; }
    function checked(id) { var el = document.getElementById(id); return el ? (el.checked ? 1 : 0) : 0; }

    document.getElementById('btn_save_tpl').addEventListener('click', function () {
        var tplName = val('tpl_name').trim();
        var tplHtml = val('tpl_html').trim();
        if (!tplName || !tplHtml) { showMsg('Template name and HTML content are required.', false); return; }

        var csrf = getCsrf();
        var fd = new FormData();
        fd.append(csrf.name, csrf.value);
        if (editId > 0) fd.append('id', String(editId));
        fd.append('template_name', tplName);
        fd.append('header_html', val('tpl_header_html'));
        fd.append('footer_html', val('tpl_footer_html'));
        fd.append('template_css', val('tpl_css'));
        fd.append('template_html', tplHtml);
        fd.append('page_size', val('tpl_page_size'));
        fd.append('page_margin_top_cm', val('tpl_margin_top'));
        fd.append('page_margin_bottom_cm', val('tpl_margin_bottom'));
        fd.append('page_margin_left_cm', val('tpl_margin_left'));
        fd.append('page_margin_right_cm', val('tpl_margin_right'));
        fd.append('margin_header_cm', val('tpl_margin_header'));
        fd.append('margin_footer_cm', '0.5');
        fd.append('status', val('tpl_status'));
        fd.append('is_default', String(checked('tpl_is_default')));
        fd.append('is_audit_only', String(checked('tpl_is_audit')));

        fetch('<?= base_url('setting/template/discharge_templates') ?>', {
            method: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: fd
        }).then(function (r) { return r.json(); }).then(function (data) {
            updateCsrf(data);
            if (data.update == 1) {
                showMsg(editId > 0 ? 'Template updated.' : 'Template created.', true);
                if (!editId) {
                    load_form('<?= base_url('Ipd_discharge/print_template_builder') ?>?mode=edit&edit=' + encodeURIComponent(data.id || 0), 'IPD Discharge Template Edit');
                }
            } else {
                showMsg(data.error_text || 'Save failed.', false);
            }
        }).catch(function () { showMsg('Request failed.', false); });
    });

    document.getElementById('btn_preview').addEventListener('click', function () {
        var ipdId = parseInt(val('preview_ipd_id'), 10);
        if (!ipdId) { alert('Enter IPD ID for preview.'); return; }
        window.open('<?= base_url('Ipd_discharge/preview_discharge_report') ?>/' + ipdId + (editId > 0 ? '?tpl=' + editId : ''), '_blank');
    });

    document.getElementById('btn_pdf').addEventListener('click', function () {
        var ipdId = parseInt(val('preview_ipd_id'), 10);
        if (!ipdId) { alert('Enter IPD ID for PDF.'); return; }
        window.open('<?= base_url('Ipd_discharge/show_discharge') ?>/' + ipdId + '/1' + (editId > 0 ? '?tpl=' + editId : ''), '_blank');
    });

    document.getElementById('btn_debug_html').addEventListener('click', function () {
        var ipdId = parseInt(val('preview_ipd_id'), 10);
        if (!ipdId) { alert('Enter IPD ID to see mPDF debug HTML.'); return; }
        window.open('<?= base_url('Ipd_discharge/show_discharge') ?>/' + ipdId + '/1?html=1' + (editId > 0 ? '&tpl=' + editId : ''), '_blank');
    });

    document.getElementById('btn_mpdf_input').addEventListener('click', function () {
        var ipdId = parseInt(val('preview_ipd_id'), 10);
        if (!ipdId || !editId) { alert('Save the template and enter IPD ID first.'); return; }
        window.open('<?= base_url('Ipd_discharge/debug_mpdf_input') ?>/' + ipdId + '/' + editId + '?build=1', '_blank');
    });

    /* Placeholder click-to-copy */
    document.querySelectorAll('.ph-chip').forEach(function (chip) {
        chip.addEventListener('click', function () {
            var token = chip.getAttribute('data-token');
            var active = document.activeElement;
            var targets = ['tpl_header_html', 'tpl_footer_html', 'tpl_html', 'tpl_css'];
            var target = null;
            targets.forEach(function (id) { if (active && active.id === id) target = active; });
            if (!target) target = document.getElementById('tpl_html');
            if (!target) return;
            var start = target.selectionStart;
            var end = target.selectionEnd;
            target.setRangeText(token, start, end, 'end');
            target.focus();
        });
    });

    /* Placeholder search */
    document.getElementById('ph_search').addEventListener('input', function () {
        var q = this.value.toLowerCase();
        document.querySelectorAll('.ph-chip').forEach(function (chip) {
            chip.style.display = !q || chip.dataset.token.toLowerCase().includes(q) ? '' : 'none';
        });
        document.querySelectorAll('.ph-group').forEach(function (group) {
            var visible = [...group.querySelectorAll('.ph-chip')].some(function (c) { return c.style.display !== 'none'; });
            group.style.display = visible ? '' : 'none';
        });
    });
})();
</script>
