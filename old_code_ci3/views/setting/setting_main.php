<section class="content-header">
    <h1>
        Admin Panel
        <small>Dashboard</small>
    </h1>
</section>
<!-- Main content -->
<section class="content">
<?php echo form_open('MedicalDashboard', array('role'=>'form','class'=>'form1')); ?>
  	<div class="row">
		<div class="col-md-12">
			<a class="btn btn-app" href="javascript:load_form_div('/Doctor/search','maindiv','Doctor Master');">
				<i class="fa fa-user-md"></i>Doctors
			</a>
			<a class="btn btn-app" href="javascript:load_form_div('/auth_dst/search','maindiv','User Panel');">
				<i class="fa fa-user"></i>Users Admin
			</a>
			<a class="btn btn-app"  href="javascript:load_form_div('/insurance/search','maindiv','Insurance/TPA Master');" >
				<span class="badge bg-yellow" id="ipd_notification"></span>
				<i class="fa fa-h-square"></i> Insurance Admin
			</a>
			<a class="btn btn-app" href="javascript:load_form_div('/item/search','maindiv','OPD Charge Master');" >
				<i class="fa fa-medkit"></i> OPD Charges
			</a>
			<a class="btn btn-app" href="javascript:load_form_div('/Item_IPD/search','maindiv','Charges IPD');">
				<i class="fa fa-ambulance"></i>IPD Charges
			</a>
			<a class="btn btn-app" href="javascript:load_form_div('/Package/search','maindiv','Package IPD');">
				<i class="fa fa-list"></i> Package
			</a>
			<a class="btn btn-app" href="javascript:load_form_div('/Lab_Admin/report_list','maindiv','Diagnosis Template');">
				<i class="fa fa-heartbeat"></i> Pathology Template
			</a>
			<a class="btn btn-app" href="javascript:load_form_div('/Lab_Admin/report_ultrasound_list','maindiv','Diagnosis Template');">
				<i class="fa fa-heartbeat"></i> Ultra Sound Template
			</a>
			<a class="btn btn-app" href="javascript:load_form_div('/Doc_Admin/doc_list','maindiv','Document Template');">
				<i class="fa fa-file-text-o"></i> Document Template
			</a>
			<a class="btn btn-app" href="javascript:load_form_div('/Reffer/index','maindiv','Refferal Admin');">
				<i class="fa fa-share"></i> Refferal Admin
			</a>
		</div>
  	</div>
	  <?php echo form_close(); ?>
  <div  class="row">
  <div class="col-md-12" id="maindiv">
  
	</div>
  </div>
 </section>
 