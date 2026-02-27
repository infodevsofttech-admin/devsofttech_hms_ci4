<input type="hidden" id="Ipd_ID" value="<?=$ipd_info[0]->id?>" />
<div class="row">
	<div class="col-md-6">
		<div class="box box-primary">
			<div class="box-header with-border">
				<h3 class="box-title">General Information  : IPD No. <?=$ipd_info[0]->ipd_code?></h3>
			</div>
			<!-- /.box-header -->
			<div class="box-body">
				<strong><i class="fa fa-book margin-r-5"></i> Name</strong>
				<p class="text-muted">
					<?=$person_info[0]->p_fname?> 
					<br/>
					<b>Phone Number : </b><?=$person_info[0]->mphone1?>
				</p>
				<strong><i class="fa fa-book margin-r-5"></i>Relative Name</strong>

				<p class="text-muted">
					<?=$ipd_info[0]->contact_person_Name?> <br/>
					<b>Phone Number : </b><?=$ipd_info[0]->P_mobile1?> , <?=$ipd_info[0]->P_mobile2?>
				</p>
				<strong><i class="fa fa-map-marker margin-r-5"></i> Address</strong>

				<p class="text-muted">
					<?=$person_info[0]->add1?>,</br>
					<?=$person_info[0]->add2?>,</br>
					<?=$person_info[0]->city?>,</br>
					<?=$person_info[0]->state?>,</br>
				</p>
				<strong><i class="fa fa-book margin-r-5"></i> Admit Date</strong>
				<p class="text-muted">
				Admit Date : <?=MysqlDate_to_str($ipd_info[0]->register_date) ?>  Time : <?=$ipd_info[0]->reg_time ?>
				</p>

				<?php if($ipd_info[0]->ipd_status>0)   {  ?>
				<p class="text-muted">
				<b>Discharge Date : </b><?=MysqlDate_to_str($ipd_info[0]->discharge_date) ?> <?=$ipd_info[0]->discharge_time ?><br>
				</p>
				<?php }  ?>
				<strong><i class="fa fa-pencil margin-r-5"></i> Associated Doctors</strong>
				<p>
					<?php
					$srno=1;
						foreach($ipd_doc_list as $row)
						{ 
							echo 'Dr. '.$row->p_fname.'  ';
							if ($this->ion_auth->in_group('IPDAdmit')) {
								echo ' <a href="javascript:remove_doc(\''.$row->id.'\',\''.$ipd_info[0]->id.'\');">Remove</a>';
							}
							echo '<br />';
							$srno=$srno+1;
						}
					?>
				</p>
				<strong><i class="fa fa-file-text-o margin-r-5"></i> Notes</strong>
				
			</div>
			<!-- /.box-body -->
		</div>
	</div>
	<div class="col-md-6">
		<div class="row"">
			<div class="box box-primary">
				<div class="box-header with-border">
					<h3 class="box-title">Update Information</h3>
				</div>
				<div class="box-body">
					<strong>Charges :</strong><?=$ipd_master[0]->charge_amount?><br/>
					<strong>Pharmacy Cr. IPD :</strong><?=$ipd_master[0]->med_amount?><br/>
					<strong>Net Amount :</strong><?=$ipd_master[0]->net_amount?><br/>
					<strong>Total Paid :</strong><?=$ipd_master[0]->total_paid_amount?><br/>
					<strong>Balance :</strong><?=$ipd_master[0]->balance_amount?><br/>
					<strong>Pharmacy Bill :</strong><?=$ipd_master[0]->cash_med_amount?><br/>
					<strong>Paid Amount :</strong><?=$ipd_master[0]->med_paid?>
						<p><a href="/ipdNew/ipd_complete_invoice/<?=$ipd_info[0]->id?>/1" target=_blank >
							<i class="fa fa-dashboard"></i> Print Bill</a>
						</p>
				</div>
			</div>
		</div>
		
		<?php if($this->ion_auth->in_group('IPDPayment')) { ?>
			<div class="row">
				<div class="col-md-12">
					<div class="form-group">
						<button type="button" class="btn btn-danger" id="btn_add_payment" data-toggle="modal" data-target="#payModal" data-invid="<?=$ipd_info[0]->id?>" data-invtype="1">Payment Add</button>
					</div>
				</div>
				<div class="col-md-12">
					<div class="form-group">
					<button type="button" class="btn btn-warning" id="btn_refund_payment" data-toggle="modal" data-target="#payModal_ded" data-invid="<?=$ipd_info[0]->id?>" data-invtype="1">Payment Refund</button>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="form-group">
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
		<?php }  ?>
	</div>
</div>
<div class="modal fade" id="payModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" id="payModalLabel">Payment</h4>
	</div>
	<div class="modal-body">
        <div class="row">
			<div class="payModal-bodyc" id="payModal-bodyc">
					
				</div>
			</div>
		</div>
	</div>

    </div>
</div>
</div>  

<div class="modal fade" id="payModal_ded" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  	<div class="modal-dialog modal-lg" role="document">
    	<div class="modal-content">
      		<div class="modal-header">
        		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        		<h4 class="modal-title" id="payModalLabel">Payment Deduction</h4>
     		</div>
      		<div class="modal-body">
       		<div class="row">
				<div class="payModal_ded-bodyc" id="payModal_ded-bodyc">
				</div>
			</div>
		</div>
    </div>
</div>
  
<script>
	
	$('#payModal').on('shown.bs.modal', function (event) {
		
		var button = $(event.relatedTarget); // Button that triggered the modal
		var invid = button.data('invid');
		var invtype = button.data('invtype');
					
		load_form_div('/ipdNew/load_model_box/'+invid,'payModal-bodyc');
	})
	
	$('#payModal_ded').on('shown.bs.modal', function () {
		load_form_div('/IpdNew/load_model_ded_box','payModal_ded-bodyc');
	})
	
	$('#payModal').on('hidden.bs.modal', function () {
		var ipd_id=$('#Ipd_ID').val();
		//load_form_div('/ipd/ipd_panel_payment/'+ipd_id,'tab_2');
		load_form('/ipdNew/ipd_panel/'+ipd_id);
	});
	
	$('#payModal_ded').on('hidden.bs.modal', function () {
		var ipd_id=$('#Ipd_ID').val();
		//load_form_div('/ipd/ipd_panel_payment/'+ipd_id,'tab_2');
		load_form('/IpdNew/ipd_panel/'+ipd_id);
	});
	
</script>
