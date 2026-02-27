<section class="content-header">
    <h1>
        ECHS Person 
        <small>Registration</small>
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
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active"><a aria-expanded="true" href="#nperson" data-toggle="tab">New Person</a></li>
                    <li class=""><a aria-expanded="false" href="#search" data-toggle="tab">Search</a></li>
                    
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="nperson">
                        <div class="jsError"></div>
                        <?php echo form_open('Patient/create_echs', array('role'=>'form','class'=>'form1')); ?>
                        <div class="row">
							<div class="col-md-3">
								<div class="form-group">
									<label>Phone Number</label>
									<input class="form-control" name="input_mphone1" placeholder="Phone Number"  data-inputmask='"mask": "999-999-9999"' data-mask  type="text" autocomplete="off">
								</div>
							</div>
							<div class="col-md-3">
								<div class="form-group">
									<label>Full Name</label>
									<input class="form-control" name="input_name" placeholder="Full Name" type="text" autocomplete="off">
								</div>
							</div>
							<div class="col-md-3">
								<div class="form-group">
									<div class="radio">
										<label>
										  <input name="optionsRadios_gender" id="options_gender1" value="1" checked="" type="radio">
										  Male
										</label>
										<label>
											<input name="optionsRadios_gender" id="options_gender2" value="2" type="radio">
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
										<input class="form-control pull-right datepicker" name="datepicker_dob" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask=""  />
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
										<input class="form-control pull-right datepicker" name="datepicker_dor" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask=""  />
									</div>
								</div>
							</div>
							
						</div>
						<div class="row">
							<div class="col-md-4">
							  <div class="form-group">
									<label>Pin/Zip Code </label>
									<input class="form-control" name="input_zip" placeholder="Pin/Zip Code" type="text" autocomplete="off">
							  </div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>Address</label>
									<input class="form-control" name="input_address" placeholder="Address" type="text" autocomplete="off">
								</div>
							</div>
							<div class="col-md-4"> 
								<div class="form-group">
									<label>city</label>
									<input class="form-control" name="input_city" placeholder="City" type="text" >
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-3">
								<div class="form-group">
									<label>Service No</label>
									<input class="form-control" name="input_echs_service_no" placeholder="Service No"  type="text" autocomplete="off">
								</div>
							</div>
							<div class="col-md-3">
								<div class="form-group">
									<label>Reg. No.</label>
									<input class="form-control" name="input_echs_regno" placeholder="Reg. No."  type="text" autocomplete="off">
								</div>
							</div>
						</div>
                        <div class="row">
							<div class="col-md-6">  
                                <button type="submit" class="btn btn-primary">Register ECHS Card</button>
							</div>
						</div>
                          
                        <?php echo form_close(); ?>
                    </div>
                        
                    <div class="tab-pane " id="search">
                        <?php echo form_open('Patient/search_echs', array('role'=>'form','class'=>'form2')); ?>
                        <div class="input-group input-group-sm">
                            <input class="form-control" type="text" id="txtsearch" name="txtsearch">
                                <span class="input-group-btn">
                                <button type="submit" class="btn btn-info btn-flat">Go!</button>
                            </span>
                        </div>
                         <?php echo form_close(); ?>
                         <div class="searchresult"></div>
                    </div>
                  
                       
                    </div>
                </div>
            </div>
        </div>
                
      </div>
      <!-- ./row -->
    </section>
<!-- /.content -->

<script>
    $(document).ready(function(){
        $('form.form1').on('submit', function(form){
            form.preventDefault();
            $.post('/index.php/Patient/create_echs', $('form.form1').serialize(), function(data){
                if(data.insertid==0)
                {
                    $('div.jsError').html(data.error_text);
                }else
                {
                    load_form('/Patient/echs_record/'+data.insertid);
					
                }
            }, 'json');
        });
   });
   
   $(document).ready(function(){
        $('form.form2').on('submit', function(form){
            form.preventDefault();
            $.post('/index.php/Patient/search_echs', $('form.form2').serialize(), function(data){
                $('div.searchresult').html(data);
                $('#example1').DataTable();
            });
        });
   });
   

    

</script>