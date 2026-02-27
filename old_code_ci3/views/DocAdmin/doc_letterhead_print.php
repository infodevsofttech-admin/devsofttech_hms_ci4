<style>@page {
margin-top: 5.5cm;
margin-bottom: 1.2cm;
margin-left: 1cm;
margin-right: 1cm;

margin-header:0.5cm;
margin-footer:1cm;
header: html_myHeader;
footer: html_myFooter;
}

body, td, div,p { font-family: freesans; font-size:13;}
p {line-height: 1.6;}

</style>

<htmlpageheader name="myHeader">
<?php if($print_on_type==1){  ?>
<table  cellspacing="0"  style="font-size: 10px;width:100%;border-style: inset;" >
    <tr>
        <td style="width: 20%;vertical-align: top;">
            <img style="width: 100px;vertical-align: top;"  src="assets/images/<?=H_logo?>" />
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
            <barcode code="<?=$bar_content?>" size="0.8" type="QR" error="M" class="barcode" />
        </td>
    </tr>
</table>
<?php } ?>
</htmlpageheader>
<htmlpagefooter name="myFooter">
<table width="100%" style="font-size: 10px;">
<tr>
    <td width="15%" >Page : {PAGENO}/{nbpg}</td>
    <td width="85%" style="text-align: right;"><?=$bar_content?></td>
</tr>
</table>
</htmlpagefooter>

<?=$content?>