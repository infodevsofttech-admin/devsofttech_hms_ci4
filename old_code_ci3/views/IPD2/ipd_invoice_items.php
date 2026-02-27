<section class="content-header">
  <h1>
	IPD Charges
	<small></small>
  </h1>
</section>
<section class="content">
<div class="row">
<div class="col-md-6">
	<div class="box box-danger">
		<div class="box-header">
			<div class="box-title">
				IPD Charges List
			</div>
		</div>
		<div class="box-body" id="show_item_list">

		</div>
		<div class="box-footer">
			
		</div>
	</div>
</div>
<div class="col-md-6">
	<?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
	
	<div class="box box-warning">
		<div class="box-header">
			<div class="box-title">
				Add Charges
			</div>
		</div>
		<div class="box-body" id="show_item_list">
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
							<div class="col-md-8 show_lab_test">
								<div class="form-group">
								<label>Registration Name</label>
									<select class="form-control input-sm select2" id="itype_name_id_1" name="itype_name_id_1" style="width: 100%;" >					
										<?php 
										foreach($item_list_1 as $row)
										{ 
											echo '<option value='.$row->id.'>'.$row->sdesc.'</option>';
										}
										?>
									</select>
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>Rate</label>
									<input class="form-control number input-sm" name="input_rate_1" id="input_rate_1" placeholder="Rate" value="0.00" type="text"  />
								</div>
							</div>
						</div>
						<div class="row"">
							<div class="col-md-6">
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
							</div>
						</div>
						<div class="row"">
							<div class="col-md-6">
								<button type="button" class="btn btn-primary" id="additem" onclick='add_item_invoice_1(this)'  >Add in List</button>
							</div>
						</div>
					</div>
				</div>
				<div class="panel panel-warning">
					<div class="panel-heading">
					<h4 class="panel-title">
						<a data-toggle="collapse" data-parent="#accordion" href="#collapse2">
						Accommodation</a>
					</h4>
					</div>
					<div id="collapse2" class="panel-collapse collapse">
					<div class="panel-body">
							<div class="row">
								<div class="col-md-6 show_lab_test">
									<div class="form-group">
									<label>Charge Name</label>
										<select class="form-control input-sm select2" id="itype_name_id_2" name="itype_name_id_2"  style="width: 100%;" >					
											<?php 
											foreach($item_list_2 as $row)
											{ 
												echo '<option value='.$row->id.'>'.$row->sdesc.'</option>';
											}
											?>
										</select>
									</div>
								</div>
								<div class="col-md-3">
									<div class="form-group">
										<label>Rate</label>
										<input class="form-control number input-sm" name="input_rate_2" id="input_rate_2" placeholder="Rate" value="0.00" type="text"  />
									</div>
								</div>
								<div class="col-md-3">
									<div class="form-group">
										<label>Qty (In Days)</label>
										<input class="form-control input-sm" name="input_qty_2" id="input_qty_2" placeholder="Qty" value="1" type="number"  />
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<div class="form-group">
										<label>Comment</label>
										<input class="form-control varchar input-sm" name="input_comment_2" id="input_comment_2" placeholder="" value="" type="text"  />
									</div>
								</div>
								<div class="col-md-4">
									<div class="form-group">
										<button type="button" class="btn btn-primary" id="additem" onclick='add_item_invoice_2(this)'   >Add in List</button>
									</div>
								</div>
							</div>
					</div>
					</div>
				</div>
				<div class="panel panel-info">
					<div class="panel-heading">
					<h4 class="panel-title">
						<a data-toggle="collapse" data-parent="#accordion" href="#collapse3">
						Surgery & Operation</a>
					</h4>
					</div>
					<div id="collapse3" class="panel-collapse collapse">
					<div class="panel-body">
							<div class="row">
								<div class="col-md-12 ">
									<div class="form-group">
									<label>Charge Name</label>
										<select class="form-control input-sm select2" id="itype_name_id_3" name="itype_name_id_3" style="width: 100%;" >					
											<?php 
											foreach($item_list_3 as $row)
											{ 
												echo '<option value='.$row->id.'>'.$row->sdesc.'</option>';
											}
											?>
										</select>
									</div>
								</div>
								<div class="col-md-3">
									<div class="form-group">
										<label>Rate</label>
										<input class="form-control number input-sm" name="input_rate_3" id="input_rate_3" placeholder="Rate" value="0.00" type="text"  />
									</div>
								</div>
								<div class="col-md-3">
									<div class="form-group">
										<label>Qty</label>
										<input class="form-control input-sm" name="input_qty_3" id="input_qty_3" placeholder="Qty" value="1" type="number"  />
									</div>
								</div>
								<div class="col-md-8">
									<div class="form-group">
									<label>Panel Doctor Name</label>
										<select class="form-control input-sm" id="doc_name_id_3" name="doc_name_id_3"  >	
											<?php 
											foreach($doclist as $row)
											{ 
												echo '<option value='.$row->id.'  >'.$row->DocSpecName.'</option>';
											}
											?>
										</select>
									</div>
								</div>
								<div class="col-md-12">
									<div class="form-group">
										<label>Comment</label>
										<input class="form-control varchar input-sm" name="input_comment_3" id="input_comment_3" placeholder="" value="" type="text"  />
									</div>
								</div>
								<div class="col-md-3">
									<div class="form-group">
										<button type="button" class="btn btn-primary" id="additem" onclick='add_item_invoice_3(this)'   >Add in List</button>
									</div>
								</div>
							</div>
					</div>
					</div>
				</div>
				<div class="panel panel-danger">
					<div class="panel-heading">
					<h4 class="panel-title">
						<a data-toggle="collapse" data-parent="#accordion" href="#collapse5">
						Procedure</a>
					</h4>
					</div>
					<div id="collapse5" class="panel-collapse collapse">
					<div class="panel-body">
								<div class="row">
								<div class="col-md-12 ">
									<div class="form-group">
									<label>Charge Name</label>
										<select class="form-control input-sm select2" id="itype_name_id_5" name="itype_name_id_5"  style="width: 100%;">					
											<?php 
											foreach($item_list_5 as $row)
											{ 
												echo '<option value='.$row->id.'>'.$row->sdesc.'</option>';
											}
											?>
										</select>
									</div>
								</div>
								<div class="col-md-3">
									<div class="form-group">
										<label>Rate</label>
										<input class="form-control number input-sm" name="input_rate_5" id="input_rate_5" placeholder="Rate" value="0.00" type="text"  />
									</div>
								</div>
								<div class="col-md-3">
									<div class="form-group">
										<label>Qty</label>
										<input class="form-control input-sm" name="input_qty_5" id="input_qty_5" placeholder="Qty" value="1" type="number"  />
									</div>
								</div>
								<div class="col-md-6">
									<div class="form-group">
									<label>Doctor Name</label>
										<select class="form-control input-sm" id="doc_name_id_5" name="doc_name_id_5"  >	
											<?php 
											echo '<option value=0>NONE</option>';
											foreach($doclist as $row)
											{ 
												echo '<option value='.$row->id.'  >'.$row->DocSpecName.'</option>';
											}
											?>
										</select>
									</div>
								</div>
								<div class="col-md-12">
									<div class="form-group">
										<label>Comment</label>
										<input class="form-control varchar input-sm" name="input_comment_5" id="input_comment_5" placeholder="" value="" type="text"  />
									</div>
								</div>
								<div class="col-md-3">
									<div class="form-group">
										<button type="button" class="btn btn-primary" id="additem" onclick='add_item_invoice_5(this)'   >Add in List</button>
									</div>
								</div>
							</div>
					</div>
					</div>
				</div>
				<div class="panel panel-info">
					<div class="panel-heading">
					<h4 class="panel-title">
						<a data-toggle="collapse" data-parent="#accordion" href="#collapse6">
						Professional Charges</a>
					</h4>
					</div>
					<div id="collapse6" class="panel-collapse collapse">
						<div class="panel-body">
							<div class="row">
								<div class="col-md-12 ">
									<div class="form-group">
									<label>Charge Name</label>
										<select class="form-control input-sm select2" id="itype_name_id_6" name="itype_name_id_6"  style="width: 100%;">					
											<?php 
											foreach($item_list_6 as $row)
											{ 
												echo '<option value='.$row->id.'>'.$row->sdesc.'</option>';
											}
											?>
										</select>
									</div>
								</div>
							</div>
							<div class="row">
								<div id="professional_charge_others">
									<div class="col-md-6">
										<div class="form-group">
											<label>Comment</label>
											<input class="form-control varchar input-sm" name="input_comment_6_a" id="input_comment_6_a" placeholder="" value="" type="text"  />
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label>Rate</label>
											<input class="form-control number input-sm" name="input_rate_6_a" id="input_rate_6_a" placeholder="Rate" value="0.00" type="text"  />
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label>Qty</label>
											<input class="form-control input-sm" name="input_qty_6_a" id="input_qty_6_a" placeholder="Qty" value="1" type="number"  />
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<button type="button" class="btn btn-primary" id="additem" onclick='add_item_invoice_6_a(this)'  >Add By Specialization</button>
										</div>
									</div>
								</div>
							</div>
							<div id="professional_charge_doc_id">
								<div class="row " style="background-color:yellow;padding: 10px;" >
									<div class="col-md-12">
										<div class="form-group">
										<label>Panel Doctor Name</label>
											<select class="form-control input-sm select2" id="doc_name_id_6_b" name="doc_name_id_6_b"  style="width: 100%;" >	
												<?php 
												foreach($doclist as $row)
												{ 
													echo '<option value='.$row->id.'  >'.$row->DocSpecName.'</option>';
												}
												?>
											</select>
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<label>IPD Fees</label>
											<select class="form-control input-sm" id="doc_fee_id_6_b" name="doc_fee_id_6_b"  >	
												
											</select>
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<label>Rate</label>
											<input class="form-control number input-sm" name="input_rate_6_b" id="input_rate_6_b" placeholder="Rate" value="0.00" type="text"  />
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<label>Qty</label>
											<input class="form-control input-sm" name="input_qty_6_b" id="input_qty_6_b" placeholder="Qty" value="1" type="number"  />
										</div>
									</div>
									<div class="col-md-12">
										<div class="form-group">
											<label>Comment</label>
											<input class="form-control varchar input-sm" name="input_comment_6_b" id="input_comment_6_b" placeholder="" value="" type="text"  />
										</div>
									</div>
									
									<div class="col-md-3">
										<div class="form-group">
											<button type="button" class="btn btn-primary" id="additem" onclick='add_item_invoice_6_b(this)' >Add By Name</button>
										</div>
									</div>
								</div>
								<div class="row" style="background-color:orange;padding: 10px;">
									<div class="col-md-12">
										<div class="form-group">
											<label>Specility</label>
											<select class="form-control input-sm select2" id="Specility_6_c" name="Specility_6_c" style="width: 100%;" >	
												<?php 
												foreach($med_spec as $row)
												{ 
													echo '<option value='.$row->id.'  >'.$row->SpecName.'</option>';
												}
												?>
											</select>
										</div>
									</div>
									<div class="col-md-12">
										<div class="form-group">
											<label>Doctor Name</label>
											<input class="form-control varchar input-sm" name="input_doc_name_6c" id="input_doc_name_6c" placeholder="" value="" type="text" autocomplete=true  />
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label>Rate</label>
											<input class="form-control number input-sm" name="input_rate_6_c" id="input_rate_6_c" placeholder="Rate" value="0.00" type="text"  />
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label>Qty</label>
											<input class="form-control input-sm" name="input_qty_6_c" id="input_qty_6_c" placeholder="Qty" value="1" type="number"  />
										</div>
									</div>
									<div class="col-md-12">
										<div class="form-group">
											<label>Comment</label>
											<input class="form-control varchar input-sm" name="input_comment_6_c" id="input_comment_6_c" placeholder="" value="" type="text"  />
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<button type="button" class="btn btn-primary" id="additem" onclick='add_item_invoice_6_c(this)'  >Add By Specialization</button>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="panel panel-warning ">
					<div class="panel-heading">
					<h4 class="panel-title">
						<a data-toggle="collapse" data-parent="#accordion" href="#collapse7">
						Implant Bills</a>
					</h4>
					</div>
					<div id="collapse7" class="panel-collapse collapse">
					<div class="panel-body">
							<div class="row">
								<div class="col-md-6 ">
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
								<div class="col-md-6">
									<div class="form-group">
										<label>Comment / Batch No. /Invoice No.</label>
										<input class="form-control varchar input-sm" name="input_comment_7" id="input_comment_7" placeholder="" value="" type="text"  />
									</div>
								</div>
								<div class="col-md-4">
									<div class="form-group">
										<label>Rate</label>
										<input class="form-control number input-sm" name="input_rate_7" id="input_rate_7" placeholder="Rate" value="0.00" type="text"  />
									</div>
								</div>
								<div class="col-md-4">
									<div class="form-group">
										<label>Qty</label>
										<input class="form-control input-sm" name="input_qty_7" id="input_qty_7" placeholder="Qty" value="1" type="number"  />
									</div>
								</div>
								<div class="col-md-4">
									<div class="form-group">
										<button type="button" class="btn btn-primary" id="additem" onclick='add_item_invoice_7(this)'  accesskey="A" ><u>A</u>dd in List</button>
									</div>
								</div>
							</div>
					</div>
					</div>
				</div>

				<div class="panel panel-danger">
					<div class="panel-heading">
					<h4 class="panel-title">
						<a data-toggle="collapse" data-parent="#accordion" href="#collapse8">
						Other Charges</a>
					</h4>
					</div>
					<div id="collapse8" class="panel-collapse collapse">
					<div class="panel-body">
								<div class="row">
								<div class="col-md-12 ">
									<div class="form-group">
									<label>Charge Name</label>
										<select class="form-control input-sm" id="itype_name_id_8" name="itype_name_id_8"  >					
											<?php 
											foreach($item_list_8 as $row)
											{ 
												echo '<option value='.$row->id.'>'.$row->sdesc.'</option>';
											}
											?>
										</select>
									</div>
								</div>
								<div class="col-md-3">
									<div class="form-group">
										<label>Rate</label>
										<input class="form-control number input-sm" name="input_rate_8" id="input_rate_8" placeholder="Rate" value="0.00" type="text"  />
									</div>
								</div>
								<div class="col-md-3">
									<div class="form-group">
										<label>Qty</label>
										<input class="form-control input-sm" name="input_qty_8" id="input_qty_8" placeholder="Qty" value="1" type="number"  />
									</div>
								</div>
								<div class="col-md-6">
									<div class="form-group">
									<label>Doctor Name</label>
										<select class="form-control input-sm" id="doc_name_id_8" name="doc_name_id_8"  >	
											<?php 
											echo '<option value=0>NONE</option>';
											foreach($doclist as $row)
											{ 
												echo '<option value='.$row->id.'  >'.$row->DocSpecName.'</option>';
											}
											?>
										</select>
									</div>
								</div>
								<div class="col-md-12">
									<div class="form-group">
										<label>Comment</label>
										<input class="form-control varchar input-sm" name="input_comment_8" id="input_comment_8" placeholder="" value="" type="text"  />
									</div>
								</div>
								<div class="col-md-3">
									<div class="form-group">
										<button type="button" class="btn btn-primary" id="additem" onclick='add_item_invoice_8(this)'   >Add in List</button>
									</div>
								</div>
							</div>
					</div>
					</div>
				</div>
				
				<div class="panel panel-success">
					<div class="panel-heading">
					<h4 class="panel-title">
						<a data-toggle="collapse" data-parent="#accordion" href="#collapse10">
						Investigation Charges</a>
					</h4>
					</div>
					<div id="collapse10" class="panel-collapse collapse">
					<div class="panel-body">
								<div class="row">
								<div class="col-md-10 ">
									<div class="form-group">
									<label>Charge Name</label>
										<select class="form-control input-sm select2" id="itype_name_id_10" name="itype_name_id_10"  style="width: 100%;">					
											<?php 
											foreach($item_list_10 as $row)
											{ 
												echo '<option value='.$row->id.'>'.$row->sdesc.'</option>';
											}
											?>
										</select>
									</div>
								</div>
								<div class="col-md-3">
									<div class="form-group">
										<label>Rate</label>
										<input class="form-control number input-sm" name="input_rate_10" id="input_rate_10" placeholder="Rate" value="0.00" type="text"  />
									</div>
								</div>
								<div class="col-md-3">
									<div class="form-group">
										<label>Qty</label>
										<input class="form-control input-sm" name="input_qty_10" id="input_qty_10" placeholder="Qty" value="1" type="number"  />
									</div>
								</div>
								<div class="col-md-6">
									<div class="form-group">
									<label>Doctor Name</label>
										<select class="form-control input-sm" id="doc_name_id_10" name="doc_name_id_10"  >	
											<?php 
											echo '<option value=0>NONE</option>';
											foreach($doclist as $row)
											{ 
												echo '<option value='.$row->id.'  >'.$row->DocSpecName.'</option>';
											}
											?>
										</select>
									</div>
								</div>
								<div class="col-md-12">
									<div class="form-group">
										<label>Comment</label>
										<input class="form-control varchar input-sm" name="input_comment_10" id="input_comment_10" placeholder="" value="" type="text"  />
									</div>
								</div>
								<div class="col-md-3">
									<div class="form-group">
										<button type="button" class="btn btn-primary" id="additem" onclick='add_item_invoice_10(this)'   >Add in List</button>
									</div>
								</div>
							</div>
					</div>
					</div>
				</div>

				<div class="panel panel-default">
					<div class="panel-heading">
					<h4 class="panel-title">
						<a data-toggle="collapse" data-parent="#accordion" href="#collapse9">
						Custom</a>
					</h4>
					</div>
					<div id="collapse9" class="panel-collapse collapse">
						<div class="panel-body">
							<div class="row">
								<div class="col-md-6 ">
									<div class="form-group">
									<label>Charge Type</label>
										<select class="form-control input-sm" id="itype_charge_type_custom" name="itype_charge_type_custom"  >					
											<?php 
											foreach($item_list_cat as $row)
											{ 
												echo '<option value='.$row->itype_id.'>'.$row->group_desc.'</option>';
											}
											?>
										</select>
									</div>
								</div>
								<div class="col-md-6">
									<div class="form-group">
										<label>Item Name</label>
										<input class="form-control varchar input-sm" name="input_charge_custom" id="input_charge_custom" placeholder="" value="" type="text"  />
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-3">
									<div class="form-group">
										<label>Rate</label>
										<input class="form-control number input-sm" name="input_rate_custom" id="input_rate_custom" placeholder="Rate" value="0.00" type="text"  />
									</div>
								</div>
								<div class="col-md-3">
									<div class="form-group">
										<label>Qty</label>
										<input class="form-control input-sm" name="input_qty_custom" id="input_qty_custom" placeholder="Qty" value="1" type="number"  />
									</div>
								</div>
								<div class="col-md-6">
									<div class="form-group">
										<label>Panel Doctor Name</label>
											<select class="form-control input-sm" id="doc_name_id_custom" name="doc_name_id_custom"  >	
												<?php 
												echo '<option value=0>NONE</option>';
												foreach($doclist as $row)
												{ 
													echo '<option value='.$row->id.'  >'.$row->DocSpecName.'</option>';
												}
												?>
											</select>
										</div>
									</div>
								<div class="col-md-12">
									<div class="form-group">
										<label>Comment</label>
										<input class="form-control varchar input-sm" name="input_comment_custom" id="input_comment_custom" placeholder="" value="" type="text"  />
									</div>
								</div>
								<div class="col-md-2">
									<div class="form-group">
										<button type="button" class="btn btn-primary" id="additem_custom" onclick='add_item_invoice_custom(this)'   >Add in List</button>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="box-footer">
		</div>
	</div>
	<?php echo form_close(); ?>
</div>
</div>
</section>
<!-- /.content -->
<script>
    $(document).ready(function(){
		var ipd_id=$('#Ipd_ID').val();
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
		var itype_name_id_6=$('#itype_name_id_6').val();
		
		doc_name_id_6_b=$('#doc_name_id_6_b').val();
		load_form_div('/IpdNew/doc_ipd_fee/'+doc_name_id_6_b,'doc_fee_id_6_b');

		$("#itype_name_id").change(function(){
			$('#input_rate').val('0.00');
			$('#input_qty').val('1');
		});

		load_form_div('/IpdNew/show_ipd_items/'+ipd_id+'/1','show_item_list');
		
		$("#doc_name_id_6_b").change(function(){
			doc_name_id_6_b=$('#doc_name_id_6_b').val();
			load_form_div('/Ipd/doc_ipd_fee/'+doc_name_id_6_b,'doc_fee_id_6_b');
		});
	});
	
	// IPD Registration
	
	
	function add_item_invoice_1(control_button)
	{
		control_button.disabled=true;

		var ipd_id=$('#Ipd_ID').val();
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		$.post('/index.php/IpdNew/ipd_showitem/1',
			{ "itype_name_id": $('#itype_name_id_1').val(), 
			"itype_idv": 1, 
			"input_qty": 1,
			"input_rate": $('#input_rate_1').val(),
			"ipd_id": $('#Ipd_ID').val(),
			"doc_id": 0, 
			"doc_name": '',
			"comment": '',
			"date_item": '',
			"ins_id": <?=$ins_comp_id?>,
			"<?=$this->security->get_csrf_token_name()?>":csrf_value}, function(data){
				if(data==0)
				{
					alert("Already Add");
				}else{
					load_form_div('/IpdNew/show_ipd_items/'+ipd_id+'/1','show_item_list');
				}
				
				$('#input_rate_1').val('0.00');
			});
			
			setTimeout(function () { 
					enable_button(control_button);
			}, 1000);
	}

	//ACCOMMODATION
	
	function add_item_invoice_2(control_button)
	{
		var ipd_id=$('#Ipd_ID').val();
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
		control_button.disabled=true;

		$.post('/index.php/IpdNew/ipd_showitem/1',
			{ "itype_name_id": $('#itype_name_id_2').val(), 
			"itype_idv": 2, 
			"input_qty": $('#input_qty_2').val(),
			"input_rate": $('#input_rate_2').val(),
			"ipd_id": $('#Ipd_ID').val(),
			"doc_id": 0, 
			"doc_name": '',
			"comment": $('#input_comment_2').val(),
			"date_item": '',
			"ins_id": <?=$ins_comp_id?>,
			"<?=$this->security->get_csrf_token_name()?>":csrf_value}, function(data){
				load_form_div('/IpdNew/show_ipd_items/'+ipd_id+'/1','show_item_list');
				$('#input_rate_2').val('0.00');
				$('#input_qty_2').val('1');
			});

		setTimeout(function () { 
				enable_button(control_button);
		}, 1000);
	}
	
	//Surgery & OPERATION
	
	function add_item_invoice_3(control_button)
	{
		var ipd_id=$('#Ipd_ID').val();
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
		control_button.disabled=true;

		if(confirm('Arue you sure to add?'))
		{
			$.post('/index.php/IpdNew/ipd_showitem/1',
			{ "itype_name_id": $('#itype_name_id_3').val(), 
			"itype_idv": 3, 
			"input_qty": $('#input_qty_3').val(),
			"input_rate": $('#input_rate_3').val(),
			"ipd_id": $('#Ipd_ID').val(),
			"doc_id": 0, 
			"doc_name": '',
			"comment": $('#input_comment_3').val(),
			"date_item": '',
			"doc_id": $("#doc_name_id_3").val(),
			"ins_id": <?=$ins_comp_id?>,
			"<?=$this->security->get_csrf_token_name()?>":csrf_value}, function(data){
				load_form_div('/IpdNew/show_ipd_items/'+ipd_id+'/1','show_item_list');
				$('#input_rate_3').val('0.00');
				$('#input_qty_3').val('1');
				$('#input_comment_3').val('');
			});
		}

		setTimeout(function () { 
					enable_button(control_button);
		}, 1000);
	}

	function add_item_invoice_5(control_button)
	{
		var ipd_id=$('#Ipd_ID').val();
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
		control_button.disabled=true;

		if(confirm('Arue you sure to add?'))
		{
			$.post('/index.php/IpdNew/ipd_showitem/1',
			{ "itype_name_id": $('#itype_name_id_5').val(), 
			"itype_idv": 5, 
			"input_qty": $('#input_qty_5').val(),
			"input_rate": $('#input_rate_5').val(),
			"ipd_id": $('#Ipd_ID').val(),
			"doc_id": 0, 
			"doc_name": '',
			"comment": $('#input_comment_5').val(),
			"date_item": '',
			"doc_id": $("#doc_name_id_5").val(),
			"ins_id": <?=$ins_comp_id?>,
			"<?=$this->security->get_csrf_token_name()?>":csrf_value}, function(data){
				load_form_div('/IpdNew/show_ipd_items/'+ipd_id+'/1','show_item_list');
				$('#input_rate_5').val('0.00');
				$('#input_qty_5').val('1');
				$('#input_comment_5').val('');
			});
		}

		setTimeout(function () { 
					enable_button(control_button);
			}, 1000);
	}
	
	//Other Charges
	function add_item_invoice_8(control_button)
	{
		var ipd_id=$('#Ipd_ID').val();
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
		control_button.disabled=true;

		if(confirm('Arue you sure to add?'))
		{
			$.post('/index.php/IpdNew/ipd_showitem/1',
			{ "itype_name_id": $('#itype_name_id_8').val(), 
			"itype_idv": 8, 
			"input_qty": $('#input_qty_8').val(),
			"input_rate": $('#input_rate_8').val(),
			"ipd_id": $('#Ipd_ID').val(),
			"doc_id": 0, 
			"doc_name": '',
			"comment": $('#input_comment_8').val(),
			"date_item": '',
			"doc_id": $("#doc_name_id_8").val(),
			"ins_id": <?=$ins_comp_id?>,
			"<?=$this->security->get_csrf_token_name()?>":csrf_value}, function(data){
				load_form_div('/IpdNew/show_ipd_items/'+ipd_id+'/1','show_item_list');
				$('#input_rate_8').val('0.00');
				$('#input_qty_8').val('1');
				$('#input_comment_8').val('');
			});
		}

		setTimeout(function () { 
					enable_button(control_button);
			}, 1000);
	}


	//Investigation Charges
	function add_item_invoice_10(control_button)
	{
		var ipd_id=$('#Ipd_ID').val();
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
		control_button.disabled=true;

		if(confirm('Arue you sure to add?'))
		{
			$.post('/index.php/IpdNew/ipd_showitem/1',
			{ "itype_name_id": $('#itype_name_id_10').val(), 
			"itype_idv": 10, 
			"input_qty": $('#input_qty_10').val(),
			"input_rate": $('#input_rate_10').val(),
			"ipd_id": $('#Ipd_ID').val(),
			"doc_id": 0, 
			"doc_name": '',
			"comment": $('#input_comment_10').val(),
			"date_item": '',
			"doc_id": $("#doc_name_id_10").val(),
			"ins_id": <?=$ins_comp_id?>,
			"<?=$this->security->get_csrf_token_name()?>":csrf_value}, function(data){
				load_form_div('/IpdNew/show_ipd_items/'+ipd_id+'/1','show_item_list');
				$('#input_rate_10').val('0.00');
				$('#input_qty_10').val('1');
				$('#input_comment_10').val('');
			});
		}

		setTimeout(function () { 
					enable_button(control_button);
			}, 1000);
	}
	

	//Professional Charges
	function add_item_invoice_6_a(control_button)
	{
		var ipd_id=$('#Ipd_ID').val();
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
		control_button.disabled=true;

		$.post('/index.php/IpdNew/ipd_showitem/1',
			{ "itype_name_id": $('#itype_name_id_6').val(), 
			"itype_idv": 6, 
			"input_qty": $('#input_qty_6_a').val(),
			"input_rate": $('#input_rate_6_a').val(),
			"ipd_id": $('#Ipd_ID').val(),
			"doc_id": 0, 
			"doc_name": '',
			"comment": $('#input_comment_6_a').val(),
			"date_item": $('#datepicker_itemdate_1').val(),
			"ins_id": <?=$ins_comp_id?>,
			"<?=$this->security->get_csrf_token_name()?>":csrf_value}, function(data){
				load_form_div('/IpdNew/show_ipd_items/'+ipd_id,'show_item_list');
				$('#input_rate_6_a').val('0.00');
				$('#input_qty_6_a').val('1');
				$('#input_comment_6_a').val('');
			});

			setTimeout(function () { 
					enable_button(control_button);
			}, 1000);
	}
	
	function add_item_invoice_6_b(control_button)
	{
		var ipd_id=$('#Ipd_ID').val();
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
		control_button.disabled=true;

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
			
			$.post('/index.php/IpdNew/ipd_showitem/1',
				{ "itype_name_id": 186,
				"itype_idv": 6,
				"input_qty": $('#input_qty_6_b').val(),
				"input_rate": doc_fee,
				"ipd_id": $('#Ipd_ID').val(),
				"doc_id": $("#doc_name_id_6_b").val(),
				"doc_spec": '',
				"doc_name": $("#doc_name_id_6_b :selected").text(),
				"ins_id": <?=$ins_comp_id?>,
				"<?=$this->security->get_csrf_token_name()?>":csrf_value,
				"comment": 'Dr. '+$("#doc_name_id_6_b :selected").text()+ ' ' +$('#input_comment_6_b').val()}, function(data){
					load_form_div('/IpdNew/show_ipd_items/'+ipd_id,'show_item_list');
					$('#input_rate_6_b').val('0.00');
					$('#input_qty_6_b').val('1');
					$('#input_comment_6_b').val('');
				});
		}

		setTimeout(function () { 
					enable_button(control_button);
			}, 1000);
	}
	
	function add_item_invoice_6_c(control_button)
	{
		var ipd_id=$('#Ipd_ID').val();
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
		var doc_fee=$('#input_rate_6_c').val();
		var doc_name= $('#input_doc_name_6c').val()+' : '+$("#Specility_6_c :selected").text();
		var comment = "Dr."+doc_name+'\n'+$('#input_comment_6_c').val();
		control_button.disabled=true;

                                                                                                                                                                                                                                                                                                                                  
		$.post('/index.php/IpdNew/ipd_showitem/1',
				{ "itype_name_id": 186, 
				"itype_idv": 6, 
				"input_qty": $('#input_qty_6_c').val(),
				"input_rate": doc_fee,
				"ipd_id": $('#Ipd_ID').val(),
				"doc_id": '0',
				"doc_spec":$('#Specility_6_c').val(),
				"<?=$this->security->get_csrf_token_name()?>":csrf_value ,				
				"doc_name": doc_name,
				"ins_id": <?=$ins_comp_id?>,
				"comment": comment}, function(data){
					load_form_div('/IpdNew/show_ipd_items/'+ipd_id,'show_item_list');
					$('#input_rate_6_c').val('0.00');
					$('#input_qty_6_c').val('1');
					$('#input_comment_6_c').val('');
				});
		
		setTimeout(function () { 
			enable_button(control_button);
		}, 1000);
	}
	
	//Implant Bills
	function add_item_invoice_7(control_button)
	{
		var ipd_id=$('#Ipd_ID').val();
		var qty=$('#input_qty_7').val();
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		control_button.disabled=true;

		if(qty>0)
		{
			$.post('/index.php/IpdNew/ipd_showitem/1',
			{ "itype_name_id": $('#itype_name_id_7').val(), 
			"itype_idv": 7, 
			"input_rate": $('#input_rate_7').val(),
			"input_qty": $('#input_qty_7').val(),
			"ipd_id": $('#Ipd_ID').val(),
			"doc_id": 0, 
			"doc_name": '',
			"ins_id": <?=$ins_comp_id?>,
			"<?=$this->security->get_csrf_token_name()?>":csrf_value,
			"comment": $('#input_comment_7').val()
			}, function(data){
				load_form_div('/IpdNew/show_ipd_items/'+ipd_id+'/1','show_item_list');
				$('#input_rate_7').val('0.00');
				$('#input_qty_7').val('1');
				$('#input_comment_7').val('');
			});
		}else{
			alert('Qty Should be Greater then 0')
		}

		setTimeout(function () { 
				enable_button(control_button);
		}, 1000);

	}
	
	//Others Items
	function add_item_invoice_custom(control_button)
	{
		
		var ipd_id=$('#Ipd_ID').val();
		var itype_charge_type_custom=$('#itype_charge_type_custom').val();
		
		var charge_name_id= 0;
		var charge_name= $('#input_charge_custom').val();
		
		var qty=$('#input_qty_custom').val();
		var rate=$('#input_rate_custom').val();

		var doc_id=$('#doc_name_id_custom').val();
		var comment=$('#input_comment_custom').val();

		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		control_button.disabled=true;

		if(qty>0 && charge_name!='' )
		{
			$.post('/index.php/IpdNew/ipd_showitem/1',
			{ 
			"itype_name_id":charge_name_id,
			"itype_name": charge_name,  
			"itype_idv": itype_charge_type_custom, 
			"input_rate": rate,
			"input_qty": qty,
			"ipd_id": ipd_id,
			"doc_id": doc_id, 
			"refername": '',
			"ins_id": <?=$ins_comp_id?>,
			"<?=$this->security->get_csrf_token_name()?>":csrf_value,
			"comment": comment
			}, function(data){
				load_form_div('/IpdNew/show_ipd_items/'+ipd_id+'/1','show_item_list');
				$('#input_rate_custom').val('0.00');
				$('#input_qty_custom').val('1');
				$('#input_comment_custom').val('');
			});
		}else{
			alert('Qty Should be Greater then 0')
		}

		setTimeout(function () { 
					enable_button(control_button);
		}, 1000);
		
	}

	// Remove and update for All

	function remove_item_invoice(itemid)
	{
		var ipd_id=$('#Ipd_ID').val();
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
		
		if(confirm("Are you sure Remove this item "))
		{
			$.post('/index.php/IpdNew/ipd_showitem/0',
			{ "itemid": itemid, 
			"lab_invoice_id": $('#lab_invoice_id').val(),
			"ipd_id": $('#Ipd_ID').val(),
			"<?=$this->security->get_csrf_token_name()?>":csrf_value}, function(data){
				load_form_div('/IpdNew/show_ipd_items/'+ipd_id,'show_item_list');
			});
		}
	}
	
	function update_qty(itemid)
	{
		var ipd_id=$('#Ipd_ID').val();
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
		
		if(confirm("Are you sure Update this item "))
		{
			var update_qty=$('#input_qty_'+itemid).val();
			var item_rate=$('#hidden_rate_'+itemid).val();
			
			$.post('/index.php/IpdNew/ipd_showitem/2',{ "itemid": itemid, 
			"lab_invoice_id": $('#lab_invoice_id').val(),
			"update_qty": update_qty,
			"item_rate": item_rate,
			"ipd_id": $('#Ipd_ID').val(),
			"<?=$this->security->get_csrf_token_name()?>":csrf_value}, function(data){
				load_form_div('/IpdNew/show_ipd_items/'+ipd_id,'show_item_list');
				notify('success','Update Item','Update Successfully');	
			});
		}
	}

	function onChangeUpdate(cb,cd) {
		var check_value=0;
		if (cb.checked)
		{
			check_value=1;
		}
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		$.post('/index.php/IpdNew/Update_Invoice_ipd_itmes_package',
		{ "ipd_item_id":cd ,
		"ipd_item_package_type": check_value,
		"<?=$this->security->get_csrf_token_name()?>":csrf_value}, function(data){
			notify('success','Update Item','Update Successfully');		
		});
	}
</script>