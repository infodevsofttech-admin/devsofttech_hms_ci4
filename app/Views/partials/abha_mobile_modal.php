<?php
/**
 * Partial: "Find ABHA via Mobile" modal.
 *
 * Compliant with ABDM M1 Guidelines:
 *   FLOW: MOBILE NUMBER -------> PROFILES -------> OTP --------> COMPLETE PROFILE FETCHED
 *
 * Steps:
 *   1. Enter 10-digit mobile number -> Click Next
 *   2. Displays found profiles -> Select profile & Authentication Type (Mobile OTP / Aadhaar OTP) -> Click Next
 *   3. Confirm OTP -> Enter 6-digit OTP sent to registered mobile -> Click Verify OTP
 *   4. Complete ABDM Profile fetched & Official ABHA card -> Compare with HMS Patients
 *
 * Include once per page:  <?= view('partials/abha_mobile_modal') ?>
 * Then call: window.AbhaMobileModal.open(function (profile) { ... });
 */
?>
<style>
    .abha-mobile-modal .modal-content { border:0; border-radius:12px; overflow:hidden; }
    .abha-mobile-modal .modal-header { border-bottom:1px solid #e5eaf1; padding:18px 22px; }
    .abha-mobile-modal .modal-body { padding:22px; }
    .abha-mobile-steps { display:flex; justify-content:center; align-items:center; margin-bottom:24px; }
    .abha-mobile-step { display:flex; align-items:center; color:#8792a3; font-weight:600; font-size:0.9rem; }
    .abha-mobile-step:not(:last-child)::after { content:""; width:38px; height:2px; background:#dce3ec; margin:0 8px; }
    .abha-mobile-step.done:not(:last-child)::after { background:#356cf4; }
    .abha-mobile-step .step-num { width:32px; height:32px; display:grid; place-items:center; border-radius:50%; background:#eef2f6; font-weight:700; margin-right:8px; flex-shrink:0; }
    .abha-mobile-step.active .step-num { color:#1748ce; background:#fff; border:2px solid #356cf4; box-shadow:0 0 0 4px #edf2ff; }
    .abha-mobile-step.active { color:#1748ce; }
    .abha-mobile-step.done .step-num { color:#fff; background:#356cf4; }
    .abha-mobile-step.done { color:#356cf4; }
    .abha-mobile-step .step-title { font-weight:600; white-space:nowrap; }

    /* Profile Selection Card Styles */
    .abha-profile-card {
        border: 1.5px solid #dce3ec;
        border-radius: 10px;
        padding: 14px 18px;
        transition: all 0.2s ease-in-out;
        cursor: pointer;
        background: #ffffff;
    }
    .abha-profile-card:hover {
        border-color: #356cf4;
        background: #f8faff;
        transform: translateY(-1px);
    }
    .abha-profile-card.selected {
        border-color: #356cf4;
        background: #eff5ff;
        box-shadow: 0 0 0 2px #356cf4;
    }
    .abha-profile-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: #eef2f6;
        display: grid;
        place-items: center;
        font-size: 1.35rem;
        color: #356cf4;
        flex-shrink: 0;
    }

    .abha-mobile-modal .abha-profile-list { margin:0; }
    .abha-mobile-modal .abha-profile-list>div { display:grid; grid-template-columns:150px minmax(0,1fr); gap:14px; padding:10px 0; border-bottom:1px solid #edf0f4; }
    .abha-mobile-modal .abha-profile-list dt { color:#6b7688; font-weight:500; }
    .abha-mobile-modal .abha-profile-list dd { margin:0; text-align:right; font-weight:600; overflow-wrap:anywhere; }
    .abha-mobile-modal .abha-card-preview { min-height:230px; display:grid; place-items:center; border:1px solid #dce3ec; background:#f8fafc; border-radius:8px; padding:12px; }
    .abha-mobile-modal .abha-card-preview img { max-width:100%; max-height:360px; object-fit:contain; border-radius:6px; box-shadow:0 4px 12px rgba(0,0,0,0.08); }

    @media (max-width:575.98px) {
        .abha-mobile-modal .modal-body { padding:16px; }
        .abha-mobile-step:not(:last-child)::after { width:18px; }
        .abha-mobile-step .step-num { width:26px; height:26px; font-size:0.8rem; margin-right:4px; }
        .abha-mobile-modal .abha-profile-list>div { grid-template-columns:110px minmax(0,1fr); }
    }
</style>

<div class="modal fade abha-mobile-modal" id="abhaMobileModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-phone text-primary me-2"></i>Find ABHA via Mobile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- 4-Step Progress Stepper -->
                <div class="abha-mobile-steps" aria-label="Verification progress">
                    <div class="abha-mobile-step active" data-step="1"><span class="step-num">1</span><span class="step-title d-none d-sm-inline">Mobile</span></div>
                    <div class="abha-mobile-step" data-step="2"><span class="step-num">2</span><span class="step-title d-none d-sm-inline">Profiles</span></div>
                    <div class="abha-mobile-step" data-step="3"><span class="step-num">3</span><span class="step-title d-none d-sm-inline">OTP</span></div>
                    <div class="abha-mobile-step" data-step="4"><span class="step-num">4</span><span class="step-title d-none d-sm-inline">Profile</span></div>
                </div>

                <div id="abhaMobileAlert"></div>

                <!-- Step 1: Mobile Number Input -->
                <section id="abhaMobileStep1">
                    <div class="card border-0 bg-light p-4 rounded-3 mb-3">
                        <label class="form-label fw-semibold text-dark fs-6" for="abhaMobileNumber">Enter Mobile Number</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white"><i class="bi bi-phone"></i></span>
                            <input type="text" class="form-control" id="abhaMobileNumber" maxlength="10" inputmode="numeric" autocomplete="tel" placeholder="10-digit mobile number">
                            <button type="button" class="btn btn-primary px-4 fw-semibold" id="abhaMobileFindBtn">
                                Next <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                        <div class="form-text mt-2 text-muted">
                            <i class="bi bi-info-circle me-1"></i>Enter the mobile number registered with the patient's ABHA account to view associated profiles.
                        </div>
                    </div>
                </section>

                <!-- Step 2: Profiles Selection & Auth Type -->
                <section id="abhaMobileStep2" class="d-none">
                    <div class="mb-3">
                        <h6 class="mb-1 fw-bold text-dark"><i class="bi bi-people text-primary me-2"></i>Select ABHA Profile</h6>
                        <p class="text-muted mb-0" id="abhaProfilesSubtitle">
                            We have found the following ABHA accounts linked with mobile <strong id="abhaMobileFoundMobile"></strong>. Select the ABHA profile you wish to login:
                        </p>
                    </div>

                    <!-- Profiles Radio List -->
                    <div class="vstack gap-2 mb-4" id="abhaProfilesList">
                        <!-- Populated by JavaScript -->
                    </div>

                    <!-- Authentication Type Selection -->
                    <div class="card border border-primary-subtle bg-light p-3 rounded-3 mb-4">
                        <label class="form-label fw-bold text-dark mb-2">
                            <i class="bi bi-shield-lock text-primary me-1"></i>Authentication type <span class="text-danger">*</span>
                        </label>
                        <div class="d-flex gap-4 flex-wrap align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="abhaAuthType" id="abhaAuthTypeMobile" value="MOBILE_OTP" checked>
                                <label class="form-check-label fw-semibold text-dark" for="abhaAuthTypeMobile">
                                    <i class="bi bi-phone me-1 text-primary"></i>Mobile OTP
                                </label>
                            </div>
                            <div class="form-check" id="abhaAuthTypeAadhaarWrap">
                                <input class="form-check-input" type="radio" name="abhaAuthType" id="abhaAuthTypeAadhaar" value="AADHAAR_OTP">
                                <label class="form-check-label fw-semibold text-dark" for="abhaAuthTypeAadhaar">
                                    <i class="bi bi-shield-check me-1 text-success"></i>Aadhaar OTP
                                </label>
                            </div>
                        </div>
                        <small class="text-muted mt-1" id="abhaAuthTypeHint">OTP will be sent to the registered mobile number.</small>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                        <button type="button" class="btn btn-outline-secondary px-3" id="abhaProfilesBackBtn">
                            <i class="bi bi-arrow-left me-1"></i>Change Mobile
                        </button>
                        <button type="button" class="btn btn-primary px-4 fw-semibold" id="abhaSendOtpBtn">
                            Next <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </div>
                </section>

                <!-- Step 3: OTP Verification -->
                <section id="abhaMobileStep3" class="d-none">
                    <div class="card border-0 bg-light p-4 rounded-3 mb-3">
                        <h6 class="fw-bold text-dark mb-1"><i class="bi bi-key text-primary me-2"></i>Confirm OTP</h6>
                        <p class="text-muted mb-3" id="abhaMobileOtpHint">OTP sent to mobile number ending with ******3177</p>

                        <label class="form-label fw-semibold text-dark" for="abhaMobileOtp">Enter 6-digit OTP</label>
                        <div class="input-group input-group-lg" style="max-width:380px;">
                            <span class="input-group-text bg-white"><i class="bi bi-shield-check"></i></span>
                            <input type="text" class="form-control letter-spacing-2" id="abhaMobileOtp" maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="6-digit OTP">
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4 gap-2 flex-wrap">
                            <div>
                                <button type="button" class="btn btn-link px-0 me-3" id="abhaMobileResendBtn" disabled>Resend OTP in 60s</button>
                                <button type="button" class="btn btn-link px-0 text-secondary" id="abhaBackToProfilesBtn">&larr; Back to Profiles</button>
                            </div>
                            <button type="button" class="btn btn-primary px-4 fw-semibold" id="abhaMobileVerifyBtn">
                                <i class="bi bi-patch-check me-1"></i>Verify OTP
                            </button>
                        </div>
                    </div>
                </section>

                <!-- Step 4: Complete ABDM Profile Fetched & Linking -->
                <section id="abhaMobileStep4" class="d-none">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <img id="abhaMobilePhoto" class="rounded d-none" alt="ABHA profile" style="width:76px;height:76px;object-fit:cover">
                                <div>
                                    <span class="badge bg-success-subtle text-success mb-1"><i class="bi bi-patch-check-fill me-1"></i>ABHA Verified</span>
                                    <h4 class="mb-0 fw-bold text-dark" id="abhaMobileProfileName"></h4>
                                    <div class="text-muted fw-semibold" id="abhaMobileProfileAddress"></div>
                                </div>
                            </div>
                            <dl class="abha-profile-list">
                                <div><dt>ABHA Number</dt><dd id="abhaMobileProfileNumber">-</dd></div>
                                <div><dt>ABHA Address</dt><dd id="abhaMobileProfileId">-</dd></div>
                                <div><dt>Date of Birth</dt><dd id="abhaMobileProfileDob">-</dd></div>
                                <div><dt>Gender</dt><dd id="abhaMobileProfileGender">-</dd></div>
                                <div><dt>Mobile</dt><dd id="abhaMobileProfileMobile">-</dd></div>
                                <div><dt>Address</dt><dd id="abhaMobileProfileFullAddress">-</dd></div>
                            </dl>
                        </div>
                        <div class="col-lg-6">
                            <div class="abha-card-preview" id="abhaMobileCardWrap">
                                <div class="text-center text-muted">
                                    <i class="bi bi-card-image fs-1 d-block mb-2"></i>
                                    <span>Official ABHA card preview.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4 gap-2 flex-wrap">
                        <button type="button" class="btn btn-outline-secondary" id="abhaChooseDifferentProfileBtn">
                            &larr; Choose Different Profile
                        </button>
                        <button type="button" class="btn btn-success px-4 fw-semibold" id="abhaMobileCompareBtn">
                            <i class="bi bi-people me-1"></i>Compare with HMS Patients
                        </button>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<script>
window.AbhaMobileModal = (function () {
    'use strict';

    var modal = null;
    var initialized = false;
    var searchTxnId = '';
    var otpTxnId = '';
    var searchMobile = '';
    var foundProfiles = [];
    var selectedProfile = null;
    var verifiedProfile = null;
    var onVerified = null;
    var resendTimer = null;
    var resendRemaining = 0;

    function csrf() {
        var input = document.querySelector('input[name="<?= csrf_token() ?>"]');
        return input ? input.value : '<?= csrf_hash() ?>';
    }
    function escapeHtml(value) {
        if (value == null) return '';
        var div = document.createElement('div');
        div.textContent = String(value);
        return div.innerHTML;
    }
    function digits(value) { return String(value == null ? '' : value).replace(/\D/g, ''); }
    function apiMessage(response, fallback) {
        return response && (response.error_text || response.message) ? escapeHtml(response.error_text || response.message) : fallback;
    }
    function alertBox(type, message) {
        var alertEl = document.getElementById('abhaMobileAlert');
        if (!alertEl) return;
        alertEl.innerHTML = message ? '<div class="alert alert-' + type + ' py-2">' + message + '</div>' : '';
    }
    function showStep(step) {
        for (var i = 1; i <= 4; i++) {
            var stepEl = document.getElementById('abhaMobileStep' + i);
            if (stepEl) {
                if (i === step) stepEl.classList.remove('d-none');
                else stepEl.classList.add('d-none');
            }
        }
        var stepHeaders = document.querySelectorAll('.abha-mobile-step');
        stepHeaders.forEach(function (header) {
            var itemStep = Number(header.getAttribute('data-step'));
            if (itemStep === step) {
                header.classList.add('active');
                header.classList.remove('done');
            } else if (itemStep < step) {
                header.classList.remove('active');
                header.classList.add('done');
            } else {
                header.classList.remove('active', 'done');
            }
        });
        alertBox('', '');
    }
    function stopTimer() {
        if (resendTimer) window.clearInterval(resendTimer);
        resendTimer = null;
    }
    function startResendTimer(seconds) {
        stopTimer();
        resendRemaining = Math.max(60, Number(seconds) || 60);
        var button = document.getElementById('abhaMobileResendBtn');
        if (button) button.disabled = true;
        function tick() {
            if (!button) return;
            if (resendRemaining <= 0) {
                stopTimer();
                button.disabled = false;
                button.textContent = 'Resend OTP';
                return;
            }
            button.textContent = 'Resend OTP in ' + resendRemaining + 's';
            resendRemaining--;
        }
        tick();
        resendTimer = window.setInterval(tick, 1000);
    }
    function genderText(value) {
        var gender = String(value || '').toUpperCase();
        if (gender === 'M' || gender === '1' || gender === 'MALE') return 'Male';
        if (gender === 'F' || gender === '2' || gender === 'FEMALE') return 'Female';
        if (gender === 'O' || gender === '3' || gender === 'OTHER') return 'Other';
        return value || '-';
    }
    function formatAbha(value) { return String(value || '').replace(/(\d{2})(\d{4})(\d{4})(\d{4})/, '$1-$2-$3-$4'); }
    function maskMobile(value) {
        var mobile = digits(value);
        return mobile.length === 10 ? mobile.replace(/(\d{6})(\d{4})/, '******$2') : (value || '-');
    }

    // Step 1: Search ABHA profiles by mobile number
    function searchProfiles() {
        var numInput = document.getElementById('abhaMobileNumber');
        var mobile = digits(numInput ? numInput.value : '');
        if (mobile.length !== 10) { alertBox('warning', 'Enter a valid 10-digit mobile number.'); return; }

        searchMobile = mobile;
        foundProfiles = [];
        selectedProfile = null;
        var button = document.getElementById('abhaMobileFindBtn');
        if (button) {
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Searching...';
        }

        var postData = new URLSearchParams();
        postData.append('mobile', mobile);
        postData.append('<?= csrf_token() ?>', csrf());

        fetch('<?= base_url('abha/find/mobile/search') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: postData.toString()
        })
        .then(function (res) { return res.json(); })
        .then(function (response) {
            if (button) {
                button.disabled = false;
                button.innerHTML = 'Next <i class="bi bi-arrow-right ms-1"></i>';
            }
            if (!response || response.ok != 1 || !Array.isArray(response.profiles) || response.profiles.length === 0) {
                alertBox('danger', apiMessage(response, 'No ABHA profiles found linked to this mobile number.'));
                return;
            }

            searchTxnId = response.txn_id || '';
            foundProfiles = response.profiles;
            renderProfilesList(foundProfiles);
            showStep(2);
        })
        .catch(function (err) {
            if (button) {
                button.disabled = false;
                button.innerHTML = 'Next <i class="bi bi-arrow-right ms-1"></i>';
            }
            alertBox('danger', 'Unable to find ABHA profiles for this mobile number.');
        });
    }

    // Step 2: Render profiles list with radio buttons
    function renderProfilesList(profiles) {
        var mobileSpan = document.getElementById('abhaMobileFoundMobile');
        if (mobileSpan) mobileSpan.textContent = maskMobile(searchMobile);

        var container = document.getElementById('abhaProfilesList');
        if (!container) return;
        container.innerHTML = '';

        profiles.forEach(function (prof, index) {
            var isSelected = (index === 0);
            if (isSelected) selectedProfile = prof;

            var name = prof.name || 'ABHA Profile #' + (index + 1);
            var abhaNum = prof.abha_number || prof.ABHANumber || prof.abha_id || '-';
            var gender = genderText(prof.gender);
            var dob = prof.dob || '-';
            var isVerified = prof.kyc_verified !== false;

            var iconClass = (gender === 'Female') ? 'bi-person-standing-dress text-danger' : 'bi-person-standing text-primary';
            if (gender === 'Other') iconClass = 'bi-person text-info';

            var photoHtml = prof.profile_photo
                ? '<img src="' + (String(prof.profile_photo).indexOf('data:') === 0 ? prof.profile_photo : 'data:image/jpeg;base64,' + prof.profile_photo) + '" class="rounded-circle" style="width:48px;height:48px;object-fit:cover">'
                : '<div class="abha-profile-avatar"><i class="bi ' + iconClass + '"></i></div>';

            var card = document.createElement('div');
            card.className = 'abha-profile-card d-flex align-items-center justify-content-between gap-3 ' + (isSelected ? 'selected' : '');
            card.setAttribute('data-index', index);
            card.innerHTML =
                '<div class="d-flex align-items-center gap-3">' +
                    '<div class="form-check m-0">' +
                        '<input class="form-check-input profile-radio" type="radio" name="abhaProfileOption" value="' + index + '" ' + (isSelected ? 'checked' : '') + '>' +
                    '</div>' +
                    photoHtml +
                    '<div>' +
                        '<div class="d-flex align-items-center gap-2 flex-wrap">' +
                            '<h6 class="mb-0 fw-bold text-dark">' + escapeHtml(name) + '</h6>' +
                            (isVerified ? '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-patch-check-fill me-1"></i>Active</span>' : '') +
                        '</div>' +
                        '<div class="text-muted small mt-1">' +
                            '<span class="me-3"><i class="bi bi-credit-card-2-front me-1"></i>ABHA: <strong>' + escapeHtml(formatAbha(abhaNum)) + '</strong></span>' +
                            '<span class="me-3"><i class="bi bi-gender-ambiguous me-1"></i>' + escapeHtml(gender) + '</span>' +
                            (dob && dob !== '-' ? '<span><i class="bi bi-calendar-event me-1"></i>DOB: ' + escapeHtml(dob) + '</span>' : '') +
                        '</div>' +
                    '</div>' +
                '</div>';

            card.addEventListener('click', function () {
                document.querySelectorAll('.abha-profile-card').forEach(function (c) { c.classList.remove('selected'); });
                card.classList.add('selected');
                var radio = card.querySelector('.profile-radio');
                if (radio) radio.checked = true;
                selectedProfile = prof;
                updateAuthMethods(prof);
            });

            container.appendChild(card);
        });

        if (profiles.length > 0) {
            updateAuthMethods(profiles[0]);
        }
    }

    function updateAuthMethods(profile) {
        var methods = Array.isArray(profile.auth_methods) ? profile.auth_methods : ['MOBILE_OTP', 'AADHAAR_OTP'];
        var supportsAadhaar = methods.some(function(m) { return String(m).toUpperCase().indexOf('AADHAAR') !== -1; });
        var aadhaarWrap = document.getElementById('abhaAuthTypeAadhaarWrap');
        if (aadhaarWrap) {
            if (supportsAadhaar) aadhaarWrap.classList.remove('d-none');
            else aadhaarWrap.classList.add('d-none');
        }
        var aadhaarRadio = document.getElementById('abhaAuthTypeAadhaar');
        var mobileRadio = document.getElementById('abhaAuthTypeMobile');
        if (!supportsAadhaar && aadhaarRadio && aadhaarRadio.checked && mobileRadio) {
            mobileRadio.checked = true;
        }
    }

    // Step 2 -> 3: Request OTP for the chosen profile
    function requestOtp() {
        if (!selectedProfile) {
            alertBox('warning', 'Please select an ABHA profile to proceed.');
            return;
        }

        var authMethodInput = document.querySelector('input[name="abhaAuthType"]:checked');
        var authMethod = authMethodInput ? authMethodInput.value : 'MOBILE_OTP';
        var profileIndex = selectedProfile.index || '1';

        var button = document.getElementById('abhaSendOtpBtn');
        if (button) {
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending OTP...';
        }

        var postData = new URLSearchParams();
        postData.append('txn_id', searchTxnId);
        postData.append('index', profileIndex);
        postData.append('auth_method', authMethod);
        postData.append('mobile', searchMobile);
        postData.append('<?= csrf_token() ?>', csrf());

        fetch('<?= base_url('abha/find/mobile/request-otp') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: postData.toString()
        })
        .then(function (res) { return res.json(); })
        .then(function (response) {
            if (button) {
                button.disabled = false;
                button.innerHTML = 'Next <i class="bi bi-arrow-right ms-1"></i>';
            }
            if (!response || response.ok != 1) {
                alertBox('danger', apiMessage(response, 'Failed to send OTP to registered mobile.'));
                return;
            }

            otpTxnId = response.txn_id || searchTxnId;
            var hintMsg = response.message || ('OTP sent to mobile number ending with ' + maskMobile(searchMobile));
            var hintEl = document.getElementById('abhaMobileOtpHint');
            if (hintEl) hintEl.textContent = hintMsg;

            var otpInput = document.getElementById('abhaMobileOtp');
            if (otpInput) otpInput.value = '';

            showStep(3);
            startResendTimer(60);
            window.setTimeout(function () {
                var el = document.getElementById('abhaMobileOtp');
                if (el) el.focus();
            }, 200);
        })
        .catch(function () {
            if (button) {
                button.disabled = false;
                button.innerHTML = 'Next <i class="bi bi-arrow-right ms-1"></i>';
            }
            alertBox('danger', 'Failed to send OTP to registered mobile.');
        });
    }

    // Step 3 -> 4: Verify OTP and render complete profile
    function verifyOtp() {
        var otpInput = document.getElementById('abhaMobileOtp');
        var otp = digits(otpInput ? otpInput.value : '');
        if (otp.length !== 6) { alertBox('warning', 'Enter the 6-digit OTP.'); return; }

        var authMethodInput = document.querySelector('input[name="abhaAuthType"]:checked');
        var authMethod = authMethodInput ? authMethodInput.value : 'MOBILE_OTP';

        var button = document.getElementById('abhaMobileVerifyBtn');
        if (button) {
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Verifying...';
        }

        var postData = new URLSearchParams();
        postData.append('txn_id', otpTxnId);
        postData.append('otp', otp);
        postData.append('auth_method', authMethod);
        postData.append('mobile', searchMobile);
        postData.append('<?= csrf_token() ?>', csrf());

        fetch('<?= base_url('abha/find/mobile/verify-otp') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: postData.toString()
        })
        .then(function (res) { return res.json(); })
        .then(function (response) {
            if (button) {
                button.disabled = false;
                button.innerHTML = '<i class="bi bi-patch-check me-1"></i>Verify OTP';
            }
            if (!response || response.ok != 1) {
                alertBox('danger', apiMessage(response, 'OTP verification failed. Please check the code.'));
                return;
            }
            stopTimer();
            renderCompleteProfile(response);
        })
        .catch(function () {
            if (button) {
                button.disabled = false;
                button.innerHTML = '<i class="bi bi-patch-check me-1"></i>Verify OTP';
            }
            alertBox('danger', 'OTP verification failed.');
        });
    }

    // Step 4: Render complete profile and card
    function renderCompleteProfile(profile) {
        verifiedProfile = profile;
        var elName = document.getElementById('abhaMobileProfileName');
        var elAddr = document.getElementById('abhaMobileProfileAddress');
        var elNum = document.getElementById('abhaMobileProfileNumber');
        var elId = document.getElementById('abhaMobileProfileId');
        var elDob = document.getElementById('abhaMobileProfileDob');
        var elGender = document.getElementById('abhaMobileProfileGender');
        var elMobile = document.getElementById('abhaMobileProfileMobile');
        var elFullAddr = document.getElementById('abhaMobileProfileFullAddress');

        if (elName) elName.textContent = profile.name || '-';
        if (elAddr) elAddr.textContent = profile.abha_address || '-';
        if (elNum) elNum.textContent = formatAbha(profile.abha_number) || '-';
        if (elId) elId.textContent = profile.abha_address || '-';
        if (elDob) elDob.textContent = profile.dob || '-';
        if (elGender) elGender.textContent = genderText(profile.gender);
        if (elMobile) elMobile.textContent = maskMobile(profile.mobile || searchMobile);
        if (elFullAddr) elFullAddr.textContent = [profile.address, profile.district, profile.state, profile.zip].filter(Boolean).join(', ') || '-';

        var photo = profile.photo ? (String(profile.photo).indexOf('data:') === 0 ? profile.photo : 'data:image/jpeg;base64,' + profile.photo) : '';
        var photoEl = document.getElementById('abhaMobilePhoto');
        if (photoEl) {
            if (photo) {
                photoEl.classList.remove('d-none');
                photoEl.src = photo;
            } else {
                photoEl.classList.add('d-none');
                photoEl.src = '';
            }
        }

        var card = profile.card_base64 || '';
        var cardWrap = document.getElementById('abhaMobileCardWrap');
        if (cardWrap) {
            if (card) {
                var cardSrc = String(card).indexOf('data:') === 0 ? card : 'data:' + (profile.card_content_type || 'image/png') + ';base64,' + card;
                var note = profile.card_source !== 'generated'
                    ? ''
                    : '<div class="small text-warning mt-2"><i class="bi bi-exclamation-triangle me-1"></i>Provisional card generated by Bridge.</div>';
                var downloadBtn = '<div class="mt-2 text-center"><a href="' + cardSrc + '" download="ABHA_Card_' + (profile.abha_number || 'card') + '.png" class="btn btn-sm btn-outline-primary"><i class="bi bi-download me-1"></i>Download ABHA Card</a></div>';
                cardWrap.innerHTML = '<div class="text-center w-100"><img src="' + cardSrc + '" alt="ABHA card" class="img-fluid rounded mb-2" style="max-height:360px;">' + downloadBtn + note + '</div>';
            } else {
                var reason = profile.card_message ? escapeHtml(profile.card_message) : 'Official ABHA card preview was not returned.';
                cardWrap.innerHTML = '<div class="text-center text-muted p-4"><i class="bi bi-card-image fs-1 d-block mb-2"></i>' + reason + '</div>';
            }
        }

        var chooseDiffBtn = document.getElementById('abhaChooseDifferentProfileBtn');
        if (chooseDiffBtn) {
            if (foundProfiles && foundProfiles.length > 1) chooseDiffBtn.classList.remove('d-none');
            else chooseDiffBtn.classList.add('d-none');
        }

        showStep(4);
    }

    function ensureInit() {
        if (initialized) return true;
        var modalEl = document.getElementById('abhaMobileModal');
        if (!modalEl || typeof window.bootstrap === 'undefined') return false;

        modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);

        var findBtn = document.getElementById('abhaMobileFindBtn');
        if (findBtn) findBtn.onclick = searchProfiles;

        var sendOtpBtn = document.getElementById('abhaSendOtpBtn');
        if (sendOtpBtn) sendOtpBtn.onclick = requestOtp;

        var resendBtn = document.getElementById('abhaMobileResendBtn');
        if (resendBtn) resendBtn.onclick = requestOtp;

        var verifyBtn = document.getElementById('abhaMobileVerifyBtn');
        if (verifyBtn) verifyBtn.onclick = verifyOtp;

        var backBtn = document.getElementById('abhaProfilesBackBtn');
        if (backBtn) backBtn.onclick = function () {
            showStep(1);
            var el = document.getElementById('abhaMobileNumber');
            if (el) el.focus();
        };

        var backToProfBtn = document.getElementById('abhaBackToProfilesBtn');
        if (backToProfBtn) backToProfBtn.onclick = function () {
            stopTimer();
            showStep(2);
        };

        var diffBtn = document.getElementById('abhaChooseDifferentProfileBtn');
        if (diffBtn) diffBtn.onclick = function () {
            showStep(2);
        };

        var numInput = document.getElementById('abhaMobileNumber');
        if (numInput) numInput.onkeydown = function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchProfiles();
            }
        };

        var otpInput = document.getElementById('abhaMobileOtp');
        if (otpInput) otpInput.onkeydown = function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                verifyOtp();
            }
        };

        var compareBtn = document.getElementById('abhaMobileCompareBtn');
        if (compareBtn) compareBtn.onclick = function () {
            if (!verifiedProfile) return;
            var profile = verifiedProfile;
            if (profile.need_confirmation === false && Number(profile.patient_id || 0) > 0) {
                alertBox('success', 'This ABHA is already linked to HMS patient <strong>' + escapeHtml(profile.p_code || '') + '</strong>. Close this window when done.');
                if (typeof onVerified === 'function') onVerified(profile);
                return;
            }
            modalEl.addEventListener('hidden.bs.modal', function handler() {
                modalEl.removeEventListener('hidden.bs.modal', handler);
                if (typeof onVerified === 'function') onVerified(profile);
            });
            modal.hide();
        };

        modalEl.addEventListener('hidden.bs.modal', stopTimer);

        initialized = true;
        return true;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ensureInit);
    } else {
        ensureInit();
    }
    window.addEventListener('load', ensureInit);

    return {
        open: function (prefillMobile, callback) {
            ensureInit();
            if (!modal) {
                var modalEl = document.getElementById('abhaMobileModal');
                if (modalEl && typeof window.bootstrap !== 'undefined') {
                    modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                }
            }

            onVerified = typeof prefillMobile === 'function' ? prefillMobile : callback;
            searchTxnId = '';
            otpTxnId = '';
            searchMobile = '';
            foundProfiles = [];
            selectedProfile = null;
            verifiedProfile = null;
            stopTimer();

            var numInput = document.getElementById('abhaMobileNumber');
            if (numInput) numInput.value = typeof prefillMobile === 'string' ? prefillMobile : '';

            var otpInput = document.getElementById('abhaMobileOtp');
            if (otpInput) otpInput.value = '';

            var photoEl = document.getElementById('abhaMobilePhoto');
            if (photoEl) {
                photoEl.classList.add('d-none');
                photoEl.src = '';
            }

            var listEl = document.getElementById('abhaProfilesList');
            if (listEl) listEl.innerHTML = '';

            showStep(1);
            if (modal) {
                modal.show();
            } else {
                // Fallback using jQuery if bootstrap object not directly on window
                if (typeof window.jQuery !== 'undefined') {
                    window.jQuery('#abhaMobileModal').modal('show');
                }
            }

            window.setTimeout(function () {
                var el = document.getElementById('abhaMobileNumber');
                if (el) el.focus();
            }, 300);
        }
    };
})();
</script>
