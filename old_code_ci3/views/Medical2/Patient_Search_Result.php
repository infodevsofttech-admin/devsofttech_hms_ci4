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
      <th>Patient Code</th>
      <th>Name {Relative Name}</th>
	  <th>Age</th>
      <th>Phone Number</th>
      <th>Insurance</th>
      <th>Action</th>
    </tr>
    </thead>
    <tbody>
    <?php for ($i = 0; $i < count($data); ++$i) { ?>
    <tr>
      <td><?=$data[$i]->p_code ?></td>
      <td><?=$data[$i]->p_fname ?> {<?=$data[$i]->p_rname ?>}</td>
	  <td><?=$data[$i]->age ?></td>
      <td><?=$data[$i]->mphone1 ?></td>
      <td><?php echo ($data[$i]->insurance_id==0 ? 'Self': 'Insuranced'); ?></td>
      <td><button onclick="load_form_div('/Medical/Invoice_counter_new/<?=$data[$i]->id ?>/0/0/','maindiv');" type="button" class="btn btn-primary">Select</button></td>
    </tr>
    <?php } ?>
    </tbody>
    <tfoot>
    <tr>
      <th>Patient Code</th>
      <th>Name</th>
      <th>Phone Number</th>
      <th>EMail</th>
      <th>Action</th>
    </tr>
    </tfoot>
  </table>
</div>
<!-- /.box-body -->
</div>
    