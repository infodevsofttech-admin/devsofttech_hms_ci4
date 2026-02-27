
<div class="row">
		<?php
		$srno = 0;
		foreach ($doc_master as $row) {
			$srno .= 1;
		?>
			<div class="col-md-4">
				<!-- Widget: user widget style 1 -->
				<div class="box box-widget widget-user-2">
					<!-- Add the bg color to the header using any of the bg-* classes -->
					<div class="widget-user-header <?= $color[$row->color_code] ?>">
						<!-- /.widget-user-image -->
						<h3 class="widget-user-username">Dr. <?= $row->p_fname ?></h3>
						<h5 class="widget-user-desc"><?= $row->Spec ?></h5>
					</div>
					<div class="box-footer no-padding">
						<ul class="nav nav-stacked">
							<li><a href="#">On Waiting <span class="pull-right badge bg-blue"><?= $row->count_wait ?></span></a></li>
							<li><a href="#">No. of Visited <span class="pull-right badge bg-aqua"><?= $row->count_visit ?></span></a></li>
							<?php if ($row->count_cancel > 0) { ?>
								<li><a href="#">Canceled <span class="pull-right badge bg-green"><?= $row->count_cancel ?></span></a></li>
							<?php } ?>
							<li><a href="#">Total <span class="pull-right badge bg-red"><?= $row->No_opd ?></span></a></li>
						</ul>
						<a href="javascript:load_form('/Opd/get_appointment_list/<?= $row->doc_id ?>/<?= $opd_date ?>');" class="btn btn-primary btn-block"><b>Show List</b></a>
					</div>
				</div>
				<!-- /.widget-user -->
			</div>
		<?php
			if ($srno % 3 == 0) {
				echo '</div><div class="row">';
			}
		} ?>
	</div>