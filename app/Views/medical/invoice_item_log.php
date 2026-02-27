<?php $today = date('Y-m-d'); ?>

<div class="card">
    <div class="card-body pt-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0">Medical Invoice Item Log</h5>
            <a class="btn btn-sm btn-outline-secondary" href="javascript:load_form_div('<?= base_url('Medical/master') ?>','medical-main','Master :Pharmacy');">Back to Master</a>
        </div>

        <form id="invoice-item-log-form" class="row g-2 mb-3" method="post" action="javascript:void(0)">
            <div class="col-md-3">
                <label class="form-label">From</label>
                <input type="date" name="date_from" class="form-control" value="<?= esc($today) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">To</label>
                <input type="date" name="date_to" class="form-control" value="<?= esc($today) ?>" required>
                <input type="hidden" name="opd_date_range" id="opd_date_range" value="">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Show</button>
            </div>
        </form>

        <div id="invoice-item-log-result"></div>
    </div>
</div>

<script>
(function() {
    const form = document.getElementById('invoice-item-log-form');
    const resultDiv = document.getElementById('invoice-item-log-result');

    function loadInvoiceItemLog() {
        if (!form) {
            return;
        }

        const from = form.querySelector('[name="date_from"]')?.value || '';
        const to = form.querySelector('[name="date_to"]')?.value || '';
        const range = from && to ? (from + 'S' + to) : '';
        const rangeField = document.getElementById('opd_date_range');
        if (rangeField) {
            rangeField.value = range;
        }

        const payload = new URLSearchParams(new FormData(form));
        resultDiv.innerHTML = 'Loading...';

        $.post('<?= base_url('Medical_backpanel/invoice_item_log_data') ?>', payload.toString(), function (html) {
            resultDiv.innerHTML = html;
        });
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        loadInvoiceItemLog();
    });

    loadInvoiceItemLog();
})();
</script>
