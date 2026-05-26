<?php
$fid  = 0;
$name = '';

if (!empty($med_formulation ?? [])) {
    $row  = $med_formulation[0];
    $fid  = (int) ($row->id ?? 0);
    // support both field name variants
    $name = (string) ($row->formulation_length ?? $row->formulation ?? '');
}
?>

<div class="card">
    <div class="card-header">Formulation</div>
    <div class="card-body pt-3">
        <div id="formulation_msg"></div>

        <form id="formulation-form" class="row g-2" method="post" action="javascript:void(0)">
            <?= csrf_field() ?>
            <input type="hidden" id="hid_fid" name="hid_fid" value="<?= esc((string) $fid) ?>">

            <div class="col-md-8">
                <label class="form-label">Formulation Name</label>
                <input class="form-control" name="input_formulation_name" id="input_formulation_name"
                       placeholder="e.g. Tablet, Syrup, Capsule…" type="text" value="<?= esc($name) ?>">
            </div>

            <div class="col-12">
                <button type="button" class="btn btn-primary" id="btn_formulation_update" accesskey="A">Add &amp; Update Formulation</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    function showMessage(html) {
        $('#formulation_msg').html(html || '');
    }

    $('#btn_formulation_update').off('click').on('click', function () {
        $.post('<?= base_url('Product_master/FormulationUpdate') ?>', $('#formulation-form').serialize(), function (data) {
            if (!data || typeof data !== 'object') {
                showMessage('<div class="alert alert-danger mb-0">Unexpected response.</div>');
                return;
            }
            showMessage(data.show_text || '');
            if ((data.insertid || 0) > 0) {
                $('#hid_fid').val(data.insertid);
                if (typeof refreshFormulationMasterList === 'function') {
                    refreshFormulationMasterList();
                }
                load_form_div('<?= base_url('Product_master/FormulationEdit') ?>/' + data.insertid, 'test_div_form');
            }
        }, 'json').fail(function () {
            showMessage('<div class="alert alert-danger mb-0">Unable to update formulation.</div>');
        });
    });
})();
</script>
