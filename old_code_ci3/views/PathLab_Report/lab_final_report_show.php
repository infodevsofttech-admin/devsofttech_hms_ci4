<?php echo form_open(); ?>

<input type="hidden" id="hid_value_req_id" value="<?=$report_format[0]->id ?>" />
	<div class="col-md-12">
	<label>Report</label>
		<textarea id='HTMLShow' name="HTMLShow"  placeholder="Place some text here">
			<?=$report_format[0]->Report_Data ?>
		</textarea>
		<script>
			CKEDITOR.replace( 'HTMLShow' );
		</script>
	</div>
	<div class="col-md-12">
		<hr/>
	</div>
	<div class="col-md-12">
		<button onclick="update_report()" type="button" class="btn btn-primary">Save</button>
		<button onclick="report_final()" type="button" class="btn btn-primary">Verified</button>
	</div>

<?php echo form_close(); ?>
<script>
	function update_report()
	{
		var HTMLData=CKEDITOR.instances.HTMLShow.getData();
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
		
		var req_id=$('#hid_value_req_id').val();
		
		$.post('/index.php/Lab_Admin/Final_Update/'+req_id,
		{ "HTMLData": HTMLData,
			'<?=$this->security->get_csrf_token_name()?>':csrf_value
 		}, function(data){
			alert(data);
			});
	}
	
	function report_final()
	{
		var req_id=$('#hid_value_req_id').val();
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
		
		if(confirm("Are you sure you want to Confirm"))
			{
				$.post('/index.php/Lab_Admin/confirm_report/'+req_id,
				{ "req_id": req_id,
					'<?=$this->security->get_csrf_token_name()?>':csrf_value
 				}, function(data){
				alert(data);
				});
			}
		
	}


</script>