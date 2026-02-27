<hr/>
<div class="row">
	<div class="col-md-12">
		<div id="supplier_list" class="box-body" style="height:500px;overflow-y:auto;">
		  <table id="report_list" class="table table-bordered table-striped TableData">
			<thead>
				<tr>
				  <th>Indent ID</th>
				  <th>Req. Type</th>
				  <th>Date</th>
				  <th>Product List</th>
				  <th>Status</th>
				  <th></th>
				</tr>
			</thead>
			<tbody>
			<?php for ($i = 0; $i < count($indent_req_list); ++$i) { ?>
			<tr>
			  <td><?=$indent_req_list[$i]->indent_code ?></td>
			  <td><?=$indent_req_list[$i]->indent_request_type_str ?></td>
			  <td><?=$indent_req_list[$i]->created_date ?></td>
			  <td><?=$indent_req_list[$i]->product_list ?></td>
			  <td><?=$indent_req_list[$i]->request_status_str?></td>
			  <td>
				<?php if($indent_req_list[$i]->request_status==0) { ?>
					<button onclick="load_form_div('/product_master/Indent_Request_Edit/<?=$indent_req_list[$i]->indent_id ?>','searchresult');" type="button" class="btn btn-warning">Edit</button>
				<?php }elseif($indent_req_list[$i]->request_status==1) { ?>
					<button onclick="load_form_div('/product_master/indent_request_item_list_view/<?=$indent_req_list[$i]->indent_id ?>','searchresult');" type="button" class="btn btn-success">View</button>
				<?php }elseif($indent_req_list[$i]->request_status==4) { ?>
					<button onclick="load_form_div('/stock/indent_store_accept_items_list/<?=$indent_req_list[$i]->indent_id ?>','searchresult');" type="button" class="btn btn-warning">Items List</button>
				<?php }else{ ?>
					<button onclick="load_form_div('/product_master/indent_request_item_list_view/<?=$indent_req_list[$i]->indent_id ?>','searchresult');" type="button" class="btn btn-warning">View</button>
				<?php } ?>
			  </td>
			</tr>
			<?php } ?>
			</tbody>
			<tfoot>
			<tr>
				<tr>
				  <th>Indent ID</th>
				  <th>Store</th>
				  <th>Date</th>
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

 