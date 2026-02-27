<!DOCTYPE html>
<html>
<head>
    <title>Organization Packing Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-search {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-search:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-box-seam"></i> Organization Packing Management
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <a href="<?= site_url('org-packing/create') ?>" class="btn btn-success" onclick="load_form_div(event, this.href)">
                                    <i class="bi bi-plus-circle"></i> Create New Packing
                                </a>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <form id="searchForm">
                                    <div class="input-group">
                                        <input type="text" 
                                               name="search" 
                                               id="searchInput" 
                                               class="form-control" 
                                               placeholder="Search by Label No or Invoice No"
                                               autocomplete="off">
                                        <button type="submit" class="btn btn-search">
                                            <i class="bi bi-search"></i> Search
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <hr>

                        <div id="searchResults">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Enter search term or leave blank to show recent packings
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Auto-search on page load
            searchPackings();

            $('#searchForm').on('submit', function(e) {
                e.preventDefault();
                searchPackings();
            });

            // Search on input change with delay
            let searchTimeout;
            $('#searchInput').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    searchPackings();
                }, 500);
            });
        });

        function searchPackings() {
            const searchValue = $('#searchInput').val();
            
            $.ajax({
                url: '<?= site_url('org-packing/search') ?>',
                method: 'POST',
                data: { search: searchValue },
                beforeSend: function() {
                    $('#searchResults').html('<div class="text-center"><div class="spinner-border" role="status"></div></div>');
                },
                success: function(response) {
                    $('#searchResults').html(response);
                },
                error: function() {
                    $('#searchResults').html('<div class="alert alert-danger">Error loading results</div>');
                }
            });
        }

        function deletePacking(id) {
            if (confirm('Are you sure you want to delete this packing?')) {
                $.ajax({
                    url: '<?= site_url('org-packing/delete/') ?>' + id,
                    method: 'POST',
                    success: function(response) {
                        searchPackings();
                        alert('Packing deleted successfully');
                    },
                    error: function() {
                        alert('Error deleting packing');
                    }
                });
            }
        }
    </script>
</body>
</html>
