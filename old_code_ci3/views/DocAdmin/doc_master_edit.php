<?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
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
			<div class="col-md-4">
				<div class="form-group">
					<label>Document Name</label>
					<?php 
					$doc_name='';
					$doc_desc='';
					$df_id='0';
					$HTMLData='';
					if (count($doc_master)>0)
					{
						$doc_name=$doc_master[0]->doc_name;
						$df_id=$doc_master[0]->df_id;
						$doc_desc=$doc_master[0]->doc_desc;
						$HTMLData=$doc_master[0]->doc_raw_format;
					}
					?>
					<input class="form-control" id="input_docname" name="input_docname" placeholder="Documnet Name" type="text" value="<?=$doc_name?>" />
					<input type="hidden" id="df_id" value="<?=$df_id?>">
				</div>
			</div>
			<div class="col-md-8">
				<div class="form-group">
					<label>Description</label>
					<input class="form-control" id="input_doc_desc" name="input_doc_desc" placeholder="Description" type="text" value="<?=$doc_desc?>" />
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
	  <h3 class="box-title">Document Parameter</h3>
	  <div class="pull-right box-tools">
		<button type="button" class="btn btn-default btn-sm" data-widget="collapse" data-toggle="tooltip" title="Collapse">
		  <i class="fa fa-minus"></i></button>
	  </div>
	</div>
	<!-- /.box-header -->
	<div class="box-body" >
		<div class="row">
			<div class="col-md-12">
				Pre-Define
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<?php for ($i = 0; $i < count($doc_pre_input); ++$i) 
				{ 
					echo '<div  style="margin:2px ;display:inline-block;color:'.$color_name[($doc_pre_input[$i]->id)%50]->code_code_2.';"><i>'.$doc_pre_input[$i]->input_name.'</i>['.$doc_pre_input[$i]->input_code.'] </div>';
				}
				?>
			</div>
		</div>
		<div class="row">
			<hr/>
		</div>
		<div class="row">
			<div class="col-md-12">
				Custom Define Input Parameter
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<?php for ($i = 0; $i < count($doc_Item_List); ++$i) 
				{ 
					echo '<div  style="margin:2px ;display:inline-block;color:'.$color_name[($doc_Item_List[$i]->item_id)%50]->code_code_2.';"><i>'.$doc_Item_List[$i]->input_name.'</i>['.$doc_Item_List[$i]->input_code.'] </div>';
				}
				?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<button onclick="load_form_div('/Doc_Admin/doc_input_list/<?=$df_id ?>','test_div');" type="button" class="btn btn-primary">Document Input List</button>
			</div>
		</div>
	</div>
</div>
<?php echo form_close(); ?>
<script>
	$('#updatereport').click(function(){
			var df_id = $('#df_id').val();
            var input_docname = $('#input_docname').val();
			var input_doc_desc=$('#input_doc_desc').val();
			var HTMLData=CKEDITOR.instances.HTMLData.getData();
			var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
			
			if(df_id>0)
			{
				$.post('/index.php/Doc_Admin/report_update',{ 
					"df_id": df_id, 
					"input_docname": input_docname,
					"input_doc_desc":input_doc_desc,
					"HTMLData":HTMLData,
					"<?=$this->security->get_csrf_token_name()?>":csrf_value
					}, function(data){
					$('#msgshow').html(data.showcontent);
					$("#alert_show").alert();
					$("#alert_show").fadeTo(5000, 500).slideUp(500, function(){
						$("#alert_show").slideUp(500);
						});
				}, 'json');
			}else{
				$.post('/index.php/Doc_Admin/report_insert',{ 
					"df_id": df_id, 
					"input_docname": input_docname,
					"input_doc_desc":input_doc_desc,
					"HTMLData":HTMLData,
					"<?=$this->security->get_csrf_token_name()?>":csrf_value
					}, function(data){
					$('#msgshow').html(data.showcontent);
					$("#alert_show").alert();
					$("#alert_show").fadeTo(5000, 500).slideUp(500, function(){
						$("#alert_show").slideUp(500);
						});
					if(data.insertid>0)
					{
						load_form('/Doc_Admin/doc_list');
					}
					
					
				}, 'json');
			}

        });

</script>