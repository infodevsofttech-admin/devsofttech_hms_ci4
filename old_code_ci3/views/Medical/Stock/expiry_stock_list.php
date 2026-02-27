<section class="content-header">
<h3 class="box-title">Nearest Expiry / or Expired Medicine Stock</h3>
</section>
<section class="content">
<table id="table_old_data" class="table table-striped table-condensed table-hover" style="font-size:12px;">
    <?php
        $srno=0;
        $inv_id=0;
        echo '<thead>';
        echo '<tr>';
                echo '<th >#</th>';
                echo '<th >Item</th>';
                echo '<th>Supplier</th>';
                echo '<th >Pur.Date</th>';
                echo '<th >Bat.</th>';
                echo '<th >Exp.Dt</th>';
                echo '<th >Rate</th>';
                echo '<th >Qty</th>';
                echo '<th >C.Qty</th>';
                echo '</tr>';
        echo '</thead>';
        $srno=0;
        
        echo '<tbody>';
        foreach($purchase_list as $row)
        { 
            
            echo '<tr>';
            echo '<td>'.$srno.'</td>';
            echo '<td>'.$row->Item_name.'</td>';
            echo '<td>'.$row->name_supplier.'</td>';
            echo '<td >'.$row->str_date_of_invoice.'</td>';
            echo '<td>'.$row->batch_no.'</td>';
            echo '<td>'.$row->exp_date.'</td>';
            echo '<td style="text-align:right;">'.$row->mrp.'</td>';
            echo '<td style="text-align:right;">'.$row->tqty .'</td>';
            echo '<td style="text-align:right;">'.$row->cur_qty.'</td>';
            
            echo '</tr>';
            
            $inv_id=$row->pur_id;
        }
        echo '</tbody>';
    ?>
</table>
</section>
<script>
$(function () {
    $('#table_old_data').DataTable();
  })
  </script>
