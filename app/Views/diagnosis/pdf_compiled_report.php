<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #111; font-size: 12px; margin: 0; padding: 0; }
        .section-title { font-size: 20px; font-weight: 700; text-align: center; margin: 4px 0 8px; }
        table.meta-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.meta-table td { vertical-align: top; padding: 0 4px; font-size: 11px; }
        .label { font-weight: 700; display: inline-block; min-width: 72px; }
        .divider { border-top: 1px solid #999; margin: 6px 0 8px; }
        .report-content { font-size: 12px; line-height: 1.45; }
        .plain-header { margin-bottom: 8px; }
    </style>
</head>
<body>
<?php
$patientName = trim((string) ($patient->p_fname ?? ''));
$relativeName = trim((string) ($patient->p_relative ?? ''));
$relativeText = trim((string) ($patient->p_rname ?? ''));
$plainHeaderHtml = (string) ($printSetting['plain_header_html'] ?? '');
$title = trim((string) ($itemType->group_desc ?? 'Compiled Report'));
?>

<?php if (($isPlainPaper ?? false) && trim($plainHeaderHtml) !== ''): ?>
    <div class="plain-header"><?= $plainHeaderHtml ?></div>
<?php endif; ?>

<div class="section-title"><?= esc($title) ?></div>

<?php $patientInfoHtml = (string) ($patientInfoHtml ?? ''); ?>
<?php if ($patientInfoHtml !== ''): ?>
    <?= $patientInfoHtml ?>
<?php endif; ?>

<div class="divider"></div>
<div class="report-content"><?= $reportHtml ?? '' ?></div>
</body>
</html>
