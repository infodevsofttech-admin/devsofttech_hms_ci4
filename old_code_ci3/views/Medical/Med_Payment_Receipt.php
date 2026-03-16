<?php
$pharmacyName = defined('H_Med_Name') ? H_Med_Name : (defined('M_store') ? M_store : 'Medical Store');
$pharmacyAddress = defined('H_Med_address_1') ? H_Med_address_1 : (defined('M_address') ? M_address : '');
$pharmacyPhone = defined('H_Med_phone_No') ? H_Med_phone_No : (defined('M_Phone_Number') ? M_Phone_Number : '');
$pharmacyGst = defined('H_Med_GST') ? H_Med_GST : '';
?>
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
		    <p align="center" style="font-size: 30px;" ><?=$pharmacyName?></p>
		    <p align="center" style="font-size: 12px" ><?=$pharmacyAddress?>, Uttarakhand<br>
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
		 <td style="width: 40%;text-align:right;">
		 <?php
        	$bar_content=$ipd_master[0]->p_fname.':IPD-'.$ipd_master[0]->ipd_code .':P-'.date('Y-m-d H:i:s');
        	?>
        	<barcode code="<?=$bar_content?>" size="0.8" type="QR" error="M" class="barcode" />
		 	
		 </td>
	</tr>
</table>
<h3 align="center">Payment Receipt</h3>
</htmlpageheader>
<htmlpagefooter name="myFooter">
    <table width="100%" style="font-size: 10px;">
        <tr>
            <td width="33%" >Page : {PAGENO}/{nbpg}</td>
            <td width="66%" style="text-align: right;">IPD-ID:<?=$ipd_master[0]->ipd_code ?> / UHID:<?=$ipd_master[0]->p_code ?> /Name : <?=$ipd_master[0]->p_fname ?></td>
        </tr>
    </table>
</htmlpagefooter>
<p style="font-size: 12px">
	To :
	<strong><?=$ipd_master[0]->p_fname ?></strong><br>
	<i>Patient Info. as per Hospital Records</i><br>
	<b>UHID ID :</b> <?=$ipd_master[0]->p_code ?><br>
	<b>IPD Code :</b> <?=$ipd_master[0]->ipd_code ?><br>
	<b>Phone No. : </b><?=$ipd_master[0]->mphone1 ?> , <?=$ipd_master[0]->P_mobile1 ?> , <?=$ipd_master[0]->P_mobile2 ?><br>
	<b>Address : </b><?=$ipd_master[0]->add1 ?> , <?=$ipd_master[0]->add2 ?> , <?=$ipd_master[0]->city ?> , <?=$ipd_master[0]->district ?> , <?=$ipd_master[0]->state ?><br>
	<b>Refer By :</b> <?=$ipd_master[0]->doc_name ?><br>
	<?php if($ipd_master[0]->org_id>0) { ?>
	<b>Org. Code :</b> <?=$orgcase[0]->case_id_code ?><br>
	<b>Org. Name :</b> <?=$orgcase[0]->insurance_company_name ?><br>
	<?php } ?>
</p>
<table border="1" cellspacing=0 style="font-size: 12px;width:100%;border-style:solid;">
	<tr>
		<th>Pay.No.</th>
		<th>Mode</th>
		<th>Date </th>
		<th>Amount</th>
	</tr>
	<?php
		foreach($payment_history as $row)
		{ 
			echo '<tr><td>'.$row->id.'</td><td>'.$row->Payment_type_str.'</td><td>'.$row->str_payment_date.'</td><td align="right">'.$row->amount.'</td></tr>';
			
		}
	?>
</table>
<br/>
<br/>
<?php if(count($inv_med_group)>0) { ?>
		 <b>Pharmacy Bill Amount at the Time  of Print Receipt </b>: 
		 Rs. <?=Round($inv_med_group[0]->net_amount) ?><br>
		 <b>Total Amount Paid : </b> Rs. <?=$inv_med_group[0]->payment_received ?><br>
		 <?php
			$balance_amount=Round($inv_med_group[0]->net_amount-$inv_med_group[0]->payment_received);
		
		 ?>
		 <b>Balance Amount : </b> Rs. <?=Round($balance_amount)?><br>
		 <b>Print Time : </b> <?=date('d-m-Y H:i:s') ?>
<?php } ?>
<br/>
<br/>
<br/>
<br/>
<br/>
<br/>
<p align="right">
	<b>For <?=$pharmacyName?> </b>
</p>