<table id="report_list" class="table table-bordered table-striped TableData">
			<thead>
				<tr>
				  <th>Org. Code</th>
				  <th>Patient Name</th>
				  <th>Claim No.</th>
				  <th>IPD/OPD</th>
				  <th>Action</th>
				</tr>
			</thead>
			<tbody>
			<?php for ($i = 0; $i < count($org_list); ++$i) { 
				echo '<tr>';
			?>
			  <td><?=$org_list[$i]->case_id_code ?></td>
			  <td><?=$org_list[$i]->p_fname ?></td>
			  <td><?=$org_list[$i]->insurance_no_1 ?></td>
			  <td><?=$org_list[$i]->IPD_OPD ?></td>
			  <td>
				  <?php 
				  	if($org_list[$i]->packing_id>0 )
					{ 
						echo 'Already Added'; 
					}else{
						if($org_list[$i]->case_type==$list_type)
						{
					?>
							<button onclick="Add_list(<?=$org_list[$i]->id ?>,<?=$packing_id?>);" type="button" class="btn btn-warning">Add in List</button>
					<?php
					 	}else{
							if($list_type==0)
							{
								echo 'IPD Org. Case';
							}else{
								echo 'OPD Org. Case';
							}
						 }
					  } ?>
				</td>
			</tr>
			<?php } ?>
			</tbody>
			<tfoot>
				<tr>
					<th>Org. Code</th>
					<th>Patient Name</th>
					<th>Claim No.</th>
					<th>IPD/OPD</th>
					<th>Action</th>
				</tr>
			</tfoot>
		  </table>
		  