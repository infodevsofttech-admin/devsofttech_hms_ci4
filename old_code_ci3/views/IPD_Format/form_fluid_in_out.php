<style>@page {
        margin-top: 2.5cm;
        margin-bottom: 1.2cm;
        margin-left: 1cm;
        margin-right: 0.5cm;
        
        margin-header:0.5cm;
        margin-footer:0.5cm;
        header: html_myHeader;
        footer: html_myFooter;
}
</style>

<htmlpageheader name="myHeader">
    <table style="font-size: 14px;" cellpadding="0" width="100%">
	<tr>
		<td style="vertical-align: top;" width="15%">
            <img style="width: 35px;vertical-align: top;"  src="assets/images/<?=H_logo?>" />
        </td>
        <td style="vertical-align: top;">
            <p align="center" style="font-size: 15px;" ><?=H_Name?></p>
            <p align="center" style="font-size: 10px" ><?=H_address_1?>, Uttarakhand<br>
        </td>
        <td style="vertical-align: top;" align="right" width="20%">
        <?php
        $bar_content=$ipd_info[0]->ipd_code;
        $bar_content=$ipd_info[0]->id;
        ?>
        <barcode code="<?=$bar_content?>" size="0.6" type="C128A" error="M" class="barcode" />
        </td>
	</tr>
</table>
<hr/>
</htmlpageheader>
<htmlpagefooter name="myFooter">
<table width="100%" style="font-size: 10px;">
<tr>
    <td width="15%" ></td>
    <td width="85%" style="text-align: right;">IPD No.:<?=$ipd_info[0]->ipd_code?> / UHID:<?=$person_info[0]->p_code?> /Name : <?=strtoupper($person_info[0]->p_fname)?> / Gender : <?=$person_info[0]->xgender?> / Age : <?=$person_info[0]->age?> </td>
</tr>
</table>
</htmlpagefooter>
<h3 align="center">ORAL / Enteral Intake & Output Chart</h3>
Name : <?=strtoupper($person_info[0]->p_fname)?>&nbsp;&nbsp;&nbsp;&nbsp;/ IPD No.:<?=$ipd_info[0]->ipd_code?> &nbsp;&nbsp;&nbsp;&nbsp; / UHID:<?=$person_info[0]->p_code?> &nbsp;&nbsp;&nbsp;&nbsp;/ Gender : <?=$person_info[0]->xgender?> &nbsp;&nbsp;&nbsp;&nbsp;/ Age : <?=$person_info[0]->age?>
<HR />
<table border="1" cellpadding="5" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td style="text-align:center; width:10px">SR.No.</td>
			<td style="text-align:center; width:30px">Date</td>
			<td style="text-align:center; width:30px">Time</td>
			<td style="text-align:center; width:150px">Description</td>
			<td colspan="2" style="text-align:center; width:60px">Vol.</td>
			<td style="text-align:center; width:40px">Total</td>
			<td style="text-align:center; width:30px">Urine</td>
			<td style="text-align:center; width:30px">Stool</td>
			<td colspan="3" style="text-align:center; width:90px">Other </td>
            <td style="text-align:center; width:40px">Total </td>
		</tr>
        <?php 
           for($i=1;$i<31;$i++){
        ?>
		<tr>
			<td style="text-align:center; width:10px"><?=$i?></td>
			<td style="width:30px">&nbsp;</td>
			<td style="width:30px">&nbsp;</td>
			<td style="width:30px">&nbsp;</td>
			<td style="width:30px">&nbsp;</td>
			<td style="width:30px">&nbsp;</td>
			<td style="width:40px">&nbsp;</td>
			<td style="width:30px">&nbsp;</td>
			<td style="width:30px">&nbsp;</td>
			<td style="width:30px">&nbsp;</td>
            <td style="width:30px">&nbsp;</td>
            <td style="width:30px">&nbsp;</td>
            <td style="width:40px">&nbsp;</td>
		</tr>
        <?php
        }
        ?>
	</tbody>
</table>

