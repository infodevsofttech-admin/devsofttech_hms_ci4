<?php $today = date('Y-m-d'); ?>

<div class="card">
    <div class="card-body pt-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0">Item Stock Statement</h5>
            <a class="btn btn-sm btn-outline-secondary" href="javascript:load_form_div('<?= base_url('Medical/master') ?>','medical-main','Master :Pharmacy');">Back to Master</a>
        </div>

        <form id="stock-statement-form" class="row g-2 mb-3" method="post" action="javascript:void(0)">
            <div class="col-md-3">
                <label class="form-label">Supplier</label>
                <select class="form-select" name="input_supplier" id="input_supplier">
                    <option value="0">All</option>
                    <?php foreach (($supplier_data ?? []) as $row): ?>
                        <option value="<?= esc((string) ($row->sid ?? 0)) ?>"><?= esc($row->name_supplier ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">From</label>
                <input type="date" class="form-control" name="date_from" value="<?= esc($today) ?>" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">To</label>
                <input type="date" class="form-control" name="date_to" value="<?= esc($today) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Search Items</label>
                <input type="text" class="form-control" name="txtsearch" placeholder="Like Item Name">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Search</button>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
                <button type="button" id="btn_stock_statement_excel" class="btn btn-outline-primary btn-sm">Search Items Excel</button>
                <button type="button" id="btn_stock_statement_batch_excel" class="btn btn-outline-primary btn-sm">Items BatchWise Excel</button>
            </div>
        </form>

        <div id="stock-statement-result"></div>
    </div>
</div>

<script>
(function() {
    const form = document.getElementById('stock-statement-form');
    const resultDiv = document.getElementById('stock-statement-result');

    function rangeToken() {
        const fromInput = form.querySelector('input[name="date_from"]');
        const toInput = form.querySelector('input[name="date_to"]');
        const from = (fromInput && fromInput.value) ? fromInput.value : '<?= esc($today) ?>';
        const to = (toInput && toInput.value) ? toInput.value : '<?= esc($today) ?>';
        return from + 'S' + to;
    }

    function itemToken() {
        const input = form.querySelector('input[name="txtsearch"]');
        const value = input ? (input.value || '').trim() : '';
        return value === '' ? '-' : encodeURIComponent(value);
    }

    function supplierToken() {
        const select = form.querySelector('select[name="input_supplier"]');
        return select ? (select.value || '0') : '0';
    }

    function loadStockStatement() {
        if (!form) {
            return;
        }

        const payload = new URLSearchParams(new FormData(form));
        resultDiv.innerHTML = 'Loading...';

        $.post('<?= base_url('Medical/store_stock_result_datewise') ?>', payload.toString(), function (html) {
            if (window.jQuery) {
                window.jQuery(resultDiv).html(html);
            } else {
                resultDiv.innerHTML = html;
            }
        });
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        loadStockStatement();
    });

    const excelBtn = document.getElementById('btn_stock_statement_excel');
    if (excelBtn) {
        excelBtn.addEventListener('click', function () {
            const url = '<?= base_url('Medical/store_stock_result_datewise_excel') ?>/' + rangeToken() + '/' + itemToken() + '/' + supplierToken();
            window.open(url, '_blank');
        });
    }

    const batchExcelBtn = document.getElementById('btn_stock_statement_batch_excel');
    if (batchExcelBtn) {
        batchExcelBtn.addEventListener('click', function () {
            const url = '<?= base_url('Medical/stock_result_excel_3') ?>/' + supplierToken() + '/' + rangeToken() + '/' + itemToken() + '/0';
            window.open(url, '_blank');
        });
    }

    loadStockStatement();
})();
</script>
