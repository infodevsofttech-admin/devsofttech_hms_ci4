<?php
$patient = $patient ?? null;
$invoices = $invoices ?? [];
$itemsByInvoice = $itemsByInvoice ?? [];
$paymentsByInvoice = $paymentsByInvoice ?? [];
$dateFrom = (string) ($dateFrom ?? '');
$dateTo = (string) ($dateTo ?? '');
$searchUhid = (string) ($searchUhid ?? '');
$grand = $grand ?? ['net' => 0, 'paid' => 0, 'balance' => 0];

$pharmacyName = defined('M_store') ? (string) M_store : 'Medical Store';
$pharmacyAddress = defined('M_address') ? (string) M_address : '';
$pharmacyPhone = defined('M_Phone_Number') ? (string) M_Phone_Number : '';
$pharmacyGst = defined('H_Med_GST') ? (string) H_Med_GST : '';

$patientName = (string) ($patient->p_fname ?? '-');
$patientCode = (string) ($patient->p_code ?? $searchUhid ?: '-');
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #111; }
        .title { font-size: 18px; font-weight: 700; margin-bottom: 2px; }
        .meta { font-size: 10px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 4px; vertical-align: top; }
        th { background: #f1f5f9; font-weight: 700; }
        .no-border { border: none !important; }
        .right { text-align: right; }
        .section-title { margin: 10px 0 4px; font-size: 12px; font-weight: 700; }
        .invoice-head { background: #e5e7eb; font-weight: 700; }
        .totals td { font-weight: 700; background: #f9fafb; }
        .small { font-size: 9px; }
    </style>
</head>
<body>
    <div class="title"><?= esc($pharmacyName) ?></div>
    <div class="meta">
        <?= esc($pharmacyAddress) ?>
        <?php if ($pharmacyPhone !== ''): ?> | Phone: <?= esc($pharmacyPhone) ?><?php endif; ?>
        <?php if ($pharmacyGst !== ''): ?> | GST: <?= esc($pharmacyGst) ?><?php endif; ?><br>
        <strong>Print Bill on UHID</strong> | Date Range: <?= esc($dateFrom) ?> to <?= esc($dateTo) ?> | UHID: <?= esc($patientCode) ?> | Patient: <?= esc($patientName) ?>
    </div>

    <?php if ($invoices === []): ?>
        <table>
            <tr><td>No record found.</td></tr>
        </table>
    <?php else: ?>
        <?php foreach ($invoices as $invoice): ?>
            <?php
            $invoiceId = (int) ($invoice->id ?? 0);
            $invoiceCode = (string) ($invoice->inv_med_code ?? ('M' . date('ym') . str_pad(substr((string) $invoiceId, -7, 7), 7, '0', STR_PAD_LEFT)));
            $invoiceDate = !empty($invoice->inv_date) ? date('d-m-Y', strtotime((string) $invoice->inv_date)) : '-';
            $docName = trim((string) ($invoice->doc_name ?? '-'));
            $itemRows = $itemsByInvoice[$invoiceId] ?? [];
            $paymentRows = $paymentsByInvoice[$invoiceId] ?? [];
            ?>

            <div class="section-title">Invoice: <?= esc($invoiceCode) ?> | Date: <?= esc($invoiceDate) ?> | Doctor: <?= esc($docName !== '' ? $docName : '-') ?></div>

            <table>
                <thead>
                    <tr>
                        <th style="width:4%;">#</th>
                        <th style="width:28%;">Item Name</th>
                        <th style="width:10%;">Batch</th>
                        <th style="width:8%;">Exp.</th>
                        <th style="width:8%;" class="right">Qty</th>
                        <th style="width:8%;" class="right">Rate</th>
                        <th style="width:10%;" class="right">Gross</th>
                        <th style="width:8%;" class="right">Disc.</th>
                        <th style="width:8%;" class="right">Inc. GST</th>
                        <th style="width:8%;" class="right">Net</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($itemRows === []): ?>
                        <tr><td colspan="10">No items</td></tr>
                    <?php else: ?>
                        <?php foreach ($itemRows as $idx => $item): ?>
                            <?php
                            $gross = (float) ($item->amount ?? 0);
                            $disc = (float) (($item->disc_amount ?? 0) + ($item->disc_whole ?? 0));
                            $net = (float) ($item->twdisc_amount ?? $item->tamount ?? $gross);
                            $cgst = (float) ($item->CGST ?? 0);
                            $sgst = (float) ($item->SGST ?? 0);
                            ?>
                            <tr>
                                <td><?= (int) $idx + 1 ?></td>
                                <td><?= esc(trim((string) (($item->item_Name ?? $item->item_name ?? '-') . ' ' . ($item->formulation ?? '')))) ?></td>
                                <td><?= esc((string) ($item->batch_no ?? '-')) ?></td>
                                <td><?= esc(!empty($item->expiry) ? date('m-Y', strtotime((string) $item->expiry)) : '-') ?></td>
                                <td class="right"><?= esc(number_format((float) ($item->qty ?? 0), 2)) ?></td>
                                <td class="right"><?= esc(number_format((float) ($item->price ?? 0), 2)) ?></td>
                                <td class="right"><?= esc(number_format($gross, 2)) ?></td>
                                <td class="right"><?= esc(number_format($disc, 2)) ?></td>
                                <td class="right"><?= esc(number_format($cgst + $sgst, 2)) ?></td>
                                <td class="right"><?= esc(number_format($net, 2)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="totals">
                    <tr>
                        <td colspan="6" class="right">Invoice Total</td>
                        <td class="right"><?= esc(number_format((float) ($invoice->gross_amount ?? 0), 2)) ?></td>
                        <td class="right"><?= esc(number_format((float) (($invoice->disc_amount ?? 0) + ($invoice->discount_amount ?? 0)), 2)) ?></td>
                        <td class="right"><?= esc(number_format((float) (($invoice->CGST_Tamount ?? 0) + ($invoice->SGST_Tamount ?? 0)), 2)) ?></td>
                        <td class="right"><?= esc(number_format((float) ($invoice->net_amount ?? 0), 2)) ?></td>
                    </tr>
                </tfoot>
            </table>

            <?php if ($paymentRows !== []): ?>
                <table class="small" style="margin-top:4px;">
                    <tr>
                        <td><strong>Payment Details:</strong>
                            <?php foreach ($paymentRows as $pay): ?>
                                <?php
                                $mode = ((int) ($pay->payment_mode ?? 1) === 2) ? 'Bank Card' : 'Cash';
                                if ((int) ($pay->payment_mode ?? 0) > 2) {
                                    $mode = 'Other';
                                }
                                if ((int) ($pay->credit_debit ?? 0) > 0) {
                                    $mode .= ' Return';
                                }
                                ?>
                                [<?= esc((string) ($pay->id ?? '-')) ?>:<?= esc($mode) ?>:<?= esc(number_format((float) ($pay->amount ?? 0), 2)) ?>]
                            <?php endforeach; ?>
                        </td>
                    </tr>
                </table>
            <?php endif; ?>

            <hr>
        <?php endforeach; ?>

        <table>
            <tr>
                <th style="width:33%;">Total Net Amount</th>
                <th style="width:33%;">Paid Amount</th>
                <th style="width:34%;">Balance Amount</th>
            </tr>
            <tr>
                <td class="right"><?= esc(number_format((float) ($grand['net'] ?? 0), 2)) ?></td>
                <td class="right"><?= esc(number_format((float) ($grand['paid'] ?? 0), 2)) ?></td>
                <td class="right"><?= esc(number_format((float) ($grand['balance'] ?? 0), 2)) ?></td>
            </tr>
        </table>
    <?php endif; ?>
</body>
</html>
