<style>@page {
margin-top: 5.2cm;
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
    <h1 style="color:firebrick;text-align:center;">Shri Ram Hospital <br />श्री राम अस्पताल</h1>
    <table  cellspacing="0"  style="font-size: 10px;width:100%;border-style: inset;border-bottom-width: 1px;" >
    <tr>
        <td style="width: 200px;vertical-align: top;text-align: left;">
        <p align="right" style="font-size: 16px;color:firebrick;" >
            Dr. Krishna Sah
        </p>
        M.B.B.S, M.S. (Obstetrics & Gynaecology),<br/>
        Consultant<br/>
        Regd. No. UK-1261
        </td>
        <td style="vertical-align: top; text-align:center;">
            <img style="width: 50px;vertical-align: top;"  src="/assets/images/<?=H_logo?>" />
        </td>
        <td style="width: 200px;vertical-align: top;text-align: right;">
            <p align="right" style="font-size: 16px;color:firebrick;" >
            Dr. Sana Anshari
            </p>
            M.B.B.S, D.G.O.<br/>
            Regd. No. UK-6583
        </td>
    </tr>
</table>
</htmlpageheader>
<htmlpagefooter name="myFooter">
    <hr/>
    <p align="center" style="font-size: 14px;">Civil Lines, Tikonia, Haldwani (Nainital) U.K. <br/>
        <span style="font-size: 12px;">Contact : 9756389281 (M), 05946-282751,224422, Hospital - 7088020108  
        <br/> Morning Time : 10 A.M. - 2 P.M. And Evening Time : 5 P.M. - 8 P.M. (Sunday evening closed)</span><br/>
        <br/>
        <span style="font-size: 12px;font-style:oblique">A3 Reception No. +91 8755904991</span><br/>
        <span style="font-size: 12px;">Not For Medico-legal Purpose</span><br/>
        
    </p>
</htmlpagefooter>
<?=$content?>
