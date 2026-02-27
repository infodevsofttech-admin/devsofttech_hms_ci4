<?php echo form_open(); ?>
<div class="panel panel-default">
	<div class="panel-body">
		<div class="row">
			<div class="col-md-12">
				<p><strong>Name :</strong><?=$person_info[0]->p_fname?>  {<i><?=$person_info[0]->p_rname?></i>}
				<strong>/ Age :</strong><?=$person_info[0]->age?> 
				<strong>/ Gender :</strong><?=$person_info[0]->xgender?> 
				<strong>/ P Code :</strong><?=$person_info[0]->p_code?> 
				<strong>/ ORG. Code :</strong><?=$org_info[0]->case_id_code?> 
				</p>
			</div>
		</div>
	</div>
</div>
<div class="form-group" id="sub_panel">
	<div class="panel panel-default" >
		<div class="panel-heading">
			<h4 class="panel-title">Amount : </h4>
			<input type="hidden" id="req_payment_id" name="req_payment_id" value="<?=$req_payment_order[0]->id?>">
		</div>
		<div class="panel-body"  >
			<div class="row">
				<div class="col-md-3">
					<div class="form-group">
					<label for="input_Amount" class="col-sm-4 control-label">Amount</label>
					<div class="col-sm-8">
						<input class="form-control number" id="input_Amount" value="<?=$req_payment_order[0]->payment_amount?>" type="text" readonly="true">
					</div>
					</div>
				</div>
			</div>
			<div class="row">
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
</div>

<?php echo form_close(); ?>
<script>
$(document).ready(function(){
	
	
	//Timepicker
		$(".timepicker").timepicker({
			showInputs: false,
			minuteStep: 1,
			showMeridian:false
		});
		
		function enable_btn()
		{
			$('#btn_update1').attr('disabled', false);
			$('#btn_update2').attr('disabled', false);
		}
	
		
		$('.timepicker').timepicker().on('changeTime.timepicker', function(e) {
			$("#hid_res_time").val(e.time.hours+':'+e.time.minutes);
			console.log('The time is ' + e.time.value);
			console.log('The hour is ' + e.time.hours);
			console.log('The minute is ' + e.time.minutes);
			console.log('The meridian is ' + e.time.meridian);
		});

		$('#btn_update1').click( function()
		{
			var amount=$('#input_Amount').val();
			var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

						
			$('#btn_update1').attr('disabled', true);
			$('#btn_update2').attr('disabled', true);
			
			if(amount>0)
			{
				$.post('/index.php/Invoice/req_payment_process',{ 
				"mode":"1",
				"amount":amount,
				"req_payment_id":$('#req_payment_id').val(),
				"cash_remark":$('#input_cash_remark').val(),
				"<?=$this->security->get_csrf_token_name()?>":csrf_value
				},
				function(data){
				if(data.update==0)
					{
						$('#sub_panel').html(data.showcontent);
						setTimeout(enable_btn,5000);
					}else
					{
						$('#sub_panel').html(data.showcontent);
					}
				},'json');
			}else{
				setTimeout(enable_btn,20000);
				alert('Amount Should be greater then Zero (0)');
			}
			
		});
		
		$('#btn_update2').click( function()
		{
			var amount=$('#input_Amount').val();
			var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

						
			$('#btn_update1').attr('disabled', true);
			$('#btn_update2').attr('disabled', true);
			
			if(amount>0)
			{
			$.post('/index.php/Invoice/req_payment_process',{ 
			"mode":"2",
			"req_payment_id":$('#req_payment_id').val(),
			"amount":amount,
			"input_card_mac": $('#input_card_mac').val(),
			"input_card_bank": $('#input_card_bank').val(),
			"input_card_digit": $('#input_card_digit').val(),
			"input_card_tran": $('#input_card_tran').val(),
			"<?=$this->security->get_csrf_token_name()?>":csrf_value}, function(data){
				if(data.update==0)
					{
						$('#sub_panel').html(data.showcontent);
						setTimeout(enable_btn,5000);
					}else
					{
						$('#sub_panel').html(data.showcontent);
					}
			},'json');
			}else{
				setTimeout(enable_btn,5000);
				alert('Amount Should be greater then Zero (0)');
			}
		});
		
		
	});

</script>