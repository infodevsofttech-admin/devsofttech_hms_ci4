<div class="card">
    <div class="card-body pt-3">
        <h5 class="card-title">OPD Sale - Patient Search</h5>

        <form method="post" action="<?= base_url('Medical/search') ?>" class="medical-search-form row g-2 align-items-center">
            <?= csrf_field() ?>
            <div class="col-md-8">
                <input class="form-control form-control-sm" type="text" id="txtsearch" name="txtsearch" placeholder="UHID / Name / Phone / Email">
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-info btn-sm">Go!</button>
                <a class="btn btn-primary btn-sm" href="javascript:load_form_div('<?= base_url('Medical/CounterSaleForm') ?>','medical-main','Counter Sale');">New Counter Sale</a>
            </div>
        </form>
        <div class="medical-search-result"></div>
    </div>
</div>

<script>
    (function () {
        var form = document.querySelector('.medical-search-form');
        var resultBox = document.querySelector('.medical-search-result');

        if (!form || !resultBox) {
            return;
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            var formData = new FormData(form);
            var body = new URLSearchParams(formData).toString();

            fetch(form.getAttribute('action'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: body
            })
            .then(function (response) {
                return response.text();
            })
            .then(function (html) {
                resultBox.innerHTML = html;
                if (window.jQuery && jQuery.fn && typeof jQuery.fn.DataTable === 'function') {
                    var table = jQuery('#medical-patient-search-table');
                    if (table.length) {
                        if (jQuery.fn.dataTable.isDataTable('#medical-patient-search-table')) {
                            table.DataTable().destroy();
                        }
                        table.DataTable();
                    }
                }
            })
            .catch(function () {
                resultBox.innerHTML = '<div class="alert alert-danger mt-3">Unable to load search result.</div>';
            });
        });
    })();
</script>
