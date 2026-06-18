<?= $this->include('storestock/_niceadmin_theme') ?>
<div class="storestock-ui">
<section class="content">
    <div class="module-shell-head">
        <div class="module-shell-title">
            <span class="main">Store Stock</span>
            <span class="sub">Indent</span>
        </div>
        <div class="module-nav-tabs">
            <a class="tab" href="javascript:load_form('<?= base_url('Storestock') ?>','Hospital Stock');">
                <i class="fa fa-home"></i> Dashboard
            </a>
            <a class="tab active" href="javascript:load_form('<?= base_url('Storestock/Indent_List') ?>','Store : Indent');">
                <i class="fa fa-shopping-cart"></i> Indent
            </a>
            <a class="tab" href="javascript:load_form('<?= base_url('Storestock/Report_2') ?>','Day Report :Store');">
                <i class="fa fa-line-chart"></i> Day Report
            </a>
            <a class="tab" href="javascript:load_form('<?= base_url('Storestock/store_stock') ?>','Store Stock : Store');">
                <i class="fa fa-barcode"></i> Store Stock
            </a>
            <a class="tab" href="javascript:load_form('<?= base_url('Storestock/main_store') ?>','Store Main : Store');">
                <i class="fa fa-desktop"></i> Store Main
            </a>
        </div>
    </div>

    <div class="workspace-shell">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">Workspace</h3>
            </div>
            <div class="box-body" id="maindiv">
                <div class="box" style="margin-bottom:0;">
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
        </div>
    </div>
</section>
</div>

<script type="text/javascript">
$(document).ready(function () {

    var dataTable = $('#indent-grid').DataTable({
        "order": [[0, "desc"]],
        "processing": true,
        "serverSide": true,
        "ajax": {
            url: "<?= base_url('Storestock/getIndentTable') ?>",
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
