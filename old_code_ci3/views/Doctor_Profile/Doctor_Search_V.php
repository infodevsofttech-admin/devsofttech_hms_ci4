<section class="content-header">
    <h1>
        Doctor 
        <small>List</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Doctor's List</li>
    </ol>
</section>
<section class="content">
      <div class="row">
        <div class="col-md-2">
        <div class="form-group">
            <button onclick="load_form_div('/Doctor/adddoctor','maindiv','Add Doctor');" type="button" class="btn btn-primary">Add New</button>
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
      <th>Name</th>
      <th>Phone Number</th>
      <th>EMail</th>
      <th>Action</th>
    </tr>
    </thead>
    <tbody>
    <?php for ($i = 0; $i < count($data); ++$i) { ?>
    <tr>
      <td><?=$data[$i]->p_title ?> <?=$data[$i]->p_fname ?></td>
      <td><?=$data[$i]->mphone1 ?></td>
      <td><?=$data[$i]->email1 ?></td>
      <td><button onclick="load_form_div('/Doctor/doctor_record/<?=$data[$i]->id ?>','maindiv','<?=$data[$i]->p_title ?> <?=$data[$i]->p_fname ?>');" type="button" class="btn btn-success">Edit It....</button></td>
    </tr>
    <?php } ?>
    </tbody>
    <tfoot>
    <tr>
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
</div>
</section>