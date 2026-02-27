<style>@page {
    margin-top: 3.0cm;
    margin-bottom: 1.2cm;
    margin-left: 0.5cm;
    margin-right: 0.5cm;

    margin-header:0.5cm;
    margin-footer:0.5cm;
    header: html_myHeader;
    footer: html_myFooter;
}

.RxPlace {
    position: absolute;
    overflow: visible;
    top: 63mm; 
    left: 10mm; 
    width: 175mm;   /* you must specify a width */
    margin: 0;
    padding: 0;
    
}

table p {
    font-size: 12px;
}

th, td {
  
  text-align: left;
  font-size: 12px;
}
</style>

<htmlpageheader name="myHeader">
<table  cellspacing="0"  style="font-size: 10px;width:100%;border-style: inset;" >
    <tr>
        <td style="width: 120px;vertical-align: top;">
            <img style="width: 100px;vertical-align: top;"  src="assets/images/<?=H_logo?>" />
        </td>
        <td style="width: 400px;vertical-align: top;text-align: left;">
            <p align="center" style="font-size: 20px;" ><?=H_Name?></p>
            
            <p align="center" style="font-size: 12px" ><?=H_address_1?>, <?=H_address_2?><br>
            <?php 
                if(H_phone_No!='')
                {
                    echo 'Phone: '.H_phone_No;
                } 
            ?>
        </td>
        <td style="width: 300px;vertical-align: top;text-align: right;">
        <p align="right" style="font-size: 22px;" >
            Dr. <?=$opd_master[0]->doc_name ?>
        </p>
        <?=nl2br($opd_master[0]->doc_sign)?>
        </td>
    </tr>
</table>
<hr/>
</htmlpageheader>
<htmlpagefooter name="myFooter">
<p align="center">Print Time : <?=date('d-m-Y H:i:s')?></p>
</htmlpagefooter>
<table width="100%" border="0" style="font-size:10pt;">
<tr>
	<td width="33.3%" VALIGN="top">
		Name : <strong><?=strtoupper($pName)?></strong><br>
		<?=$pRelative?><br>
		Gender/Age : <b><?=$age_sex?> </b><br>
		Mob :<?=$phoneno?>'<br/>
		Address :<?=$p_address?>'
	</td>
	<td width="33.3%" VALIGN="top">
		UHID : <?=$uhid_no?><br>
		Sr No.: <b><?=$opd_sr_no ?></b> <br/>
		OPD No.: <B><?=$opd_no?></B><br>
		<br><b>Date: <?=$opd_date?></b><br><?=$exp_date?>
	</td>
	<td width="33.3%" VALIGN="top">
		<b>DEPARTMENT :</b><br>
		<?=$SpecName?>
		<br>
        <?=$opd_fee_desc?>
		<br><b>No. of Visit</b> : <?=$total_no_visit?>
		<?=$last_opdvisit_date?>
        <br>Book Time : <?=$str_opd_book_date?>
	</td>
</tr>
</table>
