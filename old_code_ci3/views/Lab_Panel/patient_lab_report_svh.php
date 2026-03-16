<style>
    @page {
                    margin-top: 8.5cm;
                    margin-bottom: 2.5cm;
                    margin-left: 1cm;
                    margin-right: 0.5cm;
                    
                    margin-header:6.0cm;
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
            <td style="text-align:Left;"><?=$report_head?></td>
            <td style="text-align:Right;">Page : {PAGENO}/{nbpg}</td>
        </tr>
    </table>
</htmlpagefooter>

<?=$complete_report?>

<p align="center">###################### END OF REPORT ######################</p>
<br />
<br />
<br />
<table border="0" style="width:100%;font-size:14px;">
    <tr>
        <td>
            <!-- <img width="100px" src="'.$sign_image_file.'"  />  -->
        </td>
        <td style="text-align:right">
		<img src="/assets/images/drPreetiSingh.jpg" width="100px" />
        </td>
    </tr>
    <tr>
        <td >
            <b>Lab Technologist</b>
        </td>

        <td style="text-align:right">
		
            <b>Dr. Preeti Singh</b><br/>
            M.B.B.S. , MD
        </td>
    </tr>
    
</table>

