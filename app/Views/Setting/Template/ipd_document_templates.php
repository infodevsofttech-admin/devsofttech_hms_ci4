<?php
$rows = $rows ?? [];
$edit = $edit_row ?? [];
$notice = (string) ($notice ?? '');
$noticeType = (string) ($notice_type ?? 'success');

$editId = (int) ($edit['id'] ?? 0);
$formNo = (int) ($edit['form_no'] ?? 1);
$templateName = (string) ($edit['template_name'] ?? '');
$templateHtml = (string) ($edit['template_html'] ?? '<h3>{{FORM_TITLE}}</h3><p>{{PATIENT_NAME}}</p>');
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
$status = (int) ($edit['status'] ?? 1);

$formMap = [
    1 => 'Print Face Form',
    3 => 'Self Declaration Form',
    5 => 'Admission Form',
    8 => 'Progress Notes',
    9 => 'Fluid In / Out',
    10 => 'Sticker [2 x 6]',
    11 => 'Sticker [2 x 8]',
];

$dynamicFormHints = [];
foreach ($rows as $_row) {
    $fNo = (int) ($_row['form_no'] ?? 0);
    $tName = trim((string) ($_row['template_name'] ?? ''));
    if ($fNo <= 0 || $tName === '') {
        continue;
    }
    if (!isset($dynamicFormHints[$fNo])) {
        $dynamicFormHints[$fNo] = $tName;
    }
}
ksort($dynamicFormHints);
?>

<section class="content">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h6 class="mb-0">IPD Document Master</h6>
            <a class="btn btn-sm btn-outline-secondary" href="javascript:load_form_div('<?= base_url('setting/template/ipd_document_templates') ?>','maindiv','IPD Document Master');">Reset</a>
        </div>
        <div class="card-body">
            <?php if ($notice !== ''): ?>
                <div class="alert alert-<?= esc($noticeType) ?> py-2 mb-3"><?= esc($notice) ?></div>
            <?php endif; ?>

            <div class="alert alert-info py-2 small">
                Placeholders: <code>{{FORM_TITLE}}</code>, <code>{{HOSPITAL_NAME}}</code>, <code>{{HOSPITAL_ADDRESS}}</code>,
                <code>{{PATIENT_NAME}}</code>, <code>{{AGE}}</code>, <code>{{GENDER}}</code>, <code>{{AGE_GENDER}}</code>, <code>{{UHID}}</code>,
                <code>{{IPD_CODE}}</code>, <code>{{ADMIT_DATE}}</code>, <code>{{DOCTORS}}</code>, <code>{{INSURANCE_NAME}}</code>,
                <code>{{CURRENT_DATE}}</code>, <code>{{CURRENT_DATETIME}}</code>,
                <code>{{H_Name}}</code>, <code>{{H_address_1}}</code>, <code>{{H_address_2}}</code>, <code>{{H_phone_No}}</code>, <code>{{H_Email}}</code>,
                <code>{{H_logo}}</code>, <code>{{H_logo_abs}}</code>, <code>{{hospital_logo_html}}</code>,
                <code>{{doctor_name}}</code>, <code>{{doctor_sign_html}}</code>, <code>{{pName}}</code>, <code>{{age_sex}}</code>, <code>{{phoneno}}</code>, <code>{{print_time}}</code>,
                <code>{{qr_content}}</code>, <code>{{qr_code_html}}</code>.
            </div>

            <div class="alert alert-warning py-2 small">
                Local-language design supported. You can copy HTML blocks from old HMS files under <strong>old_code_ci3/views/IPD_Format</strong> and adapt them here.
            </div>

            <form method="post" action="<?= base_url('setting/template/ipd_document_templates') ?>" class="mb-4" id="ipd_document_template_form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $editId ?>">

                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label small">Form ID</label>
                        <input type="number" name="form_no" list="ipd_form_suggestions" class="form-control form-control-sm" value="<?= (int) $formNo ?>" min="1" step="1" required>
                        <datalist id="ipd_form_suggestions">
                            <?php foreach ($dynamicFormHints as $id => $label): ?>
                                <option value="<?= (int) $id ?>"><?= (int) $id ?> - <?= esc($label) ?></option>
                            <?php endforeach; ?>
                            <?php foreach ($formMap as $id => $label): ?>
                                <option value="<?= (int) $id ?>"><?= (int) $id ?> - <?= esc($label) ?></option>
                            <?php endforeach; ?>
                        </datalist>
                        <small class="text-muted">Use any positive number (example: 12 for Treatment Chart, 13 for Vitals).</small>
                    </div>
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
                    <div class="col-md-3">
                        <label class="form-label small">Page Size</label>
                        <select name="page_size" id="ipd_page_size" class="form-select form-select-sm">
                            <option value="A4" <?= $pageSize === 'A4' ? 'selected' : '' ?>>A4</option>
                            <option value="A4-L" <?= $pageSize === 'A4-L' ? 'selected' : '' ?>>A4 Landscape</option>
                            <option value="A5" <?= $pageSize === 'A5' ? 'selected' : '' ?>>A5</option>
                            <option value="A6" <?= $pageSize === 'A6' ? 'selected' : '' ?>>A6</option>
                            <option value="LETTER" <?= $pageSize === 'LETTER' ? 'selected' : '' ?>>Letter</option>
                            <option value="LEGAL" <?= $pageSize === 'LEGAL' ? 'selected' : '' ?>>Legal</option>
                            <option value="CUSTOM" <?= $pageSize === 'CUSTOM' ? 'selected' : '' ?>>Custom (mm)</option>
                        </select>
                    </div>

                    <div class="col-md-3" id="ipd_custom_width_wrap" style="display:none;">
                        <label class="form-label small">Custom Width (mm)</label>
                        <input type="number" name="custom_width_mm" class="form-control form-control-sm" value="<?= esc((string) $customWidthMm) ?>" min="20" max="600" step="1">
                    </div>
                    <div class="col-md-3" id="ipd_custom_height_wrap" style="display:none;">
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
                        <small class="text-muted">Simple CSS textbox, similar to OPD template settings.</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Template HTML</label>
                        <textarea name="template_html" id="ipd_document_template_editor" rows="12" class="form-control" required><?= esc($templateHtml) ?></textarea>
                        <small class="text-muted">Use Source mode for raw HTML from legacy forms.</small>
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm"><?= $editId > 0 ? 'Update Template' : 'Create Template' ?></button>
                    <?php if ($editId > 0): ?>
                        <a class="btn btn-outline-secondary btn-sm" href="javascript:load_form_div('<?= base_url('setting/template/ipd_document_templates') ?>','maindiv','IPD Document Master');">Cancel Edit</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:60px;">ID</th>
                            <th style="width:140px;">Form</th>
                            <th>Template Name</th>
                            <th style="width:90px;">Status</th>
                            <th style="width:180px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="5" class="text-center text-muted">No templates found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <?php $fNo = (int) ($row['form_no'] ?? 0); ?>
                                <tr>
                                    <td><?= (int) ($row['id'] ?? 0) ?></td>
                                    <td><?= $fNo ?> - <?= esc($formMap[$fNo] ?? 'Unknown') ?></td>
                                    <td><?= esc((string) ($row['template_name'] ?? '')) ?></td>
                                    <td><?= (int) ($row['status'] ?? 0) === 1 ? 'Active' : 'Inactive' ?></td>
                                    <td>
                                        <a class="btn btn-outline-primary btn-sm" href="javascript:load_form_div('<?= base_url('setting/template/ipd_document_templates?edit=' . (int) ($row['id'] ?? 0)) ?>','maindiv','IPD Document Master');">Edit</a>
                                        <a class="btn btn-outline-danger btn-sm" href="javascript:if(confirm('Delete this template?')) load_form_div('<?= base_url('setting/template/ipd_document_templates/delete/' . (int) ($row['id'] ?? 0)) ?>','maindiv','IPD Document Master');">Delete</a>
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
    var form = document.getElementById('ipd_document_template_form');
    var editorFieldId = 'ipd_document_template_editor';
    var submitBtn = form ? form.querySelector('button[type="submit"]') : null;
    var pageSizeEl = document.getElementById('ipd_page_size');
    var customWidthWrap = document.getElementById('ipd_custom_width_wrap');
    var customHeightWrap = document.getElementById('ipd_custom_height_wrap');

    function initEditor() {
        if (!window.CKEDITOR) {
            return;
        }

        CKEDITOR.config.versionCheck = false;
        CKEDITOR.config.removePlugins = '';

        if (CKEDITOR.instances[editorFieldId]) {
            CKEDITOR.instances[editorFieldId].destroy(true);
        }

        if (document.getElementById(editorFieldId)) {
            CKEDITOR.replace(editorFieldId, {
                height: 420,
                toolbar: [
                    { name: 'document', items: ['Source'] },
                    { name: 'clipboard', items: ['Undo', 'Redo'] },
                    { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'RemoveFormat'] },
                    { name: 'paragraph', items: ['NumberedList', 'BulletedList', 'Outdent', 'Indent', 'Blockquote'] },
                    { name: 'links', items: ['Link', 'Unlink'] },
                    { name: 'insert', items: ['Table', 'HorizontalRule', 'SpecialChar'] },
                    { name: 'styles', items: ['Format', 'Font', 'FontSize'] },
                    { name: 'colors', items: ['TextColor', 'BGColor'] },
                    { name: 'tools', items: ['Maximize'] }
                ]
            });
        }
    }

    function syncEditor() {
        if (!window.CKEDITOR) {
            return;
        }
        if (CKEDITOR.instances[editorFieldId]) {
            CKEDITOR.instances[editorFieldId].updateElement();
        }
    }

    initEditor();

    function toggleCustomSize() {
        if (!pageSizeEl || !customWidthWrap || !customHeightWrap) {
            return;
        }
        var isCustom = pageSizeEl.value === 'CUSTOM';
        customWidthWrap.style.display = isCustom ? '' : 'none';
        customHeightWrap.style.display = isCustom ? '' : 'none';
    }

    if (pageSizeEl) {
        pageSizeEl.addEventListener('change', toggleCustomSize);
    }
    toggleCustomSize();

    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            syncEditor();

            var payload = '';
            Array.prototype.forEach.call(form.elements || [], function (el) {
                if (!el || !el.name || el.disabled) {
                    return;
                }

                if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) {
                    return;
                }

                var part = encodeURIComponent(el.name) + '=' + encodeURIComponent(el.value == null ? '' : String(el.value));
                payload = payload === '' ? part : (payload + '&' + part);
            });
            var actionUrl = form.getAttribute('action') || window.location.href;
            var container = document.getElementById('maindiv');
            var prevBtnText = submitBtn ? submitBtn.textContent : '';

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Saving...';
            }

            fetch(actionUrl, {
                method: 'POST',
                body: payload,
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (resp) {
                    return resp.text();
                })
                .then(function (html) {
                    if (container) {
                        container.innerHTML = html;
                    } else {
                        document.body.innerHTML = html;
                    }
                })
                .catch(function () {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = prevBtnText || 'Update Template';
                    }
                    window.alert('Unable to save template. Please try again.');
                });
        });
    }
})();
</script>
