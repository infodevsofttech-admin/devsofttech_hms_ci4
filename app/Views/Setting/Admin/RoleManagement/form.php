<?php
$role = $role ?? [];
$isEdit = ! empty($role['id']);
$isSuperAdmin = ($role['alias'] ?? '') === 'superadmin';
$selectedPermissions = array_values((array) ($role['permissions'] ?? []));
$allPermissions = array_merge($wildcards ?? [], $permissions ?? []);
$grouped = [];
foreach ($allPermissions as $key => $label) {
    $scope = strstr($key, '.', true) ?: 'other';
    $grouped[$scope][$key] = $label;
}
?>
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div><h3 class="card-title mb-0"><?= $isEdit ? 'Edit Role' : 'New Role' ?></h3><div class="text-muted small">Users assigned this role inherit every selected permission.</div></div>
        <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('setting/admin/roles') ?>','maindiv','Role Master');"><i class="bi bi-arrow-left"></i> Back to Role Master</button>
    </div>
    <div class="card-body">
        <?php if (! empty($errors)) : ?><div class="alert alert-danger"><?php foreach ($errors as $error) : ?><div><?= esc($error) ?></div><?php endforeach ?></div><?php endif ?>
        <?php if ($isSuperAdmin) : ?><div class="alert alert-info">Super Admin permissions are protected against lockout and cannot be changed.</div><?php endif ?>

        <form id="frm_role_master" action="<?= $isEdit ? base_url('setting/admin/roles/edit/' . (int) $role['id']) : base_url('setting/admin/roles') ?>" method="post">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="alias">Role Code</label>
                    <input class="form-control" id="alias" name="alias" type="text" maxlength="64" value="<?= esc($role['alias'] ?? '') ?>" placeholder="e.g. lab_manager" <?= $isEdit ? 'readonly' : 'required' ?>>
                    <div class="form-text">Permanent lowercase system code; it cannot be changed later.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="title">Role Name</label>
                    <input class="form-control" id="title" name="title" type="text" maxlength="120" value="<?= esc($role['title'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="description">Description</label>
                    <input class="form-control" id="description" name="description" type="text" maxlength="255" value="<?= esc($role['description'] ?? '') ?>">
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between mt-4 mb-2">
                <div><strong>Inherited Permissions</strong><div class="text-muted small">Wildcard selections such as Pharmacy: All include current and future permissions in that module.</div></div>
                <?php if (! $isSuperAdmin) : ?><button class="btn btn-sm btn-outline-secondary" type="button" id="roleClearPermissions">Clear All</button><?php endif ?>
            </div>
            <div class="row g-3">
                <?php foreach ($grouped as $scope => $items) : ?>
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="border rounded p-3 h-100">
                            <div class="fw-bold mb-2"><?= esc(ucwords(str_replace('_', ' ', $scope))) ?></div>
                            <?php foreach ($items as $key => $label) : ?>
                                <div class="form-check mb-1">
                                    <input class="form-check-input role-permission" type="checkbox" name="permissions[]" id="role_perm_<?= esc(md5($key)) ?>" value="<?= esc($key) ?>" <?= in_array($key, $selectedPermissions, true) ? 'checked' : '' ?> <?= $isSuperAdmin ? 'disabled' : '' ?>>
                                    <label class="form-check-label" for="role_perm_<?= esc(md5($key)) ?>"><?= esc($label) ?> <code><?= esc($key) ?></code></label>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary" type="submit">Save Role</button>
                <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('setting/admin/roles') ?>','maindiv','Role Master');">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    $(document).off('click.roleClear', '#roleClearPermissions').on('click.roleClear', '#roleClearPermissions', function() {
        $('.role-permission').prop('checked', false);
    });
    $(document).off('submit.roleMaster', '#frm_role_master').on('submit.roleMaster', '#frm_role_master', function(event) {
        event.preventDefault();
        var form = this;
        $.post($(form).attr('action'), $(form).serialize()).done(function(html) {
            $('#maindiv').html(html);
        }).fail(function(xhr) {
            alert((xhr.responseJSON && xhr.responseJSON.error_text) || 'Unable to save role.');
        });
    });
})();
</script>
