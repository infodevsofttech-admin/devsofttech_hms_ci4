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
</style>

<htmlpageheader name="myHeader">
    <table style="font-size: 12px;" cellpadding="5">
	<tr>
    	<td style="width: 60%;vertical-align: top;">
		    <p align="center" style="font-size: 25px;" ><?=H_Name?></p>
		    <p align="center" style="font-size: 12px" ><?=M_address?>, <?=H_address_2?><br>
		</td>
		<td style="width: 40%;">
            <div class="col-md-12">
                <p><strong>Location Name :</strong>
                    <?=$invoice_stock_master[0]->issued_name?><br/>
                    <strong>Indent No. :</strong><?=$invoice_stock_master[0]->indent_code?><br/>
                    <strong>Date :</strong> <?=MysqlDate_to_str($invoice_stock_master[0]->indent_date)?>
                </p>
            </div>
		</td>
	</tr>
</table>
<h3 align="center">	Indent</h3>
</htmlpageheader>
<htmlpagefooter name="myFooter">
    <table width="100%" style="font-size: 10px;">
        <tr>
            <td width="33%" >Page : {PAGENO}/{nbpg}</td>
            <td width="66%" style="text-align: right;">Invoice No.:<?=$invoice_stock_master[0]->indent_code ?> / Location : <?=$invoice_stock_master[0]->issued_name ?></td>
        </tr>
    </table>
</htmlpagefooter>
<table  style="font-size: 10px;width:100%;border-collapse: collapse;border-style:solid ;border-width:0.1mm;padding:3mm;"   autosize="1" >
			<?php
			$srno=0;
			$head_start=0;
			$amount=0;
			foreach($inv_items as $row)
				{
					if($head_start==0)
						{
							echo '<tr style="border-style:solid ;border-width:0.1mm;"><td colspan="7" style="font-size: 18px;" ><b>Invoice ID : </b>'.$row->indent_code.'  [<b>Dated : </b><i>'.$row->str_inv_date.'</i>]</td></tr>';
							echo '<tr >';
							echo '<th style="width: 20px">#</th>';
							echo '<th align="left" style="width: 200px">Item Name</th>';
							echo '<th align="left">Batch No</th>';
							echo '<th>Exp.</th>';
							echo '<th align="right">Rate</th>';
							echo '<th align="right">Qty.</th>';
							echo '<th align="right">Gross Amt</th>';
							echo '</tr>';
						}
						
						$srno=$srno+1;
						$head_start=$head_start+1;

						if($row->sale_return==1){
							echo '<tr>';
							echo '<td colspan="7">Sale Return</td>';
							echo '</tr>';
						}

							echo '<tr >';
							echo '<td style="width: 20px">'.$srno.'</td>';
							echo '<td style="width: 200px">'.$row->item_Name.' '.$row->formulation.'</td>';
							echo '<td>'.$row->batch_no.'</td>';
							echo '<td>'.$row->expiry.'</td>';
							echo '<td align="right">'.$row->price.'</td>';
							echo '<td align="right">'.$row->qty.'</td>';
							echo '<td align="right">'.$row->amount.'</td>';
							echo '</tr>';
				}

			if(count($invoice_stock_master)>0) {
				echo '<tr style="font-size: 12px;border-style:solid ;border-width:0.1mm;">';
					echo '<th  style="width: 20px">#</th>';
					echo '<th colspan="5" align="right">Total</th>';
					echo '<th align="right">'.$invoice_stock_master[0]->gross_amount.'</th>';
					echo '</tr>';
			}
			?>
		</table>

<br/><br/>
<table width="100%"  style="font-size: 12px;">
	<tr>
		<td style="width: 60%;">

		<td>
		<td style="width:40%;text-align: center;">
			<b>For <?=H_Name?> </b>
		</td>
	</tr>
</table>
