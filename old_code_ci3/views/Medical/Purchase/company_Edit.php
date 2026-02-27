<div class="box">
				<div class="box-header">
				  <h3 class="box-title">Supplier</h3>
				</div>
				<div class="box-body">
				<?php echo form_open('Product_master/CompanyUpdate', array('role'=>'form','class'=>'form1')); ?>
					<?php
						$cid=0;
						$company_name="";
						$contact_person_name="";
						$contact_phone_no="";
						
						if(count($med_company)>0)
						{
							$cid=$med_company[0]->id;
							$company_name=$med_company[0]->company_name;
							$contact_person_name=$med_company[0]->contact_person_name;
							$contact_phone_no=$med_company[0]->contact_phone_no;
						}
						
					?>
					<input type="hidden" id="hid_cid" name="hid_cid" value="<?=$cid?>" />
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label>Company Name</label>
								<input class="form-control" name="input_company_name" id="input_company_name" placeholder="Supplier Name" type="text" value="<?=$company_name ?>"  />
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label>Contact Person Name</label>
								<input class="form-control" name="input_contact_person_name" id="input_contact_person_name" placeholder="Short Name" type="text" value="<?=$contact_person_name ?>"  />
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label>Contact Phone No</label>
								<input class="form-control" name="input_contact_phone_no" id="input_contact_phone_no" placeholder="Phone No" type="text" value="<?=$contact_phone_no ?>"  />
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-2"> 
							<div class="form-group">
								<label> </label>
								<button type="button" class="btn btn-primary" id="btn_update" accesskey="A" ><u>A</u>dd & Update Company</button>
							</div>
						</div>
					</div>
				<?php echo form_close(); ?>
				</div>
		</div>
<script>
$(document).ready(function(){
	$('#btn_update').click(function(){
		$.post('/index.php/Product_master/CompanyUpdate', $('form.form1').serialize(), function(data){
			if(data.insertid==0)
			{
				notify('error','Please Attention',data.show_text);
			}else
			{
				$('#hid_cid').val(data.insertid);
				
				notify('success','Please Attention',data.show_text);
				
				load_form_div('/Product_master/CompanyListSub','supplier_list');
				load_form_div('/Product_master/CompanyEdit/'+data.insertid,'test_div');
				
			}
		}, 'json');
	});
});
</script>