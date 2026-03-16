<div class="row">
	<div class="col-md-12">
		<div class="jsError"></div>
		<div class="box">
				<div class="box-header">
				  <h3 class="box-title">Drug Search</h3>
				</div>
				<div class="box-body">
				<?php echo form_open('Doctor/AddNew', array('role'=>'form','class'=>'form1')); ?>
					<div class="row">
						<div class="col-md-2">
							<div class="form-group">
								<label>Product Code</label>
								<input class="form-control" name="input_product_code" id="input_product_code" placeholder="Product Code" type="text" value="<?=$drug_item[0]->item_id ?>" readonly />
							</div>
						</div>
						<div class="col-md-3">
							<div class="form-group">
								<label>Product Name</label>
								<input class="form-control" name="input_product_name" placeholder="Product Name" type="text" value="<?=$drug_item[0]->itemname ?>"  />
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<label>Formulation</label>
								<input class="form-control" name="input_formulation" id="input_formulation" placeholder="Formulation" type="text" value="<?=$drug_item[0]->formulation ?>"  />
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<label>Dosage</label>
								<input class="form-control" name="input_dosage" placeholder="Dosage" type="text" value="<?=$drug_item[0]->dosage ?>"  />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Product MRP</label>
								<input class="form-control number" name="input_product_mrp" placeholder="Product MRP" type="text" value="<?=$drug_item[0]->mrp ?>"  />
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-2">
							<div class="form-group">
								<label>Selling Price</label>
								<input class="form-control number" name="input_selling_price" placeholder="Selling Price" type="text" value="<?=$drug_item[0]->mrp ?>"  />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Batch No.</label>
								<input class="form-control" name="input_batch_code" placeholder="Batch No." type="text" value=""  />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Date of Expiry</label>
									<div class="input-group date">
										<div class="input-group-addon">
										<i class="fa fa-calendar"></i>
									</div>
										<input class="form-control pull-right datepicker" value="<?=date('d/m/Y') ?>" name="datepicker_doe" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask=""  />
								</div>
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Quantity(Total Tables(s)) </label>
								<input class="form-control number" name="input_qty" placeholder="Qty * Packaging" type="text" value=""  />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Package</label>
								<input class="form-control number" name="input_package" placeholder="Package" type="text" value=""  />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Date of Stock</label>
								<div class="input-group date">
									<div class="input-group-addon">
										<i class="fa fa-calendar"></i>
									</div>
									<input class="form-control pull-right datepicker" value="<?=date('d/m/Y') ?>" name="datepicker_stock" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask=""  />
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Min Qty </label>
								<input class="form-control number" name="input_minqty" placeholder="Min Qty" type="text" value=""  />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>HSNCODE</label>
								<input class="form-control number" name="input_HSNCODE" placeholder="HSN CODE" type="text" value="<?=$drug_item[0]->HSNCODE ?>"  />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>CGST</label>
								<input class="form-control number" name="input_CGST" placeholder="CGST" type="text" value="<?=$drug_item[0]->CGST ?>"  />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>SGST</label>
								<input class="form-control number" name="input_SGST" placeholder="SGST" type="text" value="<?=$drug_item[0]->SGST ?>"  />
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Storage</label>
								<input class="form-control" name="input_storage" placeholder="Storage Name" type="text" value=""  />
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<label>Shelf No.</label>
								<input class="form-control" name="input_shelf_no" placeholder="Shelf No." type="text" value=""  />
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<label>Rack No.</label>
								<input class="form-control" name="input_rack_no" placeholder="Rack No." type="text" value=""  />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label> </label>
								<button type="button" class="btn btn-primary" id="btn_add_stock" accesskey="A" ><u>A</u>dd Stock</button>
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label> </label>
								<button type="button" class="btn btn-primary" id="btn_edit_master" accesskey="A" ><u>E</u>dit Master</button>
							</div>
						</div>
					</div>
					
				<?php echo form_close(); ?>
				</div>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<div class="box">
			<div class="box-header">
			  <h3 class="box-title">Stock Drug</h3>
			</div>
			<div class="box-body">
				<div id="show_stock">
				<table id="example1" class="table table-bordered table-striped TableData">
					<thead>
					<tr>
					  <th>Item Code</th>
					  <th>Item Name</th>
					  <th>Formulation</th>
					  <th>Storage/Shelf/Rack No.</th>
					  <th>Batch No.</th>
					  <th>Qty /Sale Qty</th>
					  <th>Expiry</th>
					  <th>MRP</th>
					  <th>Rate/Unit</th>
					</tr>
					</thead>
					<tbody>
					<?php for ($i = 0; $i < count($drug_stock_item); ++$i) { ?>
					<tr>
					  <td><a href="javascript:load_form_div('/Medical/UpdateDrugStock/<?=$drug_stock_item[$i]->ss_no ?>','maindiv');"><?=$drug_stock_item[$i]->item_code ?></a></td>
					  <td><?=$drug_stock_item[$i]->item_name ?></td>
					  <td><?=$drug_stock_item[$i]->formulation ?></td>
					  <td><?=$drug_stock_item[$i]->cold_storage ?>/<?=$drug_stock_item[$i]->shelf_no ?>/<?=$drug_stock_item[$i]->rack_no ?></td>
					  <td><?=$drug_stock_item[$i]->batch_no ?></td>
					  <td><?=$drug_stock_item[$i]->total_unit ?> / <?=$drug_stock_item[$i]->total_sale_unit ?></td>
					  <td><?=$drug_stock_item[$i]->expiry_date ?></td>
					  <td><?=$drug_stock_item[$i]->mrp ?></td>
					  <td><?=$drug_stock_item[$i]->unit_rate ?></td>
					</tr>
					<?php } ?>
					</tbody>
					<tfoot>
					<tr>
					  <th>Item Code</th>
					  <th>Item Name</th>
					  <th>Formulation</th>
					  <th>Storage/Shelf/Rack No.</th>
					  <th>Batch No.</th>
					  <th>Qty /Sale Qty</th>
					  <th>Expiry</th>
					  <th>MRP</th>
					  <th>Rate/Unit</th>
					</tr>
					</tfoot>
				  </table>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
$(document).ready(function(){
        $('#btn_add_stock').click(function(){
			$.post('/index.php/Medical/add_stock', $('form.form1').serialize(), function(data){
                if(data.insertid==0)
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
					load_form_div('/Medical/load_stock_data/'+$('#input_product_code').val(),'show_stock');

                }
            }, 'json');
		});
		
		$('#btn_edit_master').click(function(){
			$.post('/index.php/Medical/update_master', $('form.form1').serialize(), function(data){
                if(data.insertid==0)
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
					load_form_div('/Medical/load_stock_data/'+$('#input_product_code').val(),'show_stock');

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