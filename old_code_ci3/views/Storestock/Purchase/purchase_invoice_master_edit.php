<br/>
<div class="row">
	<div class="col-md-12">
		<div class="jsError"></div>
		<div class="box">
				<div class="box-header">
					<?php echo form_open('Storestock/NewPurchase', array('role'=>'form','class'=>'form1')); ?>
						<input type="hidden" id="hid_purchaseid" name="hid_purchaseid" value="<?=$inv_master_data[0]->id?>" />
						<?php if($inv_master_data[0]->inv_status==0){ ?>
						<div class="row">
							<div class="col-md-4">
								<div class="form-group">
								<label>Supplier</label>
									<select class="form-control input-sm" id="input_supplier" name="input_supplier"  >	
										<?php 
										foreach($supplier_data as $row)
										{ 
											echo '<option value='.$row->sid.'  '.combo_checked($row->sid,$inv_master_data[0]->sid).' >'.$row->name_supplier.'</option>';
										}
										?>
									</select>
								</div>
							</div>
							<div class="col-md-2">
								<div class="form-group">
									<label>Bill Type</label>
									<select class="form-control input-sm" name="cbo_billtype" id="cbo_billtype"  >
										<option value="0" <?=combo_checked("0",$inv_master_data[0]->ischallan)?> >Invoice</option>
										<option value="1" <?=combo_checked("1",$inv_master_data[0]->ischallan)?> >Challan</option>
									</select>
								</div>
							</div>
							<div class="col-md-2">
								<div class="form-group">
									<label>Invoice ID</label>
									<input class="form-control" name="input_invoicecode" placeholder="Invoice No." type="text" value="<?=$inv_master_data[0]->Invoice_no?>"   />
								</div>
							</div>
							<div class="col-md-2">
								<label> Date of Invoice</label>
								<div class="input-group date input-sm">
									<div class="input-group-addon">
										<i class="fa fa-calendar"></i>
									</div>
									<input class="form-control pull-right datepicker input-sm" name="datepicker_invoice" id="datepicker_invoice" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask value="<?=MysqlDate_to_str($inv_master_data[0]->date_of_invoice)?>"  />
								</div>
							</div>
							<div class="col-md-2">
								<button type="button" class="btn btn-primary" id="btn_update" accesskey="U" ><u>U</u>pdate</button>
							</div>
						</div>
					<?php }else{ ?>
						<div class="col-md-4">
								<div class="form-group">
									<label>Supplier</label>
									<div class="form-control" ><?=$inv_master_data[0]->name_supplier?>	</div>
								</div>
							</div>
							<div class="col-md-3">
								<div class="form-group">
									<label>Invoice ID</label>
									<div class="form-control" ><?=$inv_master_data[0]->Invoice_no?>	</div>
								</div>
							</div>
							<div class="col-md-3">
								<label> Date of Invoice</label>
								<div class="form-control" ><?=MysqlDate_to_str($inv_master_data[0]->date_of_invoice)?>	</div>
								
							</div>
					<?php }  ?>
					<?php echo form_close(); ?>
				</div>
				<div class="box-body">
				<table class="table table-striped ">
					<tr>
						<th style="width: 10px">#</th>
						<th>Item Name</th>
						<th>Batch No</th>
						<th>Exp.</th>
						<th>MRP</th>
						<th>Qty.</th>
						<th>Rate</th>
						<th>Amount</th>
						<th>Disc.</th>
						<th>Tax Amount</th>
						<th>CGST</th>
						<th>SGST</th>
						<th>Net Amount</th>
					</tr>
					<?php
					$srno=0;
						foreach($purchase_item as $row)
						{ 
							$srno=$srno+1;
							if($row->item_return==1)
							{
								$style='style="color:Red;"';
							}else{
								$style='';
							}
							echo '<tr '.$style.' >';
							echo '<td>'.$srno.'</td>';
							echo '<td>'.$row->Item_name.'</td>';
							echo '<td>'.$row->batch_no.'</td>';
							echo '<td>'.$row->expiry_date.'</td>';
							echo '<td>'.$row->mrp.'</td>';
							echo '<td>'.floatval($row->qty).'+'.floatval($row->qty_free).'</td>';
							echo '<td>'.$row->purchase_price.'</td>';
							echo '<td>'.$row->amount.'</td>';
							echo '<td>'.$row->discount.'</td>';
							echo '<td>'.$row->taxable_amount.'</td>';
							echo '<td>'.$row->CGST_per.'</td>';
							echo '<td>'.$row->SGST_per.'</td>';
							echo '<td>'.$row->net_amount.'</td>';
							echo '</tr>';
						}
					echo '<input type="hidden" id="srno" name="srno" value="'.$srno.'" />';
					?>
					<!---- Total Show  ----->
					<tr>
						<th style="width: 10px">#</th>
						<th></th>
						<th></th>
						<th></th>
						<th></th>
						<th></th>
						<th></th>
						<th></th>
						<th></th>
						<th><?=$inv_master_data[0]->Taxable_Amt ?></th>
						<th><?=$inv_master_data[0]->CGST_Amt ?></th>
						<th><?=$inv_master_data[0]->SGST_Amt ?></th>
						<th><?=$inv_master_data[0]->T_Net_Amount ?></th>
					</tr>
					
					</table>
				</div>
				<div class="box-footer" >
				<?php echo form_open('Storestock/NewPurchase', array('role'=>'form','class'=>'form2')); ?>
				<?php
				if($inv_master_data[0]->inv_status==0 ){
				?>
					<div class="row">
						<div class="col-md-2">
							<div class="form-group">
								<button onclick="load_form_div('/Storestock/PurchaseInvoiceEdit/<?=$inv_master_data[0]->id ?>','searchresult');" type="button" class="btn btn-warning">Edit Items</button>
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
							<select class="form-control" name="cbo_invoice_status" id="cbo_invoice_status"  >
								<option value="0" <?=combo_checked("0",$inv_master_data[0]->inv_status)?> >Pending Entry</option>
								<option value="1" <?=combo_checked("1",$inv_master_data[0]->inv_status)?> >Final and Checked</option>
							</select>
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<button type="button" class="btn btn-primary" id="btn_update_stock" onclick="update_invoice_status()" >Update Status</button>
							</div>
						</div>
					</div>
				<?php }elseif($this->ion_auth->in_group('MedicalStoreAdmin')) { ?>
						<div class="row">
							<div class="col-md-2">
								<div class="form-group">
								<select class="form-control" name="cbo_invoice_status" id="cbo_invoice_status"  >
									<option value="1" <?=combo_checked("1",$inv_master_data[0]->inv_status)?> >Close</option>
									<option value="0" <?=combo_checked("0",$inv_master_data[0]->inv_status)?> >Open</option>
								</select>
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<button type="button" class="btn btn-primary" id="btn_update_indent" onclick="open_purchase_invoice()" >Purchase Edit</button>
								</div>
							</div>
						</div>
				<?php }else{ echo 'Request For Open This Bill, Send This message < JNGF4 P '.$inv_master_data[0]->id.'>'; } ?>
				<?php echo form_close(); ?>
				</div>
		</div>
	</div>
</div>
<script>
function update_invoice_status()
	{
		var invoice_status=$("#cbo_invoice_status").val();
		if(confirm('Are you sure to update Status?'))
		{
			load_form_div('/Storestock/UpdatePurchaseInvoiceStatus/<?=$inv_master_data[0]->id ?>/'+invoice_status,'searchresult');
		}
	}

	function create_invoice_indent()
	{
		var cbo_store_id=$("#cbo_store_id").val();
		if(confirm('Are you sure to Create Indent to Store Direct?'))
		{
			load_form_div('/Storestock/Transfer_invoice_to_counter/<?=$inv_master_data[0]->id ?>/'+cbo_store_id,'searchresult');
		}
		
	}

	function open_purchase_invoice()
	{
		var invoice_status=$("#cbo_invoice_status").val();
		if(confirm('Are you sure to update Status?'))
		{
			load_form_div('/Storestock/UpdatePurchaseInvoiceStatus/<?=$inv_master_data[0]->id ?>/'+invoice_status,'searchresult');
		}
		
	}
	

$(document).ready(function(){
	$('#btn_update').click(function(){
		$.post('/Storestock/UpdatePurchase', $('form.form1').serialize(), function(data){
			if(data.insertid==0)
			{
				notify('error','Please Attention',data.show_text);
			}else
			{
				notify('Success','Update Success',data.show_text);
				load_form_div('/Storestock/PurchaseInvoiceEdit/'+<?=$inv_master_data[0]->id?>,'searchresult');
			}
		}, 'json');
	});
});

</script>