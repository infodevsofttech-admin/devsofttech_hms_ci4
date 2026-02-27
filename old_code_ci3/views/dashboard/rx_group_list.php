<table id="report_list" class="table table-bordered table-striped TableData">
			<thead>
				<tr>
				  <th>Rx-Group Name</th>
    			  <th>Action</th>
				 </tr>
			</thead>
			<tbody>
			<?php foreach($rx_master as $row) { ?>
			<tr>
			  <td><?=$row->rx_group_name ?></td>
			  <td>
				  <button onclick="load_form_div('/Opd_prescription/save_rx_group_edit/<?=$row->id ?>','test_div');" type="button" class="btn btn-primary">Edit</button>
			  </td>
			</tr>
			<?php } ?>
			</tbody>
			<tfoot>
			<tr>
                <th>Rx-Group Name</th>
    			<th>Action</th>
			</tr>
			</tfoot>
		  </table>
		 <script>
			$('#report_list').dataTable();
		  </script>