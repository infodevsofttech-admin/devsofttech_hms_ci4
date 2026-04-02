<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= esc((string) ($title ?? 'Report')) ?></title>
    <link href="<?= base_url('assets/img/logo.ico') ?>" rel="icon" type="image/x-icon">
    <link href="<?= base_url('assets/img/favicon.png') ?>" rel="alternate icon" type="image/png">
    <style>
        body { font-family: Arial, sans-serif; color: #1f2937; margin: 20px; }
        h2 { margin: 0 0 4px 0; font-size: 22px; }
        .subtitle { margin: 0 0 16px 0; color: #6b7280; font-size: 14px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #d1d5db; padding: 8px; font-size: 12px; }
        th { background: #f3f4f6; text-align: left; }
        .no-data { padding: 16px; color: #6b7280; border: 1px dashed #d1d5db; }
        .print-note { margin-top: 10px; color: #6b7280; font-size: 12px; }
        @media print {
            .print-note { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <h2><?= esc((string) ($title ?? 'Report')) ?></h2>
    <p class="subtitle"><?= esc((string) ($subtitle ?? '')) ?></p>

    <?php if (! empty($rows ?? [])): ?>
    <table>
        <thead>
            <tr>
                <?php foreach (($columns ?? []) as $col): ?>
                <th><?= esc((string) $col) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach (($rows ?? []) as $row): ?>
            <tr>
                <?php foreach ($row as $cell): ?>
                <td><?= esc((string) $cell) ?></td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="no-data">No records found for selected criteria.</div>
    <?php endif; ?>

    <p class="print-note">Generated at <?= esc(date('Y-m-d H:i:s')) ?> | Use browser print command.</p>

    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
</body>
</html>
