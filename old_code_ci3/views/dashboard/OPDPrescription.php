<section class="content-header">
    <br />
    <table style="width:100%">
        <tr>
            <td>
                <p class="text-primary"> Name : <span class="text-danger" style="font: size 25px;"> <?= ucwords($data[0]->p_fname); ?></span><br /> <?= ucwords($data[0]->p_relative) . ' ' . ucwords($data[0]->p_rname); ?>
                    <br /> UHID ID : <span class="text-success"><?= $data[0]->p_code ?></span>
                    <br /> Gender : <span class="text-success"><?= $data[0]->xgender ?></span>
                    / Age : <span class="text-success"><?= $data[0]->str_age ?> </span>
                    <br /> OPD ID : <span class="text-success"><?= $opd_master[0]->opd_code ?> </span>
                </p>
            </td>
            <td>
                <?php
                $pos = strpos($data[0]->profile_picture, '/uploads/', 1);
                $profile_file_path = substr($data[0]->profile_picture, $pos);
                ?>
                <a href="javascript:load_form_div('/Opd_prescription/show_profile_image/<?= $data[0]->id ?>/<?= $opd_id ?>/<?= $doc_id ?>','opd_panel');" class="btn btn-success btn-xs">
                    <img src="<?= $profile_file_path ?>" width="200px" />
                </a>
            </td>
            <td>
                <div id="vital_status" name="vital_status"></div>
            </td>
            <td>
                <div id="patient_remark" name="patient_remark"></div>
            </td>
            <td>
                <!---Start Here --->
                <video id="myVideo" class="video-js vjs-default-skin"></video>
                <script>
                    var segmentNumber = 0;
                    var data;
                    var opd_id = $('#opd_id').val();
                    var u_file_name = $('#opd_id').val() + '<?= date('YmdHis') ?>';
                    var u_file_folder = $('#opd_id').val() + '<?= date('YmdHis') ?>';

                    var player = videojs("myVideo", {
                        controls: true,
                        width: 150,
                        height: 100,
                        fluid: false,
                        controlBar: {
                            volumePanel: false
                        },
                        plugins: {
                            record: {
                                audio: true,
                                video: true,
                                maxLength: 600,
                                debug: true,
                                timeSlice: 2000
                            }
                        }
                    }, function() {

                        // print version information at startup
                        var msg = 'Using video.js ' + videojs.VERSION +
                            ' with videojs-record ' + videojs.getPluginVersion('record') +
                            ' and recordrtc ' + RecordRTC.version;
                        videojs.log(msg);
                    });

                    // error handling
                    player.on('deviceError', function() {
                        console.warn('device error:', player.deviceErrorCode);
                    });

                    player.on('error', function(error) {
                        console.log('error:', error);
                    });

                    // user clicked the record button and started recording
                    player.on('startRecord', function() {
                        console.log('started recording!');
                    });

                    var video_count = 0;

                    player.on('timestamp', function() {
                        // timestamps
                        // console.log('current timestamp: ', player.currentTimestamp);
                        // console.log('all timestamps: ', player.allTimestamps);

                        // stream data
                        data = player.recordedData;
                        console.log('array of s: ', player.recordedData);

                        if (player.recordedData && player.recordedData.length > 0) {
                            var binaryData = player.recordedData[player.recordedData.length - 1];

                            segmentNumber++;
                            var formData = new FormData();
                            //formData.append('name', binaryData['name']);

                            //Custom Add 
                            video_count = video_count + 1;

                            formData.append('name', 'Main_video_' + video_count);
                            formData.append('file_number', video_count);
                            formData.append('stream', binaryData);
                            formData.append('opd_id', opd_id);
                            formData.append('file_name_id', u_file_name);

                            var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

                            formData.append('<?= $this->security->get_csrf_token_name() ?>', csrf_value);

                            xhr('/Opd_prescription/upload_video', formData, function(fName) {
                                console.log("Video succesfully uploaded !", fName);
                            });

                            // Helper function to send 
                            function xhr(url, data, callback) {
                                var request = new XMLHttpRequest();
                                request.onreadystatechange = function() {
                                    if (request.readyState == 4 && request.status == 200) {
                                        callback(location.href + request.responseText);
                                    }
                                };
                                request.open('POST', url);
                                request.send(data);
                            }
                        }
                    });
                    // user completed recording and stream is available
                </script>
                <!--- End Here --->
            </td>
            <td style="text-align:right;vertical-align: bottom;">
                <a href="<?php echo '/opd_print/opd_PDF_print/' . $opd_id;  ?>" target="_blank" class="btn btn-default btn-xs"><i class="fa fa-print"></i> Print OPD HEAD in Letter Head</a>
                <a href="<?php echo '/opd_print/opd_blank_print/' . $opd_id;  ?>" target="_blank" class="btn btn-default btn-xs"><i class="fa fa-print"></i> Print OPD HEAD in Blank Page</a>
                <br /><br />
                <button type="button" class="btn btn-danger btn-xs" id="btn_Print0">Print Rx in Plain Paper</button>
                <button type="button" class="btn btn-success btn-xs" id="btn_Print1">Print Rx Blank LetterHead</button>
                <button type="button" class="btn btn-warning btn-xs" id="btn_Print2">Print Rx With OPD Head LetterHead</button>
            </td>

        </tr>
    </table>
</section>
<?php echo form_open('', array('role' => 'form', 'class' => 'form1')); ?>
<!-- Main content -->
<section class="content">
    <div class="jsError"></div>
    <input type="hidden" value="<?= $p_id ?>" id="p_id" name="p_id" />
    <input type="hidden" value="<?= $opd_id ?>" id="opd_id" name="opd_id" />
    <input type="hidden" value="<?= $opd_session_id ?>" id="opd_session_id" name="opd_session_id" />
    <div class="row">
        <div class="col-md-12">
            <ul class="nav nav-pills">
                <li class="active" style="font-size:14px;"><a data-toggle="pill" href="#" data-target="#home">Examination</a></li>
                <li style="font-size:14px;"><a data-toggle="pill" href="#" data-target="#menu1">Prescription</a></li>
                <li style="font-size:14px;"><a data-toggle="pill" href="/Opd_prescription/prescribed_dose/<?= $opd_id ?>/<?= $opd_session_id ?>" data-target="#menu2">Brands Prescribed / Dosage </a></li>
                <li style="font-size:14px;"><a data-toggle="pill" href="/Opd_prescription/show_profile_opd/<?= $p_id ?>/<?= $opd_session_id ?>" data-target='#menu3'>OLD Prescription</a></li>
                <li style="font-size:14px;"><a data-toggle="pill" href="#" data-target='#menu5'>Investigation</a></li>
                <li style="font-size:14px;"><a data-toggle="pill" href="/Opd_prescription/show_medical_item/<?= $p_id ?>" data-target='#menu6'>Medicine Purchase</a></li>
                <li style="font-size:14px;"><a data-toggle="pill" href="/Opd_prescription/patient_remark/<?= $p_id ?>" data-target='#menu7'>Remark</a></li>
                <li class="pull-right"><button type="submit" class="btn btn-primary" id="btn_save">Save</button></li>
            </ul>
            <div class="tab-content">
                <div id="home" class="tab-pane fade in active">
                    <div class="row">

                        <div class="box box-primary">
                            <div class="box-header with-border">
                                <div class="col-md-8">
                                    <h3 class="box-title">General Examination</h3>
                                </div>
                            </div>
                            <div class="box-body">
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="tags">Pulse[bpm]: </label>
                                            <input class="form-control number input-sm" name="input_Pulse" id="input_Pulse" onchange="onchange_vital()" type="text" value="<?= $opd_prescription[0]->pulse ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <div class="ui-widget">
                                                <label for="tags">SPO2(%): </label>
                                                <input class="form-control number input-sm" name="input_SPO2" id="input_SPO2" onchange="onchange_vital()" type="text" value="<?= $opd_prescription[0]->spo2 ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <div class="ui-widget">
                                                <label for="tags">BP [Systolic]: </label>
                                                <input class="form-control number input-sm" name="input_BP" id="input_BP" onchange="onchange_vital()" type="text" value="<?= $opd_prescription[0]->bp ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <div class="ui-widget">
                                                <label for="tags">[Diastolic]: </label>
                                                <input class="form-control number input-sm" name="input_Diastolic" onchange="onchange_vital()" id="input_Diastolic" type="text" value="<?= $opd_prescription[0]->diastolic ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <div class="ui-widget">
                                                <label for="tags">Tempature[F]: </label>
                                                <input class="form-control number input-sm" name="input_Tempature" onchange="onchange_vital()" id="input_Tempature" type="text" value="<?= $opd_prescription[0]->temp ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <div class="ui-widget">
                                                <label for="tags">RR/min: </label>
                                                <input class="form-control number input-sm" name="input_RR" id="input_RR" onchange="onchange_vital()" type="text" value="<?= $opd_prescription[0]->rr_min ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <div class="ui-widget">
                                                <label for="tags">Height[Cm]: </label>
                                                <input class="form-control input-sm" name="input_Height" onchange="onchange_vital()" id="input_Height" type="text" value="<?= $opd_prescription[0]->height ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <div class="ui-widget">
                                                <label for="tags">Weight[Kg]: </label>
                                                <input class="form-control number input-sm" name="input_Weight" onchange="onchange_vital()" id="input_Weight" type="text" value="<?= $opd_prescription[0]->weight ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <div class="ui-widget">
                                                <label for="tags">Waist[cm]: </label>
                                                <input class="form-control input-sm" name="input_Waist" id="input_Waist" onchange="onchange_vital()" type="text" value="<?= $opd_prescription[0]->waist ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <div class="ui-widget">
                                                <label for="tags">Pallor: </label>
                                                <input class="form-control input-sm" name="input_pallor" onchange="onchange_vital()" id="input_pallor" type="text" value="<?= $opd_prescription[0]->pallor ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <div class="ui-widget">
                                                <label for="tags">Icterus: </label>
                                                <input class="form-control input-sm" name="input_Icterus" onchange="onchange_vital()" id="input_Icterus" type="text" value="<?= $opd_prescription[0]->Icterus ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <div class="ui-widget">
                                                <label for="tags">Cyanosis: </label>
                                                <input class="form-control input-sm" name="input_cyanosis" id="input_cyanosis" onchange="onchange_vital()" type="text" value="<?= $opd_prescription[0]->cyanosis ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <div class="ui-widget">
                                                <label for="tags">Clubbing: </label>
                                                <input class="form-control input-sm" name="input_clubbing" id="input_clubbing" onchange="onchange_vital()" type="text" value="<?= $opd_prescription[0]->clubbing ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <div class="ui-widget">
                                                <label for="tags">Edema: </label>
                                                <input class="form-control input-sm" name="input_edema" id="input_edema" onchange="onchange_vital()" type="text" value="<?= $opd_prescription[0]->edema ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="box box-primary">
                                <div class="box-header with-border">
                                    <div class="col-md-8">
                                        <h3 class="box-title">Pain Measurement Scale</h3>
                                    </div>
                                </div>
                                <div class="box-body">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <img src="/assets/images/pain_score.jpg" style="width: 100%;" />
                                        </div>  
                                    </div>
                                    <div class="row">
                                        <div class="col-md-1">
                                        </div>
                                        <div class="col-md-2">
                                            <input type="radio" class="btn-check" name="options-pain" id="pain1" autocomplete="off" value="0" <?=radio_checked("0",$opd_prescription[0]->pain_value)?> >
                                            <label class="btn btn-success" for="pain1">No Pain</label>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="radio" class="btn-check" name="options-pain" id="pain2" autocomplete="off" value="1" <?=radio_checked("1",$opd_prescription[0]->pain_value)?> >
                                            <label class="btn btn-primary" for="pain2">Mild Pain</label>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="radio" class="btn-check" name="options-pain" id="pain3" autocomplete="off" value="2" <?=radio_checked("2",$opd_prescription[0]->pain_value)?> >
                                            <label class="btn btn-info" for="pain3">Moderate</label>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="radio" class="btn-check" name="options-pain" id="pain4" autocomplete="off" value="3" <?=radio_checked("3",$opd_prescription[0]->pain_value)?> >
                                            <label class="btn btn-warning" for="pain4">Intence</label>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="radio" class="btn-check" name="options-pain" id="pain5" autocomplete="off" value="4" <?=radio_checked("4",$opd_prescription[0]->pain_value)?> >
                                            <label class="btn btn-danger" for="pain5">Worst Pain Possible</label>
                                        </div> 
                                    
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="callout callout-warning">
                                <h5>Complication</h5>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" class='chk_opd' id="opd_prescription_pregnancy_chk" value="<?= $opd_prescription[0]->pregnancy ?>" <?php echo ($opd_prescription[0]->pregnancy == 1) ? 'Checked' : ''; ?>>
                                        Pregnancy
                                    </label>
                                </div>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" class='chk_opd' id="opd_prescription_lactation_chk" value="<?= $opd_prescription[0]->lactation ?>" <?php echo ($opd_prescription[0]->lactation == 1) ? 'Checked' : ''; ?>>
                                        Lactation
                                    </label>
                                </div>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" class='chk_opd' id="opd_prescription_liver_insufficiency_chk" value="<?= $opd_prescription[0]->liver_insufficiency ?>" <?php echo ($opd_prescription[0]->liver_insufficiency == 1) ? 'Checked' : ''; ?>>
                                        Liver Insufficiency
                                    </label>
                                </div>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" class='chk_opd' id="opd_prescription_renal_insufficiency_chk" value="<?= $opd_prescription[0]->renal_insufficiency ?>" <?php echo ($opd_prescription[0]->renal_insufficiency == 1) ? 'Checked' : ''; ?>>
                                        Renal Insufficiency
                                    </label>
                                </div>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" class='chk_opd' id="opd_prescription_pulmonary_insufficiency_chk" value="<?= $opd_prescription[0]->pulmonary_insufficiency ?>" <?php echo ($opd_prescription[0]->pulmonary_insufficiency == 1) ? 'Checked' : ''; ?>>
                                        Pulmonary Insufficiency
                                    </label>
                                </div>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" class='chk_opd' id="opd_prescription_corona_suspected_chk" value="<?= $opd_prescription[0]->corona_suspected ?>" <?php echo ($opd_prescription[0]->corona_suspected == 1) ? 'Checked' : ''; ?>>
                                        Corona Suspected
                                    </label>
                                </div>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" class='chk_opd' id="opd_prescription_dengue_chk" value="<?= $opd_prescription[0]->dengue ?>" <?php echo ($opd_prescription[0]->dengue == 1) ? 'Checked' : ''; ?>>
                                        Dengue
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="callout callout-info">
                                <h5>Addiction(if any)</h5>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" class='chk_opd' id="opd_prescription_smoking_chk" value="<?= $Pdata[0]->is_smoking ?>" <?php echo ($Pdata[0]->is_smoking == 1) ? 'Checked' : ''; ?>>
                                        Smoking
                                    </label>
                                </div>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" class='chk_opd' id="opd_prescription_alcohol_chk" value="<?= $Pdata[0]->is_alcohol ?>" <?php echo ($Pdata[0]->is_alcohol == 1) ? 'Checked' : ''; ?>>
                                        Alcohol
                                    </label>
                                </div>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" class='chk_opd' id="opd_prescription_drug_chk" value="<?= $Pdata[0]->is_drug_abuse ?>" <?php echo ($Pdata[0]->is_drug_abuse == 1) ? 'Checked' : ''; ?>>
                                        Drug abuse
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="callout callout-success">
                                <h5>Co-Morbidities</h5>
                                <div class="form-group">
                                <?php foreach($morbidities as $row_morbidities){ ?>
                                        <label>
                                            <input type="checkbox" class='chk_opd' name="morbidities" value="<?= $row_morbidities->mor_id ?>" <?php echo ($row_morbidities->p_id == '') ? '' : 'Checked'; ?> >
                                            <?= $row_morbidities->morbidities ?>
                                        </label>
                                <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="menu1" class="tab-pane fade ">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="box box-primary">
                                <div class="box-header with-border">
                                    <h3 class="box-title">Prescription</h3>
                                </div>
                                <div class="box-body">
                                    
                                    <div class="row ">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="ui-widget">
                                                    <label for="tags">Patient's Complaints/ Reported Problems: </label>
                                                    <textarea class="form-control varchar input-sm" name="input_complaints" onchange="onchange_vital()" id="input_complaints"><?= $opd_prescription[0]->complaints ?></textarea>
                                                </div>
                                            </div>
                                            <?php if (count($old_complaint_list) > 0) { ?>
                                                <p>Last Complaint</p>
                                                <div class="btn-group">
                                                    <?php
                                                    $i=0;
                                                    foreach ($old_complaint_list as $row) {
                                                        $i+=1;
                                                    ?>
                                                        <button type="button" class="btn btn-default" onclick="add_Complaints_hid(<?=$i?>)"><?=$row?></button>
                                                        <input type="hidden" id="h_complaint_<?=$i ?>" name="h_complaint_<?=$i?>" value="<?=$row?>">
                                                    <?php
                                                    }
                                                    ?>
                                                </div>
                                            <?php } ?>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="btn-group">
                                                <?php
                                                foreach ($short_complaint as $row) {
                                                ?>
                                                    <button type="button" class="btn btn-default" onclick="add_Complaints('<?= $row->Name ?>')"><?= $row->Name ?></button>
                                                <?php
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    <hr />
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="ui-widget">
                                                    <label for="tags">Provisional Diagnosis : </label>
                                                    <textarea class="form-control varchar input-sm" name="input_Provisional_diagnosis" onchange="onchange_vital()" id="input_Provisional_diagnosis"><?= $opd_prescription[0]->Provisional_diagnosis ?></textarea>
                                                </div>
                                            </div>
                                            <?php if (count($old_Provisional_diagnosis_list) > 0) { ?>
                                                <p>Last Provisional Diagnosis</p>
                                                <div class="btn-group">
                                                    <?php
                                                    foreach ($old_Provisional_diagnosis_list as $row) {
                                                    ?>
                                                        <button type="button" class="btn btn-default" onclick="add_Provisional_Diagnosis('<?= $row ?>')"><?= $row ?></button>
                                                    <?php
                                                    }
                                                    ?>
                                                </div>
                                            <?php } ?>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="ui-widget">
                                                    <label for="tags">Diagnosis: </label>
                                                    <textarea class="form-control varchar input-sm" name="input_diagnosis" onchange="onchange_vital()" id="input_diagnosis"><?= $opd_prescription[0]->diagnosis ?></textarea>
                                                </div>
                                            </div>
                                            <?php if (count($old_diagnosis_list) > 0) { ?>
                                                <p>Last Diagnosis</p>
                                                <div class="btn-group">
                                                    <?php
                                                    foreach ($old_diagnosis_list as $row) {
                                                    ?>
                                                        <button type="button" class="btn btn-default" onclick="add_Diagnosis('<?= $row ?>')"><?= $row ?></button>
                                                    <?php
                                                    }
                                                    ?>
                                                </div>
                                            <?php } ?>
                                        </div>

                                    </div>

                                    <hr />
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="ui-widget">
                                                    <label for="tags">Finding / Examinations: </label>
                                                    <textarea class="form-control varchar input-sm" name="input_Examinations" onchange="onchange_vital()" id="input_Examinations" placeholder=""><?= $opd_prescription[0]->Finding_Examinations ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="ui-widget">
                                                    <label for="tags">Prescriber's Remarks: </label>
                                                    <textarea class="form-control varchar input-sm" name="input_Prescriber" onchange="onchange_vital()" id="input_Prescriber" placeholder=""><?= $opd_prescription[0]->Prescriber_Remarks ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <hr />
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="ui-widget">
                                                    <label for="tags">Advise Investigation </label>
                                                    <textarea class="form-control varchar input-sm" name="input_Investigation" onchange="onchange_vital()" id="input_Investigation"><?= $opd_prescription[0]->investigation ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <?php
                                            $short_name = "";
                                            $btn_color = array('btn-default', 'btn-primary', 'btn-success', 'btn-info', 'btn-danger', 'btn-warning');
                                            $counter_btn = 1;
                                            $btn_style = '';
                                            foreach ($short_investigation as $row) {
                                                if ($row->short_name <> $short_name) {
                                                    echo '<br />';
                                                    $btn_style = $btn_color[fmod($counter_btn, count($btn_color))];
                                                    $counter_btn += 1;
                                                }
                                            ?>
                                                <button type="button" class="btn <?= $btn_style ?> btn-xs" style="margin: 2px;" onclick="add_investigation_test('<?= $row->Name ?>')"><?= $row->Name ?></button>
                                            <?php
                                                $short_name = $row->short_name;
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="menu2" class="tab-pane fade">

                </div>
                <div id="menu3" class="tab-pane fade">

                </div>
                <div id="menu4" class="tab-pane fade">
                </div>
                <div id="menu5" class="tab-pane fade">
                    <div class="nav-tabs-custom">
                        <ul class="nav nav-tabs">
                            <li class="active"><a href="/Opd_prescription/show_profile_investigation/<?= $p_id ?>/<?= $opd_session_id ?>" data-toggle="gpill" data-target='#activity'>Hospital
                                    Investigation</a></li>
                            <li><a href="/Opd_prescription/patient_investigation_data/<?= $p_id ?>" data-toggle="gpill" data-target='#investigation'>Investigation Report</a></li>
                            <li><a href="/Opd_prescription/patient_glucose_data/<?= $p_id ?>" data-toggle="gpill" data-target='#glucosechart'>Glucose Chart</a></li>
                        </ul>
                        <div class="tab-content">
                            <div class="active tab-pane" id="activity">

                            </div>
                            <!-- /.tab-pane -->
                            <div class="tab-pane" id="investigation">

                            </div>
                            <div class="tab-pane" id="glucosechart">

                            </div>
                            <!-- /.tab-pane -->
                        </div>
                        <!-- /.tab-content -->
                    </div>
                </div>
                <div id="menu6" class="tab-pane fade">
                </div>
                <div id="menu7" class="tab-pane fade">
                </div>
            </div>
        </div>
    </div>
    <!-- ./row -->
</section>
<!-- /.content -->
<?php echo form_close(); ?>
<div class="modal modal-wide fade" id="tallModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg" role="document" style="width: 100%;height: 100%;margin: 0;padding: 0;">
        <div class="modal-content" style="height: 100%; border-radius: 0;">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="testentryLabel">Test Name</h4>
            </div>
            <div class="modal-body" style="overflow : scroll;">
                <div class="row">
                    <div class="testentry-bodyc" id="testentry-bodyc">

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    function add_investigation_test(test_name) {
        var old_text = $('#input_Investigation').val();
        $('#input_Investigation').val(old_text + ',' + test_name);
        onchange_vital();
    }

    function add_Provisional_Diagnosis(test_name) {
        var old_text = $('#input_Provisional_diagnosis').val();
        $('#input_Provisional_diagnosis').val(old_text + ',' + test_name);
        onchange_vital();
    }

    function add_Diagnosis(test_name) {
        var old_text = $('#input_diagnosis').val();
        $('#input_diagnosis').val(old_text + ',' + test_name);
        onchange_vital();
    }

    function update_vital_status() {
        var opd_session_id = $('#opd_session_id').val();
        var p_id = $('#p_id').val();

        load_form_div("/Opd_prescription/vital_data/" + opd_session_id, "vital_status");
        load_form_div("/Opd_prescription/get_remark/" + p_id, "patient_remark");

    }


    $(document).ready(function() {

        update_vital_status();
        

        $('form.form1').on('submit', function(form) {
            form.preventDefault();

            var favorite = [];

            $.each($("input[name='morbidities']:checked"), function() {
                favorite.push($(this).val());
            });

            var morbidities_list = favorite.join("-");

            var opd_session_id = $('#opd_session_id').val();
            var opd_id = $('#opd_id').val();
            var input_Examinations = $('#input_Examinations').val();
            var input_Prescriber = $('#input_Prescriber').val();

            var input_BP = $('#input_BP').val();
            var input_Diastolic = $('#input_Diastolic').val();
            var input_Height = $('#input_Height').val();
            var input_Weight = $('#input_Weight').val();
            var input_Tempature = $('#input_Tempature').val();
            var input_Pulse = $('#input_Pulse').val();
            var input_Waist = $('#input_Waist').val();

            var input_RR = $('#input_RR').val();

            var input_glucose = $('#input_glucose').val();
            var input_SPO2 = $('#input_SPO2').val();

            var input_complaints = $('#input_complaints').val();
            var input_diagnosis = $('#input_diagnosis').val();
            var input_Provisional_diagnosis = $('#input_Provisional_diagnosis').val();

            var input_pallor = $('#input_pallor').val();
            var input_Icterus = $('#input_Icterus').val();
            var input_cyanosis = $('#input_cyanosis').val();
            var input_clubbing = $('#input_clubbing').val();
            var input_edema = $('#input_edema').val();


            var input_Investigation = $('#input_Investigation').val();

            var opd_prescription_paediatric_chk = $('#opd_prescription_paediatric_chk').is(":checked") ? 1 :
                0;
            var opd_prescription_pregnancy_chk = $('#opd_prescription_pregnancy_chk').is(":checked") ? 1 :
                0;
            var opd_prescription_lactation_chk = $('#opd_prescription_lactation_chk').is(":checked") ? 1 :
                0;
            var opd_prescription_liver_insufficiency_chk = $('#opd_prescription_liver_insufficiency_chk')
                .is(":checked") ? 1 : 0;
            var opd_prescription_renal_insufficiency_chk = $('#opd_prescription_renal_insufficiency_chk')
                .is(":checked") ? 1 : 0;
            var opd_prescription_pulmonary_insufficiency_chk = $(
                '#opd_prescription_pulmonary_insufficiency_chk').is(":checked") ? 1 : 0;
            var opd_prescription_corona_suspected_chk = $('#opd_prescription_corona_suspected_chk').is(
                ":checked") ? 1 : 0;
            var opd_prescription_dengue_chk = $('#opd_prescription_dengue_chk').is(":checked") ? 1 : 0;

            var opd_prescription_smoking_chk = $('#opd_prescription_smoking_chk').is(":checked") ? 1 : 0;
            var opd_prescription_alcohol_chk = $('#opd_prescription_alcohol_chk').is(":checked") ? 1 : 0;
            var opd_prescription_drug_chk = $('#opd_prescription_drug_chk').is(":checked") ? 1 : 0;

            var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

            var pain_value=$("input[name='options-pain']:checked").val();

            $.post('/Opd_prescription/opd_prescription_save', {
                    "opd_session_id": opd_session_id,
                    "input_Examinations": input_Examinations,
                    "input_Prescriber": input_Prescriber,
                    "input_BP": input_BP,
                    "input_Diastolic": input_Diastolic,
                    "input_Height": input_Height,
                    "input_Weight": input_Weight,
                    "input_Tempature": input_Tempature,
                    "input_Pulse": input_Pulse,
                    "input_Waist": input_Waist,
                    "input_glucose": input_glucose,
                    "input_RR": input_RR,
                    "input_SPO2": input_SPO2,
                    "opd_id": opd_id,
                    "opd_prescription_paediatric_chk": opd_prescription_paediatric_chk,
                    "opd_prescription_pregnancy_chk": opd_prescription_pregnancy_chk,
                    "opd_prescription_lactation_chk": opd_prescription_lactation_chk,
                    "opd_prescription_liver_insufficiency_chk": opd_prescription_liver_insufficiency_chk,
                    "opd_prescription_renal_insufficiency_chk": opd_prescription_renal_insufficiency_chk,
                    "opd_prescription_pulmonary_insufficiency_chk": opd_prescription_pulmonary_insufficiency_chk,
                    "opd_prescription_corona_suspected_chk": opd_prescription_corona_suspected_chk,
                    "opd_prescription_dengue_chk": opd_prescription_dengue_chk,
                    "input_complaints": input_complaints,
                    "input_Investigation": input_Investigation,
                    "input_diagnosis": input_diagnosis,
                    "input_Provisional_diagnosis": input_Provisional_diagnosis,

                    "input_pallor": input_pallor,
                    "input_Icterus": input_Icterus,
                    "input_cyanosis": input_cyanosis,
                    "input_clubbing": input_clubbing,
                    "input_edema": input_edema,

                    "pain_value":pain_value,
                    "p_id":<?= $data[0]->id ?>,
                    "morbidities_list":morbidities_list,

                    "opd_prescription_smoking_chk": opd_prescription_smoking_chk,
                    "opd_prescription_alcohol_chk": opd_prescription_alcohol_chk,
                    "opd_prescription_drug_chk": opd_prescription_drug_chk,

                    "<?= $this->security->get_csrf_token_name() ?>": csrf_value
                },
                function(data) {
                    notify('success', 'Please Attention', data);
                });
        });



        $('#btn_Print0').click(function() {
            var opd_session_id = $('#opd_session_id').val();
            var opd_id = $('#opd_id').val();

            var Get_Query = '/Opd_prescription/opd_prescription_print/' + opd_id + '/' + opd_session_id + "/0";
            //load_report_div(Get_Query,'diagnosis_table');
            window.open(Get_Query, "_blank");

        });

        $('#btn_Print1').click(function() {
            var opd_session_id = $('#opd_session_id').val();
            var opd_id = $('#opd_id').val();

            var Get_Query = '/Opd_prescription/opd_prescription_print/' + opd_id + '/' + opd_session_id + "/1";
            //load_report_div(Get_Query,'diagnosis_table');
            window.open(Get_Query, "_blank");

        });

        $('#btn_Print2').click(function() {
            var opd_session_id = $('#opd_session_id').val();
            var opd_id = $('#opd_id').val();

            var Get_Query = '/Opd_prescription/opd_prescription_print/' + opd_id + '/' + opd_session_id + "/2";
            //load_report_div(Get_Query,'diagnosis_table');
            window.open(Get_Query, "_blank");

        });

        $('[data-toggle="pill"]').click(function(e) {
            e.preventDefault()
            var loadurl = $(this).attr('href')
            if (loadurl != '#') {
                var targ = $(this).attr('data-target')
                $.get(loadurl, function(data) {
                    $(targ).html(data)
                });
            }

            $(this).tab('show')
        });

        $('[data-toggle="gpill"]').click(function(e) {
            e.preventDefault()
            var loadurl = $(this).attr('href')
            if (loadurl != '#') {
                var targ = $(this).attr('data-target')
                $.get(loadurl, function(data) {
                    $(targ).html(data)
                });
            }

            $(this).tab('show')
        });

        $('input:radio[name=options-pain]').change(function () {
            onchange_vital();
        });


        $('.chk_opd').change(function () {
            onchange_vital();
        });

    });

    //Profile Photo
    $('#tallModal').on('shown.bs.modal', function(event) {
        $('.testentry-bodyc').html('');

        var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

        var height = $(window).height() - 50;
        $(this).find(".modal-body").css("max-height", height);

        var button = $(event.relatedTarget);
        var etype = button.data('etype');

        if (etype == '1') {
            $('#testentryLabel').html('Add New Rx Group');

            $.post('/index.php/Opd_prescription/new_rx_group/<?= $doc_id ?>', {
                    '<?= $this->security->get_csrf_token_name() ?>': csrf_value
                },
                function(data) {
                    $('#testentry-bodyc').html(data);
                });
        }
        if (etype == '2') {
            $('#testentryLabel').html('Add New Rx Group');

            $.post('/index.php/Opd_prescription/new_rx_group/<?= $doc_id ?>', {
                    '<?= $this->security->get_csrf_token_name() ?>': csrf_value
                },
                function(data) {
                    $('#testentry-bodyc').html(data);
                });
        }
    });

    function add_Complaints(test_name) {
        var old_text = $('#input_complaints').val();
        $('#input_complaints').val(old_text + ', ' + test_name);
        onchange_vital();
    }

    function add_Complaints_hid(srno) {
        var old_text = $('#input_complaints').val();
        var str_complaint=$('#h_complaint_'+srno).val();
        $('#input_complaints').val(old_text + ', ' + str_complaint);
        onchange_vital();
    }

    function onchange_vital() {

        var favorite = [];

        $.each($("input[name='morbidities']:checked"), function() {
            favorite.push($(this).val());
        });

        var morbidities_list = favorite.join("-");

        var opd_session_id = $('#opd_session_id').val();
        var opd_id = $('#opd_id').val();
        var input_Examinations = $('#input_Examinations').val();
        var input_Prescriber = $('#input_Prescriber').val();

        var input_BP = $('#input_BP').val();
        var input_Diastolic = $('#input_Diastolic').val();
        var input_Height = $('#input_Height').val();
        var input_Weight = $('#input_Weight').val();
        var input_Tempature = $('#input_Tempature').val();
        var input_Pulse = $('#input_Pulse').val();
        var input_Waist = $('#input_Waist').val();

        var input_RR = $('#input_RR').val();

        var input_glucose = $('#input_glucose').val();
        var input_SPO2 = $('#input_SPO2').val();

        var input_complaints = $('#input_complaints').val();
        var input_diagnosis = $('#input_diagnosis').val();
        var input_Provisional_diagnosis = $('#input_Provisional_diagnosis').val();

        var input_Investigation = $('#input_Investigation').val();

        var opd_prescription_paediatric_chk = 0;
        var opd_prescription_pregnancy_chk = $('#opd_prescription_pregnancy_chk').is(":checked") ? 1 :
            0;
        var opd_prescription_lactation_chk = $('#opd_prescription_lactation_chk').is(":checked") ? 1 :
            0;
        var opd_prescription_liver_insufficiency_chk = $('#opd_prescription_liver_insufficiency_chk')
            .is(":checked") ? 1 : 0;
        var opd_prescription_renal_insufficiency_chk = $('#opd_prescription_renal_insufficiency_chk')
            .is(":checked") ? 1 : 0;
        var opd_prescription_pulmonary_insufficiency_chk = $(
            '#opd_prescription_pulmonary_insufficiency_chk').is(":checked") ? 1 : 0;
        var opd_prescription_corona_suspected_chk = $('#opd_prescription_corona_suspected_chk').is(
            ":checked") ? 1 : 0;
        var opd_prescription_dengue_chk = $('#opd_prescription_dengue_chk').is(":checked") ? 1 : 0;

        var opd_prescription_smoking_chk = $('#opd_prescription_smoking_chk').is(":checked") ? 1 : 0;
        var opd_prescription_alcohol_chk = $('#opd_prescription_alcohol_chk').is(":checked") ? 1 : 0;
        var opd_prescription_drug_chk = $('#opd_prescription_drug_chk').is(":checked") ? 1 : 0;

        var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

        var pain_value=$("input[name='options-pain']:checked").val();

        $.post('/Opd_prescription/opd_prescription_save', {
                "opd_session_id": opd_session_id,
                "input_Examinations": input_Examinations,
                "input_Prescriber": input_Prescriber,
                "input_BP": input_BP,
                "input_Diastolic": input_Diastolic,
                "input_Height": input_Height,
                "input_Weight": input_Weight,
                "input_Tempature": input_Tempature,
                "input_Pulse": input_Pulse,
                "input_Waist": input_Waist,
                "input_glucose": input_glucose,
                "input_RR": input_RR,
                "input_SPO2": input_SPO2,
                "opd_id": opd_id,
                "opd_prescription_paediatric_chk": opd_prescription_paediatric_chk,
                "opd_prescription_pregnancy_chk": opd_prescription_pregnancy_chk,
                "opd_prescription_lactation_chk": opd_prescription_lactation_chk,
                "opd_prescription_liver_insufficiency_chk": opd_prescription_liver_insufficiency_chk,
                "opd_prescription_renal_insufficiency_chk": opd_prescription_renal_insufficiency_chk,
                "opd_prescription_pulmonary_insufficiency_chk": opd_prescription_pulmonary_insufficiency_chk,
                "opd_prescription_corona_suspected_chk": opd_prescription_corona_suspected_chk,
                "opd_prescription_dengue_chk": opd_prescription_dengue_chk,
                "input_complaints": input_complaints,
                "input_Investigation": input_Investigation,
                "input_diagnosis": input_diagnosis,
                "input_Provisional_diagnosis": input_Provisional_diagnosis,
                "pain_value":pain_value,
                "p_id":<?= $data[0]->id ?>,
                "morbidities_list":morbidities_list,

                "opd_prescription_smoking_chk": opd_prescription_smoking_chk,
                "opd_prescription_alcohol_chk": opd_prescription_alcohol_chk,
                "opd_prescription_drug_chk": opd_prescription_drug_chk,

                "<?= $this->security->get_csrf_token_name() ?>": csrf_value
            },
            function(data) {
                console.info(data);
            });
    }

    $(function() {

        var cachemedical = {};
        var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

        function split(val) {
            return val.split(/,\s*/);
        }

        function extractLast(term) {
            return split(term).pop();
        }


        $("#input_complaints")
            // don't navigate away from the field on tab when selecting an item
            .on("keydown", function(event) {
                if (event.keyCode === $.ui.keyCode.TAB &&
                    $(this).autocomplete("instance").menu.active) {
                    event.preventDefault();
                }
            })
            .autocomplete({
                source: function(request, response) {
                    $.getJSON("Opd_prescription/get_complaints", {
                        term: extractLast(request.term)
                    }, response);
                },
                search: function() {
                    // custom minLength
                    var term = extractLast(this.value);
                    if (term.length < 2) {
                        return false;
                    }
                },
                focus: function() {
                    // prevent value inserted on focus
                    return false;
                },
                select: function(event, ui) {
                    var terms = split(this.value);
                    // remove the current input
                    terms.pop();
                    // add the selected item
                    terms.push(ui.item.value);
                    // add placeholder to get the comma-and-space at the end
                    terms.push("");
                    this.value = terms.join(", ");

                    return false;
                }
            });


        $("#input_diagnosis")
            // don't navigate away from the field on tab when selecting an item
            .on("keydown", function(event) {
                if (event.keyCode === $.ui.keyCode.TAB &&
                    $(this).autocomplete("instance").menu.active) {
                    event.preventDefault();
                }
            })
            .autocomplete({
                source: function(request, response) {
                    $.getJSON("Opd_prescription/get_complaints", {
                        term: extractLast(request.term)
                    }, response);
                },
                search: function() {
                    // custom minLength
                    var term = extractLast(this.value);
                    if (term.length < 2) {
                        return false;
                    }
                },
                focus: function() {
                    // prevent value inserted on focus
                    return false;
                },
                select: function(event, ui) {
                    var terms = split(this.value);
                    // remove the current input
                    terms.pop();
                    // add the selected item
                    terms.push(ui.item.value);
                    // add placeholder to get the comma-and-space at the end
                    terms.push("");
                    this.value = terms.join(", ");
                    return false;
                }
            });

        $("#input_Provisional_diagnosis")
            // don't navigate away from the field on tab when selecting an item
            .on("keydown", function(event) {
                if (event.keyCode === $.ui.keyCode.TAB &&
                    $(this).autocomplete("instance").menu.active) {
                    event.preventDefault();
                }
            })
            .autocomplete({
                source: function(request, response) {
                    $.getJSON("Opd_prescription/get_disease", {
                        term: extractLast(request.term)
                    }, response);
                },
                search: function() {
                    // custom minLength
                    var term = extractLast(this.value);
                    if (term.length < 2) {
                        return false;
                    }
                },
                focus: function() {
                    // prevent value inserted on focus
                    return false;
                },
                select: function(event, ui) {
                    var terms = split(this.value);
                    // remove the current input
                    terms.pop();
                    // add the selected item
                    terms.push(ui.item.value);
                    // add placeholder to get the comma-and-space at the end
                    terms.push("");
                    this.value = terms.join(", ");
                    return false;
                }
            });

        // Investigation
        $("#input_Investigation")
            // don't navigate away from the field on tab when selecting an item
            .on("keydown", function(event) {
                if (event.keyCode === $.ui.keyCode.TAB &&
                    $(this).autocomplete("instance").menu.active) {
                    event.preventDefault();
                }
            })
            .autocomplete({
                source: function(request, response) {
                    $.getJSON("Opd_prescription/get_investigation", {
                        term: extractLast(request.term)
                    }, response);
                },
                search: function() {
                    // custom minLength
                    var term = extractLast(this.value);
                    if (term.length < 2) {
                        return false;
                    }
                },
                focus: function() {
                    // prevent value inserted on focus
                    return false;
                },
                select: function(event, ui) {
                    var terms = split(this.value);
                    // remove the current input
                    terms.pop();
                    // add the selected item
                    terms.push(ui.item.value);
                    // add placeholder to get the comma-and-space at the end
                    terms.push("");
                    this.value = terms.join(", ");
                    return false;
                }


        });

        $("#input_Examinations")
            // don't navigate away from the field on tab when selecting an item
            .on("keydown", function(event) {
                if (event.keyCode === $.ui.keyCode.TAB &&
                    $(this).autocomplete("instance").menu.active) {
                    event.preventDefault();
                }
            })
            .autocomplete({
                source: function(request, response) {
                    $.getJSON("Opd_prescription/get_finding_exam", {
                        term: extractLast(request.term)
                    }, response);
                },
                search: function() {
                    // custom minLength
                    var term = extractLast(this.value);
                    if (term.length < 2) {
                        return false;
                    }
                },
                focus: function() {
                    // prevent value inserted on focus
                    return false;
                },
                select: function(event, ui) {
                    var terms = split(this.value);
                    // remove the current input
                    terms.pop();
                    // add the selected item
                    terms.push(ui.item.value);
                    // add placeholder to get the comma-and-space at the end
                    terms.push("");
                    this.value = terms.join(", ");

                    return false;
                }
            });







    });
</script>