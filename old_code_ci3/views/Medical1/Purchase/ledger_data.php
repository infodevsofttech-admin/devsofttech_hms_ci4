<div class="row">
    <div class="col-md-12">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">Statement Details
                    <small>Opening Balance : <?=$balance_till_date?> / Total Cr. : <?=$cr_total?>  / Total Dr. : <?=$dr_total?>
                        / Closing Blance : <?=$balance_till_date_close?>
                    </small>
                </h3>
            	<div class="box-tools">
                    
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
                        <?php if($c->bank_id>1) { ?>
                        <button onclick="load_form_div('/Medical_backpanel/edit_entry/<?=$c->id ?>','maindiv');" type="button" class="btn btn-primary">Open</button>
                        <?php } ?>
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
