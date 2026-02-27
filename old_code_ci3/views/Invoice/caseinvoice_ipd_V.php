<section class="content-header">
    <h1>Organisation IPD Invoice</h1>
</section>
<section class="content" id="org_content">
    <?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
    <div class="box box-danger">
        <div class="box-header">
            <div class="box-title">
                <p>
                    <strong>Name :</strong><?=$person_info[0]->p_fname?>
                    <strong>/ Age :</strong><?=$person_info[0]->age?>
                    <strong>/ Gender :</strong><?=$person_info[0]->xgender?>
                    <strong>/ P Code :</strong><?=$person_info[0]->p_code?>
                    <strong>/ Ins. Comp. :</strong><?=$insurance[0]->ins_company_name?>
                </p>
                <input type="hidden" id="pid" name="pid" value="<?=$person_info[0]->id?>" />
                <input type="hidden" id="caseid" name="caseid" value="<?=$orgcase[0]->id?>" />
                <input type="hidden" id="insurance_id" name="insurance_id" value="<?=$orgcase[0]->insurance_id?>" />
            </div>
        </div>
        <div class="box-body">
            <div class="row">
                <table class="table table-striped ">
                    <tr>
                        <th style="width: 10px">#</th>
                        <th>Date : InvID</th>
                        <th>Charges Type</th>
                        <th>Description</th>
                        <th>Qty</th>
                        <th>Rate</th>
                        <th>Amount</th>
                        <th>Org. Code</th>
                        <th>Action</th>
                        <th></th>
                    </tr>
                    <?php
			$srno=0;
			$Gamount=0;
			foreach($showinvoice1 as $row)
				{ 
					$srno=$srno+1;
					if($orgcase[0]->status<2)
					{
						echo '<tr>';
						echo '<td>'.$srno.'</td>';
						echo '<td>'.$row->str_date.' : '.$row->Code.'</td>';
						echo '<td>'.$row->Charge_type.'</td>';
						echo '<td>'.$row->Description.'</td>';
						echo '<td>'.$row->item_qty.'</td>';
						echo '<td>'.$row->item_rate.'</td>';
						echo '<td align="right">'.$row->Amount.'</td>';
						echo '<td>'.$row->orgcode.'</td>';
						echo '<td></td>';
						echo '</tr>';
					}else{
						echo '<tr>';
						echo '<td>'.$srno.'</td>';
						echo '<td>'.$row->str_date.' : '.$row->Code.'</td>';
						echo '<td>'.$row->Charge_type.'</td>';
						echo '<td>'.$row->Description.'</td>';
						echo '<td align="right">'.$row->item_qty.'</td>';
						echo '<td align="right">'.$row->item_rate.'</td>';
						echo '<td align="right">'.$row->Amount.'</td>';
						echo '<td align="right">'.$row->orgcode.'</td>';
						echo '<td></td>';
						echo '</tr>';
					}
					$Gamount=$Gamount+$row->Amount;
				}
				foreach($showinvoice2 as $row)
					{ 
						$srno=$srno+1;
						if($orgcase[0]->status<2)
						{
							echo '<tr>';
							echo '<td>'.$srno.'</td>';
							echo '<td>'.$row->str_date.' : '.$row->Code.'</td>';
							echo '<td>'.$row->Charge_type.'</td>';
							echo '<td>'.$row->Description.'</td>';
							echo '<td>'.$row->item_qty.'</td>';
							echo '<td><input class="form-control" style="width:100px" name="input_rate_'.$row->item_id.'" id="input_rate_'.$row->item_id.'"  value="'.$row->item_rate.'" type="number" /></td>';
							echo '<td align="right">'.$row->Amount.'</td>';
							echo '<td><input class="form-control" style="width:100px" name="input_orgcode_'.$row->item_id.'" id="input_orgcode_'.$row->item_id.'"  value="'.$row->orgcode.'" type="text" /></td>';
							echo '<td><button type="button" class="btn btn-primary" id="btn_update" onclick="update_rateqty('.$row->item_id.','.$row->Charge_type_id.','.$row->master_item_id.')">Update</button></td>';
							echo '</tr>';
						}else{
							echo '<tr>';
							echo '<td>'.$srno.'</td>';
							echo '<td>'.$row->str_date.' : '.$row->Code.'</td>';
							echo '<td>'.$row->Charge_type.'</td>';
							echo '<td>'.$row->Description.'</td>';
							echo '<td align="right">'.$row->item_qty.'</td>';
							echo '<td align="right">'.$row->item_rate.'</td>';
							echo '<td align="right">'.$row->Amount.'</td>';
							echo '<td align="right">'.$row->orgcode.'</td>';
							echo '<td></td>';
							echo '</tr>';
						}
						$Gamount=$Gamount+$row->Amount;
					}
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
                        <th align="right" style="text-align:right"><?=$Gamount?></th>
                        <th></th>
                    </tr>
                </table>
            </div>
           
        </div>
        <div class="box-footer">
            
        </div>
    </div>

    <?php echo form_close(); ?>
</section>
<script>
function update_rateqty(item_id, item_type_id, master_item_id) {
    var rate = $('#input_rate_' + item_id).val();
    var orgcode = $('#input_orgcode_' + item_id).val();
    var insurance_id = $('#insurance_id').val();
    var csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

    $.post('/index.php/Orgcase/update_itemrateqty', {
        "item_id": item_id,
        "item_type_id": item_type_id,
        "rate": rate,
        "orgcode": orgcode,
        "insurance_id": insurance_id,
        "master_item_id": master_item_id,
        "<?=$this->security->get_csrf_token_name()?>": csrf_value
    }, function(data) {
        load_form_div('/Orgcase/case_invoice_ipd/' + $('#caseid').val(),'tab_1');
    });
}


</script>