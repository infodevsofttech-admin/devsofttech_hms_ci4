<style>
.myfixed_pName {
    position: absolute;
    overflow: visible;
    top: 55mm; 
    left: 30mm; 
    width: 250mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
    
}

.myfixed_age_sex {
    position: absolute;
    overflow: visible;
    top: 55mm; 
    left: 130mm; 
    width: 50mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
}

.myfixed_opd_no {
    position: absolute;
    overflow: visible;
    top: 72mm; 
    left: 10mm; 
    width: 300mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
}


.myfixed_date {
    position: absolute;
    overflow: visible;
    top: 55mm; 
    left: 178mm; 
    width: 50mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
}


.myfixed_short_info {
    position: absolute;
    overflow: visible;
    top: 66mm; 
    left: 125mm; 
    width: 80mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
}

.myfixed_address {
    position: absolute;
    overflow: visible;
    top: 66mm; 
    left: 34mm; 
    width: 80mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
}

body {
    font-size: 12pt;
}
</style>

<div class="myfixed_pName">
    <?=$pName?>
</div>
<div class="myfixed_age_sex">
    <?=$age_sex?>
</div>
<div class="myfixed_opd_no">
    OPD No : <?=$opd_no?> / UHID : <?=$uhid_no?> / OPD Fee : <?=$opd_fee?>
</div>
<div class="myfixed_date">
    <?=$opd_date?>
</div>
<div class="myfixed_address">
    <?=$p_address?>
</div>


