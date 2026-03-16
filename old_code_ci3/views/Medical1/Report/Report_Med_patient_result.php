<div class="row">
	<div class="col-md-12">
		  <table id="report_list" class="table table-bordered table-striped TableData">
				<thead>
					<tr>
					  <th style="width:300px;">Item Name</th>
					  <th>Sale Drug Report Customer Wise</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($stock_list as $row) { ?>
						<tr>
							<td >
								<p class="text-danger"><?=$row->item_Name ?></p> 
							</td>
							<td>
								<?php
									$data_array=explode("#",$row->p_list);
									echo '<table class="table table-condensed">
											<tr>
												<th style="background-color: #F69454;width:80px;">Inv.No.</th>
												<th style="width:100px;">Inv.Date</th>
												<th style="background-color: #F69454;width:50px;">IPD No.</th>
												<th style="width:50px;">P Code/Name</th>
												<th style="background-color: #F69454;width:50px;">Exp. Date</th>
												<th style="width:50px;">Batch</th>
												<th style="background-color: #F69454;width:50px;">Qty</th>
											</tr>';
									foreach ($data_array as $value) {
										$value_array=explode(";",$value);

										if(count($value_array)<9)
										{
											echo '<tr>';
											echo '	<td colspan=7 style="background-color: Red;color:white">Too many Records, Select min Date Range</td>';
											echo '</tr>';
										}else{
											echo '<tr>';
											echo '	<td style="background-color: #F69454;">'.$value_array[0].'</td>';
											echo '	<td >'.$value_array[1].'</td>';
											echo '	<td style="background-color: #F69454; ">'.$value_array[7].'</td>';
											echo '	<td  >'.$value_array[4].' / '.$value_array[5].'</td>';
											echo '	<td style="background-color: #F69454; ">'.$value_array[2].'</td>';
											echo '	<td    align="right">'.$value_array[8].'</td>';
											echo '	<td  style="background-color: #F69454; " align="right">'.$value_array[9].'</td>';
											echo '</tr>';
										}
										
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
