<?php if (empty($files)) : ?>
    <div class="alert alert-info py-3 mb-0">
        <i class="bi bi-info-circle me-1"></i> No scanned copies or uploaded PDF documents found for this patient.
    </div>
<?php else : ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Document Name / Description</th>
                    <th>Type</th>
                    <th>Uploaded Date</th>
                    <th>Uploaded By</th>
                    <th class="text-end" style="width: 200px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($files as $idx => $file) : ?>
                    <tr>
                        <td><?= $idx + 1 ?></td>
                        <td>
                            <div class="fw-bold text-dark"><?= esc($file['title']) ?></div>
                            <small class="text-muted font-monospace"><?= esc(basename($file['path'])) ?></small>
                        </td>
                        <td>
                            <?php if ($file['is_pdf']) : ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</span>
                            <?php else : ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-file-earmark-image me-1"></i><?= esc(strtoupper($file['ext'] ?: 'IMAGE')) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= esc($file['insert_date'] ?: '-') ?></td>
                        <td><?= esc($file['uploaded_by'] ?: 'System') ?></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="<?= base_url(ltrim($file['path'], '/')) ?>" target="_blank" class="btn btn-outline-primary" title="View Document">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <button type="button" class="btn btn-outline-danger" onclick="deletePatientDoc(<?= (int)$file['id'] ?>, <?= (int)$pid ?>)" title="Delete Document">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
