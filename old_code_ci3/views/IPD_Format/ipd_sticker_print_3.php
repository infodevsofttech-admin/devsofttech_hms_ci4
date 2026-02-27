<style>
    @page {
        sheet-size: 60mm 40mm;
        margin-top: 1mm;
        margin-bottom: 1mm;
        margin-left: 1mm;
        margin-right: 1mm;
    }

    /* Optional: Some base styling for the table */
    table {
        width: 100%;
        border-collapse: collapse;
    }
    td {
        vertical-align: top;
        padding: 2px;
    }
</style>

<?php
    // Your existing PHP code to prepare data
    $Insurance = '';
    if (count($case_master) > 0) {
        $Insurance = '<br/><b>Insurance : </b>' . $case_master[0]->insurance_company_name;
    } else {
        $Insurance = '<br/><b>Insurance : </b> CASH / Direct';
    }

    $ipd_doc_list_show = '';
    foreach ($ipd_doc_list as $row) {
        $ipd_doc_list_show .= ' Dr. ' . $row->p_fname . ' ';
    }
?>

<!-- ======================= KEY CHANGE IS HERE ======================= -->
<!-- We wrap the content in a table with width="100%" and the magic autosize="1" attribute -->

<table autosize="1">
    <tr>
        <td>
            <div style="width: 100%; font-size: 12px; text-align: left;">
                <strong>Patient Name :</strong><?= $person_info[0]->title . ' ' . $person_info[0]->p_fname ?><br>
                <strong>Age :</strong><?= $person_info[0]->age ?> / <strong>Gender :</strong><?= $person_info[0]->xgender ?><br>
                <strong>UHID / Patient ID :</strong><?= $person_info[0]->p_code ?><br>
                <strong>IPD Code :</strong><?= $ipd_info[0]->ipd_code ?><br>
                <strong>Dept./Doctor :</strong><?= $ipd_doc_list_show ?><br>
                <strong>Date of Admission :</strong><?= $ipd_info[0]->str_register_date . $Insurance ?>
            </div>
        </td>
    </tr>
</table>