<section class="content-header">

<?php echo form_open('Org_Packing/Search_packing', array('role'=>'form','class'=>'form2')); ?>
	
			<div class="input-group input-group-sm">
				<input class="form-control" type="text" id="txtsearch" name="txtsearch">
					<span class="input-group-btn">
					<button type="submit" class="btn btn-info btn-flat">Search Org. Packing</button>
					</span>

					<span class="input-group-btn">
						 
					</span>
					<span class="input-group-btn">
						<button onclick="load_form_div('/Org_Packing/PackingNew','searchresult');" type="button" class="btn btn-warning btn-flat" >New Org. Packing</button>
					</span>
			</div>
<?php echo form_close(); ?>
</section>
<div class="searchresult" id="searchresult"></div>
 <script>
	 $(document).ready(function(){
			$('form.form2').on('submit', function(form){
				form.preventDefault();
				
				$.post('/index.php/Org_Packing/Search_packing', $('form.form2').serialize(), function(data){
					$('div.searchresult').html(data);
					$('#example1').DataTable();
				});
			});
	 });
 </script>