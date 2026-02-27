<table class="table table-striped table-bordered table-hover" id="paymentmedical_history_log">
    <thead>
        <tr>
            <th>#</th>
            <th>Bill No</th>
            <th>Invoice Code</th>
            <th>Bill Type</th>
            <th>Insert Date Time</th>
            <th>Log Insert</th>
            <th>Update Log</th>
            <th>Amount / New Amount</th>
            <th>Log Type</th>
            <th>Update By</th>
        </tr>
    </thead>
    <tbody>
<?php
$i = 1;

foreach ($paymentmedical_history_log as $row) {
    ?>
    <tr>
        <td><?php echo $i; ?></td>
        <td><?php echo $row->id; ?></td>
        <td><?php echo $row->Inv_code; ?></td>
        <td><?php echo $row->Bill_type; ?></td>
        <td><?php echo $row->insert_time; ?></td>
        <td><?php echo $row->log_insert; ?></td>
        <td><?php echo $row->update_log; ?></td>
        <td><?php echo $row->amount; ?></td>
        <td><?php echo $row->LOG_type; ?></td>
        <td><?php echo $row->update_by; ?></td>
    </tr>
    <?php
    $i++;
}
?>
    </tbody>
</table>
<script type="text/javascript" language="javascript" >
    $(document).ready(function () {
        $('#paymentmedical_history_log').DataTable({
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