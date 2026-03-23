<?php
$rows = $rows ?? [];
$edit = $edit_row ?? [];
$notice = (string) ($notice ?? '');
$noticeType = (string) ($notice_type ?? 'success');

$editId = (int) ($edit['id'] ?? 0);
$formNo = (int) ($edit['form_no'] ?? 1);
$templateName = (string) ($edit['template_name'] ?? '');
$templateHtml = (string) ($edit['template_html'] ?? '<h3>{{FORM_TITLE}}</h3><p>{{PATIENT_NAME}}</p>');
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
                <code>{{CURRENT_DATE}}</code>, <code>{{CURRENT_DATETIME}}</code>.
            </div>

            <div class="alert alert-warning py-2 small">
                Local-language design supported. You can copy HTML blocks from old HMS files under <strong>old_code_ci3/views/IPD_Format</strong> and adapt them here.
            </div>

            <form method="post" action="<?= base_url('setting/template/ipd_document_templates') ?>" class="mb-4" id="ipd_document_template_form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $editId ?>">

                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label small">Form</label>
                        <select name="form_no" class="form-select form-select-sm" required>
                            <?php foreach ($formMap as $id => $label): ?>
                                <option value="<?= (int) $id ?>" <?= $formNo === (int) $id ? 'selected' : '' ?>><?= (int) $id ?> - <?= esc($label) ?></option>
                            <?php endforeach; ?>
                        </select>
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

    if (form) {
        form.addEventListener('submit', function () {
            syncEditor();
        });
    }
})();
</script>
