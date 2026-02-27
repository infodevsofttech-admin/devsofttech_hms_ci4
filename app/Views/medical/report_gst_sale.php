<div class="pagetitle">
    <h1>Sale GST Report <small class="text-muted">Panel</small></h1>
</div>

<section class="section">
    <div class="card">
        <div class="card-body pt-3">
            <?php if (empty($gstr1_gstin ?? '')): ?>
                <div class="alert alert-danger py-2 mb-3">
                    `H_Med_GST` is empty. Please set GSTIN in constants before using GSTR1 JSON export.
                </div>
            <?php endif; ?>

            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Date Range</label>
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
                    <label class="form-label">GSTIN </label>
                    <input type="text" class="form-control" value="<?= esc($gstr1_gstin ?? '') ?>" readonly>
                </div>
                <div class="col-md-4">
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary" id="showreport">Show</button>
                        <button type="button" class="btn btn-info text-white" id="showreport_xls">Excel</button>
                        <button type="button" class="btn btn-outline-secondary" id="showreport_pdf">Print</button>
                        <button type="button" class="btn btn-success" id="showreport_gstr1">GSTR1 JSON</button>
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
    var hasGstr1Gstin = <?= !empty($gstr1_gstin ?? '') ? 'true' : 'false' ?>;

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

        var url = '<?= base_url('Medical_Report/Report_3_data') ?>/' + document.getElementById('opd_date_range').value;
        if (output > 0) {
            url += '/' + output;
        }
        return url;
    }

    function buildGstr1Url() {
        if (!syncRange()) {
            alert('Please select From and To date');
            return '';
        }

        return '<?= base_url('Medical_Report/Report_3_GSTR1') ?>/' + document.getElementById('opd_date_range').value;
    }

    document.getElementById('date_from').addEventListener('change', syncRange);
    document.getElementById('date_to').addEventListener('change', syncRange);

    document.getElementById('showreport').addEventListener('click', function () {
        var url = buildUrl(0);
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

    document.getElementById('showreport_gstr1').addEventListener('click', function () {
        if (!hasGstr1Gstin) {
            alert('H_Med_GST is empty. Please configure GSTIN in constants first.');
            return;
        }

        var url = buildGstr1Url();
        if (url) {
            window.open(url, '_blank');
        }
    });

    syncRange();
})();
</script>
