<?php
if (count($lab_invoice_request) > 0) {
	$datetimepicker1 = $lab_invoice_request[0]->collected_time;
	$datetimepicker2 = $lab_invoice_request[0]->reported_time;
} else {
	$datetimepicker1 = "";
	$datetimepicker2 = "";
}
?>
<div class="box box-success">
	<div class="box-header with-border">
		<h3 class="box-title">Lab Collection and Report Time</h3>
	</div>
	<div class="box-body">
		<div class="row">
			<div class="col-md-5">
				<label for="event_start">Collect Time:</label>
				<div class="input-group date datetimepicker2" id="datetimepicker1">
					<input name="event_start" id="event_start" type="text"
						class="form-control "
						data-date-format="YYYY-MM-DD HH:mm"
						required
						value="<?= $datetimepicker1 ?>" />

					<span class="input-group-addon">
						<span class="glyphicon glyphicon-calendar"></span>
					</span>
				</div>
			</div>
			<div class="col-md-5">
				<label for="event_start">Report Time:</label>
				<div class="input-group date datetimepicker2" id="datetimepicker2">
					<input name="event_start" id="event_start" type="text"
						class="form-control "
						data-date-format="YYYY-MM-DD HH:mm"
						required
						value="<?= $datetimepicker2 ?>" />
					<span class="input-group-addon">
						<span class="glyphicon glyphicon-calendar"></span>
					</span>
				</div>
			</div>
			<div class="col-md-2">
				<button type="button" class="btn btn-primary" id="btn_opd"
					accesskey="C" onclick="update_report_time()"><u>U</u>pdate Time</button>
			</div>
			<script type="text/javascript">
				$(function() {
					$('.datetimepicker2').datetimepicker();
					$('.datetimepicker1').datetimepicker();
				});
			</script>
		</div>
	</div>
</div>
