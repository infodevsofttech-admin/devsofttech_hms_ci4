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

                    <div class="border rounded p-2 mb-3">
                        <div class="small fw-semibold mb-2">Profile Photo</div>
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <img id="profile_photo_preview"
                                 src="<?= esc((string) ($profile_photo_url ?? base_url('assets/img/profile-img.jpg'))) ?>"
                                 alt="Profile Photo"
                                 class="rounded-circle border"
                                 style="width:72px;height:72px;object-fit:cover;">
                            <div>
                                <input type="file" class="form-control form-control-sm" id="profile_photo" accept="image/jpeg,image/png,image/webp">
                                <div class="small text-muted mt-1">Allowed: JPG, PNG, WEBP. Max size: 2MB.</div>
                            </div>
                        </div>
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

            <div class="card mt-3">
                <div class="card-header"><strong>User Settings</strong></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Sidebar Auto-Hide Delay (Seconds)</label>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control" id="sidebar_auto_hide_seconds" min="0" max="60" step="1" value="<?= esc((string) ($sidebar_auto_hide_seconds ?? '7')) ?>" placeholder="0-60">
                            <span class="input-group-text"><small class="text-muted">Hospital default: <?= esc((string) hospital_setting_value('SIDEBAR_AUTO_HIDE_SECONDS', '7')) ?>s</small></span>
                        </div>
                        <div class="small text-muted mt-1">How many seconds before sidebar auto-hides. Enter 0 to disable auto-hide. Leave blank to use hospital default.</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success btn-sm" id="btn_settings_save">Save Settings</button>
                    </div>
                    <div class="small text-success mt-2" id="settings_msg" style="display:none;">Settings saved successfully.</div>
                    <div class="small text-danger mt-2" id="settings_error" style="display:none;"></div>
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

    $('#profile_photo').on('change', function() {
        var file = this.files && this.files[0] ? this.files[0] : null;
        if (!file) {
            return;
        }

        var allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (allowed.indexOf((file.type || '').toLowerCase()) < 0) {
            $(this).val('');
            setMsg('err', 'Profile photo must be JPG, PNG, or WEBP.');
            return;
        }

        if ((file.size || 0) > 2 * 1024 * 1024) {
            $(this).val('');
            setMsg('err', 'Profile photo must be less than 2MB.');
            return;
        }

        var blobUrl = URL.createObjectURL(file);
        $('#profile_photo_preview').attr('src', blobUrl);
        setMsg('normal', 'Photo selected. Click Save Profile to upload.');
    });

    $('#btn_profile_save').on('click', function() {
        var payload = new window.FormData();
        payload.append('person_name', ($('#profile_person_name').val() || '').trim());
        payload.append('email', ($('#profile_email').val() || '').trim());
        payload.append('phone_no', ($('#profile_phone_no').val() || '').trim());
        payload.append('password', ($('#profile_password').val() || '').toString());
        payload.append('password_confirm', ($('#profile_password_confirm').val() || '').toString());
        payload.append('sidebar_auto_hide_seconds', ($('#sidebar_auto_hide_seconds').val() || '').trim());

        var photoInput = document.getElementById('profile_photo');
        if (photoInput && photoInput.files && photoInput.files[0]) {
            payload.append('profile_photo', photoInput.files[0]);
        }

        var csrf = getCsrfPair();
        payload.append(csrf.name, csrf.value);

        var $btn = $(this);
        $btn.prop('disabled', true).text('Saving...');

        $.ajax({
            url: '<?= base_url('my-profile/save') ?>',
            method: 'POST',
            data: payload,
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function(data) {
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

            if (data.profile_photo_url) {
                $('#profile_photo_preview').attr('src', data.profile_photo_url);
                $('#header-user-avatar').attr('src', data.profile_photo_url);
                $('#profile_photo').val('');
            }
        }).fail(function() {
            $btn.prop('disabled', false).text('Save Profile');
            setMsg('err', 'Unable to update profile');
        });
    });

    $('#btn_settings_save').on('click', function() {
        var sidebarAutoHideSeconds = ($('#sidebar_auto_hide_seconds').val() || '').trim();
        
        // Validate input
        if (sidebarAutoHideSeconds !== '') {
            var val = parseInt(sidebarAutoHideSeconds, 10);
            if (isNaN(val) || val < 0 || val > 60) {
                $('#settings_error').text('Please enter a value between 0 and 60.').show();
                $('#settings_msg').hide();
                return;
            }
        }

        var payload = new window.FormData();
        payload.append('sidebar_auto_hide_seconds', sidebarAutoHideSeconds);

        var csrf = getCsrfPair();
        payload.append(csrf.name, csrf.value);

        var $btn = $(this);
        $btn.prop('disabled', true).text('Saving...');

        $.ajax({
            url: '<?= base_url('my-profile/save-settings') ?>',
            method: 'POST',
            data: payload,
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function(data) {
            updateCsrf(data);
            $btn.prop('disabled', false).text('Save Settings');

            if (parseInt(data.success || '0', 10) !== 1) {
                $('#settings_error').text(data.message || 'Unable to save settings').show();
                $('#settings_msg').hide();
                return;
            }

            $('#settings_msg').show();
            $('#settings_error').hide();
            setTimeout(function() {
                $('#settings_msg').fadeOut(500);
            }, 2000);
        }).fail(function() {
            $btn.prop('disabled', false).text('Save Settings');
            $('#settings_error').text('Unable to save settings').show();
            $('#settings_msg').hide();
        });
    });
})();
</script>
