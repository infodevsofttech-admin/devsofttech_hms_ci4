<?php
/**
 * Partial: "Find ABHA by Aadhaar Number" modal.
 *
 * Compliant with ABDM M1 Guidelines (12.1 VRFY_ABHA_401 to VRFY_ABHA_405):
 *   VRFY_ABHA_401: Fetch ABHA details using Aadhaar Number
 *   VRFY_ABHA_402: Incorrect OTP handling
 *   VRFY_ABHA_403: ABHA Details not exists to Aadhaar Number -> Provision to create ABHA
 *   VRFY_ABHA_404: ABHA Details exists to Aadhaar Number -> Display Profile & Card
 *   VRFY_ABHA_405: Resend OTP Functionality (60s timer, max 2 times)
 *
 * Include once per page: <?= view('partials/abha_aadhaar_modal') ?>
 * Then call: window.AbhaAadhaarModal.open(prefillAadhaar, function (profile) { ... });
 */
?>
<style>
    .abha-aadhaar-modal .modal-content { border: 0; border-radius: 12px; overflow: hidden; }
    .abha-aadhaar-modal .modal-header { border-bottom: 1px solid #e5eaf1; padding: 18px 22px; }
    .abha-aadhaar-modal .modal-body { padding: 22px; }
    .abha-aadhaar-steps { display: flex; justify-content: center; align-items: center; margin-bottom: 24px; }
    .abha-aadhaar-step { display: flex; align-items: center; color: #8792a3; font-weight: 600; font-size: 0.9rem; }
    .abha-aadhaar-step:not(:last-child)::after { content: ""; width: 44px; height: 2px; background: #dce3ec; margin: 0 8px; }
    .abha-aadhaar-step.done:not(:last-child)::after { background: #0d6efd; }
    .abha-aadhaar-step .step-num { width: 32px; height: 32px; display: grid; place-items: center; border-radius: 50%; background: #eef2f6; font-weight: 700; margin-right: 8px; flex-shrink: 0; }
    .abha-aadhaar-step.active .step-num { color: #0d6efd; background: #fff; border: 2px solid #0d6efd; box-shadow: 0 0 0 4px #edf2ff; }
    .abha-aadhaar-step.active { color: #0d6efd; }
    .abha-aadhaar-step.done .step-num { color: #fff; background: #0d6efd; }
    .abha-aadhaar-step.done { color: #0d6efd; }
    .abha-aadhaar-step .step-title { font-weight: 600; white-space: nowrap; }

    .abha-aadhaar-modal .abha-profile-list { margin: 0; }
    .abha-aadhaar-modal .abha-profile-list > div { display: grid; grid-template-columns: 150px minmax(0, 1fr); gap: 14px; padding: 10px 0; border-bottom: 1px solid #edf0f4; }
    .abha-aadhaar-modal .abha-profile-list dt { color: #6b7688; font-weight: 500; }
    .abha-aadhaar-modal .abha-profile-list dd { margin: 0; text-align: right; font-weight: 600; overflow-wrap: anywhere; }
    .abha-aadhaar-modal .abha-card-preview { min-height: 230px; display: grid; place-items: center; border: 1px solid #dce3ec; background: #f8fafc; border-radius: 8px; padding: 12px; }
    .abha-aadhaar-modal .abha-card-preview img { max-width: 100%; max-height: 360px; object-fit: contain; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }

    @media (max-width: 575.98px) {
        .abha-aadhaar-modal .modal-body { padding: 16px; }
        .abha-aadhaar-step:not(:last-child)::after { width: 20px; }
        .abha-aadhaar-step .step-num { width: 26px; height: 26px; font-size: 0.8rem; margin-right: 4px; }
        .abha-aadhaar-modal .abha-profile-list > div { grid-template-columns: 110px minmax(0, 1fr); }
    }
</style>

<div class="modal fade abha-aadhaar-modal" id="abhaAadhaarModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-dark">
                    <i class="bi bi-fingerprint text-primary me-2"></i>Find ABHA by Aadhaar Number
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- 3-Step Stepper -->
                <div class="abha-aadhaar-steps" aria-label="Verification progress">
                    <div class="abha-aadhaar-step active" data-step="1">
                        <span class="step-num">1</span>
                        <span class="step-title">Aadhaar Number</span>
                    </div>
                    <div class="abha-aadhaar-step" data-step="2">
                        <span class="step-num">2</span>
                        <span class="step-title">Verify OTP</span>
                    </div>
                    <div class="abha-aadhaar-step" data-step="3">
                        <span class="step-num">3</span>
                        <span class="step-title">ABHA Profile</span>
                    </div>
                </div>

                <div id="abhaAadhaarAlert"></div>

                <!-- Step 1: Aadhaar Number Input -->
                <section id="abhaAadhaarStep1">
                    <div class="card border-0 bg-light p-4 rounded-3 mb-3">
                        <label class="form-label fw-semibold text-dark fs-6" for="abhaAadhaarInput">
                            Enter 12-Digit Aadhaar Number <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-lg mb-2">
                            <span class="input-group-text bg-white"><i class="bi bi-person-badge"></i></span>
                            <input type="text" class="form-control font-monospace" id="abhaAadhaarInput"
                                   maxlength="14" inputmode="numeric" autocomplete="off"
                                   placeholder="0000 0000 0000">
                            <button type="button" class="btn btn-primary px-4 fw-semibold" id="abhaAadhaarSendOtpBtn">
                                Send OTP <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                        <div class="form-text text-muted mb-3">
                            <i class="bi bi-info-circle me-1"></i>System will retrieve the ABHA profile linked to this Aadhaar number via UIDAI OTP authentication.
                        </div>

                        <!-- Beneficiary Consent -->
                        <div class="border rounded p-3 bg-white mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="abhaAadhaarConsent" checked>
                                <label class="form-check-label small text-muted" for="abhaAadhaarConsent">
                                    I confirm that the patient/beneficiary has consented to authenticate using their Aadhaar Number to retrieve their ABHA profile as per ABDM guidelines.
                                </label>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Step 2: OTP Verification -->
                <section id="abhaAadhaarStep2" class="d-none">
                    <div class="card border-0 bg-light p-4 rounded-3 mb-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-shield-lock fs-4 text-primary"></i>
                            <h6 class="mb-0 fw-bold">Enter Aadhaar OTP</h6>
                        </div>
                        <p class="text-muted small mb-3" id="abhaAadhaarOtpHint">
                            An OTP has been sent to the mobile number registered with your Aadhaar.
                        </p>

                        <div class="col-md-6 col-sm-8 mb-3">
                            <label class="form-label fw-semibold" for="abhaAadhaarOtpInput">6-digit OTP <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white"><i class="bi bi-key"></i></span>
                                <input type="text" class="form-control text-center fw-bold fs-4" id="abhaAadhaarOtpInput"
                                       maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="------">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2">
                            <div>
                                <button type="button" class="btn btn-outline-secondary" id="abhaAadhaarBackBtn">
                                    <i class="bi bi-arrow-left me-1"></i>Change Aadhaar
                                </button>
                                <button type="button" class="btn btn-link text-decoration-none px-2" id="abhaAadhaarResendBtn" disabled>
                                    Resend OTP in 60s
                                </button>
                            </div>
                            <button type="button" class="btn btn-success btn-lg px-4" id="abhaAadhaarVerifyOtpBtn">
                                <i class="bi bi-patch-check me-1"></i>Verify OTP &amp; Fetch Profile
                            </button>
                        </div>
                    </div>

                    <!-- VRFY_ABHA_403: No ABHA user registered with this Aadhaar container -->
                    <div id="abhaAadhaarNotFoundCard" class="card border-warning bg-warning-subtle p-3 rounded-3 mt-3 d-none">
                        <div class="d-flex align-items-start gap-3">
                            <i class="bi bi-exclamation-triangle-fill text-warning fs-3 mt-1"></i>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold text-dark mb-1">NO ABHA user registered with this Aadhaar Number</h6>
                                <p class="text-muted small mb-2">
                                    Aadhaar verification was successful, but no ABHA account exists for this Aadhaar number in the ABDM registry.
                                </p>
                                <button type="button" class="btn btn-primary btn-sm" id="abhaAadhaarCreateNowBtn">
                                    <i class="bi bi-plus-circle me-1"></i>Create ABHA with this Aadhaar
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Step 3: Verified ABHA Profile Details -->
                <section id="abhaAadhaarStep3" class="d-none">
                    <div class="alert alert-success py-2 mb-3">
                        <i class="bi bi-check-circle-fill me-2"></i>ABHA profile successfully retrieved and verified via Aadhaar!
                    </div>

                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <img id="abhaAadhaarPhoto" class="rounded border shadow-sm d-none" alt="ABHA photo" style="width: 80px; height: 80px; object-fit: cover;">
                                <div>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle mb-1">
                                        <i class="bi bi-patch-check-fill me-1"></i>ABHA Verified
                                    </span>
                                    <h4 class="mb-0 fw-bold" id="abhaAadhaarProfileName">-</h4>
                                    <div class="text-muted small font-monospace" id="abhaAadhaarProfileAddress">-</div>
                                </div>
                            </div>
                            <dl class="abha-profile-list">
                                <div><dt>ABHA Number</dt><dd id="abhaAadhaarProfileNumber">-</dd></div>
                                <div><dt>ABHA Address</dt><dd id="abhaAadhaarProfileId">-</dd></div>
                                <div><dt>Date of Birth</dt><dd id="abhaAadhaarProfileDob">-</dd></div>
                                <div><dt>Gender</dt><dd id="abhaAadhaarProfileGender">-</dd></div>
                                <div><dt>Mobile</dt><dd id="abhaAadhaarProfileMobile">-</dd></div>
                                <div><dt>Address</dt><dd id="abhaAadhaarProfileFullAddress">-</dd></div>
                            </dl>
                        </div>
                        <div class="col-lg-6">
                            <div class="abha-card-preview" id="abhaAadhaarCardWrap">
                                <div class="text-center text-muted">
                                    <i class="bi bi-card-image fs-1 d-block mb-2 text-primary"></i>
                                    <span id="abhaAadhaarCardMessage">Loading ABHA card...</span>
                                </div>
                            </div>
                            <a class="btn btn-outline-primary w-100 mt-2 d-none" id="abhaAadhaarDownloadCard" download="ABHA-Card.png">
                                <i class="bi bi-download me-1"></i>Download Official ABHA Card
                            </a>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-success" id="abhaAadhaarCompareBtn">
                            <i class="bi bi-people me-1"></i>Compare with HMS Patients
                        </button>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<script>
window.AbhaAadhaarModal = (function () {
    'use strict';

    var modal;
    var onCompletedCallback = null;
    var currentTxnId = '';
    var currentAadhaar = '';
    var verifiedProfile = null;
    var resendTimer = null;
    var resendRemaining = 0;
    var resendAttempts = 0; // VRFY_ABHA_405: max 2 times

    function csrf() {
        var input = document.querySelector('input[name="<?= csrf_token() ?>"]');
        return input ? input.value : '<?= csrf_hash() ?>';
    }

    function escapeHtml(val) {
        return $('<div>').text(val == null ? '' : String(val)).html();
    }

    function digits(val) {
        return String(val || '').replace(/\D/g, '');
    }

    function formatAadhaar(val) {
        var d = digits(val).slice(0, 12);
        var parts = [];
        for (var i = 0; i < d.length; i += 4) {
            parts.push(d.slice(i, i + 4));
        }
        return parts.join(' ');
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

    function alertBox(type, msg) {
        if (!msg) {
            $('#abhaAadhaarAlert').empty();
            return;
        }
        $('#abhaAadhaarAlert').html('<div class="alert alert-' + type + ' alert-dismissible fade show py-2 mb-3" role="alert">' +
            msg + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>');
    }

    function showStep(step) {
        $('#abhaAadhaarStep1, #abhaAadhaarStep2, #abhaAadhaarStep3').addClass('d-none');
        $('#abhaAadhaarStep' + step).removeClass('d-none');
        $('.abha-aadhaar-step').each(function () {
            var itemStep = Number($(this).data('step'));
            $(this).toggleClass('active', itemStep === step).toggleClass('done', itemStep < step);
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
        var button = $('#abhaAadhaarResendBtn').prop('disabled', true);
        function tick() {
            if (resendRemaining <= 0) {
                stopTimer();
                if (resendAttempts < 2) {
                    var left = 2 - resendAttempts;
                    button.prop('disabled', false).text('Resend OTP (' + left + ' left)');
                } else {
                    button.prop('disabled', true).text('Resend limit reached (max 2)');
                }
                return;
            }
            button.text('Resend OTP in ' + resendRemaining + 's');
            resendRemaining--;
        }
        tick();
        resendTimer = window.setInterval(tick, 1000);
    }

    function requestOtp(isResend) {
        var rawAadhaar = digits($('#abhaAadhaarInput').val());
        if (rawAadhaar.length !== 12 || !validateVerhoeff(rawAadhaar)) {
            $('#abhaAadhaarInput').addClass('is-invalid').trigger('focus');
            alertBox('danger', 'Aadhaar Number is not valid. Please enter a valid 12-digit Aadhaar Number.');
            return;
        }
        $('#abhaAadhaarInput').removeClass('is-invalid');

        if (!isResend && !$('#abhaAadhaarConsent').is(':checked')) {
            alertBox('warning', 'Please provide beneficiary consent to proceed.');
            return;
        }

        if (isResend) {
            if (resendAttempts >= 2) {
                alertBox('warning', 'Maximum 2 resend OTP attempts allowed. Please enter the OTP or try again later.');
                return;
            }
            resendAttempts++;
        } else {
            resendAttempts = 0;
        }

        currentAadhaar = rawAadhaar;
        var sendBtn = isResend ? $('#abhaAadhaarResendBtn') : $('#abhaAadhaarSendOtpBtn');
        var originalBtnHtml = sendBtn.html();
        sendBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Sending OTP...');

        $.post('<?= base_url('abha/find/aadhaar/request-otp') ?>', {
            aadhaar: rawAadhaar,
            '<?= csrf_token() ?>': csrf()
        }, function (res) {
            sendBtn.prop('disabled', false).html(originalBtnHtml);
            if (!res || res.ok != 1) {
                var err = (res && res.error_text) ? res.error_text : 'Aadhaar Number is not valid.';
                alertBox('danger', escapeHtml(err));
                return;
            }

            currentTxnId = res.txn_id || '';
            var hintMsg = res.message || 'OTP sent to Aadhaar linked mobile number.';
            $('#abhaAadhaarOtpHint').html('<i class="bi bi-info-circle me-1"></i>' + escapeHtml(hintMsg));
            $('#abhaAadhaarOtpInput').val('');
            $('#abhaAadhaarNotFoundCard').addClass('d-none');
            showStep(2);
            startResendTimer(res.resend_after || 60);
            $('#abhaAadhaarOtpInput').trigger('focus');
        }, 'json').fail(function (xhr) {
            sendBtn.prop('disabled', false).html(originalBtnHtml);
            var err = (xhr.responseJSON && xhr.responseJSON.error_text) ? xhr.responseJSON.error_text : 'Failed to request OTP from ABDM.';
            alertBox('danger', escapeHtml(err));
        });
    }

    function verifyOtp() {
        var otp = digits($('#abhaAadhaarOtpInput').val());
        if (otp.length !== 6) {
            $('#abhaAadhaarOtpInput').addClass('is-invalid').trigger('focus');
            alertBox('warning', 'Please enter a valid 6-digit OTP.');
            return;
        }
        $('#abhaAadhaarOtpInput').removeClass('is-invalid');

        var btn = $('#abhaAadhaarVerifyOtpBtn').prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm me-1"></span>Verifying...');

        $.post('<?= base_url('abha/find/aadhaar/verify-otp') ?>', {
            txn_id: currentTxnId,
            otp: otp,
            aadhaar: currentAadhaar,
            '<?= csrf_token() ?>': csrf()
        }, function (res) {
            btn.prop('disabled', false).html('<i class="bi bi-patch-check me-1"></i>Verify OTP &amp; Fetch Profile');

            // VRFY_ABHA_403: ABHA details do not exist
            if (res && res.no_abha_found) {
                alertBox('warning', '<strong>NO ABHA user registered with this Aadhaar Number</strong>');
                $('#abhaAadhaarNotFoundCard').removeClass('d-none');
                return;
            }

            if (!res || res.ok != 1) {
                var err = (res && res.error_text) ? res.error_text : 'Verification failed. Please try again.';
                if (/incorrect\s*otp|otp\s*is\s*(either\s*)?incorrect|invalid\s*otp/i.test(err)) {
                    err = 'Incorrect OTP. Please enter the valid OTP received on your Aadhaar-linked mobile.';
                }
                alertBox('danger', escapeHtml(err));
                return;
            }

            // VRFY_ABHA_404: ABHA exists -> render profile
            renderProfile(res);
        }, 'json').fail(function (xhr) {
            btn.prop('disabled', false).html('<i class="bi bi-patch-check me-1"></i>Verify OTP &amp; Fetch Profile');
            if (xhr.responseJSON && xhr.responseJSON.no_abha_found) {
                alertBox('warning', '<strong>NO ABHA user registered with this Aadhaar Number</strong>');
                $('#abhaAadhaarNotFoundCard').removeClass('d-none');
                return;
            }
            var err = (xhr.responseJSON && xhr.responseJSON.error_text) ? xhr.responseJSON.error_text : 'Verification failed. Please try again.';
            if (/incorrect\s*otp|otp\s*is\s*(either\s*)?incorrect|invalid\s*otp/i.test(err)) {
                err = 'Incorrect OTP. Please enter the valid OTP received on your Aadhaar-linked mobile.';
            }
            alertBox('danger', escapeHtml(err));
        });
    }

    function renderProfile(profile) {
        verifiedProfile = profile;
        stopTimer();

        var photo = profile.photo || profile.profile_photo || '';
        if (photo) {
            var photoSrc = String(photo).indexOf('data:') === 0 ? photo : 'data:image/jpeg;base64,' + photo;
            $('#abhaAadhaarPhoto').attr('src', photoSrc).removeClass('d-none');
        } else {
            $('#abhaAadhaarPhoto').addClass('d-none');
        }

        $('#abhaAadhaarProfileName').text(profile.name || '-');
        $('#abhaAadhaarProfileAddress').text(profile.abha_address || '-');
        $('#abhaAadhaarProfileNumber').text(profile.abha_number || '-');
        $('#abhaAadhaarProfileId').text(profile.abha_address || '-');
        $('#abhaAadhaarProfileDob').text(profile.dob || '-');
        $('#abhaAadhaarProfileGender').text(profile.gender || '-');
        $('#abhaAadhaarProfileMobile').text(profile.mobile || '-');

        var addrParts = [];
        [profile.address, profile.district, profile.state, profile.zip].forEach(function (part) {
            if (part && typeof part === 'string' && part.trim()) {
                var clean = part.trim();
                if (addrParts.indexOf(clean) === -1) {
                    addrParts.push(clean);
                }
            }
        });
        $('#abhaAadhaarProfileFullAddress').text(addrParts.join(', ') || '-');

        // ABHA Card render
        if (profile.card_base64) {
            var cardSrc = String(profile.card_base64).indexOf('data:') === 0
                ? profile.card_base64
                : 'data:image/png;base64,' + profile.card_base64;
            $('#abhaAadhaarCardWrap').html('<img src="' + cardSrc + '" alt="ABHA Card">');
            $('#abhaAadhaarDownloadCard').attr('href', cardSrc).removeClass('d-none');
        } else {
            $('#abhaAadhaarCardWrap').html('<div class="text-center text-muted p-4"><i class="bi bi-card-image fs-1 d-block mb-2"></i>Official ABHA card preview not available</div>');
            $('#abhaAadhaarDownloadCard').addClass('d-none');
        }

        showStep(3);
    }

    function bindEvents() {
        $('#abhaAadhaarInput').on('input', function () {
            var formatted = formatAadhaar($(this).val());
            $(this).val(formatted);
            $(this).removeClass('is-invalid');
        });

        $('#abhaAadhaarOtpInput').on('input', function () {
            $(this).val(digits($(this).val()).slice(0, 6));
            $(this).removeClass('is-invalid');
        });

        $('#abhaAadhaarSendOtpBtn').on('click', function () {
            requestOtp(false);
        });

        $('#abhaAadhaarResendBtn').on('click', function () {
            requestOtp(true);
        });

        $('#abhaAadhaarVerifyOtpBtn').on('click', function () {
            verifyOtp();
        });

        $('#abhaAadhaarBackBtn').on('click', function () {
            stopTimer();
            showStep(1);
        });

        // Switch to Create ABHA when not found (VRFY_ABHA_403)
        $('#abhaAadhaarCreateNowBtn').on('click', function () {
            modal.hide();
            if (window.AbhaCreateModal) {
                window.AbhaCreateModal.open(function (created) {
                    if (typeof onCompletedCallback === 'function') {
                        onCompletedCallback(created);
                    }
                });
                setTimeout(function () {
                    $('#abhaCreateAadhaar').val(formatAadhaar(currentAadhaar)).trigger('input');
                }, 300);
            } else {
                alert('ABHA Create Modal is unavailable.');
            }
        });

        $('#abhaAadhaarCompareBtn').on('click', function () {
            if (!verifiedProfile) return;
            modal.hide();
            if (typeof onCompletedCallback === 'function') {
                onCompletedCallback(verifiedProfile);
            }
        });
    }

    return {
        open: function (prefillAadhaar, callback) {
            onCompletedCallback = callback;
            currentTxnId = '';
            currentAadhaar = '';
            verifiedProfile = null;
            resendAttempts = 0;
            stopTimer();

            if (!modal) {
                var el = document.getElementById('abhaAadhaarModal');
                if (el && window.bootstrap && window.bootstrap.Modal) {
                    modal = new bootstrap.Modal(el);
                }
                bindEvents();
            }

            $('#abhaAadhaarInput').val(prefillAadhaar ? formatAadhaar(prefillAadhaar) : '').removeClass('is-invalid');
            $('#abhaAadhaarOtpInput').val('').removeClass('is-invalid');
            $('#abhaAadhaarConsent').prop('checked', true);
            $('#abhaAadhaarNotFoundCard').addClass('d-none');
            $('#abhaAadhaarPhoto, #abhaAadhaarDownloadCard').addClass('d-none');
            showStep(1);

            if (modal) {
                modal.show();
            }
        }
    };
})();
</script>
