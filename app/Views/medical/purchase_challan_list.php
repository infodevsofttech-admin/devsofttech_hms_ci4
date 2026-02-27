<div class="table-responsive" style="max-height:500px;overflow-y:auto;">
    <?php if (empty($purchase_challan_item ?? [])): ?>
        <p class="text-muted mb-0">No challan items available for this supplier.</p>
    <?php else: ?>
    <table class="table table-sm">
        <tbody>
        <?php $purchaseId = 0; foreach (($purchase_challan_item ?? []) as $row): ?>
            <?php if ($purchaseId !== (int) ($row->id ?? 0)): ?>
                <tr>
                    <th colspan="4">Challan No: <?= esc((string) ($row->Invoice_no ?? '')) ?> / Date: <?= esc((string) ($row->str_date_of_invoice ?? '')) ?> / Net Amount: <?= esc((string) ($row->tamount ?? 0)) ?></th>
                </tr>
                <tr style="color:red;">
                    <td>Item Name</td>
                    <td>Qty + Free</td>
                    <td>MRP</td>
                    <td>Add</td>
                </tr>
            <?php endif; ?>

            <tr>
                <td><?= esc($row->Item_name ?? '') ?></td>
                <td><?= esc((string) ($row->qty ?? 0)) ?> + <?= esc((string) ($row->qty_free ?? 0)) ?></td>
                <td><?= esc((string) ($row->mrp ?? 0)) ?></td>
                <td><a href="javascript:add_item_in_invoice(<?= (int) ($row->ss_no ?? 0) ?>)">Add</a></td>
            </tr>

            <?php $purchaseId = (int) ($row->id ?? 0); ?>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
