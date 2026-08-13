<?php
/**
 * Partial: "Scan ABHA QR" modal.
 *
 * Accepts the JSON payload encoded in an ABHA card / PHR app QR code, either from
 * a hardware (keyboard-wedge) scanner or pasted manually, and hands the parsed
 * demographics to the caller so the patient-match modal can take over.
 *
 * A QR scan captures demographics only — it is not an OTP authentication.
 *
 * Include once per page:  <?= view('partials/abha_qr_modal') ?>
 * Then call: window.AbhaQrModal.open(function (profile) { ... });
 */
?>
<style>
    .abha-qr-modal .modal-content { border:0; border-radius:12px; overflow:hidden; }
    .abha-qr-modal .modal-header { border-bottom:1px solid #e5eaf1; padding:18px 22px; }
    .abha-qr-modal .modal-body { padding:22px; }
    .abha-qr-target { border:2px dashed #b9c6d8; border-radius:10px; background:#f8fafc; padding:18px; text-align:center; }
    .abha-qr-target.listening { border-color:#356cf4; background:#eef3ff; }
    .abha-qr-input { font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:13px; }
    .abha-qr-list { margin:0; }
    .abha-qr-list>div { display:grid; grid-template-columns:150px minmax(0,1fr); gap:14px; padding:9px 0; border-bottom:1px solid #edf0f4; }
    .abha-qr-list dt { color:#6b7688; font-weight:500; }
    .abha-qr-list dd { margin:0; text-align:right; font-weight:600; overflow-wrap:anywhere; }
    @media (max-width:575.98px){ .abha-qr-list>div{grid-template-columns:110px minmax(0,1fr)} }
</style>

<div class="modal fade abha-qr-modal" id="abhaQrModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-qr-code-scan text-primary me-2"></i>Scan ABHA QR</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="abhaQrAlert"></div>

                <section id="abhaQrStep1">
                    <div class="abha-qr-target listening" id="abhaQrTarget">
                        <i class="bi bi-upc-scan text-primary" style="font-size:2.4rem"></i>
                        <h6 class="mt-2 mb-1">Ready to scan</h6>
                        <div class="text-muted small">Scan the QR on the patient's ABHA card or PHR app. The scanner types into the box below automatically.</div>
                    </div>

                    <label class="form-label fw-semibold mt-3" for="abhaQrInput">Scanned QR content</label>
                    <textarea class="form-control abha-qr-input" id="abhaQrInput" rows="4" placeholder='Scan now, or paste the QR text (e.g. {"hidn":"91-0000-0000-0000", ...})'></textarea>
                    <div class="form-text">A hardware scanner submits automatically. Pasting? Click <strong>Read QR</strong>.</div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-outline-secondary" id="abhaQrClearBtn">Clear</button>
                        <button type="button" class="btn btn-primary" id="abhaQrReadBtn"><i class="bi bi-search me-1"></i>Read QR</button>
                    </div>
                </section>

                <section id="abhaQrStep2" class="d-none">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-primary-subtle text-primary"><i class="bi bi-qr-code me-1"></i>Scanned from ABHA QR</span>
                        <span class="badge bg-warning-subtle text-warning-emphasis">Not OTP verified</span>
                    </div>
                    <h4 class="mb-0" id="abhaQrName">-</h4>
                    <div class="text-muted mb-3" id="abhaQrAddress">-</div>
                    <dl class="abha-qr-list">
                        <div><dt>ABHA Number</dt><dd id="abhaQrNumber">-</dd></div>
                        <div><dt>ABHA Address</dt><dd id="abhaQrAddressValue">-</dd></div>
                        <div><dt>Date of Birth</dt><dd id="abhaQrDob">-</dd></div>
                        <div><dt>Gender</dt><dd id="abhaQrGender">-</dd></div>
                        <div><dt>Mobile</dt><dd id="abhaQrMobile">-</dd></div>
                        <div><dt>Address</dt><dd id="abhaQrFullAddress">-</dd></div>
                    </dl>
                    <div class="d-flex justify-content-between gap-2 mt-4">
                        <button type="button" class="btn btn-outline-secondary" id="abhaQrRescanBtn"><i class="bi bi-arrow-left me-1"></i>Scan Again</button>
                        <button type="button" class="btn btn-success" id="abhaQrContinueBtn"><i class="bi bi-people me-1"></i>Compare with HMS Patients</button>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<script>
window.AbhaQrModal = (function () {
    'use strict';

    var modal;
    var onResolved = null;
    var scannedProfile = null;
    var autoSubmitTimer = null;

    function csrf() {
        var input = document.querySelector('input[name="<?= csrf_token() ?>"]');
        return input ? input.value : '<?= csrf_hash() ?>';
    }
    function escapeHtml(value) { return $('<div>').text(value == null ? '' : String(value)).html(); }
    function apiMessage(response, fallback) {
        return response && (response.error_text || response.message) ? escapeHtml(response.error_text || response.message) : fallback;
    }
    function alertBox(type, message) {
        $('#abhaQrAlert').html(message ? '<div class="alert alert-' + type + ' py-2">' + message + '</div>' : '');
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
        var digits = String(value || '').replace(/\D/g, '');
        return digits.length === 10 ? digits.replace(/(\d{6})(\d{4})/, '******$2') : (value || '-');
    }
    function showStep(step) {
        $('#abhaQrStep1,#abhaQrStep2').addClass('d-none');
        $('#abhaQrStep' + step).removeClass('d-none');
        alertBox('', '');
    }

    function readQr() {
        window.clearTimeout(autoSubmitTimer);
        var payload = $.trim($('#abhaQrInput').val());
        if (payload === '') { alertBox('warning', 'Scan the ABHA QR or paste its content first.'); return; }

        var button = $('#abhaQrReadBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Reading');
        $.post('<?= base_url('abha/register/qr_scan') ?>', { qr_data: payload, '<?= csrf_token() ?>': csrf() }, function (response) {
            button.prop('disabled', false).html('<i class="bi bi-search me-1"></i>Read QR');
            if (!response || response.ok != 1) { alertBox('danger', apiMessage(response, 'Unable to read this ABHA QR code.')); return; }
            scannedProfile = response;
            $('#abhaQrName').text(response.name || '-');
            $('#abhaQrAddress').text(response.abha_address || formatAbha(response.abha_number) || '-');
            $('#abhaQrNumber').text(formatAbha(response.abha_number) || '-');
            $('#abhaQrAddressValue').text(response.abha_address || '-');
            $('#abhaQrDob').text(response.dob || '-');
            $('#abhaQrGender').text(genderText(response.gender));
            $('#abhaQrMobile').text(maskMobile(response.mobile));
            $('#abhaQrFullAddress').text([response.address, response.district, response.state, response.zip].filter(Boolean).join(', ') || '-');
            showStep(2);
        }, 'json').fail(function (xhr) {
            button.prop('disabled', false).html('<i class="bi bi-search me-1"></i>Read QR');
            alertBox('danger', apiMessage(xhr.responseJSON, 'Unable to read this ABHA QR code.'));
        });
    }

    function resetToScan() {
        scannedProfile = null;
        $('#abhaQrInput').val('');
        showStep(1);
        $('#abhaQrInput').trigger('focus');
    }

    $(function () {
        modal = new bootstrap.Modal(document.getElementById('abhaQrModal'));

        // Keyboard-wedge scanners finish with Enter; fall back to a short idle timer.
        $('#abhaQrInput').on('keydown', function (event) {
            if (event.key === 'Enter') { event.preventDefault(); readQr(); }
        }).on('input', function () {
            window.clearTimeout(autoSubmitTimer);
            var value = $.trim($(this).val());
            if (value.length < 20) { return; }
            autoSubmitTimer = window.setTimeout(function () {
                if ($('#abhaQrStep1').hasClass('d-none')) { return; }
                readQr();
            }, 350);
        });

        $('#abhaQrReadBtn').on('click', readQr);
        $('#abhaQrClearBtn').on('click', resetToScan);
        $('#abhaQrRescanBtn').on('click', resetToScan);

        $('#abhaQrContinueBtn').on('click', function () {
            if (!scannedProfile) return;
            var profile = scannedProfile;
            // Already linked: nothing else opens, so keep this window up for the operator to close.
            if (profile.need_confirmation === false && Number(profile.patient_id || 0) > 0) {
                alertBox('success', 'This ABHA is already linked to HMS patient <strong>' + escapeHtml(profile.p_code || '') + '</strong>. The page behind has been updated. Close this window when you are done.');
                if (typeof onResolved === 'function') onResolved(profile);
                return;
            }
            $('#abhaQrModal').one('hidden.bs.modal', function () {
                if (typeof onResolved === 'function') onResolved(profile);
            });
            modal.hide();
        });

        $('#abhaQrModal').on('shown.bs.modal', function () { $('#abhaQrInput').trigger('focus'); });
        $('#abhaQrModal').on('hidden.bs.modal', function () { window.clearTimeout(autoSubmitTimer); });
    });

    return {
        open: function (callback) {
            onResolved = callback;
            resetToScan();
            alertBox('', '');
            modal.show();
        },
        submit: function (decodedText) {
            $('#abhaQrInput').val(decodedText || '');
            readQr();
        }
    };
})();
</script>
