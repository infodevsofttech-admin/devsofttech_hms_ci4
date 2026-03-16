<div class="row">
	<div class="col-md-12">
		<div class="jsError"></div>
		<div class="box">
				<div class="box-header">
				  <h3 class="box-title">Stock Information</h3>
				</div>
				<div class="box-body">
				<?php echo form_open('AddNew', array('role'=>'form','class'=>'form1')); ?>
				<input type="hidden" id="ss_no" name="ss_no" value="<?=$stock_item[0]->ss_no ?>" >
					<div class="row">
						<div class="col-md-2">
							<div class="form-group">
								<label>Product Code</label>
								<input class="form-control" name="input_product_code" id="input_product_code" placeholder="Product Code" type="text" value="<?=$stock_item[0]->item_code ?>" readonly />
							</div>
						</div>
						<div class="col-md-3">
							<div class="form-group">
								<label>Product Name</label>
								<input class="form-control" name="input_product_name" placeholder="Product Name" type="text" value="<?=$stock_item[0]->item_name ?>"  />
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<label>Formulation</label>
								<input class="form-control" name="input_formulation" id="input_formulation" placeholder="Formulation" type="text" value="<?=$stock_item[0]->formulation ?>"  />
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<label>Dosage</label>
								<input class="form-control" name="input_dosage" placeholder="Dosage" type="text" value="<?=$stock_item[0]->dosage ?>"  />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Product MRP</label>
								<input class="form-control number" name="input_product_mrp" placeholder="Product MRP" type="text" value="<?=$stock_item[0]->mrp ?>"  />
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-2">
							<div class="form-group">
								<label>Selling Price</label>
								<input class="form-control number" name="input_selling_price" placeholder="Selling Price" type="text" value="<?=$stock_item[0]->selling_price ?>"  />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Batch No.</label>
								<input class="form-control" name="input_batch_code" placeholder="Batch No." type="text" value="<?=$stock_item[0]->batch_no ?>"  />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Date of Expiry</label>
									<div class="input-group date">
										<div class="input-group-addon">
										<i class="fa fa-calendar"></i>
									</div>
										<input class="form-control pull-right datepicker" value="<?=MysqlDate_to_str($stock_item[0]->expiry_date) ?>" name="datepicker_doe" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask=""  />
								</div>
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Quantity(Total Tables(s)) </label>
								<input class="form-control number" name="input_qty" placeholder="Qty * Packaging" type="text" value="<?=$stock_item[0]->qty ?>"  />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Package</label>
								<input class="form-control number" name="input_package" placeholder="Package" type="text" value="<?=$stock_item[0]->packing ?>"  />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Date of Stock</label>
								<div class="input-group date">
									<div class="input-group-addon">
										<i class="fa fa-calendar"></i>
									</div>
									<input class="form-control pull-right datepicker" value="<?=MysqlDate_to_str($stock_item[0]->stock_date) ?>" name="datepicker_stock" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask=""  />
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Total Qty </label>
								<input class="form-control number" name="input_totalqty" placeholder="Total Qty" type="text" value="<?=$stock_item[0]->total_unit ?>"  />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Min Qty </label>
								<input class="form-control number" name="input_minqty" placeholder="Min Qty" type="text" value="<?=$stock_item[0]->min_qty ?>"  />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>HSNCODE</label>
								<input class="form-control number" name="input_HSNCODE" placeholder="HSN CODE" type="text" value="<?=$stock_item[0]->HSNCODE ?>"  />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>CGST</label>
								<input class="form-control number" name="input_CGST" placeholder="CGST" type="text" value="<?=$stock_item[0]->CGST ?>"  />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>SGST</label>
								<input class="form-control number" name="input_SGST" placeholder="SGST" type="text" value="<?=$stock_item[0]->SGST ?>"  />
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-2">
							<div class="form-group">
							<label>Supplier</label>
								<select class="form-control input-sm" id="input_supplier" name="input_supplier"  >	
									<?php 
									foreach($med_supplier as $row)
									{ 
										echo '<option value='.$row->sid.'  '.combo_checked($row->sid,$stock_item[0]->supplier_id).'  >'.$row->name_supplier.'</option>';
									}
									?>
								</select>
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<label>Purchase Price</label>
								<input class="form-control number" name="input_purchase_price" placeholder="Purchase Price" type="text" value="<?=$stock_item[0]->purchase_price ?>"  />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Storage</label>
								<input class="form-control" name="input_storage" placeholder="Storage Name" type="text" value="<?=$stock_item[0]->cold_storage ?>"  />
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<label>Shelf No.</label>
								<input class="form-control" name="input_shelf_no" placeholder="Shelf No." type="text" value="<?=$stock_item[0]->shelf_no ?>"  />
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<label>Rack No.</label>
								<input class="form-control" name="input_rack_no" placeholder="Rack No." type="text" value="<?=$stock_item[0]->rack_no ?>"  />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label> </label>
								<button type="button" class="btn btn-primary" id="btn_update_stock" accesskey="U" ><u>U</u>pdate Stock</button>
							</div>
						</div>
					</div>
					
				<?php echo form_close(); ?>
				</div>
		</div>
	</div>
</div>

<script>
$(document).ready(function(){
        $('#btn_update_stock').click(function(){
			$.post('/index.php/Medical/update_stock/'+$('#ss_no').val(), $('form.form1').serialize(), function(data){
                if(data.is_update_stock==0)
                {
                    $('#msgshow').html(data.show_text);
					$("#alert_show").alert();
					$("#alert_show").fadeTo(2000, 500).slideUp(500, function(){
						$("#alert_show").slideUp(500);
						});
                }else
                {
                    $('#msgshow').html(data.show_text);
					$('#input_product_code').val(data.product_code);
					$("#alert_show").alert();
					$("#alert_show").fadeTo(2000, 500).slideUp(500, function(){
						$("#alert_show").slideUp(500);
						});
					alert(data.product_code);
                }
            }, 'json');
		});
       
   });
   
$(document).ready(function(){
	$("#input_formulation").autocomplete({
	  source:"Medical/get_formulation_desc",
	minLength: 1,
	autofocus: true,
	select: function( event, ui ) {
		
	}		      	
	});
  });
</script>