<?php echo form_open(); ?>
		<input type="hidden" id="hid_value_req_id" value="<?=$lab_request_master[0]->id ?>">
		<div class="box">
		<div class="box-header">
		  <h3 class="box-title">Patient Name :<?=$lab_request_master[0]->patient_name ?>  </h3>
		  <small> / <?=$lab_request_master[0]->invoice_code ?></small>
		</div>
		<!-- /.box-header -->
		<div class="box-body" style="height:500px;overflow-y:auto;">
			<?php if(count($lab_request_item_entry)>0) { ?>
		  <table id="example1" class="table table-bordered table-striped TableData">
			<thead>
			<tr>
				<th>Test Name/Test ID</th>
				<th>Saved Value</th>
				<th>New Value</th>
				<th>FixedNormals</th>
				<th></th>
			</tr>
			</thead>
			<tbody>
			<?php for ($i = 0; $i < count($lab_request_item_entry); ++$i) { ?>
			<tr>
				<td><?=$lab_request_item_entry[$i]->Test ?>[<?=$lab_request_item_entry[$i]->TestID ?>]
				<?php if(trim($lab_request_item_entry[$i]->Formula)<>''){
					echo '<br/>'.trim($lab_request_item_entry[$i]->Formula);
					echo '<input type="hidden" id="formula_id_'.$lab_request_item_entry[$i]->id.'" name="" value="'.trim($lab_request_item_entry[$i]->Formula).'" >';
				} 
				?>
			
				</td>
				<td><div id="update_value_<?=$lab_request_item_entry[$i]->id?>"><?=$lab_request_item_entry[$i]->lab_test_value ?></div></td>
				<td>
				<?php
					if($lab_request_item_entry[$i]->option_value != null)
					{
						echo "<select id='test_value_".$lab_request_item_entry[$i]->id."' onchange='update_test_value(".$lab_request_item_entry[$i]->id.",this.value)' onclick='update_test_value(".$lab_request_item_entry[$i]->id.",this.value)' >";
						$row_data=explode(',',$lab_request_item_entry[$i]->option_value);
						
						for ($j = 0; $j < count($row_data); ++$j)
						{
							$col_data=explode(':',$row_data[$j]);
							echo '<option value='.$col_data[1].' >['.$col_data[1].']'.$col_data[2].'</option>';
						}
						
						echo "</select>";
						echo '<button class="btn btn-sm" onclick="update_test_value('.$lab_request_item_entry[$i]->id.',document.getElementById(\'test_value_'.$lab_request_item_entry[$i]->id.'\').value)" type="button" class="btn btn-primary">Save</button>';
					}else{
						if(trim($lab_request_item_entry[$i]->Formula)<>''){
							echo 'After Calculate';
							//echo '<input id="test_value_'.$lab_request_item_entry[$i]->id.'" type="text" value="'.$lab_request_item_entry[$i]->lab_test_value.'" readonly />';
						}else{
							echo '<input id="test_value_'.$lab_request_item_entry[$i]->id.'" type="text" value="'.$lab_request_item_entry[$i]->lab_test_value.'" onchange="update_test_value('.$lab_request_item_entry[$i]->id.',this.value)" />';
						}
					}
				
				?>
				</td>
				<td>
					<?=$lab_request_item_entry[$i]->FixedNormals ?>
				</td>
				<td>
					<!--<button onclick="update_test_value(<?=$lab_request_item_entry[$i]->id ?>,document.getElementById('test_value_<?=$lab_request_item_entry[$i]->id ?>').value)" type="button" class="btn btn-primary">Save</button> -->
				</td>
			</tr>
			<?php } ?>
			</tbody>
			<tfoot>
			<tr>
			 	<th>Test Name/Test ID</th>
				<th>Saved Value</th>
				<th>New Value</th>
				<th>FixedNormals</th>
				<th></th>
			 </tr>
			</tfoot>
		  </table>
		  <hr />
		  <?php }  ?>
			<div class="row">
				<div class="col-md-12">
					<button onclick="report_create()" type="button" class="btn btn-primary">Save & Next</button>
				</div>
			</div>
		</div>
		<!-- /.box-body -->
		</div>
	</div>
<?php echo form_close(); ?>
<script>
	function update_test_value(test_id,test_value)
	{
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
		
		$.post('/index.php/Lab_Admin/Entry_Update/',
			{ 
				"test_id": test_id,
				"test_value":test_value,
				'<?=$this->security->get_csrf_token_name()?>':csrf_value
			}, function(data){
			$('#update_value_'+test_id).html(data);
		});
	}

	function update_remark()
	{
		var HTMLData=CKEDITOR.instances.HTMLData.getData();
		
		var req_id=$('#hid_value_req_id').val();
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
		
		$.post('/index.php/Lab_Admin/Remark_Update/'+req_id,
		{ 	"HTMLData": HTMLData,
			'<?=$this->security->get_csrf_token_name()?>':csrf_value }, function(data){
			alert(data);
		});
	}
	
	function report_create()
	{
		var req_id=$('#hid_value_req_id').val();
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
		
		$.post('/index.php/Lab_Admin/create_report/'+req_id,
			{ 	"req_id": req_id,
				'<?=$this->security->get_csrf_token_name()?>':csrf_value
			}, function(data){
				$('#testentry-bodyc').html(data);
		});
	}
</script>