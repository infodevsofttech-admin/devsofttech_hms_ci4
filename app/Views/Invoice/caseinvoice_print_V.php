<style>@page {

size: 8.5in 11in;
margin-top: 5cm;
margin-bottom: 1.2cm;
margin-left: 1cm;
margin-right: 0.5cm;

margin-header:0.5cm;
margin-footer:0.5cm;
header: html_myHeader;
footer: html_myFooter;
}

.table table {
  border-collapse: collapse;
}

.table td, th {
  border: 1px solid #999;
  padding: 0.5rem;
  text-align: left;
}

</style>

<htmlpageheader name="myHeader">
<table  cellspacing="0"  style="font-size: 10px;width:100%;border:0;" >
    <tr>
        <td style="width: 20%;vertical-align: top;">
			<img style="width: 100px;vertical-align: top;"  src="assets/images/<?= H_logo ?>" />
        </td>
        <td style="width: 60%;vertical-align: top;">
			<p align="center" style="font-size: 22px;" ><?= H_Name ?></p>
			<p align="center" style="font-size: 12px" ><?= H_address_1 ?>, Uttarakhand<br>
            <?php 
				if(H_phone_No!='')
                {
					echo 'Phone: '.H_phone_No;
                } 
            ?>
        </td>
        <td style="width: 20%;vertical-align: top;text-align: right;">
        <?php
        $bar_content=$person_info[0]->p_code.':'.$orgcase[0]->id .':P-'.date('Y-m-d H:i:s');
        ?>
        <barcode code="<?=$bar_content?>" size="0.8" type="QR" error="M" class="barcode" />
        </td>
    </tr>
</table>
<h3 align="center">Bill No.#  :<?=$orgcase[0]->id?></h3>
</htmlpageheader>
<htmlpagefooter name="myFooter">
<table width="100%" style="font-size: 10px;">
<tr>
    <td width="15%" >Page : {PAGENO}/{nbpg}</td>
    <td width="85%" style="text-align: right;">Bill No.:<?=$orgcase[0]->id?> / UHID:<?=$person_info[0]->p_code ?> /Name : <?=$person_info[0]->p_fname ?></td>
</tr>
</table>
</htmlpagefooter>
<table cellspacing="0"  style="font-size: 10px;width:100%;border-style: inset;">
	<tr>
		<td width="50%" style="vertical-align: top;">
		To<br>
			<b>Patient ID :</b> <?=$person_info[0]->p_code ?><br />
			<strong><?=$person_info[0]->p_fname ?></strong><br>
			Gender : <?=$person_info[0]->xgender ?><br>
			Age : <?=$person_info[0]->age ?><br>
			Phone No : <?=$person_info[0]->mphone1 ?><br>
			</address>
		</td>
		<td>
			<b>Organisation Invoice #</b><?=$orgcase[0]->case_id_code?><br />
			<b>Organisation :</b><?=$insurance[0]->ins_company_name?><br />
			<b>Card / Insuranc No :</b> <?=$orgcase[0]->insurance_no ?><br />
				<?php
					if($orgcase[0]->insurance_id==2)
					{
						echo ' <b>Referral No. :</b>'.$orgcase[0]->insurance_no_2.' <br />';
					}
				?>
			<b>Claim ID/No :</b> <?=$orgcase[0]->insurance_no_1 ?><br />
			<?php
				if($orgcase[0]->org_submit_date=='')
				{
					$Dateofsubmit=date('d/m/Y');
				}else{
					$Dateofsubmit=MysqlDate_to_str($orgcase[0]->org_submit_date);
				}
			?>
			<b>Bill Submit Date :</b> <?=$Dateofsubmit ?><br />
		</td>
	</tr>
</table>
<br/><br/>
<table class="table "style="font-size: 10px;width:100%;border: 1px solid black;border-collapse: collapse;">
			<tr>
				<th style="width: 10px">#</th>
				<th>Date</th>
				<th>Charges Type</th>
				<th>Description</th>
				<th>Org. Code</th>
				<th>Qty</th>
				<th>Rate</th>
				<th>Sub Invoice Code</th>
				<th>Amount</th>
			</tr>
			<?php
			$srno=0;
			$Inv_total=0;
			
			foreach($showinvoice1 as $row)
				{ 
					$srno=$srno+1;
					echo '<tr>';
					echo '<td>'.$srno.'</td>';
					echo '<td>'.$row->str_date.'</td>';
					echo '<td>'.$row->Charge_type.'</td>';
					echo '<td>'.$row->Description.'</td>';
					echo '<td>'.$row->orgcode.'</td>';
					echo '<td></td>';
					echo '<td></td>';
					echo '<td>'.$row->Code.'</td>';
					echo '<td align="right">'.$row->Amount.'</td>';
					echo '</tr>';
					$Inv_total=$Inv_total+$row->Amount;
				}

			foreach($showinvoice2 as $row)
				{ 
					$srno=$srno+1;
					 if(($row->discount_amount ?? 0) > 0){
                                $readonly = 'readonly';
                                $disabled = 'disabled';
							$rate = $row->d_rate ?? $row->item_rate;
                                $discription = $row->Description.' (Discounted) Rate : '.$row->item_rate;
                            }else{
                                $readonly = '';
                                $disabled = '';
                                $rate = $row->item_rate;
                                $discription = $row->Description;
                            }
					echo '<tr>';
					echo '<td>'.$srno.'</td>';
					echo '<td>'.$row->str_date.'</td>';
					echo '<td>'.$row->Charge_type.'</td>';
					echo '<td>'.$discription.'</td>';
					echo '<td>'.$row->orgcode.'</td>';
					echo '<td>'.$row->item_qty.'</td>';
					echo '<td>'.$rate.'</td>';
					echo '<td>'.$row->Code.'</td>';
					echo '<td align="right">'.$row->Amount.'</td>';
					echo '</tr>';
					$Inv_total=$Inv_total+$row->Amount;
				}
				
			if(count($MedInvoice)>0 && $med_include==1)
				{
				$srno=$srno+1;
				$Inv_total=Round($Inv_total,0)+Round($MedInvoice[0]->Med_Amount,0);
			?>
				<tr>
					<th style="width: 10px"><?=$srno?></th>
					<th></th>
					<th >Medicine Expense</th>
					<th></th>
					<th></th>
					<th></th>
					<th align="right" style="text-align:right"><?=$MedInvoice[0]->Med_Amount?></th>
				</tr>
			<?php
				}
			?>
			<!---- Total Show  ----->
			<tr>
				<th style="width: 10px">#</th>
				<th colspan=6><?=number_to_word($Inv_total)?></th>
				<th>Gross Total</th>
				<th align="right" style="text-align:right"><?=number_format($Inv_total,2)?></th>
			</tr>
		</table>
<p></p>
<p></p>
<p></p>
<p></p>
<p align="right">Authorized Signatory</p>