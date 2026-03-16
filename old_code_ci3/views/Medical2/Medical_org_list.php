<section class="content">
<div class="box">
<div class="box-header">
  <h3 class="box-title">IPD List</h3>
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
    <?php for ($i = 0; $i < count($data); ++$i) { ?>
		<tr>
		  <td>
		  <a href="javascript:load_form_div('/Medical/list_med_orginv/<?=$data[$i]->org_id ?>','maindiv');" > 
			<?=$data[$i]->case_id_code ?>
			</a>
		  </td>
		  <td>[<?=$data[$i]->p_code ?>] <?=$data[$i]->p_fname ?><br/><?=$data[$i]->p_rname ?> </td>
			<td><?=$data[$i]->str_register_date ?></td>
		  <td><?=$data[$i]->insurance_company_name ?></td>
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
