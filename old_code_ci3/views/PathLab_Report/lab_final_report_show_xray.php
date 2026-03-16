
<?php echo form_open(); ?>
<input type="hidden" id="hid_value_req_id" value="<?=$report_format[0]->id ?>" />
	
	<div class="col-md-8">
		<div class="row">
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
				<label>Impression</label>
				<textarea id='report_data_Impression' name="report_data_Impression" class="form-control"  placeholder="Place some text here"><?=$report_format[0]->report_data_Impression ?></textarea>
				
			</div>
			<div class="col-md-12">
				<button onclick="update_report()" type="button" class="btn btn-primary">Save</button>
				<button onclick="report_final()" type="button" class="btn btn-primary">Verified</button>
			</div>
		</div>
	</div>
	<div class="col-md-4">
		<div class="row">
			<?php foreach($radiology_ultrasound_template as $row) { ?>
				<a href="javascript:set_template(<?=$row->id?>)"><?=$row->template_name?></a><br/>
			<?php } ?>
		</div>
	</div>
	

<?php echo form_close(); ?>
<script>
	function update_report()
	{
		var HTMLData=CKEDITOR.instances.HTMLShow.getData();
		var report_data_Impression=$('#report_data_Impression').val()
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
		
		var req_id=$('#hid_value_req_id').val();
		
		$.post('/index.php/Lab_Report/Final_Update_xray/'+req_id,
		{ 'HTMLData': HTMLData,
			'report_data_Impression':report_data_Impression,
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
				$.post('/index.php/Lab_Report/confirm_report_xray/'+req_id,
				{ "req_id": req_id,
					'<?=$this->security->get_csrf_token_name()?>':csrf_value
 				}, function(data){
				alert(data);
				});
			}
		
	}

	function set_template(template_id)
	{
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
		$.post('/index.php/Lab_Report/get_template_xray/'+template_id,
		{ "template_id": template_id,
			'<?=$this->security->get_csrf_token_name()?>':csrf_value
		}, function(data){
			CKEDITOR.instances.HTMLShow.setData(data.Findings);
			$('#report_data_Impression').val(data.Impression);
		},'json');
	}


</script>