<!DOCTYPE html>
<html>
<head>
    <title>Edit Packing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .section-header {
            background: #f8f9fa;
            padding: 10px;
            border-left: 4px solid #667eea;
            margin-bottom: 15px;
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
                            <i class="bi bi-pencil"></i> Edit Packing: <?= esc($packing->label_no) ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Packing Details Section -->
                        <div class="section-header">
                            <h6 class="mb-0"><i class="bi bi-info-circle"></i> Packing Details</h6>
                        </div>
                        
                        <form id="updateForm">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="label_no" class="form-label">Label No <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="label_no" 
                                           name="label_no" 
                                           value="<?= esc($packing->label_no) ?>"
                                           required>
                                </div>
                                <div class="col-md-4">
                                    <label for="date_of_create" class="form-label">Date Created <span class="text-danger">*</span></label>
                                    <input type="date" 
                                           class="form-control" 
                                           id="date_of_create" 
                                           name="date_of_create" 
                                           value="<?= date('Y-m-d', strtotime($packing->date_of_create)) ?>"
                                           required>
                                </div>
                                <div class="col-md-4">
                                    <label for="org_type" class="form-label">Organization Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="org_type" name="org_type" required>
                                        <option value="0" <?= $packing->org_type == 0 ? 'selected' : '' ?>>OPD</option>
                                        <option value="1" <?= $packing->org_type == 1 ? 'selected' : '' ?>>IPD</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Update Details
                                    </button>
                                    <a href="<?= site_url('org-packing') ?>" 
                                       class="btn btn-secondary" 
                                       onclick="load_form_div(event, this.href)">
                                        <i class="bi bi-arrow-left"></i> Back to List
                                    </a>
                                    <a href="<?= site_url('org-packing/print/' . $packing_id) ?>" 
                                       class="btn btn-success" 
                                       target="_blank">
                                        <i class="bi bi-printer"></i> Print List
                                    </a>
                                </div>
                            </div>
                        </form>

                        <hr class="my-4">

                        <!-- Add Cases Section -->
                        <div class="section-header">
                            <h6 class="mb-0"><i class="bi bi-plus-circle"></i> Add Cases to Packing</h6>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="case_search" class="form-label">Search and Select Case</label>
                                <select class="form-select" id="case_search" style="width: 100%">
                                    <option value="">Type to search by case code, patient name, or UHID</option>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="button" 
                                        class="btn btn-success w-100" 
                                        id="addCaseBtn">
                                    <i class="bi bi-plus"></i> Add to Packing
                                </button>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Cases in Packing Section -->
                        <div class="section-header">
                            <h6 class="mb-0"><i class="bi bi-list-check"></i> Cases in this Packing</h6>
                        </div>

                        <div id="casesListContainer">
                            <?= view('org_packing/cases_list', ['cases' => $cases, 'packing_id' => $packing_id]) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        const packingId = <?= $packing_id ?>;
        const orgType = <?= $packing->org_type ?>;

        $(document).ready(function() {
            // Initialize Select2 for case search
            $('#case_search').select2({
                theme: 'bootstrap-5',
                ajax: {
                    url: '<?= site_url('api/search-org-cases') ?>',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term,
                            org_type: orgType,
                            packing_id: packingId
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.map(item => ({
                                id: item.id,
                                text: item.text
                            }))
                        };
                    },
                    cache: true
                },
                minimumInputLength: 2,
                placeholder: 'Type to search...',
                allowClear: true
            });

            // Update form submission
            $('#updateForm').on('submit', function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: '<?= site_url('org-packing/update/' . $packing_id) ?>',
                    method: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function() {
                        alert('Error updating packing');
                    }
                });
            });

            // Add case button
            $('#addCaseBtn').on('click', function() {
                const caseId = $('#case_search').val();
                
                if (!caseId) {
                    alert('Please select a case first');
                    return;
                }

                $.ajax({
                    url: '<?= site_url('org-packing/add-case/') ?>' + caseId + '/' + packingId,
                    method: 'POST',
                    beforeSend: function() {
                        $('#addCaseBtn').prop('disabled', true);
                    },
                    success: function(response) {
                        $('#casesListContainer').html(response);
                        $('#case_search').val(null).trigger('change');
                        $('#addCaseBtn').prop('disabled', false);
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        alert(response?.message || 'Error adding case');
                        $('#addCaseBtn').prop('disabled', false);
                    }
                });
            });
        });

        function removeCase(orgId) {
            if (!confirm('Remove this case from packing?')) {
                return;
            }

            $.ajax({
                url: '<?= site_url('org-packing/remove-case/') ?>' + orgId + '/' + packingId,
                method: 'POST',
                success: function(response) {
                    $('#casesListContainer').html(response);
                },
                error: function() {
                    alert('Error removing case');
                }
            });
        }
    </script>
</body>
</html>
