<!-- Main content -->
<style>
    /* Hide browser autocomplete suggestions */
    input:-webkit-autofill,
    input:-webkit-autofill:hover,
    input:-webkit-autofill:focus {
        -webkit-box-shadow: 0 0 0 1000px white inset !important;
        box-shadow: 0 0 0 1000px white inset !important;
    }
    
    /* Hide autocomplete dropdown suggestions */
    input:autofill {
        background-color: white !important;
    }

    /* ABHA wizard progress track */
    .abha-progress-wrap { display:flex; align-items:flex-start; margin-bottom:24px; }
    .abha-progress-wrap .abha-node {
        display:flex; flex-direction:column; align-items:center; flex:1; position:relative;
    }
    .abha-progress-wrap .abha-node:not(:last-child)::after {
        content:''; position:absolute; top:16px; left:calc(50% + 20px);
        right:calc(-50% + 20px); height:2px; background:#dee2e6; z-index:0;
    }
    .abha-progress-wrap .abha-node.done:not(:last-child)::after { background:#28a745; }
    .abha-badge {
        width:32px; height:32px; border-radius:50%;
        display:flex; align-items:center; justify-content:center;
        font-weight:700; font-size:13px; z-index:1;
        background:#dee2e6; color:#6c757d; border:2px solid #dee2e6;
        transition: all 0.25s;
    }
    .abha-node.active .abha-badge { background:#fff; color:#007bff; border-color:#007bff; }
    .abha-node.done   .abha-badge { background:#28a745; color:#fff; border-color:#28a745; }
    .abha-node-label {
        font-size:0.72rem; text-align:center; margin-top:5px;
        color:#aaa; max-width:80px; line-height:1.2;
    }
    .abha-node.active .abha-node-label { color:#007bff; font-weight:600; }
    .abha-node.done   .abha-node-label { color:#28a745; }
    .abha-panel { border-left:3px solid #007bff; padding-left:16px; }
</style>
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Patient Management</h5>
                    <ul class="nav nav-tabs" id="patientTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="search-tab" data-bs-toggle="tab"
                                data-bs-target="#search" type="button" role="tab" aria-controls="search"
                                aria-selected="true">Search</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="nperson-tab" data-bs-toggle="tab"
                                data-bs-target="#nperson" type="button" role="tab" aria-controls="nperson"
                                aria-selected="false">New Person</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="lastopd-tab" data-bs-toggle="tab"
                                data-bs-target="#lastopd" type="button" role="tab" aria-controls="lastopd"
                                aria-selected="false" data-url="<?= base_url('billing/patient/search_opd') ?>">Last OPDs</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="abha-tab" data-bs-toggle="tab"
                                data-bs-target="#abha" type="button" role="tab" aria-controls="abha"
                                aria-selected="false">ABHA Create/Verify</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="abhareg-tab" data-bs-toggle="tab"
                                data-bs-target="#abha-register" type="button" role="tab"
                                aria-selected="false">
                                <i class="bi bi-person-check-fill me-1"></i>ABHA Register
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content pt-2" id="patientTabsContent">
                        <div class="tab-pane fade" id="nperson" role="tabpanel" aria-labelledby="nperson-tab">
                            <div class="danger" id="jsError"></div>
                            <div class="row">
                                <div class="col-md-8">
                                    <form action="<?= base_url('billing/patient/create') ?>" method="post" role="form" class="form1 needs-validation" novalidate>
                                        <?= csrf_field() ?>
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label">Aadhaar Number</label>
                                                    <input class="form-control" name="input_udai" id="input_udai"
                                                        placeholder="Aadhaar Number" type="text" autocomplete="off"
                                                        data-inputmask='"mask": "999999999999"' data-mask
                                                        onchange="onchange_aadhar()">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label">ABHA ID</label>
                                                    <input class="form-control" name="input_abha_id" id="input_abha_id"
                                                        placeholder="14-digit ABHA ID" type="text" autocomplete="off"
                                                        maxlength="14" data-inputmask='"mask": "99999999999999"' data-mask>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label">Phone Number</label>
                                                    <input class="form-control" name="input_mphone1" id="input_mphone1"
                                                        placeholder="Phone Number" type="text" autocomplete="off" required
                                                        data-inputmask='"mask": "9999999999"' data-mask
                                                        onchange="onchange_feild_number()">
                                                    <div class="invalid-feedback">Please enter phone number.</div>
                                                </div>
                                            </div>
                                            <hr/>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label class="form-label">Title</label>
                                                    <select class="form-select" name="cbo_title" id="cbo_title"
                                                        onchange="onchange_title()">
                                                        <option value="Mr.">Mr.</option>
                                                        <option value="Mrs.">Mrs.</option>
                                                        <option value="Ms.">Ms.</option>
                                                        <option value="Master" selected="selected">Master</option>
                                                        <option value="Baby">Baby</option>
                                                        <option value="Baby Girl">Baby Girl</option>
                                                        <option value="Baby Boy">Baby Boy</option>
                                                        <option value="Mohd.">Mohd.</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label">Full Name</label>
                                                    <input class="form-control varchar" name="input_name" id="input_name"
                                                        placeholder="Full Name" type="text" autocomplete="off" required>
                                                    <div class="invalid-feedback">Please enter full name.</div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label">Gender</label>
                                                    <div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" name="optionsRadios_gender" id="options_gender1" value="1"
                                                                type="radio" checked>
                                                            <label class="form-check-label" for="options_gender1">Male</label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" name="optionsRadios_gender" id="options_gender2" value="2"
                                                                type="radio">
                                                            <label class="form-check-label" for="options_gender2">Female</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                        </div>
                                        <hr />
                                        <div class="row g-3">
                                            <?php
                                            $chk_age = 0;
                                            if ($chk_age == 1) {
                                                $checkbox_checked = "checked";
                                                $age_input_2 = 'style="display: none;"';
                                                $age_input_1 = '';
                                            } else {
                                                $checkbox_checked = "";
                                                $age_input_1 = 'style="display: none;"';
                                                $age_input_2 = '';
                                            }
                                            ?>
                                            <div class="col-md-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="chk_age" name="chk_age" <?= $checkbox_checked ?>>
                                                    <label class="form-check-label" for="chk_age">Estimate Age</label>
                                                </div>
                                            </div>

                                            <div class="col-md-6" id="age_input_1" <?= $age_input_1 ?>>
                                                <div class="form-group">
                                                    <label class="form-label">Age (in Year - Month)</label>
                                                    <div class="row g-2">
                                                        <div class="col-6">
                                                            <input class="form-control number"
                                                                name="input_age_year" id="input_age_year"
                                                                placeholder="Year" type="text" autocomplete="off">
                                                        </div>
                                                        <div class="col-6">
                                                            <input class="form-control number"
                                                                name="input_age_month" id="input_age_month"
                                                                placeholder="Month" type="text" autocomplete="off">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-4" id="age_input_2" <?= $age_input_2 ?>>
                                                <label class="form-label">Date of Birth</label>
                                                <input class="form-control" name="datepicker_dob" id="datepicker_dob"
                                                    type="date" />
                                            </div>
                                        </div>
                                        <hr />

                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label">Relation</label>
                                                    <select class="form-select" name="cbo_relation" id="cbo_relation">
                                                        <option value="W/o">W/o</option>
                                                        <option value="S/o">S/o</option>
                                                        <option value="D/o">D/o</option>
                                                        <option value="C/o">C/o</option>
                                                        <option value="M/o">M/o</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label">Relative Name</label>
                                                    <input class="form-control" name="input_relative_name"
                                                        id="input_relative_name" placeholder="Full Name" type="text"
                                                        onchange="onchange_relative_name()"
                                                        autocomplete="off" required>
                                                    <div class="invalid-feedback">Please enter relative name.</div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label">Blood Group</label>

                                                    <select class="form-select" name="input_blood_group" id="input_blood_group">
                                                        <?php foreach ($blood_group as $row) { ?>
	                                                            <option value="<?= $row->blood_group ?>" <?= strcasecmp((string) $row->blood_group, 'Not Define') === 0 ? 'selected' : '' ?>><?= $row->blood_group ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label">Address</label>
                                                    <input class="form-control" name="input_address"
                                                        placeholder="Address" type="text" autocomplete="on">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label">City</label>
                                                    <input class="form-control" name="input_city" id="input_city"
                                                        placeholder="City" type="text" autocomplete="on">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label">Pin/Zip Code</label>
                                                    <input class="form-control" name="input_zip" id="input_zip"
                                                        placeholder="Pin/Zip Code" type="text" autocomplete="on">
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label">District</label>
                                                    <input class="form-control" name="input_district"
                                                        id="input_district" placeholder="District" type="text"
                                                        autocomplete="on">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label">State</label>
                                                    <input class="form-control" name="input_state"
                                                        id="input_state" placeholder="State" type="text" autocomplete="on">
                                                </div>
                                            </div>
                                        </div>
                                        <hr />
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <button type="submit" id="RegisterPatient" class="btn btn-primary">Register Patient</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-md-4" id="search_result_update">

                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade show active" id="search" role="tabpanel" aria-labelledby="search-tab">
                            <form action="<?= base_url('billing/patient/search') ?>" method="post" role="form" class="form2 needs-validation" novalidate>
                                <?= csrf_field() ?>
                                <div class="input-group">
                                    <input class="form-control" type="text" id="txtsearch" name="txtsearch" placeholder="Search">
                                    <span class="input-group-btn">
                                        <button type="submit" class="btn btn-info btn-flat">Go!</button>
                                    </span>
                                </div>
                            </form>
                            <div id="search_loading" class="alert alert-info mt-2" style="display:none;">
                                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                <span id="search_loading_text">Searching...</span>
                            </div>
                            <div class="searchresult"></div>
                        </div>
                        <div class="tab-pane fade" id="lastopd" role="tabpanel" aria-labelledby="lastopd-tab">

                        </div>
                        <div class="tab-pane fade" id="abha" role="tabpanel" aria-labelledby="abha-tab">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6 class="card-title">ABHA Number Create and Verify</h6>
                                            <p class="text-muted">Create and verify your unique health identification number</p>
                                            
                                            <ul class="nav nav-tabs" id="abhaSubTabs" role="tablist">
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link active" id="abha-create-tab" data-bs-toggle="tab"
                                                        data-bs-target="#abha-create" type="button" role="tab" aria-controls="abha-create"
                                                        aria-selected="true">Create ABHA</button>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link" id="abha-verify-tab" data-bs-toggle="tab"
                                                        data-bs-target="#abha-verify" type="button" role="tab" aria-controls="abha-verify"
                                                        aria-selected="false">Verify ABHA</button>
                                                </li>
                                            </ul>
                                            
                                            <div class="tab-content pt-3" id="abhaSubTabsContent">
                                                <div class="tab-pane fade show active" id="abha-create" role="tabpanel" aria-labelledby="abha-create-tab">

                                                    <!-- Step progress indicator -->
                                                    <div class="abha-progress-wrap mt-3 mb-4">
                                                        <div class="abha-node active" id="abha_node_1">
                                                            <div class="abha-badge" id="abha_badge_1">1</div>
                                                            <div class="abha-node-label">Aadhaar &amp; Consent</div>
                                                        </div>
                                                        <div class="abha-node" id="abha_node_2">
                                                            <div class="abha-badge" id="abha_badge_2">2</div>
                                                            <div class="abha-node-label">OTP Verify</div>
                                                        </div>
                                                        <div class="abha-node" id="abha_node_3">
                                                            <div class="abha-badge" id="abha_badge_3">3</div>
                                                            <div class="abha-node-label">Mobile Confirm</div>
                                                        </div>
                                                        <div class="abha-node" id="abha_node_4">
                                                            <div class="abha-badge" id="abha_badge_4">4</div>
                                                            <div class="abha-node-label">ABHA Ready</div>
                                                        </div>
                                                    </div>

                                                    <!-- Step 1: Aadhaar entry + consent -->
                                                    <div id="abha_step_1" class="abha-panel">
                                                        <p class="text-muted small mb-3">Enter the patient's Aadhaar number. An OTP will be sent to the mobile linked with it.</p>
                                                        <div class="row g-3 align-items-end">
                                                            <div class="col-md-4">
                                                                <label class="form-label">Aadhaar Number <span class="text-danger">*</span></label>
                                                                <input type="text" class="form-control" id="abha_aadhaar_masked"
                                                                    placeholder="0000-0000-0000" autocomplete="off"
                                                                    data-inputmask='"mask": "9999-9999-9999"' data-mask>
                                                                <small class="text-muted">Mobile must be linked with Aadhaar for OTP.</small>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label">Mobile for ABHA <span class="text-danger">*</span></label>
                                                                <input type="text" class="form-control" id="abha_reg_mobile"
                                                                    placeholder="10-digit mobile" maxlength="10"
                                                                    inputmode="numeric" autocomplete="tel">
                                                                <small class="text-muted">Will be linked to the new ABHA.</small>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label">Authentication Via</label>
                                                                <select class="form-select" id="abha_auth_type">
                                                                    <option value="aadhaar_otp">OTP</option>
                                                                    <option value="biometric">Biometric</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="mt-3">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="abha_consent_chk">
                                                                <label class="form-check-label" for="abha_consent_chk">
                                                                    I confirm the patient has given consent to share their Aadhaar details with NHA/ABDM for ABHA number creation.
                                                                    <a href="#" data-bs-toggle="collapse" data-bs-target="#abha_full_consent" class="ms-1 small">(read full consent)</a>
                                                                </label>
                                                            </div>
                                                            <div class="collapse mt-2" id="abha_full_consent">
                                                                <div class="card card-body bg-light py-2 small text-muted" style="max-height:100px;overflow-y:auto;">
                                                                    I hereby declare that I am voluntarily sharing my Aadhaar number and demographic information issued by UIDAI with the National Health Authority (NHA) for the sole purpose of creating an ABHA number. I understand this number may be used for healthcare service purposes as notified by ABDM. My personal identifiable information (Name, Address, Age, DOB, Gender, Photo) may be made available to entities in the National Digital Health Ecosystem. I consent to use and disclosure of my health information to NDHE entities as permitted by law.
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div id="abha_step1_alert" class="mt-2"></div>
                                                        <div class="mt-3">
                                                            <button type="button" class="btn btn-primary" id="abha_get_otp_btn">
                                                                <span id="abha_get_otp_spinner" class="spinner-border spinner-border-sm me-1 d-none"></span>
                                                                Send OTP
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- Step 2: OTP verification -->
                                                    <div id="abha_step_2" class="abha-panel" style="display:none;">
                                                        <p class="text-muted small mb-3">Enter the OTP sent to the mobile number linked with Aadhaar.</p>
                                                        <div class="row g-3 align-items-end">
                                                            <div class="col-md-3">
                                                                <label class="form-label">One-Time Password <span class="text-danger">*</span></label>
                                                                <input type="text" class="form-control form-control-lg text-center letter-spacing-2"
                                                                    id="abha_otp_input" placeholder="— — — — — —"
                                                                    maxlength="6" autocomplete="one-time-code" inputmode="numeric">
                                                            </div>
                                                        </div>
                                                        <div id="abha_step2_alert" class="mt-2"></div>
                                                        <div class="mt-3 d-flex gap-2 flex-wrap">
                                                            <button type="button" class="btn btn-success" id="abha_verify_otp_btn">
                                                                <span id="abha_verify_otp_spinner" class="spinner-border spinner-border-sm me-1 d-none"></span>
                                                                Confirm OTP
                                                            </button>
                                                            <button type="button" class="btn btn-outline-secondary" id="abha_resend_otp_btn">Resend OTP</button>
                                                            <button type="button" class="btn btn-link text-secondary px-0" id="abha_back_step1_btn">&larr; Back</button>
                                                        </div>
                                                    </div>

                                                    <!-- Step 3: (unused — Aadhaar verify-otp returns ABHA directly) -->
                                                    <div id="abha_step_3" style="display:none;"></div>

                                                    <!-- Step 4: ABHA Ready -->
                                                    <div id="abha_step_4" class="abha-panel" style="display:none;">
                                                        <div id="abha_created_result"></div>
                                                        <div id="abha_step4_alert" class="mt-2"></div>
                                                    </div>

                                                </div>
                                                
                                                <div class="tab-pane fade" id="abha-verify" role="tabpanel" aria-labelledby="abha-verify-tab">
                                                    <p class="text-muted small mb-3">Enter the patient's mobile number registered with ABDM to retrieve their ABHA profile.</p>
                                                    <!-- Phase 1: Mobile input -->
                                                    <div id="link_step_1">
                                                        <div class="row g-3 align-items-end">
                                                            <div class="col-md-3">
                                                                <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
                                                                <input type="text" class="form-control" id="abha_link_mobile"
                                                                    placeholder="10-digit mobile" maxlength="10" inputmode="numeric" autocomplete="off">
                                                            </div>
                                                        </div>
                                                        <div id="link_step1_alert" class="mt-2"></div>
                                                        <div class="mt-3">
                                                            <button type="button" class="btn btn-primary" id="abha_link_send_otp_btn">
                                                                <span id="abha_link_send_spinner" class="spinner-border spinner-border-sm me-1 d-none"></span>
                                                                Send OTP
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <!-- Phase 2: OTP verify -->
                                                    <div id="link_step_2" style="display:none;" class="mt-3">
                                                        <div class="row g-3 align-items-end">
                                                            <div class="col-md-3">
                                                                <label class="form-label">Enter OTP <span class="text-danger">*</span></label>
                                                                <input type="text" class="form-control form-control-lg text-center fw-bold"
                                                                    id="abha_link_otp_input" maxlength="6" placeholder="6-digit OTP"
                                                                    inputmode="numeric" autocomplete="one-time-code">
                                                            </div>
                                                        </div>
                                                        <div id="link_step2_alert" class="mt-2"></div>
                                                        <div class="mt-3 d-flex gap-2">
                                                            <button type="button" class="btn btn-success" id="abha_link_verify_otp_btn">
                                                                <span id="abha_link_verify_spinner" class="spinner-border spinner-border-sm me-1 d-none"></span>
                                                                Verify OTP
                                                            </button>
                                                            <button type="button" class="btn btn-outline-secondary" id="abha_link_resend_btn">Resend OTP</button>
                                                        </div>
                                                    </div>
                                                    <!-- Result -->
                                                    <div id="link_result" class="mt-3"></div>
                                                    <!-- Bridge test (keep) -->
                                                    <div class="mt-4 border-top pt-3">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="abha_bridge_test_btn">
                                                            <span id="abha_bridge_test_spinner" class="spinner-border spinner-border-sm me-1 d-none"></span>
                                                            Test Middle Server
                                                        </button>
                                                        <div id="abha_bridge_test_result" class="mt-2"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!-- ABHA REGISTER TAB PANE                                       -->
                <!-- ============================================================ -->
                <div class="tab-pane fade" id="abha-register" role="tabpanel" aria-labelledby="abhareg-tab">
                  <div class="row">
                    <div class="col-12">
                      <h6 class="fw-bold mb-3"><i class="bi bi-person-check-fill me-2 text-primary"></i>ABHA-Based Patient Registration</h6>

                      <!-- Method selector cards -->
                      <div id="abhareg_methods" class="row g-2 mb-3">
                        <div class="col-6 col-md-3">
                          <div class="card border abhareg-method-card text-center py-3" data-method="number" role="button" tabindex="0" onclick="abhaRegSelectMethod('number')" style="cursor:pointer">
                            <div class="fs-2 mb-1">🆔</div>
                            <div class="fw-semibold small">By ABHA Number</div>
                            <div class="text-muted" style="font-size:11px">14-digit or @abdm address</div>
                          </div>
                        </div>
                        <div class="col-6 col-md-3">
                          <div class="card border abhareg-method-card text-center py-3" data-method="qr" role="button" tabindex="0" onclick="abhaRegSelectMethod('qr')" style="cursor:pointer">
                            <div class="fs-2 mb-1">📷</div>
                            <div class="fw-semibold small">Scan ABHA QR</div>
                            <div class="text-muted" style="font-size:11px">Camera scan patient's QR</div>
                          </div>
                        </div>
                        <div class="col-6 col-md-3">
                          <div class="card border abhareg-method-card text-center py-3" data-method="facility" role="button" tabindex="0" onclick="abhaRegSelectMethod('facility')" style="cursor:pointer">
                            <div class="fs-2 mb-1">🏥</div>
                            <div class="fw-semibold small">Scan Facility QR</div>
                            <div class="text-muted" style="font-size:11px">Patient scans hospital QR</div>
                          </div>
                        </div>
                        <div class="col-6 col-md-3">
                          <div class="card border abhareg-method-card text-center py-3" data-method="mobile" role="button" tabindex="0" onclick="abhaRegSelectMethod('mobile')" style="cursor:pointer">
                            <div class="fs-2 mb-1">📱</div>
                            <div class="fw-semibold small">By Mobile OTP</div>
                            <div class="text-muted" style="font-size:11px">OTP to ABHA-linked mobile</div>
                          </div>
                        </div>
                      </div>

                      <!-- Active method banner -->
                      <div id="abhareg_active_banner" class="alert alert-primary d-flex align-items-center py-2 d-none mb-3">
                        <span id="abhareg_banner_icon" class="me-2 fs-5"></span>
                        <div>
                          <strong id="abhareg_banner_title"></strong>
                          <span id="abhareg_banner_sub" class="ms-2 small text-primary-emphasis"></span>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" onclick="abhaRegReset()">↩ Change Method</button>
                      </div>

                      <!-- Panel: Method 1 - By ABHA Number (also populated after QR scan) -->
                      <div id="abhareg_panel_number" class="abhareg-panel d-none">
                        <div id="abhareg_num_stepA">
                          <p class="text-muted small mb-2">Enter patient's 14-digit ABHA number or ABHA address (e.g., john@abdm).</p>
                          <div class="row g-2 align-items-end">
                            <div class="col-md-5">
                              <label class="form-label">ABHA Number / Address <span class="text-danger">*</span></label>
                              <div class="input-group">
                                <input type="text" id="abhareg_abha_input" class="form-control" placeholder="14-digit number or user@abdm" autocomplete="off">
                                <button type="button" id="abhareg_validate_btn" class="btn btn-outline-secondary">
                                  <span id="abhareg_validate_spinner" class="spinner-border spinner-border-sm me-1 d-none"></span>Validate
                                </button>
                              </div>
                              <div id="abhareg_abha_status" class="mt-1"></div>
                            </div>
                          </div>
                          <div id="abhareg_num_stepA_alert" class="mt-2"></div>
                          <div class="mt-3">
                            <button type="button" id="abhareg_goto_stepB" class="btn btn-primary d-none">
                              Continue — Enter Mobile <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                          </div>
                        </div>
                        <div id="abhareg_num_stepB" class="d-none mt-3">
                          <p class="text-muted small mb-2">Enter the mobile number registered with this ABHA. OTP will be sent to it.</p>
                          <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                              <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
                              <input type="text" id="abhareg_num_mobile" class="form-control" placeholder="10-digit mobile" maxlength="10" inputmode="numeric" autocomplete="off">
                            </div>
                          </div>
                          <div id="abhareg_num_stepB_alert" class="mt-2"></div>
                          <div class="mt-3 d-flex gap-2">
                            <button type="button" id="abhareg_num_send_otp_btn" class="btn btn-primary">
                              <span id="abhareg_num_send_spinner" class="spinner-border spinner-border-sm me-1 d-none"></span>Send OTP
                            </button>
                            <button type="button" id="abhareg_back_to_stepA" class="btn btn-link text-secondary">← Back</button>
                          </div>
                        </div>
                        <div id="abhareg_num_stepC" class="d-none mt-3">
                          <p class="text-muted small mb-2">Enter the 6-digit OTP received on the patient's mobile.</p>
                          <div class="row g-2">
                            <div class="col-md-3">
                              <label class="form-label">One-Time Password <span class="text-danger">*</span></label>
                              <input type="text" id="abhareg_num_otp_input" class="form-control form-control-lg text-center fw-bold" maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="— — — — — —">
                            </div>
                          </div>
                          <div id="abhareg_num_stepC_alert" class="mt-2"></div>
                          <div class="mt-3 d-flex gap-2 flex-wrap">
                            <button type="button" id="abhareg_num_verify_otp_btn" class="btn btn-success">
                              <span id="abhareg_num_verify_spinner" class="spinner-border spinner-border-sm me-1 d-none"></span>Verify OTP
                            </button>
                            <button type="button" id="abhareg_num_resend_btn" class="btn btn-outline-secondary">Resend OTP</button>
                            <button type="button" id="abhareg_back_to_stepB" class="btn btn-link text-secondary">← Back</button>
                          </div>
                        </div>
                      </div>

                      <!-- Panel: Method 2 - Scan QR -->
                      <div id="abhareg_panel_qr" class="abhareg-panel d-none">
                        <p class="text-muted small mb-2">Point camera at patient's ABHA QR code (from PHR app or printed card).</p>
                        <div id="abhareg_qr_reader" style="width:300px;max-width:100%;"></div>
                        <div id="abhareg_qr_result" class="mt-2"></div>
                        <div class="mt-2">
                          <button type="button" id="abhareg_qr_stop_btn" class="btn btn-outline-secondary btn-sm d-none">
                            <i class="bi bi-camera-video-off me-1"></i>Stop Camera
                          </button>
                        </div>
                      </div>

                      <!-- Panel: Method 3 - Facility QR (informational) -->
                      <div id="abhareg_panel_facility" class="abhareg-panel d-none">
                        <div class="alert alert-info">
                          <h6 class="fw-bold"><i class="bi bi-qr-code me-2"></i>Scan &amp; Share — Health Facility QR</h6>
                          <p class="mb-2">In this method, the <strong>patient</strong> scans the hospital's facility QR code using their ABHA / PHR app.</p>
                          <ol class="mb-2">
                            <li>Patient opens their <strong>ABHA app</strong> (or PHR app)</li>
                            <li>Selects "Scan Facility QR"</li>
                            <li>Scans the hospital's QR code displayed at the OPD counter</li>
                            <li>Grants consent — profile is sent to the HMS automatically</li>
                          </ol>
                          <p class="mb-0 text-muted small">The hospital's facility QR code is provided by NHA/ABDM based on your Health Facility Registry (HFR) ID.</p>
                        </div>
                        <div class="text-center mt-3">
                          <a href="https://abha.abdm.gov.in/abha/v3/" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Open ABHA Portal
                          </a>
                        </div>
                      </div>

                      <!-- Panel: Method 4 - Mobile OTP only -->
                      <div id="abhareg_panel_mobile" class="abhareg-panel d-none">
                        <div id="abhareg_mob_step1">
                          <p class="text-muted small mb-2">Enter the patient's mobile number linked to ABDM/ABHA. OTP will be sent for verification.</p>
                          <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                              <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
                              <input type="text" id="abhareg_mob_mobile" class="form-control" placeholder="10-digit mobile" maxlength="10" inputmode="numeric" autocomplete="off">
                            </div>
                          </div>
                          <div id="abhareg_mob_step1_alert" class="mt-2"></div>
                          <div class="mt-3">
                            <button type="button" id="abhareg_mob_send_btn" class="btn btn-primary">
                              <span id="abhareg_mob_send_spinner" class="spinner-border spinner-border-sm me-1 d-none"></span>Send OTP
                            </button>
                          </div>
                        </div>
                        <div id="abhareg_mob_step2" class="d-none mt-3">
                          <p class="text-muted small mb-2">Enter the 6-digit OTP sent to the patient's mobile.</p>
                          <div class="row g-2">
                            <div class="col-md-3">
                              <label class="form-label">One-Time Password <span class="text-danger">*</span></label>
                              <input type="text" id="abhareg_mob_otp_input" class="form-control form-control-lg text-center fw-bold" maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="— — — — — —">
                            </div>
                          </div>
                          <div id="abhareg_mob_step2_alert" class="mt-2"></div>
                          <div class="mt-3 d-flex gap-2 flex-wrap">
                            <button type="button" id="abhareg_mob_verify_btn" class="btn btn-success">
                              <span id="abhareg_mob_verify_spinner" class="spinner-border spinner-border-sm me-1 d-none"></span>Verify OTP
                            </button>
                            <button type="button" id="abhareg_mob_resend_btn" class="btn btn-outline-secondary">Resend OTP</button>
                            <button type="button" id="abhareg_mob_back_btn" class="btn btn-link text-secondary">← Back</button>
                          </div>
                        </div>
                      </div>

                      <!-- Common result card (all methods) -->
                      <div id="abhareg_result" class="d-none mt-3">
                        <div class="card border-success shadow-sm">
                          <div class="card-header bg-success text-white d-flex align-items-center gap-2 py-2">
                            <i class="bi bi-check-circle-fill"></i>
                            <span id="abhareg_result_status_badge" class="fw-semibold"></span>
                          </div>
                          <div class="card-body">
                            <div class="d-flex align-items-start gap-3">
                              <img id="abhareg_result_photo" src="" alt="" class="rounded-circle d-none" style="width:64px;height:64px;object-fit:cover;border:3px solid #198754;flex-shrink:0">
                              <div id="abhareg_result_photo_ph" class="rounded-circle d-flex align-items-center justify-content-center bg-success-subtle" style="width:64px;height:64px;font-size:30px;flex-shrink:0">👤</div>
                              <div class="flex-grow-1">
                                <div class="fw-bold fs-5 mb-1" id="abhareg_result_name">—</div>
                                <div class="font-monospace fw-semibold text-primary mb-1" id="abhareg_result_abha"></div>
                                <div class="text-muted small mb-1" id="abhareg_result_details"></div>
                                <div id="abhareg_result_hms"></div>
                              </div>
                            </div>
                            <div class="mt-3 d-flex gap-2 flex-wrap">
                              <a id="abhareg_result_profile_btn" href="#" class="btn btn-success btn-sm d-none">
                                <i class="bi bi-person-lines-fill me-1"></i>View Patient Profile
                              </a>
                              <a id="abhareg_result_card_btn" href="#" target="_blank" class="btn btn-outline-primary btn-sm d-none">
                                <i class="bi bi-card-image me-1"></i>Print ABHA Card
                              </a>
                              <button type="button" class="btn btn-outline-secondary btn-sm" onclick="abhaRegReset()">
                                <i class="bi bi-arrow-repeat me-1"></i>Register Another
                              </button>
                            </div>
                          </div>
                        </div>
                      </div>

                    </div>
                  </div>
                </div>
                <!-- /.ABHA REGISTER TAB PANE -->

            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<script>
    $(document).ready(function() {
        $('form.form1').on('submit', function(form) {
            $("#RegisterPatient").prop("disabled", true);
            form.preventDefault();

            $.post('<?= base_url('billing/patient/create') ?>', $('form.form1').serialize(), function(data) {
                if (data.insertid == 0) {
                    notify('error', 'Please Attention', data.error_text);
                    $("#RegisterPatient").prop("disabled", false);
                } else {
                    load_form('<?= base_url('billing/patient/person_record') ?>/' + data.insertid, 'Patient Record');
                }
            }, 'json');
        });

        $('form.form2').on('submit', function(form) {
            form.preventDefault();
            var searchBtn = $('form.form2 button[type="submit"]');
            var loadingDiv = $('#search_loading');
            
            // Show loading indicator and disable button
            loadingDiv.show();
            searchBtn.prop('disabled', true);
            searchBtn.text('Searching...');
            
            $.post('<?= base_url('billing/patient/search') ?>', $('form.form2').serialize(), function(data) {
                $('div.searchresult').html(data);
                
                // Hide loading indicator and re-enable button
                loadingDiv.hide();
                searchBtn.prop('disabled', false);
                searchBtn.text('Go!');
            });
        });

        /* ===================== ABHA CREATION WIZARD ===================== */
        (function () {
            var txnId  = null;   // transaction id from ABDM API
            var mobile = '';     // mobile to register with the new ABHA
            var csrfToken = function() { return $('input[name="<?= csrf_token() ?>"]').first().val(); };

            function abhaStep(step) {
                $('#abha_step_1,#abha_step_2,#abha_step_3,#abha_step_4').hide();
                $('#abha_step_' + step).show();
                // update progress nodes
                for (var i = 1; i <= 4; i++) {
                    var $n = $('#abha_node_' + i);
                    var $b = $('#abha_badge_' + i);
                    $n.removeClass('active done');
                    if (i < step) {
                        $n.addClass('done');
                        $b.html('<i class="fas fa-check fa-xs"></i>');
                    } else if (i === step) {
                        $n.addClass('active');
                        $b.text(i);
                    } else {
                        $b.text(i);
                    }
                }
            }

            function showAlert(containerId, type, msg) {
                $('#' + containerId).html('<div class="alert alert-' + type + ' py-2 mt-2">' + msg + '</div>');
            }

            // Apply inputmask to Aadhaar field
            $('#abha_aadhaar_masked').inputmask('9999-9999-9999');

            // Step 1 → Get OTP
            $('#abha_get_otp_btn').on('click', function() {
                var aadhaar = $('#abha_aadhaar_masked').val().replace(/\D/g, '');
                if (aadhaar.length !== 12) {
                    showAlert('abha_step1_alert', 'danger', 'Please enter a valid 12-digit Aadhaar number.');
                    return;
                }
                var mobileVal = $('#abha_reg_mobile').val().trim();
                if (!/^\d{10}$/.test(mobileVal)) {
                    showAlert('abha_step1_alert', 'danger', 'Please enter a valid 10-digit mobile number for ABHA.');
                    return;
                }
                if (!$('#abha_consent_chk').is(':checked')) {
                    showAlert('abha_step1_alert', 'warning', 'You must agree to the Terms and Conditions to proceed.');
                    return;
                }
                mobile = mobileVal;
                $('#abha_step1_alert').html('');
                $('#abha_get_otp_spinner').removeClass('d-none');
                $('#abha_get_otp_btn').prop('disabled', true);

                $.post('<?= base_url('abha/create/initiate') ?>', {
                    aadhaar: aadhaar,
                    auth_type: $('#abha_auth_type').val(),
                    '<?= csrf_token() ?>': csrfToken()
                }, function(resp) {
                    $('#abha_get_otp_spinner').addClass('d-none');
                    $('#abha_get_otp_btn').prop('disabled', false);
                    if (resp && resp.ok == 1) {
                        txnId = resp.txn_id || null;
                        abhaStep(2);
                    } else {
                        showAlert('abha_step1_alert', 'danger', (resp && resp.error_text) ? resp.error_text : 'Failed to send OTP. Please try again.');
                    }
                }, 'json').fail(function() {
                    $('#abha_get_otp_spinner').addClass('d-none');
                    $('#abha_get_otp_btn').prop('disabled', false);
                    showAlert('abha_step1_alert', 'danger', 'Server error. Please try again.');
                });
            });

            // Back to step 1
            $('#abha_back_step1_btn').on('click', function() { abhaStep(1); });

            // Resend OTP
            $('#abha_resend_otp_btn').on('click', function() {
                $('#abha_get_otp_btn').trigger('click');
            });

            // Step 2 → Verify OTP
            $('#abha_verify_otp_btn').on('click', function() {
                var otp = $('#abha_otp_input').val().trim();
                if (!/^\d{6}$/.test(otp)) {
                    showAlert('abha_step2_alert', 'danger', 'Please enter the 6-digit OTP.');
                    return;
                }
                $('#abha_step2_alert').html('');
                $('#abha_verify_otp_spinner').removeClass('d-none');
                $('#abha_verify_otp_btn').prop('disabled', true);

                $.post('<?= base_url('abha/create/verify_otp') ?>', {
                    otp: otp,
                    txn_id: txnId,
                    mobile: mobile,
                    '<?= csrf_token() ?>': csrfToken()
                }, function(resp) {
                    $('#abha_verify_otp_spinner').addClass('d-none');
                    $('#abha_verify_otp_btn').prop('disabled', false);
                    if (resp && resp.ok == 1) {
                        txnId = resp.txn_id || txnId;
                        var abhaNum  = resp.abha_number || '';
                        var abhaDisp = abhaNum.replace(/(\d{2})(\d{4})(\d{4})(\d{4})/, '$1-$2-$3-$4');
                        var abhaRaw  = abhaNum.replace(/\D/g, '');
                        var patMsg   = '';
                        if (resp.patient_id > 0) {
                            patMsg = resp.is_new_patient
                                ? '<span class="badge bg-success ms-1">New Patient Registered</span>'
                                : '<span class="badge bg-info ms-1">Patient Found</span>';
                            patMsg += ' <small class="text-muted">HMS ID: <strong>' + (resp.p_code || '') + '</strong></small>';
                        }
                        var cardBtn = abhaRaw.length === 14
                            ? ' <a href="<?= base_url('abha/card/') ?>' + abhaRaw + '" target="_blank" class="btn btn-sm btn-outline-primary mt-1"><i class="bi bi-card-image me-1"></i>View/Print ABHA Card</a>'
                            : '';
                        $('#abha_created_result').html(
                            '<div class="alert alert-success">' +
                            '<i class="bi bi-check-circle-fill me-2"></i>' +
                            '<strong>ABHA Number Created!</strong><br>' +
                            (abhaDisp ? 'ABHA: <strong>' + abhaDisp + '</strong><br>' : '') +
                            (resp.name ? 'Name: ' + resp.name + '<br>' : '') +
                            patMsg +
                            cardBtn +
                            '</div>'
                        );
                        abhaStep(4);
                    } else {
                        showAlert('abha_step2_alert', 'danger', (resp && resp.error_text) ? resp.error_text : 'Invalid OTP. Please try again.');
                    }
                }, 'json').fail(function() {
                    $('#abha_verify_otp_spinner').addClass('d-none');
                    $('#abha_verify_otp_btn').prop('disabled', false);
                    showAlert('abha_step2_alert', 'danger', 'Server error. Please try again.');
                });
            });

        })();
        /* =============================================================== */

        /* ===== LINK ABHA (Verify tab) — Mobile OTP flow ===== */
        (function() {
            var linkTxnId = null;

            function linkAlert(id, type, msg) {
                $('#' + id).html(msg ? '<div class="alert alert-' + type + ' py-2 mt-2">' + msg + '</div>' : '');
            }

            $('#abha_link_send_otp_btn').on('click', function() {
                var mobile = $('#abha_link_mobile').val().trim();
                if (!/^\d{10}$/.test(mobile)) {
                    linkAlert('link_step1_alert', 'danger', 'Please enter a valid 10-digit mobile number.');
                    return;
                }
                linkAlert('link_step1_alert', '', '');
                $('#abha_link_send_spinner').removeClass('d-none');
                $('#abha_link_send_otp_btn').prop('disabled', true);

                $.post('<?= base_url('abha/create/communication') ?>', {
                    mobile: mobile,
                    '<?= csrf_token() ?>': csrfToken()
                }, function(resp) {
                    $('#abha_link_send_spinner').addClass('d-none');
                    $('#abha_link_send_otp_btn').prop('disabled', false);
                    if (resp && resp.ok == 1) {
                        linkTxnId = resp.txn_id || null;
                        $('#link_step_2').show();
                        linkAlert('link_step1_alert', 'success', 'OTP sent to mobile ending ' + mobile.slice(-4) + '.');
                    } else {
                        linkAlert('link_step1_alert', 'danger', (resp && resp.error_text) ? resp.error_text : 'Failed to send OTP.');
                    }
                }, 'json').fail(function() {
                    $('#abha_link_send_spinner').addClass('d-none');
                    $('#abha_link_send_otp_btn').prop('disabled', false);
                    linkAlert('link_step1_alert', 'danger', 'Server error. Please try again.');
                });
            });

            $('#abha_link_resend_btn').on('click', function() {
                $('#abha_link_otp_input').val('');
                $('#link_step_2').hide();
                linkAlert('link_step2_alert', '', '');
                $('#abha_link_send_otp_btn').trigger('click');
            });

            $('#abha_link_verify_otp_btn').on('click', function() {
                var otp = $('#abha_link_otp_input').val().trim();
                if (!/^\d{6}$/.test(otp)) {
                    linkAlert('link_step2_alert', 'danger', 'Please enter the 6-digit OTP.');
                    return;
                }
                linkAlert('link_step2_alert', '', '');
                $('#abha_link_verify_spinner').removeClass('d-none');
                $('#abha_link_verify_otp_btn').prop('disabled', true);

                $.post('<?= base_url('abha/create/verify_comm_otp') ?>', {
                    otp: otp,
                    txn_id: linkTxnId,
                    '<?= csrf_token() ?>': csrfToken()
                }, function(resp) {
                    $('#abha_link_verify_spinner').addClass('d-none');
                    $('#abha_link_verify_otp_btn').prop('disabled', false);
                    if (resp && resp.ok == 1) {
                        var abhaNum  = resp.abha_number || '';
                        var abhaDisp = abhaNum.replace(/(\d{2})(\d{4})(\d{4})(\d{4})/, '$1-$2-$3-$4');
                        var abhaRaw  = abhaNum.replace(/\D/g, '');
                        var patMsg   = '';
                        if (resp.patient_id > 0) {
                            patMsg = resp.is_new_patient
                                ? '<span class="badge bg-success ms-1">New Patient Registered</span>'
                                : '<span class="badge bg-info ms-1">Patient Found</span>';
                            patMsg += ' <small class="text-muted">HMS ID: <strong>' + (resp.p_code || '') + '</strong></small>';
                        }
                        var cardBtn = abhaRaw.length === 14
                            ? '<br><a href="<?= base_url('abha/card/') ?>' + abhaRaw + '" target="_blank" class="btn btn-sm btn-outline-primary mt-2"><i class="bi bi-card-image me-1"></i>View/Print ABHA Card</a>'
                            : '';
                        $('#link_result').html(
                            '<div class="alert alert-success">' +
                            '<i class="bi bi-check-circle-fill me-2"></i>' +
                            '<strong>ABHA Found!</strong><br>' +
                            (abhaDisp ? 'ABHA: <strong>' + abhaDisp + '</strong><br>' : '') +
                            (resp.name   ? 'Name: '   + resp.name   + '<br>' : '') +
                            (resp.gender ? 'Gender: ' + resp.gender + '<br>' : '') +
                            (resp.dob    ? 'DOB: '    + resp.dob    + '<br>' : '') +
                            patMsg + cardBtn +
                            '</div>'
                        );
                    } else {
                        linkAlert('link_step2_alert', 'danger', (resp && resp.error_text) ? resp.error_text : 'OTP verification failed.');
                    }
                }, 'json').fail(function() {
                    $('#abha_link_verify_spinner').addClass('d-none');
                    $('#abha_link_verify_otp_btn').prop('disabled', false);
                    linkAlert('link_step2_alert', 'danger', 'Server error. Please try again.');
                });
            });
        })();
        /* =============================================================== */

        $('#abha_bridge_test_btn').on('click', function() {
            var abhaId = '12345678901234';

            $('#abha_bridge_test_result').html('');
            $('#abha_bridge_test_spinner').removeClass('d-none');
            $('#abha_bridge_test_btn').prop('disabled', true);

            $.post('<?= base_url('AbdmGateway/bridge_test_event') ?>', {
                abha_id: abhaId,
                '<?= csrf_token() ?>': $('input[name="<?= csrf_token() ?>"]').first().val()
            }, function(resp) {
                $('#abha_bridge_test_spinner').addClass('d-none');
                $('#abha_bridge_test_btn').prop('disabled', false);

                if (resp && resp.ok == 1) {
                    var html = '<div class="alert alert-success py-2">'
                        + '<strong>' + (resp.message || 'Bridge test success') + '</strong><br>'
                        + 'Connector: <code>' + (resp.connector || '-') + '</code><br>'
                        + 'Bridge URL: <code>' + (resp.bridge_url || '-') + '</code><br>'
                        + 'Queue ID: <code>' + (resp.queue_id || '-') + '</code>'
                        + '</div>';
                    $('#abha_bridge_test_result').html(html);
                } else {
                    $('#abha_bridge_test_result').html('<div class="alert alert-danger py-2">' + ((resp && resp.error_text) ? resp.error_text : 'Bridge test failed') + '</div>');
                }
            }, 'json').fail(function(xhr) {
                $('#abha_bridge_test_spinner').addClass('d-none');
                $('#abha_bridge_test_btn').prop('disabled', false);
                var msg = 'Bridge test failed due to server error.';
                if (xhr && xhr.responseJSON && xhr.responseJSON.error_text) {
                    msg = xhr.responseJSON.error_text;
                }
                $('#abha_bridge_test_result').html('<div class="alert alert-danger py-2">' + msg + '</div>');
            });
        });

        /* ===================== ABHA REGISTRATION WIZARD ===================== */
        (function() {
            var regTxnId     = null;
            var html5QrCode  = null;
            var csrf         = function() { return $('input[name="<?= csrf_token() ?>"]').first().val(); };

            var methodMeta = {
                number:   { icon: '🆔', title: 'By ABHA Number/Address', sub: 'Validate then OTP verify' },
                qr:       { icon: '📷', title: 'Scan Patient ABHA QR',   sub: 'Camera → ABHA extracted → OTP verify' },
                facility: { icon: '🏥', title: 'Scan Facility QR',       sub: 'Patient scans via ABHA app' },
                mobile:   { icon: '📱', title: 'By Mobile OTP',          sub: 'OTP to ABHA-linked mobile' }
            };

            window.abhaRegSelectMethod = function(method) {
                regTxnId = null;
                $('.abhareg-method-card').removeClass('border-primary bg-primary-subtle shadow-sm');
                $('[data-method="' + method + '"]').addClass('border-primary bg-primary-subtle shadow-sm');
                var m = methodMeta[method];
                $('#abhareg_banner_icon').text(m.icon);
                $('#abhareg_banner_title').text(m.title);
                $('#abhareg_banner_sub').text(m.sub);
                $('#abhareg_active_banner').removeClass('d-none');
                $('.abhareg-panel').addClass('d-none');
                $('#abhareg_panel_' + method).removeClass('d-none');
                $('#abhareg_result').addClass('d-none');
                if (method === 'qr') { initQrScanner(); }
                else { stopQrScanner(); }
            };

            window.abhaRegReset = function() {
                regTxnId = null;
                stopQrScanner();
                $('.abhareg-method-card').removeClass('border-primary bg-primary-subtle shadow-sm');
                $('#abhareg_active_banner').addClass('d-none');
                $('.abhareg-panel').addClass('d-none');
                $('#abhareg_result').addClass('d-none');
                $('#abhareg_abha_input').val('');
                $('#abhareg_abha_status,#abhareg_num_stepA_alert,#abhareg_num_stepB_alert,#abhareg_num_stepC_alert').html('');
                $('#abhareg_num_mobile,#abhareg_num_otp_input').val('');
                $('#abhareg_num_stepB,#abhareg_num_stepC').addClass('d-none');
                $('#abhareg_num_stepA').removeClass('d-none');
                $('#abhareg_goto_stepB').addClass('d-none');
                $('#abhareg_mob_mobile,#abhareg_mob_otp_input').val('');
                $('#abhareg_mob_step2').addClass('d-none');
                $('#abhareg_mob_step1_alert,#abhareg_mob_step2_alert').html('');
            };

            function regAlert(id, type, msg) {
                $('#' + id).html(msg ? '<div class="alert alert-' + type + ' py-2">' + msg + '</div>' : '');
            }

            function showRegResult(resp) {
                var abhaNum  = resp.abha_number || '';
                var abhaDisp = abhaNum.replace(/(\d{2})(\d{4})(\d{4})(\d{4})/, '$1-$2-$3-$4');
                var abhaRaw  = abhaNum.replace(/\D/g, '');
                var isNew    = resp.is_new_patient;
                var patId    = resp.patient_id || 0;
                var pCode    = resp.p_code || '';
                var badge    = isNew
                    ? '<span class="badge bg-success me-2">New Patient Registered</span>'
                    : '<span class="badge bg-info text-dark me-2">Returning Patient Found</span>';
                $('#abhareg_result_status_badge').html(badge + 'Identity Verified via ABHA');
                $('#abhareg_result_name').text(resp.name || '—');
                $('#abhareg_result_abha').text(abhaDisp || '');
                var details = [];
                if (resp.gender) details.push(resp.gender==='M'||resp.gender==='1'?'Male':(resp.gender==='F'||resp.gender==='2'?'Female':resp.gender));
                if (resp.dob)    details.push('DOB: ' + resp.dob);
                if (resp.mobile) details.push('Mobile: ' + String(resp.mobile).replace(/(\d{6})(\d{4})/, '******$2'));
                $('#abhareg_result_details').text(details.join(' | '));
                $('#abhareg_result_hms').html(pCode ? '<small class="text-muted">HMS ID: <strong>' + pCode + '</strong></small>' : '');
                if (resp.photo) {
                    var src = String(resp.photo).startsWith('data:') ? resp.photo : 'data:image/jpeg;base64,' + resp.photo;
                    $('#abhareg_result_photo').attr('src', src).removeClass('d-none');
                    $('#abhareg_result_photo_ph').addClass('d-none');
                } else {
                    $('#abhareg_result_photo').addClass('d-none');
                    $('#abhareg_result_photo_ph').removeClass('d-none');
                }
                if (patId > 0) {
                    $('#abhareg_result_profile_btn').attr('href', '<?= base_url('billing/patient/person_record') ?>/' + patId).removeClass('d-none');
                }
                if (abhaRaw.length === 14) {
                    $('#abhareg_result_card_btn').attr('href', '<?= base_url('abha/card/') ?>' + abhaRaw).removeClass('d-none');
                }
                $('#abhareg_result').removeClass('d-none');
                $('html,body').animate({ scrollTop: $('#abhareg_result').offset().top - 80 }, 400);
            }

            // --- Method 1: Validate ABHA ---
            $('#abhareg_validate_btn').on('click', function() {
                var abha = $('#abhareg_abha_input').val().trim();
                if (!abha) { regAlert('abhareg_num_stepA_alert', 'warning', 'Please enter ABHA number or address.'); return; }
                regAlert('abhareg_num_stepA_alert', '', '');
                $('#abhareg_abha_status').html('');
                $('#abhareg_goto_stepB').addClass('d-none');
                $('#abhareg_validate_spinner').removeClass('d-none');
                $('#abhareg_validate_btn').prop('disabled', true);
                $.post('<?= base_url('abha/register/validate') ?>', {
                    abha_id: abha,
                    '<?= csrf_token() ?>': csrf()
                }, function(resp) {
                    $('#abhareg_validate_spinner').addClass('d-none');
                    $('#abhareg_validate_btn').prop('disabled', false);
                    if (resp && resp.ok == 1 && resp.status === 'VALID') {
                        $('#abhareg_abha_status').html('<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>VALID</span>');
                        $('#abhareg_goto_stepB').removeClass('d-none');
                    } else {
                        var msg = (resp && resp.error_text) ? resp.error_text : (resp && resp.status ? 'Status: ' + resp.status : 'ABHA not found or invalid.');
                        regAlert('abhareg_num_stepA_alert', 'danger', msg);
                        $('#abhareg_abha_status').html('<span class="badge bg-danger">INVALID</span>');
                    }
                }, 'json').fail(function() {
                    $('#abhareg_validate_spinner').addClass('d-none');
                    $('#abhareg_validate_btn').prop('disabled', false);
                    regAlert('abhareg_num_stepA_alert', 'danger', 'Server error during validation.');
                });
            });

            $('#abhareg_goto_stepB').on('click', function() {
                $('#abhareg_num_stepA').addClass('d-none');
                $('#abhareg_num_stepB').removeClass('d-none');
                $('#abhareg_num_mobile').focus();
            });
            $('#abhareg_back_to_stepA').on('click', function() {
                $('#abhareg_num_stepB').addClass('d-none');
                $('#abhareg_num_stepA').removeClass('d-none');
            });
            $('#abhareg_back_to_stepB').on('click', function() {
                $('#abhareg_num_stepC').addClass('d-none');
                $('#abhareg_num_stepB').removeClass('d-none');
            });

            // --- Shared OTP functions ---
            function sendOtpToMobile(mobile, alertId, spinId, btnId, onSuccess) {
                if (!/^\d{10}$/.test(mobile)) { regAlert(alertId, 'warning', 'Please enter a valid 10-digit mobile number.'); return; }
                regAlert(alertId, '', '');
                $('#' + spinId).removeClass('d-none');
                $('#' + btnId).prop('disabled', true);
                $.post('<?= base_url('abha/create/communication') ?>', {
                    mobile: mobile, '<?= csrf_token() ?>': csrf()
                }, function(resp) {
                    $('#' + spinId).addClass('d-none');
                    $('#' + btnId).prop('disabled', false);
                    if (resp && resp.ok == 1) {
                        regTxnId = resp.txn_id || null;
                        onSuccess(mobile);
                    } else {
                        regAlert(alertId, 'danger', (resp && resp.error_text) ? resp.error_text : 'Failed to send OTP.');
                    }
                }, 'json').fail(function() {
                    $('#' + spinId).addClass('d-none');
                    $('#' + btnId).prop('disabled', false);
                    regAlert(alertId, 'danger', 'Server error. Please try again.');
                });
            }

            function verifyOtpFromMobile(otp, alertId, spinId, btnId) {
                if (!/^\d{6}$/.test(otp)) { regAlert(alertId, 'warning', 'Please enter the 6-digit OTP.'); return; }
                regAlert(alertId, '', '');
                $('#' + spinId).removeClass('d-none');
                $('#' + btnId).prop('disabled', true);
                $.post('<?= base_url('abha/create/verify_comm_otp') ?>', {
                    otp: otp, txn_id: regTxnId, '<?= csrf_token() ?>': csrf()
                }, function(resp) {
                    $('#' + spinId).addClass('d-none');
                    $('#' + btnId).prop('disabled', false);
                    if (resp && resp.ok == 1) {
                        showRegResult(resp);
                    } else {
                        regAlert(alertId, 'danger', (resp && resp.error_text) ? resp.error_text : 'OTP verification failed.');
                    }
                }, 'json').fail(function() {
                    $('#' + spinId).addClass('d-none');
                    $('#' + btnId).prop('disabled', false);
                    regAlert(alertId, 'danger', 'Server error. Please try again.');
                });
            }

            // Method 1 Send/Verify
            $('#abhareg_num_send_otp_btn').on('click', function() {
                sendOtpToMobile($('#abhareg_num_mobile').val().trim(), 'abhareg_num_stepB_alert', 'abhareg_num_send_spinner', 'abhareg_num_send_otp_btn', function() {
                    $('#abhareg_num_stepB').addClass('d-none');
                    $('#abhareg_num_stepC').removeClass('d-none');
                    $('#abhareg_num_otp_input').focus();
                });
            });
            $('#abhareg_num_verify_otp_btn').on('click', function() {
                verifyOtpFromMobile($('#abhareg_num_otp_input').val().trim(), 'abhareg_num_stepC_alert', 'abhareg_num_verify_spinner', 'abhareg_num_verify_otp_btn');
            });
            $('#abhareg_num_resend_btn').on('click', function() {
                $('#abhareg_num_stepC').addClass('d-none');
                $('#abhareg_num_stepB').removeClass('d-none');
                $('#abhareg_num_otp_input').val('');
            });

            // Method 4 Send/Verify
            $('#abhareg_mob_send_btn').on('click', function() {
                sendOtpToMobile($('#abhareg_mob_mobile').val().trim(), 'abhareg_mob_step1_alert', 'abhareg_mob_send_spinner', 'abhareg_mob_send_btn', function(mobile) {
                    $('#abhareg_mob_step2').removeClass('d-none');
                    regAlert('abhareg_mob_step1_alert', 'success', 'OTP sent to mobile ending ' + mobile.slice(-4) + '.');
                    $('#abhareg_mob_otp_input').focus();
                });
            });
            $('#abhareg_mob_verify_btn').on('click', function() {
                verifyOtpFromMobile($('#abhareg_mob_otp_input').val().trim(), 'abhareg_mob_step2_alert', 'abhareg_mob_verify_spinner', 'abhareg_mob_verify_btn');
            });
            $('#abhareg_mob_resend_btn').on('click', function() {
                $('#abhareg_mob_step2').addClass('d-none');
                $('#abhareg_mob_otp_input').val('');
                $('#abhareg_mob_send_btn').trigger('click');
            });
            $('#abhareg_mob_back_btn').on('click', function() {
                $('#abhareg_mob_step2').addClass('d-none');
                regAlert('abhareg_mob_step2_alert', '', '');
            });

            // --- Method 2: QR Scanner ---
            function initQrScanner() {
                if (html5QrCode) { return; }
                var script = document.createElement('script');
                script.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
                script.onload = function() {
                    html5QrCode = new Html5Qrcode('abhareg_qr_reader');
                    $('#abhareg_qr_stop_btn').removeClass('d-none');
                    html5QrCode.start(
                        { facingMode: 'environment' },
                        { fps: 10, qrbox: { width: 250, height: 250 } },
                        function(decodedText) {
                            stopQrScanner();
                            var abha = decodedText.trim();
                            var numMatch = abha.match(/\d{14}/);
                            if (numMatch) { abha = numMatch[0]; }
                            $('#abhareg_qr_result').html('<div class="alert alert-success py-2"><i class="bi bi-qr-code me-2"></i>QR scanned: <strong>' + abha + '</strong></div>');
                            abhaRegSelectMethod('number');
                            $('#abhareg_abha_input').val(abha);
                            $('#abhareg_validate_btn').trigger('click');
                        },
                        function() {}
                    ).catch(function(err) {
                        $('#abhareg_qr_result').html('<div class="alert alert-warning py-2">Camera error: ' + err + '. Please allow camera access.</div>');
                    });
                };
                document.head.appendChild(script);
            }

            function stopQrScanner() {
                if (html5QrCode) {
                    html5QrCode.stop().catch(function(){}).then(function() { html5QrCode = null; });
                    $('#abhareg_qr_stop_btn').addClass('d-none');
                }
            }

            $('#abhareg_qr_stop_btn').on('click', function() { stopQrScanner(); });

            // Enter key on OTP fields
            $('#abhareg_num_otp_input').on('keypress', function(e) {
                if (e.which === 13) { $('#abhareg_num_verify_otp_btn').trigger('click'); }
            });
            $('#abhareg_mob_otp_input').on('keypress', function(e) {
                if (e.which === 13) { $('#abhareg_mob_verify_btn').trigger('click'); }
            });
        })();
        /* ===== END ABHA REGISTRATION WIZARD ===== */

        $('#input_abha_id').on('change blur', function() {
            var abhaId = ($(this).val() || '').toString().trim();
            if (abhaId !== '') {
                refreshAdvancedSearchResult({
                    input_abha_id: abhaId
                });
            } else {
                $('#search_result_update').html('');
            }

            if (!/^\d{14}$/.test(abhaId)) {
                return;
            }

            $.post('<?= base_url('patient/abha_fetch_profile') ?>', {
                abha_id: abhaId,
                '<?= csrf_token() ?>': $('input[name="<?= csrf_token() ?>"]').val()
            }, function(resp) {
                if (!resp || resp.ok != 1 || !resp.profile) {
                    return;
                }

                var p = resp.profile || {};
                if (p.name && !$('#input_name').val()) {
                    $('#input_name').val(p.name);
                }
                if (p.mobile && !$('#input_mphone1').val()) {
                    $('#input_mphone1').val(p.mobile);
                }
                if (p.city && !$('#input_city').val()) {
                    $('#input_city').val(p.city);
                }
                if (p.state && !$('#input_state').val()) {
                    $('#input_state').val(p.state);
                }
                if (p.dob && !$('#datepicker_dob').val()) {
                    $('#datepicker_dob').val(p.dob);
                    if ($('#chk_age').is(':checked')) {
                        $('#chk_age').prop('checked', false).trigger('click');
                    }
                }

                if (p.gender) {
                    var g = (p.gender + '').toLowerCase();
                    if (g === '1' || g === 'male' || g === 'm') {
                        $('#options_gender1').prop('checked', true);
                    } else if (g === '2' || g === 'female' || g === 'f') {
                        $('#options_gender2').prop('checked', true);
                    }
                }
            }, 'json');
        });

        $("#input_city").autocomplete({
            source: "<?= base_url('billing/patient/city') ?>",
            minLength: 3,
            autofocus: true,
            select: function(event, ui) {
                $("#input_district").val(ui.item.l_district);
                $("#input_state").val(ui.item.l_state);
            }
        });

        $("#chk_age").on("click", function() {
            if (this.checked) {
                $('#age_input_1').show();
                $('#age_input_2').hide();
            } else {
                $('#age_input_1').hide();
                $('#age_input_2').show();
            }
        });


        function split(val) {
            return val.split(/ \s*/);
        }

        function extractLast(term) {
            return split(term).pop();
        }

        $('[data-bs-toggle="tab"]').click(function(e) {
            e.preventDefault();
            var loadurl = $(this).data('url') || $(this).attr('href');
            if (loadurl && loadurl !== '#') {
                var targ = $(this).attr('data-bs-target');
                $.get(loadurl, function(data) {
                    $(targ).html(data);
                });
            }

            var tabTrigger = new bootstrap.Tab(this);
            tabTrigger.show();
        });

        $("#input_name")
            .on("focus", function() {
                // Suppress browser autocomplete suggestions
                $(this).removeAttr('readonly').off('mousedown.autocomplete');
            })
            // don't navigate away from the field on tab when selecting an item
            .on("keydown", function(event) {
                if (event.keyCode === $.ui.keyCode.TAB &&
                    $(this).autocomplete("instance").menu.active) {
                    event.preventDefault();
                }
            })
            .autocomplete({
                source: function(request, response) {
                    $.getJSON("<?= base_url('billing/patient/get_name') ?>", {
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
                    this.value = terms.join(" ");
                    return false;
                }
            });

        $("#input_relative_name")
            // don't navigate away from the field on tab when selecting an item
            .on("keydown", function(event) {
                if (event.keyCode === $.ui.keyCode.TAB &&
                    $(this).autocomplete("instance").menu.active) {
                    event.preventDefault();
                }
            })
            .autocomplete({
                source: function(request, response) {
                    $.getJSON("<?= base_url('billing/patient/get_name') ?>", {
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
                    this.value = terms.join(" ");
                    return false;
                }
            });

        $('[data-bs-toggle="gpill"]').click(function(e) {
            e.preventDefault();
            var loadurl = $(this).data('url') || $(this).attr('href');
            if (loadurl && loadurl !== '#') {
                var targ = $(this).attr('data-bs-target');
                $.get(loadurl, function(data) {
                    $(targ).html(data);
                });
            }

            var tabTrigger = new bootstrap.Tab(this);
            tabTrigger.show();
        });

    });

    function getCsrfData() {
        var csrfName = '<?= csrf_token() ?>';
        var csrfValue = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';

        return {
            name: csrfName,
            value: csrfValue
        };
    }

    function refreshAdvancedSearchResult(payload) {
        var csrf = getCsrfData();
        var postData = $.extend({}, payload || {});
        postData[csrf.name] = csrf.value;

        $.post('<?= base_url('billing/patient/search_adv') ?>', postData, function(data) {
            $('#search_result_update').html(data);
        });
    }

    function onchange_feild_number(control_button) {
        var input_mphone1 = $('#input_mphone1').val();
        refreshAdvancedSearchResult({
            "input_mphone1": input_mphone1
        });
    }

    function onchange_aadhar(control_button) {
        var input_udai = $('#input_udai').val();
        refreshAdvancedSearchResult({
            "input_udai": input_udai
        });
    }

    function onchange_relative_name(control_button) {
        var input_relative_name = $('#input_relative_name').val();
        var input_name = $('#input_name').val();
        refreshAdvancedSearchResult({
            "input_relative_name": input_relative_name,
            "input_name": input_name
        });

    }

    function onchange_title() {
        var d = $('#cbo_title').val();

        if (d == "Mr." || d == "Master" || d == "Baby Boy" || d == "Mohd.") {
            $("#options_gender1").prop("checked", true);
        } else {
            $("#options_gender2").prop("checked", true);
        }
    }
</script>