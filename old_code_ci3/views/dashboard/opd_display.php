<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  
  <title><?=H_Name?></title>
  
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

	<!-- jQuery 3 -->
	<script src="<?php echo base_url('assets')?>/bower_components/jquery/dist/jquery.min.js"></script>
		
	<!-- jQuery UI -->
	<link rel="stylesheet" href="<?= base_url('assets/bower_components/jquery-ui.custom/jquery-ui.css');?>">
	<script src="<?= base_url('assets/bower_components/jquery-ui.custom/jquery-ui.js');?>"></script>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         
	<!-- Bootstrap 3.3.7 -->
	<script src="<?php echo base_url('assets')?>/bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
	
	<!-- Bootstrap 3.3.7 -->
	<link rel="stylesheet" href="<?php echo base_url('assets')?>/bower_components/bootstrap/dist/css/bootstrap.min.css">
  
	<!-- Font Awesome -->
	<link rel="stylesheet" href="<?php echo base_url('assets')?>/bower_components/font-awesome/css/font-awesome.min.css">

	<!-- Ionicons -->
	<link rel="stylesheet" href="<?php echo base_url('assets')?>/bower_components/Ionicons/css/ionicons.min.css">
  
	<!-- daterange picker -->
	<link rel="stylesheet" href="<?php echo base_url('assets')?>/bower_components/bootstrap-daterangepicker/daterangepicker.css">
  
	<!-- bootstrap datepicker -->
	<link rel="stylesheet" href="<?php echo base_url('assets')?>/bower_components/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css">
  
	<!-- iCheck for checkboxes and radio inputs -->
	<link rel="stylesheet" href="<?php echo base_url('assets')?>/plugins/iCheck/all.css">
  
	<!-- Bootstrap time Picker -->
	<link rel="stylesheet" href="<?php echo base_url('assets')?>/plugins/timepicker/bootstrap-timepicker.min.css">
  
	<!-- Select2 -->
	<link rel="stylesheet" href="<?php echo base_url('assets')?>/bower_components/select2/dist/css/select2.min.css">
  
	<!-- bootstrap wysihtml5 - text editor -->
	<link rel="stylesheet" href="<?php echo base_url('assets')?>/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css">
  
	<link rel="stylesheet" href="<?php echo base_url('assets')?>/bower_components/bootstrap/dist/css/bootstrap.min.css">
	
	<link rel="stylesheet" href="<?php echo base_url('assets')?>/css/bootstrap-vertical-tabs.css">
  
	<!-- Font Awesome -->
	<link rel="stylesheet" href="<?php echo base_url('assets')?>/bower_components/font-awesome/css/font-awesome.min.css">
  
	<!-- Ionicons -->
	<link rel="stylesheet" href="<?php echo base_url('assets')?>/bower_components/Ionicons/css/ionicons.min.css">
  
	<!-- Theme style -->
	<link rel="stylesheet" href="<?php echo base_url('assets')?>/dist/css/AdminLTE.min.css">
  
	<!-- AdminLTE Skins. Choose a skin from the css/skins
		   folder instead of downloading all of them to reduce the load. -->
	<link rel="stylesheet" href="<?php echo base_url('assets')?>/dist/css/skins/_all-skins.min.css">
	
	<!-- Bootstrap time Picker -->
  <link rel="stylesheet" href="<?php echo base_url('assets')?>/plugins/timepicker/bootstrap-timepicker.min.css">
 
	
	<!-- DateTime Picker 2  -->
	
	<link href="<?php echo base_url('assets')?>/plugins/bootstrap-datetimepicker/bootstrap-datetimepicker.css" rel="stylesheet">
	<script src="<?php echo base_url('assets')?>/plugins/bootstrap-datetimepicker/moment.js"></script>
	<script src="<?php echo base_url('assets')?>/plugins/bootstrap-datetimepicker/bootstrap-datetimepicker.js"></script>
	
	<!--webcam--->
	<script src="<?= base_url('assets/js/webcam.min.js');?>"></script>
		
	<link href="<?= base_url('assets');?>/css/uploadfile.css" rel="stylesheet">
	<script src="<?= base_url('assets');?>/js/jquery.uploadfile.min.js"></script>
	
	<link href="<?= base_url('assets');?>/css/custom.css" rel="stylesheet">
  	<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  	<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  	<!--[if lt IE 9]>
  		<script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
  		<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
	  <![endif]-->
	  
	  <!--Video  -->
	  	<link href="<?= base_url('assets');?>/css/video-js.min.css" rel="stylesheet">
  		<link href="<?= base_url('assets');?>/css/videojs.record.css" rel="stylesheet">
		<script src="<?= base_url('assets');?>/js/video.min.js"></script>
  		<script src="<?= base_url('assets');?>/js/RecordRTC.js"></script>
  		<script src="<?= base_url('assets');?>/js/adapter.js"></script>
		<script src="<?= base_url('assets');?>/js/videojs.record.js"></script>

		<!--notify  --->
	<link href="<?= base_url('assets');?>/notify/dist/styles/metro/notify-metro.css" rel="stylesheet" />
	<script src="<?= base_url('assets');?>/notify/dist/notify.js"></script>
	<script src="<?= base_url('assets');?>/notify/dist/styles/metro/notify-metro.js"></script>
	<!---End Here --->
</head>
<body>
    <section class="content-header">
		<h1><?=H_Name?></h1>
    </section>
    <section class="content">
    <div class="row">
        <div class="col-md-6">
            <div class="box">
            <div class="box-header">
                <h3 class="box-title">Dr. B.C. Joshi</h3>
            </div>
            <div class="box-body no-padding">
                <table class="table table-condensed">
                <?php
                $srno=0;
                    foreach($opd_list_1 as $row)
                    {
                ?>
                    <tr>
                        <td style="width: 10px"><?=$row->queue_no?></td>
                        <td style="width: 10px"><?=$row->p_code?></td>
                        <td><?=$row->P_name?></td>
                    </tr>
                <?php
                    }
                ?>
                </table>
            </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box">
            <div class="box-header">
                <h3 class="box-title">Dr. Paritosh Joshi</h3>
            </div>
            <div class="box-body no-padding">
                <table class="table table-condensed">
                    <?php
                    $srno=0;
                        foreach($opd_list_2 as $row)
                        {
                    ?>
                        <tr>
                            <td style="width: 10px"><?=$row->queue_no?></td>
                            <td style="width: 10px"><?=$row->p_code?></td>
                            <td><?=$row->P_name?></td>
                        </tr>
                    <?php
                        }
                    ?>
                </table>
            </div>
            </div>
        </div>
    </div>
    </section>
</body>
<script>
	function DisplayOPD() {
      $.post('/index.php/Opd/opd_display',{ "dtime":"" }, function(data){
        },'json');
    }
</script>
