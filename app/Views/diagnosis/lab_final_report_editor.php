<?php
$report = $report_format[0] ?? null;
$templates = $radiology_ultrasound_template ?? [];
$editReason = trim((string) ($edit_reason ?? ''));
?>

<form method="post" onsubmit="return false;">
<div class="card admin-card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h3 class="mb-0">Radiology Report Editor - <?= esc($report->report_name ?? 'Report') ?></h3>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="backToInvoiceEditor()">
            <i class="bi bi-arrow-left"></i> Back
        </button>
    </div>
    <div class="card-body">
        <?php if (!$report): ?>
            <div class="alert alert-danger">Report not found.</div>
        <?php else: ?>
            <input type="hidden" id="hid_value_req_id" name="req_id" value="<?= esc($report->id ?? '') ?>">
            <input type="hidden" id="report_mode" name="report_mode" value="xray">
            <input type="hidden" id="hid_value_report_name" value="<?= esc($report->report_name ?? '') ?>">
            <input type="hidden" id="invoice_id" value="<?= esc($report->charge_id ?? '') ?>">
            <input type="hidden" id="lab_type" value="<?= esc($report->lab_type ?? '') ?>">
            <input type="hidden" id="report_status" value="<?= esc((string) ($report->status ?? '0')) ?>">

            <?php
                $ipdId = (int) ($report->ipd_id ?? 0);
                $orgId = (int) ($report->org_id ?? 0);
                $patientType = 'Direct';
                if ($ipdId > 0) {
                    $patientType = 'IPD';
                } elseif ($orgId > 0) {
                    $patientType = 'TPA';
                }
            ?>

            <div class="card mb-3 border-light-subtle">
                <div class="card-body py-2">
                    <div class="fw-semibold mb-1">Patient Information</div>
                    <div class="row g-2 small">
                        <div class="col-md-3"><strong>Name:</strong> <?= esc($report->patient_name ?? '-') ?></div>
                        <div class="col-md-3"><strong>Invoice:</strong> <?= esc($report->invoice_code ?? (($report->charge_id ?? '') !== '' ? ('#' . (string) $report->charge_id) : '-')) ?></div>
                        <div class="col-md-2"><strong>Gender:</strong> <?= esc($report->gender_text ?? '-') ?></div>
                        <div class="col-md-2"><strong>Age:</strong> <?= esc($report->age_text ?? '-') ?></div>
                        <div class="col-md-2"><strong>Patient Type:</strong> <?= esc($patientType) ?></div>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-primary" onclick="update_report()">
                            <i class="bi bi-save"></i> Save
                        </button>
                        <button type="button" class="btn btn-success" onclick="report_final()">
                            <i class="bi bi-check-circle"></i> Verified
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="showImagingUploadsFromEditor()">
                            <i class="bi bi-images"></i> Show Upload Images
                        </button>
                        <button type="button" class="btn btn-outline-danger" onclick="runImagingAiDiagnosisFromEditor()">
                            <i class="bi bi-magic"></i> AI Diagnosis
                        </button>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="edit_reason" class="form-label"><strong>Edit Reason (NABH Audit)</strong></label>
                    <textarea id="edit_reason" class="form-control" rows="2" placeholder="Enter reason for report correction/clarification"><?= esc($editReason) ?></textarea>
                    <small class="text-muted">Required when saving changes to an already verified report.</small>
                </div>
            </div>

            <hr/>

            <div class="row g-3">
                <div class="col-md-8">
                    <div class="form-group">
                        <label class="form-label"><strong>Report Findings</strong></label>
                        <textarea id="HTMLShow" name="HTMLShow" class="form-control" rows="12"><?= $report->Report_Data ?? '' ?></textarea>
                        <script>
                            if (typeof CKEDITOR !== 'undefined') {
                                CKEDITOR.config.removePlugins = '';
                                CKEDITOR.replace('HTMLShow');
                            }
                        </script>
                    </div>

                    <hr/>

                    <div class="form-group">
                        <label class="form-label"><strong>Impression</strong></label>
                        <textarea id="report_data_Impression" name="report_data_Impression" class="form-control" rows="8"><?= $report->report_data_Impression ?? '' ?></textarea>
                        <script>
                            if (typeof CKEDITOR !== 'undefined') {
                                CKEDITOR.config.removePlugins = '';
                                CKEDITOR.replace('report_data_Impression', {
                                    toolbar: [
                                        ['Bold', 'Italic', 'Underline', '-', 'FontSize', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight'],
                                        ['NumberedList', 'BulletedList', '-', 'Link', 'Unlink', '-', 'Source']
                                    ]
                                });
                            }
                        </script>
                    </div>

                    <hr/>

                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-primary" onclick="update_report()">
                            <i class="bi bi-save"></i> Save Report
                        </button>
                        <button type="button" class="btn btn-success" onclick="report_final()">
                            <i class="bi bi-check-lg"></i> Mark as Verified
                        </button>
                    </div>
                </div>

                <div class="col-md-4" style="border-left:1px solid #e4ebf5; padding-left:12px;">
                    <label class="form-label"><strong>Templates</strong></label>
                    <div class="card border-light mb-2">
                        <div class="card-body p-2">
                            <div class="small fw-semibold mb-1">Apply Mode</div>
                            <div class="d-flex gap-2 flex-wrap mb-2">
                                <label class="form-check-label me-2">
                                    <input class="form-check-input" type="radio" name="tpl_apply_mode" value="replace" checked>
                                    Replace
                                </label>
                                <label class="form-check-label">
                                    <input class="form-check-input" type="radio" name="tpl_apply_mode" value="append">
                                    Append
                                </label>
                            </div>
                            <div class="d-flex gap-2 flex-wrap small">
                                <label class="form-check-label me-2">
                                    <input class="form-check-input" type="checkbox" id="tpl_apply_findings" checked>
                                    Findings
                                </label>
                                <label class="form-check-label">
                                    <input class="form-check-input" type="checkbox" id="tpl_apply_impression" checked>
                                    Impression
                                </label>
                            </div>
                        </div>
                    </div>
                    <input type="text" id="template_search" class="form-control form-control-sm" placeholder="Search templates..." autocomplete="off" />
                    <div id="templateList" style="max-height:60vh; overflow-y:auto; margin-top:8px;">
                        <?php foreach ($templates as $tpl): ?>
                            <?php
                                $tplName = (string) ($tpl->template_name ?? '');
                                $tplTitle = trim((string) ($tpl->title ?? ''));
                                $tplKeywords = trim((string) ($tpl->keywords ?? ''));
                                $tplCategory = trim((string) ($tpl->impression_cat ?? ''));
                                $isRelated = !empty($tpl->is_related);
                                $searchBlob = strtolower(trim($tplName . ' ' . $tplTitle . ' ' . $tplKeywords . ' ' . $tplCategory . ($isRelated ? ' related' : '')));
                            ?>
                            <div class="template-item mb-2" data-search="<?= esc($searchBlob) ?>">
                                <a href="javascript:set_template(<?= (int) ($tpl->id ?? 0) ?>)" class="d-block p-2 border rounded text-decoration-none <?= $isRelated ? 'border-primary bg-primary-subtle bg-opacity-10' : '' ?>">
                                    <div class="d-flex justify-content-between align-items-start gap-1">
                                        <div class="fw-semibold <?= $isRelated ? 'text-primary' : '' ?>"><?= esc($tplName) ?></div>
                                        <?php if ($isRelated): ?>
                                            <span class="badge bg-primary text-white" style="font-size:0.68rem;">Related</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($tplTitle !== ''): ?>
                                        <div class="small text-muted"><?= esc($tplTitle) ?></div>
                                    <?php endif; ?>
                                    <div class="mt-1 small">
                                        <?php if ($tplCategory !== ''): ?>
                                            <span class="badge text-bg-light border">Category: <?= esc($tplCategory) ?></span>
                                        <?php endif; ?>
                                        <?php if ($tplKeywords !== ''): ?>
                                            <span class="badge text-bg-light border">Tags: <?= esc($tplKeywords) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                        <div id="no_templates_msg" style="display:none; color:#888; padding:6px;">No templates found</div>
                    </div>
                </div>
            </div>

            <!-- Imaging Support Modal -->
            <div id="imagingSupportModal" class="modal fade" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="imagingSupportModalTitle">Imaging Support</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="imagingSupportModalBody" style="max-height: 70vh; overflow-y: auto;">
                            <!-- Content loaded dynamically -->
                        </div>
                    </div>
                </div>
            </div>

            <script>
            // Use global baseUrl if available
            if (typeof baseUrl === 'undefined') {
                window.baseUrl = '<?= base_url() ?>';
            }

            function backToInvoiceEditor() {
                const invoiceId = document.getElementById('invoice_id') ? document.getElementById('invoice_id').value : '';
                const labType = document.getElementById('lab_type') ? document.getElementById('lab_type').value : '';

                if (typeof load_form_div === 'function' && invoiceId && labType) {
                    load_form_div(baseUrl + 'diagnosis/select-lab-invoice/' + invoiceId + '/' + labType, 'searchresult', 'Diagnosis');
                    return;
                }

                if (typeof load_form === 'function' && invoiceId && labType) {
                    load_form(baseUrl + 'diagnosis/select-lab-invoice/' + invoiceId + '/' + labType, 'Diagnosis');
                    return;
                }

                window.history.back();
            }

            (function () {
                const input = document.getElementById('template_search');
                if (!input) {
                    return;
                }

                input.addEventListener('input', function () {
                    const q = (this.value || '').toLowerCase().trim();
                    const items = document.querySelectorAll('#templateList .template-item');
                    let count = 0;

                    items.forEach(function (item) {
                        const source = (item.getAttribute('data-search') || item.textContent || '').toLowerCase();
                        const show = source.indexOf(q) !== -1;
                        item.style.display = show ? '' : 'none';
                        if (show) {
                            count++;
                        }
                    });

                    const msg = document.getElementById('no_templates_msg');
                    if (msg) {
                        msg.style.display = count === 0 ? 'block' : 'none';
                    }
                });
            })();

            function getEditorValue(editorId) {
                if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances[editorId]) {
                    return CKEDITOR.instances[editorId].getData();
                }
                const el = document.getElementById(editorId);
                return el ? el.value : '';
            }

            function setEditorValue(editorId, value) {
                if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances[editorId]) {
                    CKEDITOR.instances[editorId].setData(value);
                    return;
                }
                const el = document.getElementById(editorId);
                if (el) {
                    el.value = value;
                }
            }

            function mergeTemplateContent(currentValue, incomingValue, mode) {
                if (mode !== 'append') {
                    return incomingValue;
                }

                const current = (currentValue || '').trim();
                const incoming = (incomingValue || '').trim();
                if (!current) {
                    return incoming;
                }
                if (!incoming) {
                    return current;
                }

                return current + '<hr>' + incoming;
            }

            // Modal helper function
            window._imagingSupportModalInstance = window._imagingSupportModalInstance || null;
            function openImagingSupportModal(title, html) {
                document.getElementById('imagingSupportModalTitle').textContent = title || 'Imaging Support';
                document.getElementById('imagingSupportModalBody').innerHTML = html;
                const el = document.getElementById('imagingSupportModal');
                window._imagingSupportModalInstance = bootstrap.Modal.getOrCreateInstance(el);
                window._imagingSupportModalInstance.show();
            }

            // Show imaging uploads (gallery)
            function showImagingUploadsFromEditor() {
                const reqId = document.getElementById('hid_value_req_id') ? document.getElementById('hid_value_req_id').value : '';
                const testName = document.getElementById('hid_value_report_name') ? document.getElementById('hid_value_report_name').value : '';
                if (!reqId) { alert('Request ID not found'); return; }
                showImagingUploads(reqId, testName);
            }

            function showImagingUploads(reqId, testName) {
                const invoiceId = document.getElementById('invoice_id') ? document.getElementById('invoice_id').value : '';
                const labType = document.getElementById('lab_type') ? document.getElementById('lab_type').value : '';
                const targetInvoice = invoiceId || reqId;
                fetch(baseUrl + 'diagnosis/imaging-upload-gallery/' + targetInvoice + '/' + (labType || '0') + '/' + reqId, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.text())
                .then(html => openImagingSupportModal(testName + ' - Uploaded Images', html))
                .catch(e => alert('Error loading images: ' + e.message));
            }

            // Run AI diagnosis
            function runImagingAiDiagnosisFromEditor() {
                const reqId = document.getElementById('hid_value_req_id').value;
                const testName = document.getElementById('hid_value_report_name').value;
                if (!reqId) { alert('Request ID not found'); return; }
                runImagingAiDiagnosis(reqId, testName);
            }

            function runImagingAiDiagnosis(reqId, testName) {
                const btn = (typeof event !== 'undefined' && event && event.target)
                    ? event.target.closest('button') : null;
                if (btn) btn.disabled = true;
                const indicator = document.createElement('span');
                indicator.textContent = ' Processing...';
                if (btn) btn.appendChild(indicator);

                openImagingSupportModal((testName || 'AI Diagnosis') + ' - AI Diagnosis',
                    '<div class="text-center py-4"><div class="spinner-border" role="status"></div>' +
                    '<div class="mt-2 text-muted">AI is reviewing uploaded images...</div></div>');

                const formData = new FormData();
                formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

                fetch(baseUrl + 'diagnosis/imaging-ai-diagnosis/' + reqId, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                })
                .then(r => r.json())
                .then(result => {
                    if ((result.update || 0) !== 1) {
                        openImagingSupportModal((testName || 'AI Diagnosis') + ' - AI Diagnosis',
                            '<div class="alert alert-danger mb-0">' + (result.error_text || 'AI diagnosis failed') + '</div>');
                        return;
                    }
                    openImagingSupportModal((testName || 'AI Diagnosis') + ' - AI Diagnosis',
                        result.html || '<div class="alert alert-warning mb-0">AI result not available.</div>');
                })
                .catch(e => {
                    openImagingSupportModal((testName || 'AI Diagnosis') + ' - AI Diagnosis',
                        '<div class="alert alert-danger mb-0">AI diagnosis request failed: ' + e.message + '</div>');
                })
                .finally(() => {
                    if (btn) {
                        btn.disabled = false;
                        if (indicator.parentNode) indicator.parentNode.removeChild(indicator);
                    }
                });
            }

            // Template functions
            function set_template(templateId) {
                fetch(baseUrl + 'diagnosis/get-template-xray/' + templateId, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(data => {
                    const findings = data.Findings || '';
                    const impression = data.Impression || '';

                    const selectedModeEl = document.querySelector('input[name="tpl_apply_mode"]:checked');
                    const mode = selectedModeEl ? selectedModeEl.value : 'replace';
                    const applyFindings = !!document.getElementById('tpl_apply_findings')?.checked;
                    const applyImpression = !!document.getElementById('tpl_apply_impression')?.checked;

                    if (!applyFindings && !applyImpression) {
                        alert('Select Findings and/or Impression to apply template.');
                        return;
                    }

                    if (applyFindings) {
                        const mergedFindings = mergeTemplateContent(getEditorValue('HTMLShow'), findings, mode);
                        setEditorValue('HTMLShow', mergedFindings);
                    }

                    if (applyImpression) {
                        const mergedImpression = mergeTemplateContent(getEditorValue('report_data_Impression'), impression, mode);
                        setEditorValue('report_data_Impression', mergedImpression);
                    }
                })
                .catch(e => console.error(e));
            }

            function update_report() {
                const reqId = document.getElementById('hid_value_req_id').value;
                if (!reqId) { alert('Invalid request'); return; }

                // Get data from CKEditor if available, otherwise from textarea
                let reportData = document.getElementById('HTMLShow').value;
                let impressionData = document.getElementById('report_data_Impression').value;

                if (typeof CKEDITOR !== 'undefined') {
                    if (CKEDITOR.instances.HTMLShow) {
                        reportData = CKEDITOR.instances.HTMLShow.getData();
                    }
                    if (CKEDITOR.instances.report_data_Impression) {
                        impressionData = CKEDITOR.instances.report_data_Impression.getData();
                    }
                }

                const data = new FormData();
                const editReason = (document.getElementById('edit_reason')?.value || '').trim();
                const reportStatus = Number(document.getElementById('report_status')?.value || 0);

                if (reportStatus === 2 && !editReason) {
                    alert('Edit reason is required for verified report changes (NABH audit).');
                    return;
                }

                data.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
                data.append('HTMLData', reportData);
                data.append('report_data_Impression', impressionData);
                data.append('report_data', reportData);
                data.append('report_data_impression', impressionData);
                data.append('edit_reason', editReason);

                fetch(baseUrl + 'diagnosis/update-report/' + reqId, {
                    method: 'POST',
                    body: data,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(result => {
                    alert(result.message || 'Report saved successfully');
                })
                .catch(e => alert('Error saving: ' + e.message));
            }

            function report_final() {
                const reqId = document.getElementById('hid_value_req_id').value;
                if (!reqId) { alert('Invalid request'); return; }

                if (!confirm('Mark this report as verified?')) return;

                const data = new FormData();
                data.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

                fetch(baseUrl + 'diagnosis/report-verify/' + reqId, {
                    method: 'POST',
                    body: data,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(result => {
                    alert(result.message || 'Report verified successfully');
                })
                .catch(e => alert('Error: ' + e.message));
            }

            // Paste AI diagnosis draft to editor
            function pasteAiDiagnosisDraft(button) {
                applyAiDiagnosisDraftToEditor(button, false);
            }

            function pasteAiDiagnosisDraftAndSave(button) {
                applyAiDiagnosisDraftToEditor(button, true);
            }

            function applyAiDiagnosisDraftToEditor(button, autoSave) {
                // Read HTML from hidden textareas injected by the AI result modal view
                const findingsTarget  = button ? button.getAttribute('data-findings-target')   : null;
                const impressionTarget = button ? button.getAttribute('data-impression-target') : null;
                const findingsEl  = findingsTarget  ? document.querySelector(findingsTarget)  : null;
                const impressionEl = impressionTarget ? document.querySelector(impressionTarget) : null;
                const findingsHtml  = findingsEl  ? findingsEl.value  : '';
                const impressionHtml = impressionEl ? impressionEl.value : '';

                if (typeof CKEDITOR !== 'undefined') {
                    if (CKEDITOR.instances.HTMLShow) {
                        CKEDITOR.instances.HTMLShow.setData(findingsHtml);
                    } else {
                        document.getElementById('HTMLShow').value = findingsHtml;
                    }
                    if (CKEDITOR.instances.report_data_Impression) {
                        CKEDITOR.instances.report_data_Impression.setData(impressionHtml);
                    } else {
                        document.getElementById('report_data_Impression').value = impressionHtml;
                    }
                } else {
                    document.getElementById('HTMLShow').value = findingsHtml;
                    document.getElementById('report_data_Impression').value = impressionHtml;
                }

                if (autoSave) {
                    setTimeout(() => update_report(), 180);
                }

                // Close modal and fully remove backdrop
                if (window._imagingSupportModalInstance) {
                    window._imagingSupportModalInstance.hide();
                } else {
                    const el = document.getElementById('imagingSupportModal');
                    const inst = bootstrap.Modal.getInstance(el);
                    if (inst) inst.hide();
                }
            }

            // ABDM report sharing panel removed per user requirement

            </script>
        <?php endif; ?>
    </div>
</div>
</form>
