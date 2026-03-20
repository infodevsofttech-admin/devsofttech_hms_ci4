<?php
$invoice = $invoice_master[0] ?? null;
$patient = $patient_master[0] ?? null;
$caseRow = $case_master[0] ?? null;

$hospitalName = defined('H_Name') ? (string) constant('H_Name') : 'Hospital';
$hospitalAddress1 = defined('H_address_1') ? (string) constant('H_address_1') : '';
$hospitalAddress2 = defined('H_address_2') ? (string) constant('H_address_2') : '';
$hospitalPhone = defined('H_phone_No') ? (string) constant('H_phone_No') : '';
$hospitalLogoName = defined('H_logo') ? trim((string) constant('H_logo')) : '';

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

$invoiceCode = (string) ($invoice->invoice_code ?? '');
$invoiceDate = (string) ($invoice->inv_date ?? '');
$grossAmount = (float) ($invoice->total_amount ?? 0);
$discount = (float) ($invoice->discount_amount ?? 0);
$netAmount = (float) ($invoice->net_amount ?? 0);

$amountReceived = (float) ($invoice->payment_part_received ?? 0);
$balanceAmount = (float) ($invoice->payment_part_balance ?? $netAmount);

$orgCaseInfo = '';
if (!empty($caseRow)) {
    $shortName = (string) ($caseRow->short_name ?? '');
    $caseCode = (string) ($caseRow->case_id_code ?? '');
    if ($shortName !== '') {
        $orgCaseInfo .= 'Org. Name : ' . $shortName . '<br>';
    }
    if ($caseCode !== '') {
        $orgCaseInfo .= 'Org. ID : ' . $caseCode;
    }
}

$qrParts = [
    (string) ($patient->p_code ?? ''),
    $invoiceCode,
    'TAmt-' . number_format($netAmount, 2, '.', ''),
    'P-' . date('Y-m-d H:i:s'),
];
$qrContent = implode(':', array_filter($qrParts, static fn ($v) => trim((string) $v) !== ''));
$paymentHistory = $payment_history_details ?? [];

if (!function_exists('convertNumberToWords')) {
    function convertNumberToWords($num)
    {
        $num = (int) round((float) $num);
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
        $teens = ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        if ($num === 0) return 'Zero';
        if ($num < 0) return 'Negative ' . convertNumberToWords(-$num);

        if ($num < 10) return $ones[$num];
        if ($num < 20) return $teens[$num - 10];
        if ($num < 100) return $tens[(int) ($num / 10)] . ($num % 10 ? ' ' . $ones[$num % 10] : '');
        if ($num < 1000) return $ones[(int) ($num / 100)] . ' Hundred' . ($num % 100 ? ' ' . convertNumberToWords($num % 100) : '');
        if ($num < 100000) return convertNumberToWords((int) ($num / 1000)) . ' Thousand' . ($num % 1000 ? ' ' . convertNumberToWords($num % 1000) : '');
        if ($num < 10000000) return convertNumberToWords((int) ($num / 100000)) . ' Lakh' . ($num % 100000 ? ' ' . convertNumberToWords($num % 100000) : '');

        return convertNumberToWords((int) ($num / 10000000)) . ' Crore' . ($num % 10000000 ? ' ' . convertNumberToWords($num % 10000000) : '');
    }
}
?>
<style>
@page {
    sheet-size: A4-L;
    margin-top: 0.7cm;
    margin-bottom: 0.8cm;
    margin-left: 0.4cm;
    margin-right: 0.4cm;
}
body { font-family: dejavusans, freeserif, sans-serif; font-size: 11.5px; color: #111; }
.main { width: 100%; }
.half { width: 50%; vertical-align: top; }
.left-border { border-right: 1px solid #ddd; }
.head-wrap { border: 1px solid #ddd; }
.hname { font-size: 19px; font-weight: 700; margin-bottom: 2px; }
.hline { font-size: 12px; }
.inv-title { font-size: 27px; font-weight: 700; text-align: right; color: #2b3f54; }
.small { font-size: 10.5px; }
.tbl { width: 100%; border-collapse: collapse; font-size: 11px; }
.tbl th, .tbl td { border: 1px solid #222; padding: 3px 5px; }
.tbl th { font-weight: 700; }
.right { text-align: right; }
.sign { margin-top: 18px; text-align: right; }
</style>

<table class="main" cellspacing="6" cellpadding="0">
    <tr>
        <?php foreach (['', ' [LAB COPY]'] as $copyLabel) : ?>
            <td class="half <?= $copyLabel === '' ? 'left-border' : '' ?>">
                <table class="tbl" style="border:none;">
                    <tr>
                        <td style="border:none; width:16%; vertical-align: top;">
                            <?php if ($logoSrc !== '') : ?>
                                <img style="width:65px;" src="<?= esc($logoSrc) ?>" alt="logo">
                            <?php endif; ?>
                        </td>
                        <td style="border:none; width:62%; vertical-align: top;">
                            <div class="hname"><?= esc($hospitalName) ?></div>
                            <div class="hline"><?= esc(trim($hospitalAddress1 . ', ' . $hospitalAddress2, ', ')) ?></div>
                            <?php if ($hospitalPhone !== '') : ?>
                                <div class="hline">Phone: <?= esc($hospitalPhone) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="border:none; width:22%; text-align:right; vertical-align: top;">
                            <div class="inv-title">INVOICE</div>
                            <div><?= esc($invoiceCode . $copyLabel) ?></div>
                            <?php if ($qrContent !== '') : ?>
                                <barcode code="<?= esc($qrContent) ?>" size="0.75" type="QR" error="M" class="barcode" />
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <table style="width:100%; margin-top:8px;" cellspacing="0" cellpadding="0">
                    <tr>
                        <td style="width:50%; vertical-align:top;">
                            Bill To<br>
                            UHID : <?= esc((string) ($patient->p_code ?? '')) ?><br>
                            Name : <strong><?= esc(strtoupper((string) ($patient->p_fname ?? ''))) ?></strong><br>
                            <?= esc((string) ($patient->p_relative ?? '')) ?> <?= esc(strtoupper((string) ($patient->p_rname ?? ''))) ?><br>
                            Sex : <strong><?= esc((string) ($patient->xgender ?? '')) ?> <?= esc((string) ($patient->age ?? '')) ?></strong>
                            P.No. : <?= esc((string) ($patient->mphone1 ?? '')) ?>
                        </td>
                        <td style="width:50%; text-align:right; vertical-align:top;">
                            <?= $orgCaseInfo ?>
                            <?php if ($orgCaseInfo !== '') : ?><br><?php endif; ?>
                            Address : <?= esc((string) (($patient->add1 ?? '') . ',' . ($patient->city ?? ''))) ?><br>
                            Date : <strong><?= esc($invoiceDate) ?></strong><br>
                            <strong>Refer By :</strong> Dr.<?= esc((string) ($invoice->refer_by_other ?? '')) ?>
                        </td>
                    </tr>
                </table>

                <hr>
                <table class="tbl">
                    <tr>
                        <th style="width:4%;">#</th>
                        <th style="width:24%;">Charges Group</th>
                        <th style="width:31%;">Charge Name</th>
                        <th style="width:13%;" class="right">Rate</th>
                        <th style="width:10%;" class="right">Qty</th>
                        <th style="width:18%;" class="right">Amt.</th>
                    </tr>
                    <?php $srno = 1; foreach (($invoiceDetails ?? []) as $row) : ?>
                        <tr>
                            <td><?= $srno ?></td>
                            <td><?= esc((string) ($row->group_desc ?? '')) ?></td>
                            <td><?= esc((string) ($row->item_name ?? '')) ?></td>
                            <td class="right"><?= esc((string) ($row->item_rate ?? '')) ?></td>
                            <td class="right"><?= esc((string) ($row->item_qty ?? '')) ?></td>
                            <td class="right"><?= esc(number_format((float) ($row->item_amount ?? 0), 2, '.', '')) ?></td>
                        </tr>
                    <?php $srno++; endforeach; ?>
                    <tr>
                        <td>#</td><td></td><td></td><td></td>
                        <td>Gross Total</td>
                        <td class="right"><?= esc(number_format($grossAmount, 2, '.', '')) ?></td>
                    </tr>
                    <?php if ($discount > 0) : ?>
                        <tr>
                            <td>#</td><td>Deduction</td><td><?= esc((string) ($invoice->discount_desc ?? '')) ?></td><td></td><td></td>
                            <td class="right">-<?= esc(number_format($discount, 2, '.', '')) ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <th>#</th><th></th><th></th><th></th>
                        <th>Net Amount</th>
                        <th class="right"><?= esc(number_format($netAmount, 2, '.', '')) ?></th>
                    </tr>
                </table>
                <hr>

                <div class="small">
                    Amount received : <?= esc(number_format($amountReceived, 2, '.', '')) ?>
                    / Balance Amount : <?= esc(number_format($balanceAmount, 2, '.', '')) ?>
                    / Net Amount : <?= esc(number_format($netAmount, 2, '.', '')) ?>
                </div>
                <div style="margin-top:5px;">
                    <strong>Amount in Words :</strong> Rs. <?= esc(convertNumberToWords($netAmount)) ?> Only<br>
                    <strong>Prepared By :</strong> <?= esc((string) ($invoice->prepared_by ?? '')) ?>
                </div>
                <div style="margin-top:5px;">
                    <?php if ((int) ($invoice->payment_mode ?? 0) > 2) : ?>
                        <strong>Payment Details [<?= esc((string) ($invoice->Payment_type_str ?? '')) ?>]</strong>
                    <?php else : ?>
                        <strong>Payment Details [Payment No.:Mode of Payment:Amount]:</strong>
                        <?php foreach ($paymentHistory as $ph) : ?>
                            [<?= esc((string) ($ph->id ?? '')) ?>:<?= esc((string) ($ph->Payment_type_str ?? '')) ?>:<?= esc(number_format((float) ($ph->amount ?? 0), 2, '.', '')) ?>]/
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="sign">
                    <strong>Signature</strong><br>
                    Date : <?= esc((string) ($invoice->confirm_invoice_datetime ?? date('Y-m-d H:i:s'))) ?>
                </div>
            </td>
        <?php endforeach; ?>
    </tr>
</table>
