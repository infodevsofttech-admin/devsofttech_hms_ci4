<?php
/**
 * Partial: "Create / Find ABHA Record" (ABDM M1 Aadhaar enrolment & retrieval) modal.
 *
 * Implements the unified 5-step wizard compliant with ABDM M1 Functional Specifications:
 *   Step 1: Aadhaar + Communication Mobile + Consent (CRT_ABHA_101, 102, 104 / VRFY_ABHA_401)
 *   Step 2: Aadhaar OTP verification (CRT_ABHA_105, 106, 107 / VRFY_ABHA_402, 403, 404, 405)
 *   Step 3: Communication Mobile OTP (only when communication mobile differs from Aadhaar mobile - CRT_ABHA_109)
 *   Step 4: ABHA Address selection/custom creation (CRT_ABHA_112)
 *   Step 5: ABHA Profile, Official Card & HIMS linking (CRT_ABHA_113, 114, 115 / TAGGING_UNIQUEPATIENTID_UNIQUEABHANUMBER)
 *
 * Include once per page:  <?= view('partials/abha_create_modal') ?>
 * Then call: window.AbhaCreateModal.open(function (profile) { ... }, prefillMobile);
 */
?>
<style>
    .abha-create-modal .modal-content { border:0; border-radius:12px; overflow:hidden; }
    .abha-create-modal .modal-header { border-bottom:1px solid #e5eaf1; padding:18px 22px; }
    .abha-create-modal .modal-body { padding:22px; }
    .abha-create-steps { display:flex; justify-content:center; align-items:center; margin-bottom:24px; gap:4px; flex-wrap:wrap; }
    .abha-create-step { display:flex; align-items:center; color:#8792a3; font-size:13px; font-weight:500; }
    .abha-create-step:not(:last-child)::after { content:""; width:34px; height:2px; background:#dce3ec; margin:0 8px; }
    .abha-create-step.done:not(:last-child)::after { background:#356cf4; }
    .abha-create-step span { width:30px; height:30px; display:grid; place-items:center; border-radius:50%; background:#eef2f6; font-weight:700; font-size:14px; }
    .abha-create-step.active span { color:#1748ce; background:#fff; border:2px solid #356cf4; box-shadow:0 0 0 4px #edf2ff; }
    .abha-create-step.done span { color:#fff; background:#356cf4; }
    .abha-create-consent { max-height:210px; overflow-y:auto; border:1px dashed #d4dbe6; border-radius:8px; padding:12px; background:#fbfcfe; transition: border-color .15s ease-in-out; }
    .abha-create-consent.border-danger { border-color:#dc3545 !important; border-style:solid !important; }
    #abhaCreateConsentAgreeWrap { transition: all .15s ease-in-out; }
    #abhaCreateConsentAgreeWrap.border-danger { border-color:#dc3545 !important; background-color:#fff5f5 !important; }
    .abha-create-address { display:block; border:1px solid #dce3ec; border-radius:8px; padding:11px 14px; cursor:pointer; font-family:ui-monospace,SFMono-Regular,Menlo,monospace; }
    .abha-create-address:has(input:checked) { border-color:#356cf4; background:#edf2ff; box-shadow:0 0 0 1px #356cf4; }
    .abha-profile-list { margin:0; }
    .abha-profile-list>div { display:grid; grid-template-columns:150px minmax(0,1fr); gap:14px; padding:10px 0; border-bottom:1px solid #edf0f4; }
    .abha-profile-list dt { color:#6b7688; font-weight:500; }
    .abha-profile-list dd { margin:0; text-align:right; font-weight:600; overflow-wrap:anywhere; }
    .abha-card-preview { min-height:230px; display:grid; place-items:center; border:1px solid #dce3ec; background:#f8fafc; border-radius:8px; padding:10px; }
    .abha-card-preview img { max-width:100%; max-height:360px; object-fit:contain; }
    .abha-policy-card { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; }
    @media (max-width:575.98px) {
        .abha-create-modal .modal-body{padding:16px}
        .abha-create-step:not(:last-child)::after{width:16px}
        .abha-profile-list>div{grid-template-columns:110px minmax(0,1fr)}
    }
</style>

<div class="modal fade abha-create-modal" id="abhaCreateModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-vcard text-primary me-2"></i>Create / Find ABHA Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="abha-create-steps" aria-label="ABHA creation progress">
                    <div class="abha-create-step active" data-step="1"><span>1</span><small class="d-none d-md-inline ms-1">Aadhaar Details</small></div>
                    <div class="abha-create-step" data-step="2"><span>2</span><small class="d-none d-md-inline ms-1">Aadhaar OTP</small></div>
                    <div class="abha-create-step" data-step="3"><span>3</span><small class="d-none d-md-inline ms-1">Mobile OTP</small></div>
                    <div class="abha-create-step" data-step="4"><span>4</span><small class="d-none d-md-inline ms-1">ABHA Address</small></div>
                    <div class="abha-create-step" data-step="5"><span>5</span><small class="d-none d-md-inline ms-1">ABHA Profile</small></div>
                </div>
                <div id="abhaCreateAlert"></div>

                <!-- Step 1: Aadhaar + communication mobile + consent -->
                <section id="abhaCreateStep1">
                    <div class="text-center mb-3">
                        <i class="bi bi-fingerprint text-primary" style="font-size:2.2rem"></i>
                        <h6 class="mt-2 mb-1">Enter Aadhaar &amp; Communication Details</h6>
                        <div class="text-muted small">Enter the 12-digit Aadhaar number and the mobile number for ABHA communication.</div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="abhaCreateAadhaar">Aadhaar Number <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text"><i class="bi bi-person-vcard"></i></span>
                                <input type="password" class="form-control" id="abhaCreateAadhaar" maxlength="14" inputmode="numeric" autocomplete="off" placeholder="0000 0000 0000">
                                <button class="btn btn-outline-secondary" type="button" id="abhaCreateAadhaarToggle" aria-label="Show Aadhaar"><i class="bi bi-eye"></i></button>
                            </div>
                            <div class="form-text"><i class="bi bi-lock-fill me-1"></i>Aadhaar is encrypted before transmission and never stored in HMS.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="abhaCreateMobile">Mobile Number (For ABHA Communication) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text"><i class="bi bi-phone"></i></span>
                                <input type="text" class="form-control" id="abhaCreateMobile" maxlength="10" inputmode="numeric" autocomplete="tel" placeholder="10-digit mobile">
                            </div>
                            <div class="form-text">Required by ABDM enrolment. Used for all health record notifications.</div>
                        </div>
                    </div>

                    <div id="abhaCreateConsentSection" class="mt-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="fw-semibold text-decoration-underline small">Consent Language (ABDM / UIDAI)</div>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="abhaCreateSelectAllConsent">Select All</button>
                        </div>
                        <div class="abha-create-consent" id="abhaCreateConsentWrap">
                            <div class="small text-muted mb-2">I hereby declare that:</div>
                            <div class="form-check mt-2">
                                <input class="form-check-input abha-consent-chk" type="checkbox" id="abhaCreateConsent1">
                                <label class="form-check-label small" for="abhaCreateConsent1">
                                    I am voluntarily sharing my Aadhaar Number / Virtual ID issued by the Unique Identification Authority of India (&quot;UIDAI&quot;), and my demographic information for the purpose of creating an Ayushman Bharat Health Account number (&quot;ABHA number&quot;) and Ayushman Bharat Health Account address (&quot;ABHA Address&quot;). I authorize NHA to use my Aadhaar number / Virtual ID for performing Aadhaar based authentication with UIDAI as per the provisions of the Aadhaar (Targeted Delivery of Financial and other Subsidies, Benefits and Services) Act, 2016 for the aforesaid purpose.
                                </label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input abha-consent-chk" type="checkbox" id="abhaCreateConsent2">
                                <label class="form-check-label small" for="abhaCreateConsent2">
                                    I consent to usage of my ABHA address and ABHA number for linking of my legacy (past) government health records and those which will be generated during this encounter.
                                </label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input abha-consent-chk" type="checkbox" id="abhaCreateConsent3">
                                <label class="form-check-label small" for="abhaCreateConsent3">
                                    I authorize the sharing of all my health records with healthcare provider(s) for the purpose of providing healthcare services to me during this encounter.
                                </label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input abha-consent-chk" type="checkbox" id="abhaCreateConsent4">
                                <label class="form-check-label small" for="abhaCreateConsent4">
                                    I confirm that I have duly informed and explained to the beneficiary the contents of the consent for the aforementioned purposes.
                                </label>
                            </div>
                        </div>

                        <!-- Primary 'I Agree' master checkbox as per ABDM M1 CRT_ABHA_102 -->
                        <div class="p-2 px-3 border rounded bg-white mt-2 shadow-sm d-flex align-items-center" id="abhaCreateConsentAgreeWrap">
                            <input class="form-check-input me-2 mt-0" type="checkbox" id="abhaCreateConsentAgree" style="width:1.25rem; height:1.25rem; cursor:pointer;">
                            <label class="form-check-label fw-bold text-dark mb-0" for="abhaCreateConsentAgree" style="cursor:pointer;">
                                I Agree &mdash; I have read and agree to all consent declarations for Aadhaar authentication and ABHA creation <span class="text-danger">*</span>
                            </label>
                        </div>
                        <div class="text-danger small mt-1 d-none" id="abhaCreateConsentError">
                            <i class="bi bi-exclamation-circle-fill me-1"></i>Consent is mandatory. Please check "I Agree" to proceed to the next step.
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="abhaCreateSendOtpBtn"><i class="bi bi-send me-1"></i>Send OTP</button>
                    </div>
                </section>

                <!-- Step 2: Aadhaar OTP -->
                <section id="abhaCreateStep2" class="d-none">
                    <div class="text-center mb-3">
                        <i class="bi bi-chat-dots-fill text-success" style="font-size:2.2rem"></i>
                        <h6 class="mt-2 mb-1">Enter Aadhaar OTP</h6>
                        <div class="text-muted small" id="abhaCreateOtpHint">We just sent an OTP on the Mobile Number linked with Aadhaar. Enter the OTP below to proceed with ABHA creation.</div>
                        <div class="text-muted small mt-1 d-none" id="abhaCreateOtpRequestId"></div>
                    </div>
                    <label class="form-label fw-semibold" for="abhaCreateOtp">6-digit Aadhaar OTP</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                        <input type="text" class="form-control" id="abhaCreateOtp" maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="Enter 6-digit OTP">
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3 gap-2 flex-wrap">
                        <div>
                            <button type="button" class="btn btn-link px-0 me-3" id="abhaCreateResendBtn" disabled>Resend OTP in 60s</button>
                            <button type="button" class="btn btn-link px-0 text-secondary" id="abhaCreateChangeAadhaarBtn">&larr; Change Aadhaar</button>
                        </div>
                        <button type="button" class="btn btn-success" id="abhaCreateVerifyOtpBtn"><i class="bi bi-shield-check me-1"></i>Verify &amp; Proceed</button>
                    </div>
                </section>

                <!-- Step 3: Communication Mobile OTP (Only if mobile != Aadhaar mobile) -->
                <section id="abhaCreateStep3" class="d-none">
                    <div class="text-center mb-3">
                        <i class="bi bi-phone-fill text-primary" style="font-size:2.2rem"></i>
                        <h6 class="mt-2 mb-1">Verify Communication Mobile Number</h6>
                        <div class="text-muted small" id="abhaCreateMobileHint"></div>
                    </div>
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-info-circle me-1"></i>Aadhaar authentication successful. Because your <strong>Communication Mobile Number</strong> is different from your Aadhaar-linked mobile, ABDM requires OTP verification for this number.
                    </div>
                    <label class="form-label fw-semibold" for="abhaCreateMobileOtp">6-digit Mobile OTP</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                        <input type="text" class="form-control" id="abhaCreateMobileOtp" maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="Enter 6-digit OTP">
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3 gap-2 flex-wrap">
                        <button type="button" class="btn btn-link px-0" id="abhaCreateMobileResendBtn" disabled>Resend OTP in 60s</button>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" id="abhaCreateSkipMobileBtn">Use Aadhaar-linked Mobile</button>
                            <button type="button" class="btn btn-primary" id="abhaCreateVerifyMobileBtn"><i class="bi bi-check2-circle me-1"></i>Verify &amp; Proceed</button>
                        </div>
                    </div>
                </section>

                <!-- Step 4: Choose / Create ABHA Address -->
                <section id="abhaCreateStep4" class="d-none">
                    <div class="text-center mb-3">
                        <i class="bi bi-at text-warning" style="font-size:2.2rem"></i>
                        <h6 class="mt-2 mb-1">Choose Your ABHA Address</h6>
                        <div class="text-muted small">Select a suggested ABHA address or enter a custom one. This is your digital health identifier.</div>
                    </div>
                    <div id="abhaCreateAddressAlert"></div>

                    <!-- Suggested Addresses -->
                    <label class="form-label fw-semibold mb-2"><i class="bi bi-stars text-warning me-1"></i>Suggested ABHA Addresses</label>
                    <div id="abhaCreateAddressList" class="d-grid gap-2 mb-3"></div>

                    <div class="text-center my-2">
                        <button type="button" class="btn btn-link text-decoration-none" id="abhaCreateCustomToggle">
                            <i class="bi bi-pencil-square me-1"></i>Use a custom address instead &rarr;
                        </button>
                    </div>

                    <!-- Custom Address Wrap -->
                    <div id="abhaCreateCustomWrap" class="d-none mb-3">
                        <label class="form-label fw-semibold" for="abhaCreateCustomAddress">Custom ABHA Address</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text"><i class="bi bi-at"></i></span>
                            <input type="text" class="form-control" id="abhaCreateCustomAddress" placeholder="username" autocomplete="off">
                            <span class="input-group-text text-muted">@sbx</span>
                        </div>
                        <div class="abha-policy-card p-3 mt-2">
                            <div class="fw-semibold small text-dark mb-1"><i class="bi bi-shield-check text-primary me-1"></i>ABDM ABHA Address Policy &amp; Rules:</div>
                            <ul class="small text-muted mb-0 ps-3">
                                <li>Length must be between <strong>8 and 18 characters</strong></li>
                                <li>Special characters allowed: at most <strong>1 dot (.)</strong> and/or <strong>1 underscore (_)</strong></li>
                                <li>Dot and underscore must be in between (not at the beginning or at the end)</li>
                                <li>Alphanumeric: letters and numbers only</li>
                            </ul>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-outline-secondary" id="abhaCreateAddressBackBtn"><i class="bi bi-arrow-left me-1"></i>Back</button>
                        <button type="button" class="btn btn-primary" id="abhaCreateConfirmAddressBtn"><i class="bi bi-check2-circle me-1"></i>Confirm Address &amp; Create ABHA</button>
                    </div>
                </section>

                <!-- Step 5: ABHA Profile & Official Card -->
                <section id="abhaCreateStep5" class="d-none">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <img id="abhaCreatePhoto" class="rounded d-none" alt="ABHA profile" style="width:76px;height:76px;object-fit:cover">
                                <div>
                                    <span class="badge bg-success-subtle text-success mb-1" id="abhaCreateBadgeWrap">
                                        <i class="bi bi-patch-check-fill me-1"></i><span id="abhaCreateStatusText">ABHA Ready</span>
                                    </span>
                                    <h4 class="mb-0" id="abhaCreateProfileName"></h4>
                                    <div class="text-muted" id="abhaCreateProfileAddress"></div>
                                </div>
                            </div>
                            <dl class="abha-profile-list">
                                <div><dt>ABHA Number</dt><dd id="abhaCreateProfileNumber">-</dd></div>
                                <div><dt>ABHA Address</dt><dd id="abhaCreateProfileId">-</dd></div>
                                <div><dt>Full Name</dt><dd id="abhaCreateProfileFullName">-</dd></div>
                                <div><dt>Date of Birth</dt><dd id="abhaCreateProfileDob">-</dd></div>
                                <div><dt>Gender</dt><dd id="abhaCreateProfileGender">-</dd></div>
                                <div><dt>Mobile</dt><dd id="abhaCreateProfileMobile">-</dd></div>
                                <div><dt>Address</dt><dd id="abhaCreateProfileFullAddress">-</dd></div>
                            </dl>
                        </div>
                        <div class="col-lg-6">
                            <div class="abha-card-preview" id="abhaCreateCardWrap">
                                <div class="text-center text-muted"><i class="bi bi-card-image fs-1 d-block mb-2"></i><span>Official ABHA card preview.</span></div>
                            </div>
                            <a class="btn btn-outline-primary w-100 mt-2 d-none" id="abhaCreateDownloadCard" download="ABHA-card"><i class="bi bi-download me-1"></i>Download ABHA Card</a>
                            <button type="button" class="btn btn-success w-100 mt-2" id="abhaCreateRegisterBtn"><i class="bi bi-person-check me-1"></i>Register / Link Patient in HMS</button>
                            <button type="button" class="btn btn-outline-primary w-100 mt-2" id="abhaCreateChooseAddressBtn"><i class="bi bi-at me-1"></i>Change / Create Custom ABHA Address</button>
                            <div class="form-text mt-2"><i class="bi bi-info-circle me-1"></i>Click <strong>Register / Link Patient</strong> to connect this ABHA to a patient record in HMS.</div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<script>
window.AbhaCreateModal = (function () {
    'use strict';

    var modal;
    var onCompleted = null;
    var createTxnId = '';
    var mobileTxnId = '';
    var communicationMobile = '';
    var createdProfile = null;
    var resendTimer = null;
    var resendRemaining = 0;
    var mobileResendTimer = null;
    var mobileResendRemaining = 0;
    var aadhaarResendCount = 0;
    var mobileResendCount = 0;

    function csrf() {
        var input = document.querySelector('input[name="<?= csrf_token() ?>"]');
        return input ? input.value : '<?= csrf_hash() ?>';
    }
    function escapeHtml(value) { return $('<div>').text(value == null ? '' : String(value)).html(); }
    function apiMessage(response, fallback) {
        var raw = response && (response.error_text || response.message)
            ? String(response.error_text || response.message)
            : fallback;
        if (/loginId/i.test(raw) && /invalid/i.test(raw)) {
            raw = 'Aadhaar Number is not valid. Valid 12-digit Aadhaar number is required.';
        }
        var message = escapeHtml(raw);
        return response && response.request_id
            ? message + ' <small class="d-block mt-1">Bridge Request ID: ' + escapeHtml(response.request_id) + '</small>'
            : message;
    }
    function validateVerhoeff(num) {
        num = digits(num);
        if (num.length !== 12 || num.charAt(0) === '0' || num.charAt(0) === '1') {
            return false;
        }
        var d = [
            [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            [1, 2, 3, 4, 0, 6, 7, 8, 9, 5],
            [2, 3, 4, 0, 1, 7, 8, 9, 5, 6],
            [3, 4, 0, 1, 2, 8, 9, 5, 6, 7],
            [4, 0, 1, 2, 3, 9, 5, 6, 7, 8],
            [5, 9, 8, 7, 6, 0, 4, 3, 2, 1],
            [6, 5, 9, 8, 7, 1, 0, 4, 3, 2],
            [7, 6, 5, 9, 8, 2, 1, 0, 4, 3],
            [8, 7, 6, 5, 9, 3, 2, 1, 0, 4],
            [9, 8, 7, 6, 5, 4, 3, 2, 1, 0]
        ];
        var p = [
            [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            [1, 5, 7, 6, 2, 8, 3, 0, 9, 4],
            [5, 8, 0, 3, 7, 9, 6, 1, 4, 2],
            [8, 9, 1, 6, 0, 4, 3, 5, 2, 7],
            [9, 4, 5, 3, 1, 2, 6, 8, 7, 0],
            [4, 2, 8, 6, 5, 7, 3, 9, 0, 1],
            [2, 7, 9, 3, 8, 0, 6, 4, 1, 5],
            [7, 0, 4, 6, 9, 1, 3, 2, 5, 8]
        ];
        var c = 0;
        var inverted = num.split('').reverse();
        for (var i = 0; i < inverted.length; i++) {
            c = d[c][p[i % 8][parseInt(inverted[i], 10)]];
        }
        return (c === 0);
    }
    function alertBox(type, message) {
        $('#abhaCreateAlert').html(message ? '<div class="alert alert-' + type + ' py-2">' + message + '</div>' : '');
    }
    function showStep(step) {
        $('#abhaCreateStep1,#abhaCreateStep2,#abhaCreateStep3,#abhaCreateStep4,#abhaCreateStep5').addClass('d-none');
        $('#abhaCreateStep' + step).removeClass('d-none');
        $('.abha-create-step').each(function () {
            var itemStep = Number($(this).data('step'));
            $(this).toggleClass('active', itemStep === step).toggleClass('done', itemStep < step);
        });
        alertBox('', '');
    }
    function stopTimers() {
        if (resendTimer) window.clearInterval(resendTimer);
        if (mobileResendTimer) window.clearInterval(mobileResendTimer);
        resendTimer = null;
        mobileResendTimer = null;
    }
    function startResendTimer(button, seconds) {
        resendRemaining = Math.max(60, Number(seconds) || 60);
        button.prop('disabled', true);
        function tick() {
            if (resendRemaining <= 0) {
                window.clearInterval(resendTimer);
                resendTimer = null;
                if (aadhaarResendCount < 2) {
                    var remaining = 2 - aadhaarResendCount;
                    button.prop('disabled', false).text('Resend OTP (' + remaining + ' left)');
                } else {
                    button.prop('disabled', true).text('Resend limit reached (max 2)');
                }
                return;
            }
            button.text('Resend OTP in ' + resendRemaining + 's');
            resendRemaining--;
        }
        if (resendTimer) window.clearInterval(resendTimer);
        tick();
        resendTimer = window.setInterval(tick, 1000);
    }
    function startMobileResendTimer(button) {
        mobileResendRemaining = 60;
        button.prop('disabled', true);
        function tick() {
            if (mobileResendRemaining <= 0) {
                window.clearInterval(mobileResendTimer);
                mobileResendTimer = null;
                if (mobileResendCount < 2) {
                    var remaining = 2 - mobileResendCount;
                    button.prop('disabled', false).text('Resend OTP (' + remaining + ' left)');
                } else {
                    button.prop('disabled', true).text('Resend limit reached (max 2)');
                }
                return;
            }
            button.text('Resend OTP in ' + mobileResendRemaining + 's');
            mobileResendRemaining--;
        }
        if (mobileResendTimer) window.clearInterval(mobileResendTimer);
        tick();
        mobileResendTimer = window.setInterval(tick, 1000);
    }
    function digits(value) { return String(value || '').replace(/\D/g, ''); }
    function maskMobile(value) {
        var mobile = digits(value);
        return mobile.length === 10 ? '******' + mobile.slice(-4) : mobile;
    }
    function genderText(value) {
        var gender = String(value || '').toUpperCase();
        if (gender === 'M' || gender === '1' || gender === 'MALE') return 'Male';
        if (gender === 'F' || gender === '2' || gender === 'FEMALE') return 'Female';
        if (gender === 'O' || gender === '3' || gender === 'OTHER') return 'Other';
        return gender || '-';
    }
    function formatAbha(value) { return String(value || '').replace(/(\d{2})(\d{4})(\d{4})(\d{4})/, '$1-$2-$3-$4'); }
    function photoSrc(value) {
        if (!value) return '';
        return String(value).indexOf('data:') === 0 ? value : 'data:image/jpeg;base64,' + value;
    }
    function mergeProfile(baseProfile, response) {
        var merged = $.extend({}, baseProfile || {});
        $.each(response || {}, function(key, value) {
            if (value !== null && value !== undefined && value !== '') {
                merged[key] = value;
            }
        });
        return merged;
    }

    function sendAadhaarOtp(isResend) {
        var aadhaar = digits($('#abhaCreateAadhaar').val());
        var mobile = digits($('#abhaCreateMobile').val());
        var totalConsent = $('.abha-consent-chk').length;
        var checkedConsent = $('.abha-consent-chk:checked').length;
        var isAllConsentChecked = (totalConsent > 0 && checkedConsent === totalConsent) || $('#abhaCreateConsentAgree').is(':checked');

        // Reset visual validation states
        $('#abhaCreateAadhaar').removeClass('is-invalid');
        $('#abhaCreateMobile').removeClass('is-invalid');
        $('#abhaCreateConsentWrap').removeClass('border-danger');
        $('#abhaCreateConsentAgreeWrap').removeClass('border-danger');
        $('#abhaCreateConsentError').addClass('d-none');

        if (aadhaar.length !== 12 || !validateVerhoeff(aadhaar)) {
            $('#abhaCreateAadhaar').addClass('is-invalid').trigger('focus');
            alertBox('warning', 'Aadhaar Number is not valid. Valid 12-digit Aadhaar number is required.');
            return;
        }
        if (mobile.length !== 10 || !/^[6-9]\d{9}$/.test(mobile)) {
            $('#abhaCreateMobile').addClass('is-invalid').trigger('focus');
            alertBox('warning', 'Please enter a valid 10-digit mobile number for ABHA communication.');
            return;
        }
        if (!isAllConsentChecked) {
            $('#abhaCreateConsentWrap').addClass('border-danger');
            $('#abhaCreateConsentAgreeWrap').addClass('border-danger');
            $('#abhaCreateConsentError').removeClass('d-none');
            alertBox('warning', 'Consent is mandatory. Please review and check "I Agree" to all consent declarations before proceeding to the next step.');
            $('#abhaCreateConsentAgree').trigger('focus');
            return;
        }

        if (isResend === true) {
            if (aadhaarResendCount >= 2) {
                alertBox('warning', 'Maximum 2 resend attempts reached for Aadhaar OTP.');
                return;
            }
            aadhaarResendCount++;
        } else {
            aadhaarResendCount = 0;
        }

        communicationMobile = mobile;
        var button = $('#abhaCreateSendOtpBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Sending');
        $.post('<?= base_url('abha/create/initiate') ?>', {
            aadhaar: aadhaar,
            auth_type: 'aadhaar_otp',
            consent: 1,
            '<?= csrf_token() ?>': csrf()
        }, function (response) {
            button.prop('disabled', false).html('<i class="bi bi-send me-1"></i>Send OTP');
            if (!response || response.ok != 1) {
                alertBox('danger', apiMessage(response, 'Unable to send Aadhaar OTP.'));
                return;
            }
            createTxnId = response.txn_id || '';
            $('#abhaCreateOtp').val('');
            var destination = response.masked_mobile || ((response.message || '').match(/\*{2,}\d{4}/) || [''])[0] || '******XXXX';
            var promptMessage = 'We just sent an OTP on the Mobile Number ' + destination + ' linked with Aadhaar. Enter the OTP below to proceed with ABHA creation.';
            $('#abhaCreateOtpHint').text(promptMessage);
            $('#abhaCreateOtpRequestId')
                .toggleClass('d-none', !response.request_id)
                .text(response.request_id ? 'Bridge Request ID: ' + response.request_id : '');
            showStep(2);
            startResendTimer($('#abhaCreateResendBtn'), 60);
            $('#abhaCreateOtp').trigger('focus');
        }, 'json').fail(function (xhr) {
            button.prop('disabled', false).html('<i class="bi bi-send me-1"></i>Send OTP');
            alertBox('danger', apiMessage(xhr.responseJSON, 'Unable to send Aadhaar OTP.'));
        });
    }

    function verifyAadhaarOtp() {
        var otp = digits($('#abhaCreateOtp').val());
        if (otp.length !== 6) {
            alertBox('warning', 'Enter the 6-digit OTP.');
            return;
        }
        var button = $('#abhaCreateVerifyOtpBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Verifying');
        $.post('<?= base_url('abha/create/verify_otp') ?>', { txn_id: createTxnId, otp: otp, mobile: communicationMobile, '<?= csrf_token() ?>': csrf() }, function (response) {
            button.prop('disabled', false).html('<i class="bi bi-shield-check me-1"></i>Verify &amp; Proceed');
            if (!response || response.ok != 1) {
                alertBox('danger', apiMessage(response, 'Invalid OTP - Please enter a valid OTP. Entered OTP is either expired or incorrect.'));
                return;
            }
            stopTimers();
            createdProfile = response;
            createTxnId = response.txn_id || createTxnId;

            // Case 1: ABHA Already Exists (VRFY_ABHA_404 & User Step 9-10)
            if (response.already_exists) {
                renderProfile(response);
                alertBox('success', '<i class="bi bi-info-circle-fill me-1"></i><strong>ABHA Already Exist:</strong> An active ABHA account is registered with this Aadhaar Number.');
                return;
            }

            // Case 2: New ABHA (CRT_ABHA_101 & User Steps 4-8)
            // Check communication mobile vs Aadhaar-linked mobile
            if (response.is_comm_mobile_same) {
                // CRT_ABHA_108: Mobile already verified by Aadhaar OTP -> Skip mobile OTP, go straight to ABHA address
                loadAddressSuggestions();
                alertBox('info', '<i class="bi bi-check-circle-fill me-1"></i>NO ABHA user registered with this Aadhaar Number. Communication mobile matches Aadhaar-linked mobile. Please choose your ABHA address.');
                return;
            } else {
                // CRT_ABHA_109: Different mobile -> Verify communication mobile OTP
                requestMobileOtp(false);
                return;
            }
        }, 'json').fail(function (xhr) {
            button.prop('disabled', false).html('<i class="bi bi-shield-check me-1"></i>Verify &amp; Proceed');
            alertBox('danger', apiMessage(xhr.responseJSON, 'OTP verification failed.'));
        });
    }

    function requestMobileOtp(isResend) {
        if (isResend === true) {
            if (mobileResendCount >= 2) {
                alertBox('warning', 'Maximum 2 resend attempts reached for Mobile OTP.');
                return;
            }
            mobileResendCount++;
        } else {
            mobileResendCount = 0;
        }

        alertBox('info', 'Aadhaar verified. Requesting OTP for communication mobile...');
        $.post('<?= base_url('abha/create/communication') ?>', { mobile: communicationMobile, txn_id: createTxnId, '<?= csrf_token() ?>': csrf() }, function (response) {
            if (!response || response.ok != 1) {
                // Fallback to address creation if communication OTP cannot be dispatched
                loadAddressSuggestions();
                alertBox('warning', 'Aadhaar verified, but alternate mobile OTP could not be sent: ' + apiMessage(response, 'Unable to send mobile OTP.') + ' Proceeding with Aadhaar mobile.');
                return;
            }
            mobileTxnId = response.txn_id || createTxnId;
            var masked = maskMobile(communicationMobile);
            $('#abhaCreateMobileHint').html('We just sent a separate verification OTP to your Communication Mobile Number <strong>' + escapeHtml(masked) + '</strong>. Enter the OTP below to proceed.');
            $('#abhaCreateMobileOtp').val('');
            showStep(3);
            startMobileResendTimer($('#abhaCreateMobileResendBtn'));
            $('#abhaCreateMobileOtp').trigger('focus');
        }, 'json').fail(function (xhr) {
            loadAddressSuggestions();
            alertBox('warning', 'Aadhaar verified, but alternate mobile OTP could not be sent: ' + apiMessage(xhr.responseJSON, 'Unable to send mobile OTP.') + ' Proceeding with Aadhaar mobile.');
        });
    }

    function verifyMobileOtp() {
        var otp = digits($('#abhaCreateMobileOtp').val());
        if (otp.length !== 6) {
            alertBox('warning', 'Enter the 6-digit mobile OTP.');
            return;
        }
        var button = $('#abhaCreateVerifyMobileBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Verifying');
        $.post('<?= base_url('abha/create/verify_comm_otp') ?>', { txn_id: mobileTxnId, otp: otp, mobile: communicationMobile, '<?= csrf_token() ?>': csrf() }, function (response) {
            button.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i>Verify &amp; Proceed');
            if (!response || response.ok != 1) {
                alertBox('danger', apiMessage(response, 'Mobile OTP verification failed.'));
                return;
            }
            stopTimers();
            createdProfile = mergeProfile(createdProfile, response);
            loadAddressSuggestions();
            alertBox('success', '<i class="bi bi-check-circle-fill me-1"></i>Communication mobile verified successfully! Now choose your ABHA address.');
        }, 'json').fail(function (xhr) {
            button.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i>Verify &amp; Proceed');
            alertBox('danger', apiMessage(xhr.responseJSON, 'Mobile OTP verification failed.'));
        });
    }

    function loadAddressSuggestions() {
        showStep(4);
        $('#abhaCreateAddressAlert').empty();
        $('#abhaCreateAddressList').html('<div class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-2"></span>Loading address suggestions…</div>');
        $('#abhaCreateCustomWrap').addClass('d-none');
        $('#abhaCreateCustomAddress').val('');

        $.post('<?= base_url('abha/create/address_suggestions') ?>', { txn_id: createTxnId, '<?= csrf_token() ?>': csrf() }, function (response) {
            if (!response || response.ok != 1 || !(response.suggestions || []).length) {
                $('#abhaCreateAddressList').html('<div class="alert alert-warning py-2 mb-0">' + apiMessage(response, 'No suggested ABHA addresses are available from the bridge.') + ' Please enter a custom address below.</div>');
                $('#abhaCreateCustomWrap').removeClass('d-none');
                return;
            }
            var html = '';
            (response.suggestions || []).forEach(function (address, index) {
                html += '<label class="abha-create-address">'
                    + '<input class="form-check-input me-2" type="radio" name="abhaCreateAddressPick" value="' + escapeHtml(address) + '"' + (index === 0 ? ' checked' : '') + '>'
                    + escapeHtml(address)
                    + '</label>';
            });
            $('#abhaCreateAddressList').html(html);
        }, 'json').fail(function (xhr) {
            $('#abhaCreateAddressList').html('<div class="alert alert-warning py-2 mb-0">' + apiMessage(xhr.responseJSON, 'Unable to load address suggestions.') + ' Please enter a custom address below.</div>');
            $('#abhaCreateCustomWrap').removeClass('d-none');
        });
    }

    function confirmAddress() {
        var custom = $.trim($('#abhaCreateCustomAddress').val());
        var selected = $('input[name="abhaCreateAddressPick"]:checked').val() || '';
        var address = $('#abhaCreateCustomWrap').hasClass('d-none') ? selected : (custom || selected);
        if (!address) {
            $('#abhaCreateAddressAlert').html('<div class="alert alert-warning py-2">Select a suggested address or enter a custom one.</div>');
            return;
        }

        var handle = address.split('@')[0];
        // Enforce CRT_ABHA_112 validation rules
        if (handle.length < 8 || handle.length > 18) {
            $('#abhaCreateAddressAlert').html('<div class="alert alert-warning py-2">ABHA address must be between 8 and 18 characters.</div>');
            return;
        }
        if (/^[._]|[._]$/.test(handle)) {
            $('#abhaCreateAddressAlert').html('<div class="alert alert-warning py-2">Special character dot (.) and underscore (_) can only be in between, not at the beginning or end.</div>');
            return;
        }
        var dots = (handle.match(/\./g) || []).length;
        var underscores = (handle.match(/_/g) || []).length;
        if (dots > 1 || underscores > 1 || !/^[a-zA-Z0-9._]+$/.test(handle)) {
            $('#abhaCreateAddressAlert').html('<div class="alert alert-warning py-2">Only letters, numbers, at most 1 dot (.) and/or 1 underscore (_) are allowed.</div>');
            return;
        }

        var button = $('#abhaCreateConfirmAddressBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Creating ABHA…');
        $.post('<?= base_url('abha/create/address') ?>', { txn_id: createTxnId, abha_address: address, '<?= csrf_token() ?>': csrf() }, function (response) {
            button.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i>Confirm Address &amp; Create ABHA');
            if (!response || response.ok != 1) {
                var err = apiMessage(response, 'Unable to set the ABHA address.');
                if (/already exist|already in use|taken/i.test(err)) {
                    err = 'ABHA Address is already exist';
                }
                $('#abhaCreateAddressAlert').html('<div class="alert alert-danger py-2">' + err + '</div>');
                return;
            }
            if (createdProfile) {
                createdProfile.abha_address = response.abha_address || address;
            }
            renderProfile(createdProfile || {});
            alertBox('success', '<i class="bi bi-check-circle-fill me-1"></i><strong>ABHA Created Successfully!</strong> ABHA address set to <strong>' + escapeHtml(response.abha_address || address) + '</strong>.');
        }, 'json').fail(function (xhr) {
            button.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i>Confirm Address &amp; Create ABHA');
            var err = apiMessage(xhr.responseJSON, 'Unable to set the ABHA address.');
            if (/already exist|already in use|taken/i.test(err)) {
                err = 'ABHA Address is already exist';
            }
            $('#abhaCreateAddressAlert').html('<div class="alert alert-danger py-2">' + err + '</div>');
        });
    }

    function renderProfile(profile) {
        createdProfile = profile;
        $('#abhaCreateProfileName').text(profile.name || '-');
        $('#abhaCreateProfileFullName').text(profile.name || '-');
        $('#abhaCreateProfileAddress').text(profile.abha_address || '-');
        $('#abhaCreateProfileNumber').text(formatAbha(profile.abha_number) || '-');
        $('#abhaCreateProfileId').text(profile.abha_address || '-');
        $('#abhaCreateProfileDob').text(profile.dob || '-');
        $('#abhaCreateProfileGender').text(genderText(profile.gender));
        $('#abhaCreateProfileMobile').text(profile.mobile
            ? maskMobile(profile.mobile) + (profile.mobile_source === 'enrolment' ? ' (submitted at enrolment)' : '')
            : '-');
        $('#abhaCreateProfileFullAddress').text([profile.address, profile.district, profile.state, profile.zip].filter(Boolean).join(', ') || '-');
        $('#abhaCreateStatusText').text(profile.abha_number ? (profile.already_exists ? 'ABHA Active' : 'ABHA Ready') : 'ABHA Verified');

        var photo = photoSrc(profile.photo);
        $('#abhaCreatePhoto').toggleClass('d-none', !photo).attr('src', photo || '');

        var card = profile.card_base64 || '';
        if (card) {
            var cardSrc = String(card).indexOf('data:') === 0 ? card : 'data:' + (profile.card_content_type || 'image/png') + ';base64,' + card;
            var isOfficial = profile.card_source !== 'generated';
            var note = isOfficial
                ? ''
                : '<div class="small text-warning mt-1"><i class="bi bi-exclamation-triangle me-1"></i>Provisional card generated by Bridge.</div>';
            $('#abhaCreateCardWrap').html('<img src="' + cardSrc + '" alt="ABHA card">' + note);
            $('#abhaCreateDownloadCard').attr('href', cardSrc).removeClass('d-none');
        } else {
            var reason = profile.card_message ? escapeHtml(profile.card_message) : 'Official ABHA card was not returned by the Bridge.';
            $('#abhaCreateCardWrap').html('<div class="text-center text-muted"><i class="bi bi-card-image fs-1 d-block mb-2"></i><span>' + reason + '</span></div>');
            $('#abhaCreateDownloadCard').addClass('d-none');
        }
        showStep(5);
        // Mark previous steps as done
        $('.abha-create-step').each(function () {
            var itemStep = Number($(this).data('step'));
            $(this).toggleClass('active', itemStep === 5).toggleClass('done', itemStep < 5);
        });
    }

    $(function () {
        modal = new bootstrap.Modal(document.getElementById('abhaCreateModal'));

        $('#abhaCreateAadhaarToggle').on('click', function () {
            var field = $('#abhaCreateAadhaar');
            var isHidden = field.attr('type') === 'password';
            field.attr('type', isHidden ? 'text' : 'password');
            $(this).find('i').attr('class', isHidden ? 'bi bi-eye-slash' : 'bi bi-eye');
        });

        function clearConsentValidation() {
            $('#abhaCreateConsentWrap').removeClass('border-danger');
            $('#abhaCreateConsentAgreeWrap').removeClass('border-danger');
            $('#abhaCreateConsentError').addClass('d-none');
            if ($('#abhaCreateAlert').text().indexOf('Consent is mandatory') !== -1) {
                alertBox('', '');
            }
        }

        $('#abhaCreateConsentAgree').on('change', function () {
            var checked = $(this).is(':checked');
            $('.abha-consent-chk').prop('checked', checked);
            $('#abhaCreateSelectAllConsent').text(checked ? 'Deselect All' : 'Select All');
            if (checked) {
                clearConsentValidation();
            }
        });

        $('.abha-consent-chk').on('change', function () {
            var total = $('.abha-consent-chk').length;
            var checked = $('.abha-consent-chk:checked').length;
            var allChecked = (total > 0 && checked === total);
            $('#abhaCreateConsentAgree').prop('checked', allChecked);
            $('#abhaCreateSelectAllConsent').text(allChecked ? 'Deselect All' : 'Select All');
            if (allChecked) {
                clearConsentValidation();
            }
        });

        $('#abhaCreateSelectAllConsent').on('click', function () {
            var total = $('.abha-consent-chk').length;
            var checked = $('.abha-consent-chk:checked').length;
            var shouldCheck = (checked < total);
            $('.abha-consent-chk').prop('checked', shouldCheck);
            $('#abhaCreateConsentAgree').prop('checked', shouldCheck);
            $(this).text(shouldCheck ? 'Deselect All' : 'Select All');
            if (shouldCheck) {
                clearConsentValidation();
            }
        });

        $('#abhaCreateAadhaar').on('input', function () {
            $(this).removeClass('is-invalid');
            if ($('#abhaCreateAlert').text().indexOf('Aadhaar Number is not valid') !== -1) {
                alertBox('', '');
            }
        });

        $('#abhaCreateMobile').on('input', function () {
            $(this).removeClass('is-invalid');
            if ($('#abhaCreateAlert').text().indexOf('mobile number') !== -1) {
                alertBox('', '');
            }
        });

        $('#abhaCreateSendOtpBtn').on('click', function () { sendAadhaarOtp(false); });
        $('#abhaCreateResendBtn').on('click', function () { sendAadhaarOtp(true); });
        $('#abhaCreateChangeAadhaarBtn').on('click', function () { stopTimers(); showStep(1); });
        $('#abhaCreateVerifyOtpBtn').on('click', verifyAadhaarOtp);
        $('#abhaCreateOtp').on('keydown', function (event) { if (event.key === 'Enter') { event.preventDefault(); verifyAadhaarOtp(); } });

        $('#abhaCreateVerifyMobileBtn').on('click', verifyMobileOtp);
        $('#abhaCreateMobileResendBtn').on('click', function () { requestMobileOtp(true); });
        $('#abhaCreateSkipMobileBtn').on('click', function () {
            stopTimers();
            loadAddressSuggestions();
            alertBox('info', 'Proceeding with Aadhaar-linked mobile for ABHA.');
        });
        $('#abhaCreateMobileOtp').on('keydown', function (event) { if (event.key === 'Enter') { event.preventDefault(); verifyMobileOtp(); } });

        $('#abhaCreateChooseAddressBtn').on('click', loadAddressSuggestions);
        $('#abhaCreateAddressBackBtn').on('click', function () {
            if (createdProfile && createdProfile.already_exists) {
                showStep(5);
            } else {
                showStep(2);
            }
        });
        $('#abhaCreateCustomToggle').on('click', function () {
            $('#abhaCreateCustomWrap').toggleClass('d-none');
            if (!$('#abhaCreateCustomWrap').hasClass('d-none')) {
                $('#abhaCreateCustomAddress').trigger('focus');
            }
        });
        $('#abhaCreateConfirmAddressBtn').on('click', confirmAddress);
        $('#abhaCreateCustomAddress').on('keydown', function (event) { if (event.key === 'Enter') { event.preventDefault(); confirmAddress(); } });

        $('#abhaCreateRegisterBtn').on('click', function () {
            if (!createdProfile) return;
            var profile = createdProfile;
            $('#abhaCreateModal').one('hidden.bs.modal', function () {
                if (typeof onCompleted === 'function') onCompleted(profile);
            });
            modal.hide();
        });

        $('#abhaCreateModal').on('hidden.bs.modal', stopTimers);
    });

    return {
        open: function (callback, prefillMobile) {
            onCompleted = callback;
            createTxnId = '';
            mobileTxnId = '';
            communicationMobile = '';
            createdProfile = null;
            aadhaarResendCount = 0;
            mobileResendCount = 0;
            stopTimers();
            $('#abhaCreateAadhaar,#abhaCreateMobile,#abhaCreateOtp,#abhaCreateMobileOtp,#abhaCreateCustomAddress').val('').removeClass('is-invalid');
            $('#abhaCreateMobile').val(digits(prefillMobile));
            $('#abhaCreateOtpRequestId').addClass('d-none').text('');
            $('#abhaCreateConsent1,#abhaCreateConsent2,#abhaCreateConsent3,#abhaCreateConsent4,#abhaCreateConsentAgree').prop('checked', false);
            $('#abhaCreateConsentWrap,#abhaCreateConsentAgreeWrap').removeClass('border-danger');
            $('#abhaCreateConsentError').addClass('d-none');
            $('#abhaCreateSelectAllConsent').text('Select All');
            $('#abhaCreatePhoto,#abhaCreateDownloadCard').addClass('d-none');
            showStep(1);
            modal.show();
        }
    };
})();
</script>
