<section class="content-header">
    <h1>
        <?=ucwords($data[0]->p_fname); ?>
        <small><a
                href="javascript:load_form('/Patient/person_record/<?=$data[0]->id?>/0');"><?=$data[0]->p_code; ?></a></small>
    </h1>
</section>
<!-- Main content -->
<section class="content">
    <div class="jsError"></div>
    <?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
    <div class="row">
        <div class="col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <div class="col-md-4">
                        <h3 class="box-title">Person Profile</h3>
                    </div>
                    <div class="col-md-8 ">
                        <a href="javascript:load_form('/Patient/person_record/<?=$data[0]->id?>/1');"
                            class="btn btn-danger btn-xs">Profile Edit</a>
                        <a href="javascript:load_form('/Patient/show_profile_image/<?=$data[0]->id?>/1');"
                            class="btn btn-success btn-xs">Edit Profile Picture</a>
                        <a href="javascript:load_form('/Patient/show_profile_opd/<?=$data[0]->id?>/1');"
                            class="btn btn-info btn-xs">OPD Scan</a>
                    </div>
                </div>
                <div class="box-body">
                    <input type="hidden" value="<?=$data[0]->id ?>" id="p_id" name="p_id" />
                    <div class="row">
                        <div class="col-md-6">
                            <p class="text-primary">Full Name : <span
                                    class="text-success"><?=ucwords($data[0]->p_fname) ?></span></p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-primary">Gender : <span class="text-success"><?=$data[0]->xgender?></span>
                                <span class="text-primary"> / Age : </span> <span
                                    class="text-success"><?=$data[0]->str_age?> </span>
                            </p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-9">
                            <p class="text-primary">Relation : <span
                                    class="text-success"><?=$data[0]->p_relative?></span>
                                <span class="text-primary">Relative Name :</span> <span
                                    class="text-success"><?=ucwords($data[0]->p_rname)?></span>
                            </p>
                        </div>
                        <div class="col-md-3">
                            <p class="text-primary">Aadhar No. : <span class="text-success"><?=$data[0]->udai?></span>
                            </p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="text-primary">Phone Number : <span
                                    class="text-success"><?=$data[0]->mphone1?></span></p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-primary">Email : <span class="text-success"><?=$data[0]->email1?></span></p>
                        </div>
						<div class="col-md-6">
                            <p class="text-primary">Blood Group : <span class="text-success"><?=$data[0]->blood_group?></span></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <p class="text-primary">Address : <span class="text-success"><?=$data[0]->add1?>
                                    <?=$data[0]->city ?>
                                    <?=$data[0]->district ?>
                                    <?=$data[0]->state ?>
                                    <?=$data[0]->zip ?>
                                </span></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <img src="<?=$profile_file_path?>" width="200px" />
                        </div>
                        <div class="col-md-6">
                            <div class="input-group input-group-sm">
                                <input class="form-control" type="text" name="input_Aadhar" id="input_Aadhar"
                                    value="<?=$data[0]->udai ?>">
                                <span class="input-group-btn">
                                    <button type="button" id="btn_update_aadhar" class="btn btn-info btn-flat">Update
                                        Aadhar No.</button>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title">Insurance Details
                        <small><a href="javascript:load_form('/Patient/show_cards/<?=$data[0]->id?>/1');"
                                class="btn btn-warning btn-xs">Insurance Update</a> </small>
                    </h3>
                </div>
                <input type="hidden" id="ins_id" value="<?=$data[0]->insurance_id?>">
                <input type="hidden" id="ins_card_id" value="<?=$data[0]->insurance_card_id?>">
                <div class="box-body">
                    <?php if($data[0]->insurance_id>0) {  ?>
                    <div class="row">
                        <div class="col-md-8">
                            <p class="text-primary">Company Name : <span
                                    class="text-success"><?=$data_insurance_card[0]->ins_company_name?></span></p>
                            <p class="text-primary">Card Holder Name : <span
                                    class="text-success"><?=$data[0]->card_holder_name?></span></p>
                            <p class="text-primary">Relation : <span
                                    class="text-success"><?=$data[0]->relation_patient_cardholder?></span></p>
                            <p class="text-primary">Insurance ID : <span
                                    class="text-success"><?=$data[0]->insurance_no?></span></p>
                        </div>
                        <div class="col-md-4">
                            <div class="row">
                                <?php if(count($case_master_opd)>0){ ?>
                                <p class="text-primary">Case Code : <span
                                        class="text-danger"><?=$case_master_opd[0]->case_id_code?></span></p>
                                <p class="text-primary">Visit Date Start : <span
                                        class="text-success"><?=$case_master_opd[0]->str_date_registration?></span></p>
                                <p class="text-primary">Clam or Case No.: <span
                                        class="text-success"><?=$case_master_opd[0]->insurance_no_1?></span></p>
                                <p class="text-primary">Any Other IDs : <span
                                        class="text-success"><?=$case_master_opd[0]->insurance_no_2?></span></p>
                                <p>
                                    <button type="button" class="btn btn-warning btn-xs" id="btn_case_opd">Update Case
                                        Information</button>
                                </p>


                                <?php }else if(count($case_master_ipd)>0){ ?>
                                <input type="hidden" id="ins_org_id" value="<?=$case_master_ipd[0]->id?>">
                                <p class="text-primary">Case Code : <span
                                        class="text-danger"><?=$case_master_ipd[0]->case_id_code?></span></p>
                                <p class="text-primary">Org. Date Start : <span
                                        class="text-success"><?=$case_master_ipd[0]->str_date_registration?></span></p>
                                <p class="text-primary">Clam or Case No.: <span
                                        class="text-success"><?=$case_master_ipd[0]->insurance_no_1?></span></p>
                                <p class="text-primary">Any Other IDs : <span
                                        class="text-success"><?=$case_master_ipd[0]->insurance_no_2?></span></p>
                                <p class="text-primary">IPD No : <span
                                        class="text-success"><?=$case_master_ipd[0]->ipd_code?></span></p>
                                <p class="text-primary">IPD Admit Date : <span
                                        class="text-success"><?=$case_master_ipd[0]->str_date_registration?></span></p>
                                <p>
                                    <button type="button" class="btn btn-warning btn-xs" id="btn_case_ipd_open">Update
                                        Case Information</button>
                                </p>

                                <?php }else{  ?>
                                <button type="button" class="btn btn-info " id="btn_case_ipd">Create Case for Credit IPD
                                    Bill </button>
                                <hr />
                                <button type="button" class="btn btn-success " id="btn_case_opd">Create Case for Credit
                                    OPD Bill </button>
                                <?php }  ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <?php if($data_insurance_card[0]->opd_allowed==1) {  ?>
                        <p>
                            <button type="button" class="btn btn-success btn-xs" id="btn_inc_opd">OPD Insurance Rates /
                                CASH</button>
                        </p>
                        <?php }  ?>
                        <?php if($data_insurance_card[0]->charge_cash==1) {  ?>
                        <p>
                            <button type="button" class="btn btn-success btn-xs" id="btn_inc_lab">Charge with Ins. Rate
                                / CASH</button>
                        </p>
                        <?php } ?>
                    </div>
                    <?php }  ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <button type="button" class="btn btn-primary" id="btn_opd" accesskey="A"><u>A</u>ppointment For
                        OPD</button>
                    <button type="button" class="btn btn-danger" id="btn_lab">Add Charge without OPD</button>
                    <button type="button" class="btn btn-warning" id="btn_ipd">IPD</button>
                    <button type="button" class="btn btn-success" id="btn_doc">Document</button>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <?php if(count($opd_List)>0) { ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="box box-success">
                        <div class="box-header with-border">
                            <h3 class="box-title">OPD Registration</h3>
                        </div>
                        <div class="box-body">
                            <?php
						foreach($opd_List as $row)
							{
								echo '<strong>'.$row->opd_code.'</strong>';
								echo '<p class="text-muted">';
								echo '<span class="text-success">Dr.'.$row->doc_name.'</span>	';
								echo '<span class="text-info"> D:'.$row->str_apointment_date.'</span>	';
								echo '<span class="text-warning"> P:'.$row->p_fname.'</span><br/>	';

                                if($this->ion_auth->in_group('OPDEdit') || $row->new_opd==1){
                                    echo '<a href="javascript:load_form(\'/Opd/invoice/'.$row->opd_id.'/0\');" class="btn btn-warning btn-xs">Edit OPD</a> ';
								    echo '<a href="/Opd_print/opd_day_care/'.$row->opd_id.'/0" target="_blank" class="btn btn-success btn-xs">Print Day Care</a> ';
                                }
								echo '</p>';
								echo '<hr />';
							}
						?>
                        </div>
                    </div>
                </div>
            </div>
            <?php }  ?>
            <?php if(count($invoice_list)>0) {  ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="box box-warning">
                        <div class="box-header with-border">
                            <h3 class="box-title">Charges Invoice</h3>
                        </div>
                        <div class="box-body">
                            <?php
							foreach($invoice_list as $row)
							{
								echo '<strong>'.$row->invoice_code.'</strong>';
								echo ' <span class="text-info">D:'.$row->str_inv_date.'</span>	';
								echo ' <span class="text-warning">N:'.$row->inv_name.'</span>	';
								echo '<p class="text-muted">';
								echo '<span class="text-success">'.$row->Item_List.'</span>	<br/>';
								
								if($row->invoice_status==0)
								{
									echo '<a href="javascript:load_form(\'/PathLab/IPD_Invoice_Edit/'.$row->id.'\');" class="btn btn-warning btn-xs">Edit Charges Invoice</a> ';
									echo '<a href="javascript:delete_invoice(\''.$row->id.'\');" class="btn btn-danger btn-xs">Delete : Un-confirm Invoice</a> ';
								}else{
									echo '<a href="javascript:load_form(\'/PathLab/showinvoice/'.$row->id.'\');" class="btn btn-info btn-xs">Show Charges Invoice</a> ';
								}
								echo '</p>';
								echo '<hr />';
							}
						?>

                        </div>
                    </div>
                </div>
            </div>
            <?php }  ?>
        </div>
    </div>
    <!-- ./row -->
    <?php echo form_close(); ?>
</section>
<!-- /.content -->

<script>
$(document).ready(function() {

    document.title = 'Pt.:<?=$data[0]->p_fname ?>/<?=$data[0]->id ?>';

    $('#btn_opd').click(function() {
        var p_id = $('#p_id').val();
        load_form('/Opd/addopd/' + p_id);
    });

    $('#btn_update_aadhar').click(function() {
        var p_id = $('#p_id').val();
        var udai = $('#input_Aadhar').val();
        var csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

        if (confirm("Are you sure Update Aadhar No.")) {
            $.post('/index.php/Patient/update_aadhar', {
                "p_id": p_id,
                "udai": udai,
                "<?=$this->security->get_csrf_token_name()?>": csrf_value
            }, function(data) {
                load_form('/Patient/person_record/' + p_id);
            });
        }
    });

    $('#btn_inc_opd').click(function() {
        var p_id = $('#p_id').val();
        load_form('/Opdcase/addopd/' + p_id);
    });

    $('#btn_ipd').click(function() {
        var p_id = $('#p_id').val();
        load_form('/IpdNew/addipd/' + p_id);
    });

    $('#btn_lab').click(function() {
        var p_id = $('#p_id').val();
        load_form('/PathLab/addPathTest/' + p_id);
    });

    $('#btn_doc').click(function() {
        var p_id = $('#p_id').val();
        load_form('/Document_Patient/p_doc_record/' + p_id);
    });

    $('#btn_inc_lab').click(function() {
        var p_id = $('#p_id').val();
        var ins_card_id = $('#ins_card_id').val();
        load_form('/PathLab/addPathTest/' + p_id + '/' + ins_card_id);
    });

    $('#btn_case_opd').click(function() {
        var p_id = $('#p_id').val();
        var ins_id = $('#ins_id').val();
        var ins_card_id = $('#ins_card_id').val();

        load_form('/Ocasemaster/newcase/' + p_id + '/' + ins_id + '/0');
    });

    $('#btn_case_ipd_open').click(function() {
        var ins_org_id = $('#ins_org_id').val();
        load_form('/Ocasemaster/open_case/' + ins_org_id + '/1');
    });

    $('#btn_case_ipd').click(function() {
        var p_id = $('#p_id').val();
        var ins_id = $('#ins_id').val();
        var ins_card_id = $('#ins_card_id').val();
        load_form('/Ocasemaster/newcase/' + p_id + '/' + ins_id + '/1');
    });

    $('#btn_card').click(function() {
        var p_id = $('#p_id').val();
        load_form('/Patient/show_cards/' + p_id);
    });

    $('#btn_update_card').click(function() {
        var p_id = $('#p_id').val();
        var ins_id = $('#ins_id').val();
        load_form('/Patient/show_cards/' + p_id + '/' + ins_id);
    });
});

function delete_invoice(inv_id) {
    var pid = $('#p_id').val();
    var csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

    if (confirm("Are you sure delete this invoice")) {
        $.post('/index.php/PathLab/deleteinvoice', {
            "inv_id": inv_id,
            "<?=$this->security->get_csrf_token_name()?>": csrf_value
        }, function(data) {
            load_form('/Patient/person_record/' + pid);
        });
    }

}
</script>