<div class="row">
	<div class="col-md-12">
		<div class="jsError"></div>
		<div class="box">
				<div class="box-header">
				  <h3 class="box-title">Indent Request Information</h3>
				  <small><i>Indent No.</i>: <?=$med_indent_request[0]->indent_code ?>  |
				  <i>Indent Date & Time</i> : <?=$med_indent_request[0]->created_date ?> 
				  <i>Indent Current Status</i> : <?=$indent_status_cont[$med_indent_request[0]->request_status] ?>
				  </small>
				</div>
				<div class="box-body">
					<table class="table table-striped ">
						<tr>
							<th style="width: 10px">#</th>
							<th>Item Name</th>
							<th>Formulation</th>
							<th>Generic Name</th>
							<th>Qty.</th>
							<th>Min Qty</th>
						</tr>
						<?php
						$srno=0;
							foreach($med_indent_request_items as $row)
							{ 
								$srno=$srno+1;
								echo '<tr>';
								echo '<td>'.$srno.'</td>';
								echo '<td>'.$row->item_name.'</td>';
								echo '<td>'.$row->formulation.'</td>';
								echo '<td>'.$row->genericname.'</td>';
								echo '<td>'.$row->request_qty.'</td>';
								echo '<td>'.$row->min_requirement.'</td>';
								echo '</tr>';
							}                                                                                                                                                                                                                            
						echo '<input type="hidden" id="srno" name="srno" value="'.$srno.'" />';
						?>
						<!---- Total Show  ----->
							<tr>
								<th style="width: 10px">#</th>
								<th>Item Name</th>
								<th>Formulation</th>
								<th>Generic Name</th>
								<th>Qty.</th>
								<th>Min Qty</th>
								<th></th>
							</tr>
						</table>
				</div>
				<div class="box-footer">
				<?php echo form_open('AddNew', array('role'=>'form','class'=>'form1')); ?>
					<input type="hidden" id="indent_id" name="indent_id" value="<?=$med_indent_request[0]->indent_id ?>" >
					<?php if($med_indent_request[0]->request_status==0){ 
				 	?>
					  			<button onclick="load_form_div('/Product_master/indent_update_request_status/<?=$med_indent_request[0]->indent_id ?>/1','searchresult');" type="button" class="btn btn-warning btn-flat" >Send Request To Store</button>
					<?php 	}elseif($med_indent_request[0]->request_status==1){ ?>
				  				<button onclick="load_form_div('/Product_master/indent_update_request_status/<?=$med_indent_request[0]->indent_id ?>/0','searchresult');" type="button" class="btn btn-warning btn-flat" >Not Accept/Return to Store</button>
								<button onclick="load_form_div('/Product_master/indent_update_request_status/<?=$med_indent_request[0]->indent_id ?>/2','searchresult');" type="button" class="btn btn-success btn-flat" >Accept Process Indent</button>
					<?php 	} ?>
					
					<?php echo form_close(); ?>
				</div>
		</div>
	</div>
</div>
