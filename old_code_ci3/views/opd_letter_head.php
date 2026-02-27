<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title><?php
  if(defined('C_EST'))
  {
    echo C_EST ;
  }else{
     echo H_Name ;
  }
 

  ?></title>
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
    <section class="invoice" style="font-size:14px;">
    	<div class="row">
    		<div class="col-xs-12 ">
    			<img style="width:250px" src="<?php echo base_url('assets/images/blank_img.png'); ?>" /> 
    		</div>
    	</div>
      <div class="row">
    		  <div class="col-xs-4">
                UHID : <i><?=$patient_master[0]->p_code ?></i>
                <?php 
                  if($patient_master[0]->udai<>"")
                  {
                    echo ' /'.$patient_master[0]->udai;
                  }
                ?><br>
                Name : <strong><?=$patient_master[0]->title ?> <?=strtoupper($patient_master[0]->p_fname) ?></strong><br>
                <?=$patient_master[0]->p_relative ?> <?=strtoupper($patient_master[0]->p_rname) ?><br>
                Sex : <b><?=$patient_master[0]->xgender ?><i> <?=$patient_master[0]->age ?></i></b><br>
          </div>
      		<div class="col-xs-4">
      		   	OPD No.: <B><?=$opd_master[0]->opd_code ?>/ <?=$opd_master[0]->opd_id?></B><br>
              OPD Fee: <?=$opd_master[0]->opd_fee_amount ?> [<?=$opd_master[0]->opd_fee_desc ?>]<br>
              <b>Date: <?=$opd_master[0]->str_apointment_date ?></b><br>
              <?php if($opd_master[0]->opd_fee_type=='3'){
                if(count($old_opd)>0){
                  echo 'Valid Upto :'.$opd_master[0]->opd_Exp_Date.'<br>';
                }
              }else{ ?>
               <b> Valid Upto : <?=$opd_master[0]->opd_Exp_Date ?></b><br>
              <?php } ?>
              
          </div>
          <div class="col-xs-4">
              Sr No.:<i><?=$opd_master[0]->opd_no ?></i><br/>
              P.No. : <i><?=$patient_master[0]->mphone1 ?></i><br>
              Address : <?=$patient_master[0]->add1.','.$patient_master[0]->city?><br>
              
          </div>
    	</div>
    </section>


</html>
