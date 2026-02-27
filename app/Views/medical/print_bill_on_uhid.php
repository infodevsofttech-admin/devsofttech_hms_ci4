<div class="pagetitle">
    <h1>Report <small class="text-muted">Panel</small></h1>
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
                    <label class="form-label">UHID / Patient ID</label>
                    <input class="form-control" id="input_uhid" placeholder="UHID" type="text">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary" id="showreport">Show</button>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-12">
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
            document.getElementById('opd_date_range').value = '';
            return false;
        }
        document.getElementById('opd_date_range').value = from + 'S' + to;
        return true;
    }

    function buildUrl() {
        if (!syncDateRange()) {
            alert('Please select From and To date');
            return '';
        }

        var uhid = (document.getElementById('input_uhid').value || '').trim();
        if (!uhid) {
            alert('Please enter UHID / Patient ID');
            return '';
        }

        return '<?= base_url('Medical_backpanel/uhid_report') ?>/' +
            document.getElementById('opd_date_range').value + '/' +
            encodeURIComponent(uhid);
    }

    document.getElementById('date_from').addEventListener('change', syncDateRange);
    document.getElementById('date_to').addEventListener('change', syncDateRange);

    document.getElementById('showreport').addEventListener('click', function () {
        var url = buildUrl();
        if (!url) {
            return;
        }

        window.open(url, '_blank');
    });

    syncDateRange();
})();
</script>
