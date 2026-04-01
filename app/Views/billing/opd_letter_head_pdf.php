<?php
$patient = $patient_master[0] ?? null;
$opd = $opd_master[0] ?? null;
$doctor = $doctor_master[0] ?? null;
$rx = $opd_prescription ?? [];

$hospitalName = defined('H_Name') ? (string) constant('H_Name') : 'Hospital';
$hospitalAddress1 = defined('H_address_1') ? (string) constant('H_address_1') : '';
$hospitalAddress2 = defined('H_address_2') ? (string) constant('H_address_2') : '';
$hospitalPhone = defined('H_phone_No') ? (string) constant('H_phone_No') : '';
$hospitalEmail = defined('H_Email') ? (string) constant('H_Email') : '';

$doctorName = trim((string) ($doctor->p_fname ?? ''));
$doctorDegree = trim((string) ($doctor->qualification ?? ($doctor->degree ?? '')));
$doctorSpec = trim((string) ($doctor->SpecName ?? ''));

$doctorRegNo = '';
if ($doctor !== null) {
    foreach (['nmc_reg_no', 'mci_reg_no', 'registration_no', 'reg_no', 'doctor_reg_no', 'doc_reg_no', 'council_reg_no'] as $field) {
        if (isset($doctor->{$field}) && trim((string) $doctor->{$field}) !== '') {
            $doctorRegNo = trim((string) $doctor->{$field});
            break;
        }
    }

    if ($doctorRegNo === '') {
        $shortDescription = trim((string) ($doctor->doc_sign ?? ''));
        if ($shortDescription !== '') {
            $normalizedShortDescription = preg_replace('/\s+/', ' ', $shortDescription);
            if ($normalizedShortDescription !== null) {
                if (preg_match('/(?:reg(?:istration)?\s*(?:no|number)?\s*[:\-]?\s*)([A-Z0-9\/-]{4,})/i', $normalizedShortDescription, $matches) === 1 && ! empty($matches[1])) {
                    $doctorRegNo = trim((string) $matches[1]);
                }
            }
        }
    }
}

$vitals = [];
if (trim((string) ($rx['bp'] ?? '')) !== '') {
    $vitals[] = 'BP: ' . trim((string) ($rx['bp'] ?? '')) . (trim((string) ($rx['diastolic'] ?? '')) !== '' ? '/' . trim((string) ($rx['diastolic'] ?? '')) : '');
}
if (trim((string) ($rx['pulse'] ?? '')) !== '') {
    $vitals[] = 'Pulse: ' . trim((string) ($rx['pulse'] ?? '')) . '/min';
}
if (trim((string) ($rx['temp'] ?? '')) !== '') {
    $vitals[] = 'Temp: ' . trim((string) ($rx['temp'] ?? ''));
}
if (trim((string) ($rx['spo2'] ?? '')) !== '') {
    $vitals[] = 'SpO2: ' . trim((string) ($rx['spo2'] ?? '')) . '%';
}

$visitDate = (string) ($opd->str_apointment_date ?? '');
$visitDateTime = '';
if (!empty($opd->apointment_date ?? '')) {
    $ts = strtotime((string) $opd->apointment_date);
    if ($ts !== false) {
        $visitDateTime = date('d-m-Y h:i A', $ts);
    }
}
$validUpto = (string) ($opd->opd_Exp_Date ?? '');
$nextVisit = trim((string) ($rx['next_visit'] ?? ''));
$investigationText = trim((string) ($rx['investigation'] ?? ''));
$printedOn = date('d-m-Y h:i A');
$layoutMode = strtolower((string) ($print_layout_mode ?? 'full'));
$showPart1 = in_array($layoutMode, ['full', 'header_meta'], true);
$showPart2 = in_array($layoutMode, ['full', 'meta_content', 'header_meta', 'meta_only'], true);
$showPart3 = in_array($layoutMode, ['full', 'meta_content', 'content_only'], true);
$topSpacerMm = in_array($layoutMode, ['content_only', 'meta_content', 'meta_only'], true) ? 22 : 0;
$drugAllergyStatus = trim((string) ($rx['drug_allergy_status'] ?? ''));
$drugAllergyDetails = trim((string) ($rx['drug_allergy_details'] ?? ''));
$adrHistory = trim((string) ($rx['adr_history'] ?? ''));
$currentMedications = trim((string) ($rx['current_medications'] ?? ''));
$painScaleMap = [
    '0' => 'No Pain',
    '1' => 'Mild Pain',
    '2' => 'Moderate',
    '3' => 'Intense',
    '4' => 'Worst Pain Possible',
];
$painValue = trim((string) ($rx['pain_value'] ?? ''));
$painLabel = $painScaleMap[$painValue] ?? '';

$toLocalText = static function (string $input): string {
    $text = trim($input);
    if ($text === '') {
        return '';
    }

    $map = [
        // Dose schedule phrases
        'one time a day' => 'दिन में एक बार',
        'two times a day' => 'दिन में दो बार',
        'three times a day' => 'दिन में तीन बार',
        'four times a day' => 'दिन में चार बार',
        'twice a day' => 'दिन में दो बार',
        'thrice a day' => 'दिन में तीन बार',
        'twice daily' => 'दिन में दो बार',
        'thrice daily' => 'दिन में तीन बार',
        'once daily' => 'दिन में एक बार',
        'alternate day' => 'एक दिन छोड़कर',
        'every 4 hours' => 'हर 4 घंटे',
        'every 6 hours' => 'हर 6 घंटे',
        'every 8 hours' => 'हर 8 घंटे',
        'every 12 hours' => 'हर 12 घंटे',
        'at bed time' => 'सोते समय',
        'at bedtime' => 'सोते समय',
        'at sleep' => 'सोते समय',
        'empty stomach' => 'खाली पेट',
        'with food' => 'भोजन के साथ',
        'with water' => 'पानी के साथ',
        'with milk' => 'दूध के साथ',
        'before meal' => 'भोजन से पहले',
        'after meal' => 'भोजन के बाद',
        'before food' => 'भोजन से पहले',
        'after food' => 'भोजन के बाद',
        'after breakfast' => 'नाश्ते के बाद',
        'before breakfast' => 'नाश्ते से पहले',
        'after lunch' => 'दोपहर के भोजन के बाद',
        'before lunch' => 'दोपहर के भोजन से पहले',
        'after dinner' => 'रात के भोजन के बाद',
        'before dinner' => 'रात के भोजन से पहले',
        'as required' => 'जरूरत पड़ने पर',
        'as needed' => 'जरूरत पड़ने पर',
        'when required' => 'जरूरत पड़ने पर',
        'morning' => 'सुबह',
        'afternoon' => 'दोपहर',
        'evening' => 'शाम',
        'night' => 'रात',
        'daily' => 'रोज',
        'weekly' => 'साप्ताहिक',
        'monthly' => 'मासिक',
        'for' => 'के लिए',
        'days' => 'दिन',
        'day' => 'दिन',
        'tab' => 'टैब',
        'cap' => 'कैप',
        'syp' => 'सिरप',
        'inj' => 'इंजेक्शन',
        'apply' => 'लगाएं',
        'continue' => 'जारी रखें',
        'rest' => 'आराम करें',
        'hydrate' => 'पर्याप्त पानी लें',
        'avoid oily food' => 'तैलीय भोजन से बचें',
        'follow up' => 'फॉलो-अप करें',
        'review warning signs' => 'चेतावनी के संकेतों पर ध्यान दें',
        'warning signs' => 'चेतावनी के संकेत',
        'if symptoms persist' => 'यदि लक्षण बने रहें',
        'if symptoms worsen' => 'यदि लक्षण बिगड़ें',
        'follow-up' => 'फॉलो-अप',
        'bed rest' => 'बिस्तर पर आराम',
        'light diet' => 'हल्का भोजन',
        'plenty of water' => 'पर्याप्त पानी पियें',
        'plenty of fluids' => 'पर्याप्त तरल पदार्थ',
        'drink water' => 'पानी पियें',
        'avoid cold' => 'ठंड से बचें',
        'no smoking' => 'धूम्रपान न करें',
        'avoid alcohol' => 'शराब से बचें',
        'take medicine regularly' => 'नियमित दवाई लें',
        'low salt diet' => 'कम नमक का भोजन',
        'low sugar diet' => 'कम चीनी का भोजन',
        'exercise regularly' => 'नियमित व्यायाम करें',
        'avoid stress' => 'तनाव से बचें',
        'wear mask' => 'मास्क पहनें',
        'wash hands' => 'हाथ धोएं',
        'review' => 'पुनः जांच',
    ];

    $translated = $text;
    uksort($map, static function ($a, $b) {
        return strlen((string) $b) <=> strlen((string) $a);
    });
    foreach ($map as $en => $local) {
        $translated = preg_replace('/\\b' . preg_quote($en, '/') . '\\b/i', $local, $translated) ?? $translated;
    }

    return $translated;
};
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>OPD Consult Note</title>
    <style>
        body { font-family: dejavusans, sans-serif; font-size: 11px; color: #111; line-height: 1.5; }
        .wrap { width: 100%; margin-top: <?= (int) $topSpacerMm ?>mm; }
        .top-head { width: 100%; border-bottom: 1px solid #666; padding-bottom: 5px; margin-bottom: 8px; }
        .top-head td { vertical-align: top; }
        .clinic-name { font-size: 19px; font-weight: 700; }
        .clinic-sub { font-size: 10px; }
        .doctor-name { font-size: 17px; font-weight: 700; text-align: right; }
        .doctor-sub { font-size: 11px; text-align: right; }
        .meta { width: 100%; border-bottom: 1px solid #999; padding-bottom: 6px; margin-bottom: 10px; }
        .meta td { width: 33.3%; vertical-align: top; padding-right: 8px; }
        .label { font-weight: 700; }
        .section-line { margin: 6px 0; }
        .rx-title { font-size: 34px; font-weight: 700; margin: 8px 0 4px 0; }
        .rx-table { width: 100%; border-collapse: collapse; margin-top: 2px; }
        .rx-table th { text-align: left; border-bottom: 1px solid #444; font-size: 11px; padding: 2px 3px; }
        .rx-table td { border-bottom: 1px solid #bbb; padding: 2px 3px; vertical-align: top; }
        .sub-line { font-size: 9.5px; color: #444; margin-top: 1px; }
        .local-line { font-size: 11px; color: #111; margin-top: 2px; }
        .small-gap { margin-top: 8px; }
        .signature { margin-top: 28px; text-align: right; }
        .signature .name { font-size: 15px; font-weight: 700; }
        .muted { color: #666; }
        .warn { color: #8b0000; font-weight: 700; }
        .footer-note { margin-top: 12px; border-top: 1px solid #999; padding-top: 5px; font-size: 9.5px; }
    </style>
</head>
<body>
<div class="wrap">
    <?php if ($showPart1) : ?>
    <table class="top-head" cellspacing="0" cellpadding="0">
        <tr>
            <td style="width:66%;">
                <div class="clinic-name"><?= esc($hospitalName) ?></div>
                <?php if ($hospitalAddress1 !== '' || $hospitalAddress2 !== '') : ?>
                    <div class="clinic-sub"><?= esc(trim($hospitalAddress1 . ', ' . $hospitalAddress2, ', ')) ?></div>
                <?php endif; ?>
                <?php if ($hospitalPhone !== '') : ?>
                    <div class="clinic-sub">Phone: <?= esc($hospitalPhone) ?></div>
                <?php endif; ?>
            </td>
            <td style="width:34%;">
                <?php if ($doctorName !== '') : ?>
                    <div class="doctor-name">Dr. <?= esc($doctorName) ?></div>
                <?php endif; ?>
                <?php if ($doctorDegree !== '') : ?>
                    <div class="doctor-sub"><?= esc($doctorDegree) ?></div>
                <?php endif; ?>
                <?php if ($doctorSpec !== '') : ?>
                    <div class="doctor-sub"><?= esc($doctorSpec) ?></div>
                <?php endif; ?>
                <div class="doctor-sub">
                    <span class="label">Reg. No.:</span>
                    <?php if ($doctorRegNo !== '') : ?>
                        <?= esc($doctorRegNo) ?>
                    <?php else : ?>
                        <span class="warn">ADD_DOCTOR_REG_NO</span>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    </table>
    <?php endif; ?>

    <?php if ($showPart2) : ?>
    <table class="meta" cellspacing="0" cellpadding="0">
        <tr>
            <td>
                <div><span class="label">Name :</span> <?= esc(trim((string) (($patient->title ?? '') . ' ' . ($patient->p_fname ?? '')))) ?></div>
                <div><?= esc(trim((string) (($patient->p_relative ?? '') . ' ' . ($patient->p_rname ?? '')))) ?></div>
                <div><span class="label">Gender/Age :</span> <?= esc((string) ($patient->age ?? '')) ?> Year / <?= esc((string) ($patient->xgender ?? '')) ?></div>
                <div><span class="label">Mob :</span> <?= esc((string) ($patient->mphone1 ?? '')) ?></div>
                <div><span class="label">Address :</span> <?= esc(trim((string) (($patient->add1 ?? '') . ' ' . ($patient->city ?? '')))) ?></div>
            </td>
            <td>
                <div><span class="label">UHID :</span> <?= esc((string) ($patient->p_code ?? '')) ?></div>
                <div><span class="label">Sr No. :</span> <?= esc((string) ($opd->opd_no ?? '')) ?></div>
                <div><span class="label">OPD No. :</span> <?= esc((string) ($opd->opd_code ?? '')) ?></div>
                <div><span class="label">Date :</span> <?= esc($visitDate) ?></div>
                <?php if ($visitDateTime !== '') : ?>
                    <div><span class="label">Date & Time :</span> <?= esc($visitDateTime) ?></div>
                <?php endif; ?>
                <div><span class="label">Valid Upto :</span> <?= esc($validUpto) ?></div>
            </td>
            <td>
                <div><span class="label">DEPARTMENT :</span></div>
                <div><?= esc($doctorSpec !== '' ? $doctorSpec : '-') ?></div>
                <div><span class="label">No. of Visit :</span> 1</div>
                <div><span class="label">Last Visit :</span> <?= esc($visitDate) ?></div>
                <div><span class="label">Book Time :</span> <?= esc($visitDateTime !== '' ? $visitDateTime : $visitDate) ?></div>
            </td>
        </tr>
    </table>
    <?php endif; ?>

    <?php if ($showPart3) : ?>
    <?php if (!empty($rx['complaints'] ?? '')) : ?>
        <div class="section-line"><span class="label">Complaint :</span> <?= nl2br(esc((string) ($rx['complaints'] ?? ''))) ?></div>
    <?php endif; ?>

    <?php if (!empty($vitals)) : ?>
        <div class="section-line"><span class="label">Vitals :</span> <?= esc(implode(' | ', $vitals)) ?></div>
    <?php endif; ?>

    <?php if ($painLabel !== '') : ?>
        <div class="section-line"><span class="label">Pain Measurement Scale :</span> <?= esc($painLabel) ?><?= $painValue !== '' ? ' (' . esc($painValue) . ')' : '' ?></div>
    <?php endif; ?>

    <?php if (!empty($rx['Finding_Examinations'] ?? '')) : ?>
        <div class="section-line"><span class="label">Finding :</span> <?= nl2br(esc((string) ($rx['Finding_Examinations'] ?? ''))) ?></div>
    <?php endif; ?>

    <?php if ($drugAllergyStatus !== '' || $drugAllergyDetails !== '' || $adrHistory !== '' || $currentMedications !== '') : ?>
        <div class="section-line"><span class="label">Drug Allergy Status :</span> <?= esc($drugAllergyStatus !== '' ? $drugAllergyStatus : 'Not Documented') ?></div>
        <?php if ($drugAllergyDetails !== '') : ?>
            <div class="section-line"><span class="label">Drug Allergy Details :</span> <?= esc($drugAllergyDetails) ?></div>
        <?php endif; ?>
        <?php if ($adrHistory !== '') : ?>
            <div class="section-line"><span class="label">ADR History :</span> <?= esc($adrHistory) ?></div>
        <?php endif; ?>
        <?php if ($currentMedications !== '') : ?>
            <div class="section-line"><span class="label">Current Medications :</span> <?= esc($currentMedications) ?></div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($rx['Provisional_diagnosis'] ?? '')) : ?>
        <div class="section-line"><span class="label">Diagnosis :</span> <?= nl2br(esc((string) ($rx['Provisional_diagnosis'] ?? ''))) ?></div>
    <?php elseif (!empty($rx['diagnosis'] ?? '')) : ?>
        <div class="section-line"><span class="label">Diagnosis :</span> <?= nl2br(esc((string) ($rx['diagnosis'] ?? ''))) ?></div>
    <?php endif; ?>

    <div class="rx-title">Rx :</div>
    <?php if (!empty($rx_medicines ?? [])) : ?>
        <table class="rx-table" cellspacing="0" cellpadding="0">
            <thead>
            <tr>
                <th style="width:6%;">#</th>
                <th style="width:43%;">Medicine Name</th>
                <th style="width:24%;">Dose / Frequency</th>
                <th style="width:11%;">Qty</th>
                <th style="width:10%;">Day</th>
                <th style="width:20%;">Instruction</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach (($rx_medicines ?? []) as $idx => $med) : ?>
                <?php
                $dosage = trim((string) ($med['dosage'] ?? ''));
                $when = trim((string) ($med['dosage_when'] ?? ''));
                $freq = trim((string) ($med['dosage_freq'] ?? ''));
                $doseText = trim($dosage . ($freq !== '' ? ' / ' . $freq : ''));
                $genericText = trim((string) ($med['genericname'] ?? ($med['generic_name'] ?? ($med['salt_name'] ?? ''))));
                $instruction = trim(($when !== '' ? $when : '') . ((trim((string) ($med['remark'] ?? '')) !== '') ? (' | ' . trim((string) ($med['remark'] ?? ''))) : ''));
                $doseLocal = $toLocalText(trim($doseText . ($when !== '' ? ' ' . $when : '')));
                $instructionLocal = $toLocalText($instruction);
                ?>
                <tr>
                    <td><?= esc((string) ($idx + 1)) ?></td>
                    <td>
                        <div><?= esc((string) ($med['med_name'] ?? '')) ?></div>
                        <?php if ($genericText !== '') : ?>
                            <div class="sub-line">Salt/Generic: <?= esc($genericText) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div><?= esc($doseText) ?></div>
                        <?php if ($doseLocal !== '' && strtolower($doseLocal) !== strtolower(trim($doseText . ($when !== '' ? ' ' . $when : '')))) : ?>
                            <div class="local-line" lang="hi"><?= esc($doseLocal) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= esc((string) ($med['qty'] ?? '')) ?></td>
                    <td><?= esc((string) ($med['no_of_days'] ?? '')) ?></td>
                    <td>
                        <div><?= esc($instruction) ?></div>
                        <?php if ($instructionLocal !== '' && strtolower($instructionLocal) !== strtolower($instruction)) : ?>
                            <div class="local-line" lang="hi"><?= esc($instructionLocal) ?></div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <div class="muted">No medicines prescribed.</div>
    <?php endif; ?>

    <?php
    $investigationList = [];
    foreach (($rx_investigations ?? []) as $inv) {
        $name = trim((string) ($inv['investigation_name'] ?? ''));
        if ($name !== '') {
            $investigationList[] = $name;
        }
    }
    if ($investigationText !== '') {
        $investigationList[] = $investigationText;
    }
    $investigationList = array_values(array_unique($investigationList));
    ?>

    <?php if (!empty($investigationList)) : ?>
        <div class="section-line small-gap"><span class="label">Investigation Advised :</span> <?= esc(implode(', ', $investigationList)) ?></div>
    <?php endif; ?>

    <?php if (!empty($rx_advices ?? [])) : ?>
        <?php
            $adviceLine = [];
            $adviceLocalLine = [];
            foreach (($rx_advices ?? []) as $adv) {
                $t = trim((string) ($adv['advice_txt'] ?? ($adv['advice'] ?? '')));
                if ($t === '') {
                    continue;
                }
                $adviceLine[] = $t;

                $localStored = trim((string) ($adv['advice_hindi'] ?? ($adv['advice_txt_hindi'] ?? '')));
                if ($localStored !== '') {
                    $adviceLocalLine[] = $localStored;
                } else {
                    $fallbackLocal = $toLocalText($t);
                    if ($fallbackLocal !== '' && strtolower($fallbackLocal) !== strtolower($t)) {
                        $adviceLocalLine[] = $fallbackLocal;
                    }
                }
            }
        ?>
        <?php if (!empty($adviceLine)) : ?>
            <?php $adviceEng = implode(' | ', $adviceLine); ?>
            <?php if (!empty($adviceLocalLine)) : ?>
                <div class="section-line" lang="hi"><span class="label">सलाह :</span> <?= esc(implode(' | ', $adviceLocalLine)) ?></div>
                <div style="font-size:9.5px; color:#666; margin-top:1px;">(Advice: <?= esc($adviceEng) ?>)</div>
            <?php else : ?>
                <div class="section-line"><span class="label">सलाह (Advice) :</span> <?= esc($adviceEng) ?></div>
            <?php endif; ?>
        <?php endif; ?>
    <?php elseif (!empty($rx['advice'] ?? '')) : ?>
        <?php $adviceEng = (string) ($rx['advice'] ?? ''); $adviceLocal = $toLocalText($adviceEng); ?>
        <?php if ($adviceLocal !== '' && strtolower($adviceLocal) !== strtolower(trim($adviceEng))) : ?>
            <div class="section-line" lang="hi"><span class="label">सलाह :</span> <?= nl2br(esc($adviceLocal)) ?></div>
            <div style="font-size:9.5px; color:#666; margin-top:1px;">(Advice: <?= nl2br(esc($adviceEng)) ?>)</div>
        <?php else : ?>
            <div class="section-line"><span class="label">सलाह (Advice) :</span> <?= nl2br(esc($adviceEng)) ?></div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($nextVisit !== '') : ?>
        <div class="section-line small-gap"><span class="label">Next Visit :</span> <?= esc($nextVisit) ?></div>
    <?php endif; ?>
    <?php endif; ?>

    <?php if ($layoutMode === 'header_meta') : ?>
        <div class="section-line small-gap">BP : __________ &nbsp;&nbsp; Pulse Rate : __________ &nbsp;&nbsp; SPO2 : __________ &nbsp;&nbsp; Ht : __________ &nbsp;&nbsp; Wt. : __________ &nbsp;&nbsp; RBS : __________</div>
    <?php endif; ?>

    <?php if ($showPart3) : ?>
        <div class="signature">
            <?php if ($doctorName !== '') : ?>
                <div class="name">Dr. <?= esc($doctorName) ?></div>
            <?php endif; ?>
            <?php if ($doctorDegree !== '') : ?>
                <div><?= esc($doctorDegree) ?></div>
            <?php endif; ?>
            <?php if ($doctorSpec !== '') : ?>
                <div><?= esc($doctorSpec) ?></div>
            <?php endif; ?>
            <div>
                <span class="label">Reg. No.:</span>
                <?php if ($doctorRegNo !== '') : ?>
                    <?= esc($doctorRegNo) ?>
                <?php else : ?>
                    <span class="warn">ADD_DOCTOR_REG_NO</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="footer-note">
            <div><span class="label">Prescription Generated:</span> <?= esc($printedOn) ?></div>
            <div class="muted">Please ensure doctor registration number and signature/stamp are present for regulatory compliance.</div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
