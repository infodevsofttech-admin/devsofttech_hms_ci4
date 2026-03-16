<table class="table">
    <tr>
        <td>Item Name</td>
        <td>Batch No.</td>
        <td>Qty in Stock</td>
        <td>MRP</td>
        <td>Qty</td>
    </tr>
<?php foreach ($Item_list as $row){  ?>
	<tr>
        <td><?=$row['item_name']?></td>
        <td><?=$row['batch_no']?></td>
        <td><?=$row['c_qty']?></td>
        <td><?=$row['mrp']."/".$row['selling_unit_rate']?></td>
        <td>
           <div class="input-group input-group-sm">
                <input type="text" class="form-control number" 
                    style="width:50px;height:25px;padding:1px;" 
                    name="input_add_qty_<?=$row['id']?>" id="input_add_qty_<?=$row['id']?>" 
                    value="0" type="number" min="1" >
            </div>
        </td>
        <td><button type="button" class="btn btn-info btn-flat " 
                    style="width:20px;height:20px;padding:1px;" onclick="add_item_from_old_list(<?=$row['id']?>)">
                    <i class="fa fa-plus"></i>
                    </button>
        </td>
    </tr>
</table>
<?php }  ?>