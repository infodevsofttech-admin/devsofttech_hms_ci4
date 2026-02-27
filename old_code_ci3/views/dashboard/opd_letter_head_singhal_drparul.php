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

.barcode {
	padding: 0;
	margin: 0;
	vertical-align: top;
	color: #000000;
}
</style>

<htmlpageheader name="myHeader">
<table  cellspacing="0"  style="font-size: 10px;width:100%;border-style: inset;border-bottom-width: 1px;border-color: green;" >
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
                MAMC & LNJP Hospital, Delhi<br/>
                UCMS & GTB Hospital, Delhi<br/>
                AIIMS New Delhi<br/>
                AIIMS Jodhpur
            </p>
        </td>
    </tr>
</table>
</htmlpageheader>
<htmlpagefooter name="myFooter">
    <hr style="color:green;" />
    <h3 style="text-align: center;color:royalblue;">Singhal Clinic & Endo-Neuro Center</h3>
    <p align="center" style="font-size: 14px;">Cheema Chauraha, Behind Spectrum Mall, Ramnagar Road, Kashipur - 244713 <br/>
        <span style="font-size: 12px;">Contact : 9045424198,05947-274865, 7017369216  </span><br/>
        <span style="font-size: 12px;">Monday to Friday : 11 A.M. - 3 P.M. And  6 P.M. - 8 P.M.  </span><br/>
        <span style="font-size: 12px;">Saturday : 11 A.M. - 3 P.M. </span><br/>
        <span style="font-size: 12px;">Saturday evening & Sunday closed</span><br/>
        <span style="font-size: 9px;color:red;">website : www.singhalclinickashipur.in</span>
    </p>
</htmlpagefooter>
<?=$content?>
