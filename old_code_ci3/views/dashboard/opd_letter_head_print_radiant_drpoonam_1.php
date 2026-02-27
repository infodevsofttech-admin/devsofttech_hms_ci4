<style>@page {
    background: url('assets/images/DrPoonam.jpg') no-repeat 0 0;
    background-image-resize: 6;

margin-top: 5.8cm;
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
</htmlpageheader>
<htmlpagefooter name="myFooter">
<p align="right" style="color:white;">Not Valid for Medico legal Purpose  /  Print Time : <?=date('d-m-Y H:i:s')?></p>
</htmlpagefooter>
<?=$content?>
