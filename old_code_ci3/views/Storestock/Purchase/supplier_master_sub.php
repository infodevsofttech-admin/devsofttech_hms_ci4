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
  <td><?=$supplier_data[$i]->name_supplier ?></td>
  <td>
	  <button onclick="load_form_div('/Storestock/SupplierEdit/<?=$supplier_data[$i]->sid ?>','test_div');" type="button" class="btn btn-primary">Edit</button>
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