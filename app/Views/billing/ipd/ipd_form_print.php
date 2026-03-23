<?php
$formNo = (int) ($form_no ?? 1);
$formMeta = is_array($form_meta ?? null) ? $form_meta : ['title' => 'IPD Form'];
$ipd = $ipd_info ?? null;
$person = $person_info ?? null;
$doctorList = is_array($doctor_names ?? null) ? $doctor_names : [];
$doctorText = ! empty($doctorList) ? implode(', ', $doctorList) : '-';
$insuranceText = trim((string) ($insurance_name ?? ''));
$hospitalName = trim((string) ($hospital_name ?? 'Hospital'));
$hospitalAddress = trim((string) ($hospital_address ?? ''));
$generatedAt = (string) ($generated_at ?? date('d-m-Y h:i A'));

$patientName = trim((string) (($person->title ?? '') . ' ' . ($person->p_fname ?? '')));
if ($patientName === '') {
    $patientName = '-';
}
$patientAge = (string) ($person->age ?? '-');
$patientGender = (string) ($person->xgender ?? '-');
$patientCode = (string) ($person->p_code ?? '-');
$ipdCode = (string) ($ipd->ipd_code ?? '-');
$admitDate = (string) ($ipd->str_register_date ?? '');
if ($admitDate === '') {
    $admitDate = (string) ($ipd->register_date ?? '-');
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111;
            line-height: 1.35;
        }

        .doc-header {
            border-bottom: 1px solid #999;
            margin-bottom: 8px;
            padding-bottom: 6px;
        }

        .doc-title {
            font-size: 16px;
            font-weight: 700;
            margin: 0 0 2px;
        }

        .doc-subtitle {
            margin: 0;
            font-size: 11px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .meta-table td {
            border: 1px solid #bbb;
            padding: 4px 6px;
            vertical-align: top;
        }

        .section-title {
            font-size: 12px;
            font-weight: 700;
            margin: 10px 0 6px;
            text-transform: uppercase;
        }

        .grid-table {
            width: 100%;
            border-collapse: collapse;
        }

        .grid-table th,
        .grid-table td {
            border: 1px solid #888;
            padding: 4px;
            vertical-align: top;
        }

        .spacer-row td {
            height: 24px;
        }

        .sign-row {
            margin-top: 20px;
            width: 100%;
            border-collapse: collapse;
        }

        .sign-row td {
            width: 50%;
            padding-top: 20px;
            font-size: 10px;
        }

        .sticker-wrap {
            border: 1px dashed #444;
            padding: 4px;
            margin: 0;
            page-break-inside: avoid;
        }

        .sticker-title {
            margin: 0 0 3px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .tiny {
            font-size: 9px;
        }
    </style>
</head>
<body>
<?php if ($formNo === 10 || $formNo === 11) : ?>
    <div class="sticker-wrap">
        <p class="sticker-title"><?= esc($hospitalName) ?></p>
        <div class="tiny">
            <?php if ($hospitalAddress !== '') : ?>
                <div><?= esc($hospitalAddress) ?></div>
            <?php endif; ?>
            <div><strong>Patient:</strong> <?= esc($patientName) ?></div>
            <div><strong>Age/Gender:</strong> <?= esc($patientAge) ?> / <?= esc($patientGender) ?></div>
            <div><strong>UHID:</strong> <?= esc($patientCode) ?></div>
            <div><strong>IPD:</strong> <?= esc($ipdCode) ?></div>
            <div><strong>Doctor:</strong> <?= esc($doctorText) ?></div>
            <div><strong>Admit:</strong> <?= esc($admitDate) ?></div>
            <?php if ($insuranceText !== '') : ?>
                <div><strong>Insurance:</strong> <?= esc($insuranceText) ?></div>
            <?php endif; ?>
        </div>
    </div>
<?php else : ?>
    <div class="doc-header">
        <p class="doc-title"><?= esc($hospitalName) ?></p>
        <?php if ($hospitalAddress !== '') : ?>
            <p class="doc-subtitle"><?= esc($hospitalAddress) ?></p>
        <?php endif; ?>
        <p class="doc-subtitle"><strong><?= esc((string) ($formMeta['title'] ?? 'IPD Form')) ?></strong></p>
    </div>

    <table class="meta-table">
        <tr>
            <td><strong>Patient Name:</strong> <?= esc($patientName) ?></td>
            <td><strong>Age / Gender:</strong> <?= esc($patientAge) ?> / <?= esc($patientGender) ?></td>
            <td><strong>UHID:</strong> <?= esc($patientCode) ?></td>
        </tr>
        <tr>
            <td><strong>IPD Code:</strong> <?= esc($ipdCode) ?></td>
            <td><strong>Date of Admission:</strong> <?= esc($admitDate) ?></td>
            <td><strong>Doctor:</strong> <?= esc($doctorText) ?></td>
        </tr>
        <tr>
            <td colspan="3">
                <strong>Insurance:</strong> <?= esc($insuranceText !== '' ? $insuranceText : 'Direct') ?>
                <span style="float:right;"><strong>Generated:</strong> <?= esc($generatedAt) ?></span>
            </td>
        </tr>
    </table>

    <?php if ($formNo === 1) : ?>
        <div class="section-title">Patient Consent</div>
        <p>
            I willingly agree for treatment, examination, consultation, investigations, medication, and medically indicated procedures during this IPD admission.
            I confirm that the information shared with the treating team is true and complete to the best of my knowledge.
        </p>
        <p>
            I understand that the hospital may take emergency decisions in the patient's best interest when immediate care is required.
            I also agree to follow hospital rules regarding safety, infection control, and billing process.
        </p>
        <table class="sign-row">
            <tr>
                <td>Patient / Attendant Signature: ____________________</td>
                <td style="text-align:right;">Hospital Representative: ____________________</td>
            </tr>
        </table>
    <?php elseif ($formNo === 3) : ?>
        <div class="section-title">Self Declaration</div>
        <p>
            I, ____________________, declare that patient <?= esc($patientName) ?> is admitted under IPD code <?= esc($ipdCode) ?>
            on date <?= esc($admitDate) ?>.
            <?php if ($insuranceText !== '') : ?>
                The patient is covered under insurance provider <?= esc($insuranceText) ?>.
            <?php else : ?>
                This admission is under self / direct payment category.
            <?php endif; ?>
        </p>
        <p>
            I confirm that all submitted details and documents are genuine and I will remain responsible for any differences in authorization, exclusions, or final bill settlement.
        </p>
        <table class="sign-row">
            <tr>
                <td>Declarant Signature: ____________________</td>
                <td style="text-align:right;">Date: ____________________</td>
            </tr>
        </table>
    <?php elseif ($formNo === 5) : ?>
        <div class="section-title">Admission Assessment</div>
        <table class="grid-table">
            <tr><th style="width:35%;">Assessment Item</th><th>Observation</th></tr>
            <tr class="spacer-row"><td>Chief Complaint</td><td></td></tr>
            <tr class="spacer-row"><td>History of Present Illness</td><td></td></tr>
            <tr class="spacer-row"><td>Past Medical / Surgical History</td><td></td></tr>
            <tr class="spacer-row"><td>Allergy History</td><td></td></tr>
            <tr class="spacer-row"><td>Medication History</td><td></td></tr>
            <tr class="spacer-row"><td>Physical Examination</td><td></td></tr>
            <tr class="spacer-row"><td>Provisional Diagnosis</td><td></td></tr>
            <tr class="spacer-row"><td>Initial Plan</td><td></td></tr>
        </table>
    <?php elseif ($formNo === 8) : ?>
        <div class="section-title">Progress Notes and Doctor Order</div>
        <table class="grid-table">
            <tr>
                <th style="width:14%;">Date</th>
                <th style="width:14%;">Time</th>
                <th>Progress Notes / Orders</th>
                <th style="width:18%;">Doctor Sign</th>
            </tr>
            <?php for ($i = 0; $i < 18; $i++) : ?>
                <tr class="spacer-row">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            <?php endfor; ?>
        </table>
    <?php elseif ($formNo === 9) : ?>
        <div class="section-title">Fluid In / Out</div>
        <table class="grid-table">
            <tr>
                <th style="width:10%;">S.No.</th>
                <th style="width:20%;">Date / Time</th>
                <th style="width:20%;">Fluid In (ml)</th>
                <th style="width:20%;">Fluid Out (ml)</th>
                <th>Remark</th>
            </tr>
            <?php for ($i = 1; $i <= 24; $i++) : ?>
                <tr class="spacer-row">
                    <td style="text-align:center;"><?= $i ?></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            <?php endfor; ?>
            <tr>
                <td colspan="2" style="text-align:right;"><strong>Total</strong></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </table>
    <?php endif; ?>
<?php endif; ?>
</body>
</html>
