    <div class="row">
        <div class="col-md-12">
        <?php echo form_open('/Master_data/tag_add', array('role' => 'form', 'class' => 'form1')); ?>
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3>
                        Patient Tags
                        <small>Add New</small>
                    </h3>
                </div>
                <!-- /.box-header -->
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Tag Name</label>
                                <input class="form-control" name="input_tag_name" placeholder="Tag Name" type="text" required="true" />
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Tag Desc</label>
                                <input class="form-control" name="input_tag_desc" placeholder="Tag Description" type="text" required="true" />
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="hid_tag_id" name="hid_tag_id" value="0" />
                </div>
                <!-- /.box-body -->
                <div class="box-footer">
                    <button type="submit" class="btn btn-danger" >Save</button>
                    <button type="button" class="btn btn-warning"  onclick="load_form_div('/Master_data/tag_index','common_model-bodyc','Tag List');">Back to Tag List</button>
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
                $("#common_model-bodyc").html('Data Posting....Please Wait');
                $.post('/Master_data/tag_save', form_array, function(data) {
                    if(data>0){
                        load_form_div('/Master_data/tag_index','common_model-bodyc');
                    }else{
                        notify('Error','Error','Data Not Saved');
                    }
                    
                });
            });
        });
    </script>