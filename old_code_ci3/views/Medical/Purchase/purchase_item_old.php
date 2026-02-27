<?php
    $item_name='';
    if(count($purchase_item_old)>0){
        $item_name=$purchase_item_old[0]->Item_name;
    }
?>
<p class="text-danger">Item : <?=$item_name?> Last 5 Purchase Orders</p>

<table class="table table-striped ">
    <tr>
        <th>Date</th>
        <th>MRP</th>
        <th>Qty</th>
        <th>Rate</th>
        <th>SCH/Disc</th>
        <th>P.U.Rate</th>
        <th>Supplier/Invoice</th>
    </tr>
    <?php
    $srno=0;
       foreach($purchase_item_old as $row)
        { 
            $srno=$srno+1;
            echo '<tr>';
            echo '<td>'.$row->date_of_invoice_str.'</td>';
            echo '<td>'.$row->mrp.'</td>';
            echo '<td>'.floatval($row->qty).'+'.floatval($row->qty_free).'</td>';
            echo '<td>'.$row->purchase_price.'</td>';
            echo '<td>'.$row->sch_disc_per.' / '.$row->discount.'</td>';
            echo '<td>'.$row->purchase_unit_rate.'</td>';
            echo '<td>'.$row->name_supplier.'/'.$row->Invoice_no.'</td>';
            echo '</tr>';
        }
    echo '<input type="hidden" id="srno" name="srno" value="'.$srno.'" />';
    ?>
    <!---- Total Show  ----->
</table>