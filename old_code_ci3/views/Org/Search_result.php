<hr/>
<div class="row">
	<div class="col-md-12">
		<div id="supplier_list" class="box-body" style="height:500px;overflow-y:auto;">
		  <table id="report_list" class="table table-bordered table-striped TableData">
			<thead>
				<tr>
				  <th>Packing Label</th>
				  <th>Date</th>
				  <th>Type</th>
				  <th>No. of Case</th>
				  <th>Status</th>
				  <th></th>
				</tr>
			</thead>
			<tbody>
			<?php for ($i = 0; $i < count($packing_list); ++$i) { 
				echo '<tr>';
			?>
			
			  <td><?=$packing_list[$i]->label_no ?></td>
			  <td><?=$packing_list[$i]->d_o_c ?></td>
			  <td><?=$packing_list[$i]->list_type ?></td>
			  <td><?=$packing_list[$i]->No_Rec ?></td>
			  <td><?=$packing_list[$i]->files_status ?></td>
			  <td>
				  	<button onclick="load_form_div('/Org_Packing/PackNewEdit/<?=$packing_list[$i]->id ?>','searchresult');" type="button" class="btn btn-warning">View & Edit</button>
			  </td>
			</tr>
			<?php } ?>
			</tbody>
			<tfoot>
				<tr>
					<tr>
						<th>Packing Label</th>
						<th>Date</th>
						<th>Type</th>
						<th>No. of Case</th>
						<th>Status</th>
						<th></th>
					</tr>
				</tr>
			</tfoot>
		  </table>
		 <script>
			$('#report_list').dataTable();

		  </script>
		</div>	
	</div>
</div>

 