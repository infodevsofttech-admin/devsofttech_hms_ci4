{{!-- Reusable ABHA OTP Link Modal --}}
<?php
/**
 * Partial: ABHA OTP Link Modal
 *
 * Include this once per page. Trigger it with:
 *   openAbhaOtpModal(patientId, currentAbhaId, mobileNumber)
 *
 * On successful verification the modal calls the global callback:
 *   window.onAbhaLinked(patientId, abhaId)   (if defined)
 * and also auto-saves via POST billing/patient/update_abha.
 *
 * Required on page: jQuery, Bootstrap 5, csrf_token()/csrf_hash() in scope.
 */
?>
<!-- ===== ABHA OTP Modal ===== -->
<div class="modal fade" id="abhaOtpModal" tabindex="-1" aria-labelledby="abhaOtpModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="abhaOtpModalLabel">
                    <i class="bi bi-person-check me-2"></i>Link ABHA via OTP
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <!-- Nav tabs -->
                <ul class="nav nav-tabs mb-3" id="abhaOtpTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-aadhaar-btn" data-bs-toggle="tab"
                                data-bs-target="#tab-aadhaar" type="button" role="tab">
                            <i class="bi bi-fingerprint me-1"></i>Aadhaar OTP
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-mobile-btn" data-bs-toggle="tab"
                                data-bs-target="#tab-mobile" type="button" role="tab">
                            <i class="bi bi-phone me-1"></i>Mobile OTP
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="abhaOtpTabContent">

                    <!-- ===== Aadhaar OTP Tab ===== -->
                    <div class="tab-pane fade show active" id="tab-aadhaar" role="tabpanel">
                        <!-- Step 1: Enter Aadhaar -->
                        <div id="aadhaar-step1">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Aadhaar Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="abha_aadhaar_input"
                                       maxlength="12" placeholder="12-digit Aadhaar number" inputmode="numeric"
                                       autocomplete="off">
                                <div class="form-text">Enter patient's Aadhaar number to receive OTP.</div>
                            </div>
                            <div id="aadhaar_step1_msg"></div>
                            <button type="button" class="btn btn-primary w-100" id="btn_aadhaar_send_otp">
                                <i class="bi bi-send me-1"></i>Send OTP
                            </button>
                        </div>

                        <!-- Step 2: Enter OTP -->
                        <div id="aadhaar-step2" style="display:none">
                            <div class="alert alert-success py-2 mb-3" id="aadhaar_otp_sent_msg">
                                OTP sent to Aadhaar-linked mobile.
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Patient's Mobile Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="abha_aadhaar_mobile"
                                       maxlength="10" placeholder="10-digit mobile number" inputmode="numeric"
                                       autocomplete="off">
                                <div class="form-text">Mobile number for ABHA communication.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Enter OTP <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-center fw-bold fs-5 letter-spacing-2"
                                       id="abha_aadhaar_otp" maxlength="6" placeholder="6-digit OTP"
                                       inputmode="numeric" autocomplete="one-time-code">
                                <div class="form-text text-end">
                                    Didn't receive? <button type="button" class="btn btn-link btn-sm p-0" id="btn_aadhaar_resend">Resend OTP</button>
                                </div>
                            </div>
                            <div id="aadhaar_step2_msg"></div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary" id="btn_aadhaar_back">
                                    <i class="bi bi-arrow-left me-1"></i>Back
                                </button>
                                <button type="button" class="btn btn-success flex-fill" id="btn_aadhaar_verify_otp">
                                    <i class="bi bi-check-circle me-1"></i>Verify OTP &amp; Link ABHA
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ===== Mobile OTP Tab ===== -->
                    <div class="tab-pane fade" id="tab-mobile" role="tabpanel">
                        <!-- Step 1: Enter mobile -->
                        <div id="mobile-step1">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Mobile Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="abha_mobile_input"
                                       maxlength="10" placeholder="10-digit mobile number" inputmode="numeric"
                                       autocomplete="off">
                                <div class="form-text">Mobile number registered with ABHA / ABDM.</div>
                            </div>
                            <div id="mobile_step1_msg"></div>
                            <button type="button" class="btn btn-primary w-100" id="btn_mobile_send_otp">
                                <i class="bi bi-send me-1"></i>Send OTP
                            </button>
                        </div>

                        <!-- Step 2: Enter OTP -->
                        <div id="mobile-step2" style="display:none">
                            <div class="alert alert-success py-2 mb-3">
                                OTP sent to <strong id="mobile_otp_sent_to"></strong>.
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Enter OTP <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-center fw-bold fs-5"
                                       id="abha_mobile_otp" maxlength="6" placeholder="6-digit OTP"
                                       inputmode="numeric" autocomplete="one-time-code">
                            </div>
                            <div id="mobile_step2_msg"></div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary" id="btn_mobile_back">
                                    <i class="bi bi-arrow-left me-1"></i>Back
                                </button>
                                <button type="button" class="btn btn-success flex-fill" id="btn_mobile_verify_otp">
                                    <i class="bi bi-check-circle me-1"></i>Verify OTP &amp; Link ABHA
                                </button>
                            </div>
                        </div>
                    </div>

                </div><!-- /tab-content -->
            </div><!-- /modal-body -->
        </div>
    </div>
</div>
<!-- ===== End ABHA OTP Modal ===== -->

<script>
(function () {
    'use strict';

    /* ---- state ---- */
    var _patientId   = 0;
    var _aadhaarTxnId = '';
    var _mobileTxnId  = '';

    /* ---- helpers ---- */
    function csrfPair() {
        var input = document.querySelector('input[name="<?= csrf_token() ?>"]');
        return {
            name:  '<?= csrf_token() ?>',
            value: input ? input.value : '<?= csrf_hash() ?>'
        };
    }
    function updateCsrf(data) {
        if (!data || !data.csrfName || !data.csrfHash) return;
        var inp = document.querySelector('input[name="' + data.csrfName + '"]');
        if (inp) inp.value = data.csrfHash;
    }

    function showMsg(id, type, html) {
        var cls = type === 'success' ? 'alert-success' : (type === 'warning' ? 'alert-warning' : 'alert-danger');
        document.getElementById(id).innerHTML = '<div class="alert ' + cls + ' py-2 mt-2">' + html + '</div>';
    }
    function clearMsg(id) { document.getElementById(id).innerHTML = ''; }

    function postAbdm(url, data, cb) {
        var csrf = csrfPair();
        data[csrf.name] = csrf.value;
        $.post(url, data, function (res) { updateCsrf(res); cb(res || {}); }, 'json')
         .fail(function (xhr) { cb(xhr && xhr.responseJSON ? xhr.responseJSON : {}); });
    }

    function disableBtn(id) { $('#' + id).prop('disabled', true); }
    function enableBtn(id)  { $('#' + id).prop('disabled', false); }

    /* ---- public opener ---- */
    window.openAbhaOtpModal = function (patientId, currentAbha, prefillMobile) {
        _patientId   = patientId || 0;
        _aadhaarTxnId = '';
        _mobileTxnId  = '';

        // reset all steps
        $('#aadhaar-step1').show(); $('#aadhaar-step2').hide();
        $('#mobile-step1').show();  $('#mobile-step2').hide();
        $('#abha_aadhaar_input').val('');
        $('#abha_aadhaar_mobile').val(prefillMobile || '');
        $('#abha_aadhaar_otp').val('');
        $('#abha_mobile_input').val(prefillMobile || '');
        $('#abha_mobile_otp').val('');
        clearMsg('aadhaar_step1_msg'); clearMsg('aadhaar_step2_msg');
        clearMsg('mobile_step1_msg');  clearMsg('mobile_step2_msg');

        var modal = new bootstrap.Modal(document.getElementById('abhaOtpModal'));
        modal.show();
    };

    /* ---- save ABHA to patient ---- */
    function saveAbhaToPatient(abhaId) {
        var csrf = csrfPair();
        var payload = { p_id: _patientId, abha_id: abhaId, verified: 1 };
        payload[csrf.name] = csrf.value;
        $.post('<?= base_url('billing/patient/update_abha') ?>', payload, function (res) {
            updateCsrf(res);
            // Update the visible field on the profile page if present
            var inp = document.getElementById('input_abha_id');
            if (inp) inp.value = abhaId;
            // Update the display-only ABHA row if present
            var display = document.getElementById('abha_id_display');
            if (display) display.textContent = abhaId;
            // Invoke global callback if defined
            if (typeof window.onAbhaLinked === 'function') {
                window.onAbhaLinked(_patientId, abhaId);
            }
        }, 'json');
    }

    /* ---- success handler ---- */
    function handleVerifySuccess(data) {
        // Gateway returns ABHAProfile in: data.ABHAProfile  (Aadhaar flow)
        //                             or: data.accounts[0]  (Mobile flow)
        var payload = data.data || data;
        var profile = payload.ABHAProfile ||
                      payload.profile ||
                      (Array.isArray(payload.accounts) && payload.accounts.length > 0 ? payload.accounts[0] : {});
        var abhaId  = (payload.ABHANumber || payload.abha_number || payload.abha_id ||
                       profile.ABHANumber || profile.abha_number || profile.abha_id ||
                       (profile.preferredAddress || profile.preferredAbhaAddress || '').replace(/@.+$/, '') ||
                       '').trim();
        var name    = (payload.name || payload.full_name ||
                       profile.name || profile.full_name ||
                       ([profile.firstName, profile.middleName, profile.lastName].filter(Boolean).join(' ')) ||
                       '').trim();

        if (abhaId === '') {
            return false;
        }

        // Normalise: ensure 14-digit dash format if needed
        var digits = abhaId.replace(/-/g, '');
        if (digits.length === 14 && /^\d+$/.test(digits)) {
            abhaId = digits; // store raw 14 digits
        }

        saveAbhaToPatient(abhaId);
        return true;
    }

    /* ================================================================
       AADHAAR OTP FLOW
    ================================================================ */

    $('#btn_aadhaar_send_otp').on('click', function () {
        clearMsg('aadhaar_step1_msg');
        var aadhaar = $('#abha_aadhaar_input').val().trim();
        if (!/^\d{12}$/.test(aadhaar)) {
            showMsg('aadhaar_step1_msg', 'danger', 'Enter a valid 12-digit Aadhaar number.');
            return;
        }
        disableBtn('btn_aadhaar_send_otp');
        $('#btn_aadhaar_send_otp').html('<span class="spinner-border spinner-border-sm me-1"></span>Sending…');

        postAbdm('<?= base_url('billing/patient/abha_aadhaar_generate_otp') ?>', { aadhaar: aadhaar }, function (res) {
            enableBtn('btn_aadhaar_send_otp');
            $('#btn_aadhaar_send_otp').html('<i class="bi bi-send me-1"></i>Send OTP');

            if (res.ok == 1) {
                var d = res.data || res;
                _aadhaarTxnId = d.txnId || d.txn_id || res.txnId || res.txn_id || '';
                var reqId = res.request_id || '';
                var modeTag = res.mode === 'test' ? ' <span class="badge bg-warning text-dark ms-1">SANDBOX — no real OTP sent</span>' : '';
                $('#aadhaar_otp_sent_msg').html(
                    '<i class="bi bi-check-circle me-1"></i>OTP sent to Aadhaar-linked mobile.' + modeTag
                    + (_aadhaarTxnId ? '<br><small class="text-muted">TxnID: <code>' + $('<div>').text(_aadhaarTxnId).html() + '</code></small>' : '')
                    + (reqId ? '<br><small class="text-muted">Gateway Request ID: <code class="user-select-all">' + $('<div>').text(reqId).html() + '</code></small>' : '')
                );
                $('#aadhaar-step1').hide();
                $('#aadhaar-step2').show();
                setTimeout(function () { $('#abha_aadhaar_otp').trigger('focus'); }, 100);
            } else {
                var errMsg = res.error_text || res.message || res.error || 'Failed to send OTP. Please try again.';
                showMsg('aadhaar_step1_msg', 'danger', '<i class="bi bi-x-circle me-1"></i>' + $('<div>').text(errMsg).html());
            }
        });
    });

    $('#btn_aadhaar_back').on('click', function () {
        $('#aadhaar-step2').hide();
        $('#aadhaar-step1').show();
        clearMsg('aadhaar_step2_msg');
    });

    $('#btn_aadhaar_resend').on('click', function () {
        var aadhaar = $('#abha_aadhaar_input').val().trim();
        if (!/^\d{12}$/.test(aadhaar)) {
            showMsg('aadhaar_step2_msg', 'warning', 'Go back and re-enter the Aadhaar number.');
            return;
        }
        clearMsg('aadhaar_step2_msg');
        $('#btn_aadhaar_resend').prop('disabled', true).text('Sending…');
        $('#abha_aadhaar_otp').val('');

        postAbdm('<?= base_url('billing/patient/abha_aadhaar_generate_otp') ?>', { aadhaar: aadhaar }, function (res) {
            $('#btn_aadhaar_resend').prop('disabled', false).text('Resend OTP');
            if (res.ok == 1 || (res.data && res.data.txnId)) {
                var d = res.data || res;
                _aadhaarTxnId = d.txnId || d.txn_id || res.txnId || res.txn_id || _aadhaarTxnId;
                var reqId = res.request_id || '';
                var modeTag = res.mode === 'test' ? ' <span class="badge bg-warning text-dark ms-1">SANDBOX</span>' : '';
                $('#aadhaar_otp_sent_msg').html(
                    '<i class="bi bi-check-circle me-1"></i>New OTP sent.' + modeTag
                    + (reqId ? '<br><small class="text-muted">Request ID: <code class="user-select-all">' + $('<div>').text(reqId).html() + '</code></small>' : '')
                );
                showMsg('aadhaar_step2_msg', 'success', '<i class="bi bi-check-circle me-1"></i>OTP resent. Enter the new OTP above.');
                setTimeout(function () { $('#abha_aadhaar_otp').trigger('focus'); }, 100);
            } else {
                var errMsg = res.error_text || res.message || res.error || 'Resend failed.';
                showMsg('aadhaar_step2_msg', 'danger', '<i class="bi bi-x-circle me-1"></i>' + $('<div>').text(errMsg).html());
            }
        });
    });

    $('#btn_aadhaar_verify_otp').on('click', function () {
        clearMsg('aadhaar_step2_msg');
        var otp    = $('#abha_aadhaar_otp').val().trim();
        var mobile = $('#abha_aadhaar_mobile').val().trim();
        if (!/^\d{6}$/.test(otp)) {
            showMsg('aadhaar_step2_msg', 'danger', 'Enter the 6-digit OTP.');
            return;
        }
        if (!/^\d{10}$/.test(mobile)) {
            showMsg('aadhaar_step2_msg', 'danger', 'Enter a valid 10-digit mobile number.');
            return;
        }
        if (!_aadhaarTxnId) {
            showMsg('aadhaar_step2_msg', 'danger', 'Transaction ID missing. Please restart.');
            return;
        }
        disableBtn('btn_aadhaar_verify_otp');
        $('#btn_aadhaar_verify_otp').html('<span class="spinner-border spinner-border-sm me-1"></span>Verifying…');

        postAbdm('<?= base_url('billing/patient/abha_aadhaar_verify_otp') ?>', { txnId: _aadhaarTxnId, otp: otp, mobile: mobile }, function (res) {
            enableBtn('btn_aadhaar_verify_otp');
            $('#btn_aadhaar_verify_otp').html('<i class="bi bi-check-circle me-1"></i>Verify OTP &amp; Link ABHA');

            if (res.ok == 1 && handleVerifySuccess(res)) {
                var _p = res.data || res;
                var _pr = _p.ABHAProfile || _p.profile || (Array.isArray(_p.accounts) && _p.accounts.length > 0 ? _p.accounts[0] : {});
                var abhaId = (_p.ABHANumber || _p.abha_number || _pr.ABHANumber || _pr.abha_number ||
                              (_pr.preferredAddress || _pr.preferredAbhaAddress || '').replace(/@.+$/, '') || '').replace(/-/g,'');
                showMsg('aadhaar_step2_msg', 'success',
                    '<i class="bi bi-check-circle me-1"></i>ABHA linked! <strong>' + $('<div>').text(abhaId).html() + '</strong>'
                );
                $('#btn_aadhaar_verify_otp').prop('disabled', true).text('Linked ✓');
                setTimeout(function () { bootstrap.Modal.getInstance(document.getElementById('abhaOtpModal')).hide(); }, 1800);
            } else {
                var errMsg = res.error_text || res.message || res.error || 'OTP verification failed.';
                showMsg('aadhaar_step2_msg', 'danger', '<i class="bi bi-x-circle me-1"></i>' + $('<div>').text(errMsg).html());
            }
        });
    });

    /* Allow Enter key in OTP field */
    $('#abha_aadhaar_otp').on('keydown', function (e) {
        if (e.key === 'Enter') $('#btn_aadhaar_verify_otp').trigger('click');
    });

    /* ================================================================
       MOBILE OTP FLOW
    ================================================================ */

    $('#btn_mobile_send_otp').on('click', function () {
        clearMsg('mobile_step1_msg');
        var mobile = $('#abha_mobile_input').val().trim();
        if (!/^\d{10}$/.test(mobile)) {
            showMsg('mobile_step1_msg', 'danger', 'Enter a valid 10-digit mobile number.');
            return;
        }
        disableBtn('btn_mobile_send_otp');
        $('#btn_mobile_send_otp').html('<span class="spinner-border spinner-border-sm me-1"></span>Sending…');

        postAbdm('<?= base_url('billing/patient/abha_mobile_generate_otp') ?>', { mobile: mobile }, function (res) {
            enableBtn('btn_mobile_send_otp');
            $('#btn_mobile_send_otp').html('<i class="bi bi-send me-1"></i>Send OTP');

            if (res.ok == 1) {
                var d = res.data || res;
                _mobileTxnId = d.txnId || d.txn_id || res.txnId || res.txn_id || '';
                $('#mobile_otp_sent_to').text(mobile.replace(/(\d{2})\d{6}(\d{2})/, '$1xxxxxx$2'));
                $('#mobile-step1').hide();
                $('#mobile-step2').show();
                setTimeout(function () { $('#abha_mobile_otp').trigger('focus'); }, 100);
            } else {
                var errMsg = res.error_text || res.message || res.error || 'Failed to send OTP.';
                showMsg('mobile_step1_msg', 'danger', '<i class="bi bi-x-circle me-1"></i>' + $('<div>').text(errMsg).html());
            }
        });
    });

    $('#btn_mobile_back').on('click', function () {
        $('#mobile-step2').hide();
        $('#mobile-step1').show();
        clearMsg('mobile_step2_msg');
    });

    $('#btn_mobile_verify_otp').on('click', function () {
        clearMsg('mobile_step2_msg');
        var otp = $('#abha_mobile_otp').val().trim();
        if (!/^\d{6}$/.test(otp)) {
            showMsg('mobile_step2_msg', 'danger', 'Enter the 6-digit OTP.');
            return;
        }
        if (!_mobileTxnId) {
            showMsg('mobile_step2_msg', 'danger', 'Transaction ID missing. Please restart.');
            return;
        }
        disableBtn('btn_mobile_verify_otp');
        $('#btn_mobile_verify_otp').html('<span class="spinner-border spinner-border-sm me-1"></span>Verifying…');

        postAbdm('<?= base_url('billing/patient/abha_mobile_verify_otp') ?>', { txnId: _mobileTxnId, otp: otp }, function (res) {
            enableBtn('btn_mobile_verify_otp');
            $('#btn_mobile_verify_otp').html('<i class="bi bi-check-circle me-1"></i>Verify OTP &amp; Link ABHA');

            if (res.ok == 1 && handleVerifySuccess(res)) {
                var _p = res.data || res;
                var _pr = _p.ABHAProfile || _p.profile || (Array.isArray(_p.accounts) && _p.accounts.length > 0 ? _p.accounts[0] : {});
                var abhaId = (_p.ABHANumber || _p.abha_number || _pr.ABHANumber || _pr.abha_number ||
                              (_pr.preferredAddress || _pr.preferredAbhaAddress || '').replace(/@.+$/, '') || '').replace(/-/g,'');
                showMsg('mobile_step2_msg', 'success',
                    '<i class="bi bi-check-circle me-1"></i>ABHA linked! <strong>' + $('<div>').text(abhaId).html() + '</strong>'
                );
                $('#btn_mobile_verify_otp').prop('disabled', true).text('Linked ✓');
                setTimeout(function () { bootstrap.Modal.getInstance(document.getElementById('abhaOtpModal')).hide(); }, 1800);
            } else {
                var errMsg = res.error_text || res.message || res.error || 'OTP verification failed.';
                showMsg('mobile_step2_msg', 'danger', '<i class="bi bi-x-circle me-1"></i>' + $('<div>').text(errMsg).html());
            }
        });
    });

    $('#abha_mobile_otp').on('keydown', function (e) {
        if (e.key === 'Enter') $('#btn_mobile_verify_otp').trigger('click');
    });

}());
</script>
