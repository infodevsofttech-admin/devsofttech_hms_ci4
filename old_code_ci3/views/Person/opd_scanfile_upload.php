<div class="row">
	<div class="col-md-12">
		<div class="form-group">
			<div id="multiple_file_uploader">Upload</div>
			<input type="hidden" id="opdid" value="<?=$opdid ?>">
			
			<script>
				$(document).ready(function()
				{
					$("#multiple_file_uploader").uploadFile({
						fileName : "myfile",
						url : "Opd/opd_scanfiles_upload/"+$('#opdid').val(),
						multiple : true,
						maxFileCount : 5,
						allowedTypes : "jpg,jpeg,png,gif,pdf,tiff,docx,doc",
						showProgress : true,
						formData: [{
                			name: '<?php echo $this->security->get_csrf_token_name(); ?>',
                			value: '<?php echo $this->security->get_csrf_hash(); ?>'
            				}]
					});
				});
			</script>
		</div>
	</div>
</div>

