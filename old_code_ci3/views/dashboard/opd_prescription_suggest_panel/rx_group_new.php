<?php echo form_open('Opd_prescription/add_medicine', array('role'=>'form','class'=>'form2')); ?>
<div class="row">
    <div class="col-md-6"> 
        <div class="form-group">
            <label>Rx-Group Name</label>
            <input class="form-control input-sm" name="input_rx_group_name" id="input_rx_group_name" type="text" value=""   >
        </div>
    </div>
    <div class="col-md-6"> 
        <div class="form-group">
            <label>Complaints</label>
            <input class="form-control input-sm" name="input_complaints" id="input_complaints" type="text" value="" >
        </div>
    </div>
    <div class="col-md-6"> 
        <div class="form-group">
            <label>Diagnosis</label>
            <input class="form-control input-sm" name="input_diagnosis" id="input_rx_diagnosis" type="text" value=""   >
        </div>
    </div>
    <div class="col-md-6"> 
        <div class="form-group">
            <label>Investigation</label>
            <input class="form-control input-sm" name="input_investigation" id="input_investigation" type="text" value=""   >
        </div>
    </div>
    <div class="col-md-6"> 
        <div class="form-group">
            <label>Finding Examinations</label>
            <input class="form-control input-sm" name="input_Finding_Examinations" id="input_Finding_Examinations" type="text" value=""  >
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <button type="button" class="btn btn-primary" id="btn_save_rx_group">Save Rx-Group</button>
        <input type="hidden" name="hid_rx_id" id="hid_rx_id" value="0" >
   </div>
</div>
<?php echo form_close(); ?>
<script>
    $( function() {
        $('#btn_save_rx_group').click(function(){
            $.post('/index.php/Opd_prescription/save_rx_group', $('form.form2').serialize(), function(data){
			if(data.insertid==0)
			{
				notify('error','Please Attention',data.show_text);
			}else
			{
				$('#hid_rx_id').val(data.insertid);
				
				notify('success','Please Attention',data.show_text);
				
				load_form_div('/Opd_prescription/rx_group_list','supplier_list');
				load_form_div('/Opd_prescription/save_rx_group_edit/'+data.insertid,'test_div');
			}
		}, 'json');
		})
    });
</script>