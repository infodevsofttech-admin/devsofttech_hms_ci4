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
    </tr>
    <?php
        foreach($product_purchase_detail as $row)
        {
    ?>
    <tr>
       <td><?=$row->name_supplier?></td>
       <td><?=$row->Invoice_no?></td>
       <td><?=$row->batch_no?></td>
       <td><?=$row->expiry_date?></td>
       <td><?=$row->mrp?></td>
       <td><?=$row->purchase_price?></td>
       <td><?=$row->tqty?></td>
       <td><?=$row->total_unit?></td>
    </tr>
    <?php
        }

    ?>
</table>