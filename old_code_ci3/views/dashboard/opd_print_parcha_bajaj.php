<style>@page {

margin-top: 5.8cm;
margin-bottom: 1.2cm;
margin-left: 0.5cm;
margin-right: 0.5cm;

margin-header:0.5cm;
margin-footer:1.0cm;
header: html_myHeader;
footer: html_myFooter;
}

.myfixed_info {
    position: absolute;
    overflow: visible;
    top: 40mm; 
    left: 80mm; 
    width: 130mm;   /* you must specify a width */
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
    <?=$content_3?>
</div>
