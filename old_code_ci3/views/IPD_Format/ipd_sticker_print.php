<style>
    .content_1 {
        position: absolute;
        overflow: visible;
        top: 15mm;
        left: 10mm;
        width: 100mm;
        height: 48mm;
        margin-top: auto;
        margin-bottom: auto;
        margin-left: auto;
        margin-right: auto;
    }

    .content_2 {
        position: absolute;
        overflow: visible;
        top: 65mm;
        left: 10mm;
        width: 100mm;
        height: 48mm;
        margin-top: auto;
        margin-bottom: auto;
        margin-left: auto;
        margin-right: auto;
    }

    .content_3 {
        position: absolute;
        overflow: visible;
        top: 115mm;
        left: 10mm;
        width: 100mm;
        height: 48mm;
        margin-top: auto;
        margin-bottom: auto;
        margin-left: auto;
        margin-right: auto;
    }


    .content_4 {
        position: absolute;
        overflow: visible;
        top: 165mm;
        left: 10mm;
        width: 100mm;
        height: 48mm;
        margin-top: auto;
        margin-bottom: auto;
        margin-left: auto;
        margin-right: auto;
    }


    .content_5 {
        position: absolute;
        overflow: visible;
        top: 215mm;
        left: 10mm;
        width: 100mm;
        height: 48mm;
        margin-top: auto;
        margin-bottom: auto;
        margin-left: auto;
        margin-right: auto;
    }


    .content_6 {
        position: absolute;
        overflow: visible;
        top: 265mm;
        left: 10mm;
        width: 100mm;
        height: 48mm;
        margin-top: auto;
        margin-bottom: auto;
        margin-left: auto;
        margin-right: auto;
    }


    .content_7 {
        position: absolute;
        overflow: visible;
        top: 15mm;
        left: 110mm;
        width: 100mm;
        height: 48mm;
        margin-top: auto;
        margin-bottom: auto;
        margin-left: auto;
        margin-right: auto;
    }

    .content_8 {
        position: absolute;
        overflow: visible;
        top: 65mm;
        left: 110mm;
        width: 100mm;
        height: 48mm;
        margin-top: auto;
        margin-bottom: auto;
        margin-left: auto;
        margin-right: auto;
    }

    .content_9 {
        position: absolute;
        overflow: visible;
        top: 115mm;
        left: 110mm;
        width: 100mm;
        height: 48mm;
        margin-top: auto;
        margin-bottom: auto;
        margin-left: auto;
        margin-right: auto;
    }

    .content_10 {
        position: absolute;
        overflow: visible;
        top: 165mm;
        left: 110mm;
        width: 100mm;
        height: 48mm;
        margin-top: auto;
        margin-bottom: auto;
        margin-left: auto;
        margin-right: auto;
    }

    .content_11 {
        position: absolute;
        overflow: visible;
        top: 215mm;
        left: 110mm;
        width: 100mm;
        height: 48mm;
        margin-top: auto;
        margin-bottom: auto;
        margin-left: auto;
        margin-right: auto;
    }

    .content_12 {
        position: absolute;
        overflow: visible;
        top: 265mm;
        left: 110mm;
        width: 100mm;
        height: 48mm;
        margin-top: auto;
        margin-bottom: auto;
        margin-left: auto;
        margin-right: auto;
    }
</style>
<?php
$Insurance = '';
if (count($case_master) > 0) {
    $Insurance = '<br/><b>Insurance : </b>' . $case_master[0]->insurance_company_name;
} else {
    $Insurance = '<br/><b>Insurance : </b> CASH / Direct';
}

$srno = 1;
$ipd_doc_list_show = '';
foreach ($ipd_doc_list as $row) {
    $ipd_doc_list_show .= ' Dr. ' . $row->p_fname . ' ';
    $srno = $srno + 1;
}
?>

<div class="content_1">
    <strong>Patient Name :</strong><?= $person_info[0]->title . ' ' . $person_info[0]->p_fname ?><br>
    <strong>Age :</strong><?= $person_info[0]->age ?> / <strong>Gender :</strong><?= $person_info[0]->xgender ?><br>
    <strong>UHID / Patient ID :</strong><?= $person_info[0]->p_code ?><br>
    <strong>IPD Code :</strong><?= $ipd_info[0]->ipd_code ?><br>
    <strong>Dept./Doctor :</strong><?= $ipd_doc_list_show ?><br>
    <strong>Date of Admission :</strong><?= $ipd_info[0]->str_register_date . $Insurance ?>
</div>
<div class="content_2">
    <strong>Patient Name :</strong><?= $person_info[0]->title . ' ' . $person_info[0]->p_fname ?><br>
    <strong>Age :</strong><?= $person_info[0]->age ?> / <strong>Gender :</strong><?= $person_info[0]->xgender ?><br>
    <strong>UHID / Patient ID :</strong><?= $person_info[0]->p_code ?><br>
    <strong>IPD Code :</strong><?= $ipd_info[0]->ipd_code ?><br>
    <strong>Dept./Doctor :</strong><?= $ipd_doc_list_show ?><br>
    <strong>Date of Admission :</strong><?= $ipd_info[0]->str_register_date . $Insurance ?>
</div>
<div class="content_3">
    <strong>Patient Name :</strong><?= $person_info[0]->title . ' ' . $person_info[0]->p_fname ?><br>
    <strong>Age :</strong><?= $person_info[0]->age ?> / <strong>Gender :</strong><?= $person_info[0]->xgender ?><br>
    <strong>UHID / Patient ID :</strong><?= $person_info[0]->p_code ?><br>
    <strong>IPD Code :</strong><?= $ipd_info[0]->ipd_code ?><br>
    <strong>Dept./Doctor :</strong><?= $ipd_doc_list_show ?><br>
    <strong>Date of Admission :</strong><?= $ipd_info[0]->str_register_date . $Insurance ?>
</div>
<div class="content_4">
    <strong>Patient Name :</strong><?= $person_info[0]->title . ' ' . $person_info[0]->p_fname ?><br>
    <strong>Age :</strong><?= $person_info[0]->age ?> / <strong>Gender :</strong><?= $person_info[0]->xgender ?><br>
    <strong>UHID / Patient ID :</strong><?= $person_info[0]->p_code ?><br>
    <strong>IPD Code :</strong><?= $ipd_info[0]->ipd_code ?><br>
    <strong>Dept./Doctor :</strong><?= $ipd_doc_list_show ?><br>
    <strong>Date of Admission :</strong><?= $ipd_info[0]->str_register_date . $Insurance ?>
</div>
<div class="content_5">
    <strong>Patient Name :</strong><?= $person_info[0]->title . ' ' . $person_info[0]->p_fname ?><br>
    <strong>Age :</strong><?= $person_info[0]->age ?> / <strong>Gender :</strong><?= $person_info[0]->xgender ?><br>
    <strong>UHID / Patient ID :</strong><?= $person_info[0]->p_code ?><br>
    <strong>IPD Code :</strong><?= $ipd_info[0]->ipd_code ?><br>
    <strong>Dept./Doctor :</strong><?= $ipd_doc_list_show ?><br>
    <strong>Date of Admission :</strong><?= $ipd_info[0]->str_register_date . $Insurance ?>
</div>
<div class="content_6">
    <strong>Patient Name :</strong><?= $person_info[0]->title . ' ' . $person_info[0]->p_fname ?><br>
    <strong>Age :</strong><?= $person_info[0]->age ?> / <strong>Gender :</strong><?= $person_info[0]->xgender ?><br>
    <strong>UHID / Patient ID :</strong><?= $person_info[0]->p_code ?><br>
    <strong>IPD Code :</strong><?= $ipd_info[0]->ipd_code ?><br>
    <strong>Dept./Doctor :</strong><?= $ipd_doc_list_show ?><br>
    <strong>Date of Admission :</strong><?= $ipd_info[0]->str_register_date . $Insurance ?>
</div>
<div class="content_7">
    <strong>Patient Name :</strong><?= $person_info[0]->title . ' ' . $person_info[0]->p_fname ?><br>
    <strong>Age :</strong><?= $person_info[0]->age ?> / <strong>Gender :</strong><?= $person_info[0]->xgender ?><br>
    <strong>UHID / Patient ID :</strong><?= $person_info[0]->p_code ?><br>
    <strong>IPD Code :</strong><?= $ipd_info[0]->ipd_code ?><br>
    <strong>Dept./Doctor :</strong><?= $ipd_doc_list_show ?><br>
    <strong>Date of Admission :</strong><?= $ipd_info[0]->str_register_date . $Insurance ?>
</div>
<div class="content_8">
    <strong>Patient Name :</strong><?= $person_info[0]->title . ' ' . $person_info[0]->p_fname ?><br>
    <strong>Age :</strong><?= $person_info[0]->age ?> / <strong>Gender :</strong><?= $person_info[0]->xgender ?><br>
    <strong>UHID / Patient ID :</strong><?= $person_info[0]->p_code ?><br>
    <strong>IPD Code :</strong><?= $ipd_info[0]->ipd_code ?><br>
    <strong>Dept./Doctor :</strong><?= $ipd_doc_list_show ?><br>
    <strong>Date of Admission :</strong><?= $ipd_info[0]->str_register_date . $Insurance ?>
</div>
<div class="content_9">
    <strong>Patient Name :</strong><?= $person_info[0]->title . ' ' . $person_info[0]->p_fname ?><br>
    <strong>Age :</strong><?= $person_info[0]->age ?> / <strong>Gender :</strong><?= $person_info[0]->xgender ?><br>
    <strong>UHID / Patient ID :</strong><?= $person_info[0]->p_code ?><br>
    <strong>IPD Code :</strong><?= $ipd_info[0]->ipd_code ?><br>
    <strong>Dept./Doctor :</strong><?= $ipd_doc_list_show ?><br>
    <strong>Date of Admission :</strong><?= $ipd_info[0]->str_register_date . $Insurance ?>
</div>
<div class="content_10">
    <strong>Patient Name :</strong><?= $person_info[0]->title . ' ' . $person_info[0]->p_fname ?><br>
    <strong>Age :</strong><?= $person_info[0]->age ?> / <strong>Gender :</strong><?= $person_info[0]->xgender ?><br>
    <strong>UHID / Patient ID :</strong><?= $person_info[0]->p_code ?><br>
    <strong>IPD Code :</strong><?= $ipd_info[0]->ipd_code ?><br>
    <strong>Dept./Doctor :</strong><?= $ipd_doc_list_show ?><br>
    <strong>Date of Admission :</strong><?= $ipd_info[0]->str_register_date . $Insurance ?>
</div>
<div class="content_11">
    <strong>Patient Name :</strong><?= $person_info[0]->title . ' ' . $person_info[0]->p_fname ?><br>
    <strong>Age :</strong><?= $person_info[0]->age ?> / <strong>Gender :</strong><?= $person_info[0]->xgender ?><br>
    <strong>UHID / Patient ID :</strong><?= $person_info[0]->p_code ?><br>
    <strong>IPD Code :</strong><?= $ipd_info[0]->ipd_code ?><br>
    <strong>Dept./Doctor :</strong><?= $ipd_doc_list_show ?><br>
    <strong>Date of Admission :</strong><?= $ipd_info[0]->str_register_date . $Insurance ?>
</div>
<div class="content_12">
    <strong>Patient Name :</strong><?= $person_info[0]->title . ' ' . $person_info[0]->p_fname ?><br>
    <strong>Age :</strong><?= $person_info[0]->age ?> / <strong>Gender :</strong><?= $person_info[0]->xgender ?><br>
    <strong>UHID / Patient ID :</strong><?= $person_info[0]->p_code ?><br>
    <strong>IPD Code :</strong><?= $ipd_info[0]->ipd_code ?><br>
    <strong>Dept./Doctor :</strong><?= $ipd_doc_list_show ?><br>
    <strong>Date of Admission :</strong><?= $ipd_info[0]->str_register_date . $Insurance ?>
</div>