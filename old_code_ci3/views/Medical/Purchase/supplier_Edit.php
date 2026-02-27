

<div class="box">
				<div class="box-header">
				  <h3 class="box-title">Supplier</h3>
				</div>
				<div class="box-body">
				<?php echo form_open('Medical/SupplierUpdate', array('role'=>'form','class'=>'form1')); ?>
					<?php
						$sid=0;
						$name_supplier="";
						$short_name="";
						$contact_no="";
						$gst_no="";
						$city="";
						$state=M_State;
						$active=1;
						
						if(count($supplier_data)>0)
						{
							$sid=$supplier_data[0]->sid;
							$name_supplier=$supplier_data[0]->name_supplier;
							$short_name=$supplier_data[0]->short_name;
							$contact_no=$supplier_data[0]->contact_no;
							$gst_no=$supplier_data[0]->gst_no;
							$city=$supplier_data[0]->city;
							$state=$supplier_data[0]->state;
							$active=$supplier_data[0]->active;
						}
						
					?>
					<input type="hidden" id="hid_sid" name="hid_sid" value="<?=$sid?>" />
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label>Supplier Name</label>
								<input class="form-control" name="input_name_supplier" id="input_name_supplier" placeholder="Supplier Name" type="text" value="<?=$name_supplier ?>"  />
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label>Short Name</label>
								<input class="form-control" name="input_short_name" id="input_short_name" placeholder="Short Name" type="text" value="<?=$short_name ?>"  />
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label>GST No.</label>
								<input class="form-control" name="input_gst_no" id="input_gst_no" placeholder="GST No." type="text" value="<?=$gst_no ?>"  />
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label>Phone No.</label>
								<input class="form-control" name="input_contact_no" id="input_contact_no" placeholder="Phone No." type="text" value="<?=$contact_no ?>"  />
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label>City</label>
								<input class="form-control varchar" name="input_city" id="input_city" placeholder="City" type="text" value="<?=$city ?>"  />
							</div>
						</div>
						<div class="col-md-6">
							<label for="input_state" class="control-label">State</label>
							<div class="form-group">
								<select name="input_state" id="input_state" class="form-control">
									<?php 
									foreach($india_state as $row)
									{
										$selected = ($row->state_name == $state) ? ' selected="selected"' : "";

										echo '<option value="'.$row->state_name.'" '.$selected.'>'.$row->state_name.'</option>';
									} 
									?>
								</select>
							</div>
						</div>
					</div>
					<div class="">
						<?php 
							if($active==1)
							{
								$chk_active_checked="checked";
							}else{
								$chk_active_checked="";
							}
							
						?>
						<div class="col-md-6">
							<div class="form-group">
								<label><input id="chk_active" name="chk_active" type="checkbox"
										<?=$chk_active_checked?>>Active</label>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-2"> 
							<div class="form-group">
								<label> </label>
								<button type="button" class="btn btn-primary" id="btn_update" accesskey="A" ><u>A</u>dd Supplier & Update</button>
							</div>
						</div>
					</div>
				<?php echo form_close(); ?>
				</div>
		</div>
<script>
$(document).ready(function(){
	$('#btn_update').click(function(){
		$.post('/index.php/Medical/SupplierUpdate', $('form.form1').serialize(), function(data){
			if(data.insertid==0)
			{
				$('#msgshow').html(data.show_text);
				$("#alert_show").alert();
				$("#alert_show").fadeTo(2000, 500).slideUp(500, function(){
					$("#alert_show").slideUp(500);
					});
			}else
			{
				$('#msgshow').html(data.show_text);
				$('#hid_sid').val(data.insertid);
				$("#alert_show").alert();
				$("#alert_show").fadeTo(2000, 500).slideUp(500, function(){
					$("#alert_show").slideUp(500);
					});
				alert('Supplier Added : ID->'+ data.insertid);
				load_form_div('/Medical/SupplierListSub','supplier_list');
				load_form_div('/Medical/SupplierEdit/'+data.insertid,'test_div');
				
			}
		}, 'json');
	});
});
</script>