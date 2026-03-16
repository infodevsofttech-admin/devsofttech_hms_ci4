<style>@page {

		sheet-size: A6; 
        
		margin-top: 4.5cm;
        margin-bottom: 1.2cm;
        margin-left: 0.5cm;
        margin-right: 0.5cm;
        
        margin-header:0.5cm;
        margin-footer:0.5cm;
        header: html_myHeader;
        footer: html_myFooter;
}
</style>

<htmlpageheader name="myHeader">
    <table style="font-size: 10px;" cellpadding="5">
	<tr>
		<td colspan="2"><p align="center" style="font-size: 18px;" ><?=M_store?></p></td>
	</tr>
	<tr>
    	<td style="width: 60%;vertical-align: top;">
		    <p align="center" style="font-size: 10px" ><?=M_address?>, Uttarakhand<br>
            <?php 
                if(M_Phone_Number!='')
                {
                    echo 'Phone: '.M_Phone_Number;
                } 
                
                if(H_Email!='')
                {
                   echo ' ,Email: '.H_Email;
                }
                
                echo '<br>';

                if(H_Med_GST!='')
                {
                    echo '<b>GST: '.H_Med_GST .'</b>';
                }
				
                if(M_LIC!='')
                {
                    echo ' L.No: '.M_LIC;
                }
            ?>
		</td>
		<td style="width: 40%;vertical-align:top ;">
		 	<p style="font-size: 10px">
			 Invoice To :
		 		<strong><?=$invoice_med_master[0]->inv_name?></strong><br>
				<?php if($invoice_med_master[0]->customer_type==0) { ?>
					Phone No. : <?=$invoice_med_master[0]->inv_phone_number?><br>
					<?php if($invoice_med_master[0]->doc_name!=''){ ?>
					Refer By : Dr. <?=$invoice_med_master[0]->doc_name ?><br>
					<?php } ?>
				<?php } ?>
			 	<?php if($invoice_med_master[0]->customer_type==1 && count($patient_master)>0) { ?>
				<i>Patient Info. as per Hospital Records</i><br>
				<b>UHID ID :</b> <?=$patient_master[0]->p_code ?><br>
				<?php } ?>
				<?php if($invoice_med_master[0]->ipd_id>0) { ?>
					<b>IPD Code :</b> <?=$ipd_master[0]->ipd_code ?><br>
					<b>Refer By :</b> <?=$ipd_master[0]->doc_name ?><br>
				<?php if($ipd_master[0]->case_id>0 && $ipd_master[0]->case_id<>'') { ?>	
					<b>Org. Code :</b> <?=$org_master[0]->case_id_code ?><br>
					<b>Org. Name :</b> <?=$org_master[0]->insurance_company_name ?><br>
				<?php } ?>
				<?php }else if($invoice_med_master[0]->case_credit==1 && $invoice_med_master[0]->case_id>0) { ?>
				<b>Org. Code :</b> <?=$org_master[0]->case_id_code ?><br>
				<b>Org. Name :</b> <?=$org_master[0]->insurance_company_name ?><br>
				<?php } ?>
			</p>
		</td>
	</tr>
</table>
<h3 align="center">TAX Invoice</h3>
</htmlpageheader>
<htmlpagefooter name="myFooter">
    <table width="100%" style="font-size: 10px;">
        <tr>
            <td width="33%" >Page : {PAGENO}/{nbpg}</td>
            <td width="66%" style="text-align: right;">Invoice No.:<?=$invoice_med_master[0]->inv_med_code ?> / Name : <?=$invoice_med_master[0]->inv_name ?></td>
        </tr>
    </table>
</htmlpagefooter>
<table  style="font-size: 14px;width:100%;border-style:solid ;border-width:0.1mm;padding:0.5mm;"  topntail=true autosize="true"   >
			<?php
			$srno=0;
			$head_start=0;
			$amount=0;
			foreach($inv_items as $row)
				{
					if($head_start==0)
						{
							echo '<tr style="font-size: 18px;border-style:solid ;border-width:0.1mm;">
							<td colspan="9" ><b>Invoice ID : </b>'.$row->inv_med_code.' [<b>Dated : </b><i>'.$row->str_inv_date.'</i>]</td></tr>';
							echo '<tr >';
							echo '<th style="width: 20px">#</th>';
							echo '<th align="left" style="width: 200px">Item Name</th>';
							echo '<th align="left">Batch No</th>';
							echo '<th>Exp.</th>';
							echo '<th align="right">Rate</th>';
							echo '<th align="right">Qty.</th>';
							echo '<th align="right">Gross Amt</th>';
							echo '<th align="right">Disc.</th>';
							echo '<th align="right">Net Amt</th>';
							echo '</tr>';
						}
						
						$srno=$srno+1;
						$head_start=$head_start+1;
							echo '<tr >';
							echo '<td style="width: 20px">'.$srno.'</td>';
							echo '<td style="width: 200px">'.$row->item_Name.' '.$row->formulation.'</td>';
							echo '<td>'.$row->batch_no.'</td>';
							echo '<td>'.$row->expiry.'</td>';
							echo '<td align="right">'.$row->price.'</td>';
							echo '<td align="right">'.$row->qty.'</td>';
							echo '<td align="right">'.$row->amount.'</td>';
							echo '<td align="right">'.$row->d_amt.'</td>';
							echo '<td align="right">'.$row->twdisc_amount.'</td>';
				
							echo '</tr>';
				}

			if(count($invoice_med_master)>0) {
				echo '<tr style="font-size: 18px;border-style:solid ;border-width:0.1mm;">';
					echo '<th  style="width: 20px">#</th>';
					echo '<th colspan="5" align="right">Total</th>';
					echo '<th align="right"> '.$invoice_med_master[0]->gross_amount.' </th>';
					echo '<th align="right"> '.$invoice_med_master[0]->inv_disc_total.' </th>';
					echo '<th align="right"> '.round($invoice_med_master[0]->net_amount).' </th>';
					echo '</tr>';
			}
			?>
		</table>

<?php if(count($invoice_med_master)>0) {  ?>
		<hr/>
		<table style="font-size: 9px;width: 100%;border-width: 0.5px;"  autosize="true">
				<tr>
					<td style="width: 10px">#</td>
					<td  align="center">CGST</td>
					<td  align="center">SGST</td>
					<td  align="center">Taxable Amount</td>
					<td  align="center">Net Amount</td>
					<?php if($invoice_med_master[0]->payment_balance>=0 ){ ?> 
					<td  align="center">Amount received</td>
					<td  align="center">Balance Amount</td>
					<?php } ?>
				</tr>
				<tr>
					<td style="width: 10px">#</td>
					<td  align="center"><?=$invoice_med_master[0]->CGST_Tamount?></td>
					<td  align="center"><?=$invoice_med_master[0]->SGST_Tamount?></td>
					<td  align="center"><?=$invoice_med_master[0]->TaxableAmount?></td>
					<td  align="center"><?=number_format(round($invoice_med_master[0]->net_amount),2)?></td>
					<?php if($invoice_med_master[0]->payment_balance>=0 ){ ?> 
					<td  align="center"><?=$invoice_med_master[0]->payment_received?></td>
					<td  align="center"><?=$invoice_med_master[0]->payment_balance?></td>
					<?php } ?>
				</tr>
		</table>
		<hr/>	
<?php } ?>
<table width="100%"  style="font-size: 9px;">
	<tr>
		<td >
			<b>Payment Details <i>[Payment No.:Mode of Payment:Amount:Date]</i>: </b>
			<?php
			foreach($payment_history as $row)
			{ 
				echo '['.$row->id.':'.$row->Payment_type_str.':'.$row->amount.':'.$row->payment_date.']/';
			}
			?>
		<td>
	</tr>
	<tr>
		<td>
			<br/><br/>
		</td>
	</tr>
	<tr>
		<td style="text-align: right;">
			<b>For <?=M_store?> </b>
		</td>
	</tr>
</table>
