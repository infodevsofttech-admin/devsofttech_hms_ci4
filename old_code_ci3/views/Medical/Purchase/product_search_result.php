<hr/>
<div class="row">
	<div class="col-md-12">
		<div id="supplier_list" class="box-body" style="height:500px;overflow-y:auto;">
		  <table id="report_list" class="table table-bordered table-striped TableData">
			<thead>
				<tr>
					<th>Prod. ID</th>
				  	<th>Name</th>
				  	<th>Formulation</th>
				  	<th>Generic Name</th>
				  	<th>Packing Type</th>
				  	<th></th>
				</tr>
			</thead>
			<tbody>
			<?php for ($i = 0; $i < count($product_list); ++$i) { ?>
			<tr>
				<td><?=$product_list[$i]->id ?></td>
				<td><?=$product_list[$i]->item_name ?></td>
				<td><?=$product_list[$i]->formulation ?></td>
				<td><?=$product_list[$i]->genericname ?></td>
				<td><?=$product_list[$i]->packing ?></td>
				<td>
					<button onclick="load_form_div('/Product_master/Product_edit/<?=$product_list[$i]->id ?>','searchresult');" type="button" class="btn btn-warning">Edit</button>
					<button onclick="load_form_div('/Product_master/Product_delete/<?=$product_list[$i]->id ?>','searchresult');" type="button" class="btn btn-warning">Delete</button>
				</td>
			</tr>
			<?php } ?>
			</tbody>
			<tfoot>
			<tr>
				<tr>
					<th>Prod. ID</th>
				  	<th>Name</th>
				  	<th>Formulation</th>
				  	<th>Generic Name</th>
				  	<th>Packing Type</th>
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

 