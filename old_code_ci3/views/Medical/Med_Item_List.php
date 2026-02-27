<?php
    $Item_List=array();
    foreach($inv_items_multiple as $row)
    {
        array_push($Item_List,$row->item_code);
    }

   
?>
<table class="table table-striped table-condensed table-hover" >
    <tr>
        <th style="width: 10px">#</th>
        <th>Item Name</th>
        <th>Batch No</th>
        <th>Exp.</th>
        <th style="text-align:right;">Rate</th>
        <th style="text-align:right;">S.Qty.</th>
        <th>Qty.</th>
        <th style="text-align:right;">Price</th>
        <th style="text-align:right;">Disc.</th>
        <th style="text-align:right;">Amount</th>
        <th></th>
    </tr>
    <?php
    $srno=0;
        foreach($inv_items as $row)
        { 
            $srno=$srno+1;
            if($row->sale_return==1){
                $style='style="background:orange;"';
               
            }else{
                $style="";
            }

            if($row->no_day<90){
               $style='style="color:red;"';
            }

            $style_repeat='';

            if(in_array($row->item_code,$Item_List)){
                $style_repeat='style="color:green;"';
            }

            if($row->cur_qty<6){
                $style='style="color:red;"';
            }

            echo '<tr '.$style.' >';

            echo '<td>'.$srno.'</td>';
            echo '<td '.$style_repeat.' >'.$row->item_Name.'</td>';
            echo '<td>'.$row->batch_no.'</td>';
            echo '<td>'.$row->expiry.'</td>';
            echo '<td style="text-align:right;">'.$row->price.'</td>';
            echo '<td style="text-align:right;">'.$row->qty.'<input type="hidden" id="hid_oldqty_'.$row->id.'" name="srno" value="'.$row->qty.'" /></td>';
            echo '<td>';
            if($row->sale_return==0){
            echo '<div class="input-group input-group-sm">
                        <input type="text" class="form-control input-sm number" 
                    style="width:50px;height:20px;padding:1px;" 
                    name="input_qty_'.$row->id.'" id="input_qty_'.$row->id.'" 
                    value="'.$row->qty.'" type="number" min="1" >
                            <span class="input-group-btn">
                            <button type="button" class="btn btn-info btn-flat btn-xs" 
                            style="width:20px;height:20px;padding:1px;" onclick="update_qty('.$row->id.')">
                                <i class="fa fa-edit"></i>
                            </button>
                            </span>
                    </div>';
            }
            echo '</td>';
            echo '<td style="text-align:right;">'.$row->amount.'</td>';
            echo '<td style="text-align:right;">'.$row->disc_amount.'</td>';
            echo '<td style="text-align:right;">'.$row->tamount.'</td>';
            
            echo '<td>';
            echo '<button type="button" class="btn btn-danger btn-xs" id="btn_add_fee" onclick="remove_item_invoice('.$row->id.')"><i class="fa fa-remove"></i></button> ';
            echo '</td>';
            echo '</tr>';
        }
    echo '<input type="hidden" id="srno" name="srno" value="'.$srno.'" />';
    echo '<input type="hidden" id="wait_for_next" name="wait_for_next" value="0" />';
    ?>
    <!---- Total Show  ----->
    <tr>
        <th style="width: 10px">#</th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th>Gross Total</th>
        <th><?=$invoiceGtotal[0]->Gtotal ?></th>
        <th><?=$invoiceGtotal[0]->t_dis_amt?></th>
        <th><?=$invoiceGtotal[0]->tamt?></th>
        <th></th>
    </tr>
    <tr>
        <th style="width: 10px">#</th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th>Tax Total</th>
        <th colspan=2>TCGST : <?=$invoiceGtotal[0]->TCGST?> / TSGST :<?=$invoiceGtotal[0]->TSGST?></th>
        <th></th>
        <th></th>
    </tr>
</table>