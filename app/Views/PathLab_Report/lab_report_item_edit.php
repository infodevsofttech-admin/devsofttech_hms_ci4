<?php
$mstTestKey = 0;
$Test = '';
$TestID = '';
$Result = '';
$Formula = '';
$VRule = '';
$VMsg = '';
$Unit = '';
$FixedNormals = '';
$isGenderSpecific = 0;
$FixedNormalsWomen = '';
$checkbox_checked = '';

if (count($lab_test_parameter ?? []) > 0) {
    $mstTestKey = $lab_test_parameter[0]->mstTestKey ?? 0;
    $Test = $lab_test_parameter[0]->Test ?? '';
    $TestID = $lab_test_parameter[0]->TestID ?? '';
    $Result = $lab_test_parameter[0]->Result ?? '';
    $Formula = $lab_test_parameter[0]->Formula ?? '';
    $VRule = $lab_test_parameter[0]->VRule ?? '';
    $VMsg = $lab_test_parameter[0]->VMsg ?? '';
    $Unit = $lab_test_parameter[0]->Unit ?? '';
    $FixedNormals = $lab_test_parameter[0]->FixedNormals ?? '';
    $isGenderSpecific = (int) ($lab_test_parameter[0]->isGenderSpecific ?? 0);
    $FixedNormalsWomen = $lab_test_parameter[0]->FixedNormalsWomen ?? '';

    if ($isGenderSpecific == 1) {
        $checkbox_checked = 'checked';
    }
}
?>
<?= form_open() ?>
<div class="card admin-card">
    <div class="card-header bg-white">
        <h3 class="mb-0">Test Parameter</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Name of Test</label>
                    <input class="form-control" id="input_Test_name" placeholder="Test Name" value="<?= esc($Test) ?>" type="text" autocomplete="off">
                    <input type="hidden" id="mstTestKey" value="<?= esc($mstTestKey) ?>">
                    <input type="hidden" id="mstRepoKey" value="<?= esc($mstRepoKey ?? 0) ?>">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Test code</label>
                    <input class="form-control" id="input_test_code" placeholder="Test code" value="<?= esc($TestID) ?>" type="text" autocomplete="off">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Default Value</label>
                    <input class="form-control" id="input_Default" placeholder="Default Value" value="<?= esc($Result) ?>" type="text" autocomplete="off">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Formula</label>
                    <input class="form-control" id="input_Formula" placeholder="Formula" value="<?= esc($Formula) ?>" type="text" autocomplete="off">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Validation Rule</label>
                    <input class="form-control" id="input_Validation" placeholder="Validation Rule" value="<?= esc($VRule) ?>" type="text" autocomplete="off">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Unit</label>
                    <input class="form-control" id="input_Unit" placeholder="Unit" value="<?= esc($Unit) ?>" type="text" autocomplete="off">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Message</label>
                    <input class="form-control" id="input_Message" placeholder="Message" value="<?= esc($VMsg) ?>" type="text" autocomplete="off">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Fixed Normals</label>
                    <input class="form-control" id="input_Fixed" placeholder="Fixed Normals" value="<?= esc($FixedNormals) ?>" type="text" autocomplete="off">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label><input id="chk_isGenderSpecific" name="chk_isGenderSpecific" type="checkbox" <?= $checkbox_checked ?>> Is Gender Specific</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Fixed Normals For Women</label>
                    <input class="form-control" id="input_FixedNormalsWomen" placeholder="Fixed Normals For Women" value="<?= esc($FixedNormalsWomen) ?>" type="text" autocomplete="off">
                </div>
            </div>
        </div>
        <hr />
        <div class="row">
            <div class="col-md-12" id="div_option_list">
                <?= view('PathLab_Report/_test_option_table', ['lab_test_option' => $lab_test_option ?? [], 'mstTestKey' => $mstTestKey]) ?>
            </div>
            <div class="col-md-12">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Code</label>
                        <input class="form-control" id="input_op_value" name="input_op_value" placeholder="Test Name" value="" type="text" autocomplete="off">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label><input id="chk_bold" name="chk_bold" type="checkbox"> Bold</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-primary" id="btn_add_option">Add Option</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-4">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="button" class="btn btn-primary" id="btn_item_update">Update</button>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button onclick="load_form_div('<?= base_url('Lab_Admin/report_test_list') ?>/<?= esc($mstRepoKey ?? 0) ?>','test_div');" type="button" class="btn btn-outline-primary">Test List</button>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button onclick="load_form_div('<?= base_url('Lab_Admin/test_parameter_load') ?>/0/<?= esc($mstRepoKey ?? 0) ?>','test_div');" type="button" class="btn btn-outline-secondary">Add New Test</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?= form_close() ?>
<script>
    $('#btn_item_update').click(function() {
        var input_Test_name = $('#input_Test_name').val();
        var input_test_code = $('#input_test_code').val();
        var input_Default = $('#input_Default').val();
        var input_Formula = $('#input_Formula').val();
        var input_Validation = $('#input_Validation').val();
        var input_Unit = $('#input_Unit').val();
        var input_Message = $('#input_Message').val();
        var input_Fixed = $('#input_Fixed').val();
        var input_FixedNormalsWomen = $('#input_FixedNormalsWomen').val();
        var mstTestKey = $('#mstTestKey').val();
        var mstRepoKey = $('#mstRepoKey').val();
        var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';
        var isChecked = $('#chk_isGenderSpecific').is(':checked') ? 1 : 0;

        if (mstTestKey > 0) {
            $.post('<?= base_url('Lab_Admin/test_parameter_edit') ?>', {
                "input_Test_name": input_Test_name,
                "input_test_code": input_test_code,
                "input_Default": input_Default,
                "input_Formula": input_Formula,
                "input_Validation": input_Validation,
                "input_Unit": input_Unit,
                "input_Message": input_Message,
                "input_Fixed": input_Fixed,
                "input_isChecked": isChecked,
                "input_FixedNormalsWomen": input_FixedNormalsWomen,
                "mstTestKey": mstTestKey,
                "mstRepoKey": mstRepoKey,
                "<?= csrf_token() ?>": csrf_value
            }, function(data) {
                if (data.showcontent && typeof notify === 'function') {
                    notify('success', 'Saved', data.showcontent);
                }
            }, 'json');
        } else {
            $.post('<?= base_url('Lab_Admin/test_parameter_add') ?>', {
                "input_Test_name": input_Test_name,
                "input_test_code": input_test_code,
                "input_Default": input_Default,
                "input_Formula": input_Formula,
                "input_Validation": input_Validation,
                "input_Unit": input_Unit,
                "input_Message": input_Message,
                "input_Fixed": input_Fixed,
                "mstTestKey": mstTestKey,
                "mstRepoKey": mstRepoKey,
                "<?= csrf_token() ?>": csrf_value
            }, function(data) {
                if (data.insert_id > 0) {
                    $('#mstTestKey').val(data.insert_id);
                }
            }, 'json');
        }
    });

    $('#btn_add_option').click(function() {
        var input_op_value = $('#input_op_value').val();
        var chk_bold = $('#chk_bold').is(':checked') ? 1 : 0;
        var mstTestKey = $('#mstTestKey').val();
        var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';

        if (mstTestKey > 0) {
            $.post('<?= base_url('Lab_Admin/test_parameter_option_add') ?>', {
                "input_op_value": input_op_value,
                "chk_bold": chk_bold,
                "mstTestKey": mstTestKey,
                "<?= csrf_token() ?>": csrf_value
            }, function(data) {
                if (data.insert_id > 0) {
                    $('#div_option_list').html(data.option_content);
                }
            }, 'json');
        } else {
            alert('First create test');
        }
    });

    function sortchange(mstTestKey, option_current, sort_current, option_prev, sort_prev) {
        var postStr = '<?= base_url('Lab_Admin/change_sort') ?>/' + mstTestKey + '/' + option_current + '/' + sort_current + '/' + option_prev + '/' + sort_prev;
        var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';

        $.post(postStr, {
            "mstTestKey": mstTestKey,
            "<?= csrf_token() ?>": csrf_value
        }, function(data) {
            if (data.insert_id > 0) {
                $('#div_option_list').html(data.option_content);
            }
        }, 'json');
    }

    function remove_option(option_id, mstTestKey) {
        var postStr = '<?= base_url('Lab_Admin/remove_test_option') ?>/' + option_id + '/' + mstTestKey;
        var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';

        $.post(postStr, {
            "mstTestKey": mstTestKey,
            "<?= csrf_token() ?>": csrf_value
        }, function(data) {
            if (data.insert_id > 0) {
                $('#div_option_list').html(data.option_content);
            }
        }, 'json');
    }
</script>
