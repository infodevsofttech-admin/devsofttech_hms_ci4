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
			<p align="center" style="font-size: 30px;" ><?=M_store?></p>
			<p align="center" style="font-size: 12px" ><?=M_address?><br>
            <?php 
                if(M_Phone_Number!='')
                {
                    echo 'Phone: '.M_Phone_Number;
                } 
                
                if(H_Email!='')
                {
					echo ' ,Email: '.H_Email;
                }
                
            ?>
		</td>
		<td style="width: 40%;">
			<p style="font-size: 12px">
			<strong><?=$person_info[0]->p_fname ?></strong><br>
			<b>Patient ID :</b> <?=$person_info[0]->p_code ?><br>
				
			</p>
		</td>
	</tr>
</table>
<h3 align="center">Return Medicine </h3>
</htmlpageheader>
<htmlpagefooter name="myFooter">
</htmlpagefooter>
<table  style="font-size: 11px;width:100%;border-collapse: collapse;border-style:none ;border-width:0.1mm;padding:3mm;"   autosize="1" >
			<?php
			$srno=0;
			$date_str="";
			$tAmount=0;
			$dAmount=0;
			$total_rec=count($inv_items);

			foreach($inv_items as $row)
			{
				if($date_str<>$row->str_ret_date)
					{
						if($date_str<>"")
						{
							echo '<tr style="font-size: 12px;border-style:solid ;border-width:0.1mm;">';
							echo '<th  style="width: 20px">#</th>';
							echo '<th colspan="6" align="right">Day Total : '.$date_str.'</th>';
							echo '<th align="right">'.number_format(round($dAmount),2).'</th>';
							echo '<th  style="width: 20px">#</th>';
							echo '</tr>';
							echo '<tr style="font-size: 12px;border-style:none ;border-width:0.1mm;">';
							echo '<td colspan="10"> <br><br></td>';
							echo '</tr>';
						}

						$dAmount=0;

						echo '<tr style="font-size: 12px;border-style:solid ;border-width:0.1mm;">
						<td colspan="10" ><b>Return  Date : </b>'.$row->str_ret_date.'</td></tr>';
						echo '<tr style="font-size: 12px;border-style:solid ;border-width:0.1mm;">';
						echo '<th style="width: 20px">Sr.No.</th>';
						echo '<th align="left" style="width: 70px">Invoice Date</th>';
						echo '<th align="left" style="width: 100px">Inv.Code.</th>';
						echo '<th style="width: 150px">Item Name</th>';
						echo '<th style="width: 50px" align="right">Rate</th>';
						echo '<th style="width: 50px" align="right">Old Qty.</th>';
						echo '<th style="width: 50px" align="right">Return Qty.</th>';
						echo '<th style="width: 50px" align="right">Amount</th>';
						echo '<th style="width: 300px" align="right">Update By</th>';
						echo '</tr>';
					}
					
					$srno=$srno+1;
					
						echo '<tr style="font-size: 12px;border-style:solid ;border-width:0.1mm;">';
						echo '<td style="width: 20px">'.$srno.'</td>';
						echo '<td >'.$row->str_inv_date.'</td>';
						echo '<td >'.$row->inv_med_code.'</td>';
						echo '<td >'.$row->item_Name.'</td>';
						echo '<td align="right">'.$row->price.'</td>';
						echo '<td align="right">'.$row->qty.'</td>';
						echo '<td align="right">'.$row->r_qty.'</td>';
						echo '<td align="right">'.$row->amount.'</td>';
						echo '<td  align="right">'.$row->update_remark.'</td>';
						echo '</tr>';
					$tAmount=$tAmount+$row->amount;
					$dAmount=$dAmount+$row->amount;
					$date_str=$row->str_ret_date;
					
					if($total_rec==$srno)
					{
						echo '<tr style="font-size: 12px;border-style:solid ;border-width:0.1mm;">';
						echo '<th  style="width: 20px">#</th>';
						echo '<th colspan="6" align="right">Day Total</th>';
						echo '<th align="right">'.number_format(round($dAmount),2).'</th>';
						echo '<th  style="width: 20px">#</th>';
						echo '</tr>';
						echo '<tr style="font-size: 12px;border-style:none ;border-width:0.1mm;">';
						echo '<td colspan="10"> <br><br></td>';
						echo '</tr>';
					}
			}

				echo '<tr style="font-size: 12px;border-style:solid ;border-width:0.1mm;">';
				echo '<th  style="width: 20px">#</th>';
				echo '<th colspan="6" align="right">Grand Total</th>';
				echo '<th align="right">'.number_format(round($tAmount),2).'</th>';
				echo '<th  style="width: 20px">#</th>';
				echo '</tr>';
			
			?>
		</table>

