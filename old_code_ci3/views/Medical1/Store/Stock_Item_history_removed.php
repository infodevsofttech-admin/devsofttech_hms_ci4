<table class="table table-condence">
    <tr>
        <th>Supplier</th>
        <th>Inv.No / Date</th>
        <th>Bat.No.</th>
        <th>Exp.Dt.</th>
        <th>MRP</th>
        <th>Purchase</th>
        <th>Qty</th>
        <th>Unit Qty</th>
        <th>Sale U.Qty</th>
        <th>Lost Unit</th>
        <th>Curr.U.Qty</th>
        <th>Update Lost Unit</th>
        <th>Remove</th>
    </tr>
    <?php
        foreach($product_purchase_detail as $row)
        {
    ?>
    <tr>
        <td><?=$row->name_supplier?></td>
        <td><?=$row->Invoice_no?> <br /> <?=$row->date_of_invoice?></td>
        <td><?=$row->batch_no?></td>
        <td><?=$row->expiry_date?></td>
        <td><?=$row->mrp?></td>
        <td><?=$row->purchase_price?></td>
        <td><?=$row->tqty?></td>
        <td><?=$row->total_unit?></td>
        <td><?=$row->total_sale_unit?></td>
        <td><?=$row->total_lost_unit?></td>
        <td><?=$row->cur_unit?></td>
        <td>
            <div class="input-group input-group-sm">
                <input type="text" class="form-control input-sm number" style="width:50px;<?=$row->id?>"
                    id="input_qty_update_<?=$row->id?>" value="0" type="number" min="1">
                <span class="input-group-btn">
                    <button type="button" class="btn btn-info btn-flat btn-xs"
                        style="width:20px;height:20px;padding:1px;" onclick="update_sale_stock(<?=$row->id?>)">
                        <i class="fa fa-edit"></i>
                    </button>
                </span>
            </div>
        </td>
        <td align="center">
        <a class="btn" href="javascript:add_sale_stock(<?=$row->id?>);"><i class="fa  fa-plus"
                    style="color:blue;"></i></a>
        </td>
    </tr>
    <?php
        }

    ?>
</table>