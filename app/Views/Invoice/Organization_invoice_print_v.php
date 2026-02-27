<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title><?= H_Name ?> | Invoice</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Bootstrap 3.3.6 -->
  
  <link href="<?php echo base_url('assets/css/bootstrap.min.css'); ?>" rel="stylesheet">
  <!-- Font Awesome -->
  
  <link rel="stylesheet" href="<?php echo base_url('assets/css/font-awesome.min.css'); ?>">
  <!-- Ionicons -->
  
  <link rel="stylesheet" href="<?php echo base_url('assets/css/ionicons.min.css'); ?>">
    <!-- Theme style -->
  
  <link rel="stylesheet" href="<?php echo base_url('assets/dist/css/AdminLTE.min.css'); ?>">

  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->
</head>
<body onload="window.print();">
<div class="wrapper">
<section class="invoice">
      <!-- title row -->
      <div class="row">
        <div class="col-xs-12">
          <h2 class="page-header">
			<i class="fa fa-globe"></i> <?= H_Name ?>
            <small class="pull-right">Print Date: <?=date('d/m/Y H:m:s') ?> </small>
          </h2>
        </div>
        <!-- /.col -->
      </div>
      <!-- info row -->
      <div class="row invoice-info">
		<div class="col-sm-3 invoice-col">
			<img style="width:60px" src="<?php echo base_url('assets/images/KHRC.png'); ?>" />
			<img src="/Testcont/bar2/<?=$patient_master[0]->p_code?>:INV-<?=$invoice_master[0]->invoice_code ?>:T<?=date('dmYhms')?>:" style="margin-left: 10px;" />
		
			<br />
			<b>Provisional Invoice #<?=$invoice_master[0]->invoice_code ?></b><br>
            <?php
				if($hc_insurance_card[0]->insurance_id>0)
				{
					echo '<strong>Ins. Comp. :</strong>'.$insurance[0]->ins_company_name.'<br>';
					if($invoice_master[0]->insurance_case_id>0)
					{
						echo '<strong> Org.Case No. :</strong>'.$case_master[0]->case_id_code.'<br>';
						if($invoice_master[0]->insurance_case_id==2)
						{
							echo ' <b>Service No. :</b>'.$case_master[0]->insurance_no_2.' </br>';
						}
						echo '<b>Claim ID/No :</b> '.$case_master[0]->insurance_no_1.'<br>';
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
		  
		  <input type="hidden" id="lab_invoice_id" name="lab_invoice_id" value="<?=$invoice_master[0]->id?>" />
		</div>
        <div class="col-sm-6 invoice-col">
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
        <div class="col-sm-3 invoice-col">
          To
          <address>
            <strong><?=$patient_master[0]->p_fname ?></strong><br>
            Gender : <?=$patient_master[0]->xgender ?><br>
            Age : <?=$patient_master[0]->age ?><br>
            Phone No : <?=$patient_master[0]->mphone1 ?><br>
			<b>Refer By :</b> <?=$invoice_master[0]->refer_by_other ?><br>
          </address>
        </div>
        <!-- /.col -->
        
      </div>
      <!-- /.row -->

      <!-- Table row -->
      <div class="row">
        <div class="col-xs-12 table-responsive">
          <table class="table table-striped ">
			<thead>
			<tr>
				<th style="width: 10px">#</th>
				<th>Charges Group</th>
				<th>Charge Name</th>
				<th>Rate</th>
				<th>Qty</th>
				<th>Amount</th>
			</tr>
			</thead>
			<tbody>
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
					echo '<td><i class="fa fa-inr"></i>'.$row->item_amount.'</td>';
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
			</tbody>
			</table>
		</div>
        <!-- /.col -->
      </div>
      <!-- /.row -->
	<div class="row">
		<div class="col-xs-12 invoice-col">
			<b>Payment Details <i>[Payment No.:Mode of Payment:Amount]</i>: </b>
        		<?php
				foreach($payment_history as $row)
				{ 
					echo '['.$row->id.':'.$row->Payment_type_str.':'.$row->amount.']/';
				}
				?>
		</div>
      </div>
	  <div class="row">
		<div class="col-xs-4 invoice-col">
			<b>Prepared By : <?=$invoice_master[0]->prepared_by ?></b>
		</div>
		<div class="col-xs-4 invoice-col">
			
		</div>
		<div class="col-xs-4 invoice-col">
		<b>Signature</b>	
		</div>
	  </div>
	   <hr />
	   <div class="row">
		<img src="/Testcont/bar1/<?=$patient_master[0]->p_code?>:T<?=date('dmYhms')?>:" style="margin-left: 10px;" />
	   </div>
		<div class="row">
			This is computer generated Provisional invoice , Signature and stamp not required
		</div>
		
      <!-- /.row -->
</section>
<!-- /.content -->
</body>
</html>
