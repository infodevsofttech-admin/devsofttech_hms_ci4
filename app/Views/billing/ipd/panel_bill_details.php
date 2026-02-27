<?php
$ipd = $ipd_info ?? null;
$person = $person_info ?? null;
$insurance = $insurance ?? [];
$age = '';
if ($person) {
    $age = get_age_1($person->dob ?? null, $person->age ?? '', $person->age_in_month ?? '', $person->estimate_dob ?? '');
}
$discountTotal = 0.0;
$extraCharge = 0.0;
if ($ipd) {
    $discountTotal = (float) ($ipd->Discount ?? 0) + (float) ($ipd->Discount2 ?? 0) + (float) ($ipd->Discount3 ?? 0);
    $extraCharge = (float) ($ipd->chargeamount1 ?? 0) + (float) ($ipd->chargeamount2 ?? 0);
}
$billTotals = $bill_totals ?? ['gross' => 0.0, 'net' => 0.0];
$paidTotal = (float) ($billTotals['paid'] ?? 0);
$balanceTotal = (float) ($billTotals['balance'] ?? 0);
$grossAmount = (float) ($ipd->gross_amount ?? 0);
$netAmount = (float) ($ipd->net_amount ?? 0);
 $balanceAmount = (float) ($ipd->balance_amount ?? 0);
if ($grossAmount <= 0) {
    $grossAmount = (float) ($billTotals['gross'] ?? 0);
}
if ($netAmount <= 0) {
    $netAmount = (float) ($billTotals['net'] ?? 0);
}
if ($balanceAmount <= 0) {
    $balanceAmount = $balanceTotal;
}
?>

<div class="card border-top border-3 border-danger">
    <div class="card-header">
        <strong>IPD Invoice</strong>
        <span class="text-muted">/ IPD ID: <?= esc($ipd->ipd_code ?? '') ?></span>
    </div>
    <div class="card-body">
        <p class="mb-2">
            <strong>Name :</strong> <?= esc($person->p_fname ?? '') ?>
            <strong>/ Age :</strong> <?= esc($age) ?>
            <strong>/ Gender :</strong> <?= esc($person->xgender ?? '') ?>
            <strong>/ P Code :</strong> <?= esc($person->p_code ?? '') ?>
            <?php if (! empty($insurance['ins_company_name'] ?? '')) : ?>
                <strong>/ Ins. Comp. :</strong> <?= esc($insurance['ins_company_name'] ?? '') ?>
            <?php endif; ?>
        </p>

        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th style="width: 40px">#</th>
                        <th>Description</th>
                        <th style="width: 110px">Org.Code</th>
                        <th style="width: 80px">Unit</th>
                        <th style="width: 110px">Rate</th>
                        <th style="width: 120px">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $srNo = 1;
                    $headDesc = '';
                    $headTotal = 0.0;

                    if (! empty($ipd_packages)) {
                        echo '<tr>';
                        echo '<td colspan="2"><strong>Package</strong></td>';
                        echo '<td colspan="4"></td>';
                        echo '</tr>';
                        $headDesc = 'Package';
                        $headTotal = 0.0;

                        foreach ($ipd_packages as $row) {
                            echo '<tr>';
                            echo '<td>' . $srNo . '</td>';
                            echo '<td>' . esc($row->package_name ?? '') . '</td>';
                            echo '<td>' . esc($row->org_code ?? '') . '</td>';
                            echo '<td></td>';
                            echo '<td class="text-end"></td>';
                            echo '<td class="text-end">' . esc(number_format((float) ($row->package_Amount ?? 0), 2)) . '</td>';
                            $srNo++;
                            $headTotal += (float) ($row->package_Amount ?? 0);
                            echo '</tr>';
                        }

                        echo '<tr>';
                        echo '<td></td><td></td><td></td><td></td>';
                        echo '<td class="text-end">Sub Total</td>';
                        echo '<td class="text-end">' . esc(number_format($headTotal, 2)) . '</td>';
                        echo '</tr>';
                    }

                    $ipdItems = $ipd_invoice_items ?? [];
                    $ipdItemCount = count($ipdItems);
                    for ($i = 0; $i < $ipdItemCount; $i++) {
                        $row = $ipdItems[$i];
                        if ($headDesc !== ($row->group_desc ?? '')) {
                            echo '<tr>';
                            echo '<td colspan="2"><strong>' . esc($row->group_desc ?? '') . '</strong></td>';
                            echo '<td colspan="4"></td>';
                            echo '</tr>';
                            $headDesc = (string) ($row->group_desc ?? '');
                            $headTotal = 0.0;
                        }

                        echo '<tr>';
                        echo '<td>' . $srNo . '</td>';
                        echo '<td>' . esc(($row->item_name ?? '') . ' ' . ($row->comment ?? '')) . '</td>';
                        echo '<td>' . esc($row->org_code ?? '') . '</td>';
                        echo '<td>' . esc($row->item_qty ?? '') . '</td>';
                        echo '<td class="text-end">' . esc(number_format((float) ($row->item_rate ?? 0), 2)) . '</td>';
                        echo '<td class="text-end">' . esc(number_format((float) ($row->item_amount ?? 0), 2)) . '</td>';
                        $srNo++;
                        $headTotal += (float) ($row->item_amount ?? 0);
                        echo '</tr>';

                        $next = $ipdItems[$i + 1] ?? null;
                        if ($next === null || ($headDesc !== ($next->group_desc ?? ''))) {
                            echo '<tr>';
                            echo '<td></td><td></td><td></td><td></td>';
                            echo '<td class="text-end">Sub Total</td>';
                            echo '<td class="text-end">' . esc(number_format($headTotal, 2)) . '</td>';
                            echo '</tr>';
                        }
                    }

                    $showItems = $showinvoice ?? [];
                    $showCount = count($showItems);
                    for ($i = 0; $i < $showCount; $i++) {
                        $row = $showItems[$i];
                        if ($headDesc !== ($row->Charge_type ?? '')) {
                            echo '<tr>';
                            echo '<td colspan="2"><strong>' . esc($row->Charge_type ?? '') . '</strong></td>';
                            echo '<td colspan="4"></td>';
                            echo '</tr>';
                            $headDesc = (string) ($row->Charge_type ?? '');
                            $headTotal = 0.0;
                        }

                        echo '<tr>';
                        echo '<td>' . $srNo . '</td>';
                        echo '<td>' . esc($row->idesc ?? '') . '</td>';
                        echo '<td>' . esc($row->orgcode ?? '') . '</td>';
                        echo '<td>' . esc($row->no_qty ?? '') . '</td>';
                        echo '<td class="text-end">' . esc(number_format((float) ($row->item_rate ?? 0), 2)) . '</td>';
                        echo '<td class="text-end">' . esc(number_format((float) ($row->amount ?? 0), 2)) . '</td>';
                        $srNo++;
                        $headTotal += (float) ($row->amount ?? 0);
                        echo '</tr>';

                        $next = $showItems[$i + 1] ?? null;
                        if ($next === null || ($headDesc !== ($next->Charge_type ?? ''))) {
                            echo '<tr>';
                            echo '<td></td><td></td><td></td><td></td>';
                            echo '<td class="text-end">Sub Total</td>';
                            echo '<td class="text-end">' . esc(number_format($headTotal, 2)) . '</td>';
                            echo '</tr>';
                        }
                    }

                    if (! empty($inv_med_list)) {
                        echo '<tr>';
                        echo '<td colspan="2">Medicine</td>';
                        echo '<td colspan="4"></td>';
                        echo '</tr>';

                        $medTotal = 0.0;
                        foreach ($inv_med_list as $row) {
                            echo '<tr>';
                            echo '<td>' . $srNo . '</td>';
                            echo '<td>' . esc($row->inv_med_code ?? '') . '</td>';
                            echo '<td></td><td></td>';
                            echo '<td class="text-end"></td>';
                            echo '<td class="text-end">' . esc(number_format((float) ($row->net_amount ?? 0), 2)) . '</td>';
                            $srNo++;
                            $medTotal += (float) ($row->net_amount ?? 0);
                            echo '</tr>';
                        }

                        echo '<tr>';
                        echo '<td></td><td></td><td></td><td></td>';
                        echo '<td class="text-end">Med. Total</td>';
                        echo '<td class="text-end">' . esc(number_format($medTotal, 2)) . '</td>';
                        echo '</tr>';
                    }
                    ?>

                    <tr>
                        <th>#</th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th>Gross Total</th>
                        <th class="text-end"><?= esc(number_format($grossAmount, 2)) ?></th>
                    </tr>
                    <?php if (($ipd->Discount ?? 0) > 0) : ?>
                        <tr>
                            <th>#</th>
                            <th colspan="2">Remark :<br><?= esc($ipd->Discount_Remark ?? '') ?></th>
                            <th></th>
                            <th>Deduction</th>
                            <th class="text-end"><?= esc(number_format((float) ($ipd->Discount ?? 0), 2)) ?></th>
                        </tr>
                    <?php endif; ?>
                    <?php if (($ipd->Discount2 ?? 0) > 0) : ?>
                        <tr>
                            <th>#</th>
                            <th colspan="2">Remark :<br><?= esc($ipd->Discount_Remark2 ?? '') ?></th>
                            <th></th>
                            <th>Deduction</th>
                            <th class="text-end"><?= esc(number_format((float) ($ipd->Discount2 ?? 0), 2)) ?></th>
                        </tr>
                    <?php endif; ?>
                    <?php if (($ipd->Discount3 ?? 0) > 0) : ?>
                        <tr>
                            <th>#</th>
                            <th colspan="2">Remark :<br><?= esc($ipd->Discount_Remark3 ?? '') ?></th>
                            <th></th>
                            <th>Deduction</th>
                            <th class="text-end"><?= esc(number_format((float) ($ipd->Discount3 ?? 0), 2)) ?></th>
                        </tr>
                    <?php endif; ?>
                    <?php if (($ipd->chargeamount1 ?? 0) > 0) : ?>
                        <tr>
                            <th>#</th>
                            <th colspan="2">Remark :<br><?= esc($ipd->charge1 ?? '') ?></th>
                            <th></th>
                            <th>Charge</th>
                            <th class="text-end"><?= esc(number_format((float) ($ipd->chargeamount1 ?? 0), 2)) ?></th>
                        </tr>
                    <?php endif; ?>
                    <?php if (($ipd->chargeamount2 ?? 0) > 0) : ?>
                        <tr>
                            <th>#</th>
                            <th colspan="2">Remark :<br><?= esc($ipd->charge2 ?? '') ?></th>
                            <th></th>
                            <th>Charge</th>
                            <th class="text-end"><?= esc(number_format((float) ($ipd->chargeamount2 ?? 0), 2)) ?></th>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <th>#</th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th>Net Amount</th>
                        <th class="text-end"><?= esc(number_format($netAmount, 2)) ?></th>
                    </tr>
                    <tr>
                        <th>#</th>
                        <th colspan="2">
                            Payment Recd.<br>
                            <?php
                            $i = 1;
                            foreach ($ipd_payment ?? [] as $row) {
                                $i++;
                                echo '[' . esc($row->id ?? '') . ':' . esc($row->pay_mode ?? '') . ':' . esc($row->pay_date_str ?? '') . ':' . esc($row->amount ?? '') . '] / ';
                                if ($i % 3 === 0) {
                                    echo '<br>';
                                }
                            }
                            ?>
                        </th>
                        <th></th>
                        <th></th>
                        <th class="text-end"><?= esc(number_format($paidTotal > 0 ? $paidTotal : (float) ($ipd->total_paid_amount ?? 0), 2)) ?></th>
                    </tr>
                    <tr>
                        <th>#</th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th>Balance</th>
                        <th class="text-end"><?= esc(number_format($balanceAmount, 2)) ?></th>
                    </tr>
                    <?php if (($ipd->payable_by_tpa ?? 0) > 0) : ?>
                        <tr>
                            <td>#</td>
                            <td colspan="2">Payable By TPA</td>
                            <td></td>
                            <td></td>
                            <td class="text-end"><?= esc(number_format((float) ($ipd->payable_by_tpa ?? 0), 2)) ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if (($ipd->discount_for_tpa ?? 0) > 0) : ?>
                        <tr>
                            <td>#</td>
                            <td colspan="2">Discount For TPA</td>
                            <td></td>
                            <td></td>
                            <td class="text-end"><?= esc(number_format((float) ($ipd->discount_for_tpa ?? 0), 2)) ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if (($ipd->discount_by_hospital ?? 0) > 0) : ?>
                        <tr>
                            <td>#</td>
                            <td colspan="2">Discount By Hospital</td>
                            <td><?= esc($ipd->discount_by_hospital_remark ?? '') ?></td>
                            <td></td>
                            <td class="text-end"><?= esc(number_format((float) ($ipd->discount_by_hospital ?? 0), 2)) ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if (($ipd->discount_by_hospital_2 ?? 0) > 0) : ?>
                        <tr>
                            <td>#</td>
                            <td colspan="2">Discount By Hospital/ Doctor</td>
                            <td><?= esc($ipd->discount_by_hospital_2_remark ?? '') ?></td>
                            <td></td>
                            <td class="text-end"><?= esc(number_format((float) ($ipd->discount_by_hospital_2 ?? 0), 2)) ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <th>#</th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th>Final Balance</th>
                        <th class="text-end"><?= esc(number_format((float) ($ipd->balance_discount_after ?? 0), 2)) ?></th>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
