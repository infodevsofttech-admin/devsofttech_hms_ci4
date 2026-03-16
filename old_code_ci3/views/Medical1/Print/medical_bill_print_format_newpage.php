<style>@page {
        margin-top: 4.5cm;
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
		 	Invoice To :
		 	<strong><?=$ipd_master[0]->p_fname ?></strong><br>
		 	<p style="font-size: 9px">
			 	<i>Patient Info. as per KHRC Records</i><br>
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
    <h3 align="center">CASH / Credit Memo</h3>
</htmlpageheader>
<htmlpagefooter name="myFooter">
    <table width="100%" style="font-size: 10px;">
        <tr>
            <td width="33%" ></td>
            <td width="66%" style="text-align: right;">IPD-ID:<?=$ipd_master[0]->ipd_code ?> / UHID:<?=$ipd_master[0]->p_code ?> /Name : <?=$ipd_master[0]->p_fname ?></td>
        </tr>
    </table>
</htmlpagefooter>

			<?php
			$srno=0;
			$head_start=0;
				foreach($inv_items as $row)
				{
					if($row->id=='' && $row->inv_med_id != '')
					{
							echo '<tr>';
							echo '<td style="width: 20px">#</td>';
							echo '<td colspan="5" ><b>Invoice Total :</b>'.$row->inv_med_code.'</td>';
							echo '<td align="right"><b>'.$row->amount.'</b></td>';
							echo '<td align="right"><b>'.$row->d_amt.'</b></td>';
							echo '<td></td>';
							echo '<td align="right"><b>'.$row->gst.'</b></td>';
							echo '<td align="right"><b>'.$row->twdisc_amount.'</b></td>';
							echo '</tr>';
							echo '</table>';
							echo '<pagebreak />';
					
							$head_start=0;
					}
					elseif($row->id=='' && $row->inv_med_id == ''){
							echo '<table  cellspacing="0"  style="font-size: 10px;width:100%;border-style: inset;" >';
							echo '<tr><td colspan="11"><hr/></td></tr>';
							echo '<tr style="font-size=12px;">';
							echo '<td style="width: 10px">#</td>';
							echo '<td colspan="5" align="center"><b>Grand Total</b></td>';
							echo '<td align="right"><b>'.$row->amount.'</b></td>';
							echo '<td align="right"><b>'.$row->d_amt.'</b></td>';
							echo '<td></td>';
							echo '<td align="right"><b>'.$row->gst.'</b></td>';
							echo '<td align="right"><b>'.$row->twdisc_amount.'</b></td>';
							echo '</tr>';
							echo '</table>';
					}else{
						
						if($head_start==0)
						{
							echo '<table  cellspacing="0"  style="font-size: 10px;width:100%;border-style: inset;" >';

							echo '<tr><td colspan="11" style="font-size:15px;align:center;"><p><b>Invoice ID : </b>'.$row->inv_med_code.' [<b>Dated : </b><i>'.$row->str_inv_date.'</i>]</p></td></tr>';
							echo '<tr>';
							echo '<th style="width: 20px">#</th>';
							echo '<th align="left" style="width: 200px">Item Name</th>';
							echo '<th align="left">Batch No</th>';
							echo '<th>Exp.</th>';
							echo '<th align="right">Rate</th>';
							echo '<th align="right">Qty.</th>';
							echo '<th align="right">Price</th>';
							echo '<th align="right">Disc.</th>';
							echo '<th align="right">HSNCODE/GST</th>';
							echo '<th align="right">GST</th>';
							echo '<th align="right">Amount</th>';
							echo '</tr>';
						}
						
						$srno=$srno+1;
						$head_start=$head_start+1;
							echo '<tr>';
							echo '<td style="width: 20px">'.$srno.'</td>';
							echo '<td style="width: 200px">'.$row->item_Name.' '.$row->formulation.'</td>';
							echo '<td>'.$row->batch_no.'</td>';
							echo '<td>'.$row->expiry.'</td>';
							echo '<td align="right">'.$row->price.'</td>';
							echo '<td align="right">'.$row->qty.'</td>';
							echo '<td align="right">'.$row->amount.'</td>';
							echo '<td align="right">'.$row->d_amt.'</td>';
							echo '<td align="center">'.$row->HSNCODE.'/'.$row->gst_per.'</td>';
							echo '<td align="right">'.$row->gst.'</td>';
							echo '<td align="right">'.$row->twdisc_amount.'</td>';
							echo '</tr>';
					}
				}
			?>
<br/><br/>
