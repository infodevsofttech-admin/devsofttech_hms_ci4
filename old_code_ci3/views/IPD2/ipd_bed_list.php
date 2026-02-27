<div class="row">
    <div class="col-md-8"> 
        <div class="form-group">
            <div class="ui-widget">
                <label for="tags">IPD Patient Search: </label>
                <input class="form-control input-sm" name="input_ipd" id="input_ipd" placeholder="Like IPD No , Patient Name" type="text" >
            </div>
        </div>
    </div>
    <div class="col-md-4">
    <span class="text-muted" name="input_product_code" id="input_product_code" >
    </div>
</div>
<script>
$(document).ready(function(){
	   var cache = {};
	   
		$("#input_drug").autocomplete({
		    source: function( request, response ) {
			$.getJSON( "IpdNew/get_ipd_no", request, function( data, status, xhr ) {
				response( data );
			});        
		},
        minLength: 1,
        autofocus: true,
		select: function( event, ui ) {
			$("#input_product_code").html('| Product Code:'+ui.item.l_item_code);
			$("#input_product_name").html('Name:'+ui.item.value);
			$("#l_ssno").val(ui.item.l_ss_no);
			$("#input_batch").html(' | Batch No.:'+ui.item.l_Batch+' | Exp.Dt: '+ui.item.l_Expiry);
			$("#input_product_mrp").html(' | MRP:'+ui.item.l_mrp + ' | Unit Rate :' + ui.item.l_unit_rate);
			$("#input_product_unit_rate").val(ui.item.l_unit_rate);
			$("#item_code").val(ui.item.item_code);
			$("#hid_c_qty").val(ui.item.l_c_qty);
			$("#stock_product_qty").html(' |Qty : '+ui.item.l_c_qty);
			}		      	
		});
	  });
</script>