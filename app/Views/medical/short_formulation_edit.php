<?php
$sfid = 0;
$name = '';

if (!empty($med_short_formulation ?? [])) {
    $row  = $med_short_formulation[0];
    $sfid = (int) ($row->id ?? 0);
    $name = (string) ($row->short_formulation ?? '');
}
?>

<div class="card">
    <div class="card-header">Short Formulation</div>
    <div class="card-body pt-3">
        <div id="short_formulation_msg"></div>

        <form id="short-formulation-form" class="row g-2" method="post" action="javascript:void(0)">
            <?= csrf_field() ?>
            <input type="hidden" id="hid_sfid" name="hid_sfid" value="<?= esc((string) $sfid) ?>">

            <div class="col-md-8">
                <label class="form-label">Short Formulation Name</label>
                <input class="form-control" name="input_short_formulation_name" id="input_short_formulation_name"
                       placeholder="e.g. Tab, Cap, Syr, Inj…" type="text" value="<?= esc($name) ?>">
            </div>

            <div class="col-12">
                <button type="button" class="btn btn-primary" id="btn_short_formulation_update" accesskey="A">Add &amp; Update Short Formulation</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    function showMessage(html) {
        $('#short_formulation_msg').html(html || '');
    }

    $('#btn_short_formulation_update').off('click').on('click', function () {
        $.post('<?= base_url('Product_master/ShortFormulationUpdate') ?>', $('#short-formulation-form').serialize(), function (data) {
            if (!data || typeof data !== 'object') {
                showMessage('<div class="alert alert-danger mb-0">Unexpected response.</div>');
                return;
            }
            showMessage(data.show_text || '');
            if ((data.insertid || 0) > 0) {
                $('#hid_sfid').val(data.insertid);
                if (typeof refreshShortFormulationMasterList === 'function') {
                    refreshShortFormulationMasterList();
                }
                load_form_div('<?= base_url('Product_master/ShortFormulationEdit') ?>/' + data.insertid, 'test_div_sform');
            }
        }, 'json').fail(function () {
            showMessage('<div class="alert alert-danger mb-0">Unable to update short formulation.</div>');
        });
    });
})();
</script>
