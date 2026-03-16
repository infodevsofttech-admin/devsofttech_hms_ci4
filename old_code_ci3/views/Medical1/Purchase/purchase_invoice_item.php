<table class="table table-striped ">
			<tr>
				<th style="width: 10px">#</th>
				<th>Item Name</th>
				<th>Batch No</th>
				<th>Exp.</th>
				<th>MRP</th>
				<th>Qty.</th>
				<th>Rate</th>
				<th>Amount</th>
				<th>Disc.</th>
				<th>Tax Amount</th>
				<th>CGST</th>
				<th>SGST</th>
				<th>Net Amount</th>
				<th></th>
			</tr>
			<?php
			$srno=0;
				foreach($purchase_item as $row)
				{ 
					$srno=$srno+1;
					if($row->item_return==1)
					{
						$style='style="color:Red;"';
					}else{
						$style='';
					}
					echo '<tr '.$style.' >';
					echo '<td>'.$srno.'</td>';
					echo '<td>'.$row->Item_name.'</td>';
					echo '<td>'.$row->batch_no.'</td>';
					echo '<td>'.$row->exp_date_str.'</td>';
					echo '<td>'.$row->mrp.'</td>';
					echo '<td>'.floatval($row->qty).'+'.floatval($row->qty_free).'</td>';
					echo '<td>'.$row->purchase_price.'</td>';
					echo '<td>'.$row->amount.'</td>';
					echo '<td>'.$row->discount.'</td>';
					echo '<td>'.$row->taxable_amount.'</td>';
					echo '<td>'.$row->CGST_per.'</td>';
					echo '<td>'.$row->SGST_per.'</td>';
					echo '<td>'.$row->net_amount.'</td>';
					echo '<td>';
					if($purchase_invoice[0]->inv_status==0)
					{
						echo '<button type="button" class="btn btn-primary" id="btn_update" onclick="edit_item_invoice('.$row->id.')"><i class="fa fa-edit"></i></button>
						<button type="button" class="btn btn-danger" id="btn_add_fee" onclick="remove_item_invoice('.$row->id.')"><i class="fa fa-remove"></i></button>';
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
				<th><?=$purchase_invoice[0]->Taxable_Amt ?></th>
				<th><?=$purchase_invoice[0]->CGST_Amt ?></th>
				<th><?=$purchase_invoice[0]->SGST_Amt ?></th>
				<th><?=$purchase_invoice[0]->T_Net_Amount ?></th>
				<th></th>
			</tr>
			
			</table>