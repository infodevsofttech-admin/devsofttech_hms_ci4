<style>@page {

margin-top: 4.5cm;
margin-bottom: 4.5cm;
margin-left: 0.7cm;
margin-right: 0.7cm;

margin-header:0.1cm;
margin-footer:1.0cm;
header: html_myHeader;
footer: html_myFooter;
}

p{
        font-size: 12.5pt;
        padding: 1px;
        margin: 5px;
    }

    hr{
        padding: 1px;
        margin: 5px;
    }

    h3{
        font-size: 14pt;
        padding: 1px;
        margin: 5px;
    }

</style>

<htmlpageheader name="myHeader">
<?php if($print_on_type=='1'){  ?>
<table  cellspacing="0"  style="font-size: 14px;width:100%;border-style: inset;" >
    <tr>
        <td style="width: 20%;vertical-align: top;">
            <img style="width: 100px;vertical-align: top;"  src="/assets/images/<?=H_logo?>" />
        </td>
        <td style="width: 70%;vertical-align: top;">
            <p align="center" style="font-size: 26px;" ><?=H_Name?></p>
            <p align="center" style="font-size: 14px" ><?=H_address_1?>, Uttarakhand<br>
            <?php 
                if(H_phone_No!='')
                {
                    echo 'Phone: '.H_phone_No;
                } 
            ?>
        </td>
        <td style="width: 10%;vertical-align: top;text-align: right;">
            <barcode code="<?=$report_head?>" size="0.8" type="QR" error="M" class="barcode" />
        </td>
    </tr>
</table>
<?php } ?>
</htmlpageheader>
<htmlpagefooter name="myFooter">
<table width="100%" style="font-size: 12px;">
<tr>
    <td width="15%" >Page : {PAGENO}/{nbpg}</td>
    <td width="85%" style="text-align: right;"><?=$report_head?></td>
</tr>
</table>
</htmlpagefooter>
<htmlpagefooter name="LastPageFooter">
    <table border="0" style="width:100%;font-size:14px;">
        <tr>
            <td>
                <!-- <img width="100px" src="'.$sign_image_file.'"  />  -->
            </td>
            <td>

            </td>
        </tr>
        <tr>
            <td >
                <b><?=nl2br($tech_name)?></b>
            </td>
            <td style="text-align:right">
                <b><?=$docname?></b><br/>
                <?=$docedu?>
            </td>
        </tr>
        <tr>
            <td style="text-align: left;">
                <br>
                <br>
            </td>
            <td style="text-align: right;">
                <br>
            </td>
        </tr>
    </table>
<p style="font-size: 10pt;"><b>Note :</b> Impression is a professional opinion. All modern machines/ Procedures have their limitations. If there is variance clinically this examination may be repeated of re-evaluated by other investigations. <b>Not valid for medico-legal purpose.</b><br/>
Any typing & technical error should be corrected within seven days. No compensation liability stands.</p>
</htmlpagefooter>
<?=$complete_report?>
<p align="center">###################### END OF REPORT ######################</p>
<sethtmlpagefooter name="LastPageFooter" value="1" />

