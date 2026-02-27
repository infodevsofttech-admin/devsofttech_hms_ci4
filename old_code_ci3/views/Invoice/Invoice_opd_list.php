<section class="content">
<div class="box">
<div class="box-header">
  <h3 class="box-title">Result</h3>
</div>
<!-- /.box-header -->
<div class="box-body">
  <table id="example1" class="table table-bordered table-striped TableData">
    <thead>
		<tr>
			<th>OPD Code</th>
			<th>Name</th>
			<th>OPD Date</th>
			<th>Doc. Name</th>
			<th>Type</th>
			<th>PayMode</th>
			<th>Fee</th>
		</tr>
    </thead>
    <tbody>
    <?php for ($i = 0; $i < count($opd_list); ++$i) { ?>
    <tr>
      <td><?=$opd_list[$i]->opd_code ?></td>
      <td><?=$opd_list[$i]->P_name ?></td>
      <td><?=$opd_list[$i]->App_Date ?></td>
	  <td><?=$opd_list[$i]->doc_name ?></td>
	  <td><?=$opd_list[$i]->Inv_Type ?></td>
      <td><?=$opd_list[$i]->PaymentMode ?></td>
      <td><button onclick="load_form_div('/opd/invoice/<?=$opd_list[$i]->opd_id ?>','searchresult');" type="button" class="btn btn-primary">Got It....</button></td>
    </tr>
    <?php } ?>
    </tbody>
    <tfoot>
    <tr>
			<th>OPD Code</th>
			<th>Name</th>
			<th>OPD Date</th>
			<th>Doc. Name</th>
			<th>Type</th>
			<th>PayMode</th>
			<th>Fee</th>
		</tr>
    </tfoot>
  </table>
</div>
<!-- /.box-body -->
</div>
</section>