<?php
$ipdId = (int) ($ipd_id ?? 0);
$ipdCode = (string) ($ipd_info->ipd_code ?? ('IPD-' . $ipdId));

$documents = is_array($documents ?? null) ? $documents : [];
?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <h6 class="mb-0">Pre-Print Documents</h6>
            <div class="d-flex align-items-center gap-2">
                <small class="text-muted">IPD: <?= esc($ipdCode) ?></small>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="load_form_div('<?= base_url('setting/template/ipd_document_templates') ?>','maindiv','IPD Document Master');">
                    <i class="bi bi-pencil-square"></i> Document Master
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-0">
                <thead>
                <tr>
                    <th style="width: 80px;">Form ID</th>
                    <th>Document</th>
                    <th style="width: 170px;" class="text-end">Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($documents)) : ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted py-3">No active document template found. Add templates from Document Master.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($documents as $document) : ?>
                    <tr>
                        <td><?= (int) $document['id'] ?></td>
                        <td><?= esc($document['label']) ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" target="_blank" href="<?= site_url('IpdNew/show_ipd_form/' . $ipdId . '/' . (int) $document['id']) ?>">
                                <i class="bi bi-printer"></i> Print
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
