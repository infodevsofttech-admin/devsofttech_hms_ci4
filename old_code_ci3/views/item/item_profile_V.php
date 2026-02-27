<section class="content-header">
    <h1>
        OPD Charge Name : <em><?=$data_item[0]->idesc; ?></em>
    </h1>
</section>
<!-- Main content -->
    <section class="content">
	<div class="jsError"></div>
      <div class="row">
        <div class="col-md-12">
            <?php echo form_open('insurance/create', array('role'=>'form','class'=>'form1')); ?>
                        <input type="hidden" value="<?=$data_item[0]->id ?>" id="p_id" name="p_id" />
                        <div class="row">
							<div class="col-md-3">
								<div class="form-group">
									<label>Charge Name</label>
									<input class="form-control" name="input_Item_name" placeholder="Item Name" value="<?=$data_item[0]->idesc ?>" type="text" autocomplete="off">
								</div>
							</div>
							<div class="col-md-3">
								<div class="form-group">
									<label>Charges Group</label>
									<select class="form-control" name="Item_Type" id="Item_Type" >
									<?php 
										foreach($data_item_type as $row)
										{ 
											echo '<option value="'.$row->itype_id.'"  '.combo_checked($data_item[0]->itype_id,$row->itype_id).'  >'.$row->group_desc.'</option>';
										}
									?>   
									</select>
								</div>
							</div>
							<div class="col-md-3">
								<div class="form-group">
									<label>Amount</label>
									<input class="form-control" name="input_amount" placeholder="amount" value="<?=$data_item[0]->amount ?>" type="text" autocomplete="off">
								</div>
							</div>
							
							</div>
							<div class="row">
							<div class="col-md-8">
								<div class="form-group">
									<label>Description</label>
									<textarea class="form-control" rows="3" name="input_Item_desc" placeholder="Enter ..."> <?=$data_item[0]->idesc_detail ?> </textarea>
									
								</div>
							</div>
							
						</div>
						<div class="row">
						<div class="col-md-4">
							<div class="form-group">
							<label> </label>
								<button type="button" class="btn btn-primary" id="btn_update">Update Record</button>
							</div>
							</div>
							</div>
					
                        <?php echo form_close(); ?>
        </div>
	</div>
	<div class="row">
		<div class="col-md-9">
		<div class="box">
		<div class="box-header">
		  <h3 class="box-title">Insurance Rates</h3>
		</div>
		<!-- /.box-header -->
		<div class="box-body">
            <?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
			<div id="incomplist">
			<table id="example1" class="table table-bordered table-striped TableData">
				<thead>
				<tr>
				  <th>Company Name</th>
				  <th>Amount</th>
				  <th>Code</th>
				  <th>Action</th>
				</tr>
				</thead>
				<tbody>
				<?php for ($i = 0; $i < count($data_insurance_item); ++$i) { ?>
				<tr>
				  <td><?=$data_insurance_item[$i]->ins_company_name ?></td>
				  <td><?=$data_insurance_item[$i]->i_amount ?></td>
				  <td><?=$data_insurance_item[$i]->code ?></td>
				  <td><button onclick="remove_item_spec('<?=$data_insurance_item[$i]->i_item_id ?>')" type="button" class="btn btn-primary">Delete</button></td>
				</tr>
				<?php } ?>
				</tbody>
				
			 </table>
			 </div>
			<?php echo form_close(); ?>
			<hr>
			<div class="col-md-4">
				<div class="form-group">
					<label>Company Name</label>
					<select class="form-control" name="ins_company_name" id="ins_company_name" >
					<?php 
						foreach($data_insurance as $row)
						{ 
							echo '<option value="'.$row->id.'">'.$row->ins_company_name.'</option>';
						}
					?>   
					</select>
				</div>
			</div>
			<div class="col-md-3">
				<div class="form-group">
					<label>Amount</label>
					<input class="form-control number" id="input_amount_1" placeholder="amount" value="<?=$data_item[0]->amount ?>" type="text" autocomplete="off">
				</div>
			</div>
			<div class="col-md-3">
				<div class="form-group">
					<label>Code</label>
					<input class="form-control" id="input_item_code" placeholder="Code Like ECHS,AIIMS"  type="text" autocomplete="off">
				</div>
			</div>
			<div class="col-md-2">
				<button type="button" class="btn btn-primary" id="btn_add_item" onclick="add_item_spec()">Add</button>
			</div>
        </div>
	</div>
	</div></div>
	
      <!-- ./row -->
    </section>
<!-- /.content -->

<script>
    $(document).ready(function(){
        $('#btn_update').click(function(){
			$.post('/index.php/item/UpdateRecord', $('form.form1').serialize(), function(data){
                if(data.update==0)
                {
                    $('div.jsError').html(data.error_text);
                }else
                {
					$('div.jsError').html(data.showcontent);
					$("#alert_show").alert();
					$("#alert_show").fadeTo(2000, 500).slideUp(500, function(){
						$("#alert_show").slideUp(500);
						});

                }
            }, 'json');
		})
  
   });
   
		function add_item_spec()
		{
			var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
			
			$.post('/index.php/item/AddInsuranceItemRecord',
			{ "ins_company_name": $('#ins_company_name').val(), 
			"input_amount": $('#input_amount_1').val(),
			"p_id": $('#p_id').val(),
			"input_item_code": $('#input_item_code').val(),"isadd":1,
			"<?=$this->security->get_csrf_token_name()?>":csrf_value }, function(data){
			$('#incomplist').html(data);
			});
		}
		   
		function remove_item_spec(item_id)
		{
			var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

			$.post('/index.php/item/remove_record_item',
			{ "in_remove_id": item_id,"p_id": $('#p_id').val(),
			"isadd":0,
			"<?=$this->security->get_csrf_token_name()?>":csrf_value }, function(data){
			$('#incomplist').html(data);
			});
		}
   
    
</script>