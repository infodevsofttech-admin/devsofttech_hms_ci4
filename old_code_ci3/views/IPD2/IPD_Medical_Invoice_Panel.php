<div class="row">
	<div class="col-md-12">
		
	</div>
</div>
<div class="row">
	<div class="col-md-6">
		<div class="box  box-info">
			<div class="box-header with-border">
			  <h3 class="box-title">IPD Credit [Include in Bill]</h3>
			  <a href="/Medical_Print/invoice_print_all/<?=$ipd_id?>/1" target="_blank" class="btn btn-danger"><i class="fa fa-print"></i> Print All IPD  Credit Bill</a>
			  <a href="<?php echo '/Medical_Print/medicine_list_print_all/'.$ipd_id.'/-1';  ?>" target="_blank" class="btn btn-warning"><i class="fa fa-file-pdf-o"></i> Print Medicine List</a>
			</div>
			<div class="box-body">
				<table class="table table-striped ">
						<tr>
							<th style="width: 10px">#</th>
							<th>Invoice ID.</th>
							<th>Patient</th>
							<th>Inv.Date</th>
							<th>Amount</th>
							<th>Inc.Invoice</th>
							<th></th>					
						</tr>
						<?php
						$srno=1;
							foreach($inv_master_credit as $row)
							{ 
								echo '<tr>';
								echo '<td>'.$srno.'</td>';
								echo '<td>'.$row->inv_med_code.'</td>';
								echo '<td>'.$row->inv_name.'</td>';
								echo '<td>'.$row->inv_date.'</td>';
								echo '<td>'.$row->net_amount.'</td>';
								$check='';
								if ($row->ipd_credit_type>0)
								{
									$check='Checked';
								}
								echo '<td><input type="checkbox" onchange="onChangeUpdate(this,'.$row->id.')" '.$check.' ></td>';
								echo '<td><button type="button" class="btn btn-primary" id="btn_remove" onclick="load_form_div(\'/IpdNew/list_med_inv_details/'.$row->ipd_id.'/'.$row->id.'\',\'show_med_invoice\')">Show Invoice</button></td>';
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
							<th></th>
							<th></th>
							<th></th>
						</tr>
					</table>
					
			</div>
		</div>
	</div>
	<div class="col-md-6" id="show_med_invoice">
	
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
		
		$.post('/index.php/Medical/Update_Invoice_ipd_credit_type',
		{ "inv_med_id":cd ,
			"<?=$this->security->get_csrf_token_name()?>":csrf_value,
			"ipd_credit_type": check_value}, function(data){
			alert("Value Update");
		
		});
	}
	
	
	
</script>
	