<?php echo form_open(); ?>
<div class="box">
	<div class="box-header">
	  <h3 class="box-title">Radiology Report Edit</h3>
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
					$repo_title='';
					$Impression='';
					$impression_cat=0;

					if (count($labReport_master)>0)
					{
                        $repo_id=$labReport_master[0]->id;
						$repo_name=$labReport_master[0]->template_name;
						$repo_title=$labReport_master[0]->title;
						$HTMLData=$labReport_master[0]->Findings;
                        $Impression=$labReport_master[0]->Impression;
                        $impression_cat=$labReport_master[0]->impression_cat;
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
					<label>Print Report Name</label>
					<input class="form-control" id="input_Reporttitle" name="input_Reporttitle" placeholder="Report Title" type="text" value="<?=$repo_title?>" />
				</div>
			</div>
		</div>
        <hr/>
		<div class="row">
			<div class="col-md-12">
                <label>Findings</label>
				<textarea id='HTMLData' name="HTMLData"  placeholder="Place some text here">
				<?=$HTMLData ?>
				</textarea>
				<script>
					CKEDITOR.replace( 'HTMLData' );
				</script>
			</div>
		</div>
        <hr/>
        <div class="row">
			<div class="col-md-12">
                <label>Impression</label>
				<textarea id='Impression' name="Impression" class="form-control"  placeholder="Place some text here"><?=$Impression ?></textarea>
			</div>
		</div>
        <hr/>
		<div class="row">
			<div class="col-md-6">
				<div class="form-group">
					<label>Press Here to Save Data</label>
					<button id="updatereport"  type="button" class="btn btn-primary">Update</button>
				</div>
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
			var group_id=$('#input_Reporttitle').val();
			var HTMLData=CKEDITOR.instances.HTMLData.getData();
            var Impression=$('#Impression').val();
			var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
			
			if(repo_id>0)
			{
				$.post('/index.php/Lab_Admin/report_ultrasound_update',{ 
					"repo_id": repo_id, 
					"input_Reportname": input_Reportname,
					"charge_id":charge_id,
					"group_id":group_id,
					"HTMLData":HTMLData,
                    "Impression":Impression,
					'<?=$this->security->get_csrf_token_name()?>':csrf_value
					}, function(data){
					$('#msgshow').html(data.showcontent);
					$("#alert_show").alert();
					$("#alert_show").fadeTo(5000, 500).slideUp(500, function(){
						$("#alert_show").slideUp(500);
						});
				}, 'json');
			}else{
				$.post('/index.php/Lab_Admin/report_ultrasound_insert',{ 
					"repo_id": repo_id, 
					"input_Reportname": input_Reportname,
					"charge_id":charge_id,
					"group_id":group_id,
					"HTMLData":HTMLData,
                    "Impression":Impression,
					'<?=$this->security->get_csrf_token_name()?>':csrf_value
					}, function(data){
					$('#msgshow').html(data.showcontent);
					$("#alert_show").alert();
					$("#alert_show").fadeTo(5000, 500).slideUp(500, function(){
						$("#alert_show").slideUp(500);
						});
					if(data.insertid>0)
					{
						load_form_div('/Lab_Admin/report_ultrasound_list','maindiv','Diagnosis Template');
					}
					
					
				}, 'json');
			}

        });

</script>