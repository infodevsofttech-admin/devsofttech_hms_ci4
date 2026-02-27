<section class="content-header">
<?php echo form_open('Patient/search', array('role'=>'form','class'=>'form2')); ?>
	<div class="input-group input-group-sm">
		<input class="form-control" type="text" id="txtsearch" name="txtsearch">
			<span class="input-group-btn">
			<button type="submit" class="btn btn-info btn-flat">Search Indent Counter</button>
			</span>
			<span class="input-group-btn">
			
			</span>
			<div class="input-group-btn">
                  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">New Indent For Counter
                    <span class="fa fa-caret-down"></span></button>
                  <ul class="dropdown-menu">
                    <?php
						foreach($store_master as $row)
						{
					?>
					<li><a href="javascript:load_form_div('/Stock/Create_Indent_counter/<?=$row->store_id?>','searchresult');"><?=$row->store_name?></a></li>
                    <?php
						}
					?>
                  </ul>
                </div>
			
	</div>
<?php echo form_close(); ?>
</section>
<div class="searchresult" id="searchresult"></div>
 <script>
	 $(document).ready(function(){
			$('form.form2').on('submit', function(form){
				form.preventDefault();
				
				$.post('/index.php/Stock/Indent_counter_Search', $('form.form2').serialize(), function(data){
					$('div.searchresult').html(data);
					$('#example1').DataTable();
				});
			});
	 });
 </script>