<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($report->invoice_code ?? 'Report') ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; color: #111; }
        .toolbar { position: sticky; top: 0; background: #f8f9fa; border-bottom: 1px solid #ddd; padding: 10px 14px; z-index: 10; }
        .toolbar button { padding: 8px 14px; border: 1px solid #bbb; background: #fff; cursor: pointer; }
        .toolbar .btn-link { text-decoration: none; color: #0d6efd; margin-left: 10px; font-size: 13px; }
        .page { padding: 18px 26px; }
        .letterhead { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
        .letterhead .title { font-size: 28px; font-weight: 700; }
        .letterhead .sub { font-size: 14px; color: #333; }
        .section-title { font-size: 18px; font-weight: 700; text-align: center; margin: 8px 0 10px; }
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .meta-table td { vertical-align: top; padding: 2px 6px; font-size: 13px; }
        .meta-table .label { font-weight: 700; min-width: 84px; display: inline-block; }
        .divider { border-top: 1px solid #bbb; margin: 10px 0 12px; }
        .report-content { font-size: 14px; line-height: 1.45; }
        @media print {
            .toolbar { display: none; }
            .page { padding: 8mm 10mm; }
        }
    </style>
</head>
<body>
    <?php
    $hospitalName = defined('H_Name') ? (string) constant('H_Name') : 'Hospital';
    $hospitalAddress1 = defined('H_address_1') ? (string) constant('H_address_1') : '';
    $hospitalAddress2 = defined('H_address_2') ? (string) constant('H_address_2') : '';
    $hospitalPhone = defined('H_phone_No') ? (string) constant('H_phone_No') : '';
    $hospitalAddress = trim($hospitalAddress1 . (empty($hospitalAddress2) ? '' : ', ' . $hospitalAddress2));

    $patientName = trim((string) ($report->p_fname ?? ''));
    $relativeName = trim((string) ($report->p_relative ?? ''));
    $relativeText = trim((string) ($report->p_rname ?? ''));
    $repoTitle = trim((string) ($report->report_name ?? $report->repo_title ?? $report->RepoGrp ?? 'Report'));
    ?>

    <div class="toolbar">
        <button type="button" onclick="window.print()">Print</button>
        <a class="btn-link" href="<?= esc(current_url()) ?>">Letterhead</a>
        <a class="btn-link" href="<?= esc(current_url() . '?print_on_type=1') ?>">Plain Paper</a>
    </div>

    <div class="page">
        <?php if (! ($isPlainPaper ?? false)): ?>
            <div class="letterhead">
                <div>
                    <div class="title"><?= esc($hospitalName) ?></div>
                    <div class="sub"><?= esc($hospitalAddress) ?></div>
                    <div class="sub"><?= esc($hospitalPhone) ?></div>
                </div>
                <div style="text-align:right; font-size:12px; color:#666;">Letterhead</div>
            </div>
        <?php endif; ?>

        <div class="section-title"><?= esc($repoTitle) ?></div>

        <table class="meta-table" border="0">
            <tr>
                <td width="50%">
                    <span class="label">Invoice ID:</span> <?= esc($report->invoice_code ?? '') ?><br>
                    <span class="label">Patient:</span> <?= esc($patientName) ?><br>
                    <span class="label">Relative:</span> <?= esc(trim($relativeName . ' ' . $relativeText)) ?><br>
                    <span class="label">Age/Sex:</span> <?= esc(($ageText ?? '-') . ' / ' . ($genderText ?? '-')) ?>
                </td>
                <td width="50%">
                    <span class="label">UHID:</span> <?= esc($report->p_code ?? '') ?><br>
                    <span class="label">Collected:</span> <?= esc($report->collected_time ?? '') ?><br>
                    <span class="label">Reported:</span> <?= esc($report->reported_time ?? '') ?><br>
                    <span class="label">Printed:</span> <?= esc(date('d-m-Y h:i A')) ?>
                </td>
            </tr>
        </table>

        <div class="divider"></div>

        <div class="report-content">
            <?= $reportHtml ?? '' ?>
        </div>
    </div>
</body>
</html>
