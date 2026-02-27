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
			  <hr>
		  
			  <strong><i class="fa fa-book margin-r-5"></i>Relative Name</strong>

			  <p class="text-muted">
				<?=$ipd_info[0]->contact_person_Name?> <br/>
				<b>Phone Number : </b><?=$ipd_info[0]->P_mobile1?> , <?=$ipd_info[0]->P_mobile2?>
			  </p>

			  <hr>
			  <strong><i class="fa fa-map-marker margin-r-5"></i> Address</strong>

			  <p class="text-muted">
				<?=$person_info[0]->add1?>,</br>
				<?=$person_info[0]->add2?>,</br>
				<?=$person_info[0]->city?>,</br>
				<?=$person_info[0]->state?>,</br>
			  </p>

			  <hr>
			  
			  <strong><i class="fa fa-book margin-r-5"></i> Admit Date</strong>
			  <p class="text-muted">
			  Admit Date : <?=MysqlDate_to_str($ipd_info[0]->register_date) ?>  Time : <?=$ipd_info[0]->reg_time ?>
			  </p>
			  <?php if($ipd_info[0]->ipd_status>0)   {  ?>
			  <p class="text-muted">
			  <b>Discharge Date : </b><?=MysqlDate_to_str($ipd_info[0]->discharge_date) ?> <?=$ipd_info[0]->discharge_time ?><br>
			  </p>
			  <?php }  ?>
			  <hr>
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
			  <hr>
			  <strong><i class="fa fa-file-text-o margin-r-5"></i> Notes</strong>
			  <p>
				<?=$ipd_info[0]->remark?>
			  </p>
			</div>
			<!-- /.box-body -->
		  </div>
		</div>
		<div class="col-md-6">
			<div class="box box-primary">
				<div class="box-header with-border">
				  <h3 class="box-title">Update Information</h3>
				</div>
				<!-- /.box-header -->
				<div class="box-body">
					<div class="row">
						<p>Charges Bill : <?=$inv_total['total_charges']?></p>
						<p>Medical Bill : </p>
						<p>Credit IPD :  <?=$inv_total['total_med_credit']?></p>
						<p>Cash : <?=$inv_total['total_med_cash']?> / <?=$inv_total['total_med_cash_paid']?></p>
						<p>Paid Amount : <?=$inv_total['total_payment']?></p>
						<p>Discount Amount : <?=$inv_total['discount']?></p>
						<p>Charge Amount : <?=$inv_total['charge']?></p>
						<p>Balance Amount : <?=$inv_total['total_balance_amount']?></p>
						<p><a href="/ipd/ipd_complete_invoice/<?=$ipd_info[0]->id?>/1" target=_blank >
							<i class="fa fa-dashboard"></i> Print Bill</a>
						</p>
					</div>
					<hr>
					<div class="row">
						<div class="box  box-info">
							<div class="box-header with-border">
							  <h3 class="box-title">Medical IPD Credit</h3>
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
												$check='Package';
												if ($row->ipd_credit_type>0)
												{
													$check='Cash';
												}
												echo '<td>'.$check.'</td>';
												echo '<td><a href="/Medical/invoice_print/'.$inv_master_credit[0]->id.'" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print</a>
													</td>';
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
					<?php }  ?>
				</div>
				<!-- /.box-body -->
		 
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
  </div>
  
<script>
	
	$('#payModal').on('shown.bs.modal', function (event) {
		
		var button = $(event.relatedTarget); // Button that triggered the modal
		var invid = button.data('invid');
		var invtype = button.data('invtype');
		
		load_form_div('/ipd/load_model_box/'+invid,'payModal-bodyc');
	})
	
	$('#payModal_ded').on('shown.bs.modal', function () {
		load_form_div('/ipd/load_model_ded_box','payModal_ded-bodyc');
	})
	
	$('#payModal').on('hidden.bs.modal', function () {
		var ipd_id=$('#Ipd_ID').val();
		//load_form_div('/ipd/ipd_panel_payment/'+ipd_id,'tab_2');
		load_form('/ipd/ipd_panel/'+ipd_id);
	});
	
	$('#payModal_ded').on('hidden.bs.modal', function () {
		var ipd_id=$('#Ipd_ID').val();
		//load_form_div('/ipd/ipd_panel_payment/'+ipd_id,'tab_2');
		load_form('/ipd/ipd_panel/'+ipd_id);
	});
	
</script>
