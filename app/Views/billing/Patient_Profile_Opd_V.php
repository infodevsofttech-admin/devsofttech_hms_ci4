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
        <div class="card-header">
            <h3 class="card-title mb-0">OPD History</h3>
        </div>
        <div class="card-body">
            <p class="mb-3">Patient: <strong><?= esc($patient->p_fname ?? '') ?></strong></p>

            <?php if (empty($opdGroups)) { ?>
                <div class="alert alert-info mb-0">No OPD scans found.</div>
            <?php } else { ?>
                <?php foreach ($opdGroups as $group) { ?>
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex flex-wrap justify-content-between">
                            <div>
                                <strong><?= esc($group['opd_code']) ?></strong>
                                <span class="text-muted ms-2">Dr. <?= esc($group['doc_name']) ?></span>
                            </div>
                            <div class="text-muted">
                                <?= esc($group['opd_date']) ?> <?= esc($group['queue_no']) ?>
                            </div>
                        </div>

                        <div class="mt-2">
                            <?php if ((int) ($group['rx_session_id'] ?? 0) > 0) { ?>
                                <a class="btn btn-primary btn-sm" target="_blank"
                                   href="<?= base_url('Opd_prescription/opd_prescription_print/' . (int) $group['opd_id'] . '/' . (int) $group['rx_session_id']) ?>">
                                    Prescription Print
                                </a>
                            <?php } ?>

                            <?php if (!empty($group['bp']) || !empty($group['diastolic']) || !empty($group['pulse']) || !empty($group['temp']) || !empty($group['spo2'])) { ?>
                                <div class="small text-muted mt-2">
                                    <?php if (!empty($group['bp'])) { ?><span class="me-3"><strong>BP:</strong> <?= esc($group['bp']) ?></span><?php } ?>
                                    <?php if (!empty($group['diastolic'])) { ?><span class="me-3"><strong>Diastolic:</strong> <?= esc($group['diastolic']) ?></span><?php } ?>
                                    <?php if (!empty($group['pulse'])) { ?><span class="me-3"><strong>Pulse:</strong> <?= esc($group['pulse']) ?></span><?php } ?>
                                    <?php if (!empty($group['temp'])) { ?><span class="me-3"><strong>Temp:</strong> <?= esc($group['temp']) ?></span><?php } ?>
                                    <?php if (!empty($group['spo2'])) { ?><span class="me-3"><strong>SPO2:</strong> <?= esc($group['spo2']) ?></span><?php } ?>
                                </div>
                            <?php } ?>

                            <?php if (!empty($group['complaints']) || !empty($group['diagnosis']) || !empty($group['investigation']) || !empty($group['advice'])) { ?>
                                <div class="row g-2 mt-2">
                                    <?php if (!empty($group['complaints'])) { ?>
                                        <div class="col-12 col-lg-6">
                                            <div class="border rounded p-2 bg-light small">
                                                <strong>Complaints:</strong> <?= esc($group['complaints']) ?>
                                            </div>
                                        </div>
                                    <?php } ?>
                                    <?php if (!empty($group['diagnosis'])) { ?>
                                        <div class="col-12 col-lg-6">
                                            <div class="border rounded p-2 bg-light small">
                                                <strong>Diagnosis:</strong> <?= esc($group['diagnosis']) ?>
                                            </div>
                                        </div>
                                    <?php } ?>
                                    <?php if (!empty($group['investigation'])) { ?>
                                        <div class="col-12 col-lg-6">
                                            <div class="border rounded p-2 bg-light small">
                                                <strong>Investigation:</strong> <?= esc($group['investigation']) ?>
                                            </div>
                                        </div>
                                    <?php } ?>
                                    <?php if (!empty($group['advice'])) { ?>
                                        <div class="col-12 col-lg-6">
                                            <div class="border rounded p-2 bg-light small">
                                                <strong>Advice:</strong> <?= esc($group['advice']) ?>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </div>

                        <?php if (empty($group['files'])) { ?>
                            <div class="text-muted mt-2">No scanned files for this OPD.</div>
                        <?php } else { ?>
                            <div class="small fw-semibold mt-3 mb-1">Scanned Files</div>
                            <div class="row g-2 mt-2">
                                <?php foreach ($group['files'] as $index => $file) { ?>
                                    <div class="col-6 col-md-3">
                                        <?php if ($file['isPdf']) { ?>
                                            <a class="btn btn-outline-secondary btn-sm w-100" href="<?= esc($file['path']) ?>" target="_blank">Open PDF</a>
                                        <?php } else { ?>
                                            <img src="<?= esc($file['path']) ?>" class="img-fluid rounded border opd-thumb"
                                                data-bs-toggle="modal" data-bs-target="#opdScanModal"
                                                data-src="<?= esc($file['path']) ?>" alt="OPD Scan">
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } ?>
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
