<?php
/** @var int $ipd_id */
/** @var string $department_name */
/** @var array<int, object> $doctors */
?>
<form class="ipd-ot-ajax-form" action="<?= site_url('ipd/ot/' . $ipd_id . '/requests') ?>" method="post" data-refresh="panel">
    <?= csrf_field() ?>
    <h5>New Surgery Request</h5>
    <p class="text-muted small">Department: <?= esc($department_name) ?></p>
    <div class="ipd-ot-form-notice"></div>
    <div class="row g-3">
        <div class="col-md-8"><label class="form-label">Planned Procedure</label><input type="text" name="procedure_name" class="form-control" maxlength="255" required></div>
        <div class="col-md-4"><label class="form-label">Side/Site</label><select name="procedure_side" class="form-select"><option value="not_applicable">Not applicable</option><option value="left">Left</option><option value="right">Right</option><option value="bilateral">Bilateral</option></select></div>
        <div class="col-md-3"><label class="form-label">Priority</label><select name="priority" class="form-select"><option value="routine">Routine</option><option value="urgent">Urgent</option><option value="emergency">Emergency</option></select></div>
        <div class="col-md-3"><label class="form-label">Requested Date</label><input type="date" name="requested_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
        <div class="col-md-2"><label class="form-label">Preferred Time</label><input type="time" name="requested_time" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Surgeon</label><select name="surgeon_id" class="form-select"><option value="0">Select later</option><?php foreach ($doctors as $doctor) : ?><option value="<?= (int) ($doctor->id ?? 0) ?>"><?= esc((string) ($doctor->DocSpecName ?? $doctor->p_fname ?? '')) ?></option><?php endforeach ?></select></div>
        <div class="col-12"><label class="form-label">Clinical/Scheduling Notes</label><textarea name="requested_notes" class="form-control" rows="3"></textarea></div>
        <div class="col-12 text-end"><button type="submit" class="btn btn-primary">Create Request</button></div>
    </div>
</form>
<?= view('ipd_ot/workflow_script') ?>