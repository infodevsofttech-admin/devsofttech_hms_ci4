<style>@page {
    background: url('assets/images/Radiant_Letter_head_back.png') no-repeat 0 0;
    background-image-resize: 6;
    margin-top: 5.8cm;
    margin-bottom: 1.2cm;
    margin-left: 0.5cm;
    margin-right: 0.5cm;

    margin-header:1cm;
    margin-footer:1cm;
    header: html_myHeader;
    footer: html_myFooter;

}

.myfixed_vital {
    position: absolute;
    overflow: visible;
    top: 82mm; 
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
        <td style="width: 400px;vertical-align: top;text-align: center;">
            <p style="font-size:46px;font-weight: bold;color:darkblue;" ><?=strtoupper(H_Name)?></p>
            <p style="font-size: 14px;font-weight: bold;color:black;" >OBSTETRICS, GYNECOLOGY AND FERTILITY CENTRE</p>
        </td>
        <td style="width: 80px;vertical-align: top;text-align: right;">
            <img style="width: 70px;vertical-align: top;"  src="assets/images/radiant_ladies.png" />
        </td>
    </tr>
</table>
<table  cellspacing="0"  style="font-size: 12px;width:100%;border-style: inset;" >
    <tr>
        <td style="width: 50%;vertical-align: top;">
            <p style="font-size: 14px;font-weight: bold;color:black;" ><span style="color:darkblue;">Address :</span>Near KVM School, 
            <br/>Mukhani Road, 
            <br/>Haldwani, Uttarakhand</p>
            <p style="font-size: 14px;font-weight: bold;color:black;" >
                <span style="color:darkblue;">Phone No. :</span>+91 8979565771<br/>
                <span style="color:darkblue;">Email :</span>radianthospitalhaldwani@gmail.com<br/>
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
                OPD Timing : 11am to 05pm | Sunday : By Appointment only | Phone No. : 8171010848 | Valid for 7 days
            </td>
            <td style="width: 300px;vertical-align: top;">
                Not Valid for Medico legal Purpose
            </td>
        </tr>
    </table>
</htmlpagefooter>
<?=$content?>
<div class="myfixed_info">
    
</div>

<div class="myfixed_vital">
<table cellspacing="0"  style="font-size: 12px;width:100%;border-style: inset;">
<tr>
    <td style="width: 100px;vertical-align: top;">
        BP :
    </td>
    <td style="width: 100px;vertical-align: top;">
        PR :
    </td>
    <td style="width: 100px;vertical-align: top;">
        Weight :
    </td>
    <td style="width: 100px;vertical-align: top;">
        LMP :
    </td>
</tr>
</table>
    
</div>

