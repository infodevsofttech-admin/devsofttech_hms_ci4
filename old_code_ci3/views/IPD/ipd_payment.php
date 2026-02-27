<div class="row">
	<div class="col-md-12">
		
	</div>
</div>
<div class="row">
	<div class="col-md-8">
		<div class="box  box-info">
				<div class="box-header with-border">
				  <h3 class="box-title">Charges</h3>
				</div>
				<div class="box-body">
					<table class="table table-striped ">
							<tr>
								<th style="width: 10px">#</th>
								<th>Invoice ID.</th>
								<th>Ref.</th>
								<th>Inv.Date</th>
								<th>Charge Type</th>
								<th>Amount</th>
								<th></th>
							</tr>
							<?php
							$srno=1;
								foreach($inv_master as $row)
								{ 
									echo '<tr>';
									echo '<td>'.$srno.'</td>';
									$check='';
									if ($row->ipd_include>0)
									{
										$check='Checked';
									}
									echo '<td><input type="checkbox" class="flat-red" onchange="onChangeUpdate(this,'.$row->inv_id.')" '.$check.' ></td>';
									
									echo '<td><a data-toggle="modal" data-target="#myModal" data-invid="'.$row->inv_id.'" data-invtype="1" >'.$row->invoice_code.'</a></td>';
									echo '<td>'.$row->refer_by_other.'</td>';
									echo '<td>'.$row->str_date.'</td>';
									echo '<td>'.$row->charge_list.'</td>';
									echo '<td>'.$row->amount.'</td>';
									if ($this->ion_auth->in_group('admin')) {
										echo '<td><a href="javascript:load_form(\'/PathLab/IPD_Invoice_Edit/'.$row->inv_id.'\');"><i class="fa fa-circle-o"></i> IPD Invoice Edit</a></td>';
									}else{
										echo '<td></td>';
									}
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
								<th></th>
								<th><th/>
							</tr>
					</table>
				</div>
		</div>
	</div>
	<div class="col-md-4">
			<div class="row">
			<div class="col-md-12">
				<div class="box  box-success">
					<div class="box-header with-border">
					  <h3 class="box-title">Payments Details</h3>
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
									foreach($ipd_payment as $row)
									{ 
										echo '<tr>';
										echo '<td>'.$srno.'</td>';
										echo '<td><a href="Ipd/ipd_cash_print/0/'.$row->id.'/2" target="_blank" >'.$row->id.'</a>';
										echo '<td>'.$row->pay_date.'</td>';
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
									<th></th>
								</tr>
							</table>
					</div>
				</div>
			</div>
			<div class="col-md-12">
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
										echo '<td><a data-toggle="modal" data-target="#myModal" data-invid="'.$row->id.'"   data-invtype="0"  >'.$row->inv_med_code.'</a></td>';
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
	</div>
</div>
<script>
function onChangeUpdate(cb,cd) {
		var check_value=0;
		if (cb.checked)
		{
			check_value=1;
		}
		
		$.post('/index.php/Ipd/Update_Invoice_ipd_credit_type',
		{ "inv_id":cd ,
		"ipd_credit_type": check_value}, function(data){
			alert("Value Update");
			
		});
	}
</script>