<style>@page {
                margin-top: 5.2cm;
                margin-bottom: 3.5cm;
                margin-left: 1cm;
                margin-right: 0.5cm;
                
                margin-header:0.5cm;
                margin-footer:1.5cm;
                header: html_myHeader;
                footer: html_myFooter; 

                background: url("/assets/images/logo-watermark.png") no-repeat 0 0;
                    
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

            @page :first {
                margin-top: 4.5cm;
                header: firstpage;
            }
            </style>
<htmlpageheader name="myHeader">
<?php if($print_on_type=='1'){  ?>
        <table style="font-size: 12px;color:DodgerBlue;" cellpadding="1">
        <tr>
            <td style="width: 60%;vertical-align: top;text-align:center;">
            <img src="/assets/images/logo-B.png" width="300px" />    
                </td>
                <td style="width: 40%;">
                <p >
                    Registered Under Govt. Of India/Uttarakhand<br/>
                    REFERENCE LAB<br/>
                    <b>APOLLO DIAGNOSTICS & PATH LAB</b><br/>
                    Clinical Establishment No.0506600263/2019<br/>
                    Patel Chowk,Shiv Pharmacy, Near S.S.Jeena<br/>
                    Base Hospital,Haldwani-263139<br/>
                    E-Mail: '. H_Email.'<br/>
                    Website : www.apollodiagnosticshld.com<br/>
                    Customer Care : +91-9690556555
                </p>
                </td>
        </tr>
    </table>
<?php } ?>
<table width="100%" style="font-size: 14px;">
<tr>
    <td width="85%" ><b><?=$report_head?></b></td>
    <td width="15%" style="text-align: right;">Page : {PAGENO}/{nbpg}</td>
</tr>
</table>
<hr/>
</htmlpageheader>
<htmlpageheader name="firstpage">
<?php if($print_on_type=='1'){  ?>
        <table style="font-size: 12px;color:DodgerBlue;" cellpadding="1">
        <tr>
            <td style="width: 60%;vertical-align: top;text-align:center;">
            <img src="/assets/images/logo-B.png" width="300px" />    
                </td>
                <td style="width: 40%;">
                <p >
                    Registered Under Govt. Of India/Uttarakhand<br/>
                    REFERENCE LAB<br/>
                    <b>APOLLO DIAGNOSTICS & PATH LAB</b><br/>
                    Clinical Establishment No.0506600263/2019<br/>
                    Patel Chowk,Shiv Pharmacy, Near S.S.Jeena<br/>
                    Base Hospital,Haldwani-263139<br/>
                    E-Mail: '. H_Email.'<br/>
                    Website : www.apollodiagnosticshld.com<br/>
                    Customer Care : +91-9690556555
                </p>
                </td>
        </tr>
    </table>
    <hr/>
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

<sethtmlpageheader name="firstpage" value="on" show-this-page="1" />
<sethtmlpageheader name="myHeader" value="on" />
<?=$complete_report?>
<p align="center">###################### END OF REPORT ######################</p>
<table border="0" style="width:100%;font-size:14px;">
        <tr>
            <td>
                <!-- <img width="100px" src="'.$sign_image_file.'"  />  -->
            </td>
            <td>

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
        <td >
            <img width="100px" src="/assets/images/Signature.jpeg"  />
            <br/>
            <b><?=$docname?></b><br/>
            <?=$docedu?>
        </td>
        <td style="text-align:right">
            <b><?=nl2br($tech_name)?></b>
        </td>
    </tr>
</table>
<hr/>
<table border="1" cellspacing="0" cellpadding="2" width="100%" style="font-size: 10px;boder:0.2px;">
    <tr>
        <td >
        This report is not for medico legal purpose. If clinical correlation is not established,kindly repeat the test at no additional cost within seven days.
        </td>
    </tr>
    <tr>
        <td >
        <p>Facilities: Pathology,Bedside Sample Collection,Health Check-ups,Allergy Testing, Biopsy ,DNA Testing,Peternity DNA Testing. Maternity DNA Testing,Forensic Testing, Nutritional & Genetic Testing.Optical, Dental X-Ray, ECG , EEG.</p>
        <p align="center">Online Booking Facilities for Diagnostics / Online Report Viewing */ 365 Days Open / Opening Hours:12*7</p>
        </td>
    </tr>
</table>

