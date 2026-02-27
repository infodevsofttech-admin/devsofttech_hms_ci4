<div class="pagetitle">
    <h1>Store Main <small class="text-muted fs-6 ms-1">Panel</small></h1>
</div>

<section class="section">
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">Master</div>
                <div class="card-body pt-3">
                    <ul class="mb-0" style="list-style: disc;">
                        <li class="mb-2">
                            <a href="javascript:load_form_div('<?= base_url('Medical/SupplierList') ?>','medical-main','Supplier :Pharmacy');">
                                <i class="bi bi-hand-index-thumb"></i> Supplier
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="javascript:load_form_div('<?= base_url('product_master/drug_master_list') ?>','medical-main','Drug Master :Pharmacy');">
                                <i class="bi bi-hand-index-thumb"></i> Drug Master
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="javascript:load_form_div('<?= base_url('product_master/company_master_list') ?>','medical-main','Drug Company :Pharmacy');">
                                <i class="bi bi-hand-index-thumb"></i> Drug Company
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="javascript:load_form_div('<?= base_url('product_master/medicine_category') ?>','medical-main','Medicine Category :Pharmacy');">
                                <i class="bi bi-hand-index-thumb"></i> Medicine Category
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="javascript:load_form_div('<?= base_url('Medical/main_store_link/drug-sale-customer-wise') ?>','medical-main','Drug Sale Customer Wise :Pharmacy');">
                                <i class="bi bi-hand-index-thumb"></i> Drug Sale Customer Wise Report
                            </a>
                        </li>
                        <li>
                            <a href="javascript:load_form_div('<?= base_url('Medical/main_store_link/print-bill-uhid') ?>','medical-main','Print Bill on UHID :Pharmacy');">
                                <i class="bi bi-hand-index-thumb"></i> Print Bill on UHID
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">Inventory</div>
                <div class="card-body pt-3">
                    <ul class="mb-0" style="list-style: disc;">
                        <li class="mb-3">
                            <a href="javascript:load_form_div('<?= base_url('Medical/Purchase') ?>','medical-main','Purchase :Pharmacy');">
                                <i class="bi bi-hand-index-thumb"></i> Purchase
                            </a>
                        </li>
                        <li>
                            <a href="javascript:load_form_div('<?= base_url('Medical/Purchase_return') ?>','medical-main','Purchase Return :Pharmacy');">
                                <i class="bi bi-hand-index-thumb"></i> Purchase Return
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div id="medical-main" class="mt-3"></div>
</section>
