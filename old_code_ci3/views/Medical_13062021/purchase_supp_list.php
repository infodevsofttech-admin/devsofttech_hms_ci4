<hr/>
<div class="row">
	<div class="col-md-12">
		<div id="supplier_list" class="box-body" style="height:500px;overflow-y:auto;">
		  <table id="report_list" class="table table-bordered table-striped TableData">
			<thead>
				<tr>
				  <th>Invoice ID</th>
				  <th>Supplier</th>
				  <th>Date</th>
				  <th>Amount</th>
				  <th></th>
				</tr>
			</thead>
			<tbody>
			<?php for ($i = 0; $i < count($purchase_list); ++$i) { ?>
			<tr>
			  <td><?=$purchase_list[$i]->Invoice_no ?></td>
			  <td><?=$purchase_list[$i]->name_supplier ?></td>
			  <td><?=$purchase_list[$i]->date_of_invoice ?></td>
			  <td><?=$purchase_list[$i]->tamount ?></td>
			  <td>
				  <button onclick="load_form_div('/Medical/PurchaseInvoiceEdit/<?=$purchase_list[$i]->id ?>','searchresult');" type="button" class="btn btn-warning">Edit</button>
			  </td>
			</tr>
			<?php } ?>
			</tbody>
			<tfoot>
			<tr>
				<tr>
				  <th>Invoice ID</th>
				  <th>Supplier</th>
				  <th>Date</th>
				  <th>Amount</th>
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

 