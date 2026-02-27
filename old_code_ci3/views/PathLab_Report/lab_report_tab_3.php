<div class="row">
	<div class="col-md-12">
		<div class="box">
		<div class="box-header">
		  <h3 class="box-title">Report List</h3>
		</div>
		<!-- /.box-header -->
		<div class="box-body" style="height:500px;overflow-y:auto;">
		  <table id="datashow3" class="table table-bordered table-striped TableData">
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
							echo '<td><button  type="button" class="btn btn-primary" data-toggle="modal" data-target="#tallModal_3" 
							data-testid="'.$value_array[2].'" data-testname="'.$value_array[0].'" data-etype="1">Report Edit</button></td>';
							echo '<td><button  type="button" 	class="btn btn-primary" data-toggle="modal"
							data-target="#tallModal_3" 
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
<div class="modal modal-wide fade" id="tallModal_3" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="tallModal_3Label"></h4>
      </div>
      <div class="modal-body">
        <div class="row">
			<div class="tallModal_3-bodyc" id="tallModal_3-bodyc">
					
				</div>
			</div>
		</div>
      </div>
      
    </div>
  </div>
<script>

	$('#datashow3').dataTable();
	
	$('#tallModal_3').on('shown.bs.modal', function (event) {
		$('.tallModal_3-bodyc').html('');
	
		var button = $(event.relatedTarget);
		// Button that triggered the modal
		var testid = button.data('testid');
		var testname = button.data('testname');

		$('#tallModal_3Label').html(testname);
		
		var etype = button.data('etype');
		  
		if(etype=='1')
		{
			$.post('/index.php/Lab_Admin/show_report_final/'+testid,{ "test_id": testid }, function(data){
			$('#tallModal_3-bodyc').html(data);
			});
		}
		
		if(etype=='4')
		{
			var repoid = button.data('repoid');
			$.post('/index.php/Lab_Admin/lab_file_list/'+repoid+'/'+testid,{ "test_id": testid }, function(data){
			$('#tallModal_3-bodyc').html(data);
			});
		}
	
	});
	
	$('#tallModal_3').on('hidden.bs.modal', function () {
		
		var lab_type=$('#lab_type').val();
		$('#tallModal_3-bodyc').html('');
		$('#tallModal_3Label').html('');
		load_form_div('/Lab_Admin/lab_tab_3/'+lab_type,'tab_3');
	});
	
</script>