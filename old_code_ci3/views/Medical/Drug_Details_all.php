<div class="col-md-12">
		<div class="box">
            <div class="box-header">
              <h3 class="box-title">Drug Details</h3>
            </div>
            <div class="box-body">
				<table id="example1" class="table table-bordered table-striped TableData">
				<thead>
					<tr>
						<th>Item Code</th>
						<th>Item Name</th>
						<th>Dosage</th>
						<th>Formulation</th>
						<th>Therapeutic</th>
						<th>subtherapeutic</th>
						<th>MRF Name</th>
						<th>MRP</th>
					</tr>
					</thead>
					<thead>
					<?php for ($i = 0; $i < count($drug_data); ++$i) { ?>
					<tr>
						<td><?=$drug_data[$i]->item_id ?></td>
						<td><?=$drug_data[$i]->itemname ?></td>
						<td><?=$drug_data[$i]->dosage ?></td>
						
					</tr>
					<?php } ?>
					</thead>
				<tbody></tbody>
			</table>
			</div>
			<div class="box-footer">
				<button type="button" id="btn-add_new"  class="btn btn-primary">Add New</button>
			</div>
		</div>
	</div>
<!-- /.content -->
<script type="text/javascript" language="javascript" >
			$(document).ready(function() {
				$( "#btn-add_new" ).click(function() {
					load_form_div('/Medical/AddDrugStock/IC0000000','maindiv');
				});
			
			});		
</script>