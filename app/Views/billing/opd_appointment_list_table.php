<?php
$canOpenPrescription = false;
$authUser = function_exists('auth') ? auth()->user() : null;
if ($authUser) {
    if (method_exists($authUser, 'can') && $authUser->can('opd.doctor-panel.access')) {
        $canOpenPrescription = true;
    }
}
?>
<div class="table-responsive">
    <table class="table table-sm table-striped align-middle mb-0">
        <thead>
        <tr>
            <th>OPD No.</th>
            <th>Current Patient</th>
            <?php if (!empty($showQueue)) : ?><th>Q No.</th><?php endif; ?>
            <th>UHID</th>
            <th>OPD Type</th>
            <th width="620">Action</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($rows)) : ?>
            <?php foreach ($rows as $row) : ?>
                <tr data-opd-id="<?= esc((int) ($row->opd_id ?? 0)) ?>" data-has-vitals="<?= esc((int) ($row->has_vitals ?? 0)) ?>">
                    <td><?= esc($row->opd_code ?? '') ?></td>
                    <td><?= esc(($row->P_name ?? '') . ' { ' . ($row->p_rname ?? '') . ' }') ?></td>
                    <?php if (!empty($showQueue)) : ?><td><?= esc((int) ($row->queue_no ?? 0)) ?></td><?php endif; ?>
                    <td><?= esc($row->p_code ?? '') ?></td>
                    <td><?= esc($row->opd_type ?? '') ?> / Amt: <?= esc($row->opd_fee_amount ?? '') ?></td>
                    <td>
                        <?php $hasVitals = isset($row->has_vitals) && (int) $row->has_vitals === 1; ?>
                        <?php if ($canOpenPrescription) : ?>
                            <a class="btn btn-outline-primary btn-sm" title="Consult" href="javascript:load_form('/Opd_prescription/Prescription/<?= esc((int) ($row->opd_id ?? 0)) ?>','Consult');">
                                Consult
                            </a>
                        <?php endif; ?>
                        <button type="button" class="btn <?= $hasVitals ? 'btn-success' : 'btn-warning text-dark' ?> btn-sm btn-opd-vitals" title="<?= $hasVitals ? 'Vitals Filled' : 'Vitals' ?>" data-opdid="<?= esc((int) ($row->opd_id ?? 0)) ?>" data-patient="<?= esc(($row->P_name ?? '') . ' { ' . ($row->p_rname ?? '') . ' }') ?>">
                            <?= $hasVitals ? 'Vitals ✓' : 'Vitals' ?>
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm btn-opd-scan" title="Scan" data-opdid="<?= esc((int) ($row->opd_id ?? 0)) ?>">
                            Scan
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm btn-opd-scan" title="Upload Scan" data-opdid="<?= esc((int) ($row->opd_id ?? 0)) ?>">
                            Upload
                        </button>
                        <button type="button" class="btn btn-outline-dark btn-sm btn-opd-scan-list" title="Scan Document List" data-opdid="<?= esc((int) ($row->opd_id ?? 0)) ?>">
                            Scan Doc List
                        </button>
                        <?php if (($tabType ?? '') === 'waiting') : ?>
                            <button type="button" class="btn btn-outline-success btn-sm btn-opd-status" data-opd-id="<?= esc((int) ($row->opd_id ?? 0)) ?>" data-opd-status="2" title="Visit Done">
                                Visit Done
                            </button>
                        <?php elseif (($tabType ?? '') === 'visited') : ?>
                            <button type="button" class="btn btn-success btn-sm btn-opd-status" data-opd-id="<?= esc((int) ($row->opd_id ?? 0)) ?>" data-opd-status="2" title="Visit Done">
                                Visit Done
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr><td colspan="<?= !empty($showQueue) ? '6' : '5' ?>" class="text-muted">No records.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>


