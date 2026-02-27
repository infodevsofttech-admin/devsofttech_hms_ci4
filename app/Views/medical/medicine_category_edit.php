<?php
$cid = 0;
$medCatDesc = '';

if (!empty($med_product_cat_master ?? [])) {
    $row = $med_product_cat_master[0];
    $cid = (int) ($row->id ?? 0);
    $medCatDesc = (string) ($row->med_cat_desc ?? '');
}
?>

<div class="card">
    <div class="card-header">Medicine Category</div>
    <div class="card-body pt-3">
        <div id="medicine_category_msg"></div>

        <form id="medicine-category-form" class="row g-2" method="post" action="javascript:void(0)">
            <?= csrf_field() ?>
            <input type="hidden" id="hid_cid" name="hid_cid" value="<?= esc((string) $cid) ?>">

            <div class="col-md-8">
                <label class="form-label">Medicine Category Name</label>
                <input class="form-control" name="input_med_cat_desc" id="input_med_cat_desc" placeholder="Medicine Category Name" type="text" value="<?= esc($medCatDesc) ?>" required>
            </div>

            <div class="col-12">
                <button type="button" class="btn btn-primary" id="btn_medicine_category_update" accesskey="A">Add & Update Category</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    function showMessage(html) {
        $('#medicine_category_msg').html(html || '');
    }

    $('#btn_medicine_category_update').off('click').on('click', function () {
        $.post('<?= base_url('Product_master/medicine_category_Update') ?>', $('#medicine-category-form').serialize(), function (data) {
            if (!data || typeof data !== 'object') {
                showMessage('<div class="alert alert-danger mb-0">Unexpected response.</div>');
                return;
            }

            showMessage(data.show_text || '');
            if ((data.insertid || 0) > 0) {
                $('#hid_cid').val(data.insertid);
                load_form_div('<?= base_url('Product_master/medicine_category_Sub') ?>', 'medicine_category_list');
                load_form_div('<?= base_url('Product_master/medicine_category_edit') ?>/' + data.insertid, 'test_div');
            }
        }, 'json').fail(function () {
            showMessage('<div class="alert alert-danger mb-0">Unable to update medicine category.</div>');
        });
    });
})();
</script>
