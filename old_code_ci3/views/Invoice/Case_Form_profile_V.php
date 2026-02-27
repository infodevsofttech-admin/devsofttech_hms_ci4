<section class="content-header">
    <h1>
        Insurance Case
        <small><?=$org_case_master[0]->case_id_code ?></small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="javascript:load_form('/Patient/person_record/<?=$org_case_master[0]->p_id ?>');"><i
                    class="fa fa-dashboard"></i> Person Panel</a></li>
        <?php
		if($case_type==1 && $ipd_id>0)
		{
		?>
        <li><a href="javascript:load_form('/IpdNew/ipd_panel/<?=$ipd_id ?>');"><i class="fa fa-dashboard"></i> IPD
                Panel</a></li>
        <?php
		}
		?>
    </ol>
</section>
<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <?php echo form_open('Patient/create', array('role'=>'form','class'=>'form1')); ?>
            <input type="hidden" name="patient_id" id="patient_id" value="<?=$org_case_master[0]->p_id ?>">
            <input type="hidden" value="<?=$org_case_master[0]->id ?>" id="c_id" name="c_id" />
            <input type="hidden" value="<?=$org_case_master[0]->case_type ?>" id="case_type" name="case_type" />
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input class="form-control" name="input_mphone1" placeholder="Phone Number"
                            value="<?=$org_case_master[0]->p_phone_number ?>" type="text" autocomplete="off"
                            data-inputmask='"mask": "9999999999"' data-mask>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input class="form-control" name="input_name" placeholder="Full Name"
                            value="<?=$org_case_master[0]->p_name ?>" type="text" autocomplete="off">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <div class="radio">
                            <label>
                                <input name="optionsRadios_gender" id="options_gender1" value="1"
                                    <?=radio_checked("1",$org_case_master[0]->p_gender)?> type="radio">
                                Male
                            </label>
                            <label>
                                <input name="optionsRadios_gender" id="options_gender2" value="2"
                                    <?=radio_checked("2",$org_case_master[0]->p_gender)?> type="radio">
                                Female
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Insurance Company Name</label>
                        <select class="form-control" id="Insurance_id" name="Insurance_id">
                            <option value='0'>All Doctors</option>
                            <?php 
										foreach($insurance_list as $row)
										{ 
											$selected=($row->id==$data_insurance[0]->id)?"Selected":"";
											
											echo '<option value='.$row->id.' '.$selected.' >'.$row->ins_company_name.'</option>';
										}
										?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Insurance No (like ECHS service No..)</label>
                        <input class="form-control" name="input_insurance_id" placeholder="Insurance Number" type="text"
                            value="<?=$org_case_master[0]->insurance_no ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Insurance Card Holder Name</label>
                        <input class="form-control" name="input_card_holder_name" placeholder="Name of Card Holder"
                            type="text" value="<?=$org_case_master[0]->insurance_card_name ?>">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Date of Registration</label>
                        <div class="input-group date">
                            <div class="input-group-addon">
                                <i class="fa fa-calendar"></i>
                            </div>
                            <input class="form-control pull-right datepicker"
                                value="<?=MysqlDate_to_str($org_case_master[0]->date_registration) ?>"
                                name="datepicker_dob" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask="" />
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Case No. or Clam No.</label>
                        <input class="form-control" name="input_insurance_no_1" placeholder="Case No. or Clam No."
                            type="text" value="<?=$org_case_master[0]->insurance_no_1 ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Other Ref. No.1</label>
                        <input class="form-control" name="input_insurance_no_2" placeholder="Other Ref. No.1"
                            type="text" value="<?=$org_case_master[0]->insurance_no_2 ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Other Ref. No.2</label>
                        <input class="form-control" name="input_insurance_no_3" placeholder="Other Ref. No.2"
                            type="text" value="<?=$org_case_master[0]->insurance_no_3 ?>">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="box">
                        <div class="box-header">
                            <h3 class="box-title">Remarks
                                <small>Write some about the problem</small>
                            </h3>
                            <!-- tools box -->
                            <div class="pull-right box-tools">
                                <button type="button" class="btn btn-default btn-sm" data-widget="collapse"
                                    data-toggle="tooltip" title="Collapse">
                                    <i class="fa fa-minus"></i></button>
                                <button type="button" class="btn btn-default btn-sm" data-widget="remove"
                                    data-toggle="tooltip" title="Remove">
                                    <i class="fa fa-times"></i></button>
                            </div>
                            <!-- /. tools -->
                        </div>
                        <!-- /.box-header -->
                        <div class="box-body pad">
                            <textarea id='remark' name="remark" placeholder="Place some text here">
									<?=$org_case_master[0]->remark ?>
									</textarea>
                            <script>
                            CKEDITOR.replace('remark');
                            </script>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <button type="button" class="btn btn-primary" id="btn_update">Update Case Record</button>
                    </div>
                </div>
            </div>
            <div class="jsError"></div>
            <?php echo form_close(); ?>
        </div>
    </div>
    <!-- ./row -->
</section>
<!-- /.content -->

<script>
$(document).ready(function() {
    $('#btn_update').click(function() {
        $.post('/index.php/Ocasemaster/update', $('form.form1').serialize(), function(data) {
            if (data.update == 0) {
                notify('error',data.error_text);
            } else {
                notify('success',data.showcontent);
            }
        }, 'json');
    });

    $('#btn_lab').click(function() {
        var c_id = $('#c_id').val();
        load_form('/Ocasemaster/addPathTest/' + c_id);
    });

});
</script>