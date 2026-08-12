<?php
/** @var array<int, array<string, mixed>> $rows */
/** @var array<int, array<string, mixed>> $departments */
/** @var string $filter_date */
/** @var int $filter_department_id */
/** @var string $filter_status */
?>
<section class="content"><div class="card"><div class="card-header"><h5 class="mb-0">OT Surgery Queue</h5></div><div class="card-body">
    <form class="row g-2 mb-3" id="ipd_ot_queue_filter">
        <div class="col-md-3"><label class="form-label">Date</label><input type="date" class="form-control" name="date" value="<?= esc($filter_date, 'attr') ?>"></div>
        <div class="col-md-3"><label class="form-label">Department</label><select class="form-select" name="department_id"><option value="0">All departments</option><?php foreach ($departments as $department) : ?><option value="<?= (int) $department['iId'] ?>" <?= (int) $department['iId'] === $filter_department_id ? 'selected' : '' ?>><?= esc((string) $department['vName']) ?></option><?php endforeach ?></select></div>
        <div class="col-md-3"><label class="form-label">Status</label><select class="form-select" name="status"><option value="">All statuses</option><?php foreach (['requested','scheduled','called','in_progress','completed','cancelled'] as $status) : ?><option value="<?= $status ?>" <?= $filter_status === $status ? 'selected' : '' ?>><?= esc(ucwords(str_replace('_', ' ', $status))) ?></option><?php endforeach ?></select></div>
        <div class="col-md-3 d-flex align-items-end"><button type="submit" class="btn btn-primary">Apply</button></div>
    </form>
    <div class="table-responsive"><table class="table table-bordered table-sm align-middle"><thead class="table-light"><tr><th>Date/Time</th><th>Patient</th><th>Department</th><th>Procedure</th><th>Surgeon</th><th>Status</th><th>Call</th><th>Action</th></tr></thead><tbody>
        <?php if (empty($rows)) : ?><tr><td colspan="8" class="text-center text-muted">No surgery cases for selected filters.</td></tr><?php endif ?>
        <?php foreach ($rows as $row) : ?><tr>
            <td><?= esc((string) (($row['scheduled_start_at'] ?? '') ?: (($row['requested_date'] ?? '') . ' ' . ($row['requested_time'] ?? '')))) ?></td>
            <td><strong><?= esc((string) ($row['p_fname'] ?? '')) ?></strong><br><small><?= esc((string) ($row['ipd_code'] ?? '')) ?> / <?= esc((string) ($row['p_code'] ?? '')) ?></small></td>
            <td><?= esc((string) ($row['department_name_snapshot'] ?? '')) ?></td><td><?= esc((string) ($row['procedure_name'] ?? '')) ?></td><td><?= esc((string) ($row['surgeon_name_snapshot'] ?? '')) ?></td>
            <td><?= esc(ucwords(str_replace('_', ' ', (string) ($row['status'] ?? '')))) ?></td><td><?= esc(ucwords(str_replace('_', ' ', (string) ($row['call_status'] ?? '')))) ?></td>
            <td><button type="button" class="btn btn-sm btn-outline-primary ipd-ot-queue-open" data-case-id="<?= (int) ($row['id'] ?? 0) ?>">Open</button></td>
        </tr><?php endforeach ?>
    </tbody></table></div>
</div></div></section>
<div class="modal fade" id="ipdOtQueueModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">OT Case</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="ipdOtQueueModalBody"></div></div></div></div>
<script>(function(){
$('#ipd_ot_queue_filter').off('submit.otQueue').on('submit.otQueue',function(e){e.preventDefault();var query=new window.URLSearchParams(new window.FormData(this)).toString();load_form('<?= site_url('ot/queue') ?>?'+query,'OT Surgery Queue');});
$(document).off('click.otQueue','.ipd-ot-queue-open').on('click.otQueue','.ipd-ot-queue-open',function(){var id=parseInt($(this).data('case-id')||0,10);bootstrap.Modal.getOrCreateInstance(document.getElementById('ipdOtQueueModal')).show();load_form_div('<?= site_url('ot/cases') ?>/'+id,'ipdOtQueueModalBody');});
window.refreshIpdOtQueue=function(){load_form('<?= site_url('ot/queue') ?>?'+$('#ipd_ot_queue_filter').serialize(),'OT Surgery Queue');};
})();</script>