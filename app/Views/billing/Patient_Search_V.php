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
      <th>Action</th>
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
      <td>
        <a href="javascript:load_form('<?= base_url('billing/patient/show_profile_opd') ?>/<?=$data[$i]->id ?>/1');"
           class="btn btn-info btn-sm"
           title="Patient History"
           aria-label="Patient History">
          <i class="bi bi-clock-history"></i>
        </a>
        <a href="javascript:load_form('<?= base_url('billing/patient/person_record') ?>/<?=$data[$i]->id ?>');"
           class="btn btn-primary btn-sm"
           title="Open Profile"
           aria-label="Open Profile">
          <i class="bi bi-person-vcard"></i>
        </a>
      </td>
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
      <th>Action</th>
    </tr>
    </tfoot>
  </table>
</div>
<!-- /.box-body -->
</div>

<script>
(function() {
  var table = document.getElementById('example1');
  if (!table) {
    return;
  }

  // Prevent duplicate initialization when this partial is loaded repeatedly.
  if (table.dataset.dtInit === '1') {
    return;
  }

  // Prefer simple-datatables for static HTML tables loaded via load_form.
  if (window.simpleDatatables && window.simpleDatatables.DataTable) {
    try {
      new window.simpleDatatables.DataTable(table);
      table.dataset.dtInit = '1';
      return;
    } catch (e) {
      console.warn('simple-datatables init failed for patient search table', e);
    }
  }

  // Fallback to full jQuery DataTables plugin only (not the local shim).
  if (window.jQuery && $.fn && $.fn.DataTable && $.fn.dataTable && $.fn.dataTable.defaults) {
    try {
      if (!$.fn.DataTable.isDataTable('#example1')) {
        $('#example1').DataTable({
          order: [[0, 'asc']],
          pageLength: 25
        });
      }
      table.dataset.dtInit = '1';
    } catch (e) {
      console.warn('jQuery DataTables init failed for patient search table', e);
    }
  }
})();
</script>
    