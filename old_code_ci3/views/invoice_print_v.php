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
<?php
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'-'.$user->id.'';
?>
<body onload="window.print();">

  <section class="invoice" style="font-size:11px;">
	<!-- title row -->
      <div class="row">
        <div class="col-xs-12">
          <h2 class="page-header">
            <i class="fa fa-globe"></i> <?=H_Name?>
            <small class="pull-right">Printed on: <?=date('d-m-Y h:m:s')?></small>
          </h2>
        </div>
        <!-- /.col -->
      </div>
	      <!-- info row -->
      <div class="row invoice-info">
		<div class="col-sm-3 invoice-col">
		<img style="width:60px" src="<?php echo base_url('assets/images/KHRC.png'); ?>" />
		<img src="/Testcont/bar2/<?=$patient_master[0]->p_code?>:OPD-<?=$opd_master[0]->opd_code ?>:T<?=date('dmYhms')?>:" style="margin-left: 10px;" />
		<br />
		<br />
		<b>OPD Invoice ID:</b> <?=$opd_master[0]->opd_code ?><br>
        <b>Patient ID :</b> <?=$patient_master[0]->p_code ?><br>
		
		<?php
			if($opd_master[0]->insurance_id>1)
			{
				echo '<strong> Ins. Comp. :</strong>'.$insurance[0]->ins_company_name.'<br>';
				if($opd_master[0]->insurance_case_id>0)
				{
					echo '<strong> Org.Case No. :</strong>'.$case_master[0]->case_id_code.'<br>';
				}
			}
		?>
		<input type="hidden" id="oid" name="oid" value="<?=$opd_master[0]->opd_id ?>" />
		<?php 
			if($opd_master[0]->payment_id>0 )
			{
					echo '<strong>Payment No. :</strong>'.$opd_master[0]->payment_id.'<br>';
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
		  <?php if(!($opd_master[0]->opd_no=='' || $opd_master[0]->opd_no==0))  { ?>
			<h3> Sr.No.<?=$opd_master[0]->opd_no ?></h3>
		  <?php } ?>
        </div>
        <!-- /.col -->
        <div class="col-sm-3 invoice-col">
          To
          <address>
            <strong> <?=$patient_master[0]->title ?> <?=strtoupper($patient_master[0]->p_fname) ?></strong><br>
            Date : <?=$opd_master[0]->str_apointment_date ?><br>
            Gender : <?=$patient_master[0]->xgender ?><br>
            Age : <?=$patient_master[0]->age ?><br>
            Phone No : <?=$patient_master[0]->mphone1 ?><br>
            No. of Times Visit : <?=$opd_master[0]->no_visit ?>
          </address>
        </div>
        <!-- /.col -->
      </div>
      <!-- /.row -->
      <!-- Table row -->
      <div class="row">
        <div class="col-xs-12 table-responsive">
          <table class="table table-striped">
            <thead>
            <tr>
                <th>Date</th>
                <th>Doctor</th>
                <th>Department</th>
  			       <th>Desc</th>
  			       <th>OPD Fee</th>
            </tr>
            </thead>
            <tbody>
            <tr>
              <td><?=$opd_master[0]->apointment_date ?></td>
              <td>Dr. <?=$opd_master[0]->doc_name ?></td>
              <td><?=$opd_master[0]->doc_spec ?></td>
			  <td><?=$opd_master[0]->opd_fee_desc ?></td>
              <td><i class="fa fa-inr"></i> <?=$opd_master[0]->opd_fee_gross_amount ?></td>
            </tr>
			<?php 
			if($opd_master[0]->opd_discount>0 )
			{   
			?>
			<tr>
			  <td>Deduction</td>
              <td colspan=2><?=$opd_master[0]->opd_disc_remark ?></td>
              <td><i class="fa fa-inr"></i> -<?=$opd_master[0]->opd_discount ?></td>
			  <td></td>
            </tr>
			<?php }  ?>
			<tr>
              <td></td>
			  <td></td>
              <td colspan=2>Net Amount</td>
              <td><i class="fa fa-inr"></i> <?=$opd_master[0]->opd_fee_amount ?></td>
			  
            </tr>
            </tbody>
          </table>
        </div>
        <!-- /.col -->
      </div>
      <!-- /.row -->
      <div class="payment_type">
        <!-- accepted payments column -->
		<?php if($opd_master[0]->opd_status==3) {
			echo '<h1> OPD cancelled </h1><Br/>';
			echo '<p>'.$opd_master[0]->opd_status_remark.'</p>';
		}else{
		?>
		
		<b>Mode of Payment : </b><?=$opd_master[0]->payment_mode_desc ?>
		
		<?php }  ?>
      </div>
	  <div class="row">
		<div class="col-xs-4 invoice-col">
			<b>Prepared By : </b><?=$opd_master[0]->prepared_by ?>
		</div>
		<div class="col-xs-4 invoice-col">
			
		</div>
		<div class="col-xs-4 invoice-col">
		<b>Signature</b>	
		</div>
	  </div>
	   <hr />
	   <div class="row">
		<img src="/Testcont/bar1/<?=$patient_master[0]->p_code?>:T<?=date('dmYhms')?>:" style="margin-left: 10px;" />
	   </div>
		<div class="row">
			This is computer generated invoice , Signature and stamp not required
		</div>
	  
      <!-- /.row -->
</section>

</body>
</html>
