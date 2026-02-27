<section class="content">
<div class="box">
<div class="box-header">
  <h3 class="box-title">IPD List</h3>
  <small >
		<?php
			if(count($high_balance)>0)
			{
				echo '<a href="javascript:load_form_div(\'/Medical/list_high_balance\',\'maindiv\');" >High Balance </a>';
			}
		?>
	</small>
  </div>
<!-- /.box-header -->
<div class="box-body">
  <table id="datashow1" class="table table-bordered table-striped TableData">
    <thead>
    <tr>
		<th>IPD Code</th>
		<th>Name/Patient Code</th>
		<th>Bed No.[Type]</th>
		<th>Register Date</th>
		<th>No. of Days</th>
		<th>Dr. Name</th>
		<th>Admit Type</th>
		<th>Amount</th>
	</tr>
    </thead>
    <tbody>
    <?php for ($i = 0; $i < count($data); ++$i) { ?>
    <tr>
      <td>
	  <a href="javascript:load_form_div('/Medical/list_med_inv/<?=$data[$i]->id ?>','maindiv');" > 
		<?=$data[$i]->ipd_code ?>
	  </a>
	  </td>
	  <td>[<?=$data[$i]->p_code ?>] <?=$data[$i]->p_fname ?><br/><?=$data[$i]->p_rname ?> </td>
	  <td><?=$data[$i]->Bed_Desc ?></td>
      <td><?=$data[$i]->str_register_date ?></td>
	  <td><?=$data[$i]->no_days ?></td>
	  <td><?=$data[$i]->doc_name ?></td>
      <td><?=$data[$i]->admit_type ?><br><?=$data[$i]->Org_Status ?></td>
	  <td>M:<?=$data[$i]->med_amount ?></td>
    </tr>
    <?php } ?>
    </tbody>
    <tfoot>
    <tr>
		<th>IPD Code</th>
		<th>Name/Patient Code</th>
		<th>Register Date</th>
		<th>Admit Type</th>
		<th>Amount</th>
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
