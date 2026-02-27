<div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h3 class="card-title mb-0">Users</h3>

            <div class="card-tools ms-auto">
                <button class="btn btn-primary" type="button" onclick="load_form_div('<?= base_url('setting/admin/user-management/new') ?>','maindiv','New User');">
                    <i class="bi bi-person-plus"></i>
                    New User
                </button>
                <button class="btn btn-outline-primary" type="button" onclick="load_form_div('<?= base_url('setting/admin/user-management/permissions') ?>','maindiv','Give Permission');">
                    <i class="bi bi-shield-lock"></i>
                    Give Permission
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
            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <label class="form-label mb-1" for="um_filter_person">Filter by Person Name</label>
                    <input type="text" id="um_filter_person" class="form-control form-control-sm" placeholder="Type person name...">
                </div>
                <div class="col-md-4">
                    <label class="form-label mb-1" for="um_filter_phone">Filter by Phone No.</label>
                    <input type="text" id="um_filter_phone" class="form-control form-control-sm" placeholder="Type phone no...">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="um_filter_reset">Clear Filters</button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Login ID</th>
                            <th>Person Name</th>
                            <th>Phone No.</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (! empty($users)) : ?>
                            <?php $rolesConfig = setting('AuthGroups.groups'); ?>
                            <?php foreach ($users as $index => $user) : ?>
                                <?php
                                $emailIdentity = $user->getEmailIdentity();
                                $email = $emailIdentity ? $emailIdentity->secret : '';
                                $personName = '';
                                $phoneNo = '';
                                if ($emailIdentity && isset($emailIdentity->extra)) {
                                    $extra = $emailIdentity->extra;
                                    if (is_string($extra) && trim($extra) !== '') {
                                        $decodedExtra = json_decode($extra, true);
                                        if (is_array($decodedExtra)) {
                                            $personName = trim((string) ($decodedExtra['full_name'] ?? ''));
                                            $phoneNo = trim((string) ($decodedExtra['phone_no'] ?? ''));
                                        }
                                    } elseif (is_array($extra)) {
                                        $personName = trim((string) ($extra['full_name'] ?? ''));
                                        $phoneNo = trim((string) ($extra['phone_no'] ?? ''));
                                    }
                                }
                                $groups = $user->getGroups() ?? [];
                                $roleLabels = [];
                                foreach ($groups as $group) {
                                    $roleLabels[] = $rolesConfig[$group]['title'] ?? $group;
                                }
                                $roleText = $roleLabels !== [] ? implode(', ', $roleLabels) : '-';
                                ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= esc($user->username ?? '') ?></td>
                                    <td><?= esc($personName !== '' ? $personName : '-') ?></td>
                                    <td><?= esc($phoneNo !== '' ? $phoneNo : '-') ?></td>
                                    <td><?= esc($email) ?></td>
                                    <td><?= esc($roleText) ?></td>
                                    <td>
                                        <?php if (! empty($user->active)) : ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else : ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="load_form_div('<?= base_url('setting/admin/user-management/edit/' . (int) ($user->id ?? 0)) ?>','maindiv','Edit User');">Edit</button>
                                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="load_form_div('<?= base_url('setting/admin/user-management/reset-password/' . (int) ($user->id ?? 0)) ?>','maindiv','Reset Password');">Reset Password</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No users loaded yet.</td>
                            </tr>
                        <?php endif ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<script>
(function() {
    function applyUserFilters() {
        var personNeedle = ($('#um_filter_person').val() || '').toString().trim().toLowerCase();
        var phoneNeedle = ($('#um_filter_phone').val() || '').toString().trim().toLowerCase();

        $('table.datatable tbody tr').each(function() {
            var $row = $(this);
            var personText = ($row.find('td').eq(2).text() || '').toString().trim().toLowerCase();
            var phoneText = ($row.find('td').eq(3).text() || '').toString().trim().toLowerCase();

            var personOk = personNeedle === '' || personText.indexOf(personNeedle) !== -1;
            var phoneOk = phoneNeedle === '' || phoneText.indexOf(phoneNeedle) !== -1;

            $row.toggle(personOk && phoneOk);
        });
    }

    $(document).off('input.umFilter', '#um_filter_person,#um_filter_phone').on('input.umFilter', '#um_filter_person,#um_filter_phone', function() {
        applyUserFilters();
    });

    $(document).off('click.umFilterReset', '#um_filter_reset').on('click.umFilterReset', '#um_filter_reset', function() {
        $('#um_filter_person,#um_filter_phone').val('');
        applyUserFilters();
    });
})();
</script>