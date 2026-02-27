<?php if (empty($opd_file_list ?? [])) { ?>
    <div class="text-muted small">No recent scans.</div>
<?php } else { ?>
    <div class="row g-2">
        <?php foreach ($opd_file_list as $item) { ?>
            <div class="col-6 col-md-4">
                <div class="border rounded p-1 h-100">
                    <?php if (!empty($item['is_pdf'])) { ?>
                        <a class="btn btn-outline-secondary btn-sm w-100" target="_blank" href="<?= esc($item['path']) ?>">Open PDF</a>
                    <?php } else { ?>
                        <a href="<?= esc($item['path']) ?>" target="_blank">
                            <img src="<?= esc($item['path']) ?>" loading="lazy" class="img-fluid rounded" style="width:100%;height:80px;object-fit:cover;">
                        </a>
                    <?php } ?>
                    <?php if ((int) ($item['ai_alert_flag'] ?? 0) === 1) { ?>
                        <div class="small mt-1 text-danger"><strong>⚠ Alert</strong> <?= esc($item['ai_alert_text'] ?? 'Abnormal report detected') ?></div>
                    <?php } ?>
                    <div class="small mt-1">
                        <span class="badge bg-light text-dark border"><?= esc($item['document_type'] ?? ($item['scan_type'] ?? 'General')) ?></span>
                        <?php $status = strtolower((string) ($item['ai_status'] ?? '')); ?>
                        <?php if ($status === 'processing' || $status === 'pending') { ?>
                            <span class="badge bg-warning text-dark">AI <?= esc(ucfirst($status)) ?></span>
                        <?php } elseif ($status === 'completed') { ?>
                            <span class="badge bg-success">AI Ready</span>
                        <?php } elseif ($status === 'failed') { ?>
                            <span class="badge bg-danger">AI Failed</span>
                        <?php } ?>
                    </div>
                    <?php if (!empty($item['content_description'])) { ?>
                        <div class="small text-muted mt-1" style="line-height:1.2"><?= nl2br(esc((string) ($item['content_description'] ?? ''))) ?></div>
                    <?php } ?>
                    <div class="small text-muted mt-1"><?= esc($item['insert_date'] ?? '') ?></div>
                    <button type="button" class="btn btn-link btn-sm text-danger p-0" onclick="removeOpdScanImage(<?= (int) ($item['id'] ?? 0) ?>)">Hide</button>
                </div>
            </div>
        <?php } ?>
    </div>
<?php } ?>
