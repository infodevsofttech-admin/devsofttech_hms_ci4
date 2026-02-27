
<table class="table table-bordered table-striped TableData" style="border: 1px solid black;">
	<thead>
		<tr>
			<th style="width:300px;">Item Name</th>
			<th>Pur Pak.</th>
			<th>Pur Cost</th>
			<th>Current Pak.</th>
			<th>Current Unit Qty</th>
			<th>Total Sale Pak.</th>
			<th>Total Sale Unit Qty</th>
			<th>Stock Cost</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($stock_list as $row) { ?>
			<tr>
				<td>
					<p class="text-danger"><?= $row->item_name ?></p>
				</td>
				<td>
					<p class="text-warning"><?= Round($row->total_pak_qty, 2) ?></p>
				</td>
				<td>
					<p class="text-warning"><?= Round($row->purchase_cost, 2) ?></p>
				</td>
				<td>
					<p class="text-warning"><?= Round($row->C_Pak_Qty, 2) ?></p>
				</td>
				<td>
					<p class="text-warning"><?= Round($row->C_Unit_Stock_Qty, 2) ?></p>
				</td>
				<td>
					<p class="text-warning"><?= Round($row->C_Pak_Sale_Qty, 2) ?></p>
				</td>
				<td>
					<p class="text-warning"><?= Round($row->sale_unit, 2) ?></p>
				</td>
				<td>
					<p class="text-danger"><?= Round($row->total_lost_unit, 2) ?></p>
				</td>
				<td>
					<p class="text-warning"><?= $row->packing ?>/<?= $row->re_order_qty ?></p>
