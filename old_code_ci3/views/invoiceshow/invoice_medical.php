
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
			<br />
			<b>Invoice #<?=$invoice_master[0]->inv_med_code ?></b><br>
			<b>Date :</b> <?=$invoice_master[0]->inv_date ?><br>
			<b>Patient ID :</b> <?=$invoice_master[0]->patient_code ?><br>
		  <?php 
			if($invoice_master[0]->payment_id>0 )
			{
					echo '<strong>Payment No. :</strong>'.$invoice_master[0]->payment_id.'<br>';
			}
			?>
		  <input type="hidden" id="med_invoice_id" name="med_invoice_id" value="<?=$invoice_master[0]->id?>" />
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
            <strong><?=$invoice_master[0]->inv_name ?></strong><br>
           	<b>Refer By :</b> <?=$invoice_master[0]->doc_name ?><br>
          </address>
        </div>
        <!-- /.col -->
      </div>
      <!-- /.row -->

      <!-- Table row -->
      <div class="row">
        <div class="col-xs-12 table-responsive">
          <table class="table table-striped ">
			<thead>
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
				<th>Vat</th>
				<th>Tax Amount</th>
				<th> Amount</th>
			</tr>
			</thead>
			<tbody>
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
					echo '<td>'.$row->vat.'</td>';
					echo '<td>'.$row->vamount.'</td>';
					echo '<td>'.$row->tamount.'</td>';
					echo '</tr>';
				}
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
				<th><?=$invoiceGtotal[0]->amt ?></th>
				<th></th>
				<th></th>
				<th><?=$invoiceGtotal[0]->vamt ?></th>
				<th><?=$invoiceGtotal[0]->Gtotal?></th>
				<th></th>
			</tr>
			</tbody>
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
		