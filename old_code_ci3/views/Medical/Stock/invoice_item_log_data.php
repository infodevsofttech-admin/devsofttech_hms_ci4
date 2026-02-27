<table class="table table-striped table-bordered table-hover" id="Invoice_history_log">
    <thead>
        <tr>
            <th>#</th>
            <th>Invoice Code</th>
            <th>Invoice Date</th>
            <th>Name</th>
            <th>Item Deleted</th>
            <th>Payment Log</th>
            <th>Update Log</th>
        </tr>
    </thead>
    <tbody>
<?php
$i = 1;

foreach ($Invoice_history_log as $row) {
    ?>
    <tr>
        <td><?php echo $i; ?></td>
        <td><?php echo $row->inv_med_code; ?></td>
        <td><?php echo $row->inv_date; ?></td>
        <td><?php echo $row->inv_name; ?></td>
        <td>
        <?php 
            $item_removed_log = explode(";",$row->del_item_list);
            //print_r($item_removed_log);
            if(count($item_removed_log)>0) {
                echo "<table style='padding:2px;margin:1px;border:1px solid #ccc;'>";
                echo "<tr>";
                echo "<th style='padding: 2px;margin: 1px;'>Item Name</th>";
                echo "<th style='padding: 2px;margin: 1px;'>Qty</th>";
                echo "<th style='padding: 2px;margin: 1px;'>Price</th>";
                echo "<th style='padding: 2px;margin: 1px;'>Amount</th>";
                echo "<th style='padding: 2px;margin: 1px;'>Delete By</th>";
                echo "</tr>";
                $item_amount_total = 0;
                foreach($item_removed_log as $row_item) {

                    $item = explode("#",$row_item);
                    
                    echo "<tr>";
                    echo "<td style='padding: 2px;margin: 1px;'>".$item[0]."</td>";
                    echo "<td style='padding: 2px;margin: 1px;text-align:right;'>".$item[1]."</td>";
                    echo "<td style='padding: 2px;margin: 1px;text-align:right;'>".$item[2]."</td>";
                    echo "<td style='padding: 2px;margin: 1px;text-align:right;'>".$item[3]."</td>";
                    echo "<td>".$item[5]."</td>";
                    echo "</tr>";
                    $item_amount_total += $item[3];
                }
                echo "<tr><td colspan='4' style='text-align:right;'>Total Amount : ".$item_amount_total."</td><td></td></tr>";
                echo "</table>";
            } else {
                echo "No Item Deleted";
            }

        ?>
        </td>
        <td>
            <?php
            $payment_log_list = explode(";",$row->payment_log);
            //print_r($item_removed_log);
            if(count($payment_log_list)>0) {
                echo "<table style='padding:2px;margin:1px;'>";
                echo "<tr>";
                echo "<th>Payment Type</th>";
                echo "<th>Amount</th>";
                echo "<th>Payment By</th>";
                echo "</tr>";
                foreach($payment_log_list as $row_item) {

                    $item = explode("#",$row_item);
                    
                    echo "<tr>";
                    echo "<td>";
                    if(isset($item[0])) {
                            if($item[0]==0) {
                                echo "Credit";
                            }else if($item[0]==1) {
                                echo "Return";
                            }
                        }
                    echo "</td>";

                    echo "<td>";
                    if(isset($item[1])) echo $item[1];
                    echo "</td>";

                    echo "<td>";
                    if(isset($item[2])) echo $item[2];
                    echo "</td>";

                    echo "</tr>";
                }
                
                echo "</table>";
            } else {
                echo "No Payment Log";
            }
            ?>
        </td>
        <td><pre><?php echo $row->log; ?></pre></td>
    </tr>
    <?php
    $i++;
}
?>
    </tbody>
</table>
<script type="text/javascript" language="javascript" >
    $(document).ready(function () {
        $('#Invoice_history_log').DataTable({
            responsive: true,
            "order": [[0, "desc"]],
            "bDestroy": true,
            "bPaginate": true,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false
        });
    });
</script>