<div class="row">
    <div class="col-md-12">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">Statement Details
                    <small>Opening Balance : <?=$balance_till_date?> / Total Cr. : <?=$cr_total?>  / Total Dr. : <?=$dr_total?>
                        / Closing Balance : <?=$balance_till_date_close?>
                    </small>
                </h3>
            	<div class="box-tools">
                    <a href="/Medical_backpanel/search_result_tran_print/<?=$s_id?>/<?=$date_range?>" target="_blank" class="btn btn-success btn-xs">Print Statement</a>
                </div>
            </div>
            <div class="box-body">
                <table id="search_list_data" class="table table-striped">
                <thead>
                    <tr>
						<th>ID</th>
						<th>Date Tran</th>
                        <th>Description</th>
						<th>Credit</th>
                        <th>Debit</th>
						<th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i=0;
                    foreach($med_supplier_ledger as $c){ 
                        $i+=1;
                    ?>
                    <tr>
						<td><?php echo $i; ?></td>
						<td><?php echo MysqlDate_to_str($c->tran_date); ?></td>
                        <td><?php echo $c->mode_desc.' '.$c->tran_desc; ?></td>
						<td><?php echo ($c->credit_debit==0?$c->amount:''); ?></td>
                        <td><?php echo ($c->credit_debit==1?$c->amount:''); ?></td>
						<td>
                        <button onclick="load_form_div('/Medical_backpanel/edit_entry/<?=$c->id ?>','search_result');" type="button" class="btn btn-primary">Open</button>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
                <tfoot>
                    <tr>
						<th>ID</th>
						<th>Date Tran</th>
                        <th>Description</th>
						<th>Credit</th>
                        <th>Debit</th>
						<th>Actions</th>
                    </tr>
                </tfoot>
                </table>
                <script>
                    $('#search_list_data').dataTable();
                </script>
            </div>
        </div>
    </div>
</div>
