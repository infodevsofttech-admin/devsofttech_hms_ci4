<div class="row">
	<div class="col-md-12">
		<div class="jsError"></div>
		<div class="box">
			<div class="box-header">
				<h3 class="box-title">Purchase Invoice Information</h3>
				<small><i>Supplier</i>: | <?= $purchase_invoice[0]->name_supplier ?>
					<?php if ($purchase_invoice[0]->ischallan == 1) { ?>
						| <i>Challan No.</i>: <?= $purchase_invoice[0]->Invoice_no ?>
					<?php } else { ?>
						<i>Invoice No.</i>: <?= $purchase_invoice[0]->Invoice_no ?>
					<?php } ?>
					<i>Invoice Date</i> : <?= $purchase_invoice[0]->str_date_of_invoice ?>

					<button onclick="load_form_div('/Storestock/PurchaseMasterEdit/<?= $purchase_invoice[0]->id ?>','searchresult');" type="button" class="btn btn-warning">Edit Invoice</button>
				</small>
			</div>
			<div class="box-body">
				<?php echo form_open('AddNew', array('role' => 'form', 'class' => 'form2')); ?>
				<div id="invoice_item_list"></div>
				<?php echo form_close(); ?>
			</div>
			<div class="box-footer">
				<?php
				if ($purchase_invoice[0]->inv_status == 0) {
				?>
					<?php echo form_open('AddNew', array('role' => 'form', 'class' => 'form1')); ?>
					<input type="hidden" id="invoice_id" name="invoice_id" value="<?= $purchase_invoice[0]->id ?>">
					<div class="row">
						<div class="col-md-8">
							<div class="row">
								<div class="col-md-6">
									<div class="form-group">
										<div class="ui-widget">
											<label for="tags">Product Search: </label>
											<input class="form-control input-sm" name="input_drug" id="input_drug" placeholder="Like Item Code , Item Name" type="text">
											<input name="input_drug_hid" id="input_drug_hid" type="hidden">
										</div>
									</div>
								</div>
								<div class="col-md-4">
									<div class="form-group">
										<label>Product Code</label>
										<input class="form-control input-sm" name="input_product_code" id="input_product_code" placeholder="Product Code" type="text" readonly />
									</div>
								</div>
								<div class="col-md-2">
									<div class="form-group">
										<a href="javascript:show_challan();"><i class="fa fa-circle-o"></i> Challan Item List</a>
									</div>
								</div>
							</div>
							<div class="row" id="update_purchase_items">
								<div class="col-md-2">
									<div class="form-group">
										<label>Packaging </label>
										<input class="form-control number input-sm" name="input_package" id="input_package" placeholder="Qty * Strip" type="text" data-toggle="tooltip" data-placement="top" title="Have Check Packing ?" />
									</div>
								</div>
								<div class="col-md-2">
									<div class="form-group">
										<label>Batch No.</label>
										<input class="form-control input-sm" name="input_batch_code" id="input_batch_code" placeholder="Batch No." type="text" />
									</div>
								</div>
								<div class="col-md-2">
									<div class="form-group form-inline">
										<label>Date of Expiry</label>
										<select ID="datepicker_doe_month" name="datepicker_doe_month" class="form-control input-sm" data-toggle="tooltip" data-placement="top" title="Check Expiry Month">
											<option VALUE="01">1</option>
											<option VALUE="02">2</option>
											<option VALUE="03">3</option>
											<option VALUE="04">4</option>
											<option VALUE="05">5</option>
											<option VALUE="06">6</option>
											<option VALUE="07">7</option>
											<option VALUE="08">8</option>
											<option VALUE="09">9</option>
											<option VALUE="10">10</option>
											<option VALUE="11">11</option>
											<option VALUE="12">12</option>
										</select>
										<select ID="datepicker_doe_year" name="datepicker_doe_year" class="form-control input-sm" data-toggle="tooltip" data-placement="top" title="Check Expiry Year">
											<?php
											$year = date('y');
											for ($i = 0; $i < 10; $i++) {
												echo "<option>" . $year . "</option>";
												$year = $year + 1;
											}
											?>
										</select>
									</div>
								</div>
								<div class="col-md-2">
									<div class="form-group">
										<label>MRP</label>
										<input class="form-control number input-sm" name="input_product_mrp" id="input_product_mrp" placeholder="Product MRP" type="text" onchange="update_selling_rate()" data-toggle="tooltip" data-placement="top" title="Check MRP" />
									</div>
								</div>
								<div class="col-md-2">
									<div class="form-group">
										<label>Qty</label>
										<input class="form-control number input-sm" name="input_Qty" id="input_Qty" placeholder="Qty" type="text" value="0" onchange="calculate()" />
									</div>
								</div>
								<div class="col-md-2">
									<div class="form-group">
										<label>Free Qty</label>
										<input class="form-control number input-sm" name="input_Qty_Free" id="input_Qty_Free" placeholder="Free Package" type="text" value="0" />
									</div>
								</div>
								<div class="col-md-2">
									<div class="form-group">
										<label>Purchase Rate</label>
										<input class="form-control number input-sm" name="input_purchase_price" id="input_purchase_price" placeholder="Purchase Price" type="text" onchange="calculate()" data-toggle="tooltip" data-placement="top" title="Check Last Rate From Right List" />
									</div>
								</div>
								<div class="col-md-2">
									<div class="form-group">
										<label>Amount</label>
										<input class="form-control number input-sm" name="amount_price" id="amount_price" placeholder="" type="text" Readonly />
									</div>
								</div>
								<div class="col-md-2">
									<div class="form-group">
										<label>Discount %</label>
										<input class="form-control number input-sm" name="input_disc_price" id="input_disc_price" placeholder="Discount in %" type="text" onchange="calculate()" />
									</div>
								</div>
								<div class="col-md-2">
									<div class="form-group">
										<label>SCH Amount</label>
										<input class="form-control number input-sm" name="input_sch_amount" id="input_sch_amount" placeholder="" type="text" onchange="calculate()" />
									</div>
								</div>
								<div class="col-md-2">
									<div class="form-group">
										<label>SCH Discount %</label>
										<input class="form-control number input-sm" name="input_sch_disc" id="input_sch_disc" placeholder="Discount in %" type="text" onchange="calculate()" />
									</div>
								</div>
								<div class="col-md-2">
									<div class="form-group">
										<label>Taxable Amount</label>
										<input class="form-control number input-sm" name="Tamount_price" id="Tamount_price" placeholder="" type="text" Readonly />
									</div>
								</div>
								<div class="col-md-2">
									<div class="form-group">
										<label>CGST</label>
										<select class="form-control input-sm" ID="input_CGST" name="input_CGST" onchange="calculate()">
											<?php
											foreach ($med_gst_per as $row) {
												echo "<option>" . $row->gst_per . "</option>";
											}
											?>
										</select>
									</div>
								</div>
								<div class="col-md-2">
									<div class="form-group">
										<label>SGST / IGST</label>
										<select class="form-control input-sm" ID="input_SGST" name="input_SGST" onchange="calculate()">
											<?php
											foreach ($med_gst_per as $row) {
												echo "<option>" . $row->gst_per . "</option>";
											}
											?>
										</select>
									</div>
								</div>
								<div class="col-md-2">
									<div class="form-group">
										<label>Net Amount</label>
										<input class="form-control number input-sm" name="Net_amount" id="Net_amount" placeholder="" type="text" Readonly />
									</div>
								</div>
								<div class="col-md-2">
									<div class="form-group">
										<label>Selling Price</label>
										<input class="form-control number input-sm" name="input_selling_price" id="input_selling_price" placeholder="Selling Price" type="text" />
									</div>
								</div>
								<div class="col-md-2">
									<div class="form-group">
										<label>HSNCODE</label>
										<input class="form-control number input-sm" name="input_HSNCODE" id="input_HSNCODE" placeholder="HSN CODE" type="text" value="3004" />
									</div>
								</div>
								<!--
						<div class="col-md-2"> 
							<div class="form-group">
								<label>Storage</label>
								<input class="form-control input-sm" name="input_storage" id="input_storage" placeholder="Storage Name" type="text"   />
							</div>
						</div>
						
						<div class="col-md-2">
							<div class="form-group">
								<label>Shelf No.</label>
								<input class="form-control input-sm" name="input_shelf_no" id="input_shelf_no" placeholder="Shelf No." type="text"   />
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<label>Rack No.</label>
								<input class="form-control input-sm" name="input_rack_no" id="input_rack_no" placeholder="Rack No." type="text"   />
							</div>
						</div>
						-->
								<div class="col-md-2">
									<div class="form-group">
										<label> </label>
										<input type="hidden" id="invoice_item_id" name="invoice_item_id" value="0">
										<button type="button" class="btn btn-primary" id="btn_update_stock" accesskey="A"><u>A</u>dd Stock</button>
									</div>
								</div>
								<div class="col-md-2">
									<div class="form-group">
										<label> </label>
										<button type="button" class="btn btn-danger" id="btn_update_stock_return" accesskey="R"><u>R</u>etrun Stock</button>
									</div>
								</div>
							</div>
						</div>
						<div class="col-md-4" style="overflow:auto;" id="purchase_items_old_history">

						</div>
					</div>
					<?php echo form_close(); ?>
				<?php } ?>
			</div>
		</div>
	</div>
</div>


<script>
	show_challan();

	function calculate() {
		var pp = $("#input_purchase_price").val();
		var qty = $("#input_Qty").val();

		var disc = $("#input_disc_price").val();

		var schamt = $("#input_sch_amount").val();
		var schdisc = $("#input_sch_disc").val();


		if (schdisc > 0) {
			pp = pp - (pp * schdisc / 100);
		}

		var amount = pp * qty;

		if (schamt > 0) {
			amount = amount - schamt;
		}


		var taxamount = (amount - (amount * disc / 100));

		var cgst = $("#input_CGST").val();
		var sgst = $("#input_SGST").val();

		var cgst_amt = taxamount * cgst / 100;
		var sgst_amt = taxamount * sgst / 100;

		var net_amount = taxamount + cgst_amt + sgst_amt;

		$("#amount_price").val(amount.toFixed(2));
		$("#Tamount_price").val(taxamount.toFixed(2));
		$("#Net_amount").val(net_amount.toFixed(2));

		$("#input_selling_price").val($("#input_product_mrp").val());
	}


	function update_selling_rate() {
		var product_mrp = $("#input_product_mrp").val();

		$("#input_selling_price").val(product_mrp);
	}

	function toggle_update_purchase_items(flag) {
		if (flag == true) {
			$('#update_purchase_items').show();
			$('#purchase_items_old_history').show();

			reset_input();
		} else {
			$('#update_purchase_items').hide();
			$('#purchase_items_old_history').hide();
			reset_input();
		}
	}

	function reset_input() {
		$("#input_product_code").val('');
		$("#input_product_mrp").val('');
		$("#input_selling_price").val('');
		$("#input_batch_code").val('');
		$("#amount_price").val('');
		$("#Tamount_price").val('');
		$("#Net_amount").val('');

		$("#input_disc_price").val('');

		$("#input_sch_amount").val('');
		$("#input_sch_disc").val('');

		$("#input_Qty").val('');
		$("#input_Qty_Free").val('');

		$("#input_Freepackage").val('');
		$("#input_package").val('');
		$("#input_purchase_price").val('');
		$("#input_drug").val('');
		$("#input_drug_hid").val('');

		$("#invoice_item_id").val('0');
	}

	function remove_item_invoice(inv_item_id) {
		$.post('/Storestock/purchase_invoice_item_delete/<?= $purchase_invoice[0]->id ?>/' + inv_item_id, {
			'inv_item_id': inv_item_id,
			<?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>'
		}, function(data) {
			if (data.is_update_stock == 0) {
				alert(data.show_text);
			} else {
				alert(data.show_text);
			}
			load_form_div('/Storestock/purchase_invoice_item_list/<?= $purchase_invoice[0]->id ?>', 'invoice_item_list');
		}, 'json');
	}

	function show_challan() {
		$('#purchase_items_old_history').show();
		load_form_div('/Storestock/challan_invoice/<?= $purchase_invoice[0]->sid ?>', 'purchase_items_old_history');
	}

	function add_item_in_invoice(ss_no) {
		$.post('/Storestock/challan_item_to_purchase/' + ss_no + '/<?= $purchase_invoice[0]->id ?>', $('form.form1').serialize(), function(data) {
			if (data.is_transfer == 0) {
				notify('error', 'Please Attention', data.show_text);
			} else {
				notify('success', 'Please Attention', data.show_text);
				load_form_div('/Storestock/purchase_invoice_item_list/<?= $purchase_invoice[0]->id ?>', 'invoice_item_list');
				load_form_div('/Storestock/challan_invoice/<?= $purchase_invoice[0]->sid ?>', 'purchase_items_old_history');
			}
		}, 'json');
	}

	function return_to_challan(ss_no) {
		$.post('/Storestock/challan_item_return/' + ss_no, $('form.form1').serialize(), function(data) {
			if (data.is_transfer == 0) {
				notify('error', 'Please Attention', data.show_text);
			} else {
				notify('success', 'Please Attention', data.show_text);
				load_form_div('/Storestock/purchase_invoice_item_list/<?= $purchase_invoice[0]->id ?>', 'invoice_item_list');
				load_form_div('/Storestock/challan_invoice/<?= $purchase_invoice[0]->sid ?>', 'purchase_items_old_history');
			}
		}, 'json');
	}

	function edit_item_invoice(inv_item_id) {
		$.post('/Storestock/purchase_invoice_item_edit/<?= $purchase_invoice[0]->id ?>/' + inv_item_id, {
			'inv_item_id': inv_item_id,
			<?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>'
		}, function(data) {
			if (data.is_update_stock == 0) {
				$('#msgshow').html(data.show_text);
				$("#alert_show").alert();
				$("#alert_show").fadeTo(2000, 500).slideUp(500, function() {
					$("#alert_show").slideUp(500);
				});
				alert(data.show_text);
			} else {
				toggle_update_purchase_items(true);

				$("#input_product_code").val(data.product_code);

				$("#input_product_mrp").val(data.product_mrp);
				$("#input_selling_price").val(data.selling_price);
				$("#input_batch_code").val(data.batch_code);

				$("#datepicker_doe_month").val(data.datepicker_doe_month);
				$("#datepicker_doe_year").val(data.datepicker_doe_year);


				$("#input_disc_price").val(data.disc_price);

				$("#input_sch_amount").val(data.sch_disc_amt);
				$("#input_sch_disc").val(data.sch_disc_per);

				$("#input_Qty").val(data.qty);
				$("#input_Qty_Free").val(data.qty_free);
				$("#input_package").val(data.package);
				$("#input_purchase_price").val(data.purchase_price);
				$("#input_drug").val(data.drug);
				$("#input_drug_hid").val(data.drug);

				$("#input_storage").val(data.cold_storage);
				$("#input_shelf_no").val(data.shelf_no);
				$("#input_rack_no").val(data.rack_no);

				$("#input_HSNCODE").val(data.HSNCODE);
				$("#input_CGST").val(data.CGST_per);
				$("#input_SGST").val(data.SGST_per);

				$("#invoice_item_id").val(data.item_id);

				calculate();

				load_form_div('Storestock/purchase_invoice_item_list_old/' + data.product_code, 'purchase_items_old_history');

				var elmnt = document.getElementById("btn_update_stock");

				//$("#input_drug").focus();
				elmnt.scrollIntoView();
			}
		}, 'json');
	}

	$(document).ready(function() {
		toggle_update_purchase_items(false);
		load_form_div('/Storestock/purchase_invoice_item_list/<?= $purchase_invoice[0]->id ?>', 'invoice_item_list');

		$('#btn_update_stock').click(function() {
			calculate();

			$('#btn_update_stock').attr('disabled', true);

			$.post('/Storestock/purchase_update_stock/' + $('#invoice_id').val(), $('form.form1').serialize(), function(data) {
				if (data.is_update_stock == 0) {
					notify('error', 'Please Attention', data.show_text);
					$('#btn_update_stock').attr('disabled', false);
				} else {
					notify('success', 'Please Attention', "Added Successfully");
					toggle_update_purchase_items(false);

					var elmnt = document.getElementById("input_drug");

					$("#input_drug").focus();
					elmnt.scrollIntoView();
					load_form_div('/Storestock/purchase_invoice_item_list/<?= $purchase_invoice[0]->id ?>', 'invoice_item_list');
					$('#btn_update_stock').attr('disabled', false);
				}
			}, 'json');
		});

		$('#btn_update_stock_return').click(function() {
			if (confirm('Are You sure ,Return this Item')) {
				$.post('/Storestock/purchase_update_stock/' + $('#invoice_id').val() + '/1', $('form.form1').serialize(), function(data) {
					if (data.is_update_stock == 0) {

						notify('error', 'Please Attention', data.show_text);
					} else {

						notify('success', 'Please Attention', 'Return Successfully');

						toggle_update_purchase_items(false);
						var elmnt = document.getElementById("input_drug");

						$("#input_drug").focus();
						elmnt.scrollIntoView();
						load_form_div('/Storestock/purchase_invoice_item_list/<?= $purchase_invoice[0]->id ?>', 'invoice_item_list');
					}
				}, 'json');
			}
		});
	});

	$(document).ready(function() {
		var cache = {};

		$("#input_drug").autocomplete({
			source: function(request, response) {
				$.getJSON("Storestock/get_drug_master", request, function(data, status, xhr) {
					response(data);
				});
			},
			minLength: 1,
			autofocus: true,
			select: function(event, ui) {

				toggle_update_purchase_items(true);

				$("#input_product_code").val(ui.item.l_item_code);
				$("#input_drug").val(ui.item.value);
				$("#input_drug_hid").val(ui.item.value);
				$("#input_product_mrp").val(ui.item.l_mrp);
				$("#input_selling_price").val(ui.item.l_mrp);

				$("#input_CGST").val(ui.item.l_CGST_per);
				$("#input_SGST").val(ui.item.l_SGST_per);
				$("#input_HSNCODE").val(ui.item.l_HSNCODE);

				$("#input_package").val(ui.item.l_package);
				$("#input_purchase_price").val(ui.item.l_purchase_price);
				$("#input_disc_price").val(ui.item.l_disc_price);

				$("#input_batch_code").val(ui.item.l_batch_no);

				$("#datepicker_doe_month").val(ui.item.datepicker_doe_month);
				$("#datepicker_doe_year").val(ui.item.datepicker_doe_year);

				$("#input_Qty_Free").val(0);

				load_form_div('Storestock/purchase_invoice_item_list_old/' + ui.item.l_item_code, 'purchase_items_old_history');

				var elmnt = document.getElementById("btn_update_stock");

				//$("#input_drug").focus();
				elmnt.scrollIntoView();

			}
		});
	});

	$(document).ready(function() {
		$("#input_formulation").autocomplete({
			source: "Medical/get_formulation_desc",
			minLength: 1,
			autofocus: true,
			select: function(event, ui) {}
		});
	});
</script>