<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">AI Settings</h3>
    </div>
    <div class="card-body">
        <?= csrf_field() ?>

        <h6 class="mb-2">AI Server (Primary - Python FastAPI)</h6>
        <div class="mb-3">
            <label class="form-label">AI Server Base URL</label>
            <input type="text" class="form-control" id="diagnosis_ai_server_url" placeholder="http://127.0.0.1:8000" value="<?= esc($diagnosis_ai_server_url ?? '') ?>">
            <div class="form-text">Used for imaging diagnosis endpoint: /diagnosis</div>
        </div>
        <div class="mb-3">
            <label class="form-label">AI OCR Endpoint (optional, for pathology file OCR)</label>
            <input type="text" class="form-control" id="diagnosis_ai_ocr_endpoint" placeholder="http://127.0.0.1:8000/ocr" value="<?= esc($diagnosis_ai_ocr_endpoint ?? '') ?>">
            <div class="form-text">If not configured, pathology AI extraction OCR will be unavailable.</div>
        </div>

        <div class="mb-3">
            <label class="form-label">Imaging Report Prompt Template <span class="badge bg-secondary">Optional Override</span></label>
            <textarea class="form-control" id="diagnosis_ai_imaging_prompt" rows="6" placeholder="Leave empty to use auto-detected prompts per study type. Use {study_name} as placeholder."><?= esc($diagnosis_ai_imaging_prompt ?? '') ?></textarea>
            <div class="form-text">
                <strong>Auto-detection is active:</strong> the server automatically selects the correct prompt structure for each study type —
                Chest X-ray, Abdomen, Spine, PNS/Sinuses, Skull, Pelvis, Extremities/Joints, Contrast studies, Mammography, MRI, CT, Ultrasound.<br>
                Fill this field only to override with a custom global prompt for all studies. Use <code>{study_name}</code> as placeholder for current study name. Leave blank to keep auto-detection.
            </div>
        </div>

        <hr>
        <h6 class="mb-2">Diagnosis AI Parse Endpoint</h6>
        <div class="mb-3">
            <label class="form-label">Diagnosis Parse Endpoint</label>
            <input type="text" class="form-control" id="diagnosis_ai_parse_endpoint" placeholder="http://127.0.0.1:8000/parse-lab-values" value="<?= esc($diagnosis_ai_parse_endpoint ?? '') ?>">
            <div class="form-text">Used by pathology value extraction flow.</div>
        </div>

        <div class="mb-3">
            <label class="form-label">Diagnosis Parse Token (optional)</label>
            <input type="password" class="form-control" id="diagnosis_ai_parse_token" placeholder="Bearer token">
            <div class="form-text">
                <?php if (!empty($diagnosis_ai_token_exists)) : ?>
                    Current token: <?= esc($diagnosis_ai_token_masked ?? '') ?>
                <?php else : ?>
                    No token configured yet.
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Diagnosis AI Timeout (seconds)</label>
                <input type="number" min="5" max="180" class="form-control" id="diagnosis_ai_timeout_seconds" value="<?= esc($diagnosis_ai_timeout_seconds ?? '45') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Diagnosis AI Retry Attempts</label>
                <input type="number" min="1" max="5" class="form-control" id="diagnosis_ai_retry_attempts" value="<?= esc($diagnosis_ai_retry_attempts ?? '2') ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Gemini Daily Limit (guardrail)</label>
                <input type="number" min="1" max="10000" class="form-control" id="diagnosis_ai_daily_limit" value="<?= esc($diagnosis_ai_daily_limit ?? '20') ?>">
                <div class="form-text">Used for warning levels at 70% and 90%.</div>
            </div>
            <div class="col-md-6 mb-3 d-flex align-items-end">
                <button type="button" class="btn btn-outline-primary" id="btn_refresh_ai_usage">Refresh Usage</button>
            </div>
        </div>

        <div class="border rounded p-3 mb-3 bg-light" id="ai_usage_card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong>AI Usage Guardrail (Today)</strong>
                <span class="badge bg-secondary" id="ai_usage_level">Unknown</span>
            </div>
            <div class="row g-2">
                <div class="col-md-3"><div class="small text-muted">Gemini Calls</div><div id="ai_usage_gemini" class="fw-bold">-</div></div>
                <div class="col-md-3"><div class="small text-muted">Fallback Calls</div><div id="ai_usage_fallback" class="fw-bold">-</div></div>
                <div class="col-md-3"><div class="small text-muted">Total Calls</div><div id="ai_usage_total" class="fw-bold">-</div></div>
                <div class="col-md-3"><div class="small text-muted">Last Hour</div><div id="ai_usage_hour" class="fw-bold">-</div></div>
            </div>
            <div class="small mt-2 text-muted" id="ai_usage_hint">Press Refresh Usage to load latest usage.</div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-primary" id="btn_save_ai_key">Save Settings</button>
            <button type="button" class="btn btn-outline-dark" id="btn_test_ai_server">Test AI Server</button>
            <button type="button" class="btn btn-outline-secondary" id="btn_test_diagnosis_ai">Test Parse Endpoint</button>
        </div>

        <div id="ai_settings_msg" class="mt-3"></div>
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
        $('#ai_settings_msg').html('<div class="' + cls + '">' + $('<div>').text(text || '').html() + '</div>');
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

    $('#btn_save_ai_key').on('click', function() {
        var aiServerUrl = ($('#diagnosis_ai_server_url').val() || '').trim();
        var aiOcrEndpoint = ($('#diagnosis_ai_ocr_endpoint').val() || '').trim();
        var diagnosisEndpoint = ($('#diagnosis_ai_parse_endpoint').val() || '').trim();
        var diagnosisImagingPrompt = ($('#diagnosis_ai_imaging_prompt').val() || '').trim();
        var diagnosisToken = ($('#diagnosis_ai_parse_token').val() || '').trim();
        var diagnosisTimeout = ($('#diagnosis_ai_timeout_seconds').val() || '').trim();
        var diagnosisRetry = ($('#diagnosis_ai_retry_attempts').val() || '').trim();

        postJson('<?= base_url('setting/admin/ai-settings/save') ?>', {
            diagnosis_ai_server_url: aiServerUrl,
            diagnosis_ai_ocr_endpoint: aiOcrEndpoint,
            diagnosis_ai_parse_endpoint: diagnosisEndpoint,
            diagnosis_ai_imaging_prompt: diagnosisImagingPrompt,
            diagnosis_ai_parse_token: diagnosisToken,
            diagnosis_ai_timeout_seconds: diagnosisTimeout,
            diagnosis_ai_retry_attempts: diagnosisRetry,
            diagnosis_ai_daily_limit: ($('#diagnosis_ai_daily_limit').val() || '').trim()
        }, function(data) {
            if ((data.update || 0) !== 1) {
                showMessage('error', data.error_text || 'Unable to save settings');
                return;
            }

            showMessage('success', data.error_text || 'Settings saved');
            $('#diagnosis_ai_parse_token').val('');
            refreshUsage();
        });
    });

    $('#btn_test_ai_server').on('click', function() {
        var aiServerUrl = ($('#diagnosis_ai_server_url').val() || '').trim();
        var diagnosisToken = ($('#diagnosis_ai_parse_token').val() || '').trim();

        postJson('<?= base_url('setting/admin/ai-settings/test') ?>', {
            provider: 'ai-server',
            diagnosis_ai_server_url: aiServerUrl,
            diagnosis_ai_parse_token: diagnosisToken
        }, function(data) {
            if ((data.update || 0) !== 1) {
                showMessage('error', data.error_text || 'AI Server test failed');
                return;
            }
            showMessage('success', data.error_text || 'AI Server connection successful');
        });
    });

    $('#btn_test_diagnosis_ai').on('click', function() {
        var diagnosisEndpoint = ($('#diagnosis_ai_parse_endpoint').val() || '').trim();
        var diagnosisToken = ($('#diagnosis_ai_parse_token').val() || '').trim();

        postJson('<?= base_url('setting/admin/ai-settings/test') ?>', {
            provider: 'diagnosis-external',
            diagnosis_ai_parse_endpoint: diagnosisEndpoint,
            diagnosis_ai_parse_token: diagnosisToken
        }, function(data) {
            if ((data.update || 0) !== 1) {
                showMessage('error', data.error_text || 'Parse endpoint test failed');
                return;
            }
            showMessage('success', data.error_text || 'Parse endpoint connection successful');
        });
    });

    function refreshUsage() {
        postJson('<?= base_url('setting/admin/ai-settings/usage') ?>', {}, function(data) {
            var usage = data && data.usage ? data.usage : null;
            if (!usage) {
                $('#ai_usage_hint').text((data && data.error_text) ? data.error_text : 'Unable to load usage');
                return;
            }

            $('#ai_usage_gemini').text((usage.gemini_today || 0) + ' / ' + (usage.daily_limit || 0));
            $('#ai_usage_fallback').text(usage.fallback_today || 0);
            $('#ai_usage_total').text(usage.total_today || 0);
            $('#ai_usage_hour').text(usage.last_hour || 0);

            var level = (usage.level || 'ok').toString();
            var levelText = 'OK';
            var levelClass = 'bg-success';
            if (level === 'warn') {
                levelText = 'Warning';
                levelClass = 'bg-warning text-dark';
            } else if (level === 'danger') {
                levelText = 'High';
                levelClass = 'bg-danger';
            } else if (level === 'critical') {
                levelText = 'Exceeded';
                levelClass = 'bg-dark';
            }

            $('#ai_usage_level').removeClass().addClass('badge ' + levelClass).text(levelText);
            $('#ai_usage_hint').text('Gemini utilization: ' + (usage.ratio || 0) + '%. Thresholds: 70% warning, 90% high.');
        });
    }

    $('#btn_refresh_ai_usage').on('click', function() {
        refreshUsage();
    });

    refreshUsage();
})();
</script>
