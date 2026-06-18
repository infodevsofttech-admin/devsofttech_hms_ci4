<?php $invoice = $purchase_invoice ?? null; ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Purchase Invoice</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; margin: 18px; color: #1f2937; }
        .title { font-size: 22px; font-weight: 700; color: #0f2f67; }
        .sub { margin-top: 4px; color: #6b7280; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; font-size: 13px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; }
        .right { text-align: right; }
    </style>
</head>
<body>
<div class="title">Purchase Invoice</div>
<div class="sub">Supplier: <?= esc((string) ($invoice->name_supplier ?? '-')) ?> | Invoice No.: <?= esc((string) ($invoice->Invoice_no ?? '')) ?> | Date: <?= esc((string) ($invoice->str_date_of_invoice ?? '')) ?></div>

<table>
    <thead>
    <tr>
        <th>#</th>
        <th>Item</th>
        <th>Batch</th>
        <th>Exp.</th>
        <th class="right">Qty</th>
        <th class="right">Rate</th>
        <th class="right">Amount</th>
        <th class="right">Net</th>
    </tr>
    </thead>
    <tbody>
    <?php $srno = 0; foreach (($purchase_invoice_item ?? []) as $row): $srno++; ?>
        <tr>
            <td><?= $srno ?></td>
            <td><?= esc((string) ($row->Item_name ?? $row->item_name ?? '')) ?></td>
            <td><?= esc((string) ($row->batch_no ?? '-')) ?></td>
            <td><?= esc((string) ($row->expiry_date ?? '-')) ?></td>
            <td class="right"><?= esc((string) ((float) ($row->qty ?? 0))) ?></td>
            <td class="right"><?= esc((string) ($row->purchase_price ?? '0')) ?></td>
            <td class="right"><?= esc((string) ($row->amount ?? '0')) ?></td>
            <td class="right"><?= esc((string) ($row->net_amount ?? '0')) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>