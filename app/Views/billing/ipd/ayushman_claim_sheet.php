<?php
$ipd = $ipd_info ?? null;
$person = $person_info ?? null;
$items = $checklist_items ?? [];
$generatedAt = $generated_at ?? date('d-m-Y H:i:s');
?>

<section class="content">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Ayushman Claim Sheet</h5>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">Print</button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-2 mb-3">
                <div class="col-md-4"><strong>Patient:</strong> <?= esc($person->p_fname ?? '') ?></div>
                <div class="col-md-2"><strong>UHID:</strong> <?= esc($person->p_code ?? '') ?></div>
                <div class="col-md-2"><strong>IPD:</strong> <?= esc($ipd->ipd_code ?? '') ?></div>
                <div class="col-md-4"><strong>Case:</strong> <?= esc($ipd->case_id_code ?? '') ?></div>
                <div class="col-md-4"><strong>Insurance:</strong> <?= esc($ipd->ins_company_name ?? '') ?></div>
                <div class="col-md-4"><strong>Scheme:</strong> <?= esc($ipd->org_insurance_comp ?? '') ?></div>
                <div class="col-md-4"><strong>Generated:</strong> <?= esc($generatedAt) ?></div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-3"><strong>Preauth Sent:</strong> <?= (int) ($ipd->preauth_send ?? 0) === 1 ? 'Yes' : 'No' ?></div>
                <div class="col-md-3"><strong>Documents Received:</strong> <?= (int) ($ipd->doc_recd ?? 0) === 1 ? 'Yes' : 'No' ?></div>
                <div class="col-md-3"><strong>Final Bill Sent:</strong> <?= (int) ($ipd->final_bill_send ?? 0) === 1 ? 'Yes' : 'No' ?></div>
                <div class="col-md-3"><strong>Approval Status:</strong> <?= esc((string) ($ipd->org_approved_status ?? 'Under Process')) ?></div>
                <div class="col-md-12"><strong>Remark:</strong> <?= esc((string) ($ipd->remark ?? '')) ?></div>
            </div>

            <?php if (empty($items)) : ?>
                <div class="alert alert-warning">No Ayushman procedures linked to this IPD package list.</div>
            <?php else : ?>
                <table class="table table-bordered table-sm table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th style="width: 140px;">Procedure Code</th>
                            <th>Procedure Name</th>
                            <th style="width: 120px;" class="text-end">Amount</th>
                            <th style="width: 90px;">Preauth</th>
                            <th>Pre Investigations</th>
                            <th>Post Investigations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $index => $item) : ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= esc((string) ($item['procedure_code'] ?? '')) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= esc((string) ($item['procedure_name'] ?? '')) ?></div>
                                    <div class="small text-muted"><?= esc((string) (($item['speciality_name'] ?? '') . ' [' . ($item['speciality_code'] ?? '') . ']')) ?></div>
                                </td>
                                <td class="text-end"><?= number_format((float) ($item['package_Amount'] ?? 0), 2) ?></td>
                                <td><?= (int) ($item['preauth_required'] ?? 0) === 1 ? 'Required' : 'No' ?></td>
                                <td><?= esc((string) ($item['pre_investigations'] ?? '')) ?></td>
                                <td><?= esc((string) ($item['post_investigations'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</section>
