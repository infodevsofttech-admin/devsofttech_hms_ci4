<?php
$ipd = $ipd_info ?? null;
$person = $person_info ?? null;
$ipdId = (int) ($ipd_id ?? 0);
$previewHtml = (string) ($content ?? '');
$noticeText = (string) ($notice ?? '');
$noticeType = (string) ($notice_type ?? 'success');

$patientName = trim((string) ($person->p_fname ?? ''));
$patientCode = trim((string) (
    $person->uhid
    ?? $person->UHID
    ?? $person->patient_code
    ?? $person->p_code
    ?? $person->reg_no
    ?? ''
));

$age = '';
if ($person) {
    $age = get_age_1($person->dob ?? null, $person->age ?? '', $person->age_in_month ?? '', $person->estimate_dob ?? '');
}
?>

<section class="content">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-light d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">Discharge Preview</h5>
            <div class="small text-muted">
                IPD: <strong><?= esc($ipd->ipd_code ?? $ipdId) ?></strong>
                <?php if ($patientName !== ''): ?>
                    | Patient: <strong><?= esc($patientName) ?></strong>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-body">
            <?php if ($noticeText !== ''): ?>
                <div class="alert alert-<?= esc($noticeType) ?> py-2" role="alert"><?= esc($noticeText) ?></div>
            <?php endif; ?>

            <div class="row g-2 mb-3 small">
                <div class="col-md-3"><strong>UHID:</strong> <?= esc($patientCode) ?></div>
                <div class="col-md-3"><strong>Age/Gender:</strong> <?= esc(trim($age . ' / ' . ($person->xgender ?? ''))) ?></div>
                <div class="col-md-3"><strong>Admit Date:</strong> <?= esc($ipd->str_register_date ?? '') ?></div>
                <div class="col-md-3"><strong>Discharge Date:</strong> <?= esc($ipd->str_discharge_date ?? '') ?></div>
            </div>

            <form method="post" action="<?= site_url('Ipd_discharge/preview_discharge_report/' . $ipdId) ?>">
                <?= csrf_field() ?>
                <div class="border rounded p-2 bg-white">
                    <textarea id="editor_Discharge_Preview" name="editor_Discharge_Preview" rows="16" class="form-control"><?= esc($previewHtml) ?></textarea>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="submit" class="btn btn-success">Save Preview</button>
                    <button type="button" class="btn btn-primary" id="btn_create">Create Discharge Summary</button>
                    <button type="button" class="btn btn-outline-primary" id="btn_regen">Regenerate Summary</button>
                    <button type="button" class="btn btn-danger" id="btn_show">Make File and Print on Letter Head</button>
                    <button type="button" class="btn btn-danger" id="btn_show2">Make File and Print On Plain Paper</button>
                    <button type="button" class="btn btn-outline-danger" id="btn_show3">New Print</button>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
(function() {
    function openUrl(url) {
        window.open(url, '_blank');
    }

    function loadOrRedirect(url, title) {
        if (typeof load_form_div === 'function') {
            load_form_div(url, 'maindiv', title || 'Discharge');
            return;
        }
        window.location.href = url;
    }

    if (window.CKEDITOR) {
        CKEDITOR.config.versionCheck = false;
        // Global layout removes notification plugins; reset here to avoid dependency errors.
        CKEDITOR.config.removePlugins = '';
        if (CKEDITOR.instances.editor_Discharge_Preview) {
            CKEDITOR.instances.editor_Discharge_Preview.destroy(true);
        }
        CKEDITOR.replace('editor_Discharge_Preview', {
            height: 360
        });
    }

    var ipdId = <?= (int) $ipdId ?>;
    var btnCreate = document.getElementById('btn_create');
    var btnRegen = document.getElementById('btn_regen');
    var btnShow = document.getElementById('btn_show');
    var btnShow2 = document.getElementById('btn_show2');
    var btnShow3 = document.getElementById('btn_show3');

    if (btnCreate) {
        btnCreate.addEventListener('click', function() {
            loadOrRedirect('<?= site_url('Ipd_discharge/ipd_select') ?>/' + ipdId, 'Create Discharge Summary');
        });
    }

    if (btnRegen) {
        btnRegen.addEventListener('click', function() {
            loadOrRedirect('<?= site_url('Ipd_discharge/preview_discharge_report') ?>/' + ipdId + '?regen=1', 'Discharge Preview');
        });
    }

    if (btnShow) {
        btnShow.addEventListener('click', function() {
            openUrl('<?= site_url('Ipd_discharge/show_discharge') ?>/' + ipdId + '/1');
        });
    }

    if (btnShow2) {
        btnShow2.addEventListener('click', function() {
            openUrl('<?= site_url('Ipd_discharge/show_discharge') ?>/' + ipdId + '/0');
        });
    }

    if (btnShow3) {
        btnShow3.addEventListener('click', function() {
            openUrl('<?= site_url('Ipd_discharge/show_file3') ?>/' + ipdId);
        });
    }
})();
</script>
