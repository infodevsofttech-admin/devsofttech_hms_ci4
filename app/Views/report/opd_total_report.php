<?php
$doctors = $doctors ?? [];
$feeTypes = $fee_types ?? [];
?>
<section class="content">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">OPD Total Report</h5>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">OPD Date Range</label>
                    <div class="d-flex gap-2">
                        <input type="date" class="form-control" id="opd_report_start">
                        <input type="date" class="form-control" id="opd_report_end">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Doctor Name</label>
                    <select class="form-control select2" id="doc_name_id" name="doc_name_id">
                        <option value="0">All Doctors</option>
                        <?php foreach ($doctors as $row) : ?>
                            <option value="<?= esc($row->id ?? '') ?>"><?= esc($row->p_fname ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">OPD Type</label>
                    <select class="form-control select2" id="opd_type_id" name="opd_type_id">
                        <option value="0">All Types</option>
                        <option value="running">Running OPD</option>
                        <option value="regular">Regular OPD</option>
                        <option value="new">New Patient OPD</option>
                        <option value="emergency">Emergency OPD</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Specific Fee</label>
                    <select class="form-control select2" id="fee_id" name="fee_id">
                        <option value="0">All Fee Types</option>
                        <?php foreach ($feeTypes as $row) : ?>
                            <option value="<?= esc($row->opd_fee_id ?? '') ?>">
                                <?= esc(($row->opd_fee_desc ?? '') . ' (Fee: ' . number_format((float) ($row->opd_fee_amount ?? 0), 2) . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mt-3 d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-primary" id="show_opd_total_report">Show</button>
                <button type="button" class="btn btn-outline-primary" id="export_opd_total_report">Export</button>
                <button type="button" class="btn btn-outline-danger" id="pdf_opd_total_report">PDF</button>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <div id="opd_total_report_result" class="table-responsive">Select filters and click Show.</div>
        </div>
    </div>
</section>

<script>
    (function() {
        function toDateValue(date) {
            var y = date.getFullYear();
            var m = String(date.getMonth() + 1).padStart(2, '0');
            var d = String(date.getDate()).padStart(2, '0');
            return y + '-' + m + '-' + d;
        }

        var today = new Date();
        var currentDate = toDateValue(today);

        document.getElementById('opd_report_start').value = currentDate;
        document.getElementById('opd_report_end').value = currentDate;

        function buildQuery() {
            var startVal = document.getElementById('opd_report_start').value;
            var endVal = document.getElementById('opd_report_end').value;

            if (!startVal || !endVal) {
                startVal = currentDate;
                endVal = currentDate;
            }

            var dateRange = encodeURIComponent(startVal + 'S' + endVal);
            var doctorId = document.getElementById('doc_name_id').value || '0';
            var opdType = encodeURIComponent(document.getElementById('opd_type_id').value || '0');
            var feeId = document.getElementById('fee_id').value || '0';

            return '<?= base_url('Report/opd_total_data') ?>/' + dateRange + '/' + doctorId + '/' + opdType + '/' + feeId;
        }

        document.getElementById('show_opd_total_report').addEventListener('click', function() {
            load_form_div(buildQuery(), 'opd_total_report_result');
        });

        document.getElementById('export_opd_total_report').addEventListener('click', function() {
            window.open(buildQuery() + '/1', '_blank');
        });

        document.getElementById('pdf_opd_total_report').addEventListener('click', function() {
            window.open(buildQuery() + '/2', '_blank');
        });

        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%'
        });
    })();
</script>
