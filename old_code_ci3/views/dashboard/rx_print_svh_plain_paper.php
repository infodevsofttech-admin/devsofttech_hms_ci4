<style>@page {
    margin-top: 4.0cm;
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
    top: 73mm; 
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
            <img style="width: 100px;vertical-align: top;"  src="/assets/images/<?=H_logo?>" />
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
<hr/>
<div class="RxPlace">
        <?php
            echo $painscale_img;
            echo '<br/>';
            if(strlen($vital_content)>0){
                echo $vital_content;
                echo '<br/>';
            }
            
            if(strlen($Complaint)>0){
                echo $Complaint;
                echo '<br/>';
            }
                        
            if(strlen($diagnosis)>0){
                echo $diagnosis;
                echo '<br/>';
            }
            
            echo $medical;
            echo '<br/>';
            
            if(strlen($investigation)>0){
                echo $investigation;
                echo '<br/>';
            }

            if(strlen($Finding_Examinations)>0){
                echo $Finding_Examinations;
                echo '<br/>';
            }

            if(strlen($Prescriber_Remarks)>0){
                echo $Prescriber_Remarks;
                echo '<br/>';
            }
            
            if(strlen($advice)>0){
                echo $advice;
                echo '<br/>';
            }

            if(strlen($next_visit)>0){
                echo $next_visit;
                echo '<br/>';
            }

            if(strlen($refer_to)>0){
                echo $refer_to;
                echo '<br/>';
            }
            
            echo '<p align="Right">';
            echo '<br/>';
            echo '<br/>';
            echo '<br/>';
            echo '<b>'.$doc_name.'</b>';
            echo '<br/>';
            echo $doc_sign;
            echo '</p>';
        ?>
</div>