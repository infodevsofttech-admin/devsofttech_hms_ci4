<div class="card">
    <div class="card-body pt-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0">Store Stock : Pharmacy</h5>
            <a class="btn btn-sm btn-outline-secondary" href="javascript:load_form_div('<?= base_url('Medical/stock_details') ?>','medical-main','Item Stock Statement :Pharmacy');">Datewise Statement</a>
        </div>

        <form id="store-stock-form" class="row g-2 mb-3" method="post" action="javascript:void(0)">
            <div class="col-md-3">
                <label class="form-label">Supplier</label>
                <select class="form-select" id="input_supplier" name="input_supplier">
                    <option value="0">Select Supplier</option>
                    <?php if (!empty($supplier_data)): ?>
                        <?php foreach ($supplier_data as $supplier): ?>
                            <option value="<?= (int) ($supplier->sid ?? 0) ?>"><?= esc($supplier->name_supplier ?? '') ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="chk_reorder" name="chk_reorder">
                    <label class="form-check-label" for="chk_reorder">ReOrder List</label>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Schedule</label>
                <select class="form-select select2" id="schedule_id" name="schedule_id[]" multiple data-placeholder="Select schedule (blank for all)">
                    <option value="1">Schedule H</option>
                    <option value="2">Schedule H1</option>
                    <option value="3">Schedule X</option>
                    <option value="4">Schedule G</option>
                    <option value="5">Narcotic</option>
                    <option value="6">High Risk</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <div class="input-group">
                    <input class="form-control" type="text" id="txtsearch" name="txtsearch" placeholder="Like Item Name, Generic, Supplier Name">
                    <button type="submit" class="btn btn-primary">Search Items</button>
                    <button type="button" id="btn_excel" class="btn btn-outline-primary">Search Items Excel</button>
                    <button type="button" id="btn_excel_3" class="btn btn-outline-primary">Items BatchWise Excel</button>
                </div>
            </div>
        </form>

        <div id="store-stock-result"></div>
    </div>
</div>

<script>
(function () {
    const form = document.getElementById('store-stock-form');
    const resultDiv = document.getElementById('store-stock-result');

    function scheduleToken() {
        const values = $('#schedule_id').val() || [];
        return values.length ? values.join('S') : '0';
    }

    function initScheduleSelect() {
        const $schedule = $('#schedule_id');
        if (!$schedule.length) {
            return;
        }

        const setup = function () {
            if (typeof $.fn.select2 !== 'function') {
                return;
            }
            if ($schedule.hasClass('select2-hidden-accessible')) {
                $schedule.select2('destroy');
            }
            $schedule.select2({
                width: '100%',
                placeholder: $schedule.data('placeholder') || 'Select schedule',
                allowClear: true,
                closeOnSelect: false
            });
        };

        if (typeof $.fn.select2 === 'function') {
            setup();
            return;
        }

        if (!document.getElementById('select2-css-cdn')) {
            const css = document.createElement('link');
            css.id = 'select2-css-cdn';
            css.rel = 'stylesheet';
            css.href = 'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css';
            document.head.appendChild(css);
        }

        if (!document.getElementById('select2-js-cdn')) {
            const js = document.createElement('script');
            js.id = 'select2-js-cdn';
            js.src = 'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js';
            js.onload = setup;
            document.head.appendChild(js);
        }
    }

    function reorderToken() {
        return $('#chk_reorder').is(':checked') ? '1' : '0';
    }

    function searchToken() {
        const value = ($('#txtsearch').val() || '').trim();
        return value === '' ? '-' : encodeURIComponent(value);
    }

    function loadResult() {
        const payload = new URLSearchParams(new FormData(form));
        resultDiv.innerHTML = 'Loading...';
        $.post('<?= base_url('Medical/store_stock_result') ?>', payload.toString(), function (html) {
            if (window.jQuery) {
                window.jQuery(resultDiv).html(html);
            } else {
                resultDiv.innerHTML = html;
            }
        }).fail(function () {
            resultDiv.innerHTML = '<div class="alert alert-danger">Unable to load store stock result.</div>';
        });
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        loadResult();
    });

    $('#btn_excel').on('click', function () {
        const url = '<?= base_url('Medical/stock_result_excel') ?>/' + reorderToken() + '/' + searchToken() + '/' + scheduleToken();
        window.open(url, '_blank');
    });

    $('#btn_excel_3').on('click', function () {
        const url = '<?= base_url('Medical/stock_result_excel_3') ?>/' + '0/0/' + searchToken() + '/' + reorderToken();
        window.open(url, '_blank');
    });

    initScheduleSelect();
    loadResult();
})();
</script>
