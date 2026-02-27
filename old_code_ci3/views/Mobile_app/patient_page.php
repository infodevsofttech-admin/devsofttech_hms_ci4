<div class="row">
    <div class="col-md-12">
        <?php 
            $attributes = array('id' => 'myform');
            echo form_open('Mobile_app/patient_search',$attributes); ?>
            <input type="hidden" id="doc_id" name="doc_id" value="<?=$doc_id?>" >
            <input type="hidden" id="day" name="day" value="<?=$day?>" >
            <div class="input-group input-group-sm" >
                <input type="text" name="data_search" id="data_search" class="form-control pull-right" 
                value="<?php echo $this->input->post('data_search'); ?>"
                placeholder="Search"  >
                <div class="input-group-btn">
                    <button type="submit" class="btn btn-default"><i class="fa fa-search"></i></button>
                </div>
            </div>
        <?php echo form_close(); ?>
    </div>
</div>
<div class="row"  >
    <div class="col-md-12">
        <h3>Results</h3>
    </div>
</div>
<div class="row"  >
    <div class="col-md-12" id="search_result">
        <?php                    
        if(isset($_view) && $_view)
            $this->load->view($_view);
        ?>
    </div>  
</div>
<script>

    $(document).ready(function(){
        var cache = {};

        var csrf_dst_name_value=$("input[name='<?=$this->security->get_csrf_token_name()?>']").val();

        $('#myform').on('submit', function(form){
            form.preventDefault();
            form_array=$('#myform').serialize();
            $("#search_result").html('Data Posting....Please Wait');
            $.post('/Mobile_app/patient_search', form_array, function(data){
                 $("#search_result").html(data);
            });
        });

        $("#data_search").autocomplete({
		    source: function( request, response ) {
                $.ajax({
                url:"/Mobile_app/patient_index_opd",
                type: 'post',
                data: {
                    "doc_id":$("#doc_id").val(),
                    "day":$("#day").val(),
                    "<?php echo $this->security->get_csrf_token_name(); ?>" : csrf_dst_name_value,
                    search: request.term
                    },
                success: function( data ) {
                    $("#search_result").html(data);
                    }       
		        });
            },
            minLength: 1,
            autofocus: true
		});

        
    });
</script>