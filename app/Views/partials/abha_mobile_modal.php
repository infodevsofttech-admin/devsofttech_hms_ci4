<?php
/**
 * Partial: "Verify ABHA by Mobile OTP" modal.
 *
 * Mirrors partials/abha_verify_modal.php so both verification journeys look the
 * same; only the identifier differs (ABHA-linked mobile instead of ABHA number).
 *
 *   1. Mobile number
 *   2. OTP
 *   3. Verified profile + official ABHA card
 *
 * Handing the profile to the patient-match modal stays the caller's job.
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
    .abha-mobile-step { display:flex; align-items:center; color:#8792a3; }
    .abha-mobile-step:not(:last-child)::after { content:""; width:44px; height:2px; background:#dce3ec; margin:0 7px; }
    .abha-mobile-step.done:not(:last-child)::after { background:#356cf4; }
    .abha-mobile-step span { width:30px; height:30px; display:grid; place-items:center; border-radius:50%; background:#eef2f6; font-weight:700; }
    .abha-mobile-step.active span { color:#1748ce; background:#fff; border:2px solid #356cf4; box-shadow:0 0 0 4px #edf2ff; }
    .abha-mobile-step.done span { color:#fff; background:#356cf4; }
    .abha-mobile-modal .abha-profile-list { margin:0; }
    .abha-mobile-modal .abha-profile-list>div { display:grid; grid-template-columns:150px minmax(0,1fr); gap:14px; padding:10px 0; border-bottom:1px solid #edf0f4; }
    .abha-mobile-modal .abha-profile-list dt { color:#6b7688; font-weight:500; }
    .abha-mobile-modal .abha-profile-list dd { margin:0; text-align:right; font-weight:600; overflow-wrap:anywhere; }
    .abha-mobile-modal .abha-card-preview { min-height:230px; display:grid; place-items:center; border:1px solid #dce3ec; background:#f8fafc; }
    .abha-mobile-modal .abha-card-preview img { max-width:100%; max-height:360px; object-fit:contain; }
    @media (max-width:575.98px) { .abha-mobile-modal .modal-body{padding:16px}.abha-mobile-step:not(:last-child)::after{width:22px}.abha-mobile-modal .abha-profile-list>div{grid-template-columns:110px minmax(0,1fr)} }
</style>

<div class="modal fade abha-mobile-modal" id="abhaMobileModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-phone text-primary me-2"></i>Verify ABHA by Mobile OTP</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="abha-mobile-steps" aria-label="Verification progress">
                    <div class="abha-mobile-step active" data-step="1"><span>1</span></div>
                    <div class="abha-mobile-step" data-step="2"><span>2</span></div>
                    <div class="abha-mobile-step" data-step="3"><span>3</span></div>
                </div>
                <div id="abhaMobileAlert"></div>

                <section id="abhaMobileStep1">
                    <label class="form-label fw-semibold" for="abhaMobileNumber">Mobile Number</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text"><i class="bi bi-phone"></i></span>
                        <input type="text" class="form-control" id="abhaMobileNumber" maxlength="10" inputmode="numeric" autocomplete="tel" placeholder="10-digit mobile">
                        <button type="button" class="btn btn-primary" id="abhaMobileSendOtpBtn"><i class="bi bi-send me-1"></i>Send OTP</button>
                    </div>
                    <div class="form-text">Enter the mobile number registered with the patient's ABHA account.</div>
                </section>

                <section id="abhaMobileStep2" class="d-none">
                    <p class="text-muted mb-2" id="abhaMobileOtpHint"></p>
                    <label class="form-label fw-semibold" for="abhaMobileOtp">6-digit OTP</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                        <input type="text" class="form-control" id="abhaMobileOtp" maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="Enter OTP">
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3 gap-2 flex-wrap">
                        <div>
                            <button type="button" class="btn btn-link px-0 me-3" id="abhaMobileResendBtn" disabled>Resend OTP in 60s</button>
                            <button type="button" class="btn btn-link px-0 text-secondary" id="abhaMobileChangeBtn">&larr; Change Mobile</button>
                        </div>
                        <button type="button" class="btn btn-primary" id="abhaMobileVerifyBtn"><i class="bi bi-patch-check me-1"></i>Verify OTP</button>
                    </div>
                </section>

                <section id="abhaMobileStep3" class="d-none">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <img id="abhaMobilePhoto" class="rounded d-none" alt="ABHA profile" style="width:76px;height:76px;object-fit:cover">
                                <div>
                                    <span class="badge bg-success-subtle text-success mb-1"><i class="bi bi-patch-check-fill me-1"></i>ABHA Verified</span>
                                    <h4 class="mb-0" id="abhaMobileProfileName"></h4>
                                    <div class="text-muted" id="abhaMobileProfileAddress"></div>
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
                            <div class="abha-card-preview" id="abhaMobileCardWrap"><div class="text-center text-muted"><i class="bi bi-card-image fs-1 d-block mb-2"></i><span>Official ABHA card was not returned by the Bridge.</span></div></div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                        <button type="button" class="btn btn-success" id="abhaMobileCompareBtn"><i class="bi bi-people me-1"></i>Compare with HMS Patients</button>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<script>
window.AbhaMobileModal = (function () {
    'use strict';

    var modal;
    var txnId = '';
    var verifiedMobile = '';
    var verifiedProfile = null;
    var onVerified = null;
    var resendTimer = null;
    var resendRemaining = 0;

    function csrf() {
        var input = document.querySelector('input[name="<?= csrf_token() ?>"]');
        return input ? input.value : '<?= csrf_hash() ?>';
    }
    function escapeHtml(value) { return $('<div>').text(value == null ? '' : String(value)).html(); }
    function digits(value) { return String(value == null ? '' : value).replace(/\D/g, ''); }
    function apiMessage(response, fallback) {
        return response && (response.error_text || response.message) ? escapeHtml(response.error_text || response.message) : fallback;
    }
    function alertBox(type, message) {
        $('#abhaMobileAlert').html(message ? '<div class="alert alert-' + type + ' py-2">' + message + '</div>' : '');
    }
    function showStep(step) {
        $('#abhaMobileStep1,#abhaMobileStep2,#abhaMobileStep3').addClass('d-none');
        $('#abhaMobileStep' + step).removeClass('d-none');
        $('.abha-mobile-step').each(function () {
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
        var button = $('#abhaMobileResendBtn').prop('disabled', true);
        function tick() {
            if (resendRemaining <= 0) {
                stopTimer();
                button.prop('disabled', false).text('Resend OTP');
                return;
            }
            button.text('Resend OTP in ' + resendRemaining + 's');
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

    function sendOtp() {
        var mobile = digits($('#abhaMobileNumber').val());
        if (mobile.length !== 10) { alertBox('warning', 'Enter a valid 10-digit mobile number.'); return; }

        verifiedMobile = mobile;
        var button = $('#abhaMobileSendOtpBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Sending');
        $.post('<?= base_url('abha/create/communication') ?>', { mobile: mobile, '<?= csrf_token() ?>': csrf() }, function (response) {
            button.prop('disabled', false).html('<i class="bi bi-send me-1"></i>Send OTP');
            if (!response || response.ok != 1) { alertBox('danger', apiMessage(response, 'Unable to send OTP to this mobile number.')); return; }
            txnId = response.txn_id || '';
            $('#abhaMobileOtp').val('');
            $('#abhaMobileOtpHint').html('OTP sent to <strong>' + escapeHtml(maskMobile(mobile)) + '</strong>.');
            showStep(2);
            startResendTimer(60);
            $('#abhaMobileOtp').trigger('focus');
        }, 'json').fail(function (xhr) {
            button.prop('disabled', false).html('<i class="bi bi-send me-1"></i>Send OTP');
            alertBox('danger', apiMessage(xhr.responseJSON, 'Unable to send OTP to this mobile number.'));
        });
    }

    function verifyOtp() {
        var otp = digits($('#abhaMobileOtp').val());
        if (otp.length !== 6) { alertBox('warning', 'Enter the 6-digit OTP.'); return; }

        var button = $('#abhaMobileVerifyBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Verifying');
        $.post('<?= base_url('abha/create/verify_comm_otp') ?>', { otp: otp, txn_id: txnId, mobile: verifiedMobile, '<?= csrf_token() ?>': csrf() }, function (response) {
            button.prop('disabled', false).html('<i class="bi bi-patch-check me-1"></i>Verify OTP');
            if (!response || response.ok != 1) { alertBox('danger', apiMessage(response, 'OTP verification failed.')); return; }
            stopTimer();
            renderProfile(response);
        }, 'json').fail(function (xhr) {
            button.prop('disabled', false).html('<i class="bi bi-patch-check me-1"></i>Verify OTP');
            alertBox('danger', apiMessage(xhr.responseJSON, 'OTP verification failed.'));
        });
    }

    function renderProfile(profile) {
        verifiedProfile = profile;
        $('#abhaMobileProfileName').text(profile.name || '-');
        $('#abhaMobileProfileAddress').text(profile.abha_address || '-');
        $('#abhaMobileProfileNumber').text(formatAbha(profile.abha_number) || '-');
        $('#abhaMobileProfileId').text(profile.abha_address || '-');
        $('#abhaMobileProfileDob').text(profile.dob || '-');
        $('#abhaMobileProfileGender').text(genderText(profile.gender));
        $('#abhaMobileProfileMobile').text(maskMobile(profile.mobile || verifiedMobile));
        $('#abhaMobileProfileFullAddress').text([profile.address, profile.district, profile.state, profile.zip].filter(Boolean).join(', ') || '-');

        var photo = profile.photo ? (String(profile.photo).indexOf('data:') === 0 ? profile.photo : 'data:image/jpeg;base64,' + profile.photo) : '';
        $('#abhaMobilePhoto').toggleClass('d-none', !photo).attr('src', photo || '');

        var card = profile.card_base64 || '';
        if (card) {
            var cardSrc = String(card).indexOf('data:') === 0 ? card : 'data:' + (profile.card_content_type || 'image/png') + ';base64,' + card;
            var note = profile.card_source !== 'generated'
                ? ''
                : '<div class="small text-warning mt-1"><i class="bi bi-exclamation-triangle me-1"></i>Provisional card generated by the Bridge, not the official ABDM card.</div>';
            $('#abhaMobileCardWrap').html('<img src="' + cardSrc + '" alt="ABHA card">' + note);
        } else {
            var reason = profile.card_message ? escapeHtml(profile.card_message) : 'ABDM did not issue an official ABHA card for this session.';
            $('#abhaMobileCardWrap').html('<div class="text-center text-muted"><i class="bi bi-card-image fs-1 d-block mb-2"></i>' + reason + '</div>');
        }
        showStep(3);
    }

    $(function () {
        modal = new bootstrap.Modal(document.getElementById('abhaMobileModal'));

        $('#abhaMobileSendOtpBtn').on('click', sendOtp);
        $('#abhaMobileResendBtn').on('click', sendOtp);
        $('#abhaMobileVerifyBtn').on('click', verifyOtp);
        $('#abhaMobileChangeBtn').on('click', function () { stopTimer(); showStep(1); $('#abhaMobileNumber').trigger('focus'); });
        $('#abhaMobileNumber').on('keydown', function (event) { if (event.key === 'Enter') { event.preventDefault(); sendOtp(); } });
        $('#abhaMobileOtp').on('keydown', function (event) { if (event.key === 'Enter') { event.preventDefault(); verifyOtp(); } });

        $('#abhaMobileCompareBtn').on('click', function () {
            if (!verifiedProfile) return;
            var profile = verifiedProfile;
            // Already linked: nothing else opens, so keep this window up for the operator to close.
            if (profile.need_confirmation === false && Number(profile.patient_id || 0) > 0) {
                alertBox('success', 'This ABHA is already linked to HMS patient <strong>' + escapeHtml(profile.p_code || '') + '</strong>. The page behind has been updated. Close this window when you are done.');
                if (typeof onVerified === 'function') onVerified(profile);
                return;
            }
            $('#abhaMobileModal').one('hidden.bs.modal', function () {
                if (typeof onVerified === 'function') onVerified(profile);
            });
            modal.hide();
        });

        $('#abhaMobileModal').on('hidden.bs.modal', stopTimer);
    });

    return {
        open: function (prefillMobile, callback) {
            onVerified = typeof prefillMobile === 'function' ? prefillMobile : callback;
            txnId = '';
            verifiedMobile = '';
            verifiedProfile = null;
            stopTimer();
            $('#abhaMobileNumber').val(typeof prefillMobile === 'string' ? prefillMobile : '');
            $('#abhaMobileOtp').val('');
            $('#abhaMobilePhoto').addClass('d-none');
            showStep(1);
            modal.show();
            window.setTimeout(function () { $('#abhaMobileNumber').trigger('focus'); }, 300);
        }
    };
})();
</script>
