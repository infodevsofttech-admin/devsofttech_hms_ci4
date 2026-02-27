<br /><br />
<div class="box">
<div class="box-header">
  <h3 class="box-title">Result</h3>
</div>
<!-- /.box-header -->
<div class="box-body">
  <table id="example1" class="table table-bordered table-striped TableData">
    <thead>
    <tr>
      <th>IPD Code</th>
	  <th>Patient Code</th>
      <th>Name {Relative Name}</th>
	  <th>Doctor Name</th>
      <th>Insurance</th>
      <th>Action</th>
    </tr>
    </thead>
    <tbody>
    <?php for ($i = 0; $i < count($ipd_master); ++$i) { ?>
    <tr>
		<td><?=$ipd_master[$i]->ipd_code ?></td>
		<td><?=$ipd_master[$i]->p_code ?></td>
		<td><?=$ipd_master[$i]->p_fname ?> {<?=$ipd_master[$i]->p_rname ?>}</td>
		<td><?=$ipd_master[$i]->doc_name ?></td>
		<td><?=$ipd_master[$i]->admit_type ?></td>
		<td><button onclick="load_form_div('/Ipd_discharge/ipd_select/<?=$ipd_master[$i]->id ?>','maindiv');" type="button" class="btn btn-primary">Select</button></td>
    </tr>
    <?php } ?>
    </tbody>
    <tfoot>
    <tr>
      <th>IPD Code</th>
	  <th>Patient Code</th>
      <th>Name {Relative Name}</th>
	  <th>Doctor Name</th>
      <th>Insurance</th>
      <th>Action</th>
    </tr>
    </tfoot>
  </table>
</div>
<!-- /.box-body -->
</div>
    