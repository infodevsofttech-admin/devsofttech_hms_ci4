<div class="panel panel-default">
	<div class="panel-body">
		<div class="row">
			<div class="col-md-12">
				<p><strong>Name :</strong><?=$person_info[0]->p_fname?>  {<i><?=$person_info[0]->p_rname?></i>}
				<strong>/ Age :</strong><?=$person_info[0]->age?> 
				<strong>/ Gender :</strong><?=$person_info[0]->xgender?> 
				<strong>/ P Code :</strong><?=$person_info[0]->p_code?> 
				<strong>/ IPD Code :</strong><?=$ipd_info[0]->ipd_code?> 
				<strong>/ No of Days :</strong><?=$ipd_info[0]->no_days?> 
				</p>
			</div>
		</div>
	</div>
</div>
<div class="form-group">
<div class="panel panel-default">
	<div class="panel-heading">
		<h4 class="panel-title">Amount : </h4>
	</div>
	<div class="panel-body">
		<div class="col-md-3">
			<div class="form-group">
			<label for="datepicker_payment" class="col-sm-4 control-label">Date of Payment</label>
				<div class="col-sm-8">
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
			  <select id="cbo_cr_dr" name="cbo_cr_dr" class="form-control">
			  	<option value="0" selected="selected"> Credit </option>
			  	<option value="1">Debit</option>
			  </select>
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
		<div class="panel panel-default">
			<div class="panel-heading">
			  <h4 class="panel-title">
				<a data-toggle="collapse" data-parent="#accordion" href="#collapse3">
				Bank Deposit or NEFT or Cheque No.</a>
			  </h4>
			</div>
			<div id="collapse3" class="panel-collapse collapse ">
			  <div class="panel-body">
					<div class="col-md-4">
						<div class="form-group">
							<label>Name of Person  </label>
							<input class="form-control" id="input_person_name" placeholder="Person Name"  type="text" >
						</div>
					</div>
					<div class="col-md-4">
						<div class="form-group">
							<label>From Bank:Customer Bank Name  </label>
							<input class="form-control" id="input_bank_name" placeholder="Customer Bank Name"  type="text" autocomplete="off">
						</div>
					</div>
					<div class="col-md-4">
						<div class="form-group">
							<label>To Bank : Hospital Bank Name</label>
							<input class="form-control" id="input_bank_hospital" placeholder="Hospital Bank Name"  type="text" autocomplete="off">
						</div>
					</div>
					<div class="col-md-4">
						<div class="form-group">
							<label>Tran. ID or UTR No. or Cheque No. </label>
							<input class="form-control" id="input_bank_tran" placeholder="Tran. ID or UTR No."  type="text" autocomplete="off">
						</div>
					</div>
					<div class="col-md-4">
						<div class="form-group">
							<label>Payment Confirm By Bank Transfer</label>
							<button type="button" class="btn btn-primary" id="btn_update3">Confirm Payment</button>
						</div>
					</div>
				
			  </div>
			</div>
		</div>
	</div>
</div>
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
			var date_payment=$('#datepicker_payment').val();
			var time_payment=$('#hid_res_time').val();
			var cr_dr=$('#cbo_cr_dr').val();
			
			$('#btn_update1').attr('disabled', true);
			$('#btn_update2').attr('disabled', true);
			
			if(amount>0)
			{
				$.post('/index.php/Ipd/ipd_confirm_payment',{ 
				"mode":"1",
				"amount":amount,
				"ipd_id":$('#Ipd_ID').val(),
				"cash_remark":$('#input_cash_remark').val(),
				"date_payment":date_payment,
				"time_payment":time_payment,
				"cr_dr":cr_dr},
				function(data){
				if(data.update==0)
					{
						$('.payModal-bodyc').html(data.error_text);
						setTimeout(enable_btn,5000);
					}else
					{
						var ipd_id=data.ipd_id;
						var payid=data.payid;
						load_form_div('/Ipd/ipd_cash_print/'+ipd_id+'/'+payid,'payModal-bodyc');
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
			var date_payment=$('#datepicker_payment').val();
			var time_payment=$('#hid_res_time').val();
			
			$('#btn_update1').attr('disabled', true);
			$('#btn_update2').attr('disabled', true);
			
			if(amount>0)
			{
			$.post('/index.php/Ipd/ipd_confirm_payment',{ 
			"mode":"2",
			"amount":amount,
			"ipd_id":$('#Ipd_ID').val(),
			"input_card_mac": $('#input_card_mac').val(),
			"input_card_bank": $('#input_card_bank').val(),
			"input_card_digit": $('#input_card_digit').val(),
			"input_card_tran": $('#input_card_tran').val(),
			"date_payment":date_payment,
			"time_payment":time_payment,
				"cr_dr":cr_dr}, function(data){
			if(data.update==0)
					{
						$('.payModal-bodyc').html(data.error_text);
						setTimeout(enable_btn,5000);
					}else
					{
						var ipd_id=data.ipd_id;
						var payid=data.payid;
						load_form_div('/Ipd/ipd_cash_print/'+ipd_id+'/'+payid,'payModal-bodyc');
					}
			},'json');
			}else{
				setTimeout(enable_btn,5000);
				alert('Amount Should be greater then Zero (0)');
			}
		});
		
		$('#btn_update3').click( function()
		{
			var amount=$('#input_Amount').val();
			var date_payment=$('#datepicker_payment').val();
			var time_payment=$('#hid_res_time').val();
			
			$('#btn_update1').attr('disabled', true);
			$('#btn_update2').attr('disabled', true);
			
			if(amount>0)
			{
			$.post('/index.php/Ipd/ipd_confirm_payment',{ 
			"mode":"5",
			"amount":amount,
			"ipd_id":$('#Ipd_ID').val(),
			"input_person_name": $('#input_person_name').val(),
			"input_bank_name": $('#input_bank_name').val(),
			"input_bank_hospital": $('#input_bank_hospital').val(),
			"input_bank_tran": $('#input_bank_tran').val(),
			"datepicker_dot": $('#datepicker_dot').val(),
			"date_payment":date_payment,
			"time_payment":time_payment,
				"cr_dr":cr_dr
			}, function(data){
			if(data.update==0)
					{
						$('.payModal-bodyc').html(data.error_text);
						setTimeout(enable_btn,5000);
					}else
					{
						var ipd_id=data.ipd_id;
						var payid=data.payid;
						load_form_div('/Ipd/ipd_cash_print/'+ipd_id+'/'+payid,'payModal-bodyc');
					}
			},'json');
			}else{
				setTimeout(enable_btn,5000);
				alert('Amount Should be greater then Zero (0)');
			}
		});
		
	});

</script>