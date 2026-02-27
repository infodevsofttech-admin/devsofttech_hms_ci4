<section class="content-header">
    <h1>
        Supplier
    </h1>
</section>
<section class="content">
<div class="row">
	<div class="col-md-12">
		<div class="box">
		<div class="box-header">
            <h3 class="box-title">Supplier List</h3>
			<div class="box-tools">
                    <a href="javascript:load_form_div('/Medical_backpanel/payment_supplier','maindiv')" class="btn btn-success btn-sm">Ledger</a>
                </div>
		</div>
		<!-- /.box-header -->
		<div id="supplier_list" class="box-body" style="height:500px;overflow-y:auto;">
		  <table id="report_list" class="table table-bordered table-striped TableData">
			<thead>
				<tr>
				  <th>Name</th>
				  <th>Balance</th>
				  <th>Last Inv. Date</th>
                  <th>Last Payment Date</th>
                  <th></th>
				 </tr>
			</thead>
			<tbody>
			<?php for ($i = 0; $i < count($supplier_data); ++$i) { ?>
			<tr>
			  <td><?=$supplier_data[$i]->name_supplier ?></td>
			  <td><?=$supplier_data[$i]->Tot_Balance ?></td>
              <td><?=$supplier_data[$i]->Last_InvDate ?></td>
              <td><?=$supplier_data[$i]->Last_Payment ?></td>
			  <td>
				  <button onclick="load_form_div('/Medical_backpanel/SupplierAccount_led/<?=$supplier_data[$i]->sid ?>','maindiv');" type="button" class="btn btn-primary">Open</button>
			  </td>
			</tr>
			<?php } ?>
			</tbody>
			<tfoot>
                <tr>
				  <th>Name</th>
				  <th>Balance</th>
				  <th>Last Inv. Date</th>
                  <th>Last Payment Date</th>
                  <th></th>
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
	</div>
</div>
</section>
 