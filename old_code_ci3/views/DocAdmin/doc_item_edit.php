<?php 
	$input_id=0;
	$input_name='';
	$input_code='';
	$input_type='';
	$input_default_value='';

	if (count($input_parameter)>0)
	{
		$input_id=$input_parameter[0]->id;
		$input_name=$input_parameter[0]->input_name;
		$input_code=$input_parameter[0]->input_code;
		$input_type=$input_parameter[0]->input_type;
		$input_default_value=$input_parameter[0]->input_default_value;
	}
?>
<?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
<div class="box">
		<div class="box-header">
		  <h3 class="box-title">Input Parameter</h3>
		</div>
		<!-- /.box-header -->
		<div class="box-body" >
			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label>Parameter Name</label>
						<input class="form-control" id="input_name" placeholder="Input Name" value="<?=$input_name ?>" type="text"  autocomplete="off">
						<input type="hidden" id="input_id" value="<?=$input_id?>">
						<input type="hidden" id="doc_id" value="<?=$doc_id?>">
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-group">
						<label>Parameter Code</label>
						<input class="form-control" id="input_code" placeholder="input_code code" value="<?=$input_code ?>" type="text"  autocomplete="off">
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label>Parameter Type</label>
						<select class="form-control" name="input_type" id="input_type"  >
							<option value="0" <?=combo_checked("0",$input_type)?> >Single Line Text</option>
							<option value="1" <?=combo_checked("1",$input_type)?> >Option Text</option>
							<option value="2"  <?=combo_checked("2",$input_type)?> >Date</option>
							<option value="3"  <?=combo_checked("3",$input_type)?>  >Multiple Line Text</option>
						</select>
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-group">
						<label>Default Value</label>
						<input class="form-control" id="input_default_value" placeholder="Input code" value="<?=$input_default_value ?>" type="text"  autocomplete="off">
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-4">
					<div class="form-group">
						<label> </label>
						<button type="button" class="btn btn-primary" id="btn_item_update">Update</button>
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-group">
						<label> </label>
						<button onclick="load_form_div('/Doc_Admin/doc_input_list/<?=$doc_id?>','test_div');" type="button" class="btn btn-primary">Input List</button>
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-group">
						<label> </label>
						<div class="col-md-4"><button onclick="load_form_div('/Doc_Admin/input_parameter_load/0/<?=$doc_id?>','test_div');" type="button" class="btn btn-primary">Add New Input</button>
					</div>
					</div>
				</div>
			</div>
		</div>
		<!-- /.box-body -->
</div>
<?php echo form_close(); ?>
<script>
	$('#btn_item_update').click(function(){
			var input_name = $('#input_name').val();
            var input_code = $('#input_code').val();
            var input_type = $('#input_type').val();
            var input_default_value = $('#input_default_value').val();

			var doc_id = $('#doc_id').val();
			var doc_sub_id = $('#input_id').val();

			var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

			if(doc_sub_id>0)
			{
				$.post('/index.php/Doc_Admin/input_parameter_edit',{ 
					"input_input_name": input_name, 
					"input_input_code": input_code,
					"input_type": input_type, 
					"input_default_value": input_default_value,
					"doc_id":doc_id,
					"doc_sub_id":doc_sub_id,
					"<?=$this->security->get_csrf_token_name()?>":csrf_value
					}, function(data){
					$('#msgshow').html(data.showcontent);
					$("#alert_show").alert();
					$("#alert_show").fadeTo(5000, 500).slideUp(500, function(){
						$("#alert_show").slideUp(500);
						});
				}, 'json');
			}else{
				$.post('/index.php/Doc_Admin/input_parameter_add',{ 
					"input_input_name": input_name, 
					"input_input_code": input_code,
					"input_type": input_type, 
					"input_default_value": input_default_value,
					"doc_id":doc_id,
					"<?=$this->security->get_csrf_token_name()?>":csrf_value
					}, function(data){
					$('#msgshow').html(data.showcontent);
					if(data.insert_id>0)
					{
						$('#input_id').val(data.insert_id);
					}
					
					$("#alert_show").alert();
					$("#alert_show").fadeTo(5000, 500).slideUp(500, function(){
						$("#alert_show").slideUp(500);
						});
				}, 'json');
			}

        });
		
		

</script>
