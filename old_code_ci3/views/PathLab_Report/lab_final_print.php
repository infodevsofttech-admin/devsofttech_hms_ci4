<?php echo form_open(); ?>
<div class="row">
<input type="hidden" id="hid_value_req_id" value="<?=$lab_invoice_request[0]->id ?>" />
	<div class="col-md-12">
	<label>Report</label>
		<textarea id='HTMLShow' name="HTMLShow"  placeholder="Place some text here">
			<?=$lab_invoice_request[0]->report_data ?>
		</textarea>
		<script>
			CKEDITOR.replace( 'HTMLShow' );
		</script>
	</div>
	<div class="col-md-12">
		<button onclick="update_report()" type="button" class="btn btn-primary">Save</button>
		
	</div>
</div>
<?php echo form_close(); ?>
<script>
	function update_report()
	{
		var HTMLData=CKEDITOR.instances.HTMLShow.getData();
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		var req_id=$('#hid_value_req_id').val();
		
		$.post('/index.php/Lab_Admin/Report_Final_print/'+req_id,
		{ "HTMLData": HTMLData,
		'<?=$this->security->get_csrf_token_name()?>':csrf_value }, 
		function(data){
			alert(data);
			});
	}
	
	


</script>