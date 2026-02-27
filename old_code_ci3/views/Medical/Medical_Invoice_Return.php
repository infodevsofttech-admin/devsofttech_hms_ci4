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
		<div class="row " id="show_item_list">
			<table id="invoice_return" class="table table-bordered table-striped TableData">
				<thead>
					<tr>
						<th style="width: 10px" tabindex="0">#</th>
						<th tabindex="0">Inv-Date</th>
						<th tabindex="0">Item Name</th>
						<th>Batch No</th>
						<th>Exp.</th>
						<th>Rate</th>
						<th>Saved Qty.</th>
						<th>Price</th>
						<th>Disc.</th>
						<th>Amount</th>
						<th>Return Qty.</th>
						<th>Update</th>
					</tr>
				</thead>
				<tbody>
				<?php
				$srno=0;
					foreach($inv_items as $row)
					{ 
						$srno=$srno+1;
						echo '<tr id="tr_id_'.$row->item_id.'">';
						echo '<td>'.$srno.'</td>';
						echo '<td>'.$row->str_inv_date.'</td>';
						echo '<td>'.$row->item_Name.'</td>';
						echo '<td>'.$row->batch_no.'</td>';
						echo '<td>'.$row->expiry.'</td>';
						echo '<td>'.$row->price.'</td>';
						echo '<td>'.$row->qty.'</td>';
						echo '<td>'.$row->amount.'</td>';
						echo '<td>'.$row->disc_amount.'</td>';
						echo '<td>'.$row->tamount.'</td>';

						if($row->r_qty=='')
						{
							echo '<td>
									<input class="form-control" style="width:100px" name="input_qty_'.$row->item_id.'" 
									id="input_qty_'.$row->item_id.'"  value="0" type="number" min="0" max="'.$row->qty.'"  />
							</td>';
							$visible_return='';
							$visible_undo='style="display:none;"';
							
						}else{
							echo '<td>
									<input class="form-control" style="width:100px" name="input_qty_'.$row->item_id.'" 
									id="input_qty_'.$row->item_id.'"  value="'.$row->r_qty.'" type="number" min="0" max="'.$row->qty.'" 
									Readonly="true"/>
							</td>';
							$visible_return='style="display:none;"';
							$visible_undo='';
						}
						echo '<td>
									<button type="button" class="btn btn-primary" id="btn_update_'.$row->item_id.'" onclick="update_qty('.$row->item_id.','.$row->qty.')"  '.$visible_return.'>
									<i class="fa fa-edit"></i></button>

									<button type="button" class="btn btn-primary" 
									id="btn_undo_'.$row->item_id.'" 
									onclick="undo_qty('.$row->item_id.')"  '.$visible_undo.'>
									<i class="fa  fa-undo"></i></button>
							</td>';					
						echo '</tr>';
					}
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
		<button  type="button" class="btn btn-success" id="finalreturn" 
		onclick="pre_return_list(<?=$inv_type?>,<?=$inv_type_id?>)"  >Final Return</button>
		<a href="<?php echo '/Medical/Med_Return_print/'.$ipd_master[0]->id.'/0';  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print Return List</a>
	</div>
</div>
<?php echo form_close(); ?>
</section>
<!-- /.content -->
<script>
	$('#invoice_return').DataTable();

	function update_qty(itemid,maxitemno)
	{
			var u_qty=$('#input_qty_'+itemid).val();
			var old_qty=$('#hid_oldqty_'+itemid).val();
			
			var wait_for_next=$("#wait_for_next").val();
	
			if(wait_for_next>0)
			{
				alert('wait......');
				return false;
			}
			$("#wait_for_next").val('1');
			
			if(u_qty>0 && u_qty<=maxitemno )
			{
				$.post('/index.php/Medical/Update_Return',
				{ "itemid": itemid, 
					"u_qty": u_qty,
					'<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>'  }, function(data){
					if(data.update>0)
	                {
						//alert(data.msg_text);
						$('#input_qty_'+itemid).attr("readonly", true);
						$('#btn_update_'+itemid).hide();
						$('#btn_undo_'+itemid).show();
					}else{
						//alert(data.msg_text);
						$("#wait_for_next").val('0');
					}	
				}, 'json');
			}else{
				alert('Return Value between 1 and '+maxitemno);
			}
			setTimeout(function(){ $("#wait_for_next").val('0'); }, 1000);
	}


	function undo_qty(itemid)
	{
			var wait_for_next=$("#wait_for_next").val();
	
			if(wait_for_next>0)
			{
				alert('wait......');
				return false;
			}
			$("#wait_for_next").val('1');
			
			$.post('/index.php/Medical/undo_Return',
				{ "itemid": itemid,
					'<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>' }, function(data){
					if(data.update>0)
	                {
						//alert(data.msg_text);
						$('#input_qty_'+itemid).attr("readonly", false);
						$('#btn_update_'+itemid).show();
						$('#btn_undo_'+itemid).hide();
					}else{
						//alert(data.msg_text);
						$("#wait_for_next").val('0');
					}	
				}, 'json');
						
			setTimeout(function(){ $("#wait_for_next").val('0'); }, 1000);
	}

   
    function pre_return_list(inv_type,inv_type_id){

		load_form_div('/Medical/pre_reurn_item_list/'+inv_type_id+'/'+inv_type,'maindiv'); 
	}
	
</script>