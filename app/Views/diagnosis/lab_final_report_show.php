<?php
$report = $report_format[0] ?? null;
?>

<?php if (! $report): ?>
    <div class="alert alert-danger mb-0">Report not found.</div>
<?php else: ?>
    <input type="hidden" id="hid_value_req_id" value="<?= esc($report->id ?? '') ?>">
    <?= csrf_field() ?>

    <div class="mb-2">
        <label class="form-label">Report</label>
        <textarea id="HTMLShow" name="HTMLShow" class="form-control" rows="14"><?= $report->Report_Data ?? '' ?></textarea>
    </div>

    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary" onclick="update_report()">Save</button>
        <button type="button" class="btn btn-success" onclick="report_final()">Verified</button>
    </div>
<?php endif; ?>
