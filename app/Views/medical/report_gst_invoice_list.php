<div class="pagetitle">
    <h1>GST Invoice List <small class="text-muted">Panel</small></h1>
</div>

<section class="section">
    <div class="card">
        <div class="card-body pt-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Discharge Date Range</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="date" class="form-control" id="date_from" value="<?= esc($today ?? date('Y-m-d')) ?>">
                        </div>
                        <div class="col-6">
                            <input type="date" class="form-control" id="date_to" value="<?= esc($today ?? date('Y-m-d')) ?>">
                        </div>
                    </div>
                    <input type="hidden" id="opd_date_range" value="">
                </div>
                <div class="col-md-4">
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary" id="showreport">Invoice Show</button>
                        <button type="button" class="btn btn-info text-white" id="showreport_xls">Invoice Excel</button>
                        <button type="button" class="btn btn-outline-secondary" id="showreport_pdf">Invoice Print</button>
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="btn-group">
                        <button type="button" class="btn btn-warning" id="showreport_hsn">HSN Wise Show</button>
                        <button type="button" class="btn btn-warning" id="showreport_hsn_xls">HSN Wise Excel</button>
                        <button type="button" class="btn btn-outline-secondary" id="showreport_hsn_pdf">HSN Wise Print</button>
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

    function buildInvoiceUrl(output) {
        if (!syncRange()) {
            alert('Please select From and To date');
            return '';
        }
        var url = '<?= base_url('Medical_Report/Report_5_data') ?>/' + document.getElementById('opd_date_range').value;
        if (output > 0) {
            url += '/' + output;
        }
        return url;
    }

    function buildHsnUrl(output) {
        if (!syncRange()) {
            alert('Please select From and To date');
            return '';
        }
        var url = '<?= base_url('Medical_Report/Report_5_HSNdata') ?>/' + document.getElementById('opd_date_range').value;
        if (output > 0) {
            url += '/' + output;
        }
        return url;
    }

    document.getElementById('date_from').addEventListener('change', syncRange);
    document.getElementById('date_to').addEventListener('change', syncRange);

    document.getElementById('showreport').addEventListener('click', function () {
        var url = buildInvoiceUrl(0);
        if (!url) {
            return;
        }
        if (typeof load_report_div === 'function') {
            load_report_div(url, 'show_report');
            return;
        }
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (response) { return response.text(); })
            .then(function (html) { document.getElementById('show_report').innerHTML = html; });
    });

    document.getElementById('showreport_xls').addEventListener('click', function () {
        var url = buildInvoiceUrl(1);
        if (url) {
            window.open(url, '_blank');
        }
    });

    document.getElementById('showreport_pdf').addEventListener('click', function () {
        var url = buildInvoiceUrl(2);
        if (url) {
            window.open(url, '_blank');
        }
    });

    document.getElementById('showreport_hsn').addEventListener('click', function () {
        var url = buildHsnUrl(0);
        if (!url) {
            return;
        }
        if (typeof load_report_div === 'function') {
            load_report_div(url, 'show_report');
            return;
        }
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (response) { return response.text(); })
            .then(function (html) { document.getElementById('show_report').innerHTML = html; });
    });

    document.getElementById('showreport_hsn_xls').addEventListener('click', function () {
        var url = buildHsnUrl(1);
        if (url) {
            window.open(url, '_blank');
        }
    });

    document.getElementById('showreport_hsn_pdf').addEventListener('click', function () {
        var url = buildHsnUrl(2);
        if (url) {
            window.open(url, '_blank');
        }
    });

    syncRange();
})();
</script>
