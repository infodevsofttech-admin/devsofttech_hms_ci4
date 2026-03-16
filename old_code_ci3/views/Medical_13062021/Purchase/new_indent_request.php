<br/>
<div class="row">
	<div class="col-md-12">
		<div class="jsError"></div>
		<div class="box">
				<div class="box-header">
				  <h3 class="box-title">New Purchase Invoice</h3>
				</div>
				<div class="box-body">
				<?php echo form_open('Medical/NewPurchase', array('role'=>'form','class'=>'form1')); ?>
						
						<div class="row">
						<div class="col-md-4">
							<div class="form-group">
							<label>Store</label>
								<select class="form-control input-sm" id="input_store_master" name="input_store_master"  >	
									<?php 
									foreach($store_master as $row)
									{ 
										echo '<option value='.$row->store_id.' >'.$row->store_name.'</option>';
									}
									?>
								</select>
							</div>
						</div>
						<div class="col-md-2">
							<button type="button" class="btn btn-primary" id="btn_update" accesskey="C" ><u>C</u>reate</button>
						</div>
					</div>
				<?php echo form_close(); ?>
				</div>
		</div>
	</div>
</div>
<script>

$(document).ready(function(){
	$('#btn_update').click(function(){
		$.post('/index.php/product_master/Create_Indent_Request', $('form.form1').serialize(), function(data){
			if(data.insertid==0)
			{
				$('#msgshow').html(data.show_text);
				$("#alert_show").alert();
				$("#alert_show").fadeTo(2000, 500).slideUp(500, function(){
					$("#alert_show").slideUp(500);
					});
			}else
			{
				alert('Indent Added : ID->'+ data.insertid);
				load_form_div('product_master/Indent_Request_Edit/'+data.insertid,'searchresult');
			}
		}, 'json');
	});
});

</script>