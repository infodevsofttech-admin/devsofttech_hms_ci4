<div class="col-md-12">
    <div class="box">
        <div class="box-header">
            <h3 class="box-title">Indent List</h3>
        </div>
        <div class="box-body">
            <table class="table table-bordered table-striped TableData" id="employee-grid" width="100%">
                <thead>
                    <tr>
                        <th>Indent No.</th>
                        <th>Department</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <thead>
                    <tr>
                        <td><input type="text" data-column="0"></td>
                        <td><input type="text" data-column="1"></td>
                        <td><input type="date" data-column="2"></td>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="box-footer">
            <a class="btn btn-app" href="javascript:load_form_div('/Storestock/new_indent','maindiv');">
                <i class="fa fa-shopping-cart"></i>New Indent
            </a>
        </div>
    </div>
</div>
<!-- /.content -->
<script type="text/javascript" language="javascript">
$(document).ready(function() {
    var start = moment();
    var end = moment();

    var dataTable = $('#employee-grid').DataTable({
        "order": [
            [0, "desc"]
        ],
        "processing": true,
        "serverSide": true,
        'footerCallback': function(tfoot, data, start, end, display) {
            var response = this.api().ajax.json();
            if (response) {
                var $th = $(tfoot).find('th');
                $th.eq(5).html(response['foot_t_sum']);
            }
        },
        data: {
            '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>',
        },
        "ajax": {
            url: "Storestock/getIndentTable", // json datasource
            dataType: "json",
            type: "post", // method  , by default get
            data: {
                '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>',

            },
            error: function() { // error handling
                $(".employee-grid-error").html("");
                $("#employee-grid").append(
                    '<tbody class="employee-grid-error"><tr><th colspan="5">No data found in the server</th></tr></tbody>'
                    );
                $("#employee-grid_processing").css("display", "none");
            }
        },
        "columnDefs": [{
            targets: 0,
            render: function(data, type, row, meta) {
                if (type === 'display') {
                    url_link = "javascript:load_form_div('/Storestock/Indent_show/" +
                        encodeURIComponent(row[3]) + "','maindiv','Inv.:" +
                        encodeURIComponent(row[1]) + "/" + encodeURIComponent(row[3]) +
                        " :Store');";
                    udata = '<a href="' + url_link + '">' + data + '</a>';
                }
                return udata;
            }
        }]

    });

    $('#employee-grid tbody').on('click', 'button', function() {
        var data = dataTable.row($(this).parents('tr')).data();
        load_form_div('/Storestock/Indent_show/' + data[7], 'maindiv');
    });

    $("#employee-grid_filter").css("display", "none"); // hiding global search box

    //$('.search-input-text').on( 'keyup click', function () {   // for text boxes
    //	var i =$(this).attr('data-column');  // getting column index
    //	var v =$(this).val();  // getting search input value
    //	dataTable.columns(i).search(v).draw();
    //} );

    $('input[type=date').on('input', function() {
        var i = $(this).attr('data-column');
        var v = $(this).val();
        dataTable.columns(i).search(v).draw();

    });


    $('input[type=text').on('input', function() {
        var i = $(this).attr('data-column');
        var v = $(this).val();
        dataTable.columns(i).search(v).draw();

    });



});
</script>