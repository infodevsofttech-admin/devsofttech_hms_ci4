<?php
$doctors = $doctors ?? [];
?>
<section class="content">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Billing Operations Report</h5>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Date Range</label>
                    <div class="d-flex gap-2">
                        <input type="datetime-local" class="form-control" id="billing_start">
                        <input type="datetime-local" class="form-control" id="billing_end">
                    </div>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Doctor (OPD)</label>
                    <select class="form-control select2" id="billing_doctor" data-placeholder="All Doctors">
                        <option value="0">All Doctors</option>
                        <?php foreach ($doctors as $row): ?>
                            <option value="<?= esc((string) ($row->doc_name ?? '')) ?>"><?= esc((string) ($row->doc_name ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="button" class="btn btn-primary w-100" id="billing_show">Show</button>
                    <button type="button" class="btn btn-outline-primary w-100" id="billing_export">Export</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <div id="billing_report_result" class="table-responsive">Select filters and click Show.</div>
        </div>
    </div>
</section>

<script>
(function() {
    function pad(v) {
        return v < 10 ? '0' + v : String(v);
    }

    function toInputValue(date) {
        return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate())
            + 'T' + pad(date.getHours()) + ':' + pad(date.getMinutes());
    }

    function toRangeValue(value, fallback) {
        var finalValue = value || fallback;
        if (finalValue.length === 16) {
            return finalValue + ':00';
        }
        return finalValue;
    }

    var now = new Date();
    var start = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 0, 0, 0);
    var end = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 0);
    document.getElementById('billing_start').value = toInputValue(start);
    document.getElementById('billing_end').value = toInputValue(end);

    function buildUrl(withExport) {
        var startVal = toRangeValue(document.getElementById('billing_start').value, toInputValue(start));
        var endVal = toRangeValue(document.getElementById('billing_end').value, toInputValue(end));
        var dateRange = startVal + 'S' + endVal;
        var doctor = document.getElementById('billing_doctor').value || '0';
        var url = '<?= base_url('Report/billing_operations_report_data') ?>/'
            + encodeURIComponent(dateRange) + '/' + encodeURIComponent(doctor);
        if (withExport) {
            url += '/1';
        }
        return url;
    }

    document.getElementById('billing_show').addEventListener('click', function() {
        load_form_div(buildUrl(false), 'billing_report_result');
    });

    document.getElementById('billing_export').addEventListener('click', function() {
        window.open(buildUrl(true), '_blank');
    });

    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });
})();
</script>
