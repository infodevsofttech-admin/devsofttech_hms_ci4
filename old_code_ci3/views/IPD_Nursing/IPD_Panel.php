<section class="content-header">
	<h1>
		Nursing IPD Panel : <a href="javascript:load_form('/IPD_Nursing/ipd_panel/<?= $ipd_info[0]->id ?>/');"><?= $ipd_info[0]->ipd_code ?> </a>
		<small><?= $ipd_info[0]->ins_company_name ?></small>
	</h1>
	<ol class="breadcrumb">
		<li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
		<li class="active"><a href="javascript:load_form('/IPD_Nursing/IpdList');">IPD List</a></li>
	</ol>
</section>
<!-- Main content -->
<section class="content">
	<div class="row">
		<div class="col-md-12">
			<p><strong>Name :</strong><?= $person_info[0]->p_fname ?> {<i><?= $person_info[0]->p_rname ?></i>}
				<strong>/ Age :</strong><?= $person_info[0]->age ?>
				<strong>/ Gender :</strong><?= $person_info[0]->xgender ?>
				<strong>/ UHID :</strong><a href="javascript:load_form('/Patient/person_record/<?= $person_info[0]->id ?>');"><?= $person_info[0]->p_code ?></a>
				<strong>/ No of Days :</strong><?= $ipd_info[0]->no_days ?>
			</p>
			<p><strong>Admit Date :</strong><?= $ipd_info[0]->str_register_date ?>
				<strong>/ Discharge Date :</strong><?= $ipd_info[0]->str_discharge_date ?>
			</p>
			<input type="hidden" id="pid" value="<?= $person_info[0]->id ?>" />
			<input type="hidden" id="pname" value="<?= $person_info[0]->p_fname ?>" />
			<input type="hidden" id="Ipd_ID" value="<?= $ipd_info[0]->id ?>" />

			<?php
			$pdata = 0;
			if (count($case_master) > 0) {
				$pdata = $case_master[0]->insurance_id;
			}
			?>
			<input type="hidden" id="ins_comp_id" name="ins_comp_id" value="<?= $pdata ?>" />
		</div>
	</div>

	<div class="row">
		<div class="col-md-12">
			<div id="tabs" class="nav-tabs-custom">
				<ul class="nav nav-tabs" id="prodTabs">
					<li><a aria-expanded="true" href="#tab_1" data-url="/IPD_Nursing/ipd_main_panel/<?= $ipd_info[0]->id ?>">Admission Info</a></li>
					<li><a aria-expanded="true" href="#tab_2" data-url="/IpdNew/ipd_bed_assign_list/<?= $ipd_info[0]->id ?>">Bed Assign</a></li>
					<li><a aria-expanded="true" href="#tab_3" data-url="/IPD_Nursing/ipd_treatment/<?= $ipd_info[0]->id ?>">TREATMENT CHART</a></li>
					<li><a aria-expanded="true" href="#tab_4" data-url="/IPD_Nursing/ipd_vitals_chart/<?= $ipd_info[0]->id ?>">VITALS CHART</a></li>
					<li><a aria-expanded="true" href="#tab_5" data-url="/IPD_Nursing/ipd_fluid_inout/<?= $ipd_info[0]->id ?>">Fluid in/Out</a></li>
				</ul>
				<div class="tab-content">
					<div id="tab_1" class="tab-pane active"></div>
					<div id="tab_2" class="tab-pane active"></div>
					<div id="tab_3" class="tab-pane active"></div>
					<div id="tab_4" class="tab-pane active"></div>
					<div id="tab_5" class="tab-pane active"></div>
				</div>
			</div>
		</div>
</section>
<!-- /.content -->
<!-- Modal -->
<div class="modal fade" id="payModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="payModalLabel">Payment</h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="payModal-bodyc" id="payModal-bodyc">

					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
	$(document).ready(function() {

		document.title = 'IPD:<?= $person_info[0]->p_fname ?>/<?= $ipd_info[0]->id ?>';
	});

	$('#tabs').on('click', '.tablink,#prodTabs a', function(e) {
		e.preventDefault();
		var url = $(this).attr("data-url");

		if (typeof url !== "undefined") {
			var pane = $(this),
				href = this.hash;

			// ajax load from data-url
			$(href).load(url, function(result) {
				pane.tab('show');
			});
		} else {
			$(this).tab('show');
		}
	});
	
</script>