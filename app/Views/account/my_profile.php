<section class="section">
    <div class="row g-3">
        <div class="col-lg-7 col-md-9">
            <div class="card">
                <div class="card-header"><strong>My Profile</strong></div>
                <div class="card-body">
                    <?= csrf_field() ?>
                    <?php
                    $loginId = trim((string) ($user->username ?? ''));
                    if ($loginId === '') {
                        $loginId = trim((string) ($user->email ?? 'User'));
                    }
                    $personName = trim((string) ($person_name ?? ''));
                    ?>

                    <div class="mb-2">
                        <label class="form-label">Login ID</label>
                        <input type="text" class="form-control form-control-sm" value="<?= esc($loginId) ?>" readonly>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Person Name</label>
                        <input type="text" class="form-control form-control-sm" id="profile_person_name" maxlength="120" value="<?= esc($personName) ?>" placeholder="Enter person name">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">User ID</label>
                        <input type="text" class="form-control form-control-sm" value="<?= esc((string) ($user->id ?? '')) ?>" readonly>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control form-control-sm" id="profile_email" maxlength="254" value="<?= esc((string) ($user->email ?? '')) ?>" placeholder="Enter email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone No.</label>
                        <input type="text" class="form-control form-control-sm" id="profile_phone_no" maxlength="20" value="<?= esc((string) ($phone_no ?? '')) ?>" placeholder="Enter phone number">
                    </div>

                    <div class="border rounded p-2 mb-3 bg-light">
                        <div class="small fw-semibold mb-2">Change Password (Optional)</div>
                        <div class="mb-2">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control form-control-sm" id="profile_password" autocomplete="new-password" placeholder="Minimum 8 characters">
                        </div>
                        <div>
                            <label class="form-label">Confirm Password</label>
                            <input type="password" class="form-control form-control-sm" id="profile_password_confirm" autocomplete="new-password" placeholder="Re-enter new password">
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm" id="btn_profile_save">Save Profile</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_profile_reset_pwd">Clear Password</button>
                    </div>
                    <div class="small text-muted mt-2" id="profile_msg">Update your email, phone, and password here.</div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    function getCsrfPair() {
        var input = document.querySelector('input[name="<?= csrf_token() ?>"]');
        if (!input) {
            return { name: '<?= csrf_token() ?>', value: '<?= csrf_hash() ?>' };
        }
        return { name: input.getAttribute('name'), value: input.value };
    }

    function updateCsrf(data) {
        if (!data || !data.csrfName || !data.csrfHash) {
            return;
        }
        var input = document.querySelector('input[name="' + data.csrfName + '"]');
        if (input) {
            input.value = data.csrfHash;
        }
    }

    function setMsg(type, text) {
        var $msg = $('#profile_msg');
        $msg.removeClass('text-success text-danger text-muted');
        if (type === 'ok') {
            $msg.addClass('text-success');
        } else if (type === 'err') {
            $msg.addClass('text-danger');
        } else {
            $msg.addClass('text-muted');
        }
        $msg.text(text || '');
    }

    $('#btn_profile_reset_pwd').on('click', function() {
        $('#profile_password,#profile_password_confirm').val('');
        setMsg('normal', 'Password fields cleared.');
    });

    $('#btn_profile_save').on('click', function() {
        var payload = {
            person_name: ($('#profile_person_name').val() || '').trim(),
            email: ($('#profile_email').val() || '').trim(),
            phone_no: ($('#profile_phone_no').val() || '').trim(),
            password: ($('#profile_password').val() || '').toString(),
            password_confirm: ($('#profile_password_confirm').val() || '').toString()
        };

        var csrf = getCsrfPair();
        payload[csrf.name] = csrf.value;

        var $btn = $(this);
        $btn.prop('disabled', true).text('Saving...');

        $.post('<?= base_url('my-profile/save') ?>', payload, function(data) {
            updateCsrf(data);
            $btn.prop('disabled', false).text('Save Profile');

            if (parseInt(data.update || '0', 10) !== 1) {
                setMsg('err', data.error_text || 'Unable to update profile');
                return;
            }

            $('#profile_password,#profile_password_confirm').val('');
            setMsg('ok', data.error_text || 'Profile updated successfully');

            var displayName = (data.display_name || '').toString();
            var loginId = (data.login_id || '').toString();
            var userId = parseInt(data.user_id || '0', 10);
            if (displayName) {
                $('#header-user-title').text(displayName);
                if (userId > 0) {
                    $('#header-user-with-id').text(displayName);
                    $('#header-user-id').text('User ID: ' + userId);
                }
            }
            if (loginId) {
                $('#header-user-login-id').text('Login ID: ' + loginId);
            }
        }, 'json').fail(function() {
            $btn.prop('disabled', false).text('Save Profile');
            setMsg('err', 'Unable to update profile');
        });
    });
})();
</script>
