<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title><?=H_Name?> | Invoice</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Bootstrap 3.3.6 -->
  
  <link href="<?php echo base_url('assets/css/bootstrap.min.css'); ?>" rel="stylesheet">
  <!-- Font Awesome -->
  
  <link rel="stylesheet" href="<?php echo base_url('assets/css/font-awesome.min.css'); ?>">
  <!-- Ionicons -->
  
  <link rel="stylesheet" href="<?php echo base_url('assets/css/ionicons.min.css'); ?>">
    <!-- Theme style -->
  
  <link rel="stylesheet" href="<?php echo base_url('assets/dist/css/AdminLTE.min.css'); ?>">

  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->
</head>
<body onload="window.print();">
<div class="wrapper">
  <section class="invoice">
      <!-- title row -->
      <div class="row">
        <div class="col-xs-12">
          <h2 class="page-header">
            <i class="fa fa-globe"></i> <?=H_Name?>
          </h2>
        </div>
        <!-- /.col -->
      </div>
      <!-- info row -->
      <div class="row invoice-info">
		<div class="col-sm-3 invoice-col">
			<img style="width:60px" src="<?php echo base_url('assets/images/'.H_logo); ?>" />
			<img src="/Testcont/bar2/<?=$ipd_master[0]->ipd_code?>:PayID-<?=$ipd_payment[0]->payment_id?>:PT<?=date('dmYhis')?>:" style="margin-left: 10px;" />
			<br />
		</div>
        <div class="col-sm-6 invoice-col">
          From
          <address>
          <strong><?=H_Name?></strong><br>
            <?=H_address_1?><br/>
            <?=H_address_2?><br/>
          </address>
        </div>
        <!-- /.col -->
        <div class="col-sm-3 invoice-col">
          To
          <address>
            <strong><?=$person_info[0]->p_fname ?></strong><br>
			<b>Patient Code :</b> <?=$person_info[0]->p_code ?><br>
            Phone No : <?=$ipd_master[0]->P_mobile1 ?><br>
			<b>Payment Receipt No #<?=$ipd_payment[0]->payment_id ?></b><br>
			<b>Date :</b> <?=$ipd_payment[0]->pay_date ?><br>
			<b>IPD Code :</b> <?=$ipd_master[0]->ipd_code ?><br>
			</address>
        </div>
        <!-- /.col -->
        
      </div>
		<hr />
		<div class="payment_type">
        <!-- accepted payments column -->
		<b>Payment Receipt No #<?=$ipd_payment[0]->payment_id ?></b><br>
		<p>
			<b>Mode of Payment : </b><?=$ipd_payment[0]->payment_mode_desc ?>
		</p>
		<p>
			<b>IPD Payment Amount : <?=$ipd_payment[0]->amount ?></b> (In Words : <?=number_to_word($ipd_payment[0]->amount) ?> )
		</p>
		<p>
			<b>Remark :</b>
		</p>
			<?=$ipd_payment[0]->remark ?>
      </div>
	  <div class="row">
		<div class="col-xs-4 invoice-col">
			<b>Prepared By : <?=$ipd_payment[0]->prepared_by ?></b>
		</div>
		<div class="col-xs-4 invoice-col no-print">
			
		</div>
		<div class="col-xs-4 invoice-col">
		<b>Signature</b>	
		</div>
	  </div>
	   <hr />
		<div class="row">
		<div class="col-xs-12 invoice-col">
			This is computer generated invoice , Signature and stamp not required
		</div>
		</div>
		
      <!-- /.row -->
</section>
      
</div>
</body>
</html>
