<div class="table-responsive">
    <table class="table table-sm table-striped align-middle mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>PO No</th>
                <th>Date</th>
                <th>Vendor</th>
                <th>Department</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Document</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($purchase_orders ?? [])): ?>
                <tr><td colspan="9" class="text-center text-muted">No purchase orders yet.</td></tr>
            <?php else: ?>
                <?php $sr = 1; foreach (($purchase_orders ?? []) as $row): ?>
                    <tr>
                        <td><?= $sr++ ?></td>
                        <td><?= esc((string) ($row['po_no'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['po_date'] ?? '')) ?></td>
                        <td><?= esc(trim((string) ($row['vendor_code'] ?? '') . ' - ' . (string) ($row['vendor_name'] ?? ''))) ?></td>
                        <td><?= esc((string) ($row['department'] ?? '')) ?></td>
                        <td><?= number_format((float) ($row['amount'] ?? 0), 2) ?></td>
                        <td><span class="badge bg-info text-dark"><?= esc((string) ($row['approval_status'] ?? '')) ?></span></td>
                        <td>
                            <?php if (! empty($row['po_document_path'])): ?>
                                <a href="<?= base_url((string) $row['po_document_path']) ?>" target="_blank" rel="noopener">View</a>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                            <?php if ((int) ($row['document_count'] ?? 0) > 0): ?>
                                <div class="small text-muted"><?= (int) ($row['document_count'] ?? 0) ?> file(s)</div>
                            <?php else: ?>
                                <div class="small text-muted">Manage in Edit</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button
                                type="button"
                                class="btn btn-outline-primary btn-sm js-po-edit"
                                data-id="<?= (int) ($row['id'] ?? 0) ?>"
                                data-po-no="<?= esc((string) ($row['po_no'] ?? ''), 'attr') ?>"
                                data-po-date="<?= esc((string) ($row['po_date'] ?? ''), 'attr') ?>"
                                data-vendor-id="<?= (int) ($row['vendor_id'] ?? 0) ?>"
                                data-department="<?= esc((string) ($row['department'] ?? ''), 'attr') ?>"
                                data-amount="<?= esc((string) ($row['amount'] ?? 0), 'attr') ?>"
                                data-status="<?= esc((string) ($row['approval_status'] ?? 'draft'), 'attr') ?>"
                            >Edit</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
