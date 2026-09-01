<?php
$panels = $panels ?? [];
$can = static fn (string $panel): bool => (bool) ($panels[$panel] ?? false);
?>
<section class="content report-hub">
    <div class="report-hub-head mb-3">
        <div>
            <h2 class="report-hub-title mb-1">Report Panel</h2>
            <p class="report-hub-sub mb-0">Find the operational report you need. Only reports assigned to your user are shown.</p>
        </div>
        <div class="report-search-wrap">
            <i class="bi bi-search"></i><input type="search" id="report-panel-search" aria-label="Find a report" placeholder="Find a report, e.g. IPD, payment, audit">
        </div>
    </div>

    <div class="row g-3" id="report-panel-grid">
        <?php if ($can('billing')) : ?>
        <div class="col-xl-3 col-md-6">
            <div class="card report-card h-100">
                <div class="card-body">
                    <div class="report-card-head">
                        <span class="report-icon report-icon-blue"><i class="bi bi-cash-stack"></i></span>
                        <h5 class="report-card-title mb-0">Billing &amp; Collection</h5>
                    </div>
                    <ul class="report-links">
                        <li><a href="javascript:load_form('<?= base_url('Report/collection_report') ?>','Collection Report');"><i class="bi bi-arrow-right-short"></i> Total Payment (Cash and Bank)</a></li>
                        <li><a href="javascript:load_form('<?= base_url('Report/old_payment_received_report') ?>','Old Payment Received Report');"><i class="bi bi-arrow-right-short"></i> Old Payment Received Report (Late Collections)</a></li>
                        <li><a href="javascript:load_form('<?= base_url('Report/report_opd_total') ?>','OPD Total Report');"><i class="bi bi-arrow-right-short"></i> OPD Total Report</a></li>
                        <li><a href="javascript:load_form('<?= base_url('Report/report_opd_patient_list') ?>','OPD Patient List');"><i class="bi bi-arrow-right-short"></i> OPD Patient List</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($can('operations')) : ?>
        <div class="col-xl-3 col-md-6">
            <div class="card report-card h-100">
                <div class="card-body">
                    <div class="report-card-head">
                        <span class="report-icon report-icon-amber"><i class="bi bi-receipt-cutoff"></i></span>
                        <h5 class="report-card-title mb-0">Billing Operations</h5>
                    </div>
                    <ul class="report-links">
                        <li><a href="javascript:load_form('<?= base_url('Report/billing_operations_report') ?>','Billing Operations Report');"><i class="bi bi-arrow-right-short"></i> Billing Operations Report</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($can('ipd') || $can('surgery')) : ?>
        <div class="col-xl-3 col-md-6"><div class="card report-card h-100"><div class="card-body"><div class="report-card-head"><span class="report-icon report-icon-green"><i class="bi bi-hospital"></i></span><h5 class="report-card-title mb-0">IPD Operations</h5></div><ul class="report-links"><?php if ($can('ipd')) : ?><li><a href="javascript:load_form('<?= base_url('Report/ipd_census_report') ?>','IPD Census &amp; Discharge Report');"><i class="bi bi-arrow-right-short"></i> IPD Census &amp; Discharge</a></li><?php endif; ?><?php if ($can('surgery')) : ?><li><a href="javascript:load_form('<?= base_url('Report/ipd_surgery_report') ?>','IPD Surgery Report');"><i class="bi bi-arrow-right-short"></i> IPD Surgery Report</a></li><?php endif; ?></ul></div></div></div>
        <?php endif; ?>
        <?php if ($can('clinical')) : ?>
        <div class="col-xl-3 col-md-6">
            <div class="card report-card h-100">
                <div class="card-body">
                    <div class="report-card-head">
                        <span class="report-icon report-icon-cyan"><i class="bi bi-clipboard2-pulse"></i></span>
                        <h5 class="report-card-title mb-0">Clinical &amp; Diagnosis</h5>
                    </div>
                    <ul class="report-links">
                        <li><a href="javascript:load_form('<?= base_url('Report/diagnosis_report') ?>','Diagnosis Report');"><i class="bi bi-arrow-right-short"></i> Diagnosis Report</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($can('insurance')) : ?>
        <div class="col-xl-3 col-md-6">
            <div class="card report-card h-100">
                <div class="card-body">
                    <div class="report-card-head">
                        <span class="report-icon report-icon-indigo"><i class="bi bi-shield-check"></i></span>
                        <h5 class="report-card-title mb-0">Insurance &amp; Credit</h5>
                    </div>
                    <ul class="report-links">
                        <li><a href="javascript:load_form('<?= base_url('Report/insurance_ipd_report') ?>','Insurance Case IPD');"><i class="bi bi-arrow-right-short"></i> Insurance Case IPD</a></li>
                        <li><a href="javascript:load_form('<?= base_url('Report/insurance_opd_report') ?>','Insurance Case OPD');"><i class="bi bi-arrow-right-short"></i> Insurance Case OPD</a></li>
                        <li><a href="javascript:load_form('<?= base_url('Report/ayushman_case_dashboard') ?>','Ayushman Case Dashboard');"><i class="bi bi-arrow-right-short"></i> Ayushman Case Dashboard</a></li>
                        <li><a href="javascript:load_form('<?= base_url('Report/ayushman_unmapped_report') ?>','Ayushman Unmapped Procedures');"><i class="bi bi-arrow-right-short"></i> Ayushman Unmapped Procedures</a></li>
                        <li><a href="javascript:load_form('<?= base_url('org-packing') ?>','Org. OPD Packing');"><i class="bi bi-arrow-right-short"></i> Or. OPD Packing</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($can('quality')) : ?>
        <div class="col-xl-3 col-md-6"><div class="card report-card h-100"><div class="card-body"><div class="report-card-head"><span class="report-icon report-icon-rose"><i class="bi bi-clipboard2-check"></i></span><h5 class="report-card-title mb-0">Quality &amp; Audit</h5></div><ul class="report-links"><li><a href="javascript:load_form('<?= base_url('Report/nabh_audit_report') ?>','NABH Audit Report');"><i class="bi bi-arrow-right-short"></i> NABH Audit Report</a></li></ul></div></div></div>
        <?php endif; ?>
        <?php if ($can('documents')) : ?>
        <div class="col-xl-3 col-md-6">
            <div class="card report-card h-100 report-card-muted">
                <div class="card-body">
                    <div class="report-card-head">
                        <span class="report-icon report-icon-slate"><i class="bi bi-file-earmark-text"></i></span>
                        <h5 class="report-card-title mb-0">Documents</h5>
                    </div>
                    <ul class="report-links">
                        <li><a href="javascript:load_form('<?= base_url('Report/document_list') ?>','Document Issue Report');"><i class="bi bi-arrow-right-short"></i> Document Issue Report</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <div id="report-panel-empty" class="report-empty text-center py-4" style="display:none;">No report matches this search.</div>
</section>

<style>
.report-hub {
    --hub-bg: #f4f6fb;
    --hub-border: #e3e8f2;
    --hub-text: #2f3b52;
    --hub-sub: #69758c;
    --hub-link: #1f4d90;
    --hub-link-hover: #0d6efd;
    --hub-shadow: 0 10px 22px rgba(30, 48, 84, 0.08);
    background: linear-gradient(145deg, #f7f9fd 0%, #edf2f9 100%);
    border: 1px solid var(--hub-border);
    border-radius: 8px;
    padding: 18px;
}

.report-hub-head { display: flex; align-items: end; justify-content: space-between; gap: 16px; }
.report-search-wrap { position: relative; min-width: min(100%, 320px); }
.report-search-wrap i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--hub-sub); }
.report-search-wrap input { width: 100%; border: 1px solid var(--hub-border); border-radius: 6px; padding: 9px 11px 9px 33px; outline: none; color: var(--hub-text); }
.report-search-wrap input:focus { border-color: #7798ca; box-shadow: 0 0 0 3px rgba(31, 77, 144, .12); }

.report-hub-title {
    color: #24334f;
    font-weight: 700;
    letter-spacing: 0.2px;
}

.report-hub-sub {
    color: var(--hub-sub);
    font-size: 0.92rem;
}

.report-card {
    border: 1px solid var(--hub-border);
    border-radius: 8px;
    box-shadow: 0 2px 0 rgba(255, 255, 255, 0.9) inset;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    background: #fff;
}

.report-card:hover {
    transform: translateY(-4px);
    border-color: #cdd8ea;
    box-shadow: var(--hub-shadow);
}

.report-card-head {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.report-card-title {
    color: var(--hub-text);
    font-size: 1.03rem;
    font-weight: 600;
}

.report-icon {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.report-icon-blue { background: #e7f0ff; color: #1f5dd0; }
.report-icon-cyan { background: #e3f9fa; color: #0f8f9a; }
.report-icon-green { background: #e7f9ef; color: #1d9960; }
.report-icon-indigo { background: #ece9ff; color: #4b49b6; }
.report-icon-slate { background: #eef1f6; color: #6b7280; }
.report-icon-amber { background: #fff3d8; color: #a86400; }
.report-icon-rose { background: #ffeaed; color: #bc3c55; }

.report-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.report-links li + li {
    margin-top: 6px;
}

.report-links a {
    color: var(--hub-link);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-weight: 500;
    line-height: 1.35;
}

.report-links a:hover {
    color: var(--hub-link-hover);
}

.report-links i {
    font-size: 1rem;
}

.report-card-muted {
    background: #fcfdff;
}

.report-empty {
    color: #7a8598;
    font-size: 0.94rem;
    padding-top: 2px;
}

@media (max-width: 767.98px) {
    .report-hub {
        padding: 14px;
    }
    .report-hub-head { align-items: stretch; flex-direction: column; }
    .report-search-wrap { min-width: 100%; }
}
</style>

<script>
(function () {
    var input = document.getElementById('report-panel-search');
    var grid = document.getElementById('report-panel-grid');
    var empty = document.getElementById('report-panel-empty');
    if (!input || !grid || !empty) return;
    input.addEventListener('input', function () {
        var query = input.value.toLowerCase().trim();
        var visible = 0;
        Array.prototype.forEach.call(grid.children, function (column) {
            var matches = !query || column.textContent.toLowerCase().indexOf(query) !== -1;
            column.style.display = matches ? '' : 'none';
            if (matches) visible++;
        });
        empty.style.display = visible ? 'none' : '';
    });
})();
</script>
