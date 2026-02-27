<?php echo form_open('Lab_Admin/report_edit_rquest', array('role'=>'form','class'=>'form_reason'));  ?>
<input type="hidden" id="repo_id"  name="repo_id" value="<?=$repo_id ?>">
    <div class="col-md-6">
        <div class="form-group">
        <label>Analytical Faults</label>
        <select name='cbo_sub_Analytical' class='form-control' id='cbo_sub_Analytical'>
            <?php foreach ($lab_log_type_master as $lab_log_type) { 
                $selected = ($lab_log_type['id'] == $Faults_id) ? "selected='selected'"  : "";
                $select_content ="<option value='".$lab_log_type['id']."'  ".$selected." >".$lab_log_type['log_type']."</option>";    
                echo $select_content;
            }
            ?>
        </select>
        </div>
    </div>
    <div class="col-md-12">
        <div class="form-group">
        <label for="usr">Comment/Answer:</label>
        <input type="text" class="form-control" id="other_reason" name="other_reason">
        </div>
	</div>
	<div class="col-md-3">
        <button type="button" class="btn btn-primary" id="btn_reopen_reason">Open Report for EDIT</button>
	</div>
	<!-- show captured image -->
<?php echo form_close(); ?>
<script>

$(document).ready(function(){

    $('#btn_reopen_reason').click(function(){
        var formdata = $('form.form_reason').serializeArray();
     
        $.post('/index.php/Lab_Admin/report_edit_request', formdata, function(data){
            if(data.insertid==0)
            {
                notify('error','Please Attention',data.showcontent);	
            }else
            {
                notify('success','Please Attention',data.showcontent);
                $('#testentry-bodyc').html(data.showcontent);
        
            }
        }, 'json');
    })

		
});
</script>	