    <div class="col-md-12">
		<div class="box-body" style="height:500px;overflow-y:auto;">
		  <table id="challan_list" class="table">
			<?php 
            $purchase_id='';
            foreach ($purchase_challan_item as $row) { 	
            
            if($purchase_id<>$row->id)
            {
                echo '<tr>';
                echo '  <th colspan=4>Challan No :'.$row->Invoice_no.' / Date : '.$row->str_date_of_invoice.' /Net Amount :'.$row->tamount.'</th>';
                echo '</tr>';

                echo '<tr style="color:red;">';
                echo ' <td>Item Name</td>';
                echo ' <td>Qty + Free</td>';
                echo ' <td>MRP</td>';
                echo ' <td>Add</td>';
                echo '</tr>';
            }

            echo '<tr>';
            echo ' <td>'.$row->Item_name.'</td>';
            echo ' <td>'.$row->qty.' + '.$row->qty_free.'</td>';
            echo ' <td>'.$row->mrp.'</td>';
            echo ' <td><a href="javascript:add_item_in_invoice('.$row->ss_no.')">Add</a></td>';
            echo '</tr>';

            $purchase_id=$row->id;

            }
            ?>
		  </table>
		</div>	
    </div>

 