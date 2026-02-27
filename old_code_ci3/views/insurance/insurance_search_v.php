<section class="content-header">
    <h1>
        Insurance Company
        <small>List</small>
    </h1>
  
</section>
 <section class="content">
      <div class="row">
        <div class="col-md-2">
        <div class="form-group">
            <button onclick="load_form_div('/insurance/AddRecord','maindiv','Insurance- New TPA');" type="button" class="btn btn-primary">Add New</button>
        </div>
        </div>

    </div>
    <div class="row">
<div class="box">
<div class="box-header">
  <h3 class="box-title">Result</h3>
</div>
<!-- /.box-header -->
<div class="box-body">
  <table id="datashow1" class="table table-bordered table-striped TableData">
    <thead>
    <tr>
      <th>Company Name</th>
      <th>Contact Number</th>
      <th>OPD Fee</th>
      <th>Active</th>
      <th></th>
    </tr>
    </thead>
    <tbody>
    <?php for ($i = 0; $i < count($data); ++$i) { ?>
    <tr>
      <td><?=$data[$i]->ins_company_name ?></td>
      <td><?=$data[$i]->ins_contact_number ?></td>
      <td><?=$data[$i]->opd_fee?></td>
      <td><?=$data[$i]->activestatus ?></td>
      <td><button onclick="load_form_div('/insurance/insurance_record/<?=$data[$i]->id ?>','maindiv','Insurance:<?=$data[$i]->ins_company_name ?>');" type="button" class="btn btn-primary">Edit It....</button></td>
    </tr>
    <?php } ?>
    </tbody>
    <tfoot>
    <tr>
      <th>Company Name</th>
      <th>Contact Number</th>
      <th>Contact Person Name</th>
      <th>E-Mail</th>
	  <th></th>
    </tr>
    </tfoot>
  </table>
  <script>
	$('#datashow1').dataTable();
  </script>
</div>
<!-- /.box-body -->
</div>
</div>
</section>