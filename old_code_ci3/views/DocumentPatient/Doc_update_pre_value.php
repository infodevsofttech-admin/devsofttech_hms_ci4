<section class="content-header">
  <h1>
	Document Data
	<small></small>
  </h1>
  <ol class="breadcrumb">
	<li><a href="javascript:load_form('/Patient/person_record/<?=$person_info[0]->id ?>');"><i class="fa fa-dashboard"></i> Person</a></li>
	<li><a href="javascript:load_form('/Document_Patient/p_doc_record/<?=$person_info[0]->id ?>');"><i class="fa fa-dashboard"></i> Document List</a></li>
  </ol>
</section>
<section class="content">
	<?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
		<input type="hidden" id="patient_doc_id" value="<?=$patient_doc_id ?>">
		<div class="box">
		<div class="box-header">
		  <h3 class="box-title">Patient Name :<?=$person_info[0]->p_fname ?>  </h3>
		  	<small> / <?=$patient_doc_id ?></small>
		</div>
		<!-- /.box-header -->
		<div class="box-body" style="height:500px;overflow-y:auto;">
			<?php if(count($doc_format_sub)>0) { 
				for ($i = 0; $i < count($doc_format_sub); ++$i) { ?>
					<div class="row">
						<div class="col-md-3">
							<label><?=$doc_format_sub[$i]->input_name?></label>
						</div>
						<div class="col-md-3">
							<label><?=$doc_format_sub[$i]->p_doc_raw_value?></label>
						</div>	
							<?php
								if($doc_format_sub[$i]->input_type ==1)
								{
									echo '
									<div class="col-md-4">
										<div class="form-group">
							                    <label>'.$doc_format_sub[$i]->input_name.' </label>
												<select id="input_id_'.$doc_format_sub[$i]->id.'" class="form-control">';
												$row_data=explode(',',$doc_format_sub[$i]->p_doc_raw_value);
												
												for ($j = 0; $j < count($row_data); ++$j)
												{
													//$col_data=explode(':',$row_data[$j]);
													//echo '<option value='.$col_data[1].' >['.$col_data[1].']'.$col_data[2].'</option>';
													echo '<option value='.$row_data[$j].' >'.$row_data[$j].'</option>';
												}
									echo "</select>
										</div>
									</div>";
								}elseif($doc_format_sub[$i]->input_type ==2){
									echo '
									<div class="col-md-4">
										<div class="form-group">
							                    <label>'.$doc_format_sub[$i]->input_name.' </label>
							                    <div class="input-group date">
							                        <div class="input-group-addon">
							                            <i class="fa fa-calendar"></i>
							                        </div>
							                        <input class="form-control pull-right datepicker" id="input_id_'.$doc_format_sub[$i]->id.'" name="input_id_'.$doc_format_sub[$i]->id.'" type="text" data-inputmask="\'alias\': \'dd/mm/yyyy\'" data-mask="" value="'.date('d/m/Y').'"  />
							                    </div>
						                	</div>
						               	</div>';
								}elseif($doc_format_sub[$i]->input_type ==3){
									echo '
									<div class="col-md-4">
										<div class="form-group">
							                <label>'.$doc_format_sub[$i]->input_name.'</label>
											<textarea class="form-control" id="input_id_'.$doc_format_sub[$i]->id.'" >'.$doc_format_sub[$i]->p_doc_raw_value.'</textarea>
										</div>
									</div>';
								}else{
									echo '
									<div class="col-md-4">
										<div class="form-group">
							                <label>'.$doc_format_sub[$i]->input_name.'</label>
											<input class="form-control" id="input_id_'.$doc_format_sub[$i]->id.'" type="text" value="'.$doc_format_sub[$i]->p_doc_raw_value.'" />
										</div>
									</div>';
								}
							?>
							<div class="col-md-2">
								<button onclick="update_data_value(<?=$doc_format_sub[$i]->id ?>,document.getElementById('input_id_<?=$doc_format_sub[$i]->id ?>').value)" type="button" class="btn btn-primary">Save</button>
							</div>
					</div>
			<?php 
				} 
			 }  
			 ?>
			<div class="row">
				<div class="col-md-12">
					<button onclick="report_create()" type="button" class="btn btn-primary">Report Complie</button>
					<button onclick="load_form('/Document_Patient/load_doc/<?=$patient_doc_id?>')" type="button" class="btn btn-primary">Edit Document</button>
				</div>
			</div>
		</div>
		<!-- /.box-body -->
		</div>
		<?php echo form_close(); ?>
</section>
<script>
	function update_data_value(test_id,test_value)
	{
			var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

			$.post('/index.php/Document_Patient/Entry_Update',
			{ 
			"test_id": test_id,
			"test_value":test_value,
			"<?=$this->security->get_csrf_token_name()?>":csrf_value, 
			}, function(data){
			$('#update_value_'+test_id).html(data);
			});
	}

	function report_create()
	{
		var patient_doc_id=$('#patient_doc_id').val();
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
		
		if(confirm('Are you sure Re-Comple ?')){
			$.post('/index.php/Document_Patient/update_doc_field/'+patient_doc_id,
				{ "patient_doc_id": patient_doc_id,
				"<?=$this->security->get_csrf_token_name()?>":csrf_value },
				 function(data){
				if(data.update==0)
				{
					alert(data.error_text);
				}else{
					alert(data.error_text);
					load_form('/Document_Patient/load_doc/'+patient_doc_id);
				}
			},'json');	
		}
	}
</script>