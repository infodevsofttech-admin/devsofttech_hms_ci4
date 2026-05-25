<section class="section">
    <form id="drug-master-search-form" class="row g-2 mb-3" method="post" action="javascript:void(0)">
        <?= csrf_field() ?>
        <div class="col-md-5">
            <input class="form-control" type="text" id="txtsearch" name="txtsearch" placeholder="Search by Product ID / Name / Generic Name">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-info">Search Product</button>
        </div>
        <div class="col-auto">
            <button type="button" id="btnResetSearch" class="btn btn-secondary">Reset</button>
        </div>
        <div class="col-auto">
            <button onclick="load_form_div('<?= base_url('Product_master/Product_edit/0') ?>','searchresult','Drug Master : New Product :Pharmacy');" type="button" class="btn btn-warning">Add New Product</button>
        </div>
    </form>

    <div id="searchresult" class="table-responsive">
        <table id="product_report_list" class="table table-bordered table-striped table-sm align-middle" style="width:100%;">
            <thead>
            <tr>
                <th>Prod. ID</th>
                <th>Name</th>
                <th>Formulation</th>
                <th>Generic Name</th>
                <th>Packing Type</th>
                <th style="width:110px;">Action</th>
            </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</section>

<script>
(function () {
    if (!window.jQuery || !$.fn || !$.fn.DataTable) {
        return;
    }

    var $form = $('#drug-master-search-form');
    var dt = $('#product_report_list').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 25,
        order: [[0, 'desc']],
        ajax: {
            url: '<?= base_url('product_master/Product_search') ?>',
            type: 'POST',
            data: function (d) {
                var tokenName = '<?= csrf_token() ?>';
                var tokenValue = $form.find('input[name="<?= csrf_token() ?>"]').val() || '<?= csrf_hash() ?>';
                d[tokenName] = tokenValue;
                d.txtsearch = $('#txtsearch').val();
            },
            dataSrc: function (json) {
                if (json && json.csrfName && json.csrfHash) {
                    $form.find('input[name="' + json.csrfName + '"]').val(json.csrfHash);
                }
                return (json && json.data) ? json.data : [];
            },
            error: function () {
                $('#product_report_list tbody').html('<tr><td colspan="6" class="text-center text-danger">Failed to load product list.</td></tr>');
            }
        },
        columnDefs: [
            { targets: 5, orderable: false, searchable: false }
        ]
    });

    $form.off('submit').on('submit', function (event) {
        event.preventDefault();
        dt.ajax.reload();
    });

    $('#btnResetSearch').off('click').on('click', function () {
        $('#txtsearch').val('');
        dt.search('').ajax.reload();
    });

    $('#txtsearch').off('keypress').on('keypress', function (event) {
        if (event.which === 13) {
            event.preventDefault();
            dt.ajax.reload();
        }
    });

    $('#product_report_list_filter input[type="search"]').off('keyup').on('keyup', function () {
        var value = $(this).val();
        if (value === '' || value.length >= 2) {
            dt.search(value).draw();
        }
    });

    $('#product_report_list_filter input[type="search"]').attr('placeholder', 'Quick filter table...');
})();

// Refresh list after returning from add/edit forms.
window.reloadDrugMasterList = function () {
    if (window.jQuery && $.fn && $.fn.DataTable && $.fn.DataTable.isDataTable('#product_report_list')) {
        $('#product_report_list').DataTable().ajax.reload(null, false);
    }
};
</script>
