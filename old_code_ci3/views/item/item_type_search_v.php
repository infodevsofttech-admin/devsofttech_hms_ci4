<section class="content-header">
    <h1>
        OPD Charges Groups
        <small>List</small>
    </h1>
  
</section>

 <section class="content">
      <div class="row">
        <div class="col-md-2">
        <div class="form-group">
            <button onclick="load_form_div('/item/AddItemTypeRecord','maindiv','OPD Charge Master');" type="button" class="btn btn-primary">Add New</button>
        </div>
        </div>
		<div class="col-md-2">
        <div class="form-group">
            <button onclick="load_form_div('/item/search','maindiv','OPD Charge Master');" type="button" class="btn btn-primary">Charges List</button>
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
  <table id="example1" class="table table-bordered table-striped TableData">
    <thead>
    <tr>
      <th>OPD or IPD</th>
      <th>Charges Group Name</th>
      <th></th>
    </tr>
    </thead>
    <tbody>
    <?php for ($i = 0; $i < count($data); ++$i) { ?>
    <tr>
		<td><?=$data[$i]->isIPD_OPD ?></td>
		<td><?=$data[$i]->group_desc ?></td>
		<td><button onclick="load_form_div('/item/itemtype_record/<?=$data[$i]->itype_id ?>','maindiv','OPD Charge Master');" type="button" class="btn btn-primary">Edit It....</button></td>
    </tr>
    <?php } ?>
    </tbody>
    <tfoot>
    <tr>
      <th>OPD or IPD</th>
      <th>Charges Group Name</th>
      <th></th>
    </tr>
    </tfoot>
  </table>
</div>
<!-- /.box-body -->
</div>
</div>
</section>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        $('#example1').dataTable();
    });
</script>
	