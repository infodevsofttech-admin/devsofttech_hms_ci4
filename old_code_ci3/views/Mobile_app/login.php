<?php
    if (isset($logout_message)) {
      echo "<div class='message'>";
      echo $logout_message;
      echo "</div>";
  }
?>

<?php
    if (isset($message_display)) {
      echo "<div class='message'>";
      echo $message_display;
      echo "</div>";
  }

?>

<?php 
  $attributes = array('id' => 'myform');
  echo form_open('Mobile_app/login/'.$doc_id,$attributes); ?>
      <div class="row">
           <h2 style="text-align: center;">Welcome</h2>
           <h1 style="text-align: center;"> 
            <span class="text-danger">Dr. <?php echo ucfirst($doctor_data_profile[0]->p_fname); ?></span>
           </h1>
           <h2 style="text-align: center;"> 
            <span class="text-warning"><?php echo $doctor_data_profile[0]->SpecName; ?></span>
           </h2>
      </div>
      <div class="row">
        <hr />
        <br/>
        <br/>
      </div>
      <div class="row">
        <div class="col-sm-12">
          <div class="form-group">
            <label>Enter Your 4 Digit PIN No.</label>
            <input class="form-control"  
            type="tel" name="password"  id="password" 
            style="font-size:36pt;margin:5px;height:100px;text-align:center;letter-spacing: 50px;"
            align="center"
            data-inputmask='"mask": "9999"' data-mask />
          </div>
        </div>
        <div class="col-sm-12">
          <input type="checkbox" name="remember_me" checked /> Remember Me
        </div>
        <div class="col-sm-12">
          <input class="btn btn-primary" type="submit" value="Login " name="submit"/>
        </div>
        <div id="note" class="col-sm-12">
          <?php
              echo "<div class='error_msg'>";
              if (isset($error_message)) {
                echo $error_message;
              }
              echo validation_errors();
              echo "</div>";
          ?>
        </div>
      </div>
  <?php echo form_close();?>
<script>
    $(document).ready(function(){
      $('#myform').on('submit', function(form){
            form.preventDefault();
            form_array=$('#myform').serialize();
            $("#body_content").html('Data Posting....Please Wait');
            $.post('/Mobile_app/login/<?=$doc_id?>', form_array, function(data){
                 $("#body_content").html(data);
                 $("[data-mask]").inputmask();
            });
          });
        });
</script>