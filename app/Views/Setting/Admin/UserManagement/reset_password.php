<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">Set Temporary PIN</h3>
        <div class="card-tools ms-auto">
            <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('setting/admin/user-management') ?>','maindiv','User Management');">
                <i class="bi bi-arrow-left"></i>
                Back to User List
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php $errors = $errors ?? session('errors'); ?>
        <?php foreach ((array) $errors as $error) : ?><span class="d-none" data-page-notification data-notification-type="error" data-notification-title="Reset Password" data-notification-message="<?= esc((string) $error, 'attr') ?>"></span><?php endforeach ?>

        <div class="mb-3">
            <div><strong>Login ID:</strong> <?= esc((string) ($user->username ?? '')) ?></div>
            <div><strong>User ID:</strong> <?= esc((string) ($user->id ?? '')) ?></div>
            <div class="text-muted small mt-1">Set a 6-digit temporary PIN. User will be forced to change password at next login.</div>
        </div>

        <form id="frm_reset_pwd" action="<?= base_url('setting/admin/user-management/reset-password/' . (int) ($user->id ?? 0)) ?>" method="post">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="password">Temporary PIN (6 digits)</label>
                    <input class="form-control" id="password" name="password" type="password" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" autocomplete="new-password" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="password_confirm">Confirm PIN</label>
                    <input class="form-control" id="password_confirm" name="password_confirm" type="password" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" autocomplete="new-password" required>
                </div>
            </div>

            <div class="mt-3 d-flex gap-2 align-items-center flex-wrap">
                <button class="btn btn-outline-primary btn-sm" type="button" id="btn_generate_pin">Generate PIN</button>
                <button class="btn btn-outline-secondary btn-sm" type="button" id="btn_copy_pin">Copy PIN</button>
                <span class="small text-muted" id="pin_hint">Use generated PIN or enter manually.</span>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-warning" type="submit">Set Temporary PIN</button>
                <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('setting/admin/user-management') ?>','maindiv','User Management');">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    var form = document.getElementById('frm_reset_pwd');
    if (!form || !window.jQuery) {
        return;
    }

    function makeSixDigitPin() {
        return String(Math.floor(100000 + Math.random() * 900000));
    }

    function applyGeneratedPin(pin) {
        $('#password,#password_confirm').val(pin);
        $('#pin_hint').text('PIN generated. Share this temporary PIN with user.');
    }

    $('#btn_generate_pin').off('click.userReset').on('click.userReset', function() {
        applyGeneratedPin(makeSixDigitPin());
    });

    $('#btn_copy_pin').off('click.userReset').on('click.userReset', function() {
        var pin = ($('#password').val() || '').toString();
        if (!/^\d{6}$/.test(pin)) {
            $('#pin_hint').text('Enter/generate valid 6-digit PIN first.');
            return;
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(pin).then(function() {
                $('#pin_hint').text('PIN copied.');
            }).catch(function() {
                $('#pin_hint').text('Copy failed. Please copy manually.');
            });
            return;
        }

        $('#pin_hint').text('Clipboard not supported. Please copy manually.');
    });

    applyGeneratedPin(makeSixDigitPin());

    $(form).off('submit.userReset').on('submit.userReset', function(event) {
        event.preventDefault();
        $.post($(form).attr('action'), $(form).serialize())
            .done(function(html) {
                $('#maindiv').html(html);
                showPageNotifications(document.getElementById('maindiv'));
            })
            .fail(function(xhr) {
                notify('error', 'Reset Password', (xhr.responseJSON && xhr.responseJSON.error_text) || 'Request failed. Please try again.');
            });
    });
})();
</script>
