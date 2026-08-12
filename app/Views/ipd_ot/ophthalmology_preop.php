<?php
/** @var int $ipd_id */
/** @var string $form_key */
/** @var array<string, mixed> $form */
/** @var string $department_name */
/** @var array<string, mixed> $record */
/** @var array<string, array<string, string>> $values */
/** @var bool $can_manage */
$isCompleted = (string) ($record['status'] ?? '') === 'completed';
?>
<form id="ipd_ophthalmology_preop_form" action="<?= site_url('ipd/ot/' . (int) $ipd_id . '/examination/' . rawurlencode($form_key)) ?>" method="post">
    <?= csrf_field() ?>
    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h5 class="mb-1"><?= esc((string) ($form['title'] ?? 'Pre-operative Examination')) ?></h5>
            <div class="text-muted small"><?= esc($department_name) ?></div>
        </div>
        <?php if ($isCompleted) : ?>
            <span class="badge bg-success">Completed</span>
        <?php endif ?>
    </div>

    <div id="ipd_ot_exam_notice"></div>
    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:34%;">Parameter</th>
                    <?php foreach (($form['columns'] ?? []) as $columnLabel) : ?>
                        <th><?= esc((string) $columnLabel) ?></th>
                    <?php endforeach ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($form['rows'] ?? []) as $rowKey => $rowLabel) : ?>
                    <tr>
                        <th><?= esc((string) $rowLabel) ?></th>
                        <?php foreach (($form['columns'] ?? []) as $columnKey => $columnLabel) : ?>
                            <td>
                                <textarea class="form-control form-control-sm" name="values[<?= esc((string) $rowKey, 'attr') ?>][<?= esc((string) $columnKey, 'attr') ?>]" rows="2" <?= $can_manage ? '' : 'readonly' ?>><?= esc((string) ($values[$rowKey][$columnKey] ?? '')) ?></textarea>
                            </td>
                        <?php endforeach ?>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>

    <?php if ($can_manage) : ?>
        <?php if ($isCompleted) : ?>
            <div class="mb-3">
                <label class="form-label" for="ipd_ot_edit_reason">Reason for editing completed examination</label>
                <input type="text" class="form-control" id="ipd_ot_edit_reason" name="edit_reason" maxlength="255" required>
            </div>
        <?php endif ?>
        <div class="d-flex justify-content-end gap-2">
            <button type="submit" class="btn btn-outline-primary" name="status" value="draft">Save Draft</button>
            <button type="submit" class="btn btn-success" name="status" value="completed">Complete Examination</button>
        </div>
    <?php endif ?>
</form>

<script>
(function() {
    var form = document.getElementById('ipd_ophthalmology_preop_form');
    if (!form) {
        return;
    }

    $(form).off('submit.ipdOtSave').on('submit.ipdOtSave', function(event) {
        event.preventDefault();
        var submitter = event.originalEvent && event.originalEvent.submitter ? event.originalEvent.submitter : null;
        var formData = new window.FormData(form);
        formData.set('status', submitter ? String(submitter.value || 'draft') : 'draft');

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).then(function(response) {
            return response.json().then(function(data) {
                return {ok: response.ok, data: data};
            });
        }).then(function(result) {
            var data = result.data || {};
            var notice = document.getElementById('ipd_ot_exam_notice');
            if (data.csrfName && data.csrfHash) {
                var csrf = form.querySelector('input[name="' + data.csrfName + '"]');
                if (csrf) {
                    csrf.value = data.csrfHash;
                }
            }
            if (!result.ok || parseInt(data.update || 0, 10) !== 1) {
                notice.innerHTML = '<div class="alert alert-danger">' + $('<div>').text(data.error_text || 'Unable to save examination.').html() + '</div>';
                return;
            }
            notice.innerHTML = '<div class="alert alert-success">' + $('<div>').text(data.error_text || 'Examination saved.').html() + '</div>';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('ipdOtExamModal')).hide();
            if (typeof window.refreshIpdOtPanel === 'function') {
                window.refreshIpdOtPanel();
            }
        }).catch(function() {
            document.getElementById('ipd_ot_exam_notice').innerHTML = '<div class="alert alert-danger">Unable to save examination.</div>';
        });
    });
})();
</script>