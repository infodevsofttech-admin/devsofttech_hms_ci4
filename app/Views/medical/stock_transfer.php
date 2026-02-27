<div class="card">
    <div class="card-body pt-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0">Purchase Item Transfer</h5>
            <a class="btn btn-sm btn-outline-secondary" href="javascript:load_form_div('<?= base_url('Medical/master') ?>','medical-main','Master :Pharmacy');">Back to Master</a>
        </div>

        <div id="transfer-status" class="alert d-none" role="alert"></div>

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">From Purchase SSNO</label>
                <input class="form-control" id="input_from_ssno" placeholder="SS No" autocomplete="off">
                <input type="hidden" id="from_ssno_sale_qty">
                <input type="hidden" id="from_ssno_item_id">
            </div>
            <div class="col-md-8 d-flex align-items-end">
                <div id="info_from_ssno" class="small text-muted"></div>
            </div>

            <div class="col-md-4">
                <label class="form-label">To Purchase SSNO</label>
                <input class="form-control" id="input_to_ssno" placeholder="SS No" autocomplete="off">
                <input type="hidden" id="to_ssno_cur_qty">
                <input type="hidden" id="to_ssno_item_id">
            </div>
            <div class="col-md-8 d-flex align-items-end">
                <div id="info_to_ssno" class="small text-muted"></div>
            </div>

            <div class="col-md-4">
                <label class="form-label">Qty (unit)</label>
                <input class="form-control" id="input_transfer_qty" placeholder="Qty" autocomplete="off">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="button" class="btn btn-danger" id="btn_transfer">Transfer Sale</button>
            </div>
        </div>

        <hr>
        <h6 class="mb-2">Recent Transfer Logs</h6>
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
                <tbody id="transfer-log-body">
                <tr><td colspan="4" class="text-muted">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function () {
    function setStatus(message, ok) {
        const box = $('#transfer-status');
        box.removeClass('d-none alert-success alert-danger');
        box.addClass(ok ? 'alert-success' : 'alert-danger');
        box.text(message);
    }

    function loadLogs() {
        $.getJSON('<?= base_url('Medical/admin_action_logs/stock_transfer') ?>', function (rows) {
            const body = $('#transfer-log-body');
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
            $('#transfer-log-body').html('<tr><td colspan="4" class="text-danger">Unable to load logs</td></tr>');
        });
    }

    function showInfo(target, data) {
        if (data && Number(data.ssno) > 0) {
            target.html('Item Name: ' + (data.Item_name || '-') + ' / Current Qty: ' + Number(data.total_current_unit || 0).toFixed(2) + ' / Sale Qty: ' + Number(data.total_sale_unit || 0).toFixed(2));
        } else {
            target.html('No record found');
        }
    }

    function fetchSsno(ssno, callback) {
        $.post('<?= base_url('Medical/ssno_info') ?>/' + encodeURIComponent(ssno), { ssno: ssno }, function (res) {
            callback(res || { ssno: 0 });
        }, 'json').fail(function () {
            callback({ ssno: 0 });
        });
    }

    $('#input_from_ssno').on('change', function () {
        const ssno = $(this).val();
        fetchSsno(ssno, function (data) {
            showInfo($('#info_from_ssno'), data);
            $('#from_ssno_sale_qty').val(Number(data.total_sale_unit || 0));
            $('#from_ssno_item_id').val(Number(data.item_code || 0));
        });
    });

    $('#input_to_ssno').on('change', function () {
        const ssno = $(this).val();
        fetchSsno(ssno, function (data) {
            showInfo($('#info_to_ssno'), data);
            $('#to_ssno_cur_qty').val(Number(data.total_current_unit || 0));
            $('#to_ssno_item_id').val(Number(data.item_code || 0));
        });
    });

    $('#btn_transfer').on('click', function () {
        const fromSsno = Number($('#input_from_ssno').val() || 0);
        const toSsno = Number($('#input_to_ssno').val() || 0);
        const transferQty = Number($('#input_transfer_qty').val() || 0);
        const fromItem = Number($('#from_ssno_item_id').val() || 0);
        const toItem = Number($('#to_ssno_item_id').val() || 0);
        const fromSaleQty = Number($('#from_ssno_sale_qty').val() || 0);
        const toCurQty = Number($('#to_ssno_cur_qty').val() || 0);

        if (transferQty <= 0) {
            setStatus('Transfer qty should be greater than 0', false);
            return;
        }
        if (fromSsno === toSsno) {
            setStatus('SS No should be different', false);
            return;
        }
        if (fromItem !== toItem) {
            setStatus('Product should be same', false);
            return;
        }
        if (transferQty > fromSaleQty) {
            setStatus('Transfer qty should be less than or equal to From SSNO sale qty', false);
            return;
        }
        if (transferQty > toCurQty) {
            setStatus('Transfer qty should be less than or equal to To SSNO current qty', false);
            return;
        }

        $.post('<?= base_url('Medical/ssno_transfer') ?>', {
            from_ssno: fromSsno,
            to_ssno: toSsno,
            tqty: transferQty
        }, function (res) {
            if (Number(res.update || 0) > 0) {
                $('#input_from_ssno').trigger('change');
                $('#input_to_ssno').trigger('change');
                setStatus('Transfer done', true);
                loadLogs();
            } else {
                setStatus(res.msg || 'Transfer failed', false);
            }
        }, 'json').fail(function () {
            setStatus('Transfer request failed', false);
        });
    });

    loadLogs();
})();
</script>
