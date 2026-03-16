<?php
$hospitalName = defined('H_Name') ? (string) constant('H_Name') : 'Hospital Name';
$hospitalAddress = defined('H_address_1') ? (string) constant('H_address_1') : 'Hospital Address';
$hospitalPhone = defined('H_phone_No') ? (string) constant('H_phone_No') : '';

$qrPayload = implode('|', [
    'GRN:' . (string) ($grn['grn_no'] ?? ''),
    'Date:' . (string) ($grn['grn_date'] ?? ''),
    'PO:' . (string) ($grn['po_no'] ?? ''),
    'Vendor:' . trim((string) ($grn['vendor_name'] ?? '')),
    'Amount:' . number_format((float) ($grn['received_amount'] ?? 0), 2, '.', ''),
]);
$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=' . rawurlencode($qrPayload);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>GRN Vendor Copy</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; color: #111; margin: 20px; }
        .hospital-head { border-bottom: 2px solid #1f3a56; padding-bottom: 10px; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: flex-start; }
        .hospital-name { font-size: 24px; font-weight: 700; color: #1f3a56; line-height: 1.1; }
        .hospital-address { font-size: 12px; color: #333; margin-top: 4px; }
        .qr-box { text-align: center; border: 1px solid #d4d4d4; padding: 5px; border-radius: 4px; background: #fff; }
        .qr-box img { width: 110px; height: 110px; display: block; }
        .qr-caption { font-size: 11px; color: #666; margin-top: 3px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .title { font-size: 20px; font-weight: 700; }
        .copy-tag { border: 1px solid #222; padding: 4px 10px; font-weight: 700; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .meta td { border: 1px solid #ddd; padding: 8px; vertical-align: top; }
        .label { color: #555; font-size: 12px; }
        .value { font-weight: 600; }
        .notes { border: 1px solid #ddd; padding: 10px; min-height: 70px; margin-top: 10px; white-space: pre-wrap; }
        .sign-row { margin-top: 30px; display: flex; justify-content: space-between; }
        .sign { width: 45%; border-top: 1px solid #777; padding-top: 6px; text-align: center; }
        .print-line { margin-top: 20px; color: #666; font-size: 12px; }
        @media print {
            .no-print { display: none; }
            body { margin: 10px; }
        }
    </style>
</head>
<body>
    <div class="hospital-head">
        <div>
            <div class="hospital-name"><?= esc($hospitalName) ?></div>
            <div class="hospital-address">
                <?= esc($hospitalAddress) ?>
                <?php if ($hospitalPhone !== ''): ?>
                    | Phone: <?= esc($hospitalPhone) ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="qr-box">
            <img src="<?= esc($qrUrl, 'attr') ?>" alt="GRN QR Code">
            <div class="qr-caption">GRN Verification QR</div>
        </div>
    </div>

    <div class="header">
        <div class="title">Goods Receipt Note</div>
        <div class="copy-tag">Vendor Copy</div>
    </div>

    <table class="meta">
        <tr>
            <td width="50%">
                <div class="label">GRN No</div>
                <div class="value"><?= esc((string) ($grn['grn_no'] ?? '')) ?></div>
            </td>
            <td width="50%">
                <div class="label">GRN Date</div>
                <div class="value"><?= esc((string) ($grn['grn_date'] ?? '')) ?></div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">PO No</div>
                <div class="value"><?= esc((string) ($grn['po_no'] ?? '')) ?></div>
            </td>
            <td>
                <div class="label">PO Date</div>
                <div class="value"><?= esc((string) ($grn['po_date'] ?? '')) ?></div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Vendor</div>
                <div class="value"><?= esc(trim((string) ($grn['vendor_code'] ?? '') . ' - ' . (string) ($grn['vendor_name'] ?? ''))) ?></div>
                <?php if (! empty($grn['vendor_phone'])): ?>
                    <div><?= esc((string) $grn['vendor_phone']) ?></div>
                <?php endif; ?>
            </td>
            <td>
                <div class="label">Received By</div>
                <div class="value"><?= esc((string) ($grn['received_by'] ?? '')) ?></div>
                <div class="label" style="margin-top:8px;">Received Amount</div>
                <div class="value"><?= number_format((float) ($grn['received_amount'] ?? 0), 2) ?></div>
            </td>
        </tr>
    </table>

    <div class="label">Notes / Remarks</div>
    <div class="notes"><?= esc((string) ($grn['remarks'] ?? '-')) ?></div>

    <div class="sign-row">
        <div class="sign">Vendor Signature</div>
        <div class="sign">Store / Receiving Signature</div>
    </div>

    <div class="print-line">Printed at: <?= esc((string) ($printed_at ?? '')) ?></div>

    <div class="no-print" style="margin-top: 16px;">
        <button type="button" onclick="window.print();">Print</button>
    </div>
</body>
</html>
