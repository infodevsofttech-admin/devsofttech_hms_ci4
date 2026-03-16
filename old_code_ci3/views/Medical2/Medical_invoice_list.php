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
							<strong>/ No of Days :</strong><?=$ipd_list[0]->no_days?> 
							</p>
							<input type="hidden" id="pid" value="<?=$person_info[0]->id?>" />
							<input type="hidden" id="pname" value="<?=$person_info[0]->p_fname?>" />
							<input type="hidden" id="Ipd_ID" value="<?=$ipd_master[0]->id?>" />
						</div>
						</div>
						<?php
						if($ipd_master[0]->ipd_status==0)
						{
						?>
						<button onclick="add_invoice('<?=$ipd_master[0]->p_id ?>','<?=$ipd_master[0]->id ?>')" type="button" class="btn btn-primary">Add New</button>
						
						<?php
							if($ipd_master[0]->lock_medical==0)
							{
							?>
								<button onclick="Lock('<?=$ipd_master[0]->id ?>','1')" type="button" class="btn btn-primary">Lock IPD for Final</button>
							<?php
							}else{
							?>
								<button onclick="Lock('<?=$ipd_master[0]->id ?>','0')" type="button" class="btn btn-primary">Unlock</button>
							<?php
							}
						}
						?>
					</div>
				</div>
				<div class="box-body">
					<?php if($ipd_master[0]->case_id>0) { ?>
						<div class="row">
							<div class="col-md-12">
								<strong>IPD Code :</strong><?=$ipd_master[0]->ipd_code?> 
								<b>Admit Date : </b><?=$ipd_list[0]->str_register_date ?> <?=$ipd_list[0]->reg_time ?> /
								<b>Discharge Date : </b><?=$ipd_list[0]->str_discharge_date ?> <?=$ipd_list[0]->discharge_time ?> /
								<b>No. of Days : </b><?php if($ipd_list[0]->no_days==0) {echo '1';} else {echo $ipd_list[0]->no_days; } ?> /
								<?php if(count($bed_list)>0){   ?>
									<b>Bed No : </b><?=$bed_list[0]->bed_no ?> <?=$bed_list[0]->room_name ?><br>
								<?php } ?>
							</div>
						</div>
						<?php }   ?>
					<?php if($ipd_master[0]->case_id>0) { ?>
						<div class="row">
							<div class="col-md-12">
								<b>Organisation Code #</b><?=$orgcase[0]->case_id_code?> /
								<b>Organisation :</b><?=$insurance[0]->ins_company_name?> 
							</div>
						</div>
						<?php } ?>
					<div class="row " >
						<div class="col-md-12">
						<br/>
							<table class="table table-striped ">
							<tr>
								<th style="width: 10px">#</th>
								<th>Invoice ID.</th>
								<th>Inv.Date</th>
								<th>Inv.Desc</th>
								<th>Credit/Cash</th>
								<th>Package</th>
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
									echo '<td>'.$row->inv_date.'</td>';
									echo '<td>'.$row->remark_ipd.'</td>';
									echo '<td>'.$row->credit.'</td>';
									echo '<td>'.$row->Package.'</td>';
									echo '<td>'.$row->net_amount.'</td>';
									echo '<td>'.$row->payment_balance.'</td>';
									if($ipd_master[0]->ipd_status==0)
									{
										echo '<td><button type="button" class="btn btn-primary" id="btn_remove" onclick="show_invoice('.$row->id.')">Show</button>
										<button type="button" class="btn btn-primary" id="btn_edit" onclick="edit_invoice('.$row->id.')">Edit</button></td>';
									}else{
										echo '<td>
												<a href="/Medical/invoice_print/'.$row->id.'" target="_blank">Print Invoice</a>
										</td>';
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
								<th></th>
								<th></th>
								<th></th>
								<th></th>
							</tr>
						</table>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<table class="table table-striped ">
								<tr>
									<td>
										Total Cash : <b><?=$inv_master_total[0]->cash_netAmount ?></b>
									</td>
									<td>
										
									</td>
									<td>
										Total Credit : <b><?=$inv_master_total[0]->IPDCrAmount ?></b>
									</td>
									<td>
										
									</td>
									<td>
										Total Package : <b><?=$inv_master_total[0]->PackageCrAmount ?></b>
									</td>
									<td>
										
									</td>
									<td>
										Total Amount : <b><?=$inv_master_total[0]->t_net_amount ?></b>
									</td>
									<td>
										
									</td>
									<td>
										Total Balance : <b><?=$inv_master_total[0]->t_payment_balance ?></b>
									</td>
									<td>
										
									</td>
								<tr>
							</table>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<table class="table table-striped ">
								<tr>
									<td>
										<a href="<?php echo '/Medical/invoice_print_all/'.$ipd_master[0]->id.'/0';  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print Cash</a>
									</td>
									<td>
										
									</td>
									<td>
										<a href="<?php echo '/Medical/invoice_print_all/'.$ipd_master[0]->id.'/1';  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print IPD Credit</a>
									</td>
									<td>
										
									</td>
									<td>
										<a href="<?php echo '/Medical/invoice_print_all/'.$ipd_master[0]->id.'/2';  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print IPD Package</a>
									</td>
									<td>
										
									</td>
									<td>
										<a href="<?php echo '/Medical/invoice_print_all/'.$ipd_master[0]->id.'/3';  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print Total</a>
									</td>
									<td>
										
									</td>
									<td>
										
									</td>
									<td>
										
									</td>
								<tr>
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
		load_form_div('/Medical/final_invoice/'+med_invoice_id,'maindiv'); 
	}
	
	function edit_invoice(med_invoice_id){
		load_form_div('/Medical/Invoice_med_show/'+med_invoice_id,'maindiv'); 
	}
	
	function add_invoice(p_id,ipd_id){
		load_form_div('/Medical/Invoice_counter_new/'+p_id+'/'+ipd_id+'/0','maindiv'); 
	}
	
	function Lock(ipd_id,lock){
		if(confirm("Are sure for This Action"))
		{
			$.post('/Medical/LockIPD/'+ipd_id+'/'+lock,
			{ }, function(data){
			if(data.update==0)
					{
						alert('Something Wrong');
					}else
					{
						alert('Update Success');
						load_form_div('/Medical/list_med_inv/'+ipd_id,'maindiv'); 
					}
			},'json');
		}
		
	}
	
	
</script>