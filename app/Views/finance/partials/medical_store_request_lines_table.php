<?php $req = $request ?? null; ?>
<div class="p-3">
    <?php if (!is_array($req)): ?>
        <div class="alert alert-warning mb-0">Request not found.</div>
    <?php else: ?>
        <div class="row g-2 mb-3">
            <div class="col-md-3"><strong>Request:</strong> <?= esc((string) ($req['request_no'] ?? '')) ?></div>
            <div class="col-md-2"><strong>Status:</strong> <?= esc((string) ($req['status'] ?? '')) ?></div>
            <div class="col-md-2"><strong>Date:</strong> <?= esc((string) ($req['request_date'] ?? '')) ?></div>
            <div class="col-md-5 text-md-end">
                <strong>Requested:</strong> <?= esc(number_format((float) ($req['requested_amount'] ?? 0), 2)) ?> |
                <strong>Paid:</strong> <?= esc(number_format((float) ($req['paid_amount'] ?? 0), 2)) ?> |
                <strong>Pending:</strong> <?= esc(number_format((float) ($req['pending_amount'] ?? 0), 2)) ?>
            </div>
        </div>

        <div class="table-responsive mb-3">
            <table class="table table-sm table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Source</th>
                        <th>Invoice</th>
                        <th>IPD</th>
                        <th>Case</th>
                        <th>Category</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Allocated</th>
                        <th class="text-end">Pending</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lines ?? [])): ?>
                        <tr><td colspan="10" class="text-center text-muted">No request lines.</td></tr>
                    <?php else: ?>
                        <?php foreach (($lines ?? []) as $i => $line): ?>
                            <tr>
                                <td><?= (int) $i + 1 ?></td>
                                <td><?= esc((string) ($line['source_type'] ?? '')) ?>#<?= (int) ($line['source_ref_id'] ?? 0) ?></td>
                                <td><?= esc((string) ($line['invoice_code'] ?? '')) ?></td>
                                <td><?= esc((string) ($line['ipd_code'] ?? '')) ?></td>
                                <td><?= esc((string) ($line['case_code'] ?? '')) ?></td>
                                <td><?= esc((string) ($line['credit_category'] ?? '')) ?></td>
                                <td class="text-end"><?= esc(number_format((float) ($line['line_amount'] ?? 0), 2)) ?></td>
                                <td class="text-end"><?= esc(number_format((float) ($line['allocated_amount'] ?? 0), 2)) ?></td>
                                <td class="text-end"><?= esc(number_format((float) ($line['pending_amount'] ?? 0), 2)) ?></td>
                                <td><span class="badge bg-secondary"><?= esc((string) ($line['line_status'] ?? '')) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h6 class="mb-2">Payments</h6>
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Payment No</th>
                        <th>Date</th>
                        <th>Mode</th>
                        <th class="text-end">Amount</th>
                        <th>Reconcile</th>
                        <th>Remark</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments ?? [])): ?>
                        <tr><td colspan="7" class="text-center text-muted">No payments posted yet.</td></tr>
                    <?php else: ?>
                        <?php foreach (($payments ?? []) as $j => $p): ?>
                            <tr>
                                <td><?= (int) $j + 1 ?></td>
                                <td><?= esc((string) ($p['payment_no'] ?? '')) ?></td>
                                <td><?= esc((string) ($p['payment_date'] ?? '')) ?></td>
                                <td><?= (int) ($p['payment_mode'] ?? 0) === 2 ? 'Bank' : 'Cash' ?></td>
                                <td class="text-end"><?= esc(number_format((float) ($p['amount'] ?? 0), 2)) ?></td>
                                <td><?= esc((string) (($p['bank_reconcile_status'] ?? '') !== '' ? $p['bank_reconcile_status'] : 'unmatched')) ?></td>
                                <td><?= esc((string) ($p['remark'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
