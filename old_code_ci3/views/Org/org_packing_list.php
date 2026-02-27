<table id="report_list" class="table table-bordered table-striped TableData">
			<thead>
				<tr>
                    <th>Sr.No</th>
				    <th>Org. Code</th>
				    <th>Patient Name</th>
				    <th>Claim No.</th>
				    <th>IPD/OPD</th>
				    <th>Action</th>
				</tr>
			</thead>
			<tbody>
			<?php for ($i = 0; $i < count($org_packing_list); ++$i) { 
				echo '<tr>';
			?>
            	<td><?=$i+1 ?></td>
			  	<td><?=$org_packing_list[$i]->case_id_code ?></td>
			  	<td><?=$org_packing_list[$i]->p_fname ?></td>
			  	<td><?=$org_packing_list[$i]->insurance_no_1 ?></td>
			  	<td><?=$org_packing_list[$i]->IPD_OPD ?></td>
			  	<td>
				  	<button onclick="remove_item_list(<?=$org_packing_list[$i]->id ?>,<?=$packing_id?>);" type="button" class="btn btn-warning">Remove</button>
			  	</td>
			</tr>
			<?php } ?>
			</tbody>
			<tfoot>
				<tr>
                        <th>Sr.No</th>
                        <th>Org. Code</th>
                        <th>Patient Name</th>
                        <th>Claim No.</th>
                        <th>IPD/OPD</th>
                        <th>Action</th>
				</tr>
			</tfoot>
		  </table>
		  