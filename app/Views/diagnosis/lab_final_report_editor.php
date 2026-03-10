<?php
$report = $report_format[0] ?? null;
$templates = $radiology_ultrasound_template ?? [];
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

            <hr/>

            <div class="row g-3">
                <div class="col-md-8">
                    <div class="form-group">
                        <label class="form-label"><strong>Report Findings</strong></label>
                        <textarea id="HTMLShow" name="HTMLShow" class="form-control" rows="12"><?= $report->Report_Data ?? '' ?></textarea>
                        <script>
                            if (typeof CKEDITOR !== 'undefined') {
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
                    <input type="text" id="template_search" class="form-control form-control-sm" placeholder="Search templates..." autocomplete="off" />
                    <div id="templateList" style="max-height:60vh; overflow-y:auto; margin-top:8px;">
                        <?php foreach ($templates as $tpl): ?>
                            <div class="template-item mb-1">
                                <a href="javascript:set_template(<?= (int) ($tpl->id ?? 0) ?>)"><?= esc($tpl->template_name ?? '') ?></a>
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
                        const show = item.textContent.toLowerCase().indexOf(q) !== -1;
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

            // Modal helper function
            function openImagingSupportModal(title, html) {
                document.getElementById('imagingSupportModalTitle').textContent = title || 'Imaging Support';
                document.getElementById('imagingSupportModalBody').innerHTML = html;
                new bootstrap.Modal(document.getElementById('imagingSupportModal')).show();
            }

            // Show imaging uploads (gallery)
            function showImagingUploadsFromEditor() {
                const reqId = document.getElementById('hid_value_req_id').value;
                const testName = document.getElementById('hid_value_report_name').value;
                if (!reqId) { alert('Request ID not found'); return; }
                showImagingUploads(reqId, testName);
            }

            function showImagingUploads(reqId, testName) {
                const labType = document.getElementById('lab_type').value;
                fetch(baseUrl + 'diagnosis/imaging-upload-gallery/' + reqId + '/' + labType + '/' + reqId, {
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
                const btn = event.target.closest('button');
                if (btn) btn.disabled = true;
                const indicator = document.createElement('span');
                indicator.textContent = ' Processing...';
                if (btn) btn.appendChild(indicator);

                fetch(baseUrl + 'diagnosis/imaging-ai-diagnosis/' + reqId, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: '<?= csrf_token() ?>=<?= csrf_hash() ?>'
                })
                .then(r => r.text())
                .then(html => {
                    openImagingSupportModal(testName + ' - AI Diagnosis', html);
                })
                .catch(e => alert('Error: ' + e.message))
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

                    if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.HTMLShow) {
                        CKEDITOR.instances.HTMLShow.setData(findings);
                    } else {
                        document.getElementById('HTMLShow').value = findings;
                    }

                    if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.report_data_Impression) {
                        CKEDITOR.instances.report_data_Impression.setData(impression);
                    } else {
                        document.getElementById('report_data_Impression').value = impression;
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
                data.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
                data.append('report_data', reportData);
                data.append('report_data_impression', impressionData);

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
            function pasteAiDiagnosisDraftAndSave(button) {
                applyAiDiagnosisDraftToEditor(button, true);
            }

            function applyAiDiagnosisDraftToEditor(button, autoSave) {
                const findingsHtml = document.getElementById('ai_findings_text') ? document.getElementById('ai_findings_text').textContent : '';
                const impressionHtml = document.getElementById('ai_impression_text') ? document.getElementById('ai_impression_text').textContent : '';

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
            }
            </script>
        <?php endif; ?>
    </div>
</div>
</form>
