<?php
$row = $row ?? [];
$notice = (string) ($notice ?? '');
$noticeType = (string) ($notice_type ?? 'success');
$modality = (int) ($modality ?? 3);
$columnsReady = (bool) ($columns_ready ?? false);
$modalityList = $modality_list ?? [];
$templates = $templates ?? [];
$selectedTemplateId = (int) ($selected_template_id ?? 0);
$hasSignatureImageColumn = (bool) ($has_signature_image_column ?? false);
$modalityLabel = (string) ($modalityList[$modality] ?? 'Diagnosis');
$panelTitle = $modalityLabel . ' Print Template';
?>

<section class="content">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><?= esc($panelTitle) ?> Settings</h6>
            <a class="btn btn-sm btn-outline-secondary" href="javascript:load_form_div('<?= base_url('setting/template/diagnosis_print_settings/' . $modality) ?>','maindiv','<?= esc($panelTitle) ?>');">Reset</a>
        </div>
        <div class="card-body">
            <div id="diag_print_notice">
                <?php if ($notice !== ''): ?>
                    <div class="alert alert-<?= esc($noticeType) ?> py-2 mb-3"><?= esc($notice) ?></div>
                <?php endif; ?>
            </div>

            <?php if (! $columnsReady): ?>
                <div class="alert alert-warning py-2 mb-3">
                    Required table/columns are missing.
                    Run migration: <b>php spark migrate</b>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= base_url('setting/template/diagnosis_print_settings/' . $modality) ?>" enctype="multipart/form-data" id="diag_print_setting_form">
                <?= csrf_field() ?>
                <input type="hidden" name="template_id" value="<?= (int) ($row['id'] ?? $selectedTemplateId) ?>">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label small">Modality</label>
                        <select class="form-select form-select-sm" name="modality" onchange="changeDiagnosisModality(this.value)">
                            <?php foreach ($modalityList as $key => $label): ?>
                                <option value="<?= (int) $key ?>" <?= (int) $key === $modality ? 'selected' : '' ?>><?= esc($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small">Template Name</label>
                        <input type="text" class="form-control form-control-sm" name="template_name" value="<?= esc((string) ($row['template_name'] ?? '')) ?>" placeholder="e.g. Standard <?= esc($modalityLabel) ?> Template" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" name="is_default" id="is_default" value="1" <?= ((int) ($row['is_default'] ?? 0) === 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_default">Set as default template for this modality</label>
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
                            <a class="btn btn-sm <?= $activeClass ?>" href="javascript:load_form_div('<?= base_url('setting/template/diagnosis_print_settings/' . $modality . '?template_id=' . $tplId) ?>','maindiv','<?= esc($panelTitle) ?>');"><?= esc($tplName) ?></a>
                        <?php endforeach; ?>
                        <a class="btn btn-sm btn-outline-success" href="javascript:load_form_div('<?= base_url('setting/template/diagnosis_print_settings/' . $modality . '?new=1') ?>','maindiv','<?= esc($panelTitle) ?>');">+ New Template</a>
                    </div>
                </div>

                <hr>
                <h6 class="mb-2">1. Page Size, Page Background Image, Page Watermark</h6>
                <div class="alert alert-light border py-2 small mb-2">
                    Legacy mapping: margin-top/bottom/left/right and margin-header/footer from old @page CSS are now configurable here in cm.
                    Example old values: top 6.1, bottom 2.5, left 0.7, right 0.7, header 0.5, footer 1.5.
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-2">
                        <label class="form-label small">Page Size</label>
                        <select class="form-select form-select-sm" name="page_size">
                            <?php $pageSize = strtoupper((string) ($row['page_size'] ?? 'A4')); ?>
                            <option value="A4" <?= $pageSize === 'A4' ? 'selected' : '' ?>>A4</option>
                            <option value="A4-L" <?= $pageSize === 'A4-L' ? 'selected' : '' ?>>A4 Landscape</option>
                            <option value="LETTER" <?= $pageSize === 'LETTER' ? 'selected' : '' ?>>Letter</option>
                            <option value="LEGAL" <?= $pageSize === 'LEGAL' ? 'selected' : '' ?>>Legal</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Top Margin (cm)</label>
                        <input type="number" step="0.1" min="0" max="25" class="form-control form-control-sm" name="page_margin_top_cm" value="<?= esc((string) ($row['page_margin_top_cm'] ?? '6.1')) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Bottom Margin (cm)</label>
                        <input type="number" step="0.1" min="0" max="25" class="form-control form-control-sm" name="page_margin_bottom_cm" value="<?= esc((string) ($row['page_margin_bottom_cm'] ?? '2.5')) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Left Margin (cm)</label>
                        <input type="number" step="0.1" min="0" max="25" class="form-control form-control-sm" name="page_margin_left_cm" value="<?= esc((string) ($row['page_margin_left_cm'] ?? '0.7')) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Right Margin (cm)</label>
                        <input type="number" step="0.1" min="0" max="25" class="form-control form-control-sm" name="page_margin_right_cm" value="<?= esc((string) ($row['page_margin_right_cm'] ?? '0.7')) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Header Margin (cm)</label>
                        <input type="number" step="0.1" min="0" max="25" class="form-control form-control-sm" name="margin_header_cm" value="<?= esc((string) ($row['margin_header_cm'] ?? '0.5')) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Footer Margin (cm)</label>
                        <input type="number" step="0.1" min="0" max="25" class="form-control form-control-sm" name="margin_footer_cm" value="<?= esc((string) ($row['margin_footer_cm'] ?? '1.5')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Page Background Image</label>
                        <input type="file" class="form-control form-control-sm" name="page_background_image" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp">
                        <?php if (! empty($row['page_background_image'])): ?>
                            <small class="text-muted d-block mt-1">Current: <?= esc((string) $row['page_background_image']) ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" name="remove_background" id="remove_background" value="1">
                            <label class="form-check-label" for="remove_background">Remove BG</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Watermark Type</label>
                        <?php $watermarkType = (string) ($row['watermark_type'] ?? 'none'); ?>
                        <select class="form-select form-select-sm" name="watermark_type">
                            <option value="none" <?= $watermarkType === 'none' ? 'selected' : '' ?>>None</option>
                            <option value="text" <?= $watermarkType === 'text' ? 'selected' : '' ?>>Text</option>
                            <option value="image" <?= $watermarkType === 'image' ? 'selected' : '' ?>>Image</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Watermark Alpha</label>
                        <input type="number" step="0.01" min="0.01" max="1" class="form-control form-control-sm" name="watermark_alpha" value="<?= esc((string) ($row['watermark_alpha'] ?? '0.12')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Watermark Text</label>
                        <input type="text" class="form-control form-control-sm" name="watermark_text" value="<?= esc((string) ($row['watermark_text'] ?? '')) ?>" placeholder="CONFIDENTIAL">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Watermark Image</label>
                        <input type="file" class="form-control form-control-sm" name="watermark_image" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp">
                        <?php if (! empty($row['watermark_image'])): ?>
                            <small class="text-muted d-block mt-1">Current: <?= esc((string) $row['watermark_image']) ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" name="remove_watermark_image" id="remove_watermark_image" value="1">
                            <label class="form-check-label" for="remove_watermark_image">Remove WM</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Signature Image (for {{signature_image_url}})</label>
                        <input type="file" class="form-control form-control-sm" name="signature_image" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" <?= $hasSignatureImageColumn ? '' : 'disabled' ?>>
                        <?php if (! $hasSignatureImageColumn): ?>
                            <small class="text-danger d-block mt-1">Signature column missing in DB. Please refresh this page once.</small>
                        <?php elseif (! empty($row['signature_image'])): ?>
                            <small class="text-muted d-block mt-1">Current: <?= esc((string) $row['signature_image']) ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" name="remove_signature_image" id="remove_signature_image" value="1" <?= $hasSignatureImageColumn ? '' : 'disabled' ?>>
                            <label class="form-check-label" for="remove_signature_image">Remove Sign</label>
                        </div>
                    </div>
                </div>

                <h6 class="mb-2">2. Header</h6>
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label small">Header HTML / mPDF tags</label>
                        <textarea class="form-control" name="header_html" rows="6" style="font-family:Consolas,Monaco,monospace;"><?= esc((string) ($row['header_html'] ?? '')) ?></textarea>
                    </div>
                </div>

                <h6 class="mb-2 mt-3">3. Header for First Page</h6>
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label small">First Page Header HTML / mPDF tags</label>
                        <textarea class="form-control" name="first_page_header_html" rows="5" style="font-family:Consolas,Monaco,monospace;"><?= esc((string) ($row['first_page_header_html'] ?? '')) ?></textarea>
                    </div>
                </div>

                <hr>
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="mb-0">4. Content Part</h6>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#pdfTemplateTipsModal" title="Template Help Tips">
                        ? Help Tips
                    </button>
                </div>
                <div class="alert alert-secondary py-2 small mb-2">
                    Main content customization with token support. Click <b>Help Tips</b> for available tokens and mPDF constants/snippets.
                </div>
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label small">Patient Info HTML Template (tokens supported)</label>
                        <textarea class="form-control" name="patient_info_html" rows="8" style="font-family:Consolas,Monaco,monospace;"><?= esc((string) ($row['patient_info_html'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Content Prefix HTML (before report body)</label>
                        <textarea class="form-control" name="content_prefix_html" rows="4" style="font-family:Consolas,Monaco,monospace;"><?= esc((string) ($row['content_prefix_html'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Content Suffix HTML (after report body)</label>
                        <textarea class="form-control" name="content_suffix_html" rows="4" style="font-family:Consolas,Monaco,monospace;"><?= esc((string) ($row['content_suffix_html'] ?? '')) ?></textarea>
                    </div>
                </div>

                <hr>
                <h6 class="mb-2">5. Footer</h6>
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label small">Footer HTML / mPDF tags</label>
                        <textarea class="form-control" name="footer_html" rows="5" style="font-family:Consolas,Monaco,monospace;"><?= esc((string) ($row['footer_html'] ?? '')) ?></textarea>
                    </div>
                </div>

                <h6 class="mb-2 mt-3">6. Footer for Last Page</h6>
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label small">Last Page Footer HTML / mPDF tags</label>
                        <textarea class="form-control" name="last_page_footer_html" rows="5" style="font-family:Consolas,Monaco,monospace;"><?= esc((string) ($row['last_page_footer_html'] ?? '')) ?></textarea>
                    </div>
                </div>

                <h6 class="mb-2 mt-3">Advanced mPDF Prefix/Suffix (Optional)</h6>
                <div class="alert alert-info py-2 small mb-2">
                    Optional raw blocks. Use if you need extra mPDF tags beyond the 6 template sections.
                </div>
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label small">mPDF Prefix HTML (before content)</label>
                        <textarea class="form-control" name="mpdf_prefix_html" rows="8" style="font-family:Consolas,Monaco,monospace;"><?= esc((string) ($row['mpdf_prefix_html'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">mPDF Suffix HTML (after content)</label>
                        <textarea class="form-control" name="mpdf_suffix_html" rows="5" style="font-family:Consolas,Monaco,monospace;"><?= esc((string) ($row['mpdf_suffix_html'] ?? '')) ?></textarea>
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm" <?= ! $columnsReady ? 'disabled' : '' ?>>Save Settings</button>
                </div>

                <div class="modal fade" id="pdfTemplateTipsModal" tabindex="-1" aria-labelledby="pdfTemplateTipsModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h6 class="modal-title" id="pdfTemplateTipsModalLabel">PDF Template Help Tips</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body small">
                                <p class="mb-2"><b>Patient Tokens</b></p>
                                <p style="font-family:Consolas,Monaco,monospace; line-height:1.8;">
                                    {{invoice_code}}, {{patient_name}}, {{relative_type}}, {{relative_name}}, {{relative}}, {{age}}, {{gender}}, {{age_sex}}, {{uhid}}, {{collected_time}}, {{reported_time}}, {{printed_time}}, {{report_title}}
                                </p>
                                <p style="font-family:Consolas,Monaco,monospace; line-height:1.8;">
                                    {{hospital_logo_url}}, {{signature_image_url}}, {{doctor_name}}, {{doctor_education}}, {{technician_name}}
                                </p>

                                <p class="mb-1" style="font-family:Consolas,Monaco,monospace;">Signature image usage: &lt;img src="{{signature_image_url}}" width="100px" /&gt;</p>

                                <p class="mb-2 mt-3"><b>mPDF Constants / Tags (use in Prefix or Suffix)</b></p>
                                <p class="mb-1" style="font-family:Consolas,Monaco,monospace;">Page No: {PAGENO}</p>
                                <p class="mb-1" style="font-family:Consolas,Monaco,monospace;">Total Pages: {nbpg}</p>
                                <p class="mb-1" style="font-family:Consolas,Monaco,monospace;">Page Break: &lt;pagebreak /&gt;</p>
                                <p class="mb-1" style="font-family:Consolas,Monaco,monospace;">Page Size (A4): &lt;style&gt;@page { size: A4; }&lt;/style&gt;</p>
                                <p class="mb-1" style="font-family:Consolas,Monaco,monospace;">Page Size (Landscape): &lt;style&gt;@page { size: A4-L; }&lt;/style&gt;</p>
                                <p class="mb-1" style="font-family:Consolas,Monaco,monospace;">Header Tag: &lt;htmlpageheader name="myHeader"&gt;...&lt;/htmlpageheader&gt;</p>
                                <p class="mb-1" style="font-family:Consolas,Monaco,monospace;">Footer Tag: &lt;htmlpagefooter name="myFooter"&gt;...&lt;/htmlpagefooter&gt;</p>

                                <p class="mb-2 mt-3"><b>Quick Footer Example</b></p>
                                <pre class="bg-light border rounded p-2 mb-0" style="font-family:Consolas,Monaco,monospace; white-space:pre-wrap;">&lt;htmlpagefooter name="myFooter" style="display:none"&gt;
  &lt;div style="text-align:right; font-size:10px;"&gt;Page {PAGENO} of {nbpg}&lt;/div&gt;
&lt;/htmlpagefooter&gt;
&lt;sethtmlpagefooter name="myFooter" value="on" /&gt;</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
(function () {
    var form = document.getElementById('diag_print_setting_form');
    var noticeBox = document.getElementById('diag_print_notice');

    function showNotice(type, message) {
        if (!noticeBox) {
            return;
        }
        var safeType = (type === 'success') ? 'success' : 'danger';
        var safeText = String(message || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        noticeBox.innerHTML = '<div class="alert alert-' + safeType + ' py-2 mb-3">' + safeText + '</div>';
    }

    window.changeDiagnosisModality = function (value) {
        var selected = parseInt(value || 0, 10);
        if (!selected) {
            return;
        }
        var url = '<?= base_url('setting/template/diagnosis_print_settings') ?>/' + selected;
        if (typeof load_form_div === 'function') {
            load_form_div(url, 'maindiv', '<?= esc($panelTitle) ?>');
        } else {
            window.location.href = url;
        }
    };

    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
            }

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function (response) {
                return response.json();
            })
            .then(function (result) {
                var status = (result && result.notice_type) ? result.notice_type : 'danger';
                var message = (result && result.notice) ? result.notice : 'Unable to save settings.';
                showNotice(status, message);

                if (result && result.csrfName && result.csrfHash) {
                    var csrfInput = form.querySelector('input[name="' + result.csrfName + '"]');
                    if (csrfInput) {
                        csrfInput.value = result.csrfHash;
                    }
                }
            })
            .catch(function (error) {
                showNotice('danger', 'Save failed: ' + error.message);
            })
            .finally(function () {
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
            });
        });
    }
})();
</script>
