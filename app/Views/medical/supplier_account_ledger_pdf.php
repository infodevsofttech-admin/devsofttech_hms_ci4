<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Supplier Ledger</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #111; }
        h2 { margin: 0 0 8px 0; font-size: 18px; }
        .meta { margin: 0 0 10px 0; font-size: 11px; }
        .summary {
            border: 1px solid #cfcfcf;
            padding: 8px;
            margin-bottom: 10px;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #444;
            padding: 5px 6px;
            vertical-align: top;
        }
        th { background: #f2f2f2; }
        .text-end { text-align: right; }
    </style>
</head>
<body>
    <?php
    $pharmacyName = defined('M_store') ? (string) M_store : '';
    $pharmacyAddress = defined('M_address') ? (string) M_address : '';
    $pharmacyPhone = defined('M_Phone_Number') ? (string) M_Phone_Number : '';
    $pharmacyState = defined('M_State') ? (string) M_State : '';
    $pharmacyGst = defined('H_Med_GST') ? (string) H_Med_GST : '';
    ?>

    <div style="text-align:center; margin-bottom:8px; border-bottom:1px solid #777; padding-bottom:8px;">
        <div style="font-size:20px; font-weight:bold;"><?= esc($pharmacyName !== '' ? $pharmacyName : 'Pharmacy') ?></div>
        <div style="font-size:11px;"><?= esc($pharmacyAddress) ?><?= $pharmacyState !== '' ? ', ' . esc($pharmacyState) : '' ?></div>
        <div style="font-size:11px;">
            <?php if ($pharmacyPhone !== ''): ?>
                Phone: <?= esc($pharmacyPhone) ?>
            <?php endif; ?>
            <?php if ($pharmacyPhone !== '' && $pharmacyGst !== ''): ?> | <?php endif; ?>
            <?php if ($pharmacyGst !== ''): ?>
                GST No.: <?= esc($pharmacyGst) ?>
            <?php endif; ?>
        </div>
    </div>

    <h2>Supplier Ledger: <?= esc($supplier->name_supplier ?? '-') ?></h2>
    <?php
    $supplierAddress = '';
    foreach (['address', 'address1', 'add1', 'add2'] as $addrField) {
        if (!empty($supplier->{$addrField} ?? '')) {
            $supplierAddress = trim((string) $supplier->{$addrField});
            break;
        }
    }

    $supplierCity = trim((string) ($supplier->city ?? ''));
    $supplierState = trim((string) ($supplier->state ?? ''));
    $supplierPhone = trim((string) ($supplier->contact_no ?? ''));
    $supplierGst = trim((string) ($supplier->gst_no ?? ''));

    $supplierAddressLine = $supplierAddress;
    if ($supplierCity !== '') {
        $supplierAddressLine .= ($supplierAddressLine !== '' ? ', ' : '') . $supplierCity;
    }
    if ($supplierState !== '') {
        $supplierAddressLine .= ($supplierAddressLine !== '' ? ', ' : '') . $supplierState;
    }
    ?>
    <div class="meta" style="margin-bottom:6px;">
        <?php if ($supplierAddressLine !== ''): ?>
            Supplier Address: <?= esc($supplierAddressLine) ?><br>
        <?php endif; ?>
        <?php if ($supplierPhone !== ''): ?>
            Supplier Phone: <?= esc($supplierPhone) ?>
        <?php endif; ?>
        <?php if ($supplierPhone !== '' && $supplierGst !== ''): ?> | <?php endif; ?>
        <?php if ($supplierGst !== ''): ?>
            Supplier GST No.: <?= esc($supplierGst) ?>
        <?php endif; ?>
    </div>
    <div class="meta">Date Range: <?= esc((string) ($dateFrom ?? '')) ?> to <?= esc((string) ($dateTo ?? '')) ?></div>

    <div class="summary">
        Opening Balance: <strong><?= esc(number_format((float) ($balance_till_date ?? 0), 2)) ?></strong>
        | Total Credit: <strong><?= esc(number_format((float) ($cr_total ?? 0), 2)) ?></strong>
        | Total Debit: <strong><?= esc(number_format((float) ($dr_total ?? 0), 2)) ?></strong>
        | Closing Balance: <strong><?= esc(number_format((float) ($balance_till_date_close ?? 0), 2)) ?></strong>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:6%;">#</th>
                <th style="width:16%;">Date Tran</th>
                <th>Description</th>
                <th class="text-end" style="width:16%;">Credit</th>
                <th class="text-end" style="width:16%;">Debit</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 0; foreach (($med_supplier_ledger ?? []) as $row): $i++; ?>
                <tr>
                    <td><?= esc((string) $i) ?></td>
                    <td><?= esc(!empty($row->tran_date) ? date('d-m-Y', strtotime((string) $row->tran_date)) : '') ?></td>
                    <td><?= esc(trim((string) (($row->mode_desc ?? '') . ' ' . ($row->tran_desc ?? '')))) ?></td>
                    <td class="text-end"><?= (int) ($row->credit_debit ?? 0) === 0 ? esc(number_format((float) ($row->amount ?? 0), 2)) : '' ?></td>
                    <td class="text-end"><?= (int) ($row->credit_debit ?? 0) === 1 ? esc(number_format((float) ($row->amount ?? 0), 2)) : '' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
