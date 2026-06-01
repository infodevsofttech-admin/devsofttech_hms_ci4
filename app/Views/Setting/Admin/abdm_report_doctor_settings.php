<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">
            <i class="bi bi-person-vcard me-2 text-primary"></i>ABDM Report Doctor Mapping
        </h3>
        <span class="badge bg-primary">Lab & Radiology</span>
    </div>
    <div class="card-body">
        <?= csrf_field() ?>

        <div class="alert alert-info py-2 mb-3" style="font-size:13px;">
            Select which doctor should be used in FHIR <code>Practitioner</code> for each report modality.
            If a modality-specific doctor is not found, system falls back to default resolution.
        </div>

        <?php if (empty($doctors ?? [])) : ?>
            <div class="alert alert-warning">No doctors found in Doctor Master. Add doctors first, then map them here.</div>
        <?php else : ?>
            <div class="row g-3">
                <?php foreach (($mapping_keys ?? []) as $key => $label) : ?>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><?= esc($label) ?></label>
                        <select class="form-select abdm-doc-map" id="<?= esc($key) ?>" data-key="<?= esc($key) ?>">
                            <option value="">-- Not mapped --</option>
                            <?php foreach (($doctors ?? []) as $doc) : ?>
                                <option value="<?= (int) ($doc['id'] ?? 0) ?>" <?= ((int) (($selected[$key] ?? 0)) === (int) ($doc['id'] ?? 0)) ? 'selected' : '' ?>>
                                    <?= esc((string) ($doc['label'] ?? ('Doctor #' . (int) ($doc['id'] ?? 0)))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="mt-3 d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-primary" id="btn_save_abdm_doc_map">
                <i class="bi bi-save me-1"></i>Save Mapping
            </button>
        </div>

        <div id="abdm_doc_map_msg" class="mt-3"></div>
    </div>
</div>

<script>
(function () {
    'use strict';

    function getCsrfPair() {
        var input = document.querySelector('input[name="<?= csrf_token() ?>"]');
        if (!input) {
            return { name: '<?= csrf_token() ?>', value: '<?= csrf_hash() ?>' };
        }
        return { name: input.getAttribute('name'), value: input.value };
    }

    function updateCsrf(data) {
        if (!data || !data.csrfName || !data.csrfHash) return;
        var input = document.querySelector('input[name="' + data.csrfName + '"]');
        if (input) input.value = data.csrfHash;
    }

    function showMsg(type, html) {
        var cls = type === 'success' ? 'alert alert-success' : 'alert alert-danger';
        $('#abdm_doc_map_msg').html('<div class="' + cls + ' py-2">' + html + '</div>');
    }

    function collectPayload() {
        var payload = {};
        document.querySelectorAll('.abdm-doc-map').forEach(function (el) {
            payload[el.getAttribute('data-key')] = (el.value || '').trim();
        });
        return payload;
    }

    $('#btn_save_abdm_doc_map').on('click', function () {
        var payload = collectPayload();
        var csrf = getCsrfPair();
        payload[csrf.name] = csrf.value;

        var $btn = $(this).prop('disabled', true).text('Saving...');
        $('#abdm_doc_map_msg').html('');

        $.post('<?= base_url('setting/admin/abdm-report-doctors/save') ?>', payload, function (res) {
            updateCsrf(res);
            if (res && parseInt(res.update || '0', 10) === 1) {
                showMsg('success', '<i class="bi bi-check-circle me-1"></i>' + (res.error_text || 'Saved.'));
            } else {
                showMsg('danger', '<i class="bi bi-x-circle me-1"></i>' + ((res && res.error_text) ? res.error_text : 'Save failed.'));
            }
            $btn.prop('disabled', false).html('<i class="bi bi-save me-1"></i>Save Mapping');
        }, 'json').fail(function () {
            showMsg('danger', '<i class="bi bi-x-circle me-1"></i>Save failed.');
            $btn.prop('disabled', false).html('<i class="bi bi-save me-1"></i>Save Mapping');
        });
    });
})();
</script>
