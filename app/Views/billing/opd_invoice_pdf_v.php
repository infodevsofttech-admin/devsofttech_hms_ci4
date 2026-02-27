<?php
$opd = $opd_master[0] ?? null;
$patient = $patient_master[0] ?? null;
$insuranceRow = $insurance[0] ?? null;
$caseRow = $case_master[0] ?? null;

$hospitalName = defined('H_Name') ? (string) constant('H_Name') : 'Hospital';
$hospitalAddress1 = defined('H_address_1') ? (string) constant('H_address_1') : '';
$hospitalAddress2 = defined('H_address_2') ? (string) constant('H_address_2') : '';
$hospitalPhone = defined('H_phone_No') ? (string) constant('H_phone_No') : '';
$hospitalEmail = defined('H_Email') ? (string) constant('H_Email') : '';
$hospitalLogoName = defined('H_logo') ? trim((string) constant('H_logo')) : '';

$hospitalNameLen = function_exists('mb_strlen') ? mb_strlen($hospitalName) : strlen($hospitalName);
$hospitalNameFontSize = 20;
if ($hospitalNameLen > 42) {
    $hospitalNameFontSize = 14;
} elseif ($hospitalNameLen > 36) {
    $hospitalNameFontSize = 16;
} elseif ($hospitalNameLen > 30) {
    $hospitalNameFontSize = 18;
}

$invoiceId = (string) ($opd->opd_code ?? '');
$appointmentDate = (string) ($opd->str_apointment_date ?? '');
$doctorName = trim((string) ($opd->doc_name ?? ''));
$department = trim((string) ($opd->doc_spec ?? ''));
$description = trim((string) ($opd->opd_fee_desc ?? ''));
$grossAmount = (float) ($opd->opd_fee_gross_amount ?? $opd->opd_fee_amount ?? 0);
$discount = (float) ($opd->opd_discount ?? 0);
$netAmount = (float) ($opd->opd_fee_amount ?? 0);
$paymentMode = trim((string) ($opd->payment_mode_desc ?? $opd->Payment_type_str ?? ''));
$printedOn = date('d-m-Y h:i:s A');
$invoiceStatus = ((int) ($opd->opd_status ?? 0) === 3) ? 'Cancelled' : (($paymentMode !== '' && strtolower($paymentMode) !== 'pending') ? 'Paid' : 'Pending');
$currencyPrefix = 'Rs.';

$logoPathCandidates = [];
if ($hospitalLogoName !== '') {
    $logoPathCandidates[] = FCPATH . 'assets/images/' . $hospitalLogoName;
    $logoPathCandidates[] = FCPATH . 'assets/img/' . $hospitalLogoName;
}
$logoPathCandidates[] = FCPATH . 'assets/img/logo.png';

$logoSrc = '';
foreach ($logoPathCandidates as $candidate) {
    if (is_file($candidate)) {
        $logoSrc = str_replace('\\', '/', $candidate);
        break;
    }
}

$qrParts = [
    'Hospital:' . $hospitalName,
    'HospitalPhone:' . $hospitalPhone,
    'Invoice:' . ($invoiceId !== '' ? $invoiceId : ('OPD-' . (string) ($opd->opd_id ?? ''))),
    'Patient:' . (string) ($patient->p_code ?? ''),
    'Name:' . trim((string) (($patient->title ?? '') . ' ' . ($patient->p_fname ?? ''))),
    'Date:' . $appointmentDate,
    'Net:' . number_format($netAmount, 2, '.', ''),
    'PaymentID:' . (string) ($opd->payment_id ?? ''),
    'Status:' . $invoiceStatus,
];
$qrContent = implode(' | ', array_filter($qrParts, static fn ($v) => trim((string) $v) !== ''));
$qrPackageAvailable = class_exists('\\Mpdf\\QrCode\\QrCode');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>OPD Invoice</title>
    <style>
        body { font-family: dejavusans, freeserif, sans-serif; font-size: 10.5px; color: #111; }
        .wrap { width: 100%; }
        .top { width: 100%; border-bottom: 2px solid #1f2d3a; padding-bottom: 9px; margin-bottom: 10px; }
        .top td { vertical-align: top; }
        .top-logo { text-align: left; }
        .logo-img { max-width: 92px; max-height: 92px; }
        .hospital-name { font-weight: 700; color: #1f2d3a; letter-spacing: 0.2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .hospital-line { font-size: 9.8px; color: #334; line-height: 1.45; }
        .invoice-title { text-align: right; font-size: 20px; font-weight: 700; color: #1f2d3a; letter-spacing: 0.4px; }
        .invoice-sub { text-align: right; font-size: 9.6px; color: #556; margin-top: 2px; }
        .invoice-status { text-align: right; margin-top: 5px; }
        .status-chip { display: inline-block; border: 1px solid #8ea2b7; background: #eef3f8; color: #2b3a4b; padding: 2px 8px; font-size: 9.6px; font-weight: 700; border-radius: 10px; }
        .qr-cell { text-align: right; }
        .qr-block { display: inline-block; text-align: center; margin-top: 2px; }
        .qr-label { font-size: 8.8px; color: #5a6776; margin-top: 2px; }
        .meta { width: 100%; margin-bottom: 8px; border-collapse: separate; border-spacing: 0; }
        .meta td { vertical-align: top; padding: 7px 8px; border: 1px solid #d6dee8; }
        .meta-three td { width: 33.33%; }
        .meta-two td { width: 50%; }
        .meta-title { font-size: 10.5px; font-weight: 700; color: #1f2d3a; margin-bottom: 4px; text-transform: uppercase; }
        .line { margin-bottom: 2px; }
        .label { font-weight: 700; color: #1c2a39; }
        .charges { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .charges th { background: #edf3f9; border: 1px solid #c5d1de; font-size: 10px; padding: 7px 6px; text-align: left; text-transform: uppercase; }
        .charges td { border: 1px solid #d3dde8; padding: 7px 6px; }
        .amount { text-align: right; white-space: nowrap; }
        .totals-wrap { width: 100%; margin-top: 10px; }
        .totals-left { width: 58%; vertical-align: top; font-size: 9.6px; color: #4b5968; }
        .totals-right { width: 42%; vertical-align: top; }
        .summary { width: 100%; border-collapse: collapse; }
        .summary td { border: 1px solid #d0dae5; padding: 6px 8px; }
        .summary .key { background: #f7f9fc; font-weight: 700; color: #2e3f51; }
        .summary .val { text-align: right; width: 36%; font-weight: 700; }
        .summary .net-row .key,
        .summary .net-row .val { background: #edf3f9; font-size: 11px; }
        .footer { margin-top: 16px; border-top: 1px solid #d0dae5; padding-top: 8px; }
        .footer-left { float:left; width:60%; font-size: 9.6px; color: #4c5968; }
        .footer-right { float:right; width:35%; text-align:right; }
        .sign-line { margin-top: 18px; border-top: 1px solid #9aabbd; padding-top: 3px; font-size: 9.8px; color: #2d3c4b; }
    </style>
</head>
<body>
<div class="wrap">
    <table class="top" cellspacing="0" cellpadding="0">
        <tr>
            <td style="width:14%;" class="top-logo">
                <?php if ($logoSrc !== '') : ?>
                    <img class="logo-img" src="<?= esc($logoSrc) ?>" alt="Hospital Logo" />
                <?php endif; ?>
            </td>
            <td style="width:66%;">
                <div class="hospital-name" style="font-size: <?= (int) $hospitalNameFontSize ?>px;"><?= esc($hospitalName) ?></div>
                <?php if ($hospitalAddress1 !== '' || $hospitalAddress2 !== '') : ?>
                    <div class="hospital-line"><?= esc(trim($hospitalAddress1 . ', ' . $hospitalAddress2, ', ')) ?></div>
                <?php endif; ?>
                <?php if ($hospitalPhone !== '' || $hospitalEmail !== '') : ?>
                    <div class="hospital-line">
                        <?= $hospitalPhone !== '' ? ('Phone: ' . esc($hospitalPhone)) : '' ?>
                        <?= ($hospitalPhone !== '' && $hospitalEmail !== '') ? ' | ' : '' ?>
                        <?= $hospitalEmail !== '' ? ('Email: ' . esc($hospitalEmail)) : '' ?>
                    </div>
                <?php endif; ?>
            </td>
            <td style="width:20%;" class="qr-cell">
                <?php if ($qrContent !== '' && $qrPackageAvailable) : ?>
                    <div class="qr-block">
                        <barcode code="<?= esc($qrContent) ?>" size="0.62" type="QR" error="M" class="barcode" />
                        <div class="qr-label">Scan for invoice info</div>
                    </div>
                <?php elseif ($qrContent !== '') : ?>
                    <div class="qr-block">
                        <div class="qr-label">QR unavailable (mpdf/qrcode not installed)</div>
                    </div>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <table class="meta meta-three" cellspacing="0" cellpadding="0">
        <tr>
            <td>
                <div class="meta-title">Patient Details</div>
                <div class="line"><span class="label">Patient ID:</span> <?= esc((string) ($patient->p_code ?? '')) ?></div>
                <div class="line"><span class="label">Name:</span> <?= esc(trim((string) (($patient->title ?? '') . ' ' . ($patient->p_fname ?? '')))) ?></div>
                <div class="line"><span class="label">Gender:</span> <?= esc((string) ($patient->xgender ?? '')) ?></div>
                <div class="line"><span class="label">Age:</span> <?= esc((string) ($patient->age ?? '')) ?></div>
                <div class="line"><span class="label">Phone:</span> <?= esc((string) ($patient->mphone1 ?? '')) ?></div>
            </td>
            <td>
                <div class="meta-title">Visit Details</div>
                <div class="line"><span class="label">Date:</span> <?= esc($appointmentDate) ?></div>
                <div class="line"><span class="label">Doctor:</span> <?= esc($doctorName !== '' ? ('Dr. ' . $doctorName) : '') ?></div>
                <div class="line"><span class="label">Department:</span> <?= esc($department) ?></div>
                <div class="line"><span class="label">Payment No:</span> <?= esc((string) ($opd->payment_id ?? '')) ?></div>
                <div class="line"><span class="label">Visit Count:</span> <?= esc((string) ($opd->no_visit ?? '')) ?></div>
            </td>
            <td>
                <div class="meta-title">Invoice Details</div>
                <div class="line"><span class="label">Invoice ID:</span> <?= esc($invoiceId) ?></div>
                <div class="line"><span class="label">Invoice Date:</span> <?= esc($appointmentDate) ?></div>
                <div class="line"><span class="label">Printed On:</span> <?= esc($printedOn) ?></div>
                <div class="line"><span class="label">Status:</span> <?= esc($invoiceStatus) ?></div>
                <div class="line"><span class="label">Payment Mode:</span> <?= esc($paymentMode !== '' ? $paymentMode : 'Pending') ?></div>
                <div class="line"><span class="label">OPD ID:</span> <?= esc((string) ($opd->opd_id ?? '')) ?></div>
            </td>
        </tr>
    </table>

    <?php if (!empty($insuranceRow) || !empty($caseRow)) : ?>
        <table class="meta meta-two" cellspacing="0" cellpadding="0" style="margin-top:-2px;">
            <tr>
                <td>
                    <div class="meta-title">Insurance / Organization</div>
                    <?php if (!empty($insuranceRow)) : ?>
                        <div class="line"><span class="label">Insurance:</span> <?= esc((string) ($insuranceRow->ins_company_name ?? '')) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($caseRow)) : ?>
                        <div class="line"><span class="label">Org. Case No:</span> <?= esc((string) ($caseRow->case_id_code ?? '')) ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="meta-title">Billing Status</div>
                    <?php if ((int) ($opd->opd_status ?? 0) === 3) : ?>
                        <div class="badge">OPD Cancelled</div>
                        <div class="line" style="margin-top:4px;"><?= esc((string) ($opd->opd_status_remark ?? '')) ?></div>
                    <?php else : ?>
                        <div class="line"><span class="label">Mode of Payment:</span> <?= esc($paymentMode !== '' ? $paymentMode : 'Pending') ?></div>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    <?php endif; ?>

    <table class="charges">
        <thead>
            <tr>
                <th style="width:16%;">Date</th>
                <th style="width:24%;">Doctor</th>
                <th style="width:24%;">Department</th>
                <th style="width:20%;">Description</th>
                <th style="width:16%;" class="amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?= esc($appointmentDate) ?></td>
                <td><?= esc($doctorName !== '' ? ('Dr. ' . $doctorName) : '') ?></td>
                <td><?= esc($department) ?></td>
                <td><?= esc($description) ?></td>
                <td class="amount"><?= esc($currencyPrefix) ?> <?= number_format($grossAmount, 2) ?></td>
            </tr>
            <?php if ($discount > 0) : ?>
                <tr>
                    <td colspan="4"><span class="label">Discount</span><?= !empty($opd->opd_disc_remark ?? '') ? (' - ' . esc((string) ($opd->opd_disc_remark ?? ''))) : '' ?></td>
                    <td class="amount">-<?= esc($currencyPrefix) ?> <?= number_format($discount, 2) ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <table class="totals-wrap" cellspacing="0" cellpadding="0">
        <tr>
            <td class="totals-left">Amount is collected against OPD consultation and related visit charges.</td>
            <td class="totals-right">
                <table class="summary" cellspacing="0" cellpadding="0">
                    <tr>
                        <td class="key">Gross Amount</td>
                        <td class="val"><?= esc($currencyPrefix) ?> <?= number_format($grossAmount, 2) ?></td>
                    </tr>
                    <tr>
                        <td class="key">Discount</td>
                        <td class="val">-<?= esc($currencyPrefix) ?> <?= number_format($discount, 2) ?></td>
                    </tr>
                    <tr class="net-row">
                        <td class="key">Net Amount</td>
                        <td class="val"><?= esc($currencyPrefix) ?> <?= number_format($netAmount, 2) ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="footer">
        <div class="footer-left">
            <div>This is a computer-generated invoice.</div>
            <div>For billing assistance, contact hospital reception.</div>
        </div>
        <div class="footer-right">
            <div class="sign-line">Authorized Signatory</div>
        </div>
        <div style="clear:both;"></div>
    </div>
</div>
</body>
</html>
