<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h3 class="card-title mb-0">Active HMS Sessions</h3>
            <div class="text-muted small">Users active within the last 2 minutes</div>
        </div>
        <div class="card-tools ms-auto d-flex gap-2">
            <button class="btn btn-outline-primary" type="button" id="btn_refresh_user_sessions">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
            <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('setting/admin/user-management') ?>','maindiv','User Management');">
                <i class="bi bi-arrow-left"></i> Back to Users
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php $sessions = $sessions ?? []; ?>
        <div class="alert alert-info py-2">
            <strong><?= count($sessions) ?></strong> user<?= count($sessions) === 1 ? '' : 's' ?> currently online. A user can have only one active HMS session.
        </div>
        <div id="user_session_notice"></div>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Login Time</th>
                        <th>Last Activity</th>
                        <th>IP Address</th>
                        <th>Browser / Device</th>
                        <th style="width:130px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($sessions === []) : ?>
                        <tr><td colspan="6" class="text-center text-muted">No active users.</td></tr>
                    <?php else : ?>
                        <?php foreach ($sessions as $row) : ?>
                            <?php $isCurrent = (int) ($row['user_id'] ?? 0) === (int) ($current_user_id ?? 0); ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= esc((string) ($row['person_name'] ?: ($row['username'] ?? ''))) ?></div>
                                    <div class="small text-muted"><?= esc((string) ($row['username'] ?? '')) ?></div>
                                    <?php if ($isCurrent) : ?><span class="badge bg-success">This session</span><?php endif ?>
                                </td>
                                <td><?= esc(date('d/m/Y h:i A', strtotime((string) ($row['login_at'] ?? 'now')))) ?></td>
                                <td><?= esc(date('d/m/Y h:i:s A', strtotime((string) ($row['last_activity'] ?? 'now')))) ?></td>
                                <td><?= esc((string) ($row['ip_address'] ?? '-')) ?></td>
                                <td class="small text-break" style="max-width:320px;"><?= esc((string) ($row['user_agent'] ?? '-')) ?></td>
                                <td>
                                    <?php if ($isCurrent) : ?>
                                        <span class="text-muted small">Use Sign Out</span>
                                    <?php else : ?>
                                        <button type="button" class="btn btn-outline-danger btn-sm btn-force-user-logout" data-user-id="<?= (int) ($row['user_id'] ?? 0) ?>" data-user-name="<?= esc((string) ($row['username'] ?? 'user'), 'attr') ?>">
                                            Force Logout
                                        </button>
                                    <?php endif ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function() {
    var listUrl = '<?= base_url('setting/admin/user-management/sessions') ?>';

    $(document)
        .off('click.userSessionsRefresh', '#btn_refresh_user_sessions')
        .on('click.userSessionsRefresh', '#btn_refresh_user_sessions', function() {
            load_form_div(listUrl, 'maindiv', 'Active Sessions');
        })
        .off('click.userSessionsLogout', '.btn-force-user-logout')
        .on('click.userSessionsLogout', '.btn-force-user-logout', function() {
            var button = this;
            var userId = parseInt(button.getAttribute('data-user-id') || '0', 10);
            var userName = button.getAttribute('data-user-name') || 'this user';
            if (userId <= 0 || !window.confirm('Force logout ' + userName + '?')) {
                return;
            }

            button.disabled = true;
            $.post('<?= base_url('setting/admin/user-management/sessions/force-logout') ?>/' + userId, {
                '<?= csrf_token() ?>': $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>'
            }, function(data) {
                if (!data || parseInt(data.update || '0', 10) !== 1) {
                    $('#user_session_notice').html('<div class="alert alert-danger">' + $('<div>').text((data && data.error_text) || 'Unable to end session.').html() + '</div>');
                    button.disabled = false;
                    return;
                }
                load_form_div(listUrl, 'maindiv', 'Active Sessions');
            }, 'json').fail(function(xhr) {
                var message = (xhr.responseJSON && xhr.responseJSON.error_text) || 'Unable to end session.';
                $('#user_session_notice').html('<div class="alert alert-danger">' + $('<div>').text(message).html() + '</div>');
                button.disabled = false;
            });
        });
})();
</script>
