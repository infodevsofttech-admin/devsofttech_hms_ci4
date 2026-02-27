<div class="row">
	<div class="col-md-12">
		<div class="box">
		<div class="box-header">
		  <h3 class="box-title">Report List</h3>
		</div>
		<!-- /.box-header -->
		<div class="box-body" style="height:500px;overflow-y:auto;">
		  <table id="datashow1" class="table table-bordered table-striped TableData">
			<thead>
			<tr>
			  <th>Invoice No.</th>
			  <th>Person Name</th>
			  <th>Date</th>
			  <th>Tests Name </th>
			 </tr>
			</thead>
			<tbody>
			<?php for ($i = 0; $i < count($labreport_preprocess); ++$i) { ?>
			<tr>
			  <td><?=$labreport_preprocess[$i]->invoice_code ?></td>
			  <td><?=$labreport_preprocess[$i]->inv_name ?> / <?=$labreport_preprocess[$i]->age ?></td>
			  <td><?=$labreport_preprocess[$i]->inv_date ?></td>
			  <td>
			  <?php
						$data_array=explode("#",$labreport_preprocess[$i]->data_array);
						echo '<table style="border-collapse: separate; border-spacing: 10px;">';
						foreach ($data_array as $value) {
							echo '<tr>';
							$value_array=explode(";",$value);

							echo '<td><button onclick="update_request(\''.$value_array[2].'\')" 
							type="button" class="btn btn-primary">'.$value_array[0].'</button></td>';
							
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
			  <th>Invoice No.</th>
			  <th>Person Name</th>
			  <th>Date</th>
			  <th>Tests Name </th>
			 
			 </tr>
			</tfoot>
		  </table>
		</div>
		<!-- /.box-body -->
		</div>
	</div>
	</div>
<script>
$('#datashow1').dataTable();

	function update_request(test_id)
	{
			var lab_type=$('#lab_type').val();
		
			$.post('/index.php/Lab_Admin/lab_tab_1_process/'+test_id+'/'+lab_type,{ "test_id": test_id }, function(data){
			if(data>0)
			{
				alert('Data Saved');
			}
			load_form_div('/Lab_Admin/lab_tab_1/'+lab_type,'tab_1');
			});
	}
	
</script>