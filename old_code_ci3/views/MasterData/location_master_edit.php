<div class="row">
        <div class="col-md-12">
        <?php echo form_open('/Storestock/Location_save', array('role' => 'form', 'class' => 'form1')); ?>
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3>
                        Location 
                        <small><?=$location_master[0]->l_id?></small>
                    </h3>
                </div>
                <!-- /.box-header -->
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Location Name</label>
                                <input class="form-control" name="input_loc_name" placeholder="Location Name" type="text" required="true" value="<?=$location_master[0]->loc_name?>" />
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Location Desc</label>
                                <input class="form-control" name="input_loc_desc" placeholder="Location Description" type="text" required="true" value="<?=$location_master[0]->loc_desc?>" />
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="hid_location_id" name="hid_location_id" value="<?=$location_master[0]->l_id?>" />
                </div>
                <!-- /.box-body -->
                <div class="box-footer">
                    <button type="submit" class="btn btn-danger" >Save</button>
                    <button type="button" class="btn btn-warning"  onclick="load_form_div('/Storestock/location_master_list','maindiv','Location List');">Back to Location</button>
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
                $.post('/Storestock/Location_save', form_array, function(data) {
                    if(data>0){
                        notify('Success','Success','Data Saved');
                        load_form_div('/Storestock/location_master_list','maindiv');
                    }else{
                        notify('Error','Error','Data Not Saved');
                    }
                    
                });
            });
        });
    </script>