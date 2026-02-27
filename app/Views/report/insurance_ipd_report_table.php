<?php
$rows = $rows ?? [];
$minRange = $min_range ?? '';
$maxRange = $max_range ?? '';
$caseType = $case_type ?? 'IPD';
?>

<div class="mb-3">
    <p><strong>Date Range:</strong> <?= esc($minRange) ?> to <?= esc($maxRange) ?></p>
    <p><strong>Type:</strong> <?= esc($caseType) ?> TPA Cases</p>
</div>

<?php if (empty($rows)) : ?>
    <div class="alert alert-info">No data found for the selected criteria.</div>
<?php else : ?>
    <table class="table table-bordered table-sm table-striped">
        <thead class="table-light">
            <tr>
                <th style="width: 40px;">#</th>
                <th style="width: 100px;">Case Info</th>
                <th style="width: 100px;">IPD Code</th>
                <th style="width: 90px;">UHID</th>
                <th>Patient Name</th>
                <th style="width: 150px;">Insurance</th>
                <th style="width: 80px;">Status</th>
                <th style="width: 90px;">Admit Date</th>
                <th style="width: 90px;">Discharge</th>
                <th class="text-end" style="width: 90px;">Charge</th>
                <th class="text-end" style="width: 90px;">Med</th>
                <th class="text-end" style="width: 80px;">Disc.</th>
                <th class="text-end" style="width: 90px;">Net Amt</th>
                <th class="text-end" style="width: 90px;">Received</th>
                <th class="text-end" style="width: 90px;">Deducted</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $totalCharge = 0.0;
            $totalMed = 0.0;
            $totalDiscount = 0.0;
            $totalNet = 0.0;
            $totalReceived = 0.0;
            $totalDeducted = 0.0;

            foreach ($rows as $index => $row) {
                $chargeAmt = (float) ($row->charge_amount ?? 0);
                $medAmt = (float) ($row->med_amount ?? 0);
                $discount = (float) ($row->discount ?? 0);
                $netAmt = (float) ($row->net_amount ?? 0);
                $received = (float) ($row->amount_recived ?? 0);
                $deducted = (float) ($row->amount_deduction ?? 0);

                $totalCharge += $chargeAmt;
                $totalMed += $medAmt;
                $totalDiscount += $discount;
                $totalNet += $netAmt;
                $totalReceived += $received;
                $totalDeducted += $deducted;

                echo '<tr>';
                echo '<td>' . ($index + 1) . '</td>';
                echo '<td>' . esc($row->case_info ?? '') . '</td>';
                echo '<td>' . esc($row->ipd_code ?? '') . '</td>';
                echo '<td>' . esc($row->p_code ?? '') . '</td>';
                echo '<td>' . esc($row->p_fname ?? '') . '</td>';
                echo '<td>' . esc($row->insurance_company ?? '') . '</td>';
                echo '<td><span class="badge bg-secondary">' . esc($row->org_submit_status ?? '') . '</span></td>';
                echo '<td>' . esc($row->str_register_date ?? '') . '</td>';
                echo '<td>' . esc($row->str_discharge_date ?? '-') . '</td>';
                echo '<td class="text-end">' . number_format($chargeAmt, 2) . '</td>';
                echo '<td class="text-end">' . number_format($medAmt, 2) . '</td>';
                echo '<td class="text-end">' . number_format($discount, 2) . '</td>';
                echo '<td class="text-end"><strong>' . number_format($netAmt, 2) . '</strong></td>';
                echo '<td class="text-end">' . number_format($received, 2) . '</td>';
                echo '<td class="text-end">' . number_format($deducted, 2) . '</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
        <tfoot class="table-primary">
            <tr>
                <th colspan="9" class="text-end">Total:</th>
                <th class="text-end"><?= number_format($totalCharge, 2) ?></th>
                <th class="text-end"><?= number_format($totalMed, 2) ?></th>
                <th class="text-end"><?= number_format($totalDiscount, 2) ?></th>
                <th class="text-end"><?= number_format($totalNet, 2) ?></th>
                <th class="text-end"><?= number_format($totalReceived, 2) ?></th>
                <th class="text-end"><?= number_format($totalDeducted, 2) ?></th>
            </tr>
            <tr>
                <th colspan="12" class="text-end">Total Cases:</th>
                <th colspan="3"><?= count($rows) ?></th>
            </tr>
            <tr>
                <th colspan="12" class="text-end">Balance (Net - Received):</th>
                <th colspan="3" class="text-end"><?= number_format($totalNet - $totalReceived, 2) ?></th>
            </tr>
        </tfoot>
    </table>
<?php endif; ?>
