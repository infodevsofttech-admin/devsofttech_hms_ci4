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

    <div class="col-12" id="lab_ai_values_wrap"></div>
</div>

<script>
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

    var body = new FormData();
    body.append('file_upload_id', String(fileId));
    body.append('invoice_id', String(invoiceId));
    body.append('lab_type', String(labType));
    body.append('panel_name', panelName || '');

    fetch('<?= base_url('diagnosis/ai-extract-report-values') ?>', {
        method: 'POST',
        body: body,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(response) { return response.json(); })
    .then(function(result) {
        if ((result.update || 0) !== 1) {
            alert(result.error_text || 'AI extraction failed');
            return;
        }
        alert(result.error_text || 'Extraction complete');
        loadAiExtractedValues();
    })
    .catch(function() {
        alert('AI extraction request failed');
    });
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
        var form = new FormData();
        fetch('<?= base_url('diagnosis/ai-verify-extracted-values') ?>/' + batchId, {
            method: 'POST',
            body: form,
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
