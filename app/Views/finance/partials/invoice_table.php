<div class="table-responsive">
    <table class="table table-sm table-striped align-middle mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>Invoice No</th>
                <th>Date</th>
                <th>Vendor</th>
                <th>PO</th>
                <th>GRN</th>
                <th>Amount</th>
                <th>Payment</th>
                <th>Match</th>
                <th>Variance</th>
                <th>Compliance</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($vendor_invoices ?? [])): ?>
                <tr><td colspan="11" class="text-center text-muted">No vendor invoices yet.</td></tr>
            <?php else: ?>
                <?php $sr = 1; foreach (($vendor_invoices ?? []) as $row): ?>
                    <tr>
                        <td><?= $sr++ ?></td>
                        <td><?= esc((string) ($row['invoice_no'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['invoice_date'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['vendor_name'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['po_no'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['grn_no'] ?? '')) ?></td>
                        <td><?= number_format((float) ($row['invoice_amount'] ?? 0), 2) ?></td>
                        <td><span class="badge bg-warning text-dark"><?= esc((string) ($row['payment_status'] ?? 'pending')) ?></span></td>
                        <td>
                            <?php $match = (string) ($row['match_status'] ?? 'not_checked'); ?>
                            <?php $matchClass = $match === 'matched' ? 'success' : (($match === 'minor_variance') ? 'warning text-dark' : 'danger'); ?>
                            <span class="badge bg-<?= $matchClass ?>"><?= esc($match) ?></span>
                        </td>
                        <td><?= number_format((float) ($row['variance_amount'] ?? 0), 2) ?></td>
                        <td>
                            <?php if ((int) ($row['is_compliance_hold'] ?? 0) === 1): ?>
                                <span class="badge bg-danger">Hold</span>
                            <?php else: ?>
                                <span class="badge bg-success">Clear</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
