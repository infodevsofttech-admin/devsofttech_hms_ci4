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
				  <th></th>
				</tr>
			</thead>
			<tbody>
			<?php for ($i = 0; $i < count($purchase_return_invoice); ++$i) { ?>
			<tr>
			  <td><?=$purchase_return_invoice[$i]->p_r_invoice_no ?></td>
			  <td><?=$purchase_return_invoice[$i]->name_supplier ?></td>
			  <td><?=$purchase_return_invoice[$i]->date_of_invoice ?></td>
			  <td>
				  	<button onclick="load_form_div('/Medical_backpanel/PurchaseReturnInvoiceEdit/<?=$purchase_return_invoice[$i]->id ?>','searchresult');" type="button" class="btn btn-warning">View & Edit</button>
					  <a href="<?php echo '/Medical_Print/print_purchase_return/'.$purchase_return_invoice[$i]->id;  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print</a>
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
					<th></th>
					</tr>
				</tr>
			</tfoot>
		  </table>
		 <script>
			$('#report_list').dataTable();

			function delete_invoice(inv_id)
			{
				if(confirm('Are You sure ,Delete this Invoice'))
				{
					$.post('/index.php/Medical/purchase_invoice_delete/'+inv_id, $('form.form2').serialize(), function(data){
					if(data.is_delete==0)
					{
						notify('error','Please Attention',data.show_text);
					}else
					{
						notify('success','Please Attention','Delete Successfully');
					}
				}, 'json');
				}
			}
		  </script>
		</div>	
	</div>
</div>

 