<div class="row">
	<div class="col-md-12">
		<h1>Discharge Process</h1>
	</div>
</div>

<div class="row">
	<div class="col-md-6">
		<div class="form-group">
			<label>Deduction Remark 1</label>
			<input class="form-control varchar" name="input_dis_remark" id="input_dis_remark" placeholder="Deduction Remark" value="<?=$ipd_info[0]->Discount_Remark?>" type="text"  />
		</div>
	</div>
	<div class="col-md-3">
		<div class="form-group">
			<label>Deduction Amount 1</label>
			<input class="form-control number" name="input_dis_Amount" id="input_dis_Amount" placeholder="0.00" value="<?=$ipd_info[0]->Discount?>" type="text"  />
		</div>
	</div>
	<div class="col-md-3">
		<div class="form-group">
			<label>Update Deduction 1</label>
			<button type="button" id="btn_dis_update" name="btn_dis_update" class="btn btn-primary form-control">Discount Update</button>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-md-6">
		<div class="form-group">
			<label>Deduction Remark 2</label>
			<input class="form-control varchar" name="input_dis_remark2" id="input_dis_remark2" placeholder="Deduction Remark" value="<?=$ipd_info[0]->Discount_Remark2?>" type="text"  />
		</div>
	</div>
	<div class="col-md-3">
		<div class="form-group">
			<label>Deduction Amount 2</label>
			<input class="form-control number" name="input_dis_Amount2" id="input_dis_Amount2" placeholder="0.00" value="<?=$ipd_info[0]->Discount2?>" type="text"  />
		</div>
	</div>
	<div class="col-md-3">
		<div class="form-group">
			<label>Update Deduction 2</label>
			<button type="button" id="btn_dis_update2" name="btn_dis_update2" class="btn btn-primary form-control">Discount Update</button>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-md-6">
		<div class="form-group">
			<label>Deduction Remark 3</label>
			<input class="form-control varchar" name="input_dis_remark3" id="input_dis_remark3" placeholder="Deduction Remark" value="<?=$ipd_info[0]->Discount_Remark3?>" type="text"  />
		</div>
	</div>
	<div class="col-md-3">
		<div class="form-group">
			<label>Deduction Amount 3</label>
			<input class="form-control number" name="input_dis_Amount3" id="input_dis_Amount3" placeholder="0.00" value="<?=$ipd_info[0]->Discount3?>" type="text"  />
		</div>
	</div>
	<div class="col-md-3">
		<div class="form-group">
			<label>Update Deduction 3</label>
			<button type="button" id="btn_dis_update3" name="btn_dis_update3" class="btn btn-primary form-control">Discount Update</button>
		</div>
	</div>
</div>
<hr>
<div class="row">
	<div class="col-md-6">
		<div class="form-group">
			<label>Addition Charge Remark 1</label>
			<input class="form-control varchar" name="input_charge_remark1" id="input_charge_remark1" placeholder="Charge Name" value="<?=$ipd_info[0]->charge1?>" type="text"  />
		</div>
	</div>
	<div class="col-md-3">
		<div class="form-group">
			<label>Addition Charge Amount</label>
			<input class="form-control number" name="input_charge_Amount1" id="input_charge_Amount1" placeholder="0.00" value="<?=$ipd_info[0]->chargeamount1?>" type="text"  />
		</div>
	</div>
	<div class="col-md-3">
		<div class="form-group">
			<label>Update Addition Charge</label>
			<button type="button" id="btn_charge_update1" name="btn_charge_update1" class="btn btn-primary form-control">Charge Update</button>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-md-6">
		<div class="form-group">
			<label>Addition Charge Remark 2</label>
			<input class="form-control varchar" name="input_charge_remark2" id="input_charge_remark2" placeholder="Charge Name" value="<?=$ipd_info[0]->charge2?>" type="text"  />
		</div>
	</div>
	<div class="col-md-3">
		<div class="form-group">
			<label>Addition Charge Amount</label>
			<input class="form-control number" name="input_charge_Amount2" id="input_charge_Amount2" placeholder="0.00" value="<?=$ipd_info[0]->chargeamount2?>" type="text"  />
		</div>
	</div>
	<div class="col-md-3">
		<div class="form-group">
			<label>Update Addition Charge</label>
			<button type="button" id="btn_charge_update2" name="btn_charge_update2" class="btn btn-primary form-control">Charge Update</button>
		</div>
	</div>
</div>
<hr>
<div class="row">
	<div class="col-md-3">
                <div class="form-group">
                    <label>Discharge Date</label>
                    <div class="input-group date">
                        <div class="input-group-addon">
                            <i class="fa fa-calendar"></i>
                        </div>
                        <input id="dis_date" name="dis_date" class="form-control pull-right datepicker" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask="" value=<?=date('d/m/Y') ?>  />
                    </div>
                </div>
              </div>
	<div class="col-md-3">
		<div class="form-group">
		  <label>Time:</label>
		  <div class="input-group">
			<input id="dis_time" name="dis_time" class="form-control timepicker" type="text" >
			<div  class="input-group-addon">
			  <i class="fa fa-clock-o"></i>
			</div>
		  </div>
		  <!-- /.input group -->
		</div>
	</div>
	<div class="col-md-3">
		<div class="form-group">
		<label>Patient Status</label>
		<select id="p_status" name="p_status" class="form-control" >
		<?php foreach($ipd_discharge_status as $row){ ?>
			<option value="<?=$row->id?>" <?=combo_checked($row->id,$ipd_info[0]->discarge_patient_status)?>  ><?=$row->status_desc?></option>
		<?php }  ?>
		</select>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-md-4">
		<div class="form-group">
			<label>Balance Amount Approved By</label>
			<input class="form-control" name="input_bal_user" id="input_bal_user" placeholder="Full Name" type="text"  />
		</div>
	</div>
	<div class="col-md-6">
		<div class="form-group">
			<label>Balance Amount Remark</label>
			<input class="form-control" name="input_bal_remark" id="input_bal_remark" placeholder="" type="text"  />
		</div>
	</div>
	<div class="col-md-2">
		<div class="form-group">
		<label>Update Discharge Status</label>
			<button type="button" id="btn_update_request" name="btn_update_request" class="btn btn-primary form-control">Update Discharge Request</button>
			<?php if($this->ion_auth->in_group('IPDDischarge')){
				if($ipd_info[0]->lock_medical==0) { ?>
				<button type="button" id="btn_update" name="btn_update" class="btn btn-primary form-control">Final Discharge</button>
			<?php }else{  ?>
				<b>Discharge Button Not Enable due to medical bill pending. Please contact Medical Store </b>
			<?php
			}}
			?>
		</div>
		<div class="jsError"></div>
	</div>
</div>
<script>
initfunc();

$(document).ready(function(){
        $('#btn_update').click(function(){
			var p_status=$('#p_status').val();
			
			if(p_status=="0")
			{
				alert("Please Select Patient Status.");
				return false;
			}
			
			if(confirm("Are you sure you want to discharge"))
			{
				$.post('/index.php/Ipd/discharge_update', 
				{ "Ipd_ID": $('#Ipd_ID').val(),
				"p_status": p_status,
				"discharge_remark": $('#discharge_remark').val(),
				"input_bal_user": $('#input_bal_user').val(),
				"input_bal_remark": $('#input_bal_remark').val(),
				"dis_date": $('#dis_date').val(),
				"dis_time": $('#dis_time').val(),
				"isadd":1 }, function(data){
					$('#btn_update').hide();
				});
			}else{
				 return false;
			}
		});
		
		
		$('#btn_update_request').click(function(){
			var p_status=$('#p_status').val();
			
			if(p_status=="0")
			{
				alert("Please Select Patient Status.");
				return false;
			}
			
			if(confirm("Are you sure you want to discharge request"))
			{
				$.post('/index.php/Ipd/discharge_request', 
				{ "Ipd_ID": $('#Ipd_ID').val(),
				"p_status": p_status,
				"dis_date": $('#dis_date').val(),
				"dis_time": $('#dis_time').val(),
				"isadd":1 }, function(data){
					$('#btn_update_request').hide();
				});
			}else{
				 return false;
			}
		});
		
		$('#btn_dis_update').click(function(){

			var ipd_id=$('#Ipd_ID').val();

			$.post('/index.php/Ipd/discount_update/1', 
			{ "Ipd_ID": $('#Ipd_ID').val(),
			"Discount_Remark": $('#input_dis_remark').val(),
			"Discount": $('#input_dis_Amount').val()
			}, function(data){
				alert('Discount Updated');
				load_form_div('/ipd/ipd_account_panel/'+ipd_id,'account_status');
			});

		});
		
		$('#btn_dis_update2').click(function(){
			var ipd_id=$('#Ipd_ID').val();
			$.post('/index.php/Ipd/discount_update/2', 
			{ "Ipd_ID": $('#Ipd_ID').val(),
			"Discount_Remark": $('#input_dis_remark2').val(),
			"Discount": $('#input_dis_Amount2').val()
			}, function(data){
				alert('Discount Updated');
				load_form_div('/ipd/ipd_account_panel/'+ipd_id,'account_status');
			});
		});
		
		$('#btn_dis_update3').click(function(){

			var ipd_id=$('#Ipd_ID').val();

			$.post('/index.php/Ipd/discount_update/3', 
			{ "Ipd_ID": $('#Ipd_ID').val(),
			"Discount_Remark": $('#input_dis_remark3').val(),
			"Discount": $('#input_dis_Amount3').val()
			}, function(data){
				alert('Discount Updated');
				load_form_div('/ipd/ipd_account_panel/'+ipd_id,'account_status');
			});
			
		});
		
		$('#btn_charge_update1').click(function(){

			var ipd_id=$('#Ipd_ID').val();

			$.post('/index.php/Ipd/charge_update/1', 
			{ "Ipd_ID": $('#Ipd_ID').val(),
			"input_charge_remark1": $('#input_charge_remark1').val(),
			"input_charge_Amount1": $('#input_charge_Amount1').val()
			}, function(data){
				alert('Charge Updated');
				load_form_div('/ipd/ipd_account_panel/'+ipd_id,'account_status');
			});
			
		});
		
		$('#btn_charge_update2').click(function(){

			var ipd_id=$('#Ipd_ID').val();

			$.post('/index.php/Ipd/charge_update/2', 
			{ "Ipd_ID": $('#Ipd_ID').val(),
			"input_charge_remark2": $('#input_charge_remark2').val(),
			"input_charge_Amount2": $('#input_charge_Amount2').val()
			}, function(data){
				alert('Charge Updated');
				load_form_div('/ipd/ipd_account_panel/'+ipd_id,'account_status');
			});
			
		});
		
		
		
});
</script>