<?php echo form_open('AddNew', array('role'=>'form','class'=>'form1')); ?>
<input type="hidden" id="hid_product_id" name="hid_product_id" value="<?=$product_id?>" />
<input type="hidden" id="hid_indent_id" name="hid_indent_id" value="<?=$indent_id?>" />
<input type="hidden" id="hid_indent_item_id" name="hid_indent_item_id" value="<?=$indent_item_id?>" />
<table class="table table-striped ">
	<tr>
		<th style="width: 10px">#</th>
		<th>Item Name</th>
		<th>Batch No.</th>
		<th>Expiry Date</th>
		<th>Qty.</th>
		<th>Issued Qty.</th>
		<th>Action</th>
	</tr>
	<?php
	$srno=0;
		foreach($stock_list as $row)
		{ 
			$srno=$srno+1;
			echo '<tr>';
			echo '<td>'.$srno.'</td>';
			echo '<td>'.$row->Item_name.'</td>';
			echo '<td>'.$row->batch_no.'</td>';
			echo '<td>'.$row->expiry_date.'</td>';
			echo '<td>'.$row->tqty.'</td>';
			echo '<td>'.$row->issue_item.'</td>';
			if($row->store_stock_id==0)
			{
				echo '<td>
				<table width="200px">
					<tr>
						<td>
							<input type="hidden" id="hid_pur_item_id_'.$srno.'" name="hid_pur_item_id_'.$srno.'" value="'.$row->id.'" />
							<input type="text" class="form-control input-sm number" placeholder="Qty" id="qty_issue_'.$srno.'" 
							name="qty_issue_'.$srno.'" value="'.$row->issue_item.'" />
						</td>
						<td>
							<button class="btn btn-success input-sm" type="button" onclick="update_indent_process('.$srno.')">Update</button>
						</td>
					</tr>
				</table>';
			}else{
				echo '<td></td>' ;
			}
			
			echo '</tr>';
		}                                                                                                                                                                                                                            
	echo '<input type="hidden" id="srno" name="srno" value="'.$srno.'" />';
	?>
	<!---- Total Show  ----->
		<tr>
			<th style="width: 10px">#</th>
			<th>Item Name</th>
			<th>Batch No.</th>
			<th>Expiry Date</th>
			<th>Qty.</th>
			<th>Issued Qty.</th>
			<th>Action</th>
		</tr>
</table>
<?php echo form_close(); ?>