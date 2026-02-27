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
		<th>User Name</th>
		<th><?php echo lang('index_fname_th');?></th>
		<th><?php echo lang('index_lname_th');?></th>
		<th><?php echo lang('index_email_th');?></th>
		<th><?php echo lang('index_status_th');?></th>
		<th><?php echo lang('index_action_th');?></th>
	</tr>
	</thead>
	<tbody>
	<?php foreach ($users as $user):?>
		<tr>
			<td><?php echo htmlspecialchars($user->username,ENT_QUOTES,'UTF-8');?></td>
            <td><?php echo htmlspecialchars($user->first_name,ENT_QUOTES,'UTF-8');?></td>
            <td><?php echo htmlspecialchars($user->last_name,ENT_QUOTES,'UTF-8');?></td>
            <td><?php echo htmlspecialchars($user->email,ENT_QUOTES,'UTF-8');?></td>
			<td>
			<?php 
				if($user->active){
					echo "<a href='javascript:load_form_div(\"auth_dst/activate_status/".$user->id."/0\",\"maindiv\",\"User Panel\")'>".lang('index_active_link')."</a>";

				}else{
					echo "<a href='javascript:load_form_div(\"auth_dst/activate_status/".$user->id."/1\",\"maindiv\",\"User Panel\")'>".lang('index_inactive_link')."</a>";
				} 
			?>
			</td>
			<td><a href="javascript:load_form_div('auth_dst/edit_user/<?=$user->id?>','maindiv','User Panel:<?=$user->username?>')">Edit</a></td>
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
<p><a href="javascript:load_form_div('auth_dst/create_user_form','maindiv','User Panel')" > Create a new user</a> </p>
</div>
</div>