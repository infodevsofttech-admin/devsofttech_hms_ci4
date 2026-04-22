<?= $this->include('storestock/_niceadmin_theme') ?>
<div class="storestock-ui">
<table class="table table-condensed table-hover">
    <thead>
        <tr>
            <th>Pur. ID</th>
            <th>Supplier</th>
            <th>Inv.No / Date</th>
            <th>Bat.No.</th>
            <th>Exp.Dt.</th>
            <th>MRP</th>
            <th>Purchase</th>
            <th>Qty / Packing</th>
            <th>Unit Qty</th>
            <th>Sale U.Qty</th>
            <th>Lost Unit</th>
            <th>Curr.U.Qty</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($product_purchase_detail as $row) { ?>
            <tr>
                <td><?= esc($row->id) ?></td>
                <td><?= esc($row->name_supplier) ?></td>
                <td><?= esc($row->Invoice_no) ?><br /><?= esc($row->date_of_invoice) ?></td>
                <td><?= esc($row->batch_no) ?></td>
                <td><?= esc($row->expiry_date) ?></td>
                <td><?= esc($row->mrp) ?></td>
                <td><?= esc($row->purchase_price) ?></td>
                <td><?= esc($row->tqty) ?> / <?= esc($row->packing) ?></td>
                <td><?= esc($row->total_unit) ?></td>
                <td><?= esc($row->total_sale_unit) ?></td>
                <td><?= esc($row->total_lost_unit) ?></td>
                <td><?= esc($row->cur_unit) ?></td>
            </tr>
        <?php } ?>
        <?php if (empty($product_purchase_detail)) { ?>
            <tr><td colspan="12" class="text-center text-muted">No stock records found.</td></tr>
        <?php } ?>
    </tbody>
</table>
</div>
