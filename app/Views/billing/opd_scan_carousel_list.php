<?php $items = $slides ?? []; ?>
<?php $hasMultipleSlides = count($items) > 1; ?>
<?php if (empty($items)) : ?>
    <div class="text-muted">No scan documents found.</div>
<?php else : ?>
    <div id="opdScanCarousel" class="carousel slide" data-bs-ride="false">
        <?php if ($hasMultipleSlides) : ?>
            <div class="carousel-indicators">
                <?php foreach ($items as $index => $item) : ?>
                    <button type="button" data-bs-target="#opdScanCarousel" data-bs-slide-to="<?= (int) $index ?>" class="<?= $index === 0 ? 'active' : '' ?>" <?= $index === 0 ? 'aria-current="true"' : '' ?> aria-label="Slide <?= (int) ($index + 1) ?>"></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

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

                    <div class="d-flex justify-content-between align-items-center mt-2" style="position:relative;z-index:3;">
                        <div class="small text-muted">
                            Uploaded: <?= esc($item['insert_date'] ?? '') ?>
                        </div>
                        <?php if ((int) ($item['can_delete_limited'] ?? $item['can_delete_today'] ?? 0) === 1) : ?>
                            <button
                                type="button"
                                class="btn btn-outline-danger btn-sm js-opd-scan-delete"
                                data-file-id="<?= (int) ($item['id'] ?? 0) ?>"
                                data-opd-id="<?= (int) ($opdid ?? 0) ?>"
                            >Delete</button>
                        <?php else : ?>
                            <span class="badge bg-secondary">Delete: within 24h or same date</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($hasMultipleSlides) : ?>
            <button class="carousel-control-prev" type="button" data-bs-target="#opdScanCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#opdScanCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        <?php endif; ?>
    </div>
<?php endif; ?>

<script>
(function() {
    if (typeof window.jQuery === 'undefined') {
        return;
    }

    var $ = window.jQuery;
    var csrfName = '<?= csrf_token() ?>';
    var csrfHash = '<?= csrf_hash() ?>';
    var baseUrl = '<?= rtrim(base_url('/'), '/') ?>/';

    function getCsrfPayload() {
        var payload = {};
        var $input = $('input[name="' + csrfName + '"]').first();
        if ($input.length && $input.val()) {
            csrfHash = $input.val();
        }
        payload[csrfName] = csrfHash;
        return payload;
    }

    function syncCsrf(resp) {
        if (!resp || !resp.csrfName || !resp.csrfHash) {
            return;
        }
        csrfName = resp.csrfName;
        csrfHash = resp.csrfHash;
        var $inputs = $('input[name="' + resp.csrfName + '"]');
        if ($inputs.length) {
            $inputs.val(resp.csrfHash);
        }
    }

    function findRefreshContainer($trigger) {
        var $panel = $('#testentry-bodyc');
        if ($panel.length) {
            return $panel;
        }
        var $modalBody = $trigger.closest('.modal-body');
        if ($modalBody.length) {
            return $modalBody;
        }
        return $trigger.closest('#opdScanCarousel').parent();
    }

    $(document)
        .off('click.opdScanDeleteInline', '.js-opd-scan-delete')
        .on('click.opdScanDeleteInline', '.js-opd-scan-delete', function() {
            var $btn = $(this);
            var fileId = parseInt($btn.attr('data-file-id') || '0', 10);
            var opdid = parseInt($btn.attr('data-opd-id') || '0', 10);
            if (!fileId || !opdid) {
                return;
            }

            if (!window.confirm('Delete this scan document?')) {
                return;
            }

            var payload = getCsrfPayload();
            $btn.prop('disabled', true);

            $.ajax({
                url: baseUrl + 'Opd/opd_file_delete/' + fileId,
                method: 'POST',
                dataType: 'json',
                data: payload,
            }).done(function(resp) {
                syncCsrf(resp || {});

                if (!resp || parseInt(resp.update || 0, 10) !== 1) {
                    var msg = (resp && resp.error_text) ? resp.error_text : 'Unable to delete document.';
                    window.alert(msg);
                    return;
                }

                var refreshPayload = getCsrfPayload();
                $.ajax({
                    url: baseUrl + 'Opd/opd_file_list/' + opdid,
                    method: 'POST',
                    data: refreshPayload,
                }).done(function(html) {
                    findRefreshContainer($btn).html(html || '<div class="text-muted">No scan documents found.</div>');
                }).fail(function() {
                    window.location.href = baseUrl + 'Opd/opd_file_list/' + opdid;
                });
            }).fail(function() {
                window.alert('Unable to delete document.');
            }).always(function() {
                $btn.prop('disabled', false);
            });
        });
})();
</script>
