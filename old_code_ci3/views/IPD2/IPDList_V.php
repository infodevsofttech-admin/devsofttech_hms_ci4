<section class="content-header">
    <h1>
        IPD List
        <small></small>
    </h1>
    <ol class="breadcrumb">
       <li class="active" ><a href="javascript:load_form('/Ipd/ipd_room_status');">Bed Status</a></li>
    </ol>
</section>
<section class="content">
<div class="box">
  <div class="box-header">
  <h3 class="box-title">Result</h3>
  </div>
<!-- /.box-header -->
<div class="box-body">
  <table id="datashow1" class="table table-bordered table-striped TableData">
    <thead>
    <tr>
		  <th>IPD Code</th>
		  <th>Name/Patient Code</th>
		  <th>Bed No.[Type]</th>
		  <th>Register Date</th>
		  <th>No. of Days</th>
		  <th>Dr. Name</th>
		  <th>Admit Type</th>
		  <th>Amount</th>
		  <th></th>
    </tr>
    </thead>
    <tbody>
    <?php for ($i = 0; $i < count($data); ++$i) { ?>
    <tr style=";color:<?=$data[$i]->color ?>;">
      <td><?=$data[$i]->ipd_code ?></td>
	  <td>[<?=$data[$i]->p_code ?>] <?=$data[$i]->p_fname ?><br/><?=$data[$i]->p_rname ?> </td>
	  <td><?=$data[$i]->Bed_Desc ?></td>
      <td><?=$data[$i]->str_register_date ?></td>
	  <td><?=$data[$i]->no_days ?></td>
	  <td><?=$data[$i]->doc_name ?></td>
      <td><?=$data[$i]->admit_type ?><br><?=$data[$i]->Org_Status ?><br><?=$data[$i]->insurance_no_1 ?></td>
	  <td>C:<?=$data[$i]->charge_amount ?>/M:<?=$data[$i]->med_amount ?>/P:<?=$data[$i]->paid_amount?>/B:<?=$data[$i]->balance?></td>
      <td><button onclick="load_form('/IpdNew/ipd_panel/<?=$data[$i]->id ?>');" type="button" class="btn btn-primary">Got It....</button></td>
    </tr>
    <?php } ?>
    </tbody>
    <tfoot>
    <tr>
      <th>IPD Code</th>
		  <th>Name/Patient Code</th>
		  <th>Register Date</th>
		  <th>Admit Type</th>
		  <th>Amount</th>
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
</section>