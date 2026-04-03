<section class="content-header">
    <div class="clearfix">
        <div style="float:left;">
            <h1>OPD Print Template Builder</h1>
        </div>
        <div style="float:right; margin-top:8px;">
            <div class="btn-group btn-group-sm" role="group" aria-label="Template Navigation">
                <a class="btn btn-info" href="javascript:load_form_div('<?= base_url('Opd/print_template_builder') ?>?mode=list','maindiv','OPD Template List');">Template List</a>
                <a class="btn btn-default" href="#opd-paper-settings">Paper Print Settings</a>
                <button type="button" class="btn btn-primary" disabled>Edit Template</button>
            </div>
        </div>
    </div>
</section>

<?php
$paperSettings = $paper_settings ?? [];
$paperPageSize = strtoupper((string) ($paperSettings['page_size'] ?? 'A4'));
$paperCustomWidth = (string) ($paperSettings['custom_width_mm'] ?? '210');
$paperCustomHeight = (string) ($paperSettings['custom_height_mm'] ?? '297');
?>

<section class="content">
    <div class="row">
        <div class="col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Edit Template Content</h3>
                    <div class="box-tools">
                        <button type="button" class="btn btn-default btn-sm" id="btn_back_to_list">Back to List</button>
                    </div>
                </div>
                <div class="box-body">
                    <?= csrf_field() ?>
                    <div class="row">
                        <div class="col-sm-6">
                            <label>Template Name</label>
                            <input type="text" id="tmpl_name" class="form-control" value="<?= esc($selected_name ?? 'default') ?>" placeholder="default">
                            <input type="hidden" id="tmpl_original_name" value="<?= esc($selected_name ?? '') ?>">
                        </div>
                        <div class="col-sm-6" style="padding-top:25px;">
                            <button type="button" class="btn btn-default" id="btn_new_part">New</button>
                            <button type="button" class="btn btn-default" id="btn_load_part">Edit</button>
                            <button type="button" class="btn btn-primary" id="btn_save_part">Save</button>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top:10px;">
                        <label>Template HTML Content (for template id save/load)</label>
                        <textarea id="tmpl_content" class="form-control" rows="10" style="font-family:Consolas,monospace;"><?= esc($template_content ?? '') ?></textarea>
                    </div>
                    <div id="tmpl_msg" class="text-muted"></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">Placeholders</h3>
                </div>
                <div class="box-body" style="max-height:280px; overflow:auto;">
                    <?php foreach (($placeholders ?? []) as $ph) : ?>
                        <span class="label label-default" style="display:inline-block; margin:2px;">{{<?= esc($ph) ?>}}</span>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="alert alert-info">
                <strong>Old-style (recommended):</strong> save a name (example: <strong>opd_print_parcha_chamunda_hospital_ksp</strong>), then set that name in doctor master fields:<br>
                <code>opd_print_format</code>, <code>opd_blank_print</code>, <code>rx_pre_print_letter_head_format</code>, <code>rx_blank_letter_head</code>, <code>rx_plain_paper</code>.<br><br>
                <strong>Optional composed mode:</strong> use <code>compose_default</code> with type-specific parts.
            </div>
        </div>
    </div>

    <div class="row" style="margin-top:10px;">
        <div class="col-md-12">
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

                    <div class="row" id="opd_custom_size_row" style="display:none; margin-top:-2px; margin-bottom:8px;">
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
                        <div class="col-md-2 col-sm-4 col-xs-6" style="margin-bottom:8px;">
                            <label>Top (cm)</label>
                            <input type="number" step="0.1" min="0" max="25" id="opd_margin_top" class="form-control input-sm" value="<?= esc((string) ($paperSettings['page_margin_top_cm'] ?? '6.1')) ?>">
                        </div>
                        <div class="col-md-2 col-sm-4 col-xs-6" style="margin-bottom:8px;">
                            <label>Bottom (cm)</label>
                            <input type="number" step="0.1" min="0" max="25" id="opd_margin_bottom" class="form-control input-sm" value="<?= esc((string) ($paperSettings['page_margin_bottom_cm'] ?? '2.5')) ?>">
                        </div>
                        <div class="col-md-2 col-sm-4 col-xs-6" style="margin-bottom:8px;">
                            <label>Left (cm)</label>
                            <input type="number" step="0.1" min="0" max="25" id="opd_margin_left" class="form-control input-sm" value="<?= esc((string) ($paperSettings['page_margin_left_cm'] ?? '0.7')) ?>">
                        </div>
                        <div class="col-md-2 col-sm-4 col-xs-6" style="margin-bottom:8px;">
                            <label>Right (cm)</label>
                            <input type="number" step="0.1" min="0" max="25" id="opd_margin_right" class="form-control input-sm" value="<?= esc((string) ($paperSettings['page_margin_right_cm'] ?? '0.7')) ?>">
                        </div>
                        <div class="col-md-2 col-sm-4 col-xs-6" style="margin-bottom:8px;">
                            <label>Header (cm)</label>
                            <input type="number" step="0.1" min="0" max="25" id="opd_margin_header" class="form-control input-sm" value="<?= esc((string) ($paperSettings['margin_header_cm'] ?? '0.5')) ?>">
                        </div>
                        <div class="col-md-2 col-sm-4 col-xs-6" style="margin-bottom:8px;">
                            <label>Footer (cm)</label>
                            <input type="number" step="0.1" min="0" max="25" id="opd_margin_footer" class="form-control input-sm" value="<?= esc((string) ($paperSettings['margin_footer_cm'] ?? '1.5')) ?>">
                        </div>
                    </div>

                    <div class="form-group" style="margin-top:8px;">
                        <label>Header HTML</label>
                        <textarea id="opd_header_html" class="form-control input-sm" rows="4" style="font-family:Consolas,monospace;"><?= esc((string) ($paperSettings['header_html'] ?? '')) ?></textarea>
                    </div>

                    <div class="form-group" style="margin-top:8px;">
                        <label>Footer HTML</label>
                        <textarea id="opd_footer_html" class="form-control input-sm" rows="4" style="font-family:Consolas,monospace;"><?= esc((string) ($paperSettings['footer_html'] ?? '')) ?></textarea>
                    </div>

                    <div class="form-group" style="margin-top:8px;">
                        <label>HTML Content</label>
                        <textarea id="opd_paper_html_content" class="form-control input-sm" rows="8" style="font-family:Consolas,monospace;"><?= esc((string) ($paperSettings['paper_html_content'] ?? '')) ?></textarea>
                    </div>

                    <div class="small text-muted" style="margin-bottom:8px;">
                        <strong>How it works:</strong><br>
                        1. Margin values (cm) auto-populate the CSS @page block<br>
                        2. <strong>Header HTML:</strong> Enter HTML for top of page (logo, hospital name, address, doctor info using {{variables}})<br>
                        3. <strong>Footer HTML:</strong> Enter HTML for bottom of page (print time, page number, etc. using {{variables}})<br>
                        4. <strong>HTML Content:</strong> Enter main body content between header and footer (patient info, diagnosis, etc.)<br><br>
                        <strong>Available variables:</strong><br>
                        Hospital: {{H_Name}}, {{H_address_1}}, {{H_address_2}}, {{H_phone_No}}, {{H_Email}}, {{H_logo}}<br>
                        Patient/OPD: {{pName}}, {{age_sex}}, {{phoneno}}, {{uhid_no}}, {{opd_no}}, {{opd_date}}<br>
                        Medical: {{Complaint}}, {{diagnosis}}, {{medical}}, {{advice}}, {{doctor_name}}, {{short_description}}, {{doctor_short_description}}, {{doctor_sign_html}}<br>
                        Dates: {{print_time}}, {{current_date}}<br>
                        Margins (cm): {{MarginTop}}, {{MarginBottom}}, {{MarginLeft}}, {{MarginRight}}, {{MarginHeader}}, {{MarginFooter}}
                    </div>

                    <button type="button" class="btn btn-success btn-sm" id="btn_save_opd_paper">Save OPD Print Settings</button>
                    <button type="button" class="btn btn-default btn-sm" id="btn_reset_opd_paper">Reset</button>
                </div>
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

    document.getElementById('btn_load_part').addEventListener('click', function () {
        var name = normalizeTemplateId(document.getElementById('tmpl_name').value || 'default');
        if (!name) { name = 'default'; }
        document.getElementById('tmpl_name').value = name;
        load_form_div('<?= base_url('Opd/print_template_builder') ?>?mode=edit&name=' + encodeURIComponent(name), 'maindiv', 'OPD Template Edit');
    });

    document.getElementById('btn_new_part').addEventListener('click', function () {
        document.getElementById('tmpl_name').value = '';
        document.getElementById('tmpl_content').value = '';
        document.getElementById('tmpl_original_name').value = '';
        setMsg('Enter new template name, then write HTML and click Save.', true);
    });

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
        return pageBlock + headerBlock + footerBlock + bodyText;
    }

    var pageSizeSelect = document.getElementById('opd_page_size');
    if (pageSizeSelect) {
        pageSizeSelect.addEventListener('change', toggleCustomSize);
    }
    toggleCustomSize();

    document.getElementById('btn_save_part').addEventListener('click', function () {
        var name = normalizeTemplateId(document.getElementById('tmpl_name').value || '');
        var originalName = normalizeTemplateId(document.getElementById('tmpl_original_name').value || '');
        var content = document.getElementById('tmpl_content').value || '';
        document.getElementById('tmpl_name').value = name;
        if (!content.trim()) {
            content = buildTemplateFromPaperFields();
            var tmplContentEl = document.getElementById('tmpl_content');
            if (tmplContentEl && content.trim()) {
                tmplContentEl.value = content;
            }
        }
        if (!name) {
            setMsg('Template name required', false);
            return;
        }
        var csrf = getCsrfPair();
        var payload = {
            section: 'full',
            name: name,
            content: content
        };
        payload[csrf.name] = csrf.value;

        var saveTemplate = function () {
            $.post('<?= base_url('Opd/print_template_save') ?>', payload, function (res) {
                if (res && res.csrfName && res.csrfHash) {
                    var tokenInput = document.querySelector('input[name="' + res.csrfName + '"]');
                    if (tokenInput) tokenInput.value = res.csrfHash;
                }
                if (!res || Number(res.update || 0) !== 1) {
                    setMsg((res && res.error_text) ? res.error_text : 'Unable to save template', false);
                    return;
                }
                document.getElementById('tmpl_original_name').value = (res.name || name);
                setMsg('Saved: ' + (res.section || 'full') + '/' + (res.name || name), true);
                setTimeout(function () {
                    load_form_div('<?= base_url('Opd/print_template_builder') ?>?mode=edit&name=' + encodeURIComponent(res.name || name), 'maindiv', 'OPD Template Edit');
                }, 250);
            }, 'json').fail(function () {
                setMsg('Unable to save template', false);
            });
        };

        if (originalName && originalName !== name) {
            var renamePayload = {
                old_name: originalName,
                new_name: name
            };
            var renameCsrf = getCsrfPair();
            renamePayload[renameCsrf.name] = renameCsrf.value;

            $.post('<?= base_url('Opd/print_template_rename') ?>', renamePayload, function (renameRes) {
                if (renameRes && renameRes.csrfName && renameRes.csrfHash) {
                    var renameTokenInput = document.querySelector('input[name="' + renameRes.csrfName + '"]');
                    if (renameTokenInput) renameTokenInput.value = renameRes.csrfHash;
                }

                if (!renameRes || Number(renameRes.update || 0) !== 1) {
                    setMsg((renameRes && renameRes.error_text) ? renameRes.error_text : 'Unable to rename template', false);
                    return;
                }

                document.getElementById('tmpl_original_name').value = name;
                saveTemplate();
            }, 'json').fail(function () {
                setMsg('Unable to rename template', false);
            });
            return;
        }

        saveTemplate();
    });

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
                paper_html_content: document.getElementById('opd_paper_html_content').value || ''
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
