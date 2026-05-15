<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">
            <i class="bi bi-hdd-network me-2 text-primary"></i>ABDM Gateway (e-Atria Bridge)
        </h3>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary">API v3</span>
            <a href="https://abdm-bridge.e-atria.in/api-docs" target="_blank" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-box-arrow-up-right"></i> API Docs
            </a>
        </div>
    </div>
    <div class="card-body">
        <?= csrf_field() ?>

        <div class="alert alert-info py-2 mb-3" style="font-size:13px">
            <strong>Gateway:</strong> <a href="https://abdm-bridge.e-atria.in" target="_blank">abdm-bridge.e-atria.in</a>
            &nbsp;|&nbsp;
            <strong>Get your API key:</strong> Hospital Portal → Profile page on the gateway.
            &nbsp;|&nbsp;
            <strong>Auth:</strong> <code>Authorization: Bearer &lt;api-key&gt;</code>
        </div>

        <!-- HMS Name + HFR ID -->
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">HMS Name <span class="text-muted fw-normal">(as registered on gateway)</span></label>
                <input type="text" class="form-control" id="abdm_hms_name"
                       placeholder="e.g. City Hospital HMS"
                       value="<?= esc($hms_name ?? '') ?>">
                <div class="form-text">Display name used when submitting records to ABDM.</div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">HFR ID <span class="badge bg-secondary">Health Facility Registry</span></label>
                <input type="text" class="form-control" id="abdm_hfr_id"
                       placeholder="e.g. TH-2026-001"
                       value="<?= esc($hfr_id ?? '') ?>">
                <div class="form-text">Your hospital's HFR ID from the National Health Facility Registry.</div>
            </div>
        </div>

        <!-- Gateway URL -->
        <div class="mb-3">
            <label class="form-label fw-semibold">Gateway Base URL</label>
            <input type="url" class="form-control" id="abdm_gateway_url"
                   placeholder="https://abdm-bridge.e-atria.in/api"
                   value="<?= esc($gateway_url ?? 'https://abdm-bridge.e-atria.in/api') ?>">
            <div class="form-text">
                Base URL of the ABDM bridge gateway (no trailing slash).
                Default: <code>https://abdm-bridge.e-atria.in/api</code>
            </div>
        </div>

        <!-- API Key -->
        <div class="mb-3">
            <label class="form-label fw-semibold">API Key (Bearer Token)</label>
            <div class="input-group">
                <input type="password" class="form-control" id="abdm_api_token"
                       placeholder="Enter to set / update API key"
                       autocomplete="new-password">
                <button class="btn btn-outline-secondary" type="button" id="btn_toggle_abdm_token" title="Show/Hide">
                    <i class="bi bi-eye" id="abdm_token_eye"></i>
                </button>
            </div>
            <div class="form-text" id="abdm_token_hint">
                <?php if (!empty($token_exists)) : ?>
                    <span class="text-success">&#10003; API key saved
                        (<?= esc($token_masked ?? '') ?>).
                        Leave blank to keep existing.
                    </span>
                <?php else : ?>
                    <span class="text-warning">No API key configured. Obtain it from the gateway admin panel.</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Status row -->
        <div class="mb-3 p-3 rounded border bg-light" id="abdm_status_row" style="font-size:13px;">
            <div class="d-flex flex-wrap gap-3">
                <span><strong>Connector:</strong> <code><?= esc($connector ?? 'eatria_bridge') ?></code></span>
                <span><strong>Sync Provider:</strong> <code><?= esc($abdm_sync_provider ?? 'eatria') ?></code></span>
                <span><strong>Gateway:</strong>
                    <a href="<?= esc($gateway_url ?? 'https://abdm-bridge.e-atria.in/api') ?>" target="_blank">
                        <?= esc($gateway_url ?? 'https://abdm-bridge.e-atria.in/api') ?>
                    </a>
                </span>
            </div>
        </div>

        <!-- Action buttons -->
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-primary" id="btn_save_abdm_gateway">
                <i class="bi bi-save me-1"></i>Save Settings
            </button>
            <button type="button" class="btn btn-outline-success" id="btn_test_abdm_gateway">
                <i class="bi bi-wifi me-1"></i>Test Connection
            </button>
        </div>

        <div id="abdm_gateway_msg" class="mt-3"></div>
    </div>
</div>

<script>
(function () {
    'use strict';

    function getCsrfPair() {
        var input = document.querySelector('input[name="<?= csrf_token() ?>"]');
        if (!input) {
            return { name: '<?= csrf_token() ?>', value: '<?= csrf_hash() ?>' };
        }
        return { name: input.getAttribute('name'), value: input.value };
    }

    function updateCsrf(data) {
        if (!data || !data.csrfName || !data.csrfHash) return;
        var input = document.querySelector('input[name="' + data.csrfName + '"]');
        if (input) input.value = data.csrfHash;
    }

    function showMsg(type, html) {
        var cls = type === 'success' ? 'alert alert-success' : (type === 'warning' ? 'alert alert-warning' : 'alert alert-danger');
        $('#abdm_gateway_msg').html('<div class="' + cls + ' py-2">' + html + '</div>');
    }

    function postAjax(url, data, cb) {
        var csrf = getCsrfPair();
        data[csrf.name] = csrf.value;
        $.post(url, data, function (res) {
            updateCsrf(res);
            cb(res || {});
        }, 'json').fail(function (xhr) {
            cb((xhr && xhr.responseJSON) ? xhr.responseJSON : {});
        });
    }

    function getPayload() {
        return {
            gateway_url: ($('#abdm_gateway_url').val() || '').trim(),
            api_token:   ($('#abdm_api_token').val() || '').trim(),
            hfr_id:      ($('#abdm_hfr_id').val() || '').trim(),
            hms_name:    ($('#abdm_hms_name').val() || '').trim()
        };
    }

    // Toggle show/hide API key
    $('#btn_toggle_abdm_token').on('click', function () {
        var inp = document.getElementById('abdm_api_token');
        var eye = document.getElementById('abdm_token_eye');
        if (inp.type === 'password') {
            inp.type = 'text';
            eye.className = 'bi bi-eye-slash';
        } else {
            inp.type = 'password';
            eye.className = 'bi bi-eye';
        }
    });

    // Update hint when user types new token
    $('#abdm_api_token').on('input', function () {
        var val = $(this).val().trim();
        $('#abdm_token_hint').html(val.length > 0
            ? '<span class="text-warning">&#9998; New API key will be saved on Save Settings.</span>'
            : '<span class="text-muted">Leave blank to keep existing key.</span>'
        );
    });

    // Save settings
    $('#btn_save_abdm_gateway').on('click', function () {
        var $btn = $(this).prop('disabled', true).text('Saving…');
        $('#abdm_gateway_msg').html('');

        postAjax('<?= base_url('setting/admin/abdm-gateway/save') ?>', getPayload(), function (res) {
            $btn.prop('disabled', false).html('<i class="bi bi-save me-1"></i>Save Settings');

            if (res.update == 1) {
                showMsg('success', '<i class="bi bi-check-circle me-1"></i>' + (res.error_text || 'Saved.'));
                if (res.token_exists) {
                    var masked = $('<div>').text(res.token_masked || '').html();
                    $('#abdm_token_hint').html('<span class="text-success">&#10003; API key saved (' + masked + '). Leave blank to keep existing.</span>');
                    $('#abdm_api_token').val('');
                }
            } else {
                showMsg('danger', '<i class="bi bi-x-circle me-1"></i>' + (res.error_text || 'Save failed.'));
            }
        });
    });

    // Test connection
    $('#btn_test_abdm_gateway').on('click', function () {
        var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Testing…');
        $('#abdm_gateway_msg').html('');

        postAjax('<?= base_url('setting/admin/abdm-gateway/test') ?>', getPayload(), function (res) {
            $btn.prop('disabled', false).html('<i class="bi bi-wifi me-1"></i>Test Connection');

            if (res.update == 1) {
                var authLine = res.auth_msg ? ('<br><small>' + $('<div>').text(res.auth_msg).html() + '</small>') : '';
                var modeBadge = res.mode === 'test'
                    ? '<span class="badge bg-warning text-dark ms-1">TEST MODE</span>'
                    : '<span class="badge bg-success ms-1">LIVE</span>';
                showMsg('success',
                    '<i class="bi bi-check-circle me-1"></i>' +
                    $('<div>').text(res.error_text || 'Gateway reachable').html() +
                    modeBadge + authLine
                );
            } else {
                showMsg('danger', '<i class="bi bi-x-circle me-1"></i>' + (res.error_text || 'Test failed.'));
            }
        });
    });
}());
</script>
