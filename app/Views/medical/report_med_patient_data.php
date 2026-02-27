<?php
$rows = $rows ?? [];
$dateFrom = (string) ($dateFrom ?? '');
$dateTo = (string) ($dateTo ?? '');

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
?>

<div class="alert alert-light border">
    <strong>Date Range:</strong> <?= esc($dateFrom) ?> to <?= esc($dateTo) ?>
    <span class="ms-3"><strong>Total Items:</strong> <?= esc((string) count($groups)) ?></span>
</div>

<?php if ($groups === []): ?>
    <div class="alert alert-warning mb-0">No records found for selected filters.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-bordered table-sm align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:140px;">Inv.No.</th>
                    <th style="width:110px;">Inv.Date</th>
                    <th style="width:120px;">IPD No.</th>
                    <th>P Code/Name</th>
                    <th style="width:100px;">Exp. Date</th>
                    <th style="width:110px;">Batch</th>
                    <th class="text-end" style="width:90px;">Qty</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($groups as $itemName => $group): ?>
                    <tr>
                        <td colspan="7" class="text-bg-danger">
                            <strong><?= esc($itemName) ?></strong>
                            <?php if (trim((string) ($group['head'] ?? '')) !== ''): ?>
                                <span class="ms-2">[ <?= esc((string) $group['head']) ?> ]</span>
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
                            <td class="text-end"><?= esc(number_format((float) ($row['t_qty'] ?? 0), 2)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="table-warning">
                        <td colspan="6" class="text-end"><strong>Total Qty (<?= esc($itemName) ?>)</strong></td>
                        <td class="text-end"><strong><?= esc(number_format((float) ($group['total_qty'] ?? 0), 2)) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
