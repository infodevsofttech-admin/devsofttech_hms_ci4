<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Old Payment Received Report</h3>
        <span class="badge bg-info text-dark"><i class="bi bi-calendar-range me-1"></i>Invoice Date &lt; Payment Date</span>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            This report lists payments received during the selected date range where the original invoice was generated on an <strong>earlier date</strong> (Invoice Date &lt; Payment Date).
        </p>

        <form class="row g-2 align-items-end mb-3" onsubmit="event.preventDefault(); loadOldPaymentData(0);">
            <div class="col-md-5">
                <label class="form-label fw-bold">Payment Collection Date Range</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="old_pay_min" placeholder="Start Date" value="<?= date('Y-m-d 00:00:00') ?>">
                    <span class="input-group-text">to</span>
                    <input type="text" class="form-control" id="old_pay_max" placeholder="End Date" value="<?= date('Y-m-d 23:59:00') ?>">
                </div>
            </div>
            <div class="col-md-7 d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-primary" onclick="loadOldPaymentData(0)">
                    <i class="bi bi-search me-1"></i> Show Details
                </button>
                <button type="button" class="btn btn-success" onclick="loadOldPaymentData(1)">
                    <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                </button>
                <button type="button" class="btn btn-danger" onclick="loadOldPaymentData(2)">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                </button>
                <button type="button" class="btn btn-light" onclick="load_form('<?= base_url('Report/old_payment_received_report') ?>','Old Payment Received Report')">
                    <i class="bi bi-arrow-clockwise me-1"></i> Reset
                </button>
            </div>
        </form>

        <div id="old_payment_results_container" class="mt-3">
            <div class="text-center py-5 border rounded bg-light">
                <i class="bi bi-search display-6 text-muted"></i>
                <p class="text-muted mt-2">Click <strong>Show Details</strong> to load old payment collections for the selected date range.</p>
            </div>
        </div>
    </div>
</div>

<script>
function buildDateRangeStr() {
    var minVal = (document.getElementById('old_pay_min').value || '').trim();
    var maxVal = (document.getElementById('old_pay_max').value || '').trim();
    if (!minVal) minVal = '<?= date('Y-m-d 00:00:00') ?>';
    if (!maxVal) maxVal = '<?= date('Y-m-d 23:59:00') ?>';

    return encodeURIComponent(minVal) + 'S' + encodeURIComponent(maxVal);
}

function loadOldPaymentData(outputType) {
    var dateRange = buildDateRangeStr();
    var url = '<?= base_url('Report/old_payment_received_report_data') ?>/' + dateRange + '/' + (outputType || 0);

    if (outputType === 1 || outputType === 2) {
        window.open(url, '_blank');
        return;
    }

    var container = document.getElementById('old_payment_results_container');
    container.innerHTML = '<div class="text-center py-5"><span class="spinner-border text-primary"></span><p class="mt-2 text-muted">Loading Old Payment Received Data...</p></div>';

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.text(); })
        .then(function(html) {
            container.innerHTML = html;
            if (window.jQuery && $.fn && $.fn.DataTable) {
                var $t = $('#oldPaymentTable');
                if ($t.length > 0 && $t.find('tbody tr').length > 0) {
                    if ($.fn.DataTable.isDataTable($t)) {
                        $t.DataTable().destroy();
                    }
                    $t.DataTable({ pageLength: 25, order: [[0, 'desc']] });
                }
            }
        })
        .catch(function(e) {
            container.innerHTML = '<div class="alert alert-danger">Unable to load report: ' + e.message + '</div>';
        });
}

(function() {
    loadOldPaymentData(0);
})();
</script>
