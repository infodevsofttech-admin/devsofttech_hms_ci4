<div class="row">
	<div class="col-md-6">
		<div class="box  box-success">
			<div class="box-header with-border">
				<h3 class="box-title">IPD Payments Details</h3>
			</div>
			<div class="box-body">
				
				<table class="table table-striped ">
						<tr>
							<th style="width: 10px">#</th>
							<th>ID.</th>
							<th>Payment Date</th>
							<th>Amount</th>
							<th>Mode</th>
						</tr>
						<?php
						$srno=1;
							foreach($ipd_payment_history as $row)
							{ 
								echo '<tr>';
								echo '<td>'.$srno.'</td>';
								echo '<td><a href="IpdNew/ipd_cash_print_pdf/'.$row->payof_id.'/'.$row->id.'" target="_blank" >'.$row->id.'</a>';
								echo '<td>'.$row->pay_date_str.'</td>';
								echo '<td>'.$row->amount.'</td>';
								echo '<td>'.$row->pay_mode.'</td>';
								$srno=$srno+1;
								echo '</tr>';
							}
						echo '<input type="hidden" id="srno" name="srno" value="'.$srno.'" />';
						?>
						<!---- Total Show  ----->
						<tr>
							<th style="width: 10px">#</th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
						</tr>
					</table>
			</div>
		</div>
	</div>
	<div class="col-md-6">
		<div class="box">
			<div class="box-header with-border">
				<h3 class="box-title">Medicine</h3>
			</div>
			<div class="box-body">
			<div class="row">
				<table class="table table-striped ">
						<tr>
							<th style="width: 10px">#</th>
							<th>Invoice ID.</th>
							<th>Inv.Date</th>
							<th>Amount</th>
						</tr>
						<?php
						$srno=1;
							foreach($inv_med_master as $row)
							{ 
								echo '<tr>';
								echo '<td>'.$srno.'</td>';
								echo '<td><a href="javascript:'.$row->id.'" >'.$row->inv_med_code.'</a></td>';
								echo '<td>'.$row->inv_date.'</td>';
								echo '<td>'.$row->net_amount.'</td>';
								$srno=$srno+1;
								echo '</tr>';
							}
						echo '<input type="hidden" id="srno" name="srno" value="'.$srno.'" />';
						?>
						<!---- Total Show  ----->
						<tr>
							<th style="width: 10px">#</th>
							<th></th>
							<th></th>
							<th></th>
							
						</tr>
					</table>
			</div>
			</div>
		</div>
	</div>
</div>
