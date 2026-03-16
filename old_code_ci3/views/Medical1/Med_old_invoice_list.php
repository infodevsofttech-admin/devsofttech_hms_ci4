<table class="table table-striped table-condensed table-hover" style="font-size:12px;">
    <?php
        $srno=0;
        $inv_id=0;

        foreach($med_inv_item_list as $row)
        { 
            
            if($inv_id<>$row->inv_id)
            {
                $srno=0;

                echo '<tr>';
                echo '<td colspan="7"></td>';
                echo '</tr>';

                echo '<tr  style="background:orange;">';
                echo '<td>#</td>';
                echo '<td colspan="2">'.$row->inv_med_code.'</td>';
                echo '<td colspan="2">'.$row->inv_date.'</td>';
                echo '<td colspan="2" style="text-align:right;">'.$row->net_amount.'</td>';
                echo '</tr>';

                echo '<tr>';
                echo '<td >#</td>';
                echo '<td >Item</td>';
                echo '<td >Bat./Exp.Dt</td>';
                echo '<td >Rate</td>';
                echo '<td >Qty</td>';
                echo '<td >N.Amt.</td>';
                echo '<td >R.Qty</td>';
                echo '</tr>';
            }

            $srno=$srno+1;
            echo '<tr>';
            echo '<td>'.$srno.'</td>';
            echo '<td>'.$row->item_Name.'</td>';
            echo '<td>'.$row->batch_no.'<br/>'.$row->exp_date.'</td>';
            echo '<td style="text-align:right;">'.$row->price.'</td>';
            echo '<td style="text-align:right;">'.$row->qty.'</td>';
            echo '<td style="text-align:right;">'.$row->twdisc_amount.'</td>';
            if($row->sale_return==0){
            echo '<td>
                    <div class="input-group input-group-sm">
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
            }else{
                echo '<td></td>';
            }
            echo '</tr>';
           
            $inv_id=$row->inv_id;
        }
    ?>
</table>
