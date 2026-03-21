<?php
$rows = $rows ?? [];
$minRange = $min_range ?? '';
$maxRange = $max_range ?? '';
$invoiceTypeLabel = $invoice_type_label ?? 'All Types';

$formatIndianDateTime = static function ($value): string {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $value;
    }

    return date('d-m-Y H:i:s', $timestamp);
};

$minRangeDisplay = $formatIndianDateTime($minRange);
$maxRangeDisplay = $formatIndianDateTime($maxRange);
?>

<div class="mb-3">
    <p><strong>Date Range:</strong> <?= esc($minRangeDisplay) ?> to <?= esc($maxRangeDisplay) ?></p>
    <p><strong>Invoice Type:</strong> <?= esc($invoiceTypeLabel) ?></p>
</div>

<?php if (empty($rows)) : ?>
    <div class="alert alert-info">No data found for the selected criteria.</div>
<?php else : ?>
    <table class="table table-bordered table-sm">
        <thead class="table-light">
            <tr>
                <th style="width: 50px;">#</th>
                <th>Test Name</th>
                <th class="text-end" style="width: 120px;">No. of Qty</th>
                <th class="text-end" style="width: 120px;">Amount</th>
                <th class="text-end" style="width: 120px;">Cost Amt</th>
                <th class="text-end" style="width: 140px;">Direct Patient Amt</th>
                <th class="text-end" style="width: 130px;">IPD Direct Amt</th>
                <th class="text-end" style="width: 120px;">IPD TPA Amt</th>
                <th class="text-end" style="width: 120px;">Org Credit Amt</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $diagnosisHead = '';
            $diagnosisHeadTotal = 0.0;
            $diagnosisActHeadTotal = 0.0;
            $diagnosisQtyTotal = 0.0;
            $diagnosisDirectPatientTotal = 0.0;
            $diagnosisIpdDirectTotal = 0.0;
            $diagnosisIpdTpaTotal = 0.0;
            $diagnosisOrgCreditTotal = 0.0;
            $grandTotal = 0.0;
            $grandActTotal = 0.0;
            $grandQtyTotal = 0.0;
            $grandDirectPatientTotal = 0.0;
            $grandIpdDirectTotal = 0.0;
            $grandIpdTpaTotal = 0.0;
            $grandOrgCreditTotal = 0.0;
            $rowCount = count($rows);
            $rowNum = 0;

            for ($i = 0; $i < $rowCount; $i++) {
                $row = $rows[$i];
                $groupDesc = $row->group_desc ?? '';
                $itemName = $row->item_name ?? '';
                $noQty = (float) ($row->no_qty ?? 0);
                $totalAmount = (float) ($row->total_amount ?? 0);
                $totalActAmount = (float) ($row->total_act_amount ?? 0);
                $directPatientAmount = (float) ($row->direct_patient_amount ?? 0);
                $ipdDirectAmount = (float) ($row->ipd_direct_amount ?? 0);
                $ipdTpaAmount = (float) ($row->ipd_tpa_amount ?? 0);
                $orgCreditAmount = (float) ($row->org_credit_amount ?? 0);

                // Diagnosis group header
                if ($groupDesc !== $diagnosisHead) {
                    // Show previous group total if not first group
                    if ($diagnosisHead !== '') {
                        echo '<tr class="table-secondary">';
                        echo '<td></td>';
                        echo '<td><strong>Total of ' . esc($diagnosisHead) . '</strong></td>';
                        echo '<td class="text-end"><strong>' . number_format($diagnosisQtyTotal, 0) . '</strong></td>';
                        echo '<td class="text-end"><strong>' . number_format($diagnosisHeadTotal, 2) . '</strong></td>';
                        echo '<td class="text-end"><strong>' . number_format($diagnosisActHeadTotal, 2) . '</strong></td>';
                        echo '<td class="text-end"><strong>' . number_format($diagnosisDirectPatientTotal, 2) . '</strong></td>';
                        echo '<td class="text-end"><strong>' . number_format($diagnosisIpdDirectTotal, 2) . '</strong></td>';
                        echo '<td class="text-end"><strong>' . number_format($diagnosisIpdTpaTotal, 2) . '</strong></td>';
                        echo '<td class="text-end"><strong>' . number_format($diagnosisOrgCreditTotal, 2) . '</strong></td>';
                        echo '</tr>';
                        
                        $diagnosisHeadTotal = 0.0;
                        $diagnosisActHeadTotal = 0.0;
                        $diagnosisQtyTotal = 0.0;
                        $diagnosisDirectPatientTotal = 0.0;
                        $diagnosisIpdDirectTotal = 0.0;
                        $diagnosisIpdTpaTotal = 0.0;
                        $diagnosisOrgCreditTotal = 0.0;
                    }
                    
                    echo '<tr class="table-info">';
                    echo '<td colspan="9"><strong>' . esc($groupDesc) . '</strong></td>';
                    echo '</tr>';
                    $diagnosisHead = $groupDesc;
                }

                // Item row
                $rowNum++;
                echo '<tr>';
                echo '<td>' . $rowNum . '</td>';
                echo '<td>' . esc($itemName) . '</td>';
                echo '<td class="text-end">' . number_format($noQty, 0) . '</td>';
                echo '<td class="text-end">' . number_format($totalAmount, 2) . '</td>';
                echo '<td class="text-end">' . number_format($totalActAmount, 2) . '</td>';
                echo '<td class="text-end">' . number_format($directPatientAmount, 2) . '</td>';
                echo '<td class="text-end">' . number_format($ipdDirectAmount, 2) . '</td>';
                echo '<td class="text-end">' . number_format($ipdTpaAmount, 2) . '</td>';
                echo '<td class="text-end">' . number_format($orgCreditAmount, 2) . '</td>';
                echo '</tr>';

                // Update totals
                $diagnosisHeadTotal += $totalAmount;
                $diagnosisActHeadTotal += $totalActAmount;
                $diagnosisQtyTotal += $noQty;
                $diagnosisDirectPatientTotal += $directPatientAmount;
                $diagnosisIpdDirectTotal += $ipdDirectAmount;
                $diagnosisIpdTpaTotal += $ipdTpaAmount;
                $diagnosisOrgCreditTotal += $orgCreditAmount;
                $grandTotal += $totalAmount;
                $grandActTotal += $totalActAmount;
                $grandQtyTotal += $noQty;
                $grandDirectPatientTotal += $directPatientAmount;
                $grandIpdDirectTotal += $ipdDirectAmount;
                $grandIpdTpaTotal += $ipdTpaAmount;
                $grandOrgCreditTotal += $orgCreditAmount;
            }
            
            // Show last group total
            if ($diagnosisHead !== '' && $rowCount > 0) {
                echo '<tr class="table-secondary">';
                echo '<td></td>';
                echo '<td><strong>Total of ' . esc($diagnosisHead) . '</strong></td>';
                echo '<td class="text-end"><strong>' . number_format($diagnosisQtyTotal, 0) . '</strong></td>';
                echo '<td class="text-end"><strong>' . number_format($diagnosisHeadTotal, 2) . '</strong></td>';
                echo '<td class="text-end"><strong>' . number_format($diagnosisActHeadTotal, 2) . '</strong></td>';
                echo '<td class="text-end"><strong>' . number_format($diagnosisDirectPatientTotal, 2) . '</strong></td>';
                echo '<td class="text-end"><strong>' . number_format($diagnosisIpdDirectTotal, 2) . '</strong></td>';
                echo '<td class="text-end"><strong>' . number_format($diagnosisIpdTpaTotal, 2) . '</strong></td>';
                echo '<td class="text-end"><strong>' . number_format($diagnosisOrgCreditTotal, 2) . '</strong></td>';
                echo '</tr>';
            }
            ?>
        </tbody>
        <tfoot class="table-primary">
            <tr>
                <th colspan="2"><strong>Grand Total</strong></th>
                <th class="text-end"><strong><?= number_format($grandQtyTotal, 0) ?></strong></th>
                <th class="text-end"><strong><?= number_format($grandTotal, 2) ?></strong></th>
                <th class="text-end"><strong><?= number_format($grandActTotal, 2) ?></strong></th>
                <th class="text-end"><strong><?= number_format($grandDirectPatientTotal, 2) ?></strong></th>
                <th class="text-end"><strong><?= number_format($grandIpdDirectTotal, 2) ?></strong></th>
                <th class="text-end"><strong><?= number_format($grandIpdTpaTotal, 2) ?></strong></th>
                <th class="text-end"><strong><?= number_format($grandOrgCreditTotal, 2) ?></strong></th>
            </tr>
        </tfoot>
    </table>
<?php endif; ?>
