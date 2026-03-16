<section class="content-header">
    <h1>
        <?php echo lang('create_user_heading');?> 
        <small><?php echo lang('create_user_subheading');?></small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Admin User</a></li>
        <li class="active">New User</li>
    </ol>
</section>
<!-- Main content -->
<section class="content">
<div id='userform'>
<div id="infoMessage"><?php echo $message;?></div>

<?php echo form_open("", array('role'=>'form','class'=>'form1'));?>
	<div class="row">
		<div class="col-md-3">
			<div class="form-group">
			<?php echo lang('create_user_fname_label', 'first_name');?> <br />
            <?php echo form_input($first_name);?>
			</div>
		</div>
		<div class="col-md-3">
			<div class="form-group">
			<?php echo lang('create_user_lname_label', 'last_name');?> <br />
            <?php echo form_input($last_name);?>
			</div>
		</div>
      <?php
      if($identity_column!=='email') {
          echo '<div class="col-md-3">';
		   echo '<div class="form-group">';
          echo lang('create_user_identity_label', 'identity');
         
          echo form_error('identity');
          echo form_input($identity);
          echo '</div>
		</div>';
      }
      ?>
	</div>
    <div class="row">
		<div class="col-md-3">
			<div class="form-group">
            <?php echo lang('create_user_email_label', 'email');?> <br />
            <?php echo form_input($email);?>
			</div>
		</div>
		<div class="col-md-3">
			<div class="form-group">
            <?php echo lang('create_user_phone_label', 'phone');?> <br />
            <?php echo form_input($phone);?>
			</div>
		</div>
    </div>
	<div class="row">
		<div class="col-md-3">
			<div class="form-group">		
            <?php echo lang('create_user_password_label', 'password');?> <br />
            <?php echo form_input($password);?>
			</div>
		</div>
		<div class="col-md-3">
			<div class="form-group">
            <?php echo lang('create_user_password_confirm_label', 'password_confirm');?> <br />
            <?php echo form_input($password_confirm);?>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-3">
			<p><?php echo form_submit('submit', lang('create_user_submit_btn'));?></p>
		</div>
	</div>

<?php echo form_close();?>
</div>
</section>
<script>
$(document).ready(function(){
        $('form.form1').on('submit', function(form){
            form.preventDefault();
            $.post('/index.php/auth_ihc/save_new_user', $('form.form1').serialize(), function(data){
                if(data.insertid==0)
                {
                    $('div.jsError').html(data.error_text);
                }else
                {
                    load_form('/auth_ihc/index);
                }
            }, 'json');
        });
   });
</script>