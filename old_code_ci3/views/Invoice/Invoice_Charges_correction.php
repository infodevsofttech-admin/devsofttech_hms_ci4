<section class="content-header">
      <h1>
        Invoice
        <small>#<?=$invoice_master[0]->invoice_code ?></small>
      </h1>
</section>
<section class="invoice">
      <!-- title row -->
      <div class="row">
        <div class="col-xs-12">
          <h2 class="page-header">
            <i class="fa fa-globe"></i> Krishna Hospital & Research Centre
            <small class="pull-right">Print Date: <?=date('d/m/Y') ?> </small>
          </h2>
        </div>
        <!-- /.col -->
      </div>
      <!-- info row -->
      <div class="row invoice-info">
        <div class="col-sm-4 invoice-col">
          From
          <address>
            <strong>Krishna Hospital & Research Centre </strong><br>
            3-136, Guru Nanakpura, Haldwani<br>
            Distt. Nainital, Uttarakhand<br>
            Phone: (05946) 222426,282623<br>
			Fax No.:(05946) 282624
            Email: krishnahospitalhaldwani@gmail.com
          </address>
        </div>
        <!-- /.col -->
        <div class="col-sm-4 invoice-col">
          To
          <address>
            <strong><?=$patient_master[0]->p_fname ?></strong><br>
            Gender : <?=$patient_master[0]->xgender ?><br>
            Age : <?=$patient_master[0]->age ?><br>
            Phone No : <?=$patient_master[0]->mphone1 ?>
          </address>
        </div>
        <!-- /.col -->
        <div class="col-sm-4 invoice-col">
          <b>Invoice #<?=$invoice_master[0]->invoice_code ?></b><br>
          <br>
          <b>Date :</b> <?=$invoice_master[0]->inv_date ?><br>
          <b>Patient ID :</b> <?=$patient_master[0]->p_code ?><br>
		  <b>Refer By :</b> <?=$invoice_master[0]->refer_by_other ?><br>
		  <input type="hidden" value="<?=$invoice_master[0]->id ?>" id="invoice_id" name="invoice_id" />
		   <?php 
			if($invoice_master[0]->payment_id>0 )
			{
					echo '<strong>Payment No. :</strong>'.$invoice_master[0]->payment_id.'<br>';
			}
			?>
        </div>
        <!-- /.col -->
      </div>
      <!-- /.row -->

      <!-- Table row -->
      <div class="row">
        <div class="col-xs-12 table-responsive">
          <table class="table table-striped ">
			<tr>
				<th style="width: 10px">#</th>
				<th>Charges Group</th>
				<th>Charge Name</th>
				<th>Rate</th>
				<th>Qty</th>
				<th>Amount</th>
				<th></th>
			</tr>
			<?php
			$srno=1;
				foreach($invoiceDetails as $row)
				{ 
					echo '<tr>';
					echo '<td>'.$srno.'</td>';
					echo '<td>'.$row->desc.'</td>';
					echo '<td>'.$row->item_name.'</td>';
					echo '<td>'.$row->item_rate.'</td>';
					echo '<td>'.$row->item_qty.'</td>';
					echo '<td>'.$row->item_amount.'</td>';
					$srno=$srno+1;
					echo '<td></td>';
					echo '</tr>';
				}
			?>
			<!---- Total Show  ----->
			<tr>
				<th style="width: 10px">#</th>
				<th ></th>
				<th></th>
				<th></th>
				<th>Gross Total</th>
				<th><?=$invoiceGtotal[0]->Gtotal?></th>
				<th></th>
			</tr>
			<?php if($invoice_master[0]->payment_status==0) {  ?>
			<tr>
				<th style="width: 10px">#</th>
				<th>Deduction</th>
				<th colspan=3><input  class="form-control" name="input_dis_desc" id="input_dis_desc" placeholder="Ded. Desc." value="<?=$invoice_master[0]->discount_desc ?>" type="text" /> </th>
				
				<th><input style="width: 100px" class="form-control" name="input_dis_amt" id="input_dis_amt" placeholder="Amount" value="<?=$invoice_master[0]->discount_amount ?>" type="text" /></th>
				<th><button type="button" class="btn btn-primary" id="btn_update_ded">Update</button></th>
			</tr>
			<?php }else{ ?>
			<tr>
				<th style="width: 10px">#</th>
				<th>Deduction</th>
				<th colspan=3><?=$invoice_master[0]->discount_desc ?></th>
				
				<th><?=$invoice_master[0]->discount_amount ?></th>
				<th></th>
			</tr>
			<?php } ?>
			<tr>
				<th style="width: 10px">#</th>
				<th></th>
				<th></th>
				<th></th>
				<th>Net Amount</th>
				<th><?=$invoice_master[0]->net_amount?></th>
				<th></th>
			</tr>
			<?php if($invoice_master[0]->payment_status==1) {  
			if($invoice_master[0]->payment_mode==1 || $invoice_master[0]->payment_mode==2)
			{
				if($invoice_master[0]->correction_amount==0)
				{
			?>
			<tr>
				<td><b>Cancel Invoice</b></td>
				<td colspan=2>
				<input  class="form-control varchar" name="input_corr_desc" id="input_corr_desc" placeholder="Correction  Desc." value="<?=$invoice_master[0]->correction_remark ?>" type="text" /> </td>
				<td>
					Return Amount :
				</td>
				<td><input style="width: 100px" class="form-control number" name="input_corr_amt" id="input_corr_amt" placeholder="Amount" value="<?=$invoice_master[0]->net_amount?>" type="text" readonly=true  /></td>
				<td><button type="button" class="btn btn-primary" id="btn_update_ded">Cancel</button></td>
			</tr>
			<?php }else{   ?>
			<tr>
				<td><b>Cancel Invoice</b></td>
				<td colspan=2><?=$invoice_master[0]->correction_remark ?></td>
				<td><?php if($invoice_master[0]->correction_crdr==1) { echo 'Return';} else {'Add';}  ?>
				</td>
				<td><?=$invoice_master[0]->correction_amount ?></td>
				<td></td>
			</tr>
			<?php }
			if ($invoice_master[0]->correction_net_amount>0) { ?>
			<tr>
				<th style="width: 10px">#</th>
				<th></th>
				<th></th>
				<th></th>
				<th>Final Amount</th>
				<th><?=$invoice_master[0]->correction_net_amount?></th>
				<th></th>
			</tr>
			 <?php
				}
			}
			 }?>
			 
			</table>
		</div>
        <!-- /.col -->
      </div>
	  
      <!-- /.row -->
	 <?php if($invoice_master[0]->payment_status>0) {  ?>
      	<div class="row no-print">
        <div class="col-xs-6">
          <a href="<?php echo '/PathLab/invoice_print/'.$invoice_master[0]->id;  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print</a>
        </div>
		<div class="col-xs-6">
          Payment Method by : <?=$invoice_master[0]->payment_mode_desc ?>           
        </div>
     </div>
	<?php } ?>
      <!-- /.row -->
</section>
<script>
$(document).ready(function(){
	
	function enable_btn()
		{
			$('#btn_update_ded').attr('disabled', false);
		}
	
	$('#btn_update_ded').click( function()
	{	$('#btn_update_ded').attr('disabled', true);
	
		if(confirm("Are you sure process this correction "))
		{
		$.post('/index.php/Invoice/update_correction_charges',{ "invoice_id": $('#invoice_id').val(), 
			"input_corr_desc": $('#input_corr_desc').val(), 
			"input_corr_amt": $('#input_corr_amt').val(),
			"optionsRadios_crdr":$('input:radio[name=optionsRadios_crdr]:checked').val()
			 }, function(data){
			load_form_div('/Invoice/showinvoice/'+$('#invoice_id').val(),'searchresult');
			});
		}else{
			setTimeout(enable_btn,5000);
			}
	});
	
});
</script>