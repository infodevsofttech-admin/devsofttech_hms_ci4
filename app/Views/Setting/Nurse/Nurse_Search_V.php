<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0 text-primary"><i class="bi bi-person-heart me-2"></i>Nursing Staff Master</h5>
            <small class="text-muted">Manage nursing staff records and credentials for hospital workspace</small>
        </div>
        <button type="button" class="btn btn-primary btn-sm" id="btn_add_nurse" data-bs-toggle="modal" data-bs-target="#nurseMasterModal">
            <i class="bi bi-plus-lg me-1"></i> Add New Nurse
        </button>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-6 col-lg-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control form-control-sm border-start-0" id="nurse_search_input" placeholder="Search by name, ID code, qualification..." value="<?= esc($searchQuery ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_refresh_nurse_list">
                        <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="nurse_table">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;" class="ps-3">#</th>
                            <th style="width: 120px;">Staff ID</th>
                            <th>Full Name</th>
                            <th style="width: 150px;">ABDM HPR ID</th>
                            <th style="width: 140px;">Council Reg No.</th>
                            <th style="width: 120px;">Designation</th>
                            <th>Qualification</th>
                            <th>Contact / Mobile</th>
                            <th style="width: 90px;">Status</th>
                            <th style="width: 110px;" class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="nurse_table_body">
                        <?php if (empty($nurses)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="bi bi-person-x fs-3 d-block mb-1"></i>
                                    No nursing staff records found. Click <strong>"Add New Nurse"</strong> to create one.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($nurses as $idx => $nurse): ?>
                                <tr id="nurse_row_<?= (int)$nurse['id'] ?>" data-nurse='<?= json_encode($nurse, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                                    <td class="ps-3 fw-bold text-secondary"><?= $idx + 1 ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= esc($nurse['nurse_code']) ?></span></td>
                                    <td class="fw-semibold text-primary">
                                        <?= esc($nurse['name']) ?>
                                        <?php if (!empty($nurse['gender'])): ?>
                                            <small class="text-muted"> (<?= esc($nurse['gender']) ?>)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($nurse['hpr_id'])): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-shield-check me-1"></i><?= esc($nurse['hpr_id']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code class="text-dark"><?= esc($nurse['registration_no'] ?: '-') ?></code></td>
                                    <td><span class="badge bg-secondary-subtle text-dark border"><?= esc($nurse['designation'] ?: 'Staff Nurse') ?></span></td>
                                    <td><?= esc($nurse['qualification'] ?: '-') ?></td>
                                    <td><?= esc($nurse['contact_no'] ?: '-') ?></td>
                                    <td>
                                        <?php if ((int)$nurse['is_active'] === 1): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <button type="button" class="btn btn-outline-dark btn-sm py-0 px-2 me-1 btn-qr-nurse" data-code="<?= esc($nurse['nurse_code']) ?>" data-name="<?= esc($nurse['name']) ?>">
                                            <i class="bi bi-qr-code"></i> QR
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2 btn-edit-nurse" data-id="<?= (int)$nurse['id'] ?>">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 btn-delete-nurse" data-id="<?= (int)$nurse['id'] ?>" data-name="<?= esc($nurse['name']) ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Add/Edit Nurse -->
<div class="modal fade" id="nurseMasterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light py-2">
                <h5 class="modal-title text-primary fs-6" id="nurseModalTitle"><i class="bi bi-person-plus me-1"></i> Add Nursing Staff</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="nurseMasterForm">
                <input type="hidden" id="nurse_id" name="id" value="0">
                <div class="modal-body">
                    <div id="nurse_form_status" class="alert alert-danger d-none py-2 px-3 mb-3"></div>
                    
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small mb-1">Staff Code / ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="nurse_code" name="nurse_code" placeholder="e.g. NUR-001" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small mb-1">Designation</label>
                            <select class="form-select form-select-sm" id="nurse_designation" name="designation">
                                <option value="Staff Nurse">Staff Nurse</option>
                                <option value="Nursing Supervisor">Nursing Supervisor</option>
                                <option value="Head Nurse">Head Nurse</option>
                                <option value="Sister Incharge">Sister Incharge</option>
                                <option value="ANM">ANM (Auxiliary Nurse Midwife)</option>
                                <option value="GNM">GNM (General Nurse & Midwife)</option>
                                <option value="Nurse Practitioner">Nurse Practitioner</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small mb-1">Status</label>
                            <select class="form-select form-select-sm" id="nurse_is_active" name="is_active">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-bold small mb-1">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="nurse_name" name="name" placeholder="Full name of nurse" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small mb-1">Gender</label>
                            <select class="form-select form-select-sm" id="nurse_gender" name="gender">
                                <option value="Female">Female</option>
                                <option value="Male">Male</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <!-- ABDM / HPR & Registration Section -->
                        <div class="col-12 mt-2">
                            <div class="p-2 bg-light rounded border">
                                <div class="fw-bold small text-primary mb-1"><i class="bi bi-shield-check me-1"></i> ABDM &amp; Council Credentials</div>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small mb-1">ABDM HPR ID / Address</label>
                                        <input type="text" class="form-control form-control-sm" id="nurse_hpr_id" name="hpr_id" placeholder="e.g. 12-3456-7890-1234 or nurse@hprid">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small mb-1">Nursing Council Registration No.</label>
                                        <input type="text" class="form-control form-control-sm" id="nurse_registration_no" name="registration_no" placeholder="e.g. NC-98765/2023">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mt-2">
                            <label class="form-label fw-bold small mb-1">Qualification</label>
                            <input type="text" class="form-control form-control-sm" id="nurse_qualification" name="qualification" placeholder="e.g. B.Sc Nursing, M.Sc Nursing, GNM">
                        </div>
                        <div class="col-md-6 mt-2">
                            <label class="form-label fw-bold small mb-1">Department</label>
                            <input type="text" class="form-control form-control-sm" id="nurse_department" name="department" value="Nursing" placeholder="Department">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small mb-1">Contact No</label>
                            <input type="text" class="form-control form-control-sm" id="nurse_contact_no" name="contact_no" placeholder="Phone number">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small mb-1">Email Address</label>
                            <input type="email" class="form-control form-control-sm" id="nurse_email" name="email" placeholder="Email address">
                        </div>

                        <!-- App Security PIN Section -->
                        <div class="col-md-6 mt-2">
                            <div class="p-2 bg-warning-subtle border border-warning rounded">
                                <label class="form-label fw-bold small text-dark mb-1"><i class="bi bi-key me-1"></i> Mobile App Security PIN</label>
                                <input type="password" maxlength="6" class="form-control form-control-sm" id="nurse_app_pin" name="app_pin" placeholder="Set or update 4-6 digit PIN">
                                <small class="text-muted" style="font-size: 10px;">Used by nurse to login to NursingCare PWA App</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btn_save_nurse">
                        <i class="bi bi-check-lg me-1"></i> Save Nurse
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal for Nurse App QR Code -->
<div class="modal fade" id="nurseQrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content text-center">
            <div class="modal-header bg-light py-2">
                <h5 class="modal-title text-primary fs-6 mb-0" id="qrModalTitle"><i class="bi bi-qr-code-scan me-1"></i> Nurse App QR Code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <h6 class="fw-bold mb-0 text-dark" id="qr_nurse_name">Staff Nurse</h6>
                <span class="badge bg-secondary mb-3" id="qr_nurse_code">NUR-001</span>
                <div class="p-2 border rounded d-inline-block bg-white shadow-sm mb-3">
                    <img id="qr_code_img" src="" alt="Nurse App QR Code" style="width: 180px; height: 180px;" />
                </div>
                <p class="small text-muted mb-0" style="font-size: 11px;">Scan with mobile phone to open <strong>NursingCare PWA</strong> for this nurse profile.</p>
            </div>
            <div class="modal-footer bg-light py-2 justify-content-center">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var saveUrl = '<?= base_url('setting/admin/nurse/save') ?>';
    var deleteUrlBase = '<?= base_url('setting/admin/nurse/delete') ?>';
    var appBaseUrl = '<?= base_url('app/nursing') ?>';

    function resetNurseForm() {
        $('#nurse_id').val('0');
        $('#nurse_code').val('NUR-' + Math.floor(100 + Math.random() * 900));
        $('#nurse_name').val('');
        $('#nurse_hpr_id').val('');
        $('#nurse_registration_no').val('');
        $('#nurse_gender').val('Female');
        $('#nurse_designation').val('Staff Nurse');
        $('#nurse_qualification').val('');
        $('#nurse_contact_no').val('');
        $('#nurse_email').val('');
        $('#nurse_app_pin').val('');
        $('#nurse_department').val('Nursing');
        $('#nurse_is_active').val('1');
        $('#nurse_form_status').addClass('d-none').text('');
        $('#nurseModalTitle').html('<i class="bi bi-person-plus me-1"></i> Add Nursing Staff');
    }

    $(document).on('click', '.btn-qr-nurse', function() {
        var code = $(this).data('code') || '';
        var name = $(this).data('name') || '';
        var appUrl = appBaseUrl + '?nurse_code=' + encodeURIComponent(code);
        var qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' + encodeURIComponent(appUrl);

        $('#qr_nurse_name').text(name);
        $('#qr_nurse_code').text(code);
        $('#qr_code_img').attr('src', qrApiUrl);

        var modalEl = document.getElementById('nurseQrModal');
        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else {
            $(modalEl).modal('show');
        }
    });

    $('#btn_add_nurse').on('click', function() {
        resetNurseForm();
    });

    $(document).on('click', '.btn-edit-nurse', function() {
        var id = $(this).data('id');
        var row = $('#nurse_row_' + id);
        if (!row.length) return;
        var data = row.data('nurse');
        if (!data) return;

        $('#nurse_id').val(data.id || '0');
        $('#nurse_code').val(data.nurse_code || '');
        $('#nurse_name').val(data.name || '');
        $('#nurse_hpr_id').val(data.hpr_id || '');
        $('#nurse_registration_no').val(data.registration_no || '');
        $('#nurse_gender').val(data.gender || 'Female');
        $('#nurse_designation').val(data.designation || 'Staff Nurse');
        $('#nurse_qualification').val(data.qualification || '');
        $('#nurse_contact_no').val(data.contact_no || '');
        $('#nurse_email').val(data.email || '');
        $('#nurse_department').val(data.department || 'Nursing');
        $('#nurse_is_active').val(data.is_active !== undefined ? data.is_active : '1');
        $('#nurse_form_status').addClass('d-none').text('');
        $('#nurseModalTitle').html('<i class="bi bi-pencil-square me-1"></i> Edit Nursing Staff');

        var modalEl = document.getElementById('nurseMasterModal');
        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else {
            $(modalEl).modal('show');
        }
    });

    $('#nurseMasterForm').on('submit', function(e) {
        e.preventDefault();
        var $status = $('#nurse_form_status');
        $status.addClass('d-none').text('');

        var formData = $(this).serialize();
        $.post(saveUrl, formData, function(resp) {
            if (resp && resp.ok) {
                var modalEl = document.getElementById('nurseMasterModal');
                if (window.bootstrap && window.bootstrap.Modal) {
                    var modal = window.bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                } else {
                    $(modalEl).modal('hide');
                }
                load_form_div('<?= base_url('setting/admin/nurse') ?>', 'maindiv', 'Nursing Staff Master');
            } else {
                $status.removeClass('d-none').text((resp && resp.error) ? resp.error : 'Failed to save nurse.');
            }
        }, 'json').fail(function() {
            $status.removeClass('d-none').text('Server error while saving nurse record.');
        });
    });

    $(document).on('click', '.btn-delete-nurse', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        if (!confirm('Are you sure you want to delete nurse: ' + name + '?')) return;

        $.post(deleteUrlBase + '/' + id, {}, function(resp) {
            if (resp && resp.ok) {
                load_form_div('<?= base_url('setting/admin/nurse') ?>', 'maindiv', 'Nursing Staff Master');
            } else {
                alert((resp && resp.error) ? resp.error : 'Failed to delete nurse.');
            }
        }, 'json').fail(function() {
            alert('Failed to delete nurse record.');
        });
    });

    $('#btn_refresh_nurse_list').on('click', function() {
        load_form_div('<?= base_url('setting/admin/nurse') ?>', 'maindiv', 'Nursing Staff Master');
    });

    $('#nurse_search_input').on('keyup', function(e) {
        if (e.key === 'Enter') {
            var q = encodeURIComponent($(this).val());
            load_form_div('<?= base_url('setting/admin/nurse') ?>?q=' + q, 'maindiv', 'Nursing Staff Master');
        }
    });
})();
</script>
