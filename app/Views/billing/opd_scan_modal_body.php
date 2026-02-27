<div class="row mb-2">
    <div class="col-md-12">
        <p><strong>Name :</strong> <?= esc($person_info[0]->p_fname ?? '') ?>
            <strong>/ Age :</strong> <?= esc($person_info[0]->age ?? '') ?>
            <strong>/ Gender :</strong> <?= esc($person_info[0]->xgender ?? '') ?>
            <strong>/ P Code :</strong> <?= esc($person_info[0]->p_code ?? '') ?>
            <strong>/ OPD :</strong> <?= esc($opd_master[0]->opd_code ?? '') ?></p>
    </div>
</div>
<div class="row">
    <div class="col-md-3">
        <video id="opd_scan_video" autoplay playsinline style="width:100%;max-width:220px;border:1px solid #ced4da;border-radius:4px;"></video>
        <canvas id="opd_scan_canvas" style="display:none;"></canvas>
        <input type="hidden" id="opd_scan_opdid" value="<?= esc((int) ($opdid ?? 0)) ?>">
        <div class="mt-2 d-flex gap-2">
            <button type="button" id="opd_scan_capture_btn" class="btn btn-warning btn-sm">Capture</button>
            <button type="button" id="opd_scan_stop_btn" class="btn btn-outline-secondary btn-sm">Stop</button>
        </div>
        <div class="mt-2">
            <label class="form-label mb-1 small">Upload File (PDF/Image)</label>
            <input type="file" id="opd_scan_upload_file" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.webp,image/*,application/pdf">
            <button type="button" id="opd_scan_upload_btn" class="btn btn-outline-primary btn-sm mt-2">Upload File</button>
        </div>
    </div>
    <div class="col-md-5">
        <div id="opd_scan_results" class="text-muted small">Captured image will appear here.</div>
        <div id="opd_scan_list" class="mt-2"></div>
    </div>
    <div class="col-md-4">
        <div class="card border-danger">
            <div class="card-header py-2"><strong>Scan to Text</strong></div>
            <div class="card-body" id="opd_scan_text_box">
                <div class="text-muted small">Loading...</div>
            </div>
        </div>
        <div class="card border-primary mt-2">
            <div class="card-header py-2"><strong>AI Diagnosis Support</strong></div>
            <div class="card-body">
                <div class="small text-muted" id="opd_scan_ai_result">AI analysis runs automatically in background after each scan/upload.</div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    // Keep references to camera stream and frequently used DOM elements.
    var streamRef = null;
    var $video = $('#opd_scan_video');
    var $canvas = $('#opd_scan_canvas');
    var $captureBtn = $('#opd_scan_capture_btn');
    var $stopBtn = $('#opd_scan_stop_btn');
    var $uploadBtn = $('#opd_scan_upload_btn');
    var $results = $('#opd_scan_results');
    var $textBox = $('#opd_scan_text_box');
    var $aiResult = $('#opd_scan_ai_result');
    var opdid = parseInt($('#opd_scan_opdid').val() || '0', 10);
    var listPollingTimer = null;

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

    function startCamera() {
        // Start webcam stream for quick bedside capture.
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            $results.html('<div class="text-danger">Camera not supported in this browser.</div>');
            return;
        }

        navigator.mediaDevices.getUserMedia({ video: true, audio: false })
            .then(function(stream) {
                streamRef = stream;
                var video = $video.get(0);
                video.srcObject = stream;
                video.play();
            })
            .catch(function() {
                $results.html('<div class="text-danger">Unable to access webcam. Please allow camera permission.</div>');
            });
    }

    function stopCamera() {
        // Stop all active tracks to release camera hardware.
        if (streamRef) {
            streamRef.getTracks().forEach(function(track) { track.stop(); });
            streamRef = null;
        }
    }

    function loadLastList() {
        // Refresh latest uploaded/captured files for this OPD.
        if (opdid <= 0) {
            return;
        }
        $.get('<?= base_url('Opd/opd_file_last_list') ?>/' + opdid, function(html) {
            $('#opd_scan_list').html(html || '');
        });
    }

    function renderExtractedText(text) {
        if (!text) {
            $textBox.html('<div class="text-muted small">AI extraction is running in background. You can continue scanning other documents.</div>');
            return;
        }
        var safe = $('<div>').text(text).html();
        $textBox.html('<textarea class="form-control form-control-sm" rows="8">' + safe + '</textarea>'
            + '<div class="small text-muted mt-1">Use this text in OPD Finding / Investigation.</div>');
    }

    function queueAiProcessing(fileId) {
        // Trigger non-blocking AI processing for the uploaded file.
        if (!fileId) {
            return;
        }

        var csrf = getCsrfPair();
        var payload = {
            file_id: fileId,
            apply_to_opd: 0
        };
        payload[csrf.name] = csrf.value;

        $.post('<?= base_url('Opd/scan_ai_process_file') ?>', payload, function(data) {
            updateCsrf(data || {});
            if (data && parseInt(data.update || '0', 10) === 1) {
                $aiResult.removeClass('text-danger').addClass('text-muted').text('AI completed for latest report.');
            } else {
                var msg = (data && data.error_text) ? data.error_text : 'Background AI processing failed for one file.';
                $aiResult.removeClass('text-muted').addClass('text-danger').text(msg);
            }
            loadLastList();
        }, 'json').fail(function() {
            $aiResult.removeClass('text-muted').addClass('text-danger').text('Background AI processing failed for one file.');
            loadLastList();
        });
    }

    function startListPolling() {
        if (listPollingTimer) {
            clearInterval(listPollingTimer);
        }
        listPollingTimer = setInterval(loadLastList, 5000);
    }

    function submitScanFormData(formData) {
        // Common upload handler used by both camera capture and file upload.
        $.ajax({
            url: '<?= base_url('Opd/save_image') ?>/' + opdid,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(data) {
                updateCsrf(data || {});
                $captureBtn.prop('disabled', false);
                $uploadBtn.prop('disabled', false).text('Upload File');

                if (!data || data.update != 1) {
                    $results.html('<div class="text-danger">' + ((data && data.error_text) ? data.error_text : 'Upload failed') + '</div>');
                    $textBox.html('<div class="text-danger small">Unable to process scan text.</div>');
                    return;
                }

                var src = data.file_path || '';

                if (src) {
                    if (src.toLowerCase().endsWith('.pdf')) {
                        $results.html('<div class="small text-success mb-1">Uploaded PDF</div><a target="_blank" href="' + $('<div>').text(src).html() + '">Open PDF</a>');
                    } else {
                        $results.html('<div class="small text-success mb-1">Captured / Uploaded</div><img src="' + $('<div>').text(src).html() + '?t=' + Date.now() + '" style="max-width:100%;border:1px solid #ced4da;border-radius:4px;">');
                    }
                } else {
                    $results.html('<div class="text-success">Captured/Uploaded successfully.</div>');
                }

                renderExtractedText('');
                $aiResult.removeClass('text-danger text-success').addClass('text-muted').text('Queued for background AI analysis...');
                queueAiProcessing(parseInt(data.file_id || '0', 10));
                loadLastList();
            },
            error: function() {
                $captureBtn.prop('disabled', false);
                $uploadBtn.prop('disabled', false).text('Upload File');
                $results.html('<div class="text-danger">Upload request failed.</div>');
                $textBox.html('<div class="text-danger small">Unable to process scan text.</div>');
            }
        });
    }

    function captureAndUpload() {
        // Capture current webcam frame and send it as multipart form data.
        if (opdid <= 0) {
            return;
        }

        var video = $video.get(0);
        if (!video || !video.videoWidth) {
            $results.html('<div class="text-danger">Camera frame not ready. Try again.</div>');
            return;
        }

        $captureBtn.prop('disabled', true);
        $textBox.html('<div class="text-muted small">Processing scan text...</div>');

        var canvas = $canvas.get(0);
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        var ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        canvas.toBlob(function(blob) {
            if (!blob) {
                $captureBtn.prop('disabled', false);
                $results.html('<div class="text-danger">Capture failed.</div>');
                return;
            }

            var formData = new window.FormData();
            var csrf = getCsrfPair();
            formData.append('webcam', blob, 'capture.jpg');
            formData.append(csrf.name, csrf.value);
            submitScanFormData(formData);
        }, 'image/jpeg', 0.9);
    }

    function uploadSelectedFile() {
        // Upload selected PDF/image file from local device.
        if (opdid <= 0) {
            return;
        }
        var fileInput = $('#opd_scan_upload_file').get(0);
        var file = fileInput && fileInput.files ? fileInput.files[0] : null;
        if (!file) {
            $results.html('<div class="text-danger">Choose a file first.</div>');
            return;
        }

        $uploadBtn.prop('disabled', true).text('Uploading...');
        $textBox.html('<div class="text-muted small">Uploading file...</div>');

        var formData = new window.FormData();
        var csrf = getCsrfPair();
        formData.append('userfile', file, file.name || 'upload_file');
        formData.append(csrf.name, csrf.value);
        submitScanFormData(formData);
    }

    $captureBtn.off('click.opdscan').on('click.opdscan', captureAndUpload);
    $stopBtn.off('click.opdscan').on('click.opdscan', stopCamera);
    $uploadBtn.off('click.opdscan').on('click.opdscan', uploadSelectedFile);

    window.removeOpdScanImage = function(fileId) {
        if (!fileId) {
            return;
        }
        $.post('<?= base_url('Opd/opd_file_hide') ?>/' + fileId, {}, function(html) {
            $('#opd_scan_list').html(html || '');
        });
    };

    startCamera();
    loadLastList();
    startListPolling();

    $('#tallModal').off('hidden.bs.modal.opdscan').on('hidden.bs.modal.opdscan', function() {
        stopCamera();
        if (listPollingTimer) {
            clearInterval(listPollingTimer);
            listPollingTimer = null;
        }
        $('#opd_scan_results').html('Captured image will appear here.');
    });
})();
</script>
