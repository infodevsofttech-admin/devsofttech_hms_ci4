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
      <th>ECHS Code</th>
      <th>Name</th>
      <th>Phone Number</th>
      <th>Date of Birth</th>
      <th>Action</th>
    </tr>
    </thead>
    <tbody>
    <?php for ($i = 0; $i < count($data); ++$i) { ?>
    <tr>
      <td><?=$data[$i]->service_no ?>/<?=$data[$i]->reg_no ?></td>
      <td><?=$data[$i]->name ?></td>
      <td><?=$data[$i]->mphone1 ?></td>
      <td><?=$data[$i]->dob ?></td>
      <td><button onclick="load_form('/Patient/echs_record/<?=$data[$i]->id ?>');" type="button" class="btn btn-primary">Got It....</button></td>
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
    