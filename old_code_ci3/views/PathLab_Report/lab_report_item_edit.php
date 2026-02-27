<?php 
	$mstTestKey=0;
	$Test='';
	$TestID='';
	$Result='';
	$Formula='';
	$VRule='';
	$VMsg='';
	$Unit='';
	$FixedNormals='';
	$isGenderSpecific=0;
	$FixedNormalsWomen='';
	$checkbox_checked="";
	
	if (count($lab_test_parameter)>0)
	{
		$mstTestKey=$lab_test_parameter[0]->mstTestKey;
		$Test=$lab_test_parameter[0]->Test;
		$TestID=$lab_test_parameter[0]->TestID;
		$Result=$lab_test_parameter[0]->Result;
		$Formula=$lab_test_parameter[0]->Formula;
		$VRule=$lab_test_parameter[0]->VRule;
		$VMsg=$lab_test_parameter[0]->VMsg;
		$Unit=$lab_test_parameter[0]->Unit;
		$FixedNormals=$lab_test_parameter[0]->FixedNormals;
		$isGenderSpecific=$lab_test_parameter[0]->isGenderSpecific;
		$FixedNormalsWomen=$lab_test_parameter[0]->FixedNormalsWomen;

		if($isGenderSpecific==1)
		{
			$checkbox_checked="checked";
		}
		

	}
?>
<?php echo form_open(); ?>
<div class="box">
		<div class="box-header">
			<h3 class="box-title">Test Parameter</h3>
		</div>
		<!-- /.box-header -->
		<div class="box-body" >
			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label>Name of Test</label>
						<input class="form-control" id="input_Test_name" placeholder="Test Name" value="<?=$Test ?>" type="text"  autocomplete="off">
						<input type="hidden" id="mstTestKey" value="<?=$mstTestKey?>">
						<input type="hidden" id="mstRepoKey" value="<?=$mstRepoKey?>">
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-group">
						<label>Test code</label>
						<input class="form-control" id="input_test_code" placeholder="Test code" value="<?=$TestID ?>" type="text"  autocomplete="off">
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label>Default Value</label>
						<input class="form-control" id="input_Default" placeholder="Default Value" value="<?=$Result ?>" type="text"  autocomplete="off">
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-group">
						<label>Formula</label>
						<input class="form-control" id="input_Formula" placeholder="Formula" value="<?=$Formula ?>" type="text"  autocomplete="off">
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label>Validation Rule</label>
						<input class="form-control" id="input_Validation" placeholder="Validation Rule" value="<?=$VRule ?>" type="text"  autocomplete="off">
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-group">
						<label>Unit</label>
						<input class="form-control" id="input_Unit" placeholder="Unit" value="<?=$Unit ?>" type="text"  autocomplete="off">
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label>Message</label>
						<input class="form-control" id="input_Message" placeholder="Message" value="<?=$VMsg ?>" type="text"  autocomplete="off">
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-group">
						<label>Fixed Normals</label>
						<input class="form-control" id="input_Fixed" placeholder="Fixed Normals" value="<?=$FixedNormals ?>" type="text"  autocomplete="off">
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label><input id="chk_isGenderSpecific" name="chk_isGenderSpecific" type="checkbox" <?=$checkbox_checked?>>
						Is Gender Specific</label>
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-group">
						<label>Fixed Normals For Women</label>
						<input class="form-control" id="input_FixedNormalsWomen" placeholder="Fixed Normals For Women" value="<?=$FixedNormalsWomen ?>" type="text"  autocomplete="off">
					</div>
				</div>
			</div>
			<hr />
			<div class="row">
				<div class="col-md-12" id='div_option_list'>
					<table id="example2" class="table table-bordered table-striped TableData">
						<thead>
						<tr>
							<th>#</th>
							<th>Code</th>
							<th>Bold</th>
							<th>Action</th>
						</tr>
						</thead>
						<tbody>
						<?php for ($i = 0; $i < count($lab_test_option); ++$i) { ?>
						<tr>
							<td><?=$lab_test_option[$i]->sort_id ?></td>
							<td><?=$lab_test_option[$i]->option_value ?></td>
							<td><?=$lab_test_option[$i]->option_bold_str ?></td>
							<td>
								<div class="btn-group-horizontal">
									<button type="button" class="btn btn-default" onclick="remove_option('<?=$lab_test_option[$i]->id?>','<?=$mstTestKey?>')" >
										<i class="fa fa-remove"></i></button>
									<?php 
									$option_current=$lab_test_option[$i]->id;
									$sort_current=$lab_test_option[$i]->sort_id;
									
									if($i+1 < count($lab_test_option))
									{
										$option_next=$lab_test_option[$i+1]->id;
										$sort_next=$lab_test_option[$i+1]->sort_id;
										
										echo '<button type="button" class="btn btn-default" onclick="sortchange('.$mstTestKey.','.$option_current.','.$sort_current.','.$option_next.','.$sort_next.')">
												<i class="fa fa-level-down"></i></button>';
									}
									if($i>0)
									{
										$option_prev=$lab_test_option[$i-1]->id;
										$sort_prev=$lab_test_option[$i-1]->sort_id;
										
										echo '<button type="button" class="btn btn-default" onclick="sortchange('.$mstTestKey.','.$option_current.','.$sort_current.','.$option_prev.','.$sort_prev.')">
												<i class="fa fa-level-up"></i></button>';

									}
									?>
								</div>
							</td>
						</tr>
						<?php } ?>
						</tbody>
					</table>
				</div>
				<div class="col-md-12">
					<div class="col-md-4">
						<div class="form-group">
							<label>Code</label>
							<input class="form-control" id="input_op_value" name="input_op_value" placeholder="Test Name" value="" type="text"  autocomplete="off">
						</div>
					</div>
					<div class="col-md-2">
						<div class="form-group">
							<div class="col-md-3">
								<div class="form-group">
									<label><input id="chk_bold" name="chk_bold" type="checkbox" > Bold</label>
								</div>
							</div>
							
						</div>
					</div>
					<div class="col-md-2">
						<div class="form-group">
							<label></label>
							<button type="button" class="btn btn-primary" id="btn_add_option">Add Option</button>
						</div>
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
						<button onclick="load_form_div('/Lab_Admin/report_test_list/<?=$mstRepoKey?>','test_div');" type="button" class="btn btn-primary">Test List</button>
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-group">
						<label> </label>
						<div class="col-md-4"><button onclick="load_form_div('/Lab_Admin/test_parameter_load/0/<?=$mstRepoKey?>','test_div');" type="button" class="btn btn-primary">Add New Test</button>
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
			var input_Test_name = $('#input_Test_name').val();
            var input_test_code = $('#input_test_code').val();
			var input_Default = $('#input_Default').val();
			var input_Formula = $('#input_Formula').val();
			var input_Validation = $('#input_Validation').val();
			var input_Unit = $('#input_Unit').val();
			var input_Message = $('#input_Message').val();
			var input_Fixed = $('#input_Fixed').val();
			var input_FixedNormalsWomen = $('#input_FixedNormalsWomen').val();
			var mstTestKey = $('#mstTestKey').val();
			var mstRepoKey = $('#mstRepoKey').val();

			var isChecked = 0;
			
			var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
			
			if($('#chk_isGenderSpecific').is(':checked'))
			{
				var isChecked =1;
			}
			
			if(mstTestKey>0)
			{
				$.post('/index.php/Lab_Admin/test_parameter_edit',{ 
					"input_Test_name": input_Test_name, 
					"input_test_code": input_test_code,
					"input_Default":input_Default,
					"input_Formula":input_Formula,
					"input_Validation":input_Validation,
					"input_Unit":input_Unit,
					"input_Message":input_Message,
					"input_Fixed":input_Fixed,
					"input_isChecked":isChecked,
					"input_FixedNormalsWomen":input_FixedNormalsWomen,
					"mstTestKey":mstTestKey,
					"mstRepoKey":mstRepoKey,
					'<?=$this->security->get_csrf_token_name()?>':csrf_value
					}, function(data){
					$('#msgshow').html(data.showcontent);
					$("#alert_show").alert();
					$("#alert_show").fadeTo(5000, 500).slideUp(500, function(){
						$("#alert_show").slideUp(500);
						});
				}, 'json');
			}else{
				$.post('/index.php/Lab_Admin/test_parameter_add',{ 
					"input_Test_name": input_Test_name, 
					"input_test_code": input_test_code,
					"input_Default":input_Default,
					"input_Formula":input_Formula,
					"input_Validation":input_Validation,
					"input_Unit":input_Unit,
					"input_Message":input_Message,
					"input_Fixed":input_Fixed,
					"mstTestKey":mstTestKey,
					"mstRepoKey":mstRepoKey,
					'<?=$this->security->get_csrf_token_name()?>':csrf_value
					}, function(data){
					$('#msgshow').html(data.showcontent);
					if(data.insert_id>0)
					{
						$('#mstTestKey').val(data.insert_id);
					}
					
					$("#alert_show").alert();
					$("#alert_show").fadeTo(5000, 500).slideUp(500, function(){
						$("#alert_show").slideUp(500);
						});
				}, 'json');
			}

        });

		$('#btn_add_option').click(function(){
			var input_op_value = $('#input_op_value').val();
            var chk_bold =0;
			var mstTestKey = $('#mstTestKey').val();
			var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
			
			if ($('#chk_bold').is(":checked"))
			{
			  chk_bold=1;
			}else{
				chk_bold=0;
			}
						
				if(mstTestKey>0)
				{
					$.post('/index.php/Lab_Admin/test_parameter_option_add',{ 
						"input_op_value": input_op_value, 
						"chk_bold": chk_bold,
						"mstTestKey":mstTestKey,
						'<?=$this->security->get_csrf_token_name()?>':csrf_value
						}, function(data){
						$('#msgshow').html(data.showcontent);
						if(data.insert_id>0)
						{
							$('#div_option_list').html(data.option_content);
							
						}
						$("#alert_show").alert();
						$("#alert_show").fadeTo(5000, 500).slideUp(500, function(){
							$("#alert_show").slideUp(500);
							});
					}, 'json');
				}else{
					alert("First create test");
				}
			
        });
		
		function sortchange(mstTestKey,option_current,sort_current,option_prev,sort_prev)
		{
			var post_str='/index.php/Lab_Admin/change_sort/'+mstTestKey+'/'+option_current+'/'+sort_current+'/'+option_prev+'/'+sort_prev;
			
			var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

			$.post(post_str,{ 
						"mstTestKey":mstTestKey,
						'<?=$this->security->get_csrf_token_name()?>':csrf_value
						}, function(data){
						$('#msgshow').html(data.showcontent);
						if(data.insert_id>0)
						{
							$('#div_option_list').html(data.option_content);
						}
						$("#alert_show").alert();
						$("#alert_show").fadeTo(5000, 500).slideUp(500, function(){
							$("#alert_show").slideUp(500);
							});
					}, 'json');
		}
		
		
		
		function remove_option(option_id,mstTestKey)
		{
			
			var post_str='/index.php/Lab_Admin/remove_test_option/'+option_id+'/'+mstTestKey;
			var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

			$.post(post_str,{ 
						"mstTestKey":mstTestKey,
						'<?=$this->security->get_csrf_token_name()?>':csrf_value
						}, function(data){
						$('#msgshow').html(data.showcontent);
						if(data.insert_id>0)
						{
							$('#div_option_list').html(data.option_content);
						}
						$("#alert_show").alert();
						$("#alert_show").fadeTo(5000, 500).slideUp(500, function(){
							$("#alert_show").slideUp(500);
							});
					}, 'json');
		}

</script>
