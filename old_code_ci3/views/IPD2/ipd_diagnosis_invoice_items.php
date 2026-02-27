      <div class="row">
        <div class="col-ms-12 table-responsive table-condensed">
          <table class="table table-striped ">
			<tr>
				<th style="width: 10px">#</th>
				<th>Charges Group</th>
				<th>Charge Name</th>
				<th>Rate</th>
				<th>Qty</th>
				<th>Amount</th>
				<th></th>
			</tr>
			<?php
			$srno=1;
				foreach($invoiceDetails as $row)
				{ 
					echo '<tr>';
					echo '<td>'.$srno.'</td>';
					echo '<td>'.$row->desc.'</td>';
					echo '<td>'.$row->item_name.'</td>';
					echo '<td>'.$row->item_rate.'</td>';
					echo '<td>'.$row->item_qty.'</td>';
					echo '<td><i class="fa fa-inr"></i>'.$row->item_amount.'</td>';
					$srno=$srno+1;
					echo '<td></td>';
					echo '</tr>';
				}
			?>
			<!---- Total Show  ----->
			<tr>
				<th style="width: 10px">#</th>
				<th ></th>
				<th></th>
				<th></th>
				<th>Gross Total</th>
				<th><?=$invoiceGtotal[0]->Gtotal?></th>
				<th></th>
			</tr>
			<?php if($invoice_master[0]->discount_amount>0) {?>
			<tr>
				<th style="width: 10px">#</th>
				<th>Deduction</th>
				<th><?=$invoice_master[0]->discount_desc ?></th>
				<th></th>
				<th></th>
				<th><i class="fa fa-inr"></i>-<?=$invoice_master[0]->discount_amount ?></th>
				<th></th>
			</tr>
			<?php } ?>
			<tr>
				<th style="width: 10px">#</th>
				<th></th>
				<th></th>
				<th></th>
				<th>Net Amount</th>
				<th><i class="fa fa-inr"></i><?=$invoice_master[0]->net_amount?></th>
				<th></th>
			</tr>
			<?php if($invoice_master[0]->correction_amount>0) {?>
			<tr>
				<th style="width: 10px">#</th>
				<th>Correction</th>
				<th><?=$invoice_master[0]->correction_remark ?></th>
				<th></th>
				<th></th>
				<th><i class="fa fa-inr"></i>-<?=$invoice_master[0]->correction_amount ?></th>
				<th></th>
			</tr>
			<tr>
				<th style="width: 10px">#</th>
				<th></th>
				<th></th>
				<th></th>
				<th>Final Amount</th>
				<th><i class="fa fa-inr"></i><?=$invoice_master[0]->correction_net_amount?></th>
				<th></th>
			</tr>
			<?php } ?>
			</table>
		</div>
        <!-- /.col -->
      </div>
      <!-- /.row -->
	  <div class="row">
		<div class="col-md-6 invoice-col">
			<b>Prepared By : <?=$invoice_master[0]->prepared_by ?></b>
		</div>
		<div class="col-md-6 invoice-col">
        <b>Mode of Payment : </b><?=$invoice_master[0]->payment_mode_desc ?>
		</div>

	  </div>
	   