<input type="hidden" id="master_indent_id" name="master_indent_id" value="<?=$med_indent_request[0]->indent_id ?>" />
<div class="row">
	<div class="col-md-12">
		<div class="jsError"></div>
		<div class="box">
				<div class="box-header">
				  <h3 class="box-title">Indent Request Information</h3>
				  <small><i>Indent No.</i>: <?=$med_indent_request[0]->indent_code ?>  |
				  <i>Indent Date & Time</i> : <?=$med_indent_request[0]->created_date ?>
				  <i>Indent Current Status</i> : <?=$indent_status_cont[$med_indent_request[0]->request_status] ?>
					<?php if($med_indent_request[0]->request_status==2) { ?> 			
							<button onclick="load_form_div('/Product_master/indent_update_request_status/<?=$med_indent_request[0]->indent_id ?>/3','searchresult');" type="button" class="btn btn-warning btn-flat" >Complete Indent to Send</button>
					<?php }elseif($med_indent_request[0]->request_status==3){ ?>
				  				<button onclick="load_form_div('/Product_master/indent_update_request_status/<?=$med_indent_request[0]->indent_id ?>/2','searchresult');" type="button" class="btn btn-warning btn-flat" >Edit Indent</button>
					<?php } ?>
				  </small>
				</div>
				<div class="box-body">
					<div class="row">
						<div class="col-md-6" id="show_ident_list">
						
						</div>
						<div class="col-md-6" id="show_store">
						
						</div>
					</div>
				</div>
				<div class="box-footer" id="process_list">
					
				</div>
		</div>
	</div>
</div>

<script>
	function update_indent_process(srno)
	{
		var pur_item_id=$("#hid_pur_item_id_"+srno).val();
		var qty_issue=$("#qty_issue_"+srno).val();
		
		var hid_product_id=$("#hid_product_id").val();
		var hid_indent_id=$("#hid_indent_id").val();
		var hid_indent_item_id=$("#hid_indent_item_id").val();
		
		$.post('/index.php/stock/indent_process_update_Qty', 
			{
				'pur_item_id':pur_item_id,
				'qty_issue':qty_issue,
				'hid_product_id':hid_product_id,
				'hid_indent_id':hid_indent_id,
				'hid_indent_item_id':hid_indent_item_id,
				<?php echo $this->security->get_csrf_token_name(); ?>:'<?php echo $this->security->get_csrf_hash(); ?>'
			},
			function(data){
				if(data.is_update_stock==0)
				{
					$('#msgshow').html(data.show_text);
					$("#alert_show").alert();
					$("#alert_show").fadeTo(2000, 500).slideUp(500, function(){
						$("#alert_show").slideUp(500);
						});
				}else
				{
					alert('Update Successfully');
					
					load_form_div('/Stock/indent_process_items_list/'+hid_indent_id,'process_list');
					load_form_div('/Stock/indent_issue_items_sub/'+hid_indent_id,'show_ident_list');
				}
			});
	}
	
	$(document).ready(function(){
	  var master_indent_id=$("#master_indent_id").val();
	  load_form_div('/Stock/indent_process_items_list/'+master_indent_id,'process_list');
	  load_form_div('/Stock/indent_issue_items_sub/'+master_indent_id,'show_ident_list');
	});

</script>