<section class="content-header">
  <h1>
	IPD Billing Panel
	<small></small>
  </h1>
</section>
<section class="content">
<?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
<div class="row">
	<div class="col-md-12">
		<div class="box box-danger">
			<div class="box-header">
				<div class="box-title">
					IPD Package
				</div>
			</div>
			<div class="box-body" id="show_item_list_package">
				
			</div>
			<div class="box-footer">
				<div class="panel-group" id="accordion_package">
						<div class="panel panel-default">
							<div class="panel-heading">
							  <h4 class="panel-title">
								<a data-toggle="collapse" data-parent="#accordion_package" href="#collapse_package_1">
								Manual Package</a>
							  </h4>
							</div>
							<div id="collapse_package_1" class="panel-collapse collapse">
							  <div class="panel-body">
								<div class="row">
									<div class="col-xs-12">
										<div class="form-group">
											<label>Package Name</label>
											<input class="form-control varchar input-sm" name="input_pakage_name_m" id="input_pakage_name_m" placeholder="" value="" type="text"  />
										</div>
									</div>
									<div class="col-xs-6">
										<div class="form-group">
											<label>Comment</label>
											<input class="form-control varchar input-sm" name="input_Package_comment_m" id="input_Package_comment_m" placeholder="" value="" type="text"  />
										</div>
									</div>
									<div class="col-xs-3">
										<div class="form-group">
											<label>Rate</label>
											<input class="form-control number input-sm" name="input_amount_m" id="input_amount_m" placeholder="Amount" value="0.00" type="text"  />
										</div>
									</div>
									<div class="col-xs-3">
										<div class="form-group">
											<button type="button" class="btn btn-primary" id="additem" onclick='add_package_manual()'  >Add in List</button>
										</div>
									</div>
								</div>
							  </div>
							</div>
						</div>
						<div class="panel panel-default">
							<div class="panel-heading">
							  <h4 class="panel-title">
								<a data-toggle="collapse" data-parent="#accordion_package" href="#collapse_package_2">
								Package List</a>
							  </h4>
							</div>
							<div id="collapse_package_2" class="panel-collapse collapse">
							  <div class="panel-body">
									<div class="row">
										<div class="col-md-12 show_lab_test">
											<div class="form-group">
												<label>Package Name</label>
												<select class="form-control input-sm select2" id="package_list_id" name="package_list_id" style="width: 100%;"  >					
													<?php 
													foreach($package_list as $row)
													{ 
														echo '<option value='.$row->id.'>'.$row->ipd_pakage_name.'  : '.$row->org_code.'</option>';
													}
													?>
												</select>
											</div>
										</div>
										<div class="col-md-3">
											<div class="form-group">
												<label>Amount</label>
												<input class="form-control number input-sm" name="input_amount_p" id="input_amount_p" placeholder="Rate" value="0.00" type="text"  />
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label>Comment</label>
												<input class="form-control varchar input-sm" name="input_comment_p" id="input_comment_p" placeholder="" value="" type="text"  />
											</div>
										</div>
										<div class="col-md-3">
											<div class="form-group">
												<button type="button" class="btn btn-primary" id="additem" onclick='add_package_predefine()'   >Add in List</button>
											</div>
										</div>
									</div>
							  </div>
							</div>
						</div>
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
		var ipd_id=$('#Ipd_ID').val();
		load_form_div('/IpdNew/show_Package/'+ipd_id,'show_item_list_package');
	});
	
	//Package Charges
	function add_package_manual()
	{
		var ipd_id=$('#Ipd_ID').val();
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
		
		$.post('/index.php/IpdNew/ipd_package_showitem/1',
		{ "package_name": $('#input_pakage_name_m').val(), 
		"package_id": 1, 
		"input_amount": $('#input_amount_m').val(),
		"ipd_id": $('#Ipd_ID').val(),
		'<?=$this->security->get_csrf_token_name()?>':csrf_value,
		"comment": $('#input_Package_comment_m').val()}, function(data){
			load_form_div('/IpdNew/show_Package/'+ipd_id,'show_item_list_package');
			$('#input_amount_m').val('0.00');
			$('#input_Package_comment_m').val('');
			$('#input_pakage_name_m').val('');
		});
	}
	
	//Package Charges
	function add_package_predefine()
	{
		var ipd_id=$('#Ipd_ID').val();
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		
		$.post('/index.php/IpdNew/ipd_package_showitem/1',
		{ "package_name": $("#package_list_id:selected").text(), 
		"package_id": $('#package_list_id').val(), 
		"input_amount": $('#input_amount_p').val(),
		"ipd_id": $('#Ipd_ID').val(),
		'<?=$this->security->get_csrf_token_name()?>':csrf_value,
		"comment": $('#input_comment_p').val()}, function(data){
			load_form_div('/IpdNew/show_Package/'+ipd_id,'show_item_list_package');
			$('#input_amount_p').val('0.00');
			$('#input_comment_p').val('');
		});
	}
	
	// Remove and update for All

	function remove_item_invoice(itemid)
	{
		var ipd_id=$('#Ipd_ID').val();
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		
		if(confirm("Are you sure Remove this item "))
		{
			$.post('/index.php/IpdNew/ipd_package_showitem/0',
			{ "itemid": itemid, 
			"lab_invoice_id": $('#lab_invoice_id').val(),
			'<?=$this->security->get_csrf_token_name()?>':csrf_value,
			"ipd_id": $('#Ipd_ID').val()}, function(data){
				load_form_div('/IpdNew/show_Package/'+ipd_id,'show_item_list_package');
			});
		}
	}
	
	function update_qty(itemid)
	{
		var ipd_id=$('#Ipd_ID').val();
		
		if(confirm("Are you sure Update this item "))
		{
			var update_qty=$('#input_qty_'+itemid).val();
			var item_rate=$('#hidden_rate_'+itemid).val();
			var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

			
			$.post('/index.php/IpdNew/ipd_package_showitem/2',{ 
			"itemid": itemid, 
			"input_amount": $('#input_amt_'+itemid).val(),
			"ipd_id": $('#Ipd_ID').val(),
			'<?=$this->security->get_csrf_token_name()?>':csrf_value}, function(data){
				load_form_div('/IpdNew/show_Package/'+ipd_id,'show_item_list_package');
			});
		}
	}
   
    
</script>