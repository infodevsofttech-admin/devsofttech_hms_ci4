<?php
$invoice = $invoice_master[0] ?? null;
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

$invoiceCode = (string) ($invoice->invoice_code ?? '');
$invoiceDate = (string) ($invoice->inv_date ?? '');
$printedOn = date('d-m-Y h:i:s A');
$invoiceStatus = ((int) ($invoice->invoice_status ?? 0) === 1) ? 'Paid' : (((int) ($invoice->invoice_status ?? 0) === 2) ? 'Cancelled' : 'Pending');
$currencyPrefix = 'Rs.';
$grossAmount = (float) ($invoice->total_amount ?? 0);
$discount = (float) ($invoice->discount_amount ?? 0);
$netAmount = (float) ($invoice->net_amount ?? 0);

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
    'Invoice:' . $invoiceCode,
    'Patient:' . (string) ($patient->p_code ?? ''),
    'Name:' . trim((string) ($patient->p_fname ?? '')),
    'Date:' . $invoiceDate,
    'Net:' . number_format($netAmount, 2, '.', ''),
    'Status:' . $invoiceStatus,
];
$qrContent = implode(' | ', array_filter($qrParts, static fn ($v) => trim((string) $v) !== ''));
$qrPackageAvailable = class_exists('\\Mpdf\\QrCode\\QrCode');

/**
 * Convert numbers to words (basic implementation for Indian system)
 * Wrapped with function_exists to prevent redeclaration when rendering multiple times
 */
if (!function_exists('convertNumberToWords')) {
    function convertNumberToWords($num) {
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
        $teens = ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        
        if ($num == 0) return 'Zero';
        if ($num < 0) return 'Negative ' . convertNumberToWords(-$num);
        
        if ($num < 10) return $ones[$num];
        if ($num < 20) return $teens[$num - 10];
        if ($num < 100) return $tens[(int)($num / 10)] . ($num % 10 ? ' ' . $ones[$num % 10] : '');
        if ($num < 1000) return $ones[(int)($num / 100)] . ' Hundred' . ($num % 100 ? ' ' . convertNumberToWords($num % 100) : '');
        if ($num < 100000) return convertNumberToWords((int)($num / 1000)) . ' Thousand' . ($num % 1000 ? ' ' . convertNumberToWords($num % 1000) : '');
        if ($num < 10000000) return convertNumberToWords((int)($num / 100000)) . ' Lakh' . ($num % 100000 ? ' ' . convertNumberToWords($num % 100000) : '');
        if ($num < 1000000000) return convertNumberToWords((int)($num / 10000000)) . ' Crore' . ($num % 10000000 ? ' ' . convertNumberToWords($num % 10000000) : '');
        
        return (string)$num;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice</title>
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
        .payment-details { font-size: 9.6px; color: #445; margin-top: 4px; line-height: 1.6; }
        .amount-words { font-weight: 700; color: #1f2d3a; }
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
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-sub"><?= esc($invoiceCode) ?></div>
                <div class="invoice-status">
                    <div class="status-chip"><?= esc($invoiceStatus) ?></div>
                </div>
                <?php if ($qrContent !== '' && $qrPackageAvailable) : ?>
                    <div class="qr-block">
                        <barcode code="<?= esc($qrContent) ?>" size="0.62" type="QR" error="M" class="barcode" />
                        <div class="qr-label">Scan for info</div>
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
                <div class="line"><span class="label">Name:</span> <?= esc(trim((string) ($patient->p_fname ?? ''))) ?></div>
                <div class="line"><span class="label">Gender:</span> <?= esc((string) ($patient->xgender ?? '')) ?></div>
                <div class="line"><span class="label">Age:</span> <?= esc((string) ($patient->age ?? '')) ?></div>
                <div class="line"><span class="label">Phone:</span> <?= esc((string) ($patient->mphone1 ?? '')) ?></div>
            </td>
            <td>
                <div class="meta-title">Refer Details</div>
                <div class="line"><span class="label">Refer By:</span> <?= esc((string) ($invoice->refer_by_other ?? '')) ?></div>
                <div class="line"><span class="label">Invoice Date:</span> <?= esc($invoiceDate) ?></div>
                <div class="line"><span class="label">Printed On:</span> <?= esc($printedOn) ?></div>
            </td>
            <td>
                <div class="meta-title">Invoice Details</div>
                <div class="line"><span class="label">Invoice ID:</span> <?= esc($invoiceCode) ?></div>
                <div class="line"><span class="label">Status:</span> <?= esc($invoiceStatus) ?></div>
                <div class="line"><span class="label">Payment Mode:</span> <?php 
                    $mode = (int) ($invoice->payment_mode ?? 0);
                    echo $mode === 1 ? 'Cash' : ($mode === 2 ? 'Bank Card/Online' : ($mode === 3 ? 'IPD Credit' : ($mode === 4 ? 'Org. Credit' : 'Pending')));
                ?></div>
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
                <td></td>
            </tr>
        </table>
    <?php endif; ?>

    <table class="charges">
        <thead>
            <tr>
                <th style="width:5%;">#</th>
                <th style="width:20%;">Charges Group</th>
                <th style="width:30%;">Charge Name</th>
                <th style="width:15%;">Rate</th>
                <th style="width:15%;">Qty</th>
                <th style="width:15%;" class="amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php $srno = 1; foreach ($invoiceDetails as $row) : ?>
                <tr>
                    <td><?= $srno ?></td>
                    <td><?= esc((string) ($row->group_desc ?? '')) ?></td>
                    <td><?= esc((string) ($row->item_name ?? '')) ?></td>
                    <td class="amount"><?= esc((string) ($row->item_rate ?? '')) ?></td>
                    <td class="amount"><?= esc((string) ($row->item_qty ?? '')) ?></td>
                    <td class="amount"><?= esc($currencyPrefix) ?> <?= number_format((float) ($row->item_amount ?? 0), 2) ?></td>
                </tr>
            <?php $srno++; endforeach; ?>
            <?php if ($discount > 0) : ?>
                <tr>
                    <td colspan="5"><span class="label">Discount</span><?= !empty($invoice->discount_desc ?? '') ? (' - ' . esc((string) ($invoice->discount_desc ?? ''))) : '' ?></td>
                    <td class="amount">-<?= esc($currencyPrefix) ?> <?= number_format($discount, 2) ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <table class="totals-wrap" cellspacing="0" cellpadding="0">
        <tr>
            <td class="totals-left">
                <div class="payment-details">
                    <div><span class="label">Amount received:</span> <?= esc($currencyPrefix) ?> <?= number_format(0, 2) ?> | <span class="label">Balance Amount:</span> <?= esc($currencyPrefix) ?> <?= number_format($netAmount, 2) ?> | <span class="label">Net Amount:</span> <?= esc($currencyPrefix) ?> <?= number_format($netAmount, 2) ?></div>
                    <div style="margin-top:6px;"><span class="amount-words">Amount in Words: </span><?php 
                        $amountInWords = convertNumberToWords((int)$netAmount);
                        echo esc($amountInWords) . ' Only';
                    ?></div>
                </div>
            </td>
            <td class="totals-right">
                <table class="summary" cellspacing="0" cellpadding="0">
                    <tr>
                        <td class="key">Gross Total</td>
                        <td class="val"><?= esc($currencyPrefix) ?> <?= number_format($grossAmount, 2) ?></td>
                    </tr>
                    <?php if ($discount > 0) : ?>
                    <tr>
                        <td class="key">Discount</td>
                        <td class="val">-<?= esc($currencyPrefix) ?> <?= number_format($discount, 2) ?></td>
                    </tr>
                    <?php endif; ?>
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
