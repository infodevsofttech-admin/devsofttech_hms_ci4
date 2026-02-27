<style>
    @page {
                    margin-top: 7cm;
                    margin-bottom: 4cm;
                    margin-left: 1cm;
                    margin-right: 0.5cm;
                    
                    margin-header:4cm;
                    margin-footer:1.5cm;
                    header: html_myHeader;
                    footer: html_myFooter; 

                    background: url("'.K_PATH_IMAGES.'/logo-watermark.png") no-repeat 0 0;
                    
            }

            body { 
                font-family: times; 
                font-size: 10pt;
            }

            h1{
                font-size:12pt;
            }

            h3{
                font-size:10pt;
            }
</style>
<htmlpageheader name="myHeader">
    <?=$report_header?>
</htmlpageheader>
<htmlpagefooter name="myFooter">
    <table border="0" cellspacing="0" cellpadding="2" width="100%" style="font-size: 12px;boder:0.2px;">
        <tr>
            <td style="text-align:Left;"></td>
            <td style="text-align:Right;">Page : {PAGENO}/{nbpg}</td>
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
                <?=nl2br($docedu)?>
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
</htmlpagefooter>
<?=$complete_report?>

<p align="center">###################### END OF REPORT ######################</p>
<sethtmlpagefooter name="LastPageFooter" value="1" />
