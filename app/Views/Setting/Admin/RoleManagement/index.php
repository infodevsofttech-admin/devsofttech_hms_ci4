<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h3 class="card-title mb-0">Role Master</h3>
            <div class="text-muted small">Define job roles and their inherited permissions.</div>
        </div>
        <div class="card-tools ms-auto d-flex gap-2">
            <button class="btn btn-primary" type="button" onclick="load_form_div('<?= base_url('setting/admin/roles/new') ?>','maindiv','New Role');">
                <i class="bi bi-plus-lg"></i> New Role
            </button>
            <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('setting/admin/user-management') ?>','maindiv','User Management');">
                <i class="bi bi-arrow-left"></i> Back to Users
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (! empty($message)) : ?><div class="alert alert-success"><?= esc($message) ?></div><?php endif ?>
        <?php if (! empty($errors)) : ?>
            <div class="alert alert-danger"><?php foreach ($errors as $error) : ?><div><?= esc($error) ?></div><?php endforeach ?></div>
        <?php endif ?>

        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead><tr><th>Role</th><th>Code</th><th>Permissions</th><th>Users</th><th>Status</th><th>Type</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach (($roles ?? []) as $role) : ?>
                    <tr>
                        <td><strong><?= esc($role['title']) ?></strong><div class="text-muted small"><?= esc($role['description'] ?? '') ?></div></td>
                        <td><code><?= esc($role['alias']) ?></code></td>
                        <td><?= (int) $role['permission_count'] ?></td>
                        <td><?= (int) $role['user_count'] ?></td>
                        <td><span class="badge <?= (int) $role['is_active'] === 1 ? 'bg-success' : 'bg-secondary' ?>"><?= (int) $role['is_active'] === 1 ? 'Active' : 'Inactive' ?></span></td>
                        <td><?= (int) $role['is_builtin'] === 1 ? '<span class="badge bg-light text-dark">Built-in</span>' : 'Custom' ?></td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                <button class="btn btn-sm btn-outline-primary" type="button" onclick="load_form_div('<?= base_url('setting/admin/roles/edit/' . (int) $role['id']) ?>','maindiv','Edit Role');">Edit</button>
                                <?php if ((int) $role['is_builtin'] !== 1) : ?>
                                    <button class="btn btn-sm btn-outline-secondary btn-role-status" type="button" data-role-id="<?= (int) $role['id'] ?>" data-active="<?= (int) $role['is_active'] === 1 ? 0 : 1 ?>"><?= (int) $role['is_active'] === 1 ? 'Deactivate' : 'Activate' ?></button>
                                    <?php if ((int) $role['is_active'] !== 1 && (int) $role['user_count'] === 0) : ?>
                                        <button class="btn btn-sm btn-outline-danger btn-role-delete" type="button" data-role-id="<?= (int) $role['id'] ?>" data-title="<?= esc($role['title'], 'attr') ?>">Delete</button>
                                    <?php endif ?>
                                <?php endif ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function() {
    var csrfName = '<?= csrf_token() ?>';
    var csrfHash = '<?= csrf_hash() ?>';

    function postRole(url, data) {
        data[csrfName] = csrfHash;
        $.post(url, data, null, 'json').done(function(response) {
            csrfHash = response.csrf_hash || csrfHash;
            load_form_div('<?= base_url('setting/admin/roles') ?>', 'maindiv', 'Role Master');
        }).fail(function(xhr) {
            var response = xhr.responseJSON || {};
            csrfHash = response.csrf_hash || csrfHash;
            alert(response.error_text || 'Unable to update role.');
        });
    }

    $(document).off('click.roleStatus', '.btn-role-status').on('click.roleStatus', '.btn-role-status', function() {
        postRole('<?= base_url('setting/admin/roles/status') ?>/' + $(this).data('role-id'), {is_active: $(this).data('active')});
    });

    $(document).off('click.roleDelete', '.btn-role-delete').on('click.roleDelete', '.btn-role-delete', function() {
        if (!confirm('Delete role ' + $(this).data('title') + '?')) return;
        postRole('<?= base_url('setting/admin/roles/delete') ?>/' + $(this).data('role-id'), {});
    });
})();
</script>
