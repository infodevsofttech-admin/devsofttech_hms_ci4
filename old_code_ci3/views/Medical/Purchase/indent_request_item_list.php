<table class="table table-striped ">
			<tr>
				<th style="width: 10px">#</th>
				<th>Product Code</th>
				<th>Item Name</th>
				<th>Formulation</th>
				<th>Generic Name</th>
				<th>Qty.</th>
				<th>Min Qty</th>
				<th></th>
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
					echo '<td>
					<button type="button" class="btn btn-primary" id="btn_update" onclick="edit_item_indent('.$row->id.')"><i class="fa fa-edit"></i></button>
					<button type="button" class="btn btn-danger" id="btn_add_fee" onclick="remove_item_indent('.$row->id.')"><i class="fa fa-remove"></i></button></td>';
					echo '</tr>';
				}
			echo '<input type="hidden" id="srno" name="srno" value="'.$srno.'" />';
			?>
			<!---- Total Show  ----->
			<tr>
				<th style="width: 10px">#</th>
				<th>Product Code</th>
				<th>Item Name</th>
				<th>Formulation</th>
				<th>Generic Name</th>
				<th>Qty.</th>
				<th>Min Qty</th>
				<th></th>
			</tr>
			
			</table>