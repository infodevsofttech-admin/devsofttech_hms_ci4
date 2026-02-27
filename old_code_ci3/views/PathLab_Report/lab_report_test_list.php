<?php echo form_open(); ?>
<div class="box">
		<div class="box-header">
			<div class="col-md-4"><h3 class="box-title">Test List</h3></div>
			<div class="col-md-4"></div>
			<div class="col-md-4">
			<button onclick="load_form_div('/Lab_Admin/test_search_page/<?=$mstRepoKey?>','test_div');" type="button" class="btn btn-primary">Add New Test</button>
			</div>
		</div>
		<!-- /.box-header -->
		<div class="box-body" >
			<table id="example2" class="table table-bordered table-striped TableData">
				<thead>
				<tr>
					<th>#</th>
					<th>Test Name</th>
					<th>Test Code</th>
					<th>Action</th>
				 </tr>
				</thead>
				<tbody>
				<?php for ($i = 0; $i < count($lab_Rep_Item_List); ++$i) 
				{ 
				
				?>
				<tr>
					<td><?=$i+1 ?></td>
					<td><?=$lab_Rep_Item_List[$i]->Test ?></td>
					<td><?=$lab_Rep_Item_List[$i]->TestID ?></td>
					<td>
					<div class="btn-group-horizontal">
						<button type="button" class="btn btn-default" onclick="load_form_div('/Lab_Admin/test_parameter_load/<?=$lab_Rep_Item_List[$i]->mstTestKey ?>/<?=$mstRepoKey?>','test_div');" >
							<i class="fa fa-edit"></i></button>
						<button type="button" class="btn btn-default" onclick="remove_item('<?=$mstRepoKey ?>','<?=$lab_Rep_Item_List[$i]->mstTestKey?>');" >
							<i class="fa fa-remove"></i></button>
						<?php 
						$option_current=$lab_Rep_Item_List[$i]->id;
						$sort_current=$lab_Rep_Item_List[$i]->EOrder;
						
						if($i+1 < count($lab_Rep_Item_List))
						{
							$option_next=$lab_Rep_Item_List[$i+1]->id;
							$sort_next=$lab_Rep_Item_List[$i+1]->EOrder;
							
							echo '<button type="button" class="btn btn-default" onclick="sortchange('.$mstRepoKey.','.$option_current.','.$sort_current.','.$option_next.','.$sort_next.')">
									<i class="fa fa-level-down"></i></button>';
						}
						if($i>0)
						{
							$option_prev=$lab_Rep_Item_List[$i-1]->id;
							$sort_prev=$lab_Rep_Item_List[$i-1]->EOrder;
							
							echo '<button type="button" class="btn btn-default" onclick="sortchange('.$mstRepoKey.','.$option_current.','.$sort_current.','.$option_prev.','.$sort_prev.')">
									<i class="fa fa-level-up"></i></button>';

						}
						?>
					</div>
					</td>
				</tr>
				<?php } ?>
				</tbody>
				<tfoot>
				<tr>
					<th>#</th>
					<th>Test Name</th>
					<th>Test Code</th>
					<th>Action</th>
				 </tr>
				</tfoot>
			  </table>
		</div>
		<!-- /.box-body -->
</div>
<?php echo form_close(); ?>
<script>
	function sortchange(mstRepoKey,option_current,sort_current,option_prev,sort_prev)
		{
			var post_str='/index.php/Lab_Admin/change_sort_item/'+mstRepoKey+'/'+option_current+'/'+sort_current+'/'+option_prev+'/'+sort_prev;
			var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

			$.post(post_str,{ 
						"mstRepoKey":mstRepoKey,
						'<?=$this->security->get_csrf_token_name()?>':csrf_value
						}, function(data){
						$('#test_div').html(data);
					});
		}

	function remove_item(mstRepoKey,mstTestKey)
	{
		var post_str='/index.php/Lab_Admin/remove_test_item/'+mstRepoKey+'/'+mstTestKey;
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

			$.post(post_str,{ 
						"mstRepoKey":mstRepoKey,
						'<?=$this->security->get_csrf_token_name()?>':csrf_value
						}, function(data){
						load_form_div('/Lab_Admin/report_test_list/'+mstRepoKey,'test_div');
					});
	}

</script>