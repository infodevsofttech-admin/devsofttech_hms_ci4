<style>
	@page {

		sheet-size: A5-L;

		margin-top: 2.6cm;
		margin-bottom: 1.2cm;
		margin-left: 0.5cm;
		margin-right: 0.5cm;

		margin-header: 0.5cm;
		margin-footer: 0.5cm;
		header: html_myHeader;
		footer: html_myFooter;
	}
</style>

<htmlpageheader name="myHeader">
	<table style="font-size: 14px;width:100%;" cellpadding="5">
		<tr>
			<td colspan="2"></td>
		</tr>
		<tr>
			<td style="width: 70%;vertical-align: top;">
				<p align="center" style="font-size: 15px;font-weight: bold;"><?= M_store ?></p>
				<p align="center" style="font-size: 12px"><?= M_address ?><br>
					<?php
					if (M_Phone_Number != '') {
						echo 'Phone: ' . M_Phone_Number;
					}

					if (H_Email != '') {
						echo ' ,Email: ' . H_Email;
					}

					?>
			</td>
			<td style="width: 30%;vertical-align:top ;margin: 10px;">
				<p style="font-size: 12px">
					Invoice To :
					<strong><?= $invoice_med_master[0]->inv_name ?></strong><br>
					<?php if ($invoice_med_master[0]->customer_type == 0) { ?>
						Phone No. : <?= $invoice_med_master[0]->inv_phone_number ?><br>
						<?php if ($invoice_med_master[0]->doc_name != '') { ?>
							Refer By :  <?= $invoice_med_master[0]->doc_name ?><br>
						<?php } ?>
					<?php } ?>
					<?php if ($invoice_med_master[0]->customer_type == 1 && count($patient_master) > 0) { ?>
						<i>Patient Info. as per Hospital Records</i><br>
						<b>UHID ID :</b> <?= $patient_master[0]->p_code ?><br>
					<?php } ?>
					<?php if ($invoice_med_master[0]->ipd_id > 0) { ?>
						<b>IPD Code :</b> <?= $ipd_master[0]->ipd_code ?><br>
						<b>Refer By :</b> <?= $ipd_master[0]->doc_name ?><br>
						<?php if ($ipd_master[0]->case_id > 0 && $ipd_master[0]->case_id <> '') { ?>
							<b>Org. Code :</b> <?= $org_master[0]->case_id_code ?><br>
							<b>Org. Name :</b> <?= $org_master[0]->insurance_company_name ?><br>
						<?php } ?>
					<?php } else if ($invoice_med_master[0]->case_credit == 1 && $invoice_med_master[0]->case_id > 0) { ?>
						<b>Org. Code :</b> <?= $org_master[0]->case_id_code ?><br>
						<b>Org. Name :</b> <?= $org_master[0]->insurance_company_name ?><br>
					<?php } ?>
				</p>
			</td>
		</tr>
	</table>
	<hr style="margin: 0px;"/>
</htmlpageheader>
<htmlpagefooter name="myFooter">
	<hr style="margin: 0px;"/>
	<table width="100%" style="font-size: 10px;">
		<tr>
			<td width="33%">Page : {PAGENO}/{nbpg}</td>
			<td width="66%" style="text-align: right;">Invoice No.:<?= $invoice_med_master[0]->inv_med_code ?> / Name : <?= $invoice_med_master[0]->inv_name ?></td>
		</tr>
	</table>
</htmlpagefooter>
<table style="font-size: 12px;width:100%;" cellpadding="1">
		<tr>
			<td style="width: 50%;vertical-align:top ;margin: 10px;">
				<?php
				if (H_Med_GST != '') {
					echo '<b>GST: ' . H_Med_GST . '</b>';
				}

				if (M_LIC != '') {
					echo '<br/> L.No: ' . M_LIC;
				}
				?>
			</td>
			<td style="width: 50%;vertical-align:top;text-align:right ;margin: 10px;">
				<b>Invoice ID : </b><?= $invoice_med_master[0]->inv_med_code ?><br>
				<b>Date : </b><?= $invoice_med_master[0]->str_inv_date ?>
			</td>
		</tr>
		<tr>
			<td colspan="3" style="text-align: center;font-size: 14px;font-weight: bold;">
				<?php if (H_Med_GST != '') { ?>
					Bill of Supply 
				<?php }else{ ?>
					Invoice
				<?php } ?><br/>
				<span style="text-align: center;font-size: 10px;font-weight:normal;">(composition taxable person, not eligible to collect tax on supplies)</span>
			</td>
		</tr>
	</table>
<table style="font-size: 14px;width:100%;border-style:solid ;border-collapse: collapse;border-width:0.1mm;padding: 5px;" topntail=true autosize="true">
	<?php
	$srno = 0;
	$head_start = 0;
	$amount = 0;

	$no_of_rec=14-count($inv_items);

	foreach ($inv_items as $row) {
		if ($head_start == 0) {
			echo '<tr style="font-size: 20px;border-style:solid ;border-width:0.1mm;">';
			echo '<th style="width: 40px;border-right:1px solid;">#</th>';
			echo '<th align="left" style="width: 400px;border-right:1px solid;">Item Name</th>';
			echo '<th align="left" style="width: 100px;border-right:1px solid;" >Pack</th>';
			echo '<th align="left" style="width: 100px;border-right:1px solid;" >Batch No</th>';
			echo '<th align="left" style="width: 100px;border-right:1px solid;" >Exp.</th>';
			echo '<th align="right" style="width: 100px;border-right:1px solid;">Qty.</th>';
			echo '<th align="right" style="width: 100px;border-right:1px solid;">Rate</th>';
			echo '<th align="right" style="width: 100px;border-right:1px solid;">Gross Amt</th>';
            echo '<th align="right" style="width: 100px;border-right:1px solid;">Disc.</th>';
            echo '<th align="right" style="width: 100px;border-right:1px solid;">Net Amt</th>';
			echo '</tr>';
		}

		$srno = $srno + 1;
		$head_start = $head_start + 1;

		if($row->sale_return==1){
			echo '<tr>';
			echo '<td colspan="8">Sale Return</td>';
			echo '</tr>';
		}

		echo '<tr  >';
		echo '<td align="center" style="width: 40px;border-right:1px solid;">' . $srno . '</td>';
		echo '<td style="width: 400px;border-right:1px solid;">' . $row->item_Name . '</td>';
		echo '<td style="width: 100px;border-right:1px solid;">' . $row->formulation . '</td>';
		echo '<td style="width: 100px;border-right:1px solid;">' . $row->batch_no . '</td>';
		echo '<td style="width: 100px;border-right:1px solid;" >' . $row->expiry . '</td>';
		echo '<td align="right" style="border-right:1px solid;">' . $row->qty . '</td>';
		echo '<td align="right" style="border-right:1px solid;">' . $row->price . '</td>';
		echo '<td align="right" style="border-right:1px solid;">' . number_format(round($row->amount),2) . '</td>';
        echo '<td align="right" style="border-right:1px solid;">' . number_format(round($row->d_amt),2) . '</td>';
        echo '<td align="right" style="border-right:1px solid;">' . number_format(round($row->twdisc_amount),2) . '</td>';
		echo '</tr>';
	}

	for($i=0;$i<$no_of_rec;$i++)
	{
		echo '<tr >';
		echo '<td align="center" style="width: 40px;border-right:1px solid;">.</td>';
		echo '<td style="width: 400px;border-right:1px solid;"> </td>';
		echo '<td style="width: 100px;border-right:1px solid;"> </td>';
		echo '<td style="width: 100px;border-right:1px solid;"> </td>';
		echo '<td style="width: 100px;border-right:1px solid;" > </td>';
		echo '<td align="right" style="border-right:1px solid;"  > </td>';
		echo '<td align="right" style="border-right:1px solid;" > </td>';
		echo '<td align="right" style="border-right:1px solid;" > </td>';
        echo '<td align="right" style="border-right:1px solid;" > </td>';
		echo '<td align="right" style="border-right:1px solid;" > </td>';
		echo '</tr>';
	}

	if (count($invoice_med_master) > 0) {
		echo '<tr style="font-size: 25px;border-style:solid ;border-width:0.1mm;">';
		echo '<th  style="width: 40px">#</th>';
		echo '<th colspan="6" align="right">Total</th>';
		echo '<th align="right">'.number_format(round($invoice_med_master[0]->gross_amount),2).'</th>';
        echo '<th align="right">'.number_format(round($invoice_med_master[0]->inv_disc_total),2).'</th>';
        echo '<th align="right">'.number_format(round($invoice_med_master[0]->net_amount),2).'</th>';
		echo '</tr>';
	}
	?>
</table>

<?php if (count($invoice_med_master) > 0) {  ?>
	<table style="font-size: 11px;width: 100%;border-width: 0.5px;padding:0.5mm;" autosize="true">
		<tr>
			<td style="width: 10px">#</td>
			<td align="center">Gross</td>
			<td align="center">Discount</td>
			<td align="center" style="font-size: 15px;font-weight: bold;">Net Amount</td>
			<?php if ($invoice_med_master[0]->payment_balance >= 0) { ?>
				<td align="center">Amount received</td>
				<td align="center">Balance Amount</td>
			<?php } ?>
		</tr>
		<tr>
			<td style="width: 10px">#</td>
			<td align="center"><?= number_format(round($invoice_med_master[0]->gross_amount),2) ?></td>
			<td align="center"><?= number_format(round($invoice_med_master[0]->discount_amount + $invoice_med_master[0]->item_discount_amount),2) ?></td>
			<td align="center" style="font-size: 15px;font-weight: bold;"><?= number_format(round($invoice_med_master[0]->net_amount), 2) ?></td>
			<?php if ($invoice_med_master[0]->payment_balance >= 0) { ?>
				<td align="center"><?= $invoice_med_master[0]->payment_received ?></td>
				<td align="center"><?= $invoice_med_master[0]->payment_balance ?></td>
			<?php } ?>
		</tr>
	</table>
	<hr style="margin: 0px;"/>
<?php } ?>
<table width="100%" style="font-size: 10px;">
	<tr>
		<td>
			<b>Payment Details <i>[Payment No.:Mode of Payment:Amount:Date]</i>: </b>
			<?php
			foreach ($payment_history as $row) {
				echo '[' . $row->id . ':' . $row->Payment_type_str . ':' . $row->amount . ':' . $row->payment_date . ']/';
			}
			?>
		<td>
	</tr>
	<tr>
		<td>
			<br /><br />
		</td>
	</tr>
	<tr>
		<td style="text-align: right;">
			<b>For <?= M_store ?> </b>
		</td>
	</tr>
</table>