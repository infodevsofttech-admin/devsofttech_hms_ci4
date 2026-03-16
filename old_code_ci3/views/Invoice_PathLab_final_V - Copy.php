<section class="content-header">
      <h1>
        Invoice
        <small>#<?=$invoice_master[0]->invoice_code ?></small>
      </h1>
      
	  <ol class="breadcrumb">
		<?php
			if($invoice_master[0]->ipd_id>0)
			{
				echo '<li><a href="javascript:load_form(\'/Ipd/ipd_panel/'.$invoice_master[0]->ipd_id.'\');"><i class="fa fa-dashboard"></i> IPD Panel</a></li>';
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
					if($invoice_master[0]->insurance_case_id>0)
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
        <div class="col-xs-12 table-responsive">
          <table class="table table-striped ">
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
				<th><?=$invoiceGtotal[0]->Gtotal?></th>
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
			<?php if ($invoice_master[0]->ipd_id<1) { ?>
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
      <!-- /.row -->
	 <?php 
	 if ($invoice_master[0]->ipd_id<1 && $invoice_master[0]->insurance_case_id<2 )
	 {		 
	 if ($invoice_master[0]->payment_part_balance>0  || $invoice_master[0]->payment_part_balance < 0 ) {  ?>
      <div class="payment_type">
	  <div class="row">
		<div class="col-md-4">
			<div class="form-group">
				<label>Received Amount</label>
				<input class="form-control number" name="input_amount_paid" id="input_amount_paid"  type="text" value="<?=$invoice_master[0]->payment_part_balance?>" />
				<input type="hidden" name="hid_bal_amount" id="hid_bal_amount" value="<?=$invoice_master[0]->payment_part_balance?>" />
			</div>
		</div>
	  </div>
        <!-- accepted payments column -->
		<div class="jsError"></div>
        <div id="payment_type" class="row">
			 <div class="col-md-12">
                <div class="form-group">
				<label>Payment Mode</label>
					 <div class="panel-group" id="accordion">
						<?php if(count($ipd_master)>0 && $invoice_master[0]->payment_status==0 ) {  ?>
						  <div class="panel panel-default">
							<div class="panel-heading">
							  <h4 class="panel-title">
								<a data-toggle="collapse" data-parent="#accordion" href="#collapse3">
								Credit to IPD</a>
							  </h4>
							</div>
							<div id="collapse3" class="panel-collapse collapse  collapse in">
							  <div class="panel-body">
								<div class="row">
									<div class="col-md-3">
										<div class="form-group">
											<label>IPD ID</label>
											<input class="form-control" id="input_ipd_id" placeholder="Claim ID"  type="text" value=<?=$ipd_master[0]->ipd_code  ?> autocomplete="off" readonly>
											<input type="hidden" id="hidden_ipd_id" value="<?=$ipd_master[0]->id  ?>" >
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label>Credit Confirm</label>
											<button type="button" class="btn btn-primary" id="btn_update3">Click here to Confirm</button>
										</div>
									</div>
								</div>
							  </div>
							</div>
						  </div>
						<?php  } elseif(count($case_master)>0 && $invoice_master[0]->payment_status==0 ){  ?>
							<div class="panel panel-default">
								<div class="panel-heading">
								  <h4 class="panel-title">
									<a data-toggle="collapse" data-parent="#accordion" href="#collapse4">
									Credit to Organization</a>
								  </h4>
								</div>
								<div id="collapse4" class="panel-collapse collapse">
									<div class="panel-body">
									  <?php if(count($case_master)>0) {  ?>
										<div class="row">
											<div class="col-md-3">
												<div class="form-group">
													<label>Case ID</label>
													<input class="form-control" id="input_case_id" placeholder="Claim ID"  type="text" value=<?=$case_master[0]->case_id_code  ?> autocomplete="off" readonly>
													<input type="hidden" id="hidden_case_id" value="<?=$case_master[0]->id  ?>" >
												</div>
											</div>
											<div class="col-md-3">
												<div class="form-group">
													<label>Credit Confirm</label>
													<button type="button" class="btn btn-primary" id="btn_update4">Click here to Confirm</button>
												</div>
											</div>
										</div>
									  <?php }else{ ?>
										<div class="row">
											<div class="col-md-3">
												<button onclick="load_form('/Patient/person_record/<?=$invoice_master[0]->attach_id ?>');" type="button" class="btn btn-primary">Create Case.</button> 
											</div>
										</div>
										<?php } ?>
									</div>
								</div>
							</div>
						<?php } ?>
						<?php if($IPD_Credit<1 ) {  ?>
							<?php if($invoice_master[0]->payment_part_balance>0)
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
											<div class="col-md-2">
												<div class="form-group">
													<label>Card Swipe Machine  </label>
													<input class="form-control" id="input_card_mac" placeholder="Card Swipe Machine Bank Name"  type="text" autocomplete="off">
												</div>
											</div>
											<div class="col-md-3">
												<div class="form-group">
													<label>Customer Bank Name  </label>
													<input class="form-control" id="input_card_bank" placeholder="Customer Bank Name"  type="text" autocomplete="off">
												</div>
											</div>
											<div class="col-md-2">
												<div class="form-group">
													<label>Last Four Digit of Card</label>
													<input class="form-control" id="input_card_digit" placeholder="Last Four Digit of Card"  type="text" autocomplete="off">
												</div>
											</div>
											<div class="col-md-3">
												<div class="form-group">
													<label>Tran. ID  </label>
													<input class="form-control" id="input_card_tran" placeholder="Card Tran.ID."  type="text" autocomplete="off">
												</div>
											</div>
											<div class="col-md-2">
												<div class="form-group">
													<label>Payment Confirm By Card</label>
													<button type="button" class="btn btn-primary" id="btn_update2">Confirm Payment</button>
												</div>
											</div>
										</div>
									  </div>
									</div>
								  </div>
							<?php }elseif($invoice_master[0]->payment_part_balance<0){   ?>
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
						<?php } ?>
							
						<?php } ?>
					</div> 
				</div>
			</div>
		</div>
      </div>
	 <?php }
	 }else{  ?> 
	
	<?php } ?>
     <div class="row no-print">
        <div class="col-xs-6">
			<a href="<?php echo '/PathLab/invoice_print/'.$invoice_master[0]->id;  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print Invoice</a>
		</div>
     </div>
	  <!-- /.row -->
	<?php if ($this->ion_auth->in_group('ChargeInvoiceUpdate') && ($invoice_master[0]->invoice_status==1 )) { ?>
	<hr />
		<div class="row">
			<div class="col-xs-12">
			<button type="button" class="btn btn-primary" id="btn_cancel_opd" onclick="load_form('/PathLab/IPD_Invoice_Edit/<?=$invoice_master[0]->id?>')" >Edit Invoice Items</button>
			
			<?php	if($invoice_master[0]->invoice_status==1) {  
			$credit_invoice=0;
			if($invoice_master[0]->insurance_case_id==0 && $invoice_master[0]->ipd_id==0)
			{
			?>
				
			<?php }  ?>
			<?php	if($invoice_master[0]->insurance_case_id==0) {  
						if(count($case_master)>0)
						{
							$credit_invoice=1;
			?>				<input type="hidden" id="hid_org_id" name="hid_org_id" value="<?=$case_master[0]->id?>" >
							<button type="button" class="btn btn-success" id="btn_cr_org">Credit To Org. [<?=$case_master[0]->case_id_code?>] </button>
			<?php 		}
				}  ?>
			<?php	if($invoice_master[0]->ipd_id==0) {  
						if(count($ipd_master)>0)
						{
							$credit_invoice=1;
			?>				
							<input type="hidden" id="hidden_ipd_id" value="<?=$ipd_master[0]->id  ?>" >
							<button type="button" class="btn btn-success" id="btn_cr_ipd">Credit To IPD [<?=$ipd_master[0]->ipd_code?>] </button>
			<?php 		}
				}  ?>

			<?php if($credit_invoice==0) { ?>	
				<button type="button" class="btn btn-success" id="btn_cancel_inv">Cancel Invoice</button>
			<?php }  ?>
			<?php }  ?>
			</div>
		</div>
		<div class="row">
			<div class="col-xs-12">
			<hr/>
			<?php	if($invoice_master[0]->payment_part_received>1)
			{
			?>
				<table class="table">
					<tr>
						<th style="width: 10px">#</th>
						<th>Deduction</th>
						<th colspan=3><input  class="form-control" name="input_dis_desc" id="input_dis_desc" placeholder="Ded. Desc." value="<?=$invoice_master[0]->discount_desc ?>" type="text" /> </th>
						<th><input style="width: 100px" class="form-control" name="input_dis_amt" id="input_dis_amt" placeholder="Amount" value="<?=$invoice_master[0]->discount_amount ?>" type="text" /></th>
						<th><button type="button" class="btn btn-primary" id="btn_update_ded">Update</button></th>
					</tr>
				</table>
			<?php } ?>
			</div>
		</div>
	<?php }  ?>
	 
</section>
<!-- /.content -->
<div class="clearfix"></div>

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
		
		alert('pamt:' + pamt + ' /ramt:'+ ramt);
		
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
				"input_amount_paid":$('#input_amount_paid').val()	}, function(data){
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
		
		alert('pamt:' + pamt + ' /ramt:'+ ramt);
		
		if(!(pamt<=ramt && pamt>0))
		{
			alert("Amount Should be Greater then 0 and Not Greater then Balance Amount");
			setTimeout(enable_btn,1000);
			return false;
		}
		
		$.post('/index.php/PathLab/confirm_payment',{ "mode":"2",
		"invoice_id":$('#invoice_id').val(),
		"input_card_mac": $('#input_card_mac').val(),
		"input_card_bank": $('#input_card_bank').val(),
		"input_card_digit": $('#input_card_digit').val(),
		"input_card_tran": $('#input_card_tran').val(),
		"input_amount_paid":$('#input_amount_paid').val() }, function(data){
        if(data.update==0)
                {
                    $('div.jsError').html(data.error_text);
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
		$.post('/index.php/PathLab/confirm_payment',{ 
		"mode":"3",
		"invoice_id":$('#invoice_id').val(),
		"ipd_id":$('#hidden_ipd_id').val()	}, function(data){
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
		$.post('/index.php/PathLab/confirm_payment',
		{ "mode":"4","invoice_id":$('#invoice_id').val(),
			"case_id": $('#hidden_case_id').val()}, function(data){
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
		$.post('/index.php/PathLab/update_discount',{ "invoice_id": $('#invoice_id').val(), 
			"input_dis_desc": $('#input_dis_desc').val(), 
			"input_dis_amt": $('#input_dis_amt').val()
			 }, function(data){
			load_form('/PathLab/showinvoice/'+$('#invoice_id').val());
			});
	});
	
	$('#btn_cr_org').click( function()
	{
		$.post('/index.php/PathLab/charge_crorg/'+$('#invoice_id').val()+'/'+$('#hid_org_id').val(),
		{ "oid": $('#oid').val(),"org_code_id": $('#hid_org_id').val()
			 }, function(data){
			load_form('/PathLab/showinvoice/'+$('#invoice_id').val());
			});
	});
	
	$('#btn_cr_ipd').click( function()
	{
		$.post('/index.php/PathLab/charge_crIPD/'+$('#invoice_id').val()+'/'+$('#hidden_ipd_id').val(),
		{ "inv_id": $('#invoice_id').val(),"ipd_code_id": $('#hidden_ipd_id').val()
			 }, function(data){
			load_form('/PathLab/showinvoice/'+$('#invoice_id').val());
			});
	});
	
	$('#btn_cancel_inv').click( function()
	{
		if(confirm('Are you sure to cancel this invoice'))
		{
		$.post('/index.php/PathLab/cancel_inv/'+$('#invoice_id').val(),
		{ "inv_id": $('#invoice_id').val() }, function(data){
			load_form('/PathLab/showinvoice/'+$('#invoice_id').val());
			});
		}
	});
	
	$('#btn_update_return').click( function()
	{
		$.post('/index.php/PathLab/charge_refund/'+$('#invoice_id').val()+'/'+$('#input_amount_paid').val(),
		{ "invoice_id": $('#invoice_id').val(),"input_amount_paid": $('#input_amount_paid').val()
			 }, function(data){
				alert('Refund Request has been Created');
				load_form('/PathLab/showinvoice/'+$('#invoice_id').val());
			});
	});
});

</script>