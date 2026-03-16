	<div class="row">
			<div class="col-md-12">
			<?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
			<div class="box box-danger">
				<div class="box-header">
					<div class="box-title">
					<div class="row">
						<div class="col-md-12">
							<p><strong>Name :</strong><?=$person_info[0]->p_fname?>    {<i><?=$person_info[0]->p_rname?></i>}
							<strong>/ Age :</strong><?=$person_info[0]->age?> 
							<strong>/ Gender :</strong><?=$person_info[0]->xgender?> 
							<strong>/ P Code :</strong><?=$person_info[0]->p_code?> 
							<strong>/ Organisation Invoice #</strong><?=$orgcase_master[0]->case_id_code?>
							<strong>/Organisation :</strong><?=$orgcase_master[0]->insurance_company_name?>
							</p>
							<input type="hidden" id="pid" value="<?=$person_info[0]->id?>" />
							<input type="hidden" id="pname" value="<?=$person_info[0]->p_fname?>" />
							
						</div>
					</div>
						<button onclick="add_invoice('<?=$orgcase_master[0]->p_id ?>','<?=$orgcase_master[0]->id ?>')" type="button" class="btn btn-primary">Add New</button>
					</div>
				</div>
				<div class="box-body">
					<div class="row " >
						<div class="col-md-12">
						<table class="table table-striped ">
							<tr>
								<th style="width: 10px">#</th>
								<th>Invoice ID.</th>
								<th>Patient</th>
								<th>Inv.Date</th>
								<th>Inv.Desc</th>
								<th>Credit/Cash</th>
								<th>Amount</th>
								<th>Balance</th>
								<th></th>					
							</tr>
							<?php
							$srno=1;
								foreach($inv_master as $row)
								{ 
									echo '<tr>';
									echo '<td>'.$srno.'</td>';
									echo '<td>'.$row->inv_med_code.'</td>';
									echo '<td>'.$row->inv_name.'</td>';
									echo '<td>'.$row->inv_date.'</td>';
									echo '<td>'.$row->remark_ipd.'</td>';
									echo '<td>'.$row->credit.'</td>';
									echo '<td>'.$row->net_amount.'</td>';
									echo '<td>'.$row->payment_balance.'</td>';
									echo '<td><button type="button" class="btn btn-primary" id="btn_remove" onclick="show_invoice('.$row->id.')">Show</button>
									<button type="button" class="btn btn-primary" id="btn_edit" onclick="edit_invoice('.$row->id.')">Edit</button></td>';
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
								<th></th>
							</tr>
						</table>
						</div>
					</div>
				</div>
			</div>	
			<?php echo form_close(); ?>
			</div>
		</div>
		
<script>

	function show_invoice(med_invoice_id){
		$('#tallModal_4').modal('hide');
		$('#tallModal_4').hide('hide');
		setTimeout(load_form_div('/Medical/final_invoice/'+med_invoice_id,'maindiv'), 2000); 
	}
	
	function edit_invoice(med_invoice_id){
		$('#tallModal_4').modal('hide');
		$('#tallModal_4').hide('hide');
		setTimeout(load_form_div('/Medical/Invoice_med_show/'+med_invoice_id,'maindiv'), 2000); 
	}
	
	function add_invoice(p_id,org_id){
		$('#tallModal_4').modal('hide');
		$('#tallModal_4').hide('hide');
		setTimeout(load_form_div('/Medical/Invoice_counter_new/'+p_id+'/0/'+org_id,'maindiv'), 2000); 
	}

</script>