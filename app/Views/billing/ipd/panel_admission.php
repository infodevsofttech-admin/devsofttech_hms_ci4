<?php
$ipd = $ipd_info ?? null;
$person = $person_info ?? null;
$age = '';
if ($person) {
    $age = get_age_1($person->dob ?? null, $person->age ?? '', $person->age_in_month ?? '', $person->estimate_dob ?? '');
}
?>
<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Patient</h5>
                <p class="mb-1"><strong>Name :</strong> <?= esc($person->p_fname ?? '') ?> {<?= esc($person->p_rname ?? '') ?>}</p>
                <p class="mb-1"><strong>UHID :</strong> <?= esc($person->p_code ?? '') ?></p>
                <p class="mb-1"><strong>Gender :</strong> <?= esc($person->xgender ?? '') ?></p>
                <p class="mb-1"><strong>Age :</strong> <?= esc($age) ?></p>
                <p class="mb-0"><strong>Phone :</strong> <?= esc($person->mphone1 ?? '') ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Admission</h5>
                <p class="mb-1"><strong>IPD Code :</strong> <?= esc($ipd->ipd_code ?? '') ?></p>
                <p class="mb-1"><strong>Admit Date :</strong> <?= esc($ipd->str_register_date ?? '') ?></p>
                <p class="mb-1"><strong>Discharge Date :</strong> <?= esc($ipd->str_discharge_date ?? '') ?></p>
                <p class="mb-1"><strong>No. of Days :</strong> <?= esc($ipd->no_days ?? '') ?></p>
                <p class="mb-0"><strong>Insurance :</strong> <?= esc($ipd->ins_company_name ?? 'Direct') ?></p>
            </div>
        </div>
    </div>
</div>
