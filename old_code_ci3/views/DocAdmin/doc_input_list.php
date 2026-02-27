<?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
<div class="box">
		<div class="box-header">
			<div class="col-md-4"><h3 class="box-title">Input List</h3></div>
			<div class="col-md-4"></div>
			<div class="col-md-4">
			<button onclick="load_form_div('/Doc_Admin/input_parameter_load/0/<?=$doc_id?>','test_div');" type="button" class="btn btn-primary">Add New Input</button>
			</div>
		</div>
		<!-- /.box-header -->
		<div class="box-body" >
			<table id="example2" class="table table-bordered table-striped TableData">
				<thead>
				<tr>
					<th>#</th>
					<th>Input Name</th>
					<th>Input Code</th>
					<th>Action</th>
				 </tr>
				</thead>
				<tbody>
				<?php for ($i = 0; $i < count($doc_Item_List); ++$i) 
				{ 
				
				?>
				<tr>
					<td><?=$i+1 ?></td>
					<td><?=$doc_Item_List[$i]->input_name ?></td>
					<td><?=$doc_Item_List[$i]->input_code ?></td>
					<td>
					<div class="btn-group-horizontal">
						<button type="button" class="btn btn-default" onclick="load_form_div('/Doc_Admin/input_parameter_load/<?=$doc_Item_List[$i]->item_id ?>/<?=$doc_id?>','test_div');" >
							<i class="fa fa-edit"></i></button>
						<button type="button" class="btn btn-default" onclick="remove_item('<?=$doc_id ?>','<?=$doc_Item_List[$i]->item_id?>');" >
							<i class="fa fa-remove"></i></button>
						<?php 
						$option_current=$doc_Item_List[$i]->item_id;
						$sort_current=$doc_Item_List[$i]->short_order;
						
						if($i+1 < count($doc_Item_List))
						{
							$option_next=$doc_Item_List[$i+1]->item_id;
							$sort_next=$doc_Item_List[$i+1]->short_order;
							
							echo '<button type="button" class="btn btn-default" onclick="sortchange('.$doc_id.','.$option_current.','.$sort_current.','.$option_next.','.$sort_next.')">
									<i class="fa fa-level-down"></i></button>';
						}
						if($i>0)
						{
							$option_prev=$doc_Item_List[$i-1]->item_id;
							$sort_prev=$doc_Item_List[$i-1]->short_order;
							
							echo '<button type="button" class="btn btn-default" onclick="sortchange('.$doc_id.','.$option_current.','.$sort_current.','.$option_prev.','.$sort_prev.')">
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
					<th>Input Name</th>
					<th>Input Code</th>
					<th>Action</th>
				 </tr>
				</tfoot>
			  </table>
		</div>
		<!-- /.box-body -->
</div>
<?php echo form_close(); ?>
<script>
	function sortchange(doc_id,option_current,sort_current,option_prev,sort_prev)
		{
			var post_str='/index.php/Doc_Admin/change_sort_item/'+doc_id+'/'+option_current+'/'+sort_current+'/'+option_prev+'/'+sort_prev;
			var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

			$.post(post_str,{ 
						"doc_id":doc_id,
						"<?=$this->security->get_csrf_token_name()?>":csrf_value
						}, function(data){
						$('#test_div').html(data);
					});
		}

	function remove_item(doc_id,doc_sub_id)
	{
		var post_str='/index.php/Doc_Admin/remove_input_item/'+doc_id+'/'+doc_sub_id;
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
			
			$.post(post_str,{ 
						"doc_id":doc_id,
						"<?=$this->security->get_csrf_token_name()?>":csrf_value
						}, function(data){
						load_form_div('/Doc_Admin/doc_input_list/'+doc_id,'test_div');
					});
	}

</script>