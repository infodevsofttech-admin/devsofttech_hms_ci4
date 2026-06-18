<?php $rows = $purchase_return_invoice_item ?? []; ?>
<div class="table-responsive">
    <table id="purchase_return_report_list" class="table table-bordered table-striped table-sm align-middle">
        <thead>
        <tr>
            <th>#</th>
            <th>Item</th>
            <th>Batch</th>
            <th>Expiry</th>
            <th>Qty</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="5" class="text-center text-muted">No return items found.</td></tr>
        <?php endif; ?>
        <?php $srno = 0; foreach ($rows as $row): $srno++; ?>
            <tr>
                <td><?= $srno ?></td>
                <td><?= esc((string) ($row->Item_name ?? '')) ?></td>
                <td><?= esc((string) ($row->batch_no_r ?? $row->batch_no ?? '-')) ?></td>
                <td><?= esc((string) ($row->expiry_date_r ?? $row->expiry_date ?? '-')) ?></td>
                <td><?= esc((string) ($row->r_qty ?? $row->qty ?? '0')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>