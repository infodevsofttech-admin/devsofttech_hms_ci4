<?php
$report = $report_format[0] ?? null;
$templates = $radiology_ultrasound_template ?? [];
?>

<?php if (! $report): ?>
    <div class="alert alert-danger mb-0">Report not found.</div>
<?php else: ?>
    <input type="hidden" id="hid_value_req_id" value="<?= esc($report->id ?? '') ?>">
    <input type="hidden" id="report_mode" value="xray">
    <?= csrf_field() ?>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="mb-2">
                <label class="form-label">Report</label>
                <textarea id="HTMLShow" name="HTMLShow" class="form-control" rows="12"><?= $report->Report_Data ?? '' ?></textarea>
            </div>

            <div class="mb-2">
                <label class="form-label">Impression</label>
                <textarea id="report_data_Impression" name="report_data_Impression" class="form-control" rows="7"><?= $report->report_data_Impression ?? '' ?></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" onclick="update_report()">Save</button>
                <button type="button" class="btn btn-success" onclick="report_final()">Verified</button>
            </div>
        </div>

        <div class="col-lg-4">
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
