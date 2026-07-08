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
        <video id="opd_scan_video" autoplay muted playsinline style="width:100%;max-width:220px;border:1px solid #ced4da;border-radius:4px;"></video>
        <div class="mt-2">
            <label for="opd_scan_camera_select" class="form-label mb-1 small">Camera</label>
            <div class="d-flex gap-2 align-items-start">
                <select id="opd_scan_camera_select" class="form-select form-select-sm">
                    <option value="">Default camera</option>
                </select>
                <button type="button" id="opd_scan_camera_refresh" class="btn btn-outline-secondary btn-sm">Refresh</button>
            </div>
        </div>
        <div id="opd_scan_resolution" class="small text-muted mt-1">Camera: detecting resolution...</div>
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
                <button type="button" id="opd_scan_ai_process_btn" class="btn btn-primary btn-sm mb-2" disabled>Process AI</button>
                <div class="small text-muted" id="opd_scan_ai_result">Capture or upload a file, then click Process AI when needed.</div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="opd_scan_edit_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Capture</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex gap-2 flex-wrap mb-2">
                    <button type="button" id="opd_scan_rotate_left" class="btn btn-outline-secondary btn-sm">Rotate Left</button>
                    <button type="button" id="opd_scan_rotate_right" class="btn btn-outline-secondary btn-sm">Rotate Right</button>
                    <button type="button" id="opd_scan_crop_btn" class="btn btn-outline-primary btn-sm">Crop Selection</button>
                    <button type="button" id="opd_scan_reset_edit" class="btn btn-outline-warning btn-sm">Reset</button>
                </div>
                <div class="small text-muted mb-2">Drag on the image to select crop area. Rotate if needed, then save.</div>
                <div class="border rounded p-2 text-center bg-light">
                    <canvas id="opd_scan_edit_canvas" style="max-width:100%;cursor:crosshair;"></canvas>
                </div>
                <div id="opd_scan_edit_status" class="small text-muted mt-2">No crop selected.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="opd_scan_save_edit" class="btn btn-primary btn-sm">Save and Upload</button>
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
    var $processAiBtn = $('#opd_scan_ai_process_btn');
    var $results = $('#opd_scan_results');
    var $textBox = $('#opd_scan_text_box');
    var $aiResult = $('#opd_scan_ai_result');
    var $cameraSelect = $('#opd_scan_camera_select');
    var $cameraRefresh = $('#opd_scan_camera_refresh');
    var $resolution = $('#opd_scan_resolution');
    var allowImagePreuploadEdit = <?= !empty($allow_image_preupload_edit) ? 'true' : 'false' ?>;
    var editModalEl = document.getElementById('opd_scan_edit_modal');
    var editModal = editModalEl && window.bootstrap ? new bootstrap.Modal(editModalEl) : null;
    var editCanvas = document.getElementById('opd_scan_edit_canvas');
    var editCtx = editCanvas ? editCanvas.getContext('2d') : null;
    var $editStatus = $('#opd_scan_edit_status');
    var opdid = parseInt($('#opd_scan_opdid').val() || '0', 10);
    var latestFileId = 0;
    var cameraStorageKey = 'opd_scan_preferred_camera';
    var selectedDeviceId = '';
    var editState = {
        originalDataUrl: '',
        currentDataUrl: '',
        sourceType: 'capture',
        sourceFilename: 'capture.jpg',
        image: null,
        imageRect: null,
        selection: null,
        dragging: false,
        dragStart: null
    };
    var videoConstraints = {
        width: { ideal: 1920, max: 3840 },
        height: { ideal: 1080, max: 2160 },
        frameRate: { ideal: 30, max: 30 }
    };

    try {
        selectedDeviceId = window.localStorage.getItem(cameraStorageKey) || '';
    } catch (e) {
        selectedDeviceId = '';
    }

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

    function dataUrlToBlob(dataUrl) {
        var parts = (dataUrl || '').split(',');
        if (parts.length < 2) {
            return null;
        }
        var mimeMatch = parts[0].match(/data:(.*?);base64/);
        var mime = mimeMatch ? mimeMatch[1] : 'image/jpeg';
        var binary = window.atob(parts[1]);
        var len = binary.length;
        var bytes = new window.Uint8Array(len);
        for (var i = 0; i < len; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return new window.Blob([bytes], { type: mime });
    }

    function clamp(value, min, max) {
        return Math.max(min, Math.min(max, value));
    }

    function getCanvasPoint(evt) {
        var rect = editCanvas.getBoundingClientRect();
        return {
            x: (evt.clientX || 0) - rect.left,
            y: (evt.clientY || 0) - rect.top
        };
    }

    function normalizeSelection(start, end, bounds) {
        var x1 = clamp(Math.min(start.x, end.x), bounds.x, bounds.x + bounds.width);
        var y1 = clamp(Math.min(start.y, end.y), bounds.y, bounds.y + bounds.height);
        var x2 = clamp(Math.max(start.x, end.x), bounds.x, bounds.x + bounds.width);
        var y2 = clamp(Math.max(start.y, end.y), bounds.y, bounds.y + bounds.height);
        return {
            x: x1,
            y: y1,
            width: Math.max(0, x2 - x1),
            height: Math.max(0, y2 - y1)
        };
    }

    function drawEditorCanvas() {
        if (!editCanvas || !editCtx || !editState.image) {
            return;
        }

        var maxWidth = Math.min(900, Math.max(320, window.innerWidth - 140));
        var maxHeight = Math.min(560, Math.max(240, window.innerHeight - 260));
        var imageWidth = editState.image.width || 1;
        var imageHeight = editState.image.height || 1;
        var scale = Math.min(maxWidth / imageWidth, maxHeight / imageHeight, 1);
        var drawWidth = Math.max(1, Math.round(imageWidth * scale));
        var drawHeight = Math.max(1, Math.round(imageHeight * scale));

        editCanvas.width = drawWidth;
        editCanvas.height = drawHeight;
        editCtx.clearRect(0, 0, drawWidth, drawHeight);
        editCtx.drawImage(editState.image, 0, 0, drawWidth, drawHeight);
        editState.imageRect = { x: 0, y: 0, width: drawWidth, height: drawHeight };

        if (editState.selection && editState.selection.width > 2 && editState.selection.height > 2) {
            editCtx.save();
            editCtx.strokeStyle = '#0d6efd';
            editCtx.lineWidth = 2;
            editCtx.setLineDash([6, 4]);
            editCtx.strokeRect(editState.selection.x, editState.selection.y, editState.selection.width, editState.selection.height);
            editCtx.fillStyle = 'rgba(13, 110, 253, 0.12)';
            editCtx.fillRect(editState.selection.x, editState.selection.y, editState.selection.width, editState.selection.height);
            editCtx.restore();
            $editStatus.text('Crop area: ' + Math.round(editState.selection.width) + ' x ' + Math.round(editState.selection.height));
        } else {
            $editStatus.text('No crop selected.');
        }
    }

    function loadEditorImage(dataUrl, preserveSelection) {
        if (!dataUrl) {
            return;
        }
        var img = new window.Image();
        img.onload = function() {
            editState.currentDataUrl = dataUrl;
            editState.image = img;
            if (!preserveSelection) {
                editState.selection = null;
            }
            drawEditorCanvas();
        };
        img.src = dataUrl;
    }

    function rotateEditorImage(direction) {
        if (!editState.image) {
            return;
        }
        var source = document.createElement('canvas');
        var sourceCtx = source.getContext('2d');
        var srcWidth = editState.image.width;
        var srcHeight = editState.image.height;
        var clockwise = direction === 'right';

        source.width = srcHeight;
        source.height = srcWidth;
        sourceCtx.save();
        if (clockwise) {
            sourceCtx.translate(source.width, 0);
            sourceCtx.rotate(Math.PI / 2);
        } else {
            sourceCtx.translate(0, source.height);
            sourceCtx.rotate(-Math.PI / 2);
        }
        sourceCtx.drawImage(editState.image, 0, 0);
        sourceCtx.restore();
        loadEditorImage(source.toDataURL('image/jpeg', 0.95));
    }

    function cropEditorImage() {
        if (!editState.image || !editState.selection || !editState.imageRect) {
            $editStatus.text('Select a crop area first.');
            return;
        }
        var selection = editState.selection;
        if (selection.width < 8 || selection.height < 8) {
            $editStatus.text('Crop area is too small.');
            return;
        }

        var scaleX = editState.image.width / editState.imageRect.width;
        var scaleY = editState.image.height / editState.imageRect.height;
        var srcX = Math.round(selection.x * scaleX);
        var srcY = Math.round(selection.y * scaleY);
        var srcW = Math.round(selection.width * scaleX);
        var srcH = Math.round(selection.height * scaleY);

        var cropCanvas = document.createElement('canvas');
        var cropCtx = cropCanvas.getContext('2d');
        cropCanvas.width = Math.max(1, srcW);
        cropCanvas.height = Math.max(1, srcH);
        cropCtx.drawImage(editState.image, srcX, srcY, srcW, srcH, 0, 0, cropCanvas.width, cropCanvas.height);
        loadEditorImage(cropCanvas.toDataURL('image/jpeg', 0.95));
    }

    function openCaptureEditor(dataUrl, sourceType, sourceFilename) {
        editState.originalDataUrl = dataUrl;
        editState.currentDataUrl = dataUrl;
        editState.sourceType = sourceType || 'capture';
        editState.sourceFilename = sourceFilename || (editState.sourceType === 'upload' ? 'upload.jpg' : 'capture.jpg');
        editState.selection = null;
        loadEditorImage(dataUrl);
        if (editModal) {
            editModal.show();
        }
    }

    function saveEditedCapture() {
        var blob = dataUrlToBlob(editState.currentDataUrl);
        if (!blob) {
            $editStatus.text('Unable to prepare edited image.');
            return;
        }
        if (editModal) {
            editModal.hide();
        }
        var formData = new window.FormData();
        var csrf = getCsrfPair();
        if (editState.sourceType === 'upload') {
            formData.append('userfile', blob, editState.sourceFilename || 'upload.jpg');
        } else {
            formData.append('webcam', blob, editState.sourceFilename || 'capture.jpg');
        }
        formData.append(csrf.name, csrf.value);
        submitScanFormData(formData);
    }

    function isEditableImageFile(file) {
        if (!file) {
            return false;
        }
        var type = (file.type || '').toLowerCase();
        if (type.indexOf('image/') === 0) {
            return true;
        }
        var name = (file.name || '').toLowerCase();
        return /\.(jpg|jpeg|png|webp|gif)$/.test(name);
    }

    function readFileAsDataUrl(file, onDone) {
        if (!file) {
            return;
        }
        var reader = new window.FileReader();
        reader.onload = function(evt) {
            if (typeof onDone === 'function') {
                onDone((evt && evt.target) ? (evt.target.result || '') : '');
            }
        };
        reader.onerror = function() {
            $results.html('<div class="text-danger">Unable to read selected image.</div>');
        };
        reader.readAsDataURL(file);
    }

    function saveSelectedDevice(deviceId) {
        selectedDeviceId = deviceId || '';
        try {
            if (selectedDeviceId) {
                window.localStorage.setItem(cameraStorageKey, selectedDeviceId);
            } else {
                window.localStorage.removeItem(cameraStorageKey);
            }
        } catch (e) {
        }
    }

    function getPreferredConstraints(deviceId) {
        var constraints = {
            width: videoConstraints.width,
            height: videoConstraints.height,
            frameRate: videoConstraints.frameRate
        };
        if (deviceId) {
            constraints.deviceId = { exact: deviceId };
        }
        return constraints;
    }

    function refreshCameraList(preferredDeviceId) {
        if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
            return $.Deferred ? $.Deferred().resolve().promise() : null;
        }

        return navigator.mediaDevices.enumerateDevices().then(function(devices) {
            var videoDevices = [];
            devices.forEach(function(device) {
                if (device.kind === 'videoinput') {
                    videoDevices.push(device);
                }
            });

            var currentValue = preferredDeviceId || selectedDeviceId || $cameraSelect.val() || '';
            var optionsHtml = '<option value="">Default camera</option>';
            videoDevices.forEach(function(device, index) {
                var label = device.label || ('Camera ' + (index + 1));
                var value = device.deviceId || '';
                var selectedAttr = value && value === currentValue ? ' selected' : '';
                optionsHtml += '<option value="' + $('<div>').text(value).html() + '"' + selectedAttr + '>' + $('<div>').text(label).html() + '</option>';
            });
            $cameraSelect.html(optionsHtml);

            if (currentValue && $cameraSelect.find('option[value="' + currentValue.replace(/"/g, '\\"') + '"]').length) {
                $cameraSelect.val(currentValue);
            } else {
                $cameraSelect.val('');
                if (preferredDeviceId || selectedDeviceId) {
                    saveSelectedDevice('');
                }
            }
        }).catch(function() {
        });
    }

    function startCamera(deviceId) {
        // Start webcam stream for quick bedside capture.
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            $results.html('<div class="text-danger">Camera not supported in this browser.</div>');
            $resolution.text('Camera: not supported in this browser');
            return;
        }

        stopCamera();

        function attachStream(stream) {
            streamRef = stream;
            var video = $video.get(0);
            video.srcObject = stream;
            video.onloadedmetadata = function() {
                video.play().catch(function() {});
                updateResolutionLabel();
            };
            setTimeout(updateResolutionLabel, 300);
            setTimeout(updateResolutionLabel, 1000);
        }

        var preferredDeviceId = typeof deviceId === 'string' ? deviceId : ($cameraSelect.val() || selectedDeviceId || '');

        navigator.mediaDevices.getUserMedia({ video: getPreferredConstraints(preferredDeviceId), audio: false })
            .then(function(stream) {
                saveSelectedDevice(preferredDeviceId);
                attachStream(stream);
                refreshCameraList(preferredDeviceId);
            })
            .catch(function() {
                // Fallback to default camera settings if HD constraints fail.
                navigator.mediaDevices.getUserMedia({ video: true, audio: false })
                    .then(function(stream) {
                        saveSelectedDevice('');
                        attachStream(stream);
                        refreshCameraList('');
                    })
                    .catch(function() {
                        $results.html('<div class="text-danger">Unable to access webcam. Please allow camera permission.</div>');
                        $resolution.text('Camera: permission denied or unavailable');
                    });
            });
    }

    function updateResolutionLabel() {
        var video = $video.get(0);
        var width = 0;
        var height = 0;

        if (streamRef && streamRef.getVideoTracks) {
            var track = streamRef.getVideoTracks()[0];
            if (track && track.getSettings) {
                var settings = track.getSettings();
                width = parseInt(settings.width || 0, 10) || 0;
                height = parseInt(settings.height || 0, 10) || 0;
            }
        }

        if ((!width || !height) && video) {
            width = parseInt(video.videoWidth || 0, 10) || width;
            height = parseInt(video.videoHeight || 0, 10) || height;
        }

        if (width > 0 && height > 0) {
            $resolution.text('Camera: ' + width + ' x ' + height);
        } else {
            $resolution.text('Camera: detecting resolution...');
        }
    }

    function stopCamera() {
        // Stop all active tracks to release camera hardware.
        if (streamRef) {
            streamRef.getTracks().forEach(function(track) { track.stop(); });
            streamRef = null;
        }
        $resolution.text('Camera: stopped');
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
            $textBox.html('<div class="text-muted small">AI text is not processed yet. Click Process AI to extract text.</div>');
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

        $processAiBtn.prop('disabled', true).text('Processing...');

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
                var msg = (data && data.error_text) ? data.error_text : 'AI processing failed for this file.';
                $aiResult.removeClass('text-muted').addClass('text-danger').text(msg);
            }
            $processAiBtn.prop('disabled', latestFileId <= 0).text('Process AI');
            loadLastList();
        }, 'json').fail(function() {
            $aiResult.removeClass('text-muted').addClass('text-danger').text('AI processing failed for this file.');
            $processAiBtn.prop('disabled', latestFileId <= 0).text('Process AI');
            loadLastList();
        });
    }

    function processLatestAi() {
        if (latestFileId <= 0) {
            $aiResult.removeClass('text-muted').addClass('text-danger').text('Capture or upload a file before running AI.');
            return;
        }
        $aiResult.removeClass('text-danger').addClass('text-muted').text('AI processing started...');
        renderExtractedText('');
        queueAiProcessing(latestFileId);
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
                latestFileId = parseInt(data.file_id || '0', 10);
                $processAiBtn.prop('disabled', latestFileId <= 0).text('Process AI');
                $aiResult.removeClass('text-danger text-success').addClass('text-muted').text('File saved. Click Process AI when you want analysis.');
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
        // Capture current webcam frame, allow edit, then upload it.
        if (opdid <= 0) {
            return;
        }

        var video = $video.get(0);
        if (!video || !video.videoWidth) {
            $results.html('<div class="text-danger">Camera frame not ready. Try again.</div>');
            return;
        }

        $captureBtn.prop('disabled', true);
        $textBox.html('<div class="text-muted small">Capturing and uploading file...</div>');

        var canvas = $canvas.get(0);
        var width = video.videoWidth;
        var height = video.videoHeight;
        if (streamRef && streamRef.getVideoTracks) {
            var track = streamRef.getVideoTracks()[0];
            if (track && track.getSettings) {
                var settings = track.getSettings();
                width = parseInt(settings.width || width || 0, 10) || width;
                height = parseInt(settings.height || height || 0, 10) || height;
            }
        }
        canvas.width = width;
        canvas.height = height;
        var ctx = canvas.getContext('2d');
        ctx.imageSmoothingEnabled = true;
        ctx.imageSmoothingQuality = 'high';
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        updateResolutionLabel();
        $captureBtn.prop('disabled', false);
        if (!allowImagePreuploadEdit) {
            canvas.toBlob(function(blob) {
                if (!blob) {
                    $results.html('<div class="text-danger">Capture failed.</div>');
                    return;
                }
                var formData = new window.FormData();
                var csrf = getCsrfPair();
                formData.append('webcam', blob, 'capture.jpg');
                formData.append(csrf.name, csrf.value);
                submitScanFormData(formData);
            }, 'image/jpeg', 0.95);
            return;
        }
        openCaptureEditor(canvas.toDataURL('image/jpeg', 0.95), 'capture', 'capture.jpg');
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

        if (allowImagePreuploadEdit && isEditableImageFile(file)) {
            $textBox.html('<div class="text-muted small">Preparing image editor...</div>');
            readFileAsDataUrl(file, function(dataUrl) {
                if (!dataUrl) {
                    $results.html('<div class="text-danger">Unable to read selected image.</div>');
                    return;
                }
                openCaptureEditor(dataUrl, 'upload', file.name || 'upload.jpg');
            });
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
    $processAiBtn.off('click.opdscan').on('click.opdscan', processLatestAi);
    $cameraSelect.off('change.opdscan').on('change.opdscan', function() {
        var deviceId = $(this).val() || '';
        saveSelectedDevice(deviceId);
        startCamera(deviceId);
    });
    $cameraRefresh.off('click.opdscan').on('click.opdscan', function() {
        refreshCameraList($cameraSelect.val() || selectedDeviceId || '');
        startCamera($cameraSelect.val() || selectedDeviceId || '');
    });
    $('#opd_scan_rotate_left').off('click.opdscan').on('click.opdscan', function() {
        rotateEditorImage('left');
    });
    $('#opd_scan_rotate_right').off('click.opdscan').on('click.opdscan', function() {
        rotateEditorImage('right');
    });
    $('#opd_scan_crop_btn').off('click.opdscan').on('click.opdscan', function() {
        cropEditorImage();
    });
    $('#opd_scan_reset_edit').off('click.opdscan').on('click.opdscan', function() {
        loadEditorImage(editState.originalDataUrl);
    });
    $('#opd_scan_save_edit').off('click.opdscan').on('click.opdscan', function() {
        saveEditedCapture();
    });

    if (editCanvas) {
        editCanvas.addEventListener('mousedown', function(evt) {
            if (!editState.imageRect) {
                return;
            }
            editState.dragging = true;
            editState.dragStart = getCanvasPoint(evt);
            editState.selection = normalizeSelection(editState.dragStart, editState.dragStart, editState.imageRect);
            drawEditorCanvas();
        });
        editCanvas.addEventListener('mousemove', function(evt) {
            if (!editState.dragging || !editState.dragStart || !editState.imageRect) {
                return;
            }
            editState.selection = normalizeSelection(editState.dragStart, getCanvasPoint(evt), editState.imageRect);
            drawEditorCanvas();
        });
        editCanvas.addEventListener('mouseup', function(evt) {
            if (!editState.dragging || !editState.dragStart || !editState.imageRect) {
                return;
            }
            editState.selection = normalizeSelection(editState.dragStart, getCanvasPoint(evt), editState.imageRect);
            editState.dragging = false;
            editState.dragStart = null;
            drawEditorCanvas();
        });
        editCanvas.addEventListener('mouseleave', function() {
            if (editState.dragging) {
                editState.dragging = false;
                editState.dragStart = null;
            }
        });
    }

    window.removeOpdScanImage = function(fileId) {
        if (!fileId) {
            return;
        }
        if (!window.confirm('Delete this scan file?')) {
            return;
        }

        var csrf = getCsrfPair();
        var payload = {};
        payload[csrf.name] = csrf.value;

        $.post('<?= base_url('Opd/opd_file_delete') ?>/' + fileId, payload, function(resp) {
            updateCsrf(resp || {});
            if (!resp || parseInt(resp.update || 0, 10) !== 1) {
                $results.html('<div class="text-danger">' + ((resp && resp.error_text) ? $('<div>').text(resp.error_text).html() : 'Unable to delete scan file.') + '</div>');
                return;
            }
            loadLastList();
            $results.html('<div class="text-success">Scan deleted successfully.</div>');
        }, 'json').fail(function() {
            $results.html('<div class="text-danger">Unable to delete scan file.</div>');
        });
    };

    refreshCameraList(selectedDeviceId);
    startCamera(selectedDeviceId);
    loadLastList();

    $('#tallModal').off('hidden.bs.modal.opdscan').on('hidden.bs.modal.opdscan', function() {
        stopCamera();
        $('#opd_scan_results').html('Captured image will appear here.');
        $resolution.text('Camera: closed');
    });
    if (editModalEl) {
        editModalEl.addEventListener('hidden.bs.modal', function() {
            editState.selection = null;
            editState.dragging = false;
            editState.dragStart = null;
            $editStatus.text('No crop selected.');
        });
    }
})();
</script>
