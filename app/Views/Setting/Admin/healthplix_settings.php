<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">HealthPlix Integration</h3>
        <span class="badge bg-primary fs-6">API Version: V1</span>
    </div>
    <div class="card-body">
        <?= csrf_field() ?>
        <div class="alert alert-info py-2 mb-3" style="font-size:13px">
            <strong>Base URL (QA/Dev):</strong> <code>https://consultation-edge-dev.healthplix.com</code> &nbsp;|&nbsp;
            <strong>Doc version:</strong> 1.7 (26-Apr-2025) &nbsp;|&nbsp;
            All paths below are pre-filled as per official API Specification Doc.
        </div>

        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" id="healthplix_enabled" <?= (($healthplix_enabled ?? '0') === '1') ? 'checked' : '' ?>>
            <label class="form-check-label" for="healthplix_enabled">Enable HealthPlix for this hospital</label>
            <div class="form-text">Keep disabled for hospitals that do not use HealthPlix.</div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">HealthPlix API Base URL</label>
                <input type="text" class="form-control" id="healthplix_base_url" placeholder="https://consultation-edge-dev.healthplix.com" value="<?= esc($healthplix_base_url ?? '') ?>">
                <div class="form-text">QA/Dev URL from HealthPlix doc. Production URL is provided by HealthPlix team.</div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Tenant Header Name <span class="badge bg-secondary">Fixed by HealthPlix</span></label>
                <input type="text" class="form-control" id="healthplix_tenant_header_name" placeholder="tenant_id" value="<?= esc($healthplix_tenant_header_name ?? 'tenant_id') ?>">
                <div class="form-text">Header name for tenant ID in API requests. Default: <code>tenant_id</code> (do NOT put your Tenant ID value here).</div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Fetch Callback Secret</label>
                <input type="password" class="form-control" id="healthplix_fetch_secret" placeholder="Enter to set/update callback secret">
                <div class="form-text" id="healthplix_fetch_secret_hint">
                    <?php if (!empty($healthplix_fetch_secret_exists)) : ?>
                        <span class="text-success">&#10003; Secret saved (<?= esc($healthplix_fetch_secret_masked ?? '') ?>). Leave blank to keep existing.</span>
                    <?php else : ?>
                        No callback secret configured yet.
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <hr class="my-2">
        <h6 class="mb-2">API Endpoints <span class="badge bg-secondary">V1</span></h6>
        <div class="form-text mb-2">Paths are relative to the Base URL above. Pre-filled from HealthPlix API Specification Doc v1.7.</div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Token Path</label>
                <input type="text" class="form-control" id="healthplix_token_path" placeholder="v1/generate/token" value="<?= esc($healthplix_token_path ?? 'v1/generate/token') ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Patient Registration Path</label>
                <input type="text" class="form-control" id="healthplix_patient_path" placeholder="v1/patient/register" value="<?= esc($healthplix_patient_path ?? 'v1/patient/register') ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Appointment Booking Path</label>
                <input type="text" class="form-control" id="healthplix_appointment_path" placeholder="v1/appointment/register" value="<?= esc($healthplix_appointment_path ?? 'v1/appointment/register') ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Tenant ID (Basic Auth Username)</label>
                <input type="text" class="form-control" id="healthplix_tenant_id" value="<?= esc($healthplix_tenant_id ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Tenant Key (Basic Auth Password)</label>
                <input type="password" class="form-control" id="healthplix_tenant_key" placeholder="Enter to set/update key">
                <div class="form-text" id="healthplix_tenant_key_hint">
                    <?php if (!empty($healthplix_tenant_key_exists)) : ?>
                        <span class="text-success">&#10003; Key saved (<?= esc($healthplix_tenant_key_masked ?? '') ?>). Leave blank to keep existing.</span>
                    <?php else : ?>
                        No tenant key configured yet.
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Default Doctor Identifier</label>
                <input type="text" class="form-control" id="healthplix_doctor_identifier" value="<?= esc($healthplix_doctor_identifier ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Doctor Email</label>
                <input type="email" class="form-control" id="healthplix_doctor_email" value="<?= esc($healthplix_doctor_email ?? '') ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Service Identifier</label>
                <input type="text" class="form-control" id="healthplix_service_identifier" value="<?= esc($healthplix_service_identifier ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Service Name</label>
                <input type="text" class="form-control" id="healthplix_service_name" value="<?= esc($healthplix_service_name ?? '') ?>">
            </div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-primary" id="btn_save_healthplix">Save Settings</button>
            <button type="button" class="btn btn-outline-success" id="btn_test_healthplix">Test Token</button>
        </div>

        <div id="healthplix_settings_msg" class="mt-3"></div>
    </div>
</div>

<script>
(function() {
    function getCsrfPair() {
        var input = document.querySelector('input[name="<?= csrf_token() ?>"]');
        if (!input) {
            return { name: '<?= csrf_token() ?>', value: '<?= csrf_hash() ?>' };
        }
        return { name: input.getAttribute('name'), value: input.value };
    }

    function updateCsrf(data) {
        if (!data || !data.csrfName || !data.csrfHash) {
            return;
        }
        var input = document.querySelector('input[name="' + data.csrfName + '"]');
        if (input) {
            input.value = data.csrfHash;
        }
    }

    function showMessage(type, text) {
        var cls = type === 'success' ? 'alert alert-success' : 'alert alert-danger';
        $('#healthplix_settings_msg').html('<div class="' + cls + '">' + $('<div>').text(text || '').html() + '</div>');
    }

    function updateFetchSecretHintFromServer(data) {
        if (data && data.healthplixFetchSecretConfigured) {
            var maskedSecret = data.healthplixFetchSecretMasked || '********';
            $('#healthplix_fetch_secret_hint').html('<span class="text-success">&#10003; Secret saved (' + $('<div>').text(maskedSecret).html() + '). Leave blank to keep existing.</span>');
            return;
        }

        $('#healthplix_fetch_secret_hint').html('<span class="text-muted">No callback secret configured yet.</span>');
    }

    function postJson(url, payload, cb) {
        var csrf = getCsrfPair();
        payload = payload || {};
        payload[csrf.name] = csrf.value;

        $.post(url, payload, function(data) {
            updateCsrf(data);
            cb(data || {});
        }, 'json').fail(function(xhr) {
            var response = (xhr && xhr.responseJSON) ? xhr.responseJSON : {};
            cb(response || {});
        });
    }

    function payload() {
        return {
            healthplix_enabled: $('#healthplix_enabled').is(':checked') ? '1' : '0',
            healthplix_base_url: ($('#healthplix_base_url').val() || '').trim(),
            healthplix_fetch_secret: ($('#healthplix_fetch_secret').val() || '').trim(),
            healthplix_tenant_header_name: ($('#healthplix_tenant_header_name').val() || '').trim(),
            healthplix_tenant_id: ($('#healthplix_tenant_id').val() || '').trim(),
            healthplix_tenant_key: ($('#healthplix_tenant_key').val() || '').trim(),
            healthplix_doctor_identifier: ($('#healthplix_doctor_identifier').val() || '').trim(),
            healthplix_doctor_email: ($('#healthplix_doctor_email').val() || '').trim(),
            healthplix_service_identifier: ($('#healthplix_service_identifier').val() || '').trim(),
            healthplix_service_name: ($('#healthplix_service_name').val() || '').trim(),
            healthplix_token_path: ($('#healthplix_token_path').val() || '').trim(),
            healthplix_patient_path: ($('#healthplix_patient_path').val() || '').trim(),
            healthplix_appointment_path: ($('#healthplix_appointment_path').val() || '').trim()
        };
    }

    // Update hint live as user types
    $('#healthplix_tenant_key').on('input', function() {
        var val = $(this).val().trim();
        if (val.length > 0) {
            $('#healthplix_tenant_key_hint').html('<span class="text-warning">&#9998; New key will be saved on Save Settings.</span>');
        } else {
            $('#healthplix_tenant_key_hint').html('<span class="text-muted">Leave blank to keep existing key.</span>');
        }
    });

    $('#healthplix_fetch_secret').on('input', function() {
        var val = $(this).val().trim();
        if (val.length > 0) {
            $('#healthplix_fetch_secret_hint').html('<span class="text-warning">&#9998; New secret will be saved on Save Settings.</span>');
        } else {
            $('#healthplix_fetch_secret_hint').html('<span class="text-muted">Leave blank to keep existing callback secret.</span>');
        }
    });

    $('#btn_save_healthplix').on('click', function() {
        postJson('<?= base_url('setting/admin/healthplix-settings/save') ?>', payload(), function(data) {
            if ((data.update || 0) !== 1) {
                showMessage('error', data.error_text || 'Unable to save settings');
                return;
            }

            showMessage('success', data.error_text || 'Settings saved');
            var savedKey = ($('#healthplix_tenant_key').val() || '').trim();
            if (savedKey.length > 0) {
                var masked = savedKey.substring(0, 4) + '****' + savedKey.slice(-4);
                $('#healthplix_tenant_key_hint').html('<span class="text-success">&#10003; Key saved (' + masked + '). Leave blank to keep existing.</span>');
            } else {
                $('#healthplix_tenant_key_hint').html('<span class="text-muted">Leave blank to keep existing key.</span>');
            }

            updateFetchSecretHintFromServer(data);

            $('#healthplix_tenant_key').val('');
            $('#healthplix_fetch_secret').val('');
        });
    });

    $('#btn_test_healthplix').on('click', function() {
        postJson('<?= base_url('setting/admin/healthplix-settings/test') ?>', payload(), function(data) {
            if ((data.update || 0) !== 1) {
                showMessage('error', data.error_text || 'HealthPlix token test failed');
                return;
            }
            showMessage('success', data.error_text || 'HealthPlix token test successful');
        });
    });
})();
</script>
