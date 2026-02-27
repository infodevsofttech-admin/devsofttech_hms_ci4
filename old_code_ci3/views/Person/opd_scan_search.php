
		<table id="example1" class="table table-bordered table-striped TableData">
		<thead>
		<tr>
		  <th>OPD Code</th>
		  <th>Name</th>
		  <th>Doctor Name</th>
		  <th>Phone Number</th>
		  <th>Action</th>
		</tr>
		</thead>
		<tbody>
		<?php for ($i = 0; $i < count($opd_list); ++$i) { ?>
		<tr>
		  <td><?=$opd_list[$i]->opd_code ?></br>[ <?=$opd_list[$i]->App_Date ?> ]</td>
		  <td><?=$opd_list[$i]->p_fname ?> </br> {<?=$opd_list[$i]->p_code ?>}</td>
		  <td><?=$opd_list[$i]->doc_name ?></td>
		  <td><?=$opd_list[$i]->mphone1 ?></td>
		  <td><button onclick="select_opd('<?=$opd_list[$i]->opd_id ?>');" type="button" class="btn btn-primary">Select</button></td>
		</tr>
		<?php } ?>
		</tbody>
		<tfoot>
		<tr>
		  <th>OPD Code</th>
		  <th>Name</th>
		  <th>Doctor Name</th>
		  <th>Phone Number</th>
		  <th>Action</th>
		</tr>
		</tfoot>
	  </table>

<script>
	function select_opd(opdid)
	{
		$('#opdid').val(opdid);
		load_form_div('/Opd/SelectOPD/'+opdid,'searchresult');
	}
</script>
