<?php
$rows = $rows ?? [];
$edit = $edit_row ?? [];
$notice = (string) ($notice ?? '');
$noticeType = (string) ($notice_type ?? 'success');

$editId = (int) ($edit['id'] ?? 0);
$templateName = (string) ($edit['template_name'] ?? '');
$templateHtml = (string) ($edit['template_html'] ?? '<div>{{CONTENT}}</div>');
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
                Available placeholders: <code>{{CONTENT}}</code>, <code>{{PATIENT_NAME}}</code>, <code>{{UHID}}</code>, <code>{{IPD_CODE}}</code>,
                <code>{{AGE_GENDER}}</code>, <code>{{ADMIT_DATE}}</code>, <code>{{DISCHARGE_DATE}}</code>, <code>{{CURRENT_DATE}}</code>.
            </div>

            <div class="alert alert-warning py-2 small">
                NABH drafting checklist: include reason for admission, significant findings, diagnosis, procedures, course in hospital,
                condition at discharge, discharge medication with dose/duration, follow-up plan, and warning signs/emergency contact.
            </div>

            <form method="post" action="<?= base_url('setting/template/discharge_templates') ?>" class="mb-4" id="discharge_template_form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $editId ?>">

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
                    <div class="col-12">
                        <label class="form-label small">Template HTML</label>
                        <textarea name="template_html" id="template_html_editor" rows="12" class="form-control" required><?= esc($templateHtml) ?></textarea>
                        <small class="text-muted">Use the editor to format layout. Use the <code>Source</code> button for raw HTML. Keep <code>{{CONTENT}}</code> in the template.</small>
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm"><?= $editId > 0 ? 'Update Template' : 'Create Template' ?></button>
                    <?php if ($editId > 0): ?>
                        <a class="btn btn-outline-secondary btn-sm" href="javascript:load_form_div('<?= base_url('setting/template/discharge_templates') ?>','maindiv','IPD Discharge Template');">Cancel Edit</a>
                    <?php endif; ?>
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
                            <tr><td colspan="5" class="text-center text-muted">No templates found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?= (int) ($row['id'] ?? 0) ?></td>
                                    <td><?= esc((string) ($row['template_name'] ?? '')) ?></td>
                                    <td><?= (int) ($row['is_default'] ?? 0) === 1 ? 'Yes' : 'No' ?></td>
                                    <td><?= (int) ($row['status'] ?? 0) === 1 ? 'Active' : 'Inactive' ?></td>
                                    <td>
                                        <a class="btn btn-outline-primary btn-sm" href="javascript:load_form_div('<?= base_url('setting/template/discharge_templates?edit=' . (int) ($row['id'] ?? 0)) ?>','maindiv','IPD Discharge Template');">Edit</a>
                                        <a class="btn btn-outline-danger btn-sm" href="javascript:if(confirm('Delete this template?')) load_form_div('<?= base_url('setting/template/discharge_templates/delete/' . (int) ($row['id'] ?? 0)) ?>','maindiv','IPD Discharge Template');">Delete</a>
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
    var editorFieldId = 'template_html_editor';

    function initTemplateEditor() {
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
                height: 360,
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

    function syncTemplateEditor() {
        if (!window.CKEDITOR) {
            return;
        }

        if (CKEDITOR.instances[editorFieldId]) {
            CKEDITOR.instances[editorFieldId].updateElement();
        }
    }

    initTemplateEditor();

    if (form) {
        form.addEventListener('submit', function () {
            syncTemplateEditor();
        });
    }
})();
</script>
