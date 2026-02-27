<div class="row">
	<div class="col-md-12">
		<div class="jsError"></div>
		<?php echo form_open('AddNew', array('role'=>'form','class'=>'form1')); ?>
		<div class="box">
				<div class="box-header">
					<h3 class="box-title">Product </h3>
					<small><i>New Product</i></small>
				</div>
				<div class="box-body">
					<input type="hidden" id="product_id" name="product_id" value="0" >
					<input type="hidden" id="related_drug_id" name="related_drug_id" value="0" >
					<div class="row">
						<div class="col-md-4"> 
							<div class="form-group">
								<div class="ui-widget">
									<label for="tags">Product Name</label>
									<input class="form-control input-sm" name="input_item_name" id="input_item_name" placeholder="Product Name" type="text" >
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-6"> 
							<div class="form-group">
								<div class="ui-widget">
									<label for="tags">Generic Name</label>
									<input class="form-control input-sm" name="input_genericname" id="input_genericname" placeholder="Generic Name" type="text" >
								</div>
							</div>
						</div>
						<div class="col-md-6">
							<label for="med_cat_id" class="control-label">Medicine Catagory</label>
							<div class="form-group">
								<select class="form-control select2" id=med_cat_id name="med_cat_id[]" multiple="multiple" data-placeholder="Select a Schedule, Blank for All"  >
									<?php 
									foreach($med_product_cat_master as $row){ ?>
										<option value='<?=$row->id?>'><?=$row->med_cat_desc?></option>
									<?php } ?>
								</select>
							</div>
						</div>
						<div class="col-md-3">
							<label for="comp_id" class="control-label">Formulation</label>
							<div class="form-group">
								<select name="input_formulation" id="input_formulation" class="form-control">
									<?php 
									foreach($med_formulation as $row)
									{
										echo '<option value="'.$row->formulation.'"  >'.$row->formulation_length.'</option>';
									} 
									?>
								</select>
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Packing</label>
								<input class="form-control number input-sm" name="input_packing_type" id="input_packing_type" placeholder="10 Tablets in Strip,1 Bottle" type="text" value=""  />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Re-Order Qty</label>
								<input class="form-control number input-sm" name="input_re_order_qty" id="input_re_order_qty" placeholder="Re-Order Qty " type="text" value="5"  />
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-2"> 
							<div class="form-group">
								<label>HSNCODE</label>
								<input class="form-control number input-sm" name="input_HSNCODE" id="input_HSNCODE" placeholder="HSN CODE" type="text" value="3004"   />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>CGST</label>
								<input class="form-control number input-sm" name="input_CGST" id="input_CGST" placeholder="CGST" type="text" value="6"  />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>SGST</label>
								<input class="form-control number input-sm" name="input_SGST" id="input_SGST" placeholder="SGST" type="text" value="6"  />
							</div>
						</div>
					</div>
					<div class="row">

						<div class="col-md-2"> 
							<div class="form-group">
								<label>Rack No.</label>
								<input class="form-control number input-sm" name="input_rack_no" id="input_rack_no" placeholder="Rack No." 
								type="text"   />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Shelf No</label>
								<input class="form-control number input-sm" name="input_shelf_no" id="input_shelf_no" placeholder="Shelf No" 
								type="text"   />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Cold Storage</label>
								<input class="form-control number input-sm" name="input_cold_storage" id="input_cold_storage" 
								placeholder="Cold Storage" 
								type="text"   />
							</div>
						</div>
						<div class="col-md-3">
							<label for="comp_id" class="control-label">Company Master</label>
							<div class="form-group">
								<select name="input_company_name" id="input_company_name" class="form-control">
									<?php 
									foreach($med_company as $company_master)
									{
										echo '<option value="'.$company_master->id.'"  >'.$company_master->company_name.'</option>';
									} 
									?>
								</select>
							</div>
						</div>
					</div>
				</div>
				<div class="box-footer">
					<div class="row">
						<div class="col-md-2"> 
							<div class="form-group">
								<label> </label>
								<button type="button" class="btn btn-primary" id="btn_update_stock" accesskey="A" ><u>A</u>dd Product in Master</button>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-2">
							<div class="form-group">
								<label>
								<input id="chk_ban_flag_id" name="chk_ban_flag_id" type="checkbox"  > 
								Banned Drug</label>
							</div>
							<div class="form-group">
								<label><input id="chk_batch_applicable" name="chk_batch_applicable" type="checkbox"  checked > Batch Applicable</label>
							</div>
							<div class="form-group">
								<label><input id="chk_is_continue" name="chk_is_continue" type="checkbox"  checked >Is Continue</label>
							</div>
							<div class="form-group">
								<label><input id="chk_exp_date_applicable" name="chk_exp_date_applicable" type="checkbox"  checked >Exp.Date Applicable</label>
							</div>
							<div class="form-group">
								<label>
								<input id="chk_narcotic" name="chk_narcotic" type="checkbox"  > 
								Narcotic</label>
							</div>
							<div class="form-group">
								<label>
								<input id="chk_schedule_h" name="chk_schedule_h" type="checkbox"  > 
								Schedule H</label>
							</div>
							<div class="form-group">
								<label>
								<input id="chk_schedule_h1" name="chk_schedule_h1" type="checkbox"  > 
								Schedule H1</label>
							</div>
							<div class="form-group">
								<label>
								<input id="chk_schedule_x" name="chk_schedule_x" type="checkbox" > 
								Schedule X</label>
							</div>
							<div class="form-group">
								<label>
								<input id="chk_schedule_g" name="chk_schedule_g" type="checkbox" > 
								Schedule G</label>
							</div>
							<div class="form-group">
								<label>
								<input id="chk_high_risk" name="chk_high_risk" type="checkbox" > 
								High Risk</label>
							</div>
						</div>
					</div>
				</div>
		</div>
		<?php echo form_close(); ?>
	</div>
</div>
<script>
	$(document).ready(function(){
		document.title='Drug Add in Master :Pharma';
	});

	function reset_input()
	{
		$("#input_item_name").val('');
		$("#input_formulation").val('');
		$("#input_packing_type").val('');
		$("#input_re_order_qty").val('0');
		$("#input_HSNCODE").val('');
		$("#input_CGST").val('');
		$("#input_SGST").val('');

		$("#input_company_name").val('');

	}

   $(document).ready(function(){

		$("#input_formulation").autocomplete({
		  source:"product_master/get_formulation_desc",
			minLength: 1,
			autofocus: true,
			select: function( event, ui ) {
				$("#input_formulation").val(ui.item.value);
			}
		});

		$("#input_genericname1").autocomplete({
			source:"product_master/get_genericname",
			minLength: 1,
			autofocus: true,
			select: function( event, ui ) {
				$("#input_genericname").val(ui.item.value);
			}
		});



		$('#btn_update_stock').click(function(){
			$.post('/index.php/product_master/product_master_update/'+$('#product_id').val(), $('form.form1').serialize(), function(data){
                if(data.is_update_stock==0)
                {
					notify('error','Please Attention',data.show_text);
                }else
                {
					notify('success','Please Attention','Update Successfully');
					load_form_div('/Product_master/NewProduct','searchresult');
                }
            }, 'json');
		});

	});


</script>