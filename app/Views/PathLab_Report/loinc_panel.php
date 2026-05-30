<?php
/**
 * LOINC Mapping Admin Panel — PathLab_Report/loinc_panel.php
 *
 * Shows all lab panels (lab_repo) and their component tests (lab_tests)
 * with editable LOINC codes.  Includes a "Sync from Bridge API" button.
 *
 * Variables injected by Setting\Template::loincPanel():
 *   $panels      — array of lab_repo rows  (mstRepoKey, Title, loinc_code, loinc_synced_at)
 *   $testsByRepo — [mstRepoKey => [lab_tests rows with loinc_* columns]]
 */
?>
<section class="content">
<div class="container-fluid">

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <h4 class="mb-0"><i class="fas fa-flask me-2 text-primary"></i>LOINC Mapping — Pathology Tests</h4>
    <div class="d-flex gap-2 flex-wrap">
        <button id="btn-sync-loinc" class="btn btn-sm btn-success">
            <i class="fas fa-sync-alt me-1"></i>Sync from Bridge API
        </button>
        <a href="<?= site_url('Lab_Admin/report_list') ?>" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to Report List
        </a>
    </div>
</div>

<!-- Sync output box -->
<div id="sync-output-box" class="alert alert-info d-none mb-3" style="white-space:pre-wrap;font-family:monospace;font-size:0.82rem;max-height:220px;overflow-y:auto;"></div>

<p class="text-muted small mb-3">
    LOINC codes allow lab results to be published as structured FHIR <strong>Observations</strong> inside
    ABDM <em>DiagnosticReportRecord</em> bundles. Blank LOINC codes are sent as display-only text observations.
    Use <kbd>Sync from Bridge API</kbd> for bulk auto-matching or edit individual rows below.
</p>

<?php if (empty($panels)): ?>
    <div class="alert alert-warning">No lab panels found. Add panels via <a href="<?= site_url('Lab_Admin/report_list') ?>">Report List</a>.</div>
<?php else: ?>

<?php foreach ($panels as $panel):
    $panelId    = (int) $panel['mstRepoKey'];
    $panelTitle = esc($panel['Title'] ?? '');
    $panelLoinc = esc($panel['loinc_code'] ?? '');
    $panelSync  = esc($panel['loinc_synced_at'] ?? '');
    $tests      = $testsByRepo[$panelId] ?? [];
    $hasPanelLoinc = trim($panel['loinc_code'] ?? '') !== '';
?>
<div class="card mb-3 shadow-sm">
    <div class="card-header py-2 d-flex align-items-center justify-content-between flex-wrap gap-2 bg-light">
        <div>
            <strong><?= $panelTitle ?></strong>
            <?php if ($hasPanelLoinc): ?>
                <span class="badge bg-success ms-2" title="LOINC Panel Code"><?= $panelLoinc ?></span>
            <?php else: ?>
                <span class="badge bg-secondary ms-2">No Panel LOINC</span>
            <?php endif; ?>
            <?php if ($panelSync): ?>
                <small class="text-muted ms-2">synced: <?= $panelSync ?></small>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <input type="text" class="form-control form-control-sm loinc-panel-input"
                   style="width:130px"
                   placeholder="Panel LOINC"
                   value="<?= $panelLoinc ?>"
                   data-id="<?= $panelId ?>"
                   title="LOINC code for panel (DiagnosticReport.code)">
            <button class="btn btn-xs btn-outline-primary btn-save-panel"
                    data-id="<?= $panelId ?>">Save</button>
        </div>
    </div>

    <?php if (! empty($tests)): ?>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0 align-middle" style="font-size:0.82rem">
            <thead class="table-light">
                <tr>
                    <th style="width:22%">Test Name</th>
                    <th style="width:8%">Unit</th>
                    <th style="width:14%">LOINC Code</th>
                    <th style="width:16%">Property</th>
                    <th style="width:16%">System</th>
                    <th style="width:12%">Scale</th>
                    <th style="width:8%">Synced</th>
                    <th style="width:4%"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tests as $t):
                $tid   = (int) $t['mstTestKey'];
                $tname = esc($t['Test'] ?? '');
                $tunit = esc($t['Unit'] ?? '');
                $tloinc = esc($t['loinc_code'] ?? '');
                $tprop  = esc($t['loinc_property'] ?? '');
                $tsys   = esc($t['loinc_system'] ?? '');
                $tscale = esc($t['loinc_scale'] ?? '');
                $tsync  = esc($t['loinc_synced_at'] ?? '');
                $hasLoinc = trim($t['loinc_code'] ?? '') !== '';
            ?>
            <tr data-test-id="<?= $tid ?>">
                <td>
                    <?= $tname ?>
                    <?php if ($hasLoinc): ?><i class="fas fa-check-circle text-success ms-1" title="Has LOINC"></i><?php endif; ?>
                </td>
                <td class="text-muted"><?= $tunit ?></td>
                <td><input type="text" class="form-control form-control-sm loinc-test-code"
                           value="<?= $tloinc ?>" placeholder="e.g. 718-7" style="width:100%"></td>
                <td><input type="text" class="form-control form-control-sm loinc-test-prop"
                           value="<?= $tprop ?>" placeholder="MCnc" style="width:100%"></td>
                <td><input type="text" class="form-control form-control-sm loinc-test-sys"
                           value="<?= $tsys ?>" placeholder="Bld" style="width:100%"></td>
                <td><input type="text" class="form-control form-control-sm loinc-test-scale"
                           value="<?= $tscale ?>" placeholder="Qn" style="width:100%"></td>
                <td class="text-muted" style="font-size:0.75rem"><?= $tsync ? date('d-m-Y', strtotime($tsync)) : '-' ?></td>
                <td>
                    <button class="btn btn-xs btn-outline-success btn-save-test" data-test-id="<?= $tid ?>" title="Save LOINC">
                        <i class="fas fa-save"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>

</div><!-- /container-fluid -->
</section>

<script>
(function () {
    'use strict';

    const SYNC_URL   = '<?= site_url('Lab_Admin/loinc_sync') ?>';
    const UPDATE_URL = '<?= site_url('Lab_Admin/loinc_update') ?>';

    // ── Sync button ──────────────────────────────────────────────────────────
    document.getElementById('btn-sync-loinc').addEventListener('click', function () {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Syncing…';

        const outputBox = document.getElementById('sync-output-box');
        outputBox.classList.add('d-none');
        outputBox.textContent = '';

        const fd = new FormData();
        fetch(SYNC_URL, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
            .then(r => r.json())
            .then(data => {
                outputBox.classList.remove('d-none');
                outputBox.classList.toggle('alert-success', data.ok == 1);
                outputBox.classList.toggle('alert-danger',  data.ok != 1);
                outputBox.classList.remove('alert-info');
                outputBox.textContent = data.output || (data.ok == 1 ? 'Sync complete.' : 'Sync failed.');
                if (data.ok == 1) {
                    setTimeout(() => location.reload(), 2000);
                }
            })
            .catch(err => {
                outputBox.classList.remove('d-none', 'alert-info');
                outputBox.classList.add('alert-danger');
                outputBox.textContent = 'Request error: ' + err.message;
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sync-alt me-1"></i>Sync from Bridge API';
            });
    });

    // ── Save panel LOINC ─────────────────────────────────────────────────────
    document.querySelectorAll('.btn-save-panel').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id    = this.dataset.id;
            const input = document.querySelector('.loinc-panel-input[data-id="' + id + '"]');
            const code  = input ? input.value.trim() : '';

            const fd = new FormData();
            fd.append('type',       'panel');
            fd.append('id',         id);
            fd.append('loinc_code', code);

            fetch(UPDATE_URL, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
                .then(r => r.json())
                .then(data => {
                    input.classList.toggle('is-valid',   data.ok == 1);
                    input.classList.toggle('is-invalid', data.ok != 1);
                    setTimeout(() => input.classList.remove('is-valid', 'is-invalid'), 2000);
                });
        });
    });

    // ── Save individual test LOINC ───────────────────────────────────────────
    document.querySelectorAll('.btn-save-test').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const tid = this.dataset.testId;
            const row = document.querySelector('tr[data-test-id="' + tid + '"]');
            if (! row) return;

            const code  = row.querySelector('.loinc-test-code')?.value.trim()  ?? '';
            const prop  = row.querySelector('.loinc-test-prop')?.value.trim()  ?? '';
            const sys   = row.querySelector('.loinc-test-sys')?.value.trim()   ?? '';
            const scale = row.querySelector('.loinc-test-scale')?.value.trim() ?? '';

            const fd = new FormData();
            fd.append('type',           'test');
            fd.append('id',             tid);
            fd.append('loinc_code',     code);
            fd.append('loinc_property', prop);
            fd.append('loinc_system',   sys);
            fd.append('loinc_scale',    scale);

            fetch(UPDATE_URL, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
                .then(r => r.json())
                .then(data => {
                    const icon = btn.querySelector('i');
                    if (icon) {
                        icon.className = data.ok == 1 ? 'fas fa-check text-success' : 'fas fa-times text-danger';
                        setTimeout(() => { icon.className = 'fas fa-save'; }, 2000);
                    }
                });
        });
    });
}());
</script>
