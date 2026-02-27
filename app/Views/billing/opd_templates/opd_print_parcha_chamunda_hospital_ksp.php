<?php
$opd = $opd_master[0] ?? null;
$patient = $patient_master[0] ?? null;
$doctor = $doctor_master[0] ?? null;

$pName = strtoupper(trim((string) (($patient->title ?? '') . ' ' . ($patient->p_fname ?? ''))));
$pRelative = trim((string) (($patient->p_relative ?? '') . ' ' . ($patient->p_rname ?? '')));
$ageSex = trim((string) (($patient->xgender ?? '') . ' / ' . ($patient->age ?? '')));
$phone = (string) ($patient->mphone1 ?? '');
$pAddress = trim((string) (($patient->add1 ?? '') . ', ' . ($patient->add2 ?? '') . ', ' . ($patient->city ?? '')), ' ,');

$uhidNo = (string) ($patient->p_code ?? '');
$opdSrNo = (string) ($opd->opd_no ?? '');
$opdNo = (string) ($opd->opd_code ?? '');
$opdDate = (string) ($opd->str_apointment_date ?? '');
$expDate = (string) ($opd->opd_Exp_Date ?? '');

$specName = trim((string) ($doctor->SpecName ?? $opd->doc_spec ?? ''));
$opdFeeDesc = trim((string) ($opd->opd_fee_desc ?? ''));
$totalNoVisit = (string) ($opd->no_visit ?? '');
$lastVisitDate = trim((string) (($old_opd[0]->str_apointment_date ?? '') ?: ''));
$bookTime = (string) ($opd->apointment_date ?? '');

$lastVisitLine = $lastVisitDate !== '' ? ('<br>Last Visit : ' . esc($lastVisitDate)) : '';
$expDateLine = $expDate !== '' ? ('<b>Valid Upto : </b>' . esc($expDate)) : '';
?>
<style>
@page {
    margin-top: 5.5cm;
    margin-bottom: 1.2cm;
    margin-left: 0.5cm;
    margin-right: 0.5cm;
    margin-header: 0.5cm;
    margin-footer: 0.5cm;
    header: html_myHeader;
    footer: html_myFooter;
}
body { font-family: freeserif, dejavusans, sans-serif; font-size: 10pt; }
</style>

<htmlpageheader name="myHeader">
    <p align="center"></p>
</htmlpageheader>

<htmlpagefooter name="myFooter">
    <p align="center">Print Time : <?= date('d-m-Y H:i:s') ?></p>
</htmlpagefooter>

<table width="100%" border="0" style="font-size:10pt;">
    <tr>
        <td width="33.3%" valign="top">
            Name : <strong><?= esc($pName) ?></strong><br>
            <?= esc($pRelative) ?><br>
            Gender/Age : <b><?= esc($ageSex) ?></b><br>
            Mob : <?= esc($phone) ?><br>
            Address : <?= esc($pAddress) ?>
        </td>
        <td width="33.3%" valign="top">
            UHID : <?= esc($uhidNo) ?><br>
            Sr No.: <b><?= esc($opdSrNo) ?></b><br>
            OPD No.: <b><?= esc($opdNo) ?></b><br>
            <br><b>Date: <?= esc($opdDate) ?></b><br>
            <?= $expDateLine ?>
        </td>
        <td width="33.3%" valign="top">
            <b>DEPARTMENT :</b><br>
            <?= esc($specName) ?><br>
            <?= esc($opdFeeDesc) ?><br>
            <b>No. of Visit</b> : <?= esc($totalNoVisit) ?>
            <?= $lastVisitLine ?><br>
            Book Time : <?= esc($bookTime) ?>
        </td>
    </tr>
</table>
