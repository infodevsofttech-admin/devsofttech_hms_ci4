<section class="content-header">
    <h1>
        <?php echo lang('edit_user_heading');?> 
        <small><?php echo lang('edit_user_subheading');?></small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Admin User</a></li>
        <li class="active">User</li>
    </ol>
</section>
<section class="content">
<div id="infoMessage"><?php echo $message;?></div>

<?php echo form_open("", array('role'=>'form','class'=>'form1'));?>
    <div class="row">
		<?php
		  if($identity_column!=='email') {
			  echo '<div class="col-md-3">';
			   echo '<div class="form-group">';
			  echo lang('edit_user_identity_label', 'identity');
			  echo form_input($identity);
			  echo '</div>
			</div>';
		  }
		?>
	</div>
	<div class="row">
		<div class="col-md-4">
			<div class="form-group">
            <?php echo lang('edit_user_password_label', 'password');?> <br />
            <?php echo form_input($password);?>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-4">
			<div class="form-group">
            <?php echo lang('edit_user_password_confirm_label', 'password_confirm');?><br />
            <?php echo form_input($password_confirm);?>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-6">
			<div class="form-group">
			  <?php echo form_hidden('id', $user->id);?>
			  <?php echo form_hidden($csrf); ?>

				<p><?php echo form_submit('submit', lang('edit_user_submit_btn'));?></p>
			</div>
		</div>
	</div>
<?php echo form_close();?>
</section>
<script>
$(document).ready(function(){
        $('form.form1').on('submit', function(form){
            form.preventDefault();
            $.post('/index.php/auth_ihc/edit_user_self/<?=$user->id?>', $('form.form1').serialize(), function(data){
                $("#Content1").html(data);
            });
        });
   });
</script>
