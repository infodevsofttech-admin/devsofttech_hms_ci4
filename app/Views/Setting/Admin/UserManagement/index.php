<div id="maindiv">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2 border-bottom">
            <div>
                <h4 class="card-title mb-0 fw-bold text-dark">User Management</h4>
                <div class="text-muted small">Manage hospital staff accounts, roles, clinical linkages, and credentials</div>
            </div>

            <div class="card-tools ms-auto d-flex align-items-center gap-2 flex-wrap">
                <button class="btn btn-outline-info" type="button" onclick="load_form_div('<?= base_url('setting/admin/user-management/sessions') ?>','maindiv','Active Sessions');">
                    <i class="bi bi-broadcast"></i> Active Sessions
                </button>
                <button class="btn btn-outline-dark" type="button" onclick="load_form_div('<?= base_url('setting/admin/roles') ?>','maindiv','Role Master');">
                    <i class="bi bi-shield-check"></i> Role Master
                </button>
                <button class="btn btn-primary" type="button" onclick="load_form_div('<?= base_url('setting/admin/user-management/new') ?>','maindiv','New User');">
                    <i class="bi bi-person-plus-fill"></i> New User
                </button>
            </div>
        </div>

        <div class="card-body p-3">
            <?php $message = $message ?? session('message'); ?>
            <?php $errors = $errors ?? session('errors'); ?>
            <?php if (! empty($message)) : ?><span class="d-none" data-page-notification data-notification-type="success" data-notification-title="User Management" data-notification-message="<?= esc((string) $message, 'attr') ?>"></span><?php endif ?>
            <?php foreach ((array) $errors as $error) : ?><span class="d-none" data-page-notification data-notification-type="error" data-notification-title="User Management" data-notification-message="<?= esc((string) $error, 'attr') ?>"></span><?php endforeach ?>

            <!-- Advanced Filter Bar -->
            <div class="row g-2 mb-3 align-items-end bg-light p-2 rounded border">
                <div class="col-md-4 col-sm-6">
                    <label class="form-label small fw-semibold text-secondary mb-1" for="um_filter_search">Search Staff / User</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" id="um_filter_search" class="form-control" placeholder="Search by name, login ID, phone, email, emp code...">
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label small fw-semibold text-secondary mb-1" for="um_filter_role">Filter by Role</label>
                    <select id="um_filter_role" class="form-select form-select-sm">
                        <option value="">All Roles</option>
                        <?php if (! empty($roles)) : ?>
                            <?php foreach ($roles as $roleKey => $roleInfo) : ?>
                                <option value="<?= esc($roleKey) ?>"><?= esc($roleInfo['title'] ?? $roleKey) ?></option>
                            <?php endforeach ?>
                        <?php endif ?>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label small fw-semibold text-secondary mb-1" for="um_filter_status">Filter by Status</label>
                    <select id="um_filter_status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="active">Active Only</option>
                        <option value="inactive">Inactive Only</option>
                    </select>
                </div>
                <div class="col-md-2 col-sm-6 d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="um_filter_reset">
                        <i class="bi bi-x-circle"></i> Reset
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0" id="tbl_user_list">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Staff Member</th>
                            <th>Clinical / Staff Link</th>
                            <th>Contact Details</th>
                            <th>Role</th>
                            <th style="width: 140px;">Status</th>
                            <th style="width: 170px;" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (! empty($users)) : ?>
                            <?php
                            $rolesConfig = setting('AuthGroups.groups');
                            $currentUid = function_exists('auth') ? (int) (auth()->user()->id ?? 0) : 0;
                            ?>
                            <?php foreach ($users as $index => $user) : ?>
                                <?php
                                $userId = (int) ($user['id'] ?? 0);
                                $isSelf = ($userId === $currentUid);
                                $groups = (array) ($user['groups'] ?? []);
                                $personName = trim((string) ($user['full_name'] ?? ''));
                                $phoneNo = trim((string) ($user['phone_no'] ?? ''));
                                $email = trim((string) ($user['email'] ?? ''));
                                $empCode = trim((string) ($user['employee_code'] ?? ''));
                                $dept = trim((string) ($user['department'] ?? ''));
                                $desig = trim((string) ($user['designation'] ?? ''));
                                $isActive = ! empty($user['active']);

                                // Clinical mapping
                                $linkedDoctor = trim((string) ($user['linked_doctor_name'] ?? ''));
                                $linkedNurse = trim((string) ($user['linked_nurse_name'] ?? ''));
                                ?>
                                <tr data-user-id="<?= $userId ?>"
                                    data-username="<?= esc(strtolower($user['username'] ?? ''), 'attr') ?>"
                                    data-person-name="<?= esc(strtolower($personName), 'attr') ?>"
                                    data-phone="<?= esc($phoneNo, 'attr') ?>"
                                    data-email="<?= esc(strtolower($email), 'attr') ?>"
                                    data-emp-code="<?= esc(strtolower($empCode), 'attr') ?>"
                                    data-roles="<?= esc(strtolower(implode(' ', $groups)), 'attr') ?>"
                                    data-status="<?= $isActive ? 'active' : 'inactive' ?>">
                                    <td class="text-muted small"><?= $index + 1 ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-circle d-flex align-items-center justify-content-center fw-bold bg-primary text-white rounded-circle" style="width:34px; height:34px; font-size:13px;">
                                                <?= strtoupper(substr($personName !== '' ? $personName : ($user['username'] ?? 'U'), 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark">
                                                    <?= esc($personName !== '' ? $personName : $user['username']) ?>
                                                    <?php if ($isSelf) : ?>
                                                        <span class="badge bg-secondary ms-1" style="font-size:10px;">You</span>
                                                    <?php endif ?>
                                                </div>
                                                <div class="small text-muted d-flex align-items-center gap-2">
                                                    <code><?= esc($user['username'] ?? '') ?></code>
                                                    <?php if ($empCode !== '') : ?>
                                                        <span class="badge bg-light text-secondary border py-0">Emp: <?= esc($empCode) ?></span>
                                                    <?php endif ?>
                                                    <?php if ($desig !== '' || $dept !== '') : ?>
                                                        <span>&bull; <?= esc(implode(' / ', array_filter([$desig, $dept]))) ?></span>
                                                    <?php endif ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($linkedDoctor !== '') : ?>
                                            <span class="badge rounded-pill" style="background-color:#0d9488; color:#fff;" title="Linked Doctor">
                                                <i class="bi bi-hospital"></i> <?= esc($linkedDoctor) ?>
                                            </span>
                                            <?php if (! empty($user['linked_doctor_reg'])) : ?>
                                                <div class="text-muted" style="font-size:10px;">Reg: <?= esc($user['linked_doctor_reg']) ?></div>
                                            <?php endif ?>
                                        <?php elseif ($linkedNurse !== '') : ?>
                                            <span class="badge rounded-pill" style="background-color:#d946ef; color:#fff;" title="Linked Nurse">
                                                <i class="bi bi-heart-pulse"></i> <?= esc($linkedNurse) ?>
                                            </span>
                                        <?php else : ?>
                                            <span class="text-muted small">&mdash;</span>
                                        <?php endif ?>
                                    </td>
                                    <td>
                                        <div><?= esc($phoneNo !== '' ? $phoneNo : '-') ?></div>
                                        <div class="small text-muted"><?= esc($email !== '' ? $email : '-') ?></div>
                                    </td>
                                    <td>
                                        <?php if (! empty($groups)) : ?>
                                            <?php foreach ($groups as $g) : ?>
                                                <?php
                                                $roleTitle = $rolesConfig[$g]['title'] ?? $g;
                                                $badgeStyle = 'background-color:#64748b; color:#fff;';
                                                if (in_array($g, ['superadmin', 'admin'], true)) {
                                                    $badgeStyle = 'background-color:#dc2626; color:#fff;';
                                                } elseif ($g === 'doctor') {
                                                    $badgeStyle = 'background-color:#0284c7; color:#fff;';
                                                } elseif ($g === 'nurse') {
                                                    $badgeStyle = 'background-color:#7c3aed; color:#fff;';
                                                } elseif (strpos($g, 'billing') !== false || strpos($g, 'cashier') !== false) {
                                                    $badgeStyle = 'background-color:#16a34a; color:#fff;';
                                                } elseif (strpos($g, 'pharmacy') !== false) {
                                                    $badgeStyle = 'background-color:#ea580c; color:#fff;';
                                                } elseif (strpos($g, 'stock') !== false) {
                                                    $badgeStyle = 'background-color:#475569; color:#fff;';
                                                }
                                                ?>
                                                <span class="badge rounded-pill mb-1" style="<?= $badgeStyle ?>"><?= esc($roleTitle) ?></span>
                                            <?php endforeach ?>
                                        <?php else : ?>
                                            <span class="badge bg-light text-muted border">No role</span>
                                        <?php endif ?>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch d-inline-flex align-items-center gap-1 mb-0">
                                            <input class="form-check-input btn-toggle-user-status"
                                                   type="checkbox"
                                                   role="switch"
                                                   data-user-id="<?= $userId ?>"
                                                   data-username="<?= esc((string) ($user['username'] ?? ''), 'attr') ?>"
                                                   <?= $isActive ? 'checked' : '' ?>
                                                   <?= $isSelf ? 'disabled title="You cannot deactivate your own account"' : '' ?>>
                                            <span class="status-label small fw-semibold <?= $isActive ? 'text-success' : 'text-secondary' ?>">
                                                <?= $isActive ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary" title="Edit user" aria-label="Edit user" onclick="load_form_div('<?= base_url('setting/admin/user-management/edit/' . $userId) ?>','maindiv','Edit User');">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-warning" title="Set Temporary PIN" aria-label="Reset password" onclick="load_form_div('<?= base_url('setting/admin/user-management/reset-password/' . $userId) ?>','maindiv','Reset Password');">
                                                <i class="bi bi-key-fill"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-info" title="Additional permissions" aria-label="Additional permissions" onclick="load_form_div('<?= base_url('setting/admin/user-management/permissions?user_id=' . $userId) ?>','maindiv','Additional Permissions');">
                                                <i class="bi bi-shield-lock"></i>
                                            </button>
                                            <?php if (! $isSelf) : ?>
                                                <button type="button" class="btn btn-outline-danger btn-delete-user" title="Deactivate & remove user" aria-label="Delete user" data-user-id="<?= $userId ?>" data-username="<?= esc((string) ($user['username'] ?? ''), 'attr') ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No users found. Click <strong>New User</strong> to add your first staff member.</td>
                            </tr>
                        <?php endif ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<script>
(function() {
    function filterUsersTable() {
        var query = ($('#um_filter_search').val() || '').toString().trim().toLowerCase();
        var selectedRole = ($('#um_filter_role').val() || '').toString().trim().toLowerCase();
        var selectedStatus = ($('#um_filter_status').val() || '').toString().trim().toLowerCase();

        $('#tbl_user_list tbody tr').each(function() {
            var $row = $(this);
            if ($row.find('td').length <= 1) return; // skip empty state row

            var username = ($row.data('username') || '').toString();
            var personName = ($row.data('person-name') || '').toString();
            var phone = ($row.data('phone') || '').toString();
            var email = ($row.data('email') || '').toString();
            var empCode = ($row.data('emp-code') || '').toString();
            var roles = ($row.data('roles') || '').toString();
            var status = ($row.data('status') || '').toString();

            var matchesQuery = (query === '') ||
                (username.indexOf(query) !== -1) ||
                (personName.indexOf(query) !== -1) ||
                (phone.indexOf(query) !== -1) ||
                (email.indexOf(query) !== -1) ||
                (empCode.indexOf(query) !== -1);

            var matchesRole = (selectedRole === '') || (roles.indexOf(selectedRole) !== -1);
            var matchesStatus = (selectedStatus === '') || (status === selectedStatus);

            $row.toggle(matchesQuery && matchesRole && matchesStatus);
        });
    }

    $(document).off('input.umFilter', '#um_filter_search').on('input.umFilter', '#um_filter_search', filterUsersTable);
    $(document).off('change.umFilter', '#um_filter_role,#um_filter_status').on('change.umFilter', '#um_filter_role,#um_filter_status', filterUsersTable);

    $(document).off('click.umReset', '#um_filter_reset').on('click.umReset', '#um_filter_reset', function() {
        $('#um_filter_search,#um_filter_role,#um_filter_status').val('');
        filterUsersTable();
    });

    // Quick Active / Inactive Status Switcher
    $(document).off('change.umToggleStatus', '.btn-toggle-user-status').on('change.umToggleStatus', '.btn-toggle-user-status', function(e) {
        var $checkbox = $(this);
        var userId = parseInt($checkbox.data('user-id') || '0', 10);
        var username = ($checkbox.data('username') || 'user').toString();
        var targetChecked = $checkbox.is(':checked');

        var confirmMsg = targetChecked ?
            ('Activate account for ' + username + '?') :
            ('Deactivate ' + username + '? Any active login sessions will be terminated immediately.');

        if (!window.confirm(confirmMsg)) {
            $checkbox.prop('checked', !targetChecked);
            return;
        }

        var postData = {};
        postData['<?= csrf_token() ?>'] = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';

        $.post('<?= base_url('setting/admin/user-management/toggle-status') ?>/' + userId, postData, function(res) {
            if (!res || parseInt(res.success || '0', 10) !== 1) {
                notify('error', 'Status Toggle', (res && res.error_text) || 'Failed to update user status.');
                $checkbox.prop('checked', !targetChecked);
                return;
            }

            var newActive = parseInt(res.active || '0', 10) === 1;
            $checkbox.prop('checked', newActive);
            var $row = $checkbox.closest('tr');
            $row.attr('data-status', newActive ? 'active' : 'inactive');

            var $label = $checkbox.siblings('.status-label');
            if (newActive) {
                $label.removeClass('text-secondary').addClass('text-success').text('Active');
            } else {
                $label.removeClass('text-success').addClass('text-secondary').text('Inactive');
            }

            notify('success', 'User Status', res.message || 'Status updated successfully.');
        }, 'json').fail(function(xhr) {
            notify('error', 'Status Toggle', (xhr.responseJSON && xhr.responseJSON.error_text) || 'Request failed.');
            $checkbox.prop('checked', !targetChecked);
        });
    });

    // Delete (Deactivate) User
    $(document).off('click.userDelete', '.btn-delete-user').on('click.userDelete', '.btn-delete-user', function() {
        var userId = parseInt($(this).data('user-id') || '0', 10);
        var username = ($(this).data('username') || 'this user').toString();
        if (userId <= 0 || !window.confirm('Deactivate and archive account for ' + username + '? The account will be disabled immediately.')) {
            return;
        }

        var postData = {};
        postData['<?= csrf_token() ?>'] = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';

        $.post('<?= base_url('setting/admin/user-management/delete') ?>/' + userId, postData, function(data) {
            if (!data || parseInt(data.update || '0', 10) !== 1) {
                notify('error', 'User Deactivation', (data && data.error_text) || 'Unable to deactivate user.');
                return;
            }
            notify('success', 'User Deactivation', data.error_text || 'User deactivated successfully.');
            load_form_div('<?= base_url('setting/admin/user-management') ?>', 'maindiv', 'User Management');
        }, 'json').fail(function(xhr) {
            notify('error', 'User Deactivation', (xhr.responseJSON && xhr.responseJSON.error_text) || 'Unable to deactivate user.');
        });
    });
})();
</script>
</div>