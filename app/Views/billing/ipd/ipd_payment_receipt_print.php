<?php
$payment = $ipd_payment[0] ?? null;
$ipd = $ipd_master[0] ?? null;
$patient = $patient_master[0] ?? null;
?>
<div class="card">
    <div class="card-header">
        <strong>IPD Payment <?= esc($payment->Amount_str ?? '') ?> Receipt : <?= esc($payid ?? '') ?></strong>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>UHID :</strong> <?= esc($patient->p_code ?? '') ?></p>
                <p><strong>Name :</strong> <?= esc($patient->title ?? '') ?> <?= esc($patient->p_fname ?? '') ?></p>
                <p><strong>Sex :</strong> <?= esc($patient->xgender ?? '') ?> <?= esc($patient->age ?? '') ?></p>
                <p><strong>Phone :</strong> <?= esc($patient->mphone1 ?? '') ?></p>
            </div>
            <div class="col-md-6 text-end">
                <p><strong>IPD No. :</strong> <?= esc($ipd->ipd_code ?? '') ?></p>
                <p><strong>Date :</strong> <?= esc($payment->payment_date_str ?? '') ?></p>
            </div>
        </div>
        <hr>
        <p><strong>IPD <?= esc($payment->Amount_str ?? '') ?> Amount :</strong> Rs. <?= esc($payment->amount ?? '') ?></p>
        <p><strong>Amount in Words :</strong> Rs. <?= number_to_word((float) ($payment->amount ?? 0)) ?></p>
        <p><strong>Prepared By :</strong> <?= esc($payment->update_by ?? '') ?></p>
    </div>
</div>
