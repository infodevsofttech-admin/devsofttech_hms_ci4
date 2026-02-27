<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Single Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #111; }
        .toolbar { margin-bottom: 16px; }
        .toolbar button { padding: 8px 14px; border: 1px solid #ccc; background: #f8f8f8; cursor: pointer; }
        .meta { margin-bottom: 12px; font-size: 14px; color: #444; }
        .report-content { border-top: 1px solid #ddd; padding-top: 12px; }
        @media print {
            .toolbar { display: none; }
            body { margin: 10mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print</button>
    </div>

    <div class="meta">
        <div><strong>Patient:</strong> <?= esc($report->patient_name ?? '') ?></div>
        <div><strong>Invoice:</strong> <?= esc($report->invoice_code ?? '') ?></div>
    </div>

    <div class="report-content">
        <?php if (! empty($report->Report_Data)): ?>
            <?= $report->Report_Data ?>
        <?php else: ?>
            <p>No report data available for this request.</p>
        <?php endif; ?>
    </div>
</body>
</html>
