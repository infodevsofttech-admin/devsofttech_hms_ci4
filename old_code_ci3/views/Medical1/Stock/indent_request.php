<section class="content-header">

<?php echo form_open('Patient/search', array('role'=>'form','class'=>'form2')); ?>
	<div class="input-group input-group-sm">
		<input class="form-control" type="text" id="txtsearch" name="txtsearch">
			<span class="input-group-btn">
			<button type="submit" class="btn btn-info btn-flat">Search Indent Request</button>
			</span>
			<span class="input-group-btn">

			</span>
			<span class="input-group-btn">
				
			</span>
	</div>
<?php echo form_close(); ?>
</section>
<div class="searchresult" id="searchresult"></div>
 <script>
	 $(document).ready(function(){
			$('form.form2').on('submit', function(form){
				form.preventDefault();
				
				$.post('/index.php/Stock/Indent_Request_Search', $('form.form2').serialize(), function(data){
					$('div.searchresult').html(data);
					$('#example1').DataTable();
				});
			});
	 });
 </script>