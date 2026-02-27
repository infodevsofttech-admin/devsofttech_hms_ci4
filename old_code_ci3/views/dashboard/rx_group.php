<section class="content-header">
	<h1>
		Rx-Group
	</h1>
	<ol class="breadcrumb">
		<li><a href="javascript:load_form('/Opd_prescription/rx_opd_medicine')"><i class="fa fa-dashboard"></i> Add Medicine</a></li>
	</ol>
</section>
<section class="content">
	<div class="row">
		<div class="col-md-4">
			<div class="box">
				<div class="box-header">

					<div class="col-md-6">
						<h3 class="box-title">Rx-Group List List</h3>
					</div>
					<div class="col-md-2"></div>
					<div class="col-md-2">
						<button onclick="load_form_div('/Opd_prescription/new_rx_group/0','test_div');" type="button" class="btn btn-primary">Add New Rx-Group</button>
					</div>

				</div>
				<!-- /.box-header -->
				<div id="supplier_list" class="box-body" style="height:500px;overflow-y:auto;">
					<table id="report_list" class="table table-bordered table-striped TableData">
						<thead>
							<tr>
								<th>Rx-Group Name</th>
								<th>Action</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($rx_master as $row) { ?>
								<tr>
									<td><?= $row->rx_group_name ?></td>
									<td>
										<button onclick="load_form_div('/Opd_prescription/save_rx_group_edit/<?= $row->id ?>','test_div');" type="button" class="btn btn-primary">Edit</button>
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
				</div>
				<!-- /.box-body -->
			</div>
		</div>
		<div class="col-md-8" id="test_div">

		</div>
	</div>
	</div>
</section>