<section class="content-header">
    <h1>
        <?php echo lang('edit_user_heading');?> 
        <small><?php echo lang('edit_user_subheading');?></small>
    </h1>
</section>
<section class="content">
<div id="infoMessage"><?php echo $message;?></div>

<?php echo form_open("", array('role'=>'form','class'=>'form1'));?>
    <div class="row">
		<div class="col-md-4">
			<div class="form-group">
            <?php echo lang('edit_user_fname_label', 'first_name');?> <br />
            <?php echo form_input($first_name);?>
			</div>
		</div>
		<div class="col-md-4">
			<div class="form-group">
            <?php echo lang('edit_user_lname_label', 'last_name');?> <br />
            <?php echo form_input($last_name);?>
			</div>
		</div>
		<?php
		  if($identity_column!=='email') {
			  echo '<div class="col-md-4">';
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
            <?php echo lang('edit_user_phone_label', 'phone');?> <br />
            <?php echo form_input($phone);?>
			</div>
		</div>
		<div class="col-md-4">
			<div class="form-group">
            <?php echo lang('edit_user_password_label', 'password');?> <br />
			<input type="password" name="password" value="" id="password" class="form-control" autocomplete="false">
			</div>
		</div>
		<div class="col-md-4">
			<div class="form-group">
            <?php echo lang('edit_user_password_confirm_label', 'password_confirm');?><br />
			<input type="password" name="password_confirm" value="" id="password_confirm" class="form-control" autocomplete="false">
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<div class="box box-danger">
				<div class="box-header with-border">
				<h3 class="box-title">Role Assign</h3>
				</div>
				<div class="box-body">
				<?php if ($this->ion_auth->is_admin()): ?>
			<?php 
					$module_head_name=""; 
				foreach ($groups_data as $group):
					if($module_head_name!=$group->module_name):
						if($module_head_name!=""){
							echo '</div>';
							echo '</div>';
						}
						echo '<div class="col-md-4">';
						echo '<div class="form-group">';
						echo '<h3>'.$group->module_name.'</h3>';
						$module_head_name=$group->module_name;
					endif	
				?>
					<label class="checkbox">
					<?php
						$gID=$group->id;
						$checked = null;
						$item = null;
						foreach($currentGroups as $grp) {
							if ($gID == $grp->id) {
								$checked= ' checked="checked"';
							break;
							}
						}
					?>
					<input type="checkbox" name="groups[]" value="<?php echo $group->id;?>"<?php echo $checked;?>>
					<?php echo htmlspecialchars($group->name,ENT_QUOTES,'UTF-8');?>
					</label>
					<?php endforeach ?>
					<?php echo '</div>';?>
				<?php endif ?>
				</div>
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
            $.post('/index.php/auth_dst/edit_user/<?=$user->id?>', $('form.form1').serialize(), function(data){
                $("#maindiv").html(data);
            });
        });
   });
</script>
