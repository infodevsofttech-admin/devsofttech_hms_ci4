
<div class="row">
	<div class="col-md-12">
		<div id="supplier_list_1" class="box-body" style="height:500px;overflow-y:auto;">
		  <table id="report_list" class="table table-bordered table-striped TableData">
				<thead>
					<tr>
					  <th style="width:300px;">Item Name</th>
					  <th>Stock Batch Invoice Status</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($stock_list as $row) { ?>
						<tr>
							<td >
								<p class="text-danger"><?=$row->Item_name ?></p> 
								<p class="text-secondary">Supplier :<?=$row->name_supplier ?></p>
								<p class="text-warning">Current Total Stock : <?=$row->Current_Stock ?></p>
							</td>
							<td>
								<?php
									$data_array=explode("#",$row->Qty_BatchWise);
									echo '<table class="table table-condensed">
											<tr>
												<th style="background-color: #F69454;width:50px;">Inv.No.</th>
												<th style="width:50px;">Bat.No.</th>
												<th style="background-color: #F69454;width:50px;">Exp.Dt.</th>
												<th style="width:150px;">MRP/Purchase Price/Date</th>
												<th style="background-color: #F69454;width:50px;">Qty</th>
												<th style="width:50px;">T.Unit Qty.</th>
												<th style="background-color: #F69454;width:50px;">Cur.Unit Qty.</th>
												<th style="width:50px;">Sale Unit</th>
												<th style="background-color: #F69454;width:50px;">Return Unit</th>
												<th style="width:50px;">Lost Unit</th>
												<th style="width:100px;">Update New Lost Units</th>
												<th style="background-color: #F69454;width:50px;">Remove Unit</th>
											</tr>';
									foreach ($data_array as $value) {
										$value_array=explode(";",$value);
										
										if($value_array[8]==1)
										{
											$style='style="color:Red;"';
										}else{
											$style='';
										}
										
										echo '<tr '.$style.'>';
										echo '	<td style="background-color: #F69454;">'.$value_array[1].'</td>';
										echo '	<td >'.$value_array[2].'</td>';
										echo '	<td style="background-color: #F69454; ">'.$value_array[3].'</td>';
										echo '	<td  >'.$value_array[4].'/'.$value_array[12].'/'.$value_array[13].'</td>';
										echo '	<td style="background-color: #F69454; ">'.$value_array[5].'</td>';
										echo '	<td    align="right">'.$value_array[11].'</td>';
										echo '	<td style="background-color: #F69454; " align="right"><div id="Cur_unit_qty_'.$value_array[0].'">'.$value_array[6].'</div></td>';
										echo '	<td   align="right">'.$value_array[7].'</td>';
										echo '	<td style="background-color: #F69454; " align="right">'.$value_array[9].'</td>';
										echo '	<td   align="right">'.$value_array[10].'</td>';
										echo '	<td > <div class="input-group">
														<input class="form-control number input-sm " 
														type="text" size="5" 
														id="input_qty_update_'.$value_array[0].'" 
														name="input_qty_update_'.$value_array[0].'" 
														value="0" >
														<span class="input-group-btn">
															<a class="btn" href="javascript:update_sale_stock(\''.$value_array[0].'\');"><i class="fa fa-edit" ></i></a>
														</span>
													</div>
												</td>';
										echo '<td style="background-color: #F69454; "><a class="btn" href="javascript:remove_sale_stock(\''.$value_array[0].'\');"><i class="fa fa-remove" style="color:red"></i></a></td>';
										echo '</tr>';
									}
									echo '</table>';
								?>								
							</td>
						</tr>
					<?php } ?>
				</tbody>
				<tfoot>
					<tr>
					  <th>Item Name</th>
					  <th>Stock Batch Invoice Status</th>
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

 