<section class="content-header">
    <h1>
        <?php if($orgcase[0]->case_type==0){ 
		  echo 'Organisation OPD Invoice';
	  }else{
		echo 'Organisation IPD Invoice';
	  }	  
	?>


        <small><a href="javascript:load_form('/Orgcase/case_invoice/<?=$orgcase[0]->case_id_code?>');"> Case ID:
                <?=$orgcase[0]->case_id_code?></a></small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="/Orgcase/case_invoice/<?=$orgcase[0]->case_id_code ?>/1/0/1" target=_blank><i
                    class="fa fa-dashboard"></i> Org. Invoice Print</a></li>
        <li><a href="/Orgcase/case_invoice/<?=$orgcase[0]->case_id_code ?>/1/0/0" target=_blank><i
                    class="fa fa-dashboard"></i> Org. Invoice Print W/o Medical</a></li>
        <li><a href="javascript:load_form_div('/Orgcase/contingent_bill/<?=$orgcase[0]->id ?>','org_content');"><i
                    class="fa fa-dashboard"></i> CONTINGENT BILL </a></li>
    </ol>
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
                            if($row->discount_amount>0){
                                $readonly = 'readonly';
                                $disabled = 'disabled';
                                $rate = $row->d_rate;
                                $discription = $row->Description.' (Discounted) Rate : '.$row->item_rate;
                            }else{
                                $readonly = '';
                                $disabled = '';
                                $rate = $row->item_rate;
                                $discription = $row->Description;
                            }

							echo '<tr>';
							echo '<td>'.$srno.'</td>';
							echo '<td>'.$row->str_date.' : '.$row->Code.'</td>';
							echo '<td>'.$row->Charge_type.'</td>';
							echo '<td>'.$discription.'</td>';
							echo '<td>'.$row->item_qty.'</td>';
                            
							echo '<td><input class="form-control" style="width:100px" name="input_rate_'.$row->item_id.'" id="input_rate_'.$row->item_id.'"  value="'.$rate.'" type="number" '.$readonly.' /></td>';
							echo '<td align="right">'.$row->Amount.'</td>';
							echo '<td><input class="form-control" style="width:100px" name="input_orgcode_'.$row->item_id.'" id="input_orgcode_'.$row->item_id.'"  value="'.$row->orgcode.'" type="text" '.$readonly.' /></td>';
							echo '<td><button type="button" class="btn btn-primary" id="btn_update" '.$disabled.' onclick="update_rateqty('.$row->item_id.','.$row->Charge_type_id.','.$row->master_item_id.')">Update</button></td>';
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
            <div class="row">
                <h3>Medical Bills</h3>
            </div>
            <div class="row">
                <table class="table table-striped ">
                    <tr>
                        <th style="width: 10px">#</th>
                        <th>Invoice ID.</th>
                        <th>Inv.Date</th>
                        <th>Amount</th>
                        <th></th>
                    </tr>
                    <?php
				$srno=1;
					foreach($MedInvoice_data as $row)
					{ 
						echo '<tr>';
						echo '<td>'.$srno.'</td>';
						echo '<td>'.$row->inv_med_code.'</td>';
						echo '<td>'.$row->inv_date.'</td>';
						echo '<td>'.$row->net_amount.'</td>';
						echo '<td><a href="Medical_Print/invoice_print_single_bill/'.$row->med_id.'" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print</a></td>';
						$srno=$srno+1;
						echo '</tr>';
					}
				echo '<input type="hidden" id="srno" name="srno" value="'.$srno.'" />';
				?>
                    <!---- Total Show  ----->
                    <tr>
                        <th style="width: 10px">#</th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                </table>
            </div>
        </div>
        <div class="box-footer">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        Current Status :
                        <?php 
						if($orgcase[0]->status==0)
						{
							echo '<b> Pending </b>';
						}
						if($orgcase[0]->status==1)
						{
							echo '<b> Ready For Submission </b>';
						}
						if($orgcase[0]->status==2)
						{
							echo '<b> Submitted in Org. </b>';
						}
						?>
                    </div>
                </div>
                <?php
					if($orgcase[0]->org_submit_date=='')
					{
						$Dateofsubmit=date('d/m/Y');
					}else{
						$Dateofsubmit=MysqlDate_to_str($orgcase[0]->org_submit_date);
					}
				?>
                <div class="col-md-3">
                    <?php
						if($orgcase[0]->status==0){ 
					?>
                    <label> Submit Date</label>
                    <div class="input-group date input-sm">
                        <div class="input-group-addon">
                            <i class="fa fa-calendar"></i>
                        </div>
                        <input class="form-control pull-right datepicker input-sm" name="datepicker_submit"
                            id="datepicker_submit" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask
                            value="<?=$Dateofsubmit ?>" />
                    </div>
                    <?php
						}else{
							echo '<input type="hidden" name="datepicker_submit" id="datepicker_submit" value="'.$Dateofsubmit.'">';
						}
					?>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <input type="hidden" id="case_status_id" name="case_status_id"
                            value="<?=$orgcase[0]->status?>" />
                        <?php
							if($orgcase[0]->status==0)
							{
								echo '<input type="hidden" id="case_U_status_id" name="case_U_status_id" value="1" />';
								echo '<button type="button" class="btn btn-primary" id="btn_case_1">Case Process For Submit</button>';
							}else if($orgcase[0]->status==1){
								echo '<input type="hidden" id="case_U_status_id" name="case_U_status_id" value="2" />';
								echo '<button type="button" class="btn btn-primary" id="btn_case_1">Case Submitted To Org.</button>';
							}else{
								echo '<a data-toggle="modal" data-target="#payModal" data-caseid="'.$orgcase[0]->case_id_code.'" href="#" >Org. Payment</a>';
							}
						?>
                    </div>
                </div>
            </div>
            <?php if($orgcase[0]->case_type==0){  ?>
            <hr>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Create Payment Request</label>
                        <input type="number" class="form-control input-sm number" name="input_pay_amount"
                            id="input_pay_amount" placeholder="Amount" autocomplete="off">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Remark</label>
                        <input type="text" class="form-control input-sm varchar" name="input_remark" id="input_remark"
                            placeholder="Remark" autocomplete="on">
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-primary" id="btn_create_payment">Create Payment
                        Request</button>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Create Refund Request</label>
                        <input type="number" class="form-control input-sm number" name="input_refund_amount"
                            id="input_refund_amount" placeholder="Amount" autocomplete="off">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Remark</label>
                        <input type="text" class="form-control input-sm varchar" name="input_refund_remark"
                            id="input_refund_remark" placeholder="Remark" autocomplete="on">
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-warning" id="btn_create_refund">Create Refund Request</button>
                </div>
            </div>
            <?php }  ?>
            <div class="row">
				<div class="col-md-6">
                <?php if(count($payment_history)>0){
				echo "<h3>Payment History</h3>";
				echo "<table class='table'>
					<tr>
						<th>Pay ID</th>
						<th>Date</th>
						<th>Amount</th>
						<th>Mode</th>
						<th>Update By</th>
					</tr>";
						
				foreach($payment_history as $row){
					echo "<tr>";
					echo "<td>".$row->id."</td>";
					echo "<td>".$row->str_date."</td>";
					echo "<td>".$row->Pay_mode."</td>";
					echo "<td>".$row->amount."</td>";
					echo "<td>".$row->update_by."</td>";
					echo "</tr>";
				}
				echo "</table>";
			}
			?>
				</div>
            </div>
        </div>
    </div>

    <?php echo form_close(); ?>
</section>
<div class="modal fade" id="payModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="payModalLabel">Payment</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="payModal-bodyc" id="payModal-bodyc">

                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
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
        load_form('/Orgcase/case_invoice/0/0/' + $('#caseid').val());
    });
}

$(document).ready(function() {

    var csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

    $('#btn_case_1').click(function() {
        if (confirm("Are you sure?  ... All document Complete ?.......")) {
            $.post('/index.php/Orgcase/update_status', {
                "case_status_id": $('#case_status_id').val(),
                "caseid": $('#caseid').val(),
                "status": $('#case_U_status_id').val(),
                "org_submit_date": $('#datepicker_submit').val(),
                "<?=$this->security->get_csrf_token_name()?>": csrf_value
            }, function(data) {
                load_form('/Orgcase/case_invoice/0/0/' + $('#caseid').val());
            });
        }
    });

    $('#btn_create_payment').click(function() {
        if (confirm("Are you sure?  ... Create Payment Request ?.......")) {
            $.post('/index.php/Orgcase/payment_request', {
                "input_pay_amount": $('#input_pay_amount').val(),
                "caseid": $('#caseid').val(),
                "input_remark": $('#input_remark').val(),
                "<?=$this->security->get_csrf_token_name()?>": csrf_value
            }, function(data) {
                alert(data);
            });
        }
    });

    $('#btn_create_refund').click(function() {
        if (confirm("Are you sure?  ... Create Refund Request ?.......")) {
            $.post('/index.php/Orgcase/refund_request', {
                "input_refund_amount": $('#input_refund_amount').val(),
                "caseid": $('#caseid').val(),
                "input_refund_remark": $('#input_refund_remark').val(),
                "<?=$this->security->get_csrf_token_name()?>": csrf_value
            }, function(data) {
                alert(data);
            });
        }
    });

    $('#payModal').on('shown.bs.modal', function(event) {

        var button = $(event.relatedTarget); // Button that triggered the modal
        var invid = button.data('caseid');

        load_form_div('/Orgcase/load_model_box/' + invid, 'payModal-bodyc');
    })


});
</script>