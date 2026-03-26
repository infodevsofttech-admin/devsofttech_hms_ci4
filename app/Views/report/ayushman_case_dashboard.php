<section class="content">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-speedometer2 me-2"></i>Ayushman Case Dashboard
            </h5>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="load_form('<?= base_url('Report/insurance_credit_main') ?>', 'Insurance Credit')">
                <i class="bi bi-arrow-left me-1"></i>Back to Main
            </button>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Date Range (IPD Register Date)</label>
                    <div class="d-flex gap-2">
                        <input type="date" class="form-control" id="dashboard_start" value="<?= esc(date('Y-m-01')) ?>">
                        <input type="date" class="form-control" id="dashboard_end" value="<?= esc(date('Y-m-d')) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Alert Type</label>
                    <select class="form-select" id="dashboard_alert_type">
                        <option value="all">All Cases</option>
                        <option value="preauth-pending">Preauth Pending</option>
                        <option value="docs-pending">Documents Pending</option>
                        <option value="mapping-gap">Mapping Gap</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="button" class="btn btn-primary w-100" id="show_dashboard">
                        <i class="bi bi-search me-1"></i>Show
                    </button>
                    <button type="button" class="btn btn-outline-success w-100" id="export_dashboard">
                        <i class="bi bi-file-earmark-excel me-1"></i>Export
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <div id="ayushman_dashboard_result" class="table-responsive">
                <div class="text-center text-muted py-4">
                    <i class="bi bi-filter-circle" style="font-size: 2rem;"></i>
                    <p class="mt-2">Select date and alert filter, then click Show</p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    (function() {
        function buildUrl(output) {
            var startDate = document.getElementById('dashboard_start').value;
            var endDate = document.getElementById('dashboard_end').value;
            var dateRange = startDate + 'S' + endDate;
            var alertType = document.getElementById('dashboard_alert_type').value || 'all';
            var url = '<?= base_url('Report/ayushman_case_dashboard_data') ?>/' + encodeURIComponent(dateRange) + '/' + encodeURIComponent(alertType);
            if (output === 1) {
                url += '/1';
            }
            return url;
        }

        document.getElementById('show_dashboard').addEventListener('click', function() {
            load_form_div(buildUrl(0), 'ayushman_dashboard_result');
        });

        document.getElementById('export_dashboard').addEventListener('click', function() {
            window.open(buildUrl(1), '_blank');
        });
    })();
</script>
