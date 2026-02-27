<div class="nav-tabs-custom">
	<ul class="nav nav-tabs">
		<li class="active"><a href="#activity" data-toggle="tab">Records</a></li>
	</ul>
	<div class="tab-content">
		<div class="active tab-pane" id="activity">
			<!-- Post -->
			<?php
			foreach ($opd_master as $row) {
				$sql = "select p.*,o.opd_code,o.doc_name,
					if(o.apointment_date=curdate(),1,0) as opd_today 
					from opd_prescription p 
					join opd_master o on o.opd_id=p.opd_id 
					where p.opd_id=$row->opd_id";
				$query = $this->db->query($sql);
				$opd_data = $query->result();

				$sql = "select * from file_upload_data where show_type=0 and  opd_id=$row->opd_id order by id";
				$query = $this->db->query($sql);
				$opd_file_list = $query->result();

				$sql = "select * from file_opd_rec where  opd_id=$row->opd_id order by id";
				$query = $this->db->query($sql);
				$file_opd_rec = $query->result();
			?>
				<div class="post">
					<?php

					$opd_details_str = "";

					$opd_details_str .= '<b>OPD ID :</b>' . $row->opd_code;
					$opd_details_str .= '/<b> OPD Date :</b>' . MysqlDate_to_str($row->apointment_date);

					if (count($opd_data) > 0) {

						$sql = "SELECT * ,d.dose_show_sign AS dose_shed,dw.dose_sign_desc AS dose_when,
							df.dose_sign_desc AS dose_frequency,d_on.dose_sign_desc AS dose_where
							FROM (((opd_prescrption_prescribed pt
							LEFT JOIN  opd_dose_shed d ON pt.dosage=d.dose_shed_id)
							LEFT JOIN opd_dose_when dw ON pt.dosage_when=dw.dose_when_id)
							LEFT JOIN opd_dose_frequency df ON pt.dosage_freq=df.dose_freq_id)
							LEFT JOIN opd_dose_where d_on ON pt.dosage_where=d_on.dose_where_id 
							where pt.opd_pre_id=" . $opd_data[0]->id;
						$query = $this->db->query($sql);
						$opd_prescribed = $query->result();

						$opd_details_str .= ' / <b>Queue No.:</b>' . $opd_data[0]->queue_no . '<br/>';

						if ($opd_data[0]->bp != '') {
							$opd_details_str .= '<b>BP :</b> ' . $opd_data[0]->bp . ' ';
						}

						if ($opd_data[0]->diastolic != '') {
							$opd_details_str .= '<b>Diastolic :</b> ' . $opd_data[0]->diastolic . ' ';
						}

						if ($opd_data[0]->pulse != '') {
							$opd_details_str .= '<b>Pulse :</b> ' . $opd_data[0]->pulse . ' ';
						}

						if ($opd_data[0]->temp != '') {
							$opd_details_str .= '<b>Temp. :</b> ' . $opd_data[0]->temp . ' ';
						}

						$Complaint = "";

						if ($opd_data[0]->complaints != '') {
							$Complaint = "<br/><b>Complaint : </b>";
							$Complaint .= '' . $opd_data[0]->complaints . '';
						}

						$diagnosis = "";

						if ($opd_data[0]->diagnosis != '') {
							$diagnosis = "<br/><b>Diagnosis : </b>";
							$diagnosis .= ' ' . $opd_data[0]->diagnosis . '';
						}

						$investigation = "";
						if ($opd_data[0]->investigation != '') {
							$investigation = "<br/><b>Investigation Advised : </b>";
							$investigation .= ' ' . $opd_data[0]->investigation . '';
						}

						$opd_details_str .= $Complaint . $diagnosis . $investigation;


						//New Start

						if (count($opd_prescribed) > 0) {
							$medical = "<H4>Rx :</H4>";
							$sr_no = 1;
							$medical .= '
								<table  style="padding: 5px;" class="table"  >
									<tr>
										<th >Prescribed</th>
										<th >Dose</th>
										<th >Timing - Freq. - Duration</th>
										<th >Qty</th>
									</tr>';

							foreach ($opd_prescribed as $row_prescribed) {
								$medical_extend = "";

								$medical .= '<tr>
														<td>' . $sr_no . ' - ' . $row_prescribed->med_type . ' ' . $row_prescribed->med_name . '</td>
														<td>' . $row_prescribed->dose_shed . '</td>
														<td>' . $row_prescribed->dose_when . ' - ' . $row_prescribed->dose_frequency . ' - ' . $row_prescribed->dose_where . ' - ' . $row_prescribed->no_of_days . ' </td>
														<td>' . $row_prescribed->qty . '</td>
													</tr>';
								if (strlen($row_prescribed->remark) > 0) {
									$medical_extend = '<tr>
													<td colspan="4">' . $row_prescribed->remark . '</td>
													</tr>';
								}
								$sr_no = $sr_no + 1;
							}
							$medical .= "</table>";
							$opd_details_str .= $medical;
						}

						$advice = "";

						if ($opd_data[0]->advice != '') {
							$advice = "<br/><b>Advice : </b>";
							$advice .= '' . $opd_data[0]->advice . '';
						}

						$opd_details_str .= $advice;

						$next_visit = "";

						if ($opd_data[0]->next_visit != '') {
							$next_visit = "<br/><b>Next Visit : </b>";
							$next_visit .= '' . $opd_data[0]->next_visit . '';
						}

						$opd_details_str .= $next_visit;

						$refer_to = "";

						if ($opd_data[0]->refer_to != '') {
							$refer_to = "<br/><b>Refer To : </b>";
							$refer_to .= '' . $opd_data[0]->refer_to . '';
						}

						$opd_details_str .= $refer_to;

						//New End
					}
					?>

					<div class="user-block">
						<img class="img-circle img-bordered-sm" src="/assets/images/Doctor_img_icon.png" alt="User Image">
						<span class="username">
							<a href="#">Dr. <?= $row->doc_name ?></a>
						</span>
						<span class="description" style="color:black;"><?= $opd_details_str ?></span>
					</div>
					<!-- /.user-block -->
					<div class="row margin-bottom">
						<?php
						$i = 0;
						//Files Show
						foreach ($opd_file_list as $opd_file_row) {
							$i = $i + 1;
							echo '<div class="col-sm-12">';
							$pos = strpos($opd_file_row->full_path, '/uploads/', 1);
							$file_path = substr($opd_file_row->full_path, $pos);

							//$file_path=str_replace('hms_uploads','uploads',$opd_file_row->full_path);

							if (strtoupper($opd_file_row->file_ext) == '.PDF') {
								echo '<embed  src="' . $file_path . '" width="800px" height="1000px" type="application/pdf"  ></embed>';
							} else {
								echo '<img data-src="'.$file_path.'" class="lazyload" />';
							}

							echo '</div>';
						}

						?>
					</div>
					<!-- /.row -->
				</div>

			<?php } ?>
			<!-- /.post -->
		</div>
		<!-- /.tab-pane -->

		<!-- /.tab-pane -->
	</div>
	<!-- /.tab-content -->
</div>
