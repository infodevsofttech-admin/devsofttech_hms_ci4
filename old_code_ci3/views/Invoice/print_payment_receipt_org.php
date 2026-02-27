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
			<img style="width:60px" src="<?php echo base_url('assets/images/<?=H_logo?>'); ?>" />
			<br />
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
            <strong><?=$person_info[0]->p_fname ?></strong><br>
            Phone No : <?=$ipd_master[0]->P_mobile1 ?><br>
			<b>Payment Reciept No #<?=$ipd_payment[0]->payment_id ?></b><br>
			<b>Date :</b> <?=$ipd_payment[0]->pay_date ?><br>
			<b>IPD Code :</b> <?=$ipd_master[0]->ipd_code ?><br>
			</address>
        </div>
        <!-- /.col -->
        
      </div>
		<hr />
			<b>IPD Credit Amount : <?=$ipd_payment[0]->amount ?></b>
			<br/>
			<b>Remark :</b>
			<br />
			<?=$ipd_payment[0]->remark ?>
		<hr />
		<div class="payment_type">
        <!-- accepted payments column -->
		<b>Mode of Payment : </b><?=$ipd_payment[0]->payment_mode_desc ?>
      </div>
	  <div class="row">
		<div class="col-xs-4 invoice-col">
			<b>Prepared By : <?=$ipd_payment[0]->prepared_by ?></b>
		</div>
		<div class="col-xs-4 invoice-col no-print">
			<a href="<?php echo '/Ipd/ipd_cash_print/'.$ipd_master[0]->id.'/'.$ipd_payment[0]->id.'/1';  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print</a>
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
      