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
				<th>Name</th>
				<th>GST</th>
				<th>Action</th>
			</tr>
			</tfoot>
		  </table>