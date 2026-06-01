<div class="pagetitle">
    <h1>Payment Report <small class="text-muted">Panel</small></h1>
</div>

<section class="section">
    <div class="card">
        <div class="card-body pt-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" value="<?= esc($today ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" value="<?= esc($today ?? date('Y-m-d')) ?>">
                    <input type="hidden" id="inv_date_range" value="">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Received By</label>
                    <select class="form-select" id="emp_name_id">
                        <option value="0">All Users</option>
                        <?php foreach (($actors ?? []) as $actor): ?>
                            <option value="<?= esc((string) ($actor['update_by_id'] ?? '0')) ?>"><?= esc((string) ($actor['update_by'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary" id="showreport">Show Report</button>
                        <button type="button" class="btn btn-outline-primary" id="showreportexport">Export</button>
                        <button type="button" class="btn btn-outline-secondary" id="showreportprint">Print A4</button>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <div id="show_report"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    function syncDateRange() {
        var from = document.getElementById('date_from').value || '';
        var to = document.getElementById('date_to').value || '';
        if (!from || !to) {
            document.getElementById('inv_date_range').value = '';
            return false;
        }
        document.getElementById('inv_date_range').value = from + 'S' + to;
        return true;
    }

    function buildUrl(output) {
        if (!syncDateRange()) {
            alert('Please select From and To date');
            return '';
        }

        var empNameId = document.getElementById('emp_name_id').value || '0';
        return '<?= base_url('Medical_Report/Report_Payment_Recieved_data') ?>/' +
            document.getElementById('inv_date_range').value + '/' + empNameId + '/' + output;
    }

    function loadReport(url) {
        if (!url) {
            return;
        }

        if (typeof load_report_div === 'function') {
            load_report_div(url, 'show_report');
            return;
        }

        var target = document.getElementById('show_report');
        target.innerHTML = '<div class="alert alert-info">Loading...</div>';
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (response) { return response.text(); })
            .then(function (html) { target.innerHTML = html; })
            .catch(function () {
                target.innerHTML = '<div class="alert alert-danger">Unable to load report.</div>';
            });
    }

    document.getElementById('date_from').addEventListener('change', syncDateRange);
    document.getElementById('date_to').addEventListener('change', syncDateRange);

    document.getElementById('showreport').addEventListener('click', function () {
        loadReport(buildUrl(0));
    });

    document.getElementById('showreportexport').addEventListener('click', function () {
        var exportUrl = buildUrl(1);
        if (exportUrl) {
            window.open(exportUrl, '_blank');
        }
    });

    document.getElementById('showreportprint').addEventListener('click', function () {
        var pdfUrl = buildUrl(2);
        if (pdfUrl) {
            window.open(pdfUrl, '_blank');
        }
    });

    syncDateRange();
})();
</script>
