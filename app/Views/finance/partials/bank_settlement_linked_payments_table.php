<?php
$rows = $rows ?? [];
$entry = $entry ?? [];
$settlementId = (int) ($settlement_id ?? 0);
$total = 0.0;
foreach ($rows as $r) {
    $total += (float) ($r['amount'] ?? 0);
}
?>
<div class="p-2 border-bottom bg-light small d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <strong>Settlement Ref:</strong> <?= esc((string) ($entry['settlement_ref'] ?? '')) ?>
        <span class="ms-3"><strong>Date:</strong> <?= esc((string) ($entry['settlement_date'] ?? '')) ?></span>
        <span class="ms-3"><strong>Rows:</strong> <?= count($rows) ?></span>
        <span class="ms-3"><strong>Total:</strong> ₹<?= number_format($total, 2) ?></span>
    </div>
    <?php if ($settlementId > 0): ?>
        <button type="button" class="btn btn-outline-success btn-sm"
            onclick="window.open('<?= base_url('Finance/bank_settlement_linked_payments_export?settlement_id=' . $settlementId) ?>', '_blank')">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV
        </button>
    <?php endif; ?>
</div>
<div class="table-responsive">
    <table class="table table-sm table-striped align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>HMS Ref</th>
                <th>Payment Date</th>
                <th class="text-end">Amount</th>
                <th>Txn Ref / UTR</th>
                <th>Channel</th>
                <th>Accepted By</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-3">No linked payments found.</td>
                </tr>
            <?php else: ?>
                <?php $sr = 1; foreach ($rows as $row): ?>
                    <?php
                    $sourceLabel = trim((string) ($row['bank_source_label'] ?? ''));
                    if ($sourceLabel === '[]') {
                        $sourceLabel = '';
                    }
                    $isPos = stripos($sourceLabel, 'machine') !== false || stripos((string) ($row['bankcard_machine'] ?? ''), 'machine') !== false;
                    ?>
                    <tr>
                        <td><?= $sr++ ?></td>
                        <td class="font-monospace small">PH-<?= (int) ($row['id'] ?? 0) ?></td>
                        <td class="text-nowrap"><?= esc((string) ($row['payment_date'] ?? '')) ?></td>
                        <td class="text-end fw-semibold">₹<?= number_format((float) ($row['amount'] ?? 0), 2) ?></td>
                        <td class="font-monospace small"><?= esc((string) ($row['card_tran_id'] ?? '—')) ?></td>
                        <td>
                            <span class="badge <?= $isPos ? 'bg-info text-dark' : 'bg-secondary' ?>"><?= $isPos ? 'POS' : 'UPI/Direct' ?></span>
                            <?php if ($sourceLabel !== ''): ?>
                                <div class="small text-muted"><?= esc($sourceLabel) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= esc((string) ($row['accepted_by_name'] ?? '—')) ?></td>
                        <td>
                            <?php if ((string) ($row['bank_reconcile_status'] ?? '') === 'matched'): ?>
                                <span class="badge bg-success">Matched</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Unmatched</span>
                            <?php endif; ?>
                            <?php if ((string) ($row['bank_reconcile_batch_ref'] ?? '') !== ''): ?>
                                <div class="small text-muted"><?= esc((string) ($row['bank_reconcile_batch_ref'] ?? '')) ?></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
