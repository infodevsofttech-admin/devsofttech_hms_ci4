<?php echo form_open(); ?>
<div class="box">
	<div class="box-header">
	  <h3 class="box-title">Report Edit</h3>
	  <div class="pull-right box-tools">
		<button type="button" class="btn btn-default btn-sm" data-widget="collapse" data-toggle="tooltip" title="Collapse">
		  <i class="fa fa-minus"></i></button>
	  </div>
	</div>
	<!-- /.box-header -->
	<div class="box-body" >
		<div class="row">
			<div class="col-md-12">
				<div class="form-group">
					<label>Report Name</label>
					<?php 
					$repo_name='';
					$repo_id='0';
					$HTMLData='';
					if (count($labReport_master)>0)
					{
						$repo_name=$labReport_master[0]->Title;
						$repo_id=$labReport_master[0]->mstRepoKey;
						$HTMLData=$labReport_master[0]->HTMLData;
					}
					?>
					<input class="form-control" id="input_Reportname" name="input_Reportname" placeholder="Report Name" type="text" value="<?=$repo_name?>" />
					<input type="hidden" id="repo_id" value="<?=$repo_id?>">
				</div>
			</div>
			<div class="col-md-6">
				<div class="form-group">
					<label>Attach Charge Name</label>
					<select class="form-control" id="charge_id" name="charge_id"  >	
						<?php 
						if (count($labReport_master)>0)
						{
							$sel_value=$labReport_master[0]->charge_id;
						}else
						{
							$sel_value=0;
						}
												
						echo '<option value="0"  '.combo_checked("0",$sel_value).'  >No Attach</option>';
						
						foreach($hc_items as $row)
						{ 
							echo '<option value='.$row->id.'  '.combo_checked($row->id,$sel_value).'  >'.$row->idesc.'</option>';
						}
						?>
					</select>
				</div>
			</div>
			<div class="col-md-6">
				<div class="form-group">
					<label>Group</label>
					<select class="form-control" id="group_id" name="group_id"  >	
						<?php 
						if (count($labReport_master)>0)
						{
							$sel_value=$labReport_master[0]->GrpKey;
						}else
						{
							$sel_value=0;
						}
						
						foreach($lab_rgroups as $row)
						{ 
							echo '<option value='.$row->mstRGrpKey.'  '.combo_checked($row->mstRGrpKey,$sel_value).'  >'.$row->RepoGrp.'</option>';
						}
						?>
					</select>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<textarea id='HTMLData' name="HTMLData"  placeholder="Place some text here">
				<?=$HTMLData ?>
				</textarea>
				<script>
					CKEDITOR.replace( 'HTMLData' );
				</script>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6">
				<div class="form-group">
					<label></label>
					<button id="updatereport"  type="button" class="btn btn-primary">Update</button>
				</div>
			</div>
		</div>
		
	</div>
</div>
<div class="box">
	<div class="box-header">
	  <h3 class="box-title">Report Parameter</h3>
	  <div class="pull-right box-tools">
		<button type="button" class="btn btn-default btn-sm" data-widget="collapse" data-toggle="tooltip" title="Collapse">
		  <i class="fa fa-minus"></i></button>
	  </div>
	</div>
	<!-- /.box-header -->
	<div class="box-body" >
		<div class="row">
			<div class="col-md-12">
				<?php for ($i = 0; $i < count($lab_Rep_Item_List); ++$i) 
				{ 
					echo '<div  style="margin:2px ;display:inline-block;color:'.$color_name[($lab_Rep_Item_List[$i]->id)%50]->code_code_2.';"><i>'.$lab_Rep_Item_List[$i]->Test.'</i>['.$lab_Rep_Item_List[$i]->TestID.'] </div>';
				}
				?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<button onclick="load_form_div('/Lab_Admin/report_test_list/<?=$repo_id ?>','test_div');" type="button" class="btn btn-primary">Test List</button>
			</div>
		</div>
	</div>
</div>
<?php echo form_close(); ?>
<script>
	$('#updatereport').click(function(){
			var repo_id = $('#repo_id').val();
            var input_Reportname = $('#input_Reportname').val();
			var charge_id=$('#charge_id').val();
			var group_id=$('#group_id').val();
			var HTMLData=CKEDITOR.instances.HTMLData.getData();
			var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
			
			if(repo_id>0)
			{
				$.post('/index.php/Lab_Admin/report_update',{ 
					"repo_id": repo_id, 
					"input_Reportname": input_Reportname,
					"charge_id":charge_id,
					"group_id":group_id,
					"HTMLData":HTMLData,
					'<?=$this->security->get_csrf_token_name()?>':csrf_value
					}, function(data){
					$('#msgshow').html(data.showcontent);
					$("#alert_show").alert();
					$("#alert_show").fadeTo(5000, 500).slideUp(500, function(){
						$("#alert_show").slideUp(500);
						});
				}, 'json');
			}else{
				$.post('/index.php/Lab_Admin/report_insert',{ 
					"repo_id": repo_id, 
					"input_Reportname": input_Reportname,
					"charge_id":charge_id,
					"group_id":group_id,
					"HTMLData":HTMLData,
					'<?=$this->security->get_csrf_token_name()?>':csrf_value
					}, function(data){
					$('#msgshow').html(data.showcontent);
					$("#alert_show").alert();
					$("#alert_show").fadeTo(5000, 500).slideUp(500, function(){
						$("#alert_show").slideUp(500);
						});
					if(data.insertid>0)
					{
						load_form('/Lab_Admin/report_list');
					}
					
					
				}, 'json');
			}

        });

</script>