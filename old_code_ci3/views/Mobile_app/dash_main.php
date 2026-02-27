<?php
$session_data = $this->session->userdata('logged_in'); 
?>
<div class="row">
           <h1 style="text-align: center;"> 
            <span class="text-danger">Dr. <?php echo ucfirst($session_data['p_fname'].' '.$session_data['p_mname']); ?></span>
           </h1>
           <p style="text-align: center;">
           <input type="checkbox" class="minimal" id="chk_all_patient" name="chk_all_patient">
            All Patient
            </p>
</div>
<div class="row">
  <div class="col-md-12">
         <a href="javascript:Search_patient_all('-1');" class="btn btn-info ">
        <i class="fa fa-address-book"></i> Search Patient
      </a>
      <a href="javascript:Search_patient('0');" class="btn btn-success ">
          <i class="fa fa-child"></i> Today OPDs 
      </a>
  </div>
</div>
<div class="row">
<hr/>
  <div class="main_menu">
<?php 
        $currentDate = new DateTime(date('Y-m-d'));

        for($i=1;$i<8;$i++){ 
          $date_interval_str='P1D';
          $show_date= $currentDate->sub(new DateInterval($date_interval_str)); 
          $show_date_str = $show_date->format('D d-m-Y');
?>
          <a href="javascript:Search_patient('<?=$i?>');" class="btn btn-app ">
                <i class="fa fa-address-card"></i> OPD :  <?=$show_date_str?>
          </a>
<?php   }  ?>
        </div>
</div>
<div class="row">
<hr/>
  <div class="col-md-12"> 
    <a href="javascript:load_form('/Mobile_app/ipd_panel');" class="btn btn-warning">
            <i class="fa fa-bed"></i>IPD
    </a>
  </div>
</div>
<div class="row">
<hr/>
  <div class="col-md-12"> 
  <a href="javascript:logout();" class="btn btn-danger">
          <i class="glyphicon glyphicon-log-out"></i>Log Out 
  </a>
  </div>
</div>
<script>
  function Search_patient(day)
  {
    var doc_id=<?=$session_data['doc_id']?>;
    if ($('#chk_all_patient').is(":checked"))
    {
      doc_id=0;
    }
    load_form('/Mobile_app/patient_index/'+day+'/'+doc_id);
  }

  function Search_patient_all(day)
  {
    var doc_id=<?=$session_data['doc_id']?>;
    if ($('#chk_all_patient').is(":checked"))
    {
      doc_id=0;
    }
    load_form('/Mobile_app/patient_index_all/'+day+'/'+doc_id);
  }
</script>
