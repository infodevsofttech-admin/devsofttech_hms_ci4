<style>@page {
        margin-top: 4.2cm;
        margin-bottom: 1.2cm;
        margin-left: 1cm;
        margin-right: 0.5cm;
        
        margin-header:0.5cm;
        margin-footer:0.5cm;
        header: html_myHeader;
        footer: html_myFooter;
	}

	table{
			width: 100%;
			border: 0mm solid black;
			font-size: 11.5px;
			
		}
			
		table.layout {
			border: 0mm solid black;
		}

		table.layout td {
			padding-left: 2mm;
			padding-right: 1mm;
			border-left: 1px dotted black;
		}

		table.layout th {
			padding-left: 2mm;
			padding-right: 1mm;
			text-align: left;
		}


</style>

<htmlpageheader name="myHeader">
    <table style="font-size: 12px;" cellpadding="5">
	<tr>
		<td style="width: 60%;vertical-align: top;">
			<p align="center" style="font-size: 30px;" ><?=M_store?></p>
			<p align="center" style="font-size: 12px" ><?=M_address?>, Uttarakhand<br>
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
		<td style="width: 40%;">
			<p style="font-size: 12px">
			Invoice To :
				<strong><?=$ipd_master[0]->p_fname ?></strong><br>
				<i>Patient Info. as per Hospital Records</i><br>
				<b>UHID ID :</b> <?=$ipd_master[0]->p_code ?><br>
				<b>IPD Code :</b> <?=$ipd_master[0]->ipd_code ?><br>
				<b>Refer By :</b> <?=$ipd_master[0]->doc_name ?><br>
				<?php if($ipd_master[0]->org_id>0) { ?>
				<b>Org. Code :</b> <?=$orgcase[0]->case_id_code ?><br>
				<b>Org. Name :</b> <?=$orgcase[0]->insurance_company_name ?><br>
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
            <td width="66%" style="text-align: right;">IPD-ID:<?=$ipd_master[0]->ipd_code ?> / UHID:<?=$ipd_master[0]->p_code ?> /Name : <?=$ipd_master[0]->p_fname ?></td>
        </tr>
    </table>
</htmlpagefooter>

<?php
$Grand_return_total=0;
$Grand_sale_total=0;
$invoice_total=0;
?>

<table  class="layout" autosize="1">
			<?php
			$srno=0;
			$head_start=0;
				foreach($inv_items as $row)
				{
					if($row->id=='' && $row->inv_med_id != '')
					{
							echo '<tr >';
							echo '<th style="width: 20px">#</th>';
							echo '<th colspan="5" ><b>Invoice Total :</b>'.$row->inv_med_code.'</th>';
							echo '<th align="right"><b>'.$invoice_total.'</b></th>';
							echo '</tr>';
					
							$head_start=0;
					}
					elseif($row->id=='' && $row->inv_med_id == ''){
							echo '<tr><th colspan="7"><hr/></th></tr>';
							echo '<tr >';
							echo '<th style="width: 10px">#</th>';
							echo '<th colspan="5" align="center"><b>Grand Sale Total</b></th>';
							echo '<th align="right"><b>'.$Grand_sale_total.'</b></th>';
							echo '</tr>';
							
					}else{
						
						if($head_start==0)
						{
							echo '<tr>
									<th colspan="7"><br> <br></th></tr>';
							echo '<tr ><th colspan="7" ><b>Invoice ID : </b>'.$row->inv_med_code.' [<b>Dated : </b><i>'.$row->str_inv_date.'</i> ]</th></tr>';
							echo '<tr style="border-style:solid ;border-width:0.1mm;">';
							echo '<th style="width: 20px">#</th>';
							echo '<th align="left" style="width: 200px">Item Name</th>';
							echo '<th align="left">Batch No</th>';
							echo '<th>Exp.</th>';
							echo '<th align="right">Rate</th>';
							echo '<th align="right">Qty.</th>';
							echo '<th align="right">Amount</th>';
							echo '</tr>';

							$invoice_total=0;
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
							echo '</tr>';

							$Grand_sale_total+=$row->amount;
							$invoice_total+=$row->amount;

					}
				}
			?>
		</table>

		<h3> Return Items </h3>
		<table  class="layout" autosize="1">
			<?php
			$srno=0;
			$head_start=0;
				foreach($inv_items_return as $row)
				{
					if($row->id=='' && $row->inv_med_id != '')
					{
							echo '<tr >';
							echo '<th style="width: 20px">#</th>';
							echo '<th colspan="5" ><b>Invoice Total :</b>'.$row->inv_med_code.'</th>';
							echo '<th align="right"><b>'.$invoice_total.'</b></th>';
							echo '</tr>';
					
							$head_start=0;
					}
					elseif($row->id=='' && $row->inv_med_id == ''){
							echo '<tr><th colspan="7"><hr/></th></tr>';
							echo '<tr >';
							echo '<th style="width: 10px">#</th>';
							echo '<th colspan="5" align="center"><b>Grand Return Total</b></th>';
							echo '<th align="right"><b>'.$Grand_return_total.'</b></th>';
							echo '</tr>';

							
					}else{
						
						if($head_start==0)
						{
							echo '<tr>
									<th colspan="7"><br> <br></th></tr>';
							echo '<tr ><th colspan="7" ><b>Invoice ID : </b>'.$row->inv_med_code.' [<b>Dated : </b><i>'.$row->str_inv_date.'</i> ]</th></tr>';
							echo '<tr style="border-style:solid ;border-width:0.1mm;">';
							echo '<th style="width: 20px">#</th>';
							echo '<th align="left" style="width: 200px">Item Name</th>';
							echo '<th align="left">Batch No</th>';
							echo '<th>Exp.</th>';
							echo '<th align="right">Rate</th>';
							echo '<th align="right">Qty.</th>';
							echo '<th align="right">Amount</th>';
							echo '</tr>';
							$invoice_total=0;
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
							echo '</tr>';
							$Grand_return_total+=$row->amount;
							$invoice_total+=$row->amount;

					}
				}
			?>
		</table>

<?php if(count($inv_med_group)>0) {  
	$Gross_amt=$Grand_sale_total-$Grand_return_total;

	
	?>
		<hr/>
		<table class="layout" autosize="1">
				<tr>
					<th style="width: 10px">#</th>
					<th style="width: 120px" align="center">Sale [A]</th>
					<th style="width: 120px" align="center">Sale Return [B]</th>
					<th style="width: 120px" align="center">Gross Amt. [G] =([A] - [B])</th>
					<th style="width: 120px" align="center">Discount [D]</th>
					<th style="width: 120px" align="center">Net Amount [N] =([G]-[D])</th>
					<?php if($inv_med_group[0]->payment_balance>=0 ){ ?> 
					<th style="width: 120px" align="center">Amount received</th>
					<th style="width: 120px" align="center">Balance Amount</th>
					<?php } ?>
				</tr>
				<tr>
				<th style="width: 10px">#</th>
					<th style="width: 120px" align="center"><?=($Grand_sale_total)?></th>
					<th style="width: 120px" align="center"><?=$Grand_return_total?></th>
					<th style="width: 120px" align="center"><?=$Gross_amt?></th>
					<th style="width: 120px" align="center"><?=$inv_med_group[0]->discount_group?></th>
					<th style="width: 120px" align="center"><?=number_format(round($inv_med_group[0]->net_amount),2)?></th>
					<?php //if($inv_med_group[0]->payment_balance>=0 ){ ?> 
					<th style="width: 120px" align="center"><?=$inv_med_group[0]->payment_received?></th>
					<th style="width: 120px" align="center"><?=$inv_med_group[0]->payment_balance?></th>
					<?php //} ?>
				</tr>
		</table>
		<hr/>	
<?php } ?>
<br/><br/>
<table width="100%"  style="font-size: 12px;">
	<tr>
		<td style="width: 60%;">
			<b>Payment Details <i>[Payment No.:Mode of Payment:Amount]</i>: </b>
			<?php
			foreach($payment_history as $row)
			{ 
				echo '['.$row->id.':'.$row->Payment_type_str.':'.$row->amount.']/';
			}
			?>
		<td>
		<td style="width:40%;text-align: center;">
			<b>For <?=M_store?> </b>
		</td>
	</tr>
</table>
