<?php
$pharmacyName = defined('H_Med_Name') ? H_Med_Name : (defined('M_store') ? M_store : 'Medical Store');
$pharmacyAddress = defined('H_Med_address_1') ? H_Med_address_1 : (defined('M_address') ? M_address : '');
$pharmacyPhone = defined('H_Med_phone_No') ? H_Med_phone_No : (defined('M_Phone_Number') ? M_Phone_Number : '');
$pharmacyGst = defined('H_Med_GST') ? H_Med_GST : '';
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title><?=$pharmacyName?> | Provisional Invoice</title>
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

<section class="invoice" style="font-size:12px;" >
      <!-- title row -->
      <div class="row">
		<div class="col-xs-3">
			<img src="/Testcont/bar2/<?=$ipd_master[0]->p_fname?>:IPD-<?=$ipd_master[0]->ipd_code ?>:T<?=date('dmYhms')?>" style="margin: 10px;" />
		</div>
        <div class="col-xs-9">
          <h1 align="center">
            <i class="fa fa-globe"></i> <?=$pharmacyName?> 
          </h1>
		  <p align="center"><?=$pharmacyAddress?><br>
            <?=$pharmacyPhone?>
            <br><b>GST: <?=$pharmacyGst?>   /  L.No: OBR 1/NTL/AUG/2004 / BR 1/NTL/AUG/2004 </b></p>
        </div>
        <!-- /.col -->
      </div>
	  <div class="row">
		<?php
			
		?>
		<h3 align="center">Provisional CASH / Credit Memo</h3>
	  </div>
	  <div class="row ">
	    <div class="col-xs-4">
          To
			<address>
				<strong><?=$ipd_master[0]->p_fname ?></strong><br>
				<b>Patient ID :</b> <?=$ipd_master[0]->p_code ?><br>
				<b>Refer By :</b> <?=$ipd_master[0]->doc_name ?><br>
			</address>
			
        </div>
		<div class="col-xs-4">
          	<address>
				<b>IPD Code :</b> <?=$ipd_master[0]->ipd_code ?><br>
				<?php if($ipd_master[0]->org_id>0) { ?>
				<b>Org. Code :</b> <?=$orgcase[0]->case_id_code ?><br>
				<b>Org. Name :</b> <?=$orgcase[0]->insurance_company_name ?><br>
				<?php } ?>
			</address>
        </div>
	</div>
	
  <div class="row">
	<div class="col-xs-12 table-responsive">
		<table border=1 class="table table-condensed" >
			<?php
			$srno=0;
			$head_start=0;
				foreach($inv_items as $row)
				{
					if($row->id=='' && $row->inv_med_id != '')
					{
							echo '<tr>';
							echo '<td style="width: 10px">#</td>';
							echo '<td colspan="5" ><b>Invoice Total :</b>'.$row->inv_med_code.'</td>';
							echo '<td align="right"><b>'.$row->amount.'</b></td>';
							echo '</tr>';
					
							$head_start=0;
					}
					elseif($row->id=='' && $row->inv_med_id == ''){
							echo '<tr><td colspan="11"><hr/></td></tr>';
							echo '<tr>';
							echo '<td style="width: 10px">#</td>';
							echo '<td colspan="5" align="center"><b>Grand Total</b></td>';
							echo '<td align="right"><b>'.$row->amount.'</b></td>';
							echo '</tr>';
					}else{
						
						if($head_start==0)
						{
							echo '<tr><td colspan="11"><hr/></td></tr>';

							echo '<tr><td></td><td colspan="10">[<b>Dated : </b><i>'.$row->str_inv_date.'</i>]</td></tr>';
							echo '<tr>';
							echo '<th style="width: 10px">#</th>';
							echo '<th>Item Name</th>';
							echo '<th>Batch No</th>';
							echo '<th>Exp.</th>';
							echo '<th align="right">Rate</th>';
							echo '<th align="right">Qty.</th>';
							echo '<th align="right">Price</th>';
							echo '</tr>';
						}
						
						$srno=$srno+1;
						$head_start=$head_start+1;
							echo '<tr>';
							echo '<td>'.$srno.'</td>';
							echo '<td>'.$row->item_Name.' '.$row->formulation.'</td>';
							echo '<td>'.$row->batch_no.'</td>';
							echo '<td>'.$row->expiry.'</td>';
							echo '<td align="right">'.$row->price.'</td>';
							echo '<td align="right">'.$row->qty.'</td>';
							echo '<td align="right">'.$row->amount.'</td>';
							echo '</tr>';
					}
				}
			?>
			<!---- Total Show  ----->
			
		</table>
   </div>
  </div>
	 <?php if(count($inv_med_group)>0) {  ?>
	 <div class="row">
		<table class="table">
				<tr>
					<th style="width: 10px">#</th>
					<th  align="center">Gross Amount : <?=$inv_med_group[0]->gross_amount?></th>
					<th  align="center">Deduction : <?=$inv_med_group[0]->discount_group?></th>
					<th  align="center">Net Amount : <?=$inv_med_group[0]->net_amount?></th>
					<th  align="center">Amount received : <?=$inv_med_group[0]->payment_received?></th>
					<th  align="center">Balance Amount : <?=$inv_med_group[0]->payment_balance?></th>
				</tr>
		</table>
	  </div>
	 <?php } ?>
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
