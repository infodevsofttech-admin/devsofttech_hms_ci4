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
					<?php if($med_indent_request[0]->request_status==3) { ?> 			
                        <button onclick="load_form_div('/Product_master/indent_update_request_status/<?=$med_indent_request[0]->indent_id ?>/2','searchresult');" type="button" class="btn btn-success btn-flat" >Edit Indent</button>		
                        <button onclick="load_form_div('/Product_master/indent_update_request_status/<?=$med_indent_request[0]->indent_id ?>/4','searchresult');" type="button" class="btn btn-warning btn-flat" >Complete Indent to Send to Counter</button>
					<?php }elseif($med_indent_request[0]->request_status==4){ ?>
                        <button onclick="load_form_div('/Product_master/indent_update_request_status/<?=$med_indent_request[0]->indent_id ?>/3','searchresult');" type="button" class="btn btn-warning btn-flat" >Return To Verification</button>		
					<?php } ?>
				  </small>
				</div>
				<div class="box-body">
					<div class="row">
						<div class="col-md-12" id="show_ident_list">
						
						</div>
					</div>
				</div>
				<div class="box-footer" id="process_list">
					
				</div>
		</div>
	</div>
</div>

<script>
	$(document).ready(function(){
	  var master_indent_id=$("#master_indent_id").val();
	  load_form_div('/Stock/indent_accept_items_list/'+master_indent_id,'show_ident_list');
	});
</script>