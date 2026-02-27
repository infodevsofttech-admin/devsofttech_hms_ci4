<section class="content-header">
  <h1>
	Medical Return Items
	<small></small>
  </h1>
</section>
<section class="content">
<?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
<div class="box box-danger">
    <div class="box-header">
		<div class="row">
			<input type="hidden" id="pid" name="pid" value="<?=$person_info[0]->id ?>" />
			<div class="col-md-2">
				<div class="form-group">
					<label>Patient Code</label>
					<input class="form-control" name="input_patient_code" id="input_patient_code" placeholder="Patient Code" type="text" value="<?=$person_info[0]->p_code ?>" readonly=true />
				</div>
			</div>
			<div class="col-md-3">
				<div class="form-group">
					<label>Customer Name</label>
					<input class="form-control" name="input_custmer_Name" id="input_custmer_Name" placeholder="Customer Name" type="text" value="<?=$person_info[0]->p_fname ?>"  readonly=true />
				</div>
			</div>
		<?php if($inv_type==0 && $inv_type_id>0) { 	?>
			<div class="col-md-3">
				<div class="form-group">
					<label>IPD Code</label>
					<div class="form-control"  >
						<a href="javascript:load_form_div('/Medical/list_med_inv/<?=$ipd_master[0]->id ?>','maindiv');" > 
							<?=$ipd_master[0]->ipd_code ?>
						</a>
					</div>
				</div>
			</div>
			<?php }elseif($inv_type==1 && $inv_type_id>0){  ?>
				<div class="col-md-3">
					<div class="form-group">
						<label>Case Code</label>
						<div class="form-control">
							<a href="javascript:load_form_div('/Medical/list_med_orginv/<?=$org_master[0]->id ?>','maindiv');" > 
								<?=$org_master[0]->case_id_code ?>
							</a>
						</div>
					</div>
				</div>
			<?php }  ?>	
		</div>
	</div>
	<div class="box-body">
		<form
		<div class="row " id="show_item_list">
			<table id="invoice_return" class="table table-bordered table-striped">
				<thead>
					<tr>
						<th style="width: 10px">#</th>
						<th>Inv-Date</th>
						<th>Item Name</th>
						<th>Batch No</th>
						<th>Exp.</th>
						<th>Rate</th>
						<th>Old Qty.</th>
						<th>Return Qty.</th>
						<th>Price</th>
						<th>Disc.</th>
						<th>Amount</th>

					</tr>
				</thead>
				<tbody>
				<?php
				$srno=0;
				$tAmount=0;
					foreach($inv_items as $row)
					{ 
						$srno=$srno+1;
						echo '<tr>';
						echo '<td>'.$srno.'</td>';
						echo '<td>'.$row->str_inv_date.'</td>';
						echo '<td>'.$row->item_Name.'</td>';
						echo '<td>'.$row->batch_no.'</td>';
						echo '<td>'.$row->expiry.'</td>';
						echo '<td>'.$row->price.'</td>';
						echo '<td>'.$row->qty.'</td>';
						echo '<td>'.$row->r_qty.'</td>';
						echo '<td>'.$row->amount.'</td>';
						echo '<td>'.$row->disc_amount.'</td>';
						echo '<td>'.$row->tamount.'</td>';
						echo '</tr>';

						$tAmount=$tAmount+$row->amount;
					}

					echo '
					<tfoot>
						<tr>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td>Total</td>
							<td>'.$tAmount.'</td>
							<td></td>
						</tr>
					</tfoot>';

				?>
				</tbody>
				<!---- Total Show  ----->
			</table>
			<?php
			echo '<input type="hidden" id="srno" name="srno" value="'.$srno.'" />';
			echo '<input type="hidden" id="wait_for_next" name="wait_for_next" value="0" />';
			?>
		</div>
	</div>
	<div class="box-footer">
		<button type="button" class="btn btn-warning"   onclick="Med_Return(<?=$ipd_master[0]->id?>)" >Medicine Reurn</button>

		<a href="<?php echo '/Medical/Med_Return_print/'.$ipd_master[0]->id.'/0';  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print Return List</a>

		<button type="button" class="btn btn-warning"   onclick="Med_Return_final(<?=$ipd_master[0]->id?>)" >Final Return Update</button>
		
	</div>

</div>
<?php echo form_close(); ?>
</section>
<!-- /.content -->
<script>
	$('#invoice_return').DataTable();

    function Med_Return(inv_type,inv_type_id){
		load_form_div('/Medical/Invoice_Item_Return/'+inv_type+'/'+inv_type_id,'maindiv'); 
	}

	function Med_Return_final(inv_type,inv_type_id){
		load_form_div('/Medical/Med_Return_final/'+inv_type+'/'+inv_type_id,'maindiv'); 
	}

	function Med_Return_print(inv_type,inv_type_id){
		load_form_div('/Medical/Med_Return_print/'+inv_type+'/'+inv_type_id,'maindiv'); 
	}
</script>