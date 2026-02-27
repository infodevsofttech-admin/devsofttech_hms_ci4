<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($invoice->invoice_code ?? 'Report') ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; color: #111; }
        .toolbar { position: sticky; top: 0; background: #f8f9fa; border-bottom: 1px solid #ddd; padding: 10px 14px; z-index: 10; }
        .toolbar button { padding: 7px 14px; border: 1px solid #bbb; background: #fff; cursor: pointer; }
        .page { padding: 18px 26px; }
        .letterhead { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
        .letterhead .title { font-size: 28px; font-weight: 700; }
        .letterhead .sub { font-size: 14px; color: #333; }
        .meta { margin-bottom: 10px; font-size: 14px; }
        .meta strong { display: inline-block; min-width: 120px; }
        .divider { border-top: 1px solid #bbb; margin: 10px 0 14px; }
        @media print {
            .toolbar { display: none; }
            .page { padding: 8mm 10mm; }
        }
    </style>
</head>
<body>
    <?php
    $hospitalName = H_Name;
    $hospitalAddress1 = H_address_1;
    $hospitalAddress2 = H_address_2;
    $hospitalPhone = H_phone_No;
    $hospitalAddress = trim($hospitalAddress1 . (empty($hospitalAddress2) ? '' : ', ' . $hospitalAddress2));
    ?>

    <div class="toolbar">
        <button type="button" onclick="window.print()">Print</button>
    </div>

    <div class="page">
        <?php if (! $isPlainPaper): ?>
            <div class="letterhead">
                <div>
                    <div class="title"><?= esc($hospitalName) ?></div>
                    <div class="sub"><?= esc($hospitalAddress) ?></div>
                    <div class="sub"><?= esc($hospitalPhone) ?></div>
                </div>
                <div style="text-align:right; font-size:12px; color:#666;">Letterhead</div>
            </div>
        <?php endif; ?>

        <div class="meta">
            <div><strong>Invoice ID:</strong> <?= esc($invoice->invoice_code ?? '') ?></div>
            <div><strong>Patient Name:</strong> <?= esc($patient->p_fname ?? '') ?></div>
            <div><strong>UHID:</strong> <?= esc($patient->p_code ?? '') ?></div>
            <div><strong>Report Type:</strong> <?= esc($reportHead ?? 'Lab Report') ?></div>
        </div>

        <div class="divider"></div>

        <?php if (! empty($invoiceRequest->report_header)): ?>
            <div><?= $invoiceRequest->report_header ?></div>
        <?php endif; ?>

        <div><?= $invoiceRequest->report_data ?? '' ?></div>
    </div>
</body>
</html>
