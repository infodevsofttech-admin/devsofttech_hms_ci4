<style>@page {

margin-top: 5.5cm;
margin-bottom: 1.2cm;
margin-left: 0.5cm;
margin-right: 0.5cm;

margin-header:0.5cm;
margin-footer:1.5cm;
header: html_myHeader;
footer: html_myFooter;
}

.myfixed_info {
    position: absolute;
    overflow: visible;
    top: 40mm; 
    left: 10mm; 
    width: 200mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
}



</style>

<htmlpageheader name="myHeader">
<p align="center"></p>
</htmlpageheader>
<htmlpagefooter name="myFooter">
<p align="center"></p>
</htmlpagefooter>
<div class="myfixed_info">
    <table>
        <tr>
            <td style="width: 250px;vertical-align:top;">
                Name : <span style="font-size:16px;"><b><?=$pName?></b></span><br/>
                <?=$pRelative?><br/>
                Gender/Age : <b><?=$age_sex?></b><br/>
                Mob. : <?=$phoneno?><br/>
                Add. : <?=$p_address?>
            </td>
            <td style="width: 250px;vertical-align:top;">
                Sr No : <b><?=$opd_sr_no?></b><br/>
                UHID : <b><?=$uhid_no?></b><br/>
                OPD No. : <?=$opd_no?><br/>
                Date : <?=$opd_date?><br/>
                <?=$exp_date?><br/>
                OPD Fee : <?=$opd_fee?>
            </td>
            <td style="width: 300px;vertical-align:top;">
                <span style="font-size:16px;"><b>Dr. Ajay Bajaj / Dr Vibhu Shankar</b></span><br/>
                MBBS,MS,MCh.(Neuro Surgery)<br/> 
                Senior Consultant Neuro Surgery<br/><br/>                
            </td>
        </tr>
    </table>
</div>
