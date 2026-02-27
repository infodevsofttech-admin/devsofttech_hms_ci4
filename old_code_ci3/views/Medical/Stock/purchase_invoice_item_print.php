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
<td colspan="2"><p align="center" style="font-size: 15px;" ><?=M_store?></p></td>
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
	 Invoice From :
		 <strong>Supplier : <?=$inv_master_data[0]->name_supplier?></strong><br>
		 <strong>Purchase Invoice No.:  <?=$inv_master_data[0]->Invoice_no?></strong><br>
		 <strong>Invoice Date : <?=MysqlDate_to_str($inv_master_data[0]->date_of_invoice)?></strong><br>
	</p>
</td>
</tr>
</table>
<h3 align="center">Purchase Invoice</h3>
</htmlpageheader>
<htmlpagefooter name="myFooter">
<table width="100%" style="font-size: 10px;">
<tr>
	<td width="33%" >Page : {PAGENO}/{nbpg}</td>
	<td width="66%" style="text-align: right;">Invoice No.:<?=$inv_master_data[0]->Invoice_no?> / Name : <?=$inv_master_data[0]->name_supplier?></td>
</tr>
</table>
</htmlpagefooter>

<table  style="font-size: 10px;width:100%;border-style:solid ;border-width:0.1mm;padding:0.5mm;"  autosize="true"   >
			<tr>
				<th style="width: 10px">#</th>
				<th>Item Name</th>
				<th>Batch No</th>
				<th>Exp.</th>
				<th>MRP</th>
				<th>Qty.</th>
				<th>Rate</th>
				<th>Amount</th>
				<th>Disc.</th>
				<th>Tax Amount</th>
				<th>CGST</th>
				<th>SGST</th>
				<th>Net Amount</th>
			</tr>
			<?php
			$srno=0;
					foreach($purchase_item as $row)
						{ 
							$srno=$srno+1;
							if($row->item_return==1)
							{
								$style='style="color:Red;"';
							}else{
								$style='';
							}
							echo '<tr '.$style.' >';
							echo '<td>'.$srno.'</td>';
							echo '<td>'.$row->Item_name.'</td>';
							echo '<td>'.$row->batch_no.'</td>';
							echo '<td>'.$row->expiry_date.'</td>';
							echo '<td>'.$row->mrp.'</td>';
							echo '<td>'.floatval($row->qty).'+'.floatval($row->qty_free).'</td>';
							echo '<td>'.$row->purchase_price.'</td>';
							echo '<td>'.$row->amount.'</td>';
							echo '<td>'.$row->discount.'</td>';
							echo '<td>'.$row->taxable_amount.'</td>';
							echo '<td>'.$row->CGST_per.'</td>';
							echo '<td>'.$row->SGST_per.'</td>';
							echo '<td>'.$row->net_amount.'</td>';
							echo '</tr>';
						}
			?>

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
				<th><?=$inv_master_data[0]->Taxable_Amt ?></th>
				<th><?=$inv_master_data[0]->CGST_Amt ?></th>
				<th><?=$inv_master_data[0]->SGST_Amt ?></th>
				<th><?=$inv_master_data[0]->T_Net_Amount ?></th>
			</tr>
			
			</table>