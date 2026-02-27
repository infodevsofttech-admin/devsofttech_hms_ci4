<section class="content">
    <div class="row">
        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Investigation Value</h3>
                </div>
                <div class="box-body">
                    <?php echo form_open('Opd_prescription/add_investigation_value', array('role'=>'form','class'=>'form_investigation')); ?>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label> Date of Test</label>
                                <div class="input-group date input-sm">
                                    <div class="input-group-addon">
                                        <i class="fa fa-calendar"></i>
                                    </div>
                                    <input class="form-control pull-right datepicker input-sm"
                                        name="datepicker_investigation" id="datepicker_investigation" type="text"
                                        data-inputmask="'alias': 'dd/mm/yyyy'" data-mask value="<?=date('d/m/Y')?>" />
                                </div>
                                <!-- /.input group -->
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group ">
                                <label>Investigation</label>
                                <input type="text" class="form-control input-sm" id="input_investigation"
                                    name="input_investigation" placeholder="X-Ray Chest PA, SGOT ">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group ">
                                <label for="input_glucose_value">Value</label>
                                <input type="number" class="form-control input-sm number" id="input_investigation_value"
                                    name="input_investigation_value" placeholder="Value">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group ">
                                <label for="input_glucose_value">Report</label>
                                <input type="text" class="form-control input-sm" id="input_investigation_report"
                                    name="input_investigation_report" placeholder="Report">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group ">
                                <button type="button" onclick="save_investigation_value()"
                                    class="btn btn-danger input-sm" id="btn_add">Add Data</button>
                            </div>
                        </div>
                    </div>
                    <?php echo form_close(); ?>

                </div>
                <!-- /.box-body -->
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Investigation Value</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <table class="table">
                            <tr>
                                <th style="text-align:center ;">Date</th>
                                <th style="text-align:center ;">Investigation</th>
                                <th style="text-align:center ;">Value</th>
                                <th style="text-align:center ;">Normal Range</th>
                                <th style="text-align:center ;">Result</th>
                                <th style="text-align:center ;">Remove</th>
                            </tr>
                            <?php foreach($patient_investigation_data as $row){ ?>
                            <tr>
                                <td style="text-align:center ;"><?=$row->date_investigation?></td>
                                <td style="text-align:center ;"><?=$row->investigation_name?></td>
                                <td style="text-align:center ;"><?=$row->result?></td>
                                <td style="text-align:center ;"><?=$row->normal_range?></td>
                                <td style="text-align:center ;"><?=$row->result_text?></td>
                                <td style="text-align:center ;"><button type="button"
                                        onclick="delete_investigation_value(<?=$row->id?>)"
                                        class="btn btn-block btn-warning input-sm" id="btn_add">Delete</button></td>
                            </tr>
                            <?php } ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
</section>
<script>
function save_investigation_value() {
    $("#btn_add").prop("disabled", true);

    $.post('/Opd_prescription/add_investigation_value/<?=$p_id?>', $('form.form_investigation').serialize(), function(
        data) {
        if (data.insertid == 0) {
            notify('msg', 'Please Attention', data.msg);
            $("#btn_add").prop("disabled", false);
        } else {
            notify('success', 'Data Added', data.msg);
            load_form_div('/Opd_prescription/patient_investigation_data/<?=$p_id ?>', 'investigation');
        }
    }, 'json');
}

function delete_investigation_value(g_id) {
    if (confirm('Are you sure to delete this entry')) {
        load_form_div('/Opd_prescription/patient_investigation_value_del/<?=$p_id ?>/' + g_id, 'investigation');

    }
}

$(function() {
    $('#datepicker_investigation').datepicker({
        autoclose: true,
        format: 'dd/mm/yyyy'
    })
});
</script>