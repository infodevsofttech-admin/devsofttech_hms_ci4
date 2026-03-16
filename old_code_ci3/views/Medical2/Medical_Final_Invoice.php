<div id='Medical_invoice_final'>
<section class="content-header">
  <h1>
	Medical Invoice
	<small></small>
  </h1>
</section>
<section class="content">
<?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
<div class="box box-danger">
    <div class="box-header">
		<div class="box-title">
			<input type="hidden" id="pid" name="pid" value="<?=$invoiceMaster[0]->patient_id ?>" />
			<input type="hidden" id="med_invoice_id" name="med_invoice_id" value="<?=$invoiceMaster[0]->id ?>" />
        </div>
    </div>
	<div class="box-body">
		<div class="row">
			<div class="col-md-2">
				<div class="form-group">
					<label>Patient Code</label>
					<div class="form-control"  ><?=$invoiceMaster[0]->patient_code ?></div>
				</div>
			</div>
			<div class="col-md-2">
				<div class="form-group">
					<label>Customer Name</label>
					<div class="form-control"  ><?=$invoiceMaster[0]->inv_name ?></div>
				</div>
			</div>
			<div class="col-xs-2">
				<div class="form-group">
					<label>Doctor</label>
					<div class="form-control varchar"  ><?=$invoiceMaster[0]->doc_name?></div>
				</div>
			</div>
			<?php if($invoiceMaster[0]->ipd_id > 0) { ?>
			<div class="col-md-2">
				<div class="form-group">
					<label>IPD Code</label>
					<div class="form-control"  >
						<a href="javascript:load_form_div('/Medical/list_med_inv/<?=$invoiceMaster[0]->ipd_id ?>','maindiv');" > 
							<?=$invoiceMaster[0]->ipd_code ?>
						</a>
					</div>
				</div>
			</div>
			<div class="col-md-2">
				<div class="form-group">
					<label>Invoice Type</label>
					<div class="form-control" ><?=$invoiceMaster[0]->credit_status ?></div>
				</div>
			</div>
			<div class="col-xs-2">
				<div class="form-group">
					<label>IPD Remark</label>
					<input class="form-control varchar" name="input_remark_ipd" id="input_remark_ipd" placeholder="Any Remark for IPD" value="<?=$invoiceMaster[0]->remark_ipd?>" type="text"  />
				</div>
			</div>
			<?php }elseif($invoiceMaster[0]->case_id > 0) {  ?>
			<div class="col-md-3">
					<div class="form-group">
						<label>Case Code</label>
						<div class="form-control">
							<a href="javascript:load_form_div('/Medical/list_med_orginv/<?=$OCaseMaster[0]->id ?>','maindiv');" > 
								<?=$OCaseMaster[0]->case_id_code ?>
							</a>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="form-group">
						<label>Case Credit Status</label>
						<div class="form-control" ><?=$invoiceMaster[0]->credit_org_status ?></div>
					</div>
				</div>
			<?php }  ?>	
		</div>
		<div class="row " id="show_item_list">
		<table class="table table-striped ">
			<tr>
				<th style="width: 10px">#</th>
				<th>Item code</th>
				<th>Item Name</th>
				<th>Formulation</th>
				<th>Batch No</th>
				<th>Exp.</th>
				<th>Rate</th>
				<th>Qty.</th>
				<th>Price</th>
				<th>Disc.</th>
				<th>HSNCODE/C-SGST</th>
				<th>CGST</th>
				<th>SGST</th>
				<th>Amount</th>
			</tr>
			<?php
			
			$srno=0;
				foreach($inv_items as $row)
				{ 
					$srno=$srno+1;
					echo '<tr>';
					echo '<td>'.$srno.'</td>';
					echo '<td>'.$row->item_code.'</td>';
					echo '<td>'.$row->item_Name.'</td>';
					echo '<td>'.$row->formulation.'</td>';
					echo '<td>'.$row->batch_no.'</td>';
					echo '<td>'.$row->expiry.'</td>';
					echo '<td>'.$row->price.'</td>';
					echo '<td>'.$row->qty.'</td>';
					echo '<td>'.$row->amount.'</td>';
					echo '<td>'.$row->disc_amount.'</td>';
					echo '<td>'.$row->HSNCODE.'</td>';
					echo '<td>'.$row->CGST.'</td>';
					echo '<td>'.$row->SGST.'</td>';
					echo '<td>'.$row->tamount.'</td>';
					echo '</tr>';
				}
			echo '<input type="hidden" id="srno" name="srno" value="'.$srno.'" />';
			?>
			<!---- Total Show  ----->
			<tr>
				<th style="width: 10px">#</th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th>Gross Total</th>
				<th><?=$invoiceMaster[0]->gross_amount ?></th>
				<th><?=$invoiceMaster[0]->inv_disc_total?></th>
				<th></th>
				<th><?=$invoiceMaster[0]->CGST_Tamount?></th>
				<th><?=$invoiceMaster[0]->SGST_Tamount?></th>
				<th><?=$invoiceMaster[0]->net_amount?></th>
			</tr>
			
			<tr>
				<th style="width: 10px">#</th>
				<th>Deduction</th>
				<th colspan=9><input  class="form-control" name="input_dis_desc" id="input_dis_desc" placeholder="Ded. Desc." value="<?=$invoiceMaster[0]->discount_remark ?>" type="text" /> </th>
				<th><input style="width: 100px" class="form-control" name="input_dis_amt" id="input_dis_amt" placeholder="Amount" value="<?=$invoiceMaster[0]->discount_amount ?>" type="text" /></th>
				<th><button type="button" class="btn btn-primary" id="btn_update_ded">Update</button></th>
			</tr>
			<tr>
				<th style="width: 10px">#<input type="hidden" name="hid_amount_recevied" id="hid_amount_recevied" value="<?=$invoiceMaster[0]->payment_received?>"></th>
				<th colspan="2">Amount received : <?=$invoiceMaster[0]->payment_received?></th>
				<th>Balance Amount : <?=$invoiceMaster[0]->payment_balance?></th>
				<th>Net Amount</th>
				<th><?=$invoiceMaster[0]->net_amount?></th>
				<th></th>
			</tr>
		</table>
		</div>
		
		      <!-- /.row -->
	 <?php if($invoiceMaster[0]->payment_balance<>0) {  ?>
      <div class="payment_type">
        <!-- accepted payments column -->
		<div class="jsError"></div>
		<div class="col-md-4">
			<div class="form-group">
				<label>Received Amount</label>
				<input class="form-control" name="input_amount_paid" id="input_amount_paid"  type="text" value="<?=$invoiceMaster[0]->payment_balance?>" />
			</div>
		</div>
        <div id="payment_type" class="row">
			 <div class="col-md-12">
                <div class="form-group">
				<label>Payment Mode</label>
					 <div class="panel-group" id="accordion">
						<?php if($invoiceMaster[0]->ipd_credit==0 && $invoiceMaster[0]->case_credit==0) {  ?>
						<?php if ($invoiceMaster[0]->payment_balance>0) {  ?>
						 <div class="panel panel-default">
							<div class="panel-heading">
							  <h4 class="panel-title">
								<a data-toggle="collapse" data-parent="#accordion" href="#collapse1">
								Cash</a>
							  </h4>
							</div>
							<div id="collapse1" class="panel-collapse collapse in">
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
						<?php }else if ($invoiceMaster[0]->payment_balance<0) { ?>
						<div class="panel panel-default">
							<div class="panel-heading">
							  <h4 class="panel-title">
								<a data-toggle="collapse" data-parent="#accordion" href="#collapse1">
								Cash Return</a>
							  </h4>
							</div>
							<div id="collapse1" class="panel-collapse collapse in">
							  <div class="panel-body">
								<button type="button" class="btn btn-primary" id="btn_update_return">Confirm Cash Return and Print Receipt</button>
							  </div>
							</div>
						</div>
						<?php }
						} 
						
						?>
						  <div class="panel panel-default">
							<div class="panel-heading">
							  <h4 class="panel-title">
								<a data-toggle="collapse" data-parent="#accordion" href="#collapse5">
								Print Pending Invoice info for Cash Counter</a>
							  </h4>
							</div>
							<div id="collapse3" class="panel-collapse collapse">
							  <div class="panel-body">
								<button type="button" class="btn btn-primary" id="btn_print">Print Invoice Info.</button>
							  </div>
							</div>
						  </div>
					</div> 
				</div>
			</div>
		</div>
      </div>
	<?php  } ?> 
	<div class="row no-print">
        <div class="col-xs-6">
          <a href="<?php echo '/Medical/invoice_print/'.$invoiceMaster[0]->id;  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print</a>
        </div>
     </div>
      <!-- /.row -->
	</div>
</div>
<?php echo form_close(); ?>
</section>
<!-- /.content -->

<script>
   $(document).ready(function(){
	   
	function enable_btn()
	{
		$('#btn_update1').attr('disabled', false);
		$('#btn_update2').attr('disabled', false);
		$('#btn_update_return').attr('disabled', false);
		
	}

	$('#btn_update1').click( function()
	{
		var inv_id = $('#med_invoice_id').val();
		
		$('#btn_update1').attr('disabled', true);
		$('#btn_update2').attr('disabled', true);
		
		if(confirm("Are you sure process this invoice "))
		{
		
			$.post('/index.php/Medical/confirm_payment',
			{ "mode":"1","med_invoice_id":inv_id,"input_amount_paid":$('#input_amount_paid').val() }, function(data){
			if(data.update==0)
				{
					$('div.jsError').html(data.error_text);
					setTimeout(enable_btn,5000);
				}else
				{
					load_form_div('/Medical/final_invoice/'+inv_id,'Medical_invoice_final');
				}
			},'json');
		}else{
			setTimeout(enable_btn,5000);
			return false;
		}
	});
	
	$('#btn_update2').click( function()
	{
		$('#btn_update1').attr('disabled', true);
		$('#btn_update2').attr('disabled', true);
		
		$.post('/index.php/Medical/confirm_payment',
		{ "mode":"2","med_invoice_id":$('#med_invoice_id').val(),
		"input_card_mac": $('#input_card_mac').val(),
		"input_card_bank": $('#input_card_bank').val(),
		"input_card_digit": $('#input_card_digit').val(),
		"input_card_tran": $('#input_card_tran').val() }, function(data){
        if(data.update==0)
                {
                    $('div.jsError').html(data.error_text);
                }else
                {
					$('div.payment_type').html(data.showcontent);
				}
		},'json');
	});
	
	$('#btn_update3').click( function()
	{
		$.post('/index.php/Medical/confirm_payment',
		{ "mode":"3","med_invoice_id":$('#med_invoice_id').val(),
			"ipd_id": $('#hidden_ipd_id').val()}, function(data){
        if(data.update==0)
                {
                    $('div.jsError').html(data.error_text);
                }else
                {
					$('div.payment_type').html(data.showcontent);
				}
		},'json');
	});
	
	$('#btn_update4').click( function()
	{
		$.post('/index.php/Medical/confirm_payment',
		{ "mode":"4","med_invoice_id":$('#med_invoice_id').val(),
			"case_id": $('#hidden_case_id').val()}, function(data){
        if(data.update==0)
                {
                    $('div.jsError').html(data.error_text);
                }else
                {
					$('div.payment_type').html(data.showcontent);
				}
		},'json');
	});
	
	$('#btn_update_ded').click( function()
	{
		$('#btn_update1').attr('disabled', true);
		$('#btn_update2').attr('disabled', true);
		
		var inv_id = $('#med_invoice_id').val();
		$.post('/index.php/Medical/update_discount',{ "med_invoice_id": inv_id, 
			"input_dis_desc": $('#input_dis_desc').val(), 
			"input_dis_amt": $('#input_dis_amt').val()
			 }, function(data){
				 load_form_div('/Medical/final_invoice/'+inv_id,'Medical_invoice_final');
				 setTimeout(enable_btn,1000);
			});
	});
	
	$('#btn_update_return').click( function(e)
	{
		var inv_id = $('#med_invoice_id').val();
		
		$('#btn_update1').attr('disabled', true);
		$('#btn_update2').attr('disabled', true);
		
		if(confirm("Are you sure process this invoice "))
		{
			$.post('/index.php/Medical/confirm_payment',
			{ "mode":"5","med_invoice_id":inv_id,"input_amount_paid":$('#input_amount_paid').val() }, function(data){
			if(data.update==0)
				{
					$('div.jsError').html(data.error_text);
					setTimeout(enable_btn,5000);
				}else
				{
					load_form_div('/Medical/final_invoice/'+inv_id,'Medical_invoice_final');
				}
			},'json');
		}else{
			setTimeout(enable_btn,5000);
			return false;
		}
	});
	
});
</script>
</div>