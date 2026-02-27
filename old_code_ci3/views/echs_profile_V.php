<section class="content-header">
    <h1>
        ECHS Card : 
        <small><?=$data[0]->name; ?></small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Person</li>
    </ol>
</section>
<!-- Main content -->
    <section class="content">
      <div class="row">
        <div class="col-md-12">
            <?php echo form_open('Patient/update_echs', array('role'=>'form','class'=>'form1')); ?>
            <input type="hidden" value="<?=$data[0]->id ?>" id="p_id" name="p_id" />
			<div class="jsError"></div>
                        <div class="row">
							<div class="col-md-3">
								<div class="form-group">
									<label>Phone Number</label>
									<input class="form-control" name="input_mphone1" placeholder="Phone Number" value="<?=$data[0]->mphone1 ?>" type="text"  data-inputmask='"mask": "999-999-9999"' data-mask autocomplete="off">
								</div>
							</div>
							<div class="col-md-3">
								<div class="form-group">
									<label>Full Name</label>
									<input class="form-control" name="input_name" placeholder="Full Name" value="<?=$data[0]->name ?>" type="text" autocomplete="off">
								</div>
							</div>
							<div class="col-md-3">
								<div class="form-group">
									<div class="radio">
										<label>
										  <input name="optionsRadios_gender" id="options_gender1" value="1"  <?=radio_checked("1",$data[0]->gender)?>  type="radio">
										  Male
										</label>
										<label>
											<input name="optionsRadios_gender" id="options_gender2" value="2"  <?=radio_checked("2",$data[0]->gender)?>  type="radio">
											Female
										</label>
										</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-3">
								<div class="form-group">
									<label>Date of Birth</label>
										<div class="input-group date">
										<div class="input-group-addon">
										<i class="fa fa-calendar"></i>
										</div>
										<input class="form-control pull-right datepicker" name="datepicker_dob" value="<?=MysqlDate_to_str($data[0]->dob) ?>" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask=""  />
									</div>
								</div>
							</div>
							<div class="col-md-3">
								<div class="form-group">
									<label>Date of reteried</label>
										<div class="input-group date">
										<div class="input-group-addon">
										<i class="fa fa-calendar"></i>
										</div>
										<input class="form-control pull-right datepicker" name="datepicker_dor" value="<?=MysqlDate_to_str($data[0]->dor) ?>" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask=""  />
									</div>
								</div>
							</div>
							<div class="col-md-3">
								<div class="form-group">
									<label>Service No</label>
									<input class="form-control" name="input_echs_service_no" value="<?=$data[0]->service_no ?>" placeholder="Service No"  type="text" autocomplete="off">
								</div>
							</div>
							<div class="col-md-3">
								<div class="form-group">
									<label>Reg. No.</label>
									<input class="form-control" name="input_echs_regno" value="<?=$data[0]->reg_no ?>" placeholder="Reg. No."  type="text" autocomplete="off">
								</div>
							</div>
							
						</div>
						<div class="row">
							<div class="col-md-4">
							  <div class="form-group">
									<label>Pin/Zip Code </label>
									<input class="form-control" name="input_zip" placeholder="Pin/Zip Code" value="<?=$data[0]->zip ?>" type="text" autocomplete="off">
							  </div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>Address</label>
									<input class="form-control" name="input_address" placeholder="Address" value="<?=$data[0]->add1 ?>" type="text" autocomplete="off">
								</div>
							</div>
							<div class="col-md-4"> 
								<div class="form-group">
									<label>city</label>
									<input class="form-control" name="input_city" placeholder="City" value="<?=$data[0]->city ?>" type="text" >
								</div>
							</div>
							
						</div>
						
                        <div class="row">
							<div class="col-md-6">  
                                <button type="button" class="btn btn-primary" id="btn_update">Update ECHS Record</button>
							</div>
						</div>
						<hr />
			<?php echo form_close(); ?>
        </div>
      </div>
	<div class='echs_member'>
		<?php
			foreach($echs_member as $row)
			{   
					if($row->mem_gender==1)
						$mem_gender_str='Male';
					else
						$mem_gender_str='FeMale';
			
				echo '<div class="row">';
				echo '	<div class="col-md-3">';
				echo '		<div class="form-group">';
				echo '		<label>Member Name</label>';
				echo '		<input class="form-control" name="show_echs_mem_name" value="'.$row->mem_name.'"   type="text" autocomplete="off" readonly>';
				echo '		</div>';
				echo '	</div>';
				echo '		<div class="col-md-2">';
				echo '			<div class="form-group">';
				echo '			<label>Gender</label>';
				echo '			<input class="form-control" name="input_echs_mem_name" value="'.$mem_gender_str.'"   type="text" autocomplete="off" readonly>';
				echo '			</div>';
				echo '		</div>';
				echo '	<div class="col-md-2">';
				echo '		<div class="form-group">';
				echo '		<label>Date of Birth</label>';
				echo '		<input class="form-control" name="input_echs_mem_dob" value="'.$row->mem_dob.'"   type="text" autocomplete="off" readonly>';
				echo '		</div>';
				echo '	</div>';
				echo '	<div class="col-md-2">';
				echo '		<div class="form-group">';
				echo '		<label>Relation</label>';
				echo '		<input class="form-control" name="input_echs_mem_relation" value="'.$row->mem_relation.'"   type="text" autocomplete="off" readonly>';
				echo '		</div>';
				echo '	</div>';
				echo '	<div class="col-md-2">';
				echo '		<div class="form-group">';
				echo '		<button onclick="load_form(\'/Patient/person_from_echs/'.$row->id.'\');" type="button" class="btn btn-primary">Select It....</button>';
				echo '		</div>';
				echo '	</div>';
				echo '</div>';
			}
		?>
	</div>
	<hr />
	<?php echo form_open('Patient/update_echs', array('role'=>'form','class'=>'form2')); ?>
	<input type="hidden" value="<?=$data[0]->id ?>" id="em_id" name="em_id" />
	<div class="row">
		<div class="col-md-3">
			<div class="form-group">
				<label>Member Name</label>
				<input class="form-control" name="input_echs_mem_name"  placeholder="Service No"  type="text" autocomplete="off">
			</div>
		</div>
		<div class="col-md-2">
			<div class="form-group">
				<label>Gender</label>
				<div class="radio">
					<label>
					  <input name="optionsRadios_gender" id="options_gender1" value="1"  type="radio">
					  Male
					</label>
					<label>
					<input name="optionsRadios_gender" id="options_gender2" value="2" type="radio"  >
						Female
					</label>
				</div>
			</div>
		</div>
		<div class="col-md-2">
			<div class="form-group">
				<label>Relation</label>
				<input class="form-control" name="input_echs_mem_relation"  placeholder="Reg. No."  type="text" autocomplete="off">
			</div>
		</div>
		<div class="col-md-2">
			<div class="form-group">
				<label>Date of Birth</label>
					<div class="input-group date">
					<div class="input-group-addon">
					<i class="fa fa-calendar"></i>
					</div>
					<input class="form-control pull-right datepicker" name="input_echs_mem_dob"  type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask=""  />
				</div>
			</div>
		</div>
		<div class="col-md-3">
			<div class="form-group">
				<label> </label>
				<button type="button" class="btn btn-primary" id="btn_mem_update">Add Member</button>
			</div>
		</div>
	</div>		
	<?php echo form_close(); ?>
    <!-- ./row -->
    </section>
<!-- /.content -->

<script>
    $(document).ready(function(){
		
        $('#btn_update').click(function(){
			$.post('/index.php/Patient/update_echs', $('form.form1').serialize(), function(data){
                if(data.update==0)
                {
                    $('div.jsError').html(data.error_text);
                }else
                {
					$('div.jsError').html(data.showcontent);
					$("#alert_show").alert();
					$("#alert_show").fadeTo(2000, 500).slideUp(500, function(){
						$("#alert_show").slideUp(500);
						});

                }
            }, 'json');
		})
		
		$('#btn_mem_update').click(function(){
			$.post('/index.php/Patient/create_echs_member', $('form.form2').serialize(), function(data){
                if(data.insertid==0)
                {
                    $('div.jsError').html(data.error_text);
                }else
                {
					$('div.echs_member').html(data.showcontent);
					
                }
            }, 'json');
		})
   });
   
   
    
</script>