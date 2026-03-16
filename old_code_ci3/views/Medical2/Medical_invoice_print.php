<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Krishna Pharmacy | Invoice</title>
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
<div class="wrapper" style="font-size:11px;" >
<section class="invoice">
      <!-- title row -->
      <div class="row">
        <div class="col-xs-12">
          <h1 align="center">
            <i class="fa fa-globe"></i> Krishna Pharmacy 
          </h1>
		  <p align="center">3-136, Guru Nanakpura, Haldwani,Distt. Nainital, Uttarakhand<br>
            Phone: (05946) 222426,282623 Fax No.:(05946) 282624, Email: krishnahospitalhaldwani@gmail.com
            <br><b>GST: 05AAJFK5301R1Z2  /  L.No: OBR 1/NTL/AUG/2004</b></p>
			<h3 align="center">CASH / Credit Memo<h3>
        </div>
        <!-- /.col -->
      </div>
	  <div class="row">
        <div class="col-xs-12">
          <h1 align="center"></h1>
        </div>
        <!-- /.col -->
      </div>
      <!-- info row -->
      <div class="row ">
		<div class="col-xs-3">
			<img src="/Testcont/bar2/<?=$invoice_master[0]->inv_name?>:INV-<?=$invoice_master[0]->inv_med_code ?>:T<?=date('dmYhms')?>:GT-<?=$invoice_master[0]->net_amount?>:GST:05AAZFK5301R122" style="margin: 10px;" />
		</div>
        <div class="col-xs-3">
          To
			<address>
				<strong><?=$invoice_master[0]->inv_name ?></strong><br>
				<b>Date :</b> <?=$invoice_master[0]->inv_date ?><br>
				<b>Patient ID :</b> <?=$invoice_master[0]->patient_code ?><br>
			</address>
			<input type="hidden" id="med_invoice_id" name="med_invoice_id" value="<?=$invoice_master[0]->id?>" />
        </div>
		<div class="col-xs-3">
          	<address>
				<b>Refer By :</b> <?=$invoice_master[0]->doc_name ?><br>
				<?php if($invoice_master[0]->ipd_id>0) { ?>
				<b>IPD Code :</b> <?=$invoice_master[0]->ipd_code ?><br>
				<?php } ?>
				<?php if($invoice_master[0]->case_id>0) { ?>
				<b>Org. Code :</b> <?=$orgcase[0]->case_id_code ?><br>
				<b>Org. Name :</b> <?=$orgcase[0]->insurance_company_name ?><br>
				<?php } ?>
			</address>
			<input type="hidden" id="med_invoice_id" name="med_invoice_id" value="<?=$invoice_master[0]->id?>" />
        </div>
        <!-- /.col -->
		</div>
      <div class="row">
		<div class="col-xs-12 table-responsive">
			<table border=1 class="table table-condensed ">
				<tr>
					<th style="width: 10px">#</th>
					<th>Item Name</th>
					<th>Batch No</th>
					<th>Exp.</th>
					<th align="right">Rate</th>
					<th align="right">Qty.</th>
					<th align="right">Price</th>
					<th align="right">Disc.</th>
					<th align="right">HSNCODE/GST</th>
					<th align="right">GST</th>
					<th align="right">Amount</th>
				</tr>
				<?php
				$srno=0;
					foreach($inv_items as $row)
					{ 
						$srno=$srno+1;
						echo '<tr>';
						echo '<td>'.$srno.'</td>';
						echo '<td>'.$row->item_Name.' ['.$row->formulation.']</td>';
						echo '<td>'.$row->batch_no.'</td>';
						echo '<td>'.$row->expiry.'</td>';
						echo '<td align="right">'.$row->price.'</td>';
						echo '<td align="right">'.$row->qty.'</td>';
						echo '<td align="right">'.$row->amount.'</td>';
						echo '<td align="right">'.$row->disc_amount.'</td>';
						echo '<td align="center">'.$row->HSNCODE.'/'.$row->gst_per.'</td>';
						echo '<td align="right">'.$row->gst.'</td>';
						echo '<td align="right">'.$row->tamount.'</td>';
						echo '</tr>';
					}
				echo '<input type="hidden" id="srno" name="srno" value="'.$srno.'" />';
				?>
				<!---- Total Show  ----->
				<tr>
					<td style="width: 10px">#</td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td>Gross Total</td>
					<td align="right"><?=$invoiceGtotal[0]->Gtotal ?></td>
					<td align="right"><?=$invoiceGtotal[0]->t_dis_amt?></td>
					<td></td>
					<td align="right"><?=$invoiceGtotal[0]->TGST?></td>
					<td align="right"><?=$invoiceGtotal[0]->tamt?></td>
				</tr>
			</table>
       </div>
      </div>
	  <div class="row">
		<table class="table">
				<tr>
					<th style="width: 10px">#<input type="hidden" name="hid_amount_recevied" id="hid_amount_recevied" value="<?=$invoice_master[0]->payment_received?>"></th>
					<th  align="center">Deduction :[<?=$invoice_master[0]->discount_remark ?>] <?=$invoice_master[0]->discount_amount?></th>
					<th  align="center">CGST : <?=$invoiceGtotal[0]->TCGST?></th>
					<th  align="center">SGST : <?=$invoiceGtotal[0]->TSGST?></th>
					<th  align="center">Net Amount : <?=$invoice_master[0]->net_amount?></th>
					<th  align="center">Amount received : <?=$invoice_master[0]->payment_received?></th>
					<th  align="center">Balance Amount : <?=$invoice_master[0]->payment_balance?></th>
				</tr>
			</table>
	  </div>
      <!-- /.row -->
	<div class="row">
		<div class="col-xs-12">
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
		<div class="row ">
			<div class="col-xs-12">
			This is computer generated invoice , Signature and stamp not required
			</div>
		</div>
      <!-- /.row -->
</section>
<!-- /.content -->
</body>
</html>
