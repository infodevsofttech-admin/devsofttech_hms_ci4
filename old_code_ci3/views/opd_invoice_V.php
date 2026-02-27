<section class="content-header">
    <h1>OPD Invoice</h1>
    <ol class="breadcrumb">
        <li><a href="javascript:load_form('/Patient/person_record/<?=$opd_master[0]->p_id?>/0');"><i
                    class="fa fa-dashboard"></i> Person Home</a></li>
    </ol>
</section>
<section class="invoice">
    <?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
    <div class="row invoice-info">
        <!-- /.col -->
        <div class="col-sm-4 invoice-col">
            To
            <address>
                <strong><?=strtoupper($opd_master[0]->P_name) ?></strong><br>
                <?=$patient_master[0]->p_relative ?> : <?=$patient_master[0]->p_rname ?><br>
                OPD Book Date (y-m-d time) : <?=$opd_master[0]->opd_book_date ?><br>
                Gender : <?=($patient_master[0]->gender==1)?'Male':'Female' ?><br>
                Age : <?=$patient_master[0]->age ?><br>
                Phone No : <?=$patient_master[0]->mphone1 ?>
            </address>
        </div>
        <!-- /.col -->
        <div class="col-sm-4 invoice-col">
            <b>OPD ID:</b> <?=$opd_master[0]->opd_code ?><br>
            <b>Date of Appointment:</b> <?=$opd_master[0]->str_apointment_date ?><br>
            <b>Patient ID :</b> <?=$patient_master[0]->p_code ?><br>
            <?php
				if($opd_master[0]->insurance_id>1)
				{
					echo '<strong> Ins. Comp. :</strong>'.$insurance[0]->ins_company_name.'<br>';
				}
		?>
            <input type="hidden" id="oid" name="oid" value="<?=$opd_master[0]->opd_id ?>" />
            <?php 
			if($opd_master[0]->payment_id>0 )
			{
					echo '<strong>Payment No. :</strong>'.$opd_master[0]->payment_id.'<br>';
			}
			?>
        </div>
        <!-- /.col -->
    </div>
    <!-- /.row -->

    <!-- Table row -->
    <div class="row">
        <div class="col-xs-12 table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Doctor</th>
                        <th>Department</th>
                        <th>OPD Fee</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?=MysqlDate_to_str($opd_master[0]->apointment_date) ?></td>
                        <td>Dr. <?=$opd_master[0]->doc_name ?></td>
                        <td><?=$opd_master[0]->doc_spec ?></td>
                        <td><?=$opd_master[0]->opd_fee_gross_amount ?></td>
                        <td><?=$opd_master[0]->opd_fee_desc ?></td>
                    </tr>
                    <?php if($opd_master[0]->payment_status==0) {  ?>
                    <tr>
                        <td>Any Deduction</td>
                        <td colspan=2><input class="form-control varchar" name="input_dis_desc" id="input_dis_desc"
                                placeholder="Ded. Desc." value="<?=$opd_master[0]->opd_disc_remark ?>" type="text" />
                        </td>
                        <td><input style="width: 100px" class="form-control number" name="input_dis_amt"
                                id="input_dis_amt" placeholder="Amount" value="<?=$opd_master[0]->opd_discount ?>"
                                type="text" /></td>
                        <td><button type="button" class="btn btn-primary" id="btn_update_ded">Update</button></td>
                    </tr>
                    <?php }else{
			if($opd_master[0]->opd_discount>0 )
			{
			?>
                    <tr>
                        <td>Deduction</td>
                        <td colspan=2><?=$opd_master[0]->opd_disc_remark ?></td>
                        <td><?=$opd_master[0]->opd_discount ?></td>
                        <td></td>
                    </tr>
                    <?php }
			}			?>
                    <tr>
                        <th style="width: 10px">#</th>
                        <th colspan=2>Net Amount</th>
                        <th style=""><?=$opd_master[0]->opd_fee_amount?></th>
                        <th></th>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- /.col -->
    </div>
    <!-- /.row -->
    <?php if($opd_master[0]->payment_status==0 && $refund_status==0) {  ?>
    <div class="payment_type">
        <!-- accepted payments column -->
        <div class="jsError danger"></div>
        <div id="payment_type" class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label>Payment Mode</label>
                    <div class="panel-group" id="accordion">
                        <?php if($pending_amount>0 ) {  ?>
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <a data-toggle="collapse" data-parent="#accordion" href="#collapse1">
                                        Cash</a>
                                </h4>
                            </div>
                            <div id="collapse1" class="panel-collapse collapse in">
                                <div class="panel-body">
                                    <button type="button" class="btn btn-primary" id="btn_update1">Confirm Cash Received
                                        and Print Receipt</button>
                                </div>
                            </div>
                        </div>
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <a data-toggle="collapse" data-parent="#accordion" href="#collapse2">
                                        Credit / Debit Card</a>
                                </h4>
                            </div>
                            <div id="collapse2" class="panel-collapse collapse">
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Payment By </label>
                                                <select class="form-control" name="cbo_pay_type" id="cbo_pay_type" " >
												<?php
													foreach($bank_data as $row){
														echo '<option value="'.$row->id.'" > '.$row->pay_type.' ['.$row->bank_name.']'.'</option>';
													}
												?>
											</select>
										</div>
									</div>
									<div class=" col-md-4">
                                                    <div class="form-group">
                                                        <label>Tran. ID/Ref. </label>
                                                        <input class="form-control" id="input_card_tran"
                                                            placeholder="Card Tran.ID." type="text" autocomplete="off">
                                                    </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Payment Confirm By Bank/Online</label>
                                                    <button type="button" class="btn btn-primary"
                                                        id="btn_update2">Confirm Payment</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php }elseif($pending_amount<0){ 

					}else{ ?>
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h4 class="panel-title">
                                        <a data-toggle="collapse" data-parent="#accordion" href="#collapse5">
                                            Zero Amount </a>
                                    </h4>
                                </div>
                                <div id="collapse5" class="panel-collapse collapse in">
                                    <div class="panel-body">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <button id="btn_update0" type="button" class="btn btn-primary">Confirm
                                                    With Zero Amount</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                            <?php if($opd_master[0]->insurance_id>0 ) {  ?>
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h4 class="panel-title">
                                        <a data-toggle="collapse" data-parent="#accordion" href="#collapse4">
                                            Credit to Organization</a>
                                    </h4>
                                </div>
                                <div id="collapse4" class="panel-collapse collapse">
                                    <div class="panel-body">
                                        <?php foreach($case_master as $row) {  ?>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Case ID</label>
                                                    <input class="form-control" id="input_case_id"
                                                        placeholder="Claim ID" type="text"
                                                        value=<?=$row->case_id_code  ?> autocomplete="off" readonly>
                                                    <input type="hidden" id="hidden_case_id" value="<?=$row->id  ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Credit Confirm</label>
                                                    <button type="button" class="btn btn-primary" id="btn_update4">Click
                                                        here to Confirm</button>
                                                </div>
                                            </div>
                                        </div>
                                        <?php }?>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php  }else{  ?>
        <div class="row no-print">
            <div class="col-xs-6">
                <a href="<?php echo '/opd_print/invoice_print_pdf/'.$opd_master[0]->opd_id;  ?>" target="_blank"
                    class="btn btn-default"><i class="fa fa-print"></i> Print Invoice</a>
                <a href="<?php echo '/opd_print/opd_PDF_print/'.$opd_master[0]->opd_id;  ?>" target="_blank"
                    class="btn btn-default"><i class="fa fa-print"></i> Print Letter Head</a>
                <a href="<?php echo '/opd_print/opd_Cont_print/'.$opd_master[0]->opd_id;  ?>" target="_blank"
                    class="btn btn-default"><i class="fa fa-print"></i> Print Cont. Paper</a>
                <a href="<?php echo '/opd_print/opd_blank_print/'.$opd_master[0]->opd_id;  ?>" target="_blank"
                    class="btn btn-default"><i class="fa fa-print"></i> Print in Blank</a>
            </div>
            <div class="col-xs-6">
                <?php if($refund_status==1) { 
				echo 'Payment Refund Status : Pending';
			}elseif($opd_master[0]->opd_status==3){
				echo '<h1>Status is Cancelled</h1>' ;
			}else{?>
                Payment Method by : <?=$opd_master[0]->payment_mode_desc ?>
                <?php } ?>
            </div>
        </div>
        <?php } ?>
        <!-- /.row -->
        <?php if ($this->ion_auth->in_group('OPDEdit') && ($opd_master[0]->opd_status==1 || $opd_master[0]->opd_status==2)) { ?>
        <hr />
        <div class="row well well-sm">
            <?php	if($opd_master[0]->opd_status==1) {  ?>
            <div class="col-xs-6">
                <input class="form-control varchar" name="input_remark" id="input_remark" placeholder="Remark"
                    type="text" />
            </div>
            <div class="col-xs-2">
                <button type="button" class="btn btn-primary" id="btn_cancel_opd">Cancel OPD</button>
            </div>
            <?php }  ?>
            <div class="col-xs-4">
                <?php	if($opd_master[0]->opd_status==1 || $opd_master[0]->opd_status==2 ) {  
						if(count($case_master)>0)
						{
			?> <input type="hidden" id="hid_org_id" name="hid_org_id" value="<?=$case_master[0]->id?>">
                <button type="button" class="btn btn-success" id="btn_cr_org">Credit To Org.
                    [<?=$case_master[0]->case_id_code?>] </button>
                <?php 		}
				}  ?>
            </div>
        </div>
        <div class="row well well-sm">
            <div class="col-xs-3">
                <label>Change Doctor </label>
                <select class="form-control input-sm" id="doc_name_id" name="doc_name_id">
                    <?php 
					foreach($doc_spec_l as $row)
					{ 
						echo '<option value='.$row->id.'  '.combo_checked($row->id,$opd_master[0]->doc_id).'  >'.$row->p_fname.'</option>';
					}
					?>
                </select>
            </div>
            <div class="col-xs-3">
                <div class="form-group">
                    <label>Date <?=MysqlDate_to_str($opd_master[0]->apointment_date)?></label>
                    <div class="input-group date">
                        <div class="input-group-addon">
                            <i class="fa fa-calendar"></i>
                        </div>
                        <input class="form-control pull-right datepicker input-sm" id="datepicker_opddate"
                            name="datepicker_opddate" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask=""
                            value="<?=MysqlDate_to_str($opd_master[0]->apointment_date)?>" />
                    </div>
                </div>
            </div>
            <div class="col-xs-3">
                <div class="form-group">
                    <label>OPD Fee <?=$opd_master[0]->opd_fee_gross_amount?> /
                        <?=$opd_master[0]->opd_fee_amount?></label>
                    <input style="width: 100px" class="form-control number" name="input_opd_fee_amt"
                        id="input_opd_fee_amt" placeholder="Amount" value="<?=$opd_master[0]->opd_fee_gross_amount ?>"
                        type="text" />
                </div>
            </div>
            <div class="col-xs-2">
                <div class="form-group">
                    <button type="button" class="btn btn-primary" id="update_doc_date" accesskey="U"><u>U</u>pdate
                        Doctor and Date</button>
                </div>
            </div>
        </div>
        <div class="row well well-sm">
            <div class="col-xs-3">
                <?php if(($opd_master[0]->opd_status==1 or $opd_master[0]->opd_status==2) AND ($opd_master[0]->payment_mode==4)){ ?>
                <div class="form-group">
                    <button type="button" class="btn btn-primary" id="update_opd_payment_mode_change">Reverse to
                        Cash</button>
                </div>
                <?php }  ?>
            </div>
        </div>
        <?php }  ?>
</section>
<!-- /.content -->
<div class="clearfix"></div>
<input type="hidden" id="spid" value="<?=date('dmyHas').rand(10000,99999)?>" />
<?php echo form_close(); ?>
<script>
$(document).ready(function() {

    function enable_btn() {
        $('#btn_update1').attr('disabled', false);
        $('#btn_update2').attr('disabled', false);
    }

    $('#btn_update0').click(function() {
        $('#btn_update1').attr('disabled', true);
        $('#btn_update2').attr('disabled', true);

        var csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

        var spid = $('#spid').val();

        if (confirm("Are you sure process this invoice ")) {
            $.post('/index.php/Opd/confirm_payment', {
                "mode": "0",
                "oid": $('#oid').val(),
                "spid": spid,
                '<?=$this->security->get_csrf_token_name()?>': csrf_value
            }, function(data) {
                if (data.update == 0) {
                    $('div.jsError').html(data.error_text);
                } else {
                    load_form('/Opd/invoice/' + $('#oid').val());
                }
            }, 'json');
        } else {
            setTimeout(enable_btn, 5000);
        }
    });

    $('#btn_update1').click(function() {
        $('#btn_update1').attr('disabled', true);
        $('#btn_update2').attr('disabled', true);

        var csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

        var spid = $('#spid').val();

        if (confirm("Are you sure process this invoice ")) {
            $.post('/index.php/Opd/confirm_payment', {
                "mode": "1",
                "oid": $('#oid').val(),
                "spid": spid,
                '<?=$this->security->get_csrf_token_name()?>': csrf_value
            }, function(data) {
                if (data.update == 0) {
                    $('div.jsError').html(data.error_text);
                } else {
                    load_form('/Opd/invoice/' + $('#oid').val());
                }
            }, 'json');
        } else {
            setTimeout(enable_btn, 5000);
        }
    });

    $('#btn_update2').click(function() {
        $('#btn_update1').attr('disabled', true);
        $('#btn_update2').attr('disabled', true);

        var csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

        var spid = $('#spid').val();

        if (confirm("Are you sure process this invoice ")) {
            $.post('/index.php/Opd/confirm_payment', {
                "mode": "2",
                "oid": $('#oid').val(),
                "cbo_pay_type": $('#cbo_pay_type').val(),
                "input_card_tran": $('#input_card_tran').val(),
                "spid": spid,
                '<?=$this->security->get_csrf_token_name()?>': csrf_value
            }, function(data) {
                if (data.update == 0) {
                    //$('div.jsError').html(data.error_text);
                    notify('error', 'Please Attention', data.error_text);
                    setTimeout(enable_btn, 5000);
                } else {
                    load_form('/Opd/invoice/' + $('#oid').val());
                }
            }, 'json');
        } else {
            setTimeout(enable_btn, 5000);
        }
    });

    $('#btn_update3').click(function() {
        var csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

        $.post('/index.php/Opd/confirm_payment', {
            "mode": "3",
            "oid": $('#oid').val(),
            "ipd_id": $('#hidden_ipd_id').val(),
            '<?=$this->security->get_csrf_token_name()?>': csrf_value
        }, function(data) {
            if (data.update == 0) {
                $('div.jsError').html(data.error_text);
            } else {
                load_form('/Opd/invoice/' + $('#oid').val());
            }
        }, 'json');
    });

    $('#btn_update4').click(function() {
        var csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

        $.post('/index.php/Opd/confirm_payment', {
            "mode": "4",
            "oid": $('#oid').val(),
            "case_id": $('#hidden_case_id').val(),
            '<?=$this->security->get_csrf_token_name()?>': csrf_value
        }, function(data) {
            if (data.update == 0) {
                $('div.jsError').html(data.error_text);

            } else {
                load_form('/Opd/invoice/' + $('#oid').val());
            }
        }, 'json');
    });

    $('#btn_update_ded').click(function() {
        var csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

        if (confirm("Are you sure process this invoice ")) {
            $.post('/index.php/Opd/opd_discount_update/' + $('#oid').val(), {
                "oid": $('#oid').val(),
                "input_dis_desc": $('#input_dis_desc').val(),
                "input_dis_amt": $('#input_dis_amt').val(),
                '<?=$this->security->get_csrf_token_name()?>': csrf_value
            }, function(data) {
                load_form('/Opd/invoice/' + $('#oid').val());
            });
        }
    });

    $('#btn_cancel_opd').click(function() {
        var csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

        if (confirm("Are you sure process this invoice ")) {
            $.post('/index.php/Opd/opd_cancel/' + $('#oid').val(), {
                "oid": $('#oid').val(),
                "input_remark": $('#input_remark').val(),
                '<?=$this->security->get_csrf_token_name()?>': csrf_value
            }, function(data) {
                load_form('/Opd/invoice/' + $('#oid').val());
            });
        }
    });

    $('#btn_cr_org').click(function() {
        var csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

        if (confirm("Are you sure process this invoice ")) {
            $.post('/index.php/Opd/opd_crorg/' + $('#oid').val() + '/' + $('#hid_org_id').val(), {
                "oid": $('#oid').val(),
                "org_code_id": $('#hid_org_id').val(),
                '<?=$this->security->get_csrf_token_name()?>': csrf_value
            }, function(data) {
                load_form('/Opd/invoice/' + $('#oid').val());
            });
        }
    });

    $('#update_doc_date').click(function() {
        var csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

        if (confirm("Are you sure process this invoice ")) {
            $.post('/index.php/Opd/update_doc_date/' + $('#oid').val(), {
                "oid": $('#oid').val(),
                "doc_name_id": $('#doc_name_id').val(),
                "opd_fee_amt": $('#input_opd_fee_amt').val(),
                "datepicker_opddate": $('#datepicker_opddate').val(),
                '<?=$this->security->get_csrf_token_name()?>': csrf_value
            }, function(data) {
                load_form('/Opd/invoice/' + $('#oid').val());
            });
        }
    });

});
</script>