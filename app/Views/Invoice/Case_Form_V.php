<section class="content-header">
    <h1>
        Person 
        <small><?=$data[0]->p_fname; ?></small>
    </h1>
	<ol class="breadcrumb">
		<li><a href="javascript:load_form('<?= base_url('billing/patient/person_record') ?>/<?=$data[0]->id ?>');"><i class="fa fa-dashboard"></i> Person</a></li>
	</ol>
</section>
<!-- Main content -->
    <section class="content">
      <div class="row">
		<div class="col-md-12">
			<?php $card = $hc_insurance_card[0] ?? (object) []; ?>
			<?php $ins = $data_insurance[0] ?? (object) []; ?>
			<form role="form" class="form1">
						<?= csrf_field() ?>
						<input type="hidden" value="<?=$data[0]->id ?>" id="p_id" name="p_id" />
						<input type="hidden" value="<?=$case_type ?>" id="case_type" name="case_type" />
						<input type="hidden" value="<?= $card->id ?? 0 ?>" id="inc_card_id" name="inc_card_id" />
						<input type="hidden" value="<?= $ins->active ?? 0 ?>" id="insurance_active" name="insurance_active" />
                        <div class="row">
							<div class="col-md-4">
								<div class="form-group">
									<label>Phone Number</label>
									<input class="form-control" name="input_mphone1" placeholder="Phone Number" value="<?=$data[0]->mphone1 ?>" type="text" autocomplete="off"  data-inputmask='"mask": "999-999-9999"' data-mask >
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>Full Name</label>
									<input class="form-control" name="input_name" placeholder="Full Name" value="<?=$data[0]->p_fname ?>" type="text" autocomplete="off">
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<div class="radio">
										<label>
										  <input name="optionsRadios_gender" id="options_gender1" value="1" <?=radio_checked("1",$data[0]->gender)?> type="radio">
										  Male
										</label>
										<label>
											<input name="optionsRadios_gender" id="options_gender2" value="2" <?=radio_checked("2",$data[0]->gender)?> type="radio">
											Female
										</label>
										</div>
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col-md-4"> 
								<div class="form-group">
									<label>Insurance Company Name</label>
									<select class="form-control" id="Insurance_id" name="Insurance_id"   >
										<option value='0'  >All Doctors</option>
										<?php 
										foreach($insurance_list as $row)
										{ 
											$selected=($row->id==($ins->id ?? 0))?"Selected":"";
											
											echo '<option value='.$row->id.' '.$selected.' >'.$row->ins_company_name.'</option>';
										}
										?>
									</select>
								</div>
							</div>
							<div class="col-md-4"> 
								<div class="form-group">
									<label>Insurance No (like ECHS service No..)</label>
									<input class="form-control" name="input_insurance_id" placeholder="Insurance Number" type="text" value="<?= $card->insurance_no ?? '' ?>"  >
								</div>
							</div>
							<div class="col-md-4"> 
								<div class="form-group">
									<label>Insurance Card Holder Name</label>
									<input class="form-control" name="input_card_holder_name" placeholder="Name of Card Holder" type="text" value="<?= $card->card_holder_name ?? '' ?>" >
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-3">
								<div class="form-group">
									<label>Date of Registration</label>
										<div class="input-group date">
										<div class="input-group-addon">
										<i class="fa fa-calendar"></i>
										</div>
										<input class="form-control pull-right datepicker" value="<?=date('d/m/Y') ?>" name="datepicker_dob" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask=""  />
									</div>
								</div>
							</div>
							<div class="col-md-3"> 
								<div class="form-group">
									<label>Case No. or Clam No.</label>
									<input class="form-control" name="input_insurance_no_1" placeholder="Case No. or Clam No." type="text"   >
								</div>
							</div>
							<div class="col-md-3"> 
								<div class="form-group">
									<label>Other Ref. No.1</label>
									<input class="form-control" name="input_insurance_no_2" placeholder="Other Ref. No.1" type="text"   >
								</div>
							</div>
							<div class="col-md-3"> 
								<div class="form-group">
									<label>Other Ref. No.2</label>
									<input class="form-control" name="input_insurance_no_3" placeholder="Other Ref. No.2" type="text"   >
								</div>
							</div>
						</div>
						<div class="row">
							<button type="submit" class="btn btn-primary" id="btn_update">Create Case Record</button>
						</div>
						<div class="jsError"></div>
                        </form>
						
        </div>
		</div>
      <!-- ./row -->
    </section>
<!-- /.content -->

<script>
   $(document).ready(function(){
        $('form.form1').on('submit', function(form){
            form.preventDefault();
            var insurance_active=$('#insurance_active').val();
			var input_insurance_compnay_id=$('#input_insurance_compnay_id').val();
			var case_type=$('#case_type').val();

			if(insurance_active>0)
			{
				$.post('<?= base_url('billing/case/create') ?>', $('form.form1').serialize(), function(data){
					if(data.insertid==0)
					{
						$('div.jsError').html(data.error_text);
					}else
					{
						load_form('<?= base_url('billing/case/open_case') ?>/'+data.insertid+'/'+case_type);
					}
            	}, 'json');
			}else{
				alert(input_insurance_compnay_id +' is InActive, Please Contact to TPA Dept.');
			}
			
        });
   });
   
   
    
</script>
