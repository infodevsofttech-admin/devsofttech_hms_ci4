<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2 border-bottom">
        <div>
            <h4 class="card-title mb-0 fw-bold text-dark">New User &amp; Staff Account</h4>
            <div class="text-muted small">Create credentials, assign baseline role, and optionally link to a Doctor or Nurse profile</div>
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
        <?php foreach ((array) $errors as $error) : ?><span class="d-none" data-page-notification data-notification-type="error" data-notification-title="Create User" data-notification-message="<?= esc((string) $error, 'attr') ?>"></span><?php endforeach ?>

        <form id="frm_user_create" class="needs-validation" novalidate action="<?= base_url('setting/admin/user-management/new') ?>" method="post">
            <?= csrf_field() ?>

            <!-- Section 1: Account Credentials -->
            <div class="mb-4">
                <h6 class="fw-bold text-primary text-uppercase border-bottom pb-2 mb-3">
                    <i class="bi bi-shield-lock"></i> Account Credentials &amp; Baseline Role
                </h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" for="username">Login ID / Username <span class="text-danger">*</span></label>
                        <input class="form-control" id="username" name="username" type="text" maxlength="30" placeholder="e.g. nur-637 or dr.sharma" value="<?= esc($formData['username'] ?? old('username')) ?>" required>
                        <div class="form-text">3-30 characters, letters, numbers, dot (.), hyphen (-), underscore (_).</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" for="email">Email Address <span class="text-danger">*</span></label>
                        <input class="form-control" id="email" name="email" type="email" placeholder="staff@hospital.com (or leave blank for auto)" value="<?= esc($formData['email'] ?? old('email')) ?>">
                        <div class="form-text">Used for notifications. Leave blank for auto-generated internal account.</div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-semibold mb-0" for="password">Password <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="btn_quick_pwd" style="font-size: 11px;">
                                <i class="bi bi-magic me-1"></i>Set Staff@123
                            </button>
                        </div>
                        <div class="input-group">
                            <input class="form-control" id="password" name="password" type="password" minlength="8" placeholder="Minimum 8 characters" required>
                            <button class="btn btn-outline-secondary" type="button" id="btn_toggle_pwd" title="Toggle Show/Hide Password">
                                <i class="bi bi-eye" id="pwd_eye_icon"></i>
                            </button>
                        </div>
                        <div class="form-text">At least 8 characters. Minimum standard security.</div>
                    </div>

                    <?php
                    $defaultRole = $formData['role'] ?? old('role');
                    if (empty($defaultRole) && ! empty($selected_doctor_id)) {
                        $defaultRole = 'doctor';
                    } elseif (empty($defaultRole) && ! empty($selected_nurse_id)) {
                        $defaultRole = 'nurse';
                    }
                    $defaultDoctorId = (int) ($formData['doctor_id'] ?? old('doctor_id') ?? $selected_doctor_id ?? 0);
                    $defaultNurseId  = (int) ($formData['nurse_id'] ?? old('nurse_id') ?? $selected_nurse_id ?? 0);
                    ?>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" for="role">Assigned Role <span class="text-danger">*</span></label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">Select role</option>
                            <?php if (! empty($roles)) : ?>
                                <?php foreach ($roles as $roleKey => $roleInfo) : ?>
                                    <option value="<?= esc($roleKey) ?>" <?= $defaultRole === $roleKey ? 'selected' : '' ?>>
                                        <?= esc($roleInfo['title'] ?? $roleKey) ?>
                                    </option>
                                <?php endforeach ?>
                            <?php endif ?>
                        </select>
                        <div class="form-text">Provides baseline module permissions.</div>
                    </div>

                    <!-- Dynamic Clinical Linkage for Doctor -->
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
                                    ?>
                                    <option value="<?= (int) $doc['id'] ?>" <?= ($defaultDoctorId === (int) $doc['id']) ? 'selected' : '' ?>>
                                        <?= esc($docName) ?> <?= ! empty($doc['doctor_reg_no']) ? '(Reg: ' . esc($doc['doctor_reg_no']) . ')' : '' ?>
                                    </option>
                                <?php endforeach ?>
                            <?php endif ?>
                        </select>
                        <div class="form-text text-info">Auto-fills doctor details &amp; sets doctor context in OPD/IPD.</div>
                    </div>

                    <!-- Dynamic Clinical Linkage for Nurse -->
                    <div class="col-md-4 clinical-link-field clinical-nurse-link" style="display:none;">
                        <label class="form-label fw-semibold text-purple" for="nurse_id">
                            <i class="bi bi-heart-pulse"></i> Link to Nurse Master
                        </label>
                        <select class="form-select border-primary" id="nurse_id" name="nurse_id">
                            <option value="">-- No Nurse Linked --</option>
                            <?php if (! empty($nurses)) : ?>
                                <?php foreach ($nurses as $nurse) : ?>
                                    <option value="<?= (int) $nurse['id'] ?>" <?= ($defaultNurseId === (int) $nurse['id']) ? 'selected' : '' ?>>
                                        <?= esc($nurse['name']) ?> (<?= esc($nurse['nurse_code'] ?? 'Staff') ?>)
                                    </option>
                                <?php endforeach ?>
                            <?php endif ?>
                        </select>
                        <div class="form-text">Auto-fills nursing details &amp; logs bedside notes.</div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Staff Details & Hospital Profile -->
            <div class="mb-4">
                <h6 class="fw-bold text-secondary text-uppercase border-bottom pb-2 mb-3">
                    <i class="bi bi-person-lines-fill"></i> Staff Profile &amp; Hospital Details
                </h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="person_name">Person / Staff Full Name</label>
                        <input class="form-control" id="person_name" name="person_name" type="text" maxlength="120" placeholder="e.g. Dr. Rajesh Sharma" value="<?= esc($formData['person_name'] ?? old('person_name')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="phone_no">Mobile Phone No.</label>
                        <input class="form-control" id="phone_no" name="phone_no" type="text" maxlength="20" placeholder="e.g. 9876543210" value="<?= esc($formData['phone_no'] ?? old('phone_no')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="employee_code">Employee / Staff Code</label>
                        <input class="form-control" id="employee_code" name="employee_code" type="text" maxlength="50" placeholder="e.g. EMP-104" value="<?= esc($formData['employee_code'] ?? old('employee_code')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="department">Department</label>
                        <input class="form-control" id="department" name="department" type="text" maxlength="100" placeholder="e.g. General Medicine, Nursing Station 1, Billing Counter" value="<?= esc($formData['department'] ?? old('department')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="designation">Designation</label>
                        <input class="form-control" id="designation" name="designation" type="text" maxlength="100" placeholder="e.g. Consultant Physician, Staff Nurse, Head Cashier" value="<?= esc($formData['designation'] ?? old('designation')) ?>">
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 pt-3 border-top">
                <button class="btn btn-primary px-4" type="submit">
                    <i class="bi bi-check-circle"></i> Create User
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
    var form = document.getElementById('frm_user_create');
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

    // Quick password and toggle visibility
    $('#btn_quick_pwd').off('click').on('click', function() {
        $('#password').val('Staff@123').trigger('input');
        if (typeof notify === 'function') {
            notify('info', 'Password Set', 'Default password set to Staff@123');
        }
    });

    $('#btn_toggle_pwd').off('click').on('click', function() {
        var pwdInput = document.getElementById('password');
        var icon = document.getElementById('pwd_eye_icon');
        if (pwdInput && icon) {
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                pwdInput.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }
    });

    // Auto-populate when selecting a Doctor
    $('#doctor_id').off('change.docSelect').on('change.docSelect', function() {
        var docId = parseInt($(this).val() || '0', 10);
        if (docId <= 0) return;

        var doc = doctorsData.find(function(d) { return parseInt(d.id, 10) === docId; });
        if (!doc) return;

        var title = (doc.p_title || 'Dr.').trim();
        var fname = (doc.p_fname || '').trim();
        var lname = (doc.p_lname || '').trim();
        var fullName = (title + ' ' + fname + ' ' + lname).trim();
        var suggestedUser = 'dr.' + (fname ? fname.toLowerCase().replace(/[^a-z0-9._-]/g, '') : 'doc');

        if (!$('#person_name').val()) $('#person_name').val(fullName);
        if (!$('#phone_no').val() && doc.mphone1) $('#phone_no').val(doc.mphone1);
        if (!$('#designation').val()) $('#designation').val('Doctor / Consultant');
        if (!$('#department').val()) $('#department').val('Clinical / OPD');
        if (!$('#username').val() && fname) {
            $('#username').val(suggestedUser);
        }
        if (!$('#email').val()) {
            if (doc.email1) {
                $('#email').val(doc.email1);
            } else {
                $('#email').val(suggestedUser + '@hospital.local');
            }
        }
    });

    // Auto-populate when selecting a Nurse
    $('#nurse_id').off('change.nurseSelect').on('change.nurseSelect', function() {
        var nurseId = parseInt($(this).val() || '0', 10);
        if (nurseId <= 0) return;

        var nurse = nursesData.find(function(n) { return parseInt(n.id, 10) === nurseId; });
        if (!nurse) return;

        var uName = (nurse.nurse_code ? nurse.nurse_code.toLowerCase().replace(/[^a-z0-9._-]/g, '') : '');

        if (!$('#person_name').val() && nurse.name) $('#person_name').val(nurse.name);
        if (!$('#phone_no').val() && nurse.contact_no) $('#phone_no').val(nurse.contact_no);
        if (!$('#employee_code').val() && nurse.nurse_code) $('#employee_code').val(nurse.nurse_code);
        if (!$('#designation').val()) $('#designation').val(nurse.designation || 'Staff Nurse');
        if (!$('#department').val()) $('#department').val(nurse.department || 'Nursing');
        if (!$('#username').val() && uName) {
            $('#username').val(uName);
        }
        if (!$('#email').val()) {
            if (nurse.email) {
                $('#email').val(nurse.email);
            } else if (uName) {
                $('#email').val(uName + '@hospital.local');
            }
        }
    });

    <?php if (! empty($selected_doctor_id)) : ?>
        $('#doctor_id').trigger('change.docSelect');
    <?php elseif (! empty($selected_nurse_id)) : ?>
        $('#nurse_id').trigger('change.nurseSelect');
    <?php endif; ?>

    // AJAX Form submission with client-side validation
    $(form).off('submit.userCreate').on('submit.userCreate', function(event) {
        event.preventDefault();

        var u = ($('#username').val() || '').trim();
        var p = ($('#password').val() || '').trim();
        var e = ($('#email').val() || '').trim();
        var r = ($('#role').val() || '').trim();

        if (!u || u.length < 3) {
            if (typeof notify === 'function') notify('warning', 'Validation', 'Please enter a valid Login ID (minimum 3 characters).');
            $('#username').focus();
            return;
        }

        if (!/^[a-zA-Z0-9._-]+$/.test(u)) {
            if (typeof notify === 'function') notify('warning', 'Validation', 'Login ID can only contain letters, numbers, dot, hyphen, or underscore.');
            $('#username').focus();
            return;
        }

        if (!r) {
            if (typeof notify === 'function') notify('warning', 'Validation', 'Please select an assigned role.');
            $('#role').focus();
            return;
        }

        if (!p || p.length < 8) {
            if (typeof notify === 'function') notify('warning', 'Validation', 'Password is required and must be at least 8 characters. Click "Set Staff@123" for quick fill.');
            $('#password').focus();
            return;
        }

        if (!e) {
            e = u.toLowerCase() + '@hospital.local';
            $('#email').val(e);
        }

        $.post($(form).attr('action'), $(form).serialize())
            .done(function(html) {
                $('#maindiv').html(html);
                if (typeof showPageNotifications === 'function') {
                    showPageNotifications(document.getElementById('maindiv'));
                }
            })
            .fail(function(xhr) {
                if (typeof notify === 'function') {
                    notify('error', 'Create User', (xhr.responseJSON && xhr.responseJSON.error_text) || 'Request failed. Please check form validation.');
                }
            });
    });
})();
</script>
