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
            <input type="text" id="template_search" class="form-control form-control-sm" placeholder="Search templates..." autocomplete="off" />

            <div id="templateList" style="max-height: 60vh; overflow-y: auto; margin-top: 8px;">
                <?php foreach ($templates as $tpl): ?>
                    <div class="template-item mb-1">
                        <a href="javascript:set_template(<?= (int) ($tpl->id ?? 0) ?>)"><?= esc($tpl->template_name ?? '') ?></a>
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
    </script>
<?php endif; ?>
