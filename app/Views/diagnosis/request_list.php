<input type="hidden" id="lab_type" name="lab_type" value="<?= esc($lab_type) ?>" />

<div class="pagetitle">
    <h1><?= esc($lab_type_name) ?></h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="javascript:load_form('<?= base_url('diagnosis') ?>','Diagnosis');">Diagnosis</a></li>
            <li class="breadcrumb-item active"><?= esc($lab_type_name) ?></li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Search</h5>
            
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="input-group">
                        <input class="form-control" type="text" id="txtsearch" name="txtsearch" placeholder="Invoice / UHID / Phone">
                        <button type="button" class="btn btn-primary" id="btn_search">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="input-group">
                        <input class="form-control" type="text" id="txtsearch_srno" name="txtsearch_srno" placeholder="Daily Serial No.">
                        <button type="button" class="btn btn-info" id="btn_srno">
                            <i class="bi bi-search"></i> Sr. No.
                        </button>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="input-group">
                        <input class="form-control" type="text" id="txtsearch_labno" name="txtsearch_labno" placeholder="Lab No.">
                        <button type="button" class="btn btn-success" id="btn_labno">
                            <i class="bi bi-search"></i> Lab No.
                        </button>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <?php if ($lab_type == 5): ?>
                        <a href="javascript:void(0);" class="btn btn-secondary">
                            <i class="bi bi-file-earmark-text"></i> Template
                        </a>
                    <?php else: ?>
                        <a href="javascript:void(0);" class="btn btn-secondary">
                            <i class="bi bi-file-earmark-text"></i> Template
                        </a>
                    <?php endif; ?>
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
        load_form(url, 'Pathology Invoice Details');
    };
});
</script>
