<div class="pagetitle">
    <h1>Org. Invoice <small class="text-muted">Panel</small></h1>
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
                <div class="col-md-4">
                    <label class="form-label">Insurance</label>
                    <select class="form-select" id="Insurance_id">
                        <option value="-1">All Org.</option>
                        <?php foreach (($data_insurance ?? []) as $row): ?>
                            <option value="<?= (int) ($row->id ?? 0) ?>"><?= esc((string) ($row->ins_company_name ?? ($row->short_name ?? ('Insurance-' . ($row->id ?? ''))))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
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

        var insuranceId = document.getElementById('Insurance_id').value || '-1';
        var url = '<?= base_url('Medical_backpanel/Org_Bills_Report') ?>/' + document.getElementById('opd_date_range').value + '/' + insuranceId;
        if (output > 0) {
            url += '/' + output;
        }
        return url;
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
