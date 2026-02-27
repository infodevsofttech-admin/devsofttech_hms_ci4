<?php echo form_open(); ?>
<div class="box">
		<div class="box-header">
		  <h3 class="box-title">Test Parameter</h3>
		  <button onclick="load_form_div('/Lab_Admin/test_parameter_load/0/<?=$repo_id?>','test_div');" type="button" class="btn btn-primary">Add New Test</button>
			<input type="hidden" id="repo_id" value="<?=$repo_id?>" /> 
		</div>
		<!-- /.box-header -->
		<div class="box-body" >
			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label>Name of Test</label>
						<input class="form-control" id="input_Test_name" placeholder="Test Name"  type="text"  autocomplete="off">
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-group">
						<label> </label>
						<button type="button" class="btn btn-primary" id="btn_item_search">Search</button>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12" id="search_result">
					
				</div>
			</div>
		</div>
</div>
<?php echo form_close(); ?>
<script>
		$('#btn_item_search').click(function(){
			var input_Test_name = $('#input_Test_name').val();
            var repo_id = $('#repo_id').val();
			var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
			
			$.post('/index.php/Lab_Admin/test_item_search',{ 
					"input_Test_name": input_Test_name,"repo_id": repo_id,
					'<?=$this->security->get_csrf_token_name()?>':csrf_value
					}, function(data){
					$('#search_result').html(data);
				});
        });

		function add_test(repo_id,test_id)
		{
			var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
			
			$.post('/index.php/Lab_Admin/add_test_repo/'+repo_id+'/'+test_id,{ 
					"repo_id": repo_id,
					'<?=$this->security->get_csrf_token_name()?>':csrf_value
					}, function(data){
					$('#msgshow').html(data.showcontent);
					$("#alert_show").alert();
					$("#alert_show").fadeTo(5000, 500).slideUp(500, function(){
						$("#alert_show").slideUp(500);
						});
					if(data.insertid>0)
					{
						load_form_div('/Lab_Admin/report_test_list/'+repo_id,'test_div');
					}
					
				}, 'json');
		}
		

		

</script>
