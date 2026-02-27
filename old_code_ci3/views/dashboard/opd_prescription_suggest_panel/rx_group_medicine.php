<?php echo form_open('Opd_prescription/add_medicine', array('role'=>'form','class'=>'form2')); ?>
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Prescribed/ Dosage : <?=$opd_prescription_template[0]->rx_group_name?></h3>
    </div>
    <div class="box-body">
        <div class="row" id="medical_table">
            <div class="col-md-12">
                <table class="table ">
                    <?php if(count($opd_prescrption_prescribed_template)>0) 
                { echo '<tr>
                            <th>Type</th>
                            <th>Prescribed</th>
                            <th>Dosage</th>
                            <th>Remarks</th>
                            <th></th>
                        </tr>'; } ?>
                    <?php foreach($opd_prescrption_prescribed_template as $row) { ?>
                    <tr>
                        <td>
                            <?=$row->med_type?></td>
                        <td><?=$row->med_name?></td>
                        <td><?=$row->dose_shed?></td>
                        <td><?=$row->dose_when?>
                            <?=$row->dose_frequency?> <?=$row->dose_where?>
                            <?=$row->qty?> <?=$row->no_of_days?> <?=$row->remark?></td>
                        <td>
                            <a href="javascript:medicalSelect('<?=$row->id?>')"><i class="fa fa-edit"></i></a>
                            <a href="javascript:medicalRemove('<?=$row->id?>','<?=$row->rx_group_id?>')"><i
                                    class="fa fa-remove"></i></a>
                        </td>
                    </tr>
                    <?php } ?>
                </table>
            </div>
        </div>
    </div>
    <div class="box-footer">
        <div class="row">
            <div class="col-md-8">
                <div class="form-group">
                    <div class="ui-widget">
                        <label for="tags">Prescribed: </label>
                        <input class="form-control input-sm" name="input_med_name" id="input_med_name"
                            placeholder="Like ACIVIR IV INJ 10ML , ACTIBILE 600TAB" type="text">
                        <input name="hid_med_id" id="hid_med_id" type="hidden" value="0">
                        <input name="hid_p_med_id" id="hid_p_med_id" type="hidden" value="0">
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <div class="ui-widget">
                        <label for="tags">Type </label>
                        <input class="form-control input-sm" name="input_med_type" id="input_med_type"
                            placeholder="TAB,CAP,SYR,INJ" type="text">
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <div class="ui-widget">
                        <label for="tags">Dose: </label>
                        <select class="form-control input-sm" id="input_dosage" name="input_dosage"
                            data-placeholder="Select a dosage timing">
                            <option value='0'>All</option>
                            <?php 
                            foreach($opd_dose_shed as $row)
                            { 
                                echo '<option value="'.$row->dose_shed_id.'"  >'.$row->dose_show_sign.'</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <div class="ui-widget">
                        <label for="tags">When: </label>
                        <select class="form-control input-sm" id="input_dosage_when" name="input_dosage_when"
                            data-placeholder="Select a dosage">
                            <option value='0'></option>
                            <?php 
                            foreach($opd_dose_when as $row)
                            { 
                                echo '<option value="'.$row->dose_when_id.'"  >'.$row->dose_sign.'</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <div class="ui-widget">
                        <label for="tags">Frequency: </label>
                        <select class="form-control input-sm" id="input_dosage_freq" name="input_dosage_freq"
                            data-placeholder="Select a Frequency">
                            <option value='0'></option>
                            <?php 
                            foreach($opd_dose_frequency as $row)
                            { 
                                echo '<option value="'.$row->dose_freq_id.'"  >'.$row->dose_sign.'</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <div class="ui-widget">
                        <label for="tags">Where: </label>
                        <select class="form-control input-sm" id="input_dose_where" name="input_dose_where"
                            data-placeholder="Place in body">
                            <option value='0'></option>
                            <?php 
                            foreach($opd_dose_where as $row)
                            { 
                                echo '<option value="'.$row->dose_where_id.'"  >'.$row->dose_sign.'</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <div class="ui-widget">
                        <label for="tags">Duration: </label>
                        <input class="form-control input-sm" name="input_no_of_days" id="input_no_of_days" type="text">
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <div class="ui-widget">
                        <label for="tags">Qty: </label>
                        <input class="form-control input-sm" name="input_qty" id="input_qty" type="text">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <div class="ui-widget">
                        <label for="tags">Remark: </label>
                        <input class="form-control input-sm" name="input_remark" id="input_remark" placeholder=""
                            type="text">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <div class="ui-widget">
                        <label for="tags">Salt Name: </label>
                        <input class="form-control input-sm" name="input_genericname" id="input_genericname" placeholder="" type="text">
                    </div>
                </div>
            </div>
            <div class="col-md-1">
                <div class="form-group">
                    <div class="ui-widget">
                        <label for="tags"> </label>
                        <button type="button" id="btn_medical" class="btn btn-primary">+ADD / Update</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php echo form_close(); ?>
<script>
var cachemedical = {};
var csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

function medicalRemove(p_med_id, rx_group_id) {
    load_form_div('/Opd_prescription/remove_medical/' + p_med_id + '/' + rx_group_id, 'medical_table');
}

function medicalSelect(p_med_id) {

    var csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

    $.post('/Opd_prescription/medical_Select_prescription_template', {
            "p_med_id": p_med_id,
            "<?=$this->security->get_csrf_token_name()?>": csrf_value
        },
        function(data) {
            $("#input_med_name").val(data.med_name);
            $("#hid_med_id").val(data.med_id);
            $("#input_med_type").val(data.med_type);
            $("#input_dosage").val(data.dosage);
            $("#input_dosage_when").val(data.dosage_when);
            $("#input_dosage_freq").val(data.dosage_freq);
            $("#input_no_of_days").val(data.no_of_days);
            $("#input_qty").val(data.qty);
            $("#input_remark").val(data.remark);
            $("#input_dose_where").val(data.dose_where);
            $("#input_genericname").val(data.genericname);
            
            $("#hid_p_med_id").val(data.p_med_id);

        }, 'json');
}

$(document).ready(function() {
    $('#btn_medical').click(function() {

        var input_med_name = $('#input_med_name').val();
        var hid_med_id = $('#hid_med_id').val();
        var input_med_type = $('#input_med_type').val();
        var input_dosage = $('#input_dosage').val();
        var input_dosage_when = $('#input_dosage_when').val();
        var input_dosage_freq = $('#input_dosage_freq').val();
        var input_no_of_days = $('#input_no_of_days').val();
        var input_qty = $('#input_qty').val();
        var input_remark = $('#input_remark').val();
        var input_dose_where = $('#input_dose_where').val();
        var input_genericname = $('#input_genericname').val();
        var hid_p_med_id = $("#hid_p_med_id").val();

        var csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

        $.post('/Opd_prescription/rx_group_medicine_save/<?=$rx_group_id?>', {
                "input_med_name": input_med_name,
                "input_med_type": input_med_type,
                "input_dosage": input_dosage,
                "input_dosage_when": input_dosage_when,
                "input_dosage_freq": input_dosage_freq,
                "input_no_of_days": input_no_of_days,
                "input_qty": input_qty,
                "input_remark": input_remark,
                "input_dose_where": input_dose_where,
                "input_genericname":input_genericname,
                "hid_med_id": hid_med_id,
                "<?=$this->security->get_csrf_token_name()?>": csrf_value
            },
            function(data) {
                $('#medical_table').html(data);
                $("#input_med_name").val('');
                $("#input_med_type").val('');
                $("#input_dosage").val('');
                $("#input_dosage_when").val('');
                $("#input_dosage_freq").val('');
                $("#input_no_of_days").val('');
                $("#input_qty").val('');
                $("#input_remark").val('');
                $("#input_dose_where").val('');
                $("#div_genericname").html('');
                $("#hid_med_id").val('0');
                $("#hid_p_med_id").val('0');
                $('#input_med_name').focus();
            });
    });

    $("#input_med_name").autocomplete({
        source: function(request, response) {
            var term = request.term;
            if (term in cachemedical) {
                response(cachemedical[term]);
                return;
            }
            $.getJSON("Opd_prescription/get_medical", request, function(data, status, xhr) {
                cachemedical[term] = data;
                response(data);
            });
        },
        minLength: 1,
        autofocus: true,
        select: function(event, ui) {
            $("#input_med_name").val(ui.item.value);
            $("#hid_med_id").val(ui.item.med_id);
            $("#input_med_type").val(ui.item.formulation);
            $("#input_genericname").val(ui.item.genericname);
        }
    });


    $("#input_med_name").autocomplete({
        source: function(request, response) {
            var term = request.term;
            if (term in cachemedical) {
                response(cachemedical[term]);
                return;
            }
            $.getJSON("Opd_prescription/get_medical", request, function(data, status, xhr) {
                cachemedical[term] = data;
                response(data);
            });
        },
        minLength: 1,
        autofocus: true,
        select: function(event, ui) {
            $("#input_med_name").val(ui.item.value);
            $("#hid_med_id").val(ui.item.med_id);
            $("#input_med_type").val(ui.item.formulation);
            $("#input_genericname").val(ui.item.genericname);
        }
    });

});
</script>