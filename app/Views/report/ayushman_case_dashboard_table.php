<?php
$rows = $rows ?? [];
$summary = $summary ?? ['total_cases' => 0, 'preauth_pending' => 0, 'docs_pending' => 0, 'mapping_gaps' => 0];
$minRange = $min_range ?? '';
$maxRange = $max_range ?? '';
$alertType = $alert_type ?? 'all';
?>

<div class="mb-3">
    <div><strong>Date Range:</strong> <?= esc($minRange) ?> to <?= esc($maxRange) ?></div>
    <div><strong>Alert Filter:</strong> <?= esc($alertType) ?></div>
</div>

<div class="row g-2 mb-3">
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body py-2">
                <div class="small text-muted">Total Cases</div>
                <div class="fs-5 fw-semibold text-primary"><?= (int) ($summary['total_cases'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body py-2">
                <div class="small text-muted">Preauth Pending</div>
                <div class="fs-5 fw-semibold text-warning"><?= (int) ($summary['preauth_pending'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-secondary">
            <div class="card-body py-2">
                <div class="small text-muted">Documents Pending</div>
                <div class="fs-5 fw-semibold text-secondary"><?= (int) ($summary['docs_pending'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-danger">
            <div class="card-body py-2">
                <div class="small text-muted">Mapping Gaps</div>
                <div class="fs-5 fw-semibold text-danger"><?= (int) ($summary['mapping_gaps'] ?? 0) ?></div>
            </div>
        </div>
    </div>
</div>

<?php if (empty($rows)) : ?>
    <div class="alert alert-info">No Ayushman cases found for selected criteria.</div>
<?php else : ?>
    <table class="table table-bordered table-sm table-striped align-middle">
        <thead class="table-light">
            <tr>
                <th style="width:40px;">#</th>
                <th style="width:120px;">Case</th>
                <th style="width:110px;">IPD</th>
                <th style="width:90px;">UHID</th>
                <th>Patient</th>
                <th style="width:140px;">Insurance</th>
                <th style="width:95px;">Reg Date</th>
                <th style="width:80px;" class="text-end">Ayushman Proc</th>
                <th style="width:80px;" class="text-end">Unmapped</th>
                <th style="width:80px;" class="text-end">Preauth Req</th>
                <th style="width:100px;">Preauth Sent</th>
                <th style="width:100px;">Docs</th>
                <th style="width:130px;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $index => $row) : ?>
                <?php
                $preauthPending = (int) ($row['preauth_required_count'] ?? 0) > 0 && (int) ($row['preauth_send'] ?? 0) !== 1;
                $docsPending = (int) ($row['doc_recd'] ?? 0) !== 1;
                $mappingGap = (int) ($row['unmapped_count'] ?? 0) > 0;
                ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= esc($row['case_id_code'] ?? '') ?></td>
                    <td><?= esc($row['ipd_code'] ?? '') ?></td>
                    <td><?= esc($row['p_code'] ?? '') ?></td>
                    <td><?= esc($row['p_fname'] ?? '') ?></td>
                    <td>
                        <?= esc($row['insurance_company'] ?? '') ?>
                        <?php if (! empty($row['Org_insurance_comp'] ?? '')) : ?>
                            <div class="small text-muted"><?= esc($row['Org_insurance_comp'] ?? '') ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= esc($row['str_register_date'] ?? '') ?></td>
                    <td class="text-end"><?= (int) ($row['ayushman_proc_count'] ?? 0) ?></td>
                    <td class="text-end"><?= (int) ($row['unmapped_count'] ?? 0) ?></td>
                    <td class="text-end"><?= (int) ($row['preauth_required_count'] ?? 0) ?></td>
                    <td><?= $preauthPending ? '<span class="badge bg-warning text-dark">Pending</span>' : '<span class="badge bg-success">Done</span>' ?></td>
                    <td><?= $docsPending ? '<span class="badge bg-secondary">Pending</span>' : '<span class="badge bg-success">Done</span>' ?></td>
                    <td>
                        <?= esc((string) ($row['org_approved_status'] ?? 'Under Process')) ?>
                        <?php if ($mappingGap) : ?>
                            <div><span class="badge bg-danger mt-1">Mapping Gap</span></div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
