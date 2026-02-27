<div class="row">
	<div class="col-md-12">
		<div class="box box-primary">
			<div class="box-header with-border">
				<h3 class="box-title">Purchase Return</h3>
			</div>
			<div class="box-body">
				<table class="table table-bordered table-striped TableData">
					<thead>
						<tr>
							<th>Invoice No</th>
                            <th>Supplier Name</th>
							<th>Date</th>
							<th>Item Name</th>
							<th>Unit Qty</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$total_return_qty = 0;
						foreach ($return_purchase as $row) {
							$total_return_qty += $row->qty;
						?>
							<tr>
								<td><?php echo $row->p_r_invoice_no; ?></td>
                                <td><?php echo $row->name_supplier; ?></td>
								<td><?php echo $row->date_of_invoice; ?></td>
								<td><?php echo $row->Item_name; ?></td>
								<td><?php echo $row->qty; ?></td>
							</tr>
						<?php
						}
						?>
                        <tfoot>
						<tr>
							<th colspan="4">Total Return Qty</th>
							<th><?php echo $total_return_qty; ?></th>
						</tr>
                        </tfoot>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<div class="col-md-12">
		<div class="box box-primary">
			<div class="box-header with-border">
				<h3 class="box-title">Purchase Invoice (Return Adjustment)</h3>
			</div>
			<div class="box-body">
				<table class="table table-bordered table-striped TableData">
					<thead>
						<tr>
							<th>Invoice No</th>
                            <th>Supplier Name</th>
							<th>Date</th>
							<th>Item Name</th>
							<th>Qty</th>
                            <th>Packing</th>
                            <th>Unit Qty</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$total_invoice_qty = 0;
                        $total_unit_invoice_qty=0;
						foreach ($return_invoice_purchase as $row) {
							$total_invoice_qty += $row->qty;
                            $total_unit_invoice_qty += ($row->packing*$row->qty);
						?>
							<tr>
								<td><?php echo $row->Invoice_no; ?></td>
                                <td><?php echo $row->name_supplier; ?></td>
								<td><?php echo $row->date_of_invoice; ?></td>
								<td><?php echo $row->Item_name; ?></td>
								<td><?php echo $row->qty; ?></td>
                                <td><?php echo $row->packing; ?></td>
                                <td><?php echo $row->packing*$row->qty; ?></td>
                            </tr>
						<?php
						}
						?>
                        <tfoot>
						<tr>
							<th colspan="4">Total Invoice Qty</th>
							<th><?php echo $total_invoice_qty; ?></th>
                            <th></th>
                            <th><?php echo $total_unit_invoice_qty; ?></th>
						</tr>
                        </tfoot>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
