<div class="row">
	<div class="col-md-12">
		<div class="jsError"></div>
		<div class="box">
				<div class="box-header">
				  <h3 class="box-title">Indent :<span class="text-green"> <?=$med_indent_request[0]->store_name ?></span></h3>
				  <small><i>Indent No.</i>: <?=$med_indent_request[0]->indent_code ?>  |
				  <i>Indent Date & Time</i> : <?=$med_indent_request[0]->created_date ?> 
				  <i>Indent Current Status</i> : <?=$indent_status_cont[$med_indent_request[0]->request_status] ?>
					<?php if($med_indent_request[0]->request_status==3) { ?> 			
                        <button onclick="load_form_div('/Product_master/indent_update_request_status/<?=$med_indent_request[0]->indent_id ?>/2','searchresult');" type="button" class="btn btn-success btn-flat" >Edit Indent</button>		
                        <button onclick="load_form_div('/Product_master/indent_update_request_status/<?=$med_indent_request[0]->indent_id ?>/4','searchresult');" type="button" class="btn btn-warning btn-flat" >Complete Indent to Send to Counter</button>
					<?php }elseif($med_indent_request[0]->request_status==0){ ?>
						<button onclick="load_form_div('/Product_master/indent_update_request_status/<?=$med_indent_request[0]->indent_id ?>/3','searchresult');" type="button" class="btn btn-success btn-flat" >Complete Send to Verify</button>		
					<?php }elseif($med_indent_request[0]->request_status==4){ ?>
                        <button onclick="load_form_div('/Product_master/indent_update_request_status/<?=$med_indent_request[0]->indent_id ?>/3','searchresult');" type="button" class="btn btn-warning btn-flat" >Return To Verification</button>		
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
						</div>
					<div class="row">
						<div class="col-md-4">
							<div class="row">
								Same product Other Stock
							</div>
						</div>
						<div class="col-md-8">
							<div class="row">
								<div class="col-md-6"> 
									<div class="form-group">
										<div class="ui-widget">
											<label for="tags">Product Name</label>
											<div class="form-control input-sm" name="input_item_name" id="input_item_name" 	></div>
										</div>
									</div>
								</div>
								<input type="hidden" id="med_indent_request_items_id" name="med_indent_request_items_id" value="0" >
								<input type="hidden" id="product_id" name="product_id" value="0" >
								<input type="hidden" id="inv_ssno" name="inv_ssno" value="0" >
								<input type="hidden" id="product_name" name="product_name" value="0" >
								<div class="col-md-6"> 
									<div class="form-group">
										<label>Batch No. / MRP /Expiry / Purchase Date</label>
										<div class="form-control input-sm" name="input_batch_mrp" id="input_batch_mrp" 	></div>
									</div>
								</div>
								<div class="col-md-3"> 
									<div class="form-group">
										<div class="ui-widget">
											<label for="tags">Stock Qty</label>
											<div class="form-control input-sm" name="input_stock_qty" id="input_stock_qty" 	></div>
											<input type="hidden" id="p_stock_qty" name="p_stock_qty" value="0" >
										</div>
									</div>
								</div>
								<div class="col-md-3"> 
									<div class="form-group">
										<label>Packing</label>
										<div class="form-control input-sm" name="input_packing_type" id="input_packing_type" 	></div>
									</div>
								</div>
								<div class="col-md-3"> 
									<div class="form-group">
										<label>Add Qty in List</label>
										<input class="form-control number input-sm" name="input_add_qty" id="input_add_qty" 
										placeholder="10 Tablets in Strip,1 Bottle" type="text" value="" />
									</div>
								</div>
								<div class="col-md-3"> 
									<div class="form-group">
										<label> </label>
										<button type="button" class="btn btn-primary" id="btn_update_stock" >Add in Indent</button>
									</div>
								</div>
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
			$("#p_stock_qty").val('');
			$("#input_add_qty").val('0');
			$("#input_stock_qty").html('');
			$("#input_packing_type").html('');
			$("#input_batch_mrp").val('');
			$("#product_name").val('');
			$("#inv_ssno").val('');
			$("#product_id").val('0');
			$("#med_indent_request_items_id").val('0');
		}
		
		function remove_item_indent(inv_item_id)
		{
			$.post('/index.php/Stock/indent_request_item_delete/<?=$med_indent_request[0]->indent_id ?>/'+inv_item_id,
			 {'inv_item_id':inv_item_id,
			 <?php echo $this->security->get_csrf_token_name(); ?>:'<?php echo $this->security->get_csrf_hash(); ?>'}, 
			 function(data){
                if(data.is_delete==0)
                {
                    alert(data.show_text);
                }else
                {
                    alert(data.show_text);
					load_form_div('/Stock/indent_request_item_list/<?=$med_indent_request[0]->indent_id ?>','invoice_item_list');
                }
            }, 'json');
		}
		
		function get_product_stock_list()
		{
			load_form_div('/Stock/indent_request_item_list/<?=$med_indent_request[0]->indent_id ?>','invoice_item_list');
		}

	$(document).ready(function(){
		var cache = {};
		
		load_form_div('/Stock/indent_request_item_list/<?=$med_indent_request[0]->indent_id ?>','invoice_item_list');
	
        $('#btn_update_stock').click(function(){
			
			var add_qty=parseInt($("#input_add_qty").val());
			var stock_qty=parseInt($("#p_stock_qty").val());

			alert(add_qty);

			if(add_qty>0 && add_qty<=stock_qty)
			{
				$.post('/index.php/Stock/indent_update_item_direct/'+$('#indent_id').val(), 
					$('form.form1').serialize(), function(data){
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
						load_form_div('/Stock/indent_request_item_list/<?=$med_indent_request[0]->indent_id ?>','invoice_item_list');
					}
				}, 'json');
			}else{
				alert('Qty Should between 1 to Stock Qty '+stock_qty);
			}
			
		});
		
		$("#input_drug").autocomplete({
				source: function( request, response ) {
				var term = request.term;
				if ( term in cache ) {
				  response( cache[ term ] );
				  return;
				}
				$.getJSON( "/Stock/get_product_master", 
					{<?php echo $this->security->get_csrf_token_name(); ?>:'<?php echo $this->security->get_csrf_hash(); ?>',
					'term':term}, function( data, status, xhr ) {
				  cache[ term ] = data;
				  response( data );
				});
			},
			minLength: 1,
			autofocus: true,
			select: function( event, ui ) {
					$("#input_item_name").html(ui.item.l_itemname);
					$("#product_id").val(ui.item.l_item_code);
					$("#inv_ssno").val(ui.item.l_item_ssno);
					$("#input_batch_mrp").html(ui.item.l_batch_mrp_exp);
					$("#input_stock_qty").html(ui.item.l_item_qty);
					$("#p_stock_qty").val(ui.item.l_item_qty);
					$("#input_packing_type").html(ui.item.l_packing);
					$("#input_add_qty").val('0');				
					$("#med_indent_request_items_id").val('0');
					$("#product_name").val(ui.item.l_itemname);
					
				}		      	
		});
   });

 
</script>