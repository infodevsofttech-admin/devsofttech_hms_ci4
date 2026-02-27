<style>
    body {
        font-family: Verdana, Geneva, Tahoma, sans-serif;
    }

    table {
        border-collapse: collapse;
    }

    thead {
        vertical-align: bottom;
        text-align: center;
        font-weight: bold;
    }

    tfoot {
        text-align: center;
        font-weight: bold;
    }

    th {
        text-align: left;
        padding-left: 0.35em;
        padding-right: 0.35em;
        padding-top: 0.35em;
        padding-bottom: 0.35em;
        vertical-align: top;
    }

    td {
        padding-left: 0.35em;
        padding-right: 0.35em;
        padding-top: 0.35em;
        padding-bottom: 0.35em;
        vertical-align: top;
    }

    p,
    td {
        font-family: Verdana, Geneva, Tahoma, sans-serif;
        font-size: 10pt;
        padding: 1px;
        margin: 5px;
    }

    p {
        text-indent: -25px;
    }
</style>
<h2 style="text-align:center;margin:1px; padding:0px;"><?= $h1_head ?></h2>
<hr style="margin:1px; padding:0px;" />
<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td width="150px"><b>Name</b></td>
        <td width="250px"><?= ucwords($ipd_master[0]->p_fname) ?></td>
        <td width="150px"><b>UHID</b></td>
        <td width="250px"><?= $ipd_master[0]->p_code ?></td>
    </tr>
    <tr>
        <td width="150px"><b>Age & Gender</b></td>
        <td width="250px"><?= $ipd_master[0]->str_age . ' / ' . $ipd_master[0]->xgender ?></td>
        <td width="150px"><b>IPD No.</b></td>
        <td width="250px"><?= $ipd_master[0]->ipd_code ?></td>
    </tr>
    <tr>
        <td width="150px"><b>Guardian</b></td>
        <td width="250px"><?= ucwords($ipd_master[0]->p_relative) . ' ' . ucwords($ipd_master[0]->p_rname) ?></td>
        <td width="150px"><b>Admission</b></td>
        <td width="250px"><?= $ipd_master[0]->str_register_date . ' ' . $Reg_time ?></td>
    </tr>
    <tr>
        <td width="150px"><b>Phone No.</b></td>
        <td width="250px"><?= $ipd_master[0]->mphone1 ?></td>
        <td width="150px"><b>Discharge</b></td>
        <td width="250px"><?= $ipd_master[0]->str_discharge_date . ' ' . $Discharge_time ?></td>
    </tr>
    <tr>
        <td width="150px"><b>Address</b></td>
        <td width="250px"><?= ucwords($ipd_master[0]->add1).','.ucwords($ipd_master[0]->add2).','.ucwords($ipd_master[0]->city) ?></td>
        <td width="150px"><b>Org. Name</b></td>
        <td width="250px"><?= $ipd_master[0]->admit_type ?></td>
    </tr>
    <tr>
        <td width="150px"><b>Department</b></td>
        <td width="250px"><?= ucwords($depart_name) ?></td>
        <td width="150px"></td>
        <td width="250px"></td>
    </tr>
</table>
<hr style="margin:1px; padding:0px;" />
<?php if (isset($FinalDiagnosis)) { ?>
<?=$FinalDiagnosis?>
<?php } ?>
<?php if (isset($Surgery)) { ?>
<?=$Surgery?>
<?php } ?>
<?php if (isset($Procedure)) { ?>
<?= $Procedure ?>
<?php } ?>
<?php if (isset($isdelivery)) { ?>
<?= $isdelivery ?>
<?php } ?>
<?php if (isset($personal_history)) { ?>
<?= $personal_history ?>
<?php } ?>
<?php if (isset($discharge_complaint)) { ?>
<?= $discharge_complaint ?>
<?php } ?>
<?php if (isset($discharge_general_exam)) { ?>
<?= $discharge_general_exam ?>
<?php } ?>
<?php if (isset($lab_test_content)) { ?>
<?= $lab_test_content ?>
<?php } ?>
<?php if (isset($Course_in_the_hospital)) { ?>
<?= $Course_in_the_hospital ?>
<?php } ?>
<?php if (isset($discharge_exam_on_discharge)) { ?>
<?= $discharge_exam_on_discharge ?>
<?php } ?>
<?php if (isset($Discharge_Medications)) { ?>
<?= $Discharge_Medications ?>
<?php } ?>
<?php if (isset($diet_advice)) { ?>
<p><?= $diet_advice ?></p>
<?php } ?>
<?php if (isset($Discharge_Instructions)) { ?>
<p><?= $Discharge_Instructions ?></p>
<?php } ?>