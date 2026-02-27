<div class="row">
	<div class="col-md-12">
		<h1>Discharge Process</h1>
	</div>
</div>

<div class="row">
	<div class="col-md-3">
                <div class="form-group">
                    <label>Discharge Date</label>
                    <div class="input-group date">
                        <div class="input-group-addon">
                            <i class="fa fa-calendar"></i>
                        </div>
                        <input id="dis_date" name="dis_date" class="form-control pull-right datepicker"  value="<?=MysqlDate_to_str($ipd_info[0]->discharge_date) ?>" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask="" readonly="true"   />
                    </div>
                </div>
              </div>
	<div class="col-md-3">
		<div class="form-group">
		  <label>Time:</label>
		  <div class="input-group">
			<input id="dis_time" name="dis_time" class="form-control timepicker" type="text" readonly="true" value="<?=$ipd_info[0]->discharge_time ?>" >
			<div  class="input-group-addon">
			  <i class="fa fa-clock-o"></i>
			</div>
		  </div>
		  <!-- /.input group -->
		</div>
	</div>
	
</div>
<div class="row">
	<div class="col-md-12">
		<div class="box">
	<div class="box-header">
	  <h3 class="box-title">Remarks
		
	  </h3>
	</div>
	<!-- /.box-header -->
	<div class="box-body pad">
		<?=$ipd_info[0]->discharge_remark ?>
	</div>
  </div>
	</div>
</div>
<div class="row">
	<div class="col-md-4">
		<div class="form-group">
			<label>Balance Amount Approved By</label>
			<input class="form-control" name="input_bal_user" id="input_bal_user" placeholder="Full Name" type="text"  value="<?=$ipd_info[0]->discharge_balance_user ?>" readonly="true" />
		</div>
	</div>
	<div class="col-md-6">
		<div class="form-group">
			<label>Balance Amount Remark</label>
			<input class="form-control" name="input_bal_remark" id="input_bal_remark" placeholder="" type="text" value="<?=$ipd_info[0]->discharge_balance_remark ?>" readonly="true" />
		</div>
	</div>
	<?php if($this->ion_auth->in_group('IPDReAdmit')) { ?>
		<div class="col-md-2">
			<div class="form-group">
			<label>ReAdmit</label>
				
				<button type="button" id="btn_admit" name="btn_admit" class="btn btn-primary form-control">ReAdmit</button>
				
			</div>
			<div class="jsError"></div>
		</div>
	<?php }  ?>
</div>
<script>
$('#btn_admit').click(function(){

			if(confirm("Are you sure you want to Re-Admit"))
			{
				var ipd_id=$('#Ipd_ID').val();
				var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();


				$.post('/index.php/Ipd/readmit/2', 
				{ "Ipd_ID": $('#Ipd_ID').val(),
					"<?=$this->security->get_csrf_token_name()?>":csrf_value
				}, function(data){
					alert('Admit Again Done');
					load_form_div('/ipd/ipd_account_panel/'+ipd_id,'account_status');
				});
			}
		
			
		});
</script>