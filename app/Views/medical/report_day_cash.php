<div class="pagetitle">
    <h1>Day Report <small class="text-muted">Panel</small></h1>
</div>

<section class="section">
    <div class="card">
        <div class="card-body pt-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control" id="sale_date" value="<?= esc($today ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Bill Type</label>
                    <select class="form-select" id="bill_type">
                        <option value="0">All Bills</option>
                        <option value="1">OPD Cash</option>
                        <option value="2">OPD Organisation</option>
                        <option value="3">IPD Cash</option>
                        <option value="4">IPD Credit</option>
                        <option value="5">IPD Package</option>
                        <option value="6">Sale Return</option>
                    </select>
                </div>
                <div class="col-md-5">
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
    function buildUrl(output) {
        var saleDate = document.getElementById('sale_date').value || '';
        if (!saleDate) {
            alert('Please select date');
            return '';
        }

        var billType = document.getElementById('bill_type').value || '0';

        return '<?= base_url('Medical_Report/Report_2_data') ?>/' + saleDate + '/' + billType + '/' + output;
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
})();
</script>
