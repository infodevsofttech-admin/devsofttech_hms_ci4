<?php $rows = $rows ?? []; ?>
<div class="table-responsive">
    <table class="table table-sm table-striped align-middle mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>Payment ID</th>
                <th>Payment Date</th>
                <th>Invoice Ref</th>
                <th>Collector</th>
                <th>Mode</th>
                <th class="text-end">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7" class="text-center text-muted">No payment rows were saved for this submission.</td></tr>
            <?php else: ?>
                <?php $sr = 1; $total = 0.0; foreach ($rows as $row): ?>
                    <?php $amt = (float) ($row['amount'] ?? 0); $total += $amt; ?>
                    <tr>
                        <td><?= $sr++ ?></td>
                        <td><?= (int) ($row['payment_history_id'] ?? 0) ?></td>
                        <td><?= esc((string) ($row['payment_date'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['payof_code'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['update_by'] ?? '')) ?></td>
                        <td>
                            <?php
                            $mode = (int) ($row['payment_mode'] ?? 0);
                            echo esc($mode === 1 ? 'Cash' : ($mode === 2 ? 'Bank' : 'Other'));
                            ?>
                        </td>
                        <td class="text-end"><?= number_format($amt, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="6" class="text-end fw-semibold">Total</td>
                    <td class="text-end fw-semibold"><?= number_format($total, 2) ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
