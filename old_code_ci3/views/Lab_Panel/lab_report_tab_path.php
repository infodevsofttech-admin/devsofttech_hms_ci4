<br />
<div class="row">
	<div class="col-md-12">
		<div class="box">
		<div class="box-header">
		  <h3 class="box-title">Pathology</h3>
		</div>
		<!-- /.box-header -->
		<div class="box-body" >
		  <table id="datashow1" class="table table-bordered table-striped TableData">
			<thead>
			<tr>
			  <th>Invoice No.</th>
			  <th>Day Sr.No.</th>
			  <th>Lab Test No.</th>
			  <th>Person Name</th>
			  <th>Date</th>
			  <th>Tests Name </th>
			  <th>Action</th>
			</tr>
			</thead>
			<tbody>
			<?php for ($i = 0; $i < count($labreport_preprocess); ++$i) { ?>
			<tr>
			  <td><?=$labreport_preprocess[$i]->invoice_code ?></td>
			  <td><?=$labreport_preprocess[$i]->daily_sr_no ?></td>
			  <td><?=$labreport_preprocess[$i]->lab_test_no ?></td>
			  <td><?=$labreport_preprocess[$i]->inv_name ?> | <?=$labreport_preprocess[$i]->age ?></td>
			  <td><?=$labreport_preprocess[$i]->inv_date ?></td>
			  <td>
			  <?php
						$data_array=explode("#",$labreport_preprocess[$i]->data_array);
						echo '<p> ';
						foreach ($data_array as $value) {
							$value_array=explode(";",$value);

							if($value_array[3]<1)
							{
								echo '<span class="label label-danger">'.$value_array[0].'</span> ';
							}else{
								if($value_array[4]>0)
								{
									echo '<span class="label label-success">'.$value_array[0].'</span> ';
								}else{
									echo '<span class="label label-warning">'.$value_array[0].'</span> ';
								}
							}
						}
						echo '</p>';
				?>
			  </td>
			  <td>
					<button  type="button" class="btn btn-primary" onclick="show_record(<?=$labreport_preprocess[$i]->inv_id ?>)" >Select</button>
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
			  <th>Action</th>
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
  
    $('#datashow1').DataTable({
      'paging'      : true,
      'lengthChange': false,
      'searching'   : true,
      'ordering'    : true,
      'info'        : true,
      'autoWidth'   : true,
	  'order': [[0, 'desc']]
    })

	function show_record(inv_id)
	{
			var lab_type=$('#lab_type').val();
					
			load_form_div('/Lab_Report/select_lab_invoice_path/'+inv_id+'/'+lab_type,'searchresult');
			
	}
	
	
				
				
</script>