<?php
$ipd = $ipd_info ?? null;
$person = $person_info ?? null;
$packages = $ipd_packages ?? [];
$ipdItems = $ipd_invoice_items ?? [];
$showItems = $showinvoice ?? [];
$medicalItems = $inv_med_list ?? [];
$payments = $ipd_payment ?? [];
$billTotals = $bill_totals ?? ['gross' => 0, 'net' => 0, 'paid' => 0, 'balance' => 0];

$printMode = (int) ($print_mode ?? 1);
$showPaymentDetails = (bool) ($show_payment_details ?? true);

$hospitalName = defined('H_Name') ? (string) constant('H_Name') : 'Hospital';
$hospitalAddress1 = defined('H_address_1') ? (string) constant('H_address_1') : '';
$hospitalAddress2 = defined('H_address_2') ? (string) constant('H_address_2') : '';
$hospitalPhone = defined('H_phone_No') ? (string) constant('H_phone_No') : '';
$hospitalLogoName = defined('H_logo') ? trim((string) constant('H_logo')) : '';

$logoPathCandidates = [];
if ($hospitalLogoName !== '') {
    $logoPathCandidates[] = FCPATH . 'assets/images/' . ltrim($hospitalLogoName, '/\\');
    $logoPathCandidates[] = FCPATH . 'assets/img/' . ltrim($hospitalLogoName, '/\\');
}
$logoPathCandidates[] = FCPATH . 'assets/img/logo.png';
$logoPathCandidates[] = FCPATH . 'assets/images/logo.png';

$logoSrc = '';
foreach ($logoPathCandidates as $candidate) {
    if (is_file($candidate)) {
        $logoSrc = str_replace('\\', '/', $candidate);
        break;
    }
}

$titleByMode = [
    1 => 'Provisional Bill',
    2 => 'Provisional Bill',
    3 => 'Item Wise Bill',
    4 => 'TPA Final Bill',
    5 => 'Provisional Bill',
    6 => 'Provisional Bill',
];
$billTitle = $titleByMode[$printMode] ?? 'Provisional Bill';

$age = '';
if ($person) {
    $age = get_age_1($person->dob ?? null, $person->age ?? '', $person->age_in_month ?? '', $person->estimate_dob ?? '');
}

$admitDateText = trim((string) ($ipd->str_register_date ?? ''));
if ($admitDateText === '' && ! empty($ipd->register_date)) {
    $admitDateText = (string) $ipd->register_date;
}
$admitTimeText = trim((string) ($ipd->reg_time ?? ''));
if ($admitTimeText !== '') {
    $admitDateText = trim($admitDateText . ' ' . substr($admitTimeText, 0, 5));
}

$dischargeDateText = trim((string) ($ipd->str_discharge_date ?? ''));
if ($dischargeDateText === '' && ! empty($ipd->discharge_date)) {
    $dischargeDateText = (string) $ipd->discharge_date;
}
$dischargeTimeText = trim((string) ($ipd->discharge_time ?? ''));
if ($dischargeTimeText !== '') {
    $dischargeDateText = trim($dischargeDateText . ' ' . substr($dischargeTimeText, 0, 5));
}

$grossAmount = (float) ($ipd->gross_amount ?? 0);
if ($grossAmount <= 0) {
    $grossAmount = (float) ($billTotals['gross'] ?? 0);
}

$netAmount = (float) ($ipd->net_amount ?? 0);
if ($netAmount <= 0) {
    $netAmount = (float) ($billTotals['net'] ?? 0);
}

$paidAmount = (float) ($billTotals['paid'] ?? 0);
if ($paidAmount <= 0) {
    $paidAmount = (float) ($ipd->total_paid_amount ?? 0);
}

$balanceAmount = (float) ($ipd->balance_amount ?? 0);
if ($balanceAmount <= 0) {
    $balanceAmount = (float) ($billTotals['balance'] ?? 0);
}

$patientAddress = trim((string) (($person->add1 ?? '') . ' ' . ($person->add2 ?? '') . ' ' . ($person->city ?? '') . ' ' . ($person->state ?? '')));
$patientAddress = preg_replace('/\s+/', ' ', $patientAddress ?? '');

$mode3ShowAmountAfterDiscount = $printMode === 3;
$isDischargeFinal = (int) ($ipd->discarge_patient_status ?? 0) > 0;
$billHeadingText = $isDischargeFinal
    ? 'Bill No. : ' . (string) ($ipd->ipd_code ?? '')
    : $billTitle;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= esc($billTitle) ?></title>
    <style>
        body { font-family: dejavusans, sans-serif; font-size: 10.2px; color: #111; }
        .meta-top { width: 100%; margin-bottom: 6px; }
        .meta-top td { border: none; font-size: 9.2px; }
        .header { width: 100%; border-bottom: 1px solid #999; padding-bottom: 6px; margin-bottom: 8px; }
        .header td { border: none; vertical-align: top; }
        .logo { width: 58px; height: 58px; text-align: center; }
        .logo img { width: 52px; height: 52px; object-fit: contain; }
        .hospital-name { font-size: 20px; font-weight: 700; line-height: 1.15; }
        .hospital-line { font-size: 11px; line-height: 1.25; }
        .qr { text-align: right; width: 90px; }
        .title { text-align: center; font-size: 19px; font-weight: 700; margin: 6px 0 8px; }
        .info { width: 100%; margin-bottom: 8px; }
        .info td { border: none; vertical-align: top; width: 50%; font-size: 10.4px; line-height: 1.3; }
        .label { font-weight: 700; }
        .bill-table { width: 100%; border-collapse: collapse; margin-top: 2px; }
        .bill-table th, .bill-table td { border: 1px solid #888; padding: 3px 4px; font-size: 10.1px; }
        .bill-table th { background: #f2f2f2; font-size: 10.2px; }
        .center { text-align: center; }
        .right { text-align: right; }
        .group { font-weight: 700; font-size: 10.2px; }
        .totalline { font-weight: 700; }
        .footer-sign { margin-top: 14px; width: 100%; }
        .footer-sign td { border: none; width: 50%; font-weight: 700; font-size: 10.4px; }
    </style>
</head>
<body>
    <table class="meta-top" cellspacing="0" cellpadding="0">
        <tr>
            <td>Page: {PAGENO}/{nbpg}</td>
            <td style="text-align:right;">
                IPD-ID:<?= esc((string) ($ipd->ipd_code ?? '')) ?> / UHID:<?= esc((string) ($person->p_code ?? '')) ?> / Name:<?= esc((string) ($person->p_fname ?? '')) ?>
            </td>
        </tr>
    </table>

    <table class="header" cellspacing="0" cellpadding="0">
        <tr>
            <td class="logo">
                <?php if ($logoSrc !== '') : ?>
                    <img src="<?= esc($logoSrc) ?>" alt="logo" width="52" height="52">
                <?php endif; ?>
            </td>
            <td>
                <div class="hospital-name"><?= esc($hospitalName) ?></div>
                <div class="hospital-line"><?= esc(trim($hospitalAddress1 . ', ' . $hospitalAddress2, ', ')) ?></div>
                <?php if ($hospitalPhone !== '') : ?>
                    <div class="hospital-line">Phone: <?= esc($hospitalPhone) ?></div>
                <?php endif; ?>
            </td>
            <td class="qr">
                <?php if (! empty($letterhead_mode)) : ?>
                    <barcode code="<?= esc((string) ($ipd->ipd_code ?? 'IPD')) ?>|<?= esc((string) ($person->p_code ?? '')) ?>" size="0.75" type="QR" error="M" />
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <div class="title"><?= esc($billHeadingText) ?></div>

    <table class="info" cellspacing="0" cellpadding="0">
        <tr>
            <td>
                <div><span class="label">Patient Name :</span> <?= esc((string) ($person->p_fname ?? '')) ?></div>
                <div><span class="label">SON OF :</span> <?= esc((string) ($person->p_rname ?? '')) ?></div>
                <div><span class="label">Patient-ID/UHID :</span> <?= esc((string) ($person->p_code ?? '')) ?></div>
                <div><span class="label">Gender :</span> <?= esc((string) ($person->xgender ?? '')) ?></div>
                <div><span class="label">Age :</span> <?= esc((string) $age) ?></div>
                <div><span class="label">Phone No :</span> <?= esc((string) ($person->mphone1 ?? '')) ?></div>
                <div><span class="label">Address :</span> <?= esc((string) $patientAddress) ?></div>
            </td>
            <td>
                <div><span class="label">IPD ID :</span> <?= esc((string) ($ipd->ipd_code ?? '')) ?></div>
                <div><span class="label">Admit Date :</span> <?= esc((string) $admitDateText) ?></div>
                <div><span class="label">Discharge Date :</span> <?= esc((string) $dischargeDateText) ?></div>
                <div><span class="label">No. of Days :</span> <?= esc((string) ($ipd->no_days ?? '')) ?></div>
                <div><span class="label">Bed No :</span> <?= esc((string) ($ipd->bed_no ?? '')) ?></div>
                <div><span class="label">Doctor Name :</span> <?= esc((string) ($ipd->r_doc_name ?? '')) ?></div>
                <?php if (! empty($ipd->status_desc ?? '')) : ?>
                    <div><span class="label">Status :</span> <?= esc((string) ($ipd->status_desc ?? '')) ?></div>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <table class="bill-table" cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                <th style="width:5%;" class="center">#</th>
                <th style="width:39%;">Description</th>
                <th style="width:12%;" class="center">Org.Code</th>
                <th style="width:8%;" class="center">Unit</th>
                <th style="width:12%;" class="right">Rate</th>
                <th style="width:12%;" class="right">Amount</th>
                <?php if ($mode3ShowAmountAfterDiscount) : ?>
                    <th style="width:12%;" class="right">Amt After Discount</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php
            $srNo = 1;
            $headDesc = '';
            $headTotal = 0.0;

            if (! empty($packages)) {
                echo '<tr><td></td><td class="group">Package</td><td></td><td></td><td></td><td></td>' . ($mode3ShowAmountAfterDiscount ? '<td></td>' : '') . '</tr>';
                foreach ($packages as $row) {
                    $amt = (float) ($row->package_Amount ?? 0);
                    echo '<tr>';
                    echo '<td class="center">' . $srNo . '</td>';
                    echo '<td>' . esc((string) ($row->package_name ?? '')) . '</td>';
                    echo '<td class="center">' . esc((string) ($row->org_code ?? '')) . '</td>';
                    echo '<td class="center"></td>';
                    echo '<td class="right"></td>';
                    echo '<td class="right">' . esc(number_format($amt, 2)) . '</td>';
                    if ($mode3ShowAmountAfterDiscount) {
                        echo '<td class="right">' . esc(number_format($amt, 2)) . '</td>';
                    }
                    echo '</tr>';
                    $srNo++;
                }
            }

            for ($i = 0, $n = count($ipdItems); $i < $n; $i++) {
                $row = $ipdItems[$i];
                if ($headDesc !== (string) ($row->group_desc ?? '')) {
                    $headDesc = (string) ($row->group_desc ?? '');
                    echo '<tr><td></td><td class="group">' . esc($headDesc) . '</td><td></td><td></td><td></td><td></td>' . ($mode3ShowAmountAfterDiscount ? '<td></td>' : '') . '</tr>';
                }

                $amt = (float) ($row->item_amount ?? 0);
                echo '<tr>';
                echo '<td class="center">' . $srNo . '</td>';
                echo '<td>' . esc(trim((string) (($row->item_name ?? '') . ' ' . ($row->comment ?? '')))) . '</td>';
                echo '<td class="center">' . esc((string) ($row->org_code ?? '')) . '</td>';
                echo '<td class="center">' . esc((string) ($row->item_qty ?? '')) . '</td>';
                echo '<td class="right">' . esc(number_format((float) ($row->item_rate ?? 0), 2)) . '</td>';
                echo '<td class="right">' . esc(number_format($amt, 2)) . '</td>';
                if ($mode3ShowAmountAfterDiscount) {
                    echo '<td class="right">' . esc(number_format($amt, 2)) . '</td>';
                }
                echo '</tr>';
                $srNo++;
                $headTotal += $amt;
            }

            for ($i = 0, $n = count($showItems); $i < $n; $i++) {
                $row = $showItems[$i];
                if ($headDesc !== (string) ($row->Charge_type ?? '')) {
                    $headDesc = (string) ($row->Charge_type ?? '');
                    echo '<tr><td></td><td class="group">' . esc($headDesc) . '</td><td></td><td></td><td></td><td></td>' . ($mode3ShowAmountAfterDiscount ? '<td></td>' : '') . '</tr>';
                }

                $amt = (float) ($row->amount ?? 0);
                echo '<tr>';
                echo '<td class="center">' . $srNo . '</td>';
                echo '<td>' . esc((string) ($row->idesc ?? '')) . '</td>';
                echo '<td class="center">' . esc((string) ($row->orgcode ?? '')) . '</td>';
                echo '<td class="center">' . esc((string) ($row->no_qty ?? '')) . '</td>';
                echo '<td class="right">' . esc(number_format((float) ($row->item_rate ?? 0), 2)) . '</td>';
                echo '<td class="right">' . esc(number_format($amt, 2)) . '</td>';
                if ($mode3ShowAmountAfterDiscount) {
                    echo '<td class="right">' . esc(number_format($amt, 2)) . '</td>';
                }
                echo '</tr>';
                $srNo++;
            }

            foreach ($medicalItems as $row) {
                $amt = (float) ($row->net_amount ?? 0);
                echo '<tr>';
                echo '<td class="center">' . $srNo . '</td>';
                echo '<td>' . esc((string) ($row->inv_med_code ?? 'Medicine')) . '</td>';
                echo '<td></td><td></td><td></td>';
                echo '<td class="right">' . esc(number_format($amt, 2)) . '</td>';
                if ($mode3ShowAmountAfterDiscount) {
                    echo '<td class="right">' . esc(number_format($amt, 2)) . '</td>';
                }
                echo '</tr>';
                $srNo++;
            }
            ?>

            <tr class="totalline">
                <td></td>
                <td colspan="<?= $mode3ShowAmountAfterDiscount ? '4' : '3' ?>" class="right">Gross Total</td>
                <td class="right"><?= esc(number_format($grossAmount, 2)) ?></td>
                <?php if ($mode3ShowAmountAfterDiscount) : ?><td class="right"></td><?php endif; ?>
            </tr>

            <tr class="totalline">
                <td></td>
                <td colspan="<?= $mode3ShowAmountAfterDiscount ? '4' : '3' ?>" class="right">Net Amount</td>
                <td class="right"><?= esc(number_format($netAmount, 2)) ?></td>
                <?php if ($mode3ShowAmountAfterDiscount) : ?><td class="right"></td><?php endif; ?>
            </tr>

            <?php if ($showPaymentDetails) : ?>
                <tr>
                    <td></td>
                    <td colspan="<?= $mode3ShowAmountAfterDiscount ? '4' : '3' ?>" class="totalline">Payment Recd.</td>
                    <td class="right"><?= esc(number_format($paidAmount, 2)) ?></td>
                    <?php if ($mode3ShowAmountAfterDiscount) : ?><td></td><?php endif; ?>
                </tr>
                <tr class="totalline">
                    <td></td>
                    <td colspan="<?= $mode3ShowAmountAfterDiscount ? '4' : '3' ?>" class="right">Balance</td>
                    <td class="right"><?= esc(number_format($balanceAmount, 2)) ?></td>
                    <?php if ($mode3ShowAmountAfterDiscount) : ?><td></td><?php endif; ?>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <table class="footer-sign" cellspacing="0" cellpadding="0">
        <tr>
            <td style="text-align:left;">Patient / Relative Signature</td>
            <td style="text-align:right;">Authorized Signatory</td>
        </tr>
    </table>
</body>
</html>
