<div class="pagetitle">
    <h1>Report Drug Sale to Patient <small class="text-muted">Panel</small></h1>
</div>

<section class="section">
    <div class="card">
        <div class="card-body pt-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" value="<?= esc($today ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" value="<?= esc($today ?? date('Y-m-d')) ?>">
                    <input type="hidden" id="inv_date_range" value="">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Type of medicine</label>
                    <select class="form-select select2" id="schedule_id" multiple data-placeholder="Select a Schedule, Blank for All">
                        <option value="1">Schedule H</option>
                        <option value="2">Schedule H1</option>
                        <option value="3">Schedule X</option>
                        <option value="4">Schedule G</option>
                        <option value="5">Narcotic</option>
                        <option value="6">High Risk</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Drug Name / Batch</label>
                    <input class="form-control" type="text" id="txtsearch" placeholder="Like Item Name or Batch No">
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
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
    if (window.jQuery && typeof window.jQuery.fn.select2 === 'function') {
        window.jQuery('#schedule_id').select2({
            width: '100%',
            placeholder: window.jQuery('#schedule_id').data('placeholder') || 'Select a Schedule, Blank for All',
            allowClear: true,
            closeOnSelect: false
        });
    }

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

    function getScheduleToken() {
        var select = document.getElementById('schedule_id');
        var values = [];
        for (var i = 0; i < select.options.length; i++) {
            if (select.options[i].selected) {
                values.push(select.options[i].value);
            }
        }
        return values.length ? values.join('S') : '0';
    }

    function buildUrl(output) {
        if (!syncDateRange()) {
            alert('Please select From and To date');
            return '';
        }

        var drugName = (document.getElementById('txtsearch').value || '').trim();
        if (drugName === '') {
            drugName = '-';
        }

        var schedule = getScheduleToken();

        return '<?= base_url('Medical_backpanel/drug_patient_distribute') ?>/' +
            document.getElementById('inv_date_range').value + '/' +
            encodeURIComponent(drugName) + '/' +
            schedule + '/' +
            output;
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

    function printReportA4() {
        var pdfUrl = buildUrl(2);
        if (!pdfUrl) {
            return;
        }
        window.open(pdfUrl, '_blank');
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
        printReportA4();
    });

    syncDateRange();
})();
</script>
