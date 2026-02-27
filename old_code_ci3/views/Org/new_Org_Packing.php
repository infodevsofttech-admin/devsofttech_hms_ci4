<br/>
<div class="row">
	<div class="col-md-12">
		<div class="jsError"></div>
		<div class="box">
				<div class="box-header">
				  <h3 class="box-title">New Org. Packing</h3>
				</div>
				<div class="box-body">
				<?php echo form_open('Org_Packing/NewPurchase', array('role'=>'form','class'=>'form1')); ?>
						<div class="row">
						<div class="col-md-2">
							<div class="form-group">
								<label>Bill Type</label>
								<select class="form-control input-sm" name="cbo_billtype" id="cbo_billtype"  >
									<option value="0"  >OPD Org.</option>
									<option value="1"  >IPD Org.</option>
								</select>
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<label>Packing Batch Number</label>
								<input class="form-control input-sm" name="input_packingcode" placeholder="Package Batch No" type="text"   />
							</div>
						</div>
						<div class="col-md-2">
							<label> Date of Packing</label>
							<div class="input-group date ">
								<div class="input-group-addon">
									<i class="fa fa-calendar"></i>
								</div>
								<input class="form-control pull-right datepicker input-sm" name="datepicker_packing" id="datepicker_packing" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask value="<?=date('d/m/Y') ?>"  />
							</div>
						</div>
						<div class="col-md-2">
							<button type="button" class="btn btn-primary" id="btn_update" accesskey="C" ><u>C</u>reate</button>
						</div>
					</div>
				<?php echo form_close(); ?>
				</div>
		</div>
	</div>
</div>
<script>
$(document).ready(function(){
	$('#btn_update').click(function(){
		$.post('/index.php/Org_Packing/CreatePackNew', $('form.form1').serialize(), function(data){
			if(data.insertid==0)
			{
				notify('error','Please Attention',data.show_text);
			}else
			{
				show_msg='Invoice Added : ID->'+ data.insertid;
				notify('success','Please Attention',show_msg);

				load_form_div('Org_Packing/PackNewEdit/'+data.insertid,'searchresult');
			}
		}, 'json');
	});
});

</script>