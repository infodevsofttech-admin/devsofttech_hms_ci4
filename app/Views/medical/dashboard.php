<div class="pagetitle d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2 pb-2 border-bottom">
    <div class="d-flex align-items-center gap-2">
        <h4 class="mb-0 text-primary fw-bold">
            <i class="bi bi-capsule-pill"></i> Medical Store
        </h4>
    </div>

    <!-- Right-aligned Compact Button Groups / Dropdowns -->
    <div class="d-flex align-items-center flex-wrap gap-2">
        
        <!-- 1. Sales & Billing Group -->
        <div class="btn-group">
            <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle shadow-sm" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-receipt"></i> Sales &amp; Billing
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li><h6 class="dropdown-header text-uppercase py-1" style="font-size: 10px; letter-spacing: 0.5px;">Dispensing &amp; Billing</h6></li>
                <li>
                    <a class="dropdown-item py-1" href="javascript:load_form_div('<?= base_url('Medical/search_customer') ?>','medical-main','OPD Search Panel :Pharmacy');">
                        <i class="bi bi-cart-plus text-primary me-2"></i> <strong>OPD Sale</strong>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-1" href="javascript:load_form_div('<?= base_url('Medical/Invoice_Med_Draft') ?>','medical-main','Invoice List :Pharmacy');">
                        <i class="bi bi-receipt text-success me-2"></i> Counter Invoice
                    </a>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li><h6 class="dropdown-header text-uppercase py-1" style="font-size: 10px; letter-spacing: 0.5px;">Inpatient &amp; Credit</h6></li>
                <li>
                    <a class="dropdown-item py-1" href="javascript:load_form_div('<?= base_url('Medical/list_org_ipd') ?>','medical-main','IPD List :Pharmacy');">
                        <i class="bi bi-hospital text-info me-2"></i> IPD / Credit Invoice
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-1" href="javascript:load_form_div('<?= base_url('Medical/list_org') ?>','medical-main','OrgCr. List :Pharmacy');">
                        <i class="bi bi-building text-secondary me-2"></i> Org. Credit Invoice
                    </a>
                </li>
            </ul>
        </div>

        <!-- 2. Store & Procurement Group -->
        <div class="btn-group">
            <button type="button" class="btn btn-outline-info btn-sm dropdown-toggle shadow-sm text-dark" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-box-seam"></i> Store &amp; Orders
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li><h6 class="dropdown-header text-uppercase py-1" style="font-size: 10px; letter-spacing: 0.5px;">Purchasing &amp; Supply</h6></li>
                <li>
                    <a class="dropdown-item py-1" href="javascript:load_form_div('<?= base_url('Medical/purchase_orders') ?>','medical-main','Purchase Orders :Pharmacy');">
                        <i class="bi bi-file-earmark-text text-primary me-2"></i> <strong>Purchase Orders (PO)</strong>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-1" href="javascript:load_form_div('<?= base_url('Medical/grn_list') ?>','medical-main','GRN Register :Pharmacy');">
                        <i class="bi bi-box-arrow-in-down text-success me-2"></i> <strong>Inward GRN</strong>
                    </a>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li><h6 class="dropdown-header text-uppercase py-1" style="font-size: 10px; letter-spacing: 0.5px;">Inventory &amp; Stores</h6></li>
                <li>
                    <a class="dropdown-item py-1" href="javascript:load_form_div('<?= base_url('Medical/store_stock') ?>','medical-main','Store Stock :Pharmacy');">
                        <i class="bi bi-boxes text-info me-2"></i> Store Stock &amp; Batches
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-1" href="javascript:load_form_div('<?= base_url('Medical/main_store') ?>','medical-main','Store Main :Pharmacy');">
                        <i class="bi bi-display text-dark me-2"></i> Central Store Main
                    </a>
                </li>
            </ul>
        </div>

        <!-- 3. Reports & Finance Group -->
        <div class="btn-group">
            <button type="button" class="btn btn-outline-success btn-sm dropdown-toggle shadow-sm" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-graph-up"></i> Reports &amp; Finance
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li><h6 class="dropdown-header text-uppercase py-1" style="font-size: 10px; letter-spacing: 0.5px;">Sales &amp; Cash</h6></li>
                <li>
                    <a class="dropdown-item py-1" href="javascript:load_form_div('<?= base_url('Medical_Report/Report_2') ?>','medical-main','Day Report :Pharmacy');">
                        <i class="bi bi-calendar-day text-primary me-2"></i> Day Counter Report
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-1" href="javascript:load_form_div('<?= base_url('Medical_Report/Report_1') ?>','medical-main','Sale Day Report :Pharmacy');">
                        <i class="bi bi-graph-up-arrow text-success me-2"></i> Sale Day Summary Report
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-1" href="javascript:load_form_div('<?= base_url('Medical_Report/Report_Payment_Recieved') ?>','medical-main','Payment Report :Pharmacy');">
                        <i class="bi bi-cash-stack text-info me-2"></i> Payment Received Report
                    </a>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li><h6 class="dropdown-header text-uppercase py-1" style="font-size: 10px; letter-spacing: 0.5px;">Settlement &amp; Dues</h6></li>
                <li>
                    <a class="dropdown-item py-1" href="javascript:load_form_div('<?= base_url('Medical/credit_payout_request') ?>','medical-main','Credit Payout Request :Pharmacy');">
                        <i class="bi bi-wallet2 text-warning me-2"></i> Credit Payout Request
                    </a>
                </li>
            </ul>
        </div>

        <!-- 4. Master Button -->
        <a class="btn btn-outline-secondary btn-sm shadow-sm" href="javascript:load_form_div('<?= base_url('Medical/master') ?>','medical-main','Master :Pharmacy');">
            <i class="bi bi-sliders"></i> Master
        </a>

    </div>
</div>

<section class="section">
    <div id="medical-main" class="pt-1"></div>
</section>
