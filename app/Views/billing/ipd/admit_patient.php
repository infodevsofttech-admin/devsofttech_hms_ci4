<?php
$defaultType = $default_type ?? 'emergency';
?>
<div class="container-fluid px-0">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0 mb-3" style="border-radius:12px;">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2" style="border-bottom:1px solid #e2e8f0;">
                    <div>
                        <h4 class="mb-0 fw-bold" style="color:#0f172a;">
                            <i class="bi bi-person-plus-fill text-primary me-2"></i>Patient Admission Desk
                        </h4>
                        <small class="text-muted">Register patient admission for <strong>Emergency / Casualty</strong>, <strong>Day Care (&lt; 24h)</strong>, or <strong>Inpatient (IPD)</strong></small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="javascript:load_form_div('<?= base_url('billing/ipd/current-admission') ?>','maindiv','Current Admission');">
                            <i class="bi bi-arrow-left me-1"></i> Current Admissions
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm" onclick="javascript:load_form('<?= base_url('billing/patient') ?>','Patient Registration');">
                            <i class="bi bi-person-plus me-1"></i> New Patient Registration
                        </button>
                    </div>
                </div>

                <div class="card-body p-4">
                    <!-- Admission Category Switcher -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark small text-uppercase" style="letter-spacing:0.5px;">1. Select Primary Admission Type:</label>
                        <div class="d-flex flex-wrap gap-3" id="admitTypePillGroup">
                            <div class="form-check form-check-inline p-0 m-0">
                                <input class="btn-check" type="radio" name="admit_target_type" id="type_emergency" value="emergency" <?= ($defaultType === 'emergency') ? 'checked' : '' ?>>
                                <label class="btn btn-outline-danger px-3 py-2 fw-semibold" for="type_emergency">
                                    <i class="bi bi-ambulance me-2"></i>Emergency / Casualty
                                </label>
                            </div>
                            <div class="form-check form-check-inline p-0 m-0">
                                <input class="btn-check" type="radio" name="admit_target_type" id="type_daycare" value="daycare" <?= ($defaultType === 'daycare') ? 'checked' : '' ?>>
                                <label class="btn btn-outline-warning text-dark px-3 py-2 fw-semibold" for="type_daycare">
                                    <i class="bi bi-clock-history me-2"></i>Day Care Unit (&lt; 24 Hrs)
                                </label>
                            </div>
                            <div class="form-check form-check-inline p-0 m-0">
                                <input class="btn-check" type="radio" name="admit_target_type" id="type_ipd" value="ipd" <?= ($defaultType === 'ipd') ? 'checked' : '' ?>>
                                <label class="btn btn-outline-primary px-3 py-2 fw-semibold" for="type_ipd">
                                    <i class="bi bi-hospital me-2"></i>Regular Inpatient (IPD)
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Search Input Box -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark small text-uppercase" style="letter-spacing:0.5px;">2. Search Registered Patient:</label>
                        <div class="input-group input-group-lg shadow-sm" style="border-radius:10px; overflow:hidden;">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search fs-5"></i></span>
                            <input type="text" id="patientAdmitSearchInput" class="form-control border-start-0" placeholder="Type patient name, UHID / Patient Code (e.g. 24090001), or mobile number..." autofocus autocomplete="off">
                            <button class="btn btn-primary px-4 fw-semibold" type="button" id="btnSearchAdmit">Search</button>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 px-1">
                            <span class="text-muted small"><i class="bi bi-info-circle me-1"></i>Start typing at least 2 characters to auto-search</span>
                            <a href="javascript:load_form('<?= base_url('billing/patient') ?>','Patient Registration');" class="small text-decoration-none fw-semibold">
                                + Patient not registered yet? Register New Patient &rarr;
                            </a>
                        </div>
                    </div>

                    <!-- Search Loading Indicator -->
                    <div id="admitSearchLoading" class="text-center py-4" style="display:none;">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted small">Searching patient database...</p>
                    </div>

                    <!-- Search Results Area -->
                    <div id="admitSearchResultsWrapper">
                        <div class="text-center py-5 text-muted border rounded-3 bg-light" id="admitEmptyNotice">
                            <i class="bi bi-person-badge fs-1 text-secondary opacity-50 d-block mb-2"></i>
                            <h6 class="fw-semibold text-secondary">Search for a patient to begin admission</h6>
                            <p class="small mb-0">Enter a name, patient code, or phone number in the search bar above.</p>
                        </div>

                        <div class="table-responsive d-none" id="admitResultsTableWrapper">
                            <table class="table table-hover align-middle border">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:130px;">UHID / Code</th>
                                        <th>Patient Name</th>
                                        <th>Age / Gender</th>
                                        <th>Mobile</th>
                                        <th>Current Status</th>
                                        <th class="text-end" style="min-width:240px;">Direct Admission Action</th>
                                    </tr>
                                </thead>
                                <tbody id="admitResultsTbody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var searchTimer = null;
    var $input = $('#patientAdmitSearchInput');
    var $loading = $('#admitSearchLoading');
    var $emptyNotice = $('#admitEmptyNotice');
    var $tableWrapper = $('#admitResultsTableWrapper');
    var $tbody = $('#admitResultsTbody');

    function getSelectedType() {
        return $('input[name="admit_target_type"]:checked').val() || 'emergency';
    }

    function executePatientSearch() {
        var q = $.trim($input.val());
        if (q.length < 2) {
            $tableWrapper.addClass('d-none');
            $emptyNotice.removeClass('d-none').html(
                '<i class="bi bi-person-badge fs-1 text-secondary opacity-50 d-block mb-2"></i>' +
                '<h6 class="fw-semibold text-secondary">Search for a patient to begin admission</h6>' +
                '<p class="small mb-0">Enter at least 2 characters to find patient records.</p>'
            );
            return;
        }

        $loading.show();
        $tableWrapper.addClass('d-none');
        $emptyNotice.addClass('d-none');

        $.ajax({
            url: '<?= base_url('billing/ipd/search-patient-admit') ?>',
            method: 'GET',
            data: { q: q },
            dataType: 'json'
        }).done(function(data) {
            $loading.hide();
            if (!data || data.length === 0) {
                $tableWrapper.addClass('d-none');
                $emptyNotice.removeClass('d-none').html(
                    '<i class="bi bi-exclamation-circle fs-1 text-warning d-block mb-2"></i>' +
                    '<h6 class="fw-semibold text-dark">No patient found matching "' + $('<div>').text(q).html() + '"</h6>' +
                    '<p class="small text-muted mb-3">Check the spelling or register this patient first.</p>' +
                    '<button type="button" class="btn btn-sm btn-success fw-semibold" onclick="load_form(\'<?= base_url('billing/patient') ?>\',\'Patient Registration\');">' +
                    '<i class="bi bi-person-plus me-1"></i> Register New Patient Now</button>'
                );
                return;
            }

            renderPatientsTable(data);
        }).fail(function() {
            $loading.hide();
            $emptyNotice.removeClass('d-none').html(
                '<i class="bi bi-x-circle fs-1 text-danger d-block mb-2"></i>' +
                '<h6 class="text-danger fw-semibold">Error communicating with server</h6>' +
                '<p class="small text-muted mb-0">Please try again or contact system support.</p>'
            );
        });
    }

    function renderPatientsTable(patients) {
        $tbody.empty();
        var curType = getSelectedType();

        $.each(patients, function(idx, pt) {
            var activeIpd = pt.active_ipd;
            var isAdmitted = activeIpd && activeIpd.id;
            var tr = $('<tr>');

            // UHID / Code
            var uhidHtml = '<span class="badge bg-light text-dark border font-monospace fs-6">' + (pt.p_code || ('#' + pt.id)) + '</span>';
            tr.append($('<td>').html(uhidHtml));

            // Name
            var nameHtml = '<div class="fw-bold text-dark">' + $('<div>').text(pt.patient_name || 'Unnamed').html() + '</div>';
            tr.append($('<td>').html(nameHtml));

            // Age / Gender
            var ageGender = (pt.age ? pt.age + ' Y' : '-') + ' / ' + (pt.gender_text || '-');
            tr.append($('<td>').text(ageGender));

            // Phone
            var phone = pt.mphone1 || pt.mphone2 || '-';
            tr.append($('<td>').text(phone));

            // Status
            if (isAdmitted) {
                var admTypeBadge = 'badge bg-primary';
                var admLabel = 'Inpatient';
                if (activeIpd.admission_type === 'emergency') {
                    admTypeBadge = 'badge bg-danger';
                    admLabel = 'Casualty';
                } else if (activeIpd.admission_type === 'daycare') {
                    admTypeBadge = 'badge bg-warning text-dark';
                    admLabel = 'Day Care';
                }
                var statusHtml = '<span class="' + admTypeBadge + ' me-1">' + admLabel + '</span>' +
                    '<a href="javascript:load_form(\'<?= base_url('billing/ipd/panel') ?>/' + activeIpd.id + '\',\'IPD Panel\');" class="small fw-semibold text-decoration-none">[' + (activeIpd.ipd_code || ('#' + activeIpd.id)) + '] Panel &rarr;</a>';
                tr.append($('<td>').html(statusHtml));
            } else {
                tr.append($('<td>').html('<span class="badge bg-success-subtle text-success border border-success-subtle">Not Admitted</span>'));
            }

            // Direct Admission Buttons
            var actionTd = $('<td class="text-end">');
            if (isAdmitted) {
                actionTd.html(
                    '<button type="button" class="btn btn-outline-secondary btn-sm" onclick="load_form(\'<?= base_url('billing/ipd/panel') ?>/' + activeIpd.id + '\',\'IPD Panel\');">' +
                    '<i class="bi bi-box-arrow-up-right me-1"></i> Open Active Panel</button>'
                );
            } else {
                var btnGroup = $('<div class="btn-group btn-group-sm">');
                
                // Emergency Button
                var erBtn = $('<button type="button" class="btn btn-danger" title="Admit directly to Emergency / Casualty">')
                    .html('<i class="bi bi-ambulance me-1"></i>Casualty')
                    .on('click', function() {
                        load_form('<?= base_url('IpdNew/addipd') ?>/' + pt.id + '?type=emergency', 'Emergency Admission');
                    });
                
                // Day Care Button
                var dcBtn = $('<button type="button" class="btn btn-warning text-dark" title="Admit directly to Day Care (&lt; 24h)">')
                    .html('<i class="bi bi-clock-history me-1"></i>Day Care')
                    .on('click', function() {
                        load_form('<?= base_url('IpdNew/addipd') ?>/' + pt.id + '?type=daycare', 'Day Care Admission');
                    });
                
                // Regular IPD Button
                var ipdBtn = $('<button type="button" class="btn btn-primary" title="Admit directly to Inpatient IPD">')
                    .html('<i class="bi bi-hospital me-1"></i>IPD')
                    .on('click', function() {
                        load_form('<?= base_url('IpdNew/addipd') ?>/' + pt.id + '?type=ipd', 'IPD Admission');
                    });

                btnGroup.append(erBtn).append(dcBtn).append(ipdBtn);
                actionTd.append(btnGroup);
            }

            tr.append(actionTd);
            $tbody.append(tr);
        });

        $tableWrapper.removeClass('d-none');
        $emptyNotice.addClass('d-none');
    }

    $input.on('keyup input', function(e) {
        if (e.key === 'Enter') {
            clearTimeout(searchTimer);
            executePatientSearch();
            return;
        }
        clearTimeout(searchTimer);
        searchTimer = setTimeout(executePatientSearch, 300);
    });

    $('#btnSearchAdmit').on('click', executePatientSearch);

    $('input[name="admit_target_type"]').on('change', function() {
        var curQ = $.trim($input.val());
        if (curQ.length >= 2) {
            executePatientSearch();
        }
    });

    // Auto-focus search input
    setTimeout(function() {
        $input.trigger('focus');
    }, 150);
});
</script>
