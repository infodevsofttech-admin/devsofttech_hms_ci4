<?php
$item_codes = [];
foreach ($inv_stock_item as $row) {
    $item_codes[] = $row->item_code;
}
$duplicate_codes = array_filter(
    array_count_values($item_codes),
    fn($c) => $c > 1
);
?>
<table class="table table-striped table-condensed table-hover">
    <tr>
        <th style="width:10px;">#</th>
        <th>Item Name</th>
        <th>Batch No</th>
        <th>Exp.</th>
        <th style="text-align:right;">Rate</th>
        <th style="text-align:right;">S.Qty.</th>
        <th>Qty.</th>
        <th style="text-align:right;">Price</th>
        <th style="text-align:right;">Amount</th>
        <th></th>
    </tr>
    <?php
    $srno = 0;
    foreach ($inv_stock_item as $row) {
        $srno++;
        $style = '';

        if (isset($row->sale_return) && $row->sale_return == 1) {
            $style = 'style="background:orange;"';
        }

        if (isset($row->no_day) && $row->no_day < 90) {
            $style = 'style="color:red;"';
        }

        if (isset($row->cur_qty) && $row->cur_qty < 6) {
            $style = 'style="color:red;"';
        }

        $style_repeat = isset($duplicate_codes[$row->item_code]) ? 'style="color:green;"' : '';
        ?>
        <tr <?= $style ?>>
            <td><?= $srno ?></td>
            <td <?= $style_repeat ?>><?= esc($row->item_Name) ?></td>
            <td><?= esc($row->batch_no) ?></td>
            <td><?= esc($row->expiry) ?></td>
            <td style="text-align:right;"><?= $row->price ?></td>
            <td style="text-align:right;"><?= $row->qty ?>
                <input type="hidden" id="hid_oldqty_<?= $row->id ?>" value="<?= $row->qty ?>" />
            </td>
            <td>
                <?php if (!isset($row->sale_return) || $row->sale_return == 0) { ?>
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control input-sm number"
                           style="width:50px;height:20px;padding:1px;"
                           id="input_qty_<?= $row->id ?>"
                           value="<?= $row->qty ?>" min="1">
                    <span class="input-group-btn">
                        <button type="button" class="btn btn-info btn-flat btn-xs"
                                style="width:20px;height:20px;padding:1px;"
                                onclick="update_qty(<?= $row->id ?>)">
                            <i class="fa fa-edit"></i>
                        </button>
                    </span>
                </div>
                <?php } ?>
            </td>
            <td style="text-align:right;"><?= $row->amount ?></td>
            <td style="text-align:right;"><?= $row->tamount ?></td>
            <td>
                <button type="button" class="btn btn-danger btn-xs"
                        onclick="remove_item_invoice(<?= $row->id ?>)">
                    <i class="fa fa-remove"></i>
                </button>
            </td>
        </tr>
        <?php
    }
    ?>
    <input type="hidden" id="srno" name="srno" value="<?= $srno ?>" />
    <input type="hidden" id="wait_for_next" name="wait_for_next" value="0" />
    <!-- Totals -->
    <tr>
        <th>#</th><th></th><th></th><th></th><th></th><th></th>
        <th>Gross Total</th>
        <th style="text-align:right;"><?= $invoiceGtotal[0]->Gtotal ?? 0 ?></th>
        <th style="text-align:right;"><?= $invoiceGtotal[0]->tamt ?? 0 ?></th>
        <th></th>
    </tr>
    <tr>
        <th>#</th><th></th><th></th><th></th><th></th>
        <th>Tax Total</th>
        <th colspan="2">TCGST : <?= $invoiceGtotal[0]->TCGST ?? 0 ?> / TSGST : <?= $invoiceGtotal[0]->TSGST ?? 0 ?></th>
        <th></th><th></th>
    </tr>
</table>
