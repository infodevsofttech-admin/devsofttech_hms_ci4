<?php
$report = $report_format[0] ?? null;
$templates = $radiology_ultrasound_template ?? [];
?>

<style>
.xray-editor-shell {
    background: #f9fbff;
    border: 1px solid #dce5f2;
    border-radius: 12px;
    padding: 12px;
}
.xray-action-row {
    position: sticky;
    bottom: 0;
    z-index: 2;
    background: linear-gradient(180deg, rgba(249,251,255,0.1) 0%, rgba(249,251,255,0.95) 22%, rgba(249,251,255,1) 100%);
    padding-top: 8px;
}
.xray-template-pane {
    border-left: 1px solid #e4ebf5;
    padding-left: 12px;
}
.xray-template-pane a {
    text-decoration: none;
}
</style>

<?php if (! $report): ?>
    <div class="alert alert-danger mb-0">Report not found.</div>
<?php else: ?>
    <input type="hidden" id="hid_value_req_id" value="<?= esc($report->id ?? '') ?>">
    <input type="hidden" id="report_mode" value="xray">
    <input type="hidden" id="hid_value_report_name" value="<?= esc($report->report_name ?? '') ?>">
    <?= csrf_field() ?>

    <div class="row g-3 xray-editor-shell">
        <div class="col-lg-8">
            <div class="mb-2">
                <label class="form-label">Report</label>
                <textarea id="HTMLShow" name="HTMLShow" class="form-control" rows="12"><?= $report->Report_Data ?? '' ?></textarea>
            </div>

            <div class="mb-2">
                <label class="form-label">Impression</label>
                <textarea id="report_data_Impression" name="report_data_Impression" class="form-control" rows="7"><?= $report->report_data_Impression ?? '' ?></textarea>
            </div>

            <div class="d-flex gap-2 flex-wrap xray-action-row">
                <button type="button" class="btn btn-primary" onclick="update_report()">Save</button>
                <button type="button" class="btn btn-success" onclick="report_final()">Verified</button>
                <button type="button" class="btn btn-outline-primary" onclick="showImagingUploadsFromEditor()">
                    <i class="bi bi-images"></i> Show Upload Images
                </button>
                <button type="button" class="btn btn-outline-danger" onclick="runImagingAiDiagnosisFromEditor()">
                    <i class="bi bi-magic"></i> AI Diagnosis
                </button>
            </div>
        </div>

        <div class="col-lg-4 xray-template-pane">
            <label class="form-label">Templates</label>
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

            <div id="templateList" style="max-height: 60vh; overflow-y: auto; margin-top: 8px;">
                <?php foreach ($templates as $tpl): ?>
                    <?php
                        $tplName = (string) ($tpl->template_name ?? '');
                        $tplTitle = trim((string) ($tpl->title ?? ''));
                        $tplKeywords = trim((string) ($tpl->keywords ?? ''));
                        $tplCategory = trim((string) ($tpl->impression_cat ?? ''));
                        $searchBlob = strtolower(trim($tplName . ' ' . $tplTitle . ' ' . $tplKeywords . ' ' . $tplCategory));
                    ?>
                    <div class="template-item mb-2" data-search="<?= esc($searchBlob) ?>">
                        <a href="javascript:set_template(<?= (int) ($tpl->id ?? 0) ?>)" class="d-block p-2 border rounded text-decoration-none">
                            <div class="fw-semibold"><?= esc($tplName) ?></div>
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

    <script>
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

        input.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter') {
                return;
            }

            const firstVisible = document.querySelector('#templateList .template-item:not([style*="display: none"]) a');
            if (firstVisible) {
                e.preventDefault();
                firstVisible.click();
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

    function set_template(templateId) {
        fetch('<?= base_url('diagnosis/get-template-xray') ?>/' + templateId, {
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
    </script>
<?php endif; ?>
