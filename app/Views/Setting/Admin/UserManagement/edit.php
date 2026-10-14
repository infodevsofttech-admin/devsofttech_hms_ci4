<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2 border-bottom">
        <div>
            <h4 class="card-title mb-0 fw-bold text-dark">Edit User &amp; Staff Account</h4>
            <div class="text-muted small">Update login credentials, role assignments, staff metadata, and clinical links</div>
        </div>
        <div class="card-tools ms-auto">
            <button class="btn btn-outline-secondary" type="button" onclick="load_form_div('<?= base_url('setting/admin/user-management') ?>','maindiv','User Management');">
                <i class="bi bi-arrow-left"></i> Back to User List
            </button>
        </div>
    </div>
    <div class="card-body p-4">
        <?php $errors = $errors ?? session('errors'); ?>
        <?php $formData = $formData ?? []; ?>
        <?php foreach ((array) $errors as $error) : ?><span class="d-none" data-page-notification data-notification-type="error" data-notification-title="Edit User" data-notification-message="<?= esc((string) $error, 'attr') ?>"></span><?php endforeach ?>

        <form id="frm_user_edit" class="needs-validation" novalidate action="<?= base_url('setting/admin/user-management/edit/' . (int) ($user->id ?? 0)) ?>" method="post">
            <?= csrf_field() ?>

            <!-- Section 1: Account Credentials -->
            <div class="mb-4">
                <h6 class="fw-bold text-primary text-uppercase border-bottom pb-2 mb-3">
                    <i class="bi bi-shield-lock"></i> Account Credentials &amp; Role
                </h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" for="username">Login ID / Username <span class="text-danger">*</span></label>
                        <input class="form-control" id="username" name="username" type="text" maxlength="30" value="<?= esc($formData['username'] ?? ($user->username ?? '')) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" for="email">Email Address <span class="text-danger">*</span></label>
                        <input class="form-control" id="email" name="email" type="email" value="<?= esc($formData['email'] ?? ($email ?? '')) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" for="password">New Password (Optional)</label>
                        <input class="form-control" id="password" name="password" type="password" minlength="8" autocomplete="new-password" placeholder="Leave blank to keep current">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold" for="role">Assigned Role <span class="text-danger">*</span></label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">Select role</option>
                            <?php foreach (($roles ?? []) as $roleKey => $roleInfo) : ?>
                                <option value="<?= esc($roleKey) ?>" <?= (string) ($formData['role'] ?? ($current_role ?? '')) === (string) $roleKey ? 'selected' : '' ?>>
                                    <?= esc($roleInfo['title'] ?? $roleKey) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>

                    <!-- Clinical Linkage for Doctor -->
                    <div class="col-md-4 clinical-link-field clinical-doctor-link" style="display:none;">
                        <label class="form-label fw-semibold text-info-emphasis" for="doctor_id">
                            <i class="bi bi-hospital"></i> Link to Doctor Master
                        </label>
                        <select class="form-select border-info" id="doctor_id" name="doctor_id">
                            <option value="">-- No Doctor Linked --</option>
                            <?php if (! empty($doctors)) : ?>
                                <?php foreach ($doctors as $doc) : ?>
                                    <?php
                                    $docName = trim(($doc['p_title'] ?? 'Dr.') . ' ' . ($doc['p_fname'] ?? '') . ' ' . ($doc['p_lname'] ?? ''));
                                    $selectedDoc = (int) ($formData['doctor_id'] ?? ($doctor_id ?? 0));
                                    ?>
                                    <option value="<?= (int) $doc['id'] ?>" <?= ($selectedDoc === (int) $doc['id']) ? 'selected' : '' ?>>
                                        <?= esc($docName) ?> <?= ! empty($doc['doctor_reg_no']) ? '(Reg: ' . esc($doc['doctor_reg_no']) . ')' : '' ?>
                                    </option>
                                <?php endforeach ?>
                            <?php endif ?>
                        </select>
                        <div class="form-text text-info">Locks doctor context in OPD and prescriptions.</div>
                    </div>

                    <!-- Clinical Linkage for Nurse -->
                    <div class="col-md-4 clinical-link-field clinical-nurse-link" style="display:none;">
                        <label class="form-label fw-semibold text-purple" for="nurse_id">
                            <i class="bi bi-heart-pulse"></i> Link to Nurse Master
                        </label>
                        <select class="form-select border-primary" id="nurse_id" name="nurse_id">
                            <option value="">-- No Nurse Linked --</option>
                            <?php if (! empty($nurses)) : ?>
                                <?php foreach ($nurses as $nurse) : ?>
                                    <?php $selectedNurse = (int) ($formData['nurse_id'] ?? ($nurse_id ?? 0)); ?>
                                    <option value="<?= (int) $nurse['id'] ?>" <?= ($selectedNurse === (int) $nurse['id']) ? 'selected' : '' ?>>
                                        <?= esc($nurse['name']) ?> (<?= esc($nurse['nurse_code'] ?? 'Staff') ?>)
                                    </option>
                                <?php endforeach ?>
                            <?php endif ?>
                        </select>
                        <div class="form-text">Used for nursing chart signing and bedside records.</div>
                    </div>

                    <div class="col-md-4 d-flex align-items-center">
                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" id="active" name="active" type="checkbox" role="switch" value="1" <?= (int) ($formData['active'] ?? ($user->active ?? 0)) === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="active">Account Active</label>
                            <div class="form-text">Deactivating terminates all active sessions immediately.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Staff Details -->
            <div class="mb-4">
                <h6 class="fw-bold text-secondary text-uppercase border-bottom pb-2 mb-3">
                    <i class="bi bi-person-lines-fill"></i> Staff Profile Details
                </h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="person_name">Person / Staff Full Name</label>
                        <input class="form-control" id="person_name" name="person_name" type="text" maxlength="120" value="<?= esc($formData['person_name'] ?? ($person_name ?? '')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="phone_no">Mobile Phone No.</label>
                        <input class="form-control" id="phone_no" name="phone_no" type="text" maxlength="20" value="<?= esc($formData['phone_no'] ?? ($phone_no ?? '')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="employee_code">Employee / Staff Code</label>
                        <input class="form-control" id="employee_code" name="employee_code" type="text" maxlength="50" placeholder="e.g. EMP-104" value="<?= esc($formData['employee_code'] ?? ($employee_code ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="department">Department</label>
                        <input class="form-control" id="department" name="department" type="text" maxlength="100" placeholder="e.g. General Medicine, Nursing Station 1, Billing Counter" value="<?= esc($formData['department'] ?? ($department ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="designation">Designation</label>
                        <input class="form-control" id="designation" name="designation" type="text" maxlength="100" placeholder="e.g. Consultant Physician, Staff Nurse, Head Cashier" value="<?= esc($formData['designation'] ?? ($designation ?? '')) ?>">
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 pt-3 border-top">
                <button class="btn btn-primary px-4" type="submit">
                    <i class="bi bi-check2-circle"></i> Save Changes
                </button>
                <button class="btn btn-light px-3" type="button" onclick="load_form_div('<?= base_url('setting/admin/user-management') ?>','maindiv','User Management');">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    var form = document.getElementById('frm_user_edit');
    if (!form || !window.jQuery) {
        return;
    }

    var doctorsData = <?= json_encode($doctors ?? []) ?>;
    var nursesData = <?= json_encode($nurses ?? []) ?>;

    function handleRoleChange() {
        var role = ($('#role').val() || '').toLowerCase();
        $('.clinical-link-field').hide();

        if (role === 'doctor') {
            $('.clinical-doctor-link').fadeIn(150);
        } else if (role === 'nurse') {
            $('.clinical-nurse-link').fadeIn(150);
        }
    }

    $('#role').off('change.roleSelect').on('change.roleSelect', handleRoleChange);
    handleRoleChange();

    // Auto-fill when choosing doctor
    $('#doctor_id').off('change.docSelect').on('change.docSelect', function() {
        var docId = parseInt($(this).val() || '0', 10);
        if (docId <= 0) return;

        var doc = doctorsData.find(function(d) { return parseInt(d.id, 10) === docId; });
        if (!doc) return;

        var title = (doc.p_title || 'Dr.').trim();
        var fname = (doc.p_fname || '').trim();
        var lname = (doc.p_lname || '').trim();
        var fullName = (title + ' ' + fname + ' ' + lname).trim();

        if (!$('#person_name').val()) $('#person_name').val(fullName);
        if (!$('#phone_no').val() && doc.mphone1) $('#phone_no').val(doc.mphone1);
        if (!$('#designation').val()) $('#designation').val('Doctor / Consultant');
        if (!$('#department').val()) $('#department').val('Clinical / OPD');
    });

    // Auto-fill when choosing nurse
    $('#nurse_id').off('change.nurseSelect').on('change.nurseSelect', function() {
        var nurseId = parseInt($(this).val() || '0', 10);
        if (nurseId <= 0) return;

        var nurse = nursesData.find(function(n) { return parseInt(n.id, 10) === nurseId; });
        if (!nurse) return;

        if (!$('#person_name').val() && nurse.name) $('#person_name').val(nurse.name);
        if (!$('#phone_no').val() && nurse.contact_no) $('#phone_no').val(nurse.contact_no);
        if (!$('#employee_code').val() && nurse.nurse_code) $('#employee_code').val(nurse.nurse_code);
        if (!$('#designation').val()) $('#designation').val(nurse.designation || 'Staff Nurse');
        if (!$('#department').val()) $('#department').val(nurse.department || 'Nursing');
    });

    $(form).off('submit.userEdit').on('submit.userEdit', function(event) {
        event.preventDefault();
        $.post($(form).attr('action'), $(form).serialize())
            .done(function(html) {
                $('#maindiv').html(html);
                if (typeof showPageNotifications === 'function') {
                    showPageNotifications(document.getElementById('maindiv'));
                }
            })
            .fail(function(xhr) {
                notify('error', 'Edit User', (xhr.responseJSON && xhr.responseJSON.error_text) || 'Request failed. Please try again.');
            });
    });
})();
</script>
