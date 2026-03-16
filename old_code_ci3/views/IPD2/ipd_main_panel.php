<?php echo form_open('', array('role' => 'form', 'class' => 'form1')); ?>
<div class="row">
	<div class="col-md-6">
		<div class="box box-primary">
			<div class="box-header with-border">
				<h3 class="box-title">General Information</h3>
			</div>
			<!-- /.box-header -->
			<div class="box-body">
				<strong><i class="fa fa-book margin-r-5"></i> Name</strong>

				<p class="text-muted">
					<?= $person_info[0]->p_fname ?>
					<br />
					<b>Phone Number : </b><?= $person_info[0]->mphone1 ?>
				</p>
				<hr>

				<strong><i class="fa fa-book margin-r-5"></i>Relative Name</strong>

				<p class="text-muted">
					<input type="text" value="<?= $ipd_info[0]->contact_person_Name ?>" id="contact_person_Name" name="contact_person_Name" /> <br />
					<b>Phone Number : </b>
					<input type="text" value="<?= $ipd_info[0]->P_mobile1 ?>" id="P_mobile1" name="P_mobile1" /> ,
					<input type="text" value="<?= $ipd_info[0]->P_mobile2 ?>" id="P_mobile2" name="P_mobile2" /><br />
					<div class="form-group">
						<div class="radio">
							<label>
							<input name="optionsRadios_mlc" id="options_mlc1" value="0"  type="radio" <?= radio_checked(0,$ipd_info[0]->case_type) ?> >
							NON MLC
							</label>
							<label>
								<input name="optionsRadios_mlc" id="options_mlc2" value="1"  type="radio" <?= radio_checked(1,$ipd_info[0]->case_type) ?>>
								MLC
							</label>
						</div>
					</div>
					<button type="button" class="btn btn-warning" id="btn_update_relative_update">Save</button>
				</p>

				<hr>
				<strong><i class="fa fa-map-marker margin-r-5"></i> Address</strong>

				<p class="text-muted">
					<?= $person_info[0]->add1 ?>,</br>
					<?= $person_info[0]->add2 ?>,</br>
					<?= $person_info[0]->city ?>,</br>
					<?= $person_info[0]->state ?>,</br>
				</p>

				<hr>

				<strong><i class="fa fa-book margin-r-5"></i> Admit Date</strong>
				<p class="text-muted">

				</p>
				<p>
				<?php if ($this->ion_auth->in_group('IPDAdmit')) {   ?>
				<div class="row">
					<div class="col-md-4">
						<div class="form-group">
							<label>Date</label>
							<div class="input-group date">
								<div class="input-group-addon">
									<i class="fa fa-calendar"></i>
								</div>
								<input id="datepicker_res_date" name="datepicker_res_date" class="form-control pull-right datepicker" id="datepicker_dob" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask="" value="<?= MysqlDate_to_str($ipd_info[0]->register_date) ?>" />
							</div>
						</div>
					</div>
					<div class="col-md-4">
						<div class="bootstrap-timepicker">
							<div class="form-group">
								<label>Time: (24 Hour Format)</label>
								<div class="input-group">
									<input id="res_time" name="res_time" class="form-control" type="text" value="<?= $ipd_info[0]->reg_time ?>">
									<div class="input-group-addon">
										<i class="fa fa-clock-o"></i>
									</div>
								</div>
								<!-- /.input group -->
							</div>
						</div>
					</div>
					<div class="col-md-4">
						<div class="form-group">
							<button type="button" class="btn btn-warning" id="btn_update_date">Update Date</button>
						</div>
					</div>
				</div>

				<script>
					$("#datepicker_res_date").inputmask("dd/mm/yyyy", {
						"placeholder": "dd/mm/yyyy"
					});
					//Timepicker
					$('#res_time').datetimepicker({
						format: 'HH:mm'
					});
					$('.datepicker').datepicker({
						format: "dd/mm/yyyy",
						autoclose: true
					});

					$(document).ready(function() {
						$('#btn_update_date').click(function() {
							var ipd_id = $('#Ipd_ID').val();
							var strdate = $('#datepicker_res_date').val();
							var strtime = $('#res_time').val();
							var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

							$.post('/index.php/IpdNew/update_ipdadmit_time', {
								"Ipd_ID": ipd_id,
								"strdate": strdate,
								"strtime": strtime,
								"<?= $this->security->get_csrf_token_name() ?>": csrf_value
							}, function(data) {
								alert('Admit Date & Time Changed');
								//load_form_div('/ipdNew/ipd_account_panel/'+ipd_id,'account_status');
							});

						});

						$('#btn_update_relative_update').click(function() {
							var ipd_id = $('#Ipd_ID').val();
							var contact_person_Name = $('#contact_person_Name').val();
							var P_mobile1 = $('#P_mobile1').val();
							var P_mobile2 = $('#P_mobile2').val();
							var case_type = $('input[name = "optionsRadios_mlc"]:checked').val();

							var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

							$.post('/index.php/IpdNew/update_relative_update', {
								"Ipd_ID": ipd_id,
								"contact_person_Name": contact_person_Name,
								"P_mobile1": P_mobile1,
								"P_mobile2": P_mobile2,
								"case_type": case_type,
								"<?= $this->security->get_csrf_token_name() ?>": csrf_value
							}, function(data) {
								alert('Update Changed');
								//load_form_div('/ipdNew/ipd_account_panel/'+ipd_id,'account_status');
							});

						});

						

					});
				</script>

			<?php } else {  ?>
				Admit Date : <?= MysqlDate_to_str($ipd_info[0]->register_date) ?> Time : <?= $ipd_info[0]->reg_time ?>
			<?php } ?>
			</p>
			<?php if ($this->ion_auth->in_group('IPDDocUpdate')) {   ?>
			<hr>
			<strong><i class="fa fa-pencil margin-r-5"></i> Department</strong>
			
				<div class="row">
					<div class="col-md-9">
						<select class="form-control" id="dept_id" name="dept_id">
							<?php
							foreach ($hc_department as $row) {
								echo '<option value=' . $row->iId . '  '.combo_checked($row->iId,$ipd_info[0]->dept_id).' >' . $row->vName . '</option>';
							}
							?>
						</select>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<button type="button" class="btn btn-warning" id="btn_update_department">Update Department</button>
						</div>
					</div>
				</div>
				
			<?php } ?>
			<script>
				$('#btn_update_department').click(function() {
							var ipd_id = $('#Ipd_ID').val();
							var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();
							var dept_id = $('#dept_id').val();
						
							$.post('/index.php/IpdNew/update_department/'+ipd_id, {
								"Ipd_ID": ipd_id,
								"dept_id": dept_id,
								"<?= $this->security->get_csrf_token_name() ?>": csrf_value
							}, function(data) {
								alert('Update Changed');
								//load_form_div('/ipdNew/ipd_account_panel/'+ipd_id,'account_status');
							});

						});
			</script>
			<hr>
			<strong><i class="fa fa-pencil margin-r-5"></i> Associated Doctors</strong>
			<p>
				<?php
				$srno = 1;
				foreach ($ipd_doc_list as $row) {
					echo 'Dr. ' . $row->p_fname . '  ';
					if ($this->ion_auth->in_group('IPDDocUpdate')) {
						echo ' <a href="javascript:remove_doc(\'' . $row->id . '\',\'' . $ipd_info[0]->id . '\');">Remove</a>';
					}
					echo '<br />';
					$srno = $srno + 1;
				}
				?>
			</p>
			<?php if ($this->ion_auth->in_group('IPDDocUpdate')) {   ?>
				<div class="row">
					<div class="col-md-3">
						<select class="form-control" id="doc_name_id" name="doc_name_id">
							<?php
							foreach ($doclist as $row) {
								echo '<option value=' . $row->id . '  >' . $row->p_fname . '</option>';
							}
							?>
						</select>
					</div>
					<div class="col-md-3">
						<a href="javascript:add_doc('<?= $ipd_info[0]->id ?>');">Add Doctor</a>
					</div>
				</div>
			<?php } ?>
			<hr>
			<strong><i class="fa fa-pencil margin-r-5"></i> Referred List</strong>
			<p>
				<?php
				$srno = 1;
				foreach ($refer_list as $row) {
					echo $row->refer_name . '  ';
					if ($this->ion_auth->in_group('IPDReferUpdate')) {
						echo ' <a href="javascript:remove_refer(\'' . $row->id . '\',\'' . $ipd_info[0]->id . '\');">Remove</a>';
					}
					echo '<br />';
					$srno = $srno + 1;
				}
				?>
			</p>
			<?php if ($this->ion_auth->in_group('IPDReferUpdate')) {   ?>
				<div class="row">
					<div class="col-md-9">
						<select class="form-control" id="refer_id" name="refer_id">
							<?php
							foreach ($refer_master as $row) {
								echo '<option value=' . $row->id . '  >' . $row->refer_name . '</option>';
							}
							?>
						</select>
					</div>
					<div class="col-md-3">
						<a href="javascript:add_refer('<?= $ipd_info[0]->id ?>');">Add Refer</a>
					</div>
				</div>
				
			<?php } else {
			} ?>
			<hr>
			<strong><i class="fa fa-file-text-o margin-r-5"></i> Notes</strong>
			<p>
				<?= $ipd_info[0]->remark ?>
			</p>
			</div>
			<!-- /.box-body -->
		</div>
	</div>
	<div class="col-md-6">
		<div class="box box-primary">
			<div class="box-header with-border">
				<h3 class="box-title">Update Information</h3>
			</div>
			<!-- /.box-header -->
			<div class="box-body">
				<div class="row">
					<div class="col-md-3">
						<div class="form-group">
							<a href="<?php echo '/IpdNew/show_ipd_form/' . $ipd_info[0]->id . '/1';  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print Face Form</a>
						</div>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<a href="<?php echo '/IpdNew/show_ipd_form/' . $ipd_info[0]->id . '/5';  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Admission Form</a>
						</div>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<a href="<?php echo '/IpdNew/show_ipd_form/' . $ipd_info[0]->id . '/10';  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i>Sticker [2 x 6]</a>
						</div>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<a href="<?php echo '/IpdNew/show_ipd_form/' . $ipd_info[0]->id . '/11';  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Sticker [2 x 8]</a>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-3">
						<div class="form-group">
							<a href="<?php echo '/IpdNew/show_ipd_form/' . $ipd_info[0]->id . '/6';  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Treatment Chart</a>
						</div>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<a href="<?php echo '/IpdNew/show_ipd_form/' . $ipd_info[0]->id . '/7';  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Vitals Chart </a>
						</div>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<a href="<?php echo '/IpdNew/show_ipd_form/' . $ipd_info[0]->id . '/8';  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Progress Notes</a>
						</div>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<a href="<?php echo '/IpdNew/show_ipd_form/' . $ipd_info[0]->id . '/9';  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Fluid In / Out</a>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-3">
						<div class="form-group">
							<a href="<?php echo '/IpdNew/show_ipd_form/' . $ipd_info[0]->id . '/2';  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print Face Form (COVID)</a>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-3">
						<div class="form-group">
							<a href="<?php echo '/IpdNew/show_ipd_form/' . $ipd_info[0]->id . '/4';  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print Clearance Check List (COVID)</a>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-6">
						<div class="form-group">
							<a href="<?php echo '/IpdNew/show_ipd_form/' . $ipd_info[0]->id . '/3';  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print SELF DECLARATOION FROM HEALTH INSURANCE CARD HOLDER</a>
						</div>
					</div>
				</div>
				<?php if (count($case_master) > 0) { ?>
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<input type="hidden" id="ins_id" value="<?= $case_master[0]->insurance_card_id ?>" />
								<input type="hidden" id="case_id" value="<?= $case_master[0]->id ?>" />
								<button type="button" class="btn btn-success" id="btn_inc_lab">Org. Add Charge</button>
								<button type="button" class="btn btn-warning " id="btn_case_ipd">Update Case Information</button>
								<button type="button" class="btn btn-warning " onclick="load_form_div('/Orgcase/contingent_bill/<?= $case_master[0]->id ?>','tab_1');">CONTINGENT BILL</button>
								<button type="button" class="btn btn-danger " onclick="load_form_div('/Orgcase/case_invoice_ipd/<?= $case_master[0]->id ?>','tab_1');">Edit Org Invoice</button>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label><input type="checkbox" name="doc_recd" id="doc_recd" class="flat-red" value='<?= $case_master[0]->doc_recd ?>' <?= radio_checked("1", $case_master[0]->doc_recd) ?> onchange="onChangeUpdate('<?= $case_master[0]->id ?>','doc_recd',this)"> Document Received</label>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label><input type="checkbox" name="preauth_send" id="preauth_send" class="flat-red" value='<?= $case_master[0]->preauth_send ?>' <?= radio_checked("1", $case_master[0]->preauth_send) ?> onchange="onChangeUpdate('<?= $case_master[0]->id ?>','preauth_send',this)"> Pre-Auth Send</label>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<hr />
						</div>
					</div>
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label>Org. Status</label>
								<select id="org_status" class="form-control">
									<?php
									foreach ($org_approved_status as $row) {
										echo '<option value="' . $row->id . '" ' . combo_checked($row->id, $case_master[0]->org_approved_status_id) . ' >' . $row->app_status . '</option>';
									}
									?>
								</select>
							</div>
						</div>
						<div class="col-md-3">
							<div class="form-group">
								<label>Approved Amount</label>
								<input class="form-control number" name="input_app_Amount" id="input_app_Amount" placeholder="Approved Amount" value="<?= $case_master[0]->org_approved_amount ?>" type="text" />
							</div>
						</div>
						<div class="col-md-3">
							<div class="form-group">
								<button type="button" class="btn btn-success" id="btn_orgstatus_update">Update Status</button>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<hr />
						</div>
					</div>
					<div class="row">
						<div class="col-md-4">
							<div class="form-group">
								<label><input type="checkbox" name="final_bill_send" id="final_bill_send" class="flat-red" value='<?= $case_master[0]->final_bill_send ?>' <?= radio_checked("1", $case_master[0]->final_bill_send) ?> onchange="onChangeUpdate('<?= $case_master[0]->id ?>','final_bill_send',this)"> Final Bill Send</label>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label>Final Send Bill Amount </label>
								<div class="input-group input-group-sm">
									<input type="text" class="form-control" name="input_final_bill_amount" id="input_final_bill_amount" placeholder="Send Bill Amount" value="<?= $case_master[0]->final_bill_amount ?>">
									<span class="input-group-btn">
										<button type="button" class="btn btn-info btn-flat" onclick="onUpdateORG('<?= $case_master[0]->id ?>','final_bill_amount',document.getElementById('input_final_bill_amount').value)">Update</button>
									</span>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<hr />
						</div>
					</div>
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label>Submit Insurance Company Name </label>
								<div class="input-group input-group-sm">
									<input type="text" class="form-control" name="input_company_Name" id="input_company_Name" placeholder="Company Name" value="<?= $case_master[0]->Org_insurance_comp ?>">
									<span class="input-group-btn">
										<button type="button" class="btn btn-info btn-flat" onclick="onUpdateORG('<?= $case_master[0]->id ?>','Org_insurance_comp',document.getElementById('input_company_Name').value)">Update</button>
									</span>
								</div>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label>Final Bill Approved Amount </label>
								<div class="input-group input-group-sm">
									<input type="text" class="form-control" name="input_final_approve_amount" id="input_final_approve_amount" placeholder="Final Approved Amount" value="<?= $case_master[0]->final_approve_amount ?>">
									<span class="input-group-btn">
										<button type="button" class="btn btn-info btn-flat" onclick="onUpdateORG('<?= $case_master[0]->id ?>','final_approve_amount',document.getElementById('input_final_approve_amount').value)">Update</button>
									</span>
								</div>
							</div>
						</div>
					</div>
					<script>
						function onChangeUpdate(org, fvalue, cb) {
							var check_value = 0;
							if (cb.checked) {
								check_value = 1;
							}
							var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

							$.post('/index.php/IpdNew/update_org_doc', {
								"org_id": org,
								"feild": fvalue,
								"fvalue": check_value,
								"<?= $this->security->get_csrf_token_name() ?>": csrf_value
							}, function(data) {
								alert("Value Update");
							});
						}

						function onUpdateORG(org, fvalue, input_value) {
							var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();
							$.post('/index.php/IpdNew/update_org_doc', {
								"org_id": org,
								"feild": fvalue,
								"fvalue": input_value,
								"<?= $this->security->get_csrf_token_name() ?>": csrf_value
							}, function(data) {
								alert("Value Update");
							});
						}
					</script>
				<?php } else { ?>
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<button type="button" class="btn btn-primary" id="btn_lab">Cash Add Charge</button>
							</div>
						</div>
					</div>

					<?php foreach ($case_master_open as $row) {
					?>
						<button type="button" class="btn btn-success" id="btn_orgstatus_add" onclick="add_ipd_org(<?= $ipd_info[0]->id ?>,<?= $row->id ?>)">
							Attach This IPD With Org. <?= $row->case_id_code ?>
						</button>
					<?php } ?>
				<?php } ?>
			</div>
			<!-- /.box-body -->
		</div>
	</div>
</div>
<?php echo form_close(); ?>
<script>
	$(document).ready(function() {
		$('#btn_orgstatus_update').click(function() {
			var amount = $('#input_app_Amount').val();
			var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

			$.post('/index.php/IpdNew/ipd_org_status_update', {
					"case_id": $('#case_id').val(),
					"amount": amount,
					"ipd_id": $('#pid').val(),
					"org_status": $('#org_status').val(),
					"<?= $this->security->get_csrf_token_name() ?>": csrf_value
				},
				function(data) {
					if (data.update == 0) {
						alert(data.error_text);
					} else {
						alert(data.msg);
					}
				}, 'json');

		});

		$('#btn_lab').click(function() {
			var p_id = $('#pid').val();
			load_form('/PathLab/addPathTest/' + p_id);
		});

		$('#btn_inc_lab').click(function() {

			var p_id = $('#pid').val();
			var ins_id = $('#ins_id').val();
			load_form('/PathLab/addPathTest/' + p_id + '/' + ins_id);
		});

		$('#btn_case_ipd').click(function() {
			var org_id = <?= $ipd_info[0]->case_id ?>;

			load_form('/Ocasemaster/open_case/' + org_id + '/1');
		});

	});

	function remove_doc(ipd_doc_id, ipd_id) {
		var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

		$.post('/index.php/IpdNew/add_remove_doc/' + ipd_doc_id, {
			"<?= $this->security->get_csrf_token_name() ?>": csrf_value
		}, function(data) {
			load_form_div('/ipdNew/ipd_main_panel/' + ipd_id, 'tab_1');
		});
	}

	function add_doc(ipd_id) {
		var doc_id = $('#doc_name_id').val();
		var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

		$.post('/index.php/ipdNew/add_add_doc/' + ipd_id + '/' + doc_id, {
			"<?= $this->security->get_csrf_token_name() ?>": csrf_value
		}, function(data) {
			load_form_div('/ipdNew/ipd_main_panel/' + ipd_id, 'tab_1');
		});
	}

	function remove_refer(r_id,ipd_id) {
		var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

		$.post('/index.php/ipdNew/remove_refer/' + r_id, {
			"<?= $this->security->get_csrf_token_name() ?>": csrf_value
		}, function(data) {
			load_form_div('/ipdNew/ipd_main_panel/' + ipd_id, 'tab_1');
		});
	}

	function add_refer(ipd_id) {
		var refer_by = $('#refer_id').val();
		var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

		$.post('/index.php/ipdNew/add_Refer/' + ipd_id + '/' + refer_by, {
			"<?= $this->security->get_csrf_token_name() ?>": csrf_value
		}, function(data) {
			load_form_div('/ipdNew/ipd_main_panel/' + ipd_id, 'tab_1');
		});
	}

	function add_ipd_org(ipd_id, org_id) {
		var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();
		$.post('/index.php/IpdNew/add_ipd_org/' + ipd_id + '/' + org_id, {
			"<?= $this->security->get_csrf_token_name() ?>": csrf_value
		}, function(data) {
			load_form_div('/ipdNew/ipd_main_panel/' + ipd_id, 'tab_1');
		});
	}
</script>