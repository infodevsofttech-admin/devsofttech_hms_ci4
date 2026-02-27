<table id="invoice_return" class="table table-bordered table-striped TableData">
				<thead>
					<tr>
						<th style="width: 10px" tabindex="0">#</th>
						<th tabindex="0">Inv-Date</th>
						<th tabindex="0">Item Name</th>
						<th>Batch No</th>
						<th>Exp.</th>
						<th>Rate</th>
						<th>Saved Qty.</th>
						<th>Price</th>
						<th>Disc.</th>
						<th>Amount</th>
						<th>Return Qty.</th>
						<th>Update</th>
					</tr>
				</thead>
				<tbody>
				<?php
				$srno=0;
					foreach($inv_items as $row)
					{ 
						$srno=$srno+1;
						echo '<tr id="tr_id_'.$row->item_id.'">';
						echo '<td>'.$srno.'</td>';
						echo '<td>'.$row->str_inv_date.'</td>';
						echo '<td>'.$row->item_Name.'</td>';
						echo '<td>'.$row->batch_no.'</td>';
						echo '<td>'.$row->expiry.'</td>';
						echo '<td>'.$row->price.'</td>';
						echo '<td>'.$row->qty.'</td>';
						echo '<td>'.$row->amount.'</td>';
						echo '<td>'.$row->disc_amount.'</td>';
						echo '<td>'.$row->tamount.'</td>';
                        echo '<td>'.$row->r_qty.'</td>';
                        echo '<td>
									<button type="button" class="btn btn-primary" id="btn_update" onclick="remove_item('.$row->item_id.')" >
									<i class="fa fa-remove"></i></button>
							</td>';					
						echo '</tr>';
					}
				?>
				</tbody>
				<!---- Total Show  ----->
			</table>