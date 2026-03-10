<?php
$fileRows = is_array($files ?? null) ? $files : [];
$studyName = trim((string) ($study_name ?? ''));
?>

<div class="mb-3">
    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
        <div>
            <h6 class="mb-1">Uploaded Images</h6>
            <?php if ($studyName !== ''): ?>
                <div class="small text-muted">Study: <?= esc($studyName) ?></div>
            <?php endif; ?>
        </div>
        <div class="small text-muted">Latest uploads for this imaging study</div>
    </div>
</div>

<?php if ($fileRows === []): ?>
    <div class="alert alert-warning mb-0">No uploaded images or PDFs found for this study.</div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($fileRows as $row): ?>
            <div class="col-md-6 col-xl-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <strong class="small"><?= esc((string) ($row['name'] ?? 'Uploaded file')) ?></strong>
                            <?php if (!empty($row['ai_status'])): ?>
                                <span class="badge bg-light text-dark border"><?= esc((string) $row['ai_status']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="small text-muted mb-2">
                            <?= esc((string) ($row['desc'] ?? '')) ?>
                            <?php if (!empty($row['scan_type'])): ?>
                                | <?= esc((string) $row['scan_type']) ?>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($row['url']) && !empty($row['is_image'])): ?>
                            <a href="<?= esc((string) $row['url']) ?>" target="_blank" rel="noopener">
                                <img src="<?= esc((string) $row['url']) ?>" alt="Uploaded image" class="img-fluid rounded border" style="width:100%; max-height:220px; object-fit:cover;">
                            </a>
                        <?php elseif (!empty($row['url']) && !empty($row['is_pdf'])): ?>
                            <div class="border rounded p-3 text-center bg-light">
                                <div class="display-6 text-danger"><i class="bi bi-file-earmark-pdf"></i></div>
                                <a href="<?= esc((string) $row['url']) ?>" target="_blank" rel="noopener">Open PDF</a>
                            </div>
                        <?php else: ?>
                            <div class="border rounded p-3 text-muted bg-light">Preview unavailable</div>
                        <?php endif; ?>

                        <div class="mt-2 small text-muted">
                            Uploaded: <?= esc((string) ($row['insert_time'] ?? '')) ?>
                        </div>

                        <?php if (!empty($row['ai_alert_text'])): ?>
                            <div class="mt-2 small <?= !empty($row['ai_status']) && $row['ai_status'] === 'failed' ? 'text-danger' : 'text-muted' ?>">
                                <?= esc((string) $row['ai_alert_text']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>