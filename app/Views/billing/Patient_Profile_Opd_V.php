<?php
$patient = $patient ?? (object) [];
$opdGroups = (isset($opdGroups) && is_array($opdGroups)) ? $opdGroups : [];
$backUrl = trim((string) ($backUrl ?? ''));
$backTitle = trim((string) ($backTitle ?? 'Profile'));
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
$totalOpdVisits = (int) ($totalOpdVisits ?? 0);
$lastVisitDateRaw = trim((string) ($lastVisitDate ?? ''));
$lastVisitDateLabel = $lastVisitDateRaw !== '' ? date('d-m-Y', strtotime($lastVisitDateRaw)) : 'Not available';
$lastVisitSrNo = trim((string) ($lastVisitSrNo ?? ''));

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
                    <img src="<?= esc($patientPhotoPath) ?>" alt="Patient Photo" class="opd-patient-photo" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:1px solid #dee2e6;background:#f8f9fa;cursor:zoom-in;" data-bs-toggle="modal" data-bs-target="#patientPhotoModal">
                    <div class="opd-patient-meta-row d-flex flex-wrap align-items-center gap-3">
                        <span>Patient: <strong><?= esc($patient->p_fname ?? '') ?></strong></span>
                        <span><strong>Age:</strong> <?= $patientAge !== '' ? esc($patientAge) : 'Not available' ?></span>
                        <span><strong>Gender:</strong> <?= esc($patientGender) ?></span>
                        <span><strong>Last Visit:</strong> <?= esc($lastVisitDateLabel) ?></span>
                        <span><strong>OPD Sr No.:</strong> <?= $lastVisitSrNo !== '' ? esc($lastVisitSrNo) : 'Not available' ?></span>
                        <span><strong>No. of Visit:</strong> <?= esc((string) $totalOpdVisits) ?></span>
                    </div>
                </div>
            </div>

            <ul class="nav nav-tabs nav-tabs-bordered" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#opd-history-tab" type="button" role="tab">OPD History</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#opd-abdm-tab" type="button" role="tab">ABDM Fetched Data</button>
                </li>
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
                    max-height: 75vh;
                    overflow: auto;
                    background: #f8f9fa;
                }
                .opd-scan-modal-img {
                    max-width: 100%;
                    max-height: 72vh;
                    width: auto;
                    height: auto;
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
                                        <?= esc($group['opd_date']) ?> <?= esc($group['queue_no']) ?>
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

                <div class="tab-pane fade" id="opd-abdm-tab" role="tabpanel">
                    <div class="alert alert-light border d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2">
                        <div>
                            <div class="fw-semibold">ABHA Context</div>
                            <div class="small text-muted">
                                ABHA ID: <strong><?= $patientAbhaId !== '' ? esc($patientAbhaId) : 'Not available' ?></strong>
                                &nbsp;|&nbsp;
                                ABHA Address: <strong><?= $patientAbhaAddress !== '' ? esc($patientAbhaAddress) : 'Not available' ?></strong>
                                &nbsp;|&nbsp;
                                Status:
                                <?php if ($abhaIsVerified) { ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">VERIFIED</span>
                                <?php } else { ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><?= $abhaVerifiedStatus !== '' ? esc($abhaVerifiedStatus) : 'UNVERIFIED' ?></span>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btnLoadAbdmDocs">Refresh Fetched Data</button>
                            <button type="button" class="btn btn-primary btn-sm" id="btnAutoAbdmFlow" <?= ($abhaIsVerified && $patientAbhaAddress !== '') ? '' : 'disabled' ?>>Start ABDM Sync</button>
                        </div>
                    </div>

                    <div id="abdmStatusBox" class="small text-muted mb-2">Click "Refresh Fetched Data" to load ABDM records mapped to this patient.</div>

                    <div class="border rounded p-2 mb-3 bg-light">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2" id="abdmFlowSteps">
                            <span class="abdm-flow-step" data-step="1">1. Requested</span>
                            <span class="abdm-flow-step" data-step="2">2. Granted</span>
                            <span class="abdm-flow-step" data-step="3">3. Fetched</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div id="abdmFlowProgressBar" class="progress-bar" role="progressbar" style="width:0%"></div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-lg-5">
                            <div class="input-group input-group-sm mb-2">
                                <input id="abdmDocSearch" class="form-control" placeholder="Search title, care context, doctor">
                                <button class="btn btn-outline-secondary" type="button" id="btnSearchAbdmDocs">Search</button>
                            </div>
                            <div class="table-responsive border rounded">
                                <table class="table table-sm table-hover mb-0" id="abdmDocTable">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Title</th>
                                            <th>Care Context</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td colspan="3" class="text-muted text-center">No records loaded.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="border rounded p-3 bg-light" id="abdmDocDetailBox">
                                <div class="text-muted">Select a fetched document to view details.</div>
                            </div>
                        </div>
                    </div>

                </div>
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
                    <img src="<?= esc($patientPhotoPath) ?>" alt="Patient Photo" style="max-width:100%;max-height:75vh;width:auto;height:auto;border-radius:.5rem;">
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="opdScanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
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
</section>

<script>
$(function() {
    var patientId = <?= (int) ($patient->id ?? 0) ?>;
    var abdmDocumentsUrl = '<?= base_url('billing/patient/abdm_documents/' . (int) ($patient->id ?? 0)) ?>';
    var abdmAutoFlowUrl = '<?= base_url('billing/patient/abdm_content_auto_flow/' . (int) ($patient->id ?? 0)) ?>';
    var abdmDocDetailBaseUrl = '<?= base_url('billing/patient/abdm_document_detail/' . (int) ($patient->id ?? 0)) ?>';
    var currentAbdmRows = [];
    var autoFlowTimer = null;
    var autoFlowRequestId = '';
    var autoFlowAttempts = 0;
    var autoFlowMaxAttempts = 15;

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
            html = '<tr><td colspan="3" class="text-muted text-center">No ABDM fetched records found.</td></tr>';
            $('#abdmDocTable tbody').html(html);
            $('#abdmDocDetailBox').html('<div class="text-muted">No records available for this patient.</div>');
            return;
        }

        rows.forEach(function(row) {
            html += '<tr class="abdm-doc-row" data-id="' + escHtml(row.id) + '">' +
                '<td>' + escHtml(fmtDateTime(row.document_date || row.created_at)) + '</td>' +
                '<td>' + escHtml(row.document_title || '-') + '</td>' +
                '<td>' + escHtml(row.care_context_reference || '-') + '</td>' +
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
        html += '<div class="small mb-3">Organization: <strong>' + escHtml(item.organization_name || '-') + '</strong></div>';

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

        $('#abdmDocDetailBox').html(html);
    }

    function loadAbdmDocs() {
        var q = ($('#abdmDocSearch').val() || '').toString().trim();
        var url = abdmDocumentsUrl + '?limit=200';
        if (q !== '') {
            url += '&q=' + encodeURIComponent(q);
        }
        $('#abdmStatusBox').removeClass('text-danger').addClass('text-muted').text('Loading ABDM records...');

        fetch(url, { credentials: 'same-origin' })
            .then(function(resp) { return resp.json(); })
            .then(function(data) {
                if (!data || data.ok !== 1) {
                    throw new Error((data && data.error) || 'Unable to load ABDM records.');
                }
                currentAbdmRows = Array.isArray(data.items) ? data.items : [];
                renderAbdmRows(currentAbdmRows);
                $('#abdmStatusBox').removeClass('text-danger').addClass('text-muted').text('Loaded ' + currentAbdmRows.length + ' fetched ABDM record(s).');
            })
            .catch(function(err) {
                $('#abdmStatusBox').removeClass('text-muted').addClass('text-danger').text('ABDM load failed: ' + (err.message || err));
            });
    }

    function setFlowProgress(step) {
        var current = Number(step || 0);
        if (current < 0) {
            current = 0;
        }
        if (current > 3) {
            current = 3;
        }

        var width = 0;
        if (current === 1) {
            width = 34;
        } else if (current === 2) {
            width = 67;
        } else if (current >= 3) {
            width = 100;
        }

        $('#abdmFlowProgressBar').css('width', width + '%');

        $('#abdmFlowSteps .abdm-flow-step').each(function() {
            var $el = $(this);
            var stepNo = Number($el.data('step') || 0);
            $el.removeClass('is-active is-done');
            if (stepNo < current) {
                $el.addClass('is-done');
            } else if (stepNo === current && current > 0) {
                $el.addClass('is-active');
            }
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
                var info = msg !== '' ? msg : ('Phase: ' + (phase || 'UNKNOWN'));

                if (phase === 'COMPLETED') {
                    setFlowProgress(3);
                } else if (phase === 'GRANTED') {
                    setFlowProgress(2);
                } else if (phase === 'REQUESTED' || phase === 'PENDING') {
                    setFlowProgress(1);
                }

                $('#abdmStatusBox').removeClass('text-danger').addClass('text-muted').text(info + (autoFlowRequestId ? (' | Request ID: ' + autoFlowRequestId) : ''));

                var shouldPoll = Number(data.poll_again || 0) === 1;
                if (shouldPoll && autoFlowAttempts < autoFlowMaxAttempts) {
                    autoFlowAttempts += 1;
                    autoFlowTimer = setTimeout(runAutoFlowStep, 8000);
                    return;
                }

                $('#btnAutoAbdmFlow').prop('disabled', false).text('One-Click Sync');
                if (phase === 'COMPLETED') {
                    loadAbdmDocs();
                }
            })
            .catch(function(err) {
                stopAutoFlowLoop();
                $('#btnAutoAbdmFlow').prop('disabled', false).text('One-Click Sync');
                $('#abdmStatusBox').removeClass('text-muted').addClass('text-danger').text('Auto flow failed: ' + (err.message || err));
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

    $(document).off('click.abdmOpd', '#btnAutoAbdmFlow').on('click.abdmOpd', '#btnAutoAbdmFlow', function() {
        var $btn = $(this);
        if ($btn.prop('disabled')) {
            return;
        }
        stopAutoFlowLoop();
        autoFlowRequestId = '';
        autoFlowAttempts = 0;
        setFlowProgress(1);
        $btn.prop('disabled', true).text('Sync Running...');
        $('#abdmStatusBox').removeClass('text-danger').addClass('text-muted').text('Starting one-click sync flow...');
        runAutoFlowStep();
    });

    $(document).off('shown.bs.tab.abdmOpd', '[data-bs-target="#opd-abdm-tab"]').on('shown.bs.tab.abdmOpd', '[data-bs-target="#opd-abdm-tab"]', function() {
        if (!currentAbdmRows.length) {
            loadAbdmDocs();
        }
    });
});
</script>
