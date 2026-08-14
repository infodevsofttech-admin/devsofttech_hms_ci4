<style>
    .abha-verify-modal .modal-content { border:0; border-radius:12px; overflow:hidden; }
    .abha-verify-modal .modal-header { border-bottom:1px solid #e5eaf1; padding:18px 22px; }
    .abha-verify-modal .modal-body { padding:22px; }
    .abha-verify-steps { display:flex; justify-content:center; align-items:center; margin-bottom:24px; }
    .abha-verify-step { display:flex; align-items:center; color:#8792a3; }
    .abha-verify-step:not(:last-child)::after { content:""; width:44px; height:2px; background:#dce3ec; margin:0 7px; }
    .abha-verify-step.done:not(:last-child)::after { background:#356cf4; }
    .abha-verify-step span { width:30px; height:30px; display:grid; place-items:center; border-radius:50%; background:#eef2f6; font-weight:700; }
    .abha-verify-step.active span { color:#1748ce; background:#fff; border:2px solid #356cf4; box-shadow:0 0 0 4px #edf2ff; }
    .abha-verify-step.done span { color:#fff; background:#356cf4; }
    .abha-auth-option { display:block; border:1px solid #dce3ec; border-radius:8px; padding:14px; cursor:pointer; }
    .abha-auth-option:has(input:checked) { border-color:#356cf4; background:#edf2ff; box-shadow:0 0 0 1px #356cf4; }
    .abha-profile-list { margin:0; }
    .abha-profile-list>div { display:grid; grid-template-columns:150px minmax(0,1fr); gap:14px; padding:10px 0; border-bottom:1px solid #edf0f4; }
    .abha-profile-list dt { color:#6b7688; font-weight:500; }
    .abha-profile-list dd { margin:0; text-align:right; font-weight:600; overflow-wrap:anywhere; }
    .abha-card-preview { min-height:230px; display:grid; place-items:center; border:1px solid #dce3ec; background:#f8fafc; }
    .abha-card-preview img { max-width:100%; max-height:360px; object-fit:contain; }
    @media (max-width:575.98px) { .abha-verify-modal .modal-body{padding:16px}.abha-verify-step:not(:last-child)::after{width:22px}.abha-profile-list>div{grid-template-columns:110px minmax(0,1fr)} }
</style>

<div class="modal fade abha-verify-modal" id="abhaVerifyModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-shield-check text-primary me-2"></i>Verify ABHA Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="abha-verify-steps" aria-label="Verification progress">
                    <div class="abha-verify-step active" data-step="1"><span>1</span></div>
                    <div class="abha-verify-step" data-step="2"><span>2</span></div>
                    <div class="abha-verify-step" data-step="3"><span>3</span></div>
                    <div class="abha-verify-step" data-step="4"><span>4</span></div>
                </div>
                <div id="abhaVerifyAlert"></div>

                <section id="abhaVerifyStep1">
                    <label class="form-label fw-semibold" for="abhaVerifyIdentifier" id="abhaVerifyIdentifierLabel">ABHA Number or ID</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text"><i class="bi bi-person-vcard"></i></span>
                        <input type="text" class="form-control" id="abhaVerifyIdentifier" placeholder="91-0000-0000-0000" autocomplete="off">
                        <button type="button" class="btn btn-primary" id="abhaVerifyLookupBtn"><i class="bi bi-search me-1"></i>Find Account</button>
                    </div>
                    <div class="form-text" id="abhaVerifyIdentifierHelp">Enter the patient's 14-digit ABHA Number or ID. A mobile number is not required for account lookup.</div>
                </section>

                <section id="abhaVerifyStep2" class="d-none">
                    <div class="mb-3"><strong id="abhaVerifyFoundName"></strong><div class="text-muted small" id="abhaVerifyFoundIdentity"></div></div>
                    <p class="mb-2">Choose where ABDM should send the OTP.</p>
                    <div id="abhaVerifyAuthOptions" class="d-grid gap-2"></div>
                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-outline-secondary" id="abhaVerifyBackLookupBtn"><i class="bi bi-arrow-left me-1"></i>Back</button>
                        <button type="button" class="btn btn-primary" id="abhaVerifySendOtpBtn"><i class="bi bi-send me-1"></i>Send OTP</button>
                    </div>
                </section>

                <section id="abhaVerifyStep3" class="d-none">
                    <p class="text-muted mb-2" id="abhaVerifyOtpHint"></p>
                    <label class="form-label fw-semibold" for="abhaVerifyOtp">6-digit OTP</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                        <input type="text" class="form-control" id="abhaVerifyOtp" maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="Enter OTP">
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3 gap-2">
                        <button type="button" class="btn btn-link px-0" id="abhaVerifyResendBtn" disabled>Resend OTP in 60s</button>
                        <button type="button" class="btn btn-primary" id="abhaVerifyOtpBtn"><i class="bi bi-patch-check me-1"></i>Verify OTP</button>
                    </div>
                </section>

                <section id="abhaVerifyStep4" class="d-none">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <img id="abhaVerifyPhoto" class="rounded d-none" alt="ABHA profile" style="width:76px;height:76px;object-fit:cover">
                                <div><span class="badge bg-success-subtle text-success mb-1"><i class="bi bi-patch-check-fill me-1"></i>ABHA Verified</span><h4 class="mb-0" id="abhaVerifyProfileName"></h4><div class="text-muted" id="abhaVerifyProfileAddress"></div></div>
                            </div>
                            <dl class="abha-profile-list">
                                <div><dt>ABHA Number</dt><dd id="abhaVerifyProfileNumber">-</dd></div>
                                <div><dt>ABHA Address</dt><dd id="abhaVerifyProfileId">-</dd></div>
                                <div><dt>Date of Birth</dt><dd id="abhaVerifyProfileDob">-</dd></div>
                                <div><dt>Gender</dt><dd id="abhaVerifyProfileGender">-</dd></div>
                                <div><dt>Mobile</dt><dd id="abhaVerifyProfileMobile">-</dd></div>
                                <div><dt>Address</dt><dd id="abhaVerifyProfileFullAddress">-</dd></div>
                            </dl>
                        </div>
                        <div class="col-lg-6">
                            <div class="abha-card-preview" id="abhaVerifyCardWrap"><div class="text-center text-muted"><i class="bi bi-card-image fs-1 d-block mb-2"></i><span id="abhaVerifyCardMessage">Official ABHA card was not returned by the Bridge.</span></div></div>
                            <a class="btn btn-outline-primary w-100 mt-2 d-none" id="abhaVerifyDownloadCard" download="ABHA-card"><i class="bi bi-download me-1"></i>Download ABHA Card</a>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                        <button type="button" class="btn btn-success" id="abhaVerifyCompareBtn"><i class="bi bi-people me-1"></i>Compare with HMS Patients</button>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<script>
window.AbhaVerifyModal = (function () {
    'use strict';
    var modal;
    var lookupResponse = null;
    var verifiedProfile = null;
    var onVerified = null;
    var lookupType = 'number';
    var resendTimer = null;
    var resendRemaining = 0;

    function csrf() {
        var input = document.querySelector('input[name="<?= csrf_token() ?>"]');
        return input ? input.value : '<?= csrf_hash() ?>';
    }
    function escapeHtml(value) { return $('<div>').text(value == null ? '' : String(value)).html(); }
    function apiMessage(response, fallback) {
        return response && (response.error_text || response.message) ? escapeHtml(response.error_text || response.message) : fallback;
    }
    function alertBox(type, message) { $('#abhaVerifyAlert').html(message ? '<div class="alert alert-' + type + ' py-2">' + message + '</div>' : ''); }
    function showStep(step) {
        $('#abhaVerifyStep1,#abhaVerifyStep2,#abhaVerifyStep3,#abhaVerifyStep4').addClass('d-none');
        $('#abhaVerifyStep' + step).removeClass('d-none');
        $('.abha-verify-step').each(function () {
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
        var button = $('#abhaVerifyResendBtn').prop('disabled', true);
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
    function authLabel(method) {
        if (method === 'AADHAAR_OTP') return { title:'Aadhaar OTP', detail:'OTP to the mobile linked with Aadhaar', icon:'bi-fingerprint' };
        return { title:'ABHA Registered Mobile OTP', detail:'OTP to the mobile registered with this ABHA account', icon:'bi-phone' };
    }
    function renderAuthMethods(response) {
        var methods = (response.auth_methods || []).filter(function (method) { return method === 'MOBILE_OTP' || method === 'AADHAAR_OTP'; });
        var blocked = response.blocked_auth_methods || [];
        var availableMethods = methods.filter(function (method) { return blocked.indexOf(method) === -1; });
        var html = '';
        methods.forEach(function (method) {
            var label = authLabel(method);
            var disabled = blocked.indexOf(method) !== -1;
            var checked = availableMethods[0] === method;
            var mobile = response.masked_mobile || (response.account && response.account.masked_mobile) || '';
            var destination = mobile ? ' (' + escapeHtml(mobile) + ')' : '';
            html += '<label class="abha-auth-option' + (disabled ? ' opacity-50' : '') + '"><span class="d-flex align-items-center gap-3"><input class="form-check-input mt-0" type="radio" name="abhaVerifyAuth" value="' + method + '" ' + (checked ? 'checked' : '') + ' ' + (disabled ? 'disabled' : '') + '><i class="bi ' + label.icon + ' fs-4 text-primary"></i><span><strong>' + label.title + destination + '</strong><small class="d-block text-muted">' + label.detail + '</small></span></span></label>';
        });
        $('#abhaVerifyAuthOptions').html(html || '<div class="alert alert-warning mb-0">This ABHA account did not return a supported OTP method. The Bridge must return MOBILE_OTP or AADHAAR_OTP.</div>');
        $('#abhaVerifySendOtpBtn').prop('disabled', !availableMethods.length);
    }
    function lookup() {
        var identifier = $('#abhaVerifyIdentifier').val().trim();
        if (!identifier) { alertBox('warning', lookupType === 'address' ? 'Enter an ABHA Address.' : 'Enter an ABHA Number or ID.'); return; }
        var button = $('#abhaVerifyLookupBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Looking up');
        var request = { lookup_type: lookupType, '<?= csrf_token() ?>':csrf() };
        request[lookupType === 'address' ? 'abha_address' : 'abha_id'] = identifier;
        $.post('<?= base_url('abha/register/validate') ?>', request, function (response) {
            button.prop('disabled', false).html('<i class="bi bi-search me-1"></i>Find Account');
            if (!response || response.ok != 1 || response.status !== 'VALID') { alertBox('danger', apiMessage(response, 'ABHA account was not found.')); return; }
            if (!response.txn_id) { alertBox('warning', 'The Bridge found this ABHA but did not return the login transaction ID. Update /v3/abha/login/search before OTP verification can continue.'); return; }
            lookupResponse = response;
            var account = response.account || {};
            $('#abhaVerifyFoundName').text(account.name || 'ABHA account found');
            $('#abhaVerifyFoundIdentity').text(account.abha_address || account.abha_number || identifier);
            renderAuthMethods(response);
            showStep(2);
        }, 'json').fail(function (xhr) {
            button.prop('disabled', false).html('<i class="bi bi-search me-1"></i>Find Account');
            alertBox('danger', apiMessage(xhr.responseJSON, 'Unable to look up the ABHA account.'));
        });
    }
    function requestOtp() {
        var method = $('input[name="abhaVerifyAuth"]:checked').val();
        if (!method || !lookupResponse) return;
        var account = lookupResponse.account || {};
        var button = $('#abhaVerifySendOtpBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Sending');
        $.post('<?= base_url('abha/register/login/request-otp') ?>', { txn_id:lookupResponse.txn_id, auth_method:method, abha_id:account.abha_number || '', abha_address:account.abha_address || '', '<?= csrf_token() ?>':csrf() }, function (response) {
            button.prop('disabled', false).html('<i class="bi bi-send me-1"></i>Send OTP');
            if (!response || response.ok != 1) { alertBox('danger', apiMessage(response, 'Unable to send OTP.')); return; }
            lookupResponse.txn_id = response.txn_id || lookupResponse.txn_id;
            lookupResponse.auth_method = method;
            var label = authLabel(method);
            $('#abhaVerifyOtpHint').text('OTP sent using ' + label.title + (response.masked_mobile ? ' (' + response.masked_mobile + ')' : '') + '.');
            $('#abhaVerifyOtp').val('');
            showStep(3);
            startResendTimer(response.resend_after);
            $('#abhaVerifyOtp').trigger('focus');
        }, 'json').fail(function (xhr) {
            button.prop('disabled', false).html('<i class="bi bi-send me-1"></i>Send OTP');
            alertBox('danger', apiMessage(xhr.responseJSON, 'The ABDM Bridge does not yet support account-bound OTP request.'));
        });
    }
    function profilePhoto(value) { return !value ? '' : (String(value).indexOf('data:') === 0 ? value : 'data:image/jpeg;base64,' + value); }
    function formatAbha(value) { return String(value || '').replace(/(\d{2})(\d{4})(\d{4})(\d{4})/, '$1-$2-$3-$4'); }
    function renderProfile(profile) {
        verifiedProfile = profile;
        $('#abhaVerifyProfileName').text(profile.name || '-');
        $('#abhaVerifyProfileAddress').text(profile.abha_address || '-');
        $('#abhaVerifyProfileNumber').text(formatAbha(profile.abha_number) || '-');
        $('#abhaVerifyProfileId').text(profile.abha_address || '-');
        $('#abhaVerifyProfileDob').text(profile.dob || '-');
        $('#abhaVerifyProfileGender').text(profile.gender || '-');
        $('#abhaVerifyProfileMobile').text(profile.mobile || '-');
        $('#abhaVerifyProfileFullAddress').text([profile.address, profile.district, profile.state, profile.zip].filter(Boolean).join(', ') || '-');
        var photo = profilePhoto(profile.photo);
        $('#abhaVerifyPhoto').toggleClass('d-none', !photo).attr('src', photo || '');
        var card = profile.card_base64 || '';
        if (card) {
            var cardSrc = String(card).indexOf('data:') === 0 ? card : 'data:' + (profile.card_content_type || 'image/png') + ';base64,' + card;
            var isOfficial = profile.card_source !== 'generated';
            var note = isOfficial
                ? ''
                : '<div class="small text-warning mt-1"><i class="bi bi-exclamation-triangle me-1"></i>Provisional card generated by the Bridge, not the official ABDM card.</div>';
            $('#abhaVerifyCardWrap').html('<img src="' + cardSrc + '" alt="ABHA card">' + note);
            $('#abhaVerifyDownloadCard').attr('href', cardSrc).removeClass('d-none');
        } else {
            var reason = profile.card_message ? escapeHtml(profile.card_message) : 'ABDM did not issue an official ABHA card for this session.';
            $('#abhaVerifyCardWrap').html('<div class="text-center text-muted"><i class="bi bi-card-image fs-1 d-block mb-2"></i>' + reason + '</div>');
            $('#abhaVerifyDownloadCard').addClass('d-none');
        }
        showStep(4);
    }
    function verifyOtp() {
        var otp = $('#abhaVerifyOtp').val().replace(/\D/g, '');
        if (otp.length !== 6) { alertBox('warning', 'Enter the 6-digit OTP.'); return; }
        var button = $('#abhaVerifyOtpBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Verifying');
        $.post('<?= base_url('abha/register/login/verify-otp') ?>', { txn_id:lookupResponse.txn_id, auth_method:lookupResponse.auth_method, otp:otp, '<?= csrf_token() ?>':csrf() }, function (response) {
            button.prop('disabled', false).html('<i class="bi bi-patch-check me-1"></i>Verify OTP');
            if (!response || response.ok != 1) { alertBox('danger', apiMessage(response, 'OTP verification failed.')); return; }
            stopTimer();
            renderProfile(response);
        }, 'json').fail(function (xhr) {
            button.prop('disabled', false).html('<i class="bi bi-patch-check me-1"></i>Verify OTP');
            alertBox('danger', apiMessage(xhr.responseJSON, 'OTP verification failed.'));
        });
    }
    $(function () {
        modal = new bootstrap.Modal(document.getElementById('abhaVerifyModal'));
        $('#abhaVerifyLookupBtn').on('click', lookup);
        $('#abhaVerifyIdentifier').on('keydown', function (event) { if (event.key === 'Enter') { event.preventDefault(); lookup(); } });
        $('#abhaVerifyBackLookupBtn').on('click', function () { showStep(1); });
        $('#abhaVerifySendOtpBtn').on('click', requestOtp);
        $('#abhaVerifyOtpBtn').on('click', verifyOtp);
        $('#abhaVerifyOtp').on('keydown', function (event) { if (event.key === 'Enter') { event.preventDefault(); verifyOtp(); } });
        $('#abhaVerifyResendBtn').on('click', requestOtp);
        $('#abhaVerifyCompareBtn').on('click', function () {
            if (!verifiedProfile) return;
            var profile = verifiedProfile;
            // Already linked: nothing else opens, so keep this window up for the operator to close.
            if (profile.need_confirmation === false && Number(profile.patient_id || 0) > 0) {
                alertBox('success', 'This ABHA is already linked to HMS patient <strong>' + escapeHtml(profile.p_code || '') + '</strong>. The page behind has been updated. Close this window when you are done.');
                if (typeof onVerified === 'function') onVerified(profile);
                return;
            }
            $('#abhaVerifyModal').one('hidden.bs.modal', function () {
                if (typeof onVerified === 'function') onVerified(profile);
            });
            modal.hide();
        });
        $('#abhaVerifyModal').on('hidden.bs.modal', stopTimer);
    });
    return {
        open: function (identifier, initialLookup, callback, requestedLookupType) {
            lookupResponse = initialLookup || null;
            verifiedProfile = null;
            onVerified = callback;
            lookupType = requestedLookupType === 'address' ? 'address' : 'number';
            $('#abhaVerifyIdentifierLabel').text(lookupType === 'address' ? 'ABHA Address' : 'ABHA Number or ID');
            $('#abhaVerifyIdentifier').attr('placeholder', lookupType === 'address' ? 'username@abdm' : '91-0000-0000-0000');
            $('#abhaVerifyIdentifierHelp').text(lookupType === 'address'
                ? 'Enter the patient\'s ABHA Address, for example username@abdm.'
                : 'Enter the patient\'s 14-digit ABHA Number or ID. A mobile number is not required for account lookup.');
            $('#abhaVerifyIdentifier').val(identifier || '');
            $('#abhaVerifyPhoto,#abhaVerifyDownloadCard').addClass('d-none');
            showStep(1);
            modal.show();
            if (initialLookup && initialLookup.ok == 1) {
                var account = initialLookup.account || {};
                $('#abhaVerifyFoundName').text(account.name || 'ABHA account found');
                $('#abhaVerifyFoundIdentity').text(account.abha_address || account.abha_number || identifier || '');
                renderAuthMethods(initialLookup);
                showStep(2);
            }
        }
    };
})();
</script>