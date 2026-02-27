<style>@page {
    background: url('assets/images/Radiant_Letter_head_back_2.png') no-repeat 0 0;
    background-image-resize: 6;
    margin-top: 4.8cm;
    margin-bottom: 0.7cm;
    margin-left: 0.5cm;
    margin-right: 0.5cm;

    margin-header:1cm;
    margin-footer:0.5cm;
    header: html_myHeader;
    footer: html_myFooter;

}

.myfixed_vital {
    position: absolute;
    overflow: visible;
    top: 74mm; 
    left: 10mm; 
    width: 200mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
    
}

.myfixed_info {
    position: absolute;
    overflow: visible;
    top: 62mm; 
    left: 10mm; 
    width: 200mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
    
}

.myfixed_pinfo {
    position: absolute;
    overflow: visible;
    top: 62mm; 
    left: 10mm; 
    width: 200mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
    
}

.myfixed_msg_1 {
    position: absolute;
    overflow: visible;
    top: 70mm; 
    left: 5mm; 
    width: 200mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
    
}

.myfixed_msg_2 {
    position: absolute;
    overflow: visible;
    top: 120mm; 
    left: 5mm; 
    width: 200mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
    
}

.myfixed_msg_3 {
    position: absolute;
    overflow: visible;
    top: 170mm; 
    left: 5mm; 
    width: 200mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
    
}

.myfixed_msg_4 {
    position: absolute;
    overflow: visible;
    top: 220mm; 
    left: 5mm; 
    width: 200mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
    
}
</style>

<htmlpageheader name="myHeader">
<table  cellspacing="0"  style="font-size: 12px;width:100%;border-style: inset;" >
    <tr>
        <td style="width: 80px;vertical-align: top;">
            <img style="width: 80px;vertical-align: top;"  src="assets/images/<?=H_logo?>" />
        </td>
        <td style="width: 400px;vertical-align: bottom;text-align: Left;">
            <span style="font-size:48px;font-weight: bold;color:darkblue;margin-bottom:0" ><?=strtoupper(H_Name)?></span><br/>
            <span style="font-size: 16px;font-weight: bold;color:black;" >NEURO-BRAIN & SPINE CENTRE</span>
        </td>
        <td style="width: 80px;vertical-align: top;text-align: right;">
            <img style="width: 70px;vertical-align: top;"  src="assets/images/radiant_neuro.png" />
        </td>
    </tr>
</table>
<table  cellspacing="0"  style="font-size: 10px;width:100%;border-style: inset;" >
    <tr>
        <td style="width: 50%;vertical-align: top;">
            <p style="font-size: 14px;font-weight: bold;color:black;" ><span style="color:darkblue;"><br>Address :</span>Near KVM School, 
             Mukhani Road, Haldwani
            </p>
            <p style="font-size: 14px;font-weight: bold;color:black;" >
                <span style="color:darkblue;">Phone No. :</span>7817844055, 9068429666, 05946-365929<br/>
            </p>
        </td>
        <td style="width: 50%;vertical-align: top;text-align: right;">
            <p  style="font-size: 28px;font-weight: bold;color:darkblue;" >
            Dr. <?=$opd_master[0]->doc_name ?>
            </p>
            <?=nl2br($opd_master[0]->doc_sign)?>
        </td>
    </tr>
</table>
</htmlpageheader>
<htmlpagefooter name="myFooter">
    <table cellspacing="0"  style="font-size: 12px;width:100%;border-style: inset;">
        <tr>
            <td style="width: 600px;vertical-align: top;">
                OPD Timing : 10AM to 5PM | Sunday OPD by Appointment only
            </td>
            <td style="width: 300px;vertical-align: top;text-align:right;">
            Not Valid for Medico legal Purpose
            </td>
        </tr>
    </table>
</htmlpagefooter>
<?=$content_2?>
<hr/>
<div class="myfixed_info">
    
</div>

<div class="myfixed_vital">

    
</div>

