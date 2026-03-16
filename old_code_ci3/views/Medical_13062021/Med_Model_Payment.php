
<div class="row">
<div class="col-md-12">
		<div class="panel panel-default">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-12">
						<p><strong>Name :</strong><?=$person_info[0]->p_fname?>  {<i><?=$person_info[0]->p_rname?></i>}
						<strong>/ Age :</strong><?=$person_info[0]->age?> 
						<strong>/ Gender :</strong><?=$person_info[0]->xgender?> 
						<strong>/ P Code :</strong><?=$person_info[0]->p_code?> 
						<strong>/ IPD Code :</strong>
						<a href="javascript:load_form_div('/Medical/list_med_inv/<?=$ipd_info[0]->id ?>','maindiv');" > 
							<?=$ipd_info[0]->ipd_code ?>
						</a>
						<strong>/ No of Days :</strong><?=$ipd_info[0]->no_days?> 
						<input type="hidden" id="pid" value="<?=$person_info[0]->id?>" />
						<input type="hidden" id="pname" value="<?=$person_info[0]->p_fname?>" />
						<input type="hidden" id="Ipd_ID" value="<?=$ipd_info[0]->id?>" />
						<input type="hidden" id="Med_Group_id" value="<?=$inv_med_group[0]->med_group_id?>" />
						</p>
					</div>
				</div>
			</div>
		</div>
</div>
</div>
<div class="row">
	<div class="col-md-8">
		<div id="show_contant">
		<div class="form-group">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h4 class="panel-title">Medical Amount : </h4>
			</div>
			<div class="panel-body">
				<div class="col-md-6">
					<div class="form-group">
					<label for="datepicker_payment" class="col-sm-4 control-label">Date of Payment</label>
						<div class="col-sm-6">
							<div class="input-group date">
							<div class="input-group-addon">
							<i class="fa fa-calendar"></i>
							</div>
							<input class="form-control pull-right datepicker" name="datepicker_payment" id="datepicker_payment" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask="" value=<?=date('d/m/Y') ?>   />
							</div>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="form-group">
					  <label for="cbo_cr_dr" class="col-sm-4 control-label">Amount</label>
					   <div class="col-sm-8">
						  <select id="cbo_cr_dr" name="cbo_cr_dr" class="form-control">
						  	<option value="0" selected="selected"> Credit </option>
						  	<option value="1">Debit</option>
						  </select>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="form-group">
					  <label for="input_Amount" class="col-sm-4 control-label">Amount</label>
					  <div class="col-sm-8">
						<input class="form-control number" id="input_Amount" placeholder="0.00" type="text">
					  </div>
					</div>
				</div>
			</div>
		</div>
		<div class="panel-group" id="accordion">
				<div class="panel panel-default">
					<div class="panel-heading">
					  <h4 class="panel-title">
						<a data-toggle="collapse" data-parent="#accordion" href="#collapse1">
						Cash</a>
					  </h4>
					</div>
					<div id="collapse1" class="panel-collapse collapse in">
					  <div class="panel-body">
						<div class="col-md-4">
								<div class="form-group">
									<label>Remark  </label>
									<input class="form-control" id="input_cash_remark" placeholder="Any remark"  type="text" autocomplete="off">
								</div>
							</div>
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
						
							<div class="col-md-4">
								<div class="form-group">
									<label>Card Swipe Machine  </label>
									<input class="form-control" id="input_card_mac" placeholder="Card Swipe Machine Bank Name"  type="text" autocomplete="off">
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>Customer Bank Name  </label>
									<input class="form-control" id="input_card_bank" placeholder="Customer Bank Name"  type="text" autocomplete="off">
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>Last Four Digit of Card</label>
									<input class="form-control" id="input_card_digit" placeholder="Last Four Digit of Card"  type="text" autocomplete="off">
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>Tran. ID  </label>
									<input class="form-control" id="input_card_tran" placeholder="Card Tran.ID."  type="text" autocomplete="off">
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>Payment Confirm By Card</label>
									<button type="button" class="btn btn-primary" id="btn_update2">Confirm Payment</button>
								</div>
							</div>

					  </div>
					</div>
				</div>
				
			</div>
		</div>
		</div>
	</div>
	<div class="col-md-4">
		<b>Payment Details </b>
		<table class="table table-striped ">
			<tr>
				<th>Pay.No.</th>
				<th>Mode</th>
				<th>Date </th>
				<th>Amount</th>
				<th></th>
			</tr>
			
			<?php
				foreach($payment_history as $row)
				{ 
					echo '<tr><td>'.$row->id.'</td><td>'.$row->Payment_type_str.'</td><td>'.$row->str_payment_date.'</td><td align="right">'.$row->paid_amount.'</td></tr>';
				}
			?>
		</table>
		<div class="row">
	<div class="col-xs-12 table-responsive">
		<?php if(count($inv_med_group)>0) { ?>
		<p>
		 <b>Pharmacy Bill Amount</b>: 
		 Rs. <?=$inv_med_group[0]->net_amount ?><br>
		 <b>Total Amount Paid : </b> Rs. <?=$inv_med_group[0]->payment_received ?>
		 </br>
		 <b>Balance Amount</b>: 
		 Rs. <?=$inv_med_group[0]->payment_balance ?><br>
		 <a href="<?php echo '/Medical/payment_receipt/'.$ipd_info[0]->id.'/0';  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print Cash</a>
		</p>
		
			<?php 
			$i=0;
			foreach($phone_number_list as $row) { 
				$i=$i+1;
			?>
				<div class="input-group input-group-sm">
					<input type="text" class="form-control number" id="in_phone_number_<?=$i?>" value=<?=$row?>>
						<span class="input-group-btn">
						<button type="button" class="btn btn-info btn-flat" onclick="send_sms(<?=$i?>)">Send SMS !</button>
						</span>
				</div>
			 <?php 	 } ?>
		<?php } ?>
	</div>
</div>
		
	</div>
</div>
<script>
function send_sms(phone_id)
{
	var phone_number=$('#in_phone_number_'+phone_id).val();

	if(phone_number.length==10 && !isNaN(phone_number))
	{
		$.post('/index.php/Medical/payment_receipt_sms/'+$('#Ipd_ID').val(),{ 
		"ipd_id":$('#Ipd_ID').val(),
		"phone_number":phone_number,
		'<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>'
		},
		function(data){
		if(data.update==0)
			{
				alert('Some Error');
			}else
			{
				alert('Message Send Request Done');
			}
		},'json');
	}else{
		alert('Phone No. Not Correct !');
	}
	
}


$(document).ready(function(){
	function enable_btn()
	{
		$('#btn_update1').attr('disabled', false);
		$('#btn_update2').attr('disabled', false);
		
	}

	
		$('#btn_update1').click( function()
		{
			var amount=$('#input_Amount').val();
			var date_payment=$('#datepicker_payment').val();
			var Med_Group_id=$('#Med_Group_id').val();
			var cr_dr=$('#cbo_cr_dr').val();
			
			$('#btn_update1').attr('disabled', true);
			$('#btn_update2').attr('disabled', true);
						
			if(amount>0)
			{
				if(confirm("Are you sure to made payment"))
				{
					$.post('/index.php/Medical/group_confirm_payment',{ 
					"mode":"1",
					"amount":amount,
					"Med_Group_id":Med_Group_id,
					"ipd_id":$('#Ipd_ID').val(),
					"cash_remark":$('#input_cash_remark').val(),
					"date_payment":date_payment,
					"cr_dr":cr_dr,
					'<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>'
					},
					function(data){
					if(data.update==0)
						{
							$('#show_contant').html(data.error_text);
						}else
						{
							var ipd_id=data.ipd_id;
							var payid=data.payid;
							alert('Payment Update');
							load_form_div('/Medical/load_model_box/'+ipd_id,'maindiv');
						}
					},'json');
				}else{
					setTimeout(enable_btn,5000);
				}
				
				
			}else{
				//setTimeout(enable_btn,5000);
				alert('Amount Should be greater then Zero (0)');
			}
			
		});
		
		$('#btn_update2').click( function()
		{
			var amount=$('#input_Amount').val();
			var date_payment=$('#datepicker_payment').val();
			var cr_dr=$('#cbo_cr_dr').val();
			var Med_Group_id=$('#Med_Group_id').val();
			
			var cr_dr=$('#cbo_cr_dr').val();
						
			$('#btn_update1').attr('disabled', true);
			$('#btn_update2').attr('disabled', true);
			
			if(amount>0)
			{
				$.post('/index.php/Medical/group_confirm_payment',{ 
				"mode":"2",
				"amount":amount,
				"Med_Group_id":Med_Group_id,
				"ipd_id":$('#Ipd_ID').val(),
				"input_card_mac": $('#input_card_mac').val(),
				"input_card_bank": $('#input_card_bank').val(),
				"input_card_digit": $('#input_card_digit').val(),
				"input_card_tran": $('#input_card_tran').val(),
				"date_payment":date_payment,
				"cr_dr":cr_dr,
				'<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>'
				}, function(data){
					if(data.update==0)
						{
							$('.payModal-bodyc').html(data.error_text);
							setTimeout(enable_btn,60000);
						}else
						{
							var ipd_id=data.ipd_id;
							var payid=data.payid;
							alert('Payment Update');
							load_form_div('/Medical/load_model_box/'+ipd_id,'maindiv');
						}
				},'json');
			}else{
				setTimeout(enable_btn,2000);
				alert('Amount Should be greater then Zero (0)');
			}
		});

		
		
	});

</script>