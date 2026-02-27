<section class="content-header">
    <h1>
        Doctor 
        <small>Add New</small>
    </h1>
    <ol class="breadcrumb">
        <li class="active">Doctor</li>
    </ol>
</section>
<!-- Main content -->
    <section class="content">
      <div class="row">
        <div class="col-md-9">
            <div class="jsError"></div>
            <?php echo form_open('/Doctor/AddNew', array('role'=>'form','class'=>'form1')); ?>
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                <label>Title</label>
                                <select class="form-control" name="select_title" id="select_title">
                                    <option value="Dr">Dr.</option>
                                    <option value="Mr">Mr.</option>
                                    <option value="Ms">Ms.</option>
                                </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input class="form-control" name="input_name" placeholder="Full Name" type="text" required="true" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                 <div class="form-group">
                                    <label>Email</label>
                                    <input class="form-control" name="input_email" name="input_email" placeholder="Email" 
                                    type="email" required="true" />
                                 </div>
                            </div>
                        </div>
                        <div class="row">
                             <div class="col-md-2">
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
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input class="form-control number" name="input_mphone1" placeholder="Phone Number" 
                                    type="text" required="true">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Date of Birth</label>
                                    <div class="input-group date">
                                        <div class="input-group-addon">
                                            <i class="fa fa-calendar"></i>
                                        </div>
                                        <input class="form-control pull-right datepicker" name="datepicker_dob" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask=""   />
                                    </div>
                                </div>
                            </div>
                        </div>
                         <div class="row">
                                
                         
                         </div>
                         <div class="row">
                         
                         
                         </div>
                         <button type="submit" class="btn btn-primary">Save</button>
                          
                        <?php echo form_close(); ?>
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
            form_array=$('form.form1').serialize();
            $("#maindiv").html('Data Posting....Please Wait');
            $.post('/index.php/Doctor/AddNew', form_array, function(data){
                $("#maindiv").html(data);
            });
        });
   });
   
    
</script>