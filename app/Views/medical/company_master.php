<div class="card">
    <div class="card-body pt-3">
        <div id="company_master_msg" class="mb-2"></div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0">Company Master</h5>
            <div class="d-flex gap-2">
                <button onclick="syncCompanyMastersFromApi();" type="button" class="btn btn-outline-success btn-sm">Sync From API</button>
                <button onclick="load_form_div('<?= base_url('Product_master/CompanyEdit/0') ?>','test_div','Company : New Company :Pharmacy');" type="button" class="btn btn-primary btn-sm">Add New</button>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="table-responsive">
                    <table id="company_report_list" class="table table-bordered table-striped table-sm align-middle w-100">
                        <thead>
                        <tr>
                            <th>Company Name</th>
                            <th>Person / Phone</th>
                            <th style="width:90px;">Action</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
            <div class="col-lg-6" id="test_div"></div>
        </div>
    </div>
</div>

<script>
(function () {
    if (!window.jQuery || !$.fn.DataTable) return;

    $('#company_report_list').DataTable({
        processing : true,
        serverSide : true,
        destroy    : true,
        ajax: {
            url : '<?= base_url('product_master/CompanyListData') ?>',
            type: 'GET'
        },
        columns: [
            { data: 0, title: 'Company Name' },
            { data: 1, title: 'Person / Phone' },
            { data: 2, title: 'Action', orderable: false, searchable: false }
        ],
        pageLength: 25,
        order: [[0, 'asc']],
        language: {
            processing: '<span class="spinner-border spinner-border-sm me-2"></span>Loading…'
        }
    });
})();

function refreshCompanyMasterList() {
    if (window.jQuery && $.fn.DataTable && $.fn.DataTable.isDataTable('#company_report_list')) {
        $('#company_report_list').DataTable().ajax.reload(null, false);
    }
}

function syncCompanyMastersFromApi() {
    $('#company_master_msg').html('<div class="alert alert-info mb-0"><span class="spinner-border spinner-border-sm me-2"></span>Syncing company and formulation masters from gateway API…</div>');
    $.ajax({
        url     : '<?= base_url('product_master/SyncDrugMasters') ?>',
        type    : 'POST',
        dataType: 'json',
        success : function (response) {
            $('#company_master_msg').html(response.show_text || '');
            refreshCompanyMasterList();
        },
        error: function () {
            $('#company_master_msg').html('<div class="alert alert-danger mb-0">Unable to sync masters.</div>');
        }
    });
}
</script>

