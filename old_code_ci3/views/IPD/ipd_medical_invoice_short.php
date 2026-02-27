<div class="box  box-info">
			<div class="box-header with-border">
			  <h3 class="box-title">Invoice ID : <?=$inv_master_credit[0]->inv_med_code  ?></h3>
			  <small><a href="/Medical/invoice_print/<?=$inv_master_credit[0]->id ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print</a>
			  </small>
								
			</div>
			<div class="box-body">
				<table class="table table-striped ">
						<tr>
							<th style="width: 10px">#</th>
							<th>Item Name</th>
							<th>Qty.</th>
							<th>Price</th>
							<th>Amount</th>
						</tr>
						<?php
						$srno=1;
							foreach($inv_master_credit_details as $row)
							{ 
								echo '<tr>';
								echo '<td>'.$srno.'</td>';
								echo '<td>'.$row->item_Name.'['.$row->formulation.']</td>';
								echo '<td>'.$row->qty.'</td>';
								echo '<td>'.$row->price.'</td>';
								echo '<td>'.$row->tamount.'</td>';
								$srno=$srno+1;
								echo '</tr>';
							}
						echo '<input type="hidden" id="srno" name="srno" value="'.$srno.'" />';
						?>
						<!---- Total Show  ----->
						<tr>
							<th style="width: 10px">#</th>
							<th></th>
							<th></th>
							<th>Total</th>
							<th><?=$inv_master_credit[0]->net_amount  ?></th>
						</tr>
					</table>
					
			</div>
		</div>