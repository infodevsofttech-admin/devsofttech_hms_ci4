<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Krishna Pharmacy | Invoice</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Bootstrap 3.3.7 -->
  <link rel="stylesheet" href="<?php echo base_url('assets')?>/bower_components/bootstrap/dist/css/bootstrap.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="<?php echo base_url('assets')?>/bower_components/font-awesome/css/font-awesome.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="<?php echo base_url('assets')?>/bower_components/Ionicons/css/ionicons.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="<?php echo base_url('assets')?>/dist/css/AdminLTE.css" type="text/css" media="print">
  
</head>
<body onload="window.print();">

<section class="invoice" style="font-size:11px;" >
      <!-- title row -->
      <div class="row">
		<div class="col-xs-3">
			<img src="/Testcont/bar2/<?=$invoice_master[0]->inv_name?>:INV-<?=$invoice_master[0]->inv_med_code ?>:T<?=date('dmYhms')?>:GT-<?=$invoice_master[0]->net_amount?>:GST:05AAZFK5301R122" style="margin: 10px;" />
		</div>
        <div class="col-xs-9">
          <h1 align="center">
            <i class="fa fa-globe"></i> Krishna Pharmacy 
          </h1>
		  <p align="center">3-136, Guru Nanakpura, Haldwani,Distt. Nainital, Uttarakhand<br>
            Phone: +91 9837669939, Email: krishnahospitalhaldwani@gmail.com
            <br><b>GST: 05AAJFK5301R1Z2   /  L.No: OBR 1/NTL/AUG/2004 / BR 1/NTL/AUG/2004 </b></p>
        </div>
        <!-- /.col -->
      </div>
	  <div class="row">
		<h3 align="center">CASH / Credit Memo</h3>
	  </div>
	  <div class="row ">
	    <div class="col-xs-4">
          To
			<address>
				<strong><?=$invoice_master[0]->inv_name ?></strong><br>
				<b>Patient ID :</b> <?=$invoice_master[0]->patient_code ?><br>
				<b>Refer By :</b> <?=$invoice_master[0]->doc_name ?><br>
			</address>
			<input type="hidden" id="med_invoice_id" name="med_invoice_id" value="<?=$invoice_master[0]->id?>" />
        </div>
		<div class="col-xs-4">
			<address>
				<b>Medical Invoice : </b> <?=$invoice_master[0]->inv_med_code ?><br>
				<b>Invoice Date :</b> <?=MysqlDate_to_str($invoice_master[0]->inv_date) ?><br>
				
			</address>
		</div>
		<div class="col-xs-4">
          	<address>
				<?php if($invoice_master[0]->ipd_id>0) { ?>
				<b>IPD Code :</b> <?=$invoice_master[0]->ipd_code ?><br>
				<?php } ?>
				<?php if($invoice_master[0]->case_id>0) { ?>
				<b>Org. Code :</b> <?=$orgcase[0]->case_id_code ?><br>
				<b>Org. Name :</b> <?=$orgcase[0]->insurance_company_name ?><br>
				<?php } ?>
			</address>
        </div>
	</div>
	<div class="row">
			<hr />
	</div>
      <div class="row">
		<div class="col-xs-12 table-responsive">
			<table border=1 class="table table-condensed" >
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
						echo '<td align="right">'.$row->d_amt.'</td>';
						echo '<td align="center">'.$row->HSNCODE.'/'.$row->gst_per.'</td>';
						echo '<td align="right">'.$row->gst.'</td>';
						echo '<td align="right">'.$row->twdisc_amount.'</td>';
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
					<td colspan="2" align="right"><b>Gross Total</b></td>
					<td align="right"><b><?=$invoice_master[0]->gross_amount ?></b></td>
					<td align="right"><b><?=$invoice_master[0]->inv_disc_total?></b></td>
					<td></td>
					<td align="right"><b><?=$invoice_master[0]->TGST?></b></td>
					<td align="right"><b><?=$invoice_master[0]->net_amount?></b></td>
				</tr>
			</table>
       </div>
      </div>
	  <div class="row">
		<table class="table">
				<tr>
					<th style="width: 10px">#<input type="hidden" name="hid_amount_recevied" id="hid_amount_recevied" value="<?=$invoice_master[0]->payment_received?>"></th>
					<th  align="center">Deduction :[<?=$invoice_master[0]->discount_remark ?>] <?=$invoice_master[0]->inv_disc_total?></th>
					<th  align="center">CGST : <?=$invoice_master[0]->CGST_Tamount?></th>
					<th  align="center">SGST : <?=$invoice_master[0]->SGST_Tamount?></th>
					<th  align="center">Taxable Amount : <?=$invoice_master[0]->TaxableAmount?></th>
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
	<div class="row ">
	<hr />
		<div class="col-xs-12">
		This is computer generated invoice , Signature and stamp not required
		</div>
	</div>
  <!-- /.row -->
</section>

<!-- /.content -->
</body>
</html>
