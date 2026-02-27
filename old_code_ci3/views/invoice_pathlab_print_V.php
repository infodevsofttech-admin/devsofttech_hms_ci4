<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title><?=H_Name?> | Invoice</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Bootstrap 3.3.6 -->
  
  <link href="<?php echo base_url('assets/css/bootstrap.css'); ?>" rel="stylesheet">
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
<div class="wrapper" style="font-size:11px;">
  <section class="invoice">
      <!-- title row -->
      <div class="row">
        <div class="col-xs-12">
          <h2 class="page-header">
            <i class="fa fa-globe"></i> <?=H_Name?> 
            
          </h2>
        </div>
        <!-- /.col -->
      </div>
      <!-- info row -->
      <div class="row invoice-info">
		<div class="col-sm-3 invoice-col">
			<img style="width:60px" src="/assets/images/<?=H_logo?>" />
			<img src="/Testcont/bar2/<?=$patient_master[0]->p_code?>:<?=$invoice_master[0]->invoice_code ?>:P-<?=date('Y-m-d H:i:s')?>:C-<?=$invoice_master[0]->confirm_invoice?>" style="margin-left: 10px;" />
		
			<br />
			<b>Invoice #<?=$invoice_master[0]->invoice_code ?></b><br>
          <b>Date :</b> <?=$invoice_master[0]->inv_date ?><br>
          <b>Patient ID :</b> <?=$patient_master[0]->p_code ?><br>
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
		  <input type="hidden" id="lab_invoice_id" name="lab_invoice_id" value="<?=$invoice_master[0]->id?>" />
		  
		</div>
        <div class="col-sm-6 invoice-col">
          From
          <address>
            <strong><?=H_Name?> </strong><br>
            <?=H_address_1?><br>
            <?=H_address_2?><br>
            Phone: <?=H_phone_No?><br>
			Email: <?=H_Email?>
          </address>
        </div>
        <!-- /.col -->
        <div class="col-sm-3 invoice-col">
          To
          <address>
            <strong><?=strtoupper($patient_master[0]->p_fname) ?></strong><br>
			<?=$patient_master[0]->p_relative ?>  : <?=strtoupper($patient_master[0]->p_rname) ?><br>
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
          <table class="table  table-condensed ">
			<tr>
				<th style="width: 10px">#</th>
				<th>Charges Group</th>
				<th>Charge Name</th>
				<th>Org.Code</th>
				<th>Rate</th>
				<th>Qty</th>
				<th>Amount</th>
				<th></th>
			</tr>
			<?php
			$srno=1;
				foreach($invoiceDetails as $row)
				{ 
					echo '<tr>';
					echo '<td>'.$srno.'</td>';
					echo '<td>'.$row->group_desc.'</td>';
					echo '<td>'.$row->item_name.'</td>';
					echo '<td>'.$row->org_code.'</td>';
					echo '<td>'.$row->item_rate.'</td>';
					echo '<td>'.$row->item_qty.'</td>';
					echo '<td><i class="fa fa-inr"></i>'.$row->item_amount.'</td>';
					$srno=$srno+1;
					echo '<td></td>';
					echo '</tr>';
				}
			?>
			<!---- Total Show  ----->
			<tr>
				<th style="width: 10px">#</th>
				<th ></th>
				<th></th>
				<th></th>
				<th>Gross Total</th>
				<th><?=$invoiceGtotal[0]->Gtotal?></th>
				<th></th>
			</tr>
			<?php if($invoice_master[0]->discount_amount>0) {?>
			<tr>
				<th style="width: 10px">#</th>
				<th>Deduction</th>
				<th><?=$invoice_master[0]->discount_desc ?></th>
				<th></th>
				<th></th>
				<th><i class="fa fa-inr"></i>-<?=$invoice_master[0]->discount_amount ?></th>
				<th></th>
			</tr>
			<?php } ?>
			<?php if($invoice_master[0]->ipd_id<1) {?>
			<tr>
				<th style="width: 10px">#</th>
				<th colspan="2">Amount received : <?=$invoice_master[0]->payment_part_received?></th>
				<th>Balance Amount : <?=$invoice_master[0]->payment_part_balance?></th>
				<th>Net Amount</th>
				<th><?=$invoice_master[0]->net_amount?></th>
				<th></th>
			</tr>
			<?php } ?>
			
			<?php if($invoice_master[0]->correction_amount>0) {?>
			<tr>
				<th style="width: 10px">#</th>
				<th>Cancel Invoice</th>
				<th><?=$invoice_master[0]->correction_remark ?></th>
				<th></th>
				<th></th>
				<th><i class="fa fa-inr"></i>-<?=$invoice_master[0]->correction_amount ?></th>
				<th></th>
			</tr>
			<tr>
				<th style="width: 10px">#</th>
				<th></th>
				<th></th>
				<th></th>
				<th>Final Amount</th>
				<th><i class="fa fa-inr"></i><?=$invoice_master[0]->correction_net_amount?></th>
				<th></th>
			</tr>
			<?php } ?>
			</table>
		</div>
        <!-- /.col -->
      </div>
	<h1><?=$invoice_master[0]->Invoice_status_str?></h1>
      <!-- /.row -->
	<?php if($invoice_master[0]->payment_mode>2) { ?>
	<div class="row">
		<div class="col-xs-12 invoice-col">
			<b>Payment Details <i>[<?=$invoice_master[0]->Payment_type_str ?>]</i>
		</div>
	</div>
	<?php }else{ ?>
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
	<?php }  ?>
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
			This is computer generated provisional invoice , Signature and stamp not required
		</div>
		
      <!-- /.row -->
</section>
      
</body>
</html>
