<style>@page {

margin-top: 3.2cm;
margin-bottom: 1.2cm;
margin-left: 0.5cm;
margin-right: 0.5cm;

margin-header:0.5cm;
margin-footer:0.5cm;
header: html_myHeader;
footer: html_myFooter;
}
</style>

<htmlpageheader name="myHeader">

<table  cellspacing="0"   style="font-size: 10px;width:100%;border-style: inset;border-bottom-width: 1px;border-color: green;" >
    <tr>
        <td style="width: 200px;vertical-align: top;text-align: left;">
        <p align="right" style="font-size: 22px;" >
            Dr. <?=$opd_master[0]->doc_name ?>
        </p>
        <?=nl2br($opd_master[0]->doc_sign)?>
        </td>
        <td style="vertical-align: top; text-align:center;">
            <img style="width: 50px;vertical-align: top;"  src="/assets/images/<?=H_logo?>" />
            <p align="center" ><span style="font-size: 22px;">SINGHAL CLINIC</span>
            <br/><span style="font-size: 12px;">ENDO-NEURO CENTER</span>
            </p>
        </td>
        <td style="width: 200px;vertical-align: top;text-align: right;">
            <p>
                <b>Formerly at:</b><br/>
                GB Pant Hospital, Delhi<br/>
                IHBAS Hospital, Delhi<br/>
                AIIMS, New Delhi
            </p>
        </td>
    </tr>
</table>
</htmlpageheader>
<htmlpagefooter name="myFooter">
    <hr style="padding:0px;color:green;"  />
    <h3 style="text-align: center;color:royalblue;">Singhal Clinic & Endo-Neuro Center</h3>
    <p align="center" style="font-size: 12px;">Cheema Chauraha, Behind Spectrum Mall, Ramnagar Road, Kashipur - 244713 <br/>
        <span style="font-size: 10px;">Contact : 05947-274865, 7017369216  / Time : 6 P.M. - 8 P.M. Monday to Friday</span><br/>
        <span style="font-size: 9px;color:red;">website : www.singhalclinickashipur.in</span>
    </p>
</htmlpagefooter>
<?=$content?>
