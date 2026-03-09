<?php
$ipd = $ipd_info ?? null;
$person = $person_info ?? null;
$ipdId = (int) ($ipd_id ?? 0);
$printType = (int) ($print_type ?? 1);
$previewHtml = (string) ($content ?? '');

$patientName = trim((string) ($person->p_fname ?? ''));
$patientCode = trim((string) (
    $person->uhid
    ?? $person->UHID
    ?? $person->patient_code
    ?? $person->p_code
    ?? $person->reg_no
    ?? ''
));

$title = 'IPD Discharge Preview';
if ($printType === 0) {
    $title = 'IPD Discharge Preview (Plain Paper)';
} elseif ($printType === 3) {
    $title = 'IPD Discharge Preview (New Print)';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8fafc;
            color: #111827;
            margin: 0;
        }
        .print-wrap {
            width: 900px;
            max-width: 100%;
            margin: 16px auto;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            box-sizing: border-box;
        }
        .meta {
            font-size: 13px;
            margin-bottom: 14px;
            line-height: 1.6;
        }
        .meta strong {
            color: #0f172a;
        }
        .content {
            border-top: 1px dashed #cbd5e1;
            padding-top: 12px;
        }
        @media print {
            body {
                background: #ffffff;
            }
            .print-wrap {
                border: none;
                margin: 0;
                padding: 0;
                width: auto;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <div class="print-wrap">
        <h2 style="margin:0 0 10px 0;"><?= esc($title) ?></h2>
        <div class="meta">
            <div><strong>IPD:</strong> <?= esc($ipd->ipd_code ?? $ipdId) ?></div>
            <div><strong>Patient:</strong> <?= esc($patientName) ?></div>
            <div><strong>UHID:</strong> <?= esc($patientCode) ?></div>
            <div><strong>Date:</strong> <?= esc(date('d-m-Y h:i A')) ?></div>
        </div>

        <div class="content">
            <?= $previewHtml ?>
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
