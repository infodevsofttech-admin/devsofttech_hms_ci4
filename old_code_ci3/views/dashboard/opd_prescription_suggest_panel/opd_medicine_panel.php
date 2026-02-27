<?php echo form_open('Opd_prescription/add_medicine', array('role' => 'form', 'class' => 'form2')); ?>
<div class="col-md-6">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Medicine List</h3>
        </div>
        <div class="box-body">
            <div class="row" id="medical_table">
                <div class="col-md-12">
                    <table class="table ">
                        <?php if (count($opd_med_master) > 0) {
                            echo '<tr>
                            <th>Type</th>
                            <th>Prescribed</th>
                            <th>Dosage</th>
                            <th>Remarks</th>
                            <th></th>
                        </tr>';
                        } ?>
                        <?php foreach ($opd_med_master as $row) { ?>
                            <tr>
                                <td> <?= $row->formulation ?></td>
                                <td><?= $row->item_name ?></td>
                                <td><?= $row->dose_shed ?></td>
                                <td><?= $row->dose_when ?>
                                    <?= $row->dose_frequency ?> <?= $row->dose_where ?>
                                    <?= $row->qty ?> <?= $row->no_of_days ?> <?= $row->remark ?></td>
                                <td>
                                    <a href="javascript:medicalSelect('<?= $row->id ?>')"><i class="fa fa-edit"></i></a>
                                </td>
                            </tr>
                        <?php } ?>
                    </table>
                </div>
            </div>
        </div>
        <div class="box-footer">

        </div>
    </div>
</div>
<div class="col-md-6">
</div>
    <?php echo form_close(); ?>
    <script>
        var cachemedical = {};
        var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

        function medicalRemove(p_med_id, rx_group_id) {
            load_form_div('/Opd_prescription/remove_medical/' + p_med_id + '/' + rx_group_id, 'medical_table');
        }

        function medicalSelect(p_med_id) {

            var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

            $.post('/Opd_prescription/medical_Select_prescription_template', {
                    "p_med_id": p_med_id,
                    "<?= $this->security->get_csrf_token_name() ?>": csrf_value
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

                var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

                $.post('/Opd_prescription/rx_group_medicine_save/<?= $rx_group_id ?>', {
                        "input_med_name": input_med_name,
                        "input_med_type": input_med_type,
                        "input_dosage": input_dosage,
                        "input_dosage_when": input_dosage_when,
                        "input_dosage_freq": input_dosage_freq,
                        "input_no_of_days": input_no_of_days,
                        "input_qty": input_qty,
                        "input_remark": input_remark,
                        "input_dose_where": input_dose_where,
                        "input_genericname": input_genericname,
                        "hid_med_id": hid_med_id,
                        "<?= $this->security->get_csrf_token_name() ?>": csrf_value
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