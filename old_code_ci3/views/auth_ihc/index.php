<br /><br />
<div id="infoMessage"><?php echo $message;?></div>
<div class="box">
<div class="box-header">
  <h3 class="box-title"><?php echo lang('index_heading');?></h3>
</div>
<!-- /.box-header -->
<div class="box-body">
<p><?php echo lang('index_subheading');?></p>
<table id="datashow1" class="table table-bordered table-striped TableData">
	<thead>
	<tr>
		<th><?php echo lang('index_fname_th');?></th>
		<th><?php echo lang('index_lname_th');?></th>
		<th><?php echo lang('index_email_th');?></th>
		<th><?php echo lang('index_groups_th');?></th>
		<th><?php echo lang('index_status_th');?></th>
		<th><?php echo lang('index_action_th');?></th>
	</tr>
	</thead>
	<tbody>
	<?php foreach ($users as $user):?>
		<tr>
            <td><?php echo htmlspecialchars($user->first_name,ENT_QUOTES,'UTF-8');?></td>
            <td><?php echo htmlspecialchars($user->last_name,ENT_QUOTES,'UTF-8');?></td>
            <td><?php echo htmlspecialchars($user->email,ENT_QUOTES,'UTF-8');?></td>
			<td>
				<?php foreach ($user->groups as $group):?>
					<?php echo htmlspecialchars($group->name,ENT_QUOTES,'UTF-8') ;?><br />
                <?php endforeach?>
			</td>
			<td><?php echo ($user->active) ? anchor("auth_ihc/deactivate/".$user->id, lang('index_active_link')) : anchor("auth/activate/". $user->id, lang('index_inactive_link'));?></td>
			<td><a href="javascript:load_form('auth_ihc/edit_user/<?=$user->id?>')">Edit</a></td>
		</tr>
	<?php endforeach;?>
	</tbody>
</table>
<script>
	$('#datashow1').dataTable();
</script>
</div>
<!-- /.box-body -->
<div class="box-footer">
<p>
<p><a href="javascript:load_form('auth_ihc/create_user_form')" > Create a new user</a> | 
<a href="javascript:load_form('auth_ihc/create_group')"> Create a new group</a></p>
</div>
</div>