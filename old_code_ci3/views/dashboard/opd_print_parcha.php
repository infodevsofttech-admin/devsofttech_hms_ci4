<style>@page {

margin-top: 6cm;
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
<?=$content?>