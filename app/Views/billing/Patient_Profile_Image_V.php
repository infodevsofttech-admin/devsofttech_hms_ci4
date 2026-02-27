<div class="pagetitle">
    <h1>Profile Image</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="javascript:load_form('<?= base_url('billing/patient/person_record') ?>/<?= esc($patient->id) ?>/0');">Profile</a></li>
            <li class="breadcrumb-item active">Image</li>
        </ol>
    </nav>
</div>

<section class="section profile">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Profile Image</h3>
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
                                <video id="camera" autoplay playsinline class="w-100 rounded" style="max-height: 240px;"></video>
                            </div>
                            <div class="d-flex gap-2 mt-2">
                                <button type="button" id="start_camera" class="btn btn-outline-primary btn-sm">Start Camera</button>
                                <button type="button" id="stop_camera" class="btn btn-outline-secondary btn-sm" disabled>Stop Camera</button>
                                <button type="button" id="capture_btn" class="btn btn-primary btn-sm" disabled>Capture</button>
                                <button type="button" id="retake_btn" class="btn btn-outline-info btn-sm" disabled>Retake</button>
                            </div>
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

                    <form method="post" enctype="multipart/form-data"
                        action="<?= base_url('billing/patient/patient_file_upload') ?>/<?= esc($patient->id) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="doc_type" value="profile">
                        <input type="hidden" name="update_profile" value="1">

                        <div class="input-group input-group-sm">
                            <input type="file" name="upload_file" class="form-control" accept="image/*">
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

<script>
$(function() {
    var saveUrl = '<?= base_url('billing/patient/save_profile_image') ?>/<?= esc($patient->id) ?>';
    var csrfName = '<?= csrf_token() ?>';
    var csrfValue = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';

    var stream = null;
    var video = document.getElementById('camera');
    var canvas = document.getElementById('snapshot');
    var $startBtn = $('#start_camera');
    var $stopBtn = $('#stop_camera');
    var $captureBtn = $('#capture_btn');
    var $cameraStatus = $('#camera_status');
    var $retakeBtn = $('#retake_btn');
    var $clearHistory = $('#clear_history');
    var historyList = [];
    var currentIndex = -1;
    var modalEl = document.getElementById('capturePreviewModal');
    var modalInstance = modalEl ? new bootstrap.Modal(modalEl) : null;

    function setStatus(type, message) {
        var klass = type === 'success' ? 'alert-success' : 'alert-danger';
        $('#upload_status').html('<div class="alert ' + klass + ' py-1 mb-0">' + message + '</div>');
    }

    function setCameraState(active) {
        $startBtn.prop('disabled', active);
        $stopBtn.prop('disabled', !active);
        $captureBtn.prop('disabled', !active);
        $retakeBtn.prop('disabled', true);
        $cameraStatus.text(active ? 'Camera is active.' : 'Camera is idle.');
    }

    function startCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            $cameraStatus.text('Camera not supported in this browser.');
            return;
        }

        if (stream) {
            stopCamera();
        }

        navigator.mediaDevices.getUserMedia({ video: true })
            .then(function(s) {
                stream = s;
                video.srcObject = s;
                setCameraState(true);
            })
            .catch(function(err) {
                var msg = (err && err.name === 'NotAllowedError')
                    ? 'Camera access denied.'
                    : 'Camera not available.';
                $cameraStatus.text(msg);
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
    }

    window.pageCleanup = function() {
        stopCamera();
    };

    $startBtn.on('click', startCamera);
    $stopBtn.on('click', stopCamera);
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
    startCamera();

    $('#capture_btn').on('click', function() {
        if (!stream || !video || !canvas) {
            if (typeof notify === 'function') {
                notify('error', 'Camera', 'Start the camera first.');
            }
            return;
        }

        var width = video.videoWidth || 640;
        var height = video.videoHeight || 480;
        canvas.width = width;
        canvas.height = height;
        var ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, width, height);

        var dataUri = canvas.toDataURL('image/jpeg', 0.9);
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
    });

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

    $(window).on('beforeunload', function() {
        stopCamera();
    });

    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopCamera();
        }
    });
});
</script>
