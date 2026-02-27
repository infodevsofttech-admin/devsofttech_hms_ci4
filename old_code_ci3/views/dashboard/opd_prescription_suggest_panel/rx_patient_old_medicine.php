<?php 
                foreach($opd_master as $row)
                {
					$sql="select p.*,o.opd_code,o.doc_name,
					if(o.apointment_date=curdate(),1,0) as opd_today 
					from opd_prescription p 
					join opd_master o on o.opd_id=p.opd_id 
					where p.opd_id=$row->opd_id";
					$query = $this->db->query($sql);
					$opd_data= $query->result();
				
                ?>
					<div class="post">
						<?php

						$opd_details_str="";
					
						$opd_details_str.= '<b> OPD Date :</b>'.MysqlDate_to_str($row->apointment_date).'<br/>';
		
						if(count($opd_data)>0)
						{

							$sql="SELECT pt.* ,d.dose_show_sign AS dose_shed,dw.dose_sign_desc AS dose_when,
							df.dose_sign_desc AS dose_frequency,d_on.dose_sign_desc AS dose_where
							FROM (((opd_prescrption_prescribed pt
							LEFT JOIN  opd_dose_shed d ON pt.dosage=d.dose_shed_id)
							LEFT JOIN opd_dose_when dw ON pt.dosage_when=dw.dose_when_id)
							LEFT JOIN opd_dose_frequency df ON pt.dosage_freq=df.dose_freq_id)
							LEFT JOIN opd_dose_where d_on ON pt.dosage_where=d_on.dose_where_id 
							where pt.opd_pre_id=".$opd_data[0]->id;
							$query = $this->db->query($sql);
							$opd_prescribed= $query->result();

							
							if($opd_data[0]->bp!='')
							{
								$opd_details_str.= '<b>BP :</b> '.$opd_data[0]->bp.' ';
							}
		
							if($opd_data[0]->diastolic!='')
							{
							$opd_details_str.= '<b>Diastolic :</b> '.$opd_data[0]->diastolic.' ';
							}
		
							if($opd_data[0]->pulse!='')
							{
							$opd_details_str.= '<b>Pulse :</b> '.$opd_data[0]->pulse.' ';
							}
		
							if($opd_data[0]->temp!='')
							{
							$opd_details_str.= '<b>Temp. :</b> '.$opd_data[0]->temp.' ';
							}

							$Complaint="";
		
							if($opd_data[0]->complaints!='')
							{
								$Complaint="<br/><b>Complaint : </b>";
								$Complaint.=''.$opd_data[0]->complaints.'';
							}

							$diagnosis="";
		
							if($opd_data[0]->diagnosis!='')
							{
								$diagnosis="<br/><b>Diagnosis : </b>";
								$diagnosis.=' '.$opd_data[0]->diagnosis.'';

							}
							
							$investigation="";
							if($opd_data[0]->investigation!='')
							{
								$investigation="<br/><b>Investigation Advised : </b>";
								$investigation.=' '.$opd_data[0]->investigation.'';
							}

							$opd_details_str.=$Complaint.$diagnosis.$investigation;

                            //New Start
							
							if(count($opd_prescribed)>0) 
							{ 
								$medical="";
								$sr_no=1;
								$medical.= '
								<table  style="padding: 5px;" class="table">
                                <caption><Strong>Rx : </Strong>  <a href="javascript:add_medicine_opd_all('.$opd_data[0]->id.')">Add All</a></caption>
									<tr>
										<th >Prescribed</th>
										<th >Dose</th>
									</tr>'; 
							
									foreach($opd_prescribed as $row_prescribed)
									{
										$medical_extend="";
									
										$medical.='<tr>
														<td><a href="javascript:add_medicine_opd('.$row_prescribed->id.')"><i class="fa fa-plus"></i> '.$sr_no.'-'.$row_prescribed->med_type.' '.$row_prescribed->med_name.'</a></td>
														<td>'.$row_prescribed->dose_shed.'</td>
													</tr>';
										if(strlen($row_prescribed->remark)>0)
										{
											$medical_extend='<tr>
													<td colspan="4">'.$row_prescribed->remark.'</td>
													</tr>';
										}
										$sr_no=$sr_no+1;
									}
									$medical.="</table>";
									$opd_details_str.=$medical;
							}
					
							$advice="";
		
							if($opd_data[0]->advice!='')
							{
								$advice="<br/><b>Advice : </b>";
								$advice.=''.$opd_data[0]->advice.'';
							}

							$opd_details_str.=$advice;

							$next_visit="";
		
							if($opd_data[0]->next_visit!='')
							{
								$next_visit="<br/><b>Next Visit : </b>";
								$next_visit.=''.$opd_data[0]->next_visit.'';
							}

							$opd_details_str.=$next_visit;

							$refer_to="";
		
							if($opd_data[0]->refer_to!='')
							{
								$refer_to="<br/><b>Refer To : </b>";
								$refer_to.=''.$opd_data[0]->refer_to.'';
							}

							$opd_details_str.=$refer_to;
							//New End
						}
						?>
					
                            <h4>OPD By :<a href="#">Dr. <?=$row->doc_name?></a></h4>
							<p><?=$opd_details_str?></p>
						</div>
					<!-- /.user-block -->
					<!-- /.row -->
					</div>
                <?php } ?>
<script>
    function add_medicine_opd(med_id){
        load_form_div('/Opd_prescription/add_medicince_opd/'+med_id+'/<?=$opd_session_id?>','medical_table');
    }

    function add_medicine_opd_all(opd_prescription_id){
        load_form_div('/Opd_prescription/add_medicine_opd_all/<?=$opd_session_id?>/'+opd_prescription_id,'medical_table');
    }
</script>