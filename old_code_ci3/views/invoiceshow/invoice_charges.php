  <section class="invoice">
      <!-- title row -->
      <div class="row">
        <div class="col-xs-12">
          <h2 class="page-header">
            <i class="fa fa-globe"></i> <?=H_Name?>
            <small class="pull-right">Print Date: <?=date('d/m/Y H:m:s') ?> </small>
          </h2>
        </div>
        <!-- /.col -->
      </div>
      <!-- info row -->
      <div class="row invoice-info">
		<div class="col-sm-3 invoice-col">
			<img style="width:60px" src="<?php echo base_url('assets/images/KHRC.png'); ?>" />
			
			<br />
			<b>Invoice #<?=$invoice_master[0]->invoice_code ?></b><br>
          <b>Date :</b> <?=$invoice_master[0]->inv_date ?><br>
          <b>Patient ID :</b> <?=$patient_master[0]->p_code ?><br>
		  
		  <input type="hidden" id="lab_invoice_id" name="lab_invoice_id" value="<?=$invoice_master[0]->id?>" />
		  <?php 
			if($invoice_master[0]->payment_id>0 )
			{
					echo '<strong>Payment No. :</strong>'.$invoice_master[0]->payment_id.'<br>';
			}
			?>
		</div>
        <div class="col-sm-6 invoice-col">
          From
          <address>
            <strong><?=H_Name?> </strong><br>
            <?=H_address_1?><br>
            <?=H_address_2?><br>
            Phone: <?=H_phone_No?><br>
			Email: <?=H_Email?>
          </address>
        </div>
        <!-- /.col -->
        <div class="col-sm-3 invoice-col">
          To
          <address>
            <strong><?=$patient_master[0]->p_fname ?></strong><br>
            Gender : <?=$patient_master[0]->xgender ?><br>
            Age : <?=$patient_master[0]->age ?><br>
            Phone No : <?=$patient_master[0]->mphone1 ?><br>
			<b>Refer By :</b> <?=$invoice_master[0]->refer_by_other ?><br>
          </address>
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
					echo '<td><i class="fa fa-inr"></i>'.$row->item_amount.'</td>';
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
			<?php if($invoice_master[0]->discount_amount>0) {?>
			<tr>
				<th style="width: 10px">#</th>
				<th>Deduction</th>
				<th><?=$invoice_master[0]->discount_desc ?></th>
				<th></th>
				<th></th>
				<th><i class="fa fa-inr"></i>-<?=$invoice_master[0]->discount_amount ?></th>
				<th></th>
			</tr>
			<?php } ?>
			<tr>
				<th style="width: 10px">#</th>
				<th></th>
				<th></th>
				<th></th>
				<th>Net Amount</th>
				<th><i class="fa fa-inr"></i><?=$invoice_master[0]->net_amount?></th>
				<th></th>
			</tr>
			<?php if($invoice_master[0]->correction_amount>0) {?>
			<tr>
				<th style="width: 10px">#</th>
				<th>Correction</th>
				<th><?=$invoice_master[0]->correction_remark ?></th>
				<th></th>
				<th></th>
				<th><i class="fa fa-inr"></i>-<?=$invoice_master[0]->correction_amount ?></th>
				<th></th>
			</tr>
			<tr>
				<th style="width: 10px">#</th>
				<th></th>
				<th></th>
				<th></th>
				<th>Final Amount</th>
				<th><i class="fa fa-inr"></i><?=$invoice_master[0]->correction_net_amount?></th>
				<th></th>
			</tr>
			<?php } ?>
			</table>
		</div>
        <!-- /.col -->
      </div>
      <!-- /.row -->
		<div class="payment_type">
        <!-- accepted payments column -->
		<b>Mode of Payment : </b><?=$invoice_master[0]->payment_mode_desc ?>
      </div>
	  <div class="row">
		<div class="col-xs-4 invoice-col">
			<b>Prepared By : <?=$invoice_master[0]->prepared_by ?></b>
		</div>
		<div class="col-xs-4 invoice-col">
			
		</div>
		<div class="col-xs-4 invoice-col">
		<b>Signature</b>	
		</div>
	  </div>
	   <hr />
		<div class="row">
			This is computer generated invoice , Signature and stamp not required
		</div>
		
      <!-- /.row -->
</section>