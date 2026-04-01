<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= defined('H_Name') ? constant('H_Name') : 'OPD Letter Head' ?></title>
    <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
    <style>
        body { font-size: 14px; }
        .sheet { margin: 20px; }
        .rx-block-title { margin-top: 8px; margin-bottom: 4px; font-weight: 700; }
        .rx-table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        .rx-table th, .rx-table td { border: 1px solid #999; padding: 4px 6px; font-size: 13px; vertical-align: top; }
        .rx-table th { background: #f7f7f7; }
    </style>
</head>
<body onload="window.print();">
<section class="sheet">
    <div class="row">
        <div class="col-xs-4">
            UHID: <strong><?= esc($patient_master[0]->p_code ?? '') ?></strong><br>
            Name: <strong><?= esc(($patient_master[0]->title ?? '') . ' ' . strtoupper($patient_master[0]->p_fname ?? '')) ?></strong><br>
            <?= esc(($patient_master[0]->p_relative ?? '') . ' ' . strtoupper($patient_master[0]->p_rname ?? '')) ?><br>
            Sex: <strong><?= esc($patient_master[0]->xgender ?? '') ?></strong>
            / Age: <strong><?= esc($patient_master[0]->age ?? '') ?></strong>
        </div>
        <div class="col-xs-4">
            OPD No.: <strong><?= esc($opd_master[0]->opd_code ?? '') ?> / <?= esc($opd_master[0]->opd_id ?? '') ?></strong><br>
            OPD Fee: <?= esc($opd_master[0]->opd_fee_amount ?? '') ?> [<?= esc($opd_master[0]->opd_fee_desc ?? '') ?>]<br>
            <strong>Date: <?= esc($opd_master[0]->str_apointment_date ?? '') ?></strong><br>
            <?php if (($opd_master[0]->opd_fee_type ?? '') === '3' && !empty($old_opd)) : ?>
                Valid Upto: <?= esc($old_opd[0]->opd_Exp_Date ?? '') ?><br>
            <?php else : ?>
                <strong>Valid Upto: <?= esc($opd_master[0]->opd_Exp_Date ?? '') ?></strong><br>
            <?php endif; ?>
        </div>
        <div class="col-xs-4">
            Sr No.: <strong><?= esc($opd_master[0]->opd_no ?? '') ?></strong><br>
            Phone: <?= esc($patient_master[0]->mphone1 ?? '') ?><br>
            Address: <?= esc(($patient_master[0]->add1 ?? '') . ',' . ($patient_master[0]->city ?? '')) ?><br>
        </div>
    </div>

    <?php
        $rx = $opd_prescription ?? [];
        $painLabels = [
            '0' => 'No Pain',
            '1' => 'Mild Pain',
            '2' => 'Moderate',
            '3' => 'Intense',
            '4' => 'Worst Pain Possible',
        ];
        $painValue = (string) ($rx['pain_value'] ?? '');
        $painText = $painLabels[$painValue] ?? '';

        $complications = [];
        if (!empty($rx['pregnancy'] ?? 0)) { $complications[] = 'Pregnancy'; }
        if (!empty($rx['lactation'] ?? 0)) { $complications[] = 'Lactation'; }
        if (!empty($rx['liver_insufficiency'] ?? 0)) { $complications[] = 'Liver Insufficiency'; }
        if (!empty($rx['renal_insufficiency'] ?? 0)) { $complications[] = 'Renal Insufficiency'; }
        if (!empty($rx['pulmonary_insufficiency'] ?? 0)) { $complications[] = 'Pulmonary Insufficiency'; }
        if (!empty($rx['corona_suspected'] ?? 0)) { $complications[] = 'Corona Suspected'; }
        if (!empty($rx['dengue'] ?? 0)) { $complications[] = 'Dengue'; }

        $addictions = [];
        if (!empty($patient_master[0]->is_smoking ?? 0)) { $addictions[] = 'Smoking'; }
        if (!empty($patient_master[0]->is_alcohol ?? 0)) { $addictions[] = 'Alcohol'; }
        if (!empty($patient_master[0]->is_drug_abuse ?? 0)) { $addictions[] = 'Drug Abuse'; }

        $isFemale = strtoupper((string) ($patient_master[0]->xgender ?? '')) === 'F';
        $womenRelatedProblems = trim((string) ($rx['women_related_problems'] ?? ''));
        $womenLmp = trim((string) ($rx['women_lmp'] ?? ''));
        $womenLastBaby = trim((string) ($rx['women_last_baby'] ?? ''));
        $womenPregnancyRelated = trim((string) ($rx['women_pregnancy_related'] ?? ''));

        $vitals = array_filter([
            trim((string) ($rx['bp'] ?? '')) !== '' ? ('BP: ' . trim((string) ($rx['bp'] ?? '')) . '/' . trim((string) ($rx['diastolic'] ?? ''))) : '',
            trim((string) ($rx['pulse'] ?? '')) !== '' ? ('Pulse: ' . trim((string) ($rx['pulse'] ?? '')) . '/min') : '',
            trim((string) ($rx['temp'] ?? '')) !== '' ? ('Temp: ' . trim((string) ($rx['temp'] ?? ''))) : '',
            trim((string) ($rx['spo2'] ?? '')) !== '' ? ('SpO2: ' . trim((string) ($rx['spo2'] ?? '')) . '%') : '',
            trim((string) ($rx['rr_min'] ?? '')) !== '' ? ('RR: ' . trim((string) ($rx['rr_min'] ?? '')) . '/min') : '',
            trim((string) ($rx['height'] ?? '')) !== '' ? ('Height: ' . trim((string) ($rx['height'] ?? ''))) : '',
            trim((string) ($rx['weight'] ?? '')) !== '' ? ('Weight: ' . trim((string) ($rx['weight'] ?? ''))) : '',
            trim((string) ($rx['waist'] ?? '')) !== '' ? ('Waist: ' . trim((string) ($rx['waist'] ?? ''))) : '',
        ]);
    ?>

    <?php if (!empty($rx)) : ?>
        <hr>
        <div class="row">
            <div class="col-xs-12"><strong>Prescription Summary</strong></div>

            <?php if (!empty($rx['complaints'] ?? '')) : ?>
                <div class="col-xs-12"><strong>Complaints:</strong> <?= nl2br(esc((string) ($rx['complaints'] ?? ''))) ?></div>
            <?php endif; ?>

            <?php if (!empty($rx['diagnosis'] ?? '')) : ?>
                <div class="col-xs-12"><strong>Diagnosis:</strong> <?= nl2br(esc((string) ($rx['diagnosis'] ?? ''))) ?></div>
            <?php endif; ?>

            <?php if (!empty($rx['Provisional_diagnosis'] ?? '')) : ?>
                <div class="col-xs-12"><strong>Provisional Diagnosis:</strong> <?= nl2br(esc((string) ($rx['Provisional_diagnosis'] ?? ''))) ?></div>
            <?php endif; ?>

            <?php if (!empty($rx['Finding_Examinations'] ?? '')) : ?>
                <div class="col-xs-12"><strong>Finding/Examination:</strong> <?= nl2br(esc((string) ($rx['Finding_Examinations'] ?? ''))) ?></div>
            <?php endif; ?>

            <?php if (!empty($vitals)) : ?>
                <div class="col-xs-12"><strong>Vitals:</strong> <?= esc(implode(' | ', $vitals)) ?></div>
            <?php endif; ?>

            <?php if ($painText !== '') : ?>
                <div class="col-xs-12"><strong>Pain Measurement Scale:</strong> <?= esc($painText) ?><?= $painValue !== '' ? ' (' . esc($painValue) . ')' : '' ?></div>
            <?php endif; ?>

            <?php if (!empty($complications)) : ?>
                <div class="col-xs-12"><strong>Complication:</strong> <?= esc(implode(', ', $complications)) ?></div>
            <?php endif; ?>

            <?php if (!empty($addictions)) : ?>
                <div class="col-xs-12"><strong>Addiction:</strong> <?= esc(implode(', ', $addictions)) ?></div>
            <?php endif; ?>

            <?php if (!empty($selected_morbidities ?? [])) : ?>
                <div class="col-xs-12"><strong>Co-Morbidities:</strong> <?= esc(implode(', ', $selected_morbidities)) ?></div>
            <?php endif; ?>

            <?php if ($isFemale && $womenRelatedProblems !== '') : ?>
                <div class="col-xs-12"><strong>Women Related Problems:</strong> <?= nl2br(esc($womenRelatedProblems)) ?></div>
            <?php endif; ?>

            <?php if ($isFemale && $womenLmp !== '') : ?>
                <div class="col-xs-12"><strong>LMP (No. of days before):</strong> <?= esc($womenLmp) ?></div>
            <?php endif; ?>

            <?php if ($isFemale && $womenLastBaby !== '') : ?>
                <div class="col-xs-12"><strong>Last Baby:</strong> <?= esc($womenLastBaby) ?></div>
            <?php endif; ?>

            <?php if ($isFemale && $womenPregnancyRelated !== '') : ?>
                <div class="col-xs-12"><strong>Pregnancy Related:</strong> <?= nl2br(esc($womenPregnancyRelated)) ?></div>
            <?php endif; ?>

            <?php if (!empty($rx['investigation'] ?? '')) : ?>
                <div class="col-xs-12"><strong>Investigation:</strong> <?= nl2br(esc((string) ($rx['investigation'] ?? ''))) ?></div>
            <?php endif; ?>

            <?php if (!empty($rx['Prescriber_Remarks'] ?? '')) : ?>
                <div class="col-xs-12"><strong>Prescription Remarks:</strong> <?= nl2br(esc((string) ($rx['Prescriber_Remarks'] ?? ''))) ?></div>
            <?php endif; ?>

            <?php if (!empty($rx['advice'] ?? '')) : ?>
                <div class="col-xs-12"><strong>Advice:</strong> <?= nl2br(esc((string) ($rx['advice'] ?? ''))) ?></div>
            <?php endif; ?>

            <?php if (!empty($rx['next_visit'] ?? '')) : ?>
                <div class="col-xs-12"><strong>Next Visit:</strong> <?= esc((string) ($rx['next_visit'] ?? '')) ?></div>
            <?php endif; ?>

            <?php if (!empty($rx['refer_to'] ?? '')) : ?>
                <div class="col-xs-12"><strong>Refer To:</strong> <?= esc((string) ($rx['refer_to'] ?? '')) ?></div>
            <?php endif; ?>
        </div>

        <?php if (!empty($rx_medicines ?? [])) : ?>
            <div class="rx-block-title">Medicines</div>
            <table class="rx-table">
                <thead>
                    <tr>
                        <th>Medicine</th>
                        <th>Type</th>
                        <th>Dose</th>
                        <th>When</th>
                        <th>Frequency</th>
                        <th>Days</th>
                        <th>Qty</th>
                        <th>Remark</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($rx_medicines ?? []) as $med) : ?>
                        <tr>
                            <td><?= esc((string) ($med['med_name'] ?? '')) ?></td>
                            <td><?= esc((string) ($med['med_type'] ?? '')) ?></td>
                            <td><?= esc((string) ($med['dosage'] ?? '')) ?></td>
                            <td><?= esc((string) ($med['dosage_when'] ?? '')) ?></td>
                            <td><?= esc((string) ($med['dosage_freq'] ?? '')) ?></td>
                            <td><?= esc((string) ($med['no_of_days'] ?? '')) ?></td>
                            <td><?= esc((string) ($med['qty'] ?? '')) ?></td>
                            <td><?= esc((string) ($med['remark'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if (!empty($rx_investigations ?? [])) : ?>
            <div class="rx-block-title">Investigations</div>
            <table class="rx-table">
                <thead>
                    <tr>
                        <th style="width:150px;">Code</th>
                        <th>Investigation Name</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($rx_investigations ?? []) as $inv) : ?>
                        <tr>
                            <td><?= esc((string) ($inv['investigation_code'] ?? '')) ?></td>
                            <td><?= esc((string) ($inv['investigation_name'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if (!empty($rx_advices ?? [])) : ?>
            <div class="rx-block-title">Advice List</div>
            <table class="rx-table">
                <thead>
                    <tr>
                        <th>Advice</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($rx_advices ?? []) as $adv) : ?>
                        <tr>
                            <td>
                                <?php $advLocal = trim((string) ($adv['advice_hindi'] ?? ($adv['advice_txt_hindi'] ?? ''))); ?>
                                <?= esc($advLocal !== '' ? $advLocal : (string) ($adv['advice_txt'] ?? ($adv['advice'] ?? ''))) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</section>
</body>
</html>
