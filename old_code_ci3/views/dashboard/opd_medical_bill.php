<div class="col-md-6">
<?php 
    // External Loop
    foreach($invoice_med_master as $row_master){
        $sql="SELECT t.item_Name,t.formulation,t.qty
			from inv_med_item t 
			WHERE t.inv_med_id=$row_master->id
			ORDER BY t.id ";
		$query = $this->db->query($sql);
        $medical_store_item= $query->result();
?>
        <div class="box box-primary">
            <div class="box-header with-border">
                    <h3 class="box-title"><?=$row_master->inv_med_code?> Date : <?=$row_master->inv_date?></h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <table class="table table-striped table-condensed table-hover" >
                <?php foreach($medical_store_item as $row_item) { ?>
                    <tr>
                        <td>
                            <?=$row_item->item_Name?>
                        </td>
                        <td>
                            <?=$row_item->formulation?>
                        </td>
                        <td>
                            <?=$row_item->qty?>
                        </td>
                    </tr>
                <?php } ?>
                </table>
            </div>
            <!-- /.box-body -->
        </div>
<?php
    //External Loop  
    }
?>
</div>