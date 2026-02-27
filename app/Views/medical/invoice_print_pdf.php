<?php
$invoiceCode = (string) ($invoice->inv_med_code ?? ('M' . date('ym') . str_pad(substr((string) ((int) ($invoice->id ?? 0)), -7, 7), 7, '0', STR_PAD_LEFT)));
$invoiceDate = ! empty($invoice->inv_date) ? date('d-m-Y', strtotime((string) $invoice->inv_date)) : date('d-m-Y');

$pharmacyName = defined('M_store') ? (string) M_store : 'Medical Store';
$pharmacyAddress = defined('M_address') ? (string) M_address : '';
$pharmacyPhone = defined('M_Phone_Number') ? (string) M_Phone_Number : '';
$pharmacyState = defined('M_State') ? (string) M_State : '';
$pharmacyGst = defined('H_Med_GST') ? (string) H_Med_GST : '';

$customerName = trim((string) ($invoice->inv_name ?? 'Walk-in Customer'));
$customerCode = trim((string) ($invoice->patient_code ?? ''));
$customerPhone = trim((string) ($invoice->inv_phone_number ?? ($patient->mphone1 ?? '')));
$doctorName = trim((string) ($invoice->doc_name ?? ''));

$grossTotal = 0.0;
$discountTotal = 0.0;
$cgstTotal = 0.0;
$sgstTotal = 0.0;
$netTotal = 0.0;

$compact = in_array((int) ($printFormat ?? 0), [1, 2, 3], true);
$baseFont = $compact ? '10px' : '11px';
$titleFont = $compact ? '16px' : '20px';
$thPad = $compact ? '5px 4px' : '6px 5px';
$tdPad = $compact ? '4px 4px' : '5px 5px';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: <?= $baseFont ?>; color:#1f2937; }
        .invoice-wrap { border: 1px solid #d1d5db; }
        .header { border-bottom: 1px solid #d1d5db; padding: 10px 12px; }
        .store-name { font-size: <?= $titleFont ?>; font-weight: 700; letter-spacing: .2px; color:#111827; }
        .store-meta { font-size: <?= $compact ? '9px' : '10px' ?>; color:#374151; margin-top: 2px; line-height: 1.45; }
        .doc-title { margin-top: 8px; font-weight: 700; font-size: <?= $compact ? '11px' : '12px' ?>; color:#111827; }
        .meta { width: 100%; border-collapse: collapse; }
        .meta td { border: 1px solid #e5e7eb; padding: <?= $tdPad ?>; vertical-align: top; }
        .meta-title { font-weight: 700; color:#111827; margin-bottom: 2px; }
        .muted { color:#6b7280; }
        .items { width: 100%; border-collapse: collapse; margin-top: 0; }
        .items th { background: #f3f4f6; border: 1px solid #d1d5db; padding: <?= $thPad ?>; font-weight:700; font-size: <?= $compact ? '9px' : '10px' ?>; }
        .items td { border: 1px solid #e5e7eb; padding: <?= $tdPad ?>; font-size: <?= $compact ? '9px' : '10px' ?>; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .summary td { border: 1px solid #d1d5db; padding: <?= $tdPad ?>; }
        .summary .label { font-weight: 700; background:#f9fafb; }
        .summary .val { text-align: right; font-weight: 700; }
        .footer-note { margin-top: 10px; font-size: <?= $compact ? '9px' : '10px' ?>; color:#4b5563; }
        .sign-row { width: 100%; border-collapse: collapse; margin-top: 16px; }
        .sign-row td { width: 50%; padding-top: 18px; font-size: <?= $compact ? '9px' : '10px' ?>; }
    </style>
</head>
<body>
    <div class="invoice-wrap">
        <div class="header">
            <div class="store-name"><?= esc($pharmacyName !== '' ? $pharmacyName : 'Medical Store') ?></div>
            <div class="store-meta">
                <?= esc($pharmacyAddress) ?><?= $pharmacyState !== '' ? ', ' . esc($pharmacyState) : '' ?>
                <?php if ($pharmacyPhone !== ''): ?> | Phone: <?= esc($pharmacyPhone) ?><?php endif; ?>
                <?php if ($pharmacyGst !== ''): ?> | GSTIN: <?= esc($pharmacyGst) ?><?php endif; ?>
            </div>
            <div class="doc-title">TAX INVOICE</div>
        </div>

        <table class="meta">
            <tr>
                <td style="width:50%;">
                    <div class="meta-title">Bill To</div>
                    <div><strong><?= esc($customerName !== '' ? $customerName : 'Walk-in Customer') ?></strong></div>
                    <div class="muted">Patient Code: <?= esc($customerCode !== '' ? $customerCode : '-') ?></div>
                    <div class="muted">Phone: <?= esc($customerPhone !== '' ? $customerPhone : '-') ?></div>
                    <div class="muted">Doctor: <?= esc($doctorName !== '' ? $doctorName : '-') ?></div>
                </td>
                <td style="width:50%;">
                    <div class="meta-title">Invoice Details</div>
                    <div><strong>Invoice No:</strong> <?= esc($invoiceCode) ?></div>
                    <div><strong>Date:</strong> <?= esc($invoiceDate) ?></div>
                    <div><strong>Payment Type:</strong> <?= ((int) ($invoice->ipd_credit ?? 0) === 1 || (int) ($invoice->case_credit ?? 0) === 1) ? 'Credit' : 'Cash' ?></div>
                    <div><strong>IPD/Case:</strong> <?= esc((string) ($invoice->ipd_code ?? $invoice->case_id ?? '-')) ?></div>
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th style="width:4%;">#</th>
                    <th style="width:10%;">Code</th>
                    <th style="width:26%;">Item Name</th>
                    <th style="width:10%;">Batch</th>
                    <th style="width:10%;">Exp.</th>
                    <th style="width:7%;" class="text-right">Qty</th>
                    <th style="width:8%;" class="text-right">Rate</th>
                    <th style="width:8%;" class="text-right">Gross</th>
                    <th style="width:6%;" class="text-right">Disc</th>
                    <th style="width:10%;" class="text-right">Inc. GST</th>
                    <th style="width:11%;" class="text-right">Net</th>
                </tr>
            </thead>
            <tbody>
                <?php if (! empty($items)): ?>
                    <?php foreach ($items as $index => $item): ?>
                        <?php
                        $gross = (float) ($item->amount ?? 0);
                        $disc = (float) ($item->disc_amount ?? ($item->twdisc_amount ?? 0));
                        $net = (float) ($item->twdisc_amount ?? $item->tamount ?? ($item->net_amount ?? 0));

                        $cgstPer = (float) ($item->CGST_per ?? 0);
                        $sgstPer = (float) ($item->SGST_per ?? 0);
                        $gstPerTotal = $cgstPer + $sgstPer;

                        $cgst = (float) ($item->CGST ?? ($item->c_gst_amt ?? 0));
                        $sgst = (float) ($item->SGST ?? ($item->s_gst_amt ?? 0));

                        if (($cgst <= 0 || $sgst <= 0) && $gstPerTotal > 0 && $net > 0) {
                            $taxableFromInclusive = $net * 100 / (100 + $gstPerTotal);
                            $taxTotal = $net - $taxableFromInclusive;
                            $cgst = $taxTotal * ($cgstPer / $gstPerTotal);
                            $sgst = $taxTotal * ($sgstPer / $gstPerTotal);
                        } elseif (($cgst <= 0 || $sgst <= 0) && $gstPerTotal > 0 && $gross > 0) {
                            $cgst = $gross * $cgstPer / 100;
                            $sgst = $gross * $sgstPer / 100;
                        }

                        $grossTotal += $gross;
                        $discountTotal += $disc;
                        $cgstTotal += $cgst;
                        $sgstTotal += $sgst;
                        $netTotal += $net;
                        ?>
                        <tr>
                            <td class="text-center"><?= (int) $index + 1 ?></td>
                            <td><?= esc((string) ($item->item_code ?? '-')) ?></td>
                            <td><?= esc((string) ($item->item_Name ?? '-')) ?></td>
                            <td><?= esc((string) ($item->batch_no ?? '-')) ?></td>
                            <td><?= esc((string) ($item->expiry ?? '-')) ?></td>
                            <td class="text-right"><?= esc(number_format((float) ($item->qty ?? 0), 2)) ?></td>
                            <td class="text-right"><?= esc(number_format((float) ($item->price ?? 0), 2)) ?></td>
                            <td class="text-right"><?= esc(number_format($gross, 2)) ?></td>
                            <td class="text-right"><?= esc(number_format($disc, 2)) ?></td>
                            <td class="text-right\"><?= esc(number_format($cgst + $sgst, 2)) ?></td>
                            <td class="text-right"><?= esc(number_format($net, 2)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="12" class="text-center">No items available.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php
        $grossFinal = (float) ($invoice->gross_amount ?? $grossTotal);
        $discFinal = (float) ($invoice->disc_amount ?? $discountTotal);
        $netFinal = (float) ($invoice->net_amount ?? $netTotal);
        ?>

        <table class="summary">
            <tr>
                <td class="label" style="width:25%;">Gross Total</td>
                <td class="val" style="width:25%;">₹ <?= esc(number_format($grossFinal, 2)) ?></td>
                <td class="label" style="width:25%;">Discount Total</td>
                <td class="val" style="width:25%;">₹ <?= esc(number_format($discFinal, 2)) ?></td>
            </tr>
            <tr>
                <td class="label">Inc. GST Total</td>
                <td class="val">₹ <?= esc(number_format($cgstTotal + $sgstTotal, 2)) ?></td>
                <td class="label"></td>
                <td class="val"></td>
            </tr>
            <tr>
                <td class="label">Net Amount</td>
                <td class="val" colspan="3">₹ <?= esc(number_format($netFinal, 2)) ?></td>
            </tr>
        </table>

        <div class="footer-note">
            This is a computer-generated invoice. Please check medicine name, quantity, and amount at the time of purchase.
        </div>

        <table class="sign-row">
            <tr>
                <td>Customer Signature</td>
                <td style="text-align:right;">Authorized Signatory</td>
            </tr>
        </table>
    </div>
</body>
</html>
