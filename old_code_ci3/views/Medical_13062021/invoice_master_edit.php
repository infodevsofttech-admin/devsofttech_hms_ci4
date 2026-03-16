<div id='Medical_invoice_final'>
<section class="content-header">
  <h1>
	Medical Invoice Master Edit : 
	<small>
	<a href="javascript:load_form_div('/Medical/Invoice_med_show/<?=$invoiceMaster[0]->id ?>/0','maindiv');" >Back to Invoice</a>
	</small>
  </h1>
</section>
<section class="content">
<?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
<input type="hidden" id="med_invoice_id" name="med_invoice_id" value="<?=$invoiceMaster[0]->id ?>" />
<div class="box box-success">
    <div class="box-header">
		<div class="row">
			<div class="col-md-12">
				<input type="hidden" id="pid" name="pid" value="<?=$invoiceMaster[0]->patient_id ?>" />
				<input type="hidden" id="med_invoice_id" name="med_invoice_id" value="<?=$invoiceMaster[0]->id ?>" />
				<p><strong>Name :</strong><?=$invoiceMaster[0]->inv_name?>
				<strong>/ P Code :</strong><?=$invoiceMaster[0]->patient_code?> 
				<strong>/ Invoice No. :</strong><?=$invoiceMaster[0]->inv_med_code?>
				<strong>/ Date :</strong> <?=MysqlDate_to_str($invoiceMaster[0]->inv_date)?>
				<?php if($invoiceMaster[0]->ipd_id > 0) { ?>
					<strong>IPD Code :</strong>
					<a href="javascript:load_form_div('/Medical/list_med_inv/<?=$ipd_master[0]->id ?>/0','maindiv');" >
						<?=$ipd_master[0]->ipd_code?>
					</a>
					<strong>Admit Date : </strong><?=$ipd_list[0]->str_register_date ?> <?=$ipd_list[0]->reg_time ?>
					<strong>/ Doctor :</strong><?=$ipd_list[0]->doc_name?>
					<strong>/ TPA-Org. :<?=$org_Name?>
					<?php }elseif($invoiceMaster[0]->case_id > 0){  ?>
						<strong>/ Org. Case ID :<?=$OCaseMaster[0]->case_id_code ?>
					<?php } ?>
				</p>
			</div>
		</div>
    </div>
	<div class="box-body">
		<div class="row">
			<div class="col-md-4">
                <div class="form-group">
                    <label>Change Date</label>
                    <div class="input-group date">
                        <div class="input-group-addon">
                            <i class="fa fa-calendar"></i>
                        </div>
                        <input class="form-control pull-right datepicker" id="datepicker_invoicedate" name="datepicker_invoicedate" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask="" value="<?=MysqlDate_to_str($invoiceMaster[0]->inv_date)?>"  />
                    </div>
                </div>
            </div>
			<div class="col-md-2">
				<div class="form-group">
					<label> </label>
					<button type="button" class="btn btn-primary"  onclick="update_invdate()">Update Invoice Date </button>
				</div>
			</div>
		</div>
		<div class="row">
			<?php if($invoiceMaster[0]->ipd_id > 0) { ?>
			<div class="col-md-4">
				<?php if($invoiceMaster[0]->payment_received ==0) { ?>
				<div class="form-group">
					<div class="radio">
						<label>
						  <input name="optionsRadios_credit" id="options_credit1" value="0" <?=radio_checked("0",$invoiceMaster[0]->ipd_credit)?> type="radio">
						  Cash / Direct
						</label>
						<label>
							<input name="optionsRadios_credit" id="options_credit2" value="1" <?=radio_checked("1",$invoiceMaster[0]->ipd_credit)?> type="radio">
							Credit To Hospital
						</label>
					</div>
				</div>
				<?php }else{ ?>
					<div class="form-group">
						<label>Status</label>
						<div class="form-control" ><?=$invoiceMaster[0]->credit_status ?></div>
					</div>
				<?php } ?>
			</div>
			<div class="col-md-2">
				<div class="form-group">
					<label> </label>
					<button type="button" class="btn btn-primary"  onclick="update_ipd_credit_status()">Update Credit Type </button>
				</div>
			</div>
			<?php }elseif($invoiceMaster[0]->case_id > 0){  ?>
			<div class="col-md-4">
				<?php if($invoiceMaster[0]->payment_received ==0) { ?>
				<div class="form-group">
					<div class="radio">
						<label>
						  <input name="optionsRadios_credit" id="options_credit1" value="0" <?=radio_checked("0",$invoiceMaster[0]->case_credit)?> type="radio">
						  Cash
						</label>
						<label>
							<input name="optionsRadios_credit" id="options_credit2" value="1" <?=radio_checked("1",$invoiceMaster[0]->case_credit)?> type="radio">
							Credit
						</label>
					</div>
				</div>
				<?php } ?>
			</div>
			<?php }  ?>	
		</div>
	</div>
</div>
<div class="box box-info">
    <div class="box-header">
		<div class="box-title">
			<p style="font-size: 14px;">Change Name : <span style="color:red;">Current Name : <?=$invoiceMaster[0]->patient_code ?></span> </p>
        </div>
    </div>
	<div class="box-body">
		<div class="row">
			<div class="col-md-4">
				<div class="form-group">
					<label>Patient Name</label>
					<input class="form-control" id="P_Name" name="P_Name"  >
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group">
					<label>Patient Phone No.</label>
					<input class="form-control" id="P_Phone" name="P_Phone"  >Patient Name</div>
				</div>
			</div>
			<div class="col-md-3">
				<div class="form-group">
					<label> </label>
					<button type="button" class="btn btn-primary" id="btn_update" onclick="update_name_phone()">Update Name </button>
				</div>
			</div>
		</div>
</div>
<div class="box box-danger">
    <div class="box-header">
		<div class="box-title">
			<p style="font-size: 14px;">Change UHID : <span style="color:red;">Current UHID : <?=$invoiceMaster[0]->patient_code ?></span> </p>
        </div>
    </div>
	<div class="box-body">
		<div class="row">
			<div class="col-md-3">
				<div class="form-group">
					<label>UHID /Patient Code</label>
					<input class="form-control" name="input_uhid" id="input_uhid" placeholder="UHID /Patient Code"  type="text"  autocomplete="off" onchange="onchange_uhid()">
				</div>
			</div>
			<div class="col-md-6">
				<div class="form-group">
					<label>Patient Information</label>
					<div class="form-control" id="P_Info" name="P_Info"  >Patient Information</div>
				</div>
			</div>
			<div class="col-md-3">
				<div class="form-group">
					<label> </label>
					<input type="hidden" id="pid" name="pid" value="0" />
					<button type="button" class="btn btn-primary" id="btn_update" onclick="update_uhid()">Update UHID Record </button>
				</div>
			</div>
		</div>
</div>
<div class="box box-warning">
    <div class="box-header">
		<div class="box-title">
			<p style="font-size: 14px;">Change IPD :  
			<?php if($invoiceMaster[0]->ipd_credit>0){ ?>
				<span style="color:red;">Current IPD : <?=$invoiceMaster[0]->ipd_code ?></span> 
			<?php } ?>
			</p>
        </div>
    </div>
	<div class="box-body">
		<div class="row">
			<div class="col-md-3">
				<div class="form-group">
					<label>IPD No./Code</label>
					<input class="form-control" name="input_ipd_no" id="input_ipd_no" 
					placeholder="IPD Code"  type="text"  autocomplete="off" onchange="onchange_ipd()">
				</div>
			</div>
			<div class="col-md-6">
				<div class="form-group">
					<label>Patient Information</label>
					<div class="form-control" id="IPD_Info" name="IPD_Info"  ></div>
				</div>
			</div>
			<div class="col-md-3">
				<div class="form-group">
					<label> </label>
					<input type="hidden" id="ipd_id" name="ipd_id" value="0" />
					<button type="button" class="btn btn-warning"  onclick="update_ipd()">Update IPD NO. Record </button>
				</div>
			</div>
		</div>
</div>
<div class="box box-info">
    <div class="box-header">
		<div class="box-title">
			<p style="font-size: 14px;">Change Org. Case : 
			<?php if($invoiceMaster[0]->case_credit>0){ ?>
				<span style="color:red;">Current Org. Case : <?=$invoiceMaster[0]->case_id ?></span> 
			<?php } ?>
			<input type="hidden" id="med_invoice_id" name="med_invoice_id" value="<?=$invoiceMaster[0]->id ?>" />
			</p>
        </div>
    </div>
	<div class="box-body">
		<div class="row">
			<div class="col-md-3">
				<div class="form-group">
					<label>Org.Case No./Code</label>
					<input class="form-control" name="input_org_no" id="input_org_no" 
					placeholder="ORG. Code"  type="text"  autocomplete="off" onchange="onchange_org()">
				</div>
			</div>
			<div class="col-md-6">
				<div class="form-group">
					<label>Patient Information</label>
					<div class="form-control" id="org_case_Info" name="org_case_Info"  >Patient Information</div>
				</div>
			</div>
			<div class="col-md-3">
				<div class="form-group">
					<label> </label>
					<input type="hidden" id="org_id" name="org_id" value="0" />
					<button type="button" class="btn btn-info" onclick="update_org()">Update Org NO. Record </button>
				</div>
			</div>
		</div>
</div>

<?php echo form_close(); ?>
</section>
<!-- /.content -->

<script>

	function onchange_uhid()
	{
		var input_uhid=$('#input_uhid').val();
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		$.post('/index.php/Patient/P_info',{
			"input_uhid":input_uhid,
			'<?=$this->security->get_csrf_token_name()?>':csrf_value}, 
			function(data){
				$('#pid').val(data.Patient_id);
				$('#P_Info').html(data.Patient_info);
			},'json');
	}

	  function onchange_ipd()
	  {
		var input_ipd_no=$('#input_ipd_no').val();
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		$.post('/index.php/IpdNew/IPD_info',{
			"input_ipd_no":input_ipd_no,
			'<?=$this->security->get_csrf_token_name()?>':csrf_value}, function(data){
                $('#ipd_id').val(data.IPD_id);
				$('#IPD_Info').html(data.ipd_info);
				
        },'json');
	  }

	  function onchange_org()
	  {
		var input_org_no=$('#input_org_no').val();
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		$.post('/index.php/Orgcase/ORG_info',{
			"input_org_no":input_org_no,
			'<?=$this->security->get_csrf_token_name()?>':csrf_value}, function(data){
                $('#org_id').val(data.org_id);
				$('#org_case_Info').html(data.org_info);
				
        },'json');
	  }

	  function update_uhid()
	  {
		var pid=$('#pid').val();
		var med_invoice_id=$('#med_invoice_id').val();
		
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		if(pid>0 && pid!='')
		{
			$.post('/index.php/Medical/update_uhid',{
			"pid":pid,
			"med_invoice_id":med_invoice_id,
			'<?=$this->security->get_csrf_token_name()?>':csrf_value}, function(data){
                alert(data.remark);
        	},'json');
		}else{
			alert('Type Correct UDHI or Patient ID ');
		}
		
	  }

	  function update_name_phone()
	  {
		var P_Name=$('#P_Name').val();
		var P_Phone=$('#P_Phone').val();
		var med_invoice_id=$('#med_invoice_id').val();
		
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		if(confirm('Are you Sure Update Patient Name'))
		{
			$.post('/index.php/Medical/update_name_phone',{
			"pid":0,
			"customer_type":0,
			"P_Name":P_Name,
			"P_Phone":P_Phone,
			"med_invoice_id":med_invoice_id,
			'<?=$this->security->get_csrf_token_name()?>':csrf_value}, function(data){
                alert(data.remark);
        	},'json');
		}
	  }

	  function update_ipd()
	  {
		var ipd_id=$('#ipd_id').val();
		var med_invoice_id=$('#med_invoice_id').val();
		
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		if(ipd_id>0 && ipd_id!='')
		{
			$.post('/index.php/Medical/update_ipd',{
			"ipd_id":ipd_id,
			"med_invoice_id":med_invoice_id,
			'<?=$this->security->get_csrf_token_name()?>':csrf_value}, function(data){
                alert(data.remark);
        	},'json');
		}else{
			alert('Type Correct IPD ');
		}
	  }

	  function update_org()
	  {
		var org_id=$('#org_id').val();
		var med_invoice_id=$('#med_invoice_id').val();
		
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		if(org_id>0 && org_id!='')
		{
			$.post('/index.php/Medical/update_org',{
			"org_id":org_id,
			"med_invoice_id":med_invoice_id,
			'<?=$this->security->get_csrf_token_name()?>':csrf_value}, function(data){
                alert(data.remark);
        	},'json');
		}else{
			alert('Type Correct ORG ');
		}
	  }

	  function update_invdate()
	  {
		var inv_date=$('#datepicker_invoicedate').val();
		var med_invoice_id=$('#med_invoice_id').val();
		
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		if(inv_date!='')
		{
			$.post('/index.php/Medical/update_invdate',{
			"med_invoice_id":med_invoice_id,
			"inv_date":inv_date,
			'<?=$this->security->get_csrf_token_name()?>':csrf_value
			}, function(data){
                alert(data.remark);
        	},'json');
		}else{
			alert('Type Correct Date ');
		}
	  }

	  function update_ipd_credit_status()
	  {
		var credit_ipd=$("input[name='optionsRadios_credit']:checked").val();
		var med_invoice_id=$('#med_invoice_id').val();
		
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		if(credit_ipd!='')
		{
			$.post('/index.php/Medical/update_cr_status_ipd',{
			"med_invoice_id":med_invoice_id,
			"credit_ipd":credit_ipd,
			'<?=$this->security->get_csrf_token_name()?>':csrf_value
			}, function(data){
                alert(data.remark);
        	},'json');
		}else{
			alert('Select Credit Type ');
		}
	  }
</script>
</div>