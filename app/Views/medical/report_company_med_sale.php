<div class="pagetitle">
    <h1>Company Wise Medicine Sale Report</h1>
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
                    <input type="hidden" id="opd_date_range" value="">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Company</label>
                    <select id="input_company_name" class="form-select">
                        <?php foreach (($med_company ?? []) as $company): ?>
                            <option value="<?= (int) ($company->id ?? 0) ?>"><?= esc((string) ($company->company_name ?? ('Company-' . ($company->id ?? '')))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary" id="showreport">Show</button>
                        <button type="button" class="btn btn-outline-primary" id="showreport_xls">Excel</button>
                        <button type="button" class="btn btn-outline-secondary" id="showreport_pdf">Print</button>
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
    function syncRange() {
        var from = document.getElementById('date_from').value || '';
        var to = document.getElementById('date_to').value || '';
        if (!from || !to) {
            document.getElementById('opd_date_range').value = '';
            return false;
        }
        document.getElementById('opd_date_range').value = from + 'S' + to;
        return true;
    }

    function buildUrl(output) {
        if (!syncRange()) {
            alert('Please select From and To date');
            return '';
        }

        var companyId = document.getElementById('input_company_name').value || '0';
        return '<?= base_url('Medical_Report/Report_company_med_sale_data') ?>/' + document.getElementById('opd_date_range').value + '/' + companyId + '/' + output;
    }

    function showReport() {
        var url = buildUrl(0);
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

    document.getElementById('date_from').addEventListener('change', syncRange);
    document.getElementById('date_to').addEventListener('change', syncRange);

    document.getElementById('showreport').addEventListener('click', showReport);
    document.getElementById('showreport_xls').addEventListener('click', function () {
        var url = buildUrl(1);
        if (url) {
            window.open(url, '_blank');
        }
    });
    document.getElementById('showreport_pdf').addEventListener('click', function () {
        var url = buildUrl(2);
        if (url) {
            window.open(url, '_blank');
        }
    });

    syncRange();
})();
</script>
