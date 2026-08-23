<?php
$rows = $rows ?? [];
$summary = $summary ?? [];
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div><strong>Period:</strong> <?= esc($min_range ?? '') ?> to <?= esc($max_range ?? '') ?></div>
    <span class="text-muted small"><?= count($rows) ?> relevant case<?= count($rows) === 1 ? '' : 's' ?></span>
</div>
<div class="row g-2 mb-3">
    <?php foreach ([
        ['Admissions', (int) ($summary['admissions'] ?? 0), 'primary'],
        ['Discharges', (int) ($summary['discharges'] ?? 0), 'success'],
        ['Active cases', (int) ($summary['active_cases'] ?? 0), 'warning'],
        ['Avg. stay', number_format((float) ($summary['average_stay'] ?? 0), 1) . ' days', 'info'],
    ] as [$label, $value, $tone]) : ?>
        <div class="col-6 col-lg-3"><div class="border rounded p-2 bg-light"><div class="text-muted small"><?= esc($label) ?></div><div class="fs-5 fw-semibold text-<?= esc($tone) ?>"><?= esc((string) $value) ?></div></div></div>
    <?php endforeach; ?>
</div>
<?php if (empty($rows)) : ?>
    <div class="alert alert-info mb-0">No IPD cases match the selected filters.</div>
<?php else : ?>
    <table class="table table-sm table-striped table-bordered align-middle mb-0">
        <thead class="table-light"><tr><th>#</th><th>IPD code</th><th>UHID</th><th>Patient</th><th>Department</th><th>Doctor</th><th>Admission</th><th>Discharge</th><th>Status</th><th class="text-end">Stay</th></tr></thead>
        <tbody>
            <?php foreach ($rows as $index => $row) : ?>
                <tr>
                    <td><?= $index + 1 ?></td><td><?= esc($row->ipd_code ?? '') ?></td><td><?= esc($row->p_code ?? '') ?></td><td><?= esc($row->p_fname ?? '') ?></td>
                    <td><?= esc($row->department_name ?? '') ?></td><td><?= esc($row->doctor_name ?? '') ?></td>
                    <td><?= esc(substr((string) ($row->register_date ?? ''), 0, 10)) ?></td><td><?= esc(substr((string) ($row->discharge_date ?? ''), 0, 10) ?: '-') ?></td>
                    <td><span class="badge text-bg-<?= (int) ($row->ipd_status ?? 0) === 1 ? 'success' : 'warning' ?>"><?= (int) ($row->ipd_status ?? 0) === 1 ? 'Discharged' : 'Active' ?></span></td><td class="text-end"><?= max(0, (int) ($row->stay_days ?? 0)) ?> days</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>