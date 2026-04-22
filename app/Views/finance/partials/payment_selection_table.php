<?php
$rows = $rows ?? [];
$totals = $totals ?? ['payment_count' => 0, 'total_receipts' => 0];
?>
<div class="d-flex justify-content-between align-items-center mb-2">
    <div class="small text-muted">Matched Payments: <strong><?= (int) ($totals['payment_count'] ?? 0) ?></strong></div>
    <div class="small text-muted">Total: <strong><?= number_format((float) ($totals['total_receipts'] ?? 0), 2) ?></strong></div>
</div>
<div class="table-responsive border rounded">
    <table class="table table-sm table-striped align-middle mb-0">
        <thead>
            <tr>
                <th style="width:36px;"><input type="checkbox" id="payment_select_all"></th>
                <th>#</th>
                <th>Payment Date</th>
                <th>Invoice Ref</th>
                <th>Collected By</th>
                <th>Mode</th>
                <th class="text-end">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7" class="text-center text-muted">No payments found for selected filter.</td></tr>
            <?php else: ?>
                <?php $sr = 1; foreach ($rows as $row): ?>
                    <?php $pid = (int) ($row['id'] ?? 0); ?>
                    <tr>
                        <td><input type="checkbox" class="payment-checkbox" name="payment_ids[]" value="<?= $pid ?>"></td>
                        <td><?= $sr++ ?></td>
                        <td><?= esc((string) ($row['payment_date'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['payof_code'] ?? '')) ?></td>
                        <td>
                            <?php
                            $collector = trim((string) ($row['update_by'] ?? ''));
                            $collectorId = (int) ($row['update_by_id'] ?? 0);
                            echo esc($collector !== '' ? $collector : ('User #' . $collectorId));
                            ?>
                        </td>
                        <td>
                            <?php
                            $mode = (int) ($row['payment_mode'] ?? 0);
                            echo esc($mode === 1 ? 'Cash' : ($mode === 2 ? 'Bank' : 'Other'));
                            ?>
                        </td>
                        <td class="text-end payment-amount" data-amount="<?= (float) ($row['amount'] ?? 0) ?>"><?= number_format((float) ($row['amount'] ?? 0), 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
