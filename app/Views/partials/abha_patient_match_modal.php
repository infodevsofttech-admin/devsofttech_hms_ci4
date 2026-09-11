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
 * Then call: window.AbhaPatientMatchModal.open(profileResp, candidates, function(confirmResp) { ... }, preferredPatientId);
 */
?>
<div class="modal fade" id="abhaMatchModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center" id="abhaMatch_modal_title">
                    <i class="bi bi-people-fill me-2" id="abhaMatch_modal_icon"></i>
                    <span id="abhaMatch_modal_title_text">Matching Patients in HMS</span>
                    <span id="abhaMatch_modal_title_badge" class="badge bg-danger ms-2 d-none">Already Exists</span>
                </h5>
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
                        <div id="abhaMatch_update_preview" class="alert alert-info py-2 small d-none"></div>
                        <div id="abhaMatch_empty" class="alert alert-warning py-2 small d-none">
                            No matching patient found in HMS by name, age or gender. You can create a new patient record.
                        </div>
                        <div class="small text-muted mt-2"><i class="bi bi-info-circle me-1"></i>The verified ABHA identity is linked in both existing-patient actions. Keep Existing preserves HMS demographics; Update Details applies the latest ABHA profile.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-secondary" id="abhaMatch_create_new_btn">
                        <i class="bi bi-person-plus me-1"></i>Create New Patient
                    </button>
                    <span id="abhaMatch_duplicate_warning" class="small text-danger d-none">
                        <i class="bi bi-shield-lock-fill me-1"></i>ABHA already registered. Cannot create duplicate.
                    </span>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <a href="#" target="_blank" class="btn btn-outline-danger d-none" id="abhaMatch_view_profile_btn">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Open Patient Profile
                    </a>
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
    function profileValue(value) { return String(value == null ? '' : value).trim().toUpperCase(); }
    function normalizedGender(value) {
        var gender = profileValue(value);
        if (gender === '1' || gender === 'M' || gender === 'MALE') return 'M';
        if (gender === '2' || gender === 'F' || gender === 'FEMALE') return 'F';
        if (gender === '3' || gender === 'O' || gender === 'OTHER') return 'O';
        return gender;
    }
    function renderUpdatePreview(c) {
        var fields = [];
        var p = _profile || {};
        if (profileValue(p.name) !== profileValue(c.name)) fields.push('Name');
        if (normalizedGender(p.gender) !== normalizedGender(c.gender_label)) fields.push('Gender');
        if (String(p.dob || '').slice(0, 4) !== String(c.dob || '').slice(0, 4)) fields.push('Birth year');
        if (String(p.mobile || '').replace(/\D/g, '') !== String(c.mobile || '').replace(/\D/g, '')) fields.push('Mobile');
        var abhaAddress = [p.address, p.district, p.state, p.zip].filter(Boolean).join(', ');
        if (profileValue(abhaAddress) !== profileValue(c.address)) fields.push('Address');
        $('#abhaMatch_update_preview').toggleClass('d-none', !fields.length).html(
            fields.length ? '<strong>Update Details will change:</strong> ' + fields.map(esc).join(', ') : '<strong>No HMS demographic changes detected.</strong>'
        );
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
            var exactAbhaNote = m.abha
                ? '<div class="small text-danger fw-semibold mt-1"><i class="bi bi-shield-lock-fill me-1"></i>Already registered with this exact ABHA ID in HMS (' + esc(c.p_code || '') + ')</div>'
                : '';
            var cardCls = m.abha ? 'border-danger bg-danger-subtle' : (m.aadhaar ? 'border-warning bg-warning-subtle' : '');
            var html = ''
                + '<div class="card mb-2 abhaMatch-candidate ' + cardCls + '" data-id="' + c.id + '" style="cursor:pointer">'
                + '  <div class="card-body py-2">'
                + '    <div class="d-flex justify-content-between align-items-start">'
                + '      <div>'
                + (c.photo_url ? '        <img src="' + esc(c.photo_url) + '" alt="Patient photo" class="rounded-circle me-2" style="width:38px;height:38px;object-fit:cover;vertical-align:middle">' : '')
                + '        <div class="fw-semibold d-inline-block">' + esc(c.name || '—') + ' <small class="text-muted">(' + esc(c.p_code || '') + ')</small></div>'
                + '        <div class="mt-1">'
                +            (m.abha ? '<span class="badge bg-danger me-1 mb-1"><i class="bi bi-shield-lock-fill me-1"></i>Exact ABHA Match</span>' : '')
                +            (m.aadhaar ? '<span class="badge bg-danger me-1 mb-1"><i class="bi bi-fingerprint me-1"></i>Exact Aadhaar Match</span>' : '')
                +            badge(m.name, 'Name')
                +            badge(m.age, 'Age ' + (c.age != null ? c.age : '?'))
                +            badge(m.gender, genderText(c.gender))
                +            badge(m.mobile, 'Mobile ' + maskMobile(c.mobile))
                +            (!m.aadhaar ? badge(m.aadhaar, 'Aadhaar') : '')
                + '        </div>'
                + '        <div class="small text-muted mt-1">DOB: ' + esc(c.dob || '—') + ' | Mobile: ' + esc(maskMobile(c.mobile) || '—') + '</div>'
                + '        <div class="small text-muted mt-1">Address: ' + esc(c.address || '—') + '</div>'
                +          conflictNote
                +          exactAbhaNote
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
        var selected = (_profile && window._abhaMatchCandidates || []).find(function (candidate) { return Number(candidate.id) === Number(id); });
        if (selected) renderUpdatePreview(selected);
    }

    function submitConfirm(action, patientId, updateMode, $btn) {
        $('#abhaMatch_alert').empty();
        var origHtml = $btn.html();

        if (action === 'new') {
            var exactConflict = (_profile && _profile.already_registered) || (window._abhaMatchCandidates || []).some(function(c) {
                return c.match && (c.match.abha || c.match.aadhaar);
            });
            if (exactConflict) {
                $('#abhaMatch_alert').html(alertHtml('danger', 'Cannot create duplicate patient: An ABHA ID or Aadhaar Number can only belong to one patient in HMS. Please select and update the existing patient record.'));
                return;
            }
        }

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
            card_base64: _profile.card_base64 || '',
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

        $(document).off('click.abhaPatientMatch', '.abhaMatch-candidate')
            .on('click.abhaPatientMatch', '.abhaMatch-candidate', function () {
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
        open: function (profile, candidates, onResolved, preferredPatientId) {
            _profile = profile || {};
            window._abhaMatchCandidates = candidates || [];
            _selectedId = 0;
            _onResolved = onResolved;
            $('#abhaMatch_alert').empty();
            $('#abhaMatch_keep_existing_btn,#abhaMatch_update_existing_btn').prop('disabled', true);
            $('#abhaMatch_view_profile_btn').addClass('d-none');
            $('#abhaMatch_duplicate_warning').addClass('d-none');

            renderProfile(_profile);
            renderCandidates(candidates || []);
            $('#abhaMatch_update_preview').addClass('d-none').empty();

            // Detect exact ABHA or Aadhaar conflict
            var exactAbhaCandidate = (candidates || []).find(function (c) {
                return (c.match && c.match.abha) || (_profile.conflict_patient && Number(c.id) === Number(_profile.conflict_patient.id));
            });
            var exactAadhaarCandidate = (candidates || []).find(function (c) {
                return c.match && c.match.aadhaar;
            });
            var isExactMatch = !!(exactAbhaCandidate || exactAadhaarCandidate || _profile.already_registered);
            var conflictPatient = exactAbhaCandidate || exactAadhaarCandidate || _profile.conflict_patient || null;

            if (isExactMatch && conflictPatient) {
                // Case 1: Exact ABHA / Aadhaar Match already registered in HMS
                $('#abhaMatch_modal_icon').attr('class', 'bi bi-shield-exclamation text-danger me-2');
                $('#abhaMatch_modal_title_text').text('Patient Already Registered with this ABHA ID');
                $('#abhaMatch_modal_title_badge').removeClass('d-none').text('Already Exists');

                var profileLink = '<?= base_url('billing/patient/person_record/') ?>' + (conflictPatient.id || '');
                $('#abhaMatch_alert').html(
                    '<div class="alert alert-danger py-2 mb-3">' +
                    '  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">' +
                    '    <div>' +
                    '      <i class="bi bi-shield-lock-fill me-2 fs-5 align-middle"></i>' +
                    '      <strong>Unique ABHA ID Policy:</strong> This ABHA ID is already linked to patient ' +
                    '      <strong>' + esc(conflictPatient.p_code || '') + ' (' + esc(conflictPatient.name || conflictPatient.p_fname || '') + ')</strong>. ' +
                    '      Each patient in HMS must have a unique ABHA ID. Creating a duplicate patient is not allowed.' +
                    '    </div>' +
                    (conflictPatient.id ? '    <a href="' + profileLink + '" target="_blank" class="btn btn-sm btn-outline-danger text-nowrap"><i class="bi bi-box-arrow-up-right me-1"></i>View Profile</a>' : '') +
                    '  </div>' +
                    '</div>'
                );

                // Disable "Create New Patient" because of duplicate conflict
                $('#abhaMatch_create_new_btn').prop('disabled', true).addClass('disabled opacity-50 btn-outline-secondary').removeClass('btn-primary').attr('title', 'Disabled: ABHA ID is already linked to patient ' + (conflictPatient.p_code || ''));
                $('#abhaMatch_duplicate_warning').removeClass('d-none');
                if (conflictPatient.id) {
                    $('#abhaMatch_view_profile_btn').attr('href', profileLink).removeClass('d-none');
                }

                // Auto-select the matching existing patient
                if (conflictPatient.id) {
                    selectCandidate(Number(conflictPatient.id));
                }
            } else if ((candidates || []).length > 0) {
                // Case 2: No exact conflict, but potential demographic matches found
                $('#abhaMatch_modal_icon').attr('class', 'bi bi-people-fill text-primary me-2');
                $('#abhaMatch_modal_title_text').text('Matching Patients in HMS');
                $('#abhaMatch_modal_title_badge').addClass('d-none');

                $('#abhaMatch_create_new_btn').prop('disabled', false).removeClass('disabled opacity-50 btn-primary').addClass('btn-outline-secondary').removeAttr('title');

                if (Number(preferredPatientId || 0) > 0
                    && (candidates || []).some(function (candidate) { return Number(candidate.id) === Number(preferredPatientId); })) {
                    selectCandidate(Number(preferredPatientId));
                }
            } else {
                // Case 3: 0 matches found in HMS (new person - e.g. Keshav Singh)
                $('#abhaMatch_modal_icon').attr('class', 'bi bi-person-plus-fill text-success me-2');
                $('#abhaMatch_modal_title_text').text('Register New Patient from ABHA');
                $('#abhaMatch_modal_title_badge').addClass('d-none');

                $('#abhaMatch_empty').text('No matching patient found in HMS by name, age or gender. You can register a new patient record with this verified ABHA profile.');

                // Highlight "Create New Patient" as the primary action
                $('#abhaMatch_create_new_btn').prop('disabled', false).removeClass('disabled opacity-50 btn-outline-secondary').addClass('btn-primary').removeAttr('title');
            }

            if (_bsModal) { _bsModal.show(); } else { $modalEl.modal('show'); }
        }
    };
})();
</script>
