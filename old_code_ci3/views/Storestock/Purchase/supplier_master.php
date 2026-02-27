<section class="content-header">
    <h1>
        Supplier
    </h1>
</section>
<section class="content">
<div class="row">
	<div class="col-md-6">
		<div class="box">
		<div class="box-header">
		  
			<div class="col-md-6"><h3 class="box-title">Supplier List</h3></div>
			<div class="col-md-2"></div>
			<div class="col-md-2">
				<button onclick="load_form_div('/Storestock/SupplierEdit/0','test_div','Supplier : New Supplier :Pharma');" type="button" class="btn btn-primary">Add New</button></div>
			
		</div>
		<!-- /.box-header -->
		<div id="supplier_list" class="box-body" style="height:500px;overflow-y:auto;">
		  <table id="report_list" class="table table-bordered table-striped TableData">
			<thead>
				<tr>
				  <th>Name</th>
				  <th>GST</th>
				  <th>Action</th>
				 </tr>
			</thead>
			<tbody>
			<?php for ($i = 0; $i < count($supplier_data); ++$i) { ?>
			<tr>
			  <td><?=$supplier_data[$i]->name_supplier ?></td>
			  <td><?=$supplier_data[$i]->gst_no ?></td>
			  <td>
				  <button onclick="load_form_div('/Storestock/SupplierEdit/<?=$supplier_data[$i]->sid ?>','test_div','Supplier :<?=$supplier_data[$i]->name_supplier ?>:Pharma');" type="button" class="btn btn-primary">Edit</button>
			  </td>
			</tr>
			<?php } ?>
			</tbody>
			<tfoot>
			<tr>
				<th>Name</th>
				<th>GST</th>
				<th>Action</th>
			</tr>
			</tfoot>
		  </table>
		 <script>
			$('#report_list').dataTable();
		  </script>
		</div>
		<!-- /.box-body -->
		</div>
		</div>
	<div class="col-md-6" id="test_div">
		
	</div>
	</div>
</div>
</section>
 