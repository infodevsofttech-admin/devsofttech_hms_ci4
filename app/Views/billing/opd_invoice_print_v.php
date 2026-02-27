<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= defined('H_Name') ? H_Name : 'Hospital' ?> | OPD Invoice</title>
    <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
    <style>
        body { font-size: 12px; }
        .invoice { margin: 16px; }
        .table td, .table th { padding: 6px; }
    </style>
</head>
<body onload="window.print();">
<section class="invoice">
    <h3>
        <?= defined('H_Name') ? H_Name : 'Hospital' ?>
        <small style="float:right;">Printed on: <?= date('d-m-Y h:i:s A') ?></small>
    </h3>
    <hr>

    <div class="row">
        <div class="col-xs-4">
            <strong>OPD Invoice ID:</strong> <?= esc($opd_master[0]->opd_code ?? '') ?><br>
            <strong>Patient ID:</strong> <?= esc($patient_master[0]->p_code ?? '') ?><br>
            <?php if (($opd_master[0]->insurance_id ?? 0) > 1 && !empty($insurance)) : ?>
                <strong>Ins. Comp.:</strong> <?= esc($insurance[0]->ins_company_name ?? '') ?><br>
            <?php endif; ?>
            <?php if (($opd_master[0]->insurance_case_id ?? 0) > 0 && !empty($case_master)) : ?>
                <strong>Org. Case No.:</strong> <?= esc($case_master[0]->case_id_code ?? '') ?><br>
            <?php endif; ?>
            <?php if (!empty($opd_master[0]->payment_id)) : ?>
                <strong>Payment No.:</strong> <?= esc($opd_master[0]->payment_id ?? '') ?><br>
            <?php endif; ?>
        </div>
        <div class="col-xs-4">
            <strong><?= defined('H_Name') ? H_Name : 'Hospital' ?></strong><br>
            <?= defined('H_address_1') ? H_address_1 : '' ?><br>
            <?= defined('H_address_2') ? H_address_2 : '' ?><br>
            <?= defined('H_phone_No') ? 'Phone: ' . H_phone_No : '' ?><br>
            <?= defined('H_Email') ? 'Email: ' . H_Email : '' ?>
        </div>
        <div class="col-xs-4">
            <strong><?= esc(($patient_master[0]->title ?? '') . ' ' . strtoupper($patient_master[0]->p_fname ?? '')) ?></strong><br>
            Date: <?= esc($opd_master[0]->str_apointment_date ?? '') ?><br>
            Gender: <?= esc($patient_master[0]->xgender ?? '') ?><br>
            Age: <?= esc($patient_master[0]->age ?? '') ?><br>
            Phone: <?= esc($patient_master[0]->mphone1 ?? '') ?><br>
            Visit Count: <?= esc($opd_master[0]->no_visit ?? '') ?>
        </div>
    </div>

    <br>
    <table class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Date</th>
            <th>Doctor</th>
            <th>Department</th>
            <th>Description</th>
            <th>OPD Fee</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><?= esc($opd_master[0]->apointment_date ?? '') ?></td>
            <td>Dr. <?= esc($opd_master[0]->doc_name ?? '') ?></td>
            <td><?= esc($opd_master[0]->doc_spec ?? '') ?></td>
            <td><?= esc($opd_master[0]->opd_fee_desc ?? '') ?></td>
            <td><?= esc($opd_master[0]->opd_fee_gross_amount ?? '') ?></td>
        </tr>
        <?php if (!empty($opd_master[0]->opd_discount) && (float) ($opd_master[0]->opd_discount ?? 0) > 0) : ?>
            <tr>
                <td>Deduction</td>
                <td colspan="2"><?= esc($opd_master[0]->opd_disc_remark ?? '') ?></td>
                <td>-<?= esc($opd_master[0]->opd_discount ?? '') ?></td>
                <td></td>
            </tr>
        <?php endif; ?>
        <tr>
            <td></td>
            <td></td>
            <td colspan="2"><strong>Net Amount</strong></td>
            <td><strong><?= esc($opd_master[0]->opd_fee_amount ?? '') ?></strong></td>
        </tr>
        </tbody>
    </table>

    <?php if ((int) ($opd_master[0]->opd_status ?? 0) === 3) : ?>
        <strong>OPD Cancelled</strong><br>
        <?= esc($opd_master[0]->opd_status_remark ?? '') ?>
    <?php else : ?>
        <strong>Mode of Payment:</strong> <?= esc($opd_master[0]->payment_mode_desc ?? '') ?>
    <?php endif; ?>
</section>
</body>
</html>
