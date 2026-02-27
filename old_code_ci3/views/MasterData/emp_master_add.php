<div class="row">
        <div class="col-md-12">
        <?php echo form_open('/Master_data/tag_add', array('role' => 'form', 'class' => 'form1')); ?>
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3>
                        Employee 
                        <small>Add New</small>
                    </h3>
                </div>
                <!-- /.box-header -->
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Employee Code</label>
                                <input class="form-control" name="input_emp_code" placeholder="Employee Code" type="text" required="true" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Employee Name</label>
                                <input class="form-control" name="input_emp_name" placeholder="Employee Name" type="text" required="true" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Date of Birth</label>
                                <input class="form-control" name="input_emp_dob"  type="date" required="true" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Joining Date</label>
                                <input class="form-control" name="input_emp_joinning_date"  type="date" required="true" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input class="form-control" name="input_emp_phone_no" placeholder="Phone Number" type="text" required="true" />
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="hid_emp_id" name="hid_emp_id" value="0" />
                </div>
                <!-- /.box-body -->
                <div class="box-footer">
                    <button type="submit" class="btn btn-danger" >Save</button>
                    <button type="button" class="btn btn-warning"  onclick="load_form_div('/Master_data/Employee_master_list','maindiv','Employee List');">Back to Employee List</button>
                </div>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('form.form1').on('submit', function(form) {
                form.preventDefault();
                form_array = $('form.form1').serialize();
                $("#maindiv").html('Data Posting....Please Wait');
                $.post('/Master_data/Employee_save', form_array, function(data) {
                    if(data>0){
                        
                        load_form_div('/Master_data/Employee_master_list','maindiv');
                        notify('Success','Success','Data Saved');
                    }else{
                        notify('Error','Error','Data Not Saved');
                    }
                    
                });
            });
        });
    </script>