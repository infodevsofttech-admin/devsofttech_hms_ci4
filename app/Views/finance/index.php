<section class="content finance-sop">
    <div class="finance-head mb-3">
        <h2 class="mb-1">Finance & Accounting - Phase 1</h2>
        <p class="mb-0 text-muted">Vendor onboarding, PO booking, GRN capture, and invoice registration.</p>
        <div class="mt-2 d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-primary btn-sm js-finance-launch" data-url="<?= base_url('Finance/section/vendor_master') ?>" data-title="Vendor Master">
                <i class="bi bi-building"></i> Vendor Master
            </button>
            <button type="button" class="btn btn-outline-primary btn-sm js-finance-launch" data-url="<?= base_url('Finance/section/purchase_order') ?>" data-title="Purchase Order">
                <i class="bi bi-card-checklist"></i> Purchase Order
            </button>
            <button type="button" class="btn btn-outline-primary btn-sm js-finance-launch" data-url="<?= base_url('Finance/section/grn_entry') ?>" data-title="GRN Entry">
                <i class="bi bi-box-seam"></i> GRN Entry
            </button>
            <button type="button" class="btn btn-outline-primary btn-sm js-finance-launch" data-url="<?= base_url('Finance/section/vendor_invoice') ?>" data-title="Vendor Invoice">
                <i class="bi bi-receipt"></i> Vendor Invoice
            </button>
            <a class="btn btn-primary btn-sm" href="javascript:load_form('<?= base_url('Finance/phase2') ?>','Finance & Accounting - Phase 2');">
                Open Finance & Accounting - Phase 2
            </a>
            <a class="btn btn-outline-primary btn-sm" href="javascript:load_form('<?= base_url('Finance/cashbook') ?>','Cash Collection & Disbursement SOP');">
                Open Cash Collection & Disbursement SOP
            </a>
            <a class="btn btn-outline-secondary btn-sm" href="javascript:load_form('<?= base_url('Finance/doctor_payout') ?>','Doctor Payout Workflow');">
                Open Doctor Payout Workflow
            </a>
            <a class="btn btn-outline-success btn-sm" href="javascript:load_form('<?= base_url('Finance/bank_deposits') ?>','Bank Deposit Register');">
                Open Bank Deposit Register
            </a>
            <a class="btn btn-outline-dark btn-sm" href="javascript:load_form('<?= base_url('Finance/compliance_report') ?>','Finance Compliance Report');">
                Open Finance Compliance Report
            </a>
        </div>
    </div>

    <div class="row g-2 mb-2">
        <div class="col-md-3 col-6">
            <div class="card border-success">
                <div class="card-body py-2">
                    <div class="small text-muted">Matched</div>
                    <div class="h5 mb-0 text-success"><?= (int) (($match_summary['matched'] ?? 0)) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-warning">
                <div class="card-body py-2">
                    <div class="small text-muted">Minor Variance</div>
                    <div class="h5 mb-0 text-warning"><?= (int) (($match_summary['minor_variance'] ?? 0)) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-danger">
                <div class="card-body py-2">
                    <div class="small text-muted">Mismatch / Not Checked</div>
                    <div class="h5 mb-0 text-danger"><?= (int) (($match_summary['mismatch'] ?? 0)) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-dark">
                <div class="card-body py-2">
                    <div class="small text-muted">Compliance Hold</div>
                    <div class="h5 mb-0"><?= (int) (($match_summary['hold'] ?? 0)) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <strong id="finance_sub_title">Vendor Master</strong>
        </div>
        <div class="card-body">
            <div id="finance-main"></div>
        </div>
    </div>
</section>

<script>
(function() {
    var launchers = Array.prototype.slice.call(document.querySelectorAll('.js-finance-launch'));
    var titleBox = document.getElementById('finance_sub_title');

    function activateButton(activeBtn) {
        launchers.forEach(function(btn) {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-primary');
        });

        if (activeBtn) {
            activeBtn.classList.remove('btn-outline-primary');
            activeBtn.classList.add('btn-primary');
        }
    }

    function loadSection(btn) {
        if (!btn) {
            return;
        }

        var url = btn.getAttribute('data-url');
        var title = btn.getAttribute('data-title') || 'Finance Section';
        activateButton(btn);
        if (titleBox) {
            titleBox.textContent = title;
        }

        load_form_div(url, 'finance-main', title + ' :Finance');
    }

    launchers.forEach(function(btn) {
        btn.addEventListener('click', function() {
            loadSection(btn);
        });
    });

    if (launchers.length > 0) {
        loadSection(launchers[0]);
    }
})();
</script>
