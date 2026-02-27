<style>@page {

margin-top: 3.5cm;
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
<h1 align="center" >DAY CARE DISCHARGE</h1>
</htmlpageheader>
<htmlpagefooter name="myFooter">

</htmlpagefooter>
<?php
    $old_uhid='';
    if($patient_master[0]->udai<>"")
    {
        $old_uhid= ' /'.$patient_master[0]->udai;
    }
?>
<hr/>
    <table width="100%" >
        <tr>
                <td valign="TOP" width="50%">
                        UHID : <?=$patient_master[0]->p_code?>  <?=$old_uhid?><br>
                        Name : <strong><?=$patient_master[0]->title?> <?=strtoupper($patient_master[0]->p_fname)?></strong><br>
                        <?=$patient_master[0]->p_relative?> <?=strtoupper($patient_master[0]->p_rname)?><br>
                        Sex : <b><?=$patient_master[0]->xgender?> <?=$patient_master[0]->age?></b><br>
                </td>
                <td valign="TOP" width="50%">
                        <b>Date: <?=$opd_master[0]->str_apointment_date ?></b><br><br>
                        Admit Time: <B>____________________</B><br><br>
                        Discharge Time: ___________________<br>
                </td>
        </tr>
    </table> 
<hr/>
<br/>
<h3>Hospital Course</h3>
<br/>
<br/>
<br/>
<br/>
<br/>
<br/>
<br/>
<br/>
<br/>
<br/>
<h3>Treatment Advice</h3>
<br/>
<br/>
<br/>
<br/>
<br/>
<br/>
<br/>
<br/>
