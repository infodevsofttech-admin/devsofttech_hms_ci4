 <!-- Left side column. contains the logo and sidebar -->
  <aside class="main-sidebar">
    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">
      <!-- Sidebar user panel -->
      <!-- search form -->
      <form action="#" method="get" class="sidebar-form">
        <div class="input-group">
          <input type="text" name="q" class="form-control" placeholder="Search...">
              <span class="input-group-btn">
                <button type="submit" name="search" id="search-btn" class="btn btn-flat"><i class="fa fa-search"></i>
                </button>
              </span>
        </div>
      </form>
      <!-- /.search form -->
      <!-- sidebar menu: : style can be found in sidebar.less -->
      <ul class="sidebar-menu">
        <li class="header">MAIN NAVIGATION</li>
        <li>
          <a href="javascript:load_form('/dashboard','Dashboard');">
            <i class="fa fa-dashboard"></i> <span>Dashboard</span>
            <span class="pull-right-container">
              <small class="label pull-right bg-green">new</small>
            </span>
          </a>
        </li>
      <?php if ($this->ion_auth->in_group('Billing') ) { ?>
        <li>
          <a href="javascript:load_form('/Patient','Patient');"  accesskey="P">
          <img src="/assets/images/icon/patient.png" class="img_icon"  /><span> Patient</span>
          </a>
        </li>
        <?php } ?>
        
		<?php if ($this->ion_auth->in_group('MedicalStore') && (M_Pharmacy==1)) { ?>
		<li>
          <a href="javascript:load_form('/Medical','Pharma');"  accesskey="M">
          <img src="/assets/images/icon/pharmacy.png" class="img_icon"  /> <span> Medical</span>
          </a>
        </li>
		<?php }  ?>
    <?php if ($this->ion_auth->in_group('Billing') ) { ?>
        <li>
          <a href="javascript:load_form('/Report/index','Report : Hospital');"   accesskey="R">
          <img src="/assets/images/icon/reports.png" class="img_icon"  /><span>Reports</span>
          </a>
        </li>
        <?php }  ?>
    <?php if ($this->ion_auth->in_group('Billing') ) { ?>
		
    		<li class="treeview">
          <a href="#" >
            <i class="fa fa-folder"></i> <span>Invoice</span>
                <span class="pull-right-container">
                  <i class="fa fa-angle-left pull-right"></i>
                </span>
          </a>
          <ul class="treeview-menu">
            <li><a href="javascript:load_form('/Orgcase/search_all','OPD  Org. Invoice');"   ><i class="fa fa-circle-o"></i>OPD  Org. Invoice</a></li>
            <li><a href="javascript:load_form('/Invoice/opdlist','OPD Invoice');" ><i class="fa fa-circle-o"></i> OPD Invoice</a></li>
            <li><a href="javascript:load_form('/Invoice/chargeslist','Charges Invoice');"><i class="fa fa-circle-o"></i> Charges Invoice</a></li>
            <li><a href="javascript:load_form('/Invoice/list_refund','Refund Request');"><i class="fa fa-circle-o"></i> Refund Request</a></li>
            <li><a href="javascript:load_form('/Invoice/list_req_payment','Payment Request');"><i class="fa fa-circle-o"></i> Payment Request</a></li>
            <?php if ($this->ion_auth->in_group('admin')) { ?>
              <li><a href="javascript:load_form('/Payment','Payment Edit');"><i class="fa fa-circle-o"></i> Payment Edit</a></li>
            <?php } ?>
          </ul>
        </li>
        <?php } ?>
        <?php if($this->ion_auth->in_group('OPDAppointment')) { ?>
        <li>
          <a href="javascript:load_form('/Opd/get_appointment','Appointments');">
          <img src="/assets/images/icon/Appointment.png" class="img_icon"  /> <span> Appointments</span>
          </a>
        </li>
        <?php } ?>
        <?php if(($this->ion_auth->in_group('DiagnosisPanel')) && (M_Diagnois==1)) { ?>
		    <li>
          <a href="javascript:load_form('/Lab_Report/lab_master','Diagnosis');">
          <img src="/assets/images/icon/lab_test_icon.png" class="img_icon"  /> <span> Diagnosis</span>
          </a>
        </li>
        <?php } ?>
        <?php if(($this->ion_auth->in_group('IPDPanel')) && (M_IPD==1)) { ?>
        <li>
          <a href="javascript:load_form('/IpdNew/IpdDashboard','IPD Panel');">
          <img src="/assets/images/icon/ipd_img.png" class="img_icon"  /><span> IPD Panel</span>
          </a>
        </li>
        <?php } ?>
		<?php if ($this->ion_auth->in_group('admin')) { ?>
        <li class="treeview">
          <a href="javascript:load_form('/Setting','Admin Panel');">
          <img src="/assets/images/icon/admin.png" class="img_icon"  /><span>Admin Panel</span>
          </a>
        </li>
		<?php } ?>
		<li>
          <a href="Welcome/logout">
            <i class="fa  fa-users"></i> <span>Logout</span>
          </a>
        </li>
      </ul>
    </section>
    <!-- /.sidebar -->
  </aside>
