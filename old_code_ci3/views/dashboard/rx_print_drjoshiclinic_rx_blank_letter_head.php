<style>
    @page {
        margin-top: 4.2cm;
        margin-bottom: 1.2cm;
        margin-left: 0.5cm;
        margin-right: 0.5cm;

        margin-header: 0.5cm;
        margin-footer: 5cm;
        header: html_myHeader;
        footer: html_myFooter;
    }

    .RxPlace {
        position: absolute;
        overflow: visible;
        top: 73mm;
        left: 70mm;
        width: 135mm;
        /* you must specify a width */
        margin: 0;
        padding: 0;

    }

    table p {
        font-size: 12px;
    }

    th,
    td {

        text-align: left;
        font-size: 12px;
    }
</style>

<htmlpageheader name="myHeader">
    <p align="center"></p>
</htmlpageheader>
<htmlpagefooter name="myFooter">
    <p align="center">Print Time : <?= date('d-m-Y H:i:s') ?></p>
</htmlpagefooter>
<div class="head_part">
    <table width="100%" border="0" style="font-size:10pt;">
        <tr>
            <td width="33.3%" VALIGN="top">
                Name : <strong><?= strtoupper($pName) ?></strong><br>
                <?= $pRelative ?><br>
                Gender/Age : <b><?= $age_sex ?> </b><br>
                Mob :<?= $phoneno ?>'<br />
                Address :<?= $p_address ?>'
            </td>
            <td width="33.3%" VALIGN="top">
                UHID : <?= $uhid_no ?><br>
                Sr No.: <b><?= $opd_sr_no ?></b> <br />
                OPD No.: <B><?= $opd_no ?></B><br>
                <br><b>Date: <?= $opd_date ?></b><br><?= $exp_date ?>
            </td>
            <td width="33.3%" VALIGN="top">
                <b>DEPARTMENT :</b><br>
                <?= $SpecName ?>
                <br>
                <?= $opd_fee_desc ?>
                <br><b>No. of Visit</b> : <?= $total_no_visit ?>
                <?= $last_opdvisit_date ?>
                <br>Book Time : <?= $str_opd_book_date ?>
            </td>
        </tr>
    </table>
</div>
<div class="RxPlace">
    <?php
    if(strlen($vital_content)>0){
        echo $vital_content;
        echo '<br/>';
    }
    
    if(strlen($Complaint)>0){
        echo $Complaint;
        echo '<br/>';
    }

    if(strlen($Finding_Examinations)>0){
        echo $Finding_Examinations;
        echo '<br/>';
    }

    if(strlen($Provisional_diagnosis)>0){
        echo $Provisional_diagnosis;
        echo '<br/>';
    }
                
    if(strlen($diagnosis)>0){
        echo $diagnosis;
        echo '<br/>';
    }
    
    echo $medical;
    echo '<br/>';
    
    if(strlen($investigation)>0){
        echo $investigation;
        echo '<br/>';
    }

    

    if(strlen($Prescriber_Remarks)>0){
        echo $Prescriber_Remarks;
        echo '<br/>';
    }
    
    if(strlen($advice)>0){
        echo $advice;
        echo '<br/>';
    }

    if(strlen($next_visit)>0){
        echo $next_visit;
        echo '<br/>';
    }

    if(strlen($refer_to)>0){
        echo $refer_to;
        echo '<br/>';
    }
    echo '<br/>';
    
    echo $doctor;
    ?>
</div>