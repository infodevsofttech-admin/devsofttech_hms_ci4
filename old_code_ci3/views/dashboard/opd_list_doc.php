<?php echo form_open('', array('role' => 'form', 'class' => 'form1')); ?>
<section class="content-header">
	<div class="row">
		<div class="col-md-4">
			<h3 style="margin-top: 0px;margin-bottom: 0px;">Dr. <?= $doc_master[0]->p_fname ?>
				<small><?= $doc_master[0]->Spec ?></small>
			</h3>
		</div>
		<div class="col-md-6">
			<?=$doc_master_panel?>
		</div>
		<div class="col-md-2">
			<a href="javascript:load_form('/Opd/get_appointment_list/<?=$doc_id?>/<?=$opd_date?>');"><i class="fa fa-dashboard"></i> Back to OPD</a>
		</div>
	</div>
</section>
<section class="content">
<div id="opd_panel">
	<!-- Main content -->
	<div class="row">
		<div class="col-md-12">
			<div class="nav-tabs-custom">
				<ul class="nav nav-tabs">
					<li><a href="#tab0" data-toggle="tab">On Booking</a></li>
					<li class="active"><a href="#tab1" data-toggle="tab">On Waiting</a></li>
					<li><a href="#tab2" data-toggle="tab">Visited</a></li>
					<li><a href="#tab3" data-toggle="tab">Canceled</a></li>
				</ul>
				<div class="tab-content">
					<div class=" tab-pane" id="tab0">
						<?= $tab0 ?>
					</div>
					<div class="tab-pane active" id="tab1">
						<?= $tab1 ?>
					</div>
					<div class="tab-pane" id="tab2">
						<?= $tab2 ?>
					</div>
					<div class="tab-pane" id="tab3">
						<?= $tab3 ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</section>
<div class="modal modal-wide fade" id="tallModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title" id="testentryLabel">OPD Scan</h4>
					</div>
					<div class="modal-body">
						<div class="row">
							<div class="testentry-bodyc col-md-12" id="testentry-bodyc">

							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
<?php echo form_close(); ?>
	<script>
		//var ResInterval_opd_list = setInterval('Panel_Refresh()', 10000); // 60 seconds

		var Panel_Refresh = function() {
			var doc_id = $('#doc_id').val();
			var opd_date = $("#datepicker_opd").data('datepicker').getFormattedDate('yyyy-mm-dd');

			var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

			var request = $.ajax({
				url: "/index.php/Opd/update_appointment_panel",
				method: "POST",
				data: {
					"doc_id": doc_id,
					"opd_date": opd_date,
					"<?= $this->security->get_csrf_token_name() ?>": csrf_value
				},
				dataType: "json"
			});

			request.done(function(data) {
				$('#opd_status').html(data.doc_master_panel);
				$('#tab0').html(data.tab0);
				$('#tab1').html(data.tab1);
				$('#tab2').html(data.tab2);
				$('#tab3').html(data.tab3);

				$('#msgshow').html("");
			});

			request.fail(function(jqXHR, textStatus) {
				$('#msgshow').html("Network failed: " + textStatus);
			});

			//setTimeout(Panel_Refresh,10000);
		};


		$('#tallModal').on('shown.bs.modal', function(event) {
			$('.testentry-bodyc').html('');
			var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();
			var height = $(window).height() - 50;
			$(this).find(".modal-body").css("max-height", height);

			var button = $(event.relatedTarget);
			// Button that triggered the modal
			var opdid = button.data('opdid');
			var etype = button.data('etype');

			if (etype == '1') {

				$.post('/index.php/Opd/opd_load_doc/' + opdid, {
					"opdid": opdid,
					'<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
				}, function(data) {
					$('#testentry-bodyc').html(data);
				});
			}
			if (etype == '2') {

				$.post('/index.php/Opd/opd_file_list/' + opdid, {
					"opdid": opdid,
					'<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
				}, function(data) {
					$('#testentry-bodyc').html(data);
				});
			}

			if (etype == '3') {
				var repoid = button.data('repoid');
				$.post('/index.php/Opd/opd_file_upload/' + opdid, {
					"opdid": opdid,
					'<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
				}, function(data) {
					$('#testentry-bodyc').html(data);
				});
			}
		});

		$('#tallModal').on('hidden.bs.modal', function() {
			$('.testentry-bodyc').html('');
			$('#testentryLabel').html('');
			Webcam.reset();
			refresh_panel();
		});


		function update_status(opdid, status_id) {
			if (confirm("Are you sure you want to Change Status")) {
				$.post('/index.php/Opd/opd_status/' + opdid + '/' + status_id, {
					"opdid": opdid,
					'<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
				}, function(data) {
					load_form('/Opd/get_appointment_list/<?= $doc_master[0]->doc_id ?>/<?=$opd_date?>');
				});
			} else {
				return false;
			}
		}


		function radio_current_queue_no_change(radio_id) {
			var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

			$.post('/index.php/Opd_prescription/update_opd_queue/' + radio_id, {
				"opd_session_id": radio_id,
				"<?= $this->security->get_csrf_token_name() ?>": csrf_value
			}, function(data) {
				notify('success', data);
			}, 'json');
		}


		function Opd_Prescription(opdid) {
			load_form_div('/Opd_prescription/Prescription/' + opdid,'opd_panel');
		}

		function Opd_create_queue(opdid) {
			var doc_id = $('#doc_id').val();
			var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

			$.post('/index.php/Opd_prescription/create_opd_queue/' + opdid, {
				"opdid": opdid,
				"<?= $this->security->get_csrf_token_name() ?>": csrf_value
			}, function(data) {
				alert(data);
			});
		}
	</script>