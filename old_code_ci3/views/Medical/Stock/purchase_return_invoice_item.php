<table class="table table-striped ">
			<tr>
				<th style="width: 10px">#</th>
				<th>RecNo</th>
				<th>Item Name</th>
				<th>Batch No</th>
				<th>Exp.</th>
				<th>MRP / P. Rate/Unit</th>
				<th>Qty.(Unit / Pack)</th>
				<th>Rate</th>
				<th></th>
			</tr>
			<?php
			$srno=0;
				foreach($purchase_return_invoice_item as $row)
				{ 
					$srno=$srno+1;
					echo '<tr >';
					echo '<td>'.$srno.'</td>';
					echo '<td>'.$row->r_id.'</td>';
					echo '<td>'.$row->Item_name.'</td>';
					echo '<td>'.$row->batch_no_r_s.'</td>';
					echo '<td>'.$row->exp_date_str.'</td>';
					echo '<td>'.$row->mrp.' / '.$row->purchase_unit_rate.'</td>';
					echo '<td>'.floatval($row->r_qty).' / '.floatval($row->qty_pak).'</td>';
					echo '<td>'.$row->r_amount.'</td>';
					echo '<td>';
					if(1==1)
					{
						echo '<button type="button" class="btn btn-danger" id="btn_add_fee" onclick="remove_item_invoice('.$row->r_id.')"><i class="fa fa-remove"></i></button>';
					}
					echo '</td>';
					echo '</tr>';
				}
			echo '<input type="hidden" id="srno" name="srno" value="'.$srno.'" />';
			?>
			<!---- Total Show  ----->
			<tr>
				<th style="width: 10px">#</th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
			</tr>
			
			</table>