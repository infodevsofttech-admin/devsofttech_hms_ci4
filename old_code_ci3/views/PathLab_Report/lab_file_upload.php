<?php echo form_open(); ?>
<div class="row">
	<div class="col-md-12">
		<div class="form-group">
			<div id="multiple_file_uploader">Upload</div>
			<input type="hidden" id="repo_id" value="<?=$repo_id ?>">
			<input type="hidden" id="test_id" value="<?=$test_id ?>">
			<script>
				$(document).ready(function()
				{
					$("#multiple_file_uploader").uploadFile({
						fileName : "myfile",
						url : "Lab_Admin/lab_files_upload/"+$('#repo_id').val()+"/"+$('#test_id').val(),
						multiple : true,
						maxFileCount : 5,
						allowedTypes : "jpg,jpeg,png,gif,pdf,tiff,docx,doc",
						showProgress : true,
						formData: {'<?=$this->security->get_csrf_token_name()?>':$('input[name=<?=$this->security->get_csrf_token_name()?>]').val()}
					});
				});
			</script>
		</div>
	</div>
</div>
<?php echo form_close(); ?>

