<section class="content-header">
	<h1>
		<?= ucwords($data[0]->p_fname); ?>
		<small><a href="#"><?= $data[0]->p_code; ?></a></small>
	</h1>
</section>
<!-- Main content -->
<?php echo form_open('', array('role' => 'form', 'class' => 'form1')); ?>
<section class="content">
	<div class="jsError"></div>
	<div class="row">
		<div class="col-md-8">
			<div class="row">
			<div class="col-md-12">
				<div class="box box-primary">
					<div class="box-header with-border">
						<div class="col-md-8">
							<h3 class="box-title">Person Profile</h3>
						</div>
						<div class="col-md-4 ">

						</div>
					</div>
					<div class="box-body">
						<input type="hidden" value="<?= $data[0]->id ?>" id="p_id" name="p_id" />
						<div class="row">
							<div class="col-md-6">
								<p class="text-primary">Full Name : <span class="text-success"><?= ucwords($data[0]->p_fname) ?></span></p>
							</div>
							<div class="col-md-6">
								<p class="text-primary">Gender : <span class="text-success"><?= $data[0]->xgender ?></span>
									<span class="text-primary"> / Age : </span> <span class="text-success"><?= $data[0]->str_age ?> </span>
								</p>
							</div>
						</div>
						<div class="row">
							<div class="col-md-9">
								<p class="text-primary">Relation : <span class="text-success"><?= $data[0]->p_relative ?></span>
									<span class="text-primary">Relative Name :</span> <span class="text-success"><?= ucwords($data[0]->p_rname) ?></span>
								</p>
							</div>
							<div class="col-md-3">
								<p class="text-primary">Aadhar No. : <span class="text-success"><?= $data[0]->udai ?></span></p>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6">
								<p class="text-primary">Phone Number : <span class="text-success"><?= $data[0]->mphone1 ?></span></p>
							</div>
							<div class="col-md-6">
								<p class="text-primary">Email : <span class="text-success"><?= $data[0]->email1 ?></span></p>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<p class="text-primary">Address : <span class="text-success"><?= $data[0]->add1 ?>
										<?= $data[0]->city ?>
										<?= $data[0]->district ?>
										<?= $data[0]->state ?>
										<?= $data[0]->zip ?>
									</span></p>
							</div>
						</div>
					</div>
				</div>
			</div>
			</div>
			<div class="row">
				<input type="hidden" value="<?= $inv_id ?>" id="inv_id" name="inv_id" />
				<div class="col-md-12">
					<div class="box box-success">
						<div class="box-header with-border">
							<h3 class="box-title">Test List : [Invoice Code : <a href="javascript:load_form_div('/Lab_Report/select_lab_invoice/<?= $inv_id ?>/<?= $lab_type ?>','searchresult');"><?= $inv_code ?></a>]</h3>
						</div>
						<div class="box-body" id="test_list">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="col-md-4">

		</div>
	</div>
	<!-- ./row -->
</section>
<!-- /.content -->
<div class="modal modal-wide fade" id="tallModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" style="width:100%;">
	<div class="modal-dialog modal-lg" role="document" style="width:90%;">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="testentryLabel">Test Name</h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="testentry-bodyc" id="testentry-bodyc">

					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="modal modal-wide fade" id="tallModal_4" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" style="width:100%;">
	<div class="modal-dialog modal-lg" role="document" style="width:90%;">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="tallModal_4Label"></h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="tallModal_4-bodyc" id="tallModal_4-bodyc">
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php echo form_close(); ?>
<script>
	$(document).ready(function() {
		var lab_type = $('#lab_type').val();
		var inv_id = $('#inv_id').val();
		load_form_div('/Lab_Report/xray_test_list/' + inv_id + '/' + lab_type, 'test_list');
		//load_form_div('/Lab_Report/report_file_list/'+inv_id+'/'+lab_type,'file_show');
	});

	function refresh_data() {
		var lab_type = $('#lab_type').val();
		var inv_id = $('#inv_id').val();

		load_form_div('/Lab_Report/xray_test_list/' + inv_id + '/' + lab_type, 'test_list');
		//load_form_div('/Lab_Report/report_file_list/'+inv_id+'/'+lab_type,'file_show');
	}

	function update_request(test_id) {
		var lab_type = $('#lab_type').val();
		var inv_id = $('#inv_id').val();
		var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

		$.post('/index.php/Lab_Report/lab_tab_1_process/' + test_id + '/' + lab_type, {
			"test_id": test_id,
			'<?= $this->security->get_csrf_token_name() ?>': csrf_value
		}, function(data) {
			load_form_div('/Lab_Report/xray_test_list/' + inv_id + '/' + lab_type, 'test_list');
		});
	}

	$('#tallModal').on('shown.bs.modal', function(event) {
		$('.testentry-bodyc').html('');

		var lab_type = $('#lab_type').val();
		var inv_id = $('#inv_id').val();
		var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

		var height = $(window).height() - 50;
		$(this).find(".modal-body").css("max-height", height);

		var button = $(event.relatedTarget);
		// Button that triggered the modal
		var testid = button.data('testid');
		var testname = button.data('testname');
		var etype = button.data('etype');

		$('#testentryLabel').html(testname);

		if (etype == '1') {
			$.post('/index.php/Lab_Report/create_report_xray/' + testid, {
				"test_id": testid,
				'<?= $this->security->get_csrf_token_name() ?>': csrf_value
			}, function(data) {
				$('#testentry-bodyc').html(data);
			});
		}

		if (etype == '6') {
			var repoid = button.data('repoid');

			var Get_Query = '/index.php/Lab_Admin/report_compile_xray_single/' + inv_id + '/' + lab_type + '/' + repoid + '/1';
			load_report_div(Get_Query, 'testentry-bodyc');
		}

		if (etype == '7') {
			var repoid = button.data('repoid');

			$.post('/index.php/Lab_Report/show_report_final/' + repoid + '', {
				"test_id": repoid,
				'<?= $this->security->get_csrf_token_name() ?>': csrf_value
			}, function(data) {
				$('#testentry-bodyc').html(data);
			});
		}

		if (etype == '8') {
			var repoid = button.data('repoid');

			$.post('/index.php/Lab_Admin/report_edit_request_show/' + repoid + '', {
				"test_id": repoid,
				'<?= $this->security->get_csrf_token_name() ?>': csrf_value
			}, function(data) {
				$('#testentry-bodyc').html(data);
			});
		}

	});

	$('#tallModal').on('hidden.bs.modal', function() {
		var lab_type = $('#lab_type').val();
		var inv_id = $('#inv_id').val();

		$('.testentry-bodyc').html('');
		$('#testentryLabel').html('');

		refresh_data();

		Webcam.reset();

	});

	//For Complie and Print

	function report_show(inv_id) {
		load_report_div(Get_Query, 'show_report_pdf');
	}

	function report_compile(inv_id, lab_type) {
		var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

		$.post('/index.php/Lab_Admin/report_compile/' + inv_id + '/' + lab_type, {
			"inv_id": inv_id,
			'<?= $this->security->get_csrf_token_name() ?>': csrf_value
		}, function(data) {
			notify('success', 'Please Attention', data);
		});
	}

	function report_item_remove(item_id) {
		var lab_type = $('#lab_type').val();
		var inv_id = $('#inv_id').val();
		var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

		if (confirm('Are you sure, Delete This Test')) {
			$.post('/index.php/Lab_Admin/report_remove/' + inv_id + '/' + lab_type + '/' + item_id, {
				"inv_id": inv_id,
				'<?= $this->security->get_csrf_token_name() ?>': csrf_value
			}, function(data) {
				alert(data);
				load_form_div('/Lab_Report/test_list/' + inv_id + '/' + lab_type, 'test_list');
			});
		}
	}

	function onChangeUpdate(cb, item_id) {
		var check_value = 0;
		var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

		$("#CHK_" + item_id).prop("disabled", true);

		if (cb.checked) {
			check_value = 1;
		}

		$.post('/index.php/Lab_Admin/Update_CombineReport', {
			"item_id": item_id,
			"checked": check_value,
			'<?= $this->security->get_csrf_token_name() ?>': csrf_value
		}, function(data) {
			notify('success', 'Please Attention', 'Value Update');
			$("#CHK_" + item_id).prop("disabled", false);

		});
		sleep(2000);
		$("#CHK_" + item_id).prop("disabled", false);

	}

	function update_report_time() {
		var lab_type = $('#lab_type').val();
		var inv_id = $('#inv_id').val();
		var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

		//var datetimepicker1=$('#datetimepicker1').data('DateTimePicker').date();
		//var datetimepicker2=$('#datetimepicker2').data('DateTimePicker').date();

		var datetimepicker1 = $('#datetimepicker1').find("input").val();
		var datetimepicker2 = $('#datetimepicker2').find("input").val();


		$.post('/index.php/Lab_Admin/Update_Report_time', {
			"inv_id": inv_id,
			"lab_type": lab_type,
			"datetimepicker1": datetimepicker1,
			"datetimepicker2": datetimepicker2,
			'<?= $this->security->get_csrf_token_name() ?>': csrf_value
		}, function(data) {
			alert("Value Update");
		});

	}

	function update_lab_no(inv_id, lab_type) {
		var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();
		var lab_req_id = $('#lab_req_id').val();
		var inputLabNo = $('#inputLabNo').val();

		$.post('/index.php/Lab_Admin/update_lab_no', {
			"lab_req_id": lab_req_id,
			"inputLabNo": inputLabNo,
			'<?= $this->security->get_csrf_token_name() ?>': csrf_value
		}, function(data) {
			notify('Atten', 'Please Attention', data);
		});
	}

	$('#tallModal_4').on('shown.bs.modal', function(event) {
		$('.tallModal_4-bodyc').html('');

		var button = $(event.relatedTarget);
		// Button that triggered the modal
		var testid = button.data('testid');
		var testname = button.data('testname');
		var etype = button.data('etype');
		var labtype = button.data('labtype');
		var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

		if (etype == '1') {
			$.post('/index.php/Lab_Admin/show_print_final_edit/' + testid + '/' + labtype + '/0', {
				"test_id": testid,
				'<?= $this->security->get_csrf_token_name() ?>': csrf_value
			}, function(data) {
				$('#tallModal_4Label').html(testname);
				$('#tallModal_4-bodyc').html(data);
			});
		} else if (etype == '0') {
			var Get_Query = '/index.php/Lab_Admin/show_print_final_edit/' + testid + '/' + labtype + '/1';
			$('#tallModal_4Label').html(testname);
			load_report_div(Get_Query, 'tallModal_4-bodyc');
		} else if (etype == '2') {
			var Get_Query = '/index.php/Lab_Admin/print_pdf_create/' + testid + '/' + labtype + '/1';
			$('#tallModal_4Label').html(testname);
			//window.open(Get_Query, "_blank");
			load_report_div(Get_Query, 'tallModal_4-bodyc');
		} else if (etype == '9') {
			var Get_Query = '/index.php/Lab_Admin/print_pdf_create/' + testid + '/' + labtype + '/1/1';
			$('#tallModal_4Label').html(testname);
			//window.open(Get_Query, "_blank");
			load_report_div(Get_Query, 'tallModal_4-bodyc');
		} else if (etype == '3') {
			var inv_id = button.data('inv_id');
			$.post('/index.php/Lab_Admin/lab_file_upload_complete/' + inv_id + '/' + labtype, {
				"inv_id": inv_id,
				'<?= $this->security->get_csrf_token_name() ?>': csrf_value
			}, function(data) {
				$('#tallModal_4Label').html(testname);
				$('#tallModal_4-bodyc').html(data);
			});
		} else if (etype == '4') {
			var inv_id = button.data('inv_id');
			$.post('/index.php/Lab_Admin/lab_file_scan_complete/' + inv_id + '/' + labtype, {
				"inv_id": inv_id,
				'<?= $this->security->get_csrf_token_name() ?>': csrf_value
			}, function(data) {
				$('#tallModal_4Label').html(testname);
				$('#tallModal_4-bodyc').html(data);
			});
		} else if (etype == '5') {
			var inv_id = button.data('inv_id');
			$.post('/index.php/Lab_Admin/lab_file_list_all/' + inv_id + '/' + labtype, {
				"inv_id": inv_id,
				'<?= $this->security->get_csrf_token_name() ?>': csrf_value
			}, function(data) {
				$('#tallModal_4Label').html(testname);
				$('#tallModal_4-bodyc').html(data);
			});
		}
	});

	$('#tallModal_4').on('hidden.bs.modal', function() {
		var lab_type = $('#lab_type').val();
		var inv_id = $('#inv_id').val();

		$('#tallModal_4-bodyc').html('');
		$('#tallModal_4Label').html('');

		refresh_data();
		Webcam.reset();
	});
</script>