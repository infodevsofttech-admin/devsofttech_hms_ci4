<?php
$row = $row ?? [];
$notice = (string) ($notice ?? '');
$noticeType = (string) ($notice_type ?? 'success');
$templates = $templates ?? [];
$selectedTemplateId = (int) ($selected_template_id ?? 0);
$columnsReady = (bool) ($columns_ready ?? false);
?>

<section class="content">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Document Print Template Settings</h6>
            <a class="btn btn-sm btn-outline-secondary" href="javascript:load_form_div('<?= base_url('setting/template/document_print_settings') ?>','maindiv','Document Print Template');">Reset</a>
        </div>
        <div class="card-body">
            <div id="doc_print_notice">
                <?php if ($notice !== ''): ?>
                    <div class="alert alert-<?= esc($noticeType) ?> py-2 mb-3"><?= esc($notice) ?></div>
                <?php endif; ?>
            </div>

            <?php if (! $columnsReady): ?>
                <div class="alert alert-warning py-2 mb-3">
                    Required table is missing. Run migration: <b>php spark migrate</b>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= base_url('setting/template/document_print_settings') ?>" id="doc_print_setting_form">
                <?= csrf_field() ?>
                <input type="hidden" name="template_id" value="<?= (int) ($row['id'] ?? $selectedTemplateId) ?>">

                <div class="row g-2">
                    <div class="col-md-5">
                        <label class="form-label small">Template Name</label>
                        <input type="text" class="form-control form-control-sm" name="template_name" value="<?= esc((string) ($row['template_name'] ?? '')) ?>" placeholder="e.g. Standard Document Print" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Page Size</label>
                        <?php $pageSize = strtoupper((string) ($row['page_size'] ?? 'A4')); ?>
                        <select class="form-select form-select-sm" name="page_size">
                            <option value="A4" <?= $pageSize === 'A4' ? 'selected' : '' ?>>A4</option>
                            <option value="A4-L" <?= $pageSize === 'A4-L' ? 'selected' : '' ?>>A4 Landscape</option>
                            <option value="LETTER" <?= $pageSize === 'LETTER' ? 'selected' : '' ?>>Letter</option>
                            <option value="LEGAL" <?= $pageSize === 'LEGAL' ? 'selected' : '' ?>>Legal</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" name="is_default" id="doc_print_is_default" value="1" <?= ((int) ($row['is_default'] ?? 0) === 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="doc_print_is_default">Set as default document print template</label>
                        </div>
                    </div>
                </div>

                <div class="mt-2 mb-2">
                    <label class="form-label small mb-1">Template List</label>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($templates as $tpl): ?>
                            <?php
                            $tplId = (int) ($tpl['id'] ?? 0);
                            $tplName = (string) ($tpl['template_name'] ?? ('Template ' . $tplId));
                            $activeClass = ($tplId === (int) ($row['id'] ?? $selectedTemplateId)) ? 'btn-primary' : 'btn-outline-primary';
                            ?>
                            <a class="btn btn-sm <?= $activeClass ?>" href="javascript:load_form_div('<?= base_url('setting/template/document_print_settings?template_id=' . $tplId) ?>','maindiv','Document Print Template');"><?= esc($tplName) ?></a>
                        <?php endforeach; ?>
                        <a class="btn btn-sm btn-outline-success" href="javascript:load_form_div('<?= base_url('setting/template/document_print_settings?new=1') ?>','maindiv','Document Print Template');">+ New Template</a>
                    </div>
                </div>

                <hr>
                <h6 class="mb-2">1. Page & Margin Settings</h6>
                <div class="alert alert-light border py-2 small mb-2">
                    Equivalent to old @page block values. Example legacy values: top 6.1, bottom 2.5, left 0.7, right 0.7, header 0.5, footer 1.5.
                </div>

                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label small">Top Margin (cm)</label>
                        <input type="number" step="0.1" min="0" max="25" class="form-control form-control-sm" name="page_margin_top_cm" value="<?= esc((string) ($row['page_margin_top_cm'] ?? '6.1')) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Bottom Margin (cm)</label>
                        <input type="number" step="0.1" min="0" max="25" class="form-control form-control-sm" name="page_margin_bottom_cm" value="<?= esc((string) ($row['page_margin_bottom_cm'] ?? '2.5')) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Left Margin (cm)</label>
                        <input type="number" step="0.1" min="0" max="25" class="form-control form-control-sm" name="page_margin_left_cm" value="<?= esc((string) ($row['page_margin_left_cm'] ?? '0.7')) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Right Margin (cm)</label>
                        <input type="number" step="0.1" min="0" max="25" class="form-control form-control-sm" name="page_margin_right_cm" value="<?= esc((string) ($row['page_margin_right_cm'] ?? '0.7')) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Header Margin (cm)</label>
                        <input type="number" step="0.1" min="0" max="25" class="form-control form-control-sm" name="margin_header_cm" value="<?= esc((string) ($row['margin_header_cm'] ?? '0.5')) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Footer Margin (cm)</label>
                        <input type="number" step="0.1" min="0" max="25" class="form-control form-control-sm" name="margin_footer_cm" value="<?= esc((string) ($row['margin_footer_cm'] ?? '1.5')) ?>">
                    </div>
                </div>

                <hr>
                <h6 class="mb-2">2. Header / Footer HTML (Optional)</h6>
                <div class="alert alert-secondary py-2 small mb-2">
                    If Header HTML is blank, default hospital header logic is used based on print mode.
                </div>

                <div class="card border-info mb-3">
                    <div class="card-header bg-info bg-opacity-10 py-1 d-flex justify-content-between align-items-center">
                        <span class="fw-semibold small text-info">Available Placeholders — click any to insert into focused textarea</span>
                        <span class="text-muted small" id="doc_tpl_focus_label">Focus a textarea below first</span>
                    </div>
                    <div class="card-body p-2">
                        <div class="mb-1"><span class="badge bg-secondary me-1">Hospital</span>
                            <?php foreach ([
                                '{{H_Name}}'           => 'Hospital Name',
                                '{{H_address_1}}'      => 'Address Line 1',
                                '{{H_address_2}}'      => 'Address Line 2',
                                '{{H_phone_No}}'       => 'Phone No.',
                                '{{H_Email}}'          => 'Email',
                                '{{H_logo}}'           => 'Logo filename',
                                '{{H_logo_abs}}'       => 'Logo absolute path',
                                '{{hospital_logo_html}}' => 'Logo <img> tag',
                            ] as $ph => $desc): ?>
                                <button type="button" class="btn btn-outline-info btn-sm py-0 px-1 me-1 mb-1 doc-ph-btn" data-ph="<?= esc($ph) ?>" title="<?= esc($desc) ?>"><code><?= esc($ph) ?></code></button>
                            <?php endforeach; ?>
                        </div>
                        <div class="mb-1"><span class="badge bg-secondary me-1">Patient / IPD</span>
                            <?php foreach ([
                                '{{PATIENT_NAME}}' => 'Full patient name',
                                '{{UHID}}'         => 'Patient UHID',
                                '{{IPD_CODE}}'     => 'IPD admission number',
                                '{{ADMIT_DATE}}'   => 'Date of admission',
                                '{{AGE_GENDER}}'   => 'Age & gender combined',
                                '{{age_sex}}'      => 'Age/sex short form',
                                '{{phoneno}}'      => 'Patient phone',
                                '{{DOCTORS}}'      => 'Treating doctors',
                                '{{doctor_name}}'  => 'Primary doctor name',
                                '{{doctor_sign_html}}' => 'Doctor signature img',
                            ] as $ph => $desc): ?>
                                <button type="button" class="btn btn-outline-success btn-sm py-0 px-1 me-1 mb-1 doc-ph-btn" data-ph="<?= esc($ph) ?>" title="<?= esc($desc) ?>"><code><?= esc($ph) ?></code></button>
                            <?php endforeach; ?>
                        </div>
                        <div class="mb-1"><span class="badge bg-secondary me-1">Date / Page</span>
                            <?php foreach ([
                                '{{CURRENT_DATE}}'     => 'Today\'s date',
                                '{{CURRENT_DATETIME}}' => 'Today date + time',
                                '{{print_time}}'       => 'Print timestamp',
                                '{PAGENO}'             => 'mPDF: current page',
                                '{nbpg}'               => 'mPDF: total pages',
                            ] as $ph => $desc): ?>
                                <button type="button" class="btn btn-outline-warning btn-sm py-0 px-1 me-1 mb-1 doc-ph-btn" data-ph="<?= esc($ph) ?>" title="<?= esc($desc) ?>"><code><?= esc($ph) ?></code></button>
                            <?php endforeach; ?>
                        </div>
                        <div class="mb-0"><span class="badge bg-secondary me-1">QR / Barcode</span>
                            <?php foreach ([
                                '{{qr_content}}'   => 'QR raw content string',
                                '{{qr_code_html}}' => 'QR code <img> tag',
                            ] as $ph => $desc): ?>
                                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-1 me-1 mb-1 doc-ph-btn" data-ph="<?= esc($ph) ?>" title="<?= esc($desc) ?>"><code><?= esc($ph) ?></code></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label small">Header HTML / mPDF tags</label>
                        <textarea class="form-control doc-ph-target" name="header_html" rows="6" style="font-family:Consolas,Monaco,monospace;"><?= esc((string) ($row['header_html'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Footer HTML / mPDF tags</label>
                        <textarea class="form-control doc-ph-target" name="footer_html" rows="4" style="font-family:Consolas,Monaco,monospace;"><?= esc((string) ($row['footer_html'] ?? '')) ?></textarea>
                    </div>
                </div>

                <script>
                (function () {
                    var activeTarget = null;

                    document.querySelectorAll('.doc-ph-target').forEach(function (ta) {
                        ta.addEventListener('focus', function () {
                            activeTarget = ta;
                            var label = document.getElementById('doc_tpl_focus_label');
                            if (label) {
                                label.textContent = 'Inserting into: ' + (ta.name || ta.id || 'textarea');
                                label.className = 'small text-success fw-semibold';
                            }
                        });
                    });

                    document.querySelectorAll('.doc-ph-btn').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            var ph = btn.getAttribute('data-ph') || '';
                            if (!ph) { return; }
                            if (!activeTarget) {
                                alert('Click inside a Header or Footer textarea first, then click a placeholder.');
                                return;
                            }
                            var start = activeTarget.selectionStart || 0;
                            var end   = activeTarget.selectionEnd   || 0;
                            var val   = activeTarget.value;
                            activeTarget.value = val.substring(0, start) + ph + val.substring(end);
                            activeTarget.selectionStart = activeTarget.selectionEnd = start + ph.length;
                            activeTarget.focus();
                        });
                    });
                })();
                </script>

                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm" <?= ! $columnsReady ? 'disabled' : '' ?>>Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
(function () {
    var form = document.getElementById('doc_print_setting_form');
    var noticeBox = document.getElementById('doc_print_notice');

    function showNotice(type, message) {
        if (!noticeBox) {
            return;
        }
        var safeType = (type === 'success') ? 'success' : 'danger';
        var safeText = String(message || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        noticeBox.innerHTML = '<div class="alert alert-' + safeType + ' py-2 mb-3">' + safeText + '</div>';
    }

    if (!form) {
        return;
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        var submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
        }

        $.ajax({
            url: form.action,
            method: 'POST',
            data: $(form).serialize(),
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .done(function (result) {
            var status = (result && result.notice_type) ? result.notice_type : 'danger';

            if (status === 'success') {
                var savedId = (result && result.selected_template_id) ? result.selected_template_id : 0;
                var reloadUrl = '<?= base_url('setting/template/document_print_settings') ?>'
                    + (savedId > 0 ? '?template_id=' + savedId : '');
                if (typeof load_form_div === 'function') {
                    load_form_div(reloadUrl, 'maindiv', 'Document Print Template');
                } else {
                    window.location.href = reloadUrl;
                }
            } else {
                var message = (result && result.notice) ? result.notice : 'Unable to save settings.';
                showNotice(status, message);

                if (result && result.csrfName && result.csrfHash) {
                    var csrfInput = form.querySelector('input[name="' + result.csrfName + '"]');
                    if (csrfInput) {
                        csrfInput.value = result.csrfHash;
                    }
                }
            }
        })
        .fail(function (xhr) {
            var fallback = 'Save failed.';
            if (xhr && xhr.responseJSON && xhr.responseJSON.notice) {
                fallback = xhr.responseJSON.notice;
            }
            showNotice('danger', fallback);
        })
        .always(function () {
            if (submitBtn) {
                submitBtn.disabled = false;
            }
        });
    });
})();
</script>
