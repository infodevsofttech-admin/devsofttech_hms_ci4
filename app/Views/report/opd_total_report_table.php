<?php
$rows = $rows ?? [];
$summary = $summary ?? [];
$minRange = $min_range ?? '';
$maxRange = $max_range ?? '';

$totalOpd = (int) ($summary['no_of_opd'] ?? 0);
$totalRunning = (int) ($summary['running_count'] ?? 0);
$totalRegular = (int) ($summary['regular_count'] ?? 0);
$totalNew = (int) ($summary['new_count'] ?? 0);
$totalEmergency = (int) ($summary['emergency_count'] ?? 0);
$totalAmount = (float) ($summary['total_amount'] ?? 0);

$formatDate = static function ($value): string {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $value;
    }

    return date('d-m-Y', $timestamp);
};
?>

<p class="mb-2"><strong>OPD Date Range:</strong> <?= esc($formatDate($minRange)) ?> to <?= esc($formatDate($maxRange)) ?></p>

<?php if (empty($rows)) : ?>
    <div class="text-muted">No OPD records found for the selected filters.</div>
<?php else : ?>
    <table class="table table-bordered table-sm align-middle">
        <thead class="table-warning">
            <tr>
                <th style="width: 60px;">#</th>
                <th>Doctor Name</th>
                <th>OPD Type / Fee Type</th>
                <th class="text-end" style="width: 95px;">OPD Fee</th>
                <th class="text-end" style="width: 95px;">No. of OPD</th>
                <th class="text-end" style="width: 95px;">Running</th>
                <th class="text-end" style="width: 95px;">Regular</th>
                <th class="text-end" style="width: 95px;">New</th>
                <th class="text-end" style="width: 95px;">Emergency</th>
                <th class="text-end" style="width: 110px;">Total Amt</th>
            </tr>
        </thead>
        <tbody>
            <?php $srNo = 1; ?>
            <?php foreach ($rows as $row) : ?>
                <tr>
                    <td><?= esc($srNo++) ?></td>
                    <td><?= esc('Dr. ' . ($row->doctor_name ?? '')) ?></td>
                    <td><?= esc($row->opd_fee_desc ?? '') ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row->opd_fee_amount ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc((int) ($row->no_of_opd ?? 0)) ?></td>
                    <td class="text-end"><?= esc((int) ($row->running_count ?? 0)) ?></td>
                    <td class="text-end"><?= esc((int) ($row->regular_count ?? 0)) ?></td>
                    <td class="text-end"><?= esc((int) ($row->new_count ?? 0)) ?></td>
                    <td class="text-end"><?= esc((int) ($row->emergency_count ?? 0)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row->total_amount ?? 0), 2)) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="table-warning">
                <th colspan="4">Total</th>
                <th class="text-end"><?= esc($totalOpd) ?></th>
                <th class="text-end"><?= esc($totalRunning) ?></th>
                <th class="text-end"><?= esc($totalRegular) ?></th>
                <th class="text-end"><?= esc($totalNew) ?></th>
                <th class="text-end"><?= esc($totalEmergency) ?></th>
                <th class="text-end"><?= esc(number_format($totalAmount, 2)) ?></th>
            </tr>
        </tfoot>
    </table>
<?php endif; ?>
