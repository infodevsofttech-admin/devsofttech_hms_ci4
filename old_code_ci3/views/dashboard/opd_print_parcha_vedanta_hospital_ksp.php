<style>@page {

margin-top: 5.5cm;
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
<p align="center"></p>
</htmlpageheader>
<htmlpagefooter name="myFooter">
<p align="center">Print Time : <?=date('d-m-Y H:i:s')?></p>
</htmlpagefooter>
<table width="100%" border="0" style="font-size:10pt;">
<tr>
	<td width="33.3%" VALIGN="top">
		Name : <strong><?=strtoupper($pName)?></strong><br>
		<?=$pRelative?><br>
		Gender/Age : <b><?=$age_sex?> </b><br>
		Mob :<?=$phoneno?>'<br/>
		Address :<?=$p_address?>'
	</td>
	<td width="33.3%" VALIGN="top">
		UHID : <?=$uhid_no?><br>
		Sr No.: <b><?=$opd_sr_no ?></b> <br/>
		OPD No.: <B><?=$opd_no?></B><br>
		<br><b>Date: <?=$opd_date?></b><br><?=$exp_date?>

	</td>
	<td width="33.3%" VALIGN="top">
		<b>DEPARTMENT :</b><br>
		<?=$SpecName?>
		<br>
		<?=$opd_fee_desc?>
		<br><b>No. of Visit</b> : <?=$total_no_visit?>
		<?=$last_opdvisit_date?>
		<br>Book Time : <?=$str_opd_book_date?>
	</td>
</tr>
</table>