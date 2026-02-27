<div id="report_list" class="box-body" style="height:500px;overflow-y:auto;">
		  <table id="report_list" class="table table-bordered table-striped TableData">
			<thead>
				<tr>
				  <th>Category Name</th>
				  <th>Action</th>
				 </tr>
			</thead>
			<tbody>
			<?php for ($i = 0; $i < count($med_product_cat_master); ++$i) { ?>
			<tr>
			  <td><?=$med_product_cat_master[$i]->med_cat_desc ?></td>
			  <td>
				  <button onclick="load_form_div('/Product_master/medicine_category_edit/<?=$med_product_cat_master[$i]->id ?>','test_div');" type="button" class="btn btn-primary">Edit</button>
			  </td>
			</tr>
			<?php } ?>
			</tbody>
			<tfoot>
			<tr>
				<th>Company Name</th>
                <th>Action</th>
			</tr>
			</tfoot>
		  </table>