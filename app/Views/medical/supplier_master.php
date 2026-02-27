<div class="card">
    <div class="card-body pt-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0">Supplier Master</h5>
            <button onclick="load_form_div('<?= base_url('Medical/SupplierEdit/0') ?>','test_div','Supplier : New Supplier :Pharmacy');" type="button" class="btn btn-primary btn-sm">Add New</button>
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <div id="supplier_list" class="table-responsive" style="max-height:500px;overflow-y:auto;">
                    <?= view('medical/supplier_master_sub', ['supplier_data' => $supplier_data ?? []]) ?>
                </div>
            </div>
            <div class="col-lg-6" id="test_div"></div>
        </div>
    </div>
</div>
