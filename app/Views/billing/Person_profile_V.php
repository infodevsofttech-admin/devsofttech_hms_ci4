<div class="pagetitle">
    <h1>Profile</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="javascript:load_form('<?= base_url('billing/patient/person_record') ?>/<?=$data[0]->id?>/0');">Home</a></li>
            <li class="breadcrumb-item">Patient</li>
            <li class="breadcrumb-item active">Profile</li>
        </ol>
    </nav>
</div>

<section class="section profile">
    <div class="jsError"></div>
    <form role="form" class="form1">
        <?= csrf_field() ?>
        <?php
            $patientAbhaId = '';
            foreach (['abha_id', 'abha_no', 'abha', 'abha_address'] as $abhaField) {
                $candidateAbha = trim((string) ($data[0]->{$abhaField} ?? ''));
                if ($candidateAbha !== '') {
                    $patientAbhaId = $candidateAbha;
                    break;
                }
            }
            $abhaAddress = trim((string) ($data[0]->abha_address ?? ''));
            if ($abhaAddress === '' && preg_match('/abha_address\s*:\s*([A-Za-z0-9._-]+@[A-Za-z0-9.-]+)/i', (string) ($data[0]->log ?? ''), $abhaLogMatch) === 1) {
                $abhaAddress = trim((string) ($abhaLogMatch[1] ?? ''));
            }
            $abhaVerifiedStatus = trim((string) ($data[0]->abha_verified_status ?? ''));
            $abhaVerificationType = trim((string) ($data[0]->abha_verification_type ?? ''));
            $abhaKycVerified = (int) ($data[0]->abha_kyc_verified ?? 0) === 1;
            $abhaMobileVerified = (int) ($data[0]->abha_mobile_verified ?? 0) === 1;
            $abhaLinkedAt = trim((string) ($data[0]->abdm_linked_at ?? ''));
            $abhaPhotoAvailable = trim((string) ($data[0]->abha_profile_photo_base64 ?? '')) !== '';
            $isAbhaLinkedAndVerified = $patientAbhaId !== '' && strtoupper($abhaVerifiedStatus) === 'VERIFIED';
        ?>
        <?php
            $user = auth()->user();
            $canEditOpd = is_object($user) && method_exists($user, 'can') ? $user->can('billing.opd.edit') : false;
            $canChargeEdit = is_object($user) && method_exists($user, 'can') ? $user->can('billing.charges.edit') : false;
        ?>
        <input type="hidden" value="<?=$data[0]->id ?>" id="p_id" name="p_id" />
        <input type="hidden" id="ins_id" value="<?=$data[0]->insurance_id?>">
        <input type="hidden" id="ins_card_id" value="<?=$data[0]->insurance_card_id?>">

        <div class="row">
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-body profile-card pt-4 d-flex flex-column align-items-center">
                        <img src="<?=$profile_file_path?>" alt="Profile" class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                        <h2 class="mt-3 mb-1"><?=ucwords($data[0]->p_fname) ?></h2>
                        <h3 class="mb-2"><?=$data[0]->p_code; ?></h3>
                        <div class="d-flex gap-2 flex-wrap justify-content-center">
                            <button type="button" class="btn btn-danger btn-sm" onclick="load_form('<?= base_url('billing/patient/person_record') ?>/<?=$data[0]->id?>/1');">Profile Edit</button>
                            <button type="button" class="btn btn-success btn-sm" onclick="load_form('<?= base_url('billing/patient/show_profile_image') ?>/<?=$data[0]->id?>/1');">Edit Picture</button>
                            <?php $consultHistoryBackUrl = base_url('billing/patient/person_record') . '/' . (int) ($data[0]->id ?? 0) . '/0'; ?>
                            <button type="button" class="btn btn-info btn-sm" onclick="load_form('<?= base_url('billing/patient/show_profile_opd') ?>/<?= (int) ($data[0]->id ?? 0) ?>/1?<?= http_build_query(['back_url' => $consultHistoryBackUrl, 'back_title' => 'Profile']) ?>');">Consult History</button>
                        </div>
                        <div class="mt-3 w-100">
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-primary btn-sm" id="btn_opd" accesskey="A"><u>A</u>ppointment For OPD</button>
                                <?php if ($canChargeEdit) : ?>
                                    <button type="button" class="btn btn-danger btn-sm" id="btn_lab">Add Charge</button>
                                <?php endif; ?>
                                <button type="button" class="btn btn-warning btn-sm" id="btn_ipd">IPD</button>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm flex-fill" id="btn_patient_profile_scan" title="Scan document using camera/scanner">
                                        <i class="bi bi-camera me-1"></i>Scan
                                    </button>
                                    <button type="button" class="btn btn-outline-success btn-sm flex-fill" id="btn_patient_profile_upload" title="Upload PDF or Image file">
                                        <i class="bi bi-upload me-1"></i>Upload
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 w-100">
                            <div class="d-flex justify-content-between align-items-center border rounded px-3 py-2">
                                <div>
                                    <div class="small text-muted">OPD Visits</div>
                                    <div class="fw-bold"><?= count($opd_List) ?></div>
                                </div>
                                <div>
                                    <div class="small text-muted">Invoices</div>
                                    <div class="fw-bold"><?= count($invoice_list) ?></div>
                                </div>
                                <div>
                                    <div class="small text-muted">Insurance</div>
                                    <div class="fw-bold"><?= ($data[0]->insurance_id ?? 0) > 0 ? 'Yes' : 'No' ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card">
                    <div class="card-body pt-3">
                        <ul class="nav nav-tabs nav-tabs-bordered" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#profile-overview" type="button" role="tab">Overview</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-insurance" type="button" role="tab">Insurance</button>
                            </li>
                            <?php if (!$isAbhaLinkedAndVerified) : ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-abha" type="button" role="tab">
                                    <i class="bi bi-person-check me-1"></i>ABHA Create/Verify
                                </button>
                            </li>
                            <?php endif; ?>
                            <?php if(count($opd_List)>0) { ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-opd" type="button" role="tab">OPD</button>
                            </li>
                            <?php } ?>
                            <?php if(count($invoice_list)>0) { ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-invoices" type="button" role="tab">Invoices</button>
                            </li>
                            <?php } ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab-patient-documents-btn" data-bs-toggle="tab" data-bs-target="#profile-documents" type="button" role="tab" onclick="loadPatientDocuments(<?= (int)$data[0]->id ?>)">
                                    <i class="bi bi-file-earmark-medical me-1"></i>Documents
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content pt-2">
                            <div class="tab-pane fade show active profile-overview" id="profile-overview" role="tabpanel">
                                <h5 class="card-title">Profile Details</h5>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">Full Name</div>
                                    <div class="col-lg-9 col-md-8"><?=ucwords($data[0]->p_fname) ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">Gender / Age</div>
                                    <div class="col-lg-9 col-md-8"><?=$data[0]->xgender?> / <?= esc(get_age_1($data[0]->dob ?? null, $data[0]->age ?? '', $data[0]->age_in_month ?? '', $data[0]->estimate_dob ?? '')) ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">Relation</div>
                                    <div class="col-lg-9 col-md-8">
                                        <?php if (trim((string) ($data[0]->p_relative ?? '')) !== '' || trim((string) ($data[0]->p_rname ?? '')) !== '') : ?>
                                            <?=$data[0]->p_relative?> <?=ucwords($data[0]->p_rname)?>
                                        <?php else : ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Relation not filled</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">Refer By</div>
                                    <div class="col-lg-9 col-md-8">
                                        <?php if (trim((string) ($data[0]->referby ?? '')) !== '') : ?>
                                            <?= esc(ucwords(strtolower((string) ($data[0]->referby ?? '')))) ?>
                                        <?php else : ?>
                                            <span class="text-muted small">Not filled</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">Aadhar No.</div>
                                    <div class="col-lg-9 col-md-8">
                                        <?php $aadhaarLast4 = trim((string) ($data[0]->udai_last4 ?? '')); ?>
                                        <?= $aadhaarLast4 !== '' ? 'XXXX-XXXX-' . esc($aadhaarLast4) : 'Not available' ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">ABHA ID</div>
                                    <div class="col-lg-9 col-md-8 d-flex align-items-center gap-2 flex-wrap">
                                        <span id="abha_id_display">
                                        <?php if ($patientAbhaId !== '') : ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace fs-6"><?= esc($patientAbhaId) ?></span>
                                        <?php else : ?>
                                            <span class="text-muted small">Not linked</span>
                                        <?php endif; ?>
                                        </span>
                                        <?php if (!$isAbhaLinkedAndVerified) : ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary py-0" style="font-size:12px"
                                            onclick="openAbhaOtpModal(
                                                <?= (int)$data[0]->id ?>,
                                                '<?= esc($patientAbhaId) ?>',
                                                '<?= esc($data[0]->mphone1 ?? '') ?>'
                                            )">
                                            <i class="bi bi-person-check me-1"></i><?= $patientAbhaId !== '' ? 'Re-link ABHA' : 'Link ABHA via OTP' ?>
                                        </button>
                                        <?php endif; ?>
                                        <?php
                                            $abhaCardNum = '';
                                            foreach (['abha_id', 'abha_no', 'abha'] as $abhaNumberField) {
                                                $candidateAbhaNumber = preg_replace('/\D/', '', (string) ($data[0]->{$abhaNumberField} ?? ''));
                                                if (strlen($candidateAbhaNumber) === 14) {
                                                    $abhaCardNum = $candidateAbhaNumber;
                                                    break;
                                                }
                                            }
                                            $hasLinkedAbha = strlen($abhaCardNum) === 14;
                                            $hasOfficialAbhaCard = $hasLinkedAbha && trim((string) ($data[0]->abha_card_base64 ?? '')) !== '';
                                        ?>
                                        <?php if ($hasOfficialAbhaCard) : ?>
                                        <a href="<?= base_url('abha/card/' . esc($abhaCardNum, 'url')) ?>"
                                           target="_blank"
                                           class="btn btn-sm btn-outline-success py-0"
                                           style="font-size:12px"
                                           title="View and print the official NHA ABHA card">
                                            <i class="bi bi-card-image me-1"></i>Official ABHA Card
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($hasLinkedAbha) : ?>
                                        <a href="<?= base_url('abha/hospital-card/' . (int) ($data[0]->id ?? 0)) ?>"
                                           target="_blank"
                                           class="btn btn-sm btn-outline-primary py-0"
                                           style="font-size:12px"
                                           title="View and print the hospital patient card">
                                            <i class="bi bi-person-vcard me-1"></i>Hospital Card
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">ABHA Address</div>
                                    <div class="col-lg-9 col-md-8">
                                        <?php if ($abhaAddress !== '') : ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= esc($abhaAddress) ?></span>
                                        <?php else : ?>
                                            <span class="text-muted small">Not available</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">ABHA Verification</div>
                                    <div class="col-lg-9 col-md-8 d-flex align-items-center gap-2 flex-wrap">
                                        <?php if ($abhaVerifiedStatus !== '') : ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle"><?= esc($abhaVerifiedStatus) ?></span>
                                        <?php else : ?>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">UNAVAILABLE</span>
                                        <?php endif; ?>

                                        <?php if ($abhaVerificationType !== '') : ?>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle"><?= esc($abhaVerificationType) ?></span>
                                        <?php endif; ?>

                                        <span class="badge <?= $abhaKycVerified ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle' ?>">KYC <?= $abhaKycVerified ? 'YES' : 'NO' ?></span>
                                        <span class="badge <?= $abhaMobileVerified ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle' ?>">MOBILE <?= $abhaMobileVerified ? 'YES' : 'NO' ?></span>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">ABHA Linked At</div>
                                    <div class="col-lg-9 col-md-8">
                                        <?= $abhaLinkedAt !== '' ? esc($abhaLinkedAt) : '<span class="text-muted small">Not linked via OTP</span>' ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">ABHA Photo</div>
                                    <div class="col-lg-9 col-md-8">
                                        <span class="badge <?= $abhaPhotoAvailable ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' ?>">
                                            <?= $abhaPhotoAvailable ? 'AVAILABLE (BASE64 STORED)' : 'NOT AVAILABLE' ?>
                                        </span>
                                    </div>
                                </div>
                                <?php $hpPatientSyncId = trim((string) ($data[0]->healthplix_sync_id ?? '')); ?>
                                <?php if ($hpPatientSyncId !== '') : ?>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">HealthPlix ID</div>
                                    <div class="col-lg-9 col-md-8">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle"><?= esc($hpPatientSyncId) ?></span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">Phone</div>
                                    <div class="col-lg-9 col-md-8">
                                        <?php if (trim((string) ($data[0]->mphone1 ?? '')) !== '') : ?>
                                            <?=$data[0]->mphone1?>
                                        <?php else : ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Phone not filled</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">Email</div>
                                    <div class="col-lg-9 col-md-8"><?=$data[0]->email1?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">Blood Group</div>
                                    <div class="col-lg-9 col-md-8"><?=$data[0]->blood_group?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">Address</div>
                                    <div class="col-lg-9 col-md-8"><?=$data[0]->add1?> <?=$data[0]->city ?> <?=$data[0]->district ?> <?=$data[0]->state ?> <?=$data[0]->zip ?></div>
                                </div>

                                <h5 class="card-title">Quick Update</h5>
                                <div class="row g-2 align-items-end">
                                    <div class="col-lg-6">
                                        <label class="form-label">Aadhar No.</label>
                                        <div class="input-group input-group-sm">
                                            <input class="form-control" type="text" name="input_Aadhar" id="input_Aadhar" value="<?= trim((string) ($data[0]->udai_last4 ?? '')) !== '' ? 'XXXXXXXX' . esc((string) $data[0]->udai_last4) : '' ?>">
                                            <button type="button" id="btn_update_aadhar" class="btn btn-info">Update</button>
                                        </div>
                                    </div>
                                    <?php if (!$isAbhaLinkedAndVerified) : ?>
                                    <div class="col-lg-6">
                                        <label class="form-label">ABHA ID</label>
                                        <div class="input-group input-group-sm">
                                            <input class="form-control" type="text" name="input_abha_id" id="input_abha_id"
                                                   value="<?= esc($patientAbhaId) ?>" maxlength="17"
                                                   placeholder="14-digit or xx-xxxx-xxxx-xxxx">
                                            <button type="button" id="btn_update_abha" class="btn btn-info">Save</button>
                                            <button type="button" class="btn btn-outline-primary" title="Link via OTP"
                                                onclick="openAbhaOtpModal(<?= (int)$data[0]->id ?>,'<?= esc($patientAbhaId) ?>','<?= esc($data[0]->mphone1 ?? '') ?>')">
                                                <i class="bi bi-person-check"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">Type to set manually, or use <i class="bi bi-person-check"></i> for OTP flow.</div>
                                    </div>
                                    <?php endif; ?>
                                    <div class="col-lg-6">
                                        <button type="button" class="btn btn-warning btn-sm mt-4" onclick="load_form('<?= base_url('billing/patient/show_cards') ?>/<?=$data[0]->id?>/1');">Insurance Update</button>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade profile-edit pt-3" id="profile-insurance" role="tabpanel">
                                <h5 class="card-title">Insurance Details</h5>
                                <?php if($data[0]->insurance_id>0) {  ?>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">Company</div>
                                    <div class="col-lg-9 col-md-8"><?=$data_insurance_card[0]->ins_company_name?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">Card Holder</div>
                                    <div class="col-lg-9 col-md-8"><?=$data[0]->card_holder_name?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">Relation</div>
                                    <div class="col-lg-9 col-md-8"><?=$data[0]->relation_patient_cardholder?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">Insurance ID</div>
                                    <div class="col-lg-9 col-md-8"><?=$data[0]->insurance_no?></div>
                                </div>

                                <div class="mt-3">
                                    <?php if(count($case_master_opd)>0){ ?>
                                        <div class="alert alert-light border">
                                            <div><strong>Case Code:</strong> <?=$case_master_opd[0]->case_id_code?></div>
                                            <div><strong>Visit Date Start:</strong> <?=$case_master_opd[0]->str_date_registration?></div>
                                            <div><strong>Claim/Case No.:</strong> <?=$case_master_opd[0]->insurance_no_1?></div>
                                            <div><strong>Other IDs:</strong> <?=$case_master_opd[0]->insurance_no_2?></div>
                                            <button type="button" class="btn btn-warning btn-sm mt-2" id="btn_case_opd">Update Case Information</button>
                                        </div>
                                    <?php }else if(count($case_master_ipd)>0){ ?>
                                        <input type="hidden" id="ins_org_id" value="<?=$case_master_ipd[0]->id?>">
                                        <div class="alert alert-light border">
                                            <div><strong>Case Code:</strong> <?=$case_master_ipd[0]->case_id_code?></div>
                                            <div><strong>Org. Date Start:</strong> <?=$case_master_ipd[0]->str_date_registration?></div>
                                            <div><strong>Claim/Case No.:</strong> <?=$case_master_ipd[0]->insurance_no_1?></div>
                                            <div><strong>Other IDs:</strong> <?=$case_master_ipd[0]->insurance_no_2?></div>
                                            <div><strong>IPD No:</strong> <?=$case_master_ipd[0]->ipd_code?></div>
                                            <div><strong>IPD Admit Date:</strong> <?=$case_master_ipd[0]->str_date_registration?></div>
                                            <button type="button" class="btn btn-warning btn-sm mt-2" id="btn_case_ipd_open">Update Case Information</button>
                                        </div>
                                    <?php }else{ ?>
                                        <div class="d-flex flex-wrap gap-2">
                                            <button type="button" class="btn btn-info btn-sm" id="btn_case_ipd">Create Case for Credit IPD Bill</button>
                                            <button type="button" class="btn btn-success btn-sm" id="btn_case_opd">Create Case for Credit OPD Bill</button>
                                        </div>
                                    <?php } ?>
                                </div>

                                <div class="mt-3 d-flex flex-wrap gap-2">
                                    <?php if($data_insurance_card[0]->opd_allowed==1) {  ?>
                                    <button type="button" class="btn btn-success btn-sm" id="btn_inc_opd">OPD Insurance Rates / CASH</button>
                                    <?php }  ?>
                                    <?php if($data_insurance_card[0]->charge_cash==1 && $canChargeEdit) {  ?>
                                    <button type="button" class="btn btn-success btn-sm" id="btn_inc_lab">Charge with Ins. Rate / CASH</button>
                                    <?php } ?>
                                </div>
                                <?php } else { ?>
                                    <div class="alert alert-info mb-0">No insurance details available.</div>
                                <?php } ?>
                            </div>

                            <?php if(count($opd_List)>0) { ?>
                            <div class="tab-pane fade pt-3" id="profile-opd" role="tabpanel">
                                <h5 class="card-title">OPD Registration</h5>
                                <?php
                                    foreach($opd_List as $row)
                                    {
                                        echo '<div class="mb-3">';
                                        echo '<strong>'.$row->opd_code.'</strong>'; 
                                        echo '<div class="text-muted">';
                                        echo '<span class="text-success">Dr.'.$row->doc_name.'</span> '; 
                                        echo '<span class="text-info">D:'.$row->str_apointment_date.'</span> '; 
                                        echo '<span class="text-warning">P:'.$row->p_fname.'</span><br/>';

                                        if($canEditOpd || $row->new_opd==1){
                                            echo '<a href="javascript:load_form(\'' . base_url('Opd/invoice') . '/' . $row->opd_id . '\');" class="btn btn-warning btn-sm me-2">Edit OPD</a>';
                                            echo '<a href="' . base_url('Opd_print/opd_day_care') . '/' . $row->opd_id . '/0" target="_blank" class="btn btn-success btn-sm">Print Day Care</a>';
                                        }
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                ?>
                            </div>
                            <?php } ?>

                            <?php if(count($invoice_list)>0) { ?>
                            <div class="tab-pane fade pt-3" id="profile-invoices" role="tabpanel">
                                <h5 class="card-title">Charges Invoice</h5>
                                <?php
                                    foreach($invoice_list as $row)
                                    {
                                        echo '<div class="mb-3">';
                                        echo '<strong>'.$row->invoice_code.'</strong>';
                                        echo ' <span class="text-info">D:'.$row->str_inv_date.'</span>';
                                        echo ' <span class="text-warning">N:'.$row->inv_name.'</span>';
                                        echo '<div class="text-muted">';
                                        echo '<span class="text-success">'.$row->Item_List.'</span><br/>';
                                        if($row->invoice_status==0)
                                        {
                                            if ($canChargeEdit) {
                                                echo '<a href="javascript:load_form(\'' . base_url('billing/charges/edit') . '/' . $row->id . '\');" class="btn btn-warning btn-sm me-2">Edit Charges Invoice</a>';
                                                echo '<a href="javascript:delete_invoice(\''.$row->id.'\');" class="btn btn-danger btn-sm">Delete : Un-confirm Invoice</a>';
                                            }
                                        }else{
                                            echo '<a href="javascript:load_form(\'' . base_url('billing/charges/show') . '/' . $row->id . '\');" class="btn btn-info btn-sm">Show Charges Invoice</a>';
                                        }
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                ?>
                            </div>
                            <?php } ?>

                            <div class="tab-pane fade pt-3" id="profile-documents" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h5 class="card-title mb-0 py-0">Patient Documents</h5>
                                        <div class="text-muted small">Scanned physical copies, uploaded PDFs, and medical reports.</div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn_tab_scan_doc">
                                            <i class="bi bi-camera me-1"></i>Scan Document
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success" id="btn_tab_upload_doc">
                                            <i class="bi bi-upload me-1"></i>Upload PDF / Image
                                        </button>
                                    </div>
                                </div>
                                <div id="patient_documents_container">
                                    <div class="text-muted p-3"><div class="spinner-border spinner-border-sm me-2"></div>Loading patient documents...</div>
                                </div>
                            </div>

                            <?php if (!$isAbhaLinkedAndVerified) : ?>
                            <div class="tab-pane fade pt-3" id="profile-abha" role="tabpanel">
                                <h5 class="card-title mb-1">ABHA Number Create and Verify</h5>
                                <p class="text-muted small mb-3">Create a new ABHA or link an existing ABHA profile to this patient.</p>
                                <button type="button" class="btn btn-primary" id="profile_open_abha_create_btn">
                                    <i class="bi bi-person-plus-fill me-1"></i>Create ABHA
                                </button>
                            </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</section>

<?php if (!$isAbhaLinkedAndVerified) : ?>
<?= view('partials/abha_patient_match_modal') ?>
<?= view('partials/abha_create_modal') ?>
<?php endif; ?>

<script>
$(document).ready(function() {

    $(document).off('click.profileAbhaCreate', '#profile_open_abha_create_btn')
        .on('click.profileAbhaCreate', '#profile_open_abha_create_btn', function() {
            if (!window.AbhaCreateModal || !window.AbhaPatientMatchModal) {
                alert('ABHA create workflow is unavailable. Reload the patient profile and try again.');
                return;
            }

            window.AbhaCreateModal.open(function(profile) {
                var refreshProfile = function(result) {
                    var patientId = Number((result && result.patient_id) || <?= (int) ($data[0]->id ?? 0) ?>);
                    load_form('<?= base_url('billing/patient/person_record') ?>/' + patientId + '/0');
                };

                if (profile && profile.need_confirmation === false && Number(profile.patient_id || 0) > 0) {
                    refreshProfile(profile);
                    return;
                }

                var currentPatientId = <?= (int) ($data[0]->id ?? 0) ?>;
                var candidates = (profile.candidates || []).slice();
                if (!candidates.some(function(candidate) { return Number(candidate.id) === currentPatientId; })) {
                    var existingAbhaDigits = String(<?= json_encode($patientAbhaId) ?>).replace(/\D/g, '');
                    var incomingAbhaDigits = String(profile.abha_number || '').replace(/\D/g, '');
                    candidates.unshift({
                        id: currentPatientId,
                        p_code: <?= json_encode((string) ($data[0]->p_code ?? '')) ?>,
                        name: <?= json_encode(trim((string) ($data[0]->p_fname ?? '') . ' ' . (string) ($data[0]->p_lname ?? ''))) ?>,
                        gender: <?= (int) ($data[0]->gender ?? 0) ?>,
                        gender_label: <?= json_encode((string) ($data[0]->xgender ?? '')) ?>,
                        dob: <?= json_encode((string) ($data[0]->dob ?? '')) ?>,
                        mobile: <?= json_encode((string) ($data[0]->mphone1 ?? '')) ?>,
                        address: <?= json_encode(trim(implode(', ', array_filter([
                            (string) ($data[0]->add1 ?? ''),
                            (string) ($data[0]->district ?? ''),
                            (string) ($data[0]->state ?? ''),
                            (string) ($data[0]->zip ?? ''),
                        ])))) ?>,
                        abha: <?= json_encode($patientAbhaId) ?>,
                        abha_conflict: existingAbhaDigits.length === 14
                            && incomingAbhaDigits.length === 14
                            && existingAbhaDigits !== incomingAbhaDigits,
                        match: {}
                    });
                }
                window.AbhaPatientMatchModal.open(profile, candidates, refreshProfile, currentPatientId);
            }, <?= json_encode((string) ($data[0]->mphone1 ?? '')) ?>);
        });

    function getPatientIdOrWarn() {
        var raw = $('#p_id').val();
        var patientId = parseInt(raw, 10);
        if (!Number.isFinite(patientId) || patientId <= 0) {
            console.error('Invalid patient id for OPD/IPD navigation', { raw: raw });
            alert('Patient ID is missing. Please reload patient profile.');
            return null;
        }
        return patientId;
    }

    document.title = 'Pt.:<?=$data[0]->p_fname ?>/<?=$data[0]->id ?>';

    $('#btn_opd').off('click.personProfile').on('click.personProfile', function() {
        var p_id = getPatientIdOrWarn();
        if (p_id === null) return;
        load_form('<?= base_url('Opd/addopd') ?>/' + p_id);
    });

    $('#btn_update_aadhar').off('click.personProfile').on('click.personProfile', function() {
        var p_id = $('#p_id').val();
        var udai = $('#input_Aadhar').val();
        var csrf_name = '<?= csrf_token() ?>';
        var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';

        if (confirm("Are you sure Update Aadhar No.")) {
            $.post('<?= base_url('billing/patient/update_aadhar') ?>', {
                "p_id": p_id,
                "udai": udai,
                [csrf_name]: csrf_value
            }, function(data) {
                load_form('<?= base_url('billing/patient/person_record') ?>/' + p_id);
            });
        }
    });

    $('#btn_update_abha').off('click.personProfile').on('click.personProfile', function() {
        var p_id = $('#p_id').val();
        var abha_id = ($('#input_abha_id').val() || '').trim();
        var csrf_name = '<?= csrf_token() ?>';
        var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';

        if (abha_id !== '' && !/^\d{14}$/.test(abha_id)) {
            alert('ABHA ID must be a 14-digit number.');
            return;
        }

        if (confirm("Are you sure Update ABHA ID.")) {
            $.post('<?= base_url('billing/patient/update_abha') ?>', {
                "p_id": p_id,
                "abha_id": abha_id,
                [csrf_name]: csrf_value
            }, function(data) {
                load_form('<?= base_url('billing/patient/person_record') ?>/' + p_id);
            });
        }
    });

    $('#btn_inc_opd').off('click.personProfile').on('click.personProfile', function() {
        var p_id = getPatientIdOrWarn();
        if (p_id === null) return;
        load_form('<?= base_url('Opdcase/addopd') ?>/' + p_id);
    });

    $('#btn_ipd').off('click.personProfile').on('click.personProfile', function() {
        var p_id = getPatientIdOrWarn();
        if (p_id === null) return;
        load_form('<?= base_url('IpdNew/addipd') ?>/' + p_id);
    });

    $('#btn_lab').off('click.personProfile').on('click.personProfile', function() {
        var p_id = getPatientIdOrWarn();
        if (p_id === null) return;
        load_form('<?= base_url('billing/charges/add') ?>/' + p_id);
    });

    $('#btn_inc_lab').off('click.personProfile').on('click.personProfile', function() {
        var p_id = getPatientIdOrWarn();
        if (p_id === null) return;
        var ins_card_id = $('#ins_card_id').val();
        load_form('<?= base_url('billing/charges/add') ?>/' + p_id + '/' + ins_card_id);
    });

    $('#btn_case_opd').off('click.personProfile').on('click.personProfile', function() {
        var p_id = getPatientIdOrWarn();
        if (p_id === null) return;
        var ins_id = $('#ins_id').val();
        var ins_card_id = $('#ins_card_id').val();

        load_form('<?= base_url('billing/case/newcase') ?>/' + p_id + '/' + ins_id + '/0');
    });

    $('#btn_case_ipd_open').off('click.personProfile').on('click.personProfile', function() {
        var ins_org_id = $('#ins_org_id').val();
        load_form('<?= base_url('billing/case/open_case') ?>/' + ins_org_id + '/1');
    });

    $('#btn_case_ipd').off('click.personProfile').on('click.personProfile', function() {
        var p_id = getPatientIdOrWarn();
        if (p_id === null) return;
        var ins_id = $('#ins_id').val();
        var ins_card_id = $('#ins_card_id').val();
        load_form('<?= base_url('billing/case/newcase') ?>/' + p_id + '/' + ins_id + '/1');
    });

    $('#btn_card').off('click.personProfile').on('click.personProfile', function() {
        var p_id = getPatientIdOrWarn();
        if (p_id === null) return;
        load_form('<?= base_url('billing/patient/show_cards') ?>/' + p_id);
    });

    $('#btn_update_card').off('click.personProfile').on('click.personProfile', function() {
        var p_id = getPatientIdOrWarn();
        if (p_id === null) return;
        var ins_id = $('#ins_id').val();
        load_form('<?= base_url('billing/patient/show_cards') ?>/' + p_id + '/' + ins_id);
    });
});

function delete_invoice(inv_id) {
    var pid = $('#p_id').val();
    var csrf_name = '<?= csrf_token() ?>';
    var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';

    if (confirm("Are you sure delete this invoice")) {
        $.post('<?= base_url('billing/charges/delete') ?>', {
            "inv_id": inv_id,
            [csrf_name]: csrf_value
        }, function(data) {
            load_form('<?= base_url('billing/patient/person_record') ?>/' + pid);
        });
    }

}

    /* ---- ABHA OTP linked callback: refresh display ---- */
    window.onAbhaLinked = function (patientId, abhaId) {
        var disp = document.getElementById('abha_id_display');
        if (disp) {
            disp.innerHTML = '<span class="badge bg-success-subtle text-success border border-success-subtle font-monospace fs-6">' +
                $('<div>').text(abhaId).html() + '</span>';
        }
        var inp = document.getElementById('input_abha_id');
        if (inp) inp.value = abhaId;
    };

    /* ---- Patient Document Management Scripts ---- */
    window.loadPatientDocuments = function (patientId) {
        var pid = patientId || Number($('#p_id').val() || 0);
        if (!pid) return;
        $('#patient_documents_container').html('<div class="text-muted p-3"><div class="spinner-border spinner-border-sm me-2"></div>Loading patient documents...</div>');
        $.get('<?= base_url('billing/patient/patient_file_list') ?>/' + pid, function(html) {
            $('#patient_documents_container').html(html);
        }).fail(function() {
            $('#patient_documents_container').html('<div class="alert alert-danger py-2 mb-0">Unable to load patient documents.</div>');
        });
    };

    window.openPatientUploadModal = function (patientId) {
        var pid = patientId || Number($('#p_id').val() || 0);
        if (!pid) return;
        $('#patientUploadPid').val(pid);
        $('#patientDocTitleInput').val('');
        $('#patientDocFileInput').val('');
        $('#patientUploadErrorMsg').addClass('d-none').text('');
        var modalEl = document.getElementById('patientDocumentUploadModal');
        if (modalEl) {
            var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    };

    window.openPatientScanModal = function (patientId) {
        var pid = patientId || Number($('#p_id').val() || 0);
        if (!pid) return;
        var latestOpdId = <?= count($opd_List) > 0 ? (int)($opd_List[0]->opd_id ?? $opd_List[0]->id ?? 0) : 0 ?>;
        var scanTargetId = latestOpdId > 0 ? latestOpdId : pid;
        
        var modalEl = document.getElementById('patientWebcamScanModal') || document.getElementById('testentry');
        if (modalEl) {
            var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            $('#patientWebcamScanModalLabel').html('<i class="bi bi-camera me-2 text-primary"></i>Webcam Document Scanner');
            $('#testentry-bodyc').html('<div class="text-muted p-3"><div class="spinner-border spinner-border-sm me-2"></div>Initializing webcam scanner...</div>');
            modal.show();
            $.post('/Opd/opd_load_doc/' + scanTargetId, {}, function(html) {
                $('#testentry-bodyc').html(html || '<div class="text-danger p-3">Unable to load webcam camera scanner.</div>');
            }).fail(function() {
                $('#testentry-bodyc').html('<div class="text-danger p-3">Unable to load webcam camera scanner.</div>');
            });
        }
    };

    window.deletePatientDoc = function (fileId, patientId) {
        if (!fileId || !confirm('Are you sure you want to delete this document?')) return;
        var csrfName = '<?= csrf_token() ?>';
        var csrfValue = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';
        $.post('<?= base_url('billing/patient/delete_patient_doc') ?>/' + fileId, { [csrfName]: csrfValue }, function(resp) {
            if (resp && resp.update === 1) {
                loadPatientDocuments(patientId);
            } else {
                alert((resp && resp.error_text) ? resp.error_text : 'Unable to delete document.');
            }
        }, 'json').fail(function() {
            alert('Unable to delete document.');
        });
    };

    $(document).off('click', '#btn_patient_profile_scan, #btn_tab_scan_doc').on('click', '#btn_patient_profile_scan, #btn_tab_scan_doc', function() {
        openPatientScanModal();
    });

    $(document).off('click', '#btn_patient_profile_upload, #btn_tab_upload_doc').on('click', '#btn_patient_profile_upload, #btn_tab_upload_doc', function() {
        openPatientUploadModal();
    });

    $(document).off('submit', '#patientDocUploadForm').on('submit', '#patientDocUploadForm', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        var $btn = $('#btnSubmitPatientDocUpload');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Uploading...');
        $('#patientUploadErrorMsg').addClass('d-none').text('');

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(resp) {
                $btn.prop('disabled', false).html('<i class="bi bi-upload me-1"></i>Upload Document');
                if (resp && resp.update === 1) {
                    var modalEl = document.getElementById('patientDocumentUploadModal');
                    if (modalEl) {
                        var modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                    }
                    var tabBtn = document.getElementById('tab-patient-documents-btn');
                    if (tabBtn) {
                        var tab = bootstrap.Tab.getOrCreateInstance(tabBtn);
                        tab.show();
                    }
                    loadPatientDocuments(resp.pid || $('#p_id').val());
                } else {
                    var err = (resp && resp.error_text) ? resp.error_text : 'Upload failed.';
                    $('#patientUploadErrorMsg').removeClass('d-none').text(err);
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<i class="bi bi-upload me-1"></i>Upload Document');
                $('#patientUploadErrorMsg').removeClass('d-none').text('Upload request failed. Please check file size and try again.');
            }
        });
    });

    // Cleanup camera stream when webcam scan modal is closed
    $(document).on('hidden.bs.modal', '#patientWebcamScanModal', function() {
        var $stopBtn = $('#opd_scan_stop_btn');
        if ($stopBtn.length) {
            $stopBtn.trigger('click');
        }
        loadPatientDocuments($('#p_id').val());
    });

</script>

<!-- Patient Webcam Camera Scanner Modal -->
<div class="modal fade" id="patientWebcamScanModal" tabindex="-1" aria-labelledby="patientWebcamScanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="patientWebcamScanModalLabel"><i class="bi bi-camera me-2 text-primary"></i>Webcam Document Scanner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="testentry-bodyc">
                <div class="text-muted p-3"><div class="spinner-border spinner-border-sm me-2"></div>Initializing webcam scanner...</div>
            </div>
        </div>
    </div>
</div>

<!-- Patient Document Upload Modal -->
<div class="modal fade" id="patientDocumentUploadModal" tabindex="-1" aria-labelledby="patientDocumentUploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="patientDocUploadForm" action="<?= base_url('billing/patient/upload_patient_doc/' . (int)($data[0]->id ?? 0)) ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" id="patientUploadPid" name="pid" value="<?= (int)($data[0]->id ?? 0) ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="patientDocumentUploadModalLabel"><i class="bi bi-upload me-2 text-primary"></i>Upload Patient Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="patientUploadErrorMsg" class="alert alert-danger py-2 mb-3 d-none"></div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Document Title / Type</label>
                        <input type="text" class="form-control" id="patientDocTitleInput" name="document_type" placeholder="e.g. Scanned Physical Prescription, Lab Report, ID Card" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select File (PDF or Image)</label>
                        <input type="file" class="form-control" id="patientDocFileInput" name="userfile" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
                        <div class="form-text">Allowed formats: PDF, JPG, PNG, WEBP (Max 8MB). Automatically enqueued for ABDM Health Document sharing.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btnSubmitPatientDocUpload"><i class="bi bi-upload me-1"></i>Upload Document</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= view('partials/abha_otp_modal') ?>