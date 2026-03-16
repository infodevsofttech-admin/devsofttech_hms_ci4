<div class="row">
    <div class="col-md-12">
      	<div class="box box-warning">
            <div class="box-header with-border">
              	<h3 class="box-title">Add Entry</h3>
              	<div class="box-tools">
              		
              	</div>
            </div>
            <?php 
            $attributes = array('id' => 'myform_add_entry');
            echo form_open('Medical_backpanel/add_entry/'.$s_id,$attributes); ?>
            <input type="hidden" name="s_id" value="<?=$s_id?>"  id="s_id" />
          	<div class="box-body">
          		<div class="row clearfix">
				  	<div class="col-md-3">
						<label for="cr_type" class="control-label">Cr/Dr</label>
						<div class="form-group">
							<select name="cr_dr_type" id="cr_dr_type" class="form-control" >
								<option value="0">Credit</option>
								<option value="1">Debit</option>
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
									$selected = ($row->bank_id == $this->input->post('cr_type')) ? ' selected="selected"' : "";
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
							value="<?php echo ($this->input->post('tran_date'))?$this->input->post('tran_date'):date('d/m/Y');   ?>"  />
						</div>
					</div>
					<div class="col-md-3">
						<label for="amount" class="control-label">Amount</label>
						<div class="form-group">
							<input type="text" name="amount" value="<?php echo $this->input->post('amount'); ?>" class="form-control number" id="amount" required="true" min=1 />
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-6">
						<label for="remark" class="control-label">Remark/Chq. No.</label>
						<div class="form-group">
							<textarea name="tran_desc" class="form-control" id="tran_desc"><?php echo $this->input->post('tran_desc'); ?></textarea>
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
            $.post('/Medical_backpanel/add_entry/<?=$s_id?>', form_array, function(data){
                 $("#maindiv").html(data);
            });
        });
	});
</script>