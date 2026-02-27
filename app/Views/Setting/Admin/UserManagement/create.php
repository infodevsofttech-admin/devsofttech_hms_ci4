

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h3 class="card-title mb-0">New Users</h3>
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
            <form class="needs-validation" novalidate action="<?= base_url('setting/admin/user-management/new') ?>" method="post">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="username">Username</label>
                        <input class="form-control" id="username" name="username" type="text" value="<?= esc($formData['username'] ?? old('username')) ?>" required>
                        <div class="invalid-feedback">Username is required.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">Email</label>
                        <input class="form-control" id="email" name="email" type="email" value="<?= esc($formData['email'] ?? old('email')) ?>" required>
                        <div class="invalid-feedback">Email is required.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password">Password</label>
                        <input class="form-control" id="password" name="password" type="password" required>
                        <div class="invalid-feedback">Password is required.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="role">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">Select role</option>
                            <?php if (! empty($roles)) : ?>
                                <?php foreach ($roles as $roleKey => $roleInfo) : ?>
                                    <option value="<?= esc($roleKey) ?>" <?= ($formData['role'] ?? old('role')) === $roleKey ? 'selected' : '' ?>>
                                        <?= esc($roleInfo['title'] ?? $roleKey) ?>
                                    </option>
                                <?php endforeach ?>
                            <?php endif ?>
                        </select>
                        <div class="invalid-feedback">Role is required.</div>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Create User</button>
                    <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('setting/admin/user-management') ?>','maindiv','User Management');">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function() {
            var form = document.querySelector('form[action="<?= base_url('setting/admin/user-management/new') ?>"]');
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
