<?php
/**
 * Partial: ABHA Create / Verify panel
 *
 * Included inside the #profile-abha tab pane on Person_profile_V.php.
 * PHP vars available from parent view: $data[0], $patientAbhaId
 *
 * Create ABHA  — 4-step wizard (Aadhaar → OTP → Mobile Confirm → ABHA Ready)
 * Verify ABHA  — inline Aadhaar OTP + Mobile OTP sub-tabs
 *
 * All element IDs use the prefix "cvp_" to avoid conflicts with abha_otp_modal.php.
 */
$_pid  = (int) ($data[0]->id ?? 0);
$_abha = esc($patientAbhaId ?? '');
$_mob  = esc($data[0]->mphone1 ?? '');
$_aadh = esc($data[0]->udai ?? '');
?>

<h5 class="card-title mb-0">ABHA Number Create and Verify</h5>
<p class="text-muted small mb-3">Create and verify your unique health identification number</p>

<!-- ── Inner tabs: Create / Verify ─────────────────────────────── -->
<ul class="nav nav-tabs mb-3" id="cvpTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="cvp-create-tab-btn"
                data-bs-toggle="tab" data-bs-target="#cvp-create" type="button" role="tab">
            Create ABHA
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link text-primary" id="cvp-verify-tab-btn"
                data-bs-toggle="tab" data-bs-target="#cvp-verify" type="button" role="tab">
            Verify ABHA
        </button>
    </li>
</ul>

<div class="tab-content" id="cvpTabContent">

    <!-- ══════════════════════════════════════════════════════════════
         CREATE ABHA TAB
    ══════════════════════════════════════════════════════════════ -->
    <div class="tab-pane fade show active" id="cvp-create" role="tabpanel">

        <!-- Step progress indicator -->
        <div class="d-flex align-items-center mb-4 px-1" id="cvp-step-indicator">
            <?php
            $stepLabels = ['Aadhaar &<br>Consent', 'OTP<br>Verify', 'Mobile<br>Confirm', 'ABHA<br>Ready'];
            foreach ($stepLabels as $idx => $lbl) :
                $n = $idx + 1;
            ?>
            <div class="d-flex flex-column align-items-center" id="cvp-step-dot-<?= $n ?>">
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold"
                     id="cvp-step-circle-<?= $n ?>"
                     style="width:36px;height:36px;border:2px solid <?= $n===1?'#0d6efd':'#dee2e6' ?>;
                            background:<?= $n===1?'#0d6efd':'' ?>;color:<?= $n===1?'#fff':'' ?>">
                    <?= $n ?>
                </div>
                <small class="mt-1 text-center" id="cvp-step-label-<?= $n ?>"
                       style="font-size:11px;line-height:1.3;color:<?= $n===1?'#0d6efd':'#6c757d' ?>">
                    <?= $lbl ?>
                </small>
            </div>
            <?php if ($n < 4) : ?>
            <div class="flex-fill mx-2" id="cvp-line-<?= $n ?>-<?= $n+1 ?>"
                 style="height:2px;background:#dee2e6;margin-bottom:22px"></div>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <!-- ── Step 1: Aadhaar & Consent ─────────────────────────── -->
        <div id="cvp-step1">
            <div class="alert alert-info py-2 small mb-3">
                <i class="bi bi-info-circle me-1"></i>
                Enter the patient's Aadhaar number. An OTP will be sent to the mobile linked with it.
            </div>
            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Aadhaar Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="cvp_aadhaar_input"
                           maxlength="12" placeholder="0000-0000-0000"
                           inputmode="numeric" autocomplete="off"
                           value="<?= $_aadh ?>">
                    <div class="form-text">Mobile must be linked with Aadhaar for OTP.</div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Authentication Via</label>
                    <select class="form-select" id="cvp_auth_via">
                        <option value="OTP" selected>OTP</option>
                    </select>
                </div>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="cvp_consent">
                <label class="form-check-label small" for="cvp_consent">
                    I confirm the patient has given consent to share their Aadhaar details with NHA/ABDM for ABHA number creation.
                    <a href="https://abdm.gov.in/publications/consent-framework" target="_blank" rel="noopener noreferrer" class="ms-1">(read full consent)</a>
                </label>
            </div>
            <div id="cvp_step1_msg"></div>
            <button type="button" class="btn btn-primary" id="cvp_btn_send_otp">
                <i class="bi bi-send me-1"></i>Send OTP
            </button>
        </div>

        <!-- ── Step 2: OTP Verify ─────────────────────────────────── -->
        <div id="cvp-step2" style="display:none">
            <div class="alert alert-success py-2 mb-3" id="cvp_otp_sent_msg">
                <i class="bi bi-check-circle me-1"></i>OTP sent to Aadhaar-linked mobile.
            </div>
            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Patient's Mobile <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="cvp_mobile_input"
                           maxlength="10" placeholder="10-digit mobile"
                           inputmode="numeric" autocomplete="off"
                           value="<?= $_mob ?>">
                    <div class="form-text">Mobile number for ABHA communication.</div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Enter OTP <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text-center fw-bold fs-5"
                           id="cvp_otp_input" maxlength="6" placeholder="6-digit OTP"
                           inputmode="numeric" autocomplete="one-time-code">
                    <div class="form-text text-end">
                        <button type="button" class="btn btn-link btn-sm p-0" id="cvp_btn_resend">Resend OTP</button>
                    </div>
                </div>
            </div>
            <div id="cvp_step2_msg"></div>
            <div class="d-flex gap-2 mt-2">
                <button type="button" class="btn btn-outline-secondary" id="cvp_btn_step2_back">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </button>
                <button type="button" class="btn btn-success" id="cvp_btn_verify_otp">
                    <i class="bi bi-check-circle me-1"></i>Verify OTP
                </button>
            </div>
        </div>

        <!-- ── Step 3: Mobile Confirm ─────────────────────────────── -->
        <div id="cvp-step3" style="display:none">
            <div class="alert alert-warning py-2 mb-3">
                <i class="bi bi-phone me-1"></i>
                Aadhaar verified. Please confirm your mobile number to complete ABHA linking.
            </div>
            <div class="col-sm-6 mb-3">
                <label class="form-label fw-semibold">Mobile Number <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="cvp_mob_confirm_input"
                       maxlength="10" placeholder="10-digit mobile"
                       inputmode="numeric" autocomplete="off"
                       value="<?= $_mob ?>">
            </div>
            <div id="cvp_step3_send_msg"></div>
            <button type="button" class="btn btn-primary" id="cvp_btn_mob_send_otp">
                <i class="bi bi-send me-1"></i>Send Mobile OTP
            </button>

            <!-- Mobile OTP sub-section (revealed after send) -->
            <div id="cvp-step3-otp" style="display:none" class="mt-3">
                <div class="col-sm-6 mb-3">
                    <label class="form-label fw-semibold">Mobile OTP <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text-center fw-bold fs-5"
                           id="cvp_mob_otp_input" maxlength="6" placeholder="6-digit OTP"
                           inputmode="numeric" autocomplete="one-time-code">
                </div>
                <div id="cvp_step3_otp_msg"></div>
                <div class="d-flex gap-2 mt-2">
                    <button type="button" class="btn btn-outline-secondary" id="cvp_btn_step3_back">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </button>
                    <button type="button" class="btn btn-success" id="cvp_btn_mob_verify_otp">
                        <i class="bi bi-check-circle me-1"></i>Verify Mobile OTP
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Step 4: ABHA Ready ─────────────────────────────────── -->
        <div id="cvp-step4" style="display:none">
            <div class="text-center py-3">
                <div class="mb-3 position-relative d-inline-block">
                    <img id="cvp_profile_photo" src="" alt="Profile"
                         class="rounded-circle d-none"
                         style="width:80px;height:80px;object-fit:cover;border:3px solid #198754">
                    <i class="bi bi-person-check-fill text-success" style="font-size:3.5rem"
                       id="cvp_profile_icon"></i>
                </div>
                <h4 class="fw-bold text-success mb-1" id="cvp_abha_number_display">—</h4>
                <p class="mb-0 fw-semibold" id="cvp_abha_name_display"></p>
                <p class="text-muted small" id="cvp_abha_address_display"></p>
                <div id="cvp_step4_msg"></div>
                <div class="d-flex justify-content-center gap-2 mt-3">
                    <button type="button" class="btn btn-success" id="cvp_btn_link_patient">
                        <i class="bi bi-link-45deg me-1"></i>Link to Patient Record
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="cvp_btn_reset">
                        <i class="bi bi-arrow-repeat me-1"></i>Start Over
                    </button>
                </div>
            </div>
        </div>

    </div><!-- /cvp-create -->

    <!-- ══════════════════════════════════════════════════════════════
         VERIFY ABHA TAB
    ══════════════════════════════════════════════════════════════ -->
    <div class="tab-pane fade" id="cvp-verify" role="tabpanel">

        <ul class="nav nav-tabs nav-tabs-bordered mb-3" id="cvpVerifySubTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab"
                        data-bs-target="#cvpv-aadhaar" type="button" role="tab">
                    <i class="bi bi-fingerprint me-1"></i>Aadhaar OTP
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab"
                        data-bs-target="#cvpv-mobile" type="button" role="tab">
                    <i class="bi bi-phone me-1"></i>Mobile OTP
                </button>
            </li>
        </ul>

        <div class="tab-content" id="cvpVerifySubContent">

            <!-- Aadhaar OTP Verify -->
            <div class="tab-pane fade show active" id="cvpv-aadhaar" role="tabpanel">
                <div id="cvpv-a-step1">
                    <div class="col-sm-6 mb-3">
                        <label class="form-label fw-semibold">Aadhaar Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="cvpv_aadhaar_input"
                               maxlength="12" placeholder="12-digit Aadhaar"
                               inputmode="numeric" autocomplete="off"
                               value="<?= $_aadh ?>">
                    </div>
                    <div id="cvpv_a_step1_msg"></div>
                    <button type="button" class="btn btn-primary" id="cvpv_btn_a_send_otp">
                        <i class="bi bi-send me-1"></i>Send OTP
                    </button>
                </div>
                <div id="cvpv-a-step2" style="display:none">
                    <div class="alert alert-success py-2 mb-3" id="cvpv_a_otp_sent_msg">
                        <i class="bi bi-check-circle me-1"></i>OTP sent.
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Mobile <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="cvpv_a_mobile"
                                   maxlength="10" placeholder="10-digit mobile"
                                   inputmode="numeric" autocomplete="off"
                                   value="<?= $_mob ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Enter OTP <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-center fw-bold fs-5"
                                   id="cvpv_a_otp" maxlength="6" placeholder="6-digit OTP"
                                   inputmode="numeric" autocomplete="one-time-code">
                        </div>
                    </div>
                    <div id="cvpv_a_step2_msg"></div>
                    <div class="d-flex gap-2 mt-2">
                        <button type="button" class="btn btn-outline-secondary" id="cvpv_btn_a_back">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </button>
                        <button type="button" class="btn btn-success" id="cvpv_btn_a_verify">
                            <i class="bi bi-check-circle me-1"></i>Verify &amp; Link ABHA
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile OTP Verify -->
            <div class="tab-pane fade" id="cvpv-mobile" role="tabpanel">
                <div id="cvpv-m-step1">
                    <div class="col-sm-6 mb-3">
                        <label class="form-label fw-semibold">Mobile Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="cvpv_mobile_input"
                               maxlength="10" placeholder="10-digit mobile"
                               inputmode="numeric" autocomplete="off"
                               value="<?= $_mob ?>">
                    </div>
                    <div id="cvpv_m_step1_msg"></div>
                    <button type="button" class="btn btn-primary" id="cvpv_btn_m_send_otp">
                        <i class="bi bi-send me-1"></i>Send OTP
                    </button>
                </div>
                <div id="cvpv-m-step2" style="display:none">
                    <div class="alert alert-success py-2 mb-3">
                        OTP sent to <strong id="cvpv_m_sent_to"></strong>.
                    </div>
                    <div class="col-sm-6 mb-3">
                        <label class="form-label fw-semibold">Enter OTP <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-center fw-bold fs-5"
                               id="cvpv_m_otp" maxlength="6" placeholder="6-digit OTP"
                               inputmode="numeric" autocomplete="one-time-code">
                    </div>
                    <div id="cvpv_m_step2_msg"></div>
                    <div class="d-flex gap-2 mt-2">
                        <button type="button" class="btn btn-outline-secondary" id="cvpv_btn_m_back">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </button>
                        <button type="button" class="btn btn-success" id="cvpv_btn_m_verify">
                            <i class="bi bi-check-circle me-1"></i>Verify &amp; Link ABHA
                        </button>
                    </div>
                </div>
            </div>

        </div><!-- /cvpVerifySubContent -->
    </div><!-- /cvp-verify -->

</div><!-- /cvpTabContent -->

<script>
(function () {
    'use strict';

    var _pid           = <?= $_pid ?>;
    var _txn           = '';   // Create: Aadhaar txnId
    var _mobTxn        = '';   // Create: Mobile txnId (step 3)
    var _foundAbha     = '';   // Create: resolved ABHA number
    var _foundName     = '';
    var _foundAddr     = '';
    var _foundPhoto    = '';
    var _vAadhTxn      = '';   // Verify-Aadhaar txnId
    var _vMobTxn       = '';   // Verify-Mobile txnId

    /* ── helpers ─────────────────────────────────────────────────── */

    function csrfPair() {
        var inp = document.querySelector('input[name="<?= csrf_token() ?>"]');
        return { name: '<?= csrf_token() ?>', value: inp ? inp.value : '<?= csrf_hash() ?>' };
    }
    function updateCsrf(d) {
        if (!d || !d.csrfName || !d.csrfHash) return;
        var inp = document.querySelector('input[name="' + d.csrfName + '"]');
        if (inp) inp.value = d.csrfHash;
    }
    function post(url, data, cb) {
        var c = csrfPair(); data[c.name] = c.value;
        $.post(url, data, function (r) { updateCsrf(r); cb(r || {}); }, 'json')
         .fail(function (xhr) { cb(xhr && xhr.responseJSON ? xhr.responseJSON : {}); });
    }
    function showMsg(id, type, html) {
        var cls = { success: 'alert-success', warning: 'alert-warning', danger: 'alert-danger' }[type] || 'alert-danger';
        var el = document.getElementById(id);
        if (el) el.innerHTML = '<div class="alert ' + cls + ' py-2 mt-2">' + html + '</div>';
    }
    function clearMsg(id) { var el = document.getElementById(id); if (el) el.innerHTML = ''; }
    function setBtn(id, disabled, html) {
        var b = document.getElementById(id);
        if (!b) return;
        b.disabled = disabled;
        if (html !== undefined) b.innerHTML = html;
    }
    function safe(s) { return $('<div>').text(s).html(); }

    /* ── step indicator ──────────────────────────────────────────── */
    function setStep(n) {
        for (var i = 1; i <= 4; i++) {
            var circle = document.getElementById('cvp-step-circle-' + i);
            var label  = document.getElementById('cvp-step-label-'  + i);
            if (!circle) continue;
            if (i < n) {
                circle.style.cssText = 'width:36px;height:36px;border:2px solid #198754;background:#198754;color:#fff';
                circle.innerHTML = '<i class="bi bi-check-lg"></i>';
                if (label) label.style.color = '#198754';
            } else if (i === n) {
                circle.style.cssText = 'width:36px;height:36px;border:2px solid #0d6efd;background:#0d6efd;color:#fff';
                circle.textContent = i;
                if (label) label.style.color = '#0d6efd';
            } else {
                circle.style.cssText = 'width:36px;height:36px;border:2px solid #dee2e6;background:;color:';
                circle.textContent = i;
                if (label) label.style.color = '#6c757d';
            }
        }
        for (var j = 1; j <= 3; j++) {
            var line = document.getElementById('cvp-line-' + j + '-' + (j + 1));
            if (line) line.style.background = j < n ? '#198754' : '#dee2e6';
        }
    }

    /* ── extract ABHA data from gateway response ─────────────────── */
    function extractAbha(res) {
        var payload = res.data || res;
        var profile = payload.ABHAProfile ||
                      (Array.isArray(payload.accounts) && payload.accounts.length > 0 ? payload.accounts[0] : {});
        var abhaId  = (payload.ABHANumber || payload.abha_number || payload.abha_id ||
                       profile.ABHANumber || profile.abha_number || profile.abha_id ||
                       (profile.preferredAddress || profile.preferredAbhaAddress || '').replace(/@.+$/, '') ||
                       '').trim();
        var name    = (payload.name || profile.name || profile.full_name ||
                       [profile.firstName, profile.middleName, profile.lastName].filter(Boolean).join(' ') || '').trim();
        var addr    = (profile.preferredAbhaAddress || profile.preferredAddress || '').replace(/@.*/, '');
        var photo   = profile.profilePhoto || '';
        return { abhaId: abhaId, name: name, addr: addr, photo: photo };
    }

    /* ── show step 4 ─────────────────────────────────────────────── */
    function showStep4(abhaId, name, addr, photo) {
        _foundAbha = abhaId; _foundName = name; _foundAddr = addr; _foundPhoto = photo;
        var digits = abhaId.replace(/-/g, '');
        var fmt = /^\d{14}$/.test(digits)
            ? digits.replace(/(\d{2})(\d{4})(\d{4})(\d{4})/, '$1-$2-$3-$4')
            : abhaId;
        document.getElementById('cvp_abha_number_display').textContent  = fmt;
        document.getElementById('cvp_abha_name_display').textContent    = name;
        document.getElementById('cvp_abha_address_display').textContent = addr ? addr + '@abdm' : '';
        var photoEl = document.getElementById('cvp_profile_photo');
        var iconEl  = document.getElementById('cvp_profile_icon');
        if (photo && photo.length > 20) {
            photoEl.src = photo.startsWith('data:') ? photo : 'data:image/jpeg;base64,' + photo;
            photoEl.classList.remove('d-none');
            iconEl.style.display = 'none';
        } else {
            photoEl.classList.add('d-none');
            iconEl.style.display = '';
        }
        setBtn('cvp_btn_link_patient', false, '<i class="bi bi-link-45deg me-1"></i>Link to Patient Record');
        clearMsg('cvp_step4_msg');
        $('#cvp-step1,#cvp-step2,#cvp-step3,#cvp-step3-otp').hide();
        $('#cvp-step4').show();
        setStep(4);
    }

    /* ── save ABHA to patient record ─────────────────────────────── */
    function saveAbha(abhaId, onDone) {
        var digits = abhaId.replace(/-/g, '');
        post('<?= base_url('billing/patient/update_abha') ?>', { p_id: _pid, abha_id: digits }, function (r) {
            updateCsrf(r);
            if (typeof onDone === 'function') onDone(digits);
        });
    }

    /* ═══════════════════════════════════════════════════════════════
       CREATE ABHA  — STEP 1
    ═══════════════════════════════════════════════════════════════ */
    setStep(1);

    document.getElementById('cvp_btn_send_otp').addEventListener('click', function () {
        clearMsg('cvp_step1_msg');
        var aadhaar = ($('#cvp_aadhaar_input').val() || '').replace(/\D/g, '');
        if (!/^\d{12}$/.test(aadhaar)) {
            showMsg('cvp_step1_msg', 'danger', 'Enter a valid 12-digit Aadhaar number.'); return;
        }
        if (!document.getElementById('cvp_consent').checked) {
            showMsg('cvp_step1_msg', 'warning', 'Please confirm patient consent before proceeding.'); return;
        }
        setBtn('cvp_btn_send_otp', true, '<span class="spinner-border spinner-border-sm me-1"></span>Sending…');
        post('<?= base_url('billing/patient/abha_aadhaar_generate_otp') ?>', { aadhaar: aadhaar }, function (res) {
            setBtn('cvp_btn_send_otp', false, '<i class="bi bi-send me-1"></i>Send OTP');
            if (res.ok == 1) {
                var d = res.data || res;
                _txn = d.txnId || d.txn_id || res.txnId || '';
                var modeTag = res.mode === 'test' ? ' <span class="badge bg-warning text-dark ms-1">SANDBOX</span>' : '';
                document.getElementById('cvp_otp_sent_msg').innerHTML =
                    '<i class="bi bi-check-circle me-1"></i>OTP sent to Aadhaar-linked mobile.' + modeTag +
                    (_txn ? '<br><small class="text-muted">TxnID: <code>' + safe(_txn) + '</code></small>' : '');
                $('#cvp-step1').hide(); $('#cvp-step2').show();
                setStep(2);
                setTimeout(function () { document.getElementById('cvp_otp_input').focus(); }, 80);
            } else {
                showMsg('cvp_step1_msg', 'danger', '<i class="bi bi-x-circle me-1"></i>' +
                    safe(res.error_text || res.message || res.error || 'Failed to send OTP.'));
            }
        });
    });

    /* ── Step 2 ──────────────────────────────────────────────────── */
    document.getElementById('cvp_btn_step2_back').addEventListener('click', function () {
        $('#cvp-step2').hide(); $('#cvp-step1').show();
        clearMsg('cvp_step2_msg'); setStep(1);
    });

    document.getElementById('cvp_btn_resend').addEventListener('click', function () {
        var aadhaar = ($('#cvp_aadhaar_input').val() || '').replace(/\D/g, '');
        if (!/^\d{12}$/.test(aadhaar)) { showMsg('cvp_step2_msg', 'warning', 'Go back and re-enter Aadhaar.'); return; }
        setBtn('cvp_btn_resend', true, 'Sending…');
        post('<?= base_url('billing/patient/abha_aadhaar_generate_otp') ?>', { aadhaar: aadhaar }, function (res) {
            setBtn('cvp_btn_resend', false, 'Resend OTP');
            if (res.ok == 1) {
                var d = res.data || res;
                _txn = d.txnId || d.txn_id || _txn;
                showMsg('cvp_step2_msg', 'success', '<i class="bi bi-check-circle me-1"></i>New OTP sent.');
                $('#cvp_otp_input').val('').trigger('focus');
            } else {
                showMsg('cvp_step2_msg', 'danger', 'Resend failed: ' + safe(res.message || res.error || ''));
            }
        });
    });

    document.getElementById('cvp_btn_verify_otp').addEventListener('click', function () {
        clearMsg('cvp_step2_msg');
        var otp    = ($('#cvp_otp_input').val()    || '').trim();
        var mobile = ($('#cvp_mobile_input').val() || '').trim();
        if (!/^\d{6}$/.test(otp))    { showMsg('cvp_step2_msg', 'danger', 'Enter the 6-digit OTP.'); return; }
        if (!/^\d{10}$/.test(mobile)) { showMsg('cvp_step2_msg', 'danger', 'Enter a valid 10-digit mobile.'); return; }
        if (!_txn) { showMsg('cvp_step2_msg', 'danger', 'Transaction ID missing — please restart.'); return; }

        setBtn('cvp_btn_verify_otp', true, '<span class="spinner-border spinner-border-sm me-1"></span>Verifying…');
        post('<?= base_url('billing/patient/abha_aadhaar_verify_otp') ?>',
             { txnId: _txn, otp: otp, mobile: mobile }, function (res) {
            setBtn('cvp_btn_verify_otp', false, '<i class="bi bi-check-circle me-1"></i>Verify OTP');
            if (res.ok != 1) {
                showMsg('cvp_step2_msg', 'danger', '<i class="bi bi-x-circle me-1"></i>' +
                    safe(res.error_text || res.message || res.error || 'Verification failed.'));
                return;
            }
            var abhaData = extractAbha(res);
            if (abhaData.abhaId) {
                showStep4(abhaData.abhaId, abhaData.name, abhaData.addr, abhaData.photo);
            } else {
                // No ABHA profile — need mobile confirmation
                var mob = mobile || '';
                if (mob) document.getElementById('cvp_mob_confirm_input').value = mob;
                var d = res.data || res;
                _mobTxn = d.txnId || d.txn_id || _txn;
                $('#cvp-step2').hide(); $('#cvp-step3').show();
                setStep(3);
            }
        });
    });

    document.getElementById('cvp_otp_input').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') document.getElementById('cvp_btn_verify_otp').click();
    });

    /* ── Step 3: Mobile Confirm ──────────────────────────────────── */
    document.getElementById('cvp_btn_mob_send_otp').addEventListener('click', function () {
        clearMsg('cvp_step3_send_msg');
        var mobile = ($('#cvp_mob_confirm_input').val() || '').trim();
        if (!/^\d{10}$/.test(mobile)) { showMsg('cvp_step3_send_msg', 'danger', 'Enter a valid 10-digit mobile.'); return; }

        setBtn('cvp_btn_mob_send_otp', true, '<span class="spinner-border spinner-border-sm me-1"></span>Sending…');
        post('<?= base_url('billing/patient/abha_mobile_generate_otp') ?>', { mobile: mobile }, function (res) {
            setBtn('cvp_btn_mob_send_otp', false, '<i class="bi bi-send me-1"></i>Send Mobile OTP');
            if (res.ok == 1) {
                var d = res.data || res;
                _mobTxn = d.txnId || d.txn_id || res.txnId || '';
                $('#cvp-step3-otp').show();
                setTimeout(function () { document.getElementById('cvp_mob_otp_input').focus(); }, 80);
            } else {
                showMsg('cvp_step3_send_msg', 'danger', '<i class="bi bi-x-circle me-1"></i>' +
                    safe(res.error_text || res.message || res.error || 'Failed to send OTP.'));
            }
        });
    });

    document.getElementById('cvp_btn_step3_back').addEventListener('click', function () {
        $('#cvp-step3,#cvp-step3-otp').hide();
        $('#cvp-step2').show();
        clearMsg('cvp_step3_send_msg'); clearMsg('cvp_step3_otp_msg');
        setStep(2);
    });

    document.getElementById('cvp_btn_mob_verify_otp').addEventListener('click', function () {
        clearMsg('cvp_step3_otp_msg');
        var otp = ($('#cvp_mob_otp_input').val() || '').trim();
        if (!/^\d{6}$/.test(otp)) { showMsg('cvp_step3_otp_msg', 'danger', 'Enter the 6-digit OTP.'); return; }
        if (!_mobTxn) { showMsg('cvp_step3_otp_msg', 'danger', 'Transaction ID missing — please restart.'); return; }

        setBtn('cvp_btn_mob_verify_otp', true, '<span class="spinner-border spinner-border-sm me-1"></span>Verifying…');
        post('<?= base_url('billing/patient/abha_mobile_verify_otp') ?>', { txnId: _mobTxn, otp: otp }, function (res) {
            setBtn('cvp_btn_mob_verify_otp', false, '<i class="bi bi-check-circle me-1"></i>Verify Mobile OTP');
            if (res.ok != 1) {
                showMsg('cvp_step3_otp_msg', 'danger', '<i class="bi bi-x-circle me-1"></i>' +
                    safe(res.error_text || res.message || res.error || 'Verification failed.'));
                return;
            }
            var abhaData = extractAbha(res);
            if (abhaData.abhaId) {
                showStep4(abhaData.abhaId, abhaData.name, abhaData.addr, abhaData.photo);
            } else {
                showMsg('cvp_step3_otp_msg', 'warning',
                    'Mobile verified but no ABHA account found. Please contact ABDM support.');
            }
        });
    });

    document.getElementById('cvp_mob_otp_input').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') document.getElementById('cvp_btn_mob_verify_otp').click();
    });

    /* ── Step 4: Link to patient ─────────────────────────────────── */
    document.getElementById('cvp_btn_link_patient').addEventListener('click', function () {
        if (!_foundAbha) return;
        setBtn('cvp_btn_link_patient', true, '<span class="spinner-border spinner-border-sm me-1"></span>Saving…');
        saveAbha(_foundAbha, function (digits) {
            showMsg('cvp_step4_msg', 'success',
                '<i class="bi bi-check-circle me-1"></i>ABHA <strong>' + safe(digits) + '</strong> linked to patient.');
            setBtn('cvp_btn_link_patient', true, 'Linked ✓');
            if (typeof window.onAbhaLinked === 'function') window.onAbhaLinked(_pid, digits);
        });
    });

    document.getElementById('cvp_btn_reset').addEventListener('click', function () {
        _txn = ''; _mobTxn = ''; _foundAbha = ''; _foundName = ''; _foundAddr = ''; _foundPhoto = '';
        $('#cvp-step2,#cvp-step3,#cvp-step3-otp,#cvp-step4').hide();
        $('#cvp-step1').show();
        ['cvp_step1_msg','cvp_step2_msg','cvp_step3_send_msg','cvp_step3_otp_msg','cvp_step4_msg'].forEach(clearMsg);
        $('#cvp_otp_input,#cvp_mob_otp_input').val('');
        document.getElementById('cvp_consent').checked = false;
        setStep(1);
    });

    /* ═══════════════════════════════════════════════════════════════
       VERIFY TAB  — Aadhaar OTP
    ═══════════════════════════════════════════════════════════════ */
    document.getElementById('cvpv_btn_a_send_otp').addEventListener('click', function () {
        clearMsg('cvpv_a_step1_msg');
        var aadhaar = ($('#cvpv_aadhaar_input').val() || '').replace(/\D/g, '');
        if (!/^\d{12}$/.test(aadhaar)) { showMsg('cvpv_a_step1_msg', 'danger', 'Enter a valid 12-digit Aadhaar.'); return; }
        setBtn('cvpv_btn_a_send_otp', true, '<span class="spinner-border spinner-border-sm me-1"></span>Sending…');
        post('<?= base_url('billing/patient/abha_aadhaar_generate_otp') ?>', { aadhaar: aadhaar }, function (res) {
            setBtn('cvpv_btn_a_send_otp', false, '<i class="bi bi-send me-1"></i>Send OTP');
            if (res.ok == 1) {
                var d = res.data || res;
                _vAadhTxn = d.txnId || d.txn_id || res.txnId || '';
                document.getElementById('cvpv_a_otp_sent_msg').innerHTML =
                    '<i class="bi bi-check-circle me-1"></i>OTP sent to Aadhaar-linked mobile.' +
                    (_vAadhTxn ? ' <small class="text-muted">TxnID: <code>' + safe(_vAadhTxn) + '</code></small>' : '');
                $('#cvpv-a-step1').hide(); $('#cvpv-a-step2').show();
                setTimeout(function () { document.getElementById('cvpv_a_otp').focus(); }, 80);
            } else {
                showMsg('cvpv_a_step1_msg', 'danger', '<i class="bi bi-x-circle me-1"></i>' +
                    safe(res.error_text || res.message || res.error || 'Failed to send OTP.'));
            }
        });
    });

    document.getElementById('cvpv_btn_a_back').addEventListener('click', function () {
        $('#cvpv-a-step2').hide(); $('#cvpv-a-step1').show(); clearMsg('cvpv_a_step2_msg');
    });

    document.getElementById('cvpv_btn_a_verify').addEventListener('click', function () {
        clearMsg('cvpv_a_step2_msg');
        var otp    = ($('#cvpv_a_otp').val()    || '').trim();
        var mobile = ($('#cvpv_a_mobile').val() || '').trim();
        if (!/^\d{6}$/.test(otp))    { showMsg('cvpv_a_step2_msg', 'danger', 'Enter the 6-digit OTP.'); return; }
        if (!/^\d{10}$/.test(mobile)) { showMsg('cvpv_a_step2_msg', 'danger', 'Enter a valid 10-digit mobile.'); return; }
        if (!_vAadhTxn) { showMsg('cvpv_a_step2_msg', 'danger', 'Transaction ID missing.'); return; }

        setBtn('cvpv_btn_a_verify', true, '<span class="spinner-border spinner-border-sm me-1"></span>Verifying…');
        post('<?= base_url('billing/patient/abha_aadhaar_verify_otp') ?>',
             { txnId: _vAadhTxn, otp: otp, mobile: mobile }, function (res) {
            setBtn('cvpv_btn_a_verify', false, '<i class="bi bi-check-circle me-1"></i>Verify &amp; Link ABHA');
            if (res.ok != 1) {
                showMsg('cvpv_a_step2_msg', 'danger', '<i class="bi bi-x-circle me-1"></i>' +
                    safe(res.error_text || res.message || res.error || 'Verification failed.'));
                return;
            }
            var abhaData = extractAbha(res);
            if (!abhaData.abhaId) {
                showMsg('cvpv_a_step2_msg', 'warning', 'OTP verified but no ABHA found for this Aadhaar.'); return;
            }
            var digits = abhaData.abhaId.replace(/-/g, '');
            saveAbha(digits, function () {
                showMsg('cvpv_a_step2_msg', 'success',
                    '<i class="bi bi-check-circle me-1"></i>ABHA <strong>' + safe(digits) + '</strong> linked!');
                setBtn('cvpv_btn_a_verify', true, 'Linked ✓');
                if (typeof window.onAbhaLinked === 'function') window.onAbhaLinked(_pid, digits);
            });
        });
    });

    document.getElementById('cvpv_a_otp').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') document.getElementById('cvpv_btn_a_verify').click();
    });

    /* ── Verify Tab — Mobile OTP ─────────────────────────────────── */
    document.getElementById('cvpv_btn_m_send_otp').addEventListener('click', function () {
        clearMsg('cvpv_m_step1_msg');
        var mobile = ($('#cvpv_mobile_input').val() || '').trim();
        if (!/^\d{10}$/.test(mobile)) { showMsg('cvpv_m_step1_msg', 'danger', 'Enter a valid 10-digit mobile.'); return; }
        setBtn('cvpv_btn_m_send_otp', true, '<span class="spinner-border spinner-border-sm me-1"></span>Sending…');
        post('<?= base_url('billing/patient/abha_mobile_generate_otp') ?>', { mobile: mobile }, function (res) {
            setBtn('cvpv_btn_m_send_otp', false, '<i class="bi bi-send me-1"></i>Send OTP');
            if (res.ok == 1) {
                var d = res.data || res;
                _vMobTxn = d.txnId || d.txn_id || res.txnId || '';
                document.getElementById('cvpv_m_sent_to').textContent =
                    mobile.replace(/(\d{2})\d{6}(\d{2})/, '$1xxxxxx$2');
                $('#cvpv-m-step1').hide(); $('#cvpv-m-step2').show();
                setTimeout(function () { document.getElementById('cvpv_m_otp').focus(); }, 80);
            } else {
                showMsg('cvpv_m_step1_msg', 'danger', '<i class="bi bi-x-circle me-1"></i>' +
                    safe(res.error_text || res.message || res.error || 'Failed to send OTP.'));
            }
        });
    });

    document.getElementById('cvpv_btn_m_back').addEventListener('click', function () {
        $('#cvpv-m-step2').hide(); $('#cvpv-m-step1').show(); clearMsg('cvpv_m_step2_msg');
    });

    document.getElementById('cvpv_btn_m_verify').addEventListener('click', function () {
        clearMsg('cvpv_m_step2_msg');
        var otp = ($('#cvpv_m_otp').val() || '').trim();
        if (!/^\d{6}$/.test(otp)) { showMsg('cvpv_m_step2_msg', 'danger', 'Enter the 6-digit OTP.'); return; }
        if (!_vMobTxn) { showMsg('cvpv_m_step2_msg', 'danger', 'Transaction ID missing.'); return; }

        setBtn('cvpv_btn_m_verify', true, '<span class="spinner-border spinner-border-sm me-1"></span>Verifying…');
        post('<?= base_url('billing/patient/abha_mobile_verify_otp') ?>', { txnId: _vMobTxn, otp: otp }, function (res) {
            setBtn('cvpv_btn_m_verify', false, '<i class="bi bi-check-circle me-1"></i>Verify &amp; Link ABHA');
            if (res.ok != 1) {
                showMsg('cvpv_m_step2_msg', 'danger', '<i class="bi bi-x-circle me-1"></i>' +
                    safe(res.error_text || res.message || res.error || 'Verification failed.'));
                return;
            }
            var abhaData = extractAbha(res);
            if (!abhaData.abhaId) {
                showMsg('cvpv_m_step2_msg', 'warning', 'OTP verified but no ABHA accounts found.'); return;
            }
            var digits = abhaData.abhaId.replace(/-/g, '');
            saveAbha(digits, function () {
                showMsg('cvpv_m_step2_msg', 'success',
                    '<i class="bi bi-check-circle me-1"></i>ABHA <strong>' + safe(digits) + '</strong> linked!');
                setBtn('cvpv_btn_m_verify', true, 'Linked ✓');
                if (typeof window.onAbhaLinked === 'function') window.onAbhaLinked(_pid, digits);
            });
        });
    });

    document.getElementById('cvpv_m_otp').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') document.getElementById('cvpv_btn_m_verify').click();
    });

}());
</script>
