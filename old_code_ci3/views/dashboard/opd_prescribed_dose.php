<?php echo form_open('Opd_prescription/add_medicine', array('role'=>'form','class'=>'form2','autocomplete'=>'on')); ?>
<div class="col-md-8">
    <div class="box box-primary">
        <div class="box-header with-border">
            <div class="col-md-6">
                <h3 class="box-title">Prescribed/ Dosage</h3>
            </div>
            <div class="col-md-6">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control input-sm" id="txt_rxgroup" name="txt_rxgroup">
                    <span class="input-group-btn">
                        <button type="button" onclick="SaveAsRsGroup()" class="btn btn-xs btn-info btn-flat">Save As
                            RX-Group</button>
                    </span>
                </div>
            </div>
        </div>
        <div class="box-body">
            <div class="row" id="medical_table">
                <div class="col-md-12">
                    <table class="table ">
                        <?php if(count($opd_drug)>0) 
                    { echo '<tr>
                                <th>Type</th>
                                <th>Prescribed</th>
                                <th>Dosage</th>
                                <th>Remarks</th>
                                <th></th>
                                <th></th>
                            </tr>'; } ?>
                        <?php foreach($opd_drug as $row) { ?>
                        <tr>
                            <td><?=$row->med_type?></td>
                            <td><?=$row->med_name?></td>
                            <td><?=$row->dose_shed?></td>
                            <td><?=$row->dose_when?>
                                <?=$row->dose_frequency?> <?=$row->dose_where?>
                                <?=$row->qty?> <?=$row->no_of_days?> <?=$row->remark?></td>
                            <td>
                                <a href="javascript:medicalSelect('<?=$row->id?>','<?=$row->opd_pre_id?>')"><i
                                        class="fa fa-edit"></i></a>
                            </td>
                            <td>
                                <a href="javascript:medicalRemove('<?=$row->id?>','<?=$row->opd_pre_id?>')"><i
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
                            <input class="form-control input-sm" name="input_med_name" id="input_med_name" type="text">
                            <input name="hid_opd_med_item_id" id="hid_opd_med_item_id" type="hidden" value="0">
                            <input name="hid_med_id" id="hid_med_id" type="hidden" value="0">
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
                <div class="col-md-3">
                    <div class="form-group">
                        <div class="ui-widget">
                            <label for="tags">Dose: </label>
                            <select class="form-control input-sm" id="input_dosage" name="input_dosage"
                                data-placeholder="Select a dosage timing">
                                <option value='0'></option>
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
                <div class="col-md-3">
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
                <div class="col-md-3">
                    <div class="form-group">
                        <div class="ui-widget">
                            <label for="input_no_of_days">Duration: </label>
                            <input class="form-control input-sm" name="input_no_of_days" id="input_no_of_days"
                                type="text" >
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <div class="ui-widget">
                            <label for="tags">Where: </label>
                            <select class="form-control input-sm" id="input_dose_where" name="input_dose_where"
                                data-placeholder="Body Place">
                                <option value='0'> </option>
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
                <div class="col-md-3">
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
                            <label for="input_remark">Medicine Advice: </label>
                            <input class="form-control input-sm" name="input_remark" id="input_remark" placeholder=""
                                type="text">
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
            <div class="row">
                <div class="col-md-12">
                    <p name="div_genericname" id="div_genericname"></p>
                </div>
            </div>
        </div>
    </div>
    <div class="box box-success">
        <div class="box-header with-border">
        </div>
        <div class="box-body">
            <div class="col-md-12" id="opd_patient_advice">
            <?=$advice_given?>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <div class="ui-widget">
                        <label for="tags">Advise Add</label>
                        <textarea class="form-control " name="input_advice" id="input_advice" onchange="save_prescribed_extra()" ><?=$opd_prescription[0]->advice?></textarea>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <div class="ui-widget">
                        <label for="tags">Next Visit</label>
                        <input class="form-control input-sm" name="input_next_visit" id="input_next_visit" type="text"
                            value="<?=$opd_prescription[0]->next_visit?>" onchange="save_prescribed_extra()"
                            autocomplete="true">
                    </div>
                </div>
                <div class="btn-group">
                    <?php
                    foreach ($opd_nextvisit as $row) {
                    ?>
                        <button type="button" class="btn btn-default" onclick="add_next_visit('<?= $row->next_visit_day ?>')"><?= $row->next_visit_day ?></button>
                    <?php
                    }
                    ?>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <div class="ui-widget">
                        <label for="tags">Refer To </label>
                        <input class="form-control input-sm" name="input_refer_to" id="input_refer_to" type="text"
                            value="<?=$opd_prescription[0]->refer_to?>" onchange="save_prescribed_extra()"
                            autocomplete="true">
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<div class="col-md-4">
    <div class="panel-group" id="accordion">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" data-parent="#accordion" href="#collapse1">
                        Rx Group</a>
                </h4>
            </div>
            <div id="collapse1" class="panel-collapse collapse">
                <div class="panel-body">
                    <div id="rx_group_panel" name="rx_group_panel"></div>
                </div>
            </div>
        </div>
        <div class="panel panel-success">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" data-parent="#accordion" href="#collapse2">
                        Old Prescription</a>
                </h4>
            </div>
            <div id="collapse2" class="panel-collapse collapse in">
                <div class="panel-body">
                    <div id="old_prescribed"></div>
                </div>
            </div>
        </div>
        <div class="panel panel-warning">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" data-parent="#accordion" href="#collapse3">
                        Pre define Advise</a>
                        <button type="button" class="btn btn-box-tool " >+ Add Advise</button>
                </h4>
            </div>
            <div id="collapse3" class="panel-collapse collapse">
                <div class="panel-body">
                    <?php
                        if(count($opd_advise)>0){
                            echo '<table class="table">';
                            foreach($opd_advise as $row)
                            {
                                echo '<tr>';
                                echo '<td>'.$row->advice.'</td><td><a href="javascript:Add_advice('.$row->id.')" >+Add</a></td>';
                                echo '</tr>';
                            }
                            echo '</table>';
                        }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php echo form_close(); ?>
<script>
//Medical

load_form_div('/Opd_prescription/save_rx_group_list/<?=$doc_id?>', 'rx_group_panel');
load_form_div('/Opd_prescription/show_old_Prescribed/<?=$p_id?>/<?=$opd_id?>', 'old_prescribed');

var cachemedical = {};
var csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

function add_next_visit(test_name) {
        var old_text = $('#input_next_visit').val();
        $('#input_next_visit').val(test_name);
        save_prescribed_extra();
}

function medicalRemove(med_prec_id, opd_session_id) {
    var opd_session_id = $('#opd_session_id').val();
    load_form_div('/Opd_prescription/medical_prescribed_Remove/' + med_prec_id + '/' + opd_session_id, 'medical_table');
}

function medicalSelect(med_prec_id, opd_session_id) {
    var opd_session_id = $('#opd_session_id').val();
    var csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

    $.post('/Opd_prescription/medical_Select', {
            "opd_session_id": opd_session_id,
            "med_prec_id": med_prec_id,
            "<?=$this->security->get_csrf_token_name()?>": csrf_value
        },
        function(data) {
            $("#input_med_name").val(data.med_name);
            $("#input_med_type").val(data.med_type);
            $("#input_dosage").val(data.dosage);
            $("#input_dosage_when").val(data.dosage_when);
            $("#input_dosage_freq").val(data.dosage_freq);
            $("#input_dose_where").val(data.dosage_where);
            $("#input_no_of_days").val(data.no_of_days);
            $("#input_qty").val(data.qty);
            $("#input_remark").val(data.remark);
            $("#hid_opd_med_item_id").val(data.id);
            $("#hid_med_id").val(data.med_id);
            $("#input_med_name").focus();
        }, 'json');
}

function SaveAsRsGroup() {
    var opd_session_id = $('#opd_session_id').val();
    var doc_id = <?=$doc_id?>;
    var rx_group_name = $('#txt_rxgroup').val();
    var csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

    if (rx_group_name == '') {
        alert('Name should be not blank')

    } else {
        $.post('/Opd_prescription/save_rx_group_from_opd_prescription', {
                "opd_session_id": opd_session_id,
                "doc_id": doc_id,
                "rx_group_name": rx_group_name,
                "<?=$this->security->get_csrf_token_name()?>": csrf_value
            },
            function(data) {
                load_form_div('/Opd_prescription/save_rx_group_list/<?=$doc_id?>', 'rx_group_panel');
            });
    }
}

function add_prescribe(rx_group_id) {
    var opd_session_id = $('#opd_session_id').val();
    var csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

    $.post('/Opd_prescription/rx_group_select/' + rx_group_id, {
            "opd_session_id": opd_session_id,
            "rx_group_id": rx_group_id,
            "<?=$this->security->get_csrf_token_name()?>": csrf_value
        },
        function(data) {

            $('#medical_table').html(data);

        });

    $("#input_med_name").focus();
}

function save_prescribed_extra() {
    var opd_session_id = $('#opd_session_id').val();

    var input_advice = $('#input_advice').val();
    var input_refer_to = $('#input_refer_to').val();
    var input_next_visit = $('#input_next_visit').val();

    var csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

    $.post('/Opd_prescription/save_prescribed_extra', {
            "input_advice": input_advice,
            "input_refer_to": input_refer_to,
            "input_next_visit": input_next_visit,
            "opd_session_id": opd_session_id,
            "<?=$this->security->get_csrf_token_name()?>": csrf_value
        },
        function(data) {

        });
}




function Add_advice(advice_id)
{
    var opd_session_id = $('#opd_session_id').val();
    var csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

    $.post('/Opd_prescription/add_patient_advice/', {
                "opd_session_id": opd_session_id,
                "advice_id": advice_id,
                "opd_id":<?=$opd_id?>,
                "<?=$this->security->get_csrf_token_name()?>": csrf_value
            },
            function(data) {
                $('#opd_patient_advice').html(data);
            });
    
}

function del_advice(advice_id)
{
    load_form_div('/Opd_prescription/del_patient_advice/'+advice_id,'opd_patient_advice');
}

$(document).ready(function() {
    $('#btn_medical').click(function() {
        var opd_session_id = $('#opd_session_id').val();

        var input_med_name = $('#input_med_name').val();
        var input_med_type = $('#input_med_type').val();
        var input_dosage = $('#input_dosage').val();
        var input_dosage_when = $('#input_dosage_when').val();
        var input_dosage_freq = $('#input_dosage_freq').val();
        var input_no_of_days = $('#input_no_of_days').val();
        var input_dose_where = $('#input_dose_where').val();
        var input_qty = $('#input_qty').val();
        var input_remark = $('#input_remark').val();
        var hid_opd_med_item_id = $('#hid_opd_med_item_id').val();
        var hid_med_id = $('#hid_med_id').val();

        var csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

        $.post('/Opd_prescription/add_medical', {
                "opd_session_id": opd_session_id,
                "input_med_name": input_med_name,
                "input_med_type": input_med_type,
                "input_dosage": input_dosage,
                "input_dosage_when": input_dosage_when,
                "input_dosage_freq": input_dosage_freq,
                "input_no_of_days": input_no_of_days,
                "input_dose_where": input_dose_where,
                "input_qty": input_qty,
                "input_remark": input_remark,
                "opd_med_item_id": hid_opd_med_item_id,
                "hid_med_id": hid_med_id,
                "<?=$this->security->get_csrf_token_name()?>": csrf_value
            },
            function(data) {
                $('#medical_table').html(data);
                $("#input_med_name").val('');
                $("#input_med_type").val('');
                $("#input_dosage").val('');
                $("#input_dosage_when").val('');
                $("#input_dosage_freq").val(1);
                $("#input_no_of_days").val('');
                $("#input_dose_where").val('');
                $("#input_qty").val('');
                $("#input_remark").val('');
                $("#div_genericname").html('');
                $("#hid_opd_med_item_id").val('0');
                $("#hid_med_id").val('0');
                $("#input_med_name").focus();
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
            $("#div_genericname").html(ui.item.genericname);
            $("#input_dosage").val(ui.item.dosage);
            $("#input_dosage_when").val(ui.item.dosage_when);
            $("#input_dosage_freq").val(ui.item.dosage_freq);
            $("#input_no_of_days").val(ui.item.no_of_days);
            $("#input_dose_where").val(ui.item.dose_where);
            $("#input_qty").val(ui.item.qty);
            $("#input_remark").val(ui.item.remark);
        }
    });


});
</script>

<script>
$(function() {
        var availableTags = [<?=$opd_dose_duration?>];

        var availableRemarks = [<?=$opd_dose_remark?>];

        var availableFormulation = [<?=$med_formulation?>];

        $("#input_dosage_freq").val(1);

        function split(val) {
            return val.split(/ \s*/);
        }

        function extractLast(term) {
            return split(term).pop();
        }

        $("#input_no_of_days")
            // don't navigate away from the field on tab when selecting an item
            .on("keydown", function(event) {
                if (event.keyCode === $.ui.keyCode.TAB &&
                    $(this).autocomplete("instance").menu.active) {
                    event.preventDefault();
                }
            })
            .autocomplete({
                minLength: 0,
                source: function(request, response) {
                    // delegate back to autocomplete, but extract the last term
                    response($.ui.autocomplete.filter(
                        availableTags, extractLast(request.term)));
                },
                focus: function() {
                    // prevent value inserted on focus
                    return false;
                },
                select: function(event, ui) {
                    var terms = split(this.value);
                    // remove the current input
                    terms.pop();
                    // add the selected item
                    terms.push(ui.item.value);
                    // add placeholder to get the comma-and-space at the end
                    terms.push("");
                    this.value = terms.join(" ");
                    return false;
                }
            });

        $("#input_remark").autocomplete({
            source: availableRemarks
        });

        $("#input_med_type").autocomplete({
            source: availableFormulation
        });
    }

);
</script>