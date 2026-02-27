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
			<th>Invoice Code</th>
			<th>Name</th>
			<th>Inv. Date</th>
			<th>ref. Doc. Name</th>
			<th>Type</th>
			<th>PayMode</th>
			<th>Inv. Amount</th>
			<th>Pending</th>
			<th>Action</th>
		</tr>
    </thead>
    <tbody>
    <?php for ($i = 0; $i < count($charges_list); ++$i) { ?>
    <tr>
      <td><?=$charges_list[$i]->invoice_code ?></td>
      <td><?=$charges_list[$i]->p_fname ?></td>
      <td><?=$charges_list[$i]->Inv_Date ?></td>
	  <td>Dr. <?=$charges_list[$i]->refer_by_other ?></td>
	  <td><?=$charges_list[$i]->Inv_Type ?></td>
      <td><?=$charges_list[$i]->PaymentMode ?></td>
	  <td><?=$charges_list[$i]->net_amount ?></td>
	  <td><?=$charges_list[$i]->payment_part_balance ?></td>
      <td><?php if($charges_list[$i]->invoice_status==1 ) {  ?>
				<a href="javascript:load_form_div('/PathLab/showinvoice/<?=$charges_list[$i]->inv_id ?>','searchresult')" class="btn btn-primary">View</a>	
			<?php }elseif($charges_list[$i]->invoice_status==2){ ?>
				<a href='/PathLab/invoice_print/<?=$charges_list[$i]->inv_id ?>' target='_blank' class="btn btn-primary">Cancelled</a>
			<?php }else{ ?>
			<button onclick="load_form_div('/PathLab/showinvoice/<?=$charges_list[$i]->inv_id ?>','searchresult');" type="button" class="btn btn-primary">Pending</button>
			<?php }  ?>
	  </td>
    </tr>
    <?php } ?>
    </tbody>
    <tfoot>
    <tr>
			<th>Invoice Code</th>
			<th>Name</th>
			<th>Inv. Date</th>
			<th>ref. Doc. Name</th>
			<th>Type</th>
			<th>PayMode</th>
			<th>Inv. Amount</th>
			<th>Pending</th>
			<th>Action</th>
		</tr>
    </tfoot>
  </table>
</div>
<!-- /.box-body -->
</div>
</section>