<?php
$rows = $rows ?? [];
$edit = $edit_row ?? [];
$notice = (string) ($notice ?? '');
$noticeType = (string) ($notice_type ?? 'success');

$editId = (int) ($edit['id'] ?? 0);
$templateName = (string) ($edit['template_name'] ?? '');
$templateHtml = (string) ($edit['template_html'] ?? '<div>{{CONTENT}}</div>');
$headerHtml = (string) ($edit['header_html'] ?? '');
$footerHtml = (string) ($edit['footer_html'] ?? '');
$templateCss = (string) ($edit['template_css'] ?? '');
$pageSize = strtoupper((string) ($edit['page_size'] ?? 'A4'));
$customWidthMm = (int) ($edit['custom_width_mm'] ?? 210);
$customHeightMm = (int) ($edit['custom_height_mm'] ?? 297);
$marginTop = (string) ($edit['page_margin_top_cm'] ?? '0.8');
$marginBottom = (string) ($edit['page_margin_bottom_cm'] ?? '0.8');
$marginLeft = (string) ($edit['page_margin_left_cm'] ?? '0.8');
$marginRight = (string) ($edit['page_margin_right_cm'] ?? '0.8');
$marginHeader = (string) ($edit['margin_header_cm'] ?? '0.5');
$marginFooter = (string) ($edit['margin_footer_cm'] ?? '0.5');
$isDefault = (int) ($edit['is_default'] ?? 0);
$status = (int) ($edit['status'] ?? 1);
?>

<section class="content">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h6 class="mb-0">IPD Discharge Templates</h6>
            <a class="btn btn-sm btn-outline-secondary" href="javascript:load_form_div('<?= base_url('setting/template/discharge_templates') ?>','maindiv','IPD Discharge Template');">Reset</a>
        </div>
        <div class="card-body">
            <?php if ($notice !== ''): ?>
                <div class="alert alert-<?= esc($noticeType) ?> py-2 mb-3"><?= esc($notice) ?></div>
            <?php endif; ?>

            <div class="alert alert-info py-2 small">
                <strong>Available placeholders:</strong><br>
                <strong>1. Hospital Information:</strong> <code>{{hospital_name}}</code>, <code>{{H_address_1}}</code>, <code>{{H_address_2}}</code>, <code>{{hospital_phone}}</code>, <code>{{hospital_email}}</code>, <code>{{H_logo}}</code>, <code>{{H_logo_abs}}</code>, <code>{{hospital_address}}</code><br>
                <strong>2. Patient Information:</strong> <code>{{PATIENT_NAME}}</code>, <code>{{UHID}}</code>, <code>{{IPD_CODE}}</code>, <code>{{AGE_GENDER}}</code>, <code>{{GUARDIAN}}</code>, <code>{{GUARDIAN_RELATION}}</code>, <code>{{GUARDIAN_NAME}}</code>, <code>{{PATIENT_ADDRESS}}</code>, <code>{{PATIENT_PHONE}}</code> ✨<br>
                <strong>3. IPD Information:</strong> <code>{{DEPARTMENT}}</code>, <code>{{ADMIT_DATE}}</code>, <code>{{DISCHARGE_DATE}}</code>, <code>{{ADMISSION_TIME}}</code>, <code>{{DISCHARGE_TIME}}</code>, <code>{{ISDELIVERY}}</code>, <code>{{INSURANCE_COMPANY}}</code> ✨, <code>{{DOCTOR_NAMES}}</code> ✨, <code>{{DOCTOR_NAME}}</code> ✨<br>
                <strong>4. IPD Discharge Content:</strong> <code>{{CONTENT}}</code> (all sections), <code>{{PATIENT_INFO_TABLE}}</code> (pre-built patient table)<br>
                <strong>Section placeholders for custom layout/order:</strong> <code>{{DISCHARGE_SUMMARY}}</code>, <code>{{FINAL_DIAGNOSIS}}</code>, <code>{{SURGERY}}</code>, <code>{{PROCEDURE}}</code>, <code>{{PERSONAL_HISTORY}}</code>, <code>{{PRESENTING_COMPLAINTS}}</code>, <code>{{PAIN_MEASUREMENT_SCALE}}</code>, <code>{{GENERAL_EXAM_ADMISSION}}</code>, <code>{{CLINICAL_INVESTIGATION_REPORTS}}</code>, <code>{{COURSE_IN_HOSPITAL}}</code>, <code>{{EXAMINATION_ON_DISCHARGE}}</code>, <code>{{DRUG_ALLERGY_ADR}}</code>, <code>{{CO_MORBIDITIES}}</code>, <code>{{DISCHARGE_MEDICATIONS}}</code>, <code>{{DIETARY_ADVICE}}</code>, <code>{{OTHER_ADVICE}}</code>, <code>{{REVIEW_AFTER}}</code>, <code>{{FOLLOW_UP_INSTRUCTIONS}}</code>, <code>{{SIGNATURE_BLOCK}}</code><br>
                <strong>5. Common/Meta:</strong> <code>{{DISCHARGE_STATUS}}</code>, <code>{{CURRENT_DATE}}</code>, <code>{{PRINT_TIME}}</code><br>
                <small class="text-muted">✨ = Newly added tokens | All tokens are case-insensitive | Empty values auto-hide | Legacy aliases auto-normalized to preferred placeholders</small>
            </div>

            <div class="alert alert-warning py-2 small">
                NABH drafting checklist: include reason for admission, significant findings, diagnosis, procedures, course in hospital,
                condition at discharge, discharge medication with dose/duration, follow-up plan, and warning signs/emergency contact.
            </div>

            <div class="alert alert-secondary py-2 small">
                Page settings support: <code>A4</code>, <code>A4-L</code>, <code>A5</code>, <code>A6</code>, <code>LETTER</code>, <code>LEGAL</code>, <code>CUSTOM</code>.
            </div>

            <div class="card border-info-subtle mb-4">
                <div class="card-body py-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4 col-lg-3">
                            <label class="form-label small">IPD ID for Preview</label>
                            <input type="number" min="1" class="form-control form-control-sm" id="discharge_preview_ipd_id" placeholder="Enter IPD ID">
                        </div>
                        <div class="col-md-8 col-lg-9 d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btn_discharge_preview">Preview Discharge</button>
                            <button type="button" class="btn btn-outline-danger btn-sm" id="btn_discharge_pdf">Open PDF Print</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_discharge_placeholder_preview">Placeholder Data Preview</button>
                        </div>
                    </div>
                    <div class="small text-muted mt-2">
                        Uses the live discharge routes: <code>/Ipd_discharge/preview_discharge_report/{ipdId}</code>, <code>/Ipd_discharge/show_discharge/{ipdId}/1</code>, and <code>/Ipd_discharge/placeholder_preview/{ipdId}</code>.
                    </div>
                </div>
            </div>

            <div id="discharge_template_notice" class="mb-2"></div>

            <form method="post" action="<?= base_url('setting/template/discharge_templates') ?>" class="mb-4" id="discharge_template_form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="discharge_template_id" value="<?= $editId ?>">

                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small">Template Name</label>
                        <input type="text" name="template_name" class="form-control form-control-sm" value="<?= esc($templateName) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="1" <?= $status === 1 ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= $status === 0 ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" name="is_default" id="is_default" value="1" <?= $isDefault === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_default">Set as default</label>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small">Page Size</label>
                        <select name="page_size" id="discharge_page_size" class="form-select form-select-sm">
                            <option value="A4" <?= $pageSize === 'A4' ? 'selected' : '' ?>>A4</option>
                            <option value="A4-L" <?= $pageSize === 'A4-L' ? 'selected' : '' ?>>A4 Landscape</option>
                            <option value="A5" <?= $pageSize === 'A5' ? 'selected' : '' ?>>A5</option>
                            <option value="A6" <?= $pageSize === 'A6' ? 'selected' : '' ?>>A6</option>
                            <option value="LETTER" <?= $pageSize === 'LETTER' ? 'selected' : '' ?>>Letter</option>
                            <option value="LEGAL" <?= $pageSize === 'LEGAL' ? 'selected' : '' ?>>Legal</option>
                            <option value="CUSTOM" <?= $pageSize === 'CUSTOM' ? 'selected' : '' ?>>Custom (mm)</option>
                        </select>
                    </div>
                    <div class="col-md-3 discharge-custom-size-wrap" style="display:none;">
                        <label class="form-label small">Custom Width (mm)</label>
                        <input type="number" name="custom_width_mm" class="form-control form-control-sm" value="<?= esc((string) $customWidthMm) ?>" min="20" max="600" step="1">
                    </div>
                    <div class="col-md-3 discharge-custom-size-wrap" style="display:none;">
                        <label class="form-label small">Custom Height (mm)</label>
                        <input type="number" name="custom_height_mm" class="form-control form-control-sm" value="<?= esc((string) $customHeightMm) ?>" min="20" max="1000" step="1">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small">Top (cm)</label>
                        <input type="number" name="page_margin_top_cm" class="form-control form-control-sm" value="<?= esc($marginTop) ?>" step="0.1" min="0" max="25">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Bottom (cm)</label>
                        <input type="number" name="page_margin_bottom_cm" class="form-control form-control-sm" value="<?= esc($marginBottom) ?>" step="0.1" min="0" max="25">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Left (cm)</label>
                        <input type="number" name="page_margin_left_cm" class="form-control form-control-sm" value="<?= esc($marginLeft) ?>" step="0.1" min="0" max="25">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Right (cm)</label>
                        <input type="number" name="page_margin_right_cm" class="form-control form-control-sm" value="<?= esc($marginRight) ?>" step="0.1" min="0" max="25">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Header (cm)</label>
                        <input type="number" name="margin_header_cm" class="form-control form-control-sm" value="<?= esc($marginHeader) ?>" step="0.1" min="0" max="25">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Footer (cm)</label>
                        <input type="number" name="margin_footer_cm" class="form-control form-control-sm" value="<?= esc($marginFooter) ?>" step="0.1" min="0" max="25">
                    </div>

                    <div class="col-12">
                        <label class="form-label small">Header HTML</label>
                        <textarea name="header_html" rows="4" class="form-control" style="font-family:Consolas,Monaco,monospace;"><?= esc($headerHtml) ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Footer HTML</label>
                        <textarea name="footer_html" rows="4" class="form-control" style="font-family:Consolas,Monaco,monospace;"><?= esc($footerHtml) ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Style (CSS)</label>
                        <textarea name="template_css" rows="4" class="form-control" style="font-family:Consolas,Monaco,monospace;" placeholder="Example: .title{font-size:16px;font-weight:bold;}"><?= esc($templateCss) ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Template HTML</label>
                        <textarea name="template_html" id="template_html_editor" rows="12" class="form-control" required><?= esc($templateHtml) ?></textarea>
                        <small class="text-muted">Use the editor to format layout. Use the <code>Source</code> button for raw HTML. You can use one <code>{{CONTENT}}</code> block or arrange the section placeholders in any custom order.</small>
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm" id="discharge_template_submit_btn"><?= $editId > 0 ? 'Update Template' : 'Create Template' ?></button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="discharge_template_cancel_btn" style="display:<?= $editId > 0 ? 'inline-block' : 'none' ?>;">Cancel Edit</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:60px;">ID</th>
                            <th>Template Name</th>
                            <th style="width:90px;">Default</th>
                            <th style="width:90px;">Status</th>
                            <th style="width:180px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <div class="text-muted mb-2">No templates found.</div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn_create_defaults">
                                        <i class="fas fa-plus-circle"></i> Create Default Templates
                                    </button>
                                    <div class="small text-muted mt-2">Creates "Default Discharge Template" and "NABH Compliant Discharge Summary"</div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?= (int) ($row['id'] ?? 0) ?></td>
                                    <td><?= esc((string) ($row['template_name'] ?? '')) ?></td>
                                    <td><?= (int) ($row['is_default'] ?? 0) === 1 ? 'Yes' : 'No' ?></td>
                                    <td><?= (int) ($row['status'] ?? 0) === 1 ? 'Active' : 'Inactive' ?></td>
                                    <td>
                                        <button type="button" class="btn btn-outline-primary btn-sm discharge-template-edit" data-id="<?= (int) ($row['id'] ?? 0) ?>">Edit</button>
                                        <button type="button" class="btn btn-outline-danger btn-sm discharge-template-delete" data-id="<?= (int) ($row['id'] ?? 0) ?>">Delete</button>
                                    </td>
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
    var form = document.getElementById('discharge_template_form');
    var noticeBox = document.getElementById('discharge_template_notice');
    var templateIdInput = document.getElementById('discharge_template_id');
    var submitBtn = document.getElementById('discharge_template_submit_btn');
    var cancelBtn = document.getElementById('discharge_template_cancel_btn');
    var editorFieldId = 'template_html_editor';
    var pageSizeEl = document.getElementById('discharge_page_size');
    var customSizeWraps = document.querySelectorAll('.discharge-custom-size-wrap');
    var previewIpdInput = document.getElementById('discharge_preview_ipd_id');
    var previewBtn = document.getElementById('btn_discharge_preview');
    var pdfBtn = document.getElementById('btn_discharge_pdf');
    var placeholderPreviewBtn = document.getElementById('btn_discharge_placeholder_preview');
    var editButtons = document.querySelectorAll('.discharge-template-edit');
    var deleteButtons = document.querySelectorAll('.discharge-template-delete');

    var fieldTemplateName = form ? form.querySelector('input[name="template_name"]') : null;
    var fieldStatus = form ? form.querySelector('select[name="status"]') : null;
    var fieldIsDefault = form ? form.querySelector('input[name="is_default"]') : null;
    var fieldCustomWidth = form ? form.querySelector('input[name="custom_width_mm"]') : null;
    var fieldCustomHeight = form ? form.querySelector('input[name="custom_height_mm"]') : null;
    var fieldMarginTop = form ? form.querySelector('input[name="page_margin_top_cm"]') : null;
    var fieldMarginBottom = form ? form.querySelector('input[name="page_margin_bottom_cm"]') : null;
    var fieldMarginLeft = form ? form.querySelector('input[name="page_margin_left_cm"]') : null;
    var fieldMarginRight = form ? form.querySelector('input[name="page_margin_right_cm"]') : null;
    var fieldMarginHeader = form ? form.querySelector('input[name="margin_header_cm"]') : null;
    var fieldMarginFooter = form ? form.querySelector('input[name="margin_footer_cm"]') : null;
    var fieldHeaderHtml = form ? form.querySelector('textarea[name="header_html"]') : null;
    var fieldFooterHtml = form ? form.querySelector('textarea[name="footer_html"]') : null;
    var fieldTemplateCss = form ? form.querySelector('textarea[name="template_css"]') : null;

    var initialTemplateHtml = <?= json_encode($templateHtml, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    function getSelectedTemplateId() {
        return templateIdInput ? parseInt(templateIdInput.value || '0', 10) : 0;
    }

    function setNotice(type, message) {
        if (!noticeBox) {
            return;
        }

        if (!message) {
            noticeBox.innerHTML = '';
            return;
        }

        noticeBox.innerHTML = '<div class="alert alert-' + type + ' py-2 mb-0">' + message + '</div>';
    }

    function updateCsrfToken(data) {
        if (!data || !data.csrfName || !data.csrfHash || !form) {
            return;
        }

        var tokenInput = form.querySelector('input[name="' + data.csrfName + '"]');
        if (tokenInput) {
            tokenInput.value = data.csrfHash;
        }
    }

    function setSubmitMode(isEdit) {
        if (submitBtn) {
            submitBtn.textContent = isEdit ? 'Update Template' : 'Create Template';
        }
        if (cancelBtn) {
            cancelBtn.style.display = isEdit ? 'inline-block' : 'none';
        }
    }

    function resetTemplateForm() {
        if (!form) {
            return;
        }

        form.reset();
        if (templateIdInput) {
            templateIdInput.value = '0';
        }
        if (fieldStatus) {
            fieldStatus.value = '1';
        }
        if (fieldIsDefault) {
            fieldIsDefault.checked = false;
        }
        if (pageSizeEl) {
            pageSizeEl.value = 'A4';
        }
        if (fieldCustomWidth) {
            fieldCustomWidth.value = '210';
        }
        if (fieldCustomHeight) {
            fieldCustomHeight.value = '297';
        }
        if (fieldMarginTop) {
            fieldMarginTop.value = '0.8';
        }
        if (fieldMarginBottom) {
            fieldMarginBottom.value = '0.8';
        }
        if (fieldMarginLeft) {
            fieldMarginLeft.value = '0.8';
        }
        if (fieldMarginRight) {
            fieldMarginRight.value = '0.8';
        }
        if (fieldMarginHeader) {
            fieldMarginHeader.value = '0.5';
        }
        if (fieldMarginFooter) {
            fieldMarginFooter.value = '0.5';
        }
        if (fieldHeaderHtml) {
            fieldHeaderHtml.value = '';
        }
        if (fieldFooterHtml) {
            fieldFooterHtml.value = '';
        }
        if (fieldTemplateCss) {
            fieldTemplateCss.value = '';
        }

        var defaultHtml = initialTemplateHtml || '{{CONTENT}}';
        if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances && CKEDITOR.instances[editorFieldId]) {
            try {
                CKEDITOR.instances[editorFieldId].setData(defaultHtml);
            } catch (e) {
                console.warn('CKEditor setData failed in reset:', e);
                var templateHtmlEl = document.getElementById(editorFieldId);
                if (templateHtmlEl) {
                    templateHtmlEl.value = defaultHtml;
                }
            }
        } else {
            var templateHtmlEl = document.getElementById(editorFieldId);
            if (templateHtmlEl) {
                templateHtmlEl.value = defaultHtml;
            }
        }

        setSubmitMode(false);
        toggleCustomSizeFields();
    }

    function setFormFromTemplateRow(row) {
        if (!row || !form) {
            return;
        }

        if (templateIdInput) {
            templateIdInput.value = String(parseInt(row.id || 0, 10));
        }
        if (fieldTemplateName) {
            fieldTemplateName.value = String(row.template_name || '');
        }
        if (fieldStatus) {
            fieldStatus.value = String(parseInt(row.status || 0, 10) === 1 ? 1 : 0);
        }
        if (fieldIsDefault) {
            fieldIsDefault.checked = parseInt(row.is_default || 0, 10) === 1;
        }
        if (pageSizeEl) {
            pageSizeEl.value = String((row.page_size || 'A4')).toUpperCase();
        }
        if (fieldCustomWidth) {
            fieldCustomWidth.value = String(row.custom_width_mm || 210);
        }
        if (fieldCustomHeight) {
            fieldCustomHeight.value = String(row.custom_height_mm || 297);
        }
        if (fieldMarginTop) {
            fieldMarginTop.value = String(row.page_margin_top_cm || '0.8');
        }
        if (fieldMarginBottom) {
            fieldMarginBottom.value = String(row.page_margin_bottom_cm || '0.8');
        }
        if (fieldMarginLeft) {
            fieldMarginLeft.value = String(row.page_margin_left_cm || '0.8');
        }
        if (fieldMarginRight) {
            fieldMarginRight.value = String(row.page_margin_right_cm || '0.8');
        }
        if (fieldMarginHeader) {
            fieldMarginHeader.value = String(row.margin_header_cm || '0.5');
        }
        if (fieldMarginFooter) {
            fieldMarginFooter.value = String(row.margin_footer_cm || '0.5');
        }
        if (fieldHeaderHtml) {
            fieldHeaderHtml.value = String(row.header_html || '');
        }
        if (fieldFooterHtml) {
            fieldFooterHtml.value = String(row.footer_html || '');
        }
        if (fieldTemplateCss) {
            fieldTemplateCss.value = String(row.template_css || '');
        }

        var html = String(row.template_html || '{{CONTENT}}');
        if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances && CKEDITOR.instances[editorFieldId]) {
            try {
                CKEDITOR.instances[editorFieldId].setData(html);
            } catch (e) {
                console.warn('CKEditor setData failed in setForm:', e);
                var templateHtmlEl = document.getElementById(editorFieldId);
                if (templateHtmlEl) {
                    templateHtmlEl.value = html;
                }
            }
        } else {
            var templateHtmlEl = document.getElementById(editorFieldId);
            if (templateHtmlEl) {
                templateHtmlEl.value = html;
            }
        }

        setSubmitMode(true);
        toggleCustomSizeFields();
        setNotice('', '');
    }

    function openDischargeUrl(baseUrl, ipdId, printType) {
        if (!ipdId || ipdId < 1) {
            alert('Enter a valid IPD ID first.');
            return;
        }

        var url = baseUrl + '/' + ipdId;
        if (typeof printType !== 'undefined') {
            url += '/' + printType;
        }

        var tplId = getSelectedTemplateId();
        if (tplId > 0) {
            url += '?tpl=' + encodeURIComponent(tplId);
        }

        window.open(url, '_blank');
    }

    function initTemplateEditor() {
        if (typeof CKEDITOR === 'undefined') {
            console.warn('CKEditor not loaded');
            return;
        }

        var editorEl = document.getElementById(editorFieldId);
        if (!editorEl) {
            console.warn('Editor textarea not found:', editorFieldId);
            return;
        }

        CKEDITOR.config.versionCheck = false;
        CKEDITOR.config.removePlugins = '';

        // Safe destroy - wrap in try-catch to handle any state issues
        if (CKEDITOR.instances && CKEDITOR.instances[editorFieldId]) {
            try {
                CKEDITOR.instances[editorFieldId].destroy(true);
            } catch (e) {
                console.warn('CKEditor destroy failed for', editorFieldId, '- This is usually safe to ignore', e);
            }
        }

        // Small delay to ensure DOM is ready and cleanup is complete
        setTimeout(function() {
            try {
                CKEDITOR.replace(editorFieldId, {
                    height: 360,
                    toolbar: [
                        { name: 'document', items: ['Source'] },
                        { name: 'clipboard', items: ['Undo', 'Redo'] },
                        { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'RemoveFormat'] },
                        { name: 'paragraph', items: ['NumberedList', 'BulletedList', 'Outdent', 'Indent', 'Blockquote'] },
                        { name: 'align', items: ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'] },
                        { name: 'links', items: ['Link', 'Unlink'] },
                        { name: 'insert', items: ['Table', 'HorizontalRule', 'SpecialChar'] },
                        { name: 'styles', items: ['Format', 'Font', 'FontSize'] },
                        { name: 'colors', items: ['TextColor', 'BGColor'] },
                        { name: 'tools', items: ['Maximize'] }
                    ]
                });
            } catch (e) {
                console.error('CKEditor replace failed:', e);
            }
        }, 100);
    }

    function toggleCustomSizeFields() {
        if (!pageSizeEl || !customSizeWraps.length) {
            return;
        }

        var showCustom = String(pageSizeEl.value || '').toUpperCase() === 'CUSTOM';
        customSizeWraps.forEach(function (el) {
            el.style.display = showCustom ? '' : 'none';
        });
    }

    function syncTemplateEditor() {
        if (typeof CKEDITOR === 'undefined') {
            return;
        }

        if (CKEDITOR.instances && CKEDITOR.instances[editorFieldId]) {
            try {
                CKEDITOR.instances[editorFieldId].updateElement();
            } catch (e) {
                console.warn('CKEditor updateElement failed:', e);
            }
        }
    }

    // Initialize editor after a short delay to ensure DOM is ready
    // This is especially important when loaded via AJAX
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initTemplateEditor, 150);
        });
    } else {
        setTimeout(initTemplateEditor, 150);
    }

    toggleCustomSizeFields();

    if (pageSizeEl) {
        pageSizeEl.addEventListener('change', toggleCustomSizeFields);
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            syncTemplateEditor();

            var formData = new FormData(form);
            fetch(form.getAttribute('action'), {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    updateCsrfToken(data);
                    if (!data || parseInt(data.update || 0, 10) !== 1) {
                        setNotice('danger', (data && data.error_text) ? data.error_text : 'Unable to save template.');
                        return;
                    }

                    setNotice('success', data.error_text || 'Template saved.');
                    if (typeof load_form_div === 'function') {
                        load_form_div('<?= base_url('setting/template/discharge_templates') ?>?edit=' + encodeURIComponent(String(data.id || 0)), 'maindiv', 'IPD Discharge Template');
                    } else {
                        window.location.assign('<?= base_url('setting/template/discharge_templates') ?>?edit=' + encodeURIComponent(String(data.id || 0)));
                    }
                })
                .catch(function () {
                    setNotice('danger', 'Network error while saving template.');
                });
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            resetTemplateForm();
        });
    }

    editButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = parseInt(btn.getAttribute('data-id') || '0', 10);
            if (id <= 0) {
                return;
            }

            fetch('<?= base_url('setting/template/discharge_template_get') ?>/' + id, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    updateCsrfToken(data);
                    if (!data || parseInt(data.update || 0, 10) !== 1 || !data.row) {
                        setNotice('danger', (data && data.error_text) ? data.error_text : 'Unable to load template.');
                        return;
                    }

                    setFormFromTemplateRow(data.row);
                    
                    // Reinitialize editor after data is loaded to ensure proper state
                    if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances && !CKEDITOR.instances[editorFieldId]) {
                        setTimeout(initTemplateEditor, 200);
                    }
                    
                    if (fieldTemplateName) {
                        fieldTemplateName.focus();
                    }
                })
                .catch(function () {
                    setNotice('danger', 'Network error while loading template.');
                });
        });
    });

    deleteButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = parseInt(btn.getAttribute('data-id') || '0', 10);
            if (id <= 0) {
                return;
            }

            if (!window.confirm('Delete this template?')) {
                return;
            }

            // Create fresh FormData with only CSRF token
            var deleteFormData = new FormData();
            deleteFormData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

            fetch('<?= base_url('setting/template/discharge_templates/delete') ?>/' + id, {
                method: 'POST',
                body: deleteFormData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    updateCsrfToken(data);
                    if (!data || parseInt(data.update || 0, 10) !== 1) {
                        setNotice('danger', (data && data.error_text) ? data.error_text : 'Unable to delete template.');
                        return;
                    }

                    setNotice('success', data.error_text || 'Template deleted successfully.');

                    // Force page reload to show updated list
                    setTimeout(function() {
                        window.location.reload();
                    }, 500);
                })
                .catch(function () {
                    setNotice('danger', 'Network error while deleting template.');
                });
        });
    });

    if (previewBtn) {
        previewBtn.addEventListener('click', function () {
            var ipdId = parseInt((previewIpdInput && previewIpdInput.value) ? previewIpdInput.value : '0', 10);
            openDischargeUrl('<?= site_url('Ipd_discharge/preview_discharge_report') ?>', ipdId);
        });
    }

    if (pdfBtn) {
        pdfBtn.addEventListener('click', function () {
            var ipdId = parseInt((previewIpdInput && previewIpdInput.value) ? previewIpdInput.value : '0', 10);
            openDischargeUrl('<?= site_url('Ipd_discharge/show_discharge') ?>', ipdId, 1);
        });
    }

    if (placeholderPreviewBtn) {
        placeholderPreviewBtn.addEventListener('click', function () {
            var ipdId = parseInt((previewIpdInput && previewIpdInput.value) ? previewIpdInput.value : '0', 10);
            openDischargeUrl('<?= site_url('Ipd_discharge/placeholder_preview') ?>', ipdId);
        });
    }

    // Handle "Create Default Templates" button
    var createDefaultsBtn = document.getElementById('btn_create_defaults');
    if (createDefaultsBtn) {
        createDefaultsBtn.addEventListener('click', function () {
            if (!window.confirm('Create 2 default discharge templates?')) {
                return;
            }

            var formData = new FormData();
            formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
            formData.append('action', 'seed_defaults');

            fetch('<?= base_url('setting/template/discharge_templates_seed') ?>', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    updateCsrfToken(data);
                    if (!data || parseInt(data.update || 0, 10) !== 1) {
                        setNotice('danger', (data && data.error_text) ? data.error_text : 'Failed to create templates.');
                        return;
                    }

                    setNotice('success', data.error_text || 'Default templates created successfully.');

                    // Reload page to show new templates
                    setTimeout(function() {
                        window.location.reload();
                    }, 500);
                })
                .catch(function () {
                    setNotice('danger', 'Network error while creating templates.');
                });
        });
    }
})();
</script>
