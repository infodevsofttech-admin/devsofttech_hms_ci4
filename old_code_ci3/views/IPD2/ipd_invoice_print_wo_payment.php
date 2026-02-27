<style>@page {
        margin-top: 4.2cm;
        margin-bottom: 3.2cm;
        margin-left: 1cm;
        margin-right: 0.5cm;
        
		margin-header:0.5cm;
        margin-footer:0.5cm;
        header: html_myHeader;
        footer: html_myFooter;
}
</style>

<htmlpageheader name="myHeader">
	<table  cellspacing="0"  style="font-size: 10px;width:100%;border-style: inset;" >
	<tr>
		<td>
			<img style="width: 10%;vertical-align: top;"  src="assets/images/<?=H_logo?>" />
		</td>
    	<td style="width: 60%;vertical-align: top;">
		    <p align="center" style="font-size: 20px;" ><?=H_Name?></p>
		    <p align="center" style="font-size: 12px" ><?=H_address_1?>, <?=H_address_2?><br>
            <?php 
                if(H_phone_No!='')
                {
                    echo 'Phone: '.H_phone_No;
                } 
            ?>
		 </td>
		 <td style="width: 20%;vertical-align: top;">
		 		<?php
				$bar_content=$person_info[0]->p_code.':'.$ipdmaster[0]->ipd_code .':P-'.date('Y-m-d H:i:s');
				?>
				<barcode code="<?=$bar_content?>" size="0.8" type="QR" error="M" class="barcode" />
		 </td>
	</tr>
	</table>
<h3 align="center">
<?php if($ipdmaster[0]->ipd_status>0) {  ?>
		Bill No. : <?=$ipdmaster[0]->id ?><br>
<?php }else { echo '<b>Provisional Bill</b><br>'; }  ?>
</h3>
</htmlpageheader>
<htmlpagefooter name="myFooter">
	<table width="100%" style="font-size: 10px;">
        <tr>
            <td width="50%" >Patient / Relative Signature</td>
            <td width="50%" style="text-align: right;">Authorized Signatory</td>
        </tr>
    </table>
    <table width="100%" style="font-size: 10px;">
        <tr>
            <td width="33%" >Page : {PAGENO}/{nbpg}</td>
            <td width="66%" style="text-align: right;">IPD-ID:<?=$ipdmaster[0]->ipd_code ?> / UHID:<?=$person_info[0]->p_code ?> /Name : <?=$person_info[0]->p_fname ?></td>
        </tr>
    </table>
</htmlpagefooter>
<table  cellspacing="0"  style="font-size: 10px;width:100%;border-style: inset;" >
	<tr>
		<td style="width: 40%;vertical-align: top;">
		 <strong>Patient Name : </strong><?=$person_info[0]->p_fname ?><br>
				<?=$person_info[0]->p_relative ?>  : <?=$person_info[0]->p_rname ?><br>
				<b>Patient-ID/UHID :</b> <?=$person_info[0]->p_code ?><br>
				Gender : <?=$person_info[0]->xgender ?><br>
				Age : <?=$person_info[0]->age ?><br>
				Phone No : <?=$person_info[0]->mphone1 ?><br>
				Address :<?=$person_info[0]->add1 ?> , <?=$person_info[0]->add2 ?><br>
				<?=$person_info[0]->city ?> ,<?=$person_info[0]->district ?> , <?=$person_info[0]->state ?> - <?=$person_info[0]->zip ?>	
		 </td>
		 <td style="width: 30%;vertical-align: top;">
		 		<b>IPD ID :</b> <?=$ipdmaster[0]->ipd_code ?><br>
				<b>Admit Date : </b><?=$ipd_list[0]->str_register_date ?> <?=$ipd_list[0]->reg_time ?><br>
				<b>Discharge Date : </b><?=$ipd_list[0]->str_discharge_date ?> <?=$ipd_list[0]->discharge_time ?><br>
				<b>No. of Days : </b><?php if($ipd_list[0]->no_days==0) {echo '1';} else {echo $ipd_list[0]->no_days; } ?><br>
				<?php if(count($bed_list)>0){   ?>
				<b>Bed No : </b><?=$bed_list[0]->bed_no ?> <?=$bed_list[0]->room_name ?><br>
				<?php }   ?>
				<b>Doctor Name : </b><?=$ipd_list[0]->doc_name ?><br>
				<b>Status : </b><?=$ipd_list[0]->status_desc ?>
		 </td>
		 
		 <td style="width: 30%;vertical-align: top;">
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
		 </td>
	</tr>
</table>
<hr/>
<table  cellspacing="0"  style="font-size: 10px;width:100%;border-style: inset;" >
			<tr>
				<th style="width: 10px">#</th>
				<th>Description</th>
				<th style="width:100px;text-align:Left">Org.Code</th>
				<th style="width:100px;text-align:Left">Unit</th>
				<th style="width:100px;text-align:right">Rate</th>
				<th style="width:100px;text-align:right">Amount</th>
			</tr>
			<?php
			$srno=1;
			$headdesc='';
			$headTotal=0.00;
			
			if(Count($ipd_package)>0){
				echo '<tr>';
				echo '<td colspan="2"><b>Package</b></td>';
				echo '<td colspan="4"></td></tr>';
				$headdesc='Package';
				$headTotal=0.00;

				for($i=0;$i<Count($ipd_package);$i++)
				{ 
					echo '<tr>';
					echo '<td>'.$srno.'</td>';
					echo '<td>'.$ipd_package[$i]->package_name.'</td>';
					echo '<td>'.$ipd_package[$i]->org_code.'</td>';
					echo '<td></td>';
					echo '<td align="right"></td>';
					echo '<td align="right">'.$ipd_package[$i]->package_Amount.'</td>';
					$srno=$srno+1;
					$headTotal += $ipd_package[$i]->package_Amount;
					echo '</tr>';
				}

				if(false)
				{
					echo '<tr>';
					echo '<td></td>';
					echo '<td></td>';
					echo '<td></td>';
					echo '<td></td>';
					echo '<td align="right">Sub Total</td>';
					echo '<td align="right">'.number_format($headTotal,2).'</td>';
				}
				
				
			}
			for($i=0;$i<Count($ipd_invoice_item);$i++)
				{ 
					if($headdesc!=$ipd_invoice_item[$i]->group_desc)
					{
						echo '<tr>';
						echo '<td colspan="2"><b>'.$ipd_invoice_item[$i]->group_desc.'</b></td>';
						echo '<td colspan="4"></td></tr>';
						$headdesc=$ipd_invoice_item[$i]->group_desc;
						$headTotal=0.00;
					}
					
					echo '<tr>';
					echo '<td>'.$srno.'</td>';
					echo '<td>'.$ipd_invoice_item[$i]->item_name.' '.$ipd_invoice_item[$i]->comment.'</td>';
					echo '<td></td>';
					echo '<td>'.$ipd_invoice_item[$i]->item_qty.'</td>';
					echo '<td align="right">'.$ipd_invoice_item[$i]->item_rate.'</td>';
					echo '<td align="right">'.$ipd_invoice_item[$i]->item_amount.'</td>';
					$srno=$srno+1;
					$headTotal += $ipd_invoice_item[$i]->item_amount;
					echo '</tr>';

					if($headdesc!=@$ipd_invoice_item[$i+1]->group_desc)
					{
						if(false)
						{
							echo '<tr>';
							echo '<td></td>';
							echo '<td></td>';
							echo '<td></td>';
							echo '<td></td>';
							echo '<td align="right">Sub Total</td>';
							echo '<td align="right">'.number_format($headTotal,2).'</td>';
							echo '</tr>';
						}
					}
				}


			for($i=0;$i<Count($showinvoice);$i++)
				{ 
					if($headdesc!=$showinvoice[$i]->Charge_type)
					{
						echo '<tr>';
						echo '<td colspan="2"><b>'.$showinvoice[$i]->Charge_type.'</b></td>';
						echo '<td colspan="4"></td></tr>';
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

					if($headdesc!=@$showinvoice[$i+1]->Charge_type)
					{
						if(false)
						{
							echo '<tr>';
							echo '<td></td>';
							echo '<td></td>';
							echo '<td></td>';
							echo '<td></td>';
							echo '<td align="right">Sub Total</td>';
							echo '<td align="right">'.number_format($headTotal,2).'</td>';
							echo '</tr>';
						}
					}
				}
		
			
			if(count($inv_med_list)>0)
			{
				echo '<tr>';
				echo '<td colspan="2"><b>Medicine</b></td>';
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
					echo '<td align="right">'.number_format(Round($med_total,0),2).'</td>';
					echo '</tr>';
			}
			?>
			
			<!---- Total Show  ----->
			<tr>
				<th style="width: 10px">#</th>
				<th></th>
				<th></th>
				<th></th>
				<th>Gross Total</th>
				<th align="right" style="text-align:right"><?=$ipdmaster[0]->gross_amount?></th>
			</tr>
			<?php if($ipdmaster[0]->Discount>0) { ?>
			<tr>
				<th style="width: 10px">#</th>
				<th colspan="2">Remark :</br><?=$ipdmaster[0]->Discount_Remark ?></th>
				<th></th>
				<th>Discount </th>
				<th align="right" style="text-align:right"><?=$ipdmaster[0]->Discount ?></th>
				
			</tr>
			<?php }  ?>
			<?php if($ipdmaster[0]->Discount2>0) { ?>
			<tr>
				<th style="width: 10px">#</th>
				<th colspan="2">Remark :</br><?=$ipdmaster[0]->Discount_Remark2 ?></th>
				<th></th>
				<th>Discount </th>
				<th align="right" style="text-align:right"><?=$ipdmaster[0]->Discount2 ?></th>
				
			</tr>
			<?php }  ?>
			<?php if($ipdmaster[0]->Discount3>0) { ?>
			<tr>
				<th style="width: 10px">#</th>
				<th colspan="2">Remark :</br><?=$ipdmaster[0]->Discount_Remark3 ?></th>
				<th></th>
				<th>Discount </th>
				<th align="right" style="text-align:right"><?=$ipdmaster[0]->Discount3 ?></th>
			</tr>
			<?php }  ?>
			<?php if($ipdmaster[0]->chargeamount1>0) { ?>
			<tr>
				<th style="width: 10px">#</th>
				<th colspan="2">Remark :</br><?=$ipdmaster[0]->charge1 ?></th>
				<th></th>
				<th>Charge </th>
				<th align="right" style="text-align:right"><?=$ipdmaster[0]->chargeamount1 ?></th>
			</tr>
			<?php }  ?>
			<?php if($ipdmaster[0]->chargeamount2>0) { ?>
			<tr>
				<th style="width: 10px">#</th>
				<th colspan="2">Remark :</br><?=$ipdmaster[0]->charge2 ?></th>
				<th></th>
				<th>Charge </th>
				<th align="right" style="text-align:right"><?=$ipdmaster[0]->chargeamount2 ?></th>
			</tr>
			<?php }  ?>
			<tr>
				<th style="width: 10px">#</th>
				<th></th>
				<th></th>
				<th></th>
				<th>Net Amount</th>
				<th align="right" style="text-align:right"><?=$ipdmaster[0]->net_amount?></th>
			</tr>

		</table>