<style>
.myfixed_pName {
    position: absolute;
    overflow: visible;
    top: 71mm; 
    left: 70mm; 
    width: 75mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
    
}

.myfixed_age_sex {
    position: absolute;
    overflow: visible;
    top: 71mm; 
    left: 152mm; 
    width: 20mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
}


.myfixed_date {
    position: absolute;
    overflow: visible;
    top: 71mm; 
    left: 182mm; 
    width: 40mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
}

.myfixed_opd_no {
    position: absolute;
    overflow: visible;
    top: 99mm; 
    left: 60mm; 
    width: 150mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
}

.myfixed_short_info {
    position: absolute;
    overflow: visible;
    top: 81mm; 
    left: 165mm; 
    width: 65mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
}

.myfixed_add_info {
    position: absolute;
    overflow: visible;
    top: 81mm; 
    left: 70mm; 
    width: 90mm;   /* you must specify a width */
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

<div class="myfixed_date">
    <?=$opd_date?>
</div>
<div class="myfixed_short_info">
    Sr.No. : <?=$opd_sr_no?> <br/> OPD Fee : <?=$opd_fee?> <br /> <?=$exp_date?>
    <br />OPD ID : <?=$opd_no?> <br /> UHID : <?=$uhid_no?>
</div>
<div class="myfixed_add_info">
    <?=$p_address?>
</div>
