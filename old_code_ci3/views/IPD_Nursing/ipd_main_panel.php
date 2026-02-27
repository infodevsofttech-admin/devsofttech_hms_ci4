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
                    <?= $ipd_info[0]->contact_person_Name ?> <br />
					<b>Phone Number : </b><?= $ipd_info[0]->P_mobile1 ?> , <?= $ipd_info[0]->P_mobile2 ?>
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
                    Admit Date : <?= MysqlDate_to_str($ipd_info[0]->register_date) ?> Time : <?= $ipd_info[0]->reg_time ?>
				</p>
				<p>
			</p>
			<hr>
			<strong><i class="fa fa-pencil margin-r-5"></i> Associated Doctors</strong>
			<p>
				<?php
				$srno = 1;
				foreach ($ipd_doc_list as $row) {
					echo 'Dr. ' . $row->p_fname . '  ';
					echo '<br />';
					$srno = $srno + 1;
				}
				?>
			</p>
			
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
				
				<?php if (count($case_master) > 0) { ?>
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<input type="hidden" id="ins_id" value="<?= $case_master[0]->insurance_card_id ?>" />
								<input type="hidden" id="case_id" value="<?= $case_master[0]->id ?>" />
								<button type="button" class="btn btn-success" id="btn_inc_lab">Org. Add Charge</button>
							</div>
						</div>
					</div>
                    <div class="row">
						<div class="col-md-12">
							<hr />
						</div>
					</div>
					
				<?php } else { ?>
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<button type="button" class="btn btn-primary" id="btn_lab">Cash Add Charge</button>
							</div>
						</div>
					</div>
	
				<?php } ?>
			</div>
			<!-- /.box-body -->
		</div>
	</div>
</div>
<?php echo form_close(); ?>
<script>
	$(document).ready(function() {
		
		$('#btn_lab').click(function() {
			var p_id = $('#pid').val();
			load_form('/PathLab/addPathTest/' + p_id);
		});

		$('#btn_inc_lab').click(function() {

			var p_id = $('#pid').val();
			var ins_id = $('#ins_id').val();
			load_form('/PathLab/addPathTest/' + p_id + '/' + ins_id);
		});

	});

	

	
	function add_ipd_org(ipd_id, org_id) {
		var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();
		$.post('/index.php/IpdNew/add_ipd_org/' + ipd_id + '/' + org_id, {
			"<?= $this->security->get_csrf_token_name() ?>": csrf_value
		}, function(data) {
			load_form_div('/ipdNew/ipd_main_panel/' + ipd_id, 'tab_1');
		});
	}
</script>