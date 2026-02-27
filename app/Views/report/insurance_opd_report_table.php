<?php
$rows = $rows ?? [];
$minRange = $min_range ?? '';
$maxRange = $max_range ?? '';
$caseType = $case_type ?? 'OPD';
?>

<div class="mb-3">
    <p><strong>Date Range:</strong> <?= esc($minRange) ?> to <?= esc($maxRange) ?></p>
    <p><strong>Type:</strong> <?= esc($caseType) ?> Organization Cases</p>
</div>

<?php if (empty($rows)) : ?>
    <div class="alert alert-info">No data found for the selected criteria.</div>
<?php else : ?>
    <table class="table table-bordered table-sm table-striped">
        <thead class="table-light">
            <tr>
                <th style="width: 50px;">#</th>
                <th style="width: 80px;">Bill No.</th>
                <th style="width: 120px;">Case Info</th>
                <th style="width: 100px;">Org. Code</th>
                <th style="width: 100px;">UHID</th>
                <th>Patient Name</th>
                <th style="width: 200px;">Insurance Company</th>
                <th style="width: 100px;">Date</th>
                <th style="width: 100px;">Status</th>
                <th class="text-end" style="width: 100px;">Charge Amt</th>
                <th class="text-end" style="width: 100px;">Med Amt</th>
                <th class="text-end" style="width: 100px;">Net Amt</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $totalCharge = 0.0;
            $totalMed = 0.0;
            $totalNet = 0.0;

            foreach ($rows as $index => $row) {
                $chargeAmt = (float) ($row->charge_amount ?? 0);
                $medAmt = (float) ($row->med_amount ?? 0);
                $netAmt = (float) ($row->net_amount ?? 0);

                $totalCharge += $chargeAmt;
                $totalMed += $medAmt;
                $totalNet += $netAmt;

                echo '<tr>';
                echo '<td>' . ($index + 1) . '</td>';
                echo '<td>' . esc($row->id ?? '') . '</td>';
                echo '<td>' . esc($row->case_info ?? '') . '</td>';
                echo '<td>' . esc($row->case_id_code ?? '') . '</td>';
                echo '<td>' . esc($row->p_code ?? '') . '</td>';
                echo '<td>' . esc($row->p_fname ?? '') . '</td>';
                echo '<td>' . esc($row->insurance_company ?? '') . '</td>';
                echo '<td>' . esc($row->str_date_registration ?? '') . '</td>';
                echo '<td><span class="badge bg-secondary">' . esc($row->org_submit_status ?? '') . '</span></td>';
                echo '<td class="text-end">' . number_format($chargeAmt, 2) . '</td>';
                echo '<td class="text-end">' . number_format($medAmt, 2) . '</td>';
                echo '<td class="text-end"><strong>' . number_format($netAmt, 2) . '</strong></td>';
                echo '</tr>';
            }
            ?>
        </tbody>
        <tfoot class="table-primary">
            <tr>
                <th colspan="9" class="text-end">Total:</th>
                <th class="text-end"><?= number_format($totalCharge, 2) ?></th>
                <th class="text-end"><?= number_format($totalMed, 2) ?></th>
                <th class="text-end"><?= number_format($totalNet, 2) ?></th>
            </tr>
            <tr>
                <th colspan="9" class="text-end">Total Cases:</th>
                <th colspan="3"><?= count($rows) ?></th>
            </tr>
        </tfoot>
    </table>
<?php endif; ?>
