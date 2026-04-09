<?php
$itemTypes = $item_types ?? [];
$referDoctors = $refer_doctors ?? [];
?>
<section class="content">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Diagnosis Report</h5>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Date Range</label>
                    <div class="d-flex gap-2">
                        <input type="datetime-local" class="form-control" id="report_start">
                        <input type="datetime-local" class="form-control" id="report_end">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Invoice Type</label>
                    <select class="form-control" id="invoice_type" name="invoice_type">
                        <option value="0">All Types</option>
                        <option value="1">IPD</option>
                        <option value="2">OPD</option>
                        <option value="3">Organization</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Refer Doctor</label>
                    <select class="form-control select2" id="refer_doctor_id" name="refer_doctor_id" data-placeholder="Select Refer Doctor">
                        <option value="0">All Refer Doctors</option>
                        <?php foreach ($referDoctors as $row) : ?>
                            <option value="<?= esc($row->id ?? '') ?>"><?= esc($row->p_fname ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Diagnosis Head</label>
                    <select class="form-control select2" id="diagnosis_id" name="diagnosis_id" multiple data-placeholder="Select Diagnosis">
                        <option value="0">All Diagnosis</option>
                        <?php foreach ($itemTypes as $row) : ?>
                            <option value="<?= esc($row->itype_id ?? '') ?>"><?= esc($row->group_desc ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mt-3">
                <div class="btn-group" role="group" aria-label="Diagnosis report actions">
                    <button type="button" class="btn btn-primary" id="show_report">Show</button>
                    <button type="button" class="btn btn-outline-primary" id="export_report">Export</button>
                    <button type="button" class="btn btn-outline-danger" id="pdf_report">PDF</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <div id="report_result" class="table-responsive">Select filters and click Show.</div>
        </div>
    </div>
</section>

<script>
    (function() {
        function pad(value) {
            return value < 10 ? '0' + value : value;
        }

        function toInputValue(date) {
            return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate())
                + 'T' + pad(date.getHours()) + ':' + pad(date.getMinutes());
        }

        function toRangeValue(value, fallback) {
            if (!value) {
                value = fallback;
            }
            if (value.length === 16) {
                return value + ':00';
            }
            return value;
        }

        var now = new Date();
        var start = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 0, 0, 0);
        var end = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 0);

        document.getElementById('report_start').value = toInputValue(start);
        document.getElementById('report_end').value = toInputValue(end);

        function buildQuery() {
            var startVal = toRangeValue(document.getElementById('report_start').value, toInputValue(start));
            var endVal = toRangeValue(document.getElementById('report_end').value, toInputValue(end));
            var dateRange = startVal + 'S' + endVal;

            var invoiceType = document.getElementById('invoice_type').value || '0';
            var referDoctorId = document.getElementById('refer_doctor_id').value || '0';

            var diagnosisSelect = document.getElementById('diagnosis_id');
            var diagnosisValues = Array.prototype.filter.call(diagnosisSelect.options, function(option) {
                return option.selected && option.value !== '0';
            }).map(function(option) {
                return option.value;
            });

            var diagnosisList = diagnosisValues.length ? diagnosisValues.join('S') : '0';

            return '<?= base_url('Report/diagnosis_report_data') ?>/' 
                + encodeURIComponent(dateRange) + '/' + invoiceType + '/' + diagnosisList
                + '?refer_doctor_id=' + encodeURIComponent(referDoctorId);
        }

        document.getElementById('show_report').addEventListener('click', function() {
            var url = buildQuery();
            load_form_div(url, 'report_result');
        });

        document.getElementById('export_report').addEventListener('click', function() {
            var url = buildQuery() + '/1';
            window.open(url, '_blank');
        });

        document.getElementById('pdf_report').addEventListener('click', function() {
            var url = buildQuery() + '/2';
            window.open(url, '_blank');
        });

        // Initialize Select2
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%'
        });
    })();
</script>
