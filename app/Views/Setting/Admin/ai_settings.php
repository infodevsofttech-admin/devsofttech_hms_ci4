<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">AI Settings</h3>
    </div>
    <div class="card-body">
        <?= csrf_field() ?>

        <h6 class="mb-2">Gemini (optional fallback)</h6>

        <div class="mb-3">
            <label class="form-label">Gemini API Key</label>
            <input type="password" class="form-control" id="gemini_api_key" placeholder="Paste Gemini API key">
            <div class="form-text">
                <?php if (!empty($gemini_key_exists)) : ?>
                    Current key: <?= esc($gemini_key_masked ?? '') ?>
                <?php else : ?>
                    No API key configured yet.
                <?php endif; ?>
            </div>
        </div>

        <div class="alert alert-info">
            <h6 class="mb-2">How to get Gemini API key</h6>
            <ol class="mb-2 ps-3">
                <li>
                    Open <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener">Google AI Studio API Keys</a>
                    <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="btn_copy_gemini_link" data-url="https://aistudio.google.com/app/apikey">Copy Link</button>.
                </li>
                <li>Sign in and click <strong>Create API key</strong>.</li>
                <li>Copy the key and paste it in this field.</li>
                <li>Click <strong>Save Key</strong>, then <strong>Test Connection</strong>.</li>
            </ol>
            <div class="small mb-0">If test fails, check internet access from server, remove extra spaces/newline in key, or regenerate key.</div>
        </div>

        <hr>
        <h6 class="mb-2">Azure OpenAI (Primary)</h6>
        <div class="mb-3">
            <label class="form-label">Azure OpenAI Endpoint</label>
            <input type="text" class="form-control" id="azure_openai_endpoint" placeholder="https://your-resource.openai.azure.com" value="<?= esc($azure_openai_endpoint ?? '') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Azure OpenAI API Key</label>
            <input type="password" class="form-control" id="azure_openai_api_key" placeholder="Paste Azure OpenAI key">
            <div class="form-text">
                <?php if (!empty($azure_openai_key_exists)) : ?>
                    Current key: <?= esc($azure_openai_key_masked ?? '') ?>
                <?php else : ?>
                    No Azure OpenAI key configured yet.
                <?php endif; ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Deployment Name</label>
                <input type="text" class="form-control" id="azure_openai_deployment" placeholder="hms-opd-draft" value="<?= esc($azure_openai_deployment ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">API Version</label>
                <input type="text" class="form-control" id="azure_openai_api_version" placeholder="2024-10-21" value="<?= esc($azure_openai_api_version ?? '2024-10-21') ?>">
            </div>
        </div>

        <hr>
        <h6 class="mb-2">OCR (Azure Document Intelligence)</h6>
        <div class="mb-3">
            <label class="form-label">Document Intelligence Endpoint</label>
            <input type="text" class="form-control" id="azure_docintel_endpoint" placeholder="https://your-docintel.cognitiveservices.azure.com" value="<?= esc($azure_docintel_endpoint ?? '') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Document Intelligence Key</label>
            <input type="password" class="form-control" id="azure_docintel_key" placeholder="Paste Document Intelligence key">
            <div class="form-text">
                <?php if (!empty($azure_docintel_key_exists)) : ?>
                    Current key: <?= esc($azure_docintel_key_masked ?? '') ?>
                <?php else : ?>
                    No Document Intelligence key configured yet.
                <?php endif; ?>
            </div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-primary" id="btn_save_ai_key">Save Settings</button>
            <button type="button" class="btn btn-outline-success" id="btn_test_ai_key">Test Gemini</button>
            <button type="button" class="btn btn-outline-primary" id="btn_test_azure_key">Test Azure OpenAI</button>
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
        var key = ($('#gemini_api_key').val() || '').trim();
        var azureEndpoint = ($('#azure_openai_endpoint').val() || '').trim();
        var azureKey = ($('#azure_openai_api_key').val() || '').trim();
        var azureDeployment = ($('#azure_openai_deployment').val() || '').trim();
        var azureApiVersion = ($('#azure_openai_api_version').val() || '').trim();
        var docintelEndpoint = ($('#azure_docintel_endpoint').val() || '').trim();
        var docintelKey = ($('#azure_docintel_key').val() || '').trim();

        postJson('<?= base_url('setting/admin/ai-settings/save') ?>', {
            gemini_api_key: key,
            azure_openai_endpoint: azureEndpoint,
            azure_openai_api_key: azureKey,
            azure_openai_deployment: azureDeployment,
            azure_openai_api_version: azureApiVersion,
            azure_docintel_endpoint: docintelEndpoint,
            azure_docintel_key: docintelKey
        }, function(data) {
            if ((data.update || 0) !== 1) {
                showMessage('error', data.error_text || 'Unable to save API key');
                return;
            }

            showMessage('success', data.error_text || 'Settings saved');
            $('#gemini_api_key').val('');
            $('#azure_openai_api_key').val('');
            $('#azure_docintel_key').val('');
        });
    });

    $('#btn_test_ai_key').on('click', function() {
        var key = ($('#gemini_api_key').val() || '').trim();
        postJson('<?= base_url('setting/admin/ai-settings/test') ?>', {
            provider: 'gemini',
            gemini_api_key: key
        }, function(data) {
            if ((data.update || 0) !== 1) {
                showMessage('error', data.error_text || 'Test failed');
                return;
            }
            showMessage('success', data.error_text || 'Connection successful');
        });
    });

    $('#btn_test_azure_key').on('click', function() {
        var azureEndpoint = ($('#azure_openai_endpoint').val() || '').trim();
        var azureKey = ($('#azure_openai_api_key').val() || '').trim();
        var azureDeployment = ($('#azure_openai_deployment').val() || '').trim();
        var azureApiVersion = ($('#azure_openai_api_version').val() || '').trim();

        postJson('<?= base_url('setting/admin/ai-settings/test') ?>', {
            provider: 'azure',
            azure_openai_endpoint: azureEndpoint,
            azure_openai_api_key: azureKey,
            azure_openai_deployment: azureDeployment,
            azure_openai_api_version: azureApiVersion
        }, function(data) {
            if ((data.update || 0) !== 1) {
                showMessage('error', data.error_text || 'Azure test failed');
                return;
            }
            showMessage('success', data.error_text || 'Azure connection successful');
        });
    });

    $('#btn_copy_gemini_link').on('click', function() {
        var url = $(this).data('url') || '';
        if (!url) {
            showMessage('error', 'Unable to copy link');
            return;
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(function() {
                showMessage('success', 'Gemini link copied');
            }).catch(function() {
                showMessage('error', 'Copy failed. Please copy manually');
            });
            return;
        }

        var temp = document.createElement('input');
        temp.value = url;
        document.body.appendChild(temp);
        temp.select();

        try {
            document.execCommand('copy');
            showMessage('success', 'Gemini link copied');
        } catch (e) {
            showMessage('error', 'Copy failed. Please copy manually');
        }

        document.body.removeChild(temp);
    });
})();
</script>
