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

	
	<!-- lazysizes  Image Loader -->
		<script src="<?= base_url('assets');?>/js/lazysizes.min.js" async=""></script>
	<!---End Here --->
	

</head>
<body class="hold-transition skin-green sidebar-mini sidebar-collapse " >

<div class="wrapper">
	<header class="main-header">
    <!-- Logo -->
    <a href="/welcome" class="logo">
	<!-- mini logo for sidebar mini 50x50 pixels -->
	<span class="logo-mini"><b>DST</b></span>
	<!-- logo for regular state and mobile devices -->
	<span class="logo-lg"><b>DevSoft Tech</b></span>
    </a>
    <!-- Header Navbar: style can be found in header.less -->
    <nav class="navbar navbar-static-top">
	<!-- Sidebar toggle button-->
	<a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
	</a>
	<?php 
    $attributes = array('id' => 'myform');
    echo form_open('Report/customer_list_1',$attributes); ?>
    <div class="navbar-custom-menu">
        <ul class="nav navbar-nav">
			<li class="dropdown user user-menu " >
				<a href="javascript:load_form('/Welcome/opd_online_list','IPD');" >
				<img src="/assets/images/icon/Appointment.png" class="img_icon"  /> 
					<span style="color: yellow;font-size: 15px;">Online OPD : <span id="onlineopd" ><?=$No_of_online_opd?></span></span> 
				</a>
			</li>
			<?php if(M_IPD==1) { ?>
			<?php if ($this->ion_auth->in_group('IPDPanel')) { ?>
			<li class="dropdown user user-menu">
				<a href="javascript:load_form('/Ipd/IpdDashboard','IPD');" >
				<img src="/assets/images/icon/ipd_img.png" class="img_icon"  /> IPD
				</a>
			</li>
			<?php } ?>
			<?php } ?>
			<?php if ($this->ion_auth->in_group('OPDAppointment')) { ?>
			<li class="dropdown user user-menu">
				<a href="javascript:load_form('/Opd/get_appointment','OPD Appointment');" >
				<img src="/assets/images/icon/Appointment.png" class="img_icon"  /> OPD Appointment
				</a>
			</li>
			<?php } ?>
			<?php if(M_Pharmacy==1) { ?>
			<?php if ($this->ion_auth->in_group('MedicalStore')) { ?>
			<li class="dropdown user user-menu">
				<a href="javascript:load_form('/Medical','Pharmacy');" >
				<img src="/assets/images/icon/pharmacy.png" class="img_icon"  /> Pharmacy
				</a>
			</li>
			<?php }  ?>
			<?php } ?>
			<?php if(M_Diagnois==1) { ?>
			<?php if ($this->ion_auth->in_group('DiagnosisPanel')) { ?>
			<li class="dropdown user user-menu">
				<a href="javascript:load_form('/Lab_Report/lab_master','Diagnosis');" >
				<img src="/assets/images/icon/lab_test_icon.png" class="img_icon"  /> Diagnosis
				</a>
			</li>
			<?php } ?>
			<?php } ?>
			<?php if(M_OPD==1) { ?>
			<?php if ($this->ion_auth->in_group('OPDAppointment')) { ?>
			<li class="dropdown user user-menu">
				<a href="javascript:load_form('opd/scan_opd_desktop/<?=$user->id?>','OPD Scan');" class="dropdown-toggle" >
				<img src="/assets/images/icon/iball_scan.png" class="img_icon"  /> OPD Scan
				</a>
			</li>
			<?php } ?>
			<?php } ?>
			<?php if(M_STOCK==1) { ?>
			<?php if ($this->ion_auth->in_group('StoreStock')) { ?>
			<li class="dropdown user user-menu">
				<a href="javascript:load_form('/Storestock','Store Stock');" class="dropdown-toggle" >
				<img src="/assets/images/icon/store.png" class="img_icon"  /> Store Stock
				</a>
			</li>
			<?php } ?>
			<?php } ?>
			<li class="dropdown user user-menu">
				<a href="javascript:load_form('auth_dst/edit_user_self/<?=$user->id?>','User Profile');" class="dropdown-toggle" >
				<span class="hidden-xs"><?php echo $user->first_name.' '.$user->last_name ?></span>
				[<small><?=$user->username ?></small>]
				</a>
			</li>
			<!-- Control Sidebar Toggle Button -->
        </ul>
		</div>
	<?php echo form_close(); ?>
    </nav>
	</header>
	<script>
	//var ResInterval = window.setInterval('update_onlineopd()', 360000); // 60 seconds

	var update_onlineopd = function() {
	var csrf_name='<?=$this->security->get_csrf_token_name()?>';
	var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

	$.post('/index.php/Welcome/update_online_opd',
		{
			'<?=$this->security->get_csrf_token_name()?>':csrf_value
		}, function(data){
			$("#onlineopd").html(data.online_opd);
	},'json');
};
</script>