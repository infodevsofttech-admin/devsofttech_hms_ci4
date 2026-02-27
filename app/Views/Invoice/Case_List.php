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
			<th>Case No.</th>
			<th>Patient Name</th>
			<th>Case Date</th>
			<th>Card Holder Name</th>
			<th>Insurance</th>
			<th>OPD Amount</th>
			<th>Invoice Amount</th>
			<th>Status</th>
			<th></th>
		</tr>
    </thead>
    <tbody>
    <?php for ($i = 0; $i < count($searchdata); ++$i) { ?>
    <tr>
      <td><?=$searchdata[$i]->case_id_code ?></td>
      <td><?=$searchdata[$i]->p_name ?></td>
      <td><?=$searchdata[$i]->date_registration ?></td>
	  <td><?=$searchdata[$i]->insurance_card_name ?></td>
	  <td><?=$searchdata[$i]->insurance_company_name ?></td>
      <td><?=$searchdata[$i]->OPD_Amount ?></td>
	  <td><?=$searchdata[$i]->Invoice_Amount ?></td>
	  <td><?=$searchdata[$i]->str_status ?></td>
	  <td><button onclick="load_form('<?= base_url('billing/case/case_invoice') ?>/<?=$searchdata[$i]->id ?>');" type="button" class="btn btn-primary">Show It....</button></td>
    </tr>
    <?php } ?>
    </tbody>
    <tfoot>
		<tr>
			<th>Case No.</th>
			<th>Patient Name</th>
			<th>Case Date</th>
			<th>Card Holder Name</th>
			<th>Insurance</th>
			<th>OPD Amount</th>
			<th>Invoice Amount</th>
			<th></th>
		</tr>
    </tfoot>
  </table>
</div>
<!-- /.box-body -->
</div>
</section>