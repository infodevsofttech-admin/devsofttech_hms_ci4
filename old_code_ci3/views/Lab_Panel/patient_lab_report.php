<style> @page {
                margin-top: 9cm;
		margin-bottom: 5.4cm;
		margin-left: 0.7cm;
		margin-right: 0.7cm;

		margin-header:5.5cm;
		margin-footer:2.0cm;
		header: html_myHeader;
		footer: html_myFooter;
                    
                    
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
<table width="100%" style="font-size: 10px;">
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
                <img src="/assets/images/drPreetiSingh.jpg" width="100px" />
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
        <tr>
            <td style="text-align: left;">
                Page : {PAGENO}/{nbpg}
            </td>
            <td style="text-align: right;">
                <?=$report_head?>
            </td>
        </tr>
    </table>
</htmlpagefooter>
<?=$complete_report?>

<p align="center">###################### END OF REPORT ######################</p>
<sethtmlpagefooter name="LastPageFooter" value="1" />

