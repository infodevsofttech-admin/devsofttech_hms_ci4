<?php $purchaseList = $purchase_list ?? []; ?>

<div class="table-responsive">
    <table id="table_old_data" class="table table-striped table-hover table-sm" style="font-size:12px;">
        <thead>
        <tr>
            <th>#</th>
            <th>Pur.Date</th>
            <th>Item</th>
            <th>Bat./Exp.Dt</th>
            <th>Rate</th>
            <th>Pur. Qty</th>
            <th>C.Unit Qty</th>
            <th>R.Unit Qty</th>
        </tr>
        </thead>
        <tbody>
        <?php $srno = 0; ?>
        <?php foreach ($purchaseList as $row): ?>
            <?php $srno++; ?>
            <tr <?= ((int) ($row->isExp ?? 0) === 1) ? 'style="color:red;"' : '' ?>>
                <td><?= $srno ?></td>
                <td><?= esc((string) ($row->str_date_of_invoice ?? '')) ?></td>
                <td><?= esc((string) ($row->Item_name ?? '')) ?></td>
                <td><?= esc((string) ($row->batch_no ?? '')) ?><br><?= esc((string) ($row->exp_date ?? '')) ?></td>
                <td style="text-align:right;"><?= esc((string) ($row->mrp ?? 0)) ?></td>
                <td style="text-align:right;"><?= esc((string) ($row->tqty ?? 0)) ?> /<?= esc((string) ($row->packing ?? 1)) ?></td>
                <td style="text-align:right;"><?= esc((string) ($row->cur_qty ?? 0)) ?></td>
                <td>
                    <?php if ((int) ($row->item_return ?? 0) === 0 && (int) ($row->remove_item ?? 0) === 0): ?>
                        <div class="input-group input-group-sm" style="max-width:170px;">
                            <input type="number" class="form-control" style="height:30px;" name="input_qty_<?= (int) ($row->id ?? 0) ?>" id="input_qty_<?= (int) ($row->id ?? 0) ?>" value="<?= esc((string) ($row->cur_qty ?? 0)) ?>" min="1">
                            <button type="button" class="btn btn-danger" style="height:30px;" onclick="remove_item_add(<?= (int) ($row->id ?? 0) ?>)">
                                <i class="fa fa-minus-circle"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
(function () {
    if (window.jQuery && $.fn.DataTable) {
        if ($.fn.DataTable.isDataTable('#table_old_data')) {
            $('#table_old_data').DataTable().destroy();
        }
        $('#table_old_data').DataTable({
            pageLength: 25,
            order: [[1, 'desc']]
        });
    }
})();
</script>
