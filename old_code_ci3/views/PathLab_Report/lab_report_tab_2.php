<div class="row">
	<div class="col-md-12">
		<div class="box">
		<div class="box-header">
		  <h3 class="box-title">Report List</h3>
		</div>
		<!-- /.box-header -->
		<div class="box-body" style="height:500px;overflow-y:auto;">
		  <table id="datashow2" class="table table-bordered table-striped TableData">
			<thead>
			<tr>
			  <th>Invoice No./Date</th>
			  <th>Person Name</th>
			  <th>Test Name</th>
			 </tr>
			</thead>
			<tbody>
			<?php for ($i = 0; $i < count($labreport_preprocess); ++$i) { ?>
			<tr>
				<td><?=$labreport_preprocess[$i]->invoice_code ?>
				<br/>
				<?=$labreport_preprocess[$i]->inv_date ?>
				</td>
				<td><?=$labreport_preprocess[$i]->inv_name ?></td>
				<td>
				<?php
						$data_array=explode("#",$labreport_preprocess[$i]->data_array);
						echo '<table style="border-collapse: separate; border-spacing: 10px;">';
						foreach ($data_array as $value) {
							echo '<tr>';
							$value_array=explode(";",$value);
							echo '<td>'.$value_array[0].'</td>';
							echo '<td><button  type="button" class="btn btn-primary" data-toggle="modal" data-target="#tallModal" 
							data-testid="'.$value_array[2].'" data-testname="'.$value_array[0].'" data-etype="1">Entry</button></td>';
							
							echo '<td><button  type="button" 	class="btn btn-primary" data-toggle="modal"
							data-target="#tallModal" 
							data-repoid="'.$value_array[2].'" 
							data-testid="'.$value_array[3].'" 
							data-testname="'.$value_array[0].'" 
							data-etype="2">Scan</button></td>
							';
							
							echo '<td><button  type="button" 	class="btn btn-primary" data-toggle="modal"
							data-target="#tallModal" 
							data-repoid="'.$value_array[2].'" 
							data-testid="'.$value_array[3].'" 
							data-testname="'.$value_array[0].'" 
							data-etype="3">Upload Files</button></td>
							';
					
							
							echo '<td><button  type="button" 	class="btn btn-primary" data-toggle="modal"
							data-target="#tallModal" 
							data-repoid="'.$value_array[2].'" 
							data-testid="'.$value_array[3].'" 
							data-testname="'.$value_array[0].'" 
							data-etype="4">Show Files</button></td>
							';
							echo '</tr>';
						}
						echo '</table>';
				?>
				</td>
			</tr>
			<?php } ?>
			</tbody>
			<tfoot>
			<tr>
			  <th>Invoice No./Date</th>
			  <th>Person Name</th>
			  <th>Test Name</th>
			</tr>
			</tfoot>
		  </table>
		  
		</div>
		<!-- /.box-body -->
		</div>
	</div>
	
</div>
<div class="modal modal-wide fade" id="tallModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="testentryLabel">Test Name</h4>
      </div>
      <div class="modal-body">
        <div class="row">
			<div class="testentry-bodyc" id="testentry-bodyc">
					
				</div>
			</div>
		</div>
      </div>
      
    </div>
  </div>
<script>

	$('#datashow2').dataTable();
	
	
	
		
	function update_request(req_id)
	{
			$.post('/index.php/Lab_Admin/lab_tab_2_process/'+req_id,{ "test_id": req_id }, function(data){
			$('#testentry_div').html(data);
			});
	}

	$('#tallModal').on('shown.bs.modal', function (event) {
		$('.testentry-bodyc').html('');
		
		var lab_type=$('#lab_type').val();
		
		var height = $(window).height() - 50;
		$(this).find(".modal-body").css("max-height", height);

		var button = $(event.relatedTarget);
		// Button that triggered the modal
		var testid = button.data('testid');
		var testname = button.data('testname');
		var etype = button.data('etype');

		$('#testentryLabel').html(testname);
		
		if(etype=='1')
		{
			$.post('/index.php/Lab_Admin/lab_tab_2_process/'+testid,{ "test_id": testid }, function(data){
			$('#testentry-bodyc').html(data);
			});
		}
		
		if(etype=='2')
		{
			var repoid = button.data('repoid');
			$.post('/index.php/Lab_Admin/lab_file_scan/'+repoid+'/'+testid,{ "test_id": testid }, function(data){
			$('#testentry-bodyc').html(data);
			});
		}
		
		if(etype=='3')
		{
			var repoid = button.data('repoid');
			$.post('/index.php/Lab_Admin/lab_file_upload/'+repoid+'/'+testid,{ "test_id": testid }, function(data){
			$('#testentry-bodyc').html(data);
			});
		}
		
		if(etype=='4')
		{
			var repoid = button.data('repoid');
			$.post('/index.php/Lab_Admin/lab_file_list/'+repoid+'/'+testid,{ "test_id": testid }, function(data){
			$('#testentry-bodyc').html(data);
			});
		}
		
	});
	
	$('#tallModal').on('hidden.bs.modal', function () {
		$('.testentry-bodyc').html('');
		$('#testentryLabel').html('');
		Webcam.reset();
		
	});
</script>