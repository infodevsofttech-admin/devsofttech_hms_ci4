<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #111; font-size: 12px; margin: 0; padding: 0; }
        .letterhead { margin-bottom: 8px; }
        .letterhead-title { font-size: 24px; font-weight: 700; line-height: 1.1; }
        .letterhead-sub { font-size: 11px; color: #222; line-height: 1.25; }
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
$patientName    = (string) ($patientName    ?? trim((string) ($report->p_fname ?? '')));
$relativeName   = (string) ($relativeName   ?? trim((string) ($report->p_relative ?? '')));
$relativeText   = (string) ($relativeText   ?? trim((string) ($report->p_rname ?? '')));
$repoTitle      = (string) ($repoTitle      ?? trim((string) ($report->report_name ?? $report->repo_title ?? $report->RepoGrp ?? 'Report')));
$plainHeaderHtml = (string) ($printSetting['plain_header_html'] ?? '');
$patientInfoHtml = (string) ($patientInfoHtml ?? '');
?>

<?php if (($isPlainPaper ?? false) && trim($plainHeaderHtml) !== ''): ?>
    <div class="plain-header"><?= $plainHeaderHtml ?></div>
<?php endif; ?>


<?php if ($patientInfoHtml !== ''): ?>
    <?= $patientInfoHtml ?>
<?php endif; ?>

<div class="divider"></div>
<div class="section-title"><?= esc($repoTitle) ?></div>
<div class="report-content"><?= $reportHtml ?? '' ?></div>
</body>
</html>
