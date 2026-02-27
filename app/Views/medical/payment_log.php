<div class="pagetitle">
    <h1>Med. Payment Edit Logs <small class="text-muted fs-6 ms-1">Panel</small></h1>
</div>

<section class="section">
    <div class="card">
        <div class="card-body pt-3">
            <form id="form_payment_log" method="post" action="javascript:void(0)">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" id="date_from" value="<?= esc($today ?? date('Y-m-d')) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" id="date_to" value="<?= esc($today ?? date('Y-m-d')) ?>">
                        <input type="hidden" name="opd_date_range" id="opd_date_range" value="">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary">Show</button>
                    </div>
                </div>
            </form>

            <div class="mt-3" id="show_report"></div>
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

    function loadLogData() {
        if (!syncDateRange()) {
            alert('Please select From and To date');
            return;
        }

        var target = document.getElementById('show_report');
        target.innerHTML = '<div class="alert alert-info">Loading...</div>';

        $.post('<?= base_url('Payment_Medical/payment_log_data') ?>', {
            opd_date_range: document.getElementById('opd_date_range').value
        }, function (html) {
            target.innerHTML = html;
        }).fail(function () {
            target.innerHTML = '<div class="alert alert-danger">Unable to load payment logs.</div>';
        });
    }

    document.getElementById('date_from').addEventListener('change', syncDateRange);
    document.getElementById('date_to').addEventListener('change', syncDateRange);

    document.getElementById('form_payment_log').addEventListener('submit', function (event) {
        event.preventDefault();
        loadLogData();
    });

    syncDateRange();
})();
</script>
