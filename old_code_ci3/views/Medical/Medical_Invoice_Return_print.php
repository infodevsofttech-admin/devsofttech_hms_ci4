<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title><?=M_store?> | Invoice</title>
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
			<img src="/Testcont/bar2/<?=$person_info[0]->p_fname?>:IPD-<?=$ipd_master[0]->ipd_code ?>:T<?=date('dmYhms')?>" style="margin: 10px;" />
		</div>
        <div class="col-xs-9">
          <h1 align="center">
            <i class="fa fa-globe"></i> <?=M_store?> 
          </h1>
		  <p align="center"><?=M_address?><br>
            <?=M_Phone_Number?>
            <br><b>GST: <?=H_Med_GST?>   /  L.No: DEMO LIC </b></p>
        </div>
        <!-- /.col -->
      </div>
	  <div class="row">
		<h3 align="center">Return Medicine List</h3>
	  </div>
	  <div class="row ">
	    <div class="col-xs-4">
          To
			<address>
				<strong><?=$person_info[0]->p_fname ?></strong><br>
				<b>Patient ID :</b> <?=$person_info[0]->p_code ?><br>
				<b>Refer By :</b> <?=$ipd_master[0]->doc_name ?><br>
			</address>
			
        </div>
		<div class="col-xs-4">
          	<address>
				<b>IPD Code :</b> <?=$ipd_master[0]->ipd_code ?><br>
				
			</address>
        </div>
	</div>
	
  <div class="row">
	<div class="col-xs-12 table-responsive">
		<table border=1 class="table table-condensed" >
			<tr>
				<th>Sr.No.</th>
				<th>Return Date</th>
				<th>Inv.Code.</th>
				<th>Item Name</th>
				<th>Batch No.</th>
				<th>Expiry</th>
				<th>Rate</th>
				<th>Old Qty.</th>
				<th>Return Qty.</th>
				<th>Amount</th>
				<th>Update By</th>
			</tr>

			<?php
			$srno=0;
			$head_start=0;
			$tAmount=0;

				foreach($inv_items as $row)
				{
					$srno=$srno+1;
					$head_start=$head_start+1;
						echo '<tr>';
						echo '<td>'.$srno.'</td>';
						echo '<td>'.$row->return_date_time.'</td>';
						echo '<td>'.$row->inv_med_code.'</td>';
						echo '<td>'.$row->item_Name.'</td>';
						echo '<td>'.$row->batch_no.'</td>';
						echo '<td>'.$row->expiry.'</td>';
						echo '<td align="right">'.$row->price.'</td>';
						echo '<td align="right">'.$row->qty.'</td>';
						echo '<td align="right">'.$row->r_qty.'</td>';
						echo '<td align="right">'.$row->amount.'</td>';
						echo '<td>'.$row->update_remark.'</td>';
						echo '</tr>';
					$tAmount=$tAmount+$row->amount;
				}

				echo '<tr>
						<th></th>
						<th></th>
						<th></th>
						<th></th>
						<th></th>
						<th></th>
						<th></th>
						<th></th>
						<th>Total</th>
						<th>'.$tAmount.'</th>
						<th></th>
					</tr>';
			?>
			<!---- Total Show  ----->
			
		</table>
   </div>
  </div>
	
	<div class="row">
		<div class="col-xs-4 invoice-col">
			
		</div>
		<div class="col-xs-4 invoice-col">
		</div>
		<div class="col-xs-4 invoice-col">
		<b>For Krishna Pharmacy </b>	
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
