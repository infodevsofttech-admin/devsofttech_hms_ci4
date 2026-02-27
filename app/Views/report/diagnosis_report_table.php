<?php
$rows = $rows ?? [];
$minRange = $min_range ?? '';
$maxRange = $max_range ?? '';
$invoiceTypeLabel = $invoice_type_label ?? 'All Types';
?>

<div class="mb-3">
    <p><strong>Date Range:</strong> <?= esc($minRange) ?> to <?= esc($maxRange) ?></p>
    <p><strong>Invoice Type:</strong> <?= esc($invoiceTypeLabel) ?></p>
</div>

<?php if (empty($rows)) : ?>
    <div class="alert alert-info">No data found for the selected criteria.</div>
<?php else : ?>
    <table class="table table-bordered table-sm">
        <thead class="table-light">
            <tr>
                <th style="width: 50px;">#</th>
                <th>Diagnosis Group</th>
                <th>Test Name</th>
                <th class="text-end" style="width: 120px;">No. of Qty</th>
                <th class="text-end" style="width: 120px;">Amount</th>
                <th class="text-end" style="width: 120px;">Cost Amt</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $diagnosisHead = '';
            $diagnosisHeadTotal = 0.0;
            $diagnosisActHeadTotal = 0.0;
            $diagnosisQtyTotal = 0.0;
            $grandTotal = 0.0;
            $grandActTotal = 0.0;
            $grandQtyTotal = 0.0;
            $rowCount = count($rows);
            $rowNum = 0;

            for ($i = 0; $i < $rowCount; $i++) {
                $row = $rows[$i];
                $groupDesc = $row->group_desc ?? '';
                $itemName = $row->item_name ?? '';
                $noQty = (float) ($row->no_qty ?? 0);
                $totalAmount = (float) ($row->total_amount ?? 0);
                $totalActAmount = (float) ($row->total_act_amount ?? 0);

                // Diagnosis group header
                if ($groupDesc !== $diagnosisHead) {
                    // Show previous group total if not first group
                    if ($diagnosisHead !== '') {
                        echo '<tr class="table-secondary">';
                        echo '<td></td>';
                        echo '<td colspan="2"><strong>Total of ' . esc($diagnosisHead) . '</strong></td>';
                        echo '<td class="text-end"><strong>' . number_format($diagnosisQtyTotal, 0) . '</strong></td>';
                        echo '<td class="text-end"><strong>' . number_format($diagnosisHeadTotal, 2) . '</strong></td>';
                        echo '<td class="text-end"><strong>' . number_format($diagnosisActHeadTotal, 2) . '</strong></td>';
                        echo '</tr>';
                        
                        $diagnosisHeadTotal = 0.0;
                        $diagnosisActHeadTotal = 0.0;
                        $diagnosisQtyTotal = 0.0;
                    }
                    
                    echo '<tr class="table-info">';
                    echo '<td colspan="6"><strong>' . esc($groupDesc) . '</strong></td>';
                    echo '</tr>';
                    $diagnosisHead = $groupDesc;
                }

                // Item row
                $rowNum++;
                echo '<tr>';
                echo '<td>' . $rowNum . '</td>';
                echo '<td></td>';
                echo '<td>' . esc($itemName) . '</td>';
                echo '<td class="text-end">' . number_format($noQty, 0) . '</td>';
                echo '<td class="text-end">' . number_format($totalAmount, 2) . '</td>';
                echo '<td class="text-end">' . number_format($totalActAmount, 2) . '</td>';
                echo '</tr>';

                // Update totals
                $diagnosisHeadTotal += $totalAmount;
                $diagnosisActHeadTotal += $totalActAmount;
                $diagnosisQtyTotal += $noQty;
                $grandTotal += $totalAmount;
                $grandActTotal += $totalActAmount;
                $grandQtyTotal += $noQty;
            }
            
            // Show last group total
            if ($diagnosisHead !== '' && $rowCount > 0) {
                echo '<tr class="table-secondary">';
                echo '<td></td>';
                echo '<td colspan="2"><strong>Total of ' . esc($diagnosisHead) . '</strong></td>';
                echo '<td class="text-end"><strong>' . number_format($diagnosisQtyTotal, 0) . '</strong></td>';
                echo '<td class="text-end"><strong>' . number_format($diagnosisHeadTotal, 2) . '</strong></td>';
                echo '<td class="text-end"><strong>' . number_format($diagnosisActHeadTotal, 2) . '</strong></td>';
                echo '</tr>';
            }
            ?>
        </tbody>
        <tfoot class="table-primary">
            <tr>
                <th colspan="3"><strong>Grand Total</strong></th>
                <th class="text-end"><strong><?= number_format($grandQtyTotal, 0) ?></strong></th>
                <th class="text-end"><strong><?= number_format($grandTotal, 2) ?></strong></th>
                <th class="text-end"><strong><?= number_format($grandActTotal, 2) ?></strong></th>
            </tr>
        </tfoot>
    </table>
<?php endif; ?>
