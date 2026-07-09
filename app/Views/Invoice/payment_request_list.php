<style>
    .admin-hero {
        background: linear-gradient(120deg, #f4f7fb 0%, #eef3ff 100%);
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 18px;
        margin-bottom: 16px;
    }
    .admin-hero h3 {
        font-family: "Poppins", "Nunito", sans-serif;
        font-size: 22px;
        margin: 0;
        color: #0f172a;
    }
    .admin-card {
        border-radius: 12px;
        border: 1px solid #e8edf3;
        overflow: hidden;
    }
</style>

<div class="col-md-12">
    <div class="admin-hero">
        <h3>Payment Paid Request</h3>
    </div>
    <div class="card admin-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle TableData" id="employee-grid" width="100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Org. Case No.</th>
                        <th>Patient Name</th>
                        <th>Request Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <thead>
                    <tr>
                        <td><input class="form-control" type="number" data-column="0" min="0"></td>
                        <td><input class="form-control" type="text" data-column="1"></td>
                        <td><input class="form-control" type="text" data-column="2"></td>
                        <td></td>
                        <td><input class="form-control" type="text" data-column="4"></td>
                        <td>
                            <select class="form-select search-input-select" data-column="5">
                                <option value=""></option>
                                <option value="Pending">Pending</option>
                                <option value="Complete">Complete</option>
                                <option value="Cancel">Cancel</option>
                            </select>
                        </td>
                    </tr>
                </thead>
                <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" language="javascript">
    (function() {
        var $table = $('#employee-grid');
        if ($table.length === 0) {
            return;
        }

        function escHtml(value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function renderFallbackRows(rows) {
            var $tbody = $('#employee-grid tbody');
            $tbody.empty();

            if (!rows || rows.length === 0) {
                $tbody.append('<tr><td colspan="6" class="text-center text-muted">No records found</td></tr>');
                return;
            }

            rows.forEach(function(res) {
                var reqNo = res[0] || '';
                var link = "javascript:load_form('<?= base_url('Invoice/payment_form') ?>/" + encodeURIComponent(reqNo) + "');";
                $tbody.append(
                    '<tr>' +
                    '<td><a class="btn btn-sm btn-outline-primary w-100 text-start" style="white-space: normal; word-break: break-word;" title="Request No. : ' + escHtml(reqNo) + '" href="' + link + '">Request No. : ' + escHtml(reqNo) + '</a></td>' +
                    '<td>' + escHtml(res[1] || '') + '</td>' +
                    '<td>' + escHtml(res[2] || '') + '</td>' +
                    '<td>' + escHtml(res[3] || '') + '</td>' +
                    '<td>' + escHtml(res[4] || '') + '</td>' +
                    '<td>' + escHtml(res[5] || '') + '</td>' +
                    '</tr>'
                );
            });
        }

        function loadFallbackData() {
            $.ajax({
                url: "<?= base_url('Invoice/getRequestTable') ?>",
                type: 'post',
                dataType: 'json',
                data: {
                    "<?= csrf_token() ?>": "<?= csrf_hash() ?>",
                    draw: 1,
                    start: 0,
                    length: 200,
                    'order[0][column]': 0,
                    'order[0][dir]': 'desc',
                    'columns[0][search][value]': $('input[data-column="0"]').val() || '',
                    'columns[1][search][value]': $('input[data-column="1"]').val() || '',
                    'columns[2][search][value]': $('input[data-column="2"]').val() || '',
                    'columns[4][search][value]': $('input[data-column="4"]').val() || '',
                    'columns[5][search][value]': $('select[data-column="5"]').val() || ''
                }
            })
            .done(function(resp) {
                renderFallbackRows(resp && Array.isArray(resp.data) ? resp.data : []);
            })
            .fail(function() {
                renderFallbackRows([]);
            });
        }

        if (!$.fn || !$.fn.DataTable) {
            $(".search-input-select").off('change.listReqPaymentFallback').on('change.listReqPaymentFallback', loadFallbackData);
            $('input[data-column]').off('input.listReqPaymentFallback').on('input.listReqPaymentFallback', loadFallbackData);
            loadFallbackData();
            return;
        }

        if ($.fn.DataTable.isDataTable($table)) {
            $table.DataTable().destroy();
        }

        var dataTable = $table.DataTable({
            "order": [[0, "desc"]],
            "processing": true,
            "serverSide": true,
            "paging": true,
            "pageLength": 25,
            "lengthMenu": [10, 25, 50, 100],
            "lengthChange": true,
            "pagingType": "simple_numbers",
            "info": true,
            "dom": "lfrtip",
            "ajax": {
                url: "<?= base_url('Invoice/getRequestTable') ?>",
                dataType: "json",
                type: "post",
                data: {
                    "<?= csrf_token() ?>": "<?= csrf_hash() ?>"
                },
                error: function() {
                    $(".employee-grid-error").html("");
                    $("#employee-grid").append('<tbody class="employee-grid-error"><tr><th colspan="3">No data found in the server</th></tr></tbody>');
                    $("#employee-grid_processing").css("display", "none");
                }
            },
            columnDefs: [
                {
                    targets: 0,
                    render: function(data, type) {
                        if (type === 'display') {
                            var urlLink = "javascript:load_form('<?= base_url('Invoice/payment_form') ?>/" + encodeURIComponent(data) + "');";
                            return '<a class="btn btn-sm btn-outline-primary w-100 text-start" style="white-space: normal; word-break: break-word;" title="Request No. : ' + data + '" href="' + urlLink + '">Request No. : ' + data + '</a>';
                        }
                        return data;
                    }
                }
            ]
        });

        $("#employee-grid_filter").css("display", "none");
        $("#employee-grid_paginate").show();
        $("#employee-grid_info").show();

        $(".search-input-select").off('change.listReqPayment').on('change.listReqPayment', function() {
            var i = $(this).attr('data-column');
            var v = $(this).val();
            dataTable.columns(i).search(v).draw();
        });

        $('input[data-column]').off('input.listReqPayment').on('input.listReqPayment', function() {
            var i = $(this).attr('data-column');
            var v = $(this).val();
            dataTable.columns(i).search(v).draw();
        });
    })();
</script>
