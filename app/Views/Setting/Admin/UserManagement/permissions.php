<div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h3 class="card-title  mb-0">Give Permissions</h3>
            <div class="card-tools ms-auto">
                <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('setting/admin/user-management') ?>','maindiv','User Management');">
                    <i class="bi bi-arrow-left"></i>
                    Back to User List
                </button>
            </div>
        </div>
            <div class="card-body">
                <?php $message = $message ?? session('message'); ?>
                <?php $errors = $errors ?? session('errors'); ?>
                <?php if (! empty($message)) : ?>
                    <div class="alert alert-success"><?= esc($message) ?></div>
                <?php endif ?>
                <?php if (! empty($errors)) : ?>
                    <div class="alert alert-danger">
                        <?php foreach ((array) $errors as $error) : ?>
                            <div><?= esc($error) ?></div>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
                <form class="needs-validation" novalidate action="<?= base_url('setting/admin/user-management/permissions') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="user_id">User</label>
                            <select class="form-select" id="user_id" name="user_id" required>
                                <option value="">Select user</option>
                                <?php if (! empty($users)) : ?>
                                    <?php foreach ($users as $user) : ?>
                                        <?php
                                        $emailIdentity = $user->getEmailIdentity();
                                        $email = $emailIdentity ? $emailIdentity->secret : '';
                                        $label = trim(($user->username ?? '') . ($email ? ' (' . $email . ')' : ''));
                                        ?>
                                        <option value="<?= esc($user->id) ?>" <?= ! empty($selectedUser) && (int) $selectedUser->id === (int) $user->id ? 'selected' : '' ?>>
                                            <?= esc($label) ?>
                                        </option>
                                    <?php endforeach ?>
                                <?php endif ?>
                            </select>
                            <div class="invalid-feedback">User is required.</div>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button class="btn btn-outline-secondary" type="button" id="loadPermissions">Load Permissions</button>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="form-label">Permissions</label>
                        <div class="alert alert-info py-2 mb-2">
                            Pharmacy module access is controlled by <strong>pharmacy.access</strong>.
                        </div>
                        <div class="border rounded p-3 bg-light">
                            <?php
                            $selectedPermissions = [];
                            if (! empty($selectedUser)) {
                                $selectedPermissions = $selectedUser->getPermissions() ?? [];
                            }

                            $groupedPermissions = [];
                            $groupTitles = [
                                'admin' => 'Admin',
                                'users' => 'Users',
                                'beta' => 'Beta',
                                'template' => 'Templates',
                                'pharmacy' => 'Pharmacy',
                                'opd' => 'OPD Doctor Panel',
                                'billing.opd' => 'OPD',
                                'billing.charges' => 'Charges',
                                'billing.ipd' => 'IPD Billing',
                                'billing' => 'Billing',
                                'other' => 'Other',
                            ];
                            foreach ($permissions as $permissionKey => $permissionLabel) {
                                $parts = explode('.', $permissionKey);
                                $groupKey = $parts[0] ?? 'other';
                                if ($groupKey === 'billing' && isset($parts[1])) {
                                    $groupKey = $groupKey . '.' . $parts[1];
                                }

                                if (! isset($groupTitles[$groupKey])) {
                                    $titleBase = str_replace(['-', '_'], ' ', $groupKey);
                                    $titleBase = str_replace('billing ', '', $titleBase);
                                    $groupTitles[$groupKey] = ucwords($titleBase);
                                }

                                if (! isset($groupedPermissions[$groupKey])) {
                                    $groupedPermissions[$groupKey] = [];
                                }

                                $groupedPermissions[$groupKey][$permissionKey] = $permissionLabel;
                            }
                            if (! isset($groupedPermissions['other'])) {
                                $groupedPermissions['other'] = [];
                            }

                            $groupOrder = [
                                'admin',
                                'users',
                                'template',
                                'pharmacy',
                                'opd',
                                'billing.opd',
                                'billing.charges',
                                'billing.ipd',
                                'billing',
                                'beta',
                                'other',
                            ];
                            ?>
                            <?php if (! empty($permissions)) : ?>
                                <div class="row g-3">
                                    <?php foreach ($groupOrder as $groupKey) : ?>
                                        <?php if (empty($groupedPermissions[$groupKey])) { continue; } ?>
                                        <div class="col-12 col-md-6 col-lg-4">
                                            <div class="fw-bold mb-2"><?= esc($groupTitles[$groupKey] ?? $groupKey) ?></div>
                                            <?php foreach ($groupedPermissions[$groupKey] as $permissionKey => $permissionLabel) : ?>
                                                <div class="form-check mb-1">
                                                    <input class="form-check-input" type="checkbox" id="perm_<?= esc($permissionKey) ?>" name="permissions[]" value="<?= esc($permissionKey) ?>" <?= in_array($permissionKey, $selectedPermissions, true) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="perm_<?= esc($permissionKey) ?>">
                                                        <?= esc($permissionLabel) ?>
                                                    </label>
                                                </div>
                                            <?php endforeach ?>
                                        </div>
                                    <?php endforeach ?>
                                </div>
                            <?php else : ?>
                                <div class="text-muted">No permissions configured.</div>
                            <?php endif ?>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button class="btn btn-primary" type="submit">Save Permissions</button>
                        <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('setting/admin/user-management') ?>','maindiv','User Management');">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            (function() {
                var loadButton = document.getElementById('loadPermissions');
                var userSelect = document.getElementById('user_id');
                var form = document.querySelector('form[action="<?= base_url('setting/admin/user-management/permissions') ?>"]');

                if (!loadButton || !userSelect) {
                    return;
                }

                loadButton.addEventListener('click', function() {
                    var userId = userSelect.value;
                    if (!userId) {
                        return;
                    }
                    var url = '<?= base_url('setting/admin/user-management/permissions') ?>' + '?user_id=' + encodeURIComponent(userId);
                    load_form_div(url, 'maindiv', 'Give Permission');
                });

                if (!form || !window.jQuery) {
                    return;
                }

                $(form).on('submit', function(event) {
                    event.preventDefault();

                    $.post($(form).attr('action'), $(form).serialize())
                        .done(function(html) {
                            $('#maindiv').html(html);
                        })
                        .fail(function() {
                            alert('Request failed. Please try again.');
                        });
                });
            })();
        </script>
    </div>
