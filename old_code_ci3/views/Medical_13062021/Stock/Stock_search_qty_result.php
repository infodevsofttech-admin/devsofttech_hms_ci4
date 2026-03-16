
<div class="row">
	<div class="col-md-12">
		<div id="supplier_list_1" class="box-body" style="height:500px;overflow-y:auto;">
		  <table id="report_list" class="table table-bordered table-striped TableData">
				<thead>
					<tr>
					  <th style="width:300px;">Item Name</th>
					  <th>Supplier</th>
					  <th>Sale Unit Qty</th>
					  <th>Current Unit Qty</th>
					  <th>Re-Order Qty</th>
					  <th>Alert Qty</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($stock_list as $row) { ?>
						<tr>
							<td >
								<p class="text-danger"><?=$row->Item_name ?></p> 
							</td>
							<td>
								<p class="text-secondary"><?=$row->supplier_names ?></p>
							</td>
							<td>
								<p class="text-secondary"><?=$row->sal_qty ?></p>
							</td>
							<td><?php

									$C_Qty = $row->T_unit - $row->sal_qty;
									echo $C_Qty;
								?>
								
							</td>
							<td>
								<p class="text-warning"><?=$row->re_order_qty ?></p>
							</td>
							<td>
								<?php
									if($C_Qty<=$row->re_order_qty)
									{
										echo '<p class="text-danger">Qty Low</p>';
									}

								?>
								
							</td>
						</tr>
					<?php } ?>
				</tbody>
				<tfoot>
					<tr>
					  <th style="width:300px;">Item Name</th>
					  <th>Supplier</th>
					  <th>Sale Unit Qty</th>
					  <th>Current Unit Qty</th>
					  <th>Re-Order Qty</th>
					  <th>Alert Qty</th>
					</tr>
				</tfoot>
		  </table>
		 <script>
			$('#report_list').dataTable();
		  </script>
		</div>	
	</div>
</div>
<script>
	function update_sale_stock(item_id)
	{
		if(confirm("Are you sure Update Sale Qty "))
		{
		$.post('/index.php/Medical_backpanel/update_stock_adjust/',
			{ "qty": $('#input_qty_update_'+item_id).val(),
				"item_id":item_id
			 }, function(data){
				alert(data);
			});
		}
	}
	
	function remove_sale_stock(item_id)
	{
		if(confirm("Are you sure Remove This Item From List "))
		{
		$.post('/index.php/Medical_backpanel/remove_stock_item/',
			{ "item_id":item_id
			 }, function(data){
				alert(data);
			});
		}
	}

</script>

 