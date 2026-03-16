<div class="row">
    <p>Created Report History</p>

    <div class="col-12 mb-3">
        <div class="alert alert-info py-2 mb-2">
            <strong>Image-assisted AI (Doctor verify first):</strong> Use "AI Extract Values" on report image/scan to capture LFT/KFT values into DB for doctor support only.
        </div>
        <button type="button" class="btn btn-sm btn-outline-dark" onclick="loadAiExtractedValues()">View Latest Extracted Values</button>
    </div>

    <?php if (!empty($lab_file_list)): ?>
        <?php foreach ($lab_file_list as $row): ?>
            <?php
                $fileId = (int) ($row->id ?? 0);
                $fullPath = (string) ($row->full_path ?? '');
                $filePath = str_replace('hms_uploads', 'uploads', $fullPath);
                $fileDesc = (string) ($row->file_desc ?? 'File');
                $fileName = (string) ($row->file_name ?? 'View');
                $insertTime = (string) ($row->insert_time ?? '');
            ?>
            <p class="text-muted">
                <strong><?= esc($fileDesc) ?></strong>
                [<a href="<?= esc($filePath) ?>" target="_blank"><?= esc($fileName) ?></a>]
                <i>/ Created :<?= esc($insertTime) ?></i>
                <button type="button" class="btn btn-sm btn-primary ms-2" onclick="extractLabValuesFromFile(<?= $fileId ?>)">AI Extract Values</button>
            </p>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-muted">No report files found.</p>
    <?php endif; ?>

    <div class="col-12 mb-2" id="lab_ai_status_wrap"></div>
    <div class="col-12" id="lab_ai_values_wrap"></div>
</div>

<script>
var aiLastFailedRequest = null;

function setAiStatus(message, level, showRetry) {
    var wrap = document.getElementById('lab_ai_status_wrap');
    if (!wrap) {
        return;
    }

    if (!message) {
        wrap.innerHTML = '';
        return;
    }

    var css = 'alert-info';
    if (level === 'success') {
        css = 'alert-success';
    } else if (level === 'error') {
        css = 'alert-danger';
    } else if (level === 'warn') {
        css = 'alert-warning';
    }

    var retryHtml = '';
    if (showRetry && aiLastFailedRequest) {
        retryHtml = '<button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="retryLastAiExtract()">Retry AI Extract</button>';
    }

    wrap.innerHTML = '<div class="alert ' + css + ' py-2 mb-2">' + message + retryHtml + '</div>';
}

function postWithTimeout(url, payload, timeoutMs) {
    return $.ajax({
        url: url,
        method: 'POST',
        data: payload,
        dataType: 'json',
        timeout: timeoutMs,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
}

function loadAiExtractedValues() {
    var invoiceId = <?= (int) ($invoice_id ?? 0) ?>;
    var labType = <?= (int) ($lab_type ?? 0) ?>;
    if (!invoiceId || !labType) {
        alert('Invoice context missing');
        return;
    }

    fetch('<?= base_url('diagnosis/ai-extracted-values') ?>/' + invoiceId + '/' + labType, {
        method: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(response) { return response.text(); })
    .then(function(html) {
        document.getElementById('lab_ai_values_wrap').innerHTML = html;
        bindDoctorVerifyAction();
    })
    .catch(function() {
        alert('Unable to load extracted values');
    });
}

function extractLabValuesFromFile(fileId) {
    var invoiceId = <?= (int) ($invoice_id ?? 0) ?>;
    var labType = <?= (int) ($lab_type ?? 0) ?>;
    var panelName = prompt('Panel name (optional):', 'LFT/KFT');

    if (!fileId || !invoiceId || !labType) {
        alert('Missing required context');
        return;
    }

    var payload = {
        file_upload_id: String(fileId),
        invoice_id: String(invoiceId),
        lab_type: String(labType),
        panel_name: panelName || ''
    };

    setAiStatus('Running AI extraction...', 'info', false);

    postWithTimeout('<?= base_url('diagnosis/ai-extract-report-values') ?>', payload, 90000)
    .done(function(result) {
        if ((result.update || 0) !== 1) {
            var err = result.error_text || 'AI extraction failed';
            aiLastFailedRequest = {
                fileId: fileId,
                panelName: panelName || '',
            };
            setAiStatus(err, 'error', true);
            return;
        }

        aiLastFailedRequest = null;
        setAiStatus(result.error_text || 'Extraction complete', 'success', false);
        loadAiExtractedValues();
    })
    .fail(function(xhr, textStatus) {
        var isTimeout = textStatus === 'timeout';
        aiLastFailedRequest = {
            fileId: fileId,
            panelName: panelName || '',
        };
        setAiStatus(isTimeout ? 'AI extraction timed out. You can retry now.' : 'AI extraction request failed.', 'error', true);
    });
}

function retryLastAiExtract() {
    if (!aiLastFailedRequest || !aiLastFailedRequest.fileId) {
        setAiStatus('No failed request available to retry.', 'warn', false);
        return;
    }

    extractLabValuesFromFile(aiLastFailedRequest.fileId);
}

function bindDoctorVerifyAction() {
    var wrap = document.getElementById('lab_ai_values_wrap');
    if (!wrap) {
        return;
    }

    if (wrap.querySelector('[data-ai-verify-btn]')) {
        return;
    }

    var header = wrap.querySelector('.card-header');
    var batchTextNode = wrap.querySelector('.small.text-muted');
    if (!header || !batchTextNode) {
        return;
    }

    var text = batchTextNode.textContent || '';
    var match = text.match(/Batch\s*#(\d+)/i);
    if (!match) {
        return;
    }

    var batchId = parseInt(match[1], 10);
    if (!batchId) {
        return;
    }

    var verifyBtn = document.createElement('button');
    verifyBtn.type = 'button';
    verifyBtn.className = 'btn btn-sm btn-success ms-2';
    verifyBtn.setAttribute('data-ai-verify-btn', '1');
    verifyBtn.textContent = 'Mark Doctor Verified';
    verifyBtn.onclick = function() {
        fetch('<?= base_url('diagnosis/ai-verify-extracted-values') ?>/' + batchId, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(response) { return response.json(); })
        .then(function(result) {
            if ((result.update || 0) !== 1) {
                alert(result.error_text || 'Verification failed');
                return;
            }
            alert(result.error_text || 'Doctor verification saved');
            loadAiExtractedValues();
        })
        .catch(function() {
            alert('Verification request failed');
        });
    };

    header.appendChild(verifyBtn);
}
</script>
