<?php
/**
 * Partial: ABHA "Matching Patients" confirmation modal.
 *
 * Shown after a successful ABHA OTP verification (Aadhaar or Mobile) when the
 * server could not safely auto-resolve a local patient (see
 * Abha::tryAutoLinkByDirectMatch / Abha::findMatchingCandidates).
 *
 * Left column  — the verified ABHA profile (source of truth from the gateway).
 * Right column — candidate patient_master rows matched by Name/Age/Gender
 *                (and Mobile/Aadhaar when available), with matching fields
 *                highlighted so the operator can quickly decide.
 *
 * The operator must explicitly choose "Update Existing" (pick one candidate)
 * or "Create New Patient" — HMS never silently auto-creates a patient once
 * this modal is shown.
 *
 * Include once per page:  <?= view('partials/abha_patient_match_modal') ?>
 * Then call: window.AbhaPatientMatchModal.open(profileResp, candidates, function(confirmResp) { ... });
 */
?>
<div class="modal fade" id="abhaMatchModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-people-fill me-2"></i>Patient Already Exists</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="abhaMatch_alert"></div>
                <div class="row g-3">
                    <!-- Left: ABHA profile -->
                    <div class="col-md-5">
                        <div class="card h-100 border-primary">
                            <div class="card-header bg-primary text-white py-2">
                                <i class="bi bi-patch-check-fill me-1"></i>ABHA Verified Profile
                            </div>
                            <div class="card-body text-center">
                                <img id="abhaMatch_photo" src="" alt="Photo" class="rounded-circle d-none mb-2"
                                     style="width:72px;height:72px;object-fit:cover;border:3px solid #0d6efd">
                                <i class="bi bi-person-circle text-primary d-block mb-2" id="abhaMatch_photo_ph" style="font-size:3.5rem"></i>
                                <h5 class="mb-1" id="abhaMatch_name">—</h5>
                                <div class="small text-muted mb-2" id="abhaMatch_abha">—</div>
                                <table class="table table-sm table-borderless text-start mb-0">
                                    <tbody>
                                        <tr><th class="text-muted small" style="width:40%">Gender</th><td id="abhaMatch_gender">—</td></tr>
                                        <tr><th class="text-muted small">DOB / Age</th><td id="abhaMatch_dob">—</td></tr>
                                        <tr><th class="text-muted small">Mobile</th><td id="abhaMatch_mobile">—</td></tr>
                                        <tr><th class="text-muted small">Address</th><td id="abhaMatch_address">—</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Right: candidate list -->
                    <div class="col-md-7">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-semibold"><i class="bi bi-search me-1"></i>Matching Patients in HMS</div>
                            <span class="badge bg-secondary" id="abhaMatch_count">0 found</span>
                        </div>
                        <div id="abhaMatch_candidates" style="max-height:360px;overflow-y:auto"></div>
                        <div id="abhaMatch_empty" class="alert alert-warning py-2 small d-none">
                            No matching patient found in HMS by name, age or gender. You can create a new patient record.
                        </div>
                        <div class="small text-muted mt-2"><i class="bi bi-info-circle me-1"></i>The verified ABHA identity is linked in both existing-patient actions. Keep Existing preserves HMS demographics; Update Details applies the latest ABHA profile.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-secondary" id="abhaMatch_create_new_btn">
                    <i class="bi bi-person-plus me-1"></i>Create New Patient
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary" id="abhaMatch_keep_existing_btn" disabled><i class="bi bi-person-check me-1"></i>Keep Existing</button>
                    <button type="button" class="btn btn-primary" id="abhaMatch_update_existing_btn" disabled><i class="bi bi-arrow-repeat me-1"></i>Update Details</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.AbhaPatientMatchModal = (function () {
    'use strict';

    var _profile = null;
    var _selectedId = 0;
    var _onResolved = null;
    var $modalEl;
    var _bsModal;

    function csrfPair() {
        var inp = document.querySelector('input[name="<?= csrf_token() ?>"]');
        return { name: '<?= csrf_token() ?>', value: inp ? inp.value : '<?= csrf_hash() ?>' };
    }
    function updateCsrf(r) {
        if (!r || !r.csrfName || !r.csrfHash) { return; }
        var inp = document.querySelector('input[name="' + r.csrfName + '"]');
        if (inp) { inp.value = r.csrfHash; }
    }
    function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
    function genderText(g) {
        g = String(g || '').toUpperCase();
        if (g === 'M' || g === '1' || g === 'MALE') { return 'Male'; }
        if (g === 'F' || g === '2' || g === 'FEMALE') { return 'Female'; }
        if (g === 'O' || g === '3' || g === 'OTHER') { return 'Other'; }
        return g || '—';
    }
    function maskMobile(m) {
        m = String(m || '');
        return m.length === 10 ? m.replace(/(\d{6})(\d{4})/, '******$2') : m;
    }
    function badge(matched, label) {
        var cls = matched ? 'bg-success' : 'bg-light text-dark border';
        return '<span class="badge ' + cls + ' me-1 mb-1">' + (matched ? '<i class="bi bi-check-lg"></i> ' : '') + label + '</span>';
    }
    function alertHtml(type, msg) {
        return '<div class="alert alert-' + type + ' py-2">' + msg + '</div>';
    }

    function renderProfile(p) {
        var abhaNum = p.abha_number || '';
        var disp = abhaNum.replace(/(\d{2})(\d{4})(\d{4})(\d{4})/, '$1-$2-$3-$4');
        $('#abhaMatch_name').text(p.name || '—');
        $('#abhaMatch_abha').text(disp || '—');
        $('#abhaMatch_gender').text(genderText(p.gender));
        $('#abhaMatch_dob').text(p.dob || '—');
        $('#abhaMatch_mobile').text(maskMobile(p.mobile));
        $('#abhaMatch_address').text([p.address, p.district, p.state].filter(Boolean).join(', ') || '—');
        if (p.photo) {
            var src = String(p.photo).indexOf('data:') === 0 ? p.photo : 'data:image/jpeg;base64,' + p.photo;
            $('#abhaMatch_photo').attr('src', src).removeClass('d-none');
            $('#abhaMatch_photo_ph').addClass('d-none');
        } else {
            $('#abhaMatch_photo').addClass('d-none');
            $('#abhaMatch_photo_ph').removeClass('d-none');
        }
    }

    function renderCandidates(list) {
        $('#abhaMatch_count').text(list.length + ' found');
        var $wrap = $('#abhaMatch_candidates').empty();
        if (!list.length) {
            $('#abhaMatch_empty').removeClass('d-none');
            return;
        }
        $('#abhaMatch_empty').addClass('d-none');

        list.forEach(function (c) {
            var m = c.match || {};
            var conflictNote = c.abha_conflict
                ? '<div class="small text-danger mt-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>Already has a different ABHA linked (' + esc(c.abha) + ')</div>'
                : '';
            var html = ''
                + '<div class="card mb-2 abhaMatch-candidate" data-id="' + c.id + '" style="cursor:pointer">'
                + '  <div class="card-body py-2">'
                + '    <div class="d-flex justify-content-between align-items-start">'
                + '      <div>'
                + '        <div class="fw-semibold">' + esc(c.name || '—') + ' <small class="text-muted">(' + esc(c.p_code || '') + ')</small></div>'
                + '        <div class="mt-1">'
                +            badge(m.name, 'Name')
                +            badge(m.age, 'Age ' + (c.age != null ? c.age : '?'))
                +            badge(m.gender, genderText(c.gender))
                +            badge(m.mobile, 'Mobile ' + maskMobile(c.mobile))
                +            badge(m.aadhaar, 'Aadhaar')
                + '        </div>'
                + '        <div class="small text-muted mt-1">DOB: ' + esc(c.dob || '—') + ' | Mobile: ' + esc(maskMobile(c.mobile) || '—') + '</div>'
                + '        <div class="small text-muted mt-1">Address: ' + esc(c.address || '—') + '</div>'
                +          conflictNote
                + '      </div>'
                + '      <div class="form-check">'
                + '        <input class="form-check-input abhaMatch-radio" type="radio" name="abhaMatchPick" value="' + c.id + '"' + (c.abha_conflict ? ' disabled' : '') + '>'
                + '      </div>'
                + '    </div>'
                + '  </div>'
                + '</div>';
            $wrap.append(html);
        });
    }

    function selectCandidate(id) {
        _selectedId = id;
        $('.abhaMatch-candidate').removeClass('border-success bg-success-subtle');
        $('.abhaMatch-candidate[data-id="' + id + '"]').addClass('border-success bg-success-subtle');
        $('.abhaMatch-radio[value="' + id + '"]').prop('checked', true);
        $('#abhaMatch_keep_existing_btn,#abhaMatch_update_existing_btn').prop('disabled', !id);
    }

    function submitConfirm(action, patientId, updateMode, $btn) {
        $('#abhaMatch_alert').empty();
        var origHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving…');
        $('#abhaMatch_create_new_btn,#abhaMatch_keep_existing_btn,#abhaMatch_update_existing_btn').prop('disabled', true);

        var c = csrfPair();
        var payload = {
            action: action,
            update_mode: updateMode || 'update',
            patient_id: patientId || 0,
            abha_number: _profile.abha_number || '',
            name: _profile.name || '',
            mobile: _profile.mobile || '',
            gender: _profile.gender || '',
            dob: _profile.dob || '',
            abha_address: _profile.abha_address || '',
            photo: _profile.photo || '',
            verified_status: _profile.verified_status || '',
            verification_type: _profile.verification_type || '',
            kyc_verified: _profile.kyc_verified,
            mobile_verified: _profile.mobile_verified,
            address: _profile.address || '',
            district: _profile.district || '',
            state: _profile.state || '',
            zip: _profile.zip || '',
            email: _profile.email || ''
        };
        payload[c.name] = c.value;

        $.post('<?= base_url('abha/create/confirm_patient') ?>', payload, function (resp) {
            updateCsrf(resp);
            $btn.prop('disabled', false).html(origHtml);
            $('#abhaMatch_create_new_btn').prop('disabled', false);
            $('#abhaMatch_keep_existing_btn,#abhaMatch_update_existing_btn').prop('disabled', !_selectedId);

            if (!resp || resp.ok != 1) {
                $('#abhaMatch_alert').html(alertHtml('danger', esc((resp && resp.error_text) || 'Failed to save patient link.')));
                return;
            }
            if (_bsModal) { _bsModal.hide(); }
            if (typeof _onResolved === 'function') {
                _onResolved(Object.assign({}, _profile, resp));
            }
        }, 'json').fail(function () {
            $btn.prop('disabled', false).html(origHtml);
            $('#abhaMatch_create_new_btn').prop('disabled', false);
            $('#abhaMatch_keep_existing_btn,#abhaMatch_update_existing_btn').prop('disabled', !_selectedId);
            $('#abhaMatch_alert').html(alertHtml('danger', 'Server error while saving. Please try again.'));
        });
    }

    $(function () {
        $modalEl = $('#abhaMatchModal');
        _bsModal = window.bootstrap ? new bootstrap.Modal($modalEl[0]) : null;

        $(document).on('click', '.abhaMatch-candidate', function () {
            if ($(this).find('.abhaMatch-radio').is(':disabled')) { return; }
            selectCandidate(parseInt($(this).data('id'), 10));
        });

        $('#abhaMatch_create_new_btn').on('click', function () {
            submitConfirm('new', 0, 'update', $(this));
        });
        $('#abhaMatch_keep_existing_btn').on('click', function () {
            if (!_selectedId) { return; }
            submitConfirm('existing', _selectedId, 'keep', $(this));
        });
        $('#abhaMatch_update_existing_btn').on('click', function () {
            if (!_selectedId) { return; }
            submitConfirm('existing', _selectedId, 'update', $(this));
        });
    });

    return {
        open: function (profile, candidates, onResolved) {
            _profile = profile || {};
            _selectedId = 0;
            _onResolved = onResolved;
            $('#abhaMatch_alert').empty();
            $('#abhaMatch_keep_existing_btn,#abhaMatch_update_existing_btn').prop('disabled', true);
            renderProfile(_profile);
            renderCandidates(candidates || []);
            if (_bsModal) { _bsModal.show(); } else { $modalEl.modal('show'); }
        }
    };
})();
</script>
