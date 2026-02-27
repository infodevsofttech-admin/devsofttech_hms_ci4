<section class="content">
    <div class="pagetitle">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h1 class="mb-0">IPD Billing</h1>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-sm btn-primary" onclick="javascript:load_form_div('<?= base_url('billing/ipd/current-admission') ?>','maindiv','Current Admission');">
                    <i class="bi bi-person-check"></i> Current Admission
                </button>
                <button class="btn btn-sm btn-secondary" onclick="javascript:load_form_div('<?= base_url('billing/ipd/invoices') ?>','maindiv','IPD Invoice');">
                    <i class="bi bi-receipt"></i> IPD Invoice (All IPDs)
                </button>
                <button class="btn btn-sm btn-success" onclick="javascript:load_form_div('<?= base_url('billing/ipd/cash-balance') ?>','maindiv','Cash Balance');">
                    <i class="bi bi-cash-stack"></i> Cash Balance
                </button>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12" id="maindiv"></div>
    </div>
</section>