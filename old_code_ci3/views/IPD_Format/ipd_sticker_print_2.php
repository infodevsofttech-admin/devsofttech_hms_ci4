<style>

.content_1 {
    position: absolute;
    overflow: visible;
    top: 17mm; 
    left: 10mm; 
    width: 100mm;  
    height: 33mm;
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
    font-size: 9pt; 
}

.content_2 {
    position: absolute;
    overflow: visible;
    top: 51mm; 
    left: 10mm; 
    width: 100mm;  
    height: 33mm;
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
    font-size: 9pt; 
}

.content_3 {
    position: absolute;
    overflow: visible;
    top: 85mm; 
    left: 10mm; 
    width: 100mm;  
    height: 33mm;
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
    font-size: 9pt; 
}


.content_4 {
    position: absolute;
    overflow: visible;
    top: 119mm; 
    left: 10mm; 
    width: 100mm;  
    height: 33mm;
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
    font-size: 9pt; 
}


.content_5 {
    position: absolute;
    overflow: visible;
    top: 153mm; 
    left: 10mm; 
    width: 100mm;  
    height: 33mm;
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
    font-size: 9pt; 
}


.content_6 {
    position: absolute;
    overflow: visible;
    top: 187mm; 
    left: 10mm; 
    width: 100mm;  
    height: 33mm;
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
    font-size: 9pt; 
}


.content_7 {
    position: absolute;
    overflow: visible;
    top: 221mm; 
    left: 10mm; 
    width: 100mm;  
    height: 33mm;
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
    font-size: 9pt; 
}

.content_8 {
    position: absolute;
    overflow: visible;
    top: 255mm; 
    left: 10mm;
    width: 100mm;  
    height: 33mm;
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
    font-size: 9pt; 
}

.content_9 {
    position: absolute;
    overflow: visible;
    top: 17mm; 
    left: 110mm; 
    width: 100mm;  
    height: 33mm;
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
    font-size: 9pt; 
}

.content_10 {
    position: absolute;
    overflow: visible;
    top: 51mm; 
    left: 110mm; 
    width: 100mm;  
    height: 33mm;
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
    font-size: 9pt; 
}

.content_11 {
    position: absolute;
    overflow: visible;
    top: 85mm; 
    left: 110mm; 
    width: 100mm;  
    height: 33mm;
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
    font-size: 9pt; 
}


.content_12 {
    position: absolute;
    overflow: visible;
    top: 119mm; 
    left: 110mm; 
    width: 100mm;  
    height: 33mm;
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
    font-size: 9pt; 
}


.content_13 {
    position: absolute;
    overflow: visible;
    top: 153mm; 
    left: 110mm; 
    width: 100mm;  
    height: 33mm;
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
    font-size: 9pt; 
}


.content_14 {
    position: absolute;
    overflow: visible;
    top: 187mm; 
    left: 110mm; 
    width: 100mm;  
    height: 33mm;
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
    font-size: 9pt; 
}

.content_15 {
    position: absolute;
    overflow: visible;
    top: 221mm; 
    left: 110mm; 
    width: 100mm;  
    height: 33mm;
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
    font-size: 9pt; 
}

.content_16 {
    position: absolute;
    overflow: visible;
    top: 255mm; 
    left: 110mm; 
    width: 100mm;  
    height: 33mm;
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
    font-size: 9pt; 
}

</style>
<?php
    $Insurance='';
    if(count($case_master)>0){ 
        $Insurance= '<br/><b>Insurance : </b>'.$case_master[0]->insurance_company_name;
    }else{
        $Insurance= '<br/><b>Insurance : </b> CASH / Direct';
    }

    $srno=1;
    $ipd_doc_list_show='';
    foreach($ipd_doc_list as $row)
    { 
        $ipd_doc_list_show.= ' Dr. '.$row->p_fname.' ';
        $srno=$srno+1;
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
<div class="content_13">
    <strong>Patient Name :</strong><?= $person_info[0]->title . ' ' . $person_info[0]->p_fname ?><br>
    <strong>Age :</strong><?= $person_info[0]->age ?> / <strong>Gender :</strong><?= $person_info[0]->xgender ?><br>
    <strong>UHID / Patient ID :</strong><?= $person_info[0]->p_code ?><br>
    <strong>IPD Code :</strong><?= $ipd_info[0]->ipd_code ?><br>
    <strong>Dept./Doctor :</strong><?= $ipd_doc_list_show ?><br>
    <strong>Date of Admission :</strong><?= $ipd_info[0]->str_register_date . $Insurance ?>
</div>
<div class="content_14">
    <strong>Patient Name :</strong><?= $person_info[0]->title . ' ' . $person_info[0]->p_fname ?><br>
    <strong>Age :</strong><?= $person_info[0]->age ?> / <strong>Gender :</strong><?= $person_info[0]->xgender ?><br>
    <strong>UHID / Patient ID :</strong><?= $person_info[0]->p_code ?><br>
    <strong>IPD Code :</strong><?= $ipd_info[0]->ipd_code ?><br>
    <strong>Dept./Doctor :</strong><?= $ipd_doc_list_show ?><br>
    <strong>Date of Admission :</strong><?= $ipd_info[0]->str_register_date . $Insurance ?>
</div>
<div class="content_15">
    <strong>Patient Name :</strong><?= $person_info[0]->title . ' ' . $person_info[0]->p_fname ?><br>
    <strong>Age :</strong><?= $person_info[0]->age ?> / <strong>Gender :</strong><?= $person_info[0]->xgender ?><br>
    <strong>UHID / Patient ID :</strong><?= $person_info[0]->p_code ?><br>
    <strong>IPD Code :</strong><?= $ipd_info[0]->ipd_code ?><br>
    <strong>Dept./Doctor :</strong><?= $ipd_doc_list_show ?><br>
    <strong>Date of Admission :</strong><?= $ipd_info[0]->str_register_date . $Insurance ?>
</div>
<div class="content_16">
    <strong>Patient Name :</strong><?= $person_info[0]->title . ' ' . $person_info[0]->p_fname ?><br>
    <strong>Age :</strong><?= $person_info[0]->age ?> / <strong>Gender :</strong><?= $person_info[0]->xgender ?><br>
    <strong>UHID / Patient ID :</strong><?= $person_info[0]->p_code ?><br>
    <strong>IPD Code :</strong><?= $ipd_info[0]->ipd_code ?><br>
    <strong>Dept./Doctor :</strong><?= $ipd_doc_list_show ?><br>
    <strong>Date of Admission :</strong><?= $ipd_info[0]->str_register_date . $Insurance ?>
</div>