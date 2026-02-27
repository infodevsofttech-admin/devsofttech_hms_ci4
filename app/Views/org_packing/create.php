<!DOCTYPE html>
<html>
<head>
    <title>Create New Packing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-plus-circle"></i> Create New Packing
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="createForm">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="label_no" class="form-label">Label No <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="label_no" 
                                           name="label_no" 
                                           required
                                           placeholder="Enter packing label">
                                </div>
                                <div class="col-md-6">
                                    <label for="date_of_create" class="form-label">Date Created <span class="text-danger">*</span></label>
                                    <input type="date" 
                                           class="form-control" 
                                           id="date_of_create" 
                                           name="date_of_create" 
                                           value="<?= date('Y-m-d') ?>"
                                           required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="org_type" class="form-label">Organization Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="org_type" name="org_type" required>
                                        <option value="">Select Type</option>
                                        <option value="0">OPD</option>
                                        <option value="1">IPD</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-check-circle"></i> Create Packing
                                    </button>
                                    <a href="<?= site_url('org-packing') ?>" 
                                       class="btn btn-secondary" 
                                       onclick="load_form_div(event, this.href)">
                                        <i class="bi bi-x-circle"></i> Cancel
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#createForm').on('submit', function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: '<?= site_url('org-packing/store') ?>',
                    method: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    beforeSend: function() {
                        $('button[type="submit"]').prop('disabled', true);
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                            // Redirect to edit page to add cases
                            load_form_div_url('<?= site_url('org-packing/edit/') ?>' + response.insert_id);
                        } else {
                            alert(response.message);
                            $('button[type="submit"]').prop('disabled', false);
                        }
                    },
                    error: function() {
                        alert('Error creating packing');
                        $('button[type="submit"]').prop('disabled', false);
                    }
                });
            });
        });

        function load_form_div_url(url) {
            if (typeof load_form_div !== 'undefined') {
                const event = new Event('click');
                const link = document.createElement('a');
                link.href = url;
                load_form_div(event, url);
            } else {
                window.location.href = url;
            }
        }
    </script>
</body>
</html>
