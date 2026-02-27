<section class="content-header">
    <h1>
        Person 
        <small><?=ucwords($pdata[0]->p_fname); ?></small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="javascript:load_form('/Patient/person_record/<?=$pdata[0]->id ?>');"><i class="fa fa-dashboard"></i> <?=$pdata[0]->p_fname ?></a></li>
        <li class="active">Insurance Card</li>
    </ol>
</section>
<!-- Main content -->
    <section class="content">
		<div class="row">
		<div class="jsError callout callout-info" id='jsError' style="margin-bottom: 0!important;"></div>
			<div class="col-md-12">
            <?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>

                    <input type="hidden" value="<?=$pdata[0]->id ?>" id="p_id" name="p_id" />
					<?php 
					$ins_card_id="0";
					$ins_company_id="0";
					$ins_insurance_no="";
					$card_holder_name="";
					$relation_patient_cardholder="";
					$issue_date=date('d/m/Y');
					$expiry_date=date('d/m/Y');
					
					if(count($data_insurance_card)>0)
					{
						$ins_card_id=$data_insurance_card[0]->id;
						$ins_company_id=$data_insurance_card[0]->insurance_id;
						$ins_insurance_no=$data_insurance_card[0]->insurance_no;
						$card_holder_name=$data_insurance_card[0]->card_holder_name;
						$relation_patient_cardholder=$data_insurance_card[0]->relation_patient_cardholder;
						$issue_date=MysqlDate_to_str($data_insurance_card[0]->issue_date);
						$expiry_date=MysqlDate_to_str($data_insurance_card[0]->expiry_date);
					}
					?>
					<input type="hidden" value="<?=$ins_card_id ?>" id="inscard_id" name="inscard_id" />
                       <div class="row">
							<div class="col-md-4"> 
								<div class="form-group">
									<label>Insurance</label>
									<select class="form-control" name="Insurance_id" id="Insurance_id" >
									<?php 
										foreach($data_insurance as $row)
										{ 
											echo '<option value="'.$row->id.'" '.combo_checked($ins_company_id,$row->id).'  >'.$row->ins_company_name.'</option>';
										}
									?>   
									</select>
								</div>
							</div>
							<div class="col-md-4"> 
								<div class="form-group">
									<label>Insurance ID / No.</label>
									<input class="form-control" name="input_insurance_id" placeholder="Insurance Number" type="text" value="<?=$ins_insurance_no?>"  >
								</div>
							</div>
							<div class="col-md-4"> 
								<div class="form-group">
									<label>Insurance Card Holder Name</label>
									<input class="form-control" name="input_card_holder_name" placeholder="Name of Card Holder" type="text" value="<?=$card_holder_name?>"  >
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-4"> 
								<div class="form-group">
									<label>Issue Date</label>
									<div class="input-group date">
										<div class="input-group-addon">
										<i class="fa fa-calendar"></i>
										</div>
										<input class="form-control pull-right datepicker" value="<?=$issue_date ?>" name="datepicker_issue_date" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask=""  />
									</div>
								</div>
							</div>
							<div class="col-md-4"> 
								<div class="form-group">
									<label>Expiry Date</label>
									<div class="input-group date">
										<div class="input-group-addon">
										<i class="fa fa-calendar"></i>
										</div>
										<input class="form-control pull-right datepicker" value="<?=$expiry_date ?>" name="datepicker_expiry_date" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask=""  />
									</div>
								</div>
							</div>
							<div class="col-md-4"> 
								<div class="form-group">
									<label>Relation</label>
									<input class="form-control" name="input_Relation" placeholder="Relation" type="text" value="<?=$relation_patient_cardholder?>"  >
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12"> 
								<button type="button" class="btn btn-primary" id="btn_update">Add or Update Record</button>
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
			var p_id = $('#p_id').val();
			$.post('/index.php/Patient/update_card', $('form.form1').serialize(), function(data){
                if(data.insertid==0)
                {
                    $('div.jsError').html(data.error_text);
					
                }else
                {
                    load_form('/Patient/person_record/'+p_id);
                }
            }, 'json');
		});
		
        
		$('#btn_card').click(function(){
			var p_id = $('#p_id').val();
            load_form('/Patient/show_cards/'+p_id);
        });
   });
   
   

</script>