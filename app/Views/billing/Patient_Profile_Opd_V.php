<?php
$patient = $patient ?? (object) [];
$opdGroups = (isset($opdGroups) && is_array($opdGroups)) ? $opdGroups : [];
$backUrl = trim((string) ($backUrl ?? ''));
$backTitle = trim((string) ($backTitle ?? 'Profile'));
$allowImagePreuploadEdit = !empty($allow_image_preupload_edit);
if ($backUrl === '') {
    $backUrl = base_url('billing/patient/person_record') . '/' . (int) ($patient->id ?? 0) . '/0';
}
$patientAbhaId = trim((string) ($patient->abha_id ?? $patient->abha_no ?? $patient->abha ?? ''));
$patientAbhaAddress = trim((string) ($patient->abha_address ?? ''));
if ($patientAbhaAddress === '' && $patientAbhaId !== '' && strpos($patientAbhaId, '@') !== false) {
    $patientAbhaAddress = $patientAbhaId;
}
if ($patientAbhaAddress === '' && preg_match('/abha_address\s*:\s*([A-Za-z0-9._-]+@[A-Za-z0-9.-]+)/i', (string) ($patient->log ?? ''), $abhaLogMatch) === 1) {
    $patientAbhaAddress = trim((string) ($abhaLogMatch[1] ?? ''));
}
$abhaVerifiedStatus = strtoupper(trim((string) ($patient->abha_verified_status ?? '')));
$abhaKycVerified = (int) ($patient->abha_kyc_verified ?? 0) === 1;
$abhaMobileVerified = (int) ($patient->abha_mobile_verified ?? 0) === 1;
$abhaIsVerified = $abhaVerifiedStatus === 'VERIFIED' || ($abhaKycVerified && $abhaMobileVerified);
$hasAbhaIdentity = $patientAbhaAddress !== '' || $patientAbhaId !== '' || $abhaIsVerified;
$totalOpdVisits = (int) ($totalOpdVisits ?? 0);
$lastVisitDateRaw = trim((string) ($lastVisitDate ?? ''));
$lastVisitDateLabel = $lastVisitDateRaw !== '' ? date('d-m-Y', strtotime($lastVisitDateRaw)) : 'Not available';
$lastVisitOpdNo = trim((string) ($lastVisitOpdNo ?? ''));

$patientAge = trim((string) get_age_1($patient->dob ?? null, $patient->age ?? '', $patient->age_in_month ?? '', $patient->estimate_dob ?? ''));
$genderRaw = trim((string) ($patient->xgender ?? $patient->gender ?? ''));
$genderNormalized = strtoupper($genderRaw);
if ($genderNormalized === '1' || $genderNormalized === 'M' || $genderNormalized === 'MALE') {
    $patientGender = 'Male';
} elseif ($genderNormalized === '2' || $genderNormalized === 'F' || $genderNormalized === 'FEMALE') {
    $patientGender = 'Female';
} elseif ($genderNormalized === '3' || $genderNormalized === 'O' || $genderNormalized === 'OTHER') {
    $patientGender = 'Other';
} else {
    $patientGender = $genderRaw !== '' ? $genderRaw : 'Not available';
}

$patientPhotoPath = trim((string) ($profileFilePath ?? ''));
if ($patientPhotoPath === '') {
    $patientPhotoPath = trim((string) ($patient->profile_picture ?? ''));
}

$abhaPhotoBase64 = trim((string) ($patient->abha_profile_photo_base64 ?? ''));
if ($patientPhotoPath === '' && $abhaPhotoBase64 !== '') {
    $patientPhotoPath = str_starts_with($abhaPhotoBase64, 'data:image')
        ? $abhaPhotoBase64
        : 'data:image/jpeg;base64,' . $abhaPhotoBase64;
}

if ($patientPhotoPath === '') {
    $patientPhotoPath = '/assets/images/no_image.svg';
}
?>

<div class="pagetitle">
    <h1>OPD History</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="javascript:load_form('<?= base_url('billing/patient/person_record') ?>/<?= esc($patient->id) ?>/0');">Profile</a></li>
            <li class="breadcrumb-item active">OPD History</li>
        </ol>
    </nav>
</div>

<section class="section profile">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3 class="card-title mb-0">OPD History</h3>
            <button type="button" class="btn btn-outline-secondary btn-sm"
                    onclick="load_form('<?= esc($backUrl, 'js') ?>','<?= esc($backTitle, 'js') ?>')">
                Back
            </button>
        </div>
        <div class="card-body">
            <div class="opd-patient-meta-card" style="border:1px solid #dee2e6;border-radius:.5rem;padding:.75rem;margin-bottom:1rem;background:#fff;">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <img id="opdPatientPhoto" src="<?= esc($patientPhotoPath) ?>" alt="Patient Photo" class="opd-patient-photo" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:1px solid #dee2e6;background:#f8f9fa;cursor:zoom-in;" data-bs-toggle="modal" data-bs-target="#patientPhotoModal">
                    <div class="opd-patient-meta-row d-flex flex-wrap align-items-center gap-3">
                        <span>Patient: <strong><?= esc($patient->p_fname ?? '') ?></strong></span>
                        <span><strong>Age:</strong> <?= $patientAge !== '' ? esc($patientAge) : 'Not available' ?></span>
                        <span><strong>Gender:</strong> <?= esc($patientGender) ?></span>
                        <span><strong>Last Visit:</strong> <?= esc($lastVisitDateLabel) ?></span>
                        <span><strong>OPD No.:</strong> <?= $lastVisitOpdNo !== '' ? esc($lastVisitOpdNo) : 'Not available' ?></span>
                        <span><strong>No. of Visit:</strong> <?= esc((string) $totalOpdVisits) ?></span>
                    </div>
                    <?php if ($allowImagePreuploadEdit): ?>
                        <div class="ms-auto">
                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#opdProfilePictureModal">Take Picture</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <ul class="nav nav-tabs nav-tabs-bordered" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#opd-history-tab" type="button" role="tab">OPD History</button>
                </li>
                <?php if ($hasAbhaIdentity): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#opd-abdm-tab" type="button" role="tab">ABDM Fetched Data</button>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="tab-content pt-2">
                <div class="tab-pane fade show active" id="opd-history-tab" role="tabpanel">

            <style>
                .opd-history-file-card {
                    border: 1px solid #dee2e6;
                    border-radius: .5rem;
                    overflow: hidden;
                    background: #fff;
                    height: 100%;
                }
                .opd-history-file-preview {
                    display: block;
                    width: auto;
                    max-width: 100%;
                    max-height: min(42vh, 360px);
                    height: auto;
                    margin: 0 auto;
                    object-fit: contain;
                    cursor: zoom-in;
                    background: #f8f9fa;
                }
                .opd-history-pdf-link {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 260px;
                    text-align: center;
                    padding: 1rem;
                    background: #f8f9fa;
                }
                .opd-scan-modal-body {
                    max-height: calc(100vh - 140px);
                    min-height: 70vh;
                    overflow: auto;
                    background: #f8f9fa;
                    padding: .75rem;
                }
                .opd-scan-modal-dialog {
                    width: min(96vw, 1600px);
                    max-width: min(96vw, 1600px);
                }
                .opd-scan-modal-img {
                    display: block;
                    max-width: 100%;
                    max-height: calc(100vh - 190px);
                    width: auto;
                    height: auto;
                    margin: 0 auto;
                    transform-origin: center center;
                    transition: transform 0.12s ease;
                    cursor: grab;
                    user-select: none;
                }
                .opd-scan-modal-img.is-zoomed {
                    cursor: zoom-out;
                }
                .opd-scan-modal-img.is-dragging {
                    cursor: grabbing;
                }
                .abdm-doc-detail-list {
                    max-height: 45vh;
                    overflow: auto;
                }
                .abdm-flow-step {
                    font-size: 12px;
                    border: 1px solid #ced4da;
                    border-radius: 999px;
                    padding: 2px 8px;
                    color: #6c757d;
                    background: #f8f9fa;
                }
                .abdm-flow-step.is-active {
                    color: #0d6efd;
                    border-color: #0d6efd;
                    background: #e7f1ff;
                }
                .abdm-flow-step.is-done {
                    color: #198754;
                    border-color: #198754;
                    background: #e8f7ee;
                }
                .opd-patient-meta-card {
                    border: 1px solid #dee2e6;
                    border-radius: .5rem;
                    padding: .75rem;
                    margin-bottom: 1rem;
                    background: #fff;
                }
                .opd-patient-photo {
                    width: 72px;
                    height: 72px;
                    border-radius: 50%;
                    object-fit: cover;
                    border: 1px solid #dee2e6;
                    background: #f8f9fa;
                }
                .opd-patient-meta-row {
                    font-size: .95rem;
                }
            </style>

            <?php if (empty($opdGroups)) { ?>
                <div class="alert alert-info mb-0">No OPD scans found.</div>
            <?php } else { ?>
                <?php foreach ($opdGroups as $group) { ?>
                    <div class="border rounded p-3 mb-3">
                        <div class="row g-3 align-items-start">
                            <div class="col-12 col-xl-12">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                    <div>
                                        <strong><?= esc($group['opd_code']) ?></strong>
                                        <span class="text-muted ms-2">Dr. <?= esc($group['doc_name']) ?></span>
                                    </div>
                                    <div class="text-muted text-end">
                                        <?= esc($group['opd_date']) ?> <?= esc($group['queue_no'] !== '' ? $group['queue_no'] : ($group['rx_queue_no'] ?? '')) ?>
                                    </div>
                                </div>

                                <div class="mt-2 d-flex flex-wrap gap-2">
                                    <?php if ((int) ($group['rx_session_id'] ?? 0) > 0) { ?>
                                        <a class="btn btn-primary btn-sm" target="_blank"
                                           href="<?= base_url('Opd_prescription/opd_prescription_print/' . (int) $group['opd_id'] . '/' . (int) $group['rx_session_id']) ?>">
                                            Prescription Print
                                        </a>
                                    <?php } ?>
                                </div>

                                <?php if (!empty($group['bp']) || !empty($group['diastolic']) || !empty($group['pulse']) || !empty($group['temp']) || !empty($group['spo2'])) { ?>
                                    <div class="small text-muted d-flex flex-wrap gap-3 mt-2">
                                        <?php if (!empty($group['bp'])) { ?><span><strong>BP:</strong> <?= esc($group['bp']) ?></span><?php } ?>
                                        <?php if (!empty($group['diastolic'])) { ?><span><strong>Diastolic:</strong> <?= esc($group['diastolic']) ?></span><?php } ?>
                                        <?php if (!empty($group['pulse'])) { ?><span><strong>Pulse:</strong> <?= esc($group['pulse']) ?></span><?php } ?>
                                        <?php if (!empty($group['temp'])) { ?><span><strong>Temp:</strong> <?= esc($group['temp']) ?></span><?php } ?>
                                        <?php if (!empty($group['spo2'])) { ?><span><strong>SPO2:</strong> <?= esc($group['spo2']) ?></span><?php } ?>
                                    </div>
                                <?php } ?>

                                <?php if (!empty($group['complaints']) || !empty($group['diagnosis']) || !empty($group['investigation']) || !empty($group['advice'])) { ?>
                                    <div class="d-grid gap-2 mt-2">
                                        <?php if (!empty($group['complaints'])) { ?>
                                            <div class="border rounded p-2 bg-light small">
                                                <strong>Complaints:</strong> <?= esc($group['complaints']) ?>
                                            </div>
                                        <?php } ?>
                                        <?php if (!empty($group['diagnosis'])) { ?>
                                            <div class="border rounded p-2 bg-light small">
                                                <strong>Diagnosis:</strong> <?= esc($group['diagnosis']) ?>
                                            </div>
                                        <?php } ?>
                                        <?php if (!empty($group['investigation'])) { ?>
                                            <div class="border rounded p-2 bg-light small">
                                                <strong>Investigation:</strong> <?= esc($group['investigation']) ?>
                                            </div>
                                        <?php } ?>
                                        <?php if (!empty($group['advice'])) { ?>
                                            <div class="border rounded p-2 bg-light small">
                                                <strong>Advice:</strong> <?= esc($group['advice']) ?>
                                            </div>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            </div>

                            <div class="col-12 col-xl-12">
                                <?php if (empty($group['files'])) { ?>
                                    <div class="text-muted mt-2">No scanned files for this OPD.</div>
                                <?php } else { ?>
                                    <div class="small fw-semibold mb-2">Scanned Files</div>
                                    <div class="row g-3">
                                        <?php foreach ($group['files'] as $index => $file) { ?>
                                            <div class="col-12">
                                                <div class="opd-history-file-card">
                                                    <?php if ($file['isPdf']) { ?>
                                                        <a class="opd-history-pdf-link" href="<?= esc($file['path']) ?>" target="_blank">
                                                            <span>
                                                                <strong>Open PDF</strong><br>
                                                                <span class="text-muted small">Tap to view the full document</span>
                                                            </span>
                                                        </a>
                                                    <?php } else { ?>
                                                        <img src="<?= esc($file['path']) ?>" class="opd-history-file-preview w-100"
                                                            data-bs-toggle="modal" data-bs-target="#opdScanModal"
                                                            data-src="<?= esc($file['path']) ?>" alt="OPD Scan">
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            <?php } ?>

                </div>

                <?php if ($hasAbhaIdentity): ?>
                    <div class="tab-pane fade" id="opd-abdm-tab" role="tabpanel">
                        <div class="card border mt-2 mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                                    <div>
                                        <div class="fw-semibold mb-1">ABDM M3 — Fetch Patient Health Records</div>
                                        <div class="small text-muted">
                                            ABHA Address: <strong><?= $patientAbhaAddress !== '' ? esc($patientAbhaAddress) : 'Not available' ?></strong>
                                            &nbsp;|&nbsp;
                                            ABHA Status:
                                            <?php if ($abhaIsVerified) { ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">VERIFIED</span>
                                            <?php } else { ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><?= $abhaVerifiedStatus !== '' ? esc($abhaVerifiedStatus) : 'UNVERIFIED' ?></span>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button type="button" class="btn btn-primary" id="btnNewAbdmRequest" data-bs-toggle="modal" data-bs-target="#abdmCustomConsentModal" <?= ($abhaIsVerified && $patientAbhaAddress !== '') ? '' : 'disabled' ?>>
                                            + New Request
                                        </button>
                                        <div>
                                            <button type="button" class="btn btn-link btn-sm p-0 mt-1" id="btnLoadAbdmDocs">Refresh list</button>
                                        </div>
                                    </div>
                                </div>

                                <?php if (! $abhaIsVerified || $patientAbhaAddress === '') { ?>
                                    <div class="alert alert-warning mt-3 mb-0 small">
                                        ABHA must be linked and verified for this patient before ABDM records can be requested. Verify ABHA from the patient's profile first.
                                    </div>
                                <?php } else { ?>
                                    <div class="small text-muted mt-2 mb-0">
                                        Send a consent request to the patient's ABHA (PHR) app. Once the patient approves it there, HMS automatically fetches their health records — no need to click again.
                                    </div>
                                <?php } ?>

                                <div id="abdmStatusBoxWrap" class="border rounded p-3 mt-3 bg-light d-none">
                                    <span id="abdmStatusBox" class="small text-muted"></span>
                                </div>
                            </div>
                        </div>

                        <div class="card border mt-2 mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="fw-semibold">Consent Request History</div>
                                    <div>
                                        <button type="button" class="btn btn-link btn-sm p-0 me-3" id="btnCheckLiveAbdmStatus">
                                            <span class="spinner-border spinner-border-sm d-none" id="abdmLiveStatusSpinner"></span>
                                            Check Live Status
                                        </button>
                                        <button type="button" class="btn btn-link btn-sm p-0" id="btnRefreshAbdmRequests">Refresh</button>
                                    </div>
                                </div>
                                <div class="table-responsive border rounded">
                                    <table class="table table-sm table-hover mb-0" id="abdmRequestsTable">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Status</th>
                                                <th>Expiry Date</th>
                                                <th>Date From</th>
                                                <th>Date To</th>
                                                <th>View</th>
                                                <th class="text-end">Show Data</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr><td colspan="7" class="text-muted text-center">No consent requests yet.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="modal fade" id="patientPhotoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Patient Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center bg-light">
                    <img id="opdPatientPhotoModalImg" src="<?= esc($patientPhotoPath) ?>" alt="Patient Photo" style="max-width:100%;max-height:75vh;width:auto;height:auto;border-radius:.5rem;">
                </div>
            </div>
        </div>
    </div>

    <?php if ($allowImagePreuploadEdit): ?>
        <div class="modal fade" id="opdProfilePictureModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Take Profile Picture</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 align-items-start">
                            <div class="col-md-6 text-center">
                                <video id="opdProfileCameraVideo" autoplay muted playsinline style="width:100%;max-width:320px;border:1px solid #ced4da;border-radius:6px;"></video>
                                <canvas id="opdProfileCameraCanvas" style="display:none;"></canvas>
                                <div class="mt-2 d-flex gap-2 justify-content-center flex-wrap">
                                    <button type="button" id="opdProfileCameraStart" class="btn btn-outline-primary btn-sm">Start Camera</button>
                                    <button type="button" id="opdProfileCameraCapture" class="btn btn-warning btn-sm">Capture</button>
                                    <button type="button" id="opdProfileCameraStop" class="btn btn-outline-secondary btn-sm">Stop</button>
                                </div>
                                <div id="opdProfileCameraStatus" class="small text-muted mt-2">Camera is idle.</div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-2 bg-light text-center">
                                    <img id="opdProfileCapturedPreview" src="<?= esc($patientPhotoPath) ?>" alt="Profile preview" class="img-fluid rounded" style="max-height:320px;object-fit:contain;">
                                </div>
                                <div class="small text-muted mt-2">Captured image will be uploaded to the patient profile.</div>
                                <div id="opdProfileUploadStatus" class="small mt-2"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                        <button type="button" id="opdProfileSaveCapture" class="btn btn-primary btn-sm" disabled>Save Profile Picture</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="opdProfileEditModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Capture</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex gap-2 flex-wrap mb-2">
                            <button type="button" id="opdProfileRotateLeft" class="btn btn-outline-secondary btn-sm">Rotate Left</button>
                            <button type="button" id="opdProfileRotateRight" class="btn btn-outline-secondary btn-sm">Rotate Right</button>
                            <button type="button" id="opdProfileCropBtn" class="btn btn-outline-primary btn-sm">Crop Selection</button>
                            <button type="button" id="opdProfileResetEdit" class="btn btn-outline-warning btn-sm">Reset</button>
                        </div>
                        <div class="small text-muted mb-2">Drag on the image to select crop area. Rotate if needed, then save.</div>
                        <div class="border rounded p-2 text-center bg-light">
                            <canvas id="opdProfileEditCanvas" style="max-width:100%;cursor:crosshair;"></canvas>
                        </div>
                        <div id="opdProfileEditStatus" class="small text-muted mt-2">No crop selected.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="opdProfileSaveEdit" class="btn btn-primary btn-sm">Save and Upload</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="modal fade" id="opdScanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog opd-scan-modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">OPD Scan</h5>
                    <div class="d-flex align-items-center gap-2 me-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="opdScanZoomOut">-</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="opdScanZoomIn">+</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="opdScanZoomReset">Reset</button>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center opd-scan-modal-body">
                    <img id="opdScanModalImg" class="opd-scan-modal-img" alt="OPD Scan">
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="abdmConsentDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Consent Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="abdmConsentDetailBody">
                    <div class="text-muted small">Loading...</div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="abdmFetchResultModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Fetched Health Records</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="abdmFetchResultModalBody">
                    <div class="text-muted small text-center">Loading...</div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($allowImagePreuploadEdit): ?>
        <script>
        (function() {
            var patientId = <?= (int) ($patient->id ?? 0) ?>;
            var saveUrl = '<?= base_url('billing/patient/save_profile_image') ?>/' + patientId;
            var $modal = $('#opdProfilePictureModal');
            var editModalEl = document.getElementById('opdProfileEditModal');
            var editModal = editModalEl && window.bootstrap ? new bootstrap.Modal(editModalEl) : null;
            if (!$modal.length) {
                return;
            }

            var $video = $('#opdProfileCameraVideo');
            var $canvas = $('#opdProfileCameraCanvas');
            var $status = $('#opdProfileCameraStatus');
            var $preview = $('#opdProfileCapturedPreview');
            var $uploadStatus = $('#opdProfileUploadStatus');
            var $saveBtn = $('#opdProfileSaveCapture');
            var editCanvas = document.getElementById('opdProfileEditCanvas');
            var editCtx = editCanvas ? editCanvas.getContext('2d') : null;
            var $editStatus = $('#opdProfileEditStatus');
            var stream = null;
            var editState = {
                originalDataUrl: '',
                currentDataUrl: '',
                image: null,
                imageRect: null,
                selection: null,
                dragging: false,
                dragStart: null
            };

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

            function openCaptureEditor(dataUrl) {
                editState.originalDataUrl = dataUrl;
                editState.currentDataUrl = dataUrl;
                editState.selection = null;
                loadEditorImage(dataUrl);
                if (editModal) {
                    editModal.show();
                }
            }

            function saveEditedCapture() {
                var dataUri = editState.currentDataUrl || '';
                if (!dataUri) {
                    $editStatus.text('No edited image available.');
                    return;
                }
                saveCaptureDataUri(dataUri);
                if (editModal) {
                    editModal.hide();
                }
            }

            function saveCaptureDataUri(dataUri) {
                if (!dataUri) {
                    $uploadStatus.html('<span class="text-danger">No captured image to save.</span>');
                    return;
                }
                $saveBtn.prop('disabled', true);
                $.post(saveUrl, { image: dataUri, '<?= csrf_token() ?>': '<?= csrf_hash() ?>' }, function(resp) {
                    if (resp && resp.success) {
                        if (resp.path) {
                            $('#opdPatientPhoto, #opdPatientPhotoModalImg, #opdProfileCapturedPreview').attr('src', resp.path);
                        }
                        $uploadStatus.html('<span class="text-success">Profile image saved.</span>');
                        stopCamera();
                        $saveBtn.removeData('data-uri');
                        return;
                    }
                    $uploadStatus.html('<span class="text-danger">' + ((resp && resp.message) ? resp.message : 'Upload failed') + '</span>');
                    $saveBtn.prop('disabled', false);
                }, 'json').fail(function() {
                    $uploadStatus.html('<span class="text-danger">Upload failed.</span>');
                    $saveBtn.prop('disabled', false);
                });
            }

            function setStatus(text, isError) {
                $status.toggleClass('text-danger', !!isError).toggleClass('text-muted', !isError).text(text);
            }

            function stopCamera() {
                if (stream) {
                    stream.getTracks().forEach(function(track) { track.stop(); });
                    stream = null;
                }
                if ($video.length) {
                    $video[0].srcObject = null;
                }
                setStatus('Camera is idle.', false);
            }

            function startCamera() {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    setStatus('Camera not supported in this browser.', true);
                    return;
                }
                navigator.mediaDevices.getUserMedia({ video: true, audio: false }).then(function(s) {
                    stream = s;
                    $video[0].srcObject = s;
                    $video[0].play().catch(function() {});
                    setStatus('Camera is active.', false);
                }).catch(function() {
                    setStatus('Camera access denied or unavailable.', true);
                });
            }

            function captureImage() {
                if (!stream || !$video.length || !$canvas.length) {
                    setStatus('Start the camera first.', true);
                    return;
                }
                var width = $video[0].videoWidth || 640;
                var height = $video[0].videoHeight || 480;
                $canvas[0].width = width;
                $canvas[0].height = height;
                var ctx = $canvas[0].getContext('2d');
                ctx.drawImage($video[0], 0, 0, width, height);
                var dataUri = $canvas[0].toDataURL('image/jpeg', 0.95);
                if (<?= $allowImagePreuploadEdit ? 'true' : 'false' ?>) {
                    openCaptureEditor(dataUri);
                    $uploadStatus.html('<span class="text-success">Preview ready. Edit if needed, then save.</span>');
                    return;
                }
                $preview.attr('src', dataUri);
                $saveBtn.data('data-uri', dataUri).prop('disabled', false);
                $uploadStatus.html('<span class="text-success">Preview ready. Save to upload.</span>');
            }

            function saveCapture() {
                var dataUri = String($saveBtn.data('data-uri') || '');
                if (!dataUri) {
                    $uploadStatus.html('<span class="text-danger">No captured image to save.</span>');
                    return;
                }
                $saveBtn.prop('disabled', true);
                $.post(saveUrl, { image: dataUri, '<?= csrf_token() ?>': '<?= csrf_hash() ?>' }, function(resp) {
                    if (resp && resp.success) {
                        if (resp.path) {
                            $('#opdPatientPhoto, #opdPatientPhotoModalImg, #opdProfileCapturedPreview').attr('src', resp.path);
                        }
                        $uploadStatus.html('<span class="text-success">Profile image saved.</span>');
                        stopCamera();
                        $saveBtn.removeData('data-uri');
                        return;
                    }
                    $uploadStatus.html('<span class="text-danger">' + ((resp && resp.message) ? resp.message : 'Upload failed') + '</span>');
                    $saveBtn.prop('disabled', false);
                }, 'json').fail(function() {
                    $uploadStatus.html('<span class="text-danger">Upload failed.</span>');
                    $saveBtn.prop('disabled', false);
                });
            }

            $modal.on('shown.bs.modal', function() {
                startCamera();
            });
            $modal.on('hidden.bs.modal', function() {
                stopCamera();
                $saveBtn.prop('disabled', true).removeData('data-uri');
                $uploadStatus.empty();
            });
            if (editModalEl) {
                editModalEl.addEventListener('hidden.bs.modal', function() {
                    editState.selection = null;
                });
            }
            $('#opdProfileCameraStart').on('click', startCamera);
            $('#opdProfileCameraStop').on('click', stopCamera);
            $('#opdProfileCameraCapture').on('click', captureImage);
            $('#opdProfileSaveCapture').on('click', saveCapture);

            $('#opdProfileRotateLeft').on('click', function() {
                rotateEditorImage('left');
            });
            $('#opdProfileRotateRight').on('click', function() {
                rotateEditorImage('right');
            });
            $('#opdProfileCropBtn').on('click', function() {
                cropEditorImage();
            });
            $('#opdProfileResetEdit').on('click', function() {
                loadEditorImage(editState.originalDataUrl);
            });
            $('#opdProfileSaveEdit').on('click', function() {
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
        })();
        </script>
    <?php endif; ?>

    <div class="modal fade" id="abdmCustomConsentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Consent Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Health Information Types</label>
                        <div class="row row-cols-2 row-cols-md-3 g-2" id="abdmCustomHiTypes">
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="OPConsultation" id="hiOPConsultation" checked>
                                    <label class="form-check-label" for="hiOPConsultation">OP Consultation</label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="DiagnosticReport" id="hiDiagnosticReport" checked>
                                    <label class="form-check-label" for="hiDiagnosticReport">Diagnostic Report</label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Prescription" id="hiPrescription">
                                    <label class="form-check-label" for="hiPrescription">Prescription</label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="DischargeSummary" id="hiDischargeSummary">
                                    <label class="form-check-label" for="hiDischargeSummary">Discharge Summary</label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="HealthDocumentRecord" id="hiHealthDocument">
                                    <label class="form-check-label" for="hiHealthDocument">Health Document</label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="ImmunizationRecord" id="hiImmunizationRecord">
                                    <label class="form-check-label" for="hiImmunizationRecord">Immunization Record</label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="WellnessRecord" id="hiWellness">
                                    <label class="form-check-label" for="hiWellness">Wellness</label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Invoice" id="hiInvoice">
                                    <label class="form-check-label" for="hiInvoice">Invoice</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="abdmCustomPurpose">Purpose Of Request</label>
                        <select class="form-select" id="abdmCustomPurpose">
                            <option value="CAREMGT" data-text="Care Management" selected>Care Management</option>
                            <option value="BTG" data-text="Break The Glass">Break The Glass</option>
                            <option value="PUBHLTH" data-text="Public Health">Public Health</option>
                            <option value="HPAYMT" data-text="Healthcare Payment">Healthcare Payment</option>
                            <option value="DSRCH" data-text="Disease Specific Healthcare Research">Disease Specific Healthcare Research</option>
                            <option value="PATRQT" data-text="Self Requested">Self Requested</option>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="abdmCustomDateFrom">Date From</label>
                            <input type="date" class="form-control" id="abdmCustomDateFrom">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="abdmCustomDateTo">Date To</label>
                            <input type="date" class="form-control" id="abdmCustomDateTo">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="abdmCustomEraseDate">Expiry Date</label>
                            <input type="date" class="form-control" id="abdmCustomEraseDate">
                        </div>
                    </div>
                    <div class="small text-danger d-none" id="abdmCustomConsentError"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="btnSendCustomConsent">
                        <span class="spinner-border spinner-border-sm me-1 d-none" id="abdmCustomConsentSpinner" role="status" aria-hidden="true"></span>
                        Send Consent Request
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
$(function() {
    var patientId = <?= (int) ($patient->id ?? 0) ?>;
    var abdmDocumentsUrl = '<?= base_url('billing/patient/abdm_documents/' . (int) ($patient->id ?? 0)) ?>';
    var abdmAutoFlowUrl = '<?= base_url('billing/patient/abdm_content_auto_flow/' . (int) ($patient->id ?? 0)) ?>';
    var abdmFetchOnlyUrl = '<?= base_url('billing/patient/abdm_content_fetch_only/' . (int) ($patient->id ?? 0)) ?>';
    var abdmConsentDetailUrl = '<?= base_url('billing/patient/abdm_consent_detail/' . (int) ($patient->id ?? 0)) ?>';
    var abdmConsentRequestsUrl = '<?= base_url('billing/patient/abdm_consent_requests/' . (int) ($patient->id ?? 0)) ?>';
    var abdmCheckLiveStatusUrl = '<?= base_url('billing/patient/abdm_check_live_status/' . (int) ($patient->id ?? 0)) ?>';
    var abdmCustomRequestUrl = '<?= base_url('billing/patient/abdm_content_request_custom/' . (int) ($patient->id ?? 0)) ?>';
    var abdmDocDetailBaseUrl = '<?= base_url('billing/patient/abdm_document_detail/' . (int) ($patient->id ?? 0)) ?>';
    var currentAbdmRows = [];
    var currentAbdmRequests = [];
    var autoFlowTimer = null;
    var autoFlowRequestId = '';
    var autoFlowAttempts = 0;
    var autoFlowMaxAttempts = 15;
    var autoFlowRunning = false;
    var fetchOnlyRunningIdx = -1;
    var currentAbdmFetchModalIdx = -1;

    function consentStatusBadgeClass(status) {
        switch ((status || '').toString().toUpperCase()) {
            case 'GRANTED': return 'bg-info-subtle text-info border border-info-subtle';
            case 'COMPLETED': return 'bg-success-subtle text-success border border-success-subtle';
            case 'REVOKED': return 'bg-secondary-subtle text-secondary border border-secondary-subtle';
            case 'EXPIRED': return 'bg-warning-subtle text-warning border border-warning-subtle';
            case 'DENIED': return 'bg-danger-subtle text-danger border border-danger-subtle';
            case 'FAILED': return 'bg-danger-subtle text-danger border border-danger-subtle';
            default: return 'bg-warning-subtle text-warning border border-warning-subtle';
        }
    }

    function consentItemBadgeClass(status) {
        switch ((status || '').toString().toUpperCase()) {
            case 'GRANTED': return 'bg-success-subtle text-success border border-success-subtle';
            case 'REVOKED': return 'bg-secondary-subtle text-secondary border border-secondary-subtle';
            case 'EXPIRED': return 'bg-warning-subtle text-warning border border-warning-subtle';
            case 'DENIED': return 'bg-danger-subtle text-danger border border-danger-subtle';
            case 'FAILED': return 'bg-danger-subtle text-danger border border-danger-subtle';
            default: return 'bg-info-subtle text-info border border-info-subtle';
        }
    }

    function fmtConsentTs(value) {
        value = (value || '').toString().trim();
        if (value === '') {
            return '-';
        }
        // Accept both plain 'YYYY-MM-DD HH:MM:SS' and ISO forms.
        var d = new Date(value.indexOf('T') === -1 ? value.replace(' ', 'T') : value);
        if (isNaN(d.getTime())) {
            return escHtml(value);
        }
        return escHtml(d.toLocaleString());
    }

    function renderAbdmConsentDetail(consent) {
        if (!consent) {
            return '<div class="text-danger small">No consent details available.</div>';
        }

        var steps = ['REQUESTED', 'GRANTED'];
        var statusUpper = (consent.status || '').toString().toUpperCase();
        if (statusUpper === 'REVOKED') { steps.push('REVOKED'); }
        else if (statusUpper === 'EXPIRED') { steps.push('EXPIRED'); }

        var stepperHtml = '<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">';
        steps.forEach(function(step, idx) {
            var reached = steps.indexOf(statusUpper) >= idx || (statusUpper === 'COMPLETED' && step !== 'REVOKED' && step !== 'EXPIRED');
            stepperHtml += '<span class="badge ' + (reached ? 'bg-success' : 'bg-secondary') + '">' + (idx + 1) + '. ' + escHtml(step.charAt(0) + step.slice(1).toLowerCase()) + '</span>';
            if (idx < steps.length - 1) { stepperHtml += '<span class="text-muted">&rarr;</span>'; }
        });
        stepperHtml += '</div>';

        var metaHtml = '<div class="row small text-muted mb-3">'
            + '<div class="col-md-6">Consent ID: <strong>' + escHtml(consent.consent_id || '-') + '</strong></div>'
            + '<div class="col-md-6">ABHA: <strong>' + escHtml(consent.abha_address || '-') + '</strong></div>'
            + '<div class="col-md-6">Purpose: <strong>' + escHtml(consent.purpose || '-') + '</strong></div>'
            + '<div class="col-md-6">Status: <strong>' + escHtml(consent.status || '-') + '</strong></div>'
            + '<div class="col-md-6">Valid From: <strong>' + fmtConsentTs(consent.valid_from) + '</strong></div>'
            + '<div class="col-md-6">Valid To: <strong>' + fmtConsentTs(consent.valid_to) + '</strong></div>'
            + '<div class="col-md-6">Requested On: <strong>' + fmtConsentTs(consent.requested_on) + '</strong></div>'
            + '<div class="col-md-6">Erase Date: <strong>' + fmtConsentTs(consent.erase_at) + '</strong></div>'
            + '</div>';

        var rowsHtml = '';
        (consent.items || []).forEach(function(item) {
            var ts = item.status === 'GRANTED' ? item.timestamp
                : item.status === 'REVOKED' ? item.timestamp
                : item.status === 'EXPIRED' ? item.timestamp
                : item.timestamp;
            rowsHtml += '<tr>'
                + '<td>' + escHtml(item.document_name || '') + '</td>'
                + '<td>' + escHtml(item.permission || 'VIEW') + '</td>'
                + '<td><span class="badge ' + consentItemBadgeClass(item.status) + '">' + escHtml(item.status || '') + '</span></td>'
                + '<td>' + fmtConsentTs(ts) + '</td>'
                + '</tr>';
        });
        if (rowsHtml === '') {
            rowsHtml = '<tr><td colspan="4" class="text-muted text-center">No health information types recorded for this request.</td></tr>';
        }

        var tableHtml = '<div class="table-responsive"><table class="table table-sm table-bordered mb-0">'
            + '<thead><tr><th>Document Type</th><th>Permission</th><th>Status</th><th>Timestamp</th></tr></thead>'
            + '<tbody>' + rowsHtml + '</tbody></table></div>';

        return stepperHtml + metaHtml + tableHtml;
    }

    function setAbdmStatus(text, isError) {
        var t = (text || '').toString().trim();
        if (t === '') {
            $('#abdmStatusBoxWrap').addClass('d-none');
            $('#abdmStatusBox').text('');
            return;
        }
        $('#abdmStatusBoxWrap').removeClass('d-none');
        $('#abdmStatusBox').toggleClass('text-danger', !!isError).toggleClass('text-muted', !isError).text(t);
    }

    function applyNewRequestButtonState() {
        $('#btnNewAbdmRequest').prop('disabled', autoFlowRunning || fetchOnlyRunningIdx !== -1);
    }

    // Renders one request's detail (from already-loaded list data, no extra
    // network call) into the shared Consent Details modal.
    function showAbdmConsentDetailForIndex(idx) {
        var consent = currentAbdmRequests[idx];
        $('#abdmConsentDetailBody').html(renderAbdmConsentDetail(consent));
        var bsModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('abdmConsentDetailModal'));
        bsModal.show();
    }

    function renderAbdmRequestsTable(requests) {
        if (!requests || !requests.length) {
            $('#abdmRequestsTable tbody').html('<tr><td colspan="7" class="text-muted text-center">No consent requests yet.</td></tr>');
            return;
        }

        var html = '';
        requests.forEach(function(consent, idx) {
            var status = (consent.status || '').toString().toUpperCase();
            var canFetch = (status === 'GRANTED' || status === 'COMPLETED');
            var rowId = consent.id || (idx + 1);

            html += '<tr>'
                + '<td class="small">' + escHtml(rowId) + '</td>'
                + '<td><span class="badge ' + consentStatusBadgeClass(status) + '">' + escHtml(status || 'UNKNOWN') + '</span></td>'
                + '<td class="small">' + fmtConsentTs(consent.erase_at) + '</td>'
                + '<td class="small">' + fmtConsentTs(consent.valid_from) + '</td>'
                + '<td class="small">' + fmtConsentTs(consent.valid_to) + '</td>'
                + '<td><button type="button" class="btn btn-link btn-sm p-0 abdm-view-request-btn" data-idx="' + idx + '">View</button></td>'
                + '<td class="text-end">'
                + (canFetch ? ('<button type="button" class="btn btn-link btn-sm p-0 abdm-fetch-request-btn" data-idx="' + idx + '">Show Data</button>') : '<span class="text-muted small">-</span>')
                + '</td>'
                + '</tr>';
        });
        $('#abdmRequestsTable tbody').html(html);
    }

    function loadAbdmConsentRequests() {
        fetch(abdmConsentRequestsUrl, { credentials: 'same-origin' })
            .then(function(resp) { return resp.json(); })
            .then(function(data) {
                if (!data || data.ok !== 1) {
                    throw new Error((data && data.error) || 'Unable to load consent request history.');
                }
                currentAbdmRequests = Array.isArray(data.requests) ? data.requests : [];
                renderAbdmRequestsTable(currentAbdmRequests);
            })
            .catch(function(err) {
                $('#abdmRequestsTable tbody').html('<tr><td colspan="7" class="text-danger text-center">' + escHtml('Failed to load consent request history: ' + (err.message || err)) + '</td></tr>');
            });
    }

    // Re-pulls data for a specific (already GRANTED/COMPLETED) request row using
    // its saved consent reference, without creating a brand-new consent request.
    function runFetchRecordsForRow(idx) {
        if (autoFlowRunning || fetchOnlyRunningIdx !== -1) {
            return;
        }
        var consent = currentAbdmRequests[idx];
        if (!consent) {
            return;
        }
        fetchOnlyRunningIdx = idx;
        currentAbdmFetchModalIdx = idx;
        applyNewRequestButtonState();
        setAbdmStatus('Fetching latest records using existing granted consent...', false);
        openAbdmFetchResultModal();
        $('#abdmFetchResultModalBody').html('<div class="text-muted small text-center">Fetching latest records...</div>');

        var url = abdmFetchOnlyUrl + '?consent_id=' + encodeURIComponent(consent.consent_id || '') + '&consent_request_id=' + encodeURIComponent(consent.consent_request_id || '');
        fetch(url, { credentials: 'same-origin' })
            .then(function(resp) { return resp.json(); })
            .then(function(data) {
                if (!data || data.ok !== 1) {
                    throw new Error((data && data.error) || 'Fetch failed.');
                }
                setAbdmStatus((data.message || 'Records fetched successfully.').toString(), false);
                fetchOnlyRunningIdx = -1;
                applyNewRequestButtonState();
                loadAbdmDocs();
                loadAbdmConsentRequests();
                loadAbdmFetchResultModalData(consent);
            })
            .catch(function(err) {
                fetchOnlyRunningIdx = -1;
                applyNewRequestButtonState();
                setAbdmStatus('Fetch failed: ' + (err.message || err), true);
                // Even if the fetch call itself failed, still show whatever records
                // are already saved locally for this consent request.
                loadAbdmFetchResultModalData(consent);
            });
    }

    function openAbdmFetchResultModal() {
        var bsModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('abdmFetchResultModal'));
        bsModal.show();
    }

    function loadAbdmFetchResultModalData(consent) {
        var url = abdmDocumentsUrl + '?limit=200&include_summary=1';
        var reqId = (consent && consent.consent_request_id) ? consent.consent_request_id : '';
        if (reqId !== '') {
            url += '&consent_request_id=' + encodeURIComponent(reqId);
        }

        fetch(url, { credentials: 'same-origin' })
            .then(function(resp) { return resp.json(); })
            .then(function(data) {
                if (!data || data.ok !== 1) {
                    throw new Error((data && data.error) || 'Unable to load fetched records.');
                }
                renderAbdmFetchResultModal(Array.isArray(data.items) ? data.items : []);
            })
            .catch(function(err) {
                $('#abdmFetchResultModalBody').html('<div class="text-danger small text-center">' + escHtml('Failed to load records: ' + (err.message || err)) + '</div>');
            });
    }

    function renderAbdmFetchResultModal(docs) {
        if (!docs || !docs.length) {
            var fetchBtnHtml = currentAbdmFetchModalIdx >= 0
                ? '<div class="text-center mt-2"><button type="button" class="btn btn-sm btn-primary abdm-fetch-again-btn" data-idx="' + currentAbdmFetchModalIdx + '">Fetch Data</button></div>'
                : '';
            $('#abdmFetchResultModalBody').html('<div class="text-muted small text-center">No fetched records found for this consent request yet.</div>' + fetchBtnHtml);
            return;
        }

        var html = '';
        var docIds = [];
        docs.forEach(function(doc, di) {
            var summary = doc.summary || {};
            var sections = Array.isArray(summary.sections) ? summary.sections : [];
            var docId = doc.id;
            if (docId) {
                docIds.push(docId);
            }

            html += '<div class="card mb-3">'
                + '<div class="card-body">'
                + '<div class="fw-semibold">' + escHtml(doc.document_title || 'ABDM Document') + '</div>'
                + '<div class="small text-muted mb-2">'
                + escHtml(doc.organization_name || '-') + ' &nbsp;|&nbsp; '
                + escHtml(doc.practitioner_name || '-') + ' &nbsp;|&nbsp; '
                + 'Date: ' + escHtml(fmtDateTime(doc.document_date || doc.created_at))
                + '</div>';

            if (sections.length) {
                html += '<div class="abdm-fetch-sections">';
                sections.forEach(function(sec, si) {
                    var body = '';
                    if (sec.items && sec.items.length) {
                        body = '<ul class="mb-0 small">' + sec.items.map(function(it) { return '<li>' + escHtml(it) + '</li>'; }).join('') + '</ul>';
                    } else if (sec.narrative) {
                        body = '<div class="small" style="white-space:pre-line;">' + escHtml(sec.narrative) + '</div>';
                    } else {
                        body = '<div class="small text-muted">No details recorded.</div>';
                    }
                    html += '<div class="mb-2">'
                        + '<div class="fw-bold">' + escHtml(sec.title || 'Section') + '</div>'
                        + body
                        + '</div>';
                });
                html += '</div>';
            } else {
                html += '<div class="small text-muted">No structured sections available for this record.</div>';
            }

            // Attachments (scanned images/PDFs) are stripped from the list payload
            // to keep it light -- lazily fetch the full per-document detail (which
            // includes them) after the modal is rendered, see below.
            if (docId) {
                html += '<div class="mt-3"><div class="fw-semibold small mb-2">Attached Files</div>'
                    + '<div class="abdm-fetch-attachments small text-muted" data-doc-id="' + escHtml(docId) + '">Loading attached files...</div></div>';
            }

            html += '</div></div>';
        });
        $('#abdmFetchResultModalBody').html(html);

        docIds.forEach(function(docId) {
            fetch(abdmDocDetailBaseUrl + '/' + encodeURIComponent(docId), { credentials: 'same-origin' })
                .then(function(resp) { return resp.json(); })
                .then(function(data) {
                    var item = (data && data.ok === 1) ? (data.item || {}) : null;
                    var summary = (item && item.summary) || {};
                    var $target = $('.abdm-fetch-attachments[data-doc-id="' + docId + '"]');
                    if (!item) {
                        $target.removeClass('text-muted').addClass('text-danger').text('Unable to load attached files.');
                        return;
                    }
                    $target.removeClass('small text-muted').html(renderAttachmentsHtml(summary.attachments));
                })
                .catch(function() {
                    $('.abdm-fetch-attachments[data-doc-id="' + docId + '"]').removeClass('text-muted').addClass('text-danger').text('Unable to load attached files.');
                });
        });
    }

    var zoom = 1;
    var minZoom = 0.5;
    var maxZoom = 3;
    var step = 0.25;
    var panX = 0;
    var panY = 0;
    var isDragging = false;
    var dragStartX = 0;
    var dragStartY = 0;

    function applyZoom() {
        var $img = $('#opdScanModalImg');
        $img.css('transform', 'translate(' + panX + 'px, ' + panY + 'px) scale(' + zoom + ')');
        $img.toggleClass('is-zoomed', zoom > 1);
    }

    function resetZoom() {
        zoom = 1;
        panX = 0;
        panY = 0;
        isDragging = false;
        $('#opdScanModalImg').removeClass('is-dragging');
        applyZoom();
    }

    $('#opdScanModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var src = button.data('src');
        $('#opdScanModalImg').attr('src', src);
        resetZoom();
    });

    $('#opdScanModal').on('hidden.bs.modal', function() {
        $('#opdScanModalImg').attr('src', '');
        resetZoom();
    });

    $('#opdScanZoomIn').on('click', function() {
        zoom = Math.min(maxZoom, zoom + step);
        applyZoom();
    });

    $('#opdScanZoomOut').on('click', function() {
        zoom = Math.max(minZoom, zoom - step);
        applyZoom();
    });

    $('#opdScanZoomReset').on('click', function() {
        resetZoom();
    });

    $('#opdScanModalImg').on('click', function() {
        if (zoom > 1) {
            resetZoom();
        }
    });

    $('#opdScanModalImg').on('wheel', function(e) {
        e.preventDefault();
        var evt = e.originalEvent;
        var delta = evt && typeof evt.deltaY === 'number' ? evt.deltaY : 0;
        zoom = delta < 0 ? Math.min(maxZoom, zoom + step) : Math.max(minZoom, zoom - step);
        if (zoom <= 1) {
            panX = 0;
            panY = 0;
        }
        applyZoom();
    });

    $('#opdScanModalImg').on('mousedown', function(e) {
        if (zoom <= 1) {
            return;
        }
        isDragging = true;
        dragStartX = e.clientX - panX;
        dragStartY = e.clientY - panY;
        $(this).addClass('is-dragging');
        e.preventDefault();
    });

    $(document).on('mousemove.opdscanzoom', function(e) {
        if (!isDragging) {
            return;
        }
        panX = e.clientX - dragStartX;
        panY = e.clientY - dragStartY;
        applyZoom();
    });

    $(document).on('mouseup.opdscanzoom', function() {
        if (!isDragging) {
            return;
        }
        isDragging = false;
        $('#opdScanModalImg').removeClass('is-dragging');
    });

    function escHtml(val) {
        return $('<div>').text(val == null ? '' : String(val)).html();
    }

    function base64ToBlobUrl(base64, contentType) {
        try {
            var byteChars = atob(base64);
            var byteNumbers = new Array(byteChars.length);
            for (var i = 0; i < byteChars.length; i++) {
                byteNumbers[i] = byteChars.charCodeAt(i);
            }
            var byteArray = new Uint8Array(byteNumbers);
            var blob = new Blob([byteArray], { type: contentType || 'application/octet-stream' });
            return URL.createObjectURL(blob);
        } catch (e) {
            return '';
        }
    }

    function fmtDateTime(val) {
        var text = (val || '').toString().trim();
        if (!text) {
            return '-';
        }
        return text.replace('T', ' ');
    }

    function renderAbdmRows(rows) {
        var html = '';
        if (!rows || !rows.length) {
            html = '<tr><td colspan="5" class="text-muted text-center">No ABDM fetched records found.</td></tr>';
            $('#abdmDocTable tbody').html(html);
            $('#abdmDocDetailBox').html('<div class="text-muted">No records available for this patient.</div>');
            return;
        }

        rows.forEach(function(row) {
            var reqId = row.consent_request_id || '-';
            html += '<tr class="abdm-doc-row" data-id="' + escHtml(row.id) + '">' +
                '<td>' + escHtml(fmtDateTime(row.document_date || row.created_at)) + '</td>' +
                '<td>' + escHtml(row.document_title || '-') + '</td>' +
                '<td>' + escHtml(row.care_context_reference || '-') + '</td>' +
                '<td class="small text-truncate" style="max-width:140px;" title="' + escHtml(row.organization_name || '-') + '">' + escHtml(row.organization_name || '-') + '</td>' +
                '<td class="small text-truncate" style="max-width:140px;" title="' + escHtml(reqId) + '">' + escHtml(reqId) + '</td>' +
            '</tr>';
        });
        $('#abdmDocTable tbody').html(html);
    }

    function renderAbdmDetail(item) {
        if (!item) {
            $('#abdmDocDetailBox').html('<div class="text-muted">Document details unavailable.</div>');
            return;
        }

        var summary = item.summary || {};
        var conditions = Array.isArray(summary.conditions) ? summary.conditions : [];
        var vitals = Array.isArray(summary.vitals) ? summary.vitals : [];
        var meds = Array.isArray(summary.medications) ? summary.medications : [];

        var html = '';
        html += '<div class="fw-semibold mb-2">' + escHtml(item.document_title || 'ABDM Document') + '</div>';
        html += '<div class="small text-muted mb-2">Date: ' + escHtml(fmtDateTime(item.document_date || item.created_at)) + '</div>';
        html += '<div class="small mb-2">Care Context: <strong>' + escHtml(item.care_context_reference || '-') + '</strong></div>';
        html += '<div class="small mb-2">Doctor: <strong>' + escHtml(item.practitioner_name || '-') + '</strong></div>';
        html += '<div class="small mb-2">Organization: <strong>' + escHtml(item.organization_name || '-') + '</strong></div>';
        html += '<div class="small mb-3">Consent Request ID: <strong>' + escHtml(item.consent_request_id || '-') + '</strong></div>';

        html += '<div class="row g-2">';
        html += '<div class="col-md-4"><div class="border rounded p-2 h-100"><div class="fw-semibold small mb-1">Diagnoses</div><ul class="mb-0 small abdm-doc-detail-list">';
        if (conditions.length) {
            conditions.forEach(function(c) {
                html += '<li>' + escHtml(c.text || '-') + '</li>';
            });
        } else {
            html += '<li class="text-muted">No diagnosis entries</li>';
        }
        html += '</ul></div></div>';

        html += '<div class="col-md-4"><div class="border rounded p-2 h-100"><div class="fw-semibold small mb-1">Vitals</div><ul class="mb-0 small abdm-doc-detail-list">';
        if (vitals.length) {
            vitals.forEach(function(v) {
                html += '<li>' + escHtml((v.name || '-') + ': ' + (v.value || '-')) + '</li>';
            });
        } else {
            html += '<li class="text-muted">No vitals</li>';
        }
        html += '</ul></div></div>';

        html += '<div class="col-md-4"><div class="border rounded p-2 h-100"><div class="fw-semibold small mb-1">Medications</div><ul class="mb-0 small abdm-doc-detail-list">';
        if (meds.length) {
            meds.forEach(function(m) {
                html += '<li>' + escHtml((m.name || '-') + (m.dose ? (' | ' + m.dose) : '')) + '</li>';
            });
        } else {
            html += '<li class="text-muted">No medications</li>';
        }
        html += '</ul></div></div>';
        html += '</div>';

        html += '<div class="mt-3"><div class="fw-semibold small mb-2">Attached Files</div>' + renderAttachmentsHtml(summary.attachments) + '</div>';

        $('#abdmDocDetailBox').html(html);
    }

    // Builds the "Attached Files" preview grid (images shown inline w/ zoom modal,
    // PDFs/other docs as an "Open"/"View" link) from a document's summary.attachments
    // array. Shared by the doc-detail box and the per-consent-request fetch modal.
    function renderAttachmentsHtml(attachments) {
        attachments = Array.isArray(attachments) ? attachments : [];
        if (!attachments.length) {
            return '<div class="small text-muted">No attached images, PDFs, or documents found in this record.</div>';
        }

        var html = '<div class="row g-2">';
        attachments.forEach(function(att, idx) {
            var ct = (att.content_type || '').toString().toLowerCase();
            var blobUrl = att.data ? base64ToBlobUrl(att.data, att.content_type) : '';
            html += '<div class="col-md-4"><div class="border rounded p-2 h-100 text-center">';
            html += '<div class="small fw-semibold mb-1">' + escHtml(att.title || ('Attachment ' + (idx + 1))) + '</div>';
            if (!blobUrl) {
                html += '<div class="small text-muted">Preview unavailable</div>';
            } else if (ct.indexOf('image/') === 0) {
                html += '<img src="' + blobUrl + '" class="img-fluid rounded abdm-attachment-img" style="max-height:160px;cursor:pointer;" data-bs-toggle="modal" data-bs-target="#opdScanModal" data-src="' + blobUrl + '" alt="Attached image">';
            } else if (ct === 'application/pdf') {
                html += '<div class="small text-muted mb-2">PDF Document</div><a href="' + blobUrl + '" target="_blank" rel="noopener" class="btn btn-sm btn-outline-danger">View PDF</a>';
            } else {
                html += '<div class="small text-muted mb-2">' + escHtml(att.content_type || 'Document') + '</div><a href="' + blobUrl + '" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">Open Document</a>';
            }
            html += '</div></div>';
        });
        html += '</div>';
        return html;
    }

    function loadAbdmDocs() {
        var q = ($('#abdmDocSearch').val() || '').toString().trim();
        var url = abdmDocumentsUrl + '?limit=200';
        if (q !== '') {
            url += '&q=' + encodeURIComponent(q);
        }

        fetch(url, { credentials: 'same-origin' })
            .then(function(resp) { return resp.json(); })
            .then(function(data) {
                if (!data || data.ok !== 1) {
                    throw new Error((data && data.error) || 'Unable to load ABDM records.');
                }
                currentAbdmRows = Array.isArray(data.items) ? data.items : [];
                renderAbdmRows(currentAbdmRows);
            })
            .catch(function(err) {
                setAbdmStatus('ABDM load failed: ' + (err.message || err), true);
            });
    }

    function stopAutoFlowLoop() {
        if (autoFlowTimer) {
            clearTimeout(autoFlowTimer);
            autoFlowTimer = null;
        }
    }

    function runAutoFlowStep() {
        var url = abdmAutoFlowUrl;
        if (autoFlowRequestId) {
            url += '?request_id=' + encodeURIComponent(autoFlowRequestId);
        }

        fetch(url, { credentials: 'same-origin' })
            .then(function(resp) { return resp.json(); })
            .then(function(data) {
                if (!data || data.ok !== 1) {
                    throw new Error((data && data.error) || 'Auto flow failed.');
                }

                autoFlowRequestId = (data.request_id || autoFlowRequestId || '').toString();
                var phase = (data.phase || '').toString();
                var msg = (data.message || '').toString();
                var resetRequestId = Number(data.reset_request_id || 0) === 1;
                var info = msg !== '' ? msg : ('Phase: ' + (phase || 'UNKNOWN'));

                setAbdmStatus(info + (autoFlowRequestId ? (' | Request ID: ' + autoFlowRequestId) : ''), false);

                if (resetRequestId) {
                    autoFlowRequestId = '';
                    stopAutoFlowLoop();
                    autoFlowRunning = false;
                    applyNewRequestButtonState();
                    setAbdmStatus('Previous request is still being processed. Please wait for the first request to finish before starting a new one.', false);
                    loadAbdmConsentRequests();
                    return;
                }

                var shouldPoll = Number(data.poll_again || 0) === 1;
                if (shouldPoll && autoFlowAttempts < autoFlowMaxAttempts) {
                    autoFlowAttempts += 1;
                    autoFlowTimer = setTimeout(runAutoFlowStep, 8000);
                    return;
                }

                autoFlowRunning = false;
                applyNewRequestButtonState();
                loadAbdmConsentRequests();
                if (phase === 'COMPLETED') {
                    loadAbdmDocs();
                }
            })
            .catch(function(err) {
                stopAutoFlowLoop();
                autoFlowRunning = false;
                applyNewRequestButtonState();
                setAbdmStatus('Auto flow failed: ' + (err.message || err), true);
                loadAbdmConsentRequests();
            });
    }

    function loadAbdmDocDetail(docId) {
        if (!docId) {
            return;
        }
        fetch(abdmDocDetailBaseUrl + '/' + encodeURIComponent(docId), { credentials: 'same-origin' })
            .then(function(resp) { return resp.json(); })
            .then(function(data) {
                if (!data || data.ok !== 1) {
                    throw new Error((data && data.error) || 'Unable to load document detail.');
                }
                renderAbdmDetail(data.item || null);
            })
            .catch(function(err) {
                $('#abdmDocDetailBox').html('<div class="text-danger">' + escHtml('Detail load failed: ' + (err.message || err)) + '</div>');
            });
    }

    $(document).off('click.abdmOpd', '#btnLoadAbdmDocs').on('click.abdmOpd', '#btnLoadAbdmDocs', function() {
        loadAbdmDocs();
    });

    $(document).off('click.abdmOpd', '#btnRefreshAbdmRequests').on('click.abdmOpd', '#btnRefreshAbdmRequests', function() {
        loadAbdmConsentRequests();
    });

    $(document).off('click.abdmOpd', '#btnCheckLiveAbdmStatus').on('click.abdmOpd', '#btnCheckLiveAbdmStatus', function() {
        var $btn = $(this);
        if ($btn.prop('disabled')) {
            return;
        }
        $btn.prop('disabled', true);
        $('#abdmLiveStatusSpinner').removeClass('d-none');
        setAbdmStatus('Checking live status with ABDM bridge…');

        fetch(abdmCheckLiveStatusUrl, { credentials: 'same-origin' })
            .then(function(resp) { return resp.json(); })
            .then(function(data) {
                if (!data || data.ok !== 1) {
                    throw new Error((data && data.error) || 'Live status check failed.');
                }
                currentAbdmRequests = Array.isArray(data.requests) ? data.requests : [];
                renderAbdmRequestsTable(currentAbdmRequests);
                if (!data.reconcile_ok) {
                    setAbdmStatus('Refreshed from last saved status (bridge check failed: ' + (data.reconcile_error || 'unknown error') + ').', true);
                } else if (data.granted) {
                    setAbdmStatus('Live status: GRANTED. Fetched data for ' + (data.artifacts_fetched || 0) + ' facility artifact(s)' + (data.data_fetch_failed ? (', ' + data.data_fetch_failed + ' failed') : '') + '.');
                } else {
                    setAbdmStatus('Live status refreshed from ABDM bridge.');
                }
            })
            .catch(function(err) {
                setAbdmStatus('Live status check failed: ' + (err.message || err), true);
            })
            .then(function() {
                $btn.prop('disabled', false);
                $('#abdmLiveStatusSpinner').addClass('d-none');
            });
    });

    $(document).off('click.abdmOpd', '#btnSearchAbdmDocs').on('click.abdmOpd', '#btnSearchAbdmDocs', function() {
        loadAbdmDocs();
    });

    $(document).off('keypress.abdmOpd', '#abdmDocSearch').on('keypress.abdmOpd', '#abdmDocSearch', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            loadAbdmDocs();
        }
    });

    $(document).off('click.abdmOpd', '#abdmDocTable .abdm-doc-row').on('click.abdmOpd', '#abdmDocTable .abdm-doc-row', function() {
        $('#abdmDocTable .abdm-doc-row').removeClass('table-active');
        $(this).addClass('table-active');
        var docId = $(this).data('id');
        loadAbdmDocDetail(docId);
    });

    $(document).off('click.abdmOpd', '.abdm-view-request-btn').on('click.abdmOpd', '.abdm-view-request-btn', function() {
        var idx = Number($(this).data('idx'));
        showAbdmConsentDetailForIndex(idx);
    });

    $(document).off('click.abdmOpd', '.abdm-fetch-request-btn').on('click.abdmOpd', '.abdm-fetch-request-btn', function() {
        var idx = Number($(this).data('idx'));
        runFetchRecordsForRow(idx);
    });

    $(document).off('click.abdmOpd', '.abdm-fetch-again-btn').on('click.abdmOpd', '.abdm-fetch-again-btn', function() {
        var idx = Number($(this).data('idx'));
        runFetchRecordsForRow(idx);
    });

    $(document).off('shown.bs.tab.abdmOpd', '[data-bs-target="#opd-abdm-tab"]').on('shown.bs.tab.abdmOpd', '[data-bs-target="#opd-abdm-tab"]', function() {
        if (!currentAbdmRows.length) {
            loadAbdmDocs();
        }
        loadAbdmConsentRequests();
    });

    $('#abdmCustomConsentModal').on('show.bs.modal', function() {
        $('#abdmCustomConsentError').addClass('d-none').text('');
        if (!$('#abdmCustomDateFrom').val()) {
            var from = new Date();
            from.setDate(from.getDate() - 365);
            $('#abdmCustomDateFrom').val(from.toISOString().slice(0, 10));
        }
        if (!$('#abdmCustomDateTo').val()) {
            $('#abdmCustomDateTo').val(new Date().toISOString().slice(0, 10));
        }
        if (!$('#abdmCustomEraseDate').val()) {
            var erase = new Date();
            erase.setFullYear(erase.getFullYear() + 1);
            $('#abdmCustomEraseDate').val(erase.toISOString().slice(0, 10));
        }
    });

    $(document).off('click.abdmOpd', '#btnSendCustomConsent').on('click.abdmOpd', '#btnSendCustomConsent', function() {
        if (autoFlowRunning || fetchOnlyRunningIdx !== -1) {
            return;
        }

        var hiTypes = [];
        $('#abdmCustomHiTypes input:checked').each(function() {
            hiTypes.push($(this).val());
        });
        if (!hiTypes.length) {
            $('#abdmCustomConsentError').removeClass('d-none').text('Select at least one Health Information Type.');
            return;
        }

        var dateFrom = $('#abdmCustomDateFrom').val();
        var dateTo = $('#abdmCustomDateTo').val();
        var eraseDate = $('#abdmCustomEraseDate').val();
        if (dateFrom && dateTo && dateFrom >= dateTo) {
            $('#abdmCustomConsentError').removeClass('d-none').text('"Date From" must be earlier than "Date To".');
            return;
        }
        if (eraseDate && dateTo && eraseDate < dateTo) {
            $('#abdmCustomConsentError').removeClass('d-none').text('"Expiry Date" must be on or after "Date To".');
            return;
        }

        var $btn = $('#btnSendCustomConsent');
        $btn.prop('disabled', true);
        $('#abdmCustomConsentSpinner').removeClass('d-none');
        $('#abdmCustomConsentError').addClass('d-none').text('');

        $.ajax({
            url: abdmCustomRequestUrl,
            method: 'POST',
            data: {
                hi_types: hiTypes,
                date_from: dateFrom,
                date_to: dateTo,
                erase_date: eraseDate,
                purpose_code: $('#abdmCustomPurpose').val()
            }
        }).done(function(data) {
            if (!data || data.ok !== 1) {
                throw new Error((data && data.error) || 'Consent request failed.');
            }

            var bsModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('abdmCustomConsentModal'));
            bsModal.hide();

            stopAutoFlowLoop();
            autoFlowAttempts = 0;
            autoFlowRequestId = (data.request_id || '').toString();
            autoFlowRunning = true;
            applyNewRequestButtonState();
            setAbdmStatus('Sending consent request to patient\'s ABHA app...', false);
            runAutoFlowStep();
        }).fail(function(xhr) {
            var data = xhr.responseJSON;
            var errMsg = (data && data.error) || 'Consent request failed.';
            $('#abdmCustomConsentError').removeClass('d-none').text(errMsg);
        }).always(function() {
            $btn.prop('disabled', false);
            $('#abdmCustomConsentSpinner').addClass('d-none');
        });
    });

    $('[data-bs-toggle="tooltip"]').tooltip();

    applyNewRequestButtonState();
    loadAbdmConsentRequests();
});
</script>

