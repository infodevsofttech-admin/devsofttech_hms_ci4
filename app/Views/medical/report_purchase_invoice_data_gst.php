<?php
$totalTaxable = 0.0;
$totalGst = 0.0;
$totalAmount = 0.0;
?>
<div class="alert alert-light border mb-2">
    <strong>Date:</strong> <?= esc($dateFrom ?? '') ?> to <?= esc($dateTo ?? '') ?>
</div>
<div class="table-responsive">
    <table class="table table-sm table-bordered table-striped">
        <thead>
        <tr>
            <th>#</th>
            <th>Invoice</th>
            <th>Supplier</th>
            <th>Date</th>
            <th class="text-end">Taxable Amt</th>
            <th class="text-end">GST Amt</th>
            <th class="text-end">Inv Amount</th>
            <th class="text-end">0%</th>
            <th class="text-end">5%</th>
            <th class="text-end">12%</th>
            <th class="text-end">18%</th>
            <th class="text-end">28%</th>
            <th class="text-end">CGST</th>
            <th class="text-end">SGST/IGST</th>
        </tr>
        </thead>
        <tbody>
        <?php $i = 1; foreach (($rows ?? []) as $row):
            $taxable = (float) ($row['taxable_amount'] ?? 0);
            $gst = (float) ($row['gst_amount'] ?? 0);
            $amount = (float) ($row['tamount'] ?? 0);
            $totalTaxable += $taxable;
            $totalGst += $gst;
            $totalAmount += $amount;
        ?>
            <tr>
                <td><?= esc((string) $i++) ?></td>
                <td><?= esc((string) ($row['invoice_no'] ?? '')) ?></td>
                <td><?= esc((string) ($row['name_supplier'] ?? '')) ?></td>
                <td><?= esc((string) ($row['str_date_of_invoice'] ?? '')) ?></td>
                <td class="text-end"><?= esc(number_format($taxable, 2)) ?></td>
                <td class="text-end"><?= esc(number_format($gst, 2)) ?></td>
                <td class="text-end"><?= esc(number_format($amount, 2)) ?></td>
                <td class="text-end"><?= esc(number_format((float) ($row['tot_gst_0'] ?? 0), 2)) ?></td>
                <td class="text-end"><?= esc(number_format((float) ($row['tot_gst_5'] ?? 0), 2)) ?></td>
                <td class="text-end"><?= esc(number_format((float) ($row['tot_gst_12'] ?? 0), 2)) ?></td>
                <td class="text-end"><?= esc(number_format((float) ($row['tot_gst_18'] ?? 0), 2)) ?></td>
                <td class="text-end"><?= esc(number_format((float) ($row['tot_gst_28'] ?? 0), 2)) ?></td>
                <td class="text-end"><?= esc(number_format((float) ($row['total_cgst'] ?? 0), 2)) ?></td>
                <td class="text-end"><?= esc(number_format((float) ($row['total_sgst'] ?? 0), 2)) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
        <tr>
            <th colspan="4" class="text-end">Total</th>
            <th class="text-end"><?= esc(number_format($totalTaxable, 2)) ?></th>
            <th class="text-end"><?= esc(number_format($totalGst, 2)) ?></th>
            <th class="text-end"><?= esc(number_format($totalAmount, 2)) ?></th>
            <th colspan="7"></th>
        </tr>
        </tfoot>
    </table>
</div>
