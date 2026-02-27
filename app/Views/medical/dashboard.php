<div class="pagetitle">
    <h1>Medical Store</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Dashboard</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="card">
        <div class="card-body pt-3">
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-primary btn-sm" href="javascript:load_form_div('<?= base_url('Medical/search_customer') ?>','medical-main','OPD Search Panel :Pharmacy');">
                    <i class="bi bi-cart"></i> OPD Sale
                </a>
                <a class="btn btn-outline-primary btn-sm" href="javascript:load_form_div('<?= base_url('Medical/Invoice_Med_Draft') ?>','medical-main','Invoice List :Pharmacy');">
                    <i class="bi bi-receipt"></i> Invoice
                </a>
                <a class="btn btn-outline-primary btn-sm" href="javascript:load_form_div('<?= base_url('Medical/list_org_ipd') ?>','medical-main','IPD List :Pharmacy');">
                    <i class="bi bi-hospital"></i> IPD or Credit Invoice
                </a>
                <a class="btn btn-outline-primary btn-sm" href="javascript:load_form_div('<?= base_url('Medical/list_org') ?>','medical-main','OrgCr. List :Pharmacy');">
                    <i class="bi bi-building"></i> Org. Credit Invoice
                </a>
                <a class="btn btn-outline-primary btn-sm" href="javascript:load_form_div('<?= base_url('Medical/store_stock') ?>','medical-main','Store Stock :Pharmacy');">
                    <i class="bi bi-boxes"></i> Store Stock
                </a>
                <a class="btn btn-outline-primary btn-sm" href="javascript:load_form_div('<?= base_url('Medical/main_store') ?>','medical-main','Store Main :Pharmacy');">
                    <i class="bi bi-display"></i> Store Main
                </a>
                <a class="btn btn-outline-primary btn-sm" href="javascript:load_form_div('<?= base_url('Medical/master') ?>','medical-main','Master :Pharmacy');">
                    <i class="bi bi-sliders"></i> Master
                </a>
            </div>
        </div>
    </div>

    <div id="medical-main" class="mt-3"></div>
</section>
