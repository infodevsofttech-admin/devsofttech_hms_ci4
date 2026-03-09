<?php
$rows = $rows ?? [];
$summary = $summary ?? [];
$minRange = $min_range ?? '';
$maxRange = $max_range ?? '';
$moduleFilter = $module_filter ?? 'all';
$statusFilter = $status_filter ?? 'all';

$overall = $summary['all'] ?? ['total' => 0, 'compliant' => 0, 'critical_missing' => 0, 'avg_completion' => 0];
$ipd = $summary['ipd'] ?? ['total' => 0, 'compliant' => 0, 'critical_missing' => 0, 'avg_completion' => 0];
$opd = $summary['opd'] ?? ['total' => 0, 'compliant' => 0, 'critical_missing' => 0, 'avg_completion' => 0];
?>

<div class="mb-3">
    <p><strong>Date Range:</strong> <?= esc($minRange) ?> to <?= esc($maxRange) ?></p>
    <p><strong>Module:</strong> <?= esc(strtoupper((string) $moduleFilter)) ?>, <strong>Status:</strong> <?= esc((string) $statusFilter) ?></p>
</div>

<div class="row g-2 mb-3">
    <div class="col-md-4">
        <div class="border rounded p-2">
            <div><strong>Overall</strong></div>
            <div>Total: <?= (int) ($overall['total'] ?? 0) ?></div>
            <div>Compliant: <?= (int) ($overall['compliant'] ?? 0) ?></div>
            <div>Critical Missing: <?= (int) ($overall['critical_missing'] ?? 0) ?></div>
            <div>Avg Completion: <?= number_format((float) ($overall['avg_completion'] ?? 0), 1) ?>%</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="border rounded p-2">
            <div><strong>IPD Discharge</strong></div>
            <div>Total: <?= (int) ($ipd['total'] ?? 0) ?></div>
            <div>Compliant: <?= (int) ($ipd['compliant'] ?? 0) ?></div>
            <div>Critical Missing: <?= (int) ($ipd['critical_missing'] ?? 0) ?></div>
            <div>Avg Completion: <?= number_format((float) ($ipd['avg_completion'] ?? 0), 1) ?>%</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="border rounded p-2">
            <div><strong>OPD Consult</strong></div>
            <div>Total: <?= (int) ($opd['total'] ?? 0) ?></div>
            <div>Compliant: <?= (int) ($opd['compliant'] ?? 0) ?></div>
            <div>Critical Missing: <?= (int) ($opd['critical_missing'] ?? 0) ?></div>
            <div>Avg Completion: <?= number_format((float) ($opd['avg_completion'] ?? 0), 1) ?>%</div>
        </div>
    </div>
</div>

<?php if (empty($rows)) : ?>
    <div class="alert alert-info">No audit data found for selected criteria.</div>
<?php else : ?>
    <table class="table table-bordered table-sm">
        <thead class="table-light">
            <tr>
                <th style="width: 50px;">#</th>
                <th>Module</th>
                <th>Encounter</th>
                <th>Patient</th>
                <th>Date Time</th>
                <th class="text-end">Completion</th>
                <th class="text-end">Critical Missing</th>
                <th>Missing Critical Items</th>
            </tr>
        </thead>
        <tbody>
            <?php $index = 1; ?>
            <?php foreach ($rows as $row) : ?>
                <?php
                $criticalMissingCount = (int) ($row['critical_missing_count'] ?? 0);
                $rowClass = $criticalMissingCount > 0 ? 'table-warning' : 'table-success';
                $encounterLabel = trim((string) ($row['encounter_code'] ?? ''));
                if ($encounterLabel === '') {
                    $encounterLabel = '#' . (int) ($row['encounter_id'] ?? 0);
                }
                $patientLabel = trim((string) ($row['patient_name'] ?? ''));
                $patientCode = trim((string) ($row['patient_code'] ?? ''));
                if ($patientCode !== '') {
                    $patientLabel = $patientCode . ' - ' . $patientLabel;
                }
                ?>
                <tr class="<?= esc($rowClass) ?>">
                    <td><?= $index++ ?></td>
                    <td><?= esc((string) ($row['module_label'] ?? '')) ?></td>
                    <td><?= esc($encounterLabel) ?></td>
                    <td><?= esc($patientLabel) ?></td>
                    <td><?= esc((string) ($row['encounter_datetime'] ?? '')) ?></td>
                    <td class="text-end">
                        <?= (int) ($row['ok_count'] ?? 0) ?>/<?= (int) ($row['total_count'] ?? 0) ?>
                        (<?= number_format((float) ($row['completion_percent'] ?? 0), 1) ?>%)
                    </td>
                    <td class="text-end"><?= $criticalMissingCount ?></td>
                    <td><?= esc((string) ($row['critical_missing_labels'] ?? '-')) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
