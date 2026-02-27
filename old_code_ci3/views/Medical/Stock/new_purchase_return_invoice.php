<br/>
<div class="row">
	<div class="col-md-12">
		<div class="jsError"></div>
		<div class="box">
				<div class="box-header">
				  <h3 class="box-title">New Purchase Invoice</h3>
				</div>
				<div class="box-body">
				<?php echo form_open('Medical/NewPurchase', array('role'=>'form','class'=>'form1')); ?>
						<div class="row">
						<div class="col-md-4">
							<div class="form-group">
							<label>Supplier</label>
								<select class="form-control input-sm" id="input_supplier" name="input_supplier"  >	
									<?php 
									foreach($supplier_data as $row)
									{ 
										echo '<option value='.$row->sid.' >'.$row->name_supplier.'</option>';
									}
									?>
								</select>
							</div>
						</div>
						<div class="col-md-3">
							<label> Date of Invoice</label>
							<div class="input-group date input-sm">
								<div class="input-group-addon">
									<i class="fa fa-calendar"></i>
								</div>
								<input class="form-control pull-right datepicker input-sm" name="datepicker_invoice" id="datepicker_invoice" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask value="<?=date('d/m/Y') ?>"  />
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
		$.post('/index.php/Medical_backpanel/CreatePurchaseReturn', $('form.form1').serialize(), function(data){
			if(data.insertid==0)
			{
				notify('error','Please Attention',data.show_text);
				
			}else
			{
				show_msg='Return Invoice : ID->'+ data.insertid;
				notify('success','Please Attention',show_msg);

				load_form_div('Medical_backpanel/PurchaseReturnInvoiceEdit/'+data.insertid,'searchresult');
			}
		}, 'json');
	});
});

</script>