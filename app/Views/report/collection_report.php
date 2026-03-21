<?php
$employees = $employees ?? [];
$payModes = $pay_modes ?? [];
?>
<section class="content">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Collection Report</h5>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Payment Date Range</label>
                    <div class="d-flex gap-2">
                        <input type="datetime-local" class="form-control" id="report_start">
                        <input type="datetime-local" class="form-control" id="report_end">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Employee Name</label>
                    <select class="form-control select2" id="emp_name_id" name="emp_name_id" multiple data-placeholder="Select Employees">
                        <option value="0">All Employees</option>
                        <?php foreach ($employees as $row) : ?>
                            <option value="<?= esc($row->id ?? '') ?>"><?= esc($row->username ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Payment Mode</label>
                    <select class="form-control select2" id="paymode_id" name="paymode_id" data-placeholder="Select Payment Mode">
                        <option value="0">Cash &amp; Bank</option>
                        <?php foreach ($payModes as $row) : ?>
                            <option value="<?= esc($row->id ?? '') ?>"><?= esc($row->mode_desc ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mt-3 d-flex flex-wrap gap-2">
                <div class="btn-group" role="group" aria-label="Collection details actions">
                    <button type="button" class="btn btn-primary" id="show_report">Show Details</button>
                    <button type="button" class="btn btn-outline-primary" id="export_report">Details Export</button>
                    <button type="button" class="btn btn-outline-danger" id="pdf_report">Details PDF</button>
                </div>
                <div class="btn-group" role="group" aria-label="Collection total actions">
                    <button type="button" class="btn btn-warning" id="show_total_amount">Show Total Amount</button>
                    <button type="button" class="btn btn-outline-primary" id="total_export_report">Total Export</button>
                    <button type="button" class="btn btn-outline-danger" id="total_pdf_report">Total PDF</button>
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

        function buildBaseQuery() {
            var startVal = toRangeValue(document.getElementById('report_start').value, toInputValue(start));
            var endVal = toRangeValue(document.getElementById('report_end').value, toInputValue(end));
            var dateRange = startVal + 'S' + endVal;

            var empSelect = document.getElementById('emp_name_id');
            var empValues = Array.prototype.filter.call(empSelect.options, function(option) {
                return option.selected && option.value !== '0';
            }).map(function(option) {
                return option.value;
            });

            var empList = empValues.length ? empValues.join('S') : '0';
            var payMode = document.getElementById('paymode_id').value || '0';

            return {
                dateRange: encodeURIComponent(dateRange),
                empList: empList,
                payMode: payMode,
            };
        }

        function buildDetailQuery() {
            var q = buildBaseQuery();
            return '<?= base_url('Report/report_total_payment_app_show') ?>/' + q.dateRange + '/' + q.empList + '/' + q.payMode;
        }

        function buildTotalQuery() {
            var q = buildBaseQuery();
            return '<?= base_url('Report/report_total_payment_total_amount_show') ?>/' + q.dateRange + '/' + q.empList + '/' + q.payMode;
        }

        document.getElementById('show_report').addEventListener('click', function() {
            var url = buildDetailQuery();
            load_form_div(url, 'report_result');
        });

        document.getElementById('show_total_amount').addEventListener('click', function() {
            var totalUrl = buildTotalQuery();
            load_form_div(totalUrl, 'report_result');
        });

        document.getElementById('export_report').addEventListener('click', function() {
            var url = buildDetailQuery() + '/0/1';
            window.open(url, '_blank');
        });

        document.getElementById('pdf_report').addEventListener('click', function() {
            var url = buildDetailQuery() + '/0/2';
            window.open(url, '_blank');
        });

        document.getElementById('total_export_report').addEventListener('click', function() {
            var url = buildTotalQuery() + '/1';
            window.open(url, '_blank');
        });

        document.getElementById('total_pdf_report').addEventListener('click', function() {
            var url = buildTotalQuery() + '/2';
            window.open(url, '_blank');
        });

        // Initialize Select2
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%'
        });
    })();
</script>
