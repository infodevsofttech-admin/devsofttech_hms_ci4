<section class="content-header">
    <h1>
        <p class="text-primary"> Name : <span class="text-success"> <?= ucwords($ipd_master[0]->p_fname); ?></span>
            / Gender : <span class="text-success"><?= $ipd_master[0]->xgender ?></span>
            / Age : <span class="text-success"><?= $ipd_master[0]->str_age ?> </span>
            / IPD ID : <span class="text-success"><?= $ipd_master[0]->ipd_code ?> </span></p>
    </h1>
</section>
<!-- Main content -->
<section class="content">
    <div class="jsError"></div>
    <input type="hidden" value="<?= $ipd_master[0]->p_id ?>" id="p_id" name="p_id" />
    <input type="hidden" value="<?= $ipd_master[0]->id ?>" id="ipd_id" name="pid_id" />
    <div class="row">
        <div class="col-xs-3">
            <ul class="nav  nav-tabs tabs-left">
                <li class="active"><a data-toggle="tab" href="#menu2">Presenting Complaints with Duration and Reason for Admission</a></li>
                <li><a data-toggle="tab" data-target='#home'>Physical Examinations</a></li>
                <li><a data-toggle="tab" href="#menu1">Clinical Investigation Reports</a></li>
                <li><a data-toggle="tab" href="#discharge_status">Admission / Discharge Information</a></li>
                <li><a data-toggle="tab" href="#surgery_procedure">Surgery / Procedure / delivery if any </a></li>
                <li><a data-toggle="tab" href="#menu3">Final Diagnosis</a></li>
                <li><a data-toggle="tab" href="#menu4">Summary of key investigation during Hospitalization</a></li>
                <li><a data-toggle="tab" href="#menu5">Course / Treatment in the hospital</a></li>
                <li><a data-toggle="tab" href="#menu8">Condition at the time of Discharge</a></li>
                <li><a data-toggle="tab" href="#menu6">Discharge Medicince Prescribed</a></li>
                <li><a data-toggle="tab" href="#menu7">Discharge Instructions/Advise (diet, activity, discharged to home/nursing facility, etc)</a></li>
            </ul>
        </div>
        <div class="col-xs-9">
            <div class="tab-content">
                <div id="discharge_status" class="tab-pane fade in">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h2 class="box-title">Discharge Information</h3>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Department</label>
                                        <select id="dept_id" name="dept_id" class="form-control">
                                            <?php foreach ($hc_department as $row) { ?>
                                                <option value="<?= $row->iId ?>"
                                                    <?= combo_checked($row->iId, $ipd_master[0]->dept_id) ?>>
                                                    <?= $row->vName ?></option>
                                            <?php }  ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Admission Date</label>
                                        <div class="input-group date">
                                            <div class="input-group-addon">
                                                <i class="fa fa-calendar"></i>
                                            </div>
                                            <input id="register_date" name="register_date" class="form-control "
                                                type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask=""
                                                value="<?php echo MysqlDate_to_str($ipd_master[0]->register_date); ?>" readonly />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Time:</label>
                                        <div class="input-group">
                                            <input id="reg_time" name="reg_time" class="form-control" type="text"
                                                value="<?= $ipd_master[0]->reg_time ?>" readonly>
                                            <!-- <div class="input-group-addon">
                                                <i class="fa fa-clock-o"></i>
                                            </div> -->
                                        </div>
                                        <!-- /.input group -->
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Discharge Date</label>
                                        <div class="input-group date">
                                            <div class="input-group-addon">
                                                <i class="fa fa-calendar"></i>
                                            </div>
                                            <input id="dis_date" name="dis_date" class="form-control pull-right datepicker"
                                                type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask=""
                                                value="<?php echo ($ipd_master[0]->discharge_date ? MysqlDate_to_str($ipd_master[0]->discharge_date) : date('d/m/Y')); ?>" />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Time:</label>
                                        <div class="input-group">
                                            <input id="dis_time" name="dis_time" class="form-control" type="text"
                                                value="<?= $ipd_master[0]->discharge_time ?>">
                                            <div class="input-group-addon">
                                                <i class="fa fa-clock-o"></i>
                                            </div>
                                        </div>
                                        <!-- /.input group -->
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Patient Status</label>
                                        <select id="p_status" name="p_status" class="form-control">
                                            <?php foreach ($ipd_discharge_status as $row) { ?>
                                                <option value="<?= $row->id ?>"
                                                    <?= combo_checked($row->id, $ipd_master[0]->discarge_patient_status) ?>>
                                                    <?= $row->status_desc ?></option>
                                            <?php }  ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <?php if ($ipd_master[0]->ipd_status == 0) { ?>
                                <button type="button" id="btn_update_request" name="btn_update_request"
                                    class="btn btn-primary">Update</button>
                            <?php } ?>
                        </div>

                    </div>
                </div>
                <div id="surgery_procedure" class="tab-pane fade in ">
                    <div class="row">
                        <div class="box box-primary">
                            <div class="box-header with-border">
                                <h2 class="box-title">Surgery</h3>
                            </div>
                            <div class="box-body">
                                <div class="row" id="surgery_table">
                                    <div class="col-md-12">
                                        <table class="table table-condensed">
                                            <?php if (count($ipd_discharge_surgery) > 0) {
                                                echo '<tr>
														<th>Surgery Name</th>
														<th width="300px">Surgery Date</th>
														<th>Remarks</th>
														<th></th>
														<th></th>
													</tr>';
                                            } ?>
                                            <?php foreach ($ipd_discharge_surgery as $row) { ?>
                                                <tr>
                                                    <td><input class="form-control input-sm"
                                                            name="input_surgery_name_<?= $row->id ?>"
                                                            id="input_surgery_name_<?= $row->id ?>" type="text"
                                                            value="<?= $row->surgery_name ?>">
                                                    </td>
                                                    <td>
                                                        <div class="input-group date">
                                                            <div class="input-group-addon">
                                                                <i class="fa fa-calendar"></i>
                                                            </div>
                                                            <input id="surgery_date_<?= $row->id ?>"
                                                                name="surgery_date_<?= $row->id ?>"
                                                                class="form-control pull-right datepicker" type="text"
                                                                data-inputmask="'alias': 'dd/mm/yyyy'" data-mask=""
                                                                value="<?php echo ($row->surgery_date ? MysqlDate_to_str($row->surgery_date) : date('d/m/Y')); ?>" />
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input class="form-control input-sm"
                                                            name="input_surgery_remark_<?= $row->id ?>"
                                                            id="input_surgery_remark_<?= $row->id ?>" type="text"
                                                            value="<?= $row->surgery_remark ?>">
                                                    </td>
                                                    <td><a
                                                            href="javascript:surgeryUpdate('<?= $row->id ?>','<?= $row->ipd_id ?>')">Update</a>
                                                    </td>
                                                    <td><a
                                                            href="javascript:surgeryRemove('<?= $row->id ?>','<?= $row->ipd_id ?>')">Remove</a>
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
                                                <label for="tags">Surgery: </label>
                                                <input class="form-control input-sm" name="input_surgery_name"
                                                    id="input_surgery_name"
                                                    placeholder="Like Arthroscopy , Circumcision" type="text">
                                                <input type="hidden" id="input_surgery_id" name="input_surgery_id"
                                                    value="0">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Surgery Date</label>
                                            <div class="input-group date">
                                                <div class="input-group-addon">
                                                    <i class="fa fa-calendar"></i>
                                                </div>
                                                <input id="surgery_date" name="surgery_date"
                                                    class="form-control pull-right datepicker" type="text"
                                                    data-inputmask="'alias': 'dd/mm/yyyy'" data-mask=""
                                                    value="<?php echo date('d/m/Y'); ?>" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <div class="ui-widget">
                                                <label for="tags">Remarks / Comments: </label>
                                                <input class="form-control input-sm" name="input_surgery_remark"
                                                    id="input_surgery_remark" placeholder="" type="text">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <div class="ui-widget">
                                                <label for="tags"> </label>
                                                <button type="button" id="btn_surgery"
                                                    class="btn btn-primary">+ADD</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Procedure  -->
                    <div class="row">
                        <div class="box box-success">
                            <div class="box-header with-border">
                                <div class="col-md-8">
                                    <h2 class="box-title">Procedure</h3>
                                </div>
                            </div>
                            <div class="box-body">
                                <div class="row" id="procedure_table">
                                    <div class="col-md-12">
                                        <table class="table table-condensed">
                                            <?php if (count($ipd_discharge_procedure) > 0) {
                                                echo '<tr>
														<th>Procedure Name</th>
														<th>Procedure Date</th>
														<th>Remarks</th>
														<th></th>
														<th></th>
													</tr>';
                                            } ?>
                                            <?php foreach ($ipd_discharge_procedure as $row) { ?>
                                                <tr>
                                                    <td><input class="form-control input-sm"
                                                            name="input_procedure_name_<?= $row->id ?>"
                                                            id="input_procedure_name_<?= $row->id ?>" type="text"
                                                            value="<?= $row->procedure_name ?>">
                                                    </td>
                                                    <td>
                                                        <div class="input-group date">
                                                            <div class="input-group-addon">
                                                                <i class="fa fa-calendar"></i>
                                                            </div>
                                                            <input id="procedure_date_<?=$row->id ?>"
                                                                name="procedure_date_<?=$row->id ?>"
                                                                class="form-control pull-right datepicker" type="text"
                                                                data-inputmask="'alias': 'dd/mm/yyyy'" data-mask=""
                                                                value="<?php echo ($row->procedure_date ? MysqlDate_to_str($row->procedure_date) : date('d/m/Y')); ?>" />
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input class="form-control input-sm"
                                                            name="input_procedure_remark_<?= $row->id ?>"
                                                            id="input_procedure_remark_<?= $row->id ?>" type="text"
                                                            value="<?= $row->procedure_remark ?>">
                                                    </td>
                                                    <td><a
                                                            href="javascript:procedureUpdate('<?= $row->id ?>','<?= $row->ipd_id ?>')">Update</a>
                                                    </td>
                                                    <td><a
                                                            href="javascript:procedureRemove('<?= $row->id ?>','<?= $row->ipd_id ?>')">Remove</a>
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
                                                <label for="tags">Procedure: </label>
                                                <input class="form-control input-sm" name="input_procedure_name"
                                                    id="input_procedure_name"
                                                    placeholder="Like Arthroscopy , Circumcision" type="text">
                                                <input type="hidden" id="input_procedure_id" name="input_procedure_id"
                                                    value="0">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Procedure Date</label>
                                            <div class="input-group date">
                                                <div class="input-group-addon">
                                                    <i class="fa fa-calendar"></i>
                                                </div>
                                                <input id="procedure_date" name="procedure_date"
                                                    class="form-control pull-right datepicker" type="text"
                                                    data-inputmask="'alias': 'dd/mm/yyyy'" data-mask=""
                                                    value="<?php echo date('d/m/Y'); ?>" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <div class="ui-widget">
                                                <label for="tags">Remarks / Comments: </label>
                                                <input class="form-control input-sm" name="input_procedure_remark"
                                                    id="input_procedure_remark" placeholder="" type="text">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <div class="ui-widget">
                                                <label for="tags"> </label>
                                                <button type="button" id="btn_Procedure"
                                                    class="btn btn-primary">+ADD</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Delivery --->
                    <?php if ($ipd_master[0]->xgender == "Female") { ?>
                        <div class="row">
                            <div class="box box-warning">
                                <div class="box-header with-border">
                                    <div class="col-md-8">
                                        <h2 class="box-title">If Delivered, date and time of delivery</h3>
                                    </div>
                                </div>
                                <div class="box-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Is Delivery ?</label>
                                                <div class="radio">
                                                    <label>
                                                        <input name="optionsRadios_isdelivery" id="options_isdelivery1"
                                                            value="1" <?= radio_checked("1", $ipd_master[0]->isdelivery) ?>
                                                            type="radio">
                                                        Yes
                                                    </label>
                                                    <label>
                                                        <input name="optionsRadios_isdelivery" id="options_isdelivery2"
                                                            value="0" <?= radio_checked("0", $ipd_master[0]->isdelivery) ?>
                                                            type="radio">
                                                        No
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Delivery Date</label>
                                                <div class="input-group date">
                                                    <div class="input-group-addon">
                                                        <i class="fa fa-calendar"></i>
                                                    </div>
                                                    <input id="delivery_date" name="delivery_date"
                                                        class="form-control pull-right datepicker" type="text"
                                                        data-inputmask="'alias': 'dd/mm/yyyy'" data-mask=""
                                                        value="<?php echo ($ipd_master[0]->delivery_date ? MysqlDate_to_str($ipd_master[0]->delivery_date) : date('d/m/Y')); ?>" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Delivery Time:</label>
                                                <div class="input-group">
                                                    <input id="delivery_time" name="delivery_time"
                                                        class="form-control timepicker" type="text"
                                                        value="<?= $ipd_master[0]->delivery_time ?>">
                                                    <div class="input-group-addon">
                                                        <i class="fa fa-clock-o"></i>
                                                    </div>
                                                </div>
                                                <!-- /.input group -->
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Sex of baby 1:</label>
                                                <select id="delivery_sex_of_baby" name="delivery_sex_of_baby"
                                                    class="form-control">
                                                    <option value=""
                                                        <?= combo_checked("", $ipd_master[0]->delivery_sex_of_baby) ?>>None
                                                    </option>
                                                    <option value="Girl"
                                                        <?= combo_checked("Girl", $ipd_master[0]->delivery_sex_of_baby) ?>>Girl
                                                    </option>
                                                    <option value="Boy"
                                                        <?= combo_checked("Boy", $ipd_master[0]->delivery_sex_of_baby) ?>>Boy
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Sex of baby 2:</label>
                                                <select id="delivery_sex_of_baby2" name="delivery_sex_of_baby2"
                                                    class="form-control">
                                                    <option value=""
                                                        <?= combo_checked("", $ipd_master[0]->delivery_sex_of_baby2) ?>>None
                                                    </option>
                                                    <option value="Girl"
                                                        <?= combo_checked("Girl", $ipd_master[0]->delivery_sex_of_baby2) ?>>
                                                        Girl</option>
                                                    <option value="Boy"
                                                        <?= combo_checked("Boy", $ipd_master[0]->delivery_sex_of_baby2) ?>>Boy
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Sex of baby 3:</label>
                                                <select id="delivery_sex_of_baby3" name="delivery_sex_of_baby3"
                                                    class="form-control">
                                                    <option value=""
                                                        <?= combo_checked("", $ipd_master[0]->delivery_sex_of_baby) ?>>None
                                                    </option>
                                                    <option value="Girl"
                                                        <?= combo_checked("Girl", $ipd_master[0]->delivery_sex_of_baby3) ?>>
                                                        Girl</option>
                                                    <option value="Boy"
                                                        <?= combo_checked("Boy", $ipd_master[0]->delivery_sex_of_baby3) ?>>Boy
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Save Data</label>
                                                <button type="button" id="btn_update_delivery" name="btn_update_delivery"
                                                    class="btn btn-primary form-control">Update Delivery Record</button>
                                            </div>
                                            <div class="jsError"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php  } ?>
                    <!---Delivery End  --->

                    <!-- Prodedure End  --->
                </div>
                <div id="home" class="tab-pane fade in ">
                    <div class="row">
                        <div class="box box-primary">
                            <div class="box-header with-border">
                                <h2 class="box-title">Examination on Admission</h3>
                            </div>
                            <div class="box-body">
                                <h4>General Examination</h4>
                                <?php echo form_open('Ipd_discharge/gen_exam', array('role' => 'form', 'class' => 'form2')); ?>
                                <input type="hidden" value="<?= $ipd_master[0]->id ?>" id="gen_exam_ipd_id"
                                    name="gen_exam_ipd_id" />
                                <div class="row">
                                    <?php foreach ($ipd_discharge_general_exam_col_1 as $row) { ?>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <div class="ui-widget">
                                                    <label for="tags"><?= $row->col_description ?>: </label>
                                                    <input class="form-control input-sm" name="input_g_exam_<?= $row->id ?>"
                                                        id="input_g_exam_<?= $row->id ?>" type="text"
                                                        value="<?= $row->rdata_text ?>" autocomplete="on">
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                                <hr />
                                <?php echo form_open('Ipd_discharge/gen_exam', array('role' => 'form', 'class' => 'form2')); ?>
                                <input type="hidden" value="<?= $ipd_master[0]->id ?>" id="gen_exam_ipd_id"
                                    name="gen_exam_ipd_id" />
                                <div class="row">
                                    <?php foreach ($ipd_discharge_general_exam_col_2 as $row) { ?>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <div class="ui-widget">
                                                    <label for="tags"><?= $row->col_description ?>: </label>
                                                    <input class="form-control input-sm" name="input_g_exam_<?= $row->id ?>"
                                                        id="input_g_exam_<?= $row->id ?>" type="text"
                                                        value="<?= $row->rdata_text ?>" autocomplete="on">
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                                <?php echo form_close(); ?>
                            </div>
                            <div class="box-footer pad">
                                <button type="button" class="btn btn-primary" id="btn_g_exam_save"
                                    onclick="save_gen_Exam()">Save General Examination</button>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <?php foreach ($ipd_discharge_sys_exam as $row) { ?>
                            <div class="box box-info collapsed-box">
                                <div class="box-header">
                                    <h3 class="box-title"><?= $row->sys_exam_name ?>
                                        <small></small>
                                    </h3>
                                    <div class="pull-right box-tools">
                                        <button type="button" class="btn btn-warning btn-sm"
                                            onclick="save_Sys_Exam('<?= $ipd_master[0]->id ?>','<?= $row->id ?>')"
                                            data-toggle="tooltip" title="Save Data">
                                            <i class="fa fa-save"></i></button>
                                        <button type="button" class="btn btn-info btn-sm" data-widget="collapse"
                                            data-toggle="tooltip" title="Collapse">
                                            <i class="fa fa-plus"></i></button>
                                    </div>
                                </div>
                                <div class="box-body pad">
                                    <textarea id="editor_Sys_Exam_<?= $row->id ?>" name="editor_Sys_Exam_<?= $row->id ?>"
                                        class="editor" rows="10" cols="100"><?= $row->rdata ?></textarea>
                                    <script>
                                        $(function() {
                                            CKEDITOR.replace('editor_Sys_Exam_<?= $row->id ?>')
                                        })
                                    </script>
                                </div>
                                <div class="box-footer pad">
                                    <button type="button" class="btn btn-primary" id="btn_g_exam_save"
                                        onclick="save_Sys_Exam('<?= $ipd_master[0]->id ?>','<?= $row->id ?>')">Save Other Examination</button>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div id="menu1" class="tab-pane fade">
                    <div class="row">
                        <div class="box box-info ">
                            <div class="box-header">
                                <h3 class="box-title">Clinical Investigation (In-Hospital Lab)
                                    <small>Blood Hb,Blood Sugar,Renal Function,Serum Bilirubin,Urine
                                        Test</small>
                                </h3>
                                <!-- tools box -->
                                <div class="pull-right box-tools">
                                    <button type="button" class="btn btn-info btn-sm"
                                        data-widget="collapse" data-toggle="tooltip" title="Collapse">
                                        <i class="fa fa-minus"></i></button>
                                </div>
                                <!-- /. tools -->
                            </div>
                            <!-- /.box-header -->
                            <div class="box-body pad">
                                <?php
                                $i = 0;
                                foreach ($lab_request as $row) {
                                ?>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="chk" name="invprofiles_<?= $i ?>"
                                                value="<?= $row->sDate ?>" <?= $row->chked ?>>
                                            [<?= $row->sInd_Date ?>] <?= $row->test_list ?>
                                        </label>
                                    </div>
                                <?php } ?>
                            </div>
                            <div class="box-footer">
                                <button type="button" class="btn btn-primary" id="btn_g_exam_save" onclick="save_CLINICAL()">Save</button>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="box box-info  ">
                            <div class="box-header">
                                <h3 class="box-title">Investigations Done (Manual Entry)</h3>
                                <div class="pull-right box-tools">
                                    <button type="button" class="btn btn-info btn-sm"
                                        data-widget="collapse" data-toggle="tooltip" title="Collapse">
                                        <i class="fa fa-minus"></i></button>
                                </div>
                            </div>
                            <div class="box-body">
                                <?php echo form_open('Ipd_discharge/gen_exam', array('role' => 'form', 'class' => 'form10')); ?>
                                <input type="hidden" value="<?= $ipd_master[0]->id ?>" id="gen_exam_ipd_id"
                                    name="gen_exam_ipd_id" />
                                <div class="row">
                                    <?php foreach ($ipd_discharge_investigation_during_admit as $row) { ?>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <div class="ui-widget">
                                                    <label for="tags"><?= $row->col_description ?>: </label>
                                                    <input class="form-control input-sm" name="input_m_exam_<?= $row->id ?>"
                                                        id="input_m_exam_<?= $row->id ?>" type="text"
                                                        value="<?= $row->rdata_text ?>" autocomplete="on">
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                                <div class="row">
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-primary" id="btn_g_exam_save"
                                            onclick="save_invest_m_Exam()">Save</button>
                                    </div>
                                </div>
                                <?php echo form_close(); ?>
                                <div class="row">
                                    <hr />
                                </div>
                                <?php echo form_open('Ipd_discharge/gen_exam', array('role' => 'form', 'class' => 'form11')); ?>
                                <input type="hidden" value="<?= $ipd_master[0]->id ?>" id="gen_exam_ipd_id"
                                    name="gen_exam_ipd_id" />
                                <div class="row">
                                    <?php foreach ($ipd_discharge_special_investigation as $row) { ?>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <div class="ui-widget">
                                                    <label for="tags"><?= $row->col_name ?>: </label>
                                                    <input class="form-control input-sm" name="input_s_exam_<?= $row->id ?>"
                                                        id="input_s_exam_<?= $row->id ?>" type="text"
                                                        value="<?= $row->rdata_text ?>" autocomplete="on">
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                                <div class="row">
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-primary" id="btn_g_exam_save"
                                            onclick="save_invest_s_Exam()">Save</button>
                                    </div>
                                </div>
                                <?php echo form_close(); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="box box-info collapsed-box">
                            <div class="box-header">
                                <h3 class="box-title">Other Examinations or Provisional Diagnosis
                                    <small>X-Ray,ECG,CTScan,MRI</small>
                                </h3>
                                <div class="pull-right box-tools">
                                    <button type="button" class="btn btn-info btn-sm" data-widget="collapse"
                                        data-toggle="tooltip" title="Collapse">
                                        <i class="fa fa-plus"></i></button>
                                </div>
                            </div>
                            <div class="box-body pad">
                                <?php
                                $content = "";
                                if (count($ipd_discharge_2) > 0) {
                                    $content = $ipd_discharge_2[0]->rdata;
                                }
                                ?>
                                <textarea id="editor_Other_examinations" name="editor_Other_examinations" class="editor"
                                    rows="10" cols="80"><?= $content ?></textarea>
                                <script>
                                    $(function() {
                                        CKEDITOR.replace('editor_Other_examinations')
                                    })
                                </script>
                                <br />
                                <button type="button" class="btn btn-primary" onclick="save_CLINICAL()">Save</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="menu2" class="tab-pane fade in active">
                    <div class="row">
                        <?php echo form_open('Ipd_discharge/gen_exam', array('role' => 'form', 'class' => 'form3')); ?>
                        <div class="box box-info ">
                            <div class="box-header">
                                <h3 class="box-title">Personal History
                                    <small></small>
                                </h3>
                                <div class="pull-right box-tools">
                                    <button type="button" class="btn btn-warning btn-sm"
                                        onclick="save_personal_exam('<?= $Pdata[0]->id ?>')"
                                        data-toggle="tooltip" title="Save Data">
                                        <i class="fa fa-save"></i></button>
                                    <button type="button" class="btn btn-info btn-sm" data-widget="collapse"
                                        data-toggle="tooltip" title="Collapse">
                                        <i class="fa fa-minus"></i></button>
                                </div>
                            </div>
                            <div class="box-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="callout ">
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" class='chk_opd' id="opd_prescription_smoking_chk" value="<?= $Pdata[0]->is_smoking ?>" <?php echo ($Pdata[0]->is_smoking == 1) ? 'Checked' : ''; ?>>
                                                    Smoking
                                                </label>
                                            </div>
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" class='chk_opd' id="opd_prescription_alcohol_chk" value="<?= $Pdata[0]->is_alcohol ?>" <?php echo ($Pdata[0]->is_alcohol == 1) ? 'Checked' : ''; ?>>
                                                    Alcohol
                                                </label>
                                            </div>
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" class='chk_opd' id="opd_prescription_tobacoo_chk" value="<?= $Pdata[0]->is_tobacoo ?>" <?php echo ($Pdata[0]->is_tobacoo == 1) ? 'Checked' : ''; ?>>
                                                    Tobacoo
                                                </label>
                                            </div>
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" class='chk_opd' id="opd_prescription_drug_chk" value="<?= $Pdata[0]->is_drug_abuse ?>" <?php echo ($Pdata[0]->is_drug_abuse == 1) ? 'Checked' : ''; ?>>
                                                    Drug abuse
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="callout">
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" class='chk_opd' id="opd_prescription_hypertesion_chk" value="<?= $Pdata[0]->is_hypertesion ?>" <?php echo ($Pdata[0]->is_hypertesion == 1) ? 'Checked' : ''; ?>>
                                                    Hypertension
                                                </label>
                                            </div>
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" class='chk_opd' id="opd_prescription_niddm_chk" value="<?= $Pdata[0]->is_niddm ?>" <?php echo ($Pdata[0]->is_niddm == 1) ? 'Checked' : ''; ?>>
                                                    Type 2 diabetes mellitus (DM)
                                                </label>
                                            </div>
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" class='chk_opd' id="opd_prescription_hbsag_chk" value="<?= $Pdata[0]->is_hbsag ?>" <?php echo ($Pdata[0]->is_hbsag == 1) ? 'Checked' : ''; ?>>
                                                    HBsAG
                                                </label>
                                            </div>
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" class='chk_opd' id="opd_prescription_hcv_chk" value="<?= $Pdata[0]->is_hcv ?>" <?php echo ($Pdata[0]->is_hcv == 1) ? 'Checked' : ''; ?>>
                                                    HCV
                                                </label>
                                            </div>
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" class='chk_opd' id="opd_prescription_hiv_I_II_chk" value="<?= $Pdata[0]->is_hiv_I_II ?>" <?php echo ($Pdata[0]->is_hiv_I_II == 1) ? 'Checked' : ''; ?>>
                                                    HIV I & II
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="box-footer">
                                <button type="button" class="btn btn-primary" onclick="save_personal_exam('<?= $Pdata[0]->id ?>')">Save Personal History</button>
                            </div>
                        </div>
                        <?php echo form_close(); ?>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="box box-primary">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">Complaints with Duration and Reason for Admission
                                            <small></small>
                                        </h3>
                                    </div>
                                    <div class="box-body">
                                        <div class="row" id="complaints_table">
                                            <div class="col-md-12">
                                                <table class="table table-condensed">
                                                    <?php if (count($ipd_discharge_complaint) > 0) {
                                                        echo '<tr><th>Complaint Name</th><td>Remarks</th><th></th><th></th></tr>';
                                                    } ?>
                                                    <?php foreach ($ipd_discharge_complaint as $row) { ?>
                                                        <tr>
                                                            <td>
                                                                <input class="form-control input-sm"
                                                                    name="input_complaints_<?= $row->id ?>"
                                                                    id="input_complaints_<?= $row->id ?>" type="text"
                                                                    value="<?= $row->comp_report ?>">
                                                            </td>
                                                            <td>
                                                                <input class="form-control input-sm"
                                                                    name="input_complaint_remarks_<?= $row->id ?>"
                                                                    id="input_complaint_remarks_<?= $row->id ?>" type="text"
                                                                    value="<?= $row->comp_remark ?>">
                                                            </td>
                                                            <td><a
                                                                    href="javascript:complaintUpdate('<?= $row->id ?>','<?= $row->ipd_id ?>')">Update</a>
                                                            </td>
                                                            <td><a
                                                                    href="javascript:complaintRemove('<?= $row->id ?>','<?= $row->ipd_id ?>')">Remove</a>
                                                            </td>
                                                        </tr>
                                                    <?php } ?>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <div class="ui-widget">
                                                        <label for="tags">Complaints/ Reported Problems: </label>
                                                        <input class="form-control input-sm" name="input_complaints"
                                                            id="input_complaints" placeholder="Like FEVER , COUGH"
                                                            type="text" autocomplete="on">
                                                        <input type="hidden" id="input_complaints_value"
                                                            name="input_complaints_value" value="0">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="form-group">
                                                    <div class="ui-widget">
                                                        <label for="tags">Remarks / Comments /Duration: </label>
                                                        <input class="form-control input-sm"
                                                            name="input_complaints_remarks"
                                                            id="input_complaints_remarks" placeholder="" type="text">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-1">
                                                <div class="form-group">
                                                    <div class="ui-widget">
                                                        <label for="tags"> </label>
                                                        <button type="button" id="btn_complaints"
                                                            class="btn btn-primary">+ADD</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="box box-info ">
                            <div class="box-header">
                                <h3 class="box-title">Other Complaints
                                    <small></small>
                                </h3>
                                <div class="pull-right box-tools">
                                    <button type="button" class="btn btn-warning btn-sm"
                                        onclick="save_Complaints()"
                                        data-toggle="tooltip" title="Save Other Complaints">
                                        <i class="fa fa-save"></i></button>
                                    <button type="button" class="btn btn-info btn-sm" data-widget="collapse"
                                        data-toggle="tooltip" title="Collapse">
                                        <i class="fa fa-minus"></i></button>
                                </div>
                            </div>
                            <div class="box-body pad">
                                <?php
                                $content = "";
                                if (count($ipd_discharge_complaint_remark) > 0) {
                                    $content = $ipd_discharge_complaint_remark[0]->comp_remark;
                                }
                                ?>
                                <textarea id="editor_Other_Complaints" name="editor_Other_Complaints" class="editor"
                                    rows="10" cols="80"><?= $content ?></textarea>
                                <script>
                                    $(function() {
                                        CKEDITOR.replace('editor_Other_Complaints')
                                    })
                                </script>
                            </div>
                            <div class="box-footer pad">
                                <button type="button" class="btn btn-primary" onclick="save_Complaints()">Save Other Complaints</button>
                            </div>
                        </div>
                    </div>

                </div>
                <div id="menu3" class="tab-pane fade">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="box box-primary">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">Final Diagnosis at the time of Discharge</h3>
                                    </div>
                                    <div class="box-body">
                                        <div class="row" id="diagnosis_table">
                                            <div class="col-md-12">
                                                <table class="table table-condensed">
                                                    <?php if (count($ipd_discharge_diagnosis) > 0) {
                                                        echo '<tr><th>Diagnosis </th><td>Remarks</th><th></th><th></th></tr>';
                                                    } ?>
                                                    <?php foreach ($ipd_discharge_diagnosis as $row) { ?>
                                                        <tr>
                                                            <td>
                                                                <input class="form-control input-sm"
                                                                    name="input_diagnosis_<?= $row->id ?>"
                                                                    id="input_diagnosis_<?= $row->id ?>" type="text"
                                                                    value="<?= $row->comp_report ?>">
                                                            </td>
                                                            <td>
                                                                <input class="form-control input-sm"
                                                                    name="input_diagnosis_remarks_<?= $row->id ?>"
                                                                    id="input_diagnosis_remarks_<?= $row->id ?>" type="text"
                                                                    value="<?= $row->comp_remark ?>">
                                                            </td>
                                                            <td><a
                                                                    href="javascript:diagnosisUpdate('<?= $row->id ?>','<?= $row->ipd_id ?>')">Update</a>
                                                            </td>
                                                            <td><a
                                                                    href="javascript:diagnosisRemove('<?= $row->id ?>','<?= $row->ipd_id ?>')">Remove</a>
                                                            </td>
                                                        </tr>
                                                    <?php } ?>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <div class="ui-widget">
                                                        <label for="tags">Complaints/ Reported Problems: </label>
                                                        <input class="form-control input-sm" name="input_diagnosis"
                                                            id="input_diagnosis" placeholder="Like FEVER , COUGH"
                                                            type="text">
                                                        <input type="hidden" id="input_diagnosis_value"
                                                            name="input_diagnosis_value" value="0">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="form-group">
                                                    <div class="ui-widget">
                                                        <label for="tags">Remarks / Comments: </label>
                                                        <input class="form-control input-sm"
                                                            name="input_diagnosis_remarks" id="input_diagnosis_remarks"
                                                            placeholder="" type="text">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-1">
                                                <div class="form-group">
                                                    <div class="ui-widget">
                                                        <label for="tags"> </label>
                                                        <button type="button" id="btn_diagnosis"
                                                            class="btn btn-primary">+ADD</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="box box-info collapsed-box">
                            <div class="box-header">
                                <h3 class="box-title">Other Diagnosis
                                    <small></small>
                                </h3>
                                <div class="pull-right box-tools">
                                    <button type="button" class="btn btn-info btn-sm" data-widget="collapse"
                                        data-toggle="tooltip" title="Collapse">
                                        <i class="fa fa-plus"></i></button>
                                </div>
                            </div>
                            <div class="box-body pad">
                                <?php
                                $content = "";
                                if (count($ipd_discharge_diagnosis_remark) > 0) {
                                    $content = $ipd_discharge_diagnosis_remark[0]->comp_remark;
                                }
                                ?>
                                <textarea id="editor_Other_Diagnosis" name="editor_Other_Diagnosis" class="editor"
                                    rows="10" cols="80"><?= $content ?></textarea>
                            </div>
                            <div class="box-footer">
                                <button type="button" class="btn btn-primary" onclick="save_Diagnosis()">Save Diagnosis</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="menu4" class="tab-pane fade">
                    <div class="row">
                        <div class="box box-info collapsed-box">
                            <div class="box-header">
                                <h3 class="box-title">Summary of key investigation during Hospitalization
                                    <small></small>
                                </h3>
                                <div class="pull-right box-tools">
                                    <button type="button" class="btn btn-info btn-sm" data-widget="collapse"
                                        data-toggle="tooltip" title="Collapse">
                                        <i class="fa fa-plus"></i></button>
                                </div>
                            </div>
                            <div class="box-body pad">
                                <?php
                                $content = "";
                                if (count($ipd_discharge_investigtions_inhos) > 0) {
                                    $content = $ipd_discharge_investigtions_inhos[0]->comp_remark;
                                }
                                ?>
                                <textarea id="editor_investigtions_Hospitalization"
                                    name="editor_investigtions_Hospitalization" class="editor" rows="10"
                                    cols="80"><?= $content ?></textarea>
                                <script>
                                    $(function() {
                                        CKEDITOR.replace('editor_investigtions_Hospitalization')
                                    })
                                </script>
                            </div>
                            <div class="box-footer">
                                <button type="button" class="btn btn-primary"
                                    onclick="save_Hospial_investigtions()">Save Investigation</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="menu5" class="tab-pane fade">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="box box-primary">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">Course in the hospital</h3>
                                    </div>
                                    <div class="box-body">
                                        <div class="row" id="course_table">
                                            <div class="col-md-12">
                                                <table class="table table-condensed">
                                                    <?php if (count($ipd_discharge_course) > 0) {
                                                        echo '<tr><th>Course </th><td>Remarks</th><th></th><th></th></tr>';
                                                    } ?>
                                                    <?php foreach ($ipd_discharge_course as $row) { ?>
                                                        <tr>
                                                            <td>
                                                                <input class="form-control input-sm"
                                                                    name="input_course_<?= $row->id ?>"
                                                                    id="input_course_<?= $row->id ?>" type="text"
                                                                    value="<?= $row->comp_report ?>">
                                                            </td>
                                                            <td>
                                                                <input class="form-control input-sm"
                                                                    name="input_course_remarks_<?= $row->id ?>"
                                                                    id="input_course_remarks_<?= $row->id ?>" type="text"
                                                                    value="<?= $row->comp_remark ?>">
                                                            </td>
                                                            <td><a
                                                                    href="javascript:courseUpdate('<?= $row->id ?>','<?= $row->ipd_id ?>')">Update</a>
                                                            </td>
                                                            <td><a
                                                                    href="javascript:courseRemove('<?= $row->id ?>','<?= $row->ipd_id ?>')">Remove</a>
                                                            </td>
                                                        </tr>
                                                    <?php } ?>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <div class="ui-widget">
                                                        <label for="tags">Course: </label>
                                                        <input class="form-control input-sm" name="input_course"
                                                            id="input_course" placeholder="Like FEVER , COUGH"
                                                            type="text">
                                                        <input type="hidden" id="input_course_value"
                                                            name="input_course_value" value="0">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="form-group">
                                                    <div class="ui-widget">
                                                        <label for="tags">Remarks / Comments: </label>
                                                        <input class="form-control input-sm" name="input_course_remarks"
                                                            id="input_course_remarks" placeholder="" type="text">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-1">
                                                <div class="form-group">
                                                    <div class="ui-widget">
                                                        <label for="tags"> </label>
                                                        <button type="button" id="btn_course"
                                                            class="btn btn-primary">+ADD</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="box box-info collapsed-box">
                            <div class="box-header">
                                <h3 class="box-title">Any Remarks
                                    <small></small>
                                </h3>
                                <div class="pull-right box-tools">
                                    <button type="button" class="btn btn-info btn-sm" data-widget="collapse"
                                        data-toggle="tooltip" title="Collapse">
                                        <i class="fa fa-plus"></i></button>
                                </div>
                            </div>
                            <div class="box-body pad">
                                <?php
                                $content = "";
                                if (count($ipd_discharge_course_remark) > 0) {
                                    $content = $ipd_discharge_course_remark[0]->comp_remark;
                                    if (strlen($content) < 1) {
                                        $content = "Conservative with antibiotics,Anti-epileptic,analgesic,antipyretics,PPI,IV Fluids & another supportive treatments.";
                                    }
                                }
                                ?>
                                <textarea id="editor_Course_hospital" name="editor_Course_hospital" class="editor"
                                    rows="10" cols="80"><?= $content ?></textarea>
                                <script>
                                    $(function() {
                                        CKEDITOR.replace('editor_Course_hospital')
                                    })
                                </script>
                            </div>
                            <div class="box-footer">
                                <button type="button" class="btn btn-primary" onclick="save_course()">Save Treatment</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="menu6" class="tab-pane fade">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title">Discharge Medications</h3>
                        </div>
                        <div class="box-body">
                            <div class="row" id="drug_table">
                                <?= $ipd_discharge_prescrption_prescribed_content ?>
                            </div>

                        </div>
                        <div class="box-footer">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <div class="ui-widget">
                                            <label for="tags">Prescribed: </label>
                                            <input class="form-control input-sm" name="input_med_name" id="input_med_name" type="text">
                                            <input name="hid_ipd_med_item_id" id="hid_ipd_med_item_id" type="hidden" value="0">
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
                                                foreach ($opd_dose_shed as $row) {
                                                    echo '<option value="' . $row->dose_shed_id . '"  >' . $row->dose_show_sign . '</option>';
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
                                                foreach ($opd_dose_when as $row) {
                                                    echo '<option value="' . $row->dose_when_id . '"  >' . $row->dose_sign . '</option>';
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
                                                foreach ($opd_dose_frequency as $row) {
                                                    echo '<option value="' . $row->dose_freq_id . '"  >' . $row->dose_sign . '</option>';
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
                                                type="text">
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
                                                foreach ($opd_dose_where as $row) {
                                                    echo '<option value="' . $row->dose_where_id . '"  >' . $row->dose_sign . '</option>';
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
                </div>
                <div id="menu7" class="tab-pane fade">
                    <div class="row">
                        <div class="box box-info collapsed-box">
                            <div class="box-header">
                                <h3 class="box-title">Dietary
                                    <small></small>
                                </h3>
                                <div class="pull-right box-tools">
                                    <button type="button" class="btn btn-info btn-sm" data-widget="collapse"
                                        data-toggle="tooltip" title="Collapse">
                                        <i class="fa fa-plus"></i></button>
                                </div>
                            </div>
                            <div class="box-body pad">
                                <?php
                                $i = 0;
                                foreach ($dis_food_interaction as $row) {
                                ?>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="chk_food" name="food_interaction_<?= $i ?>"
                                                value="<?= $row->id ?>" <?= $row->food_exist ?>>
                                            <?= $row->food_short ?>
                                        </label>
                                    </div>
                                <?php } ?>
                                <hr />
                                <h3>Other</h3>
                                <?php
                                $content = "";
                                if (count($ipd_discharge_drug_food_interaction) > 0) {
                                    $content = $ipd_discharge_drug_food_interaction[0]->food_text;
                                    if (strlen($content) < 1) {
                                        $content = "";
                                    }
                                }
                                ?>
                                <textarea id="editor_FOOD_DRUG_INTERACTION_list"
                                    name="editor_FOOD_DRUG_INTERACTION_list" class="editor" rows="10"
                                    cols="80"><?= $content ?></textarea>
                                <br />
                                <button type="button" class="btn btn-primary"
                                    onclick="save_FOOD_DRUG_INTERACTION()">Save</button>
                            </div>
                        </div>
                    </div>
                    <?php
                    $content = "";
                    $footer_content = "";
                    if (count($ipd_discharge_instructions) > 0) {
                        $content = $ipd_discharge_instructions[0]->comp_remark;
                       
                        $footer_content .= $ipd_discharge_instructions[0]->footer_text;
                    }
                    if (strlen($footer_content) < 1) {
                        $footer_content .= '<table border="0" cellpadding="1" cellspacing="1" style="width:100%">
                                    <tbody>
                                        <tr>
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <td style="text-align:left; vertical-align:middle">_________________________</td>
                                            <td>_________________________</td>
                                            <td style="text-align:right; vertical-align:middle">_________________________</td>
                                        </tr>
                                        <tr>
                                            <td style="text-align:center; vertical-align:middle">Signature of Consultant</td>
                                            <td style="text-align:center; vertical-align:middle">Signature of Medical Officer</td>
                                            <td style="text-align:center; vertical-align:middle">Signature of Receiver / Date</td>
                                        </tr>
                                    </tbody>
                                </table>';
                    }

                    ?>
                    <div class="row">
                        <div class="box box-info collapsed-box">
                            <div class="box-header">
                                <h3 class="box-title">Discharge Advice/Instructions/Summary
                                    <small></small>
                                </h3>
                                <div class="pull-right box-tools">
                                    <button type="button" class="btn btn-info btn-sm" data-widget="collapse"
                                        data-toggle="tooltip" title="Collapse">
                                        <i class="fa fa-plus"></i></button>
                                </div>
                            </div>
                            <div class="box-body pad">
                                <textarea id="editor_Discharge_Instructions" name="editor_Discharge_Instructions"
                                    class="editor" rows="10" cols="80"><?= $content ?></textarea>
                                <script>
                                    $(function() {
                                        CKEDITOR.replace('editor_Discharge_Instructions')
                                    })
                                </script>
                                <br />
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="ui-widget">
                                            <label for="tags">Review after :</label>
                                            <input class="form-control input-sm" name="input_next_visit" id="input_next_visit" type="text"
                                                value="<?= isset($ipd_discharge_instructions[0]->review_after)?$ipd_discharge_instructions[0]->review_after:''; ?>" >
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
                            </div>
                            <div class="box-footer">
                                <button type="button" class="btn btn-primary"
                                    onclick="save_Instructions()">Save</button>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="box box-info collapsed-box">
                            <div class="box-header">
                                <h3 class="box-title">Footer TEXT
                                    <small></small>
                                </h3>
                                <div class="pull-right box-tools">
                                    <button type="button" class="btn btn-info btn-sm" data-widget="collapse"
                                        data-toggle="tooltip" title="Collapse">
                                        <i class="fa fa-plus"></i></button>
                                </div>
                            </div>
                            <div class="box-body pad">
                                <textarea id="editor_Discharge_Footer" name="editor_Discharge_Footer" class="editor"
                                    rows="10" cols="80"><?= $footer_content ?></textarea>
                                <script>
                                    $(function() {
                                        CKEDITOR.replace('editor_Discharge_Footer')
                                    })
                                </script>
                                <br />
                                <button type="button" class="btn btn-primary"
                                    onclick="save_Discharge_Footer()">Save</button>

                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="box box-info collapsed-box">
                            <div class="box-header">
                                <h3 class="box-title">FOOTER BANNER
                                    <small></small>
                                </h3>
                                <div class="pull-right box-tools">
                                    <button type="button" class="btn btn-info btn-sm" data-widget="collapse"
                                        data-toggle="tooltip" title="Collapse">
                                        <i class="fa fa-plus"></i></button>
                                </div>
                            </div>
                            <div class="box-body pad">
                                <?php
                                $i = 0;
                                foreach ($dis_banner_list as $row) {
                                ?>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="chk_banner" name="footer_banner_<?= $i ?>"
                                                value="<?= $row->id ?>" <?= $row->banner_exist ?>>
                                            <?= $row->banner_name ?>
                                        </label>
                                    </div>
                                <?php } ?>
                                <button type="button" class="btn btn-primary"
                                    onclick="save_footer_banner()">Save</button>
                            </div>
                        </div>
                    </div>

                </div>

                <div id="menu8" class="tab-pane fade">
                    <div class="row">
                        <div class="box box-primary">
                            <div class="box-header with-border">
                                <h3 class="box-title">Examination at the time of Discharge</h3>
                            </div>
                            <div class="box-body">
                                <?php echo form_open('Ipd_discharge/gen_exam', array('role' => 'form', 'class' => 'form_dis')); ?>
                                <input type="hidden" value="<?= $ipd_master[0]->id ?>" id="dis_exam_ipd_id"
                                    name="dis_exam_ipd_id" />
                                <div class="row">
                                    <?php foreach ($ipd_discharge_exam_on_discharge_col as $row) { ?>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <div class="ui-widget">
                                                    <label for="tags"><?= $row->col_description ?>: </label>
                                                    <input class="form-control input-sm" name="input_d_exam_<?= $row->id ?>"
                                                        id="input_d_exam_<?= $row->id ?>" type="text"
                                                        value="<?= $row->rdata_text ?>" autocomplete="on">
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                                <div class="row">
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-primary" id="btn_d_exam_save"
                                            onclick="save_discharge_Exam()">Save</button>
                                    </div>
                                </div>
                                <?php echo form_close(); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <button type="button" class="btn btn-primary" id="btn_create">Create IPD Discharge</button>
            <button type="button" class="btn btn-danger" id="btn_edit">Edit</button>
        </div>
    </div>
    <!-- ./row -->
</section>
<!-- /.content -->
<script>
    //PHYSICAL EXAMINATIONS

    function save_Sys_Exam(IPD_id, sys_exam_id) {
        var editor_Sys_Exam = CKEDITOR.instances['editor_Sys_Exam_' + sys_exam_id].getData();
        //var editor_Sys_Exam=$('#editor_Sys_Exam_'+sys_exam_id).val();
        $.post('/Ipd_discharge/update_Sys_Exam', {
                "editor_Sys_Exam": editor_Sys_Exam,
                "IPD_id": IPD_id,
                "sys_exam_id": sys_exam_id,
                '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
            },
            function(data) {
                notify('success', 'Please Attention', data);
            });
    }

    function save_gen_Exam() {
        $.post('/Ipd_discharge/update_gen_Exam',
            $('form.form2').serialize(),
            function(data) {
                notify('success', 'Please Attention', data);
            });
    }

    function save_invest_m_Exam() {
        $.post('/Ipd_discharge/update_investigation_m_Exam',
            $('form.form10').serialize(),
            function(data) {
                notify('success', 'Please Attention', data);
            });
    }

    function save_invest_s_Exam() {
        $.post('/Ipd_discharge/update_investigation_s_Exam',
            $('form.form11').serialize(),
            function(data) {
                notify('success', 'Please Attention', data);
            });
    }

    function save_discharge_Exam() {
        $.post('/Ipd_discharge/update_dis_Exam',
            $('form.form_dis').serialize(),
            function(data) {
                notify('success', 'Please Attention', data);
            });
    }
    //
    //CLINICAL INVESTIGATION REPORTS

    function save_CLINICAL() {
        var editor_data = CKEDITOR.instances['editor_Other_examinations'].getData();

        var chkArray = [];
        $(".chk:checked").each(function() {
            chkArray.push($(this).val());
        });
        var selected;
        selected = chkArray.join(':');

        $.post('/Ipd_discharge/update_Provisional_Exam', {
                "editor_data": editor_data,
                "ipd_id": $("#ipd_id").val(),
                "lab_investigation_list": selected,
                '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
            },
            function(data) {
                notify('success', 'Please Attention', data);
            });
    }

    //Personal Exam 

    function save_personal_exam(p_id) {

        var opd_prescription_smoking_chk = $('#opd_prescription_smoking_chk').is(":checked") ? 1 : 0;
        var opd_prescription_alcohol_chk = $('#opd_prescription_alcohol_chk').is(":checked") ? 1 : 0;
        var opd_prescription_tobacoo_chk = $('#opd_prescription_tobacoo_chk').is(":checked") ? 1 : 0;
        var opd_prescription_drug_chk = $('#opd_prescription_drug_chk').is(":checked") ? 1 : 0;

        var opd_prescription_hypertesion_chk = $('#opd_prescription_hypertesion_chk').is(":checked") ? 1 : 0;
        var opd_prescription_niddm_chk = $('#opd_prescription_niddm_chk').is(":checked") ? 1 : 0;
        var opd_prescription_hbsag_chk = $('#opd_prescription_hbsag_chk').is(":checked") ? 1 : 0;
        var opd_prescription_hcv_chk = $('#opd_prescription_hcv_chk').is(":checked") ? 1 : 0;
        var opd_prescription_hiv_I_II_chk = $('#opd_prescription_hiv_I_II_chk').is(":checked") ? 1 : 0;

        var opd_prescription_drug_chk = $('#opd_prescription_drug_chk').is(":checked") ? 1 : 0;


        $.post('/Ipd_discharge/update_personal_history', {
                "p_id": p_id,
                "opd_prescription_smoking_chk": opd_prescription_smoking_chk,
                "opd_prescription_alcohol_chk": opd_prescription_alcohol_chk,
                "opd_prescription_tobacoo_chk": opd_prescription_tobacoo_chk,
                "opd_prescription_drug_chk": opd_prescription_drug_chk,
                "opd_prescription_hypertesion_chk": opd_prescription_hypertesion_chk,
                "opd_prescription_niddm_chk": opd_prescription_niddm_chk,
                "opd_prescription_hbsag_chk": opd_prescription_hbsag_chk,
                "opd_prescription_hcv_chk": opd_prescription_hcv_chk,
                "opd_prescription_hiv_I_II_chk": opd_prescription_hiv_I_II_chk,
                '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
            },
            function(data) {
                notify('success', 'Please Attention', data);
            });
    }

    //FOOD DRUG INTERACTION 

    function save_FOOD_DRUG_INTERACTION() {

        //var editor_data=CKEDITOR.instances['editor_FOOD_DRUG_INTERACTION_list'].getData();

        var editor_data = $('#editor_FOOD_DRUG_INTERACTION_list').val();

        var chkArray = [];
        $(".chk_food:checked").each(function() {
            chkArray.push($(this).val());
        });
        var selected;
        selected = chkArray.join(':');

        $.post('/Ipd_discharge/update_food_interaction', {
                "editor_data": editor_data,
                "ipd_id": $("#ipd_id").val(),
                "food_interaction_list": selected,
                '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
            },
            function(data) {
                notify('success', 'Please Attention', data);
            });
    }

    //Footer Banner 

    function save_footer_banner() {


        var chkArray = [];
        $(".chk_banner:checked").each(function() {
            chkArray.push($(this).val());
        });
        var selected;
        selected = chkArray.join(':');

        $.post('/Ipd_discharge/update_footer_banner', {
                "ipd_id": $("#ipd_id").val(),
                "footer_banner_list": selected,
                '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
            },
            function(data) {
                notify('success', 'Please Attention', data);
            });
    }


    //Presenting Complaints with Duration and Reason for Admission


    function complaintRemove(invACode, ipd_id) {
        var ipd_id = $('#ipd_id').val();
        load_form_div('/Ipd_discharge/remove_complaint/' + invACode + '/' + ipd_id, 'complaints_table');
    }

    function complaintUpdate(invACode, ipd_id) {

        var ipd_id = $('#ipd_id').val();
        var complaint_remarks = $('#input_complaint_remarks_' + invACode).val();
        var complaints = $('#input_complaints_' + invACode).val();

        $.post('/Ipd_discharge/update_complaint', {
                "ipd_id": ipd_id,
                "complaint_remarks": complaint_remarks,
                "complaint": complaints,
                "invACode": invACode,
                '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
            },
            function(data) {
                notify('success', 'Please Attention', data);
            });
    }

    function save_Complaints() {
        var ipd_id = $('#ipd_id').val();
        var editor_Sys_Exam = CKEDITOR.instances['editor_Other_Complaints'].getData();
        //var editor_Sys_Exam = $('#editor_Other_Complaints').val();

        $.post('/Ipd_discharge/update_complaint_edit', {
                "editor_Sys_Exam": editor_Sys_Exam,
                "ipd_id": ipd_id,
                '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
            },
            function(data) {
                notify('success', 'Please Attention', data);
            });
    }

    //Surgery Add and Update



    function surgeryRemove(invACode, ipd_id) {
        var ipd_id = $('#ipd_id').val();
        load_form_div('/Ipd_discharge/remove_surgery/' + invACode + '/' + ipd_id, 'surgery_table');
    }

    function surgeryUpdate(invACode, ipd_id) {
        var ipd_id = $('#ipd_id').val();
        var surgery_name = $('#input_surgery_name_' + invACode).val();
        var surgery_remark = $('#input_surgery_remark_' + invACode).val();
        var surgery_date = $('#surgery_date_' + invACode).val();

        $.post('/Ipd_discharge/update_surgery', {
                "ipd_id": ipd_id,
                "surgery_name": surgery_name,
                "surgery_remark": surgery_remark,
                "surgery_date": surgery_date,
                "invACode": invACode,
                '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
            },
            function(data) {
                notify('success', 'Please Attention', data);
            });
    }

    //Procedure Add and Update



    function procedureRemove(invACode, ipd_id) {
        var ipd_id = $('#ipd_id').val();
        load_form_div('/Ipd_discharge/remove_procedure/' + invACode + '/' + ipd_id, 'procedure_table');
    }

    function procedureUpdate(invACode, ipd_id) {
        var ipd_id = $('#ipd_id').val();
        var procedure_name = $('#input_procedure_name_' + invACode).val();
        var procedure_remark = $('#input_procedure_remark_' + invACode).val();
        var procedure_date = $('#procedure_date_' + invACode).val();

        $.post('/Ipd_discharge/update_procedure', {
                "ipd_id": ipd_id,
                "procedure_name": procedure_name,
                "procedure_remark": procedure_remark,
                "procedure_date": procedure_date,
                "invACode": invACode,
                '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
            },
            function(data) {
                notify('success', 'Please Attention', data);
            });
    }



    //Final Diagnosis at the time of Discharge


    function diagnosisRemove(invACode, ipd_id) {
        var ipd_id = $('#ipd_id').val();
        load_form_div('/Ipd_discharge/remove_diagnosis/' + invACode + '/' + ipd_id, 'diagnosis_table');
    }

    function diagnosisUpdate(invACode, ipd_id) {

        var ipd_id = $('#ipd_id').val();
        var diagnosis_remarks = $('#input_diagnosis_remarks_' + invACode).val();
        var diagnosis = $('#input_diagnosis_' + invACode).val();

        $.post('/Ipd_discharge/update_diagnosis', {
                "ipd_id": ipd_id,
                "diagnosis_remarks": diagnosis_remarks,
                "diagnosis": diagnosis,
                "invACode": invACode,
                '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
            },
            function(data) {
                notify('success', 'Please Attention', data);
            });
    }

    function save_Diagnosis() {
        var ipd_id = $('#ipd_id').val();
        var editor_Sys_Exam = $('#editor_Other_Diagnosis').val();
        $.post('/Ipd_discharge/update_diagnosis_edit', {
                "editor_Sys_Exam": editor_Sys_Exam,
                "ipd_id": ipd_id,
                '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
            },
            function(data) {
                notify('success', 'Please Attention', data);
            });
    }

    //Summary of key investigtions during Hospitalization
    function save_Hospial_investigtions() {
        var ipd_id = $('#ipd_id').val();
        var editor_Sys_Exam = CKEDITOR.instances['editor_investigtions_Hospitalization'].getData();
        $.post('/Ipd_discharge/update_investigtions_inhos', {
                "editor_Sys_Exam": editor_Sys_Exam,
                "ipd_id": ipd_id,
                '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
            },
            function(data) {
                notify('success', 'Please Attention', data);
            });
    }

    //Course in the hospital

    function save_course() {
        var ipd_id = $('#ipd_id').val();
        var editor_Sys_Exam = CKEDITOR.instances['editor_Course_hospital'].getData();
        $.post('/Ipd_discharge/update_course_remark', {
                "editor_Sys_Exam": editor_Sys_Exam,
                "ipd_id": ipd_id,
                '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
            },
            function(data) {
                notify('success', 'Please Attention', data);
            });
    }

    function courseRemove(invACode, ipd_id) {
        var ipd_id = $('#ipd_id').val();
        load_form_div('/Ipd_discharge/remove_course/' + invACode + '/' + ipd_id, 'course_table');
    }

    function courseUpdate(invACode, ipd_id) {

        var ipd_id = $('#ipd_id').val();
        var course_remarks = $('#input_course_remarks_' + invACode).val();
        var course = $('#input_course_' + invACode).val();

        $.post('/Ipd_discharge/update_course', {
                "ipd_id": ipd_id,
                "course_remarks": course_remarks,
                "course": course,
                "invACode": invACode,
                '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
            },
            function(data) {
                notify('success', 'Please Attention', data);
            });
    }

    //Drugs Updates
    function drugRemove(invACode, ipd_id) {
        var ipd_id = $('#ipd_id').val();
        load_form_div('/Ipd_discharge/remove_drug/' + invACode + '/' + ipd_id, 'drug_table');
    }

    function drugUpdate(invACode, ipd_id) {

        var ipd_id = $('#ipd_id').val();
        var course_remarks = $('#input_drug_remarks_' + invACode).val();

        $.post('/Ipd_discharge/update_drug', {
                "ipd_id": ipd_id,
                "drug_remarks": course_remarks,
                "invACode": invACode,
                '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
            },
            function(data) {
                notify('success', 'Please Attention', data);
            });
    }

    function drugEdit(invACode, drug_name, drug_dose, drug_day) {
        var ipd_id = $('#ipd_id').val();

        $('#drug_ipd_id').val(invACode);
        $('#input_drug').val(drug_name);
        $('#input_dosage').val(drug_dose);
        $('#input_days').val(drug_day);

    }

    //Discharge Instructions 

    function save_Instructions() {
        var ipd_id = $('#ipd_id').val();
        var input_next_visit= $('#input_next_visit').val();
        var editor_Sys_Exam = CKEDITOR.instances['editor_Discharge_Instructions'].getData();
        $.post('/Ipd_discharge/save_Instructions', {
                "editor_Sys_Exam": editor_Sys_Exam,
                "input_next_visit": input_next_visit,
                "ipd_id": ipd_id,
                '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
            },
            function(data) {
                notify('success', 'Please Attention', data);
            });
    }

    function add_next_visit(test_name) {
        var old_text = $('#input_next_visit').val();
        $('#input_next_visit').val(test_name);
        save_prescribed_extra();
}

    function save_Discharge_Footer() {
        var ipd_id = $('#ipd_id').val();
        var editor_Sys_Exam = CKEDITOR.instances['editor_Discharge_Footer'].getData();
        $.post('/Ipd_discharge/save_Footer', {
                "editor_Sys_Exam": editor_Sys_Exam,
                "ipd_id": ipd_id,
                '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
            },
            function(data) {
                notify('success', 'Please Attention', data);
            });
    }


    //Auto Type
    $(document).ready(function() {
        var cacheComplaints = {};
        var cacheDiseases = {};
        var cachesurgery = {};
        var cachemedical = {};
        var cachecourse = {};
        var cacheDrug = {};
        var cacheDosage = {};

        $('#dis_time').datetimepicker({
            format: 'HH:mm'
        });

        $("#input_course").autocomplete({
            source: function(request, response) {
                var term = request.term;
                if (term in cachecourse) {
                    response(cachecourse[term]);
                    return;
                }
                $.getJSON("Ipd_discharge/get_hospital_course_master", request, function(data, status,
                    xhr) {
                    cachecourse[term] = data;
                    response(data);
                });
            },
            minLength: 2,
            autofocus: true,
            select: function(event, ui) {
                $("#input_course").val(ui.item.value);
                $("#input_course_value").val(ui.item.complaints_no);
            }
        });


        $("#input_complaints").autocomplete({
            source: function(request, response) {
                var term = request.term;
                if (term in cacheComplaints) {
                    response(cacheComplaints[term]);
                    return;
                }
                $.getJSON("Ipd_discharge/get_complaints", request, function(data, status, xhr) {
                    cacheComplaints[term] = data;
                    response(data);
                });
            },
            minLength: 2,
            autofocus: true,
            select: function(event, ui) {
                $("#input_complaints").val(ui.item.value);
                $("#input_complaints_value").val(ui.item.complaints_no);
            }
        });

        $("#input_surgery_name").autocomplete({
            source: function(request, response) {
                var term = request.term;
                if (term in cachesurgery) {
                    response(cachesurgery[term]);
                    return;
                }
                $.getJSON("Ipd_discharge/get_surgery", request, function(data, status, xhr) {
                    cachesurgery[term] = data;
                    response(data);
                });
            },
            minLength: 2,
            autofocus: true,
            select: function(event, ui) {
                $("#input_surgery_name").val(ui.item.value);
                $("#input_surgery_id").val(ui.item.complaints_no);
            }
        });


        $("#input_diagnosis").autocomplete({
            source: function(request, response) {
                var term = request.term;
                if (term in cacheDiseases) {
                    response(cacheDiseases[term]);
                    return;
                }
                $.getJSON("Ipd_discharge/get_disease", request, function(data, status, xhr) {
                    cacheDiseases[term] = data;
                    response(data);
                });
            },
            minLength: 2,
            autofocus: true,
            select: function(event, ui) {
                $("#input_diagnosis").val(ui.item.value);
                $("#input_diagnosis_value").val(ui.item.complaints_no);
            }
        });


        $('[data-toggle="pill"]').click(function(e) {
            e.preventDefault()
            var loadurl = $(this).attr('href')
            if (loadurl != '') {
                var targ = $(this).attr('data-target')
                $.get(loadurl, function(data) {
                    $(targ).html(data)
                });
            }

            $(this).tab('show')
        });

        $('#btn_complaints').click(function() {
            var ipd_id = $('#ipd_id').val();
            var input_complaints = $('#input_complaints').val();
            var input_complaints_value = $('#input_complaints_value').val();
            var input_complaints_remarks = $('#input_complaints_remarks').val();

            $.post('/Ipd_discharge/add_complaints', {
                    "ipd_id": ipd_id,
                    "input_complaints": input_complaints,
                    "input_complaints_value": input_complaints_value,
                    "input_complaints_remarks": input_complaints_remarks,
                    '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
                },
                function(data) {
                    $('#complaints_table').html(data);
                    $('#input_complaints').val('');
                    $('#input_complaints_value').val('0');
                    $('#input_complaints_remarks').val('');
                });
        });

        $('#btn_surgery').click(function() {
            var ipd_id = $('#ipd_id').val();
            var input_surgery_name = $('#input_surgery_name').val();
            var input_surgery_id = $('#input_surgery_id').val();
            var surgery_date = $('#surgery_date').val();
            var input_surgery_remark = $('#input_surgery_remark').val();

            $.post('/Ipd_discharge/add_surgery', {
                    "ipd_id": ipd_id,
                    "input_surgery_name": input_surgery_name,
                    "input_surgery_id": input_surgery_id,
                    "surgery_date": surgery_date,
                    "input_surgery_remark": input_surgery_remark,
                    '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
                },
                function(data) {
                    $('#surgery_table').html(data);
                    $('#input_surgery_name').val('');
                    $('#input_surgery_id').val('0');
                    $('#input_surgery_remark').val('');
                });
        });

        $('#btn_Procedure').click(function() {
            var ipd_id = $('#ipd_id').val();
            var input_procedure_name = $('#input_procedure_name').val();
            var input_procedure_id = $('#input_procedure_id').val();
            var procedure_date = $('#procedure_date').val();
            var input_procedure_remark = $('#input_procedure_remark').val();

            $.post('/Ipd_discharge/add_procedure', {
                    "ipd_id": ipd_id,
                    "input_procedure_name": input_procedure_name,
                    "input_procedure_id": input_procedure_id,
                    "procedure_date": procedure_date,
                    "input_procedure_remark": input_procedure_remark,
                    '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
                },
                function(data) {
                    $('#procedure_table').html(data);
                    $('#input_procedure_name').val('');
                    $('#input_procedure_id').val('0');
                    $('#input_procedure_remark').val('');
                });
        });

        $('#btn_diagnosis').click(function() {
            var ipd_id = $('#ipd_id').val();
            var input_diagnosis = $('#input_diagnosis').val();
            var input_diagnosis_value = $('#input_diagnosis_value').val();
            var input_diagnosis_remarks = $('#input_diagnosis_remarks').val();

            $.post('/Ipd_discharge/add_diagnosis', {
                    "ipd_id": ipd_id,
                    "input_diagnosis": input_diagnosis,
                    "input_diagnosis_value": input_diagnosis_value,
                    "input_diagnosis_remarks": input_diagnosis_remarks,
                    '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
                },
                function(data) {
                    $('#diagnosis_table').html(data);
                    $('#input_diagnosis').val('');
                    $('#input_diagnosis_value').val('0');
                    $('#input_diagnosis_remarks').val('');
                });
        });

        $('#btn_course').click(function() {
            var ipd_id = $('#ipd_id').val();
            var input_course = $('#input_course').val();
            var input_course_value = $('#input_course_value').val();
            var input_course_remarks = $('#input_course_remarks').val();

            $.post('/Ipd_discharge/add_course', {
                    "ipd_id": ipd_id,
                    "input_course": input_course,
                    "input_course_value": input_course_value,
                    "input_course_remarks": input_course_remarks,
                    '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
                },
                function(data) {
                    $('#course_table').html(data);
                    $('#input_course').val('');
                    $('#input_course_value').val('0');
                    $('#input_course_remarks').val('');
                });

        });

        $('#btn_create').click(function() {
            var ipd_id = $('#ipd_id').val();
            if (confirm("Are you sure create Discharge New Report, It will distored old Report")) {
                load_form_div('/Ipd_discharge/preview_discharge_report/' + ipd_id, 'maindiv');
            }

        });

        $('#btn_edit').click(function() {
            var ipd_id = $('#ipd_id').val();
            load_form_div('/Ipd_discharge/edit_discharge_report/' + ipd_id, 'maindiv');

        });

        $('#btn_update_request').click(function() {
            var p_status = $('#p_status').val();

            if (p_status == "0") {
                alert("Please Select Patient Status.");
                return false;
            }

            if (confirm("Are you sure you want to discharge request")) {
                $.post('/index.php/Ipd/discharge_request', {
                    "Ipd_ID": $('#ipd_id').val(),
                    "p_status": p_status,
                    "dis_date": $('#dis_date').val(),
                    "dis_time": $('#dis_time').val(),
                    "isadd": 1,
                    "dept_id": $('#dept_id').val(),
                    '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
                }, function(data) {
                    notify('success', 'Please Attention', data);
                });
            } else {
                return false;
            }
        });

        $('#btn_surgery_request').click(function() {
            //var issurgery=$('#optionsRadios_issurgery').val();

            var issurgery = $("input[name='optionsRadios_issurgery']:checked").val();

            var surgery_name = $('#input_surgery_name').val();
            var surgery_date = $('#dis_surgery_date').val();

            $.post('/index.php/Ipd/update_surgery', {
                "ipd_id": $('#ipd_id').val(),
                "issurgery": issurgery,
                "surgery_name": surgery_name,
                "surgery_date": surgery_date,
                '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
            }, function(data) {
                notify('success', 'Please Attention', data);
            });

        });

        $('#btn_update_delivery').click(function() {
            var isdelivery = $("input[name='optionsRadios_isdelivery']:checked").val();

            var delivery_date = $('#delivery_date').val();
            var delivery_time = $('#delivery_time').val();
            var delivery_sex_of_baby = $('#delivery_sex_of_baby').val();
            var delivery_sex_of_baby2 = $('#delivery_sex_of_baby2').val();
            var delivery_sex_of_baby3 = $('#delivery_sex_of_baby3').val();

            $.post('/index.php/Ipd_discharge/update_delivery', {
                "ipd_id": $('#ipd_id').val(),
                "isdelivery": isdelivery,
                "delivery_date": delivery_date,
                "delivery_time": delivery_time,
                "delivery_sex_of_baby": delivery_sex_of_baby,
                "delivery_sex_of_baby2": delivery_sex_of_baby2,
                "delivery_sex_of_baby3": delivery_sex_of_baby3,
                '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
            }, function(data) {
                notify('success', 'Please Attention', data);
            });

        });

        //Medicince Prescribed Start

        $('#btn_medical').click(function() {
            var ipd_id = $('#ipd_id').val();

            var input_med_name = $('#input_med_name').val();
            var input_med_type = $('#input_med_type').val();
            var input_dosage = $('#input_dosage').val();
            var input_dosage_when = $('#input_dosage_when').val();
            var input_dosage_freq = $('#input_dosage_freq').val();
            var input_no_of_days = $('#input_no_of_days').val();
            var input_dose_where = $('#input_dose_where').val();
            var input_qty = $('#input_qty').val();
            var input_remark = $('#input_remark').val();
            var hid_ipd_med_item_id = $('#hid_ipd_med_item_id').val();
            var hid_med_id = $('#hid_med_id').val();

            var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

            $.post('/Ipd_discharge/add_drug', {
                    "ipd_id": ipd_id,
                    "input_med_name": input_med_name,
                    "input_med_type": input_med_type,
                    "input_dosage": input_dosage,
                    "input_dosage_when": input_dosage_when,
                    "input_dosage_freq": input_dosage_freq,
                    "input_no_of_days": input_no_of_days,
                    "input_dose_where": input_dose_where,
                    "input_qty": input_qty,
                    "input_remark": input_remark,
                    "hid_ipd_med_item_id": hid_ipd_med_item_id,
                    "hid_med_id": hid_med_id,
                    "<?= $this->security->get_csrf_token_name() ?>": csrf_value
                },
                function(data) {
                    $('#drug_table').html(data);
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
                    $("#hid_ipd_med_item_id").val('0');
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

        //Medicince Prescribed End

    });

    function medicalRemove(med_prec_id, ipd_id) {
        load_form_div('/Ipd_discharge/remove_drug/' + med_prec_id + '/' + ipd_id, 'drug_table');
    }

    function medicalSelect(med_prec_id, ipd_id) {
        var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

        $.post('/Ipd_discharge/medical_Select', {
                "ipd_id": ipd_id,
                "med_prec_id": med_prec_id,
                "<?= $this->security->get_csrf_token_name() ?>": csrf_value
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
                $("#hid_ipd_med_item_id").val(data.id);
                $("#hid_med_id").val(data.med_id);
                $("#input_med_name").focus();
            }, 'json');
    }
</script>