<table id="table_old_data" class="table table-striped table-condensed table-hover" style="font-size:12px;">
    <?php
        $srno=0;
        $inv_id=0;
        echo '<thead>';
        echo '<tr>';
                echo '<th >#</th>';
                echo '<th >Pur.Date</th>';
                echo '<th >Item</th>';
                echo '<th >Bat./Exp.Dt</th>';
                echo '<th >Rate</th>';
                echo '<th >Pur. Qty </th>';
                echo '<th >C.Unit Qty</th>';
                echo '<th >R.Unit Qty</th>';
                echo '</tr>';
        echo '</thead>';
        $srno=0;
        
        echo '<tbody>';
        foreach($purchase_list as $row)
        { 
            
            $srno=$srno+1;
            if($row->isExp==1)
            {
                echo '<tr style="color:red;">';
            }else{
                echo '<tr>';
            }
            
            echo '<td>'.$srno.'</td>';
            echo '<td >'.$row->str_date_of_invoice.'</td>';
            echo '<td>'.$row->Item_name.'</td>';
            echo '<td>'.$row->batch_no.'<br/>'.$row->exp_date.'</td>';
            echo '<td style="text-align:right;">'.$row->mrp.'</td>';
            echo '<td style="text-align:right;">'.$row->tqty .' /'.$row->packing .'</td>';
            echo '<td style="text-align:right;">'.$row->cur_qty.'</td>';
            if($row->item_return==0 && $row->remove_item==0){
                echo '<td>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control input-sm number" 
                        style="width:50px;height:20px;padding:1px;" 
                        name="input_qty_'.$row->id.'" id="input_qty_'.$row->id.'" 
                        value="'.$row->cur_qty.'" type="number" min="1" >
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
            
            $inv_id=$row->pur_id;
        }
        echo '</tbody>';
    ?>
</table>
<script>
$(function () {
    $('#table_old_data').DataTable();
  })
  </script>
