<div class="card">
    <div class="card-body pt-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0">Company Master</h5>
            <button onclick="load_form_div('<?= base_url('Product_master/CompanyEdit/0') ?>','test_div','Company : New Company :Pharmacy');" type="button" class="btn btn-primary btn-sm">Add New</button>
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <div id="company_list" class="table-responsive" style="max-height:500px;overflow-y:auto;">
                    <?= view('medical/company_master_sub', ['med_company' => $med_company ?? []]) ?>
                </div>
            </div>
            <div class="col-lg-6" id="test_div"></div>
        </div>
    </div>
</div>
