<style>@page {
    sheet-size: A4;
    margin-top: 4cm;
    margin-bottom: 5.5in;
    margin-left: 0.5cm;
    margin-right: 0.5cm;

    margin-header:0.5cm;
    margin-footer:6in;
    header: html_myHeader;
    footer: html_myFooter;
}
</style>

<htmlpageheader name="myHeader">
<table  cellspacing="0"  style="font-size: 10px;width:100%;border-style: inset;" >
<tr>
<td style="width: 20%;vertical-align: top;">
    <img style="width: 90px;vertical-align: top;"  src="assets/images/<?=H_logo?>" />
</td>
<td style="width: 60%;vertical-align: top;">
    <p align="center" style="font-size: 26px;" ><b><?=H_Name?></b></p>
    <p align="center" style="font-size: 15px" ><?=H_address_1?>, <?=H_address_2?><br>
    <?php 
        if(H_phone_No!='')
        {
            echo 'Phone: '.H_phone_No;
        } 
    ?>
 </td>
 <td style="width: 20%;vertical-align: top;text-align: right;">
<?php
  $bar_content=':IPD-'.$ipd_id.':'.$payid.':'.$ipd_payment[0]->amount.':PT'.date('dmYhis');
?>
<barcode code="<?=$bar_content?>" size="0.8" type="QR" error="M" class="barcode" />
</td>
</tr>
</table>
<h3 align="center">IPD Payment <?=$ipd_payment[0]->Amount_str?> Receipt : <?=$payid?></h3>
</htmlpageheader>
<htmlpagefooter name="myFooter">
<table width="100%" style="font-size: 10px;">
<tr>
    <td width="15%" >Page : {PAGENO}/{nbpg}</td>
    <td width="85%" style="text-align: right;">IPD No.:<?=$ipd_master[0]->ipd_code?> / UHID:<?=$patient_master[0]->p_code?> /Name : <?=strtoupper($patient_master[0]->p_fname)?></td>
</tr>
</table>
</htmlpagefooter>
<table width="100%" style="font-size: 12px;">
    <tr>
        <td width="50%" >Patient Info.<br/> 
            UHID : <?=$patient_master[0]->p_code?><br>
            Name : <strong><?=$patient_master[0]->title?>  <?=strtoupper($patient_master[0]->p_fname)?></strong><br>
            <?=$patient_master[0]->p_relative?> <?=strtoupper($patient_master[0]->p_rname)?><br>
            Sex : <b><?=$patient_master[0]->xgender?> <?=$patient_master[0]->age?>  </b> P.No. :<?=$patient_master[0]->mphone1?>
        </td>
        <td width="50%" style="text-align: right;">
              IPD No.  : <?=$ipd_master[0]->ipd_code?><br>
							Date : <strong><?=$ipd_payment[0]->payment_date_str?></strong>
        </td>
    </tr>
</table>
<hr/>    
<p style="font-size: 15px;">
    <b>IPD <?=$ipd_payment[0]->Amount_str?> Amount : </b>Rs. <?=$ipd_payment[0]->amount?><BR/>
    <b>Amount in Words : </b>Rs.<?=number_to_word($ipd_payment[0]->amount)?>
</p>
<p>
    <b>Prepared By :</b><?=$ipd_payment[0]->update_by?><br/>
    
</p>
<p style="text-align: right;">
    <b>Signature</b>								
</p>