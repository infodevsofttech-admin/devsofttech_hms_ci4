<style>
    .orgcase-hero {
        background: linear-gradient(120deg, #f7f4f1 0%, #eaf3f9 100%);
        border: 1px solid #e4ecf4;
        border-radius: 12px;
        padding: 16px 18px;
        margin-bottom: 16px;
        color: #0f172a;
    }
    .orgcase-hero-top {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .orgcase-hero h1 {
        font-family: "Poppins", "Nunito", sans-serif;
        font-size: 32px;
        margin-bottom: 6px;
        color: #0f172a;
    }
    .orgcase-hero .case-id {
        font-weight: 600;
        color: #1d4ed8;
        text-decoration: none;
    }
    .orgcase-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-end;
    }
    .orgcase-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 12px 20px;
        font-size: 16px;
        color: #0f172a;
    }
    .orgcase-meta strong {
        color: #111827;
    }
    .orgcase-card {
        border-radius: 12px;
        border: 1px solid #e8edf3;
        overflow: hidden;
    }
    .badge-soft {
        background: #eef4ff;
        color: #1d4ed8;
        border: 1px solid #c7d7ff;
        padding: 2px 8px;
        border-radius: 999px;
        font-weight: 600;
        font-size: 12px;
    }
    .section-title {
        font-family: "Poppins", "Nunito", sans-serif;
        font-size: 18px;
        margin: 0;
    }
</style>

<section class="content-header">
    <div class="orgcase-hero">
        <div class="orgcase-hero-top">
            <h1>
                <?php if($orgcase[0]->case_type==0){ 
		          echo 'Organisation OPD Invoice';
	          }else{
		        echo 'Organisation IPD Invoice';
	          }	  
	        ?>
                <span class="ms-2">Case ID:</span>
                <a class="case-id" href="javascript:load_form('<?= base_url('Orgcase/case_invoice') ?>/<?=$orgcase[0]->case_id_code?>');">
                    <?=$orgcase[0]->case_id_code?>
                </a>
            </h1>
            <div class="orgcase-actions">
                <a class="btn btn-outline-primary btn-sm" href="<?= base_url('Orgcase/case_invoice') ?>/<?=$orgcase[0]->case_id_code ?>/1/0/1" target="_blank">
                    Org. Invoice Print
                </a>
                <a class="btn btn-outline-secondary btn-sm" href="<?= base_url('Orgcase/case_invoice') ?>/<?=$orgcase[0]->case_id_code ?>/1/0/0" target="_blank">
                    Org. Invoice Print W/o Medical
                </a>
                <a class="btn btn-outline-dark btn-sm" href="javascript:load_form_div('<?= base_url('Orgcase/contingent_bill') ?>/<?=$orgcase[0]->id ?>','org_content');">
                    Contingent Bill
                </a>
            </div>
        </div>
    </div>
</section>
<section class="content" id="org_content">
    <form role="form" class="form1">
        <?= csrf_field() ?>
    <div class="card orgcase-card">
        <div class="card-header bg-white">
            <div class="orgcase-meta">
                <div><strong>Name:</strong> <?=$person_info[0]->p_fname?></div>
                <div><strong>Age:</strong> <?=$person_info[0]->age?></div>
                <div><strong>Gender:</strong> <?=$person_info[0]->xgender?></div>
                <div><strong>P Code:</strong> <?=$person_info[0]->p_code?></div>
                <div><strong>Ins. Comp.:</strong> <?=$insurance[0]->ins_company_name?></div>
                <div><span class="badge-soft">Case Type: <?=$orgcase[0]->case_type==0 ? 'OPD' : 'IPD'?></span></div>
            </div>
            <input type="hidden" id="pid" name="pid" value="<?=$person_info[0]->id?>" />
            <input type="hidden" id="caseid" name="caseid" value="<?=$orgcase[0]->id?>" />
            <input type="hidden" id="insurance_id" name="insurance_id" value="<?=$orgcase[0]->insurance_id?>" />
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
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
                    </thead>
                    <tbody>
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
                            if(($row->discount_amount ?? 0) > 0){
                                $readonly = 'readonly';
                                $disabled = 'disabled';
                                $rate = $row->d_rate ?? $row->item_rate;
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
                    </tbody>
                    <tfoot>
                        <tr>
                            <th style="width: 10px">#</th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th class="text-end">Gross Total</th>
                            <th class="text-end"><?=$Gamount?></th>
                            <th></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="d-flex align-items-center justify-content-between mt-4 mb-2">
                <h3 class="section-title">Medical Bills</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th style="width: 10px">#</th>
                        <th>Invoice ID.</th>
                        <th>Inv.Date</th>
                        <th>Amount</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
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
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-body border-top">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        Current Status :
                        <?php 
						if($orgcase[0]->status==0)
						{
							echo '<span class="badge bg-warning text-dark">Pending</span>';
						}
						if($orgcase[0]->status==1)
						{
							echo '<span class="badge bg-info text-dark">Ready For Submission</span>';
						}
						if($orgcase[0]->status==2)
						{
							echo '<span class="badge bg-success">Submitted in Org.</span>';
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
								echo '<button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#payModal" data-caseid="'.$orgcase[0]->case_id_code.'">Org. Payment</button>';
							}
						?>
                    </div>
                </div>
            </div>
            <?php if($orgcase[0]->case_type==0){  ?>
            <hr>
            <div class="row g-3">
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
                <div class="col-md-4 d-flex align-items-end">
                    <button type="button" class="btn btn-primary w-100" id="btn_create_payment">Create Payment
                        Request</button>
                </div>
            </div>
            <hr>
            <div class="row g-3">
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
                <div class="col-md-4 d-flex align-items-end">
                    <button type="button" class="btn btn-warning w-100" id="btn_create_refund">Create Refund Request</button>
                </div>
            </div>
            <?php }  ?>
            <div class="row">
                <div class="col-md-8">
                <?php if(count($payment_history)>0){
                echo "<h3 class='section-title'>Payment History</h3>";
                echo "<div class='table-responsive'><table class='table table-sm table-striped align-middle'>
                    <thead class='table-light'>
                        <tr>
                            <th>Pay ID</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Mode</th>
                            <th>Update By</th>
                        </tr>
                    </thead>";
						
                foreach($payment_history as $row){
                    echo "<tr>";
                    echo "<td>".$row->id."</td>";
                    echo "<td>".$row->str_date."</td>";
                    echo "<td>".$row->Pay_mode."</td>";
                    echo "<td>".$row->amount."</td>";
                    echo "<td>".$row->update_by."</td>";
                    echo "</tr>";
                }
                echo "</table></div>";
            }
            ?>
                </div>
            </div>
        </div>
    </div>

    </form>
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
    var csrf_name = '<?= csrf_token() ?>';
    var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';

    $.post('<?= base_url('Orgcase/update_itemrateqty') ?>', {
        "item_id": item_id,
        "item_type_id": item_type_id,
        "rate": rate,
        "orgcode": orgcode,
        "insurance_id": insurance_id,
        "master_item_id": master_item_id,
        [csrf_name]: csrf_value
    }, function(data) {
        load_form('<?= base_url('Orgcase/case_invoice') ?>/0/0/' + $('#caseid').val() + '/1');
    });
}

$(document).ready(function() {

    var csrf_name = '<?= csrf_token() ?>';
    var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';

    $('#btn_case_1').click(function() {
        if (confirm("Are you sure?  ... All document Complete ?.......")) {
            $.post('<?= base_url('Orgcase/update_status') ?>', {
                "case_status_id": $('#case_status_id').val(),
                "caseid": $('#caseid').val(),
                "status": $('#case_U_status_id').val(),
                "org_submit_date": $('#datepicker_submit').val(),
                [csrf_name]: csrf_value
            }, function(data) {
                load_form('<?= base_url('Orgcase/case_invoice') ?>/0/0/' + $('#caseid').val() + '/1');
            });
        }
    });

    $('#btn_create_payment').click(function() {
        if (confirm("Are you sure?  ... Create Payment Request ?.......")) {
            $.post('<?= base_url('Orgcase/payment_request') ?>', {
                "input_pay_amount": $('#input_pay_amount').val(),
                "caseid": $('#caseid').val(),
                "input_remark": $('#input_remark').val(),
                [csrf_name]: csrf_value
            }, function(data) {
                alert(data);
            });
        }
    });

    $('#btn_create_refund').click(function() {
        if (confirm("Are you sure?  ... Create Refund Request ?.......")) {
            $.post('<?= base_url('Orgcase/refund_request') ?>', {
                "input_refund_amount": $('#input_refund_amount').val(),
                "caseid": $('#caseid').val(),
                "input_refund_remark": $('#input_refund_remark').val(),
                [csrf_name]: csrf_value
            }, function(data) {
                alert(data);
            });
        }
    });

    $('#payModal').on('shown.bs.modal', function(event) {

        var button = $(event.relatedTarget); // Button that triggered the modal
        var invid = button.data('caseid');

        load_form_div('<?= base_url('Orgcase/load_model_box') ?>/' + invid, 'payModal-bodyc');
    })


});
</script>