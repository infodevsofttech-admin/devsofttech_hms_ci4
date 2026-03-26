<?php
$rows = $rows ?? [];
$specialityCode = $speciality_code ?? '0';
?>

<div class="mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <strong>Speciality Filter:</strong>
        <?= esc($specialityCode === '0' ? 'All Specialities' : $specialityCode) ?>
    </div>
    <div>
        <strong>Total Unmapped Procedures:</strong> <?= count($rows) ?>
    </div>
</div>

<?php if (empty($rows)) : ?>
    <div class="alert alert-info">No unmapped Ayushman procedures found for the selected filter.</div>
<?php else : ?>
    <table class="table table-bordered table-sm table-striped align-middle">
        <thead class="table-light">
            <tr>
                <th style="width: 40px;">#</th>
                <th style="width: 140px;">Speciality</th>
                <th style="width: 120px;">Procedure Code</th>
                <th>Procedure Name</th>
                <th style="width: 110px;" class="text-end">Amount</th>
                <th style="width: 90px;">Preauth</th>
                <th>Pre Investigations</th>
                <th>Post Investigations</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $index => $row) : ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= esc(($row->speciality_name ?? '') . ' [' . ($row->speciality_code ?? '') . ']') ?></td>
                    <td><?= esc($row->procedure_code ?? '') ?></td>
                    <td><?= esc($row->procedure_name ?? '') ?></td>
                    <td class="text-end"><?= number_format((float) ($row->package_amount ?? 0), 2) ?></td>
                    <td><?= (int) ($row->preauth_required ?? 0) === 1 ? 'Yes' : 'No' ?></td>
                    <td><?= esc((string) ($row->pre_investigations ?? '')) ?></td>
                    <td><?= esc((string) ($row->post_investigations ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>