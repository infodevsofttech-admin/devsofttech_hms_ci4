<?php $items = $slides ?? []; ?>
<?php if (empty($items)) : ?>
    <div class="text-muted">No scan documents found.</div>
<?php else : ?>
    <div id="opdScanCarousel" class="carousel slide" data-bs-ride="false">
        <div class="carousel-indicators">
            <?php foreach ($items as $index => $item) : ?>
                <button type="button" data-bs-target="#opdScanCarousel" data-bs-slide-to="<?= (int) $index ?>" class="<?= $index === 0 ? 'active' : '' ?>" <?= $index === 0 ? 'aria-current="true"' : '' ?> aria-label="Slide <?= (int) ($index + 1) ?>"></button>
            <?php endforeach; ?>
        </div>

        <div class="carousel-inner bg-light rounded border" style="min-height:420px;">
            <?php foreach ($items as $index => $item) : ?>
                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>" style="padding:14px;">
                    <div class="text-center">
                        <?php if (!empty($item['is_pdf'])) : ?>
                            <iframe src="<?= esc($item['path']) ?>" style="width:100%;height:460px;border:1px solid #dee2e6;"></iframe>
                            <div class="mt-2"><a class="btn btn-outline-secondary btn-sm" target="_blank" href="<?= esc($item['path']) ?>">Open PDF in new tab</a></div>
                        <?php else : ?>
                            <img src="<?= esc($item['path']) ?>" alt="Scan Document" class="img-fluid rounded" style="max-height:520px;">
                        <?php endif; ?>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <div class="small text-muted">
                            Uploaded: <?= esc($item['insert_date'] ?? '') ?>
                        </div>
                        <?php if ((int) ($item['can_delete_today'] ?? 0) === 1) : ?>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteOpdScanFromList(<?= (int) ($item['id'] ?? 0) ?>, <?= (int) ($opdid ?? 0) ?>)">Delete</button>
                        <?php else : ?>
                            <span class="badge bg-secondary">Delete allowed only for today upload</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <button class="carousel-control-prev" type="button" data-bs-target="#opdScanCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#opdScanCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>
<?php endif; ?>
