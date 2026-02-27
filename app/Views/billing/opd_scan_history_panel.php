<?php if (empty($scan_history_items ?? [])) { ?>
    <div class="text-muted small">No scan history found for this patient.</div>
<?php } else { ?>
    <div class="rx-history-list d-grid gap-2">
        <?php foreach (($scan_history_items ?? []) as $scan) { ?>
            <div class="rx-history-item border rounded p-2" data-file-id="<?= (int) ($scan['id'] ?? 0) ?>">
                <div class="small mb-1 text-muted d-flex justify-content-between">
                    <span><strong><?= esc((string) (($scan['opd_code'] ?? '') !== '' ? $scan['opd_code'] : ('OPD #' . ((int) ($scan['opd_id'] ?? 0)))) ) ?></strong> <?= esc((string) ($scan['opd_date'] ?? '')) ?></span>
                    <span><?= esc((string) ($scan['insert_date'] ?? '')) ?></span>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <div class="rx-history-image-wrap">
                        <?php if (!empty($scan['is_pdf'])) { ?>
                            <a class="btn btn-outline-secondary btn-sm w-100" target="_blank" href="<?= esc((string) ($scan['path'] ?? '')) ?>">Open PDF</a>
                        <?php } else { ?>
                            <a target="_blank" href="<?= esc((string) ($scan['path'] ?? '')) ?>">
                                <img src="<?= esc((string) ($scan['path'] ?? '')) ?>" loading="lazy" decoding="async" class="rx-history-preview" alt="history scan">
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-1 w-100 btn-history-toggle-image">Expand</button>
                        <?php } ?>
                    </div>

                    <div class="flex-grow-1 small" style="min-width:200px;">
                        <div class="mb-1">
                            <span class="badge bg-light text-dark border js-history-doc-type"><?= esc((string) (($scan['document_type'] ?? '') !== '' ? $scan['document_type'] : (($scan['scan_type'] ?? '') !== '' ? $scan['scan_type'] : 'General'))) ?></span>
                            <?php $status = strtolower((string) ($scan['ai_status'] ?? '')); ?>
                            <?php if ($status === 'processing' || $status === 'pending') { ?>
                                <span class="badge bg-warning text-dark js-history-ai-status">AI <?= esc(ucfirst($status)) ?></span>
                            <?php } elseif ($status === 'completed') { ?>
                                <span class="badge bg-success js-history-ai-status">AI Ready</span>
                            <?php } elseif ($status === 'failed') { ?>
                                <span class="badge bg-danger js-history-ai-status">AI Failed</span>
                            <?php } else { ?>
                                <span class="badge bg-secondary js-history-ai-status">AI Not Run</span>
                            <?php } ?>
                        </div>

                        <div class="text-danger mb-1 js-history-alert <?= ((int) ($scan['ai_alert_flag'] ?? 0) === 1 && !empty($scan['ai_alert_text'])) ? '' : 'd-none' ?>">
                            ⚠ <?= esc((string) ($scan['ai_alert_text'] ?? '')) ?>
                        </div>

                        <div class="text-muted js-history-report" style="white-space:pre-line;line-height:1.2;">
                            <?= esc((string) ($scan['report_text'] ?? '')) ?>
                        </div>

                        <div class="mt-2 d-flex flex-wrap gap-1">
                            <button type="button" class="btn btn-sm btn-outline-primary btn-use-history-report" data-report="<?= esc((string) ($scan['report_text'] ?? '')) ?>">Use Report</button>
                            <button type="button" class="btn btn-sm btn-outline-success btn-history-run-ai" data-file-id="<?= (int) ($scan['id'] ?? 0) ?>">Run AI</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
<?php } ?>
