<section class="content-header">
      <h1>
        Invoice
        <small>#<?=$invoice_master[0]->invoice_code ?></small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Patient</a></li>
		<?php $editCaseId = $invoice_master[0]->insurance_case_id ?? 0; ?>
		<li><a href="javascript:load_form('<?= ($editCaseId > 0) ? base_url('billing/case/addPathTest') . '/' . $editCaseId : base_url('billing/charges/edit') . '/' . $invoice_master[0]->id ?>')">Edit</a></li>
        <li class="active">Payment</li>
      </ol>
    </section>
<section class="invoice">
      <!-- title row -->
      <div class="row">
        <div class="col-xs-12">
          <h2 class="page-header">
			<i class="fa fa-globe"></i> <?= H_Name ?>
            <small class="pull-right">Print Date: <?=date('d/m/Y') ?> </small>
          </h2>
        </div>
        <!-- /.col -->
      </div>
      <!-- info row -->
      <div class="row invoice-info">
        <div class="col-sm-4 invoice-col">
          From
          <address>
			<strong><?= H_Name ?> </strong><br>
			<?= H_address_1 ?><br>
			<?= H_address_2 ?><br>
			Phone: <?= H_phone_No ?><br>
			Email: <?= H_Email ?>
          </address>
        </div>
        <!-- /.col -->
        <div class="col-sm-4 invoice-col">
          To
          <address>
            <strong><?=$patient_master[0]->p_fname ?></strong><br>
            Gender : <?=$patient_master[0]->xgender ?><br>
            Age : <?=$patient_master[0]->age ?><br>
            Phone No : <?=$patient_master[0]->mphone1 ?>
          </address>
        </div>
        <!-- /.col -->
        <div class="col-sm-4 invoice-col">
          <b>Invoice #<?=$invoice_master[0]->invoice_code ?></b><br>
          <br>
		   <?php
				if($invoice_master[0]->insurance_id>1)
				{
					echo '<strong> Ins. Comp. :</strong>'.$insurance[0]->ins_company_name.'<br>';
					if($invoice_master[0]->insurance_case_id>0)
					{
						echo '<strong> Org.Case No. :</strong>'.$case_master[0]->case_id_code.'<br>';
					}
				}
			?>
          <b>Date :</b> <?=$invoice_master[0]->inv_date ?><br>
          <b>Patient ID :</b> <?=$patient_master[0]->p_code ?><br>
		   <?php 
			if($invoice_master[0]->payment_id>0 )
			{
					echo '<strong>Payment No. :</strong>'.$invoice_master[0]->payment_id.'<br>';
			}
			?>
		  
		  <b>Refer By :</b> <?=$invoice_master[0]->refer_by_other ?><br>
		  <input type="hidden" id="lab_invoice_id" name="lab_invoice_id" value="<?=$invoice_master[0]->id?>" />
          <?= csrf_field() ?>
        </div>
        <!-- /.col -->
      </div>
      <!-- /.row -->

      <!-- Table row -->
      <div class="row">
        <div class="col-xs-12 table-responsive">
          <table class="table table-striped ">
			<tr>
				<th style="width: 10px">#</th>
				<th>Charges Group</th>
				<th>Charge Name</th>
				<th>Rate</th>
				<th>Qty</th>
				<th>Amount</th>
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
					echo '<td>'.$row->item_amount.'</td>';
					$srno=$srno+1;
					echo '</tr>';
				}
			?>
			<!---- Total Show  ----->
			<tr>
				<th style="width: 10px">#</th>
				<th colspan="2">Amount received : <?=$invoice_master[0]->payment_part_received?></th>
				<th>Balance Amount : <?=$invoice_master[0]->payment_part_balance?></th>
				<th>Net Amount</th>
				<th><?=$invoice_master[0]->net_amount?></th>
				<th></th>
			</tr>
			</table>
		</div>
        <!-- /.col -->
      </div>
      <!-- /.row -->
	<?php if($invoice_master[0]->payment_status==0 || $invoice_master[0]->payment_part ==1 ) {  ?>
      <div class="payment_type">
		  <div class="row">
			<div class="col-md-4">
				<div class="form-group">
						<label>Received Amount</label>
						<input class="form-control" name="input_amount_paid" id="input_amount_paid"  type="text" value="<?=$invoice_master[0]->payment_part_balance?>" />
				</div>
			</div>
		  </div>
        <!-- accepted payments column -->
		<div class="jsError"></div>
        <div id="payment_type" class="row">
			 <div class="col-md-12">
                <div class="form-group">
				<label>Payment Mode</label>
					 <div class="panel-group" id="accordion">
					<?php if($invoice_master[0]->insurance_cash==1) {  ?>
						 <div class="panel panel-default">
							<div class="panel-heading">
							  <h4 class="panel-title">
								<a data-toggle="collapse" data-parent="#accordion" href="#collapse1">
								Cash</a>
							  </h4>
							</div>
							<div id="collapse1" class="panel-collapse collapse in">
							  <div class="panel-body">
								<button type="button" class="btn btn-primary" id="btn_update1">Confirm Cash Received and Print Receipt</button>
							  </div>
							</div>
						  </div>
						  <div class="panel panel-default">
							<div class="panel-heading">
							  <h4 class="panel-title">
								<a data-toggle="collapse" data-parent="#accordion" href="#collapse2">
								Credit / Debit Card</a>
							  </h4>
							</div>
							<div id="collapse2" class="panel-collapse collapse">
							  <div class="panel-body">
								<div class="row">
									<div class="col-md-3">
										<div class="form-group">
											<label>Card Swipe Machine  </label>
											<input class="form-control" id="input_card_mac" placeholder="Card Swipe Machine Bank Name"  type="text" autocomplete="off">
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label>Customer Bank Name  </label>
											<input class="form-control" id="input_card_bank" placeholder="Customer Bank Name"  type="text" autocomplete="off">
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label>Last Four Digit of Card</label>
											<input class="form-control" id="input_card_digit" placeholder="Last Four Digit of Card"  type="text" autocomplete="off">
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label>Tran. ID  </label>
											<input class="form-control" id="input_card_tran" placeholder="Card Tran.ID."  type="text" autocomplete="off">
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label>Payment Confirm By Card</label>
											<button type="button" class="btn btn-primary" id="btn_update2">Confirm Payment</button>
										</div>
									</div>
								</div>
							  </div>
							</div>
						  </div>
					<?php }  ?>	  
					<?php if(count($ipd_master)>0) {  ?>
						  <div class="panel panel-default">
							<div class="panel-heading">
							  <h4 class="panel-title">
								<a data-toggle="collapse" data-parent="#accordion" href="#collapse3">
								Credit to IPD</a>
							  </h4>
							</div>
							<div id="collapse3" class="panel-collapse collapse">
							  <div class="panel-body">
								<div class="row">
									<div class="col-md-3">
										<div class="form-group">
											<label>IPD ID</label>
											<input class="form-control" id="input_ipd_id" placeholder="Claim ID"  type="text" value=<?=$ipd_master[0]->ipd_code  ?> autocomplete="off" readonly>
											<input type="hidden" id="hidden_ipd_id" value="<?=$ipd_master[0]->id  ?>" >
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label>Credit Confirm</label>
											<button type="button" class="btn btn-primary" id="btn_update3">Click here to Confirm</button>
										</div>
									</div>
								</div>
							  </div>
							</div>
						  </div>
					<?php } elseif($invoice_master[0]->insurance_id>0 && $invoice_master[0]->insurance_credit==1) {  ?>
							<div class="panel panel-default">
							<div class="panel-heading">
							  <h4 class="panel-title">
								<a data-toggle="collapse" data-parent="#accordion" href="#collapse4">
								Credit to Organization</a>
							  </h4>
							</div>
							<div id="collapse4" class="panel-collapse collapse">
							  <div class="panel-body">
							  <?php if(count($case_master)>0) {  ?>
								<div class="row">
									<div class="col-md-3">
										<div class="form-group">
											<label>Case ID</label>
											<input class="form-control" id="input_case_id" placeholder="Claim ID"  type="text" value=<?=$case_master[0]->case_id_code  ?> autocomplete="off" readonly>
											<input type="hidden" id="hidden_case_id" value="<?=$case_master[0]->id  ?>" >
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label>Credit Confirm</label>
											<button type="button" class="btn btn-primary" id="btn_update4">Click here to Confirm</button>
										</div>
									</div>
								</div>
							  <?php }else{ ?>
							  <div class="row">
									<div class="col-md-3">
										<button onclick="load_form('<?= base_url('billing/patient/person_record') ?>/<?=$invoice_master[0]->attach_id ?>');" type="button" class="btn btn-primary">Create Case.</button> 
									</div>
							</div>
						    <?php } ?>
							  </div>
							</div>
						  </div>
					<?php } ?>
						  <div class="panel panel-default">
							<div class="panel-heading">
							  <h4 class="panel-title">
								<a data-toggle="collapse" data-parent="#accordion" href="#collapse3">
								Print Pending Invoice info for Cash Counter</a>
							  </h4>
							</div>
							<div id="collapse3" class="panel-collapse collapse">
							  <div class="panel-body">
								<button type="button" class="btn btn-primary" id="btn_print">Print Invoice Info.</button>
							  </div>
							</div>
						  </div>
					</div> 
				</div>
			</div>
		</div>
      </div>
	<?php  }else{  ?> 
	<div class="row no-print">
				<div class="col-xs-6">
					<a href="<?= base_url('OcasePathLap/invoice_print') ?>/<?=$invoice_master[0]->id?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print</a>
        </div>
		<div class="col-xs-6">
          Payment Method by : <?=$invoice_master[0]->payment_mode_desc ?>           
        </div>
     </div>
	<?php } ?>
      <!-- /.row -->
</section>
<!-- /.content -->
<div class="clearfix"></div>

<script>

$(document).ready(function(){

	$('#btn_update1').click( function()
	{
		var csrf_name = '<?= csrf_token() ?>';
		var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';
		$.post('<?= base_url('OcasePathLap/confirm_payment') ?>',
		{ "mode":"1","lab_invoice_id":$('#lab_invoice_id').val(),
		"input_amount_paid":$('#input_amount_paid').val(),
		[csrf_name]: csrf_value }, function(data){
        if(data.update==0)
                {
                    $('div.jsError').html(data.error_text);
                }else
                {
					$('div.payment_type').html(data.showcontent);
					load_form('<?= base_url('OcasePathLap/showinvoice') ?>/'+$('#invoice_id').val());
				}
		},'json');
	});
	
	$('#btn_update2').click( function()
	{
		var csrf_name = '<?= csrf_token() ?>';
		var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';
		$.post('<?= base_url('OcasePathLap/confirm_payment') ?>',
		{ "mode":"2","lab_invoice_id":$('#lab_invoice_id').val(),
		"input_card_mac": $('#input_card_mac').val(),
		"input_card_bank": $('#input_card_bank').val(),
		"input_card_digit": $('#input_card_digit').val(),
		"input_card_tran": $('#input_card_tran').val(),
		"input_amount_paid":$('#input_amount_paid').val(),
		[csrf_name]: csrf_value }, function(data){
        if(data.update==0)
                {
                    $('div.jsError').html(data.error_text);
                }else
                {
					$('div.payment_type').html(data.showcontent);
					load_form('<?= base_url('OcasePathLap/showinvoice') ?>/'+$('#invoice_id').val());
				}
		},'json');
	});
	
	$('#btn_update3').click( function()
	{
		var csrf_name = '<?= csrf_token() ?>';
		var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';
		$.post('<?= base_url('OcasePathLap/confirm_payment') ?>',
		{ "mode":"3","lab_invoice_id":$('#lab_invoice_id').val(),
			"ipd_id": $('#hidden_ipd_id').val(),
			[csrf_name]: csrf_value }, function(data){
        if(data.update==0)
                {
                    $('div.jsError').html(data.error_text);
                }else
                {
					$('div.payment_type').html(data.showcontent);
					load_form('<?= base_url('OcasePathLap/showinvoice') ?>/'+$('#invoice_id').val());
				}
		},'json');
	});
	
	$('#btn_update4').click( function()
	{
		var csrf_name = '<?= csrf_token() ?>';
		var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';
		$.post('<?= base_url('OcasePathLap/confirm_payment') ?>',
		{ "mode":"4","lab_invoice_id":$('#lab_invoice_id').val(),
			"case_id": $('#hidden_case_id').val(),
			[csrf_name]: csrf_value }, function(data){
        if(data.update==0)
                {
                    $('div.jsError').html(data.error_text);
                }else
                {
					$('div.payment_type').html(data.showcontent);
					load_form('<?= base_url('OcasePathLap/showinvoice') ?>/'+$('#invoice_id').val());
				}
		},'json');
	});
	
});

</script>