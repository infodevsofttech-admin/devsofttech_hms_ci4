<input type="hidden" id="lab_type" name="lab_type" value="<?= esc($lab_type) ?>" />

<style>
.diag-search-strip .card-body {
    padding: 12px;
}
.diag-search-strip .input-group .btn {
    min-width: 132px;
}
</style>

<section class="section">
    <div class="card diag-search-strip">
        <div class="card-body">
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <input class="form-control" type="text" id="txtsearch" name="txtsearch" placeholder="Invoice Last 6 digit ,UHID,Phone No.">
                        <button type="button" class="btn btn-primary" id="btn_search">
                            Search Invoice
                        </button>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="input-group">
                        <input class="form-control" type="text" id="txtsearch_srno" name="txtsearch_srno" placeholder="Daily Serial No.">
                        <button type="button" class="btn btn-info" id="btn_srno">
                            Search by Sr. No.
                        </button>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="input-group">
                        <input class="form-control" type="text" id="txtsearch_labno" name="txtsearch_labno" placeholder="Lab No.">
                        <button type="button" class="btn btn-success" id="btn_labno">
                            Search By Lab No.
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="searchresult" id="searchresult">
        <!-- Search results will be loaded here -->
    </div>
</section>

<script>
$(document).ready(function() {
    // Search by Invoice/Name
    $('#btn_search').click(function() {
        performSearch();
    });

    $('#txtsearch').keypress(function(e) {
        if (e.which == 13) {
            performSearch();
            return false;
        }
    });

    function performSearch() {
        var lab_type = $('#lab_type').val();
        var txtsearch = $('#txtsearch').val();

        $.ajax({
            url: '<?= base_url('diagnosis/search-lab') ?>',
            type: 'POST',
            data: {
                lab_type: lab_type,
                txtsearch: txtsearch,
                <?= csrf_token() ?>: '<?= csrf_hash() ?>'
            },
            success: function(data) {
                $('#searchresult').html(data);
                initializeDataTable();
            },
            error: function() {
                alert('Error loading data');
            }
        });
    }

    // Search by Serial Number
    $('#btn_srno').click(function() {
        var lab_type = $('#lab_type').val();
        var txtsearch_srno = $('#txtsearch_srno').val();

        $.ajax({
            url: '<?= base_url('diagnosis/search-lab-srno') ?>',
            type: 'POST',
            data: {
                lab_type: lab_type,
                txtsearch_srno: txtsearch_srno,
                <?= csrf_token() ?>: '<?= csrf_hash() ?>'
            },
            success: function(data) {
                $('#searchresult').html(data);
                initializeDataTable();
            },
            error: function() {
                alert('Error loading data');
            }
        });
    });

    $('#txtsearch_srno').keypress(function(e) {
        if (e.which == 13) {
            $('#btn_srno').click();
            return false;
        }
    });

    // Search by Lab Number
    $('#btn_labno').click(function() {
        var lab_type = $('#lab_type').val();
        var txtsearch_labno = $('#txtsearch_labno').val();

        $.ajax({
            url: '<?= base_url('diagnosis/search-lab-labno') ?>',
            type: 'POST',
            data: {
                lab_type: lab_type,
                txtsearch_labno: txtsearch_labno,
                <?= csrf_token() ?>: '<?= csrf_hash() ?>'
            },
            success: function(data) {
                $('#searchresult').html(data);
                initializeDataTable();
            },
            error: function() {
                alert('Error loading data');
            }
        });
    });

    $('#txtsearch_labno').keypress(function(e) {
        if (e.which == 13) {
            $('#btn_labno').click();
            return false;
        }
    });

    function initializeDataTable() {
        // Check if table has actual data rows (not just the empty message)
        var dataRows = $('#datashow1 tbody tr').length;
        var hasActualData = $('#datashow1 tbody tr td:not([colspan])').length > 0;
        
        // Destroy existing DataTable if it exists
        if ($.fn.DataTable.isDataTable('#datashow1')) {
            $('#datashow1').DataTable().destroy();
        }
        
        // Only initialize DataTables if there's actual data
        if (dataRows > 0 && hasActualData) {
            setTimeout(function() {
                try {
                    $('#datashow1').DataTable({
                        paging: true,
                        lengthChange: false,
                        searching: true,
                        ordering: true,
                        info: true,
                        autoWidth: false,
                        responsive: true,
                        order: [[0, 'desc']],
                        columnDefs: [
                            { targets: "_all", className: "dt-left" }
                        ]
                    });
                } catch(e) {
                    console.error('DataTable initialization error:', e);
                }
            }, 50);
        } else {
            console.log('No data rows found or data structure invalid for DataTables');
        }
    }

    // Navigate to Pathology detail view via AJAX
    window.selectPathoology = function(invoiceId) {
        var labType = $('#lab_type').val();
        var url = '<?= base_url('diagnosis/select-lab-invoice') ?>/' + invoiceId + '/' + labType;
        load_form_div(url, 'searchresult', 'Pathology Invoice Details');
    };

    // Keep old HMS behavior: load recent items immediately.
    performSearch();
});
</script>
