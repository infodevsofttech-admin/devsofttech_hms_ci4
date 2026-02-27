<br/>
<div class="row">
	<div class="col-md-12">
		<div class="jsError"></div>
		<div class="box">
				<div class="box-header">
				  <h3 class="box-title">Edit Org. Packing : <?=$id?></h3>
				</div>
				<div class="box-body">
				<?php echo form_open('Org_Packing/EditPacking', array('role'=>'form','class'=>'form1')); ?>
						<div class="row">
                        <input type="hidden" id="packing_id" name="packing_id" value="<?=$id?>">
						<div class="col-md-2">
							<div class="form-group">
								<label>Bill Type</label>
								<select class="form-control input-sm" name="cbo_billtype" id="cbo_billtype"  >
									<option value="0" <?=combo_checked("0",$org_packing[0]->org_type)?> >OPD Org.</option>
									<option value="1" <?=combo_checked("1",$org_packing[0]->org_type)?> >IPD Org.</option>
								</select>
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<label>Packing Batch Number</label>
								<input class="form-control input-sm" name="input_packingcode" placeholder="Package Batch No" type="text" value="<?=$org_packing[0]->label_no ?>"  />
							</div>
						</div>
						<div class="col-md-2">
							<label> Date of Packing</label>
							<div class="input-group date ">
								<div class="input-group-addon">
									<i class="fa fa-calendar"></i>
								</div>
								<input class="form-control pull-right datepicker input-sm" name="datepicker_packing" id="datepicker_packing" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask value="<?=MysqlDate_to_str($org_packing[0]->date_of_create) ?>"  />
							</div>
						</div>
						<div class="col-md-2">
							<button type="button" class="btn btn-primary" id="btn_update" accesskey="U" ><u>U</u>pdate</button>
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
		$.post('/index.php/Org_Packing/EditPack', $('form.form1').serialize(), function(data){
			if(data.insertid==0)
			{
				notify('error','Please Attention',data.show_text);
			}else
			{
				show_msg='Invoice Update : ID->'+ data.insertid;
				notify('success','Please Attention',show_msg);

				load_form_div('Org_Packing/PackNewEdit/'+data.insertid,'searchresult');
			}
		}, 'json');
	});
});

</script>