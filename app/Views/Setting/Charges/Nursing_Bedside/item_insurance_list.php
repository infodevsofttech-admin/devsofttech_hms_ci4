<?php $rows = $rows ?? []; ?>
<table class="table table-bordered table-striped table-sm mb-0">
    <thead>
        <tr>
            <th>Company Name</th>
            <th style="width: 120px;" class="text-end">Rate</th>
            <th style="width: 120px;">Code</th>
            <th style="width: 70px;"></th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($rows)) : ?>
            <tr><td colspan="4" class="text-center text-muted">No insurance-specific rate added.</td></tr>
        <?php else : ?>
            <?php foreach ($rows as $row) : ?>
                <tr data-insurance-id="<?= (int) ($row['hc_insurance_id'] ?? 0) ?>">
                    <td><?= esc((string) ($row['ins_company_name'] ?? '')) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['amount1'] ?? 0), 2)) ?></td>
                    <td><?= esc((string) ($row['code'] ?? '')) ?></td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm" onclick="nursingInsuranceRemove(<?= (int) ($row['id'] ?? 0) ?>)">Del</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
