<p class="text-danger">Last 5 Purchase Orders</p>
<table class="table table-striped ">
    <tr>
        <th style="width: 10px">#</th>
        <th>Item Name</th>
        <th>Date</th>
        <th>MRP</th>
        <th>Qty</th>
        <th>Rate</th>
        <th>Supplier</th>
    </tr>
    <?php
    $srno=0;
        foreach($purchase_item_old as $row)
        { 
            $srno=$srno+1;
            
            echo '<tr>';
            echo '<td>'.$srno.'</td>';
            echo '<td>'.$row->Item_name.'</td>';
            echo '<td>'.$row->date_of_invoice_str.'</td>';
            echo '<td>'.$row->mrp.'</td>';
            echo '<td>'.floatval($row->qty).'+'.floatval($row->qty_free).'</td>';
            echo '<td>'.$row->purchase_price.'</td>';
            echo '<td>'.$row->name_supplier.'</td>';
            echo '</tr>';
        }
    echo '<input type="hidden" id="srno" name="srno" value="'.$srno.'" />';
    ?>
    <!---- Total Show  ----->
</table>