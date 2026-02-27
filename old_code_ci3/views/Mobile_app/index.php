<?php
// Retrieve session data
$session_set_value = $this->session->all_userdata();

if (isset($session_set_value['remember_me']) && $session_set_value['remember_me'] == "1") {
      $session_data = $this->session->userdata('logged_in'); 
      $login_status=1;
}else{
    $login_status=0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title><?=H_Name?></title>
  <meta charset="utf-8">
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
  
  
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css" integrity="sha384-B4dIYHKNBt8Bc12p+WXckhzcICo0wtJAoU8YZTY5qE0Id1GSseTk6S+L3BlXeVIU" crossorigin="anonymous">

    <link rel="stylesheet" href="<?=base_url('assets/css/main.css');?>">
</head>
<body>
 <nav class="navbar navbar-light " style="background-color: #e3f2fd;">
  <div class="container-fluid">
    <div class="navbar-header">
      <a class="navbar-brand" href="javascript:load_form('/Mobile_app/load_dash');">Home</a>
    </div>
  </div>
</nav> 
<div class="container-fluid" id="body_content">
<?php
      if($login_status==1) {
        $this->load->view('Mobile_app/dash_main');
      } else {   ?> 
      <div class="main_menu">         
          <a href="javascript:load_form('/Mobile_app/select_doctor_login');" class="btn btn-app ">
              <i class="fa fa-address-card"></i> Login First 
          </a>
      </div>
<?php } ?>
</div>
<div id="wait" style="display:none;width:30px;height:30px;border:0px solid black;position:absolute;top:50%;left:50%;padding:2px;">
<img style="width:100px" src="<?php echo base_url('assets/image/loading_img.gif'); ?>" /></div>

<!-- Morris.js charts
<script src="https://cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script> -->
<script src="<?= base_url('assets/js/raphael.min.js');?>"></script>

<script src="<?= base_url('assets/plugins/morris/morris.min.js');?>"></script>
<!-- Sparkline -->
<script src="<?= base_url('assets/plugins/sparkline/jquery.sparkline.min.js');?>"></script>
<!-- jvectormap -->
<script src="<?= base_url('assets/plugins/jvectormap/jquery-jvectormap-1.2.2.min.js');?>"></script>
<script src="<?= base_url('assets/plugins/jvectormap/jquery-jvectormap-world-mill-en.js');?>"></script>
<!-- jQuery Knob Chart -->
<script src="<?= base_url('assets/plugins/knob/jquery.knob.js');?>"></script>
<!-- Select2 -->
<script src="<?= base_url('assets/plugins/select2/select2.full.min.js');?>"></script>
<!-- InputMask -->
<script src="<?= base_url('assets/plugins/input-mask/jquery.inputmask.js');?>"></script>
<script src="<?= base_url('assets/plugins/input-mask/jquery.inputmask.date.extensions.js');?>"></script>
<script src="<?= base_url('assets/plugins/input-mask/jquery.inputmask.extensions.js');?>"></script>

<!-- date-range-picker -->
<script src="<?php echo base_url('assets')?>/bower_components/moment/min/moment.min.js"></script>
<script src="<?php echo base_url('assets')?>/bower_components/bootstrap-daterangepicker/daterangepicker.js"></script>
<!-- bootstrap datepicker -->
<script src="<?php echo base_url('assets')?>/bower_components/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>

<!-- bootstrap time picker -->
<script src="<?php echo base_url('assets')?>/plugins/timepicker/bootstrap-timepicker.min.js"></script>

<!-- SlimScroll 1.3.0 -->
<script src="<?= base_url('assets/plugins/slimScroll/jquery.slimscroll.min.js');?>"></script>
<!-- iCheck 1.0.1 -->
<script src="<?= base_url('assets/plugins/iCheck/icheck.min.js');?>"></script>

<!-- AdminLTE App -->
<script src="<?= base_url('assets/dist/js/app.min.js');?>"></script>

<!-- DataTables -->
  <script src="<?php echo base_url('assets')?>/bower_components/datatables.net/js/jquery.dataTables.min.js"></script>
<!-- DataTables -->
<script src="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.min.js');?>"></script>
<script src="<?= base_url('assets/plugins/datatables/jquery.dataTables.delay.min.js');?>"></script>
<script src="<?= base_url('assets/plugins/datatables/extensions/TableTools/js/dataTables.tableTools.js');?>"></script>

  <!-- SlimScroll -->
  <script src="<?php echo base_url('assets')?>/bower_components/jquery-slimscroll/jquery.slimscroll.min.js"></script>

  <!-- FastClick -->
  <script src="<?php echo base_url('assets')?>/bower_components/fastclick/lib/fastclick.js"></script>

  <!-- CK Editor -->
  <script src="<?php echo base_url('assets')?>/bower_components/ckeditor/ckeditor.js"></script>
  
  <!-- Bootstrap WYSIHTML5 -->
  <script src="<?php echo base_url('assets')?>/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js"></script>

  <!-- Page script -->
<script>

        function validateNumber(event) {
            var key = event.keyCode || event.which;;

            if (key == 8 || key == 46 || key == 190
             || key == 37 || key == 39 || key == 9) {
                return true;
            }
            else if (key < 48 || key > 57) {
                return false;
            }
            else return true;
        };

        function validateVarChar(event) {
            var key = event.keyCode || event.which;;

            if (key == 8 || key == 46
             || key == 37 || key == 39 ||
                key == 9 || key == 32 || key == 188 ||
                key == 189 || key == 190 || key == 219 || key == 221 ||
                key == 44 || key == 45 || key == 47 || key == 13) {
                return true;
            }
            else if (key >= 48 && key <= 57) {
                return true;
            }
            else if (key >= 65 && key <= 90) {
                return true;
            }
            else if (key >= 97 && key <= 122) {
                return true;
            }
            else {
                return false;
            }
        };

  //------------------------------------------------------
  function initfunc () {
  
      $(document).ajaxStart(function(){
          $("#wait").css("display", "block");
      });
      
      $(document).ajaxComplete(function(){
          $("#wait").css("display", "none");
      });
   
      $("#alert_show").hide();
        //Initialize Select2 Elements
      $(".select2").select2();

      //Datemask dd/mm/yyyy
      $("#datemask").inputmask("dd/mm/yyyy", {"placeholder": "dd/mm/yyyy"});
      //Datemask2 mm/dd/yyyy
      $("#datemask2").inputmask("mm/dd/yyyy", {"placeholder": "mm/dd/yyyy"});
      //Money Euro
      $("[data-mask]").inputmask();

      //Date range picker
      $('#reservation').inputmask("dd/mm/yyyy");
        $('#reservation').daterangepicker({format: 'DD/MM/YYYY'});
      
      //Date range picker with time picker
       $('.reservationtime').daterangepicker(
        {timePicker: true, 
        timePickerIncrement: 1, 
        format: 'MM/DD/YYYY h:mm A'});
          //Date range as a button
          $('#daterange-btn').daterangepicker(
              {
                ranges: {
                  'Today': [moment(), moment()],
                  'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                  'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                  'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                  'This Month': [moment().startOf('month'), moment().endOf('month')],
                  'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                },
                startDate: moment().subtract(29, 'days'),
                endDate: moment()
              },
              function (start, end) {
                $('#daterange-btn span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
              }
          );

          //Date picker
          $('.datepicker').datepicker({
            format: "dd/mm/yyyy",autoclose: true
          });

          //iCheck for checkbox and radio inputs
          $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
            checkboxClass: 'icheckbox_minimal-blue',
            radioClass: 'iradio_minimal-blue'
          });
          //Red color scheme for iCheck
          $('input[type="checkbox"].minimal-red, input[type="radio"].minimal-red').iCheck({
            checkboxClass: 'icheckbox_minimal-red',
            radioClass: 'iradio_minimal-red'
          });
          //Flat red color scheme for iCheck
          $('input[type="checkbox"].flat-red, input[type="radio"].flat-red').iCheck({
            checkboxClass: 'icheckbox_flat-green',
            radioClass: 'iradio_flat-green'
          });

          //Timepicker
          $(".timepicker").timepicker({
            showInputs: false,
          minuteStep: 1
          });
    
          //DateTimePicker2
          $('.datetimepicker2').datetimepicker(
           {
            locale: "en",
            showClear: true
            
           });
         
          
            $(".textarea").wysihtml5();
          
            $(".number").keypress(validateNumber);
            $(".varchar").keypress(validateVarChar);  
    
      }
 
      function load_form(ourl){
        $.ajax({
            url:ourl,
            dataType:"html",
            beforeSend: function()
            {
                $('#body_content').html('loading...');
                $("#wait").css("display", "block");
            }
            }).done(function(html){
                
                $("#wait").css("display", "none");
                $("#body_content").html(html);
                delete html;
                initfunc();
          });
      }

      function load_form_div(ourl,xdiv){
          $.ajax({
              url:ourl,
              dataType:"html",
              beforeSend: function()
              {
                  $("#wait").css("display", "block");
                  $("#" + xdiv).html('loading...');
              }
              }).done(function(html){
                  $("#wait").css("display", "none");
                  $("#" + xdiv).html(html);
                  initfunc();
          });
      }

      function load_report_div(ourl,xdiv){
        $("#" + xdiv).html('<embed src="'+ourl+'" width="800px" height="600px" />')
      }
  

    $(function () {

        initfunc ();
    });

    function logout()
    {
      load_form('/Mobile_app/logout');
    }
  
</script>


</body>
</html>
