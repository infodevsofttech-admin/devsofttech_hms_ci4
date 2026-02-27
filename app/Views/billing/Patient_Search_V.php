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
      <th>Sr.No.</th>
      <th>Patient/UHID Code</th>
      <th>Name {Relative Name}</th>
	    <th>Age</th>
      <th>Last Visit</th>
      <th>Insurance</th>
    </tr>
    </thead>
    <tbody>
    <?php for ($i = 0; $i < count($data); ++$i) { ?>
    <tr>
      <td><?=$i+1?></td>
      <td><a href="javascript:load_form('<?= base_url('billing/patient/person_record') ?>/<?=$data[$i]->id ?>');"><?=$data[$i]->p_code ?></a></td>
      <td><?=$data[$i]->p_fname ?> {<?=$data[$i]->p_rname ?>}</td>
      <td><?= esc(get_age_1($data[$i]->dob ?? null, $data[$i]->age ?? '', $data[$i]->age_in_month ?? '', $data[$i]->estimate_dob ?? '', $data[$i]->Last_Visit ?? null)) ?></td>
      <td><?=$data[$i]->Last_Visit ?></td>
      <td><?php echo ($data[$i]->insurance_id==0 ? 'Self': 'Insuranced'); ?></td>
    </tr>
    <?php } ?>
    </tbody>
    <tfoot>
    <tr>
      <th>Sr.No.</th>
      <th>Patient/UHID Code</th>
      <th>Name {Relative Name}</th>
	    <th>Age</th>
      <th>Last Visit</th>
      <th>Insurance</th>
    </tr>
    </tfoot>
  </table>
</div>
<!-- /.box-body -->
</div>
    