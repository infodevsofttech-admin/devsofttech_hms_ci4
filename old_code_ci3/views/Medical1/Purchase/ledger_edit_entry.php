<div class="row">
    <div class="col-md-12">
      	<div class="box box-warning">
            <div class="box-header with-border">
              	<h3 class="box-title">Edit Entry</h3>
              	<div class="box-tools">
              		
              	</div>
            </div>
            <?php 
            $attributes = array('id' => 'myform_add_entry');
            echo form_open('Medical_backpanel/edit_entry/'.$tran_id,$attributes); ?>
            <input type="hidden" name="tran_id" value="<?=$tran_id?>"  id="tran_id" />
          	<div class="box-body">
          		<div class="row clearfix">
				  	<div class="col-md-3">
						<label for="cr_type" class="control-label">Cr/Dr</label>
						<div class="form-group">
							<select name="cr_dr_type" id="cr_dr_type" class="form-control" >
								<option value="0" <?php echo ($med_supplier_ledger[0]->credit_debit == 0) ? ' selected="selected"' : "";?>>Credit</option>
								<option value="1" <?php echo ($med_supplier_ledger[0]->credit_debit == 1) ? ' selected="selected"' : "";?>>Debit</option>
							</select>
						</div>
					</div>
					<div class="col-md-3">
						<label for="cr_type" class="control-label">Mode</label>
						<div class="form-group">
							<select name="mode_type" id="mode_type" class="form-control" >
								<?php 
								foreach($bank_account_master as $row)
								{
                                    $bank_id = ($this->input->post('mode_type')?$this->input->post('mode_type'):$med_supplier_ledger[0]->bank_id);

                                    $selected = ($row->bank_id == $bank_id) ? ' selected="selected"' : "";
									echo '<option value="'.$row->bank_id.'" '.$selected.'>'.$row->bank_account_name.' '.$row->bank_name.'</option>';
								} 
								?>
							</select>
						</div>
					</div>
					<div class="col-md-3">
						<label for="tran_date" class="control-label">Date Of Tran.</label>
						<div class="input-group date input-sm">
							<div class="input-group-addon">
								<i class="fa fa-calendar"></i>
							</div>
							<input class="form-control pull-right datepicker input-sm" name="tran_date" id="tran_date" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask 
							value="<?php echo ($this->input->post('tran_date')?$this->input->post('tran_date'):Mysqldate_to_str($med_supplier_ledger[0]->tran_date));?>"  />
						</div>
					</div>
					<div class="col-md-3">
						<label for="amount" class="control-label">Amount</label>
						<div class="form-group">
							<input type="text" name="amount" value="<?php echo ($this->input->post('amount') ? $this->input->post('amount') : $med_supplier_ledger[0]->amount); ?>" class="form-control number" id="amount" required="true" min=1 />
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-6">
						<label for="tran_desc" class="control-label">Remark/Chq. No.</label>
						<div class="form-group">
							<textarea name="tran_desc" class="form-control" id="tran_desc"><?php echo ($this->input->post('tran_desc') ? $this->input->post('tran_desc') : $med_supplier_ledger[0]->tran_desc); ?></textarea>
						</div>
					</div>
				</div>
			</div>
          	<div class="box-footer">
            	<button type="submit" class="btn btn-success">
            		<i class="fa fa-check"></i> Save
            	</button>
          	</div>
            <?php echo form_close(); ?>
      	</div>
    </div>
</div>
<script>
	$(document).ready(function(){
        $('#myform_add_entry').on('submit', function(form){
            form.preventDefault();
            form_array=$('#myform_add_entry').serialize();
            $("#maindiv").html('Data Posting....Please Wait');
            $.post('/Medical_backpanel/edit_entry/<?=$tran_id?>', form_array, function(data){
                 $("#maindiv").html(data);
            });
        });
	});
</script>