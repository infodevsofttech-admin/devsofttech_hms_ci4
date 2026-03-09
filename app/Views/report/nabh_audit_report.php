<section class="content">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">NABH Audit Report</h5>
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
                <div class="col-md-3">
                    <label class="form-label">Module</label>
                    <select class="form-control" id="module_filter">
                        <option value="all">All</option>
                        <option value="ipd">IPD Discharge</option>
                        <option value="opd">OPD Consult</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Compliance Status</label>
                    <select class="form-control" id="status_filter">
                        <option value="all">All</option>
                        <option value="critical-missing">Critical Missing</option>
                        <option value="compliant">Compliant</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid gap-2">
                    <button type="button" class="btn btn-primary" id="show_report">Show</button>
                    <button type="button" class="btn btn-outline-primary" id="export_report">Export</button>
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

            var module = document.getElementById('module_filter').value || 'all';
            var status = document.getElementById('status_filter').value || 'all';

            return '<?= base_url('Report/nabh_audit_report_data') ?>/'
                + encodeURIComponent(dateRange) + '/'
                + encodeURIComponent(module) + '/'
                + encodeURIComponent(status);
        }

        document.getElementById('show_report').addEventListener('click', function() {
            load_form_div(buildQuery(), 'report_result');
        });

        document.getElementById('export_report').addEventListener('click', function() {
            window.open(buildQuery() + '/1', '_blank');
        });
    })();
</script>
