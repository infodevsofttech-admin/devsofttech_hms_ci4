<?php
$patient = $patient ?? (object) [];
$opdGroups = (isset($opdGroups) && is_array($opdGroups)) ? $opdGroups : [];
$backUrl = trim((string) ($backUrl ?? ''));
$backTitle = trim((string) ($backTitle ?? 'Profile'));
if ($backUrl === '') {
    $backUrl = base_url('billing/patient/person_record') . '/' . (int) ($patient->id ?? 0) . '/0';
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
            <p class="mb-3">Patient: <strong><?= esc($patient->p_fname ?? '') ?></strong></p>

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
                    width: 100%;
                    height: auto;
                    object-fit: contain;
                    cursor: zoom-in;
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
    </div>

    <div class="modal fade" id="opdScanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">OPD Scan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="opdScanModalImg" class="img-fluid" alt="OPD Scan">
                </div>
            </div>
        </div>
    </div>
</section>

<script>
$(function() {
    $('#opdScanModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var src = button.data('src');
        $('#opdScanModalImg').attr('src', src);
    });
});
</script>
