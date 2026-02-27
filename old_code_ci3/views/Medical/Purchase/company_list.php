<section class="content-header">
    <h1>
        Company
    </h1>
</section>
<section class="content">
<div class="row">
	<div class="col-md-6">
		<div class="box">
		<div class="box-header">
		  
			<div class="col-md-6"><h3 class="box-title">Company List</h3></div>
			<div class="col-md-2"></div>
			<div class="col-md-2">
				<button onclick="load_form_div('/Product_master/CompanyEdit/0','test_div');" type="button" class="btn btn-primary">Add New</button></div>
			
		</div>
		<!-- /.box-header -->
		<div id="supplier_list" class="box-body" style="height:500px;overflow-y:auto;">
		  <table id="report_list" class="table table-bordered table-striped TableData">
			<thead>
				<tr>
				  <th>Company Name</th>
				  <th>Person/Phone</th>
				  <th>Action</th>
				 </tr>
			</thead>
			<tbody>
			<?php for ($i = 0; $i < count($med_company); ++$i) { ?>
			<tr>
			  <td><?=$med_company[$i]->company_name ?></td>
			  <td><?=$med_company[$i]->contact_person_name ?>/<?=$med_company[$i]->contact_phone_no ?></td>
			  <td>
				  <button onclick="load_form_div('/Product_master/CompanyEdit/<?=$med_company[$i]->id ?>','test_div');" type="button" class="btn btn-primary">Edit</button>
			  </td>
			</tr>
			<?php } ?>
			</tbody>
			<tfoot>
			<tr>
				<th>Company Name</th>
				  <th>Person/Phone</th>
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
	<div class="col-md-6" id="test_div">
		
	</div>
	</div>
</div>
</section>
 