<?php
$rows = $rows ?? [];
$dateFrom = $dateFrom ?? '';
$dateTo = $dateTo ?? '';
$showHeader = !empty($showHeader);
?>

<?php if ($showHeader): ?>
    <div class="small text-muted mb-2">Date range: <?= esc($dateFrom) ?> to <?= esc($dateTo) ?></div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-bordered table-striped table-sm align-middle" id="gst-invoice-list-table">
        <thead>
        <tr>
            <th>Invoice ID</th>
            <th>Inv.Date</th>
            <th>Inv.Name</th>
            <th>Invoice Type</th>
            <th class="text-end">Qty</th>
            <th class="text-end">Tot.Amt.</th>
            <th class="text-end">Tot.Tax.Amt</th>
            <th class="text-end">Tax.Amt.5%</th>
            <th class="text-end">CGST 2.5%</th>
            <th class="text-end">SGST 2.5%</th>
            <th class="text-end">Tax.Amt.12%</th>
            <th class="text-end">CGST 6%</th>
            <th class="text-end">SGST 6%</th>
            <th class="text-end">Tax.Amt.18%</th>
            <th class="text-end">CGST 9%</th>
            <th class="text-end">SGST 9%</th>
            <th class="text-end">Tax.Amt.28%</th>
            <th class="text-end">CGST 14%</th>
            <th class="text-end">SGST 14%</th>
            <th class="text-end">Tax.Amt.0%</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($rows)): ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= esc((string) ($row['inv_med_code'] ?? '-')) ?></td>
                    <td><?= esc((string) ($row['inv_date'] ?? '-')) ?></td>
                    <td><?= esc((string) ($row['inv_name'] ?? '-')) ?></td>
                    <td><?= esc((string) ($row['invoice_type'] ?? '-')) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['no_of_qty'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['total_amount'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['total_taxable_amount'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['sale_5_amount'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['cgst_2_5'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['sgst_2_5'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['sale_12_amount'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['cgst_6'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['sgst_6'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['sale_18_amount'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['cgst_9'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['sgst_9'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['sale_28_amount'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['cgst_14'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['sgst_14'] ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row['sale_0_amount'] ?? 0), 2)) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="20" class="text-center text-muted">No records found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
