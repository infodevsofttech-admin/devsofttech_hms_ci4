<?php
$opdRows = $opd_rows ?? [];
$ipdRows = $ipd_rows ?? [];
$minRange = $min_range ?? '';
$maxRange = $max_range ?? '';
?>

<div class="mb-3">
    <p><strong>Date Range:</strong> <?= esc($minRange) ?> to <?= esc($maxRange) ?></p>
</div>

<?php if (empty($opdRows) && empty($ipdRows)) : ?>
    <div class="alert alert-info">No data found for the selected criteria.</div>
<?php else : ?>
    
    <?php if (!empty($opdRows)) : ?>
        <h6 class="mt-4 mb-3">
            <i class="bi bi-clipboard2-pulse me-2"></i>OPD Organization Cases Summary
        </h6>
        <table class="table table-bordered table-sm table-striped">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Insurance Company</th>
                    <th class="text-center" style="width: 120px;">Total Cases</th>
                    <th class="text-end" style="width: 150px;">Total Charge</th>
                    <th class="text-end" style="width: 150px;">Total Medicine</th>
                    <th class="text-end" style="width: 150px;">Total Net</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $opdTotalCases = 0;
                $opdTotalCharge = 0.0;
                $opdTotalMed = 0.0;
                $opdTotalNet = 0.0;

                foreach ($opdRows as $index => $row) {
                    $cases = (int) ($row->total_cases ?? 0);
                    $charge = (float) ($row->total_charge ?? 0);
                    $med = (float) ($row->total_med ?? 0);
                    $net = (float) ($row->total_net ?? 0);

                    $opdTotalCases += $cases;
                    $opdTotalCharge += $charge;
                    $opdTotalMed += $med;
                    $opdTotalNet += $net;

                    echo '<tr>';
                    echo '<td>' . ($index + 1) . '</td>';
                    echo '<td>' . esc($row->insurance_company ?? '') . '</td>';
                    echo '<td class="text-center">' . number_format($cases) . '</td>';
                    echo '<td class="text-end">' . number_format($charge, 2) . '</td>';
                    echo '<td class="text-end">' . number_format($med, 2) . '</td>';
                    echo '<td class="text-end"><strong>' . number_format($net, 2) . '</strong></td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
            <tfoot class="table-success">
                <tr>
                    <th colspan="2">OPD Total:</th>
                    <th class="text-center"><?= number_format($opdTotalCases) ?></th>
                    <th class="text-end"><?= number_format($opdTotalCharge, 2) ?></th>
                    <th class="text-end"><?= number_format($opdTotalMed, 2) ?></th>
                    <th class="text-end"><?= number_format($opdTotalNet, 2) ?></th>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>

    <?php if (!empty($ipdRows)) : ?>
        <h6 class="mt-4 mb-3">
            <i class="bi bi-hospital me-2"></i>IPD TPA Cases Summary
        </h6>
        <table class="table table-bordered table-sm table-striped">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Insurance Company</th>
                    <th class="text-center" style="width: 100px;">Cases</th>
                    <th class="text-end" style="width: 120px;">Charge</th>
                    <th class="text-end" style="width: 120px;">Medicine</th>
                    <th class="text-end" style="width: 100px;">Discount</th>
                    <th class="text-end" style="width: 120px;">Net</th>
                    <th class="text-end" style="width: 120px;">Received</th>
                    <th class="text-end" style="width: 120px;">Deducted</th>
                    <th class="text-end" style="width: 120px;">Balance</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $ipdTotalCases = 0;
                $ipdTotalCharge = 0.0;
                $ipdTotalMed = 0.0;
                $ipdTotalDiscount = 0.0;
                $ipdTotalNet = 0.0;
                $ipdTotalReceived = 0.0;
                $ipdTotalDeducted = 0.0;

                foreach ($ipdRows as $index => $row) {
                    $cases = (int) ($row->total_cases ?? 0);
                    $charge = (float) ($row->total_charge ?? 0);
                    $med = (float) ($row->total_med ?? 0);
                    $discount = (float) ($row->total_discount ?? 0);
                    $net = (float) ($row->total_net ?? 0);
                    $received = (float) ($row->total_received ?? 0);
                    $deducted = (float) ($row->total_deducted ?? 0);
                    $balance = $net - $received;

                    $ipdTotalCases += $cases;
                    $ipdTotalCharge += $charge;
                    $ipdTotalMed += $med;
                    $ipdTotalDiscount += $discount;
                    $ipdTotalNet += $net;
                    $ipdTotalReceived += $received;
                    $ipdTotalDeducted += $deducted;

                    echo '<tr>';
                    echo '<td>' . ($index + 1) . '</td>';
                    echo '<td>' . esc($row->insurance_company ?? '') . '</td>';
                    echo '<td class="text-center">' . number_format($cases) . '</td>';
                    echo '<td class="text-end">' . number_format($charge, 2) . '</td>';
                    echo '<td class="text-end">' . number_format($med, 2) . '</td>';
                    echo '<td class="text-end">' . number_format($discount, 2) . '</td>';
                    echo '<td class="text-end"><strong>' . number_format($net, 2) . '</strong></td>';
                    echo '<td class="text-end">' . number_format($received, 2) . '</td>';
                    echo '<td class="text-end">' . number_format($deducted, 2) . '</td>';
                    echo '<td class="text-end">' . number_format($balance, 2) . '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
            <tfoot class="table-info">
                <tr>
                    <th colspan="2">IPD Total:</th>
                    <th class="text-center"><?= number_format($ipdTotalCases) ?></th>
                    <th class="text-end"><?= number_format($ipdTotalCharge, 2) ?></th>
                    <th class="text-end"><?= number_format($ipdTotalMed, 2) ?></th>
                    <th class="text-end"><?= number_format($ipdTotalDiscount, 2) ?></th>
                    <th class="text-end"><?= number_format($ipdTotalNet, 2) ?></th>
                    <th class="text-end"><?= number_format($ipdTotalReceived, 2) ?></th>
                    <th class="text-end"><?= number_format($ipdTotalDeducted, 2) ?></th>
                    <th class="text-end"><?= number_format($ipdTotalNet - $ipdTotalReceived, 2) ?></th>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>

    <?php if (!empty($opdRows) && !empty($ipdRows)) : ?>
        <div class="card bg-light mt-4">
            <div class="card-body">
                <h6 class="card-title">
                    <i class="bi bi-calculator me-2"></i>Grand Summary
                </h6>
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm mb-0">
                            <tr>
                                <td><strong>Total Cases (OPD + IPD):</strong></td>
                                <td class="text-end"><?= number_format($opdTotalCases + $ipdTotalCases) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Total Net Amount:</strong></td>
                                <td class="text-end"><strong><?= number_format($opdTotalNet + $ipdTotalNet, 2) ?></strong></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm mb-0">
                            <tr>
                                <td><strong>IPD Amount Received:</strong></td>
                                <td class="text-end"><?= number_format($ipdTotalReceived, 2) ?></td>
                            </tr>
                            <tr>
                                <td><strong>IPD Balance Pending:</strong></td>
                                <td class="text-end"><strong><?= number_format($ipdTotalNet - $ipdTotalReceived, 2) ?></strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

<?php endif; ?>
