<?php
$rows = $rows ?? [];
$dateFrom = (string) ($dateFrom ?? '');
$dateTo = (string) ($dateTo ?? '');
$searchText = trim((string) ($searchText ?? ''));
$scheduleId = trim((string) ($scheduleId ?? '0'));

$groups = [];
foreach ($rows as $row) {
    $itemName = trim((string) ($row['item_name'] ?? '-'));
    if ($itemName === '') {
        $itemName = '-';
    }

    if (!isset($groups[$itemName])) {
        $groups[$itemName] = [
            'head' => (string) ($row['shed_x_h'] ?? ''),
            'rows' => [],
            'total_qty' => 0.0,
        ];
    }

    $qty = (float) ($row['t_qty'] ?? 0);
    $groups[$itemName]['rows'][] = $row;
    $groups[$itemName]['total_qty'] += $qty;

    if ($groups[$itemName]['head'] === '' && !empty($row['shed_x_h'])) {
        $groups[$itemName]['head'] = (string) $row['shed_x_h'];
    }
}

$pharmacyName = defined('M_store') ? (string) M_store : 'Medical Store';
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #111; }
        .title { font-size: 18px; font-weight: 700; margin-bottom: 2px; }
        .subtitle { font-size: 12px; font-weight: 700; margin-bottom: 8px; }
        .meta { font-size: 10px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #777; padding: 4px; vertical-align: top; }
        th { background: #efefef; font-weight: 700; }
        .item-head { background: #f5f5f5; font-weight: 700; }
        .text-right { text-align: right; }
        .note { margin-top: 8px; font-size: 9px; color: #333; }
    </style>
</head>
<body>
    <div class="title"><?= esc($pharmacyName) ?></div>
    <div class="subtitle">Drug Sale Customer Wise Report</div>
    <div class="meta">
        <strong>Date Range:</strong> <?= esc($dateFrom) ?> to <?= esc($dateTo) ?>
        <?php if ($searchText !== ''): ?>
            | <strong>Drug/Batch:</strong> <?= esc($searchText) ?>
        <?php endif; ?>
        <?php if ($scheduleId !== '' && $scheduleId !== '0' && $searchText === ''): ?>
            | <strong>Schedule Filter:</strong> <?= esc($scheduleId) ?>
        <?php endif; ?>
        | <strong>Total Items:</strong> <?= esc((string) count($groups)) ?>
    </div>

    <?php if ($groups === []): ?>
        <table>
            <tr><td>No records found for selected filters.</td></tr>
        </table>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th style="width:14%;">Inv.No.</th>
                    <th style="width:10%;">Inv.Date</th>
                    <th style="width:12%;">IPD No.</th>
                    <th style="width:32%;">P Code/Name</th>
                    <th style="width:10%;">Exp. Date</th>
                    <th style="width:10%;">Batch</th>
                    <th style="width:12%;" class="text-right">Qty</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($groups as $itemName => $group): ?>
                    <tr>
                        <td colspan="7" class="item-head">
                            <?= esc($itemName) ?>
                            <?php if (trim((string) ($group['head'] ?? '')) !== ''): ?>
                                [ <?= esc((string) $group['head']) ?> ]
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php foreach (($group['rows'] ?? []) as $row): ?>
                        <tr>
                            <td><?= esc((string) ($row['inv_med_code'] ?? '-')) ?></td>
                            <td><?= esc((string) ($row['str_inv_date'] ?? '-')) ?></td>
                            <td><?= esc((string) ($row['ipd_code'] ?? '-')) ?></td>
                            <td><?= esc((string) ($row['patient_code'] ?? '-')) ?> / <?= esc((string) ($row['inv_name'] ?? '-')) ?></td>
                            <td><?= esc((string) ($row['exp_date'] ?? '-')) ?></td>
                            <td><?= esc((string) ($row['batch_no'] ?? '-')) ?></td>
                            <td class="text-right"><?= esc(number_format((float) ($row['t_qty'] ?? 0), 2)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="6" class="text-right"><strong>Total Qty (<?= esc($itemName) ?>)</strong></td>
                        <td class="text-right"><strong><?= esc(number_format((float) ($group['total_qty'] ?? 0), 2)) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="note">Generated on <?= esc(date('d-m-Y H:i')) ?></div>
</body>
</html>
