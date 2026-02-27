<div class="row">
	<div class="col-md-12">
		<div class="jsError"></div>
		<div class="box">
				<div class="box-header">
				  <h3 class="box-title">Purchase Invoice Information</h3>
				  <small><i>Supplier</i>:  | <?=$purchase_invoice[0]->name_supplier ?> 
				  |<i>Invoice No.</i>: <?=$purchase_invoice[0]->Invoice_no ?>  |
				  <i>Invoice Date</i> : <?=$purchase_invoice[0]->str_date_of_invoice ?> </small>
				  
				</div>
				<div class="box-body">
					<div id="invoice_item_list"></div>
				</div>
				<div class="box-footer">
					<?php echo form_open('AddNew', array('role'=>'form','class'=>'form1')); ?>
				<input type="hidden" id="invoice_id" name="invoice_id" value="<?=$purchase_invoice[0]->id ?>" >
					<div class="row">
						<div class="col-md-4"> 
							<div class="form-group">
								<div class="ui-widget">
									<label for="tags">Product Search: </label>
									<input class="form-control input-sm" name="input_drug" id="input_drug" placeholder="Like Item Code , Item Name" type="text" >
								</div>
							</div>
						</div>
						<div class="col-xs-2">
							<div class="form-group">
								<label>Product Code</label>
								<input class="form-control input-sm" name="input_product_code" id="input_product_code" placeholder="Product Code" type="text"  readonly />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Packaging </label>
								<input class="form-control number input-sm" name="input_package" id="input_package" placeholder="Qty * Strip" type="text"   />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Batch No.</label>
								<input class="form-control input-sm" name="input_batch_code" id="input_batch_code" placeholder="Batch No." type="text"  />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Date of Expiry</label>
									<div class="input-group date">
										<div class="input-group-addon">
										<i class="fa fa-calendar"></i>
									</div>
										<input class="form-control pull-right datepicker input-sm"  name="datepicker_doe" id="datepicker_doe" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask="" value="<?=date('d/m/Y')?>"  />
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-2"> 
							<div class="form-group">
								<label>MRP</label>
								<input class="form-control number input-sm" name="input_product_mrp" id="input_product_mrp" placeholder="Product MRP" type="text"  onchange="update_selling_rate()"  />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Qty</label>
								<input class="form-control number input-sm" name="input_Qty" id="input_Qty" placeholder="ty" type="text" value="0"  onchange="calculate()"  />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Free Qty</label>
								<input class="form-control number input-sm" name="input_Qty_Free" id="input_Qty_Free" placeholder="Free Package" type="text" value="0"   />
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<label>Purchase Rate</label>
								<input class="form-control number input-sm" name="input_purchase_price" id="input_purchase_price" placeholder="Purchase Price" type="text"  onchange="calculate()"  />
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<label>Amount</label>
								<input class="form-control number input-sm" name="amount_price" id="amount_price" placeholder="" type="text"    Readonly   />
							</div>
						</div>
					</div>
					
					<div class="row">
						<div class="col-md-2">
							<div class="form-group">
								<label>Discount %</label>
								<input class="form-control number input-sm" name="input_disc_price" id="input_disc_price" placeholder="Discount in %" type="text" onchange="calculate()"   />
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<label>SCH Amount</label>
								<input class="form-control number input-sm" name="input_sch_amount" id="input_sch_amount" placeholder="" type="text"  onchange="calculate()"     />
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<label>SCH Discount %</label>
								<input class="form-control number input-sm" name="input_sch_disc" id="input_sch_disc" placeholder="Discount in %" type="text" onchange="calculate()"   />
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<label>Taxable Amount</label>
								<input class="form-control number input-sm" name="Tamount_price" id="Tamount_price" placeholder="" type="text"    Readonly   />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>CGST</label>
								<input class="form-control number input-sm" name="input_CGST" id="input_CGST" placeholder="CGST" type="text" value="6" onchange="calculate()"   />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>SGST</label>
								<input class="form-control number input-sm" name="input_SGST" id="input_SGST" placeholder="SGST" type="text" value="6"  onchange="calculate()"  />
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<label>Net Amount</label>
								<input class="form-control number input-sm" name="Net_amount" id="Net_amount" placeholder="" type="text"    Readonly   />
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-2">
							<div class="form-group">
								<label>Selling Price</label>
								<input class="form-control number input-sm" name="input_selling_price" id="input_selling_price" placeholder="Selling Price" type="text"    />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>HSNCODE</label>
								<input class="form-control number input-sm" name="input_HSNCODE" placeholder="HSN CODE" type="text" value="3004"   />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Storage</label>
								<input class="form-control input-sm" name="input_storage" id="input_storage" placeholder="Storage Name" type="text"   />
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<label>Shelf No.</label>
								<input class="form-control input-sm" name="input_shelf_no" id="input_shelf_no" placeholder="Shelf No." type="text"   />
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<label>Rack No.</label>
								<input class="form-control input-sm" name="input_rack_no" id="input_rack_no" placeholder="Rack No." type="text"   />
							</div>
						</div>
						<div class="col-md-2"> 
							<div class="form-group">
								<label> </label>
								<input type="hidden" id="invoice_item_id" name="invoice_item_id" value="0" >
				
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
		function calculate()
		{
			var pp=$("#input_purchase_price").val();
			var qty=$("#input_Qty").val();
			
			var disc=$("#input_disc_price").val();
			
			var schamt=$("#input_sch_amount").val();
			var schdisc=$("#input_sch_disc").val();
						
			if(schdisc>0)
			{
				pp=pp-(pp*schdisc/100);
			}
			
			var amount=pp*qty;

			if(schamt>0)
			{
				amount=amount-schamt;
			}
			
			var taxamount=(amount-(amount*disc/100));
			
			var cgst=$("#input_CGST").val();
			var sgst=$("#input_SGST").val();
			
			var cgst_amt=taxamount*cgst/100;
			var sgst_amt=taxamount*sgst/100;
			
			var net_amount=taxamount+cgst_amt+sgst_amt;
		
			$("#amount_price").val(amount.toFixed(2));
			
			$("#Tamount_price").val(taxamount.toFixed(2));
			
			$("#Net_amount").val(net_amount.toFixed(2));
		}
		
		function update_selling_rate()
		{
			var product_mrp=$("#input_selling_price").val();
			
			$("#input_selling_price").val(product_mrp.toFixed(2));
		}
		
		function reset_input()
		{
			$("#input_product_code").val('');
			$("#input_product_mrp").val('');
			$("#input_selling_price").val('');
			$("#input_batch_code").val('');
			$("#amount_price").val('');
			$("#Tamount_price").val('');
			$("#Net_amount").val('');
			
			$("#input_disc_price").val('');

			$("#input_Qty").val('');
			$("#input_Freepackage").val('');
			$("#input_package").val('');
			$("#input_purchase_price").val('');
			$("#input_drug").val('');
			
			$("#input_sch_amount").val('');
			$("#input_sch_disc").val('');
			
			$("#invoice_item_id").val('0');
		}
		
		function remove_item_invoice(inv_item_id)
		{
			$.post('/index.php/Medical/purchase_invoice_item_delete/<?=$purchase_invoice[0]->id ?>/'+inv_item_id, {'inv_item_id':inv_item_id}, function(data){
                if(data.is_update_stock==0)
                {
                    $('#msgshow').html(data.show_text);
					$("#alert_show").alert();
					$("#alert_show").fadeTo(2000, 500).slideUp(500, function(){
						$("#alert_show").slideUp(500);
						});
                }else
                {
                    alert('Delete Successfully');
					load_form_div('/Medical/purchase_invoice_item_list/<?=$purchase_invoice[0]->id ?>','invoice_item_list');
                }
            }, 'json');
		}
		
		function edit_item_invoice(inv_item_id)
		{
			$.post('/index.php/Medical/purchase_invoice_item_edit/<?=$purchase_invoice[0]->id ?>/'+inv_item_id, {'inv_item_id':inv_item_id}, function(data){
                if(data.is_update_stock==0)
                {
                    $('#msgshow').html(data.show_text);
					$("#alert_show").alert();
					$("#alert_show").fadeTo(2000, 500).slideUp(500, function(){
						$("#alert_show").slideUp(500);
						});
                }else
                {
                    
					$("#input_product_code").val(data.product_code);
					$("#input_product_mrp").val(data.product_mrp);
					$("#input_selling_price").val(data.selling_price);
					$("#input_batch_code").val(data.batch_code);
					$("#datepicker_doe").val(data.datepicker_doe);
					
					$("#input_disc_price").val(data.disc_price);
					
					$("#input_sch_amount").val(data.sch_disc_amt);
					$("#input_sch_disc").val(data.sch_disc_per);
			

					$("#input_Qty").val(data.qty);
					$("#input_Qty_Free").val(data.qty_free);
					$("#input_package").val(data.package);
					$("#input_purchase_price").val(data.purchase_price);
					$("#input_drug").val(data.drug);
					
					$("#invoice_item_id").val(data.item_id);
					
					calculate();
					
                }
            }, 'json');
		}

$(document).ready(function(){
		
		load_form_div('/Medical/purchase_invoice_item_list/<?=$purchase_invoice[0]->id ?>','invoice_item_list');
	
        $('#btn_update_stock').click(function(){
			$.post('/index.php/Medical/purchase_update_stock/'+$('#invoice_id').val(), $('form.form1').serialize(), function(data){
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
					load_form_div('/Medical/purchase_invoice_item_list/<?=$purchase_invoice[0]->id ?>','invoice_item_list');
                }
            }, 'json');
		});
       
   });

   $(document).ready(function(){
	   var cache = {};
	   
	   
		$("#input_drug").autocomplete({
		    source: function( request, response ) {
			var term = request.term;
			if ( term in cache ) {
			  response( cache[ term ] );
			  return;
			}
			$.getJSON( "Medical/get_drug_master", request, function( data, status, xhr ) {
			  cache[ term ] = data;
			  response( data );
			});
		},
        minLength: 2,
        autofocus: true,
		select: function( event, ui ) {
			
			$("#input_product_code").val(ui.item.l_item_code);
			$("#input_drug").val(ui.item.value);
			$("#input_product_mrp").val(ui.item.l_mrp);
			$("#input_selling_price").val(ui.item.l_mrp);
			
			
			$("#input_CGST").val(ui.item.l_CGST_per);
			$("#input_SGST").val(ui.item.l_SGST_per);
			$("#input_HSNCODE").val(ui.item.l_HSNCODE);
			
			$("#input_package").val(ui.item.l_package);
			$("#input_purchase_price").val(ui.item.l_purchase_price);
			$("#input_disc_price").val(ui.item.l_disc_price);

			
			}		      	
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