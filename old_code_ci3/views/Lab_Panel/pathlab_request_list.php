<section class="content-header">
<input type="hidden" id="lab_type" name="lab_type" value="<?=$lab_type ?>" />
<?php echo form_open('Patient/search', array('role'=>'form','class'=>'form2')); ?>
<div class="row">
	<div Class="col-xs-3">			
		<div class="input-group input-group-sm">
				<input class="form-control" type="text" id="txtsearch" name="txtsearch" placeholder="Invoice Last 6 digit ,UHID,Phone No.">
					<span class="input-group-btn">
					<button type="submit" class="btn btn-info btn-flat" >Search Invoice</button>
				</span>
		</div>
	</div>
	<div Class="col-xs-3">
		<div class="input-group input-group-sm">
		<input class="form-control" type="text" id="txtsearch_srno" name="txtsearch_srno" placeholder="Daily Serial No.">
				<span class="input-group-btn">
				<button type="button" id="btn_srno" class="btn btn-info btn-flat">Search by Sr. No.</button>
			</span>
		</div>
	</div>
	<div Class="col-xs-3">
		<div class="input-group input-group-sm">
		<input class="form-control" type="text" id="txtsearch_labno" name="txtsearch_labno" placeholder="Lab No.">
				<span class="input-group-btn">
				<button type="button" id="btn_labno" class="btn btn-info btn-flat">Search By Lab No. </button>
			</span>
		</div>
	</div>
	<div Class="col-xs-3">
		<div class="input-group input-group-sm">
			<?php  if($lab_type==5){  ?>
				<a href="javascript:load_form_div('/Lab_Admin/report_list','searchresult','Diagnosis Template');" class="btn btn-primary btn-flat">Template</a>
			<?php } else  { ?>
				<a href="javascript:load_form_div('/Lab_Admin/report_ultrasound_list/<?php echo $lab_type; ?>','searchresult','Mri Template');" class="btn btn-primary btn-flat">Template</a>
			<?php } ?>
		</div>
	</div>
</div>
<?php echo form_close(); ?>
</section>
<section class="content-body">
<div class="searchresult" id="searchresult"></div>
</section>
 <script>
	 $(document).ready(function(){
			$('form.form2').on('submit', function(form){
				form.preventDefault();
				var lab_type=$('#lab_type').val()
				$.post('/index.php/Lab_Report/search_lab_4/'+lab_type, $('form.form2').serialize(), function(data){
					$('div.searchresult').html(data);
					$('#example1').DataTable();
				});
			});
	 });

	$('#btn_srno').click( function()
	{
		var lab_type=$('#lab_type').val()
		var txtsearch_srno= $('#txtsearch_srno').val();

		$.post('/index.php/Lab_Report/search_lab_4_srno/'+lab_type, 
		{
			"txtsearch_srno": txtsearch_srno,
			'<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>'
		}, function(data){
			$('div.searchresult').html(data);
			$('#example1').DataTable();
		});

	});

	$('#btn_labno').click( function()
	{
		var lab_type=$('#lab_type').val()
		var txtsearch_labno= $('#txtsearch_labno').val();

		$.post('/index.php/Lab_Report/search_lab_4_labno/'+lab_type, 
		{
			"txtsearch_labno": txtsearch_labno,
			'<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>'
		}, function(data){
			$('div.searchresult').html(data);
			$('#example1').DataTable();
		});

	});		
 </script>