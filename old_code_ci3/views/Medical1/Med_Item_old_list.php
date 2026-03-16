<table class="table table-striped table-condensed table-hover" style="font-size:12px;">
    <tr>
        <th style="width: 10px">#</th>
        <th>Item Name</th>
        <th>Batch No</th>
        <th style="text-align:right;">Price</th>
        <th style="text-align:right;">S.Qty.</th>
        <th style="text-align:right;">Amt.</th>
        <th style="text-align:right;">Disc.</th>
        <th style="text-align:right;">TAmt.</th>
        <th style="text-align:right;">R.Qty.</th>
        <th></th>
    </tr>
    <?php
    $srno=0;
        foreach($med_inv_item_list as $row)
        { 
            $srno=$srno+1;
            echo '<tr>';
            echo '<td>'.$srno.'</td>';
            echo '<td>'.$row->item_Name.'</td>';
            echo '<td>'.$row->batch_no.'/'.$row->exp_date.'</td>';
            echo '<td style="text-align:right;">'.$row->price.'</td>';
            echo '<td style="text-align:right;">'.$row->qty.'</td>';
            echo '<td style="text-align:right;">'.$row->amount.'</td>';
            echo '<td style="text-align:right;">'.$row->t_discount.'</td>';
            echo '<td style="text-align:right;">'.$row->twdisc_amount.'</td>';
            echo '<td>';
            echo '<div class="input-group input-group-sm">
                        <input type="text" class="form-control input-sm number" 
                    style="width:50px;height:20px;padding:1px;" 
                    name="input_qty_'.$row->id.'" id="input_qty_'.$row->id.'" 
                    value="'.$row->qty.'" type="number" min="1" >
                            <span class="input-group-btn">
                            <button type="button" class="btn btn-danger btn-xs" 
                            style="height:20px;padding:1px;" 
                            id="btn_add_return" onclick="remove_item_add('.$row->id.')">
                            <i class="fa  fa-minus-circle"></i>
                            </button>
                            </span>
                    </div>';
            echo '</td>';
            echo '</tr>';
        }
    ?>
</table>
