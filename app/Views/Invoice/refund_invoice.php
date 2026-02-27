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
        <h3>Payment Refund Request</h3>
    </div>
    <div class="card admin-card">
        <div class="card-body">
            <div class="alert alert-warning d-none" id="datatable-missing">
                DataTable plugin is not loaded. Please include jQuery DataTables to enable filtering.
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle TableData" id="employee-grid" width="100%">
                <thead>
                    <tr>
                        <th>Refund No.</th>
                        <th>Invoice No.</th>
                        <th>Invoice Type</th>
                        <th>Patient Name</th>
                        <th>Approved Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <thead>
                    <tr>
                        <td><input class="form-control" type="number" data-column="0" min="0"></td>
                        <td><input class="form-control" type="text" data-column="1"></td>
                        <td><input class="form-control" type="text" data-column="2"></td>
                        <td><input class="form-control" type="text" data-column="3"></td>
                        <td></td>
                        <td><input class="form-control" type="text" data-column="5"></td>
                        <td>
                            <select class="form-select search-input-select" data-column="6">
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
    $(document).ready(function() {
        if (!$.fn || !$.fn.DataTable) {
            $('#datatable-missing').removeClass('d-none');
            return;
        }

        var dataTable = $('#employee-grid').DataTable({
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
                url: "<?= base_url('Invoice/getRefundTable') ?>",
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
                            var urlLink = "javascript:load_form('<?= base_url('Invoice/refund_form') ?>/" + encodeURIComponent(data) + "');";
                            return '<a class="btn btn-sm btn-outline-primary w-100 text-start" style="white-space: normal; word-break: break-word;" title="Refund No. : ' + data + '" href="' + urlLink + '">Refund No. : ' + data + '</a>';
                        }
                        return data;
                    }
                }
            ]
        });

        $("#employee-grid_filter").css("display", "none");
        $("#employee-grid_paginate").show();
        $("#employee-grid_info").show();

        $(".search-input-select").change(function() {
            var i = $(this).attr('data-column');
            var v = $(this).val();
            dataTable.columns(i).search(v).draw();
        });

        $('input[data-column]').on('input', function() {
            var i = $(this).attr('data-column');
            var v = $(this).val();
            dataTable.columns(i).search(v).draw();
        });
    });
</script>
