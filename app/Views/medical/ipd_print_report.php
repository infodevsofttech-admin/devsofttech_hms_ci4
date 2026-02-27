<?php
$modeTitleMap = [
    'cash' => 'IPD Medical Invoice (Cash)',
    'cash-return' => 'IPD Medical Invoice (Cash With Return)',
    'credit' => 'IPD Medical Invoice (Credit)',
    'package' => 'IPD Medical Invoice (Package)',
    'med-list' => 'Consolidated Medicine List',
    'med-list-date' => 'Medicine List Datewise',
    'pagewise' => 'IPD Medical Invoice (Page Wise)',
    'return-list' => 'Return Medicine List',
];

$title = $modeTitleMap[$mode] ?? 'IPD Medical Print';
$ipdCode = (string) ($ipd->ipd_code ?? ('IPD-' . (int) ($ipdId ?? 0)));
$isPdf = (bool) ($isPdf ?? false);

$pharmacyName = defined('M_store') ? (string) M_store : 'Medical Store';
$pharmacyAddress = defined('M_address') ? (string) M_address : '';
$pharmacyPhone = defined('M_Phone_Number') ? (string) M_Phone_Number : '';
$pharmacyEmail = defined('H_Email') ? (string) H_Email : '';
$pharmacyGst = defined('H_Med_GST') ? (string) H_Med_GST : '';
$pharmacyLic = defined('M_LIC') ? (string) M_LIC : '';

$patientName = trim((string) ($patient->p_fname ?? ($ipd->p_name ?? '-')));
$patientCode = trim((string) ($patient->p_code ?? '-'));
$doctorName = trim((string) ($ipd->doc_name ?? '-'));
$orgCode = trim((string) ($orgCase->case_id_code ?? '-'));
$orgName = trim((string) ($orgCase->insurance_company_name ?? '-'));

$invoiceMap = [];
foreach (($invoices ?? []) as $inv) {
    $invoiceMap[(int) ($inv->id ?? 0)] = $inv;
}

$itemsByInvoice = $itemsByInvoice ?? [];
$invoiceItemTotals = $invoiceItemTotals ?? [];
$invoiceTotals = $invoiceTotals ?? ['gross' => 0, 'discount' => 0, 'net' => 0, 'balance' => 0];
$itemTotals = $itemTotals ?? ['qty' => 0, 'amount' => 0];
$itemsByDate = $itemsByDate ?? [];
$paymentHistory = $paymentHistory ?? [];
$paymentSummary = $paymentSummary ?? [
    'total_received' => 0.0,
    'balance' => (float) ($invoiceTotals['net'] ?? 0),
];

$abs = static function ($value): float {
    return abs((float) $value);
};

$fmtDate = static function ($value, string $format = 'd-m-Y'): string {
    if (empty($value)) {
        return '-';
    }
    $ts = strtotime((string) $value);
    return $ts ? date($format, $ts) : '-';
};

$fmtMoney = static function ($value): string {
    return number_format((float) $value, 2);
};

$docType = in_array($mode, ['med-list', 'med-list-date', 'return-list'], true) ? 'LIST REPORT' : 'TAX INVOICE';
?>

<style>
    @page {
        margin-top: 38mm;
        margin-bottom: 16mm;
        margin-left: 8mm;
        margin-right: 8mm;
        header: html_ipdHeader;
        footer: html_ipdFooter;
    }

    body {
        font-family: DejaVu Sans, Arial, sans-serif;
        font-size: 10px;
        color: #111827;
        margin: 0;
        padding: 0;
    }

    .screen-head {
        display: none;
    }

    .section-title {
        font-size: 11px;
        font-weight: 700;
        margin: 10px 0 5px;
        color: #111827;
    }

    .meta-table,
    .data-table,
    .summary-table,
    .payment-table {
        width: 100%;
        border-collapse: collapse;
    }

    .meta-table td {
        border: 1px solid #d1d5db;
        padding: 5px;
        vertical-align: top;
    }

    .meta-label {
        color: #374151;
    }

    .meta-value {
        font-weight: 700;
        color: #111827;
    }

    .data-table th {
        border: 1px solid #9ca3af;
        background: #f3f4f6;
        padding: 4px;
        font-weight: 700;
        font-size: 9.5px;
        text-align: left;
    }

    .data-table td {
        border: 1px solid #d1d5db;
        padding: 4px;
        font-size: 9px;
    }

    .text-right {
        text-align: right;
    }

    .text-center {
        text-align: center;
    }

    .invoice-block {
        margin-bottom: 8px;
        border: 1px solid #d1d5db;
    }

    .invoice-head {
        padding: 6px 7px;
        background: #f9fafb;
        border-bottom: 1px solid #d1d5db;
        font-size: 10px;
        font-weight: 700;
    }

    .invoice-meta {
        font-weight: 400;
        color: #374151;
        margin-left: 8px;
    }

    .return-row {
        background: #fef2f2;
    }

    .summary-table td {
        border: 1px solid #d1d5db;
        padding: 5px;
        font-size: 9.5px;
    }

    .summary-label {
        background: #f9fafb;
        font-weight: 700;
    }

    .summary-value {
        text-align: right;
        font-weight: 700;
    }

    .payment-table th,
    .payment-table td {
        border: 1px solid #d1d5db;
        padding: 4px;
        font-size: 9px;
    }

    .note {
        margin-top: 8px;
        border: 1px dashed #9ca3af;
        padding: 5px;
        color: #4b5563;
        font-size: 9px;
    }

    .sign-row {
        margin-top: 16px;
        width: 100%;
        border-collapse: collapse;
    }

    .sign-row td {
        width: 50%;
        font-size: 9px;
        padding-top: 14px;
    }

    .page-break {
        page-break-after: always;
    }

    <?php if (! $isPdf): ?>
    .screen-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .screen-head .btn {
        font-size: 12px;
        padding: 4px 8px;
        border: 1px solid #9ca3af;
        background: #fff;
        border-radius: 4px;
        text-decoration: none;
        color: #111827;
    }

    .screen-head .btn.primary {
        background: #2563eb;
        color: #fff;
        border-color: #2563eb;
    }
    <?php endif; ?>
</style>

<htmlpageheader name="ipdHeader">
    <table style="width:100%; border-collapse: collapse;" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width:62%; vertical-align: top;">
                <div style="font-size:24px; font-weight:700; color:#111827;"><?= esc($pharmacyName) ?></div>
                <div style="font-size:10px; color:#374151; line-height:1.45;">
                    <?= esc($pharmacyAddress) ?>
                    <?php if ($pharmacyPhone !== ''): ?> | Phone: <?= esc($pharmacyPhone) ?><?php endif; ?>
                    <?php if ($pharmacyEmail !== ''): ?> | Email: <?= esc($pharmacyEmail) ?><?php endif; ?><br>
                    <?php if ($pharmacyGst !== ''): ?>GST: <?= esc($pharmacyGst) ?><?php endif; ?>
                    <?php if ($pharmacyLic !== ''): ?> | L.No: <?= esc($pharmacyLic) ?><?php endif; ?>
                </div>
                <div style="margin-top:4px; font-size:12px; font-weight:700;"><?= esc($title) ?></div>
            </td>
            <td style="width:38%; vertical-align: top; font-size:10px;">
                <table style="width:100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding:1px 0; color:#374151;">Patient</td>
                        <td style="padding:1px 0; font-weight:700;">: <?= esc($patientName !== '' ? $patientName : '-') ?></td>
                    </tr>
                    <tr>
                        <td style="padding:1px 0; color:#374151;">UHID</td>
                        <td style="padding:1px 0; font-weight:700;">: <?= esc($patientCode !== '' ? $patientCode : '-') ?></td>
                    </tr>
                    <tr>
                        <td style="padding:1px 0; color:#374151;">IPD Code</td>
                        <td style="padding:1px 0; font-weight:700;">: <?= esc($ipdCode) ?></td>
                    </tr>
                    <tr>
                        <td style="padding:1px 0; color:#374151;">Refer By</td>
                        <td style="padding:1px 0; font-weight:700;">: <?= esc($doctorName !== '' ? $doctorName : '-') ?></td>
                    </tr>
                    <?php if (($ipd->org_id ?? 0) > 0): ?>
                        <tr>
                            <td style="padding:1px 0; color:#374151;">Org. Code</td>
                            <td style="padding:1px 0; font-weight:700;">: <?= esc($orgCode) ?></td>
                        </tr>
                        <tr>
                            <td style="padding:1px 0; color:#374151;">Org. Name</td>
                            <td style="padding:1px 0; font-weight:700;">: <?= esc($orgName) ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <td style="padding:1px 0; color:#374151;">Printed On</td>
                        <td style="padding:1px 0; font-weight:700;">: <?= esc(date('d-m-Y H:i')) ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <div style="margin-top:4px; border-top:1px solid #9ca3af;"></div>
</htmlpageheader>

<htmlpagefooter name="ipdFooter">
    <table style="width:100%; border-collapse: collapse; font-size:9px; color:#4b5563;">
        <tr>
            <td style="width:50%;">IPD: <?= esc($ipdCode) ?> | UHID: <?= esc($patientCode !== '' ? $patientCode : '-') ?></td>
            <td style="width:50%; text-align:right;">Page {PAGENO}/{nbpg}</td>
        </tr>
    </table>
</htmlpagefooter>

<?php if (! $isPdf): ?>
    <div class="screen-head">
        <div><strong><?= esc($title) ?></strong> (<?= esc($ipdCode) ?>)</div>
        <div>
            <button type="button" class="btn primary" onclick="window.print()">Print</button>
            <a class="btn" href="javascript:load_form_div('<?= base_url('Medical/list_med_inv/' . (int) ($ipdId ?? 0)) ?>','medical-main');">Back</a>
        </div>
    </div>
<?php endif; ?>

<table class="meta-table" style="margin-bottom:8px;">
    <tr>
        <td style="width:20%;"><span class="meta-label">Document Type</span><br><span class="meta-value"><?= esc($docType) ?></span></td>
        <td style="width:20%;"><span class="meta-label">Invoice Count</span><br><span class="meta-value"><?= esc((string) count($invoices ?? [])) ?></span></td>
        <td style="width:20%;"><span class="meta-label">Total Qty</span><br><span class="meta-value"><?= esc($fmtMoney($itemTotals['qty'] ?? 0)) ?></span></td>
        <td style="width:20%;"><span class="meta-label">Total Net</span><br><span class="meta-value">₹ <?= esc($fmtMoney($invoiceTotals['net'] ?? 0)) ?></span></td>
        <td style="width:20%;"><span class="meta-label">Balance</span><br><span class="meta-value">₹ <?= esc($fmtMoney($invoiceTotals['balance'] ?? 0)) ?></span></td>
    </tr>
</table>

<?php if (in_array($mode, ['med-list', 'med-list-date', 'return-list'], true)): ?>
    <?php if ($mode === 'med-list-date' && ! empty($itemsByDate)): ?>
        <?php foreach ($itemsByDate as $dateKey => $dateItems): ?>
            <div class="section-title">Date: <?= esc($fmtDate($dateKey, 'd-m-Y')) ?></div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:5%;">#</th>
                        <th style="width:12%;">Invoice</th>
                        <th style="width:27%;">Item Name</th>
                        <th style="width:10%;">Batch</th>
                        <th style="width:10%;">Exp.</th>
                        <th style="width:8%;" class="text-right">Qty</th>
                        <th style="width:12%;" class="text-right">Rate</th>
                        <th style="width:16%;" class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $dateAmount = 0.0; ?>
                    <?php foreach ($dateItems as $index => $item): ?>
                        <?php
                        $invId = (int) ($item->inv_med_id ?? 0);
                        $inv = $invoiceMap[$invId] ?? null;
                        $invCode = (string) ($inv->inv_med_code ?? ('M' . date('ym') . str_pad(substr((string) $invId, -7, 7), 7, '0', STR_PAD_LEFT)));
                        $amt = (float) ($item->tamount ?? $item->amount ?? 0);
                        $dateAmount += $amt;
                        ?>
                        <tr>
                            <td class="text-center"><?= (int) $index + 1 ?></td>
                            <td><?= esc($invCode) ?></td>
                            <td><?= esc(trim((string) (($item->item_Name ?? '-') . ' ' . ($item->formulation ?? '')))) ?></td>
                            <td><?= esc((string) ($item->batch_no ?? '-')) ?></td>
                            <td><?= esc($fmtDate($item->expiry, 'm-Y')) ?></td>
                            <td class="text-right"><?= esc($fmtMoney($item->qty ?? 0)) ?></td>
                            <td class="text-right"><?= esc($fmtMoney($item->price ?? 0)) ?></td>
                            <td class="text-right"><?= esc($fmtMoney($amt)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="7" class="text-right"><strong>Date Total</strong></td>
                        <td class="text-right"><strong><?= esc($fmtMoney($dateAmount)) ?></strong></td>
                    </tr>
                </tbody>
            </table>
        <?php endforeach; ?>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:5%;">#</th>
                    <th style="width:12%;">Invoice</th>
                    <th style="width:10%;">Date</th>
                    <th style="width:25%;">Item Name</th>
                    <th style="width:9%;">Batch</th>
                    <th style="width:8%;">Exp.</th>
                    <th style="width:8%;" class="text-right">Qty</th>
                    <th style="width:10%;" class="text-right">Rate</th>
                    <th style="width:13%;" class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (! empty($items)): ?>
                    <?php foreach ($items as $index => $item): ?>
                        <?php
                        $invId = (int) ($item->inv_med_id ?? 0);
                        $inv = $invoiceMap[$invId] ?? null;
                        $invCode = (string) ($inv->inv_med_code ?? ('M' . date('ym') . str_pad(substr((string) $invId, -7, 7), 7, '0', STR_PAD_LEFT)));
                        $invDate = $inv->inv_date ?? null;
                        $amount = (float) ($item->tamount ?? $item->amount ?? 0);
                        if ($mode === 'return-list') {
                            $amount = -1 * $abs($amount);
                        }
                        ?>
                        <tr class="<?= ($mode === 'return-list') ? 'return-row' : '' ?>">
                            <td class="text-center"><?= (int) $index + 1 ?></td>
                            <td><?= esc($invCode) ?></td>
                            <td><?= esc($fmtDate($invDate)) ?></td>
                            <td><?= esc(trim((string) (($item->item_Name ?? '-') . ' ' . ($item->formulation ?? '')))) ?></td>
                            <td><?= esc((string) ($item->batch_no ?? '-')) ?></td>
                            <td><?= esc($fmtDate($item->expiry, 'm-Y')) ?></td>
                            <td class="text-right"><?= esc($fmtMoney($item->qty ?? 0)) ?></td>
                            <td class="text-right"><?= esc($fmtMoney($item->price ?? 0)) ?></td>
                            <td class="text-right"><?= esc($fmtMoney($amount)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="text-center">No rows found.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="6" class="text-right">Total</th>
                    <th class="text-right"><?= esc($fmtMoney($itemTotals['qty'] ?? 0)) ?></th>
                    <th></th>
                    <th class="text-right"><?= esc($fmtMoney($itemTotals['amount'] ?? 0)) ?></th>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>
<?php else: ?>
    <?php if (! empty($invoices)): ?>
        <?php foreach ($invoices as $index => $invoice): ?>
            <?php
            $invoiceId = (int) ($invoice->id ?? 0);
            $invoiceCode = (string) ($invoice->inv_med_code ?? ('M' . date('ym') . str_pad(substr((string) $invoiceId, -7, 7), 7, '0', STR_PAD_LEFT)));
            $invoiceDate = $fmtDate($invoice->inv_date ?? null);
            $invoiceItems = $itemsByInvoice[$invoiceId] ?? [];
            $invTotals = $invoiceItemTotals[$invoiceId] ?? ['qty' => 0, 'gross' => 0, 'discount' => 0, 'gst' => 0, 'net' => 0];
            $netAmount = (float) ($invoice->net_amount ?? $invTotals['net']);
            ?>
            <div class="invoice-block">
                <div class="invoice-head">
                    Invoice: <?= esc($invoiceCode) ?>
                    <span class="invoice-meta">Date: <?= esc($invoiceDate) ?> | Type: <?= ((int) ($invoice->ipd_credit ?? 0) > 0) ? 'Credit' : 'Cash' ?></span>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:4%;">#</th>
                            <th style="width:28%;">Item Name</th>
                            <th style="width:9%;">Batch</th>
                            <th style="width:8%;">Exp.</th>
                            <th style="width:8%;" class="text-right">Qty</th>
                            <th style="width:9%;" class="text-right">Rate</th>
                            <th style="width:10%;" class="text-right">Gross</th>
                            <th style="width:8%;" class="text-right">Disc.</th>
                            <th style="width:8%;" class="text-right">Inc. GST</th>
                            <th style="width:8%;" class="text-right">Net</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (! empty($invoiceItems)): ?>
                            <?php foreach ($invoiceItems as $rowIndex => $item): ?>
                                <?php
                                $gross = (float) ($item->amount ?? 0);
                                $disc = (float) (($item->disc_amount ?? 0) + ($item->disc_whole ?? 0));
                                if ($disc == 0.0) {
                                    $disc = (float) ($item->twdisc_amount ?? 0) > 0 ? ($gross - (float) ($item->twdisc_amount ?? 0)) : 0.0;
                                }
                                $net = (float) ($item->twdisc_amount ?? ($item->tamount ?? $gross));

                                $cgstPer = (float) ($item->CGST_per ?? 0);
                                $sgstPer = (float) ($item->SGST_per ?? 0);
                                $gstPer = $cgstPer + $sgstPer;
                                $cgst = (float) ($item->CGST ?? 0);
                                $sgst = (float) ($item->SGST ?? 0);

                                if (($cgst + $sgst) <= 0.0 && $gstPer > 0.0 && $net > 0.0) {
                                    $taxableFromInclusive = $net * 100 / (100 + $gstPer);
                                    $taxTotal = $net - $taxableFromInclusive;
                                    $cgst = $taxTotal * ($cgstPer / $gstPer);
                                    $sgst = $taxTotal * ($sgstPer / $gstPer);
                                }

                                $rowIsReturn = ((float) ($item->amount ?? 0) < 0)
                                    || ((float) ($item->tamount ?? 0) < 0)
                                    || ((int) ($item->sale_return ?? 0) === 1);
                                ?>
                                <tr class="<?= $rowIsReturn ? 'return-row' : '' ?>">
                                    <td class="text-center"><?= (int) $rowIndex + 1 ?></td>
                                    <td><?= esc(trim((string) (($item->item_Name ?? '-') . ' ' . ($item->formulation ?? '')))) ?></td>
                                    <td><?= esc((string) ($item->batch_no ?? '-')) ?></td>
                                    <td><?= esc($fmtDate($item->expiry, 'm-Y')) ?></td>
                                    <td class="text-right"><?= esc($fmtMoney($item->qty ?? 0)) ?></td>
                                    <td class="text-right"><?= esc($fmtMoney($item->price ?? 0)) ?></td>
                                    <td class="text-right"><?= esc($fmtMoney($gross)) ?></td>
                                    <td class="text-right"><?= esc($fmtMoney($disc)) ?></td>
                                    <td class="text-right"><?= esc($fmtMoney($cgst + $sgst)) ?></td>
                                    <td class="text-right"><?= esc($fmtMoney($net)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="10" class="text-center">No item rows available.</td></tr>
                        <?php endif; ?>
                        <tr>
                            <td colspan="4" class="text-right"><strong>Invoice Total</strong></td>
                            <td class="text-right"><strong><?= esc($fmtMoney($invTotals['qty'] ?? 0)) ?></strong></td>
                            <td></td>
                            <td class="text-right"><strong><?= esc($fmtMoney($invTotals['gross'] ?? 0)) ?></strong></td>
                            <td class="text-right"><strong><?= esc($fmtMoney($invTotals['discount'] ?? 0)) ?></strong></td>
                            <td class="text-right"><strong><?= esc($fmtMoney($invTotals['gst'] ?? 0)) ?></strong></td>
                            <td class="text-right"><strong><?= esc($fmtMoney($invTotals['net'] ?? 0)) ?></strong></td>
                        </tr>
                    </tbody>
                </table>

                <table class="summary-table">
                    <tr>
                        <td class="summary-label" style="width:18%;">Gross Amount</td>
                        <td class="summary-value" style="width:15%;">₹ <?= esc($fmtMoney($invoice->gross_amount ?? $invTotals['gross'])) ?></td>
                        <td class="summary-label" style="width:18%;">Discount</td>
                        <td class="summary-value" style="width:15%;">₹ <?= esc($fmtMoney((($invoice->disc_amount ?? 0) + ($invoice->discount_amount ?? 0)))) ?></td>
                        <td class="summary-label" style="width:18%;">Inc. GST</td>
                        <td class="summary-value" style="width:16%;">₹ <?= esc($fmtMoney($invTotals['gst'] ?? 0)) ?></td>
                    </tr>
                    <tr>
                        <td class="summary-label">Net Amount</td>
                        <td class="summary-value" colspan="5">₹ <?= esc($fmtMoney($netAmount)) ?></td>
                    </tr>
                </table>
            </div>

            <?php if ($mode === 'pagewise' && $index < (count($invoices) - 1)): ?>
                <div class="page-break"></div>
            <?php endif; ?>
        <?php endforeach; ?>

        <div class="section-title">Grand Summary</div>
        <table class="summary-table">
            <tr>
                <td class="summary-label" style="width:20%;">Gross Total</td>
                <td class="summary-value" style="width:30%;">₹ <?= esc($fmtMoney($invoiceTotals['gross'] ?? 0)) ?></td>
                <td class="summary-label" style="width:20%;">Discount Total</td>
                <td class="summary-value" style="width:30%;">₹ <?= esc($fmtMoney($invoiceTotals['discount'] ?? 0)) ?></td>
            </tr>
            <tr>
                <td class="summary-label">Net Total</td>
                <td class="summary-value">₹ <?= esc($fmtMoney($invoiceTotals['net'] ?? 0)) ?></td>
                <td class="summary-label">Total Payment Received</td>
                <td class="summary-value">₹ <?= esc($fmtMoney($paymentSummary['total_received'] ?? 0)) ?></td>
            </tr>
            <tr>
                <td class="summary-label">Balance Total</td>
                <td class="summary-value">₹ <?= esc($fmtMoney($paymentSummary['balance'] ?? ($invoiceTotals['balance'] ?? 0))) ?></td>
                <td class="summary-label"></td>
                <td class="summary-value"></td>
            </tr>
        </table>
    <?php else: ?>
        <div class="note">No invoices found for selected print mode.</div>
    <?php endif; ?>
<?php endif; ?>

<?php if (! empty($paymentHistory)): ?>
    <div class="section-title">Payment Details</div>
    <table class="payment-table">
        <thead>
            <tr>
                <th style="width:8%;">#</th>
                <th style="width:16%;">Date</th>
                <th style="width:14%;">Mode</th>
                <th style="width:14%;">Ref No</th>
                <th style="width:26%;">Remark</th>
                <th style="width:11%;" class="text-right">Amount</th>
                <th style="width:11%;" class="text-right">Signed Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php $payTotal = 0.0; ?>
            <?php foreach ($paymentHistory as $pay): ?>
                <?php
                $payMode = ((int) ($pay->payment_mode ?? 1) === 2) ? 'Bank Card' : 'Cash';
                if ((int) ($pay->payment_mode ?? 0) > 2) {
                    $payMode = 'Other';
                }
                if ((int) ($pay->credit_debit ?? 0) > 0) {
                    $payMode .= ' Return';
                }
                $signed = (float) ($pay->paid_amount ?? $pay->amount ?? 0);
                $payTotal += $signed;
                ?>
                <tr>
                    <td class="text-center"><?= esc((string) ($pay->id ?? '-')) ?></td>
                    <td><?= esc($fmtDate($pay->payment_date ?? $pay->entry_date ?? null, 'd-m-Y H:i')) ?></td>
                    <td><?= esc($payMode) ?></td>
                    <td><?= esc((string) ($pay->card_trans_id ?? $pay->card_tran_id ?? '-')) ?></td>
                    <td><?= esc((string) ($pay->payment_desc ?? $pay->payment_remark ?? '-')) ?></td>
                    <td class="text-right"><?= esc($fmtMoney($pay->amount ?? 0)) ?></td>
                    <td class="text-right"><?= esc($fmtMoney($signed)) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="6" class="text-right"><strong>Total Paid (Signed)</strong></td>
                <td class="text-right"><strong><?= esc($fmtMoney($payTotal)) ?></strong></td>
            </tr>
        </tbody>
    </table>
<?php endif; ?>

<div class="note">
    This is a computer-generated document. Please verify medicine name, batch, and quantity at issue time.
</div>

<table class="sign-row">
    <tr>
        <td>Patient/Relative Signature</td>
        <td style="text-align:right;">For <?= esc($pharmacyName) ?> (Authorized Signatory)</td>
    </tr>
</table>
