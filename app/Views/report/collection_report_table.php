<?php
$rows = $rows ?? [];
$minRange = $min_range ?? '';
$maxRange = $max_range ?? '';
$totalCr = (float) ($total_cr ?? 0);
$totalDr = (float) ($total_dr ?? 0);
$netTotal = (float) ($net_total ?? 0);
?>

<p class="mb-2">Date(YYYY-MM-DD h:m) between <?= esc($minRange) ?> and <?= esc($maxRange) ?></p>

<?php if (empty($rows)) : ?>
    <div class="text-muted">No records found for the selected filters.</div>
<?php else : ?>
    <table class="table table-bordered table-sm align-middle">
        <thead class="table-warning">
            <tr>
                <th style="width: 70px;">PayID</th>
                <th style="width: 140px;">Date</th>
                <th style="width: 110px;">Type of Payment</th>
                <th>OPD/IPD/In. Code</th>
                <th style="width: 80px;">Pay Mode</th>
                <th style="width: 100px;" class="text-end">Amt (Cr.)</th>
                <th style="width: 100px;" class="text-end">Amt (Dr.)</th>
                <th>Bank/Source</th>
                <th>Remark</th>
                <th style="width: 120px;">Employee</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row) : ?>
                <tr>
                    <td><?= esc($row->pay_id ?? '') ?></td>
                    <td><?= esc($row->str_payment_date ?? '') ?></td>
                    <td><?= esc($row->pay_type ?? '') ?></td>
                    <td><?= esc(($row->payof_code ?? '') . ' ' . ($row->patient_name ?? '')) ?></td>
                    <td><?= esc($row->pay_mode ?? '') ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row->cr_amount ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row->dr_amount ?? 0), 2)) ?></td>
                    <td><?= esc(trim(($row->bank_name ?? '') . ' ' . ($row->bank_type ?? ''))) ?></td>
                    <td><?= esc($row->remark ?? '') ?></td>
                    <td><?= esc($row->update_by ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="table-warning">
                <th colspan="5">Total</th>
                <th class="text-end"><?= esc(number_format($totalCr, 2)) ?></th>
                <th class="text-end"><?= esc(number_format($totalDr, 2)) ?></th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
        </tfoot>
    </table>

    <p class="mb-0"><strong>Total Amount :</strong> <?= esc(number_format($netTotal, 2)) ?></p>
<?php endif; ?>
