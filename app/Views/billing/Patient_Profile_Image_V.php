<div class="pagetitle">
    <h1>Profile Image</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="javascript:load_form('<?= esc($backUrl ?? (base_url('billing/patient/person_record') . '/' . (int) ($patient->id ?? 0) . '/0'), 'js') ?>','<?= esc($backTitle ?? 'Profile', 'js') ?>');"><?= esc($backTitle ?? 'Profile') ?></a></li>
            <li class="breadcrumb-item active">Image</li>
        </ol>
    </nav>
</div>

<section class="section profile">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h3 class="card-title mb-0">Profile Image</h3>
                <a href="javascript:load_form('<?= esc($backUrl ?? (base_url('billing/patient/person_record') . '/' . (int) ($patient->id ?? 0) . '/0'), 'js') ?>','<?= esc($backTitle ?? 'Profile', 'js') ?>');" class="btn btn-outline-secondary btn-sm">Back</a>
            </div>
        </div>
        <div class="card-body">
            <p class="mb-3">Patient: <strong><?= esc($patient->p_fname ?? '') ?></strong></p>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="text-center">
                        <img id="profile-preview" src="<?= esc($profileFilePath) ?>" alt="Profile Image" class="img-thumbnail" style="max-width: 220px;">
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded p-2" style="min-height: 200px;">
                                <video id="camera" autoplay muted playsinline class="w-100 rounded" style="max-height: 240px;"></video>
                            </div>
                            <div class="mt-2">
                                <label for="profile_camera_select" class="form-label mb-1 small">Camera</label>
                                <div class="d-flex gap-2 align-items-start">
                                    <select id="profile_camera_select" class="form-select form-select-sm">
                                        <option value="">Default camera</option>
                                    </select>
                                    <button type="button" id="profile_camera_refresh" class="btn btn-outline-secondary btn-sm">Refresh</button>
                                </div>
                            </div>
                            <div class="d-flex gap-2 mt-2">
                                <button type="button" id="start_camera" class="btn btn-outline-primary btn-sm">Start Camera</button>
                                <button type="button" id="stop_camera" class="btn btn-outline-secondary btn-sm" disabled>Stop Camera</button>
                                <button type="button" id="capture_btn" class="btn btn-primary btn-sm" disabled>Capture</button>
                                <button type="button" id="retake_btn" class="btn btn-outline-info btn-sm" disabled>Retake</button>
                            </div>
                            <div id="camera_resolution" class="small text-muted mt-2">Camera: detecting resolution...</div>
                            <div id="camera_status" class="small text-muted mt-2">Camera is idle.</div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-2" style="min-height: 200px;">
                                <canvas id="snapshot" class="w-100 rounded" style="display:none;"></canvas>
                                <div id="results" class="text-muted small">Captured image will appear here.</div>
                            </div>
                            <div id="upload_status" class="mt-2"></div>
                            <div class="mt-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="small text-muted">Recent Captures</div>
                                    <button type="button" id="clear_history" class="btn btn-outline-secondary btn-sm">Clear</button>
                                </div>
                                <div id="capture_history" class="d-flex gap-2 flex-wrap"></div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">

                    <form method="post" enctype="multipart/form-data" id="profile_upload_form"
                        action="<?= base_url('billing/patient/patient_file_upload') ?>/<?= esc($patient->id) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="doc_type" value="profile">
                        <input type="hidden" name="update_profile" value="1">

                        <div class="input-group input-group-sm">
                            <input type="file" name="upload_file" id="profile_upload_file" class="form-control" accept="image/*">
                            <button type="submit" class="btn btn-success">Upload</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="capturePreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Capture Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="capturePreviewImg" class="img-fluid" alt="Capture Preview">
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" id="prev_capture" class="btn btn-outline-secondary btn-sm">Prev</button>
                <button type="button" id="next_capture" class="btn btn-outline-secondary btn-sm">Next</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="profileEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Capture</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex gap-2 flex-wrap mb-2">
                    <button type="button" id="profile_rotate_left" class="btn btn-outline-secondary btn-sm">Rotate Left</button>
                    <button type="button" id="profile_rotate_right" class="btn btn-outline-secondary btn-sm">Rotate Right</button>
                    <button type="button" id="profile_crop_btn" class="btn btn-outline-primary btn-sm">Crop Selection</button>
                    <button type="button" id="profile_reset_edit" class="btn btn-outline-warning btn-sm">Reset</button>
                </div>
                <div class="small text-muted mb-2">Drag on the image to select crop area. Rotate if needed, then save.</div>
                <div class="border rounded p-2 text-center bg-light">
                    <canvas id="profile_edit_canvas" style="max-width:100%;cursor:crosshair;"></canvas>
                </div>
                <div id="profile_edit_status" class="small text-muted mt-2">No crop selected.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="profile_save_edit" class="btn btn-primary btn-sm">Save and Upload</button>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    var saveUrl = '<?= base_url('billing/patient/save_profile_image') ?>/<?= esc($patient->id) ?>';
    var csrfName = '<?= csrf_token() ?>';
    var csrfValue = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';
    var allowImagePreuploadEdit = <?= !empty($allow_image_preupload_edit) ? 'true' : 'false' ?>;

    var stream = null;
    var video = document.getElementById('camera');
    var canvas = document.getElementById('snapshot');
    var $cameraSelect = $('#profile_camera_select');
    var $cameraRefresh = $('#profile_camera_refresh');
    var $startBtn = $('#start_camera');
    var $stopBtn = $('#stop_camera');
    var $captureBtn = $('#capture_btn');
    var $cameraStatus = $('#camera_status');
    var $cameraResolution = $('#camera_resolution');
    var $retakeBtn = $('#retake_btn');
    var $clearHistory = $('#clear_history');
    var historyList = [];
    var currentIndex = -1;
    var cameraCleanupRequested = false;
    var modalEl = document.getElementById('capturePreviewModal');
    var modalInstance = modalEl ? new bootstrap.Modal(modalEl) : null;
    var editModalEl = document.getElementById('profileEditModal');
    var editModalInstance = editModalEl ? new bootstrap.Modal(editModalEl) : null;
    var editCanvas = document.getElementById('profile_edit_canvas');
    var editCtx = editCanvas ? editCanvas.getContext('2d') : null;
    var $editStatus = $('#profile_edit_status');
    var cameraStorageKey = 'profile_image_preferred_camera';
    var selectedDeviceId = '';
    var videoConstraints = {
        width: { ideal: 1920, max: 3840 },
        height: { ideal: 1080, max: 2160 },
        frameRate: { ideal: 30, max: 30 }
    };
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

    try {
        selectedDeviceId = window.localStorage.getItem(cameraStorageKey) || '';
    } catch (e) {
        selectedDeviceId = '';
    }

    function setStatus(type, message) {
        var klass = type === 'success' ? 'alert-success' : 'alert-danger';
        $('#upload_status').html('<div class="alert ' + klass + ' py-1 mb-0">' + message + '</div>');
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
            setStatus('error', 'Unable to read selected image.');
        };
        reader.readAsDataURL(file);
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

    function loadEditorImage(dataUrl) {
        if (!dataUrl) {
            return;
        }
        var img = new window.Image();
        img.onload = function() {
            editState.currentDataUrl = dataUrl;
            editState.image = img;
            editState.selection = null;
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
        if (editModalInstance) {
            editModalInstance.show();
        }
    }

    function setCameraState(active) {
        $startBtn.prop('disabled', active);
        $stopBtn.prop('disabled', !active);
        $captureBtn.prop('disabled', !active);
        $retakeBtn.prop('disabled', true);
        $cameraStatus.text(active ? 'Camera is active.' : 'Camera is idle.');
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
            return;
        }

        navigator.mediaDevices.enumerateDevices().then(function(devices) {
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
            if (currentValue) {
                $cameraSelect.val(currentValue);
                if ($cameraSelect.val() !== currentValue) {
                    $cameraSelect.val('');
                    saveSelectedDevice('');
                }
            } else {
                $cameraSelect.val('');
            }
        }).catch(function() {
        });
    }

    function updateResolutionLabel() {
        var width = 0;
        var height = 0;
        if (stream && stream.getVideoTracks) {
            var track = stream.getVideoTracks()[0];
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
            $cameraResolution.text('Camera: ' + width + ' x ' + height);
        } else {
            $cameraResolution.text('Camera: detecting resolution...');
        }
    }

    function startCamera(deviceId) {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            $cameraStatus.text('Camera not supported in this browser.');
            $cameraResolution.text('Camera: not supported in this browser');
            return;
        }

        cameraCleanupRequested = false;
        if (stream) {
            stopCamera();
        }

        var preferredDeviceId = typeof deviceId === 'string' ? deviceId : ($cameraSelect.val() || selectedDeviceId || '');

        function attachStream(s, persistedDeviceId) {
            if (cameraCleanupRequested) {
                s.getTracks().forEach(function(track) { track.stop(); });
                return;
            }
            stream = s;
            video.srcObject = s;
            video.onloadedmetadata = function() {
                video.play().catch(function() {});
                updateResolutionLabel();
            };
            setCameraState(true);
            saveSelectedDevice(persistedDeviceId || '');
            refreshCameraList(persistedDeviceId || '');
            setTimeout(updateResolutionLabel, 300);
            setTimeout(updateResolutionLabel, 1000);
        }

        navigator.mediaDevices.getUserMedia({ video: getPreferredConstraints(preferredDeviceId), audio: false })
            .then(function(s) {
                attachStream(s, preferredDeviceId);
            })
            .catch(function() {
                navigator.mediaDevices.getUserMedia({ video: true, audio: false })
                    .then(function(s) {
                        attachStream(s, '');
                    })
                    .catch(function(err) {
                        var msg = (err && err.name === 'NotAllowedError')
                            ? 'Camera access denied.'
                            : 'Camera not available.';
                        $cameraStatus.text(msg);
                        $cameraResolution.text('Camera: unavailable');
                    });
            });
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(function(track) {
                track.stop();
            });
            stream = null;
        }
        if (video) {
            try {
                video.pause();
            } catch (e) {
                // no-op
            }
            video.srcObject = null;
        }
        setCameraState(false);
        $cameraResolution.text('Camera: stopped');
    }

    var previousPageCleanup = window.pageCleanup;
    window.pageCleanup = function() {
        cameraCleanupRequested = true;
        stopCamera();
        if (typeof previousPageCleanup === 'function') {
            previousPageCleanup();
        }
    };

    $startBtn.on('click', startCamera);
    $stopBtn.on('click', stopCamera);
    $cameraSelect.on('change', function() {
        var deviceId = $(this).val() || '';
        saveSelectedDevice(deviceId);
        startCamera(deviceId);
    });
    $cameraRefresh.on('click', function() {
        refreshCameraList($cameraSelect.val() || selectedDeviceId || '');
        startCamera($cameraSelect.val() || selectedDeviceId || '');
    });
    $retakeBtn.on('click', function() {
        $('#snapshot').hide();
        $('#results').html('Captured image will appear here.');
        $('#upload_status').html('');
        $retakeBtn.prop('disabled', true);
    });

    $clearHistory.on('click', function() {
        $('#capture_history').empty();
        historyList = [];
        currentIndex = -1;
    });

    setCameraState(false);
    refreshCameraList(selectedDeviceId);
    startCamera(selectedDeviceId);

    $('#capture_btn').on('click', function() {
        if (!stream || !video || !canvas) {
            if (typeof notify === 'function') {
                notify('error', 'Camera', 'Start the camera first.');
            }
            return;
        }

        var width = video.videoWidth || 640;
        var height = video.videoHeight || 480;
        if (stream && stream.getVideoTracks) {
            var track = stream.getVideoTracks()[0];
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
        ctx.drawImage(video, 0, 0, width, height);

        var dataUri = canvas.toDataURL('image/jpeg', 0.95);
        if (!allowImagePreuploadEdit) {
            persistProfileImageDataUri(dataUri);
            return;
        }
        openCaptureEditor(dataUri, 'capture', 'capture.jpg');
    });

    function persistProfileImageDataUri(dataUri) {
        if (!dataUri) {
            setStatus('error', 'Capture not ready');
            return;
        }

        $('#results').html('');
        $('#snapshot').show();
        $('#upload_status').html('');
        $retakeBtn.prop('disabled', false);

        var payload = {
            image: dataUri,
        };
        payload[csrfName] = csrfValue;

        $.post(saveUrl, payload, function(resp) {
            if (resp && resp.success) {
                if (resp.path) {
                    $('#profile-preview').attr('src', resp.path);
                }
                if (resp.csrf) {
                    csrfValue = resp.csrf;
                }
                setStatus('success', resp.message || 'Uploaded');
                stopCamera();
            } else {
                setStatus('error', (resp && resp.message) ? resp.message : 'Upload failed');
            }
        }, 'json').fail(function() {
            setStatus('error', 'Upload failed');
        });

        var thumb = $('<img>', {
            src: dataUri,
            class: 'rounded border',
            css: { width: '64px', height: '64px', objectFit: 'cover', cursor: 'pointer' }
        });
        var $history = $('#capture_history');
        $history.prepend(thumb);
        var items = $history.children('img');
        if (items.length > 5) {
            items.slice(5).remove();
        }

        historyList.unshift(dataUri);
        if (historyList.length > 5) {
            historyList = historyList.slice(0, 5);
        }
        $history.children('img').each(function(i) {
            $(this).attr('data-index', i);
        });

        if (editModalInstance) {
            editModalInstance.hide();
        }
    }

    function saveEditedProfileCapture() {
        persistProfileImageDataUri(editState.currentDataUrl);
    }

    $('#profile_upload_form').on('submit', function(e) {
        if (!allowImagePreuploadEdit) {
            return;
        }
        var fileInput = document.getElementById('profile_upload_file');
        var file = fileInput && fileInput.files ? fileInput.files[0] : null;
        if (!file || !isEditableImageFile(file)) {
            return;
        }
        e.preventDefault();
        setStatus('success', 'Opening image editor...');
        readFileAsDataUrl(file, function(dataUrl) {
            if (!dataUrl) {
                setStatus('error', 'Unable to read selected image.');
                return;
            }
            openCaptureEditor(dataUrl, 'upload', file.name || 'upload.jpg');
        });
    });

    $('#profile_rotate_left').on('click', function() {
        rotateEditorImage('left');
    });

    $('#profile_rotate_right').on('click', function() {
        rotateEditorImage('right');
    });

    $('#profile_crop_btn').on('click', function() {
        cropEditorImage();
    });

    $('#profile_reset_edit').on('click', function() {
        loadEditorImage(editState.originalDataUrl);
    });

    $('#profile_save_edit').on('click', function() {
        saveEditedProfileCapture();
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

    $('#capture_history').on('click', 'img', function() {
        var idx = parseInt($(this).attr('data-index'), 10);
        if (isNaN(idx)) {
            return;
        }
        currentIndex = idx;
        $('#capturePreviewImg').attr('src', historyList[currentIndex]);
        if (modalInstance) {
            modalInstance.show();
        }
    });

    if (modalEl) {
        modalEl.addEventListener('hidden.bs.modal', function() {
            currentIndex = -1;
        });
    }

    function showHistoryIndex(idx) {
        if (historyList.length === 0) {
            return;
        }
        currentIndex = (idx + historyList.length) % historyList.length;
        $('#capturePreviewImg').attr('src', historyList[currentIndex]);
    }

    $('#prev_capture').on('click', function() {
        showHistoryIndex(currentIndex - 1);
    });

    $('#next_capture').on('click', function() {
        showHistoryIndex(currentIndex + 1);
    });

    $(document).on('keydown', function(e) {
        if (!modalEl || !$(modalEl).hasClass('show')) {
            return;
        }
        if (historyList.length === 0) {
            return;
        }
        if (e.key === 'ArrowRight') {
            showHistoryIndex(currentIndex + 1);
        } else if (e.key === 'ArrowLeft') {
            showHistoryIndex(currentIndex - 1);
        }
    });

    $(window).off('beforeunload.profilecamera pagehide.profilecamera').on('beforeunload.profilecamera pagehide.profilecamera', function() {
        cameraCleanupRequested = true;
        stopCamera();
    });

    if (editModalEl) {
        editModalEl.addEventListener('hidden.bs.modal', function() {
            editState.selection = null;
            editState.dragging = false;
            editState.dragStart = null;
            $editStatus.text('No crop selected.');
        });
    }

    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            cameraCleanupRequested = true;
            stopCamera();
        }
    });
});
</script>
