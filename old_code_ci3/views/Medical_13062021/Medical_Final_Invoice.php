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
		<div class="row">
			<input type="hidden" id="pid" name="pid" value="<?=$invoiceMaster[0]->patient_id ?>" />
			<input type="hidden" id="med_invoice_id" name="med_invoice_id" value="<?=$invoiceMaster[0]->id ?>" />
			<div class="col-md-12">
				<p><strong>Name :</strong>
				<?=$invoiceMaster[0]->inv_name?>
				<strong>/ P Code :</strong><?=$invoiceMaster[0]->patient_code?> 
				<strong>/ Invoice No. :</strong><?=$invoiceMaster[0]->inv_med_code?>
				<strong>/ Date :</strong> <?=MysqlDate_to_str($invoiceMaster[0]->inv_date)?>
				<?php if($invoiceMaster[0]->ipd_id > 0) { ?>
					<strong>IPD Code :</strong>
					<a href="javascript:load_form_div('/Medical/list_med_inv/<?=$ipd_master[0]->id ?>','maindiv');" >
						<?=$ipd_master[0]->ipd_code?>
					</a>
					<strong>Admit Date : </strong><?=$ipd_list[0]->str_register_date ?> <?=$ipd_list[0]->reg_time ?>
					<strong>/ Doctor :</strong><?=$ipd_list[0]->doc_name?>
					<strong>/ TPA-Org. :</strong><?=$ipd_list[0]->admit_type?> 
					<strong>/ Bill Type :</strong><?=($invoiceMaster[0]->ipd_credit)?'Credit To Hospital':'CASH/Direct'?>
					<?php }elseif($invoiceMaster[0]->case_id > 0){  ?>
						<strong>/ Org. Case ID :<a href="javascript:load_form_div('/Medical/list_med_orginv/<?=$OCaseMaster[0]->id ?>/<?=$invoiceMaster[0]->store_id?>','maindiv');" > 
									<?=$OCaseMaster[0]->case_id_code ?>
								</a>
					<?php } ?>
				<?php if($invoiceMaster[0]->med_group_id==0){  ?>
							<button  type="button" class="btn btn-warning btn-xs" onclick="edit_invoice('<?=$invoiceMaster[0]->id ?>')" >Open Bill For Edit</button>
				<?php }  ?>
				</p>
			</div>
		</div>
    </div>
	<div class="box-body">
		<div class="row " id="show_item_list">
			<div class="col-md-12">
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
				</table >
			</div>
		<?php if($invoiceMaster[0]->group_invoice_id==0) { ?>
			<div class="col-md-12">
				<table class="table table-striped ">
					<tr>
						<th style="width: 10px">#</th>
						<th>Deduction</th>
						<th><input  class="form-control" name="input_dis_desc" id="input_dis_desc" placeholder="Ded. Desc." value="<?=$invoiceMaster[0]->discount_remark ?>" type="text" /> </th>
						<th><input style="width: 100px" class="form-control" name="input_dis_amt" id="input_dis_amt" placeholder="Amount" value="<?=$invoiceMaster[0]->discount_amount ?>" type="text" /></th>
						<th><button type="button" class="btn btn-primary" id="btn_update_ded">Update</button></th>
					</tr>
				</table>
			</div>
		<?php } ?>
			<div class="col-md-12">
				<table class="table table-striped ">
					<tr>
						<th style="width: 10px">#<input type="hidden" name="hid_amount_recevied" id="hid_amount_recevied" value="<?=$invoiceMaster[0]->payment_received?>"></th>
						<th colspan="2">Amount received : <?=$invoiceMaster[0]->payment_received?></th>
						<th>Balance Amount : <?=$invoiceMaster[0]->payment_balance?></th>
						<th>Net Amount : <?=$invoiceMaster[0]->net_amount?></th>
						<th></th>
						<th></th>
					</tr>
				</table>
			</div>
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
						<?php if(($invoiceMaster[0]->ipd_credit==0 || $invoiceMaster[0]->ipd_credit=='')&& $invoiceMaster[0]->case_credit==0 && $invoiceMaster[0]->group_invoice_id==0) {  ?>
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
					</div> 
				</div>
			</div>
		</div>
      </div>
	<?php  } ?> 
	<div class="row no-print">
        <div class="col-xs-6">
          <a href="<?php echo '/Medical_Print/invoice_print_single_bill/'.$invoiceMaster[0]->id;  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print</a>
		  <a href="<?php echo '/Medical_Print/invoice_print_single_bill/'.$invoiceMaster[0]->id.'/1';  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print A6</Small></a>
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
			{ "mode":"1","med_invoice_id":inv_id,"input_amount_paid":$('#input_amount_paid').val(),
			'<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>'			}, function(data){
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
		"input_card_tran": $('#input_card_tran').val(),
		"input_amount_paid":$('#input_amount_paid').val(),
		'<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>'}, function(data){
        if(data.update==0)
                {
                    $('div.jsError').html(data.error_text);
					enable_btn();
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
			"ipd_id": $('#hidden_ipd_id').val(),
			'<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>'}, function(data){
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
			"input_dis_amt": $('#input_dis_amt').val(),
			'<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>'
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
			{ "mode":"5","med_invoice_id":inv_id,"input_amount_paid":$('#input_amount_paid').val(),
				'<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>'
				}, function(data){
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

	function edit_invoice(med_invoice_id){
		load_form_div('/Medical/Invoice_med_show/'+med_invoice_id,'maindiv'); 
	}

	function BillFinal(invoice_id){
		if(confirm("Are sure for This Action"))
		{
			var inv_id = $('#med_invoice_id').val();
			
			$.post('/Medical/InvBillFinal/'+inv_id,
			{ '<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>'}, function(data){
			if(data.update==0)
					{
						alert('Something Wrong');
					}else
					{
						alert('Update Success');
						load_form_div('/Medical/final_invoice/'+inv_id,'Medical_invoice_final'); 
					}
			},'json');
		}
	}
</script>
</div>