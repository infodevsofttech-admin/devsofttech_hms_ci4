<style>
.myfixed_pName {
    position: absolute;
    overflow: visible;
    top: 46mm; 
    left: 60mm; 
    width: 80mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
    
}

.myfixed_age_sex {
    position: absolute;
    overflow: visible;
    top: 46mm; 
    left: 158mm; 
    width: 18mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
}

.myfixed_opd_no {
    position: absolute;
    overflow: visible;
    top: 58mm; 
    left: 35mm; 
    width: 40mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
}


.myfixed_date {
    position: absolute;
    overflow: visible;
    top: 46mm; 
    left: 185mm; 
    width: 20mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
}


.myfixed_short_info {
    position: absolute;
    overflow: visible;
    top: 55mm; 
    left: 170mm; 
    width: 40mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
    font-size: small;
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
    <b>PID:</b> <?=$uhid_no?><br/>
    <b>Ph.:</b> <?=$phoneno?><br/>
    <b>FEE:</b> <?=$opd_fee?><br/>
    <b>Visit:</b> <?=$total_no_visit?><br/>
    <?=$exp_date?><br/>
    <b>Pr.T:</b> <?=date('d-m-Y H:i')?>
</div>


