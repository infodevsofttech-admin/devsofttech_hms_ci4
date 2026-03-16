<style>@page {

margin-top: 3.8cm;
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
<table  cellspacing="0"  style="font-size: 12px;width:100%;border-style: inset;" >
    <tr>
        <td style="width: 150px;vertical-align: top;">
            <img style="width: 130px;vertical-align: top;"  src="assets/images/<?=H_logo?>" />
        </td>
        <td style="width: 400px;vertical-align: top;">
            <p align="center" style="font-size: 32px;" ><?=H_Name?></p>
            <p align="center" style="font-size: 15px" ><?=H_address_1?><br/><?=H_address_2?><br>
            <?php 
                if(H_phone_No!='')
                {
                    echo 'Phone: '.H_phone_No;
                } 
            ?>
            <?php 
                if(H_Email!='')
                {
                    echo '<br/>Email : '.H_Email;
                } 
            ?>
        </td>
        <td style="width: 250px;vertical-align: top;text-align: right;">
        <p align="right" style="font-size: 22px;" >
            Dr. <?=$opd_master[0]->doc_name ?>
        </p>
        <?=nl2br($opd_master[0]->doc_sign)?>
        <br/><br/>
        <p align="right" style="font-size: 12px;"><b>Appointment Number : 8171010848</b></p>
        </td>
    </tr>
</table>
<hr/>
</htmlpageheader>
<htmlpagefooter name="myFooter">
<p align="left">Review after _______________ days </p>
<p><hr/></p>
<p align="right"><b>Appointment Number : 8171010848</b>  /  Not Valid for Medico legal Purpose  /  Print Time : <?=date('d-m-Y H:i:s')?></p>
</htmlpagefooter>
<?=$content?>
<p align="Left" style="font-size: 12px;" >BP : __________      Pulse Rate : __________      SPO2 : __________      Ht : __________      Wt.: __________      RBS : __________  </p>
