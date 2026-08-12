<?php
/** @var int $ipd_id */
/** @var string $department_name */
/** @var array<string, array<string, mixed>> $forms */
/** @var bool $can_manage */
/** @var array<int, array<string, mixed>> $cases */
/** @var bool $can_request */
/** @var bool $can_status */
?>
<div class="d-flex justify-content-between align-items-start gap-3 mb-3">
    <div>
        <h5 class="mb-1">Surgery / OT</h5>
        <div class="text-muted small">Department: <?= esc($department_name !== '' ? $department_name : 'Not assigned') ?></div>
    </div>
    <?php if ($can_request) : ?>
        <button type="button" class="btn btn-primary btn-sm" id="ipd_ot_new_request">New Surgery Request</button>
    <?php endif ?>
</div>

<?php if (! empty($cases)) : ?>
    <div class="table-responsive mb-4">
        <table class="table table-sm table-bordered align-middle mb-0">
            <thead class="table-light"><tr><th>Request</th><th>Procedure</th><th>Requested/Scheduled</th><th>Status</th><th>Call</th><th style="width:90px;">Action</th></tr></thead>
            <tbody>
            <?php foreach ($cases as $case) : ?>
                <tr>
                    <td><?= esc((string) ($case['request_no'] ?? '')) ?></td>
                    <td><?= esc((string) ($case['procedure_name'] ?? '')) ?></td>
                    <td><?= esc((string) (($case['scheduled_start_at'] ?? '') ?: ($case['requested_date'] ?? ''))) ?></td>
                    <td><span class="badge bg-info text-dark"><?= esc(ucwords(str_replace('_', ' ', (string) ($case['status'] ?? 'requested')))) ?></span></td>
                    <td><?= esc(ucwords(str_replace('_', ' ', (string) ($case['call_status'] ?? 'not_called')))) ?></td>
                    <td><button type="button" class="btn btn-sm btn-outline-primary ipd-ot-case-open" data-case-id="<?= (int) ($case['id'] ?? 0) ?>">Open</button></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
<?php endif ?>

<?php if (empty($forms)) : ?>
    <div class="alert alert-info mb-0">No specialty pre-operative examination is configured for this department.</div>
<?php else : ?>
    <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Pre-operative Examination</th>
                    <th style="width:140px;">Status</th>
                    <th style="width:110px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($forms as $form) : ?>
                    <?php $recordStatus = (string) ($form['record']['status'] ?? 'Not started'); ?>
                    <tr>
                        <td><?= esc((string) ($form['title'] ?? 'Specialty Examination')) ?></td>
                        <td>
                            <span class="badge <?= $recordStatus === 'completed' ? 'bg-success' : ($recordStatus === 'draft' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                                <?= esc(ucfirst($recordStatus)) ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary ipd-ot-open-exam" data-form-key="<?= esc((string) $form['key'], 'attr') ?>">
                                <?= $can_manage ? 'Fill' : 'View' ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
<?php endif ?>

<div class="modal fade" id="ipdOtExamModal" tabindex="-1" aria-labelledby="ipdOtExamModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ipdOtExamModalLabel">Specialty Examination</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="ipdOtExamModalBody">Loading...</div>
        </div>
    </div>
</div>

<div class="modal fade" id="ipdOtWorkflowModal" tabindex="-1" aria-labelledby="ipdOtWorkflowModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="ipdOtWorkflowModalLabel">OT Management</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body" id="ipdOtWorkflowModalBody">Loading...</div>
    </div></div>
</div>

<script>
(function() {
    var panelUrl = '<?= site_url('ipd/ot/' . (int) $ipd_id) ?>';
    var examBaseUrl = panelUrl + '/examination/';

    window.refreshIpdOtPanel = function() {
        load_form_div(panelUrl, 'tab_ot_content');
    };

    $(document).off('click.ipdOtExam', '.ipd-ot-open-exam').on('click.ipdOtExam', '.ipd-ot-open-exam', function() {
        var formKey = String($(this).data('form-key') || '');
        if (formKey === '') {
            return;
        }
        $('#ipdOtExamModalBody').html('Loading...');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('ipdOtExamModal')).show();
        load_form_div(examBaseUrl + encodeURIComponent(formKey), 'ipdOtExamModalBody');
    });

    function openWorkflow(url) {
        $('#ipdOtWorkflowModalBody').html('Loading...');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('ipdOtWorkflowModal')).show();
        load_form_div(url, 'ipdOtWorkflowModalBody');
    }
    $(document).off('click.ipdOtRequest', '#ipd_ot_new_request').on('click.ipdOtRequest', '#ipd_ot_new_request', function() { openWorkflow(panelUrl + '/request'); });
    $(document).off('click.ipdOtCase', '.ipd-ot-case-open').on('click.ipdOtCase', '.ipd-ot-case-open', function() { openWorkflow('<?= site_url('ot/cases') ?>/' + parseInt($(this).data('case-id') || 0, 10)); });
})();
</script>