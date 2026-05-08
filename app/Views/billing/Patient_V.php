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

                                                    <!-- Step 3: Mobile confirmation -->
                                                    <div id="abha_step_3" class="abha-panel" style="display:none;">
                                                        <p class="text-muted small mb-3">Verify or update the communication mobile number for this patient.</p>
                                                        <div class="row g-3 align-items-end">
                                                            <div class="col-md-3">
                                                                <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
                                                                <input type="text" class="form-control" id="abha_comm_mobile"
                                                                    placeholder="10-digit number" maxlength="10" inputmode="numeric" autocomplete="off">
                                                            </div>
                                                        </div>
                                                        <div id="abha_step3_alert" class="mt-2"></div>
                                                        <div class="mt-3 d-flex gap-2">
                                                            <button type="button" class="btn btn-primary" id="abha_comm_next_btn">
                                                                <span id="abha_comm_spinner" class="spinner-border spinner-border-sm me-1 d-none"></span>
                                                                Confirm &amp; Continue
                                                            </button>
                                                            <button type="button" class="btn btn-link text-secondary px-0" id="abha_back_step2_btn">&larr; Back</button>
                                                        </div>
                                                    </div>

                                                    <!-- Step 4: Done / ABHA address -->
                                                    <div id="abha_step_4" class="abha-panel" style="display:none;">
                                                        <div id="abha_created_result" class="mb-3"></div>
                                                        <div class="row g-3 align-items-end">
                                                            <div class="col-md-4">
                                                                <label class="form-label">Health ID Address <small class="text-muted">(optional)</small></label>
                                                                <div class="input-group">
                                                                    <input type="text" class="form-control" id="abha_address_input" placeholder="e.g. ramesh.kumar">
                                                                    <span class="input-group-text">@abdm</span>
                                                                </div>
                                                                <small class="text-muted">Choose from suggested addresses or type your own.</small>
                                                            </div>
                                                        </div>
                                                        <div id="abha_step4_alert" class="mt-2"></div>
                                                        <div class="mt-3">
                                                            <button type="button" class="btn btn-success" id="abha_finalise_btn">
                                                                <span id="abha_finalise_spinner" class="spinner-border spinner-border-sm me-1 d-none"></span>
                                                                Save ABHA Address
                                                            </button>
                                                        </div>
                                                    </div>

                                                </div>
                                                
                                                <div class="tab-pane fade" id="abha-verify" role="tabpanel" aria-labelledby="abha-verify-tab">
                                                    <form id="abha_verify_form" class="needs-validation" novalidate>
                                                        <?= csrf_field() ?>
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="form-group mb-3">
                                                                    <label class="form-label">ABHA Number / Health ID</label>
                                                                    <input type="text" class="form-control" id="abha_verify_number" 
                                                                        name="abha_number" placeholder="ABHA Number" required>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="form-group mb-3">
                                                                    <label class="form-label">Aadhaar or Mobile</label>
                                                                    <input type="text" class="form-control" id="abha_verify_mobile" 
                                                                        name="mobile_aadhaar" placeholder="Enter Mobile or Aadhaar" required>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-12">
                                                                <button type="submit" class="btn btn-success">Verify ABHA</button>
                                                            </div>
                                                        </div>
                                                    </form>
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
            var txnId = null;   // transaction id from ABDM API
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
                if (!$('#abha_consent_chk').is(':checked')) {
                    showAlert('abha_step1_alert', 'warning', 'You must agree to the Terms and Conditions to proceed.');
                    return;
                }
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
                    '<?= csrf_token() ?>': csrfToken()
                }, function(resp) {
                    $('#abha_verify_otp_spinner').addClass('d-none');
                    $('#abha_verify_otp_btn').prop('disabled', false);
                    if (resp && resp.ok == 1) {
                        txnId = resp.txn_id || txnId;
                        if (resp.mobile) $('#abha_comm_mobile').val(resp.mobile);
                        abhaStep(3);
                    } else {
                        showAlert('abha_step2_alert', 'danger', (resp && resp.error_text) ? resp.error_text : 'Invalid OTP. Please try again.');
                    }
                }, 'json').fail(function() {
                    $('#abha_verify_otp_spinner').addClass('d-none');
                    $('#abha_verify_otp_btn').prop('disabled', false);
                    showAlert('abha_step2_alert', 'danger', 'Server error. Please try again.');
                });
            });

            // Back to step 2
            $('#abha_back_step2_btn').on('click', function() { abhaStep(2); });

            // Step 3 → Communication next
            $('#abha_comm_next_btn').on('click', function() {
                var mobile = $('#abha_comm_mobile').val().trim();
                if (!/^\d{10}$/.test(mobile)) {
                    showAlert('abha_step3_alert', 'danger', 'Please enter a valid 10-digit mobile number.');
                    return;
                }
                $('#abha_step3_alert').html('');
                $('#abha_comm_spinner').removeClass('d-none');
                $('#abha_comm_next_btn').prop('disabled', true);

                $.post('<?= base_url('abha/create/communication') ?>', {
                    mobile: mobile,
                    txn_id: txnId,
                    '<?= csrf_token() ?>': csrfToken()
                }, function(resp) {
                    $('#abha_comm_spinner').addClass('d-none');
                    $('#abha_comm_next_btn').prop('disabled', false);
                    if (resp && resp.ok == 1) {
                        txnId = resp.txn_id || txnId;
                        // Show created ABHA details
                        if (resp.abha_number) {
                            $('#abha_created_result').html(
                                '<div class="alert alert-success">' +
                                '<strong>ABHA Number Created!</strong><br>' +
                                'ABHA Number: <strong>' + resp.abha_number + '</strong><br>' +
                                (resp.name ? 'Name: ' + resp.name + '<br>' : '') +
                                '</div>'
                            );
                        }
                        if (resp.suggested_addresses && resp.suggested_addresses.length) {
                            var opts = resp.suggested_addresses.map(function(a) {
                                return '<option value="' + a + '">' + a + '</option>';
                            }).join('');
                            $('#abha_address_input').replaceWith(
                                '<select class="form-select" id="abha_address_input"><option value="">Select an address...</option>' + opts + '</select>'
                            );
                        }
                        abhaStep(4);
                    } else {
                        showAlert('abha_step3_alert', 'danger', (resp && resp.error_text) ? resp.error_text : 'Failed to proceed. Please try again.');
                    }
                }, 'json').fail(function() {
                    $('#abha_comm_spinner').addClass('d-none');
                    $('#abha_comm_next_btn').prop('disabled', false);
                    showAlert('abha_step3_alert', 'danger', 'Server error. Please try again.');
                });
            });

            // Step 4 → Finalise ABHA address
            $('#abha_finalise_btn').on('click', function() {
                var addr = $('#abha_address_input').val().trim();
                if (!addr) {
                    showAlert('abha_step4_alert', 'warning', 'Please choose an ABHA address.');
                    return;
                }
                $('#abha_step4_alert').html('');
                $('#abha_finalise_spinner').removeClass('d-none');
                $('#abha_finalise_btn').prop('disabled', true);

                $.post('<?= base_url('abha/create/address') ?>', {
                    abha_address: addr,
                    txn_id: txnId,
                    '<?= csrf_token() ?>': csrfToken()
                }, function(resp) {
                    $('#abha_finalise_spinner').addClass('d-none');
                    $('#abha_finalise_btn').prop('disabled', false);
                    if (resp && resp.ok == 1) {
                        showAlert('abha_step4_alert', 'success', 'ABHA Address <strong>' + addr + '@abdm</strong> successfully created!');
                        $('#abha_finalise_btn').hide();
                    } else {
                        showAlert('abha_step4_alert', 'danger', (resp && resp.error_text) ? resp.error_text : 'Failed to create ABHA address.');
                    }
                }, 'json').fail(function() {
                    $('#abha_finalise_spinner').addClass('d-none');
                    $('#abha_finalise_btn').prop('disabled', false);
                    showAlert('abha_step4_alert', 'danger', 'Server error. Please try again.');
                });
            });
        })();
        /* =============================================================== */

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