<?php
$rows = $rows ?? [];
$minRange = $min_range ?? '';
$maxRange = $max_range ?? '';
$formatDate = static function ($value): string {
    $timestamp = strtotime((string) $value);
    return $timestamp === false ? (string) $value : date('d-m-Y H:i', $timestamp);
};
?>
<p class="mb-2"><strong>OPD Date Range:</strong> <?= esc(date('d-m-Y', strtotime($minRange))) ?> to <?= esc(date('d-m-Y', strtotime($maxRange))) ?></p>
<?php if (empty($rows)) : ?>
    <div class="text-muted">No OPD patient records found for the selected filters.</div>
<?php else : ?>
    <table class="table table-bordered table-sm align-middle">
        <thead class="table-warning">
            <tr>
                <th>#</th>
                <th>OPD Code</th>
                <th>Patient Name</th>
                <th>Patient Code</th>
                <th>OPD Date</th>
                <th>Doctor</th>
                <th>Refer By</th>
                <th>Fee Type</th>
                <th class="text-end">Fee</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $index => $row) : ?>
                <tr>
                    <td><?= esc((string) ($index + 1)) ?></td>
                    <td><?= esc((string) ($row->opd_code ?? '')) ?></td>
                    <td><?= esc((string) ($row->P_name ?? '')) ?></td>
                    <td><?= esc((string) ($row->p_code ?? '')) ?></td>
                    <td><?= esc($formatDate($row->apointment_date ?? '')) ?></td>
                    <td><?= esc((string) ($row->doc_name ?? '')) ?></td>
                    <td><?= esc((string) ($row->referby ?? '')) ?></td>
                    <td><?= esc((string) ($row->opd_fee_desc ?? '')) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row->opd_fee_amount ?? 0), 2)) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
