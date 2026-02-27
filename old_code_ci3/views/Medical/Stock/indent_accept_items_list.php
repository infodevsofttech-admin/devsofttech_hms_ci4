<input type="hidden" id="master_indent_id" name="master_indent_id" value="<?=$med_indent_request[0]->indent_id ?>" />
<div class="row">
	<div class="col-md-12">
		<div class="jsError"></div>
		<div class="box">
				<div class="box-header">
				  <h3 class="box-title">Indent Request Information</h3>
				  <small><i>Indent No.</i>: <?=$med_indent_request[0]->indent_code ?>  |
				  <i>Indent Date & Time</i> : <?=$med_indent_request[0]->created_date ?> </small>
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
	function accept_item(indent_id,process_item_id)
	{
		var master_indent_id=$("#master_indent_id").val();
		$.post('/index.php/stock/indent_process_accept_Qty', 
			{
				'indent_id':indent_id,
				'process_item_id':process_item_id,
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
					load_form_div('/Stock/indent_accept_items_list/'+master_indent_id,'show_store');
					load_form_div('/Stock/indent_issue_items_sub/'+master_indent_id+'/1','show_ident_list');
				}
			});
	}

	function accept_item_all(indent_id)
	{
		var master_indent_id=$("#master_indent_id").val();
		$.post('/index.php/stock/indent_process_accept_Qty_all/'+indent_id, 
			{
				'indent_id':indent_id,
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
					load_form_div('/Stock/indent_accept_items_list/'+master_indent_id,'show_store');
					load_form_div('/Stock/indent_issue_items_sub/'+master_indent_id+'/1','show_ident_list');
				}
			});
	}
	
	$(document).ready(function(){
	  	var master_indent_id=$("#master_indent_id").val();
	  	load_form_div('/Stock/indent_accept_items_list/'+master_indent_id,'show_store');
	   	load_form_div('/Stock/indent_issue_items_sub/'+master_indent_id+'/1','show_ident_list');
	});

</script>