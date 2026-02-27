<br /><br />
<div class="box">
<div class="box-header">
  <h3 class="box-title">Reffer Person Name / clinic / Hospital</h3>
</div>
<!-- /.box-header -->
<div class="box-body">
<table id="datashow1" class="table table-bordered table-striped TableData">
	<thead>
	<tr>
		<th>Name</th>	
		<th>Type</th>
		<th>Register Date</th>
		<th>Status</th>
        <th>Edit</th>
	</tr>
	</thead>
	<tbody>
	<?php foreach ($refer_master as $row):?>
		<tr>
			<td><?=$row->title.' '.$row->f_name?></td>
            <td><?php 
                if($row->refer_type==0){
                    echo 'Person';                    
                } elseif($row->refer_type==1){
                    echo 'Doctor';
                }elseif($row->refer_type==2){
                    echo 'Staff';
                }elseif($row->refer_type==3){
                    echo 'Hospital/Clinic';
                }else{
                    echo 'Others';
                }         
            
            ?></td>
            <td><?=$row->str_dateadd?></td>
			<td>
			<?php 
				if($row->active){
					echo "<a href='javascript:load_form(\"Reffer/activate_status/".$row->id."/0\")'>".lang('index_active_link')."</a>";
				}else{
					echo "<a href='javascript:load_form(\"Reffer/activate_status/".$row->id."/1\")'>".lang('index_inactive_link')."</a>";
				} 
			?>
			</td>
			<td><a href="javascript:load_form('Reffer/reffer_edit/<?=$row->id?>')">Edit</a></td>
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
<p><a href="javascript:load_form('Reffer/reffer_add')" > Create a new Refferal Client</a> | 
</p>
</div>
</div>