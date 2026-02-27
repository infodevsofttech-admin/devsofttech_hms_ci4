<section class="content-header">
    <h1>
        Insurance Company 
        <small><?=$data_insurance[0]->ins_company_name; ?></small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="javascript:load_form_div('/insurance/search','maindiv','Insurance');"><i class="fa fa-dashboard"></i> List</a></li>
        <li class="active">Insurance Company</li>
    </ol>
</section>
<!-- Main content -->
    <section class="content">
	<div class="jsError"></div>
      <div class="row">
        <div class="col-md-12">
            <?php echo form_open('insurance/create', array('role'=>'form','class'=>'form1')); ?>
			<input type="hidden" value="<?=$data_insurance[0]->id ?>" id="p_id" name="p_id" />
			<div class="row">
				<div class="col-md-4">
					<div class="form-group">
						<label class="text-primary">Company Name</label>
						<input class="form-control input-sm " name="input_comp_name" placeholder="Full Name" value="<?=$data_insurance[0]->ins_company_name ?>" type="text" autocomplete="off">
					</div>
				</div>
				<div class="col-md-2">
					<div class="form-group">
						<label class="text-primary">Short Name</label>
						<input class="form-control input-sm" name="input_short_name" placeholder="Short Name" value="<?=$data_insurance[0]->short_name ?>" type="text" autocomplete="off">
					</div>
				</div>
				<div class="col-md-2">
					<div class="form-group">
						<label class="text-primary">Phone Number</label>
						<input class="form-control input-sm" name="input_mphone1" placeholder="Phone Number" value="<?=$data_insurance[0]->ins_contact_number ?>" type="text" autocomplete="off" >
					</div>
				</div>
				<div class="col-md-2">
					<div class="form-group">
						<label class="text-primary">E-Mail </label>
						<input class="form-control input-sm" name="input_email" placeholder="E-Mail" value="<?=$data_insurance[0]->ins_email ?>" type="text" autocomplete="off">
					</div>
				</div>
				<div class="col-md-2">
					<div class="form-group">
						<label class="text-primary">Contact Person Name</label>
						<input class="form-control input-sm" name="input_cname" placeholder="Full Name" value="<?=$data_insurance[0]->ins_contact_person_name ?>" type="text" autocomplete="off">
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-2">
					<div class="checkbox">
					  <label>
						<input type="checkbox" name="chk_active" id="chk_active" value="1" <?=checkbox_checked($data_insurance[0]->active)?> > Active
					  </label>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-6">
					<div class="box box-primary">
				<div class="box-header with-border">
					<h3 class="box-title">OPD</h3>
				</div>
				<div class="box-body">
					<div class="row">
						<div class="col-md-4">
							<div class="checkbox">
							  <label>
								<input type="checkbox" name="chk_opd_allowed" id="chk_opd_allowed" value="1" <?=checkbox_checked($data_insurance[0]->opd_allowed)?> > OPD Allow
							  </label>
							</div>
						</div>
						<div class="col-md-8">
							<div class="form-group">
									<label>OPD Rate Apply</label>
									<div class="radio">
										<label>
										  <input name="optionsRadios_opd_rate_direct" id="options_opd_rate_direct1" value="0" <?=radio_checked("0",$data_insurance[0]->opd_rate_direct)?> type="radio">
										  Direct Customer Rate
										</label>
										<label>
											<input name="optionsRadios_opd_rate_direct" id="options_opd_rate_direct2" value="1" <?=radio_checked("1",$data_insurance[0]->opd_rate_direct)?> type="radio">
											Rate Specific Insurance Company
										</label>
									</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-4">
							<div class="form-group">
								<label>OPD Fee Description</label>
								<input class="form-control input-sm" name="input_opd_fee_desc" placeholder="OPD Fee Description" value="<?=$data_insurance[0]->opd_desc ?>" type="text" autocomplete="off">
							</div>
						</div>
						<div class="col-md-4">
							<div class="checkbox">
							  <label>
								<input type="checkbox" name="chk_opd_cash" id="chk_opd_cash" value="1" <?=checkbox_checked($data_insurance[0]->opd_cash)?> > Direct Allowed
							  </label>
							</div>
						</div>
						<div class="col-md-4">
							<div class="checkbox">
							  <label>
								<input type="checkbox" name="chk_opd_credit" id="chk_opd_credit" value="1" <?=checkbox_checked($data_insurance[0]->opd_credit)?>> Credit Allowed
							  </label>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label>{<i>if Direct Customer Rate </i>} <br/> Discount</label>
								<input class="form-control input-sm number" name="input_opd_master_rate_discount" placeholder="Discount" value="<?=$data_insurance[0]->charge_rate_dicount ?>" type="text" autocomplete="off">
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label>{<i>if Rate Specific Insurance Company </i>} <br/>OPD Fee</label>
								<input class="form-control input-sm number" name="input_opd_fee" placeholder="OPD Fee" value="<?=$data_insurance[0]->opd_fee ?>" type="text" autocomplete="off">
							</div>
						</div>
					</div>
				</div>
				</div>
				</div>
				<div class="col-md-6">
					<div class="box box-warning">
						<div class="box-header with-border">
							<h3 class="box-title">Charges And Medicine</h3>
						</div>
						<div class="box-body">
							<div class="row">
								<div class="col-md-12">
									<div class="form-group">
											<label>Rate Apply</label>
											<div class="radio">
												<label>
												  <input name="optionsRadios_charge_rate_direct" id="options_charge_rate_direct1" value="0" <?=radio_checked("0",$data_insurance[0]->charge_rate_direct)?> type="radio">
												  Direct Customer Rate
												</label>
												<label>
													<input name="optionsRadios_charge_rate_direct" id="options_charge_rate_direct2" value="1" <?=radio_checked("1",$data_insurance[0]->charge_rate_direct)?> type="radio">
													Rate Specific Insurance Company
												</label>
											</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-4">
									<div class="form-group">
										<label>{<i>if Direct Customer Rate </i>} Discount</label>
										<input class="form-control number input-sm" name="input_charge_rate_dicount" placeholder="Discount" value="<?=$data_insurance[0]->charge_rate_dicount ?>" type="text" autocomplete="off">
									</div>
								</div>
								<div class="col-md-4">
									<div class="checkbox">
									  <label>
										<input type="checkbox" name="chk_charge_credit" id="chk_charge_credit" value="1" <?=checkbox_checked($data_insurance[0]->charge_credit)?>> Charge Credit
									  </label>
									</div>
								</div>
								<div class="col-md-4">
									<div class="checkbox">
									  <label>
										<input type="checkbox" name="chk_med_credit" id="chk_med_credit" value="1" <?=checkbox_checked($data_insurance[0]->med_credit)?>> Medicine Credit
									  </label>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<button type="button" class="btn btn-primary" id="btn_update">Update Record</button>
				</div>
			</div>
			<?php echo form_close(); ?>
		</div>
	</div>
      <!-- ./row -->
    </section>
<!-- /.content -->

<script>
    $(document).ready(function(){
        $('#btn_update').click(function(){
			$.post('/index.php/insurance/UpdateRecord', $('form.form1').serialize(), function(data){
                if(data.update==0)
                {
                    $('div.jsError').html(data.error_text);
                }else
                {
                   	notify('Success',data.showcontent)
                }
            }, 'json');
		})
   });
   
   
    
</script>