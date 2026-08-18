<?php
$doctors = $doctors ?? [];
$referBys = $refer_bys ?? [];
?>
<section class="content">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">OPD Patient List</h5>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="back_to_opd_total_report">Back to OPD Total</button>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">OPD Date Range</label>
                    <div class="d-flex gap-2">
                        <input type="date" class="form-control" id="opd_patient_start">
                        <input type="date" class="form-control" id="opd_patient_end">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Doctor Name</label>
                    <select class="form-control select2" id="opd_patient_doctor">
                        <option value="0">All Doctors</option>
                        <?php foreach ($doctors as $row) : ?>
                            <option value="<?= esc((string) ($row->id ?? '')) ?>"><?= esc((string) ($row->p_fname ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Refer By</label>
                    <select class="form-control select2" id="opd_patient_refer_by">
                        <option value="0">All Referrers</option>
                        <?php foreach ($referBys as $row) : ?>
                            <?php $referBy = trim((string) ($row->referby ?? '')); ?>
                            <option value="<?= esc($referBy) ?>"><?= esc($referBy) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mt-3 d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-primary" id="show_opd_patient_list">Show</button>
                <button type="button" class="btn btn-outline-primary" id="export_opd_patient_list">Export</button>
                <button type="button" class="btn btn-outline-danger" id="pdf_opd_patient_list">PDF</button>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <div id="opd_patient_list_result" class="table-responsive">Select filters and click Show.</div>
        </div>
    </div>
</section>

<script>
(function () {
    function toDateValue(date) {
        var y = date.getFullYear();
        var m = String(date.getMonth() + 1).padStart(2, '0');
        var d = String(date.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d;
    }

    var currentDate = toDateValue(new Date());
    document.getElementById('opd_patient_start').value = currentDate;
    document.getElementById('opd_patient_end').value = currentDate;

    function buildQuery(output) {
        var start = document.getElementById('opd_patient_start').value || currentDate;
        var end = document.getElementById('opd_patient_end').value || currentDate;
        var doctor = document.getElementById('opd_patient_doctor').value || '0';
        var referBy = document.getElementById('opd_patient_refer_by').value || '0';
        var url = '<?= base_url('Report/opd_patient_list_data') ?>/'
            + encodeURIComponent(start + 'S' + end) + '/'
            + encodeURIComponent(doctor) + '/'
            + encodeURIComponent(referBy);
        return output ? url + '/' + output : url;
    }

    document.getElementById('show_opd_patient_list').addEventListener('click', function () {
        load_form_div(buildQuery(), 'opd_patient_list_result');
    });
    document.getElementById('export_opd_patient_list').addEventListener('click', function () {
        window.open(buildQuery(1), '_blank');
    });
    document.getElementById('pdf_opd_patient_list').addEventListener('click', function () {
        window.open(buildQuery(2), '_blank');
    });
    document.getElementById('back_to_opd_total_report').addEventListener('click', function () {
        load_form('<?= base_url('Report/report_opd_total') ?>', 'OPD Total Report');
    });

    $('.select2').select2({ theme: 'bootstrap4', width: '100%' });
})();
</script>
