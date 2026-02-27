<section class="content-header">
    <h1>
        Document Template List
    </h1>
</section>
<section class="content">
<div class="row">
	<div class="col-md-6">
		<div class="box">
		<div class="box-header">
		  
			<div class="col-md-2"><h3 class="box-title">Template List</h3></div>
			<div class="col-md-6"></div>
			<div class="col-md-2"><button onclick="load_form_div('/Doc_Admin/docedit_load/0','test_div');" type="button" class="btn btn-primary">Add New Report</button></div>
		</div>
		<!-- /.box-header -->
		<div class="box-body" style="height:500px;overflow-y:auto;">
		  <table id="report_list" class="table table-bordered table-striped TableData">
			<thead>
			<tr>
			  <th>Title</th>
			  <th>Description</th>
			  <th>Action</th>
			 </tr>
			</thead>
			<tbody>
			<?php for ($i = 0; $i < count($doc_master); ++$i) { ?>
			<tr>
			  <td><?=$doc_master[$i]->doc_name ?></td>
			  <td><?=$doc_master[$i]->doc_desc ?></td>
			  <td>
				  <button onclick="load_form_div('/Doc_Admin/doc_input_list/<?=$doc_master[$i]->df_id ?>','test_div');" type="button" class="btn btn-primary">Input List</button>
				  <button onclick="load_form_div('/Doc_Admin/docedit_load/<?=$doc_master[$i]->df_id ?>','test_div');" type="button" class="btn btn-primary">Edit</button>
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