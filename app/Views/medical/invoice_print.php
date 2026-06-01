<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Invoice <?= esc($invoice->inv_med_code ?? ('M' . date('ym') . str_pad(substr((string) ((int)($invoice->id ?? 0)), -7, 7), 7, '0', STR_PAD_LEFT))) ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #222; margin: 20px; }
        .row { display: flex; justify-content: space-between; gap: 12px; }
        .mb-8 { margin-bottom: 8px; }
        .mb-12 { margin-bottom: 12px; }
        .mb-16 { margin-bottom: 16px; }
        .title { font-size: 20px; font-weight: 700; margin-bottom: 8px; }
        .sub { color: #666; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 6px; text-align: left; }
        th { background: #f3f3f3; }
        .text-end { text-align: right; }
        .totals td { font-weight: 700; }
        .print-actions { margin-bottom: 12px; }
        @media print {
            .print-actions { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <?php
        $pharmacyName = defined('H_Med_Name') ? (string) constant('H_Med_Name') : ((defined('M_store') ? (string) constant('M_store') : 'Medical Store'));
        $pharmacyAddress = defined('H_Med_address_1') ? (string) constant('H_Med_address_1') : (defined('M_address') ? (string) constant('M_address') : '');
        $pharmacyPhone = defined('H_Med_phone_No') ? (string) constant('H_Med_phone_No') : (defined('M_Phone_Number') ? (string) constant('M_Phone_Number') : '');
        $pharmacyGst = defined('H_Med_GST') ? (string) constant('H_Med_GST') : '';

        $doctorName = trim((string) ($invoice->doc_name ?? ''));
        $doctorLabel = $doctorName !== '' ? (preg_match('/^dr\.?\s*/i', $doctorName) ? $doctorName : 'Dr. ' . $doctorName) : '-';
        $items = $items ?? [];
        $rowCount = count($items);
        $baseGrossTotal = 0.0;
        $baseDiscountTotal = 0.0;
        if (! empty($items)) {
            foreach ($items as $printItem) {
                $baseGrossTotal += (float) ($printItem->amount ?? 0);
                $baseDiscountTotal += (float) ($printItem->disc_amount ?? 0);
            }
        }
        $invoiceDiscountTotal = (float) (($invoice->discount_amount ?? 0) + ($invoice->disc_amount ?? 0) + ($invoice->item_discount_amount ?? 0));
        $extraInvoiceDiscountTotal = max(0.0, $invoiceDiscountTotal - $baseDiscountTotal);
        $allocatedExtraDiscountTotal = 0.0;
    ?>
    <div class="print-actions">
        <button onclick="window.print()">Print</button>
        <button onclick="window.close()">Close</button>
    </div>

    <div class="title"><?= esc($pharmacyName !== '' ? $pharmacyName : 'Medical Store') ?></div>
    <div class="sub"><?= esc($pharmacyAddress) ?><?php if ($pharmacyPhone !== ''): ?> | Phone: <?= esc($pharmacyPhone) ?><?php endif; ?><?php if ($pharmacyGst !== ''): ?> | GSTIN: <?= esc($pharmacyGst) ?><?php endif; ?></div>
    <div class="sub mb-12"><strong>Medical Invoice No:</strong> <?= esc($invoice->inv_med_code ?? ('M' . date('ym') . str_pad(substr((string) ((int)($invoice->id ?? 0)), -7, 7), 7, '0', STR_PAD_LEFT))) ?></div>

    <div class="row mb-12">
        <div>
            <div class="mb-8"><strong>Date:</strong> <?= esc($invoice->inv_date ?? '-') ?></div>
            <div class="mb-8"><strong>Patient:</strong> <?= esc($invoice->inv_name ?? '-') ?></div>
            <div class="mb-8"><strong>Patient Code:</strong> <?= esc($invoice->patient_code ?? '-') ?></div>
        </div>
        <div>
            <div class="mb-8"><strong>Refer By:</strong> <?= esc($doctorLabel) ?></div>
            <div class="mb-8"><strong>Phone:</strong> <?= esc($invoice->inv_phone_number ?? ($patient->mphone1 ?? '-')) ?></div>
            <div class="mb-8"><strong>Type:</strong> <?= ((int)($invoice->ipd_credit ?? 0) === 1) ? 'Credit' : 'Cash' ?></div>
        </div>
    </div>

    <table class="mb-16">
        <thead>
            <tr>
                <th style="width:40px;">#</th>
                <th>Item</th>
                <th style="width:80px;">Batch</th>
                <th style="width:90px;" class="text-end">Qty</th>
                <th style="width:90px;" class="text-end">Rate</th>
                <th style="width:100px;" class="text-end">Discount</th>
                <th style="width:110px;" class="text-end">Amount</th>
                <th style="width:110px;" class="text-end">Net</th>
            </tr>
        </thead>
        <tbody>
            <?php
                $gross = 0.0;
                $discount = 0.0;
                $net = 0.0;
            ?>
            <?php if (! empty($items)): ?>
                <?php foreach ($items as $idx => $item): ?>
                    <?php
                        $rowGross = (float) ($item->amount ?? 0);
                        $baseRowDisc = (float) ($item->disc_amount ?? 0);
                        $baseRowNet = (float) ($item->twdisc_amount ?? $item->tamount ?? ($item->net_amount ?? 0));
                        $remainingExtraDiscount = max(0.0, $extraInvoiceDiscountTotal - $allocatedExtraDiscountTotal);
                        $allocExtraDisc = 0.0;
                        if ($remainingExtraDiscount > 0 && $baseGrossTotal > 0) {
                            if ((int) $idx === $rowCount - 1) {
                                $allocExtraDisc = $remainingExtraDiscount;
                            } else {
                                $allocExtraDisc = round($extraInvoiceDiscountTotal * ($rowGross / $baseGrossTotal), 2);
                                if ($allocExtraDisc > $remainingExtraDiscount) {
                                    $allocExtraDisc = $remainingExtraDiscount;
                                }
                            }
                        }
                        $allocatedExtraDiscountTotal += $allocExtraDisc;
                        $rowDisc = $baseRowDisc + $allocExtraDisc;
                        $rowNet = max(0.0, $baseRowNet - $allocExtraDisc);
                        $gross += $rowGross;
                        $discount += $rowDisc;
                        $net += $rowNet;
                    ?>
                    <tr>
                        <td><?= $idx + 1 ?></td>
                        <td><?= esc($item->item_Name ?? '-') ?><?= !empty($item->formulation) ? ' / ' . esc($item->formulation) : '' ?></td>
                        <td><?= esc($item->batch_no ?? '-') ?></td>
                        <td class="text-end"><?= esc(number_format((float)($item->qty ?? 0), 2)) ?></td>
                        <td class="text-end"><?= esc(number_format((float)($item->price ?? 0), 2)) ?></td>
                        <td class="text-end"><?= esc(number_format($rowDisc, 2)) ?></td>
                        <td class="text-end"><?= esc(number_format($rowGross, 2)) ?></td>
                        <td class="text-end"><?= esc(number_format($rowNet, 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-end">No items found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <?php
                $printGrossTotal = (float) ($invoice->gross_amount ?? $gross);
                $printDiscountTotal = (float) (($invoice->discount_amount ?? 0) + ($invoice->disc_amount ?? 0) + ($invoice->item_discount_amount ?? 0));
                $printDiscountTotal = $printDiscountTotal > 0 ? $printDiscountTotal : $discount;
                $printNetTotal = $printGrossTotal - $printDiscountTotal;
                if ($printNetTotal < 0) {
                    $printNetTotal = 0.0;
                }
            ?>
            <tr class="totals">
                <td colspan="6" class="text-end">Gross</td>
                <td colspan="2" class="text-end"><?= esc(number_format($printGrossTotal, 2)) ?></td>
            </tr>
            <tr class="totals">
                <td colspan="6" class="text-end">Discount</td>
                <td colspan="2" class="text-end"><?= esc(number_format($printDiscountTotal, 2)) ?></td>
            </tr>
            <tr class="totals">
                <td colspan="6" class="text-end">Net Amount</td>
                <td colspan="2" class="text-end"><?= esc(number_format($printNetTotal, 2)) ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="row" style="margin-top:40px;">
        <div><strong>Prepared By:</strong> _____________________</div>
        <div><strong>Authorized Sign:</strong> _____________________</div>
    </div>
</body>
</html>
