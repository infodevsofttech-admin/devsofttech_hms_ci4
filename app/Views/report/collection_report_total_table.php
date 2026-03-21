<?php
$rows = $rows ?? [];
$totals = $totals ?? [
    'opd_amount' => 0,
    'charge_amount' => 0,
    'ipd_amount' => 0,
    'org_amount' => 0,
    'return_amount' => 0,
    'total_amount' => 0,
];
$minRange = $min_range ?? '';
$maxRange = $max_range ?? '';
?>

<p class="mb-2"><strong>Date Range:</strong> <?= esc($minRange) ?> to <?= esc($maxRange) ?></p>

<?php if (empty($rows)) : ?>
    <div class="text-muted">No records found for the selected filters.</div>
<?php else : ?>
    <table class="table table-bordered table-sm align-middle">
        <thead class="table-warning">
            <tr>
                <th>User ID</th>
                <th class="text-end">OPD Amount</th>
                <th class="text-end">Charge Amount</th>
                <th class="text-end">IPD Amount</th>
                <th class="text-end">ORG Amount</th>
                <th class="text-end">Return Amount</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row) : ?>
                <tr>
                    <td>
                        <?php
                        $userName = trim((string) ($row->user_name ?? ''));
                        $userId = (int) ($row->user_id ?? 0);
                        $userLabel = $userName !== '' ? $userName : 'Unknown';
                        if ($userId > 0) {
                            $userLabel .= '[' . $userId . ']';
                        }
                        ?>
                        <?= esc($userLabel) ?>
                    </td>
                    <td class="text-end"><?= esc(number_format((float) ($row->opd_amount ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row->charge_amount ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row->ipd_amount ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row->org_amount ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row->return_amount ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row->total_amount ?? 0), 2)) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="table-warning">
                <th>Total</th>
                <th class="text-end"><?= esc(number_format((float) ($totals['opd_amount'] ?? 0), 2)) ?></th>
                <th class="text-end"><?= esc(number_format((float) ($totals['charge_amount'] ?? 0), 2)) ?></th>
                <th class="text-end"><?= esc(number_format((float) ($totals['ipd_amount'] ?? 0), 2)) ?></th>
                <th class="text-end"><?= esc(number_format((float) ($totals['org_amount'] ?? 0), 2)) ?></th>
                <th class="text-end"><?= esc(number_format((float) ($totals['return_amount'] ?? 0), 2)) ?></th>
                <th class="text-end"><?= esc(number_format((float) ($totals['total_amount'] ?? 0), 2)) ?></th>
            </tr>
        </tfoot>
    </table>
<?php endif; ?>
