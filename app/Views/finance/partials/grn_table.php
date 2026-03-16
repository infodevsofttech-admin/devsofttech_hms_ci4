<div class="table-responsive">
    <table class="table table-sm table-striped align-middle mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>GRN No</th>
                <th>Date</th>
                <th>PO No</th>
                <th>Received Amount</th>
                <th>Received By</th>
                <th>Notes</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($grns ?? [])): ?>
                <tr><td colspan="8" class="text-center text-muted">No GRN entries yet.</td></tr>
            <?php else: ?>
                <?php $sr = 1; foreach (($grns ?? []) as $row): ?>
                    <tr>
                        <td><?= $sr++ ?></td>
                        <td><?= esc((string) ($row['grn_no'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['grn_date'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['po_no'] ?? '')) ?></td>
                        <td><?= number_format((float) ($row['received_amount'] ?? 0), 2) ?></td>
                        <td><?= esc((string) ($row['received_by'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['remarks'] ?? '')) ?></td>
                        <td>
                            <a class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener" href="<?= base_url('Finance/grn_print/' . (int) ($row['id'] ?? 0)) ?>">Print</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
