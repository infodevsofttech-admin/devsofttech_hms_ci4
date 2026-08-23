<?php
$doctors = $doctors ?? [];
$departments = $departments ?? [];
?>

<section class="content">
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="mb-0"><i class="bi bi-hospital me-2"></i>IPD Census &amp; Discharge Report</h5>
                <small class="text-muted">Admissions, discharges, current cases, and length of stay.</small>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="load_form('<?= base_url('Report/index') ?>', 'Report Panel')">
                <i class="bi bi-arrow-left me-1"></i>Report Panel
            </button>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4 col-xl-3">
                    <label class="form-label" for="ipd_census_from">From date</label>
                    <input type="date" class="form-control" id="ipd_census_from">
                </div>
                <div class="col-md-4 col-xl-3">
                    <label class="form-label" for="ipd_census_to">To date</label>
                    <input type="date" class="form-control" id="ipd_census_to">
                </div>
                <div class="col-md-4 col-xl-2">
                    <label class="form-label" for="ipd_census_status">Case status</label>
                    <select class="form-select" id="ipd_census_status">
                        <option value="all">All relevant cases</option>
                        <option value="active">Active only</option>
                        <option value="discharged">Discharged only</option>
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="ipd_census_department">Department</label>
                    <select class="form-select" id="ipd_census_department">
                        <option value="0">All departments</option>
                        <?php foreach ($departments as $department) : ?>
                            <option value="<?= (int) ($department->iId ?? 0) ?>"><?= esc($department->vName ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="ipd_census_doctor">Doctor</label>
                    <select class="form-select" id="ipd_census_doctor">
                        <option value="0">All doctors</option>
                        <?php foreach ($doctors as $doctor) : ?>
                            <option value="<?= (int) ($doctor->id ?? 0) ?>"><?= esc($doctor->p_fname ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <button type="button" class="btn btn-primary" id="ipd_census_show"><i class="bi bi-search me-1"></i>Show report</button>
                <button type="button" class="btn btn-outline-success" id="ipd_census_excel"><i class="bi bi-file-earmark-excel me-1"></i>Excel</button>
                <button type="button" class="btn btn-outline-secondary ipd-census-range" data-days="0">Today</button>
                <button type="button" class="btn btn-outline-secondary ipd-census-range" data-days="6">7 days</button>
                <button type="button" class="btn btn-outline-secondary ipd-census-range" data-days="29">30 days</button>
            </div>
        </div>
    </div>
    <div class="card mt-3">
        <div class="card-body">
            <div id="ipd_census_result" class="table-responsive">
                <div class="text-center text-muted py-4"><i class="bi bi-bar-chart-steps d-block mb-2" style="font-size:2rem"></i>Select the date range and show the report.</div>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    var from = document.getElementById('ipd_census_from');
    var to = document.getElementById('ipd_census_to');
    var result = document.getElementById('ipd_census_result');
    if (!from || !to || !result) return;

    function ymd(date) { return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0'); }
    var today = new Date();
    from.value = ymd(new Date(today.getFullYear(), today.getMonth(), 1));
    to.value = ymd(today);

    function reportUrl(output) {
        if (!from.value || !to.value) { alert('Select both dates.'); return ''; }
        return '<?= base_url('Report/ipd_census_data') ?>/'
            + encodeURIComponent(from.value + 'S' + to.value)
            + '/' + encodeURIComponent(document.getElementById('ipd_census_doctor').value || '0')
            + '/' + encodeURIComponent(document.getElementById('ipd_census_department').value || '0')
            + '/' + encodeURIComponent(document.getElementById('ipd_census_status').value || 'all')
            + (output ? '/' + output : '');
    }

    function showReport() {
        var url = reportUrl(0);
        if (!url) return;
        result.innerHTML = '<div class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Loading report...</div>';
        $.get(url).done(function (html) { result.innerHTML = html; }).fail(function (xhr) { result.innerHTML = '<div class="alert alert-danger mb-0">' + (xhr.status === 403 ? 'You do not have permission to view this report.' : 'Unable to load the report.') + '</div>'; });
    }

    $('#ipd_census_show').off('click.ipdCensus').on('click.ipdCensus', showReport);
    $('#ipd_census_excel').off('click.ipdCensus').on('click.ipdCensus', function () { var url = reportUrl(1); if (url) window.open(url, '_blank'); });
    $('.ipd-census-range').off('click.ipdCensus').on('click.ipdCensus', function () { var end = new Date(); var start = new Date(); start.setDate(start.getDate() - Number($(this).data('days') || 0)); from.value = ymd(start); to.value = ymd(end); showReport(); });
})();
</script>