<div class="row">
		<div class="col-md-12">
		<?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
		<div class="box box-danger">
			<div class="box-header">
				<div class="box-title">
					<div class="row">
						<div class="col-md-12">
							<p><strong>Name :</strong><?=$person_info[0]->p_fname?> {<i><?=$person_info[0]->p_rname?></i>}
							<strong>/ Age :</strong><?=$person_info[0]->age?> 
							<strong>/ Gender :</strong><?=$person_info[0]->xgender?> 
							<strong>/ P Code :</strong><?=$person_info[0]->p_code?><br />
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
					<?php if(count($med_group_list)>0){  ?>
						<?php if($med_group_list[0]->bill_final==0){ ?>
								<button onclick="BillFinal('<?=$med_group_list[0]->med_group_id ?>')" type="button" class="btn btn-primary">Provisional Bill</button>
						<?php }else{ ?>
								<button  type="button" class="btn btn-warning">Cash Bill</button>
						<?php }  ?> 
					<?php }  ?>
				</div>
			</div>
			<div class="box-body">
					<div class="row">
						<div class="col-md-12">
							<strong>IPD Code :</strong>
							<a href="javascript:load_form_div('/Medical/list_med_inv/<?=$ipd_master[0]->id ?>','maindiv');" >
								<?=$ipd_master[0]->ipd_code?>
							</a>
							<strong>Admit Date : </strong><?=$ipd_list[0]->str_register_date ?> <?=$ipd_list[0]->reg_time ?> /
							<strong>Discharge Date : </strong><?=$ipd_list[0]->str_discharge_date ?> <?=$ipd_list[0]->discharge_time ?> /
							<strong>No. of Days : </strong><?php if($ipd_list[0]->no_days==0) {echo '1';} else {echo $ipd_list[0]->no_days; } ?> /
							<?php if(count($bed_list)>0){   ?>
								<strong>Bed No : </strong><?=$bed_list[0]->bed_no ?> <?=$bed_list[0]->room_name ?> 
							<?php } ?>
							<strong>/ Doctor :</strong><?=$ipd_list[0]->doc_name?>
							<strong>/ Status :</strong><?=$ipd_list[0]->Disstatus?>
							
						</div>
					</div>
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
								echo '<td>';
								if($ipd_master[0]->ipd_status==0 || $this->ion_auth->in_group('admin'))
								{
									echo '<button type="button" class="btn btn-primary"  onclick="edit_invoice('.$row->id.')">Edit</button>';
									if($row->group_invoice_id==0 && $row->ipd_credit==0 )
									{
										echo '<button type="button" class="btn btn-primary" id="btn_group" onclick="Med_Group('.$row->id.','.$row->ipd_id.')">Group</button>';
									}
								}

								echo '<a href="/Medical_Print/invoice_print_single_bill/'.$row->id.'" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print</a>';
								
								echo '</td>';
								$srno=$srno+1;
								echo '</tr>';
							}
						echo '<input type="hidden" id="srno" name="srno" value="'.$srno.'" />';
						?>
						<!---- Total Show  ----->
						<tr>
							<th style="width: 10px">#</th>
							<th>Invoice ID.</th>
							<th>Inv.Date</th>
							<th>Inv.Desc</th>
							<th>Credit/Cash</th>
							<th>Package</th>
							<th>Amount</th>
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
							</tr>
						</table>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<table class="table table-striped ">
							<?php
								if($ipd_master[0]->ipd_status==0 or $ipd_master[0]->insurance_id<2){
							?>
							<tr>
								<td>
									<button type="button" class="btn btn-warning" id="btn_return_item"  onclick="Med_Return(<?=$ipd_master[0]->id?>)" >Medicine Reurn</button>
								</td>
								<td>
									<button type="button" class="btn btn-success" id="btn_return_item_new"  onclick="Med_Return_new(<?=$ipd_master[0]->id?>)" >Medicine Reurn New</button>
								</td>
								<td>
								</td>
							</tr>
							<?php
								}
							?>
							<tr>
								<?php if(count($med_group_list)>0) { ?>
								<td>
									<input type="hidden" id="Med_Group_id" value="<?=$med_group_list[0]->med_group_id?>" />
									<button type="button" class="btn btn-danger" id="btn_add_payment"  onclick="Med_Cash_Payment(<?=$ipd_master[0]->id?>)" >Payment Add</button>
								</td>
								<td>
									<?php if($med_group_list[0]->discount_group_2>0) { 
									?>
									<input class="form-control number" id="input_discount2" placeholder="Discount Amount"  type="text" autocomplete="off" value="<?=$med_group_list[0]->discount_group_2?>" Readonly=true>
									<?php } ?>
								</td>
								<td>
									<div class="input-group">
										<input class="form-control number" id="input_discount" placeholder="Discount Amount"  type="text" autocomplete="off" value="<?=$med_group_list[0]->discount_group?>">
										<span class="input-group-btn">
										<button type="button" class="btn btn-primary" onclick="Update_Group_Discount(<?=$med_group_list[0]->med_group_id?>)" id="btn_update_gdiscount">Update Discount</button>
										</span>
									</div>
								</td>
								<?php
									}
								?>
							</tr>
						</table>
						<table class="table table-striped ">
							<tr>
								<td>
									<a href="<?php echo '/Medical_Print/invoice_print_all/'.$ipd_master[0]->id.'/0';  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print Cash</a>
								</td>
								<td>
									<a href="<?php echo '/Medical_Print/invoice_print_with_return_all/'.$ipd_master[0]->id.'/0';  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print Cash With Return</a>
								</td>
								<td>
									<a href="<?php echo '/Medical_Print/invoice_print_all/'.$ipd_master[0]->id.'/1';  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print IPD Credit</a>
								</td>
								<td>
									<a href="<?php echo '/Medical_Print/invoice_print_all/'.$ipd_master[0]->id.'/2';  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print IPD Package</a>
								</td>
								<td>
									<a href="<?php echo '/Medical_Print/medicine_list_print_all/'.$ipd_master[0]->id.'/-1';  ?>" target="_blank" class="btn btn-danger"><i class="fa fa-file-pdf-o"></i> Print Consolidated Medicine List</a>
								</td>
								<td>
									<a href="<?php echo '/Medical_Print/medicine_list_print/'.$ipd_master[0]->id.'/-1';  ?>" target="_blank" class="btn btn-danger"><i class="fa fa-file-pdf-o"></i> Print Medicine List Datewise</a>
								</td>
								<td>
									<a href="<?php echo '/Medical_Print/invoice_print_all_bill/'.$ipd_master[0]->id;  ?>" target="_blank" class="btn btn-danger"><i class="fa fa-file-pdf-o"></i> Print Page Wise</a>
								</td>
								<td>
								<a href="<?php echo '/Medical/Med_Return_print/'.$ipd_master[0]->id.'/0';  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print Return List</a>
								</td>
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
		load_form_div('/Medical/final_invoice/'+med_invoice_id,'maindiv'); 
	}
	
	function edit_invoice(med_invoice_id){
		load_form_div('/Medical/Invoice_med_show/'+med_invoice_id,'maindiv'); 
	}
	
	function add_invoice(p_id,ipd_id){
		load_form_div('/Medical/Invoice_counter_new/'+p_id+'/'+ipd_id+'/0','maindiv'); 
	}
	
	function Med_Return(ipd_id){
		load_form_div('/Medical/Invoice_Item_Return/'+ipd_id+'/0','maindiv'); 
	}

	function Med_Return_new(ipd_id){
		load_form_div('/Medical/Invoice_Item_Return_new/'+ipd_id+'/0','maindiv'); 
	}
	
	function Lock(ipd_id,lock){
		var csrf_dst_name_value=$("input[name='<?=$this->security->get_csrf_token_name()?>']").val();
		if(confirm("Are sure for This Action"))
		{
			$.post('/Medical/LockIPD/'+ipd_id+'/'+lock,
			{"<?php echo $this->security->get_csrf_token_name(); ?>" : csrf_dst_name_value }, function(data){
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
	
	function BillFinal(group_id){
		var csrf_dst_name_value=$("input[name='<?=$this->security->get_csrf_token_name()?>']").val();
		if(confirm("Are sure for This Action"))
		{
			var ipd_id=$('#Ipd_ID').val();
			$.post('/Medical/BillFinal/'+group_id,
			{"<?php echo $this->security->get_csrf_token_name(); ?>" : csrf_dst_name_value }, function(data){
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
		
	function Update_Group_Discount(med_group_id){
		var ipd_id=$('#Ipd_ID').val();
		var csrf_dst_name_value=$("input[name='<?=$this->security->get_csrf_token_name()?>']").val();
		if(confirm("Are sure for This Action"))
		{
			$.post('/Medical/Update_Group_Discount/'+med_group_id,
			{"ipd_id":$('#Ipd_ID').val(),
			"input_discount":$('#input_discount').val(),
			"<?php echo $this->security->get_csrf_token_name(); ?>" : csrf_dst_name_value}, function(data){
			if(data.update==0)
					{
						alert('Something Wrong');
					}else
					{
						alert('Update Discount Success');
						load_form_div('/Medical/list_med_inv/'+ipd_id,'maindiv'); 
					}
			},'json');
		}
	}
	
	function Med_Group(Med_Inv_id,ipd_id){
		var csrf_dst_name_value=$("input[name='<?=$this->security->get_csrf_token_name()?>']").val();
		if(confirm("Are sure for This Action"))
		{
			$.post('/Medical/Med_Inv_Group/'+Med_Inv_id,
			{"<?php echo $this->security->get_csrf_token_name(); ?>" : csrf_dst_name_value }, function(data){
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
	
	function Med_Cash_Payment(invid)
	{
		load_form_div('/Medical/load_model_box/'+invid,'maindiv');
	}
	$(document).ready(function(){
	document.title = 'Med-IPD.:<?=$person_info[0]->p_fname?>/IPD:<?=$ipd_master[0]->id ?>';
	});
	
</script>