<style>@page {

margin-top: 3.0cm;
margin-bottom: 2.2cm;
margin-left: 0.5cm;
margin-right: 0.5cm;

margin-header:0.5cm;
margin-footer:0.5cm;
header: html_myHeader;
footer: html_myFooter;
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
<p align="right">Print Time : <?=date('d-m-Y H:i:s')?></p>
</htmlpagefooter>
<?=$content?>
