<?php
$ipd = $ipd_info ?? null;
$person = $person_info ?? null;
$ipdId = (int) ($ipd_id ?? 0);
$previewHtml = (string) ($content ?? '');
$renderedHtml = (string) ($rendered_content ?? $previewHtml);
$noticeText = (string) ($notice ?? '');
$noticeType = (string) ($notice_type ?? 'success');
$templateRows = $template_rows ?? [];
$selectedTemplateId = (int) ($selected_template_id ?? 0);
$nabhAudit = is_array($nabh_audit ?? null) ? $nabh_audit : [];
$nabhItems = is_array($nabhAudit['items'] ?? null) ? $nabhAudit['items'] : [];
$nabhCriticalMissing = is_array($nabhAudit['critical_missing'] ?? null) ? $nabhAudit['critical_missing'] : [];
$nabhCriticalMissingCount = (int) ($nabhAudit['critical_missing_count'] ?? 0);
$nabhOkCount = (int) ($nabhAudit['ok_count'] ?? 0);
$nabhTotalCount = (int) ($nabhAudit['total_count'] ?? count($nabhItems));

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

            <form method="get" action="<?= site_url('Ipd_discharge/preview_discharge_report/' . $ipdId) ?>" class="row g-2 mb-3">
                <div class="col-md-6 col-lg-4">
                    <label class="form-label small mb-1"><strong>Discharge Template</strong></label>
                    <select class="form-select form-select-sm" name="tpl" id="tpl_selector">
                        <?php foreach ($templateRows as $tpl): ?>
                            <?php $tplId = (int) ($tpl['id'] ?? 0); ?>
                            <option value="<?= $tplId ?>" <?= $tplId === $selectedTemplateId ? 'selected' : '' ?>>
                                <?= esc((string) ($tpl['template_name'] ?? ('Template #' . $tplId))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 col-lg-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-outline-primary btn-sm">Apply Template</button>
                </div>
            </form>

            <?php if (! empty($nabhItems)): ?>
                <div class="card border-warning mb-3">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <strong>NABH Audit Mode</strong>
                        <span class="small">Completed: <?= $nabhOkCount ?>/<?= $nabhTotalCount ?></span>
                    </div>
                    <div class="card-body py-2">
                        <?php if ($nabhCriticalMissingCount > 0): ?>
                            <div class="alert alert-danger py-2 mb-2">
                                <strong>Critical sections missing (<?= $nabhCriticalMissingCount ?>):</strong>
                                <?= esc(implode(' | ', $nabhCriticalMissing)) ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success py-2 mb-2">All critical NABH checklist sections are present.</div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:55%;">Checklist Item</th>
                                        <th style="width:15%;">Type</th>
                                        <th style="width:30%;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($nabhItems as $item): ?>
                                        <?php
                                        $ok = ! empty($item['ok']);
                                        $critical = ! empty($item['critical']);
                                        ?>
                                        <tr>
                                            <td><?= esc((string) ($item['label'] ?? '')) ?></td>
                                            <td><?= $critical ? 'Critical' : 'Recommended' ?></td>
                                            <td><?= $ok ? 'OK' : ($critical ? 'Missing' : 'Needs review') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="border rounded p-3 bg-white" style="min-height: 420px; overflow:auto;">
                <?= $renderedHtml ?>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-3">
                <a class="btn btn-primary" id="btn_create" href="<?= site_url('Ipd_discharge/ipd_select/' . $ipdId) ?>">Back to Create Discharge Summary</a>
                <button type="button" class="btn btn-danger" id="btn_show">Make File and Print on Letter Head</button>
                <button type="button" class="btn btn-danger" id="btn_show2">Make File and Print On Plain Paper</button>
                <button type="button" class="btn btn-outline-danger" id="btn_show3">New Print</button>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    function openUrl(url) {
        window.open(url, '_blank');
    }

    function loadOrRedirect(url, title) {
        if (typeof load_form === 'function') {
            load_form(url, title || 'Discharge');
            return;
        }
        if (typeof load_form_div === 'function') {
            load_form_div(url, 'maindiv', title || 'Discharge');
            return;
        }
        window.location.assign(url);
    }

    var ipdId = <?= (int) $ipdId ?>;
    var nabhCriticalMissingCount = <?= (int) $nabhCriticalMissingCount ?>;
    var nabhCriticalMissingItems = <?= json_encode(array_values($nabhCriticalMissing), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var btnCreate = document.getElementById('btn_create');
    var btnShow = document.getElementById('btn_show');
    var btnShow2 = document.getElementById('btn_show2');
    var btnShow3 = document.getElementById('btn_show3');

    function confirmNabhPrint() {
        if (nabhCriticalMissingCount <= 0) {
            return true;
        }

        var detail = Array.isArray(nabhCriticalMissingItems) ? nabhCriticalMissingItems.join(' | ') : '';
        var message = 'NABH Audit: critical sections are missing.\n\n' + detail + '\n\nDo you still want to print?';
        return window.confirm(message);
    }

    if (btnCreate) {
        btnCreate.addEventListener('click', function(e) {
            if (typeof load_form !== 'function' && typeof load_form_div !== 'function') {
                return;
            }
            // Prevent default only when SPA loader exists; otherwise native link works.
            e.preventDefault();
            loadOrRedirect('<?= site_url('Ipd_discharge/ipd_select') ?>/' + ipdId, 'Create Discharge Summary');
        });
    }

    if (btnShow) {
        btnShow.addEventListener('click', function() {
            if (!confirmNabhPrint()) {
                return;
            }
            var tpl = document.getElementById('tpl_selector');
            var tplId = tpl ? parseInt(tpl.value || '0', 10) : 0;
            var url = '<?= site_url('Ipd_discharge/show_discharge') ?>/' + ipdId + '/1';
            if (tplId > 0) {
                url += '?tpl=' + tplId;
            }
            openUrl(url);
        });
    }

    if (btnShow2) {
        btnShow2.addEventListener('click', function() {
            if (!confirmNabhPrint()) {
                return;
            }
            var tpl = document.getElementById('tpl_selector');
            var tplId = tpl ? parseInt(tpl.value || '0', 10) : 0;
            var url = '<?= site_url('Ipd_discharge/show_discharge') ?>/' + ipdId + '/0';
            if (tplId > 0) {
                url += '?tpl=' + tplId;
            }
            openUrl(url);
        });
    }

    if (btnShow3) {
        btnShow3.addEventListener('click', function() {
            if (!confirmNabhPrint()) {
                return;
            }
            var tpl = document.getElementById('tpl_selector');
            var tplId = tpl ? parseInt(tpl.value || '0', 10) : 0;
            var url = '<?= site_url('Ipd_discharge/show_file3') ?>/' + ipdId;
            if (tplId > 0) {
                url += '?tpl=' + tplId;
            }
            openUrl(url);
        });
    }
})();
</script>
