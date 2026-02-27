<style>
.myfixed_pName {
    position: absolute;
    overflow: visible;
    top: 75mm; 
    left: 25mm; 
    width: 50mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
    
}

.myfixed_age_sex {
    position: absolute;
    overflow: visible;
    top: 72mm; 
    left: 100mm; 
    width: 50mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
}

.myfixed_opd_no {
    position: absolute;
    overflow: visible;
    top: 75mm; 
    left: 165mm; 
    width: 50mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
}


.myfixed_date {
    position: absolute;
    overflow: visible;
    top: 75mm; 
    left: 127mm; 
    width: 50mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
}


.myfixed_short_info {
    position: absolute;
    overflow: visible;
    top: 10mm; 
    left: 150mm; 
    width: 80mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
}
</style>

<div class="myfixed_pName">
    <?=$pName?>
</div>
<div class="myfixed_age_sex">
    <?=$age_sex?>
</div>
<div class="myfixed_opd_no">
    <?=$opd_no?>
</div>
<div class="myfixed_date">
    <?=$opd_date?>
</div>
<div class="myfixed_short_info">
    <?=$short_info?>
</div>


