<div id='Medical_invoice_final'>
<section class="content-header">
  <h1>
	Medical Invoice
	<small></small>
  </h1>
</section>
<section class="content">
<?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
<div class="box box-danger">
    <div class="box-header">
		<div class="box-title">
			<input type="hidden" id="pid" name="pid" value="<?=$invoiceMaster[0]->patient_id ?>" />
			<input type="hidden" id="med_invoice_id" name="med_invoice_id" value="<?=$invoiceMaster[0]->id ?>" />
        </div>
    </div>
	<div class="box-body">
		<div class="row">
			<div class="col-md-2">
				<div class="form-group">
					<label>Patient Code</label>
					<div class="form-control"  ><?=$invoiceMaster[0]->patient_code ?></div>
				</div>
			</div>
			<div class="col-md-2">
				<div class="form-group">
					<label>Customer Name</label>
					<div class="form-control"  ><?=$invoiceMaster[0]->inv_name ?></div>
				</div>
			</div>
			<div class="col-xs-2">
				<div class="form-group">
					<label>Doctor</label>
					<div class="form-control varchar"  ><?=$invoiceMaster[0]->doc_name?></div>
				</div>
			</div>
			<?php if($invoiceMaster[0]->ipd_id > 0) { ?>
			<div class="col-md-2">
				<div class="form-group">
					<label>IPD Code</label>
					<div class="form-control"  >
						<a href="javascript:load_form_div('/Medical/list_med_inv/<?=$invoiceMaster[0]->ipd_id ?>','maindiv');" > 
							<?=$invoiceMaster[0]->ipd_code ?>
						</a>
					</div>
				</div>
			</div>
			<div class="col-md-2">
				<div class="form-group">
					<label>Invoice Type</label>
					<div class="form-control" ><?=$invoiceMaster[0]->credit_status ?></div>
				</div>
			</div>
			<div class="col-xs-2">
				<div class="form-group">
					<label>IPD Remark</label>
					<input class="form-control varchar" name="input_remark_ipd" id="input_remark_ipd" placeholder="Any Remark for IPD" value="<?=$invoiceMaster[0]->remark_ipd?>" type="text"  />
				</div>
			</div>
			<?php }elseif($invoiceMaster[0]->case_id > 0) {  ?>
			<div class="col-md-3">
					<div class="form-group">
						<label>Case Code</label>
						<div class="form-control">
							<a href="javascript:load_form_div('/Medical/list_med_orginv/<?=$OCaseMaster[0]->id ?>','maindiv');" > 
								<?=$OCaseMaster[0]->case_id_code ?>
							</a>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="form-group">
						<label>Case Credit Status</label>
						<div class="form-control" ><?=$invoiceMaster[0]->credit_org_status ?></div>
					</div>
				</div>
			<?php }  ?>	
		</div>
		<div class="row " id="show_item_list">
		<table class="table table-striped ">
			<tr>
				<th style="width: 10px">#</th>
				<th>Item code</th>
				<th>Item Name</th>
				<th>Formulation</th>
				<th>Batch No</th>
				<th>Exp.</th>
				<th>Rate</th>
				<th>Qty.</th>
				<th>Price</th>
				<th>Disc.</th>
				<th>HSNCODE/C-SGST</th>
				<th>CGST</th>
				<th>SGST</th>
				<th>Amount</th>
			</tr>
			<?php
			
			$srno=0;
				foreach($inv_items as $row)
				{ 
					$srno=$srno+1;
					echo '<tr>';
					echo '<td>'.$srno.'</td>';
					echo '<td>'.$row->item_code.'</td>';
					echo '<td>'.$row->item_Name.'</td>';
					echo '<td>'.$row->formulation.'</td>';
					echo '<td>'.$row->batch_no.'</td>';
					echo '<td>'.$row->expiry.'</td>';
					echo '<td>'.$row->price.'</td>';
					echo '<td>'.$row->qty.'</td>';
					echo '<td>'.$row->amount.'</td>';
					echo '<td>'.$row->disc_amount.'</td>';
					echo '<td>'.$row->HSNCODE.'</td>';
					echo '<td>'.$row->CGST.'</td>';
					echo '<td>'.$row->SGST.'</td>';
					echo '<td>'.$row->tamount.'</td>';
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
				<th>Gross Total</th>
				<th><?=$invoiceMaster[0]->gross_amount ?></th>
				<th><?=$invoiceMaster[0]->inv_disc_total?></th>
				<th></th>
				<th><?=$invoiceMaster[0]->CGST_Tamount?></th>
				<th><?=$invoiceMaster[0]->SGST_Tamount?></th>
				<th><?=$invoiceMaster[0]->net_amount?></th>
			</tr>
			<?php if($invoiceMaster[0]->group_invoice_id==0) { ?>
			<tr>
				<th style="width: 10px">#</th>
				<th>Deduction</th>
				<th colspan=9><?=$invoiceMaster[0]->discount_remark ?></th>
				<th><?=$invoiceMaster[0]->discount_amount ?></th>
				<th></th>
			</tr>
			<tr>
				<th style="width: 10px">#<input type="hidden" name="hid_amount_recevied" id="hid_amount_recevied" value="<?=$invoiceMaster[0]->payment_received?>"></th>
				<th colspan="2">Amount received : <?=$invoiceMaster[0]->payment_received?></th>
				<th>Balance Amount : <?=$invoiceMaster[0]->payment_balance?></th>
				<th>Net Amount</th>
				<th><?=$invoiceMaster[0]->net_amount?></th>
				<th></th>
			</tr>
			<?php }else{ ?>
			<tr>
				<th style="width: 10px">#<input type="hidden" name="hid_amount_recevied" id="hid_amount_recevied" value="<?=$invoiceMaster[0]->payment_received?>"></th>
				<th colspan="2"></th>
				<th></th>
				<th>Net Amount</th>
				<th><?=$invoiceMaster[0]->net_amount?></th>
				<th></th>
			</tr>
			<?php } ?>
				<th style="width: 10px">#</th>
				<th>Rate Deduction</th>
				<th colspan=9></th>
				<th><input style="width: 100px" class="form-control" name="input_dis_amt" id="input_dis_amt" placeholder="Amount" value="<?=$invoiceMaster[0]->rate_update ?>" type="text" /></th>
				<th><button type="button" class="btn btn-primary" id="btn_update_ded">Update</button></th>
		</table>
		</div>
	<div class="row no-print">
        <div class="col-xs-6">
          <a href="<?php echo '/Medical/invoice_print/'.$invoiceMaster[0]->id;  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print</a>
        </div>
     </div>
      <!-- /.row -->
	</div>
</div>
<?php echo form_close(); ?>
</section>
<!-- /.content -->

<script>
   $(document).ready(function(){
	$('#btn_update_ded').click( function()
	{
	
		var inv_id = $('#med_invoice_id').val();
		$.post('/index.php/Medical_backpanel/update_rate',{ "med_invoice_id": inv_id, 
			"input_dis_amt": $('#input_dis_amt').val()
			 }, function(data){
				 load_form_div('/Medical_backpanel/Invoice_med_show/'+inv_id,'maindiv');
				 setTimeout(enable_btn,1000);
			});
	});
	
});
</script>
</div>