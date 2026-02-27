<div class="row">
    <?php echo form_open('Opd_prescription/add_medicine', array('role' => 'form', 'class' => 'form2', 'autocomplete' => 'on')); ?>
    <div class="col-md-6">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Prescribed/ Dosage</h3>
            </div>
            <div class="box-body">
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
                                <input class="form-control input-sm" name="input_med_type" id="input_med_type" placeholder="TAB,CAP,SYR,INJ" type="text">
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
            <div class="box-footer">
                
            </div>

        </div>
    </div>
    <div class="col-md-6">
    <div class="box box-danger">
            <div class="box-header with-border">
                <h3 class="box-title">Treatment </h3>
            </div>
            <div class="box-body">
            <div class="row" id="medical_table">
                    <div class="col-md-12">
                        <table class="table ">
                            <?php if (count($ipd_treatment_chart) > 0) {
                                echo '<tr>
                                <th>Type</th>
                                <th>Prescribed</th>
                                <th>Dosage</th>
                                <th>Remarks</th>
                                <th></th>
                                <th></th>
                            </tr>';
                            } ?>
                            <?php foreach ($ipd_treatment_chart as $row) { ?>
                                <tr>
                                    <td><?= $row->med_type ?></td>
                                    <td><?= $row->med_name ?></td>
                                    <td><?= $row->dose_shed ?></td>
                                    <td><?= $row->dose_when ?>
                                        <?= $row->dose_frequency ?> <?= $row->dose_where ?>
                                        <?= $row->qty ?> <?= $row->no_of_days ?> <?= $row->remark ?></td>
                                    <td>
                                        <a href="javascript:medicalSelect('<?= $row->id ?>','<?= $row->opd_pre_id ?>')"><i class="fa fa-edit"></i></a>
                                    </td>
                                    <td>
                                        <a href="javascript:medicalRemove('<?= $row->id ?>','<?= $row->opd_pre_id ?>')"><i class="fa fa-remove"></i></a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </table>
                    </div>
                </div>
            </div>
    </div>
    </div>
</div>
<div class="row">

</div>

<script>
    $(document).ready(function() {
        var cachemedical = {};

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

            var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

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
                    "<?= $this->security->get_csrf_token_name() ?>": csrf_value
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