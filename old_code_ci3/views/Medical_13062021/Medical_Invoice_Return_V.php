<section class="content-header">
  <h1>
	Medical Return Invoice No. <?=$invoiceMaster[0]->inv_med_code?>
	<small>
	<a href="javascript:load_form_div('/Medical/edit_invoice_edit/<?=$invoiceMaster[0]->id ?>','maindiv');" >Edit</a>
	</small>
  </h1>
</section>
<section class="content">
<?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
<div class="box box-danger">
    <div class="box-header">
		<div class="row">
				<input type="hidden" id="pid" name="pid" value="<?=$invoiceMaster[0]->patient_id ?>" />
				<input type="hidden" id="med_invoice_id" name="med_invoice_id" value="<?=$invoiceMaster[0]->id ?>" />
				<?php if($invoiceMaster[0]->patient_id==0) { ?>
					<div class="col-md-6">	
						<div class="form-group">
							<label class="col-sm-4 control-label" for="P_Name">Patient Name</label>
							<div class="col-sm-8">
								<input class="form-control input-sm" id="P_Name" name="P_Name" value="<?=$invoiceMaster[0]->inv_name ?>" required >
							</div>
						</div>
					</div>
					<div class="col-md-4">
						<div class="form-group">
							<label class="col-sm-4 control-label" >Phone No.</label>
							<div class="col-sm-8">
								<input class="form-control input-sm" id="P_Phone" name="P_Phone" value="<?=$invoiceMaster[0]->inv_phone_number ?>" required >
							</div>
						</div>
					</div>
					<div class="col-md-2">
						<div class="form-group">
							<label> </label>
							<button type="button" class="btn btn-primary btn-sm" id="btn_update" onclick="update_name_phone()">Update Name </button>
						</div>
					</div>
				<?php }else{ ?>
				<div class="col-md-12">
					<p><strong>Name :</strong>
					<?=$invoiceMaster[0]->inv_name?>
					<strong>/ P Code :</strong><?=$invoiceMaster[0]->patient_code?> 
					<strong>/ Invoice No. :</strong><?=$invoiceMaster[0]->inv_med_code?>
					<strong>/ Date :</strong> <?=MysqlDate_to_str($invoiceMaster[0]->inv_date)?>
					<?php if($invoiceMaster[0]->ipd_id > 0) { ?>
						<strong>IPD Code :</strong>
						<a href="javascript:load_form_div('/Medical/list_med_inv/<?=$ipd_master[0]->id ?>','maindiv');" >
							<?=$ipd_master[0]->ipd_code?>
						</a>
						<strong>Admit Date : </strong><?=$ipd_list[0]->str_register_date ?> <?=$ipd_list[0]->reg_time ?>
						<strong>/ Doctor :</strong><?=$ipd_list[0]->doc_name?>
						<strong>/ TPA-Org. :</strong><?=$ipd_list[0]->admit_type?> 
						<strong>/ Bill Type :</strong><?=($invoiceMaster[0]->ipd_credit)?'Credit To Hospital':'CASH/Direct'?>
						<?php }elseif($invoiceMaster[0]->case_id > 0){  ?>
							<strong>/ Org. Case ID :<a href="javascript:load_form_div('/Medical/list_med_orginv/<?=$OCaseMaster[0]->id ?>/<?=$invoiceMaster[0]->store_id?>','maindiv');" > 
										<?=$OCaseMaster[0]->case_id_code ?>
									</a>
						<?php } ?>
					</p>
				</div>
			<?php } ?>
		</div>
	</div>
	<div class="box-body">
		<div class="col-md-6" >
			<div class="row " >
				<div id="show_item_list">
					<?=$content?>
				</div>
			</div>
			<hr />
			<!------ 
			<div class="row " >
				<div class="row">
					<div class="col-md-8"> 
						<div class="form-group">
							<div class="ui-widget">
								<label for="tags">Product Search: </label>
								<input class="form-control input-sm" name="input_drug" id="input_drug" placeholder="Like Item Code , Item Name" type="text" >
							</span>
							</div>
						</div>
					</div>
					<div class="col-md-4">
						<span class="text-muted" name="input_product_code" id="input_product_code" ></span>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<input type="hidden" id="l_ssno" name="l_ssno"  />
						<input type="hidden" id="item_code" name="item_code"  />
						<p class="text-red" >
							<span class="text-green" name="input_product_name" id="input_product_name">
							</span>
							<span class="text-light-blue" name="input_batch" id="input_batch" >
							</span>
							<span class="text-lead" name="input_product_mrp" id="input_product_mrp">
							</span>
							<span class="text-yellow" name="stock_product_qty" id="stock_product_qty" >
							</span>
						</p>
					</div>
				</div>
				<div class="row">
					<div class="col-md-3">
						<div class="form-group">
							<label>Unit Rate </label>
							<input class="form-control input-sm" name="input_product_unit_rate" id="input_product_unit_rate" placeholder="Unit Rate" />
						</div>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<label>Qty </label>
							<input class="form-control number input-sm" name="input_product_qty" id="input_product_qty" placeholder="Qty Like No. of Tab." type="text" value=0 />
							<input type="hidden" id="hid_c_qty" name="hid_c_qty" value="0" />
						</div>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<label>Disc %</label>
							<input class="form-control number input-sm" name="input_disc" id="input_disc" placeholder="Discount %" type="text" value=0  />
						</div>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<button type="button" class="btn btn-primary" id="additem" onclick="add_item_invoice()" >Return </button>
						</div>
					</div>
				</div>
			</div>
			---->
		</div>
		<div class="col-md-6" style="font-size:12px;">
			<div class="box box-info">
				<div class="box-header with-border">
					<h4 class="box-title">Sale Medicine Within Last 2 months</h4>
					<div class="pull-right box-tools">
						<button type="button" class="btn btn-info btn-flat btn-sm" onclick="load_form_div('/Medical/Med_Return_invoice_search/<?=$invoiceMaster[0]->id ?>','search_body_part');">Search Panel</button>
					</div>
				</div>
				<!-- /.box-header -->
				<!-- form start -->
				<div class="box-body" id="search_body_part" name="search_body_part">
					
				</div>
				<!-- /.box-body -->
				<div class="box-footer" id="search_footer_part" name="search_footer_part">
					
				</div>
				<!-- /.box-footer -->
			</div>
		</div>
	</div>
	<div class="box-footer">
		<div class="row">
			<div class="col-md-2">
			</div>
			<div class="col-md-10">
				<div class="form-group">
					<button type="button" class="btn btn-success" id="finalinvoice"  >Final Return Invoice</button>
				</div>
			</div>
		</div>
	</div>
</div>
<?php echo form_close(); ?>
</section>
<!-- /.content -->
<script>
    $(document).ready(function(){
		$('#finalinvoice').click(function(){

			var inv_id = $('#med_invoice_id').val();
			var P_Name=$('#P_Name').val();
			var P_Phone=$('#P_Phone').val();
			var pid=$('#pid').val();

			if(pid==0)
			{
				if(P_Name=='' || P_Phone=='')
				{
					alert('Name and Phone No. Should not be Blank');
					$('#P_Name').focus();
					return false;
				}
			}

			load_form_div('/Medical/final_invoice/'+inv_id,'maindiv');
        });
	});

	function update_name_phone()
	{
		var P_Name=$('#P_Name').val();
		var P_Phone=$('#P_Phone').val();
		var med_invoice_id=$('#med_invoice_id').val();

		if(P_Name=='' || P_Phone=='')
		{
			alert('Name and Phone No. Should not be Blank');
			return false;
		}
	
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		if(confirm('Are you Sure Update Patient Name'))
		{
			$.post('/index.php/Medical/update_name_phone',{
			"pid":0,
			"customer_type":0,
			"P_Name":P_Name,
			"P_Phone":P_Phone,
			"med_invoice_id":med_invoice_id,
			'<?=$this->security->get_csrf_token_name()?>':csrf_value}, function(data){
				alert(data.remark);
			},'json');
		}
	}

	function remove_item_add(itemid)
	{
		var rqty=$('#input_qty_'+itemid).val();

		var inv_id = $('#med_invoice_id').val();

		var wait_for_next=$("#wait_for_next").val();
		var csrf_dst_name_value=$("input[name='<?=$this->security->get_csrf_token_name()?>']").val();

		if(wait_for_next>0)
		{
			alert('wait......');
			return false;
		}
		
		$.post('/index.php/Medical/add_remove_item',
			{ "itemid": itemid, 
			"inv_id": inv_id,
			"rqty":rqty,
			"<?php echo $this->security->get_csrf_token_name(); ?>" : csrf_dst_name_value
			}, function(data){
				if(data.update==0)
				{
					notify('error','Please Attention',data.msg_text);
				}else{
					notify('success','Please Attention',data.msg_text);
					$('#show_item_list').html(data.content);
				}
				
			}, 'json');
	}

	function remove_item_invoice(itemid)
	{
		var wait_for_next=$("#wait_for_next").val();
		var csrf_dst_name_value=$("input[name='<?=$this->security->get_csrf_token_name()?>']").val();

		if(wait_for_next>0)
		{
			alert('wait......');
			return false;
		}
		
		if(confirm('Are you sure to delete this item'))
		{
			$.post('/index.php/Medical/add_item/0',{ "itemid": itemid, 
			"inv_id": $('#med_invoice_id').val(),
			"<?php echo $this->security->get_csrf_token_name(); ?>" : csrf_dst_name_value
			}, function(data){
				$('#show_item_list').html(data.content);
				$("#<?=$this->security->get_csrf_token_name()?>").val(data.csrf_dst_name_value);
			}, 'json');
		}
	}

	$(document).ready(function(){
	    var cache = {};
	   
		$("#input_drug").autocomplete({
		    source: function( request, response ) {
			$.getJSON( "Medical/get_drug_master", request, function( data, status, xhr ) {
				response( data );
			});        
		},
        minLength: 1,
        autofocus: true,
		select: function( event, ui ) {
			$("#input_product_code").html('| Product Code:'+ui.item.l_item_code);
			$("#input_product_name").html('Name:'+ui.item.value);
			}		      	
		});
	});
    
</script>