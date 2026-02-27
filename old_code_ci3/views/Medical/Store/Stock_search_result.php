
<div class="row">
	<div class="col-md-12">
		<div id="supplier_list_1" class="box-body" style="height:500px;overflow-y:auto;">
		  <table id="report_list" class="table table-bordered table-striped TableData">
				<thead>
					<tr>
					  <th style="width:300px;">Item Name</th>
					  <th>Current Pak.</th>
					  <th>Current Unit Qty</th>
					  <th>Total Sale Pak.</th>
					  <th>Total Sale Unit Qty</th>
					  <th>Lost Unit</th>
					  <th>Package/Re-Order Qty</th>
					  <th>Removed Qty</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($stock_list as $row) { ?>
						<tr>
							<td >
								<p class="text-danger">
								<a href="#<?=$row->id ?>"
								data-toggle="modal"
								data-target="#tallModal" 
								data-product_id="<?=$row->id ?>"
								data-product_pak="<?=$row->packing ?>"
								data-etype="1"
								data-productname="<?=$row->item_name ?>"
								><?=$row->item_name ?></a> 
								<?php
									if(strlen($row->genericname)>0){
										echo '<br/><i>'.$row->genericname.'</i>';
									}
								?></p> 
							</td>
							<td>
								<p class="text-warning"><?=$row->C_Pak_Qty?></p>
							</td>
							<td>
								<p class="text-warning"><?=Round($row->C_Unit_Stock_Qty,2) ?></p>
							</td>
							<td>
								<p class="text-warning"><?=Round($row->C_Pak_Sale_Qty,2) ?></p>
							</td>
							<td>
								<p class="text-warning"><?=Round($row->sale_unit,2) ?></p>
							</td>
							<td>
								<p class="text-danger"><?=Round($row->total_lost_unit,2) ?></p>
							</td>
							<td>
								<p class="text-warning"><?=$row->packing ?>/<?=$row->re_order_qty ?></p>
							</td>
							<td>
							<button type="button" class="btn btn-primary" 
								data-toggle="modal"
								data-target="#tallModal" 
								data-product_id="<?=$row->id ?>"
								data-product_pak="<?=$row->packing ?>"
								data-etype="3"
								data-productname="<?=$row->item_name ?>">Return Info</button>
							</td>
						</tr>
					<?php } ?>
				</tbody>
				<tfoot>
					<tr>
					  <th style="width:300px;">Item Name </th>
					  <th>Current Pak.</th>
					  <th>Current Unit Qty</th>
					  <th>Total Sale Pak.</th>
					  <th>Total Sale Unit Qty</th>
					  <th>Lost Unit</th>
					  <th>Package/Re-Order Qty</th>
					  <th>Removed Qty</th>
					</tr>
				</tfoot>
		  </table>
		 <script>
			$('#report_list').dataTable();
		  </script>
		</div>	
	</div>
</div>
<div class="modal modal-wide fade"  id="tallModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  	<div class="modal-dialog modal-lg" role="document" style="width: 100%;height: 100%;margin: 0;padding: 0;">
		<div class="modal-content" style="height: 100%; border-radius: 0;">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="testentryLabel">Test Name</h4>
			</div>
			<div class="modal-body" style="overflow : scroll;">
				<div class="row">
					<div class="testentry-bodyc" id="testentry-bodyc">
						
					</div>
				</div>
			</div>
		</div>
    </div>
</div>
<script>
	function update_sale_stock(item_id)
	{
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		if(confirm("Are you sure Update Sale Qty "))
		{
		$.post('/index.php/Medical_backpanel/update_stock_adjust/',
			{ "qty": $('#input_qty_update_'+item_id).val(),
				"item_id":item_id,
				'<?=$this->security->get_csrf_token_name()?>':csrf_value
			 }, function(data){
				notify('success','Please Attention',data);
			});
		}
	}
	
	function remove_sale_stock(item_id)
	{
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		if(confirm("Are you sure Remove This Item From List "))
		{
		$.post('/index.php/Medical_backpanel/remove_stock_item/',
			{ "item_id":item_id,
				'<?=$this->security->get_csrf_token_name()?>':csrf_value
			 }, function(data){
				
				notify('success','Please Attention',data);

			});
		}
	}

	function add_sale_stock(item_id)
	{
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		if(confirm("Are you sure Add This Item From Removed List "))
		{
		$.post('/index.php/Medical_backpanel/add_stock_item/',
			{ "item_id":item_id,
				'<?=$this->security->get_csrf_token_name()?>':csrf_value
			 }, function(data){
				notify('success','Please Attention',data);
			});
		}
	}

	$('#tallModal').on('shown.bs.modal', function (event) {
		$('.testentry-bodyc').html('');
		
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
		
		var height = $(window).height() - 50;
		$(this).find(".modal-body").css("max-height", height);

		var button = $(event.relatedTarget);
		var product_id= button.data('product_id');
		var product_pak= button.data('product_pak');
		var etype = button.data('etype');
		var productname= button.data('productname');

		$('#testentryLabel').html(productname);
		
		if(etype=='1')
		{
			$.post('/index.php/Medical_backpanel/get_product_stock/'+product_id,
			{ 	"product_id": product_id,
				"product_pak": product_pak,
				"productname": productname,
				"date_range":"0",
                "input_supplier":"0",
				'<?=$this->security->get_csrf_token_name()?>':csrf_value }, function(data){
			$('#testentry-bodyc').html(data);
			});
		}
		
		if(etype=='2')
		{
			$.post('/index.php/Medical_backpanel/get_product_stock_removed/'+product_id,
			{ 	"product_id": product_id,
				"productname": productname,
				"date_range":"0",
                "supplier_id":"0",
				'<?=$this->security->get_csrf_token_name()?>':csrf_value }, function(data){
			$('#testentry-bodyc').html(data);
			});
		}

		if(etype=='3')
		{
			$.post('/index.php/Medical_backpanel/get_product_stock_return/'+product_id,
			{ 	"product_id": product_id,
				"productname": productname,
				"date_range":"0",
                "supplier_id":"0",
				'<?=$this->security->get_csrf_token_name()?>':csrf_value }, function(data){
			$('#testentry-bodyc').html(data);
			});
		}
	});

</script>

 