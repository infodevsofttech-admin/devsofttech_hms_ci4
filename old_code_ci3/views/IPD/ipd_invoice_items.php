<section class="content-header">
  <h1>
	IPD Charges
	<small></small>
  </h1>
</section>
<section class="content">
<?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
<div class="box box-danger">
    <div class="box-header">
		<div class="box-title">
			IPD Charges
        </div>
    </div>
	<div class="box-body" id="show_item_list">
		
	</div>
	<div class="box-footer">
		<div class="panel-group" id="accordion">
			<div class="panel panel-default">
				<div class="panel-heading">
				  <h4 class="panel-title">
					<a data-toggle="collapse" data-parent="#accordion" href="#collapse1">
					Registration</a>
				  </h4>
				</div>
				<div id="collapse1" class="panel-collapse collapse">
				  <div class="panel-body">
					<div class="row">
						<div class="col-xs-4 show_lab_test">
							<div class="form-group">
							<label>Registration Name</label>
								<select class="form-control input-sm" id="itype_name_id_1" name="itype_name_id_1"  >					
									<?php 
									foreach($item_list_1 as $row)
									{ 
										echo '<option value='.$row->id.'>'.$row->sdesc.'</option>';
									}
									?>
								</select>
							</div>
						</div>
						<div class="col-xs-2">
							<div class="form-group">
								<label>Rate</label>
								<input class="form-control number input-sm" name="input_rate_1" id="input_rate_1" placeholder="Rate" value="0.00" type="text"  />
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<label>Date</label>
								<div class="input-group date">
									<div class="input-group-addon">
										<i class="fa fa-calendar"></i>
									</div>
									<input class="form-control pull-right datepicker input-sm" id="datepicker_itemdate_1" name="datepicker_itemdate" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask="" value="<?=date('d/m/Y')?>"  />
								</div>
							</div>
						</div>
						<div class="col-xs-2">
							<div class="form-group">
								<button type="button" class="btn btn-primary" id="additem" onclick='add_item_invoice_1()'  >Add in List</button>
							</div>
						</div>
					</div>
				  </div>
				</div>
			</div>
			<div class="panel panel-default">
				<div class="panel-heading">
				  <h4 class="panel-title">
					<a data-toggle="collapse" data-parent="#accordion" href="#collapse2">
					ACCOMMODATION</a>
				  </h4>
				</div>
				<div id="collapse2" class="panel-collapse collapse">
				  <div class="panel-body">
						<div class="row">
							<div class="col-xs-4 show_lab_test">
								<div class="form-group">
								<label>Charge Name</label>
									<select class="form-control input-sm" id="itype_name_id_2" name="itype_name_id_2"  >					
										<?php 
										foreach($item_list_2 as $row)
										{ 
											echo '<option value='.$row->id.'>'.$row->sdesc.'</option>';
										}
										?>
									</select>
								</div>
							</div>
							<div class="col-xs-1">
								<div class="form-group">
									<label>Rate</label>
									<input class="form-control number input-sm" name="input_rate_2" id="input_rate_2" placeholder="Rate" value="0.00" type="text"  />
								</div>
							</div>
							<div class="col-xs-1">
								<div class="form-group">
									<label>Qty</label>
									<input class="form-control input-sm" name="input_qty_2" id="input_qty_2" placeholder="Qty" value="1" type="number"  />
								</div>
							</div>
							<div class="col-xs-4">
								<div class="form-group">
									<label>Comment</label>
									<input class="form-control varchar input-sm" name="input_comment_2" id="input_comment_2" placeholder="" value="" type="text"  />
								</div>
							</div>
							<div class="col-xs-2">
								<div class="form-group">
									<button type="button" class="btn btn-primary" id="additem" onclick='add_item_invoice_2()'   >Add in List</button>
								</div>
							</div>
						</div>
				  </div>
				</div>
			</div>
			<div class="panel panel-default">
				<div class="panel-heading">
				  <h4 class="panel-title">
					<a data-toggle="collapse" data-parent="#accordion" href="#collapse3">
					SURGERY & OPERATION</a>
				  </h4>
				</div>
				<div id="collapse3" class="panel-collapse collapse">
				  <div class="panel-body">
							<div class="row">
							<div class="col-xs-4 ">
								<div class="form-group">
								<label>Charge Name</label>
									<select class="form-control input-sm" id="itype_name_id_3" name="itype_name_id_3"  >					
										<?php 
										foreach($item_list_3 as $row)
										{ 
											echo '<option value='.$row->id.'>'.$row->sdesc.'</option>';
										}
										?>
									</select>
								</div>
							</div>
							<div class="col-xs-1">
								<div class="form-group">
									<label>Rate</label>
									<input class="form-control number input-sm" name="input_rate_3" id="input_rate_3" placeholder="Rate" value="0.00" type="text"  />
								</div>
							</div>
							<div class="col-xs-1">
								<div class="form-group">
									<label>Qty</label>
									<input class="form-control input-sm" name="input_qty_3" id="input_qty_3" placeholder="Qty" value="1" type="number"  />
								</div>
							</div>
							<div class="col-xs-4">
								<div class="form-group">
									<label>Comment</label>
									<input class="form-control varchar input-sm" name="input_comment_3" id="input_comment_3" placeholder="" value="" type="text"  />
								</div>
							</div>
							<div class="col-xs-2">
								<div class="form-group">
									<button type="button" class="btn btn-primary" id="additem" onclick='add_item_invoice_3()'   >Add in List</button>
								</div>
							</div>
						</div>
				  </div>
				</div>
			</div>
			<div class="panel panel-default">
				<div class="panel-heading">
				  <h4 class="panel-title">
					<a data-toggle="collapse" data-parent="#accordion" href="#collapse6">
					PROFESSIONAL CHARGES</a>
				  </h4>
				</div>
				<div id="collapse6" class="panel-collapse collapse">
				  <div class="panel-body">
						<div class="row">
							<div class="col-xs-4 ">
								<div class="form-group">
								<label>Charge Name</label>
									<select class="form-control input-sm" id="itype_name_id_6" name="itype_name_id_6"  >					
										<?php 
										foreach($item_list_6 as $row)
										{ 
											echo '<option value='.$row->id.'>'.$row->sdesc.'</option>';
										}
										?>
									</select>
								</div>
							</div>
							<div id="professional_charge_others">
								<div class="col-xs-4">
									<div class="form-group">
										<label>Comment</label>
										<input class="form-control varchar input-sm" name="input_comment_6_a" id="input_comment_6_a" placeholder="" value="" type="text"  />
									</div>
								</div>
								<div class="col-xs-1">
									<div class="form-group">
										<label>Rate</label>
										<input class="form-control number input-sm" name="input_rate_6_a" id="input_rate_6_a" placeholder="Rate" value="0.00" type="text"  />
									</div>
								</div>
								<div class="col-xs-1">
									<div class="form-group">
										<label>Qty</label>
										<input class="form-control input-sm" name="input_qty_6_a" id="input_qty_6_a" placeholder="Qty" value="1" type="number"  />
									</div>
								</div>
								<div class="col-xs-2">
									<div class="form-group">
										<button type="button" class="btn btn-primary" id="additem" onclick='add_item_invoice_6_a()'  >Add By Specialization</button>
									</div>
								</div>
							</div>
						</div>
						<div id="professional_charge_doc_id">
							<div class="row " style="background-color:yellow;padding: 10px;" >
								<div class="col-xs-3">
									<div class="form-group">
									<label>Panel Doctor Name</label>
										<select class="form-control input-sm" id="doc_name_id_6_b" name="doc_name_id_6_b"  >	
											<?php 
											foreach($doclist as $row)
											{ 
												echo '<option value='.$row->id.'  >'.$row->DocSpecName.'</option>';
											}
											?>
										</select>
									</div>
								</div>
								<div class="col-xs-3">
									<div class="form-group">
										<label>IPD Fees</label>
										<select class="form-control input-sm" id="doc_fee_id_6_b" name="doc_fee_id_6_b"  >	
											
										</select>
									</div>
								</div>
								<div class="col-xs-3">
									<div class="form-group">
										<label>Comment</label>
										<input class="form-control varchar input-sm" name="input_comment_6_b" id="input_comment_6_b" placeholder="" value="" type="text"  />
									</div>
								</div>
								<div class="col-xs-1">
									<div class="form-group">
										<label>Rate</label>
										<input class="form-control number input-sm" name="input_rate_6_b" id="input_rate_6_b" placeholder="Rate" value="0.00" type="text"  />
									</div>
								</div>
								<div class="col-xs-1">
									<div class="form-group">
										<label>Qty</label>
										<input class="form-control input-sm" name="input_qty_6_b" id="input_qty_6_b" placeholder="Qty" value="1" type="number"  />
									</div>
								</div>
								<div class="col-xs-1">
									<div class="form-group">
										<button type="button" class="btn btn-primary" id="additem" onclick='add_item_invoice_6_b()' >Add By Name</button>
									</div>
								</div>
							</div>
							<div class="row" style="background-color:orange;padding: 10px;">
								<div class="col-xs-2">
									<div class="form-group">
										<label>Specility</label>
										<select class="form-control input-sm" id="Specility_6_c" name="Specility_6_c"  >	
											<?php 
											foreach($med_spec as $row)
											{ 
												echo '<option value='.$row->id.'  >'.$row->SpecName.'</option>';
											}
											?>
										</select>
									</div>
								</div>
								<div class="col-xs-2">
									<div class="form-group">
										<label>Doctor Name</label>
										<input class="form-control varchar input-sm" name="input_doc_name_6c" id="input_doc_name_6c" placeholder="" value="" type="text"  />
									</div>
								</div>
								<div class="col-xs-2">
									<div class="form-group">
										<label>Comment</label>
										<input class="form-control varchar input-sm" name="input_comment_6_c" id="input_comment_6_c" placeholder="" value="" type="text"  />
									</div>
								</div>
								<div class="col-xs-2">
									<div class="form-group">
										<label>Rate</label>
										<input class="form-control number input-sm" name="input_rate_6_c" id="input_rate_6_c" placeholder="Rate" value="0.00" type="text"  />
									</div>
								</div>
								<div class="col-xs-2">
									<div class="form-group">
										<label>Qty</label>
										<input class="form-control input-sm" name="input_qty_6_c" id="input_qty_6_c" placeholder="Qty" value="1" type="number"  />
									</div>
								</div>
								<div class="col-xs-2">
									<div class="form-group">
										<button type="button" class="btn btn-primary" id="additem" onclick='add_item_invoice_6_c()'  >Add By Specialization</button>
									</div>
								</div>
							</div>
						</div>
				  </div>
				</div>
			</div>
			<div class="panel panel-default">
				<div class="panel-heading">
				  <h4 class="panel-title">
					<a data-toggle="collapse" data-parent="#accordion" href="#collapse7">
					Implant Bills</a>
				  </h4>
				</div>
				<div id="collapse7" class="panel-collapse collapse">
				  <div class="panel-body">
						<div class="row">
							<div class="col-xs-4 ">
								<div class="form-group">
								<label>Charge Name</label>
									<select class="form-control input-sm" id="itype_name_id_7" name="itype_name_id_7"  >					
										<?php 
										foreach($item_list_7 as $row)
										{ 
											echo '<option value='.$row->id.'>'.$row->sdesc.'</option>';
										}
										?>
									</select>
								</div>
							</div>
							<div class="col-xs-4">
								<div class="form-group">
									<label>Comment / Batch No. /Invoice No.</label>
									<input class="form-control varchar input-sm" name="input_comment_7" id="input_comment_7" placeholder="" value="" type="text"  />
								</div>
							</div>
							<div class="col-xs-2">
								<div class="form-group">
									<label>Rate</label>
									<input class="form-control number input-sm" name="input_rate_7" id="input_rate_7" placeholder="Rate" value="0.00" type="text"  />
								</div>
							</div>
							<div class="col-xs-2">
									<div class="form-group">
										<label>Qty</label>
										<input class="form-control input-sm" name="input_qty_7" id="input_qty_7" placeholder="Qty" value="1" type="number"  />
									</div>
								</div>
							<div class="col-xs-2">
								<div class="form-group">
									<button type="button" class="btn btn-primary" id="additem" onclick='add_item_invoice_7()'  accesskey="A" ><u>A</u>dd in List</button>
								</div>
							</div>
						</div>
				  </div>
				</div>
			</div>
			<div class="panel panel-default">
				<div class="panel-heading">
				  <h4 class="panel-title">
					<a data-toggle="collapse" data-parent="#accordion" href="#collapse8">
					Others</a>
				  </h4>
				</div>
				<div id="collapse8" class="panel-collapse collapse">
				  <div class="panel-body">
						<div class="row">
							<div class="col-xs-4">
								<div class="form-group">
									<label>Comment</label>
									<input class="form-control varchar input-sm" name="input_comment_8" id="input_comment" placeholder="" value="" type="text"  />
								</div>
							</div>
							<div class="col-xs-2">
								<div class="form-group">
									<label>Rate</label>
									<input class="form-control number input-sm" name="input_rate" id="input_rate" placeholder="Rate" value="0.00" type="text"  />
								</div>
							</div>
							<div class="col-xs-2">
								<div class="form-group">
									<button type="button" class="btn btn-primary" id="additem" onclick='add_item_invoice()'  accesskey="A" ><u>A</u>dd in List</button>
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
		
		var itype_name_id_6=$('#itype_name_id_6').val();
		if(itype_name_id_6==186)
			{
				$('#professional_charge_doc_id').show();
				$('#professional_charge_others').hide();
			}else{
				$('#professional_charge_doc_id').hide();
				$('#professional_charge_others').show();
			}
			
		doc_name_id_6_b=$('#doc_name_id_6_b').val();
		load_form_div('/Ipd/doc_ipd_fee/'+doc_name_id_6_b,'doc_fee_id_6_b');

		$("#itype_name_id").change(function(){
			$('#input_rate').val('0.00');
			$('#input_qty').val('1');
		});

		load_form_div('/Ipd/show_ipd_items/'+ipd_id+'/1','show_item_list');
		
		$("#itype_name_id_6").change(function(){
			itype_name_id_6=$('#itype_name_id_6').val();

			if(itype_name_id_6==186)
			{
				$('#professional_charge_doc_id').show();
				$('#professional_charge_others').hide();
			}else{
				$('#professional_charge_doc_id').hide();
				$('#professional_charge_others').show();
			}
		});
		
		$("#doc_name_id_6_b").change(function(){
			doc_name_id_6_b=$('#doc_name_id_6_b').val();
			load_form_div('/Ipd/doc_ipd_fee/'+doc_name_id_6_b,'doc_fee_id_6_b');
		});
	});
	
	// IPD Registration
	
	function add_item_invoice_1()
	{
		var ipd_id=$('#Ipd_ID').val();
		
		$.post('/index.php/Ipd/ipd_showitem/1',
			{ "itype_name_id": $('#itype_name_id_1').val(), 
			"itype_idv": 1, 
			"input_qty": 1,
			"input_rate": $('#input_rate_1').val(),
			"ipd_id": $('#Ipd_ID').val(),
			"doc_id": 0, 
			"doc_name": '',
			"comment": '',
			"date_item": ''}, function(data){
				load_form_div('/Ipd/show_ipd_items/'+ipd_id+'/1','show_item_list');
				$('#input_rate_1').val('0.00');
			});
	}

	//ACCOMMODATION
	
	function add_item_invoice_2()
	{
		var ipd_id=$('#Ipd_ID').val();
		
		$.post('/index.php/Ipd/ipd_showitem/1',
			{ "itype_name_id": $('#itype_name_id_2').val(), 
			"itype_idv": 2, 
			"input_qty": $('#input_qty_2').val(),
			"input_rate": $('#input_rate_2').val(),
			"ipd_id": $('#Ipd_ID').val(),
			"doc_id": 0, 
			"doc_name": '',
			"comment": $('#input_comment_2').val(),
			"date_item": ''}, function(data){
				load_form_div('/Ipd/show_ipd_items/'+ipd_id+'/1','show_item_list');
				$('#input_rate_2').val('0.00');
				$('#input_qty_2').val('0.00');
			});
	}
	
	//Surgery & OPERATION
	
	function add_item_invoice_3()
	{
		var ipd_id=$('#Ipd_ID').val();
		
		$.post('/index.php/Ipd/ipd_showitem/1',
			{ "itype_name_id": $('#itype_name_id_3').val(), 
			"itype_idv": 3, 
			"input_qty": $('#input_qty_3').val(),
			"input_rate": $('#input_rate_3').val(),
			"ipd_id": $('#Ipd_ID').val(),
			"doc_id": 0, 
			"doc_name": '',
			"comment": $('#input_comment_3').val(),
			"date_item": ''}, function(data){
				load_form_div('/Ipd/show_ipd_items/'+ipd_id+'/1','show_item_list');
				$('#input_rate_3').val('0.00');
				$('#input_qty_3').val('0.00');
				$('#input_comment_3').val('');
			});
	}
	
	//Professional Charges
	function add_item_invoice_6_a()
	{
		var ipd_id=$('#Ipd_ID').val();
		
		$.post('/index.php/Ipd/ipd_showitem/1',
			{ "itype_name_id": $('#itype_name_id_6').val(), 
			"itype_idv": 6, 
			"input_qty": $('#input_qty_6_a').val(),
			"input_rate": $('#input_rate_6_a').val(),
			"ipd_id": $('#Ipd_ID').val(),
			"doc_id": 0, 
			"doc_name": '',
			"comment": $('#input_comment_6_a').val(),
			"date_item": $('#datepicker_itemdate_1').val()}, function(data){
				load_form_div('/Ipd/show_ipd_items/'+ipd_id,'show_item_list');
				$('#input_rate_6_a').val('0.00');
				$('#input_qty_6_a').val('0.00');
				$('#input_comment_6_a').val('');
			});
	}
	
	function add_item_invoice_6_b()
	{
		var ipd_id=$('#Ipd_ID').val();
		
		var doc_fee=$('#doc_fee_id_6_b').val();
		var doc_fee_other=$('#input_rate_6_b').val();
		
		if(doc_fee=='' || doc_fee==null){
			doc_fee=0;
		}

		if(doc_fee_other>0)
		{
			doc_fee=doc_fee_other;
		}
		
		flag_run=1;
		
		if(doc_fee<=0)
		{
			if(confirm('Are you sure , Add this time with Rate Rs 0.00 '))
			{
				doc_fee=0;
			}else{
				flag_run=0;
			}
		}
		
		if(flag_run)
		{
			$.post('/index.php/Ipd/ipd_showitem/1',
				{ "itype_name_id": $('#itype_name_id_6').val(),
				"itype_idv": 6,
				"input_qty": $('#input_qty_6_b').val(),
				"input_rate": doc_fee,
				"ipd_id": $('#Ipd_ID').val(),
				"doc_id": $("#doc_name_id_6_b").val(),
				"doc_spec": '',
				"doc_name": '',
				"comment": 'Dr. '+$("#doc_name_id_6_b :selected").text()+ ' ' +$('#input_comment_6_b').val()}, function(data){
					load_form_div('/Ipd/show_ipd_items/'+ipd_id,'show_item_list');
					$('#input_rate_6_b').val('0.00');
					$('#input_qty_6_b').val('0.00');
					$('#input_comment_6_b').val('');
				});
		}
	}
	
	function add_item_invoice_6_c()
	{
		var ipd_id=$('#Ipd_ID').val();
		
		var doc_fee=$('#input_rate_6_c').val();

		$.post('/index.php/Ipd/ipd_showitem/1',
				{ "itype_name_id": $('#itype_name_id_6').val(), 
				"itype_idv": 6, 
				"input_qty": $('#input_qty_6_b').val(),
				"input_rate": doc_fee,
				"ipd_id": $('#Ipd_ID').val(),
				"doc_id": '0',
				"doc_spec": '0', 				
				"doc_name": $("#Specility_6_c :selected").text()+' : '+$('#input_doc_name_6c').val(),
				"comment": $('#input_comment_6_c').val()}, function(data){
					load_form_div('/Ipd/show_ipd_items/'+ipd_id,'show_item_list');
					$('#input_rate_6_c').val('0.00');
					$('#input_qty_6_c').val('0.00');
					$('#input_comment_6_c').val('');
				});
	}
	
	//Implant Bills
	function add_item_invoice_7()
	{
		var ipd_id=$('#Ipd_ID').val();
		var qty=$('#input_qty_7').val();
		if(qty>0)
		{
			$.post('/index.php/Ipd/ipd_showitem/1',
			{ "itype_name_id": $('#itype_name_id_7').val(), 
			"itype_idv": 7, 
			"input_rate": $('#input_rate_7').val(),
			"input_qty": $('#input_qty_7').val(),
			"ipd_id": $('#Ipd_ID').val(),
			"doc_id": 0, 
			"doc_name": '',
			"comment": $('#input_comment_7').val()
			}, function(data){
				load_form_div('/Ipd/show_ipd_items/'+ipd_id+'/1','show_item_list');
				$('#input_rate_7').val('0.00');
				$('#input_qty_7').val('0.00');
				$('#input_comment_7').val('');
			});
		}else{
			alert('Qty Should be Greater then 0')
		}
		
	}
	
	//Others Items
	function add_item_invoice_8()
	{
		var ipd_id=$('#Ipd_ID').val();
		var qty=$('#input_qty_8').val();
		if(qty>0)
		{
			$.post('/index.php/Ipd/ipd_showitem/1',
			{ "itype_name_id": $('#itype_name_id_8').val(), 
			"itype_idv": 8, 
			"input_rate": $('#input_rate_8').val(),
			"input_qty": $('#input_qty_8').val(),
			"ipd_id": $('#Ipd_ID').val(),
			"doc_id": 0, 
			"doc_name": '',
			"comment": $('#input_comment_8').val()
			}, function(data){
				load_form_div('/Ipd/show_ipd_items/'+ipd_id+'/1','show_item_list');
				$('#input_rate_7').val('0.00');
				$('#input_qty_7').val('0.00');
				$('#input_comment_7').val('');
			});
		}else{
			alert('Qty Should be Greater then 0')
		}
		
	}

	// Remove and update for All

	function remove_item_invoice(itemid)
	{
		var ipd_id=$('#Ipd_ID').val();
		
		if(confirm("Are you sure Remove this item "))
		{
			$.post('/index.php/Ipd/ipd_showitem/0',
			{ "itemid": itemid, 
			"lab_invoice_id": $('#lab_invoice_id').val(),
			"ipd_id": $('#Ipd_ID').val()}, function(data){
				load_form_div('/Ipd/show_ipd_items/'+ipd_id,'show_item_list');
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
			
			$.post('/index.php/Ipd/ipd_showitem/2',{ "itemid": itemid, 
			"lab_invoice_id": $('#lab_invoice_id').val(),
			"update_qty": update_qty,
			"item_rate": item_rate,
			"ipd_id": $('#Ipd_ID').val()}, function(data){
				load_form_div('/Ipd/show_ipd_items/'+ipd_id,'show_item_list');
			});
		}
	}
   
    
</script>