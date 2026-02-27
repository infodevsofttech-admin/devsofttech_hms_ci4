<?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
<section class="content-header">
      <h1>
        Invoice
        <small>#<?=$invoice_master[0]->invoice_code ?></small>
      </h1>
	  <ol class="breadcrumb">
		<?php
			if($invoice_master[0]->ipd_id>0)
			{
				echo '<li><a href="javascript:load_form(\'/IpdNew/ipd_panel/'.$invoice_master[0]->ipd_id.'\');"><i class="fa fa-dashboard"></i> IPD Panel</a></li>';
			}
		?>
		<li><a href="javascript:load_form('/Patient/person_record/<?=$invoice_master[0]->attach_id ?>');"><i class="fa fa-dashboard"></i> Person</a></li>
	  </ol>
    </section>
	<section class="invoice" >
      <div class="row invoice-info">
        <!-- /.col -->
        <div class="col-sm-6 invoice-col">
          To
          <address>
            <strong><?=$patient_master[0]->p_fname ?></strong><br>
			<?=$patient_master[0]->p_relative ?>  : <?=$patient_master[0]->p_rname ?><br>
            Gender : <?=$patient_master[0]->xgender ?><br>
            Age : <?=$patient_master[0]->age ?><br>
            Phone No : <?=$patient_master[0]->mphone1 ?>
          </address>
        </div>
        <!-- /.col -->
        <div class="col-sm-6 invoice-col">
          <b>Invoice #<?=$invoice_master[0]->invoice_code ?></b><br>
          <?php
				if($invoice_master[0]->insurance_id>1)
				{
					echo '<strong> Ins. Comp. :</strong>'.$insurance[0]->ins_company_name.'<br>';
					if($invoice_master[0]->insurance_case_id>0 && count($case_master)>0)
					{
						echo '<strong> Org.Case No. :</strong>'.$case_master[0]->case_id_code.'<br>';
					}
				}
			?>
          <b>Date :</b> <?=$invoice_master[0]->inv_date ?><br>
          <b>Patient ID :</b> <?=$patient_master[0]->p_code ?><br>
		  <b>Refer By :</b> <?=$invoice_master[0]->refer_by_other ?><br>
		  <input type="hidden" value="<?=$invoice_master[0]->id ?>" id="invoice_id" name="invoice_id" />
        </div>
        <!-- /.col -->
      </div>
      <!-- /.row -->

      <!-- Table row -->
      <div class="row">
        <div class="col-md-12 ">
          <table class="table table-striped table-responsive">
			<tr>
				<th style="width: 10px">#</th>
				<th>Charges Group</th>
				<th>Charge Name</th>
				<th>Rate</th>
				<th>Qty</th>
				<th>Amount</th>
				<th></th>
			</tr>
			<?php
			$srno=1;
				foreach($invoiceDetails as $row)
				{ 
					echo '<tr>';
					echo '<td>'.$srno.'</td>';
					echo '<td>'.$row->group_desc.'</td>';
					echo '<td>'.$row->item_name.'</td>';
					echo '<td>'.$row->item_rate.'</td>';
					echo '<td>'.$row->item_qty.'</td>';
					echo '<td>'.$row->item_amount.'</td>';
					$srno=$srno+1;
					echo '<td></td>';
					echo '</tr>';
				}
			?>
			<!---- Total Show  ----->
			<tr>
				<th style="width: 10px">#</th>
				<th></th>
				<th></th>
				<th></th>
				<th>Gross Total</th>
				<th><?=$invoice_master[0]->total_amount?></th>
				<th></th>
			</tr>
			<?php if($invoice_master[0]->payment_status==0 ) {  ?>
			<tr>
				<th style="width: 10px">#</th>
				<th>Deduction</th>
				<th colspan=3><input  class="form-control" name="input_dis_desc" id="input_dis_desc" placeholder="Ded. Desc." value="<?=$invoice_master[0]->discount_desc ?>" type="text" /> </th>
				<th><input style="width: 100px" class="form-control" name="input_dis_amt" id="input_dis_amt" placeholder="Amount" value="<?=$invoice_master[0]->discount_amount ?>" type="text" /></th>
				<th><button type="button" class="btn btn-primary" id="btn_update_ded">Update</button></th>
			</tr>
			<?php }else{ ?>
			<tr>
				<th style="width: 10px">#</th>
				<th>Deduction</th>
				<th colspan=3><?=$invoice_master[0]->discount_desc ?></th>
				
				<th><?=$invoice_master[0]->discount_amount ?></th>
				<th></th>
			</tr>
			<?php } ?>
			<?php if ($invoice_master[0]->ipd_id==0) { ?>
			<tr>
				<th style="width: 10px">#<input type="hidden" name="hid_amount_recevied" id="hid_amount_recevied" value="<?=$invoice_master[0]->payment_part_received?>"></th>
				<th colspan="2">Amount received : <?=$invoice_master[0]->payment_part_received?></th>
				<th>Balance Amount : <?=$invoice_master[0]->payment_part_balance?></th>
				<th>Net Amount</th>
				<th><?=$invoice_master[0]->net_amount?></th>
				<th></th>
			</tr>
			<?php }else{  ?>
			<tr>
				<th style="width: 10px">#</th>
				<th colspan="2"></th>
				<th></th>
				<th>Net Amount</th>
				<th><?=$invoice_master[0]->net_amount?></th>
				<th></th>
			</tr>
			<?php } ?>
			</table>
		</div>
        <!-- /.col -->
      </div>
	  <div class="row">
		<div class="col-md-4">
			<div class="form-group">
				<label>Received Amount</label>
				<input class="form-control number" name="input_amount_paid" id="input_amount_paid"  type="text" value="<?=$invoice_master[0]->payment_part_balance?>" />
				<input type="hidden" name="hid_bal_amount" id="hid_bal_amount" value="<?=$invoice_master[0]->payment_part_balance?>" />
			</div>
		</div>
	  </div>
      <!-- /.row -->
	<?php if ($invoice_master[0]->invoice_status==1 ) { ?>
		<?php if ($invoice_master[0]->payment_status==1  ) { ?>
			<div class="row">
				<div class="col-md-12">
					<a href="<?php echo '/PathLab/invoice_PDF_print/'.$invoice_master[0]->id.'/0';  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print A4</a>
					<a href="<?php echo '/PathLab/invoice_PDF_print/'.$invoice_master[0]->id.'/1';  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print Double Copy</a>
				</div>
			</div>
		<?php } ?>
		<?php if ($invoice_master[0]->payment_mode==1 || $invoice_master[0]->payment_mode==2 || $invoice_master[0]->payment_mode==0  ) { ?>
			<hr/>
			<div class="panel-group" id="accordion">
			<?php 
				if($invoice_master[0]->payment_part_balance>0)
				{
			?>
					<div class="panel panel-default">
						<div class="panel-heading">
							<h4 class="panel-title">
							<a data-toggle="collapse" data-parent="#accordion" href="#collapse1">
							Cash</a>
							</h4>
						</div>
						<div id="collapse1" class="panel-collapse">
							<div class="panel-body">
							<button type="button" class="btn btn-primary" id="btn_update1">Confirm Cash Received and Print Receipt</button>
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
										<label>Payment By  </label>
										<select class="form-control" name="cbo_pay_type" id="cbo_pay_type" " >
											<?php
												foreach($bank_data as $row){
													echo '<option value="'.$row->id.'" > '.$row->pay_type.' ['.$row->bank_name.']'.'</option>';
												}
											?>
										</select>
									</div>
								</div>
								<div class="col-md-4">
									<div class="form-group">
										<label>Tran. ID/Ref.  </label>
										<input class="form-control" id="input_card_tran" placeholder="Card Tran.ID."  type="text" autocomplete="off">
									</div>
								</div>
								<div class="col-md-3">
									<div class="form-group">
										<label>Payment Confirm By Card/Online</label>
										<button type="button" class="btn btn-primary form-control" id="btn_update2">Confirm Payment</button>
									</div>
								</div>
							</div>
							</div>
						</div>
					</div>
				<?php 
					}elseif($invoice_master[0]->payment_part_balance<0){   
				?>
					<div class="panel panel-default">
						<div class="panel-heading">
							<h4 class="panel-title">
							<a data-toggle="collapse" data-parent="#accordion" href="#collapse1">
							Cash Return</a>
							</h4>
						</div>
						<div id="collapse1" class="panel-collapse collapse in">
							<div class="panel-body">
							<button type="button" class="btn btn-primary" id="btn_update_return">Confirm Cash Return Request</button>
							</div>
						</div>
					</div>
			<?php 
				}else{ 
					if($invoice_master[0]->total_amount>0 && $invoice_master[0]->payment_mode==0){	
			?>
					<div class="panel panel-default">
						<div class="panel-heading">
							<h4 class="panel-title">
							<a data-toggle="collapse" data-parent="#accordion" href="#collapse1">
							Zero Amount </a>
							</h4>
						</div>
						<div id="collapse1" class="panel-collapse collapse in">
							<div class="panel-body">
							<button type="button" class="btn btn-primary" id="btn_zero_confirm">Confirm Zero Amount Invoice</button>
							</div>
						</div>
					</div>
			<?php 
					}
				} 
			?>
			</div>
		<?php } ?>
		<div class="row">
		<div class="col-md-8"></div>
		<?php
			if($invoice_master[0]->insurance_case_id==0 && $invoice_master[0]->ipd_id==0)
			{
			?>
				
				<?php	
					if($invoice_master[0]->ipd_id==0) {  
						if(count($ipd_master)>0)
						{
				?>
						<div class="col-md-4">
							<input type="hidden" id="hidden_ipd_id" value="<?=$ipd_master[0]->id  ?>" >
							<button type="button" class="btn btn-success" id="btn_cr_ipd">Credit To IPD [<?=$ipd_master[0]->ipd_code?>] </button>
						</div>
				<?php 	}
					}
					
					if($invoice_master[0]->insurance_case_id==0) {
						if(count($case_master)>0)
						{  
				?>
						<div class="col-md-4">	
								<input type="hidden" id="hid_org_id" name="hid_org_id" value="<?=$case_master[0]->id?>" >
								<button type="button" class="btn btn-success" id="btn_cr_org">Credit To Org. [<?=$case_master[0]->case_id_code?>] </button>
							</div>
			<?php 
						}
					}
			}		
			?>
		</div>
	 	<?php if($this->ion_auth->in_group('ChargeInvoiceUpdate')) { ?>
		<div class="row">
		<hr/>
			<div class="col-md-4">
				<button type="button" class="btn btn-primary"  onclick="load_form('/PathLab/IPD_Invoice_Edit/<?=$invoice_master[0]->id?>')" >Edit Invoice Items</button>
			</div>
			<div class="col-md-4">
			<button type="button" class="btn btn-success" id="btn_cancel_inv">Cancel Invoice</button>
			</div>
    	</div>
		
		<?php if ($invoice_master[0]->payment_mode==1 || $invoice_master[0]->payment_mode==2 ) { ?>
		<hr/>
		<div class="row">
			<table class="table">
				<tr>
					<th style="width: 10px">#</th>
					<th>Deduction</th>
					<th colspan=3><input  class="form-control" name="input_dis_desc" id="input_dis_desc" placeholder="Ded. Desc." value="<?=$invoice_master[0]->discount_desc ?>" type="text" /> </th>
					<th><input style="width: 100px" class="form-control" name="input_dis_amt" id="input_dis_amt" placeholder="Amount" value="<?=$invoice_master[0]->discount_amount ?>" type="text" /></th>
					<th><button type="button" class="btn btn-primary" id="btn_update_ded">Update Correction</button></th>
				</tr>
			</table>
		</div>
		<?php }  ?>
	<?php }  ?>
<?php }  ?>
</section>
<?php echo form_close(); ?>
<!-- /.content -->
<script>

$(document).ready(function(){
	
	function enable_btn()
	{
		$('#btn_update1').attr('disabled', false);
		$('#btn_update2').attr('disabled', false);
	}
	
	$('#btn_update1').click( function(e)
	{
		$('#btn_update1').attr('disabled', true);
		$('#btn_update2').attr('disabled', true);
		
		var ramt=Number($('#hid_bal_amount').val());
		var pamt=Number($('#input_amount_paid').val());

		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
		
		if(!(pamt<=ramt && pamt>0))
		{
			alert("Amount Should be Greater then 0 and Not Greater then Balance Amount");
			setTimeout(enable_btn,1000);
			return false;
		}
		
		if(confirm("Are you sure process this invoice "))
			{
				$.post('/index.php/PathLab/confirm_payment',
				{ "mode":"1",
				"invoice_id":$('#invoice_id').val(),
				"input_amount_paid":$('#input_amount_paid').val(),
				'<?=$this->security->get_csrf_token_name()?>':csrf_value
				}, function(data){
				if(data.update==0)
						{
							$('div.jsError').html(data.error_text);
							setTimeout(enable_btn,5000);
						}else
						{
							load_form('/PathLab/showinvoice/'+$('#invoice_id').val());
						}
				},'json');	
			}else{
				setTimeout(enable_btn,5000);
				return false;
			}
		
	});

	$('#btn_update2').click( function()
	{
		$('#btn_update2').attr('disabled', true);
		$('#btn_update1').attr('disabled', true);
		
		var ramt=Number($('#hid_bal_amount').val());
		var pamt=Number($('#input_amount_paid').val());

		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
		
		if(!(pamt<=ramt && pamt>0))
		{
			alert("Amount Should be Greater then 0 and Not Greater then Balance Amount");
			setTimeout(enable_btn,1000);
			return false;
		}
		
		$.post('/index.php/PathLab/confirm_payment',{ "mode":"2",
		"invoice_id":$('#invoice_id').val(),
		"cbo_pay_type": $('#cbo_pay_type').val(),
		"input_card_tran": $('#input_card_tran').val(),
		"input_amount_paid":$('#input_amount_paid').val(),
		'<?=$this->security->get_csrf_token_name()?>':csrf_value }, function(data){
        if(data.update==0)
                {
                    //$('div.jsError').html(data.error_text);
					notify('error','Please Attention',data.error_text);	
					setTimeout(enable_btn,5000);
                }else
                {
					load_form('/PathLab/showinvoice/'+$('#invoice_id').val());
				}
		},'json');
		
		setTimeout(enable_btn,5000);
		
	});
	
	$('#btn_update3').click( function()
	{
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		$.post('/index.php/PathLab/confirm_payment',{ 
			"mode":"3",
			"invoice_id":$('#invoice_id').val(),
			"ipd_id":$('#hidden_ipd_id').val(),
			"<?=$this->security->get_csrf_token_name()?>":csrf_value
		}, function(data){
        if(data.update==0)
                {
                    $('div.jsError').html(data.error_text);
                }else
                {
					load_form('/PathLab/showinvoice/'+$('#invoice_id').val());
				}
		},'json');
	});
	
	$('#btn_update4').click( function()
	{
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		$.post('/index.php/PathLab/confirm_payment',
		{ "mode":"4","invoice_id":$('#invoice_id').val(),
			"case_id": $('#hidden_case_id').val(),
		"<?=$this->security->get_csrf_token_name()?>":csrf_value}, function(data){
        if(data.update==0)
                {
                    $('div.jsError').html(data.error_text);
                }else
                {
					$('div.payment_type').html(data.showcontent);
					load_form('/PathLab/showinvoice/'+$('#invoice_id').val());
				}
		},'json');
	});
	
	$('#btn_update_ded').click( function()
	{
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		$.post('/index.php/PathLab/update_discount',{ "invoice_id": $('#invoice_id').val(), 
			"input_dis_desc": $('#input_dis_desc').val(), 
			"input_dis_amt": $('#input_dis_amt').val(),
			"<?=$this->security->get_csrf_token_name()?>":csrf_value
			 }, function(data){
			load_form('/PathLab/showinvoice/'+$('#invoice_id').val());
			});
	});
	
	$('#btn_cr_org').click( function()
	{
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		$.post('/index.php/PathLab/charge_crorg/'+$('#invoice_id').val()+'/'+$('#hid_org_id').val(),
		{ "oid": $('#oid').val(),"org_code_id": $('#hid_org_id').val(),
		"<?=$this->security->get_csrf_token_name()?>":csrf_value
			 }, function(data){
			load_form('/PathLab/showinvoice/'+$('#invoice_id').val());
			});
	});
	
	$('#btn_cr_ipd').click( function()
	{
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		$.post('/index.php/PathLab/charge_crIPD/'+$('#invoice_id').val()+'/'+$('#hidden_ipd_id').val(),
		{ "inv_id": $('#invoice_id').val(),"ipd_code_id": $('#hidden_ipd_id').val(),
		"<?=$this->security->get_csrf_token_name()?>":csrf_value
			 }, function(data){
			load_form('/PathLab/showinvoice/'+$('#invoice_id').val());
			});
	});
	
	$('#btn_cancel_inv').click( function()
	{
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		if(confirm('Are you sure to cancel this invoice'))
		{
		$.post('/index.php/PathLab/cancel_inv/'+$('#invoice_id').val(),
			{ 	"inv_id": $('#invoice_id').val(),
				"<?=$this->security->get_csrf_token_name()?>":csrf_value }, 
				function(data){
					load_form('/PathLab/showinvoice/'+$('#invoice_id').val());
					});
		}
	});
	
	$('#btn_update_return').click( function()
	{
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		$.post('/index.php/PathLab/charge_refund/'+$('#invoice_id').val()+'/'+$('#input_amount_paid').val(),
		{ "invoice_id": $('#invoice_id').val(),"input_amount_paid": $('#input_amount_paid').val(),
		"<?=$this->security->get_csrf_token_name()?>":csrf_value
			 }, function(data){
				alert('Refund Request has been Created');
				load_form('/PathLab/showinvoice/'+$('#invoice_id').val());
			});
	});

	$('#btn_zero_confirm').click( function()
	{
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		if(confirm("Are you sure process this invoice "))
		{
			$.post('/index.php/PathLab/confirm_payment',
			{ "mode":"5",
			"invoice_id":$('#invoice_id').val(),
			"input_amount_paid":$('#input_amount_paid').val(),
			'<?=$this->security->get_csrf_token_name()?>':csrf_value
			}, function(data){
			if(data.update==0)
					{
						$('div.jsError').html(data.error_text);
						setTimeout(enable_btn,5000);
					}else
					{
						load_form('/PathLab/showinvoice/'+$('#invoice_id').val());
					}
			},'json');	
		}else{
			setTimeout(enable_btn,5000);
		}
	});
});

</script>