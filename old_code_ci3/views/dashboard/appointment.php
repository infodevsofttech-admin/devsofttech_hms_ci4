<section class="content-header">
	<div class="row">
		<div class="col-md-6">
			<div class="form-group">
				<label for="datepicker_opd" class="col-sm-5 control-label">OPD Appointment</label>
				<div class="col-sm-7">
					<div class="input-group date ">
						<div class="input-group-addon">
							<i class="fa fa-calendar"></i>
						</div>
						<input class="form-control pull-right datepicker" name="datepicker_opd" id="datepicker_opd" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask value="<?= MysqlDate_to_str($opd_date) ?>" />
						<span class="input-group-btn">
							<button id="show_date" class="form-control "><i class="fa fa-h-square"></i></button>
						</span>
					</div>
				</div>
			</div>
		</div>
		<div class="col-md-3">

		</div>
		<div class="col-md-3">
			<button type="button" id="btn_medical" class="btn btn-primary btn-xs" onclick="load_form('/Opd_prescription/rx_group_panel','Rx Group')">Rx-Group</button>
			<button type="button" id="btn_medical" class="btn btn-warning btn-xs" onclick="load_form('/Opd_prescription/opd_medicince','Medicince List')">OPD Medicine</button>
		</div>
	</div>
</section>
<section class="content">
	<div id="opd_panel">
		<?= $content ?>
	</div>
</section>
<script>
	$(document).ready(function() {
		var ToEndDate = new Date();

		$('#show_date').click(function() {
			var opd_date = $("#datepicker_opd").data('datepicker').getFormattedDate('yyyy-mm-dd');
			load_form_div('/Opd/get_appointment_data/' + opd_date, 'opd_panel');
		});

		$('#datepicker_opd').change(function() {
			var opd_date = $("#datepicker_opd").data('datepicker').getFormattedDate('yyyy-mm-dd');
			load_form_div('/Opd/get_appointment_data/' + opd_date, 'opd_panel');
		});

	});
</script>