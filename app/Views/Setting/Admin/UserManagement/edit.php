<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">Edit User</h3>
        <div class="card-tools ms-auto">
            <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('setting/admin/user-management') ?>','maindiv','User Management');">
                <i class="bi bi-arrow-left"></i>
                Back to User List
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php $errors = $errors ?? session('errors'); ?>
        <?php $formData = $formData ?? []; ?>
        <?php if (! empty($errors)) : ?>
            <div class="alert alert-danger">
                <?php foreach ((array) $errors as $error) : ?>
                    <div><?= esc($error) ?></div>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <form id="frm_user_edit" class="needs-validation" novalidate action="<?= base_url('setting/admin/user-management/edit/' . (int) ($user->id ?? 0)) ?>" method="post">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="username">Login ID</label>
                    <input class="form-control" id="username" name="username" type="text" maxlength="30" value="<?= esc($formData['username'] ?? ($user->username ?? '')) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="person_name">Person Name</label>
                    <input class="form-control" id="person_name" name="person_name" type="text" maxlength="120" value="<?= esc($formData['person_name'] ?? ($person_name ?? '')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" id="email" name="email" type="email" value="<?= esc($formData['email'] ?? ($email ?? '')) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="phone_no">Phone No.</label>
                    <input class="form-control" id="phone_no" name="phone_no" type="text" maxlength="20" value="<?= esc($formData['phone_no'] ?? ($phone_no ?? '')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="password">New Password (Optional)</label>
                    <input class="form-control" id="password" name="password" type="password" autocomplete="new-password" placeholder="Leave blank to keep current password">
                </div>
                <div class="col-md-6 d-flex align-items-center">
                    <div class="form-check mt-4">
                        <input class="form-check-input" id="active" name="active" type="checkbox" value="1" <?= (int) ($formData['active'] ?? ($user->active ?? 0)) === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="active">Active User</label>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary" type="submit">Save Changes</button>
                <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('setting/admin/user-management') ?>','maindiv','User Management');">Cancel</button>
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
