<div class="row">
	<div class="col-md-6">
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
									echo '<td><input type="checkbox"  onchange="onChangeUpdate(this,'.$row->inv_id.')" '.$check.' ></td>';
									//echo '<td><a href="javascript:load_form_div(\'/IpdNew/ipd_Diagnosis_invoice_show/'.$row->inv_id.'\',\'show_charges_invoice\')" >'.$row->invoice_code.'</a></td>';
									echo '<td><a href="javascript:load_form(\'/PathLab/IPD_Invoice_Edit/'.$row->inv_id.'\')" >'.$row->invoice_code.'</a></td>';

									echo '<td>'.$row->str_date.'</td>';
									echo '<td>'.$row->charge_list.'</td>';
									echo '<td>'.$row->amount.'</td>';
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
	<div class="col-md-6" id="show_charges_invoice">

	</div>
</div>
<script>
function onChangeUpdate(cb,cd) {
		var check_value=0;
		if (cb.checked)
		{
			check_value=1;
		}
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
		
		$.post('/index.php/Ipd/Update_Invoice_ipd_credit_type',
		{ "inv_id":cd ,
		"ipd_credit_type": check_value,
		"<?=$this->security->get_csrf_token_name()?>":csrf_value}, function(data){
			alert("Value Update");
			
		});
	}
</script>