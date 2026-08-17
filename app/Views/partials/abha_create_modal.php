<?php
/**
 * Partial: "Create ABHA" (ABDM M1 Aadhaar enrolment) modal.
 *
 * Mirrors partials/abha_verify_modal.php so both ABHA journeys behave the same:
 *   1. Aadhaar + communication mobile + consent
 *   2. Aadhaar OTP
 *   3. Communication-mobile OTP (only when it differs from the ABHA mobile)
 *   4. Verified profile + official ABHA card
 *   5. ABHA address selection (suggested or custom)
 *
 * Handing the profile to the patient-match modal stays the caller's job, exactly
 * like the verify flow — this modal never writes to patient_master by itself.
 *
 * Include once per page:  <?= view('partials/abha_create_modal') ?>
 * Then call: window.AbhaCreateModal.open(function (profile) { ... });
 */
?>
<style>
    .abha-create-modal .modal-content { border:0; border-radius:12px; overflow:hidden; }
    .abha-create-modal .modal-header { border-bottom:1px solid #e5eaf1; padding:18px 22px; }
    .abha-create-modal .modal-body { padding:22px; }
    .abha-create-steps { display:flex; justify-content:center; align-items:center; margin-bottom:24px; }
    .abha-create-step { display:flex; align-items:center; color:#8792a3; }
    .abha-create-step:not(:last-child)::after { content:""; width:44px; height:2px; background:#dce3ec; margin:0 7px; }
    .abha-create-step.done:not(:last-child)::after { background:#356cf4; }
    .abha-create-step span { width:30px; height:30px; display:grid; place-items:center; border-radius:50%; background:#eef2f6; font-weight:700; }
    .abha-create-step.active span { color:#1748ce; background:#fff; border:2px solid #356cf4; box-shadow:0 0 0 4px #edf2ff; }
    .abha-create-step.done span { color:#fff; background:#356cf4; }
    .abha-create-consent { max-height:190px; overflow-y:auto; border:1px dashed #d4dbe6; border-radius:8px; padding:12px; background:#fbfcfe; }
    .abha-create-address { display:block; border:1px solid #dce3ec; border-radius:8px; padding:11px 14px; cursor:pointer; font-family:ui-monospace,SFMono-Regular,Menlo,monospace; }
    .abha-create-address:has(input:checked) { border-color:#356cf4; background:#edf2ff; box-shadow:0 0 0 1px #356cf4; }
    .abha-profile-list { margin:0; }
    .abha-profile-list>div { display:grid; grid-template-columns:150px minmax(0,1fr); gap:14px; padding:10px 0; border-bottom:1px solid #edf0f4; }
    .abha-profile-list dt { color:#6b7688; font-weight:500; }
    .abha-profile-list dd { margin:0; text-align:right; font-weight:600; overflow-wrap:anywhere; }
    .abha-card-preview { min-height:230px; display:grid; place-items:center; border:1px solid #dce3ec; background:#f8fafc; }
    .abha-card-preview img { max-width:100%; max-height:360px; object-fit:contain; }
    @media (max-width:575.98px) { .abha-create-modal .modal-body{padding:16px}.abha-create-step:not(:last-child)::after{width:22px}.abha-profile-list>div{grid-template-columns:110px minmax(0,1fr)} }
</style>

<div class="modal fade abha-create-modal" id="abhaCreateModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus-fill text-primary me-2"></i>Create ABHA</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="abha-create-steps" aria-label="ABHA creation progress">
                    <div class="abha-create-step active" data-step="1"><span>1</span></div>
                    <div class="abha-create-step" data-step="2"><span>2</span></div>
                    <div class="abha-create-step" data-step="3"><span>3</span></div>
                    <div class="abha-create-step" data-step="4"><span>4</span></div>
                    <div class="abha-create-step" data-step="5"><span>5</span></div>
                </div>
                <div id="abhaCreateAlert"></div>

                <!-- Step 1: Aadhaar + communication mobile + consent -->
                <section id="abhaCreateStep1">
                    <div class="text-center mb-3">
                        <i class="bi bi-fingerprint text-primary" style="font-size:2.2rem"></i>
                        <h6 class="mt-2 mb-1">Enter Aadhaar Details</h6>
                        <div class="text-muted small">An OTP will be sent to the mobile number linked with this Aadhaar.</div>
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
                            <div class="form-text">Required by ABDM enrolment. A different number is verified through the Aadhaar-bound enrolment transaction.</div>
                        </div>
                    </div>

                    <div class="abha-create-consent mt-3">
                        <div class="fw-semibold mb-2 text-decoration-underline">Consent Language</div>
                        <div class="small">I hereby declare that:</div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="abhaCreateConsent1">
                            <label class="form-check-label small" for="abhaCreateConsent1">
                                I am voluntarily sharing my Aadhaar Number / Virtual ID issued by the Unique Identification Authority of India (&quot;UIDAI&quot;), and my demographic information for the purpose of creating an Ayushman Bharat Health Account number (&quot;ABHA number&quot;) and Ayushman Bharat Health Account address (&quot;ABHA Address&quot;). I authorize NHA to use my Aadhaar number / Virtual ID for performing Aadhaar based authentication with UIDAI as per the provisions of the Aadhaar (Targeted Delivery of Financial and other Subsidies, Benefits and Services) Act, 2016 for the aforesaid purpose.
                            </label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="abhaCreateConsent2">
                            <label class="form-check-label small" for="abhaCreateConsent2">
                                I consent to usage of my ABHA address and ABHA number for linking of my legacy (past) government health records and those which will be generated during this encounter.
                            </label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="abhaCreateConsent3">
                            <label class="form-check-label small" for="abhaCreateConsent3">
                                I authorize the sharing of all my health records with healthcare provider(s) for the purpose of providing healthcare services to me during this encounter.
                            </label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="abhaCreateConsent4">
                            <label class="form-check-label small" for="abhaCreateConsent4">
                                I confirm that I have duly informed and explained to the beneficiary the contents of the consent for the aforementioned purposes.
                            </label>
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
                        <h6 class="mt-2 mb-1">Enter OTP</h6>
                        <div class="text-muted small" id="abhaCreateOtpHint">OTP sent to the Aadhaar registered mobile number.</div>
                        <div class="text-muted small mt-1 d-none" id="abhaCreateOtpRequestId"></div>
                    </div>
                    <label class="form-label fw-semibold" for="abhaCreateOtp">6-digit OTP</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                        <input type="text" class="form-control" id="abhaCreateOtp" maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="Enter OTP">
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3 gap-2 flex-wrap">
                        <div>
                            <button type="button" class="btn btn-link px-0 me-3" id="abhaCreateResendBtn" disabled>Resend OTP in 60s</button>
                            <button type="button" class="btn btn-link px-0 text-secondary" id="abhaCreateChangeAadhaarBtn">&larr; Change Aadhaar</button>
                        </div>
                        <button type="button" class="btn btn-success" id="abhaCreateVerifyOtpBtn"><i class="bi bi-shield-check me-1"></i>Verify &amp; Create ABHA</button>
                    </div>
                </section>

                <!-- Step 3: communication-mobile OTP -->
                <section id="abhaCreateStep3" class="d-none">
                    <div class="text-center mb-3">
                        <i class="bi bi-phone-fill text-primary" style="font-size:2.2rem"></i>
                        <h6 class="mt-2 mb-1">Verify New Mobile Number</h6>
                        <div class="text-muted small" id="abhaCreateMobileHint"></div>
                    </div>
                    <label class="form-label fw-semibold" for="abhaCreateMobileOtp">Mobile OTP</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                        <input type="text" class="form-control" id="abhaCreateMobileOtp" maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="6-digit OTP">
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3 gap-2 flex-wrap">
                        <button type="button" class="btn btn-link px-0" id="abhaCreateMobileResendBtn" disabled>Resend OTP in 60s</button>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" id="abhaCreateChangeMobileBtn">Change Mobile</button>
                            <button type="button" class="btn btn-outline-secondary" id="abhaCreateSkipMobileBtn">Use Aadhaar-linked Mobile</button>
                            <button type="button" class="btn btn-primary" id="abhaCreateVerifyMobileBtn"><i class="bi bi-check2-circle me-1"></i>Verify &amp; Update Mobile</button>
                        </div>
                    </div>
                </section>

                <!-- Step 4: profile + card -->
                <section id="abhaCreateStep4" class="d-none">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <img id="abhaCreatePhoto" class="rounded d-none" alt="ABHA profile" style="width:76px;height:76px;object-fit:cover">
                                <div>
                                    <span class="badge bg-success-subtle text-success mb-1"><i class="bi bi-patch-check-fill me-1"></i><span id="abhaCreateStatusText">ABHA Ready</span></span>
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
                                <div class="text-center text-muted"><i class="bi bi-card-image fs-1 d-block mb-2"></i><span>Official ABHA card was not returned by the Bridge.</span></div>
                            </div>
                            <a class="btn btn-outline-primary w-100 mt-2 d-none" id="abhaCreateDownloadCard" download="ABHA-card"><i class="bi bi-download me-1"></i>Download ABHA</a>
                            <button type="button" class="btn btn-success w-100 mt-2" id="abhaCreateRegisterBtn"><i class="bi bi-person-check me-1"></i>Register Patient</button>
                            <button type="button" class="btn btn-primary w-100 mt-2" id="abhaCreateChooseAddressBtn"><i class="bi bi-at me-1"></i>Create New ABHA Address</button>
                            <div class="form-text mt-2"><i class="bi bi-info-circle me-1"></i>Click <strong>Register Patient</strong> to compare this ABHA with existing HMS records.</div>
                        </div>
                    </div>
                </section>

                <!-- Step 5: ABHA address selection -->
                <section id="abhaCreateStep5" class="d-none">
                    <div class="text-center mb-3">
                        <i class="bi bi-briefcase-fill text-warning" style="font-size:2.2rem"></i>
                        <h6 class="mt-2 mb-1">Choose Your ABHA Address</h6>
                        <div class="text-muted small">Select a suggested ABHA address or enter a custom one. This is your digital health identity.</div>
                    </div>
                    <div id="abhaCreateAddressAlert"></div>
                    <div id="abhaCreateAddressList" class="d-grid gap-2"></div>
                    <div class="text-center mt-2">
                        <button type="button" class="btn btn-link" id="abhaCreateCustomToggle">Use a custom address instead &rarr;</button>
                    </div>
                    <div id="abhaCreateCustomWrap" class="d-none">
                        <label class="form-label fw-semibold" for="abhaCreateCustomAddress">Custom ABHA Address</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text"><i class="bi bi-at"></i></span>
                            <input type="text" class="form-control" id="abhaCreateCustomAddress" placeholder="username" autocomplete="off">
                        </div>
                        <div class="form-text">Minimum 4 characters. Letters, numbers, dot and underscore only.</div>
                    </div>
                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-outline-secondary" id="abhaCreateAddressBackBtn"><i class="bi bi-arrow-left me-1"></i>Back</button>
                        <button type="button" class="btn btn-primary" id="abhaCreateConfirmAddressBtn"><i class="bi bi-briefcase me-1"></i>Confirm Address &amp; Create ABHA</button>
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

    function csrf() {
        var input = document.querySelector('input[name="<?= csrf_token() ?>"]');
        return input ? input.value : '<?= csrf_hash() ?>';
    }
    function escapeHtml(value) { return $('<div>').text(value == null ? '' : String(value)).html(); }
    function apiMessage(response, fallback) {
        return response && (response.error_text || response.message) ? escapeHtml(response.error_text || response.message) : fallback;
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
    function startResendTimer(button, seconds, setter, getter) {
        resendRemaining = Math.max(60, Number(seconds) || 60);
        button.prop('disabled', true);
        function tick() {
            if (resendRemaining <= 0) {
                window.clearInterval(resendTimer);
                resendTimer = null;
                button.prop('disabled', false).text('Resend OTP');
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
                button.prop('disabled', false).text('Resend OTP');
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

    function sendAadhaarOtp() {
        var aadhaar = digits($('#abhaCreateAadhaar').val());
        var mobile = digits($('#abhaCreateMobile').val());
        if (aadhaar.length !== 12) { alertBox('warning', 'Enter a valid 12-digit Aadhaar number.'); return; }
        if (mobile.length !== 10) { alertBox('warning', 'Enter a valid 10-digit mobile number for ABHA communication.'); return; }
        if (!$('#abhaCreateConsent1').is(':checked')) { alertBox('warning', 'Aadhaar authentication consent is mandatory before sending the OTP.'); return; }
        if (!$('#abhaCreateConsent4').is(':checked')) { alertBox('warning', 'Confirm that the consent was explained to the patient.'); return; }

        communicationMobile = mobile;
        var button = $('#abhaCreateSendOtpBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Sending');
        $.post('<?= base_url('abha/create/initiate') ?>', { aadhaar: aadhaar, auth_type: 'aadhaar_otp', '<?= csrf_token() ?>': csrf() }, function (response) {
            button.prop('disabled', false).html('<i class="bi bi-send me-1"></i>Send OTP');
            if (!response || response.ok != 1) { alertBox('danger', apiMessage(response, 'Unable to send Aadhaar OTP.')); return; }
            createTxnId = response.txn_id || '';
            $('#abhaCreateOtp').val('');
            var destination = response.masked_mobile || ((response.message || '').match(/\*{2,}\d{4}/) || [''])[0];
            var bridgeMessage = String(response.message || '').trim();
            if (!bridgeMessage) {
                bridgeMessage = 'OTP sent to the Aadhaar registered mobile number' + (destination ? ' ending with ' + destination : '') + '.';
            }
            $('#abhaCreateOtpHint').text(bridgeMessage);
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
        if (otp.length !== 6) { alertBox('warning', 'Enter the 6-digit OTP.'); return; }
        var button = $('#abhaCreateVerifyOtpBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Verifying');
        $.post('<?= base_url('abha/create/verify_otp') ?>', { txn_id: createTxnId, otp: otp, mobile: communicationMobile, '<?= csrf_token() ?>': csrf() }, function (response) {
            button.prop('disabled', false).html('<i class="bi bi-shield-check me-1"></i>Verify &amp; Create ABHA');
            if (!response || response.ok != 1) {
                alertBox('danger', apiMessage(response, 'Invalid OTP - Please enter a valid OTP. Entered OTP is either expired or incorrect.'));
                return;
            }
            stopTimers();
            createdProfile = response;
            createTxnId = response.txn_id || createTxnId;
            if (communicationMobile && digits(response.mobile).slice(-10) !== communicationMobile) {
                requestMobileOtp();
                return;
            }
            renderProfile(response);
        }, 'json').fail(function (xhr) {
            button.prop('disabled', false).html('<i class="bi bi-shield-check me-1"></i>Verify &amp; Create ABHA');
            alertBox('danger', apiMessage(xhr.responseJSON, 'OTP verification failed.'));
        });
    }

    function requestMobileOtp() {
        alertBox('info', 'Aadhaar verified. Requesting OTP for the alternate communication mobile...');
        $.post('<?= base_url('abha/create/communication') ?>', { mobile: communicationMobile, txn_id: createTxnId, '<?= csrf_token() ?>': csrf() }, function (response) {
            if (!response || response.ok != 1) {
                renderProfile(createdProfile || {});
                alertBox('warning', 'Aadhaar verification succeeded, but the alternate mobile OTP could not be sent: ' + apiMessage(response, 'Unable to send mobile OTP.'));
                return;
            }
            mobileTxnId = response.txn_id || createTxnId;
            $('#abhaCreateMobileHint').text(response.message || ('OTP sent to ' + maskMobile(communicationMobile) + '.'));
            $('#abhaCreateMobileOtp').val('');
            showStep(3);
            startMobileResendTimer($('#abhaCreateMobileResendBtn'));
            $('#abhaCreateMobileOtp').trigger('focus');
        }, 'json').fail(function (xhr) {
            renderProfile(createdProfile || {});
            alertBox('warning', 'Aadhaar verification succeeded, but the alternate mobile OTP could not be sent: ' + apiMessage(xhr.responseJSON, 'Unable to send mobile OTP.'));
        });
    }

    function verifyMobileOtp() {
        var otp = digits($('#abhaCreateMobileOtp').val());
        if (otp.length !== 6) { alertBox('warning', 'Enter the 6-digit mobile OTP.'); return; }
        var button = $('#abhaCreateVerifyMobileBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Verifying');
        $.post('<?= base_url('abha/create/verify_comm_otp') ?>', { txn_id: mobileTxnId, otp: otp, mobile: communicationMobile, '<?= csrf_token() ?>': csrf() }, function (response) {
            button.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i>Verify &amp; Update Mobile');
            if (!response || response.ok != 1) { alertBox('danger', apiMessage(response, 'Mobile OTP verification failed.')); return; }
            stopTimers();
            createdProfile = mergeProfile(createdProfile, response);
            renderProfile(createdProfile);
        }, 'json').fail(function (xhr) {
            button.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i>Verify &amp; Update Mobile');
            alertBox('danger', apiMessage(xhr.responseJSON, 'Mobile OTP verification failed.'));
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
        $('#abhaCreateProfileMobile').text(maskMobile(profile.mobile) || '-');
        $('#abhaCreateProfileFullAddress').text([profile.address, profile.district, profile.state, profile.zip].filter(Boolean).join(', ') || '-');
        $('#abhaCreateStatusText').text(profile.abha_number ? 'ABHA Ready' : 'ABHA Verified');

        var photo = photoSrc(profile.photo);
        $('#abhaCreatePhoto').toggleClass('d-none', !photo).attr('src', photo || '');

        var card = profile.card_base64 || '';
        if (card) {
            var cardSrc = String(card).indexOf('data:') === 0 ? card : 'data:' + (profile.card_content_type || 'image/png') + ';base64,' + card;
            var isOfficial = profile.card_source !== 'generated';
            var note = isOfficial
                ? ''
                : '<div class="small text-warning mt-1"><i class="bi bi-exclamation-triangle me-1"></i>Provisional card generated by the Bridge, not the official ABDM card.</div>';
            $('#abhaCreateCardWrap').html('<img src="' + cardSrc + '" alt="ABHA card">' + note);
            $('#abhaCreateDownloadCard').attr('href', cardSrc).removeClass('d-none');
        } else {
            var reason = profile.card_message ? escapeHtml(profile.card_message) : 'ABDM did not issue an official ABHA card for this session.';
            $('#abhaCreateCardWrap').html('<div class="text-center text-muted"><i class="bi bi-card-image fs-1 d-block mb-2"></i><span>' + reason + '</span></div>');
            $('#abhaCreateDownloadCard').addClass('d-none');
        }
        showStep(4);
    }

    function loadAddressSuggestions() {
        showStep(5);
        $('#abhaCreateAddressAlert').empty();
        $('#abhaCreateAddressList').html('<div class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-2"></span>Loading suggestions…</div>');
        $('#abhaCreateCustomWrap').addClass('d-none');
        $('#abhaCreateCustomAddress').val('');

        $.post('<?= base_url('abha/create/address_suggestions') ?>', { txn_id: createTxnId, '<?= csrf_token() ?>': csrf() }, function (response) {
            if (!response || response.ok != 1 || !(response.suggestions || []).length) {
                $('#abhaCreateAddressList').empty();
                $('#abhaCreateCustomWrap').removeClass('d-none');
                $('#abhaCreateAddressAlert').html('<div class="alert alert-warning py-2">' + apiMessage(response, 'No ABHA address suggestions are available.') + ' You can type a custom address below.</div>');
                return;
            }
            var html = '';
            (response.suggestions || []).forEach(function (address, index) {
                html += '<label class="abha-create-address"><input class="form-check-input me-2" type="radio" name="abhaCreateAddressPick" value="' + escapeHtml(address) + '"' + (index === 0 ? ' checked' : '') + '>' + escapeHtml(address) + '</label>';
            });
            $('#abhaCreateAddressList').html(html);
        }, 'json').fail(function (xhr) {
            $('#abhaCreateAddressList').empty();
            $('#abhaCreateCustomWrap').removeClass('d-none');
            $('#abhaCreateAddressAlert').html('<div class="alert alert-warning py-2">' + apiMessage(xhr.responseJSON, 'Unable to load ABHA address suggestions.') + ' You can type a custom address below.</div>');
        });
    }

    function confirmAddress() {
        var custom = $.trim($('#abhaCreateCustomAddress').val());
        var selected = $('input[name="abhaCreateAddressPick"]:checked').val() || '';
        var address = $('#abhaCreateCustomWrap').hasClass('d-none') ? selected : (custom || selected);
        if (!address) { $('#abhaCreateAddressAlert').html('<div class="alert alert-warning py-2">Select a suggested address or enter a custom one.</div>'); return; }

        var button = $('#abhaCreateConfirmAddressBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving');
        $.post('<?= base_url('abha/create/address') ?>', { txn_id: createTxnId, abha_address: address, '<?= csrf_token() ?>': csrf() }, function (response) {
            button.prop('disabled', false).html('<i class="bi bi-briefcase me-1"></i>Confirm Address &amp; Create ABHA');
            if (!response || response.ok != 1) {
                $('#abhaCreateAddressAlert').html('<div class="alert alert-danger py-2">' + apiMessage(response, 'Unable to set the ABHA address.') + '</div>');
                return;
            }
            createdProfile.abha_address = response.abha_address || address;
            renderProfile(createdProfile);
            alertBox('success', 'ABHA address updated to <strong>' + escapeHtml(createdProfile.abha_address) + '</strong>.');
        }, 'json').fail(function (xhr) {
            button.prop('disabled', false).html('<i class="bi bi-briefcase me-1"></i>Confirm Address &amp; Create ABHA');
            $('#abhaCreateAddressAlert').html('<div class="alert alert-danger py-2">' + apiMessage(xhr.responseJSON, 'Unable to set the ABHA address.') + '</div>');
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
        $('#abhaCreateSendOtpBtn').on('click', sendAadhaarOtp);
        $('#abhaCreateResendBtn').on('click', sendAadhaarOtp);
        $('#abhaCreateChangeAadhaarBtn').on('click', function () { stopTimers(); showStep(1); });
        $('#abhaCreateVerifyOtpBtn').on('click', verifyAadhaarOtp);
        $('#abhaCreateOtp').on('keydown', function (event) { if (event.key === 'Enter') { event.preventDefault(); verifyAadhaarOtp(); } });
        $('#abhaCreateVerifyMobileBtn').on('click', verifyMobileOtp);
        $('#abhaCreateMobileResendBtn').on('click', requestMobileOtp);
        $('#abhaCreateSkipMobileBtn').on('click', function () { stopTimers(); renderProfile(createdProfile || {}); });
        $('#abhaCreateChangeMobileBtn').on('click', function () {
            stopTimers();
            showStep(1);
            alertBox('warning', 'Enter the mobile number already linked with this Aadhaar, then request a new Aadhaar OTP.');
            $('#abhaCreateMobile').trigger('focus');
        });
        $('#abhaCreateChooseAddressBtn').on('click', loadAddressSuggestions);
        $('#abhaCreateAddressBackBtn').on('click', function () { showStep(4); });
        $('#abhaCreateCustomToggle').on('click', function () {
            $('#abhaCreateCustomWrap').toggleClass('d-none');
            if (!$('#abhaCreateCustomWrap').hasClass('d-none')) $('#abhaCreateCustomAddress').trigger('focus');
        });
        $('#abhaCreateConfirmAddressBtn').on('click', confirmAddress);

        $('#abhaCreateRegisterBtn').on('click', function () {
            if (!createdProfile) return;
            var profile = createdProfile;
            if (profile.need_confirmation === false && Number(profile.patient_id || 0) > 0) {
                $('#abhaCreateModal').one('hidden.bs.modal', function () {
                    if (typeof onCompleted === 'function') onCompleted(profile);
                });
                modal.hide();
                return;
            }
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
            stopTimers();
            $('#abhaCreateAadhaar,#abhaCreateMobile,#abhaCreateOtp,#abhaCreateMobileOtp,#abhaCreateCustomAddress').val('');
            $('#abhaCreateMobile').val(digits(prefillMobile));
            $('#abhaCreateOtpRequestId').addClass('d-none').text('');
            $('#abhaCreateConsent1,#abhaCreateConsent2,#abhaCreateConsent3,#abhaCreateConsent4').prop('checked', false);
            $('#abhaCreatePhoto,#abhaCreateDownloadCard').addClass('d-none');
            showStep(1);
            modal.show();
        }
    };
})();
</script>
