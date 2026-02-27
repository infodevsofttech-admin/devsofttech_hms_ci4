<div class="row">
	<div class="col-md-12">
		<div class="jsError"></div>
		<div class="box">
				<div class="box-header">
				  <h3 class="box-title">Indent Request Information</h3>
				  <small><i>Indent No.</i>: <?=$med_indent_request[0]->indent_code ?>  |
				  <i>Indent Date & Time</i> : <?=$med_indent_request[0]->created_date ?>
				  <i>Indent Current Status</i> :  <?=$indent_status_cont[$med_indent_request[0]->request_status] ?>
					<?php if($med_indent_request[0]->request_status==0){ ?>
								<button onclick="load_form_div('/Product_master/indent_update_request_status/<?=$med_indent_request[0]->indent_id ?>/1','searchresult');" type="button" class="btn btn-warning btn-flat" >Send Request To Store</button>
					<?php }elseif($med_indent_request[0]->request_status==1){ ?>
								<button onclick="load_form_div('/Product_master/indent_update_request_status/<?=$med_indent_request[0]->indent_id ?>/0','searchresult');" type="button" class="btn btn-warning btn-flat" >Edit</button>
					<?php } ?>
					</small>
				</div>
				<div class="box-body">
					<div id="invoice_item_list"></div>
				</div>
				<div class="box-footer">
					<?php echo form_open('AddNew', array('role'=>'form','class'=>'form1')); ?>
					<input type="hidden" id="indent_id" name="indent_id" value="<?=$med_indent_request[0]->indent_id ?>" >
					
					<div class="row">
						<div class="col-md-6"> 
							<div class="form-group">
								<div class="ui-widget">
									<label for="tags">Product Search: </label>
									<input class="form-control input-sm" name="input_drug" id="input_drug" placeholder="Like Item Code , Item Name" type="text"  >
								</div>
							</div>
						</div>
						<div class="col-md-4"> 
							<div class="form-group">
								<div class="ui-widget">
									<label for="tags">Product Name</label>
									<input class="form-control input-sm" name="input_item_name" id="input_item_name" placeholder="Product Name" type="text" 
									 readonly="true">
								</div>
							</div>
						</div>
						<input type="hidden" id="med_indent_request_items_id" name="med_indent_request_items_id" value="0" >
						<input type="hidden" id="product_id" name="product_id" value="0" >
					
					</div>
					<div class="row">
						<div class="col-md-6"> 
							<div class="form-group">
								<div class="ui-widget">
									<label for="tags">Generic Name</label>
									<input class="form-control input-sm" name="input_genericname" id="input_genericname" placeholder="Generic Name" 
									type="text" value="" readonly="true" >
								</div>
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Formulation</label>
								<input class="form-control number input-sm" name="input_formulation" id="input_formulation" 
								placeholder="Tablet,Syrup,Capsule" type="text" value="" readonly="true"  />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Packing</label>
								<input class="form-control number input-sm" name="input_packing_type" id="input_packing_type" 
								placeholder="10 Tablets in Strip,1 Bottle" type="text" value="" readonly="true" />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Qty</label>
								<input class="form-control number input-sm" name="input_Qty" id="input_Qty" placeholder="Qty" type="text" value="0"    />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Min Qty</label>
								<input class="form-control number input-sm" name="input_Min_Qty" id="input_Min_Qty" placeholder="Qty" type="text" value="0"    />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label> </label>
								<button type="button" class="btn btn-primary" id="btn_update_stock" accesskey="U" ><u>A</u>dd Stock</button>
							</div>
						</div>
					</div>
				<?php echo form_close(); ?>
				</div>
		</div>
	</div>
</div>

<script>
		
		function reset_input()
		{
			$("#input_drug").val('');
			$("#input_item_name").val('');
			$("#input_genericname").val('');
			$("#input_formulation").val('');
			$("#input_packing_type").val('');
			$("#input_Qty").val('0');
			$("#input_Min_Qty").val('0');
			$("#product_id").val('0');
			$("#med_indent_request_items_id").val('0');
			
		}
		
		function remove_item_indent(inv_item_id)
		{
			$.post('/index.php/product_master/indent_request_item_delete/<?=$med_indent_request[0]->indent_id ?>/'+inv_item_id, {'inv_item_id':inv_item_id}, function(data){
                if(data.is_delete==0)
                {
                    alert(data.show_text);
                }else
                {
                    alert(data.show_text);
					load_form_div('/product_master/indent_request_item_list/<?=$med_indent_request[0]->indent_id ?>','invoice_item_list');
                }
            }, 'json');
		}
		
		function edit_item_indent(inv_item_id)
		{
			$.post('/index.php/product_master/indent_request_item_edit/<?=$med_indent_request[0]->indent_id ?>/'+inv_item_id, {'inv_item_id':inv_item_id}, function(data){
                if(data.is_update_stock==0)
                {
                    $('#msgshow').html(data.show_text);
					$("#alert_show").alert();
					$("#alert_show").fadeTo(2000, 500).slideUp(500, function(){
						$("#alert_show").slideUp(500);
						});
                }else
                {
					$("#med_indent_request_items_id").val(data.indent_item_id);
					$("#product_id").val(data.product_id);
					$("#input_item_name").val(data.product_name);
					$("#input_genericname").val(data.selling_price);
					$("#input_formulation").val(data.batch_code);
					$("#input_Qty").val(data.request_qty);
					$("#input_Min_Qty").val(data.min_requirement);

                }
            }, 'json');
		}

	$(document).ready(function(){
		var cache = {};
		
		load_form_div('/product_master/indent_request_item_list/<?=$med_indent_request[0]->indent_id ?>','invoice_item_list');
	
        $('#btn_update_stock').click(function(){
			$.post('/index.php/product_master/indent_update_item/'+$('#indent_id').val(), $('form.form1').serialize(), function(data){
                if(data.is_update_stock==0)
                {
                    $('#msgshow').html(data.show_text);
					$("#alert_show").alert();
					$("#alert_show").fadeTo(2000, 500).slideUp(500, function(){
						$("#alert_show").slideUp(500);
						});
                }else
                {
                    alert('Added Successfully');
					reset_input();
					
					var elmnt = document.getElementById("input_drug");
					
					$("#input_drug").focus();
					elmnt.scrollIntoView();
					load_form_div('/product_master/indent_request_item_list/<?=$med_indent_request[0]->indent_id ?>','invoice_item_list');
                }
            }, 'json');
		});
		
		$("#input_drug").autocomplete({
				source: function( request, response ) {
				var term = request.term;
				if ( term in cache ) {
				  response( cache[ term ] );
				  return;
				}
				$.getJSON( "/product_master/get_product_master", {<?php echo $this->security->get_csrf_token_name(); ?>:'<?php echo $this->security->get_csrf_hash(); ?>','term':term}, function( data, status, xhr ) {
				  cache[ term ] = data;
				  response( data );
				});
			},
			minLength: 2,
			autofocus: true,
			select: function( event, ui ) {
					$("#input_item_name").val(ui.item.l_itemname);
					$("#product_id").val(ui.item.l_item_code);
					$("#input_formulation").val(ui.item.l_formulation);
					$("#input_packing_type").val(ui.item.l_packing);
					$("#input_genericname").val(ui.item.l_genericname);
					
				}		      	
		});
   });

 
</script>