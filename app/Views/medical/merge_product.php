<div class="card">
    <div class="card-body pt-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0">Product Merge</h5>
            <a class="btn btn-sm btn-outline-secondary" href="javascript:load_form_div('<?= base_url('Medical/master') ?>','medical-main','Master :Pharmacy');">Back to Master</a>
        </div>

        <div id="merge-status" class="alert d-none" role="alert"></div>

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">From Product ID</label>
                <input type="number" class="form-control" id="input_from_product_id" placeholder="Product ID" autocomplete="off">
                <input type="hidden" id="from_product_id" value="0">
            </div>
            <div class="col-md-8 d-flex align-items-end">
                <div id="info_from_product_id" class="small text-muted"></div>
            </div>

            <div class="col-md-4">
                <label class="form-label">To Product ID</label>
                <input type="number" class="form-control" id="input_to_product_id" placeholder="Product ID" autocomplete="off">
                <input type="hidden" id="to_product_id" value="0">
            </div>
            <div class="col-md-8 d-flex align-items-end">
                <div id="info_to_product_id" class="small text-muted"></div>
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <button type="button" class="btn btn-danger" id="btn_merge">Merge Product</button>
            </div>
        </div>

        <hr>
        <h6 class="mb-2">Recent Product Merge Logs</h6>
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Summary</th>
                    <th>By</th>
                    <th>At</th>
                </tr>
                </thead>
                <tbody id="merge-log-body">
                <tr><td colspan="4" class="text-muted">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function () {
    function setStatus(message, ok) {
        const box = $('#merge-status');
        box.removeClass('d-none alert-success alert-danger');
        box.addClass(ok ? 'alert-success' : 'alert-danger');
        box.text(message);
    }

    function loadLogs() {
        $.getJSON('<?= base_url('Medical/admin_action_logs/product_merge') ?>', function (rows) {
            const body = $('#merge-log-body');
            body.empty();
            if (!rows || !rows.length) {
                body.append('<tr><td colspan="4" class="text-muted">No logs found</td></tr>');
                return;
            }
            rows.forEach(function (row) {
                body.append('<tr>'
                    + '<td>' + String(row.id || '') + '</td>'
                    + '<td>' + String(row.action_summary || '') + '</td>'
                    + '<td>' + String(row.created_by_name || '-') + '</td>'
                    + '<td>' + String(row.created_at || '') + '</td>'
                    + '</tr>');
            });
        }).fail(function () {
            $('#merge-log-body').html('<tr><td colspan="4" class="text-danger">Unable to load logs</td></tr>');
        });
    }

    function showProductInfo(target, data) {
        if (Number(data.product_id || 0) > 0) {
            target.html('Item Name: ' + (data.product_name || '-') + ' / Formulation: ' + (data.formulation || '-') + ' / Generic Name: ' + (data.genericname || '-'));
        } else {
            target.html('No record found');
        }
    }

    function fetchProduct(pid, callback) {
        $.post('<?= base_url('Medical/product_info') ?>/' + encodeURIComponent(pid), { product_id: pid }, function (res) {
            callback(res || { product_id: 0 });
        }, 'json').fail(function () {
            callback({ product_id: 0 });
        });
    }

    $('#input_from_product_id').on('change', function () {
        const pid = $(this).val();
        fetchProduct(pid, function (data) {
            showProductInfo($('#info_from_product_id'), data);
            $('#from_product_id').val(Number(data.product_id || 0));
        });
    });

    $('#input_to_product_id').on('change', function () {
        const pid = $(this).val();
        fetchProduct(pid, function (data) {
            showProductInfo($('#info_to_product_id'), data);
            $('#to_product_id').val(Number(data.product_id || 0));
        });
    });

    $('#btn_merge').on('click', function () {
        const fromPid = Number($('#from_product_id').val() || 0);
        const toPid = Number($('#to_product_id').val() || 0);

        if (fromPid <= 0 || toPid <= 0) {
            setStatus('Select valid products', false);
            return;
        }
        if (fromPid === toPid) {
            setStatus('From and To product must be different', false);
            return;
        }

        if (!confirm('Are you sure to merge product?')) {
            return;
        }

        $.post('<?= base_url('Medical/product_merged') ?>', {
            from_product_id: fromPid,
            to_product_id: toPid
        }, function (res) {
            if (Number(res.update || 0) > 0) {
                $('#input_from_product_id').trigger('change');
                $('#input_to_product_id').trigger('change');
                setStatus('Merge done', true);
                loadLogs();
            } else {
                setStatus(res.msg || 'Merge failed', false);
            }
        }, 'json').fail(function () {
            setStatus('Merge request failed', false);
        });
    });

    loadLogs();
})();
</script>
