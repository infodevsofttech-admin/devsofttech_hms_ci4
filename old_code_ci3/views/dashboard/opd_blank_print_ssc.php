<style>@page {

margin-top: 3.8cm;
margin-bottom: 1.2cm;
margin-left: 0.5cm;
margin-right: 1cm;

margin-header:0.5cm;
margin-footer:0.5cm;
header: html_myHeader;
footer: html_myFooter;
}
</style>

<htmlpageheader name="myHeader">
    <table  cellspacing="0"  style="font-size: 12px;width:100%;border-style: inset;" >
    <tr>
        <td style="width: 110px;vertical-align: top;">
            <img style="width: 100px;vertical-align: top;"  src="assets/images/<?=H_logo?>" />
        </td>
        <td style="width: 400px;vertical-align: top;">
            <p align="center" style="font-size: 25px;color:darkblue;" ><b><?=H_Name?></b></p>
            <p align="center" style="font-size: 12px" ><?=H_address_1?><br/><?=H_address_2?><br>
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
        <p align="right" style="font-size: 24px;color:darkblue;" >
           <b>Dr. <?=$opd_master[0]->doc_name ?></b>
        </p>
        <?=nl2br($opd_master[0]->doc_sign)?>
        </td>
    </tr>
</table>
<hr style="margin: 0px;" />
</htmlpageheader>
<htmlpagefooter name="myFooter">
</htmlpagefooter>
<?=$content?>

