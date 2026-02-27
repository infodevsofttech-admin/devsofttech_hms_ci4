<?php
$itemName = '';
if (!empty($purchase_item_old ?? [])) {
    $itemName = (string) ($purchase_item_old[0]->Item_name ?? '');
}
?>

<?php if (empty($purchase_item_old ?? [])): ?>
<p class="text-muted mb-0">No purchase history found for selected product.</p>
<?php return; endif; ?>

<p class="text-danger">Item : <?= esc($itemName) ?> Last 5 Purchase Orders</p>

<table class="table table-striped table-sm">
    <thead>
    <tr>
        <th>Date</th>
        <th>MRP</th>
        <th>Qty</th>
        <th>Rate</th>
        <th>SCH/Disc</th>
        <th>P.U.Rate</th>
        <th>Supplier/Invoice</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach (($purchase_item_old ?? []) as $row): ?>
        <tr>
            <td><?= esc($row->date_of_invoice_str ?? '') ?></td>
            <td><?= esc((string) ($row->mrp ?? 0)) ?></td>
            <td><?= esc((string) ($row->qty ?? 0)) ?> + <?= esc((string) ($row->qty_free ?? 0)) ?></td>
            <td><?= esc((string) ($row->purchase_price ?? 0)) ?></td>
            <td><?= esc((string) ($row->sch_disc_per ?? 0)) ?> / <?= esc((string) ($row->discount ?? 0)) ?></td>
            <td><?= esc((string) ($row->purchase_unit_rate ?? 0)) ?></td>
            <td><?= esc(($row->name_supplier ?? '-') . '/' . ($row->Invoice_no ?? '')) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
