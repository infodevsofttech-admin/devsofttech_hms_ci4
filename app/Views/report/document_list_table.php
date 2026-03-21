<?php
$rows = $rows ?? [];
$minRange = $min_range ?? '';
$maxRange = $max_range ?? '';
$uhidFilter = trim((string) ($uhid_filter ?? ''));

$formatIndianDateTime = static function ($value): string {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $value;
    }

    return date('d-m-Y H:i:s', $timestamp);
};

$minRangeDisplay = $formatIndianDateTime($minRange);
$maxRangeDisplay = $formatIndianDateTime($maxRange);
?>

<div class="mb-3">
    <p><strong>Date Range:</strong> <?= esc($minRangeDisplay) ?> to <?= esc($maxRangeDisplay) ?></p>
    <?php if ($uhidFilter !== '') : ?>
        <p><strong>UHID Filter:</strong> <?= esc($uhidFilter) ?></p>
    <?php endif; ?>
</div>

<?php if (empty($rows)) : ?>
    <div class="alert alert-info">No data found for the selected criteria.</div>
<?php else : ?>
    <table class="table table-bordered table-sm">
        <thead class="table-light">
            <tr>
                <th style="width: 50px;">#</th>
                <th style="width: 90px;" class="text-end">Doc. ID</th>
                <th>Person Name / Relative Name</th>
                <th style="width: 130px;">PCode/UHID</th>
                <th style="width: 190px;">Doctor Name</th>
                <th style="width: 120px;">Issue Date</th>
                <th>Document Name</th>
            </tr>
        </thead>
        <tbody>
            <?php $srNo = 0; ?>
            <?php foreach ($rows as $row) : ?>
                <?php
                $srNo++;
                $patientName = trim((string) ($row->p_fname ?? ''));
                $relativeTitle = trim((string) ($row->p_relative ?? ''));
                $relativeName = trim((string) ($row->p_rname ?? ''));
                $relativePart = trim($relativeTitle . ' ' . $relativeName);
                ?>
                <tr>
                    <td><?= $srNo ?></td>
                    <td class="text-end"><?= (int) ($row->doc_id ?? 0) ?></td>
                    <td>
                        <?= esc($patientName) ?>
                        <?php if ($relativePart !== '') : ?>
                            {<?= esc($relativePart) ?>}
                        <?php endif; ?>
                    </td>
                    <td><?= esc((string) ($row->p_code ?? '')) ?></td>
                    <td><?= esc((string) ($row->dr_name ?? '')) ?></td>
                    <td><?= esc((string) ($row->str_date_issue ?? '')) ?></td>
                    <td><?= esc((string) ($row->doc_name ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
