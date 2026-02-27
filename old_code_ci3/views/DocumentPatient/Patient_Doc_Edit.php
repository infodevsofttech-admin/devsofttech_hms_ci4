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
<div class="box box-danger">
    <div class="box-header">
		<div class="box-title">
			<p><strong>Name :</strong><?=$person_info[0]->p_fname?>  {<i><?=$person_info[0]->p_rname ?></i>}
			<strong>/ Age :</strong><?=$person_info[0]->age?> 
			<strong>/ Gender :</strong><?=$person_info[0]->xgender?> 
			<strong>/ P Code :</strong><?=$person_info[0]->p_code?>
			</p>
			<input type="hidden" id="pid" name="pid" value="<?=$person_info[0]->id?>" />
			<input type="hidden" id="document_id" name="document_id" value="<?=$patient_doc[0]->id?>" />
        </div>
    </div>
	<div class="box-body">
		<div class="row">
			<div class="col-xs-12">
				<div id="showfile">
				<textarea id='HTMLData' name="HTMLData"  placeholder="Place some text here">
				<?=$patient_doc[0]->raw_data ?>
				</textarea>
				<script>
					CKEDITOR.replace( 'HTMLData' );
				</script>
				</div>
			</div>
		</div>
		
	</div>
	<div class="box-footer">
		<button id="updatereport"  type="button" class="btn btn-primary">Update</button>
		<button id="editreport"  type="button" class="btn btn-primary">Edit</button>
		<button id="Re_Create"  type="button" class="btn btn-danger">Re-Create</button>
		<button id="btn_show"  type="button" class="btn btn-primary">Print on Plain Paper</button>
		<button id="btn_show2"  type="button" class="btn btn-primary">Print on Letter Head</button>
	</div>
</div>
<?php echo form_close(); ?>
</section>
<script>
$('#updatereport').click(function(){
			var document_id = $('#document_id').val();
            var HTMLData=CKEDITOR.instances.HTMLData.getData();
            var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
			
			$.post('/index.php/Document_Patient/update_doc',{ 
					"document_id": document_id, 
					"HTMLData":HTMLData,
					"<?=$this->security->get_csrf_token_name()?>":csrf_value, 
					}, function(data){
					$('#msgshow').html(data.showcontent);
					$("#alert_show").alert();
					$("#alert_show").fadeTo(5000, 500).slideUp(500, function(){
						$("#alert_show").slideUp(500);
						});
				}, 'json');
        });

		$('#showreport').click(function(){
			var document_id = $('#document_id').val();
           	load_report_div('/Document_Patient/create_final/'+document_id+'/1','showfile');
        });
		
		$('#editreport').click(function(){
			var document_id = $('#document_id').val();
           	load_form('/Document_Patient/load_doc/'+document_id);
        });
		
		$('#btn_show').click(function(){
			var document_id = $('#document_id').val();
			
			var Get_Query='/index.php/Document_Patient/create_final/'+document_id+'/1';
			//load_report_div(Get_Query,'maindiv');
			window.open(Get_Query, "_blank");
        });

        $('#btn_show2').click(function(){
			var document_id = $('#document_id').val();
			var Get_Query='/index.php/Document_Patient/create_final/'+document_id+'/0';
			//load_report_div(Get_Query,'maindiv');
			window.open(Get_Query, "_blank");
        });

        $('#Re_Create').click(function(){
			var document_id = $('#document_id').val();
           	if(confirm('Are you sure Recreate Document'))
           	{
           		load_form('/Document_Patient/re_create_doc/'+document_id);
           	}
           	
        });
		
</script>
    