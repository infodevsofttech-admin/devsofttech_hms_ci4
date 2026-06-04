<section class="content-header">
    <div class="clearfix">
        <div style="float:left;">
            <h1>OPD Print Template Builder</h1>
        </div>
        <div style="float:right; margin-top:8px;">
            <div class="btn-group btn-group-sm" role="group" aria-label="Template Navigation">
                <a class="btn btn-info" href="javascript:load_form('<?= base_url('Opd/print_template_builder') ?>?mode=list','maindiv','OPD Template List');">Template List</a>
                
            </div>
        </div>
    </div>
</section>

<?php
$paperSettings = $paper_settings ?? [];
$paperPageSize = strtoupper((string) ($paperSettings['page_size'] ?? 'A4'));
$paperCustomWidth = (string) ($paperSettings['custom_width_mm'] ?? '210');
$paperCustomHeight = (string) ($paperSettings['custom_height_mm'] ?? '297');
$selectedTemplateName = (string) ($selected_name ?? 'default');

$placeholderGroups = [
    'hospital' => [],
    'patient' => [],
    'opd_master' => [],
    'opd_consult' => [],
    'other' => [],
];

$hospitalSet = [
    'hospital_name', 'hospital_address', 'hospital_phone', 'hospital_email',
    'h_name', 'h_address_1', 'h_address_2', 'h_phone_no', 'h_email', 'h_logo',
    'hospital_section',
];

$patientSet = [
    'pname', 'prelative', 'age_sex', 'phoneno', 'p_address', 'uhid_no',
    'patient_section',
];

$opdMasterSet = [
    'opd_sr_no', 'opd_no', 'opd_date', 'exp_date', 'opd_fee_desc',
    'total_no_visit', 'last_opdvisit_date', 'str_opd_book_date',
    'doctor_name', 'doctor_full_name', 'doctor_title',
    'doctor_reg_no', 'doctor_registration',
    'doctor_phone', 'doctor_email', 'doctor_address',
    'doctor_specialization', 'specname', 'short_description',
    'doctor_short_description', 'doctor_sign_html',
    'print_time', 'current_date',
    'margintop', 'marginbottom', 'marginleft', 'marginright', 'marginheader', 'marginfooter',
];

$opdConsultSet = [
    'bp', 'bp_diastolic', 'pulse', 'temp', 'spo2', 'rr_min', 'height', 'weight', 'waist',
    'pallor', 'icterus', 'cyanosis', 'clubbing', 'edema',
    'vital_content', 'vital_content_extra', 'vital_content_full', 'general_examination',
    'pain_value', 'pain_label', 'pain_scale',
    'complication', 'addiction',
    'drug_allergy_status', 'drug_allergy_details', 'adr_history', 'current_medications',
    'drug_allergy_block', 'adr_history_block', 'current_medications_block',
    'women_lmp', 'women_last_baby', 'women_pregnancy_related', 'women_related_problems', 'women_block',
    'obstetric_history', 'menstrual_history', 'medical_surgical_history',
    'family_history', 'allergic_history', 'vaccination_history', 'patient_history_block',
    'morbidities', 'morbidities_block',
    'painscale_img',
    'complaint', 'diagnosis', 'provisional_diagnosis', 'finding_examinations',
    'complaint_onset', 'complaint_duration_days', 'complaint_severity',
    'complaint_snomed_json',
    'diagnosis_json',
    'diagnosis_snomed_id', 'diagnosis_snomed_term', 'diagnosis_snomed_source',
    'provisional_diagnosis_snomed_id', 'provisional_diagnosis_snomed_term', 'provisional_diagnosis_snomed_source',
    'complaint_list', 'diagnosis_list', 'provisional_diagnosis_list',
    'medical', 'investigation', 'prescriber_remarks', 'advice', 'next_visit', 'refer_to',
    'rx', 'rxtable', 'rxfullblock',
    'vitalsblock', 'complaintblock', 'diagnosisblock', 'investigationblock',
    'remarksblock', 'adviceblock', 'nextvisitblock',
    'content_section', 'content',
];

foreach (($placeholders ?? []) as $ph) {
    $key = strtolower((string) $ph);
    if (in_array($key, $hospitalSet, true)) {
        $placeholderGroups['hospital'][] = $ph;
    } elseif (in_array($key, $patientSet, true)) {
        $placeholderGroups['patient'][] = $ph;
    } elseif (in_array($key, $opdMasterSet, true)) {
        $placeholderGroups['opd_master'][] = $ph;
    } elseif (in_array($key, $opdConsultSet, true)) {
        $placeholderGroups['opd_consult'][] = $ph;
    } else {
        $placeholderGroups['other'][] = $ph;
    }
}

foreach ($placeholderGroups as $groupName => $groupValues) {
    $seen = [];
    $filtered = [];
    foreach ($groupValues as $groupValue) {
        $dedupeKey = strtolower((string) $groupValue);
        if (isset($seen[$dedupeKey])) {
            continue;
        }
        $seen[$dedupeKey] = true;
        $filtered[] = $groupValue;
    }
    $groupValues = $filtered;
    natcasesort($groupValues);
    $placeholderGroups[$groupName] = array_values($groupValues);
}
?>

<style>
.opd-template-builder .box {
    margin-bottom: 12px;
}

.opd-template-builder .box-title {
    font-weight: 600;
}

.opd-template-builder .section-note {
    color: #666;
    font-size: 12px;
    margin-bottom: 8px;
}

.opd-template-builder .template-actions {
    padding-top: 25px;
    text-align: right;
}

.opd-template-builder .template-actions .btn {
    margin-left: 6px;
}

.opd-template-builder .placeholder-list {
    max-height: 300px;
    overflow: auto;
    border: 1px solid #eee;
    padding: 8px;
    border-radius: 4px;
    background: #fafafa;
}

.opd-template-builder .placeholder-chip {
    display: inline-block;
    margin: 2px;
    padding: 2px 6px;
    border: 1px solid #d8d8d8;
    border-radius: 3px;
    background: #fff;
    font-size: 11px;
    color: #444;
}

.opd-template-builder .placeholder-group {
    margin-bottom: 10px;
}

.opd-template-builder .placeholder-group-title {
    font-size: 12px;
    font-weight: 600;
    color: #555;
    margin-bottom: 6px;
}

.opd-template-builder .vars-list {
    line-height: 1.7;
    max-height: 240px;
    overflow: auto;
    border: 1px solid #d9edf7;
    border-radius: 4px;
    padding: 8px;
    background: #f9fcff;
}

.opd-template-builder textarea {
    resize: vertical;
}

.opd-template-builder .group-spacer {
    margin-top: 10px;
}

.opd-template-builder .preview-frame {
    width: 100%;
    height: 560px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #fff;
}
</style>

<section class="content opd-template-builder">
    <div class="row">
        <div class="col-md-6">
            <div class="box box-success" id="opd-paper-settings">
                <div class="box-header with-border">
                    <h3 class="box-title">OPD Paper Print Settings</h3>
                </div>
                <div class="box-body">
                    <div id="opd_paper_msg" class="text-muted" style="margin-bottom:8px;"></div>
                    <div class="form-group">
                        <label>Page Size</label>
                        <select id="opd_page_size" class="form-control input-sm">
                            <option value="A4" <?= $paperPageSize === 'A4' ? 'selected' : '' ?>>A4</option>
                            <option value="A4-L" <?= $paperPageSize === 'A4-L' ? 'selected' : '' ?>>A4 Landscape</option>
                            <option value="A5" <?= $paperPageSize === 'A5' ? 'selected' : '' ?>>A5</option>
                            <option value="A6" <?= $paperPageSize === 'A6' ? 'selected' : '' ?>>A6</option>
                            <option value="LETTER" <?= $paperPageSize === 'LETTER' ? 'selected' : '' ?>>Letter</option>
                            <option value="LEGAL" <?= $paperPageSize === 'LEGAL' ? 'selected' : '' ?>>Legal</option>
                            <option value="CUSTOM" <?= $paperPageSize === 'CUSTOM' ? 'selected' : '' ?>>Custom (mm)</option>
                        </select>
                    </div>

                    <div class="row group-spacer" id="opd_custom_size_row" style="display:none; margin-bottom:8px;">
                        <div class="col-xs-6">
                            <label>Custom Width (mm)</label>
                            <input type="number" step="1" min="20" max="600" id="opd_custom_width" class="form-control input-sm" value="<?= esc($paperCustomWidth) ?>">
                        </div>
                        <div class="col-xs-6">
                            <label>Custom Height (mm)</label>
                            <input type="number" step="1" min="20" max="1000" id="opd_custom_height" class="form-control input-sm" value="<?= esc($paperCustomHeight) ?>">
                        </div>
                    </div>

                    <div class="row" style="margin-top:6px;">
                        <div class="col-md-4 col-sm-6 col-xs-6" style="margin-bottom:8px;">
                            <label>Top (cm)</label>
                            <input type="number" step="0.1" min="0" max="25" id="opd_margin_top" class="form-control input-sm" value="<?= esc((string) ($paperSettings['page_margin_top_cm'] ?? '6.1')) ?>">
                        </div>
                        <div class="col-md-4 col-sm-6 col-xs-6" style="margin-bottom:8px;">
                            <label>Bottom (cm)</label>
                            <input type="number" step="0.1" min="0" max="25" id="opd_margin_bottom" class="form-control input-sm" value="<?= esc((string) ($paperSettings['page_margin_bottom_cm'] ?? '2.5')) ?>">
                        </div>
                        <div class="col-md-4 col-sm-6 col-xs-6" style="margin-bottom:8px;">
                            <label>Left (cm)</label>
                            <input type="number" step="0.1" min="0" max="25" id="opd_margin_left" class="form-control input-sm" value="<?= esc((string) ($paperSettings['page_margin_left_cm'] ?? '0.7')) ?>">
                        </div>
                        <div class="col-md-4 col-sm-6 col-xs-6" style="margin-bottom:8px;">
                            <label>Right (cm)</label>
                            <input type="number" step="0.1" min="0" max="25" id="opd_margin_right" class="form-control input-sm" value="<?= esc((string) ($paperSettings['page_margin_right_cm'] ?? '0.7')) ?>">
                        </div>
                        <div class="col-md-4 col-sm-6 col-xs-6" style="margin-bottom:8px;">
                            <label>Header (cm)</label>
                            <input type="number" step="0.1" min="0" max="25" id="opd_margin_header" class="form-control input-sm" value="<?= esc((string) ($paperSettings['margin_header_cm'] ?? '0.5')) ?>">
                        </div>
                        <div class="col-md-4 col-sm-6 col-xs-6" style="margin-bottom:8px;">
                            <label>Footer (cm)</label>
                            <input type="number" step="0.1" min="0" max="25" id="opd_margin_footer" class="form-control input-sm" value="<?= esc((string) ($paperSettings['margin_footer_cm'] ?? '1.5')) ?>">
                        </div>
                    </div>

                    <div class="form-group group-spacer">
                        <label>Header HTML</label>
                        <textarea id="opd_header_html" class="form-control input-sm" rows="4" style="font-family:Consolas,monospace;"><?= esc((string) ($paperSettings['header_html'] ?? '')) ?></textarea>
                    </div>

                    <div class="form-group group-spacer">
                        <label>Footer HTML</label>
                        <textarea id="opd_footer_html" class="form-control input-sm" rows="4" style="font-family:Consolas,monospace;"><?= esc((string) ($paperSettings['footer_html'] ?? '')) ?></textarea>
                    </div>

                    <div class="form-group group-spacer">
                        <label>HTML Content</label>
                        <textarea id="opd_paper_html_content" class="form-control input-sm" rows="8" style="font-family:Consolas,monospace;"><?= esc((string) ($paperSettings['paper_html_content'] ?? '')) ?></textarea>
                    </div>

                    <div class="form-group group-spacer">
                        <label>Custom CSS (for template-specific layout)</label>
                        <textarea id="opd_custom_style_css" class="form-control input-sm" rows="6" style="font-family:Consolas,monospace;" placeholder=".RxPlace { position:absolute; top:73mm; left:70mm; width:135mm; }"></textarea>
                    </div>

                    <div class="small text-muted" style="margin-bottom:8px;">
                        <strong>How it works:</strong><br>
                        1. Margin values (cm) auto-populate the CSS @page block<br>
                        2. Header/Footer HTML define print header/footer blocks<br>
                        3. HTML Content is the main body between header and footer<br>
                        4. Custom CSS is optional and applies to template body elements (for exact letterhead positioning)
                    </div>

                    <button type="button" class="btn btn-success btn-sm" id="btn_save_opd_paper">Save OPD Print Settings</button>
                    <button type="button" class="btn btn-default btn-sm" id="btn_reset_opd_paper">Reset</button>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Template Preview: <?= esc($selectedTemplateName) ?></h3>
                    <div class="box-tools">
                        <button type="button" class="btn btn-default btn-sm" id="btn_back_to_list">Back to List</button>
                    </div>
                </div>
                <div class="box-body">
                    <?= csrf_field() ?>
                    <p class="section-note">Preview uses paper settings and renders in print-page style for mPDF-like output.</p>
                    <input type="hidden" id="tmpl_name" value="<?= esc($selectedTemplateName) ?>">
                    <input type="hidden" id="tmpl_original_name" value="<?= esc($selectedTemplateName) ?>">
                    <div class="form-group group-spacer">
                        <label>Preview Output</label>
                        <iframe id="tmpl_preview_frame" class="preview-frame"></iframe>
                    </div>
                    <div id="tmpl_msg" class="text-muted"></div>
                </div>
            </div>

            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">All Placeholders</h3>
                </div>
                <div class="box-body placeholder-list">
                    <div class="form-group" style="margin-bottom:8px;">
                        <input type="text" id="placeholder_search" class="form-control input-sm" placeholder="Search placeholders...">
                    </div>

                    <div class="placeholder-group" data-group="hospital">
                        <div class="placeholder-group-title">1. Hospital Related Placeholders</div>
                        <?php foreach (($placeholderGroups['hospital'] ?? []) as $ph) : ?>
                            <span class="placeholder-chip" data-placeholder="<?= esc(strtolower((string) $ph)) ?>">{{<?= esc($ph) ?>}}</span>
                        <?php endforeach; ?>
                    </div>

                    <div class="placeholder-group" data-group="patient">
                        <div class="placeholder-group-title">2. Patient Information</div>
                        <?php foreach (($placeholderGroups['patient'] ?? []) as $ph) : ?>
                            <span class="placeholder-chip" data-placeholder="<?= esc(strtolower((string) $ph)) ?>">{{<?= esc($ph) ?>}}</span>
                        <?php endforeach; ?>
                    </div>

                    <div class="placeholder-group" data-group="opd_master">
                        <div class="placeholder-group-title">3. OPD Master : OPD Date, Fee, Doctor Info</div>
                        <?php foreach (($placeholderGroups['opd_master'] ?? []) as $ph) : ?>
                            <span class="placeholder-chip" data-placeholder="<?= esc(strtolower((string) $ph)) ?>">{{<?= esc($ph) ?>}}</span>
                        <?php endforeach; ?>
                    </div>

                    <div class="placeholder-group" data-group="opd_consult">
                        <div class="placeholder-group-title">4. OPD Consult : from OPD Consult Form</div>
                        <?php foreach (($placeholderGroups['opd_consult'] ?? []) as $ph) : ?>
                            <span class="placeholder-chip" data-placeholder="<?= esc(strtolower((string) $ph)) ?>">{{<?= esc($ph) ?>}}</span>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!empty($placeholderGroups['other'] ?? [])) : ?>
                        <div class="placeholder-group" data-group="other">
                            <div class="placeholder-group-title">Other</div>
                            <?php foreach (($placeholderGroups['other'] ?? []) as $ph) : ?>
                                <span class="placeholder-chip" data-placeholder="<?= esc(strtolower((string) $ph)) ?>">{{<?= esc($ph) ?>}}</span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Placeholder Usage</h3>
                </div>
                <div class="box-body small vars-list">
                    <strong>Use placeholders with double curly braces:</strong><br>
                    <code>{{pName}}</code>, <code>{{opd_date}}</code>, <code>{{doctor_name}}</code><br><br>

                    <strong>Example:</strong><br>
                    Patient: <code>{{pName}}</code><br>
                    Date: <code>{{opd_date}}</code><br>
                    Doctor: <code>{{doctor_name}}</code><br><br>

                    <strong>Rule:</strong> The complete list is shown in <em>All Placeholders</em> above.
                </div>
            </div>

            <div class="alert alert-info">
                <strong>Old-style (recommended):</strong> save a name (example: <strong>opd_print_parcha_chamunda_hospital_ksp</strong>), then set that name in doctor master fields:<br>
                <code>opd_print_format</code>, <code>opd_blank_print</code>, <code>rx_pre_print_letter_head_format</code>, <code>rx_blank_letter_head</code>, <code>rx_plain_paper</code>.<br><br>
                <strong>Optional composed mode:</strong> use <code>compose_default</code> with type-specific parts.
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    var btnBack = document.getElementById('btn_back_to_list');
    if (btnBack) {
        btnBack.addEventListener('click', function () {
            load_form_div('<?= base_url('Opd/print_template_builder') ?>?mode=list', 'maindiv', 'OPD Template List');
        });
    }

    function getCsrfPair() {
        var input = document.querySelector('input[name="<?= csrf_token() ?>"]');
        if (!input) {
            return { name: '<?= csrf_token() ?>', value: '<?= csrf_hash() ?>' };
        }
        return { name: input.getAttribute('name'), value: input.value };
    }

    function setMsg(msg, ok) {
        var el = document.getElementById('tmpl_msg');
        el.className = ok ? 'text-success' : 'text-danger';
        el.textContent = msg || '';
    }

    function setPaperMsg(msg, ok) {
        var el = document.getElementById('opd_paper_msg');
        if (!el) {
            return;
        }
        el.className = ok ? 'text-success' : 'text-danger';
        el.textContent = msg || '';
    }

    function normalizeTemplateId(value) {
        value = (value || '').trim().toLowerCase().replace(/[ .]+/g, '_').replace(/[^a-z0-9_\-]+/g, '_').replace(/_+/g, '_');
        return value.replace(/^[_-]+|[_-]+$/g, '');
    }

    var placeholderSearch = document.getElementById('placeholder_search');
    if (placeholderSearch) {
        placeholderSearch.addEventListener('input', function () {
            var query = (placeholderSearch.value || '').toLowerCase().trim();
            var groups = document.querySelectorAll('.placeholder-group');

            groups.forEach(function (groupEl) {
                var chips = groupEl.querySelectorAll('.placeholder-chip');
                var visibleCount = 0;

                chips.forEach(function (chipEl) {
                    var key = (chipEl.getAttribute('data-placeholder') || '').toLowerCase();
                    var isVisible = !query || key.indexOf(query) !== -1;
                    chipEl.style.display = isVisible ? 'inline-block' : 'none';
                    if (isVisible) {
                        visibleCount += 1;
                    }
                });

                groupEl.style.display = visibleCount > 0 ? 'block' : 'none';
            });
        });
    }

    function toggleCustomSize() {
        var pageSize = document.getElementById('opd_page_size');
        var customRow = document.getElementById('opd_custom_size_row');
        if (!pageSize || !customRow) {
            return;
        }
        customRow.style.display = (pageSize.value === 'CUSTOM') ? 'block' : 'none';
    }

    function buildTemplateFromPaperFields() {
        var top = document.getElementById('opd_margin_top');
        var bottom = document.getElementById('opd_margin_bottom');
        var left = document.getElementById('opd_margin_left');
        var right = document.getElementById('opd_margin_right');
        var marginHeader = document.getElementById('opd_margin_header');
        var marginFooter = document.getElementById('opd_margin_footer');
        var header = document.getElementById('opd_header_html');
        var footer = document.getElementById('opd_footer_html');
        var body = document.getElementById('opd_paper_html_content');
        var customCss = document.getElementById('opd_custom_style_css');

        var pageBlock = '<style>@page {\n'
            + 'margin-top: ' + ((top && top.value) || '6.1') + 'cm;\n'
            + 'margin-bottom: ' + ((bottom && bottom.value) || '2.5') + 'cm;\n'
            + 'margin-left: ' + ((left && left.value) || '0.7') + 'cm;\n'
            + 'margin-right: ' + ((right && right.value) || '0.7') + 'cm;\n'
            + 'margin-header: ' + ((marginHeader && marginHeader.value) || '0.5') + 'cm;\n'
            + 'margin-footer: ' + ((marginFooter && marginFooter.value) || '1.5') + 'cm;\n'
            + 'header: html_myHeader;\n'
            + 'footer: html_myFooter;\n'
            + '}\n</style>\n\n';

        var headerBlock = '';
        var headerText = (header && header.value) ? header.value.trim() : '';
        if (headerText !== '') {
            if (/<\s*htmlpageheader\b/i.test(headerText)) {
                headerBlock = headerText + '\n\n';
            } else {
                headerBlock = '<htmlpageheader name="myHeader">\n' + headerText + '\n</htmlpageheader>\n\n';
            }
        }

        var footerBlock = '';
        var footerText = (footer && footer.value) ? footer.value.trim() : '';
        if (footerText !== '') {
            if (/<\s*htmlpagefooter\b/i.test(footerText)) {
                footerBlock = footerText + '\n\n';
            } else {
                footerBlock = '<htmlpagefooter name="myFooter">\n' + footerText + '\n</htmlpagefooter>\n\n';
            }
        }

        var bodyText = (body && body.value) ? body.value.trim() : '';

        var customCssText = (customCss && customCss.value) ? customCss.value.trim() : '';
        var customStyleBlock = customCssText !== '' ? '<style>\n' + customCssText + '\n</style>\n\n' : '';

        return pageBlock + headerBlock + footerBlock + customStyleBlock + bodyText;
    }

    function splitCustomCssAndBody(content) {
        var source = (content || '').trim();
        var cssList = [];

        source = source.replace(/<style\b[^>]*>([\s\S]*?)<\/style>/gi, function (full, inner) {
            var cssText = (inner || '').trim();
            if (!cssText) {
                return '';
            }

            if (/@page\b/i.test(cssText)
                || /header\s*:\s*html_myHeader/i.test(cssText)
                || /footer\s*:\s*html_myFooter/i.test(cssText)) {
                return '';
            }

            cssList.push(cssText);
            return '';
        });

        return {
            bodyHtml: source.trim(),
            customCss: cssList.join('\n\n').trim()
        };
    }

    function hydrateCustomCssFromBody() {
        var bodyEl = document.getElementById('opd_paper_html_content');
        var cssEl = document.getElementById('opd_custom_style_css');
        if (!bodyEl || !cssEl) {
            return;
        }

        var parsed = splitCustomCssAndBody(bodyEl.value || '');
        bodyEl.value = parsed.bodyHtml || '';
        cssEl.value = parsed.customCss || '';
    }

    function stripMpdfBlocks(html) {
        var source = (html || '');
        var headerHtml = '';
        var footerHtml = '';
        var customCss = [];

        // Extract CSS first from the whole source so styles inside header/footer
        // are preserved in preview (mPDF applies CSS globally).
        source = source.replace(/<style\b[^>]*>([\s\S]*?)<\/style>/gi, function (_, inner) {
            var cssText = (inner || '').trim();
            if (!cssText) {
                return '';
            }

            if (/@page\b/i.test(cssText)
                || /header\s*:\s*html_myHeader/i.test(cssText)
                || /footer\s*:\s*html_myFooter/i.test(cssText)) {
                return '';
            }

            customCss.push(cssText);
            return '';
        });

        source = source.replace(/<htmlpageheader\b[^>]*name\s*=\s*["']myHeader["'][^>]*>([\s\S]*?)<\/htmlpageheader>/i, function (_, inner) {
            headerHtml = inner || '';
            return '';
        });

        source = source.replace(/<htmlpagefooter\b[^>]*name\s*=\s*["']myFooter["'][^>]*>([\s\S]*?)<\/htmlpagefooter>/i, function (_, inner) {
            footerHtml = inner || '';
            return '';
        });

        source = source.replace(/header\s*:\s*html_myHeader\s*;?/gi, '');
        source = source.replace(/footer\s*:\s*html_myFooter\s*;?/gi, '');

        return {
            bodyHtml: source.trim(),
            headerHtml: headerHtml.trim(),
            footerHtml: footerHtml.trim(),
            customCss: customCss.join('\n\n').trim()
        };
    }

    function buildMpdfLikePreview(html) {
        var parsed = stripMpdfBlocks(html);

        var top = (document.getElementById('opd_margin_top') || {}).value || '6.1';
        var bottom = (document.getElementById('opd_margin_bottom') || {}).value || '2.5';
        var left = (document.getElementById('opd_margin_left') || {}).value || '0.7';
        var right = (document.getElementById('opd_margin_right') || {}).value || '0.7';

        var topCm = parseFloat(top) || 6.1;
        var bottomCm = parseFloat(bottom) || 2.5;
        var leftCm = parseFloat(left) || 0.7;
        var rightCm = parseFloat(right) || 0.7;
        var headerCm = parseFloat((document.getElementById('opd_margin_header') || {}).value || '0.5') || 0.5;
        var footerCm = parseFloat((document.getElementById('opd_margin_footer') || {}).value || '1.5') || 1.5;

        var pageWidth = '210mm';
        var pageHeight = '297mm';

        var pageSize = ((document.getElementById('opd_page_size') || {}).value || 'A4').toUpperCase();
        if (pageSize === 'A4-L') {
            pageWidth = '297mm';
            pageHeight = '210mm';
        } else if (pageSize === 'A5') {
            pageWidth = '148mm';
            pageHeight = '210mm';
        } else if (pageSize === 'A6') {
            pageWidth = '105mm';
            pageHeight = '148mm';
        } else if (pageSize === 'LETTER') {
            pageWidth = '216mm';
            pageHeight = '279mm';
        } else if (pageSize === 'LEGAL') {
            pageWidth = '216mm';
            pageHeight = '356mm';
        } else if (pageSize === 'CUSTOM') {
            var cw = ((document.getElementById('opd_custom_width') || {}).value || '210');
            var ch = ((document.getElementById('opd_custom_height') || {}).value || '297');
            pageWidth = cw + 'mm';
            pageHeight = ch + 'mm';
        }

        var liveHeader = ((document.getElementById('opd_header_html') || {}).value || '').trim();
        var liveFooter = ((document.getElementById('opd_footer_html') || {}).value || '').trim();
        var finalHeader = liveHeader !== '' ? liveHeader : (parsed.headerHtml || '').trim();
        var finalFooter = liveFooter !== '' ? liveFooter : (parsed.footerHtml || '').trim();

        var liveCustomCss = ((document.getElementById('opd_custom_style_css') || {}).value || '').trim();
        var finalCustomCss = liveCustomCss !== '' ? liveCustomCss : (parsed.customCss || '').trim();

        var hasHeader = finalHeader !== '';
        var hasFooter = finalFooter !== '';

        // Collapse extra white space when header/footer blocks are empty.
        var contentTopCm = hasHeader ? topCm : Math.max(0.4, topCm * 0.35);
        var contentBottomCm = hasFooter ? bottomCm : Math.max(0.4, bottomCm * 0.35);

        // Keep footer visually near page bottom like mPDF page footer behavior.
        // We reserve footer space in content padding so body text does not overlap footer.
        var footerReserveCm = hasFooter ? Math.max(contentBottomCm, footerCm + 0.6) : contentBottomCm;

        var headerBlock = hasHeader
            ? '<div class="header" style="padding:' + Math.max(0.2, headerCm * 0.6) + 'cm ' + leftCm + 'cm ' + Math.max(0.2, headerCm * 0.5) + 'cm ' + leftCm + 'cm;border-bottom:1px solid #eee;">' + finalHeader + '</div>'
            : '';

        var footerBlock = hasFooter
            ? '<div class="footer" style="padding:' + Math.max(0.2, footerCm * 0.4) + 'cm ' + leftCm + 'cm ' + Math.max(0.2, footerCm * 0.5) + 'cm ' + leftCm + 'cm;border-top:1px solid #eee;">' + finalFooter + '</div>'
            : '';

        return '<!doctype html><html><head><meta charset="utf-8">'
            + '<style>'
            + 'html,body{margin:0;padding:0;background:#f0f2f5;font-family:Arial,sans-serif;}'
            + '.sheet{width:' + pageWidth + ';min-height:' + pageHeight + ';margin:12px auto;background:#fff;box-shadow:0 2px 10px rgba(0,0,0,.15);display:flex;flex-direction:column;overflow:hidden;}'
            + '.header{flex:0 0 auto;}'
            + '.content{flex:1 1 auto;padding:' + contentTopCm + 'cm ' + rightCm + 'cm ' + footerReserveCm + 'cm ' + leftCm + 'cm;}'
            + '.footer{flex:0 0 auto;margin-top:auto;}'
            + finalCustomCss
            + '</style></head><body>'
            + '<div class="sheet">'
            + headerBlock
            + '<div class="content">' + (parsed.bodyHtml || '') + '</div>'
            + footerBlock
            + '</div></body></html>';
    }

    function renderTemplatePreview() {
        var frame = document.getElementById('tmpl_preview_frame');
        if (!frame) {
            return;
        }

        // Preview must always reflect current control values on this page.
        var html = buildTemplateFromPaperFields();
        frame.srcdoc = buildMpdfLikePreview(html);
    }

    var pageSizeSelect = document.getElementById('opd_page_size');
    if (pageSizeSelect) {
        pageSizeSelect.addEventListener('change', toggleCustomSize);
    }
    toggleCustomSize();
    hydrateCustomCssFromBody();

    var previewInputIds = [
        'opd_page_size', 'opd_custom_width', 'opd_custom_height',
        'opd_margin_top', 'opd_margin_bottom', 'opd_margin_left', 'opd_margin_right',
        'opd_margin_header', 'opd_margin_footer',
        'opd_header_html', 'opd_footer_html', 'opd_paper_html_content', 'opd_custom_style_css'
    ];

    previewInputIds.forEach(function (id) {
        var el = document.getElementById(id);
        if (!el) {
            return;
        }
        el.addEventListener('input', renderTemplatePreview);
        el.addEventListener('change', renderTemplatePreview);
    });

    renderTemplatePreview();

    var savePaperBtn = document.getElementById('btn_save_opd_paper');
    if (savePaperBtn) {
        savePaperBtn.addEventListener('click', function () {
            var csrf = getCsrfPair();
            var currentTemplateName = normalizeTemplateId(document.getElementById('tmpl_name').value || '');
            var payload = {
                template_name: currentTemplateName,
                page_size: (document.getElementById('opd_page_size').value || 'A4'),
                custom_width_mm: document.getElementById('opd_custom_width').value || '210',
                custom_height_mm: document.getElementById('opd_custom_height').value || '297',
                page_margin_top_cm: document.getElementById('opd_margin_top').value || '6.1',
                page_margin_bottom_cm: document.getElementById('opd_margin_bottom').value || '2.5',
                page_margin_left_cm: document.getElementById('opd_margin_left').value || '0.7',
                page_margin_right_cm: document.getElementById('opd_margin_right').value || '0.7',
                margin_header_cm: document.getElementById('opd_margin_header').value || '0.5',
                margin_footer_cm: document.getElementById('opd_margin_footer').value || '1.5',
                header_html: document.getElementById('opd_header_html').value || '',
                footer_html: document.getElementById('opd_footer_html').value || '',
                paper_html_content: (function () {
                    var bodyVal = document.getElementById('opd_paper_html_content').value || '';
                    var cssVal = document.getElementById('opd_custom_style_css').value || '';
                    cssVal = cssVal.trim();
                    if (!cssVal) {
                        return bodyVal;
                    }
                    return '<style>\n' + cssVal + '\n</style>\n\n' + bodyVal;
                })()
            };
            payload[csrf.name] = csrf.value;

            savePaperBtn.disabled = true;

            $.ajax({
                url: '<?= base_url('Opd/paper_print_settings') ?>',
                method: 'POST',
                data: payload,
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).done(function (res) {
                if (res && res.csrfName && res.csrfHash) {
                    var tokenInput = document.querySelector('input[name="' + res.csrfName + '"]');
                    if (tokenInput) tokenInput.value = res.csrfHash;
                }
                if (!res || res.status !== 'success') {
                    setPaperMsg((res && res.notice) ? res.notice : 'Unable to save OPD print settings', false);
                    return;
                }

                // Keep template library in sync when user saves paper settings from edit page.
                if (currentTemplateName) {
                    var tmplCsrf = getCsrfPair();
                    var templatePayload = {
                        section: 'full',
                        name: currentTemplateName,
                        content: buildTemplateFromPaperFields()
                    };
                    templatePayload[tmplCsrf.name] = tmplCsrf.value;

                    $.post('<?= base_url('Opd/print_template_save') ?>', templatePayload, function (tmplRes) {
                        if (tmplRes && tmplRes.csrfName && tmplRes.csrfHash) {
                            var tmplTokenInput = document.querySelector('input[name="' + tmplRes.csrfName + '"]');
                            if (tmplTokenInput) tmplTokenInput.value = tmplRes.csrfHash;
                        }

                        if (!tmplRes || Number(tmplRes.update || 0) !== 1) {
                            setPaperMsg((res.notice || 'OPD print settings saved.') + ' But template id save failed.', false);
                            setMsg((tmplRes && tmplRes.error_text) ? tmplRes.error_text : 'Unable to save template id', false);
                            return;
                        }

                        setMsg('Saved: full/' + (tmplRes.name || currentTemplateName), true);
                        setPaperMsg((res.notice || 'OPD print settings saved.') + ' Template id synced: ' + (tmplRes.name || currentTemplateName), true);
                    }, 'json').fail(function () {
                        setPaperMsg((res.notice || 'OPD print settings saved.') + ' But template id save failed.', false);
                        setMsg('Unable to save template id', false);
                    });
                } else {
                    setPaperMsg((res.notice || 'OPD print settings saved.'), true);
                }

                renderTemplatePreview();
            }).fail(function () {
                setPaperMsg('Unable to save OPD print settings', false);
            }).always(function () {
                savePaperBtn.disabled = false;
            });
        });
    }

    var resetPaperBtn = document.getElementById('btn_reset_opd_paper');
    if (resetPaperBtn) {
        resetPaperBtn.addEventListener('click', function () {
            var csrf = getCsrfPair();
            var currentTemplateName = normalizeTemplateId(document.getElementById('tmpl_name').value || '');
            var payload = {
                reset: 1,
                template_name: currentTemplateName
            };
            payload[csrf.name] = csrf.value;

            resetPaperBtn.disabled = true;

            $.ajax({
                url: '<?= base_url('Opd/paper_print_settings') ?>',
                method: 'POST',
                data: payload,
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).done(function (res) {
                if (res && res.csrfName && res.csrfHash) {
                    var tokenInput = document.querySelector('input[name="' + res.csrfName + '"]');
                    if (tokenInput) tokenInput.value = res.csrfHash;
                }

                if (!res || res.status !== 'success') {
                    setPaperMsg((res && res.notice) ? res.notice : 'Unable to reset OPD print settings', false);
                    return;
                }

                var settings = res.settings || {};
                document.getElementById('opd_page_size').value = settings.page_size || 'A4';
                document.getElementById('opd_custom_width').value = settings.custom_width_mm || '210';
                document.getElementById('opd_custom_height').value = settings.custom_height_mm || '297';
                document.getElementById('opd_margin_top').value = settings.page_margin_top_cm || '6.1';
                document.getElementById('opd_margin_bottom').value = settings.page_margin_bottom_cm || '2.5';
                document.getElementById('opd_margin_left').value = settings.page_margin_left_cm || '0.7';
                document.getElementById('opd_margin_right').value = settings.page_margin_right_cm || '0.7';
                document.getElementById('opd_margin_header').value = settings.margin_header_cm || '0.5';
                document.getElementById('opd_margin_footer').value = settings.margin_footer_cm || '1.5';
                document.getElementById('opd_header_html').value = settings.header_html || '';
                document.getElementById('opd_footer_html').value = settings.footer_html || '';
                document.getElementById('opd_paper_html_content').value = settings.paper_html_content || '';
                toggleCustomSize();
                hydrateCustomCssFromBody();
                renderTemplatePreview();

                setPaperMsg((res.notice || 'OPD print settings reset.'), true);
            }).fail(function () {
                setPaperMsg('Unable to reset OPD print settings', false);
            }).always(function () {
                resetPaperBtn.disabled = false;
            });
        });
    }
})();
</script>
