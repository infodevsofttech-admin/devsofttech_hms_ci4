<section class="content">
<div class="box">
<div class="box-header">
  <h3 class="box-title">ORG  List</h3>
  </div>
<!-- /.box-header -->
<div class="box-body">
  <table id="datashow1" class="table table-bordered table-striped TableData">
    <thead>
		<tr>
			<th>ORG Code</th>
			<th>Name/Patient Code</th>
			<th>Register Date</th>
			<th>Insurance Company</th>
			
		</tr>
    </thead>
    <tbody>
    <?php foreach($organization_case_master as $row) { ?>
		<tr>
		  <td>
		  <a href="javascript:load_form_div('/Medical/list_med_orginv/<?=$row->org_id ?>','maindiv');" > 
			<?=$row->case_id_code ?>
			</a>
		  </td>
		  <td>[<?=$row->p_code ?>] <?=$row->p_fname ?><br/><?=$row->p_rname ?> </td>
			<td><?=$row->str_register_date ?></td>
		  <td><?=$row->insurance_company_name ?></td>
		</tr>
    <?php } ?>
    </tbody>
    <tfoot>
    <tr>
		<th>ORG Code</th>
		<th>Name/Patient Code</th>
		<th>Register Date</th>
		<th>Insurance Company</th>
    </tr>
    </tfoot>
  </table>
 
</div>
<!-- /.box-body -->
</div>
</section>

<script>
	$('#datashow1').dataTable();
</script>
