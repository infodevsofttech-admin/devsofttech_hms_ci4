<section class="content-header">
    <h1>
        Ultra Sound Template
    </h1>
</section>
<section class="content">
<div class="row">
	<div class="col-md-6">
		<div class="box">
		<div class="box-header">
		  
			<div class="col-md-2"><h3 class="box-title">Report List</h3></div>
			<div class="col-md-6"></div>
			<div class="col-md-2"><button onclick="load_form_div('/Lab_Admin/reportedit_ultrasound_load/<?=$modality?>/0','test_div');" type="button" class="btn btn-primary">Add New Report</button></div>
			
		</div>
		<!-- /.box-header -->
		<div class="box-body" style="height:500px;overflow-y:auto;">
		  <table id="report_list" class="table table-bordered table-striped TableData">
			<thead>
			<tr>
			  <th>Report Title</th>
			  <th>Group</th>
			  <th>Action</th>
			 </tr>
			</thead>
			<tbody>
			<?php for ($i = 0; $i < count($labReport_master); ++$i) { ?>
			<tr>
			  <td><?=$labReport_master[$i]->template_name ?></td>
			  <td><?=$labReport_master[$i]->title ?></td>
			  <td>
				  <button onclick="load_form_div('/Lab_Admin/reportedit_ultrasound_load/<?=$modality?>/<?=$labReport_master[$i]->id ?>','test_div');" type="button" class="btn btn-primary">Edit</button>
			  </td>
			</tr>
			<?php } ?>
			</tbody>
			<tfoot>
			<tr>
				<th>Report Title</th>
				<th>Group</th>
				<th>Action</th>
			</tr>
			</tfoot>
		  </table>
		 
		</div>
		<!-- /.box-body -->
		</div>
		</div>
	<div class="col-md-6" id="test_div">
		
	</div>
	</div>
</div>
</section>
 <script>
			$('#report_list').dataTable();
		  </script>