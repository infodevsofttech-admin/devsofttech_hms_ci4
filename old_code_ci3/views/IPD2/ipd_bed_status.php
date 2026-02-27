<section class="content-header">
    <h1>
        Bed Status
        <small></small>
    </h1>
    <ol class="breadcrumb">
       <li class="active" ><a href="javascript:load_form('/Ipd/IpdList');">IPD List</a></li>
    </ol>
</section>
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
		<th>#</th>
		<th>Desc</th>
		<th>No. of Bed</th>
		<th>Free Bed</th>
		<th>Room Rent</th>
    </tr>
    </thead>
    <tbody>
    <?php 
	$total_bed=0;
	$total_free_bed=0;
	for ($i = 0; $i < count($roomdata); ++$i) { ?>
    <tr>
		<td><?=$i+1 ?></td>
		<td><?=$roomdata[$i]->room_name ?></td>
		<td><?=$roomdata[$i]->Total_Bed ?></td>
		<td><?=$roomdata[$i]->FreeBed ?></td>
		<td><?=$roomdata[$i]->room_rent ?></td>
    </tr>
    <?php 
	$total_bed=$total_bed+$roomdata[$i]->Total_Bed;
	$total_free_bed=$total_free_bed+$roomdata[$i]->FreeBed;
	} ?>
    </tbody>
    <tfoot>
    <tr>
		<th>#</th>
		<th>Total</th>
		<th><?=$total_bed?></th>
		<th><?=$total_free_bed?></th>
		<th></th>

    </tr>
    </tfoot>
  </table>
</div>
<!-- /.box-body -->
</div>
</section>