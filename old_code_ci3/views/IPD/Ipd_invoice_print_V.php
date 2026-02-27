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
<section class="invoice" style="font-size:12px;">
      <!-- title row -->
      <div class="row">
		<div class="col-xs-2">
			<img style="width:60px" src="<?php echo base_url('assets/images/KHRC.png'); ?>" />
		</div>
        <div class="col-xs-8">
          <h2>
             <?=H_Name?>
          </h2>
		  <address><?=H_address_1?>,<?=H_address_2?><br>
				Phone: <?=H_phone_No?> / Email: <?=H_Email?><br>
				
			</address>
		</div>
		<div class="col-xs-2">
			<img src="/Testcont/bar2/<?=$person_info[0]->p_code ?>:IPD-<?=$ipdmaster[0]->ipd_code ?>:T<?=date('dmYhis')?>:" style="margin-left: 10px;" />
		</div>
		<div class="col-xs-12">
			<h4 align="center">
			<?php if($ipdmaster[0]->ipd_status>0) {  ?>
				Bill No. : <?=$ipdmaster[0]->id ?><br>
				<?php }else { echo '<b>Provisional Bill</b><br>'; }  ?>
			</h4>
        </div>
        <!-- /.col -->
      </div>
      <!-- info row -->
      <div class="row invoice-info">
			<div class="col-sm-4 invoice-col">
				<?php if($ipdmaster[0]->case_id>0) { ?>
				<b>Organisation Invoice #</b><?=$orgcase[0]->case_id_code?><br>
				<b>Organisation :</b><?=$insurance[0]->ins_company_name?><br>
				<b>Insurance Company Name :</b><?=$orgcase[0]->Org_insurance_comp?><br>
				<b>Card No :</b> <?=$orgcase[0]->insurance_no ?><br>
				<?php
					if($orgcase[0]->insurance_id==2)
					{
						echo ' <b>Service No. :</b>'.$orgcase[0]->insurance_no_2.' </br>';
					}
				?>
				<b>Claim ID/No :</b> <?=$orgcase[0]->insurance_no_1 ?><br>
				<input type="hidden" id="case_id" name="case_id" value="<?=$orgcase[0]->id?>" />
				<?php } ?>
			</div>
			<div class="col-sm-4 invoice-col">
				<b>IPD ID :</b> <?=$ipdmaster[0]->ipd_code ?><br>
				<b>Admit Date : </b><?=$ipd_list[0]->str_register_date ?> <?=$ipd_list[0]->reg_time ?><br>
				<b>Discharge Date : </b><?=$ipd_list[0]->str_discharge_date ?> <?=$ipd_list[0]->discharge_time ?><br>
				<b>No. of Days : </b><?php if($ipd_list[0]->no_days==0) {echo '1';} else {echo $ipd_list[0]->no_days; } ?><br>
				<?php if(count($bed_list)>0){   ?>
				<b>Bed No : </b><?=$bed_list[0]->bed_no ?> <?=$bed_list[0]->room_name ?><br>
				<?php }   ?>
				<b>Doctor Name : </b><?=$ipd_list[0]->doc_name ?><br>
			</div>
			<div class="col-sm-4 invoice-col">
			    <address>
				<strong>Patient Name : </strong><?=$person_info[0]->p_fname ?><br>
				<?=$person_info[0]->p_relative ?>  : <?=$person_info[0]->p_rname ?><br>
				Gender : <?=$person_info[0]->xgender ?><br>
				Age : <?=$person_info[0]->age ?><br>
				Phone No : <?=$person_info[0]->mphone1 ?><br>
				<b>Patient ID :</b> <?=$person_info[0]->p_code ?><br>
				Address :<?=$person_info[0]->add1 ?> , <?=$person_info[0]->add2 ?><br>
				<?=$person_info[0]->city ?> ,<?=$person_info[0]->district ?> , <?=$person_info[0]->state ?> - <?=$person_info[0]->zip ?>
			  </address>
			  </address>
			</div>       
      </div>
      <!-- /.row -->

      <!-- Table row -->
      <div class="row">
	  <div class="col-xs-12 ">
			<table class="table  table-condensed" >
			<tr>
				<th style="width: 50px">#</th>
				<th>Description</th>
				<th style="width:100px;">Org.Code</th>
				<th style="width:50px;">Unit</th>
				<th style="width:50px;">Rate</th>
				<th style="width:50px;">Amount</th>
			</tr>
			<?php
			$srno=1;
			$headdesc='';
			$headTotal=0.00;
			for($i=0;$i<Count($showinvoice);$i++)
				{ 
					if($headdesc!=$showinvoice[$i]->Charge_type)
					{
						echo '<tr>';
						echo '<td></td>';
						echo '<td><b>'.$showinvoice[$i]->Charge_type.'</b></td>';
						echo '<td colspan="3"></td></tr>';
						$headdesc=$showinvoice[$i]->Charge_type;
						$headTotal=0.00;
					}	
					echo '<tr>';
					echo '<td>'.$srno.'</td>';
					echo '<td>'.$showinvoice[$i]->idesc.'</td>';
					echo '<td>'.$showinvoice[$i]->orgcode.'</td>';
					echo '<td>'.$showinvoice[$i]->no_qty.'</td>';
					echo '<td align="right">'.$showinvoice[$i]->item_rate.'</td>';
					echo '<td align="right">'.$showinvoice[$i]->amount.'</td>';
					$srno=$srno+1;
					$headTotal += $showinvoice[$i]->amount;
					
					echo '</tr>';
			
				}
				
			if(count($inv_med_list)>0)
			{
				echo '<tr>';
				echo '<td></td>';
				echo '<td><b>Medicine</b></td>';
				echo '<td colspan="3"></td></tr>';
				$med_total=0.00;
				foreach($inv_med_list as $row)
				{
					echo '<tr>';
					echo '<td>'.$srno.'</td>';
					echo '<td>'.$row->inv_med_code.'</td>';
					echo '<td></td>';
					echo '<td></td>';
					echo '<td align="right"></td>';
					echo '<td align="right">'.$row->net_amount.'</td>';
					$srno=$srno+1;
					$med_total +=$row->net_amount;
					echo '</tr>';
				}
				echo '<tr>';
					echo '<td></td>';
					echo '<td></td>';
					echo '<td></td>';
					echo '<td></td>';
					echo '<td align="right">Med. Total </td>';
					echo '<td align="right">'.number_format($med_total,2).'</td>';
					echo '</tr>';
			}
			?>
			<!---- Tot
			<!---- Total Show  ----->
			<tr>
				<th >#</th>
				<th></th>
				<th></th>
				<th colspan=2 >Gross Total</th>
				<th align="right" style="text-align:right"><?=$inv_total['total_charges']?></th>
			</tr>
			<?php if($ipdmaster[0]->chargeamount1>0) { ?>
			<tr>
				<th >#</th>
				<th colspan="2">Charge:</br><?=$ipdmaster[0]->charge1 ?></th>
				<th>( + ) </th>
				<th></th>
				<th align="right" style="text-align:right"><?=$ipdmaster[0]->chargeamount1 ?></th>
			</tr>
			<?php }  ?>
			<?php if($ipdmaster[0]->chargeamount2>0) { ?>
			<tr>
				<th >#</th>
				<th colspan="2">Charge:</br><?=$ipdmaster[0]->charge2 ?></th>
				<th>( + ) </th>
				<th></th>
				<th align="right" style="text-align:right"><?=$ipdmaster[0]->chargeamount2 ?></th>
			</tr>
			<?php }  ?>
			<?php if($ipdmaster[0]->Discount>0) { ?>
			<tr>
				<th >#</th>
				<th colspan="2">Deduction:</br><?=$ipdmaster[0]->Discount_Remark ?></th>
				<th>( - ) </th>
				<th></th>
				<th align="right" style="text-align:right"><?=$ipdmaster[0]->Discount ?></th>
			</tr>
			<?php }  ?>
			<?php if($ipdmaster[0]->Discount2>0) { ?>
			<tr>
				<th >#</th>
				<th colspan="2">Deduction:</br><?=$ipdmaster[0]->Discount_Remark2 ?></th>
				<th>( - ) </th>
				<th></th>
				<th align="right" style="text-align:right"><?=$ipdmaster[0]->Discount2 ?></th>
			</tr>
			<?php }  ?>
			<?php if($ipdmaster[0]->Discount3>0) { ?>
			<tr>
				<th >#</th>
				<th colspan="2">Deduction:</br><?=$ipdmaster[0]->Discount_Remark3 ?></th>
				<th>( - ) </th>
				<th></th>
				<th align="right" style="text-align:right"><?=$ipdmaster[0]->Discount3 ?></th>
			</tr>
			<?php }  ?>
			<tr>
				<th >#</th>
				<th></th>
				<th></th>
				<th colspan=2 >Net Amount</th>
				<th align="right" style="text-align:right"><?=$inv_total['net_amount']?></th>
			</tr>
			<tr>
				<th style="width: 10px">#</th>
				<th colspan="4" >
				Payment Recd.<br/>
					<?php
					$i=1;
					foreach($ipd_payment as $row)
					{ 
						$i=$i+1;
						echo '['.$row->pay_id.':'.$row->pay_mode.':'.$row->pay_date_str.':'.$row->amount.'] /';
						if($i%3==0)
						{
							echo '<br/>';
						}
					}
					?>
				</th>
				<th align="right" style="text-align:right"><?=$ipd_payment_total[0]->t_ipd_pay?></th>
			</tr>
			<tr>
				<th >#</th>
				<th></th>
				<th></th>
				<th>Balance</th>
				<th></th>
				<th align="right" style="text-align:right"><?=$inv_total['balance']?></th>
			</tr>
			<tr>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
			</tr>
			<tr>
				<th></th>
				<th><br><br><br>Patient / Relative  Signature</th>
				<th colspan="3" align="right" style="text-align:right"><br><br><br>Authorized Signatory</th>
			</tr>
		</table>
      </div>
	  </div>
	<hr />
	</div>
</section>
<!-- /.content -->
</body>
</html>
