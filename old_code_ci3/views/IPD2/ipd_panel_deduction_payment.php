<?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
<div class="form-group">
<div class="panel panel-default">
	<div class="panel-heading">
		<h4 class="panel-title">Amount : </h4>
	</div>
	<div class="panel-body">
		<div class="col-md-12">
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
				<button type="button" class="btn btn-primary" id="btn_update_return">Confirm Cash Received and Print Receipt</button>
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
							<input class="form-control" id="input_person_name" placeholder="Person Name"  type="text" autocomplete="off">
						</div>
					</div>

					<div class="col-md-4">
						<div class="form-group">
							<label>To Bank:Customer Bank Name  </label>
							<input class="form-control" id="input_bank_name" placeholder="Customer Bank Name"  type="text" autocomplete="off">
						</div>
					</div>
					<div class="col-md-4">
						<div class="form-group">
							<label>From Bank : Hospital Bank Name</label>
							<input class="form-control" id="input_bank_hospital" placeholder="Hospital Bank Name"  type="text" autocomplete="off">
						</div>
					</div>
					<div class="col-md-4">
						<div class="form-group">
							<label>Tran. ID or UTR No. or Cheque No. </label>
							<input class="form-control" id="input_bank_tran" placeholder="Tran. ID or UTR No."  type="text" autocomplete="off">
						</div>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<label>Date of Tran.</label>
								<div class="input-group date">
								<div class="input-group-addon">
								<i class="fa fa-calendar"></i>
								</div>
								<input class="form-control pull-right datepicker" value="<?=date('d-m-Y') ?>" name="datepicker_dot" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask=""  />
								</div>
						</div>
					</div>
					<div class="col-md-4">
						<div class="form-group">
							<label>Payment Confirm By Bank Transfer</label>
							<button type="button" class="btn btn-primary" id="btn_update_return_bank">Confirm Payment</button>
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

		function disable_btn()
		{
			$('#btn_update1').attr('disabled', true);
			$('#btn_update2').attr('disabled', true);
		}
	

		$('#btn_update_return').click( function()
		{
			var amount=$('#input_Amount').val();
			var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
			if(amount>0)
			{
				if(confirm("Are you sure process this Payment "))
				{
					disable_btn();
					
					$.post('/index.php/IpdNew/ipd_ded_payment',{ 
					"mode":"3",
					"amount":amount,
					"ipd_id":$('#Ipd_ID').val(),
					"cash_remark":$('#input_cash_remark').val(),
					"<?=$this->security->get_csrf_token_name()?>":csrf_value}, 
					function(data){
					if(data.update==0)
						{
							$('.payModal_ded-bodyc').html(data.error_text);
						}else
						{
							var ipd_id=data.ipd_id;
							var payid=data.payid;
							load_report_div('/IpdNew/ipd_cash_print_pdf/'+ipd_id+'/'+payid,'payModal_ded-bodyc');
							
						}
					},'json');
				}
			}else{
				alert('Amount Should be greater then Zero (0)');
			}
			
		});
		
		$('#btn_update_return_bank').click( function()
		{
			var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
			var amount=$('#input_Amount').val();
			if(amount>0)
			{
			disable_btn();
			if(confirm("Are you sure process this Payment "))
				{
				$.post('/index.php/IpdNew/ipd_ded_payment',{ 
				"mode":"4",
				"amount":amount,
				"ipd_id":$('#Ipd_ID').val(),
				"input_deduction_remark": $('#input_deduction_remark').val(),
				"<?=$this->security->get_csrf_token_name()?>":csrf_value
				}, function(data){
				if(data.update==0)
						{
							$('.payModal_ded-bodyc').html(data.error_text);
						}else
						{
							
							var ipd_id=data.ipd_id;
							var payid=data.payid;
							load_report_div('/IpdNew/ipd_cash_print_pdf/'+ipd_id+'/'+payid,'payModal_ded-bodyc');
													
						}
				},'json');
				}
			}else{
				alert('Amount Should be greater then Zero (0)');
			}
		});
		
			
	});


</script>