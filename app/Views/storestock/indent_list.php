<?= $this->include('storestock/_niceadmin_theme') ?>
<div class="storestock-ui">
<div class="col-md-12">
    <div class="box">
        <div class="box-header">
            <div class="toolbar-row">
                <div class="left">
                    <h3 class="box-title" style="margin-right:.65rem;">Indent List</h3>
                    <button type="button" class="pill-btn active">Live Queue</button>
                    <button type="button" class="pill-btn" onclick="$('#indent-grid').DataTable().ajax.reload();">Refresh</button>
                </div>
                <div class="right">
                    <a class="btn btn-primary btn-sm" href="javascript:load_form_div('/Storestock/new_indent','maindiv');">
                        <i class="fa fa-plus"></i> New Indent
                    </a>
                </div>
            </div>
        </div>
        <div class="box-body">
            <div class="table-wrap">
                <table class="table table-bordered table-striped" id="indent-grid" width="100%">
                    <thead>
                        <tr>
                            <th>Indent No.</th>
                            <th>Department</th>
                            <th>Date</th>
                            <th style="display:none;">ID</th>
                        </tr>
                    </thead>
                    <thead>
                        <tr>
                            <td><input type="text" data-column="0" class="form-control input-sm" placeholder="Indent No."></td>
                            <td><input type="text" data-column="1" class="form-control input-sm" placeholder="Department"></td>
                            <td><input type="date" data-column="2" class="form-control input-sm"></td>
                            <td style="display:none;"></td>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function () {

    var dataTable = $('#indent-grid').DataTable({
        "order": [[0, "desc"]],
        "processing": true,
        "serverSide": true,
        "ajax": {
            url: "Storestock/getIndentTable",
            dataType: "json",
            type: "post",
            data: function (d) {
                d['<?= csrf_token() ?>'] = '<?= csrf_hash() ?>';
            },
            error: function () {
                $("#indent-grid_processing").css("display", "none");
                $("#indent-grid tbody").html('<tr><td colspan="4">No data found in the server</td></tr>');
            }
        },
        "columnDefs": [
            {
                targets: 3,
                visible: false
            },
            {
                targets: 0,
                render: function (data, type, row) {
                    if (type === 'display') {
                        var url = "javascript:load_form_div('/Storestock/Indent_show/" +
                            encodeURIComponent(row[3]) + "','maindiv','Inv.:" +
                            encodeURIComponent(row[1]) + "/" + encodeURIComponent(row[3]) + " :Store');";
                        return '<a href="' + url + '">' + data + '</a>';
                    }
                    return data;
                }
            }
        ]
    });

    $("#indent-grid_filter").css("display", "none");

    $('input[type=date]').on('input', function () {
        var i = $(this).attr('data-column');
        var v = $(this).val();
        dataTable.columns(i).search(v).draw();
    });

    $('input[type=text]').on('input', function () {
        var i = $(this).attr('data-column');
        var v = $(this).val();
        dataTable.columns(i).search(v).draw();
    });
});
</script>
</div>
