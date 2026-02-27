<?php
$session_data = $this->session->userdata();
?>
<div class="row">
  <h1 style="text-align: center;">
    <span class="text-danger"><?php echo ucfirst($session_data['p_fname'] . ' ' . $session_data['p_mname']); ?></span>
  </h1>
</div>
<div class="row">
  <div class="col-md-12">
    <a href="javascript:Search_patient('0');" class="btn btn-success ">
      <i class="fa fa-child"></i> Current IPDs
    </a>
    <a href="javascript:load_form('/Medical_app/ipd_panel_search');" class="btn btn-info ">
      <i class="fa fa-address-book"></i> Search Discharge Patient
    </a>
    <a href="javascript:load_form('/Medical_app/opd_bill_search')" class="btn btn-warning ">
      <i class="fa fa-address-book"></i> Search Bill
    </a>
  </div>
</div>
<div class="row">
  <hr />

</div>
<div class="row">
  <hr />
  <div class="col-md-12">
    <a href="javascript:load_form('/Medical_app/ipd_panel');" class="btn btn-warning">
      <i class="fa fa-bed"></i>Current IPD
    </a>
  </div>
</div>
<div class="row">
  <hr />
  <div class="col-md-12">
    <a href="javascript:logout();" class="btn btn-danger">
      <i class="glyphicon glyphicon-log-out"></i>Log Out
    </a>
  </div>
</div>
