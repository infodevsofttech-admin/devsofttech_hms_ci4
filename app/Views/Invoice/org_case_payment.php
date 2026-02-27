<form role="form" class="form1">
<?= csrf_field() ?>
<div class="panel panel-default">
	
	<div class="panel-body">
		<div class="row">
			<div class="col-md-12">
				<p>
					<strong>Name :</strong><?=$person_info[0]->p_fname?>  {<i><?=$person_info[0]->p_rname?></i>}
					<strong>/ Age :</strong><?=$person_info[0]->str_age?> 
					<strong>/ Gender :</strong><?=$person_info[0]->xgender?> 
					<strong>/ P Code :</strong><?=$person_info[0]->p_code?>
				</p>
				<input type="hidden" value="<?=$org_info[0]->id ?>" id="org_id" name="org_id" />
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<p>
					<strong>Org Code :</strong><?=$org_info[0]->case_id_code?>
					<strong>/ Org. Name :</strong><?=$org_info[0]->insurance_company_name?> 
					<strong>/ Card No :</strong><?=$org_info[0]->insurance_no?>
					 <?=$org_info[0]->insurance_no_1?> 
					 <?=$org_info[0]->insurance_no_2?>
				</p>
				<input type="hidden" value="<?=$org_info[0]->id ?>" id="org_id" name="org_id" />
			</div>
		</div>
		<?php if(count($ipd_info)>0){ ?>
			<div class="row">
				<div class="col-md-12">
					<p>
						<strong>IPD Code :</strong><?=$ipd_info[0]->ipd_code?> 
						<strong>/ Admit Date :</strong><?=$ipd_info[0]->str_register_date?> 
						<strong>/ Dis. Date :</strong><?=$ipd_info[0]->str_discharge_date?> 
						<strong>/ Charge Amt :</strong><?=$ipd_info[0]->charge_amount?>
						<strong>/ Medical Bill :</strong><?=$ipd_info[0]->med_amount?> 
					</p>
				</div>
			</div>
		<?php }  ?>
	</div>
</div>
<div class="form-group">
	<div class="panel panel-default">
		<div class="panel-heading">
			<h4 class="panel-title">Amount : </h4>
		</div>
		<div class="panel-body">
			<div class="col-md-6">
				<div class="form-group">
				  <label >Payment Bank Info</label>
					<input class="form-control varchar" id="input_pay_info" placeholder="Bank , NEFT Info" type="text" value="<?=$org_info[0]->amount_payment_info?>">
				 </div>
			</div>
			<div class="col-md-3">
				<div class="form-group">
					<label >Date of Payment</label>
					<div class="input-group date">
						<div class="input-group-addon">
						<i class="fa fa-calendar"></i>
						</div>
						<input class="form-control pull-right datepicker" name="datepicker_payment" id="datepicker_payment" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask="" value=<?=date('d/m/Y') ?>   />
					</div>
				</div>
			</div>
			<div class="col-md-3">
				<div class="form-group">
				  <label >Claim Amount</label>
					<input class="form-control number" id="input_Amount_Claim" placeholder="0.00" type="text" value="<?=$org_info[0]->amount_claim?>">
				 </div>
			</div>
			<div class="col-md-3">
				<div class="form-group">
				  <label >Amount Received</label>
					<input class="form-control number" id="input_Amount_r" placeholder="0.00" type="text" value="<?=$org_info[0]->amount_recived?>">
				 </div>
			</div>
			<div class="col-md-3">
				<div class="form-group">
				  <label >Amount Deduction</label>
					<input class="form-control number" id="input_Amount_d" placeholder="0.00" type="text" value="<?=$org_info[0]->amount_deduction?>">
				 </div>
			</div>
			<div class="col-md-3">
				<div class="form-group">
				  <label >TDS Amount</label>
					<input class="form-control number" id="input_Amount_TDS" placeholder="0.00" type="text" value="<?=$org_info[0]->amount_tds?>">
				 </div>
			</div>
			
		</div>
		<div class="panel-footer">
			<div class="row">
				<div class="col-md-3">  
					<button type="button" id="btn_update" class="btn btn-primary">Update</button>
				</div>
			</div>
		</div>
	</div>
</div>
</form>
<script>
$(document).ready(function(){
	$('#btn_update').click( function()
		{
			var amount_r=$('#input_Amount_r').val();
			var amount_d=$('#input_Amount_d').val();

			var amount_claim=$('#input_Amount_Claim').val();
			var amount_tds=$('#input_Amount_TDS').val();

			var date_payment=$('#datepicker_payment').val();
			var org_id=$('#org_id').val();
			var input_pay_info=$('#input_pay_info').val();

			var csrf_name = '<?= csrf_token() ?>';
			var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';
				
			if(confirm("Are you sure process this Payment "))
			{
				$.post('<?= base_url('billing/case/org_confirm_payment') ?>',{ 
				"amount_r":amount_r,
				"amount_d":amount_d,
				"amount_claim":amount_claim,
				"amount_tds":amount_tds,
				"org_id":org_id,
				"pay_info":input_pay_info,
				"date_payment":date_payment,
				[csrf_name]: csrf_value
				}, function(data){
				if(data.update==0)
						{
							$('.payModal-bodyc').html(data.error_text);
							
						}else
						{
							alert('Amount Update successfully');
						}
				},'json');
			}
		});
});
</script>