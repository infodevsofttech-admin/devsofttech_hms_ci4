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
									<label for="tags">Product Description</label>
									<input class="form-control input-sm" name="input_genericname" id="input_genericname" placeholder="Description Name" type="text" >
								</div>
							</div>
						</div>
						<div class="col-md-3">
							<label for="comp_id" class="control-label">Category</label>
							<input class="form-control input-sm" name="input_formulation" id="input_formulation" placeholder="Description Name" type="text" autocomplete="TRUE" >
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
							<input class="form-control input-sm" name="input_company_name" id="input_company_name" placeholder="Company Name" type="text" >
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

		$('#btn_update_stock').click(function(){
			$.post('/index.php/product_stock_master/product_master_update/'+$('#product_id').val(), $('form.form1').serialize(), function(data){
                if(data.is_update_stock==0)
                {
					notify('error','Please Attention',data.show_text);
                }else
                {
					notify('success','Please Attention','Update Successfully');
					load_form_div('/product_stock_master/NewProduct','searchresult');
                }
            }, 'json');
		});

	});


</script>