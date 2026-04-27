<?php
    $request = service('request');
    $defaultBackUrl = base_url('billing/patient/person_record') . '/' . (int) ($patient->id ?? 0) . '/0';
    $backUrl = trim((string) $request->getGet('back_url'));
    $backTitle = trim((string) $request->getGet('back_title'));

    if ($backUrl === '') {
        $backUrl = $defaultBackUrl;
    }
    if ($backTitle === '') {
        $backTitle = 'Profile';
    }
?>

<div class="pagetitle">
    <h1>Consult History</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="javascript:load_form('<?= esc($backUrl, 'js') ?>','<?= esc($backTitle, 'js') ?>');"><?= esc($backTitle) ?></a></li>
            <li class="breadcrumb-item active">Consult History</li>
        </ol>
    </nav>
</div>

<section class="section profile">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h3 class="card-title mb-0">Old Prescription With Scanned Records</h3>
                <div class="small text-muted mt-1">Patient: <?= esc($patient->p_fname ?? '') ?></div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="javascript:load_form('<?= esc($backUrl, 'js') ?>','<?= esc($backTitle, 'js') ?>');" class="btn btn-outline-secondary btn-sm">Back</a>
                <span class="badge bg-secondary"><?= count($opdGroups ?? []) ?> OPD Record(s)</span>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($opdGroups)) { ?>
                <div class="alert alert-info mb-0">No OPD history found.</div>
            <?php } else { ?>
                <?php foreach ($opdGroups as $group) { ?>
                    <article class="border rounded p-3 mb-4 bg-white shadow-sm">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                            <div>
                                <div class="fw-semibold">Dr. <?= esc($group['doc_name'] ?? '') ?></div>
                                <div class="small text-muted">
                                    <strong>OPD ID:</strong> <?= esc($group['opd_code'] ?? '') ?>
                                    <span class="ms-2"><strong>Date:</strong> <?= esc($group['opd_date'] ?? '') ?></span>
                                    <?php if (!empty($group['queue_no'])) { ?><span class="ms-2"><strong><?= esc($group['queue_no']) ?></strong></span><?php } ?>
                                </div>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <?php if ((int) ($group['rx_session_id'] ?? 0) > 0) { ?>
                                    <a class="btn btn-outline-primary btn-sm" target="_blank"
                                       href="<?= base_url('Opd_prescription/opd_prescription_print/' . (int) $group['opd_id'] . '/' . (int) $group['rx_session_id']) ?>">
                                        Prescription Print
                                    </a>
                                <?php } ?>
                            </div>
                        </div>

                        <?php if (!empty($group['bp']) || !empty($group['diastolic']) || !empty($group['pulse']) || !empty($group['temp']) || !empty($group['spo2'])) { ?>
                            <div class="small text-dark mb-2">
                                <?php if (!empty($group['bp'])) { ?><span class="me-3"><strong>BP:</strong> <?= esc($group['bp']) ?></span><?php } ?>
                                <?php if (!empty($group['diastolic'])) { ?><span class="me-3"><strong>Diastolic:</strong> <?= esc($group['diastolic']) ?></span><?php } ?>
                                <?php if (!empty($group['pulse'])) { ?><span class="me-3"><strong>Pulse:</strong> <?= esc($group['pulse']) ?></span><?php } ?>
                                <?php if (!empty($group['temp'])) { ?><span class="me-3"><strong>Temp:</strong> <?= esc($group['temp']) ?></span><?php } ?>
                                <?php if (!empty($group['spo2'])) { ?><span class="me-3"><strong>SPO2:</strong> <?= esc($group['spo2']) ?></span><?php } ?>
                            </div>
                        <?php } ?>

                        <?php if (!empty($group['complaints']) || !empty($group['diagnosis']) || !empty($group['investigation']) || !empty($group['advice']) || !empty($group['next_visit']) || !empty($group['refer_to'])) { ?>
                            <div class="small text-dark mb-3">
                                <?php if (!empty($group['complaints'])) { ?><div><strong>Complaint:</strong> <?= esc($group['complaints']) ?></div><?php } ?>
                                <?php if (!empty($group['diagnosis'])) { ?><div><strong>Diagnosis:</strong> <?= esc($group['diagnosis']) ?></div><?php } ?>
                                <?php if (!empty($group['investigation'])) { ?><div><strong>Investigation Advised:</strong> <?= esc($group['investigation']) ?></div><?php } ?>
                                <?php if (!empty($group['advice'])) { ?><div><strong>Advice:</strong> <?= esc($group['advice']) ?></div><?php } ?>
                                <?php if (!empty($group['next_visit'])) { ?><div><strong>Next Visit:</strong> <?= esc($group['next_visit']) ?></div><?php } ?>
                                <?php if (!empty($group['refer_to'])) { ?><div><strong>Refer To:</strong> <?= esc($group['refer_to']) ?></div><?php } ?>
                            </div>
                        <?php } ?>

                        <?php if (!empty($group['medicines'])) { ?>
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-bordered align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Prescribed</th>
                                            <th>Dose</th>
                                            <th>Timing - Freq. - Route - Duration</th>
                                            <th>Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($group['medicines'] as $medicine) { ?>
                                            <tr>
                                                <td>
                                                    <?= esc(trim(($medicine['med_type'] ?? '') . ' ' . ($medicine['med_name'] ?? ''))) ?>
                                                    <?php if (!empty($medicine['remark'])) { ?>
                                                        <div class="small text-muted mt-1"><?= esc($medicine['remark']) ?></div>
                                                    <?php } ?>
                                                </td>
                                                <td><?= esc($medicine['dose'] ?? '') ?></td>
                                                <td>
                                                    <?= esc(implode(' - ', array_values(array_filter([
                                                        $medicine['timing'] ?? '',
                                                        $medicine['frequency'] ?? '',
                                                        $medicine['where'] ?? '',
                                                        $medicine['days'] ?? '',
                                                    ], static fn($value): bool => trim((string) $value) !== '')))) ?>
                                                </td>
                                                <td><?= esc($medicine['qty'] ?? '') ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } ?>

                        <?php if (empty($group['files'])) { ?>
                            <div class="text-muted">No scanned files for this OPD.</div>
                        <?php } else { ?>
                            <div class="fw-semibold mb-2">Scanned Files</div>
                            <?php foreach ($group['files'] as $file) { ?>
                                <div class="mb-3">
                                    <?php if ($file['isPdf']) { ?>
                                        <div class="d-flex gap-2 flex-wrap mb-2">
                                            <a class="btn btn-outline-secondary btn-sm" href="<?= esc($file['path']) ?>" target="_blank">Open PDF</a>
                                        </div>
                                        <embed src="<?= esc($file['path']) ?>" type="application/pdf" width="100%" height="900px" class="border rounded">
                                    <?php } else { ?>
                                        <img src="<?= esc($file['path']) ?>" class="img-fluid rounded border opd-thumb"
                                            data-bs-toggle="modal" data-bs-target="#opdScanModal"
                                            data-src="<?= esc($file['path']) ?>" alt="OPD Scan">
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        <?php } ?>
                    </article>
                <?php } ?>
            <?php } ?>
        </div>
    </div>

    <div class="modal fade" id="opdScanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Consult History</h5>
                    <div class="ms-auto d-flex gap-2 align-items-center flex-wrap justify-content-end">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="opdScanZoomOut">-</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="opdScanZoomIn">+</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="opdScanRotate">Rotate</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="opdScanZoomReset">Reset</button>
                        <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">Close X</button>
                    </div>
                </div>
                <div class="modal-body pb-0">
                    <div class="small text-muted text-end mb-2">
                        Keys: + or = (Zoom In), - (Zoom Out), R (Rotate), 0 (Reset) | Mouse: Wheel to zoom, drag image to pan
                    </div>
                </div>
                <div class="modal-body text-center">
                    <div class="overflow-auto" id="opdScanViewport" style="max-height:75vh;">
                        <img id="opdScanModalImg" class="img-fluid" alt="OPD Scan" style="transform-origin:center center; transition:transform 0.08s linear; cursor:grab; user-select:none;">
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
$(function() {
    var currentZoom = 1;
    var minZoom = 0.5;
    var maxZoom = 4;
    var zoomStep = 0.25;
    var currentRotate = 0;
    var panX = 0;
    var panY = 0;
    var isDragging = false;
    var startX = 0;
    var startY = 0;

    var $img = $('#opdScanModalImg');
    var $viewport = $('#opdScanViewport');

    function applyTransform() {
        $img.css('transform', 'translate(' + panX + 'px, ' + panY + 'px) rotate(' + currentRotate + 'deg) scale(' + currentZoom + ')');
    }

    function applyZoom() {
        applyTransform();
    }

    function resetView() {
        currentZoom = 1;
        currentRotate = 0;
        panX = 0;
        panY = 0;
        applyTransform();
    }

    $('#opdScanZoomIn').on('click', function() {
        currentZoom = Math.min(maxZoom, currentZoom + zoomStep);
        applyTransform();
    });

    $('#opdScanZoomOut').on('click', function() {
        currentZoom = Math.max(minZoom, currentZoom - zoomStep);
        applyTransform();
    });

    $('#opdScanRotate').on('click', function() {
        currentRotate = (currentRotate + 90) % 360;
        applyTransform();
    });

    $('#opdScanZoomReset').on('click', function() {
        resetView();
    });

    $viewport.on('wheel', function(event) {
        event.preventDefault();
        var originalEvent = event.originalEvent;
        if (!originalEvent) {
            return;
        }

        if (originalEvent.deltaY < 0) {
            currentZoom = Math.min(maxZoom, currentZoom + zoomStep);
        } else {
            currentZoom = Math.max(minZoom, currentZoom - zoomStep);
        }
        applyTransform();
    });

    $img.on('mousedown', function(event) {
        if (event.which !== 1) {
            return;
        }

        isDragging = true;
        startX = event.clientX - panX;
        startY = event.clientY - panY;
        $img.css('cursor', 'grabbing');
        event.preventDefault();
    });

    $(document).on('mousemove.opdScanPan', function(event) {
        if (!isDragging) {
            return;
        }

        panX = event.clientX - startX;
        panY = event.clientY - startY;
        applyTransform();
    });

    $(document).on('mouseup.opdScanPan', function() {
        if (!isDragging) {
            return;
        }
        isDragging = false;
        $img.css('cursor', 'grab');
    });

    $(document).on('keydown.opdScanHotkeys', function(event) {
        if (!$('#opdScanModal').hasClass('show')) {
            return;
        }

        var key = String(event.key || '').toLowerCase();
        if (key === '+' || key === '=' || key === 'add') {
            event.preventDefault();
            currentZoom = Math.min(maxZoom, currentZoom + zoomStep);
            applyTransform();
            return;
        }

        if (key === '-' || key === '_' || key === 'subtract') {
            event.preventDefault();
            currentZoom = Math.max(minZoom, currentZoom - zoomStep);
            applyTransform();
            return;
        }

        if (key === 'r') {
            event.preventDefault();
            currentRotate = (currentRotate + 90) % 360;
            applyTransform();
            return;
        }

        if (key === '0') {
            event.preventDefault();
            resetView();
        }
    });

    $('#opdScanModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var src = button.data('src');
        $img.attr('src', src);
        resetView();
    });

    $('#opdScanModal').on('hidden.bs.modal', function() {
        $img.attr('src', '');
        resetView();
    });
});
</script>
