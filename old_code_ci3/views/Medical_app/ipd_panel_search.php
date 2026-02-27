<style>
    .responsive {
        width: 100%;
        max-width: 400px;
        height: auto;
        padding: 2px;
    }
</style>
<div class="row">
    <div class="box box-primary">
        <div class="box-header with-border">
            IPD List Search
            <div class="box-tools pull-right">
                <a class="btn  btn-success" href="javascript:load_form('/Medical_app/ipd_panel')">Current IPD</a>
                <a class="btn  btn-warning" href="javascript:load_form('/Medical_app/search_ipd')">Search Bill</a>
            </div>
        </div>
        <div class="box-body box-profile" >
            <div class="row">
                <div class="col-md-12">
                    <?php
                    $attributes = array('id' => 'myform');
                    echo form_open('Medical_app/ipd_search_data', $attributes); ?>
                    <div class="input-group input-group-sm">
                        <input type="text" name="data_search" id="data_search" class="form-control pull-right" value="<?php echo $this->input->post('data_search'); ?>" placeholder="Search" required>
                        <div class="input-group-btn">
                            <button type="submit" class="btn btn-default"><i class="fa fa-search"></i></button>
                        </div>
                    </div>
                    <?php echo form_close(); ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <h3>Results</h3>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12" id="ipd_search_panel">
                    
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#myform').on('submit', function(form){
            form.preventDefault();
            form_array=$('#myform').serialize();
            $("#ipd_search_panel").html('Data Posting....Please Wait');
            $.post('/Medical_app/ipd_panel_search_data', form_array, function(data){
                 $("#ipd_search_panel").html(data);
            });
        });
    });
</script>