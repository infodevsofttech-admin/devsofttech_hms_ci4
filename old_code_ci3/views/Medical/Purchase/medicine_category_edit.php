<div class="box">
				<div class="box-header">
				  <h3 class="box-title">Supplier</h3>
				</div>
				<div class="box-body">
				<?php echo form_open('Product_master/medicine_category_edit', array('role'=>'form','class'=>'form1')); ?>
					<?php
						$cid=0;
						$med_cat_desc="";
						
						
						if(count($med_product_cat_master)>0)
						{
							$cid=$med_product_cat_master[0]->id;
							$med_cat_desc=$med_product_cat_master[0]->med_cat_desc;
						}
						
					?>
					<input type="hidden" id="hid_cid" name="hid_cid" value="<?=$cid?>" />
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label>Medicince Category Name</label>
								<input class="form-control" name="input_med_cat_desc" id="input_med_cat_desc" placeholder="med_cat_desc" type="text" value="<?=$med_cat_desc ?>"  required />
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-2"> 
							<div class="form-group">
								<label> </label>
								<button type="button" class="btn btn-primary" id="btn_update" accesskey="A" ><u>A</u>dd & Update Company</button>
							</div>
						</div>
					</div>
				<?php echo form_close(); ?>
				</div>
		</div>
<script>
$(document).ready(function(){
	$('#btn_update').click(function(){
		$.post('/index.php/Product_master/medicine_category_Update', $('form.form1').serialize(), function(data){
			if(data.insertid==0)
			{
				notify('error','Please Attention',data.show_text);
			}else
			{
				$('#hid_cid').val(data.insertid);
				
				notify('success','Please Attention',data.show_text);
				
				load_form_div('/Product_master/medicine_category_Sub','supplier_list');
				load_form_div('/Product_master/medicine_category_edit/'+data.insertid,'test_div');
				
			}
		}, 'json');
	});
});
</script>