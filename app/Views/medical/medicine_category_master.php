<div class="card">
    <div class="card-body pt-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0">Medicine Category Master</h5>
            <button onclick="load_form_div('<?= base_url('Product_master/medicine_category_edit/0') ?>','test_div','Medicine Category : New :Pharmacy');" type="button" class="btn btn-primary btn-sm">Add New</button>
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <div id="medicine_category_list" class="table-responsive" style="max-height:500px;overflow-y:auto;">
                    <?= view('medical/medicine_category_sub', ['med_product_cat_master' => $med_product_cat_master ?? []]) ?>
                </div>
            </div>
            <div class="col-lg-6" id="test_div"></div>
        </div>
    </div>
</div>
