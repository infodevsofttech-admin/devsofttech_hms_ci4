<section class="content-header">
  <h1>
	Document Data
	<small></small>
  </h1>
  <ol class="breadcrumb">
	<li><a href="javascript:load_form('/Patient/person_record/<?=$person_info[0]->id ?>');"><i class="fa fa-dashboard"></i> Person</a></li>
  </ol>
</section>
<section class="content">
<?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
<div class="box box-danger">
    <div class="box-header">
		<div class="box-title">
			<p><strong>Name :</strong><?=$person_info[0]->p_fname?>  {<i><?=$person_info[0]->p_rname ?></i>}
			<strong>/ Age :</strong><?=$person_info[0]->age?> 
			<strong>/ Gender :</strong><?=$person_info[0]->xgender?> 
			<strong>/ P Code :</strong><?=$person_info[0]->p_code?>
			</p>
			<input type="hidden" id="pid" name="pid" value="<?=$person_info[0]->id?>" />
        </div>
    </div>
	<div class="box-body">
		<div class="row">
			<div class="col-xs-3">
				<div class="form-group">
				<label>Document Type</label>
					<select class="form-control input-sm" id="doc_format_id" name="doc_format_id"  >	
						<option value='0' >Not in List</option>
						<?php 
						foreach($doc_format as $row)
						{ 
							echo '<option value='.$row->df_id.'  >'.$row->doc_name.'</option>';
						}
						?>
					</select>
				</div>
			</div>
			<div class="col-xs-3">
				<div class="form-group">
				<label>Doctor Name</label>
					<select class="form-control input-sm" id="doc_name_id" name="doc_name_id"  >	
						<?php 
						foreach($doclist as $row)
						{ 
							echo '<option value='.$row->id.' >'.$row->p_fname.'</option>';
						}
						?>
					</select>
				</div>
			</div>
			<div class="col-md-3">
                <div class="form-group">
                    <label>Date </label>
                    <div class="input-group date">
                        <div class="input-group-addon">
                            <i class="fa fa-calendar"></i>
                        </div>
                        <input class="form-control pull-right datepicker input-sm" id="datepicker_doc_date" name="datepicker_doc_date" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask="" value="<?=date('d/m/Y')?>"  />
                    </div>
                </div>
            </div>
			<div class="col-xs-3">
				<div class="form-group">
					<button type="button" class="btn btn-primary" id="createdoc" accesskey="C" ><u>C</u>reate</button>
				</div>
			</div>
		</div>
		<hr/>
		<div class="row">
			<div class="col-xs-12 table-responsive">
          <table class="table table-striped ">
			<tr>
				<th style="width: 10px">#</th>
				<th>Issue Date</th>
				<th>Document Name</th>
				<th></th>
			</tr>
			<?php
			$srno=1;
				foreach($patient_doc as $row)
				{ 
					echo '<tr>';
					echo '<td>'.$srno.'</td>';
					echo '<td>'.$row->str_date_issue.'</td>';
					echo '<td>'.$row->doc_name.'</td>';
					if($row->update_pre_value==0)
					{
						echo '<td><button type="button" class="btn btn-primary" id="btn_update" onclick="load_form(\'/Document_Patient/Pre_Data/'.$row->id.'\')">Update Pre-Data</button>
						</td>';

					}else{
						echo '<td><button type="button" class="btn btn-primary" id="btn_update" onclick="load_form(\'/Document_Patient/load_doc/'.$row->id.'\')">Edit</button>
						</td>';
					}
				
					echo '</tr>';
					$srno=$srno+1;
				}
			?>
			<!---- Total Show  ----->
			</table>
		</div>
		</div>
	</div>
</div>
<?php echo form_close(); ?>
</section>
<!-- /.content -->
<script>
    $(document).ready(function(){
		$('#createdoc').click(function(){
			var doc_format_id = $('#doc_format_id').val();
            var doc_name_id = $('#doc_name_id').val();
			var datepicker_doc_date=$('#datepicker_doc_date').val();
			var patient_id=$('#pid').val();
			var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
			
			if(confirm('Are you sure to create'))
			{
				$.post('/index.php/Document_Patient/create_doc',{ 
				"document_format_id": doc_format_id, 
				"doc_id": doc_name_id,
				"patient_id": patient_id,
				"doc_issue_date":datepicker_doc_date,
				"<?=$this->security->get_csrf_token_name()?>":csrf_value}, 
				function(data){
					if(data>0)
					{
						load_form('/Document_Patient/Pre_Data/'+data);
					}else{
						alert('Error : ',data);
					}
				});
			}
        });
	});
	
</script>