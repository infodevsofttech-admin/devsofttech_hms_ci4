<div class="table-responsive">
    <table class="table table-sm table-striped align-middle mb-0">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Bill No</th>
                <th>Date</th>
                <th>Pharmacy / Supplier</th>
                <th>Description</th>
                <th class="text-end">Bill Amt</th>
                <th class="text-end">Tax</th>
                <th class="text-end">Net Payable</th>
                <th class="text-end">Paid</th>
                <th class="text-end">Balance</th>
                <th>Status</th>
                <th>Mode</th>
                <th>Remarks</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pharmacy_bills ?? [])): ?>
                <tr>
                    <td colspan="14" class="text-center text-muted py-3">No pharmacy bills registered yet.</td>
                </tr>
            <?php else: ?>
                <?php
                $sr = 1;
                foreach (($pharmacy_bills ?? []) as $row):
                    $net     = (float) ($row['net_amount'] ?? 0);
                    $paid    = (float) ($row['paid_amount'] ?? 0);
                    $balance = max(0, $net - $paid);
                    $status  = (string) ($row['payment_status'] ?? 'pending');
                    $statusClass = match ($status) {
                        'paid'      => 'success',
                        'part_paid' => 'warning text-dark',
                        default     => 'danger',
                    };
                ?>
                <tr>
                    <td><?= $sr++ ?></td>
                    <td><?= esc((string) ($row['bill_no'] ?? '')) ?></td>
                    <td><?= esc((string) ($row['bill_date'] ?? '')) ?></td>
                    <td><?= esc((string) ($row['pharmacy_name'] ?? '')) ?></td>
                    <td><?= esc((string) ($row['description'] ?? '')) ?></td>
                    <td class="text-end"><?= number_format((float) ($row['bill_amount'] ?? 0), 2) ?></td>
                    <td class="text-end"><?= number_format((float) ($row['tax_amount'] ?? 0), 2) ?></td>
                    <td class="text-end fw-semibold"><?= number_format($net, 2) ?></td>
                    <td class="text-end text-success"><?= number_format($paid, 2) ?></td>
                    <td class="text-end <?= $balance > 0 ? 'text-danger fw-semibold' : 'text-muted' ?>">
                        <?= number_format($balance, 2) ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $statusClass ?>">
                            <?= esc(ucfirst(str_replace('_', ' ', $status))) ?>
                        </span>
                    </td>
                    <td><?= esc(strtoupper((string) ($row['payment_mode'] ?? ''))) ?></td>
                    <td class="text-muted small"><?= esc((string) ($row['remarks'] ?? '')) ?></td>
                    <td>
                        <?php if ($status !== 'paid'): ?>
                            <button type="button" class="btn btn-outline-success btn-sm js-pharm-settle"
                                    data-id="<?= (int) ($row['id'] ?? 0) ?>"
                                    data-bill-no="<?= esc((string) ($row['bill_no'] ?? '')) ?>"
                                    data-net="<?= $net ?>"
                                    data-paid="<?= $paid ?>">
                                Pay
                            </button>
                        <?php else: ?>
                            <span class="text-muted small"><?= esc((string) ($row['payment_date'] ?? '')) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <?php if (!empty($pharmacy_bills ?? [])): ?>
        <tfoot class="table-light fw-semibold">
            <?php
            $totNet  = array_sum(array_column($pharmacy_bills, 'net_amount'));
            $totPaid = array_sum(array_column($pharmacy_bills, 'paid_amount'));
            $totBal  = max(0, $totNet - $totPaid);
            ?>
            <tr>
                <td colspan="7" class="text-end">Totals:</td>
                <td class="text-end"><?= number_format($totNet, 2) ?></td>
                <td class="text-end text-success"><?= number_format($totPaid, 2) ?></td>
                <td class="text-end text-danger"><?= number_format($totBal, 2) ?></td>
                <td colspan="4"></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>
</div>
