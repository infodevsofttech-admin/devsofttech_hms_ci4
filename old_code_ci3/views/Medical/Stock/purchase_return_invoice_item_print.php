<?php
$pharmacyName = defined('H_Med_Name') ? H_Med_Name : (defined('M_store') ? M_store : 'Medical Store');
$pharmacyAddress = defined('H_Med_address_1') ? H_Med_address_1 : (defined('M_address') ? M_address : '');
$pharmacyPhone = defined('H_Med_phone_No') ? H_Med_phone_No : (defined('M_Phone_Number') ? M_Phone_Number : '');
$pharmacyGst = defined('H_Med_GST') ? H_Med_GST : '';
$pharmacyLicense = defined('M_LIC') ? M_LIC : '';
$hospitalEmail = defined('H_Email') ? H_Email : '';
?>
<style>@page {

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
<td colspan="2"><p align="center" style="font-size: 15px;" ><?=$pharmacyName?></p></td>
</tr>
<tr>
<td style="width: 60%;vertical-align: top;">
	<p align="center" style="font-size: 10px" ><?=$pharmacyAddress?>, Uttarakhand<br>
	<?php 
		if($pharmacyPhone!='')
		{
			echo 'Phone: '.$pharmacyPhone;
		} 
		
		if($hospitalEmail!='')
		{
		   echo ' ,Email: '.$hospitalEmail;
		}
		
		echo '<br>';

		if($pharmacyGst!='')
		{
			echo '<b>GST: '.$pharmacyGst .'</b>';
		}
		
		if($pharmacyLicense!='')
		{
			echo ' L.No: '.$pharmacyLicense;
		}
	?>
</td>
<td style="width: 40%;vertical-align:top ;">
	 <p style="font-size: 10px">
	 Invoice To :<br/>
		 <strong>Supplier : <?=$purchase_return_invoice[0]->name_supplier?></strong><br>
		 <strong>Purchase Return Invoice No.:  <?=$purchase_return_invoice[0]->Invoice_no?></strong><br>
		 <strong>Invoice Date : <?=$purchase_return_invoice[0]->str_date_of_invoice ?></strong><br>
	</p>
</td>
</tr>
</table>
<h3 align="center">Purchase Return</h3>
</htmlpageheader>
<htmlpagefooter name="myFooter">
<table width="100%" style="font-size: 12px;">
<tr>
	<td width="33%" >Page : {PAGENO}/{nbpg}</td>
	<td width="66%" style="text-align: right;">Invoice No.:<?=$purchase_return_invoice[0]->Invoice_no ?> / Name : <?=$purchase_return_invoice[0]->name_supplier ?></td>
</tr>
</table>
</htmlpagefooter>

<table  style="font-size: 12px;width:100%;border-style:solid ;border-width:0.1mm;padding:0.5mm;"  topntail=true autosize="true"  >
			<tr>
				<th style="width: 20px;text-align:right;">#</th>
				<th style="width: 40px;text-align:right;">RecNo</th>
				<th style="width: 200px;text-align:left;">Item Name</th>
				<th style="width: 100px;text-align:left;">Batch No</th>
				<th style="width: 50px;text-align:left;">Exp.</th>
				<th style="width: 100px;text-align:right;">MRP of Pack</th>
				<th style="width: 100px;text-align:right;">Return Unit Qty/ Pack.</th>
				<th style="width: 100px;text-align:right;">P. Rate/Unit</th>
				<th style="width: 100px;text-align:right;">GST %</th>
				<th style="width: 100px;text-align:right;">Amount</th>
				<th style="width: 100px;text-align:right;">GST Amount</th>
				<th style="width: 100px;text-align:right;">Net Amount</th>
			</tr>
			<?php
			$srno=0;
			$total_amt=0;
				foreach($purchase_return_invoice_item as $row)
				{ 
					$srno=$srno+1;
					echo '<tr >';
					echo '<td style="text-align:right;">'.$srno.'</td>';
					echo '<td style="text-align:right;">'.$row->r_id.'</td>';
					echo '<td style="text-align:left;">'.$row->Item_name.'</td>';
					echo '<td style="text-align:left;">'.$row->batch_no.'</td>';
					echo '<td style="text-align:left;"> '.$row->exp_date_str.'</td>';
					echo '<td style="text-align:right;">'.$row->mrp.'</td>';
					echo '<td style="text-align:right;">'.floatval($row->r_qty).' / '.floatval($row->qty_pak).'</td>';
					echo '<td style="text-align:right;">'.$row->purchase_price.' / '.$row->purchase_unit_rate.'</td>';
					echo '<td style="text-align:right;">'.$row->gst_per.'</td>';
					echo '<td style="text-align:right;">'.Round($row->r_qty*$row->purchase_unit_rate,2).'</td>';
					echo '<td style="text-align:right;">'.Round(($row->gst_per*$row->r_qty*$row->purchase_unit_rate)/100,2).'</td>';
					echo '<td style="text-align:right;">'.Round($row->r_qty*$row->purchase_unit_rate + ($row->gst_per*$row->r_qty*$row->purchase_unit_rate)/100,2).'</td>';
					echo '</tr>';
					$total_amt+=Round($row->r_qty*$row->purchase_unit_rate + ($row->gst_per*$row->r_qty*$row->purchase_unit_rate)/100,2);
				}
			echo '<input type="hidden" id="srno" name="srno" value="'.$srno.'" />';
			?>
			<!---- Total Show  ----->
			<tr>
				<th style="width: 10px">#</th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th>Total</th>
				<th style="text-align:right;"><?=$total_amt?></th>
			</tr>
			
			</table>